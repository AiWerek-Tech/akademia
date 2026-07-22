<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCoreTables extends Migration
{
    public function up()
    {
        // 1. school_units Table
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
                'constraint' => 20,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'short_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'level' => [
                'type'       => 'VARCHAR',
                'constraint' => 10, // SMP, SMA
            ],
            'npsn' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'address' => [
                'type'       => 'TEXT',
                'null'       => true,
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
            'logo_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'timezone' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'default'    => 'Asia/Jayapura',
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
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('school_units', true);

        // 2. academic_years Table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 20, // '2026/2027'
            ],
            'start_date' => [
                'type' => 'DATE',
            ],
            'end_date' => [
                'type' => 'DATE',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'DRAFT',
            ],
            'is_active' => [
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
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('academic_years', true);

        // 3. academic_periods Table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
            ],
            'academic_year_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'semester_number' => [
                'type'       => 'TINYINT',
                'constraint' => 1, // 1 or 2
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'start_date' => [
                'type' => 'DATE',
            ],
            'end_date' => [
                'type' => 'DATE',
            ],
            'workflow_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'DRAFT',
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
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
            'revision_number' => [
                'type'       => 'INT',
                'default'    => 1,
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
        $this->forge->addUniqueKey(['academic_year_id', 'semester_number']);
        $this->forge->addForeignKey('academic_year_id', 'academic_years', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('academic_periods', true);

        // 4. users Table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
            ],
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'full_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'password_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'must_change_password' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'failed_login_count' => [
                'type'       => 'INT',
                'default'    => 0,
            ],
            'locked_until' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_login_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_login_ip' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'password_changed_at' => [
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
        $this->forge->addUniqueKey('username');
        $this->forge->addUniqueKey('email'); // Handle null behavior at query validation level
        $this->forge->createTable('users', true);

        // 5. roles Table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'scope' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'GLOBAL',
            ],
            'is_system' => [
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
        $this->forge->createTable('roles', true);

        // 6. permissions Table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'module' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
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
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('permissions', true);

        // 7. user_roles Table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'role_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'unit_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'valid_from' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'valid_until' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'assigned_by' => [
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
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addUniqueKey(['user_id', 'role_id', 'unit_id']);
        $this->forge->createTable('user_roles', true);

        // 8. role_permissions Table
        $this->forge->addField([
            'role_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'permission_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey(['role_id', 'permission_id'], true);
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('permission_id', 'permissions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('role_permissions', true);

        // 9. user_unit_access Table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'unit_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'access_level' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'MEMBER',
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
            'created_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addUniqueKey(['user_id', 'unit_id']);
        $this->forge->createTable('user_unit_access', true);

        // 10. login_attempts Table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
            ],
            'user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'successful' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
            ],
            'failure_reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'attempted_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('login_attempts', true);
        
        // Add indexes manually for login attempts
        $loginAttemptsTable = $this->db->prefixTable('login_attempts');
        try {
            $this->db->query("CREATE INDEX idx_login_attempts_username_time ON {$loginAttemptsTable} (username, attempted_at)");
            $this->db->query("CREATE INDEX idx_login_attempts_ip_time ON {$loginAttemptsTable} (ip_address, attempted_at)");
        } catch (\Throwable $e) {
            // Index already exists
        }

        // 11. feature_flags Table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'enabled' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'environment' => [
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
            'updated_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('feature_flags', true);

        // 12. application_settings Table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'setting_group' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'setting_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'setting_value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'value_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'string',
            ],
            'is_public' => [
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
            'updated_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['setting_group', 'setting_key']);
        $this->forge->createTable('application_settings', true);

        // 13. audit_logs Table
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
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'unit_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'academic_period_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'module' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'entity_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'entity_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'before_json' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'after_json' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
            ],
            'user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'batch_uuid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->createTable('audit_logs', true);

        // 14. activity_logs Table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'event' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('activity_logs', true);
    }

    public function down()
    {
        // Disable foreign key checks to drop cleanly
        $this->db->disableForeignKeyChecks();

        $this->forge->dropTable('activity_logs', true);
        $this->forge->dropTable('audit_logs', true);
        $this->forge->dropTable('application_settings', true);
        $this->forge->dropTable('feature_flags', true);
        $this->forge->dropTable('login_attempts', true);
        $this->forge->dropTable('user_unit_access', true);
        $this->forge->dropTable('role_permissions', true);
        $this->forge->dropTable('user_roles', true);
        $this->forge->dropTable('permissions', true);
        $this->forge->dropTable('roles', true);
        $this->forge->dropTable('users', true);
        $this->forge->dropTable('academic_periods', true);
        $this->forge->dropTable('academic_years', true);
        $this->forge->dropTable('school_units', true);

        $this->db->enableForeignKeyChecks();
    }
}
