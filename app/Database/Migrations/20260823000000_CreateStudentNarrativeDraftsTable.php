<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creates student_narrative_drafts table for persisting human-in-the-loop report card narrative drafts.
 */
class CreateStudentNarrativeDraftsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                 => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'student_id'         => ['type' => 'BIGINT', 'unsigned' => true],
            'subject_id'         => ['type' => 'BIGINT', 'unsigned' => true],
            'classroom_id'       => ['type' => 'BIGINT', 'unsigned' => true],
            'academic_period_id' => ['type' => 'INT', 'unsigned' => true],
            'narrative_text'     => ['type' => 'TEXT'],
            'tone'               => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'STANDARD'],
            'created_by'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['student_id', 'subject_id', 'classroom_id', 'academic_period_id'], 'uq_student_narrative_draft');
        $this->forge->addKey(['classroom_id', 'subject_id']);
        $this->forge->addKey('academic_period_id');
        $this->forge->addForeignKey('student_id', 'elective_students', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('classroom_id', 'classrooms', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('academic_period_id', 'academic_periods', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('student_narrative_drafts', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('student_narrative_drafts', true);
    }
}
