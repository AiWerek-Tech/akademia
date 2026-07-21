<?php

namespace App\Services;

use App\Models\ClassroomModel;
use App\Models\AcademicPeriodModel;
use Config\Database;

class ClassroomService
{
    /**
     * Get classrooms list with unit and period context
     */
    public static function getClassrooms(array $filters = [], int $perPage = 20): array
    {
        $model = new ClassroomModel();
        $builder = $model->select('classrooms.*, grade_levels.code as grade_code, grade_levels.name as grade_name, grade_levels.phase as grade_phase, rooms.code as room_code, rooms.name as room_name, teachers.full_name as homeroom_teacher_name, school_units.code as unit_code, academic_periods.name as period_name')
            ->join('grade_levels', 'grade_levels.id = classrooms.grade_level_id')
            ->join('school_units', 'school_units.id = classrooms.unit_id')
            ->join('academic_periods', 'academic_periods.id = classrooms.academic_period_id')
            ->join('rooms', 'rooms.id = classrooms.default_room_id', 'left')
            ->join('teachers', 'teachers.id = classrooms.homeroom_teacher_id', 'left')
            ->where('classrooms.deleted_at IS NULL');

        if (!empty($filters['unit_id'])) {
            $builder->where('classrooms.unit_id', $filters['unit_id']);
        }

        if (!empty($filters['academic_period_id'])) {
            $builder->where('classrooms.academic_period_id', $filters['academic_period_id']);
        }

        if (!empty($filters['grade_level_id'])) {
            $builder->where('classrooms.grade_level_id', $filters['grade_level_id']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $builder->where('classrooms.is_active', (int)$filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $builder->groupStart()
                ->like('classrooms.code', $search)
                ->orLike('classrooms.name', $search)
                ->orLike('classrooms.major', $search)
                ->groupEnd();
        }

        $classrooms = $builder->orderBy('grade_levels.sort_order', 'ASC')
            ->orderBy('classrooms.code', 'ASC')
            ->paginate($perPage);

        return [
            'data'  => $classrooms,
            'pager' => $model->pager,
        ];
    }

    /**
     * Create classroom record
     */
    public static function createClassroom(array $data): array
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $model = new ClassroomModel();
            $code = strtoupper(trim($data['code']));

            // Check code uniqueness per academic_period_id + unit_id
            $existing = $model->where('academic_period_id', $data['academic_period_id'])
                ->where('unit_id', $data['unit_id'])
                ->where('code', $code)
                ->where('deleted_at IS NULL')
                ->first();

            if ($existing) {
                throw new \InvalidArgumentException('Kode kelas ' . $code . ' sudah ada untuk unit dan periode ini.');
            }

            $data['code']            = $code;
            $data['status']          = $data['status'] ?? 'ACTIVE';
            $data['is_active']       = isset($data['is_active']) ? (int)$data['is_active'] : 1;
            $data['revision_number'] = 1;
            $data['created_by']      = session()->get('user_id');

            $id = $model->insert($data);
            $classroom = $model->find($id);

            AuditService::log('classrooms', 'CREATE', 'Classroom', $id, null, $classroom, 'Create new classroom');

            $db->transCommit();
            return $classroom;
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Create classroom failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update classroom record with Optimistic Locking check
     */
    public static function updateClassroom(string $uuid, array $data): array
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $model = new ClassroomModel();
            $existing = $model->where('uuid', $uuid)->where('deleted_at IS NULL')->first();

            if (!$existing) {
                throw new \RuntimeException('Kelas/Rombel tidak ditemukan.');
            }

            // Optimistic locking
            if (isset($data['revision_number']) && (int)$data['revision_number'] !== (int)$existing['revision_number']) {
                throw new \RuntimeException('Stale Data Error: Kelas/Rombel ini telah diperbarui oleh pengguna lain.');
            }

            if (!empty($data['code'])) {
                $code = strtoupper(trim($data['code']));
                if ($code !== $existing['code']) {
                    $dup = $model->where('academic_period_id', $existing['academic_period_id'])
                        ->where('unit_id', $existing['unit_id'])
                        ->where('code', $code)
                        ->where('id !=', $existing['id'])
                        ->where('deleted_at IS NULL')
                        ->first();
                    if ($dup) {
                        throw new \InvalidArgumentException('Kode kelas ' . $code . ' sudah ada untuk unit dan periode ini.');
                    }
                    $data['code'] = $code;
                }
            }

            $data['revision_number'] = ((int)$existing['revision_number']) + 1;
            $data['updated_by']      = session()->get('user_id');

            $db->table('classrooms')
                ->where('id', $existing['id'])
                ->where('revision_number', $existing['revision_number'])
                ->update($data);
            if ($db->affectedRows() !== 1) {
                throw new \RuntimeException('Data kelas telah diubah oleh pengguna lain. Silakan muat ulang halaman.');
            }
            $updated = $model->find($existing['id']);

            AuditService::log('classrooms', 'UPDATE', 'Classroom', $existing['id'], $existing, $updated, 'Update classroom data');

            $db->transCommit();
            return $updated;
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Update classroom failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Copy classrooms from a source period to a target period (Preview & Apply)
     */
    public static function previewCopyPeriod(int $sourcePeriodId, int $targetPeriodId, int $unitId): array
    {
        $model = new ClassroomModel();

        $sourceClassrooms = $model->select('classrooms.*, grade_levels.code as grade_code, grade_levels.name as grade_name')
            ->join('grade_levels', 'grade_levels.id = classrooms.grade_level_id')
            ->where('classrooms.academic_period_id', $sourcePeriodId)
            ->where('classrooms.unit_id', $unitId)
            ->where('classrooms.deleted_at IS NULL')
            ->findAll();

        $targetClassrooms = $model->where('academic_period_id', $targetPeriodId)
            ->where('unit_id', $unitId)
            ->where('deleted_at IS NULL')
            ->findAll();

        $targetCodes = array_column($targetClassrooms, 'code');

        $preview = [];
        foreach ($sourceClassrooms as $sc) {
            $willSkip = in_array($sc['code'], $targetCodes, true);
            $preview[] = [
                'source'    => $sc,
                'code'      => $sc['code'],
                'name'      => $sc['name'],
                'will_copy' => !$willSkip,
                'reason'    => $willSkip ? 'Kode kelas sudah ada pada periode target' : 'Siap di-copy',
            ];
        }

        return $preview;
    }

    public static function applyCopyPeriod(int $sourcePeriodId, int $targetPeriodId, int $unitId, bool $copyHomeroom = false): array
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $preview = self::previewCopyPeriod($sourcePeriodId, $targetPeriodId, $unitId);
            $model = new ClassroomModel();

            $copiedCount = 0;
            $skippedCount = 0;

            foreach ($preview as $item) {
                if (!$item['will_copy']) {
                    $skippedCount++;
                    continue;
                }

                $sc = $item['source'];
                $model->insert([
                    'academic_period_id'  => $targetPeriodId,
                    'unit_id'             => $unitId,
                    'grade_level_id'      => $sc['grade_level_id'],
                    'code'                => $sc['code'],
                    'name'                => $sc['name'],
                    'major'               => $sc['major'],
                    'specialization'      => $sc['specialization'],
                    'capacity'            => $sc['capacity'],
                    'homeroom_teacher_id' => $copyHomeroom ? $sc['homeroom_teacher_id'] : null,
                    'default_room_id'     => $sc['default_room_id'],
                    'status'              => 'ACTIVE',
                    'is_active'           => 1,
                    'revision_number'     => 1,
                    'created_by'          => session()->get('user_id'),
                ]);
                $copiedCount++;
            }

            AuditService::log(
                'classrooms',
                'COPY_PERIOD',
                'Classroom',
                null,
                ['source_period_id' => $sourcePeriodId, 'unit_id' => $unitId],
                ['target_period_id' => $targetPeriodId, 'copied_count' => $copiedCount, 'skipped_count' => $skippedCount],
                'Copy classrooms between academic periods'
            );

            $db->transCommit();
            return [
                'copied_count'  => $copiedCount,
                'skipped_count' => $skippedCount,
            ];
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Copy period failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
