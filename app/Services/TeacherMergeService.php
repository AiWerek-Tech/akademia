<?php

namespace App\Services;

use App\Models\TeacherModel;
use App\Models\TeacherUnitAssignmentModel;
use App\Models\TeacherQualificationModel;
use App\Models\TeacherIdentifierModel;
use App\Models\DuplicateReviewGroupModel;
use Config\Database;

class TeacherMergeService
{
    /**
     * Merge duplicate teacher into canonical teacher record with admin chosen values
     */
    public static function merge(
        int $canonicalTeacherId,
        int $duplicateTeacherId,
        array $mergedData,
        ?int $reviewGroupId = null,
        ?string $reason = null
    ): bool {
        if ($canonicalTeacherId === $duplicateTeacherId) {
            throw new \InvalidArgumentException('Target canonical guru dan duplicate guru tidak boleh sama.');
        }

        $db = Database::connect();
        $db->transBegin();

        try {
            $teacherModel = new TeacherModel();
            $canonical = $teacherModel->find($canonicalTeacherId);
            $duplicate = $teacherModel->find($duplicateTeacherId);

            if (!$canonical || !$duplicate) {
                throw new \RuntimeException('Record guru yang akan di-merge tidak ditemukan.');
            }

            // 1. Prepare merged fields for canonical teacher
            $updatePayload = [
                'full_name'         => $mergedData['full_name'] ?? $canonical['full_name'],
                'normalized_name'   => TeacherDuplicateDetectionService::normalizeName($mergedData['full_name'] ?? $canonical['full_name']),
                'nip'               => !empty($mergedData['nip']) ? $mergedData['nip'] : $canonical['nip'],
                'nik'               => !empty($mergedData['nik']) ? $mergedData['nik'] : $canonical['nik'],
                'employee_number'   => !empty($mergedData['employee_number']) ? $mergedData['employee_number'] : $canonical['employee_number'],
                'gender'            => !empty($mergedData['gender']) ? $mergedData['gender'] : $canonical['gender'],
                'phone'             => !empty($mergedData['phone']) ? $mergedData['phone'] : $canonical['phone'],
                'email'             => !empty($mergedData['email']) ? $mergedData['email'] : $canonical['email'],
                'address'           => !empty($mergedData['address']) ? $mergedData['address'] : $canonical['address'],
                'employment_status' => !empty($mergedData['employment_status']) ? $mergedData['employment_status'] : $canonical['employment_status'],
                'primary_unit_id'   => !empty($mergedData['primary_unit_id']) ? $mergedData['primary_unit_id'] : $canonical['primary_unit_id'],
                'revision_number'   => ($canonical['revision_number'] ?? 1) + 1,
                'updated_at'        => date('Y-m-d H:i:s'),
                'updated_by'        => session()->get('user_id'),
            ];

            $teacherModel->update($canonicalTeacherId, $updatePayload);

            // 2. Re-assign unit assignments from duplicate to canonical
            $unitAssignmentModel = new TeacherUnitAssignmentModel();
            $dupAssignments = $unitAssignmentModel->where('teacher_id', $duplicateTeacherId)->findAll();
            foreach ($dupAssignments as $assign) {
                $existing = $unitAssignmentModel
                    ->where('teacher_id', $canonicalTeacherId)
                    ->where('unit_id', $assign['unit_id'])
                    ->first();
                if (!$existing) {
                    $unitAssignmentModel->update($assign['id'], ['teacher_id' => $canonicalTeacherId]);
                }
            }

            // 3. Re-assign qualifications
            $qualificationModel = new TeacherQualificationModel();
            $dupQualifications = $qualificationModel->where('teacher_id', $duplicateTeacherId)->findAll();
            foreach ($dupQualifications as $qual) {
                $qualificationModel->update($qual['id'], ['teacher_id' => $canonicalTeacherId]);
            }

            // 4. Soft-archive duplicate teacher
            $teacherModel->update($duplicateTeacherId, [
                'is_active'       => 0,
                'profile_status'  => 'ARCHIVED',
                'notes'           => trim(($duplicate['notes'] ?? '') . "\n[MERGED] Digabungkan ke guru ID #" . $canonicalTeacherId . ' pada ' . date('Y-m-d H:i:s')),
                'deleted_at'      => date('Y-m-d H:i:s'),
                'updated_by'      => session()->get('user_id'),
            ]);

            // 5. Update Review Group status if provided
            if ($reviewGroupId !== null) {
                $groupModel = new DuplicateReviewGroupModel();
                $groupModel->update($reviewGroupId, [
                    'status'              => 'RESOLVED',
                    'decision'            => 'MERGE',
                    'canonical_entity_id' => $canonicalTeacherId,
                    'decision_reason'     => $reason ?? 'Merged via admin side-by-side review',
                    'reviewed_by'         => session()->get('user_id'),
                    'reviewed_at'         => date('Y-m-d H:i:s'),
                ]);
            }

            // Audit
            AuditService::log(
                'teachers',
                'MERGE',
                'Teacher',
                $canonicalTeacherId,
                ['canonical' => $canonical, 'duplicate' => $duplicate],
                ['canonical_updated' => $updatePayload],
                $reason ?? 'Merge duplicate teacher record'
            );

            $db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Teacher merge failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
