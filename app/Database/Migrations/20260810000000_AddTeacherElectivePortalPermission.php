<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTeacherElectivePortalPermission extends Migration
{
    private const CODE = 'teacher_electives.view';

    public function up()
    {
        $now = date('Y-m-d H:i:s');
        $permission = $this->db->table('permissions')->where('code', self::CODE)->get()->getRowArray();
        if (!$permission) {
            $this->db->table('permissions')->insert([
                'code' => self::CODE,
                'module' => 'teacher_portal',
                'name' => 'View Own Elective Students',
                'description' => 'Melihat siswa yang memilih mapel pilihan yang diampu guru login',
                'created_at' => $now,
            ]);
            $permission = $this->db->table('permissions')->where('id', $this->db->insertID())->get()->getRowArray();
        }

        foreach (['super_admin', 'superadmin', 'guru', 'wali_kelas'] as $roleCode) {
            $role = $this->db->table('roles')->where('code', $roleCode)->get()->getRowArray();
            if (!$role || !$permission) {
                continue;
            }
            $exists = $this->db->table('role_permissions')
                ->where('role_id', (int) $role['id'])
                ->where('permission_id', (int) $permission['id'])
                ->countAllResults() > 0;
            if (!$exists) {
                $this->db->table('role_permissions')->insert([
                    'role_id' => (int) $role['id'],
                    'permission_id' => (int) $permission['id'],
                    'created_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        $permission = $this->db->table('permissions')->where('code', self::CODE)->get()->getRowArray();
        if (!$permission) {
            return;
        }
        $this->db->table('role_permissions')->where('permission_id', (int) $permission['id'])->delete();
        $this->db->table('permissions')->where('id', (int) $permission['id'])->delete();
    }
}
