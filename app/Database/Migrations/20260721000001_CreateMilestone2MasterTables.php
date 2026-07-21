<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMilestone2MasterTables extends Migration
{
    public function up()
    {
        // 1. teachers Table
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
            'employee_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'nip' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'nik' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'full_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'normalized_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'title_prefix' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'degree_suffix' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'gender' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'birth_place' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'birth_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'address' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'employment_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'employment_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'hire_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'termination_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'primary_unit_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'photo_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'profile_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'INCOMPLETE',
            ],
            'revision_number' => [
                'type'       => 'INT',
                'constraint' => 11,
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
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'updated_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey('normalized_name');
        $this->forge->addKey('nip');
        $this->forge->addKey('nik');
        $this->forge->addKey('email');
        $this->forge->addKey('phone');
        $this->forge->addKey('primary_unit_id');
        $this->forge->addKey('is_active');
        $this->forge->addForeignKey('primary_unit_id', 'school_units', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('teachers', true);

        // 2. teacher_unit_assignments Table
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'teacher_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'unit_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'academic_period_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'assignment_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50, // HOME_UNIT, TEACHING, ADMINISTRATIVE, SHARED_SERVICE
            ],
            'is_primary' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'valid_from' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'valid_until' => [
                'type' => 'DATE',
                'null' => true,
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
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'updated_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('academic_period_id', 'academic_periods', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('teacher_unit_assignments', true);

        // 3. teacher_identifiers Table
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'teacher_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'identifier_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50, // NIP, NIK, NUPTK, EMPLOYEE_NUMBER, OTHER
            ],
            'identifier_value' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'issuing_authority' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'is_primary' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'is_verified' => [
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
        $this->forge->addUniqueKey(['identifier_type', 'identifier_value']);
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('teacher_identifiers', true);

        // 4. teacher_qualifications Table
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'teacher_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'qualification_level' => [
                'type'       => 'VARCHAR',
                'constraint' => 50, // S1, S2, S3, D3, SMA, etc.
            ],
            'field_of_study' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'institution' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'graduation_year' => [
                'type'       => 'INT',
                'constraint' => 4,
                'null'       => true,
            ],
            'certificate_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'is_highest' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'document_path' => [
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
            'created_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'updated_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('teacher_qualifications', true);

        // 5. room_types Table
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_specialized' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'is_active' => [
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
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('room_types', true);

        // 6. subjects Table
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
                'constraint' => 150,
            ],
            'normalized_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'short_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 50, // WAJIB, PILIHAN, MUATAN_LOKAL, KOKURIKULER, EKSTRAKURIKULER, KEGIATAN_TETAP, OTHER
            ],
            'default_report_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
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
            'default_room_type_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'color_label' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'revision_number' => [
                'type'       => 'INT',
                'constraint' => 11,
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
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'updated_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('normalized_name');
        $this->forge->addForeignKey('default_room_type_id', 'room_types', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('subjects', true);

        // 7. subject_aliases Table
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'subject_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'alias_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'alias_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'normalized_alias' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'source' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'MANUAL',
            ],
            'unit_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'is_active' => [
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
        $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('subject_aliases', true);

        // 8. subject_unit_availability Table
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'subject_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'unit_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'is_available' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'default_category' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'report_name_override' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'notes' => [
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
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'updated_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['subject_id', 'unit_id']);
        $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('subject_unit_availability', true);

        // 9. grade_levels Table
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
            'unit_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'grade_number' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'phase' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'is_active' => [
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
            'created_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'updated_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['unit_id', 'grade_number']);
        $this->forge->addUniqueKey(['unit_id', 'code']);
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('grade_levels', true);

        // 10. rooms Table
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
            'room_type_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'unit_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'capacity' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'location' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'floor' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'shared_between_units' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'facilities_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'revision_number' => [
                'type'       => 'INT',
                'constraint' => 11,
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
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'updated_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('code');
        $this->forge->addForeignKey('room_type_id', 'room_types', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('rooms', true);

        // 11. classrooms Table
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
            ],
            'grade_level_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'major' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'specialization' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'capacity' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'homeroom_teacher_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'default_room_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'ACTIVE',
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'revision_number' => [
                'type'       => 'INT',
                'constraint' => 11,
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
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'updated_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['academic_period_id', 'unit_id', 'code']);
        $this->forge->addForeignKey('academic_period_id', 'academic_periods', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('grade_level_id', 'grade_levels', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('homeroom_teacher_id', 'teachers', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('default_room_id', 'rooms', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('classrooms', true);

        // 12. master_import_batches Table
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
            'import_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50, // TEACHERS, SUBJECTS, CLASSROOMS, ROOMS
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
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'valid_rows' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'warning_rows' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'error_rows' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'applied_rows' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'created_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'applied_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'applied_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'rolled_back_by' => [
                'type'     => 'BIGINT',
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
        $this->forge->createTable('master_import_batches', true);

        // 13. master_import_rows Table
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
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'entity_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'raw_data_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'normalized_data_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'proposed_action' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'INSERT', // INSERT, UPDATE, MERGE, LINK, SKIP, REVIEW
            ],
            'target_entity_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'validation_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
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
        $this->forge->addForeignKey('batch_id', 'master_import_batches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('master_import_rows', true);

        // 14. duplicate_review_groups Table
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
            'entity_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'OPEN', // OPEN, REVIEW_REQUIRED, RESOLVED, DISMISSED
            ],
            'confidence_score' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'match_reasons_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'decision' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'NONE', // MERGE, KEEP_SEPARATE, LINK_ALIAS, REVIEW_LATER, NONE
            ],
            'canonical_entity_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'decision_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'reviewed_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'reviewed_at' => [
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
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->createTable('duplicate_review_groups', true);

        // 15. duplicate_review_members Table
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'group_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'source_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'source_reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'entity_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'snapshot_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('group_id', 'duplicate_review_groups', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('duplicate_review_members', true);
    }

    public function down()
    {
        $this->forge->dropTable('duplicate_review_members', true);
        $this->forge->dropTable('duplicate_review_groups', true);
        $this->forge->dropTable('master_import_rows', true);
        $this->forge->dropTable('master_import_batches', true);
        $this->forge->dropTable('classrooms', true);
        $this->forge->dropTable('rooms', true);
        $this->forge->dropTable('grade_levels', true);
        $this->forge->dropTable('subject_unit_availability', true);
        $this->forge->dropTable('subject_aliases', true);
        $this->forge->dropTable('subjects', true);
        $this->forge->dropTable('room_types', true);
        $this->forge->dropTable('teacher_qualifications', true);
        $this->forge->dropTable('teacher_identifiers', true);
        $this->forge->dropTable('teacher_unit_assignments', true);
        $this->forge->dropTable('teachers', true);
    }
}
