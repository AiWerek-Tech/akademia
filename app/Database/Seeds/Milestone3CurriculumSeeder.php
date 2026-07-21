<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Milestone3CurriculumSeeder extends Seeder
{
    public function run()
    {
        $db = $this->db;

        // 1. Seed Curriculum Permissions
        $permissions = [
            ['code' => 'curriculum.view', 'module' => 'curriculum', 'name' => 'View Curriculum', 'description' => 'Melihat versi dan struktur kurikulum'],
            ['code' => 'curriculum.manage', 'module' => 'curriculum', 'name' => 'Manage Curriculum', 'description' => 'Membuat dan mengedit struktur kurikulum'],
            ['code' => 'curriculum.validate', 'module' => 'curriculum', 'name' => 'Validate Curriculum', 'description' => 'Memvalidasi struktur kurikulum (DRAFT -> VALIDATED)'],
            ['code' => 'curriculum.review', 'module' => 'curriculum', 'name' => 'Review Curriculum', 'description' => 'Mereview struktur kurikulum (VALIDATED -> REVIEWED)'],
            ['code' => 'curriculum.approve', 'module' => 'curriculum', 'name' => 'Approve Curriculum', 'description' => 'Menyetujui struktur kurikulum (REVIEWED -> APPROVED)'],
            ['code' => 'curriculum.lock', 'module' => 'curriculum', 'name' => 'Lock Curriculum', 'description' => 'Mengunci versi kurikulum (APPROVED -> LOCKED)'],
            ['code' => 'curriculum.import', 'module' => 'curriculum', 'name' => 'Import Curriculum', 'description' => 'Mengimpor struktur kurikulum dari Excel'],
            ['code' => 'curriculum.export', 'module' => 'curriculum', 'name' => 'Export Curriculum', 'description' => 'Mengekspor struktur kurikulum ke Excel/PDF'],
            ['code' => 'curriculum.revise', 'module' => 'curriculum', 'name' => 'Revise Curriculum', 'description' => 'Membuat revisi baru versi kurikulum'],
        ];

        foreach ($permissions as $permission) {
            $existing = $db->table('permissions')->where('code', $permission['code'])->get()->getRowArray();
            if ($existing) {
                $db->table('permissions')->where('id', $existing['id'])->update([
                    'module'      => $permission['module'],
                    'name'        => $permission['name'],
                    'description' => $permission['description'],
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
            } else {
                $permission['created_at'] = date('Y-m-d H:i:s');
                $db->table('permissions')->insert($permission);
            }
        }

        // Fetch IDs for role mapping
        $roles_db = $db->table('roles')->get()->getResultArray();
        $perms_db = $db->table('permissions')->get()->getResultArray();

        $role_ids = [];
        foreach ($roles_db as $r) {
            $role_ids[$r['code']] = $r['id'];
        }

        $perm_ids = [];
        foreach ($perms_db as $p) {
            $perm_ids[$p['code']] = $p['id'];
        }

        // Map permissions to roles
        $role_permissions_map = [
            'super_admin' => [
                'curriculum.view', 'curriculum.manage', 'curriculum.validate',
                'curriculum.review', 'curriculum.approve', 'curriculum.lock',
                'curriculum.import', 'curriculum.export', 'curriculum.revise',
            ],
            'kepala_sekolah' => [
                'curriculum.view', 'curriculum.review', 'curriculum.approve',
                'curriculum.lock', 'curriculum.export',
            ],
            'wakasek_kurikulum' => [
                'curriculum.view', 'curriculum.manage', 'curriculum.validate',
                'curriculum.review', 'curriculum.import', 'curriculum.export',
                'curriculum.revise',
            ],
            'admin_smp' => [
                'curriculum.view', 'curriculum.manage', 'curriculum.validate',
                'curriculum.import', 'curriculum.export', 'curriculum.revise',
            ],
            'admin_sma' => [
                'curriculum.view', 'curriculum.manage', 'curriculum.validate',
                'curriculum.import', 'curriculum.export', 'curriculum.revise',
            ],
            'guru' => [
                'curriculum.view',
            ],
            'viewer_yayasan' => [
                'curriculum.view', 'curriculum.export',
            ],
        ];

        foreach ($role_permissions_map as $role_code => $perm_codes) {
            if (!isset($role_ids[$role_code])) {
                continue;
            }
            $role_id = $role_ids[$role_code];

            foreach ($perm_codes as $pcode) {
                if (!isset($perm_ids[$pcode])) {
                    continue;
                }
                $permission_id = $perm_ids[$pcode];

                $exists = $db->table('role_permissions')
                    ->where('role_id', $role_id)
                    ->where('permission_id', $permission_id)
                    ->get()
                    ->getRowArray();

                if (!$exists) {
                    $db->table('role_permissions')->insert([
                        'role_id'       => $role_id,
                        'permission_id' => $permission_id,
                        'created_at'    => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }

        // Enable feature flag 'curriculum'
        $flagExists = $db->table('feature_flags')->where('code', 'curriculum')->get()->getRowArray();
        if ($flagExists) {
            $db->table('feature_flags')
                ->where('code', 'curriculum')
                ->update([
                    'enabled'    => 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        } else {
            $db->table('feature_flags')->insert([
                'code'        => 'curriculum',
                'name'        => 'Struktur Kurikulum',
                'description' => 'Fitur manajemen versi dan struktur kurikulum',
                'enabled'     => 1,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
