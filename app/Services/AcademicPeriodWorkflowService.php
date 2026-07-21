<?php

namespace App\Services;

use Config\Database;

class AcademicPeriodWorkflowService
{
    private static array $allowedTransitions = [
        'DRAFT'     => ['VALIDATED', 'ARCHIVED'],
        'VALIDATED' => ['REVIEWED', 'ARCHIVED'],
        'REVIEWED'  => ['APPROVED', 'ARCHIVED'],
        'APPROVED'  => ['LOCKED', 'ARCHIVED'],
        'LOCKED'    => [], // Lock is terminal unless an unlock system is explicitly built (not in scope)
        'ARCHIVED'  => []
    ];

    private static array $transitionPermissions = [
        'VALIDATED' => 'academic_periods.validate',
        'REVIEWED'  => 'academic_periods.review',
        'APPROVED'  => 'academic_periods.approve',
        'LOCKED'    => 'academic_periods.lock',
        'ARCHIVED'  => 'academic_periods.manage',
    ];

    /**
     * Executes a workflow transition with optimistic locking
     */
    public static function transition(int $periodId, string $targetStatus, int $revisionNumber, ?string $notes = null): bool
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            // Get current period record inside transaction
            $period = $db->table('academic_periods')->where('id', $periodId)->get()->getRowArray();
            if (!$period) {
                throw new \RuntimeException('Periode akademik tidak ditemukan.');
            }

            $currentStatus = $period['workflow_status'];

            // 1. Check if transition is allowed
            if (!in_array($targetStatus, self::$allowedTransitions[$currentStatus] ?? [], true)) {
                throw new \RuntimeException("Transisi dari {$currentStatus} ke {$targetStatus} tidak diperbolehkan.");
            }

            // 2. Validate permission
            helper('auth');
            $requiredPermission = self::$transitionPermissions[$targetStatus] ?? null;
            if ($requiredPermission && !has_permission($requiredPermission)) {
                throw new \RuntimeException('Anda tidak memiliki hak akses untuk melakukan aksi ini.');
            }

            // 3. Optimistic Locking Check
            if ((int)$period['revision_number'] !== $revisionNumber) {
                throw new \RuntimeException('Data telah diperbarui oleh pengguna lain. Silakan muat ulang halaman.');
            }

            $userId = session()->get('user_id');

            // Build update data
            $updateData = [
                'workflow_status' => $targetStatus,
                'revision_number' => $period['revision_number'] + 1,
                'updated_at'      => date('Y-m-d H:i:s'),
                'updated_by'      => $userId
            ];

            if ($notes !== null) {
                $updateData['notes'] = $notes;
            }

            // Status specific updates
            if ($targetStatus === 'APPROVED') {
                $updateData['approved_by'] = $userId;
                $updateData['approved_at'] = date('Y-m-d H:i:s');
            } elseif ($targetStatus === 'LOCKED') {
                $updateData['locked_by'] = $userId;
                $updateData['locked_at'] = date('Y-m-d H:i:s');
            }

            // Execute update
            $db->table('academic_periods')->where('id', $periodId)->update($updateData);

            $db->transCommit();

            // Log Audit
            AuditService::log(
                'academic_periods',
                'workflow_transition',
                'AcademicPeriod',
                $periodId,
                ['workflow_status' => $currentStatus, 'revision_number' => $period['revision_number']],
                ['workflow_status' => $targetStatus, 'revision_number' => $updateData['revision_number']],
                "Workflow transition from {$currentStatus} to {$targetStatus}. Notes: {$notes}"
            );

            return true;
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }
}
