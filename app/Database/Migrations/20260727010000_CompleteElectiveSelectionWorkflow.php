<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CompleteElectiveSelectionWorkflow extends Migration
{
    public function up()
    {
        if (! in_array('curriculum_version_id', $this->db->getFieldNames('elective_periods'), true)) {
            $this->forge->addColumn('elective_periods', [
                'curriculum_version_id' => [
                    'type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'academic_year_id',
                ],
            ]);
            $this->db->query(
                'ALTER TABLE `elective_periods` ADD CONSTRAINT `elective_periods_curriculum_version_id_foreign` '
                . 'FOREIGN KEY (`curriculum_version_id`) REFERENCES `curriculum_versions` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT'
            );
        }

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'unit_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'academic_year_id' => ['type' => 'INT', 'unsigned' => true],
            'student_number' => ['type' => 'VARCHAR', 'constraint' => 40],
            'full_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'current_grade' => ['type' => 'TINYINT', 'constraint' => 2],
            'classroom_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['unit_id', 'academic_year_id', 'student_number'], 'uq_elective_student_number');
        $this->forge->addUniqueKey(['user_id', 'academic_year_id'], 'uq_elective_student_user');
        $this->forge->addKey(['unit_id', 'academic_year_id', 'current_grade']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('academic_year_id', 'academic_years', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('classroom_id', 'classrooms', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('elective_students', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'elective_period_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'student_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'career_plan' => ['type' => 'TEXT', 'null' => true],
            'intended_major' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'selection_reason' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'DRAFT'],
            'submitted_at' => ['type' => 'DATETIME', 'null' => true],
            'bk_reviewed_at' => ['type' => 'DATETIME', 'null' => true],
            'curriculum_approved_at' => ['type' => 'DATETIME', 'null' => true],
            'finalized_at' => ['type' => 'DATETIME', 'null' => true],
            'revision_number' => ['type' => 'INT', 'default' => 1],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['elective_period_id', 'student_id'], 'uq_elective_submission');
        $this->forge->addKey(['elective_period_id', 'status']);
        $this->forge->addForeignKey('elective_period_id', 'elective_periods', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('student_id', 'elective_students', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('student_elective_submissions', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'submission_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'offering_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'choice_type' => ['type' => 'VARCHAR', 'constraint' => 10],
            'priority_order' => ['type' => 'TINYINT', 'constraint' => 1],
            'allocation_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'PENDING'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['submission_id', 'offering_id'], 'uq_elective_choice_offering');
        $this->forge->addUniqueKey(['submission_id', 'choice_type', 'priority_order'], 'uq_elective_choice_priority');
        $this->forge->addForeignKey('submission_id', 'student_elective_submissions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('offering_id', 'elective_offerings', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('student_elective_choices', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'submission_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'review_type' => ['type' => 'VARCHAR', 'constraint' => 20],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'reviewed_by' => ['type' => 'INT', 'unsigned' => true],
            'reviewed_at' => ['type' => 'DATETIME'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['submission_id', 'review_type'], 'uq_elective_review_type');
        $this->forge->addForeignKey('submission_id', 'student_elective_submissions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('reviewed_by', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('student_elective_reviews', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'submission_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'reason' => ['type' => 'TEXT'],
            'proposed_choices_json' => ['type' => 'TEXT'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'PENDING'],
            'requested_at' => ['type' => 'DATETIME'],
            'reviewed_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'review_notes' => ['type' => 'TEXT', 'null' => true],
            'reviewed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['submission_id', 'status']);
        $this->forge->addForeignKey('submission_id', 'student_elective_submissions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('reviewed_by', 'users', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('student_elective_change_requests', true);
    }

    public function down()
    {
        $this->forge->dropTable('student_elective_change_requests', true);
        $this->forge->dropTable('student_elective_reviews', true);
        $this->forge->dropTable('student_elective_choices', true);
        $this->forge->dropTable('student_elective_submissions', true);
        $this->forge->dropTable('elective_students', true);
        if (in_array('curriculum_version_id', $this->db->getFieldNames('elective_periods'), true)) {
            $this->db->query('ALTER TABLE `elective_periods` DROP FOREIGN KEY `elective_periods_curriculum_version_id_foreign`');
            $this->forge->dropColumn('elective_periods', 'curriculum_version_id');
        }
    }
}
