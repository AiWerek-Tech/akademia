<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMilestone5Tables extends Migration
{
    public function up()
    {
        // 1. schedule_versions Table
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
            'assignment_version_id' => [
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
                'default'    => 'DRAFT',
            ],
            'revision_number' => [
                'type'    => 'INT',
                'default' => 1,
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
        $this->forge->addForeignKey('assignment_version_id', 'assignment_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('schedule_versions', true);

        // 2. schedule_days Table
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
            'school_unit_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'day_of_week' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
            ],
            'day_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'is_school_day' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
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
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['school_unit_id', 'day_of_week']);
        $this->forge->addForeignKey('school_unit_id', 'school_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('schedule_days', true);

        // 3. schedule_slot_templates Table
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
            'school_unit_id' => [
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
            'is_default' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
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
        $this->forge->addUniqueKey(['school_unit_id', 'code']);
        $this->forge->addForeignKey('school_unit_id', 'school_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('schedule_slot_templates', true);

        // 4. schedule_slot_template_items Table
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
            'slot_template_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'slot_number' => [
                'type' => 'INT',
            ],
            'start_time' => [
                'type' => 'TIME',
            ],
            'end_time' => [
                'type' => 'TIME',
            ],
            'slot_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'LESSON',
            ],
            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
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
        $this->forge->addForeignKey('slot_template_id', 'schedule_slot_templates', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('schedule_slot_template_items', true);

        // 5. schedule_day_slots Table
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
            'schedule_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'day_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'slot_number' => [
                'type' => 'INT',
            ],
            'start_time' => [
                'type' => 'TIME',
            ],
            'end_time' => [
                'type' => 'TIME',
            ],
            'slot_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'LESSON',
            ],
            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
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
        $this->forge->addUniqueKey(['schedule_version_id', 'day_id', 'slot_number']);
        $this->forge->addForeignKey('schedule_version_id', 'schedule_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('day_id', 'schedule_days', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('schedule_day_slots', true);

        // 6. schedule_requirements Table
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
            'schedule_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'teaching_assignment_group_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'teaching_assignment_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
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
            'second_teacher_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'required_weekly_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
            ],
            'consecutive_slots_required' => [
                'type'       => 'TINYINT',
                'constraint' => 2,
                'default'    => 1,
            ],
            'preferred_room_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'required_room_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'is_team_teaching' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
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
        $this->forge->addForeignKey('schedule_version_id', 'schedule_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('teaching_assignment_group_id', 'teaching_assignment_groups', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('teaching_assignment_id', 'teaching_assignments', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('classroom_id', 'classrooms', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('second_teacher_id', 'teachers', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('preferred_room_id', 'rooms', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('schedule_requirements', true);

        // 7. schedule_entries Table
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
            'schedule_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'day_slot_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'schedule_requirement_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'classroom_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'teacher_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'second_teacher_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'subject_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'room_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'is_locked' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
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
        $this->forge->addUniqueKey(['schedule_version_id', 'day_slot_id', 'classroom_id']);
        $this->forge->addKey(['schedule_version_id', 'day_slot_id', 'teacher_id']);
        $this->forge->addKey(['schedule_version_id', 'day_slot_id', 'second_teacher_id']);
        $this->forge->addKey(['schedule_version_id', 'day_slot_id', 'room_id']);
        $this->forge->addForeignKey('schedule_version_id', 'schedule_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('day_slot_id', 'schedule_day_slots', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('schedule_requirement_id', 'schedule_requirements', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('classroom_id', 'classrooms', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('second_teacher_id', 'teachers', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('room_id', 'rooms', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('schedule_entries', true);

        // 8. schedule_fixed_activities Table
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
            'schedule_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'day_slot_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'school_unit_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'classroom_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'activity_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'description' => [
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
        $this->forge->addUniqueKey('uuid');
        $this->forge->addForeignKey('schedule_version_id', 'schedule_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('day_slot_id', 'schedule_day_slots', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('school_unit_id', 'school_units', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('classroom_id', 'classrooms', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('schedule_fixed_activities', true);

        // 9. teacher_availability_rules Table
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
            'teacher_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'academic_period_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'day_of_week' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => true,
            ],
            'slot_number' => [
                'type' => 'INT',
                'null' => true,
            ],
            'availability_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'UNAVAILABLE',
            ],
            'reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
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
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('academic_period_id', 'academic_periods', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('teacher_availability_rules', true);

        // 10. classroom_availability_rules Table
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
            'classroom_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'academic_period_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'day_of_week' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => true,
            ],
            'slot_number' => [
                'type' => 'INT',
                'null' => true,
            ],
            'availability_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'UNAVAILABLE',
            ],
            'reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
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
        $this->forge->addForeignKey('classroom_id', 'classrooms', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('academic_period_id', 'academic_periods', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('classroom_availability_rules', true);

        // 11. room_availability_rules Table
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
            'room_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'academic_period_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'day_of_week' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => true,
            ],
            'slot_number' => [
                'type' => 'INT',
                'null' => true,
            ],
            'availability_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'UNAVAILABLE',
            ],
            'reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
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
        $this->forge->addForeignKey('room_id', 'rooms', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('academic_period_id', 'academic_periods', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('room_availability_rules', true);

        // 12. scheduling_constraints Table
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
            'school_unit_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'constraint_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'HARD',
            ],
            'severity' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'CRITICAL',
            ],
            'weight' => [
                'type'    => 'INT',
                'default' => 10,
            ],
            'is_enabled' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'configuration_json' => [
                'type' => 'JSON',
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
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('code');
        $this->forge->addForeignKey('school_unit_id', 'school_units', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('scheduling_constraints', true);

        // 13. schedule_conflicts Table
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
            'schedule_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'conflict_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'severity' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'HIGH',
            ],
            'description' => [
                'type' => 'TEXT',
            ],
            'entity_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'entity_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'primary_entry_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'conflicting_entry_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'is_resolved' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
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
        $this->forge->addForeignKey('schedule_version_id', 'schedule_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('primary_entry_id', 'schedule_entries', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('conflicting_entry_id', 'schedule_entries', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('schedule_conflicts', true);

        // 14. schedule_generation_runs Table
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
            'schedule_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'generator_strategy' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'DETERMINISTIC_GREEDY',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'RUNNING',
            ],
            'started_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'execution_time_ms' => [
                'type' => 'INT',
                'null' => true,
            ],
            'score' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ],
            'total_requirements' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'placed_requirements' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'unplaced_requirements' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'hard_conflicts_count' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'soft_conflicts_count' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'configuration_json' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'log_output' => [
                'type' => 'LONGTEXT',
                'null' => true,
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
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addForeignKey('schedule_version_id', 'schedule_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('schedule_generation_runs', true);

        // 15. schedule_generation_candidates Table
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
            'generation_run_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'candidate_number' => [
                'type' => 'INT',
            ],
            'score' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            'hard_score' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'soft_score' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'placed_count' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'unplaced_count' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'is_applied' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'applied_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'applied_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
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
        $this->forge->addForeignKey('generation_run_id', 'schedule_generation_runs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('schedule_generation_candidates', true);

        // 16. schedule_candidate_entries Table (Shortened name to fit MySQL 64-char FK limit)
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'candidate_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'day_slot_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'schedule_requirement_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'classroom_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'teacher_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'second_teacher_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'subject_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'room_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'score_contribution' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('candidate_id', 'schedule_generation_candidates', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('day_slot_id', 'schedule_day_slots', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('schedule_requirement_id', 'schedule_requirements', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('classroom_id', 'classrooms', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('second_teacher_id', 'teachers', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('room_id', 'rooms', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('schedule_candidate_entries', true);

        // 17. schedule_locks Table
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
            'schedule_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'lock_target_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'target_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'locked_by' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'locked_at' => [
                'type' => 'DATETIME',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addForeignKey('schedule_version_id', 'schedule_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('locked_by', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('schedule_locks', true);

        // 18. schedule_exceptions Table
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
            'schedule_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'schedule_entry_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'exception_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'reason' => [
                'type' => 'TEXT',
            ],
            'approved_by' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'approved_at' => [
                'type' => 'DATETIME',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addForeignKey('schedule_version_id', 'schedule_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('schedule_entry_id', 'schedule_entries', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('approved_by', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('schedule_exceptions', true);

        // 19. schedule_revision_history Table
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
            'schedule_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'revision_number' => [
                'type' => 'INT',
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'changes_json' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'performed_by' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addForeignKey('schedule_version_id', 'schedule_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('performed_by', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('schedule_revision_history', true);

        // 20. schedule_import_batches Table
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
            'schedule_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'file_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'total_rows' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'valid_rows' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'error_rows' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'STAGED',
            ],
            'applied_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'applied_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'created_by' => [
                'type'     => 'INT',
                'unsigned' => true,
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
        $this->forge->addForeignKey('schedule_version_id', 'schedule_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('schedule_import_batches', true);

        // 21. schedule_import_rows Table
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
                'type' => 'JSON',
            ],
            'parsed_day_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'parsed_slot_number' => [
                'type' => 'INT',
                'null' => true,
            ],
            'parsed_class_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'parsed_teacher_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'parsed_subject_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'parsed_room_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'validation_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'PENDING',
            ],
            'validation_errors_json' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('batch_id', 'schedule_import_batches', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('schedule_import_rows', true);
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();

        $tables = [
            'schedule_import_rows',
            'schedule_import_batches',
            'schedule_revision_history',
            'schedule_exceptions',
            'schedule_locks',
            'schedule_candidate_entries',
            'schedule_generation_candidates',
            'schedule_generation_runs',
            'schedule_conflicts',
            'scheduling_constraints',
            'room_availability_rules',
            'classroom_availability_rules',
            'teacher_availability_rules',
            'schedule_fixed_activities',
            'schedule_entries',
            'schedule_requirements',
            'schedule_day_slots',
            'schedule_slot_template_items',
            'schedule_slot_templates',
            'schedule_days',
            'schedule_versions',
        ];

        foreach ($tables as $table) {
            $this->forge->dropTable($table, true);
        }

        $this->db->enableForeignKeyChecks();
    }
}
