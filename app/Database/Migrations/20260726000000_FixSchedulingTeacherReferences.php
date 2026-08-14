<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Aligns scheduling with the canonical teacher master.
 *
 * Milestone 4 stores teaching_assignments.teacher_id against teachers.id,
 * therefore every downstream scheduling table must use the same identity.
 */
class FixSchedulingTeacherReferences extends Migration
{
    private const REFERENCES = [
        'schedule_requirements'     => ['teacher_id', 'second_teacher_id'],
        'schedule_entries'          => ['teacher_id', 'second_teacher_id'],
        'teacher_availability_rules'=> ['teacher_id'],
        'schedule_candidate_entries'=> ['teacher_id', 'second_teacher_id'],
    ];

    public function up()
    {
        $this->replaceReferences('teachers');
    }

    public function down()
    {
        $this->replaceReferences('users');
    }

    private function replaceReferences(string $referencedTable): void
    {
        $this->db->disableForeignKeyChecks();

        try {
            foreach (self::REFERENCES as $table => $columns) {
                if (! $this->db->tableExists($table)) {
                    continue;
                }

                foreach ($columns as $column) {
                    $constraint = $table . '_' . $column . '_foreign';
                    $existing = $this->db->query(
                        'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS '
                        . 'WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
                        [$table, $constraint, 'FOREIGN KEY']
                    )->getRowArray();
                    if ($existing) {
                        $this->db->query(sprintf(
                            'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
                            $table,
                            $constraint
                        ));
                    }

                    $type = $referencedTable === 'teachers' ? 'BIGINT UNSIGNED' : 'INT UNSIGNED';
                    $nullable = $column === 'second_teacher_id' ? 'NULL DEFAULT NULL' : 'NOT NULL';
                    $this->db->query(sprintf(
                        'ALTER TABLE `%s` MODIFY `%s` %s %s',
                        $table,
                        $column,
                        $type,
                        $nullable
                    ));

                    $deleteRule = $column === 'second_teacher_id' ? 'SET NULL' : 'CASCADE';
                    $this->db->query(sprintf(
                        'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s` (`id`) ON DELETE %s ON UPDATE RESTRICT',
                        $table,
                        $constraint,
                        $column,
                        $referencedTable,
                        $deleteRule
                    ));
                }
            }
        } finally {
            $this->db->enableForeignKeyChecks();
        }
    }
}
