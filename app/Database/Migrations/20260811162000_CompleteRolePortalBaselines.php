<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Ensures every login persona can reach its dashboard and core references. */
final class CompleteRolePortalBaselines extends Migration
{
    private const GRANTS = [
        'tata_usaha' => [
            'dashboard.view', 'units.view', 'academic_years.view', 'academic_periods.view',
        ],
        'siswa' => [
            'dashboard.view', 'academic_calendar.view',
        ],
    ];

    public function up()
    {
        foreach (self::GRANTS as $roleCode => $permissionCodes) {
            foreach ($permissionCodes as $permissionCode) {
                $this->grant($roleCode, $permissionCode);
            }
        }
    }

    public function down()
    {
        foreach (self::GRANTS as $roleCode => $permissionCodes) {
            foreach ($permissionCodes as $permissionCode) {
                $role = $this->db->table('roles')->select('id')->where('code', $roleCode)->get()->getRowArray();
                $permission = $this->db->table('permissions')->select('id')->where('code', $permissionCode)->get()->getRowArray();
                if ($role && $permission) {
                    $this->db->table('role_permissions')->where([
                        'role_id' => (int) $role['id'], 'permission_id' => (int) $permission['id'],
                    ])->delete();
                }
            }
        }
    }

    private function grant(string $roleCode, string $permissionCode): void
    {
        $role = $this->db->table('roles')->select('id')->where('code', $roleCode)->get()->getRowArray();
        $permission = $this->db->table('permissions')->select('id')->where('code', $permissionCode)->get()->getRowArray();
        if (!$role || !$permission) {
            return;
        }
        $key = ['role_id' => (int) $role['id'], 'permission_id' => (int) $permission['id']];
        if ($this->db->table('role_permissions')->where($key)->countAllResults() === 0) {
            $this->db->table('role_permissions')->insert($key + ['created_at' => date('Y-m-d H:i:s')]);
        }
    }
}
