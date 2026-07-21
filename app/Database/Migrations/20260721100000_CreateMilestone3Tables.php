<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMilestone3Tables extends Migration
{
    public function up()
    {
        // 1. curriculum_versions Table
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
            ],
            'academic_period_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'source_reference' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'revision_number' => [
                'type'       => 'INT',
                'default'    => 1,
            ],
            'workflow_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'DRAFT',
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'validated_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'validated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'reviewed_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'reviewed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'approved_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'approved_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'locked_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'locked_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'archived_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'archived_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'previous_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'change_summary' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'updated_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['academic_period_id', 'code']);
        $this->forge->addForeignKey('academic_period_id', 'academic_periods', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('curriculum_versions', true);

        // 2. curriculum_structures Table
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
            ],
            'curriculum_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'unit_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'grade_level_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'classroom_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'subject_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'official_weekly_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'custom_weekly_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'manual_weekly_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'effective_weekly_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
            ],
            'effective_source' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'OFFICIAL',
            ],
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'INTRAKURIKULER',
            ],
            'block_pattern_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'minimum_days' => [
                'type'       => 'TINYINT',
                'null'       => true,
            ],
            'maximum_daily_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'counts_in_report' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'counts_as_teaching_load' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'required_room_type_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'schedule_priority' => [
                'type'       => 'INT',
                'default'    => 0,
            ],
            'adjustment_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'legal_reference' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'ACTIVE',
            ],
            'revision_number' => [
                'type'       => 'INT',
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'updated_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addForeignKey('curriculum_version_id', 'curriculum_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('grade_level_id', 'grade_levels', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('classroom_id', 'classrooms', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('required_room_type_id', 'room_types', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('curriculum_structures', true);

        // 3. curriculum_validation_results Table
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'curriculum_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'curriculum_structure_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'validation_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'severity' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'WARNING',
            ],
            'message' => [
                'type' => 'TEXT',
            ],
            'details_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_resolved' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'resolved_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'resolved_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('curriculum_version_id', 'curriculum_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('curriculum_structure_id', 'curriculum_structures', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('curriculum_validation_results', true);

        // 4. curriculum_revision_history Table
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'curriculum_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'structure_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'revision_number' => [
                'type' => 'INT',
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'before_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'after_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'change_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'actor_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('curriculum_version_id', 'curriculum_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('curriculum_revision_history', true);

        // 5. curriculum_import_batches Table
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
            ],
            'curriculum_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'source_filename' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'source_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'source_mime' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'source_size' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'UPLOADED',
            ],
            'total_rows' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'valid_rows' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'warning_rows' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'error_rows' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'applied_rows' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'created_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'applied_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'applied_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'rolled_back_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'rolled_back_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addForeignKey('curriculum_version_id', 'curriculum_versions', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('curriculum_import_batches', true);

        // 6. curriculum_import_rows Table
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'batch_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'row_number' => [
                'type' => 'INT',
            ],
            'raw_data_json' => [
                'type' => 'LONGTEXT',
            ],
            'normalized_data_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'source_unit' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'source_grade' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'source_classroom' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'source_subject' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'mapped_unit_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'mapped_grade_level_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'mapped_classroom_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'mapped_subject_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'official_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'custom_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'manual_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'effective_source' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'block_pattern_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'proposed_action' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'INSERT',
            ],
            'validation_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'PENDING',
            ],
            'validation_messages_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'admin_decision' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'decision_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('batch_id', 'curriculum_import_batches', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('curriculum_import_rows', true);
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();

        $this->forge->dropTable('curriculum_import_rows', true);
        $this->forge->dropTable('curriculum_import_batches', true);
        $this->forge->dropTable('curriculum_revision_history', true);
        $this->forge->dropTable('curriculum_validation_results', true);
        $this->forge->dropTable('curriculum_structures', true);
        $this->forge->dropTable('curriculum_versions', true);

        $this->db->enableForeignKeyChecks();
    }
}
