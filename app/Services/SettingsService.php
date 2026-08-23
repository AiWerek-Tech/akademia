<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

class SettingsService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    // ====================================================================
    // KEY-VALUE SETTINGS
    // ====================================================================

    /**
     * Get all settings grouped by group_key.
     */
    public function getAllGrouped(): array
    {
        $rows = $this->db->table('system_settings')
            ->orderBy('group_key', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['group_key']][$row['setting_key']] = $this->castValue($row['setting_value'], $row['value_type']);
        }
        return $grouped;
    }

    /**
     * Get a single setting value.
     */
    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $row = $this->db->table('system_settings')
            ->where('group_key', $group)
            ->where('setting_key', $key)
            ->get()->getRowArray();

        if (! $row) return $default;
        return $this->castValue($row['setting_value'], $row['value_type']);
    }

    /**
     * Save multiple settings at once.
     */
    public function saveBulk(array $groupData): void
    {
        $now = date('Y-m-d H:i:s');
        foreach ($groupData as $group => $settings) {
            foreach ($settings as $key => $value) {
                $existing = $this->db->table('system_settings')
                    ->where('group_key', $group)
                    ->where('setting_key', $key)
                    ->get()->getRowArray();

                if ($existing) {
                    $this->db->table('system_settings')
                        ->where('id', $existing['id'])
                        ->update([
                            'setting_value' => (string) $value,
                            'updated_at'    => $now,
                        ]);
                } else {
                    $this->db->table('system_settings')->insert([
                        'group_key'     => $group,
                        'setting_key'   => $key,
                        'setting_value' => (string) $value,
                        'value_type'    => 'string',
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                }
            }
        }
    }

    // ====================================================================
    // SCHOOL PROFILE (per-unit)
    // ====================================================================

    public function getUnitProfile(int $unitId): ?array
    {
        return $this->db->table('school_units')->where('id', $unitId)->get()->getRowArray();
    }

    public function updateUnitProfile(int $unitId, array $data): void
    {
        $this->db->table('school_units')->where('id', $unitId)->update($data);
    }

    // ====================================================================
    // DATABASE MANAGEMENT
    // ====================================================================

    /**
     * List all tables with row counts and sizes.
     */
    public function listTables(): array
    {
        $dbName = $this->db->database;
        $tables = $this->db->query("SHOW TABLE STATUS FROM `{$dbName}`")->getResultArray();

        $result = [];
        foreach ($tables as $t) {
            $result[] = [
                'name'   => $t['Name'],
                'rows'   => (int) $t['Rows'],
                'engine' => $t['Engine'] ?? 'InnoDB',
                'size'   => $this->formatBytes(($t['Data_length'] ?? 0) + ($t['Index_length'] ?? 0)),
                'size_bytes' => (int) (($t['Data_length'] ?? 0) + ($t['Index_length'] ?? 0)),
            ];
        }

        usort($result, fn($a, $b) => $b['size_bytes'] <=> $a['size_bytes']);
        return $result;
    }

    /**
     * Get total database size.
     */
    public function getTotalSize(): string
    {
        $tables = $this->listTables();
        $total = array_sum(array_column($tables, 'size_bytes'));
        return $this->formatBytes($total);
    }

    /**
     * Group tables by module.
     */
    public function getTablesByModule(): array
    {
        $prefixes = [
            'Curriculum & Learning'  => ['regulations', 'curriculum_sources', 'graduate_profile', 'learning_', 'elements', 'subject_', 'unit_', 'bab_', 'concept_', 'activities', 'prerequisites', 'misconceptions', 'resources', 'teacher_guidance'],
            'Digital KSP'           => ['ksp_', 'regulation_', 'evidence'],
            'Lesson Plans & RPP'    => ['lesson_plans', 'lesson_'],
            'Teaching & Attendance'  => ['schedule_', 'attendance', 'teaching_', 'teacher_attendance'],
            'Assessment & Mastery'  => ['assessments', 'criteria', 'rubrics', 'gradebook', 'mastery_', 'summative_', 'interventions', 'evidence_'],
            'Cocurricular & Extra'  => ['cocurricular_', 'extracurricular_', 'program_'],
            'Reporting & Portfolio' => ['report_', 'portfolio_', 'narratives'],
            'Quality & AI'          => ['teacher_reflections', 'supervision_', 'ai_copilot_', 'improvement_'],
            'Users & Auth'          => ['users', 'roles', 'permissions', 'user_', 'login_', 'audit_'],
            'Academic Structure'    => ['school_units', 'academic_', 'subjects', 'teachers', 'classrooms', 'students', 'employees'],
            'Sync & Mobile'         => ['sync_', 'mobile_', 'app_files_'],
            'System & Settings'     => ['system_settings'],
        ];

        $allTables = $this->listTables();
        $grouped = ['Lainnya' => []];

        foreach ($allTables as $t) {
            $matched = false;
            foreach ($prefixes as $module => $patterns) {
                foreach ($patterns as $pat) {
                    if (str_starts_with($t['name'], $pat)) {
                        $grouped[$module][] = $t;
                        $matched = true;
                        break 2;
                    }
                }
            }
            if (! $matched) {
                $grouped['Lainnya'][] = $t;
            }
        }

        // Remove empty groups
        return array_filter($grouped, fn($g) => ! empty($g));
    }

    /**
     * Get columns for a table.
     */
    public function getTableColumns(string $table): array
    {
        return $this->db->getFieldData($table);
    }

    /**
     * Truncate a specific table.
     */
    public function truncateTable(string $table): bool
    {
        try {
            $this->db->table($table)->truncate();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Export table as SQL dump (minimal).
     */
    public function exportTable(string $table): string
    {
        $rows = $this->db->table($table)->get()->getResultArray();
        if (empty($rows)) return "-- Empty table: {$table}\n";

        $columns = array_keys($rows[0]);
        $colList = implode(', ', array_map(fn($c) => "`{$c}`", $columns));

        $sql = "-- Export: {$table}\n";
        $sql .= "TRUNCATE TABLE `{$table}`;\n";
        $sql .= "INSERT INTO `{$table}` ({$colList}) VALUES\n";

        $valueRows = [];
        foreach ($rows as $row) {
            $vals = array_map(fn($v) => $v === null ? 'NULL' : "'" . addslashes($v) . "'", $row);
            $valueRows[] = '(' . implode(', ', $vals) . ')';
        }

        $sql .= implode(",\n", $valueRows) . ";\n";
        return $sql;
    }

    /**
     * Get table sizes summary.
     */
    public function getStorageSummary(): array
    {
        $tables = $this->listTables();
        $totalSize = array_sum(array_column($tables, 'size_bytes'));
        $totalRows = array_sum(array_column($tables, 'rows'));

        return [
            'total_tables' => count($tables),
            'total_rows'   => $totalRows,
            'total_size'   => $this->formatBytes($totalSize),
            'largest_tables' => array_slice($tables, 0, 5),
        ];
    }

    // ====================================================================
    // HELPERS
    // ====================================================================

    private function castValue(string $value, string $type): mixed
    {
        return match ($type) {
            'int'  => (int) $value,
            'bool' => (bool) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $size = (float) $bytes;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 1) . ' ' . $units[$i];
    }
}
