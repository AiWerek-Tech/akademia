<?php

namespace App\Services;

use App\Models\ScheduleRevisionHistoryModel;
use App\Models\ScheduleVersionModel;
use Config\Database;

class ScheduleWorkflowService
{
    private ScheduleVersionModel $versionModel;
    private ScheduleRevisionHistoryModel $historyModel;
    private ScheduleConflictDetectionService $conflictService;
    private $db;

    public function __construct()
    {
        $this->versionModel    = new ScheduleVersionModel();
        $this->historyModel    = new ScheduleRevisionHistoryModel();
        $this->conflictService = new ScheduleConflictDetectionService();
        $this->db              = Database::connect();
    }

    public function transitionState(int $versionId, string $targetState, int $userId, int $currentRevisionNumber, ?string $summary = null): array
    {
        $version = $this->versionModel->find($versionId);
        if (!$version) {
            throw new \RuntimeException("Schedule version ID {$versionId} not found.");
        }

        $currentState = (string)$version['workflow_status'];
        $validTransitions = [
            'DRAFT'     => ['VALIDATED', 'REJECTED'],
            'VALIDATED' => ['REVIEWED', 'DRAFT', 'REJECTED'],
            'REVIEWED'  => ['APPROVED', 'DRAFT', 'REJECTED'],
            'APPROVED'  => ['LOCKED', 'DRAFT'],
            'LOCKED'    => ['ARCHIVED', 'APPROVED'],
            'REJECTED'  => ['DRAFT'],
            'ARCHIVED'  => [],
        ];

        if (!in_array($targetState, $validTransitions[$currentState] ?? [], true)) {
            throw new \InvalidArgumentException("Invalid workflow transition from {$currentState} to {$targetState}.");
        }

        if ($targetState === 'VALIDATED') {
            $conflictResult = $this->conflictService->detectConflicts($versionId);
            if ($conflictResult['critical_conflicts'] > 0) {
                throw new \RuntimeException("Cannot validate schedule version. Found {$conflictResult['critical_conflicts']} critical conflicts.");
            }
            $unmetHours = count(array_filter($conflictResult['conflicts'], static fn(array $conflict): bool => ($conflict['conflict_type'] ?? '') === 'UNMET_HOURS'));
            if ($unmetHours > 0) {
                throw new \RuntimeException("Jadwal belum lengkap. {$unmetHours} kebutuhan jam mengajar belum terpenuhi.");
            }
        }

        $now = date('Y-m-d H:i:s');
        $updateData = [
            'workflow_status' => $targetState,
            'revision_number' => $currentRevisionNumber + 1,
            'updated_at'      => $now,
            'updated_by'      => $userId,
        ];

        if ($summary !== null) {
            $updateData['change_summary'] = $summary;
        }

        switch ($targetState) {
            case 'VALIDATED':
                $updateData['validated_by'] = $userId;
                $updateData['validated_at'] = $now;
                break;
            case 'REVIEWED':
                $updateData['reviewed_by'] = $userId;
                $updateData['reviewed_at'] = $now;
                break;
            case 'APPROVED':
                $updateData['approved_by'] = $userId;
                $updateData['approved_at'] = $now;
                $updateData['is_active']   = 1;
                break;
            case 'LOCKED':
                $updateData['locked_by'] = $userId;
                $updateData['locked_at'] = $now;
                break;
            case 'ARCHIVED':
                $updateData['archived_by'] = $userId;
                $updateData['archived_at'] = $now;
                $updateData['is_active']   = 0;
                break;
        }

        $this->db->transException(true)->transBegin();
        try {
            $this->db->table('schedule_versions')
                ->where('id', $versionId)
                ->where('revision_number', $currentRevisionNumber)
                ->where('workflow_status', $currentState)
                ->update($updateData);

            if ($this->db->affectedRows() !== 1) {
                throw new \RuntimeException('Jadwal telah diubah oleh proses lain (revisi/status tidak lagi sama).', 409);
            }

            if (! $this->historyModel->insert([
                'uuid'                => UuidService::v4(),
                'schedule_version_id' => $versionId,
                'revision_number'     => $currentRevisionNumber + 1,
                'action'              => "WORKFLOW_TRANSITION_{$currentState}_TO_{$targetState}",
                'changes_json'        => json_encode($updateData, JSON_THROW_ON_ERROR),
                'performed_by'        => $userId,
                'created_at'          => $now,
            ])) {
                throw new \RuntimeException('Riwayat perubahan workflow gagal disimpan.');
            }
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }

        return [
            'status'         => 'success',
            'previous_state' => $currentState,
            'new_state'      => $targetState,
            'new_revision'   => $currentRevisionNumber + 1,
        ];
    }
}
