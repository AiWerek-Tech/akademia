<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddScheduleConflictFingerprint extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('schedule_conflicts')) return;

        $columns = [
            'fingerprint' => "CHAR(64) NULL AFTER uuid",
            'generation_run_id' => "BIGINT UNSIGNED NULL AFTER schedule_version_id",
            'conflict_code' => "VARCHAR(80) NULL AFTER generation_run_id",
            'teacher_id' => "BIGINT UNSIGNED NULL AFTER entity_id",
            'classroom_id' => "BIGINT UNSIGNED NULL AFTER teacher_id",
            'room_id' => "BIGINT UNSIGNED NULL AFTER classroom_id",
            'day_identity' => "VARCHAR(32) NULL AFTER room_id",
            'start_time' => "TIME NULL AFTER day_identity",
            'end_time' => "TIME NULL AFTER start_time",
            'status' => "VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' AFTER end_time",
            'detected_at' => "DATETIME NULL AFTER status",
            'active_generation_scope' => "VARCHAR(64) NOT NULL DEFAULT 'CURRENT' AFTER detected_at",
        ];
        foreach ($columns as $name => $definition) {
            // Use database-side IF NOT EXISTS. CI metadata may be cached during
            // migrate -> rollback -> migrate cycles in the same PHP process.
            $this->db->query("ALTER TABLE schedule_conflicts ADD COLUMN IF NOT EXISTS {$name} {$definition}");
        }

        $rows = $this->db->table('schedule_conflicts')->get()->getResultArray();
        foreach ($rows as $row) {
            $entries = array_values(array_filter(array_map('intval', [
                $row['primary_entry_id'] ?? 0,
                $row['conflicting_entry_id'] ?? 0,
            ])));
            sort($entries, SORT_NUMERIC);
            $canonical = [
                'schedule_version_id' => (int) $row['schedule_version_id'],
                'conflict_code' => (string) ($row['conflict_type'] ?? 'UNKNOWN'),
                'entry_ids' => $entries,
                'entity_type' => (string) ($row['entity_type'] ?? ''),
                'entity_id' => (int) ($row['entity_id'] ?? 0),
                'legacy_id' => (int) $row['id'],
            ];
            $this->db->table('schedule_conflicts')->where('id', $row['id'])->update([
                'fingerprint' => hash('sha256', json_encode($canonical, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
                'conflict_code' => (string) ($row['conflict_type'] ?? 'UNKNOWN'),
                'status' => ! empty($row['is_resolved']) ? 'RESOLVED' : 'ACTIVE',
                'detected_at' => $row['updated_at'] ?? $row['created_at'] ?? date('Y-m-d H:i:s'),
                'active_generation_scope' => 'CURRENT',
            ]);
        }

        $this->db->query('ALTER TABLE schedule_conflicts MODIFY fingerprint CHAR(64) NOT NULL');
        $this->db->query('ALTER TABLE schedule_conflicts ADD UNIQUE KEY IF NOT EXISTS uq_schedule_conflict_fingerprint_scope (schedule_version_id, fingerprint, active_generation_scope)');
        $this->db->query('ALTER TABLE schedule_conflicts ADD KEY IF NOT EXISTS idx_schedule_conflict_status_detected (schedule_version_id, status, detected_at)');
    }

    public function down(): void
    {
        if (! $this->db->tableExists('schedule_conflicts')) return;
        // MariaDB may choose a new composite index to satisfy the existing FK.
        $this->db->query('ALTER TABLE schedule_conflicts ADD KEY IF NOT EXISTS idx_schedule_conflict_version_fk (schedule_version_id)');
        $this->db->query('ALTER TABLE schedule_conflicts DROP INDEX IF EXISTS uq_schedule_conflict_fingerprint_scope');
        $this->db->query('ALTER TABLE schedule_conflicts DROP INDEX IF EXISTS idx_schedule_conflict_status_detected');
        foreach (['fingerprint', 'generation_run_id', 'conflict_code', 'teacher_id', 'classroom_id', 'room_id', 'day_identity', 'start_time', 'end_time', 'status', 'detected_at', 'active_generation_scope'] as $column) {
            $this->db->query("ALTER TABLE schedule_conflicts DROP COLUMN IF EXISTS {$column}");
        }
    }
}
