<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateElectivePlanningTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'unit_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'academic_year_id' => ['type' => 'INT', 'unsigned' => true],
            'curriculum_version_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 150],
            'source_grade' => ['type' => 'TINYINT', 'constraint' => 2, 'default' => 10],
            'target_grade' => ['type' => 'TINYINT', 'constraint' => 2, 'default' => 11],
            'selection_start_at' => ['type' => 'DATETIME'],
            'selection_end_at' => ['type' => 'DATETIME'],
            'min_primary_choices' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 4],
            'max_primary_choices' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 5],
            'max_backup_choices' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 2],
            'minimum_subjects_offered' => ['type' => 'TINYINT', 'constraint' => 2, 'default' => 7],
            'allow_changes' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'change_deadline' => ['type' => 'DATE', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'DRAFT'],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'revision_number' => ['type' => 'INT', 'default' => 1],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['unit_id', 'academic_year_id', 'target_grade'], 'uq_elective_period_scope');
        $this->forge->addKey(['unit_id', 'status']);
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('academic_year_id', 'academic_years', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('curriculum_version_id', 'curriculum_versions', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('elective_periods', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'elective_period_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'subject_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'teacher_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'minimum_students' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 1],
            'maximum_students' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 36],
            'weekly_hours' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 5],
            'description' => ['type' => 'TEXT', 'null' => true],
            'study_relevance' => ['type' => 'TEXT', 'null' => true],
            'prerequisites' => ['type' => 'TEXT', 'null' => true],
            'is_open' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'revision_number' => ['type' => 'INT', 'default' => 1],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['elective_period_id', 'subject_id'], 'uq_elective_offering_subject');
        $this->forge->addKey(['elective_period_id', 'is_open']);
        $this->forge->addForeignKey('elective_period_id', 'elective_periods', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('elective_offerings', true);
    }

    public function down()
    {
        $this->forge->dropTable('elective_offerings', true);
        $this->forge->dropTable('elective_periods', true);
    }
}
