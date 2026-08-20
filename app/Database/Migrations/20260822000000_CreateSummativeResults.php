<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Phase 6 — Summative Processing.
 *
 * Stores the result of processing mastery records into a final attainment
 * score per student/subject/period according to the school's active reporting
 * policy. The processing flow supports a teacher validation step before the
 * result is considered final (blueprint §8 "Summative Processing →
 * Teacher Validation → Narrative/Grade").
 */
class CreateSummativeResults extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid'                => ['type' => 'CHAR', 'constraint' => 36],
            'unit_id'             => ['type' => 'BIGINT', 'unsigned' => true],
            'academic_period_id'  => ['type' => 'INT', 'unsigned' => true],
            'subject_id'          => ['type' => 'BIGINT', 'unsigned' => true],
            'student_id'          => ['type' => 'BIGINT', 'unsigned' => true],
            'reporting_policy_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'calculation_method'  => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'AVERAGE'],
            'raw_score'           => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'grade_label'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'detail_json'         => ['type' => 'TEXT', 'null' => true],
            'status'              => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'DRAFT'],
            'validated_by'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'validated_at'        => ['type' => 'DATETIME', 'null' => true],
            'created_by'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['unit_id', 'academic_period_id', 'subject_id', 'student_id'], 'uq_summative_student');
        $this->forge->addKey(['academic_period_id', 'subject_id', 'status']);
        $this->forge->addKey('student_id');
        $this->forge->addKey('reporting_policy_id');
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('academic_period_id', 'academic_periods', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('student_id', 'elective_students', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('reporting_policy_id', 'reporting_policies', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('summative_results', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('summative_results', true);
    }
}