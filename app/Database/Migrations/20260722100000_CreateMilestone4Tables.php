<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMilestone4Tables extends Migration
{
    public function up()
    {
        // 1. assignment_versions Table
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
            'curriculum_version_id' => [
                'type'     => 'BIGINT',
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
            'workflow_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'DRAFT', // DRAFT, VALIDATED, REVIEWED, APPROVED, LOCKED, REJECTED, ARCHIVED
            ],
            'revision_number' => [
                'type'       => 'INT',
                'default'    => 1,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
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
        $this->forge->addForeignKey('curriculum_version_id', 'curriculum_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('assignment_versions', true);

        // 2. teaching_assignment_groups Table
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
            'assignment_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'curriculum_structure_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'allocation_mode' => [
                'type'       => 'VARCHAR',
                'constraint' => 30, // SINGLE_TEACHER, SPLIT_HOURS, TEAM_TEACHING
            ],
            'required_weekly_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
            ],
            'allocated_weekly_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
            ],
            'workload_calculation_mode' => [
                'type'       => 'VARCHAR',
                'constraint' => 30, // SAME_AS_ASSIGNED, FULL_FOR_EACH, DIVIDED, MANUAL
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'ACTIVE',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'revision_number' => [
                'type'    => 'INT',
                'default' => 1,
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
        $this->forge->addForeignKey('assignment_version_id', 'assignment_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('curriculum_structure_id', 'curriculum_structures', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('teaching_assignment_groups', true);

        // 3. teaching_assignments Table
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
            'assignment_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'curriculum_structure_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'academic_period_id' => [
                'type'     => 'INT',
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
            ],
            'subject_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'teacher_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'assignment_role' => [
                'type'       => 'VARCHAR',
                'constraint' => 30, // PRIMARY, CO_TEACHER, ASSISTANT, SUBSTITUTE, OTHER
            ],
            'assigned_weekly_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
            ],
            'workload_weekly_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
            ],
            'source_weekly_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
            ],
            'allocation_percentage' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'is_primary_teacher' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'team_group_uuid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'DRAFT', // DRAFT, ACTIVE, INACTIVE, ARCHIVED
            ],
            'revision_number' => [
                'type'    => 'INT',
                'default' => 1,
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
        $this->forge->addForeignKey('assignment_version_id', 'assignment_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('curriculum_structure_id', 'curriculum_structures', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('teaching_assignments', true);

        // 4. additional_duty_types Table
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
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'default_workload_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'maximum_holders' => [
                'type' => 'INT',
                'null' => true,
            ],
            'requires_unit' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'requires_period' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'counts_toward_workload' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'sort_order' => [
                'type'    => 'INT',
                'default' => 0,
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
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('additional_duty_types', true);

        // 5. teacher_additional_duties Table
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
            'assignment_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'academic_period_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'unit_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'teacher_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'duty_type_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'title_override' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'workload_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'valid_from' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'valid_until' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'reference_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
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
                'type'    => 'INT',
                'default' => 1,
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
        $this->forge->addForeignKey('assignment_version_id', 'assignment_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('duty_type_id', 'additional_duty_types', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('teacher_additional_duties', true);

        // 6. workload_policies Table
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
            'unit_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'employment_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'employment_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'teacher_category' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'minimum_teaching_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'maximum_teaching_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'target_total_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'maximum_total_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'additional_duty_cap' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'overload_warning_threshold' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'underload_warning_threshold' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'priority' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'revision_number' => [
                'type'    => 'INT',
                'default' => 1,
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
        $this->forge->addForeignKey('academic_period_id', 'academic_periods', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('workload_policies', true);

        // 7. teacher_workload_snapshots Table
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'assignment_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'teacher_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'academic_period_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'unit_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'teaching_assigned_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
            ],
            'teaching_workload_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
            ],
            'additional_duty_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
            ],
            'total_workload_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
            ],
            'policy_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'policy_minimum' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'policy_target' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'policy_maximum' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'shortage_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
            ],
            'overload_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30, // INCOMPLETE, UNDERLOAD, WITHIN_TARGET, OVERLOAD, NO_POLICY, NEEDS_REVIEW
            ],
            'details_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'calculated_at' => [
                'type' => 'DATETIME',
            ],
            'calculated_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['assignment_version_id', 'teacher_id']);
        $this->forge->addForeignKey('assignment_version_id', 'assignment_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('policy_id', 'workload_policies', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('teacher_workload_snapshots', true);

        // 8. assignment_validation_results Table
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'assignment_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'teaching_assignment_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'teacher_id' => [
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
                'default'    => 'WARNING', // INFO, WARNING, ERROR, BLOCKER
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
        $this->forge->addForeignKey('assignment_version_id', 'assignment_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('assignment_validation_results', true);

        // 9. assignment_revision_history Table
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'assignment_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'entity_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'entity_id' => [
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
        $this->forge->addForeignKey('assignment_version_id', 'assignment_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('assignment_revision_history', true);

        // 10. assignment_import_batches Table
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
            'assignment_version_id' => [
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
        $this->forge->addForeignKey('assignment_version_id', 'assignment_versions', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('assignment_import_batches', true);

        // 11. assignment_import_rows Table
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
            'source_teacher' => [
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
            'mapped_teacher_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'assigned_weekly_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'workload_weekly_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'allocation_mode' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'assignment_role' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
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
        $this->forge->addForeignKey('batch_id', 'assignment_import_batches', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('assignment_import_rows', true);
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();

        $this->forge->dropTable('assignment_import_rows', true);
        $this->forge->dropTable('assignment_import_batches', true);
        $this->forge->dropTable('assignment_revision_history', true);
        $this->forge->dropTable('assignment_validation_results', true);
        $this->forge->dropTable('teacher_workload_snapshots', true);
        $this->forge->dropTable('workload_policies', true);
        $this->forge->dropTable('teacher_additional_duties', true);
        $this->forge->dropTable('additional_duty_types', true);
        $this->forge->dropTable('teaching_assignments', true);
        $this->forge->dropTable('teaching_assignment_groups', true);
        $this->forge->dropTable('assignment_versions', true);

        $this->db->enableForeignKeyChecks();
    }
}
