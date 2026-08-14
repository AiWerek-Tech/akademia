<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Gives operational roles the minimum capabilities their UI exposes while
 * keeping executive monitoring read-only and delegated account management
 * below the administrator tier.
 */
final class HardenOperationalRolePermissions extends Migration
{
    public function up()
    {
        $this->ensurePermission(
            'students.manage',
            'students',
            'Manage Students',
            'Menambah, memperbarui, dan menonaktifkan data peserta didik dalam unit akses'
        );

        $grants = [
            'super_admin'       => ['students.view', 'students.manage'],
            'superadmin'        => ['students.view', 'students.manage'],
            'admin_smp'         => ['students.view', 'students.manage'],
            'admin_sma'         => ['students.view', 'students.manage'],
            'wakasek_kurikulum' => ['students.view'],
            'kepala_sekolah'    => ['students.view'],
            'tata_usaha'        => [
                'students.view', 'students.manage',
                'users.view', 'users.manage', 'users.activate', 'users.reset_password',
            ],
            'viewer_yayasan'    => ['students.view'],
        ];

        foreach ($grants as $roleCode => $permissionCodes) {
            foreach ($permissionCodes as $permissionCode) {
                $this->grant($roleCode, $permissionCode);
            }
        }

        // Monitoring roles may inspect attendance but must not verify or alter it.
        foreach (['tata_usaha', 'viewer_yayasan'] as $roleCode) {
            $this->revoke($roleCode, 'attendances.admin');
        }
    }

    public function down()
    {
        foreach (['super_admin', 'superadmin', 'admin_smp', 'admin_sma', 'tata_usaha'] as $roleCode) {
            $this->revoke($roleCode, 'students.manage');
        }
        foreach (['students.view', 'users.view', 'users.manage', 'users.activate', 'users.reset_password'] as $code) {
            $this->revoke('tata_usaha', $code);
        }
        foreach (['tata_usaha', 'viewer_yayasan'] as $roleCode) {
            $this->grant($roleCode, 'attendances.admin');
        }

        $permission = $this->permission('students.manage');
        if ($permission) {
            $this->db->table('role_permissions')->where('permission_id', (int) $permission['id'])->delete();
            $this->db->table('permissions')->where('id', (int) $permission['id'])->delete();
        }
    }

    private function ensurePermission(string $code, string $module, string $name, string $description): void
    {
        if ($this->permission($code)) {
            return;
        }
        $this->db->table('permissions')->insert([
            'code' => $code,
            'module' => $module,
            'name' => $name,
            'description' => $description,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function grant(string $roleCode, string $permissionCode): void
    {
        $role = $this->db->table('roles')->select('id')->where('code', $roleCode)->get()->getRowArray();
        $permission = $this->permission($permissionCode);
        if (!$role || !$permission) {
            return;
        }
        if ($this->db->table('role_permissions')->where([
            'role_id' => (int) $role['id'],
            'permission_id' => (int) $permission['id'],
        ])->countAllResults() === 0) {
            $this->db->table('role_permissions')->insert([
                'role_id' => (int) $role['id'],
                'permission_id' => (int) $permission['id'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function revoke(string $roleCode, string $permissionCode): void
    {
        $role = $this->db->table('roles')->select('id')->where('code', $roleCode)->get()->getRowArray();
        $permission = $this->permission($permissionCode);
        if ($role && $permission) {
            $this->db->table('role_permissions')->where([
                'role_id' => (int) $role['id'],
                'permission_id' => (int) $permission['id'],
            ])->delete();
        }
    }

    private function permission(string $code): ?array
    {
        return $this->db->table('permissions')->select('id')->where('code', $code)->get()->getRowArray() ?: null;
    }
}
