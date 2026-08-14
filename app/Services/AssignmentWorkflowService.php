<?php

namespace App\Services;

use App\Models\AssignmentVersionModel;
use App\Models\TeachingAssignmentModel;
use App\Models\TeacherAdditionalDutyModel;
use App\Models\AssignmentValidationResultModel;
use App\Models\AssignmentRevisionHistoryModel;
use CodeIgniter\Database\Exceptions\DatabaseException;

class AssignmentWorkflowService
{
    /**
     * Transition assignment version status with optimistic locking and validation rules.
     */
    public static function transition(int $versionId, string $targetStatus, int $currentRevision, ?int $actorId = null, ?string $reason = null): bool
    {
        $versionModel = new AssignmentVersionModel();
        $db = \Config\Database::connect();
        
        $db->transBegin();

        $version = $versionModel->find($versionId);
        if (!$version) {
            $db->transRollback();
            throw new \RuntimeException('Assignment version tidak ditemukan.');
        }

        // Optimistic locking check
        if ((int)$version['revision_number'] !== $currentRevision) {
            $db->transRollback();
            throw new \RuntimeException('Data telah diperbarui oleh pengguna lain. Silakan muat ulang halaman.');
        }

        $sourceStatus = $version['workflow_status'];

        // Define valid transitions
        $validTransitions = [
            'DRAFT' => ['VALIDATED', 'REJECTED', 'ARCHIVED'],
            'VALIDATED' => ['REVIEWED', 'REJECTED', 'ARCHIVED'],
            'REVIEWED' => ['APPROVED', 'REJECTED', 'ARCHIVED'],
            'APPROVED' => ['LOCKED', 'ARCHIVED'],
            'REJECTED' => ['DRAFT', 'ARCHIVED'],
            'LOCKED' => ['ARCHIVED'],
            'ARCHIVED' => []
        ];

        if (!in_array($targetStatus, $validTransitions[$sourceStatus] ?? [])) {
            $db->transRollback();
            throw new \RuntimeException("Transisi status dari {$sourceStatus} ke {$targetStatus} tidak valid.");
        }

        // Action validations
        if ($targetStatus === 'VALIDATED') {
            // Run validation check, ensure no BLOCKER results
            $isValid = AssignmentValidationService::validate($versionId, $actorId);
            $validationResultModel = new AssignmentValidationResultModel();
            $blockerCount = $validationResultModel->where('assignment_version_id', $versionId)
                                                  ->where('severity', 'BLOCKER')
                                                  ->countAllResults();
            if ($blockerCount > 0) {
                $db->transRollback();
                throw new \RuntimeException('Gagal memvalidasi: Terdapat error kritis (BLOCKER) yang belum diselesaikan.');
            }
        }

        if ($targetStatus === 'APPROVED') {
            // Run validation, ensure no ERROR or BLOCKER
            $isValid = AssignmentValidationService::validate($versionId, $actorId);
            if (!$isValid) {
                $db->transRollback();
                throw new \RuntimeException('Gagal menyetujui: Terdapat unresolved ERROR atau BLOCKER pada matriks.');
            }

            // Generate/update snapshots for all active teachers
            TeacherWorkloadCalculationService::recalculateAll($versionId, (int)$version['academic_period_id'], null, $actorId);
        }

        if ($targetStatus === 'LOCKED') {
            // Deactivate any other active versions for the same period and curriculum target
            $versionModel->where('academic_period_id', $version['academic_period_id'])
                         ->where('curriculum_version_id', $version['curriculum_version_id'])
                         ->where('id !=', $versionId)
                         ->update(null, ['is_active' => 0]);
        }

        // Set transition fields
        $updateData = [
            'workflow_status' => $targetStatus,
            'revision_number' => $currentRevision + 1,
            'updated_by'      => $actorId,
            'updated_at'      => date('Y-m-d H:i:s')
        ];

        if ($targetStatus === 'LOCKED') {
            $updateData['is_active'] = 1;
            $updateData['locked_by'] = $actorId;
            $updateData['locked_at'] = date('Y-m-d H:i:s');
        }

        if ($targetStatus === 'VALIDATED') {
            $updateData['validated_by'] = $actorId;
            $updateData['validated_at'] = date('Y-m-d H:i:s');
        } elseif ($targetStatus === 'REVIEWED') {
            $updateData['reviewed_by'] = $actorId;
            $updateData['reviewed_at'] = date('Y-m-d H:i:s');
        } elseif ($targetStatus === 'APPROVED') {
            $updateData['approved_by'] = $actorId;
            $updateData['approved_at'] = date('Y-m-d H:i:s');
        } elseif ($targetStatus === 'ARCHIVED') {
            $updateData['archived_by'] = $actorId;
            $updateData['archived_at'] = date('Y-m-d H:i:s');
        }

        $db->table('assignment_versions')
            ->where('id', $versionId)
            ->where('revision_number', $currentRevision)
            ->update($updateData);
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            throw new \RuntimeException('Data telah diperbarui oleh pengguna lain. Silakan muat ulang halaman.');
        }

        // Record history
        $historyModel = new AssignmentRevisionHistoryModel();
        $historyModel->insert([
            'assignment_version_id' => $versionId,
            'entity_type'           => 'assignment_version',
            'entity_id'             => $versionId,
            'revision_number'       => $currentRevision,
            'action'                => "STATUS_CHANGE_{$sourceStatus}_TO_{$targetStatus}",
            'before_json'           => json_encode($version),
            'after_json'            => json_encode(array_merge($version, $updateData)),
            'change_reason'         => $reason,
            'actor_id'              => $actorId,
            'created_at'            => date('Y-m-d H:i:s')
        ]);

        if ($db->transStatus() === false) {
            $db->transRollback();
            return false;
        }

        $db->transCommit();
        return true;
    }

    /**
     * Clone a locked assignment version to create a new revision.
     */
    public static function cloneVersion(int $versionId, string $newCode, string $newName, ?int $actorId = null, ?string $reason = null): int
    {
        $versionModel = new AssignmentVersionModel();
        $assignmentModel = new TeachingAssignmentModel();
        $dutyModel = new TeacherAdditionalDutyModel();
        $historyModel = new AssignmentRevisionHistoryModel();

        $db = \Config\Database::connect();
        $db->transBegin();

        $source = $versionModel->find($versionId);
        if (!$source) {
            $db->transRollback();
            throw new \RuntimeException('Source assignment version tidak ditemukan.');
        }

        // Verify that source is LOCKED or APPROVED to create a revision
        if (!in_array($source['workflow_status'], ['APPROVED', 'LOCKED'])) {
            $db->transRollback();
            throw new \RuntimeException('Hanya versi APPROVED atau LOCKED yang dapat direvisi.');
        }

        // Generate uuid
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        $newUuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

        // Insert new version
        $newVersionId = $versionModel->insert([
            'uuid'                  => $newUuid,
            'academic_period_id'    => $source['academic_period_id'],
            'curriculum_version_id' => $source['curriculum_version_id'],
            'code'                  => $newCode,
            'name'                  => $newName,
            'description'           => $source['description'],
            'workflow_status'       => 'DRAFT',
            'revision_number'       => 1,
            'is_active'             => 0,
            'previous_version_id'   => $versionId,
            'change_summary'        => $reason,
            'created_by'            => $actorId,
            'created_at'            => date('Y-m-d H:i:s'),
            'updated_at'            => date('Y-m-d H:i:s')
        ]);

        // Copy teaching assignments
        $assignments = $assignmentModel->where('assignment_version_id', $versionId)->findAll();
        foreach ($assignments as $a) {
            $aData = $a;
            unset($aData['id']);
            $aData['uuid'] = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
            $aData['assignment_version_id'] = $newVersionId;
            // The version owns the draft workflow state; assignment rows remain
            // active so validation, matrices, and workload calculations can see them.
            $aData['status'] = 'ACTIVE';
            $aData['revision_number'] = 1;
            $aData['created_by'] = $actorId;
            $aData['created_at'] = date('Y-m-d H:i:s');
            $aData['updated_at'] = date('Y-m-d H:i:s');
            unset($aData['deleted_at']);
            $assignmentModel->insert($aData);
        }

        // Copy additional duties
        $duties = $dutyModel->where('assignment_version_id', $versionId)->findAll();
        foreach ($duties as $d) {
            $dData = $d;
            unset($dData['id']);
            $dData['uuid'] = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
            $dData['assignment_version_id'] = $newVersionId;
            $dData['status'] = 'ACTIVE';
            $dData['revision_number'] = 1;
            $dData['created_by'] = $actorId;
            $dData['created_at'] = date('Y-m-d H:i:s');
            $dData['updated_at'] = date('Y-m-d H:i:s');
            unset($dData['deleted_at']);
            $dutyModel->insert($dData);
        }

        // Record history
        $historyModel->insert([
            'assignment_version_id' => $newVersionId,
            'entity_type'           => 'assignment_version',
            'entity_id'             => $newVersionId,
            'revision_number'       => 1,
            'action'                => 'CLONED_FROM_VERSION_' . $versionId,
            'before_json'           => null,
            'after_json'            => json_encode($source),
            'change_reason'         => $reason,
            'actor_id'              => $actorId,
            'created_at'            => date('Y-m-d H:i:s')
        ]);

        if ($db->transStatus() === false) {
            $db->transRollback();
            throw new \RuntimeException('Transkasi cloning gagal.');
        }

        $db->transCommit();
        return $newVersionId;
    }
}
