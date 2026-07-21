<?php

namespace App\Services;

use App\Models\CurriculumVersionModel;
use App\Models\CurriculumValidationResultModel;
use App\Models\CurriculumRevisionHistoryModel;
use App\Services\AuditService;
use Config\Database;

class CurriculumWorkflowService
{
    public const STATUSES = ['DRAFT', 'VALIDATED', 'REVIEWED', 'APPROVED', 'LOCKED', 'ARCHIVED', 'REJECTED'];

    /**
     * Transition curriculum version to a new workflow status
     */
    public static function transition(string $uuid, string $targetStatus, ?string $reason = null, ?int $userId = null): array
    {
        $targetStatus = strtoupper(trim($targetStatus));
        if (!in_array($targetStatus, self::STATUSES, true)) {
            throw new \InvalidArgumentException('Status workflow tidak valid: ' . $targetStatus);
        }

        $userId = $userId ?? session()->get('user_id');
        $db = Database::connect();
        $db->transBegin();

        try {
            $versionModel = new CurriculumVersionModel();
            $version = $versionModel->where('uuid', $uuid)->first();
            if (!$version) {
                throw new \RuntimeException('Versi kurikulum tidak ditemukan.');
            }

            $currentStatus = strtoupper($version['workflow_status']);

            if ($currentStatus === 'LOCKED' && !in_array($targetStatus, ['ARCHIVED'], true)) {
                throw new \RuntimeException('Versi kurikulum ter-LOCK bersifat immutable dan tidak dapat diubah statusnya kecuali ARCHIVED.');
            }

            // Validate transition rules
            switch ($targetStatus) {
                case 'VALIDATED':
                    if (!in_array($currentStatus, ['DRAFT', 'REJECTED'], true)) {
                        throw new \RuntimeException("Transisi dari {$currentStatus} ke VALIDATED tidak diizinkan.");
                    }
                    // Run validation check
                    $valSummary = CurriculumValidationService::validateVersion($version['id']);
                    if ($valSummary['has_blockers']) {
                        throw new \RuntimeException("Versi kurikulum memiliki {$valSummary['errors']} error/blocker unresolved. Selesaikan error terlebih dahulu sebelum ke status VALIDATED.");
                    }
                    $updateData = [
                        'workflow_status' => 'VALIDATED',
                        'validated_by'    => $userId,
                        'validated_at'    => date('Y-m-d H:i:s'),
                    ];
                    break;

                case 'REVIEWED':
                    if (!in_array($currentStatus, ['VALIDATED'], true)) {
                        throw new \RuntimeException("Transisi dari {$currentStatus} ke REVIEWED tidak diizinkan.");
                    }
                    $updateData = [
                        'workflow_status' => 'REVIEWED',
                        'reviewed_by'     => $userId,
                        'reviewed_at'     => date('Y-m-d H:i:s'),
                    ];
                    break;

                case 'APPROVED':
                    if (!in_array($currentStatus, ['REVIEWED', 'VALIDATED'], true)) {
                        throw new \RuntimeException("Transisi dari {$currentStatus} ke APPROVED tidak diizinkan. Harus melalui REVIEWED terlebih dahulu.");
                    }
                    // Verify 0 unresolved ERROR/BLOCKER results
                    $valResultModel = new CurriculumValidationResultModel();
                    $unresolved = $valResultModel->where('curriculum_version_id', $version['id'])
                        ->whereIn('severity', ['ERROR', 'BLOCKER'])
                        ->where('is_resolved', 0)
                        ->countAllResults();

                    if ($unresolved > 0) {
                        throw new \RuntimeException("Versi kurikulum memiliki {$unresolved} error/blocker unresolved. Persetujuan ditolak.");
                    }

                    $updateData = [
                        'workflow_status' => 'APPROVED',
                        'approved_by'     => $userId,
                        'approved_at'     => date('Y-m-d H:i:s'),
                    ];
                    break;

                case 'LOCKED':
                    if (!in_array($currentStatus, ['APPROVED'], true)) {
                        throw new \RuntimeException("Hanya versi berstatus APPROVED yang dapat dikunci (LOCKED). Status saat ini: {$currentStatus}.");
                    }
                    $updateData = [
                        'workflow_status' => 'LOCKED',
                        'locked_by'       => $userId,
                        'locked_at'       => date('Y-m-d H:i:s'),
                    ];
                    break;

                case 'REJECTED':
                    if (in_array($currentStatus, ['APPROVED', 'LOCKED', 'ARCHIVED'], true)) {
                        throw new \RuntimeException("Versi status {$currentStatus} tidak dapat di-REJECT.");
                    }
                    if (empty($reason)) {
                        throw new \RuntimeException('Alasan penolakan (reason) wajib diisi saat menolak versi kurikulum.');
                    }
                    $updateData = [
                        'workflow_status' => 'REJECTED',
                        'change_summary'  => 'Ditolak: ' . $reason,
                    ];
                    break;

                case 'ARCHIVED':
                    $updateData = [
                        'workflow_status' => 'ARCHIVED',
                        'is_active'       => 0,
                        'archived_by'     => $userId,
                        'archived_at'     => date('Y-m-d H:i:s'),
                    ];
                    break;

                default:
                    throw new \InvalidArgumentException('Status target tidak dikenali.');
            }

            $updateData['updated_by'] = $userId;
            $versionModel->update($version['id'], $updateData);

            // Record in Revision History
            $revHistoryModel = new CurriculumRevisionHistoryModel();
            $revHistoryModel->insert([
                'curriculum_version_id' => $version['id'],
                'revision_number'       => $version['revision_number'],
                'action'                => 'WORKFLOW_TRANSITION',
                'before_json'           => json_encode(['workflow_status' => $currentStatus, 'is_active' => $version['is_active']]),
                'after_json'            => json_encode(['workflow_status' => $targetStatus, 'update' => $updateData]),
                'change_reason'         => $reason ?? "Transisi status dari {$currentStatus} ke {$targetStatus}",
                'actor_id'              => $userId,
                'created_at'            => date('Y-m-d H:i:s'),
            ]);

            AuditService::log('curriculum', 'WORKFLOW_TRANSITION', 'CurriculumVersion', $version['id'], ['status' => $currentStatus], ['status' => $targetStatus], "Transisi workflow kurikulum ke {$targetStatus}");

            $db->transCommit();
            return $versionModel->find($version['id']);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Curriculum workflow transition failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Set active version for an academic period (Only 1 active version per period allowed)
     */
    public static function setActiveVersion(string $uuid, ?int $userId = null): array
    {
        $userId = $userId ?? session()->get('user_id');
        $db = Database::connect();
        $db->transBegin();

        try {
            $versionModel = new CurriculumVersionModel();
            $version = $versionModel->where('uuid', $uuid)->first();
            if (!$version) {
                throw new \RuntimeException('Versi kurikulum tidak ditemukan.');
            }

            if (!in_array($version['workflow_status'], ['APPROVED', 'LOCKED'], true)) {
                throw new \RuntimeException('Hanya versi kurikulum yang berstatus APPROVED atau LOCKED yang dapat diaktifkan.');
            }

            // Deactivate all other versions for the same academic period
            $db->table('curriculum_versions')
                ->where('academic_period_id', $version['academic_period_id'])
                ->update(['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => $userId]);

            // Activate target version
            $versionModel->update($version['id'], [
                'is_active'  => 1,
                'updated_by' => $userId,
            ]);

            AuditService::log('curriculum', 'SET_ACTIVE', 'CurriculumVersion', $version['id'], null, ['is_active' => 1], 'Mengaktifkan versi kurikulum');

            $db->transCommit();
            return $versionModel->find($version['id']);
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }
}
