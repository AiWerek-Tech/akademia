<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Phase 11 — Universal Sync Engine, Mobile Offline-First Gateway & File Metadata.
 *
 * Implements:
 *   1. sync_version_registry — Tracks per-unit table version numbers for delta sync.
 *   2. mobile_sessions — Token-based mobile device sessions with auto-expiry.
 *   3. app_files_metadata — File metadata registry for mobile & system uploads.
 */
class CreatePhase11UniversalSyncTables extends Migration
{
    private array $permissions = [
        'sync.view'          => ['module' => 'system', 'name' => 'View Sync Registry and Mobile Logs'],
        'sync.manage'        => ['module' => 'system', 'name' => 'Manage Sync Versions and Gateways'],
        'api.mobile_access'  => ['module' => 'system', 'name' => 'Access Mobile Sync API'],
    ];

    public function up(): void
    {
        $this->createVersionRegistry();
        $this->createMobileSessions();
        $this->createFilesMetadata();
        $this->seedPermissions();
    }

    public function down(): void
    {
        $this->removePermissions();
        foreach (['app_files_metadata', 'mobile_sessions', 'sync_version_registry'] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    private function createVersionRegistry(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid'            => ['type' => 'CHAR', 'constraint' => 36],
            'unit_id'         => ['type' => 'BIGINT', 'unsigned' => true],
            'table_name'      => ['type' => 'VARCHAR', 'constraint' => 60],
            'current_version' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'last_updated_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_by'      => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'updated_by'      => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['unit_id', 'table_name']);
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('sync_version_registry', true);
    }

    private function createMobileSessions(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid'            => ['type' => 'CHAR', 'constraint' => 36],
            'user_id'         => ['type' => 'INT', 'unsigned' => true],
            'unit_id'         => ['type' => 'BIGINT', 'unsigned' => true],
            'device_id'       => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'token_hash'      => ['type' => 'VARCHAR', 'constraint' => 64],
            'app_role'        => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'GURU'],
            'client_ip'       => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'last_sync_at'    => ['type' => 'DATETIME', 'null' => true],
            'expires_at'      => ['type' => 'DATETIME'],
            'is_revoked'      => ['type' => 'TINYINT', 'default' => 0],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('token_hash');
        $this->forge->addKey(['user_id', 'is_revoked']);
        $this->forge->addKey('unit_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('mobile_sessions', true);
    }

    private function createFilesMetadata(): void
    {
        $this->forge->addField([
            'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid'                => ['type' => 'CHAR', 'constraint' => 36],
            'unit_id'             => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'uploaded_by_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'file_name'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'original_name'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'file_type'           => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'DOCUMENT'],
            'category'            => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'GENERAL'],
            'drive_file_id'       => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'storage_path'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'public_url'          => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'file_size_kb'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'mime_type'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['unit_id', 'category']);
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('uploaded_by_user_id', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('app_files_metadata', true);
    }

    private function seedPermissions(): void
    {
        $db = \Config\Database::connect();
        foreach ($this->permissions as $code => $data) {
            if (! $db->table('permissions')->where('code', $code)->get()->getRowArray()) {
                $db->table('permissions')->insert(array_merge(['code' => $code], $data));
            }
        }
        $permIds = $db->table('permissions')->whereIn('code', array_keys($this->permissions))->get()->getResultArray();
        $permIdMap = array_column($permIds, 'id', 'code');
        $roles = $db->table('roles')->whereIn('code', [
            'super_admin', 'superadmin', 'wakasek_kurikulum', 'admin_smp', 'admin_sma', 'kepala_sekolah',
        ])->get()->getResultArray();
        foreach ($roles as $role) {
            foreach ($permIdMap as $permId) {
                $exists = $db->table('role_permissions')->where('role_id', $role['id'])->where('permission_id', $permId)->countAllResults();
                if (! $exists) {
                    $db->table('role_permissions')->insert(['role_id' => $role['id'], 'permission_id' => $permId]);
                }
            }
        }
    }

    private function removePermissions(): void
    {
        $db = \Config\Database::connect();
        $permIds = $db->table('permissions')->whereIn('code', array_keys($this->permissions))->get()->getResultArray();
        if (! empty($permIds)) {
            $db->table('role_permissions')->whereIn('permission_id', array_column($permIds, 'id'))->delete();
            $db->table('permissions')->whereIn('code', array_keys($this->permissions))->delete();
        }
    }
}
