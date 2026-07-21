<?php

namespace App\Services;

use App\Models\TeacherModel;
use App\Models\TeacherUnitAssignmentModel;
use App\Models\TeacherQualificationModel;
use Config\Database;

class TeacherService
{
    /**
     * Get list of teachers with filtering and pagination
     */
    public static function getTeachers(array $filters = [], int $perPage = 15): array
    {
        $teacherModel = new TeacherModel();
        $builder = $teacherModel->where('teachers.deleted_at IS NULL');

        // Unit filter
        if (!empty($filters['unit_id'])) {
            $builder->groupStart()
                ->where('teachers.primary_unit_id', $filters['unit_id'])
                ->orWhereIn('teachers.id', function ($sub) use ($filters) {
                    return $sub->select('teacher_id')
                        ->from('teacher_unit_assignments')
                        ->where('unit_id', $filters['unit_id']);
                })
                ->groupEnd();
        }

        // Status filter
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $builder->where('teachers.is_active', (int)$filters['is_active']);
        }

        // Profile Status filter
        if (!empty($filters['profile_status'])) {
            $builder->where('teachers.profile_status', $filters['profile_status']);
        }

        // Search query
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $builder->groupStart()
                ->like('teachers.full_name', $search)
                ->orLike('teachers.nip', $search)
                ->orLike('teachers.nik', $search)
                ->orLike('teachers.employee_number', $search)
                ->orLike('teachers.email', $search)
                ->orLike('teachers.phone', $search)
                ->groupEnd();
        }

        $teachers = $builder->orderBy('teachers.full_name', 'ASC')->paginate($perPage);

        // Populate completeness for each teacher
        $assignmentModel = new TeacherUnitAssignmentModel();
        $qualModel = new TeacherQualificationModel();

        foreach ($teachers as &$t) {
            $assignments = $assignmentModel->where('teacher_id', $t['id'])->findAll();
            $qualifications = $qualModel->where('teacher_id', $t['id'])->findAll();

            $completeness = TeacherProfileCompletenessService::evaluate($t, $assignments, $qualifications);
            $t['completeness'] = $completeness;
            $t['assignments']  = $assignments;
            $t['qualifications'] = $qualifications;
        }

        return [
            'data'  => $teachers,
            'pager' => $teacherModel->pager,
        ];
    }

    /**
     * Create teacher record with unit assignments & completeness evaluation
     */
    public static function createTeacher(array $data, array $unitIds = []): array
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $teacherModel = new TeacherModel();

            // Prepare normalized fields
            $data['normalized_name'] = TeacherDuplicateDetectionService::normalizeName($data['full_name'] ?? '');
            $data['profile_status']  = 'INCOMPLETE';
            $data['revision_number'] = 1;
            $data['is_active']       = isset($data['is_active']) ? (int)$data['is_active'] : 1;
            $data['created_by']      = session()->get('user_id');

            // Unique NIP / NIK / employee_number check
            if (!empty($data['nip'])) {
                $existing = $teacherModel->where('nip', trim($data['nip']))->where('deleted_at IS NULL')->first();
                if ($existing) {
                    throw new \InvalidArgumentException('NIP ' . $data['nip'] . ' sudah terdaftar pada sistem.');
                }
            }

            if (!empty($data['nik'])) {
                $existing = $teacherModel->where('nik', trim($data['nik']))->where('deleted_at IS NULL')->first();
                if ($existing) {
                    throw new \InvalidArgumentException('NIK ' . $data['nik'] . ' sudah terdaftar pada sistem.');
                }
            }

            // Insert teacher record
            $teacherId = $teacherModel->insert($data);
            $teacher = $teacherModel->find($teacherId);

            // Unit assignments
            $assignmentModel = new TeacherUnitAssignmentModel();
            if (!empty($data['primary_unit_id'])) {
                $assignmentModel->insert([
                    'teacher_id'      => $teacherId,
                    'unit_id'         => $data['primary_unit_id'],
                    'assignment_type' => 'HOME_UNIT',
                    'is_primary'      => 1,
                    'status'          => 'ACTIVE',
                    'created_by'      => session()->get('user_id'),
                ]);
            }

            foreach ($unitIds as $uId) {
                if ((int)$uId !== (int)($data['primary_unit_id'] ?? 0)) {
                    $assignmentModel->insert([
                        'teacher_id'      => $teacherId,
                        'unit_id'         => $uId,
                        'assignment_type' => 'TEACHING',
                        'is_primary'      => 0,
                        'status'          => 'ACTIVE',
                        'created_by'      => session()->get('user_id'),
                    ]);
                }
            }

            // Evaluate profile status
            $assignments = $assignmentModel->where('teacher_id', $teacherId)->findAll();
            $eval = TeacherProfileCompletenessService::evaluate($teacher, $assignments);
            $teacherModel->update($teacherId, ['profile_status' => $eval['status']]);

            // Check duplicate candidates
            $matches = TeacherDuplicateDetectionService::scanForDuplicates($teacher, $teacherId);
            $duplicateGroupUuid = null;
            if (!empty($matches)) {
                $duplicateGroupUuid = TeacherDuplicateDetectionService::recordDuplicateGroup($teacher, $matches);
            }

            AuditService::log('teachers', 'CREATE', 'Teacher', $teacherId, null, $teacher, 'Create new teacher master');

            $db->transCommit();
            return [
                'teacher'              => $teacherModel->find($teacherId),
                'duplicate_matches'    => $matches,
                'duplicate_group_uuid' => $duplicateGroupUuid,
            ];
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Create teacher failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update teacher record with Optimistic Locking verification
     */
    public static function updateTeacher(string $uuid, array $data, array $unitIds = []): array
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $teacherModel = new TeacherModel();
            $existing = $teacherModel->where('uuid', $uuid)->where('deleted_at IS NULL')->first();

            if (!$existing) {
                throw new \RuntimeException('Data guru tidak ditemukan.');
            }

            // Optimistic Locking Check
            if (isset($data['revision_number']) && (int)$data['revision_number'] !== (int)$existing['revision_number']) {
                throw new \RuntimeException('Stale Data Error: Data guru ini telah diperbarui oleh pengguna lain. Silakan muat ulang halaman.');
            }

            $data['normalized_name'] = TeacherDuplicateDetectionService::normalizeName($data['full_name'] ?? $existing['full_name']);
            $data['revision_number'] = ((int)$existing['revision_number']) + 1;
            $data['updated_by']      = session()->get('user_id');

            // Unique check for NIP & NIK
            if (!empty($data['nip']) && $data['nip'] !== $existing['nip']) {
                $dup = $teacherModel->where('nip', trim($data['nip']))->where('id !=', $existing['id'])->where('deleted_at IS NULL')->first();
                if ($dup) {
                    throw new \InvalidArgumentException('NIP ' . $data['nip'] . ' sudah terdaftar pada guru lain.');
                }
            }

            if (!empty($data['nik']) && $data['nik'] !== $existing['nik']) {
                $dup = $teacherModel->where('nik', trim($data['nik']))->where('id !=', $existing['id'])->where('deleted_at IS NULL')->first();
                if ($dup) {
                    throw new \InvalidArgumentException('NIK ' . $data['nik'] . ' sudah terdaftar pada guru lain.');
                }
            }

            $teacherModel->update($existing['id'], $data);

            // Sync unit assignments
            $assignmentModel = new TeacherUnitAssignmentModel();
            $assignmentModel->where('teacher_id', $existing['id'])->delete();

            if (!empty($data['primary_unit_id'])) {
                $assignmentModel->insert([
                    'teacher_id'      => $existing['id'],
                    'unit_id'         => $data['primary_unit_id'],
                    'assignment_type' => 'HOME_UNIT',
                    'is_primary'      => 1,
                    'status'          => 'ACTIVE',
                    'created_by'      => session()->get('user_id'),
                ]);
            }

            foreach ($unitIds as $uId) {
                if ((int)$uId !== (int)($data['primary_unit_id'] ?? 0)) {
                    $assignmentModel->insert([
                        'teacher_id'      => $existing['id'],
                        'unit_id'         => $uId,
                        'assignment_type' => 'TEACHING',
                        'is_primary'      => 0,
                        'status'          => 'ACTIVE',
                        'created_by'      => session()->get('user_id'),
                    ]);
                }
            }

            // Re-evaluate completeness
            $updatedTeacher = $teacherModel->find($existing['id']);
            $assignments = $assignmentModel->where('teacher_id', $existing['id'])->findAll();
            $eval = TeacherProfileCompletenessService::evaluate($updatedTeacher, $assignments);

            if ($updatedTeacher['profile_status'] !== 'VERIFIED') {
                $teacherModel->update($existing['id'], ['profile_status' => $eval['status']]);
            }

            AuditService::log('teachers', 'UPDATE', 'Teacher', $existing['id'], $existing, $updatedTeacher, 'Update teacher master data');

            $db->transCommit();
            return $teacherModel->find($existing['id']);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Update teacher failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify teacher profile completeness status
     */
    public static function verifyTeacher(string $uuid): bool
    {
        $teacherModel = new TeacherModel();
        $teacher = $teacherModel->where('uuid', $uuid)->where('deleted_at IS NULL')->first();

        if (!$teacher) {
            throw new \RuntimeException('Data guru tidak ditemukan.');
        }

        $teacherModel->update($teacher['id'], [
            'profile_status' => 'VERIFIED',
            'updated_by'     => session()->get('user_id'),
        ]);

        AuditService::log('teachers', 'VERIFY', 'Teacher', $teacher['id'], $teacher, ['profile_status' => 'VERIFIED'], 'Verify teacher profile');
        return true;
    }
}
