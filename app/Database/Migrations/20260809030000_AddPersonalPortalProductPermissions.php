<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\RolePermissions;

class AddPersonalPortalProductPermissions extends Migration
{
    private const DEFINITIONS = [
        [
            'code' => 'teacher_assignment_document.view',
            'module' => 'teacher_portal',
            'name' => 'View Personal Assignment Decree',
            'description' => 'Melihat dan mencetak SK pembagian tugas milik guru yang login',
        ],
        [
            'code' => 'teacher_duty_schedule.view',
            'module' => 'teacher_portal',
            'name' => 'View Personal Duty Schedule',
            'description' => 'Melihat dan mencetak jadwal piket milik guru yang login',
        ],
    ];

    public function up()
    {
        $now = date('Y-m-d H:i:s');
        foreach (self::DEFINITIONS as $definition) {
            if ($this->db->table('permissions')->where('code', $definition['code'])->countAllResults() === 0) {
                $this->db->table('permissions')->insert($definition + ['created_at' => $now]);
            }
        }

        $permissionRows = $this->db->table('permissions')
            ->select('id, code')
            ->whereIn('code', array_column(self::DEFINITIONS, 'code'))
            ->get()->getResultArray();
        $permissionIds = array_column($permissionRows, 'id', 'code');

        foreach (['super_admin', 'superadmin', 'guru', 'wali_kelas'] as $roleCode) {
            $role = $this->db->table('roles')->where('code', $roleCode)->get()->getRowArray();
            if (!$role) {
                continue;
            }
            $codes = in_array($roleCode, ['guru', 'wali_kelas'], true)
                ? array_intersect(RolePermissions::managedRoles()[$roleCode], array_keys($permissionIds))
                : array_keys($permissionIds);
            foreach ($codes as $code) {
                $exists = $this->db->table('role_permissions')
                    ->where('role_id', $role['id'])->where('permission_id', $permissionIds[$code])
                    ->countAllResults() > 0;
                if (!$exists) {
                    $this->db->table('role_permissions')->insert([
                        'role_id' => (int) $role['id'],
                        'permission_id' => (int) $permissionIds[$code],
                        'created_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down()
    {
        foreach (self::DEFINITIONS as $definition) {
            $permission = $this->db->table('permissions')->where('code', $definition['code'])->get()->getRowArray();
            if (!$permission) {
                continue;
            }
            $this->db->table('role_permissions')->where('permission_id', $permission['id'])->delete();
            $this->db->table('permissions')->where('id', $permission['id'])->delete();
        }
    }
}
