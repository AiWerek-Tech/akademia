<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGradeLevelImportPermission extends Migration
{
    public function up()
    {
        $permission = $this->db->table('permissions')->where('code', 'grade_levels.import')->get()->getRowArray();
        if (!$permission) {
            $this->db->table('permissions')->insert([
                'code' => 'grade_levels.import',
                'module' => 'grade_levels',
                'name' => 'Import Grade Levels',
                'description' => 'Mengimpor dan memperbarui tingkat kelas melalui staging Excel',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $permissionId = (int) $this->db->insertID();
        } else {
            $permissionId = (int) $permission['id'];
        }

        $managePermission = $this->db->table('permissions')->where('code', 'grade_levels.manage')->get()->getRowArray();
        if (!$managePermission) {
            return;
        }

        $roles = $this->db->table('role_permissions')
            ->select('role_id')
            ->where('permission_id', $managePermission['id'])
            ->get()->getResultArray();
        foreach (array_unique(array_map('intval', array_column($roles, 'role_id'))) as $roleId) {
            $exists = $this->db->table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->countAllResults() > 0;
            if (!$exists) {
                $this->db->table('role_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function down()
    {
        $permission = $this->db->table('permissions')->where('code', 'grade_levels.import')->get()->getRowArray();
        if ($permission) {
            $this->db->table('permissions')->where('id', $permission['id'])->delete();
        }
    }
}
