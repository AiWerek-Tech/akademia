<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTeacherSchedulePresentationFields extends Migration
{
    private const COLORS = [
        '#DBEAFE', '#DCFCE7', '#FEF3C7', '#FCE7F3', '#EDE9FE', '#CFFAFE',
        '#FFEDD5', '#E0E7FF', '#D1FAE5', '#FEE2E2', '#F3E8FF', '#CCFBF1',
    ];

    public function up()
    {
        if (! $this->columnExists('teacher_initial')) {
            $this->db->query(
                'ALTER TABLE teachers ADD COLUMN teacher_initial VARCHAR(10) NULL AFTER degree_suffix'
            );
        }
        if (! $this->columnExists('color_code')) {
            $this->db->query(
                'ALTER TABLE teachers ADD COLUMN color_code VARCHAR(7) NULL AFTER teacher_initial'
            );
        }

        foreach ($this->db->table('teachers')->select('id, full_name, teacher_initial, color_code')->get()->getResultArray() as $teacher) {
            $updates = [];
            if (trim((string) ($teacher['teacher_initial'] ?? '')) === '') {
                $updates['teacher_initial'] = self::initials((string) $teacher['full_name']);
            }
            if (trim((string) ($teacher['color_code'] ?? '')) === '') {
                $updates['color_code'] = self::COLORS[(int) $teacher['id'] % count(self::COLORS)];
            }
            if ($updates !== []) {
                $this->db->table('teachers')->where('id', $teacher['id'])->update($updates);
            }
        }
    }

    public function down()
    {
        foreach (['color_code', 'teacher_initial'] as $column) {
            if ($this->columnExists($column)) {
                $this->forge->dropColumn('teachers', $column);
            }
        }
    }

    private static function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return mb_strtoupper(
            mb_substr((string) ($parts[0] ?? ''), 0, 1)
            . mb_substr((string) ($parts[1] ?? ''), 0, 1)
        );
    }

    private function columnExists(string $column): bool
    {
        return $this->db->query(
            'SELECT 1 FROM information_schema.COLUMNS '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
            ['teachers', $column]
        )->getRowArray() !== null;
    }
}
