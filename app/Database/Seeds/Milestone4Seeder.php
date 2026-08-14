<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Milestone4Seeder extends Seeder
{
    private function generateUuid()
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function run()
    {
        $db = $this->db;

        // 1. Enable feature flags
        $db->table('feature_flags')->where('code', 'assignments')->update(['enabled' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
        $db->table('feature_flags')->where('code', 'workload')->update(['enabled' => 1, 'updated_at' => date('Y-m-d H:i:s')]);

        // 2. Additional duty types lookup data (with standard workload hours)
        $duties = [
            ['code' => 'HEADMASTER', 'name' => 'Kepala Sekolah', 'category' => 'STRUKTURAL', 'default_workload_hours' => 24.00, 'requires_unit' => 0, 'requires_period' => 1, 'counts_toward_workload' => 1, 'sort_order' => 1],
            ['code' => 'VICE_PRINCIPAL', 'name' => 'Wakil Kepala Sekolah', 'category' => 'STRUKTURAL', 'default_workload_hours' => 12.00, 'requires_unit' => 1, 'requires_period' => 1, 'counts_toward_workload' => 1, 'sort_order' => 2],
            ['code' => 'TREASURER', 'name' => 'Bendahara Sekolah / TU', 'category' => 'STRUKTURAL', 'default_workload_hours' => 12.00, 'requires_unit' => 1, 'requires_period' => 1, 'counts_toward_workload' => 1, 'sort_order' => 3],
            ['code' => 'HOMEROOM_TEACHER', 'name' => 'Wali Kelas', 'category' => 'STRUKTURAL', 'default_workload_hours' => 2.00, 'requires_unit' => 1, 'requires_period' => 1, 'counts_toward_workload' => 1, 'sort_order' => 4],
            ['code' => 'LAB_HEAD', 'name' => 'Kepala Laboratorium', 'category' => 'STRUKTURAL', 'default_workload_hours' => 2.00, 'requires_unit' => 1, 'requires_period' => 1, 'counts_toward_workload' => 1, 'sort_order' => 5],
            ['code' => 'LIBRARY_HEAD', 'name' => 'Kepala Perpustakaan', 'category' => 'STRUKTURAL', 'default_workload_hours' => 2.00, 'requires_unit' => 1, 'requires_period' => 1, 'counts_toward_workload' => 1, 'sort_order' => 6],
            ['code' => 'PATHFINDER_DIR', 'name' => 'Director / Pembina Pathfinder', 'category' => 'KOKURIKULER', 'default_workload_hours' => 2.00, 'requires_unit' => 0, 'requires_period' => 1, 'counts_toward_workload' => 1, 'sort_order' => 7],
            ['code' => 'DAPODIK_OPERATOR', 'name' => 'Operator Dapodik', 'category' => 'FUNGSIONAL', 'default_workload_hours' => 2.00, 'requires_unit' => 0, 'requires_period' => 1, 'counts_toward_workload' => 1, 'sort_order' => 8],
            ['code' => 'COUNSELING_COORDINATOR', 'name' => 'Koordinator BK / Chaplain', 'category' => 'STRUKTURAL', 'default_workload_hours' => 2.00, 'requires_unit' => 1, 'requires_period' => 1, 'counts_toward_workload' => 1, 'sort_order' => 9],
            ['code' => 'COMMITTEE_ROLE', 'name' => 'Pembina OSIS / Panitia', 'category' => 'KOKURIKULER', 'default_workload_hours' => 2.00, 'requires_unit' => 0, 'requires_period' => 1, 'counts_toward_workload' => 1, 'sort_order' => 10],
            ['code' => 'OTHER', 'name' => 'Tugas Tambahan Lainnya', 'category' => 'LAINNYA', 'default_workload_hours' => 2.00, 'requires_unit' => 0, 'requires_period' => 1, 'counts_toward_workload' => 1, 'sort_order' => 11],
            ['code' => 'CHAPLAIN', 'name' => 'Chaplain', 'category' => 'STRUKTURAL', 'default_workload_hours' => 2.00, 'requires_unit' => 0, 'requires_period' => 1, 'counts_toward_workload' => 1, 'sort_order' => 12],
            ['code' => 'PATHFINDER_DIRECTOR', 'name' => 'Direktur Pathfinder', 'category' => 'KOKURIKULER', 'default_workload_hours' => 2.00, 'requires_unit' => 0, 'requires_period' => 1, 'counts_toward_workload' => 1, 'sort_order' => 13],
            ['code' => 'MASTER_GUIDE_DIR', 'name' => 'Direktur Master Guide', 'category' => 'KOKURIKULER', 'default_workload_hours' => 2.00, 'requires_unit' => 0, 'requires_period' => 1, 'counts_toward_workload' => 1, 'sort_order' => 14],
            ['code' => 'CASHIER', 'name' => 'Kasir', 'category' => 'STRUKTURAL', 'default_workload_hours' => 2.00, 'requires_unit' => 0, 'requires_period' => 1, 'counts_toward_workload' => 1, 'sort_order' => 15],
            ['code' => 'DORM_HEAD_BOYS', 'name' => 'Kepala Asrama Boys', 'category' => 'STRUKTURAL', 'default_workload_hours' => 2.00, 'requires_unit' => 0, 'requires_period' => 1, 'counts_toward_workload' => 1, 'sort_order' => 16],
            ['code' => 'DORM_HEAD_GIRLS', 'name' => 'Kepala Asrama Girls', 'category' => 'STRUKTURAL', 'default_workload_hours' => 2.00, 'requires_unit' => 0, 'requires_period' => 1, 'counts_toward_workload' => 1, 'sort_order' => 17],
            ['code' => 'DINING_HEAD', 'name' => 'Kepala Dining', 'category' => 'STRUKTURAL', 'default_workload_hours' => 2.00, 'requires_unit' => 0, 'requires_period' => 1, 'counts_toward_workload' => 1, 'sort_order' => 18],
            ['code' => 'TU_HEAD', 'name' => 'Kepala Tata Usaha', 'category' => 'STRUKTURAL', 'default_workload_hours' => 12.00, 'requires_unit' => 1, 'requires_period' => 1, 'counts_toward_workload' => 1, 'sort_order' => 19],
            ['code' => 'ARKAS_OPERATOR', 'name' => 'Operator Arkas / BOSP', 'category' => 'FUNGSIONAL', 'default_workload_hours' => 2.00, 'requires_unit' => 0, 'requires_period' => 1, 'counts_toward_workload' => 1, 'sort_order' => 20],
        ];

        foreach ($duties as $d) {
            $existing = $db->table('additional_duty_types')->where('code', $d['code'])->get()->getRowArray();
            if ($existing) {
                $db->table('additional_duty_types')->where('id', $existing['id'])->update([
                    'name'                   => $d['name'],
                    'category'               => $d['category'],
                    'requires_unit'          => $d['requires_unit'],
                    'requires_period'        => $d['requires_period'],
                    'counts_toward_workload' => $d['counts_toward_workload'],
                    'sort_order'             => $d['sort_order'],
                    'updated_at'             => date('Y-m-d H:i:s')
                ]);
            } else {
                $d['uuid']       = $this->generateUuid();
                $d['is_active']  = 1;
                $d['created_at'] = date('Y-m-d H:i:s');
                $d['updated_at'] = date('Y-m-d H:i:s');
                $db->table('additional_duty_types')->insert($d);
            }
        }

        // 3. New permissions
        $permissions = [
            // Assignments
            ['code' => 'assignments.view', 'module' => 'assignments', 'name' => 'View Assignments', 'description' => 'Melihat daftar dan matriks penugasan mengajar'],
            ['code' => 'assignments.manage', 'module' => 'assignments', 'name' => 'Manage Assignments', 'description' => 'Mengatur penugasan mengajar guru'],
            ['code' => 'assignments.validate', 'module' => 'assignments', 'name' => 'Validate Assignments', 'description' => 'Memvalidasi penugasan mengajar'],
            ['code' => 'assignments.review', 'module' => 'assignments', 'name' => 'Review Assignments', 'description' => 'Mereview penugasan mengajar'],
            ['code' => 'assignments.approve', 'module' => 'assignments', 'name' => 'Approve Assignments', 'description' => 'Menyetujui penugasan mengajar'],
            ['code' => 'assignments.lock', 'module' => 'assignments', 'name' => 'Lock Assignments', 'description' => 'Mengunci penugasan mengajar'],
            ['code' => 'assignments.import', 'module' => 'assignments', 'name' => 'Import Assignments', 'description' => 'Mengimpor penugasan mengajar dari Excel'],
            ['code' => 'assignments.export', 'module' => 'assignments', 'name' => 'Export Assignments', 'description' => 'Mengekspor penugasan mengajar'],
            ['code' => 'assignments.revise', 'module' => 'assignments', 'name' => 'Revise Assignments', 'description' => 'Membuat revisi penugasan yang telah dikunci'],

            // Workloads
            ['code' => 'workloads.view', 'module' => 'workloads', 'name' => 'View Workloads', 'description' => 'Melihat dashboard beban kerja guru'],
            ['code' => 'workloads.manage', 'module' => 'workloads', 'name' => 'Manage Workload Policies', 'description' => 'Mengatur kebijakan beban kerja guru'],
            ['code' => 'workloads.recalculate', 'module' => 'workloads', 'name' => 'Recalculate Workloads', 'description' => 'Menghitung ulang beban kerja guru'],
            ['code' => 'workloads.export', 'module' => 'workloads', 'name' => 'Export Workloads', 'description' => 'Mengekspor laporan beban kerja guru'],

            // Duties
            ['code' => 'duties.view', 'module' => 'duties', 'name' => 'View Additional Duties', 'description' => 'Melihat tugas tambahan guru'],
            ['code' => 'duties.manage', 'module' => 'duties', 'name' => 'Manage Additional Duties', 'description' => 'Mengatur tugas tambahan guru'],
        ];

        foreach ($permissions as $p) {
            $existing = $db->table('permissions')->where('code', $p['code'])->get()->getRowArray();
            if ($existing) {
                $db->table('permissions')->where('id', $existing['id'])->update([
                    'module'      => $p['module'],
                    'name'        => $p['name'],
                    'description' => $p['description'],
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
            } else {
                $p['created_at'] = date('Y-m-d H:i:s');
                $db->table('permissions')->insert($p);
            }
        }

        // Map roles and permissions
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

        // Define mapping array
        $mapping = [];

        // 1. super_admin gets all permissions (both existing and new)
        if (isset($role_ids['super_admin'])) {
            foreach ($perms_db as $p) {
                $mapping[] = ['role_id' => $role_ids['super_admin'], 'permission_id' => $p['id']];
            }
        }

        // 2. kepala_sekolah mapping
        if (isset($role_ids['kepala_sekolah'])) {
            $ks_perms = [
                'dashboard.view', 'units.view', 'academic_periods.view',
                'users.view', 'audit.view',
                // M4 new ones:
                'assignments.view', 'assignments.review', 'assignments.approve', 'assignments.lock',
                'workloads.view', 'workloads.export', 'duties.view'
            ];
            foreach ($ks_perms as $kp) {
                if (isset($perm_ids[$kp])) {
                    $mapping[] = ['role_id' => $role_ids['kepala_sekolah'], 'permission_id' => $perm_ids[$kp]];
                }
            }
        }

        // 3. wakasek_kurikulum mapping
        if (isset($role_ids['wakasek_kurikulum'])) {
            $wk_perms = [
                'dashboard.view', 'units.view', 'academic_years.view', 'academic_years.manage',
                'academic_periods.view', 'academic_periods.manage',
                // M4 new ones:
                'assignments.view', 'assignments.manage', 'assignments.validate', 'assignments.review',
                'assignments.import', 'assignments.export', 'assignments.revise',
                'workloads.view', 'workloads.manage', 'workloads.recalculate', 'workloads.export',
                'duties.view', 'duties.manage'
            ];
            foreach ($wk_perms as $wp) {
                if (isset($perm_ids[$wp])) {
                    $mapping[] = ['role_id' => $role_ids['wakasek_kurikulum'], 'permission_id' => $perm_ids[$wp]];
                }
            }
        }

        // 4. admin_smp and admin_sma mapping
        $admin_unit_perms = [
            'dashboard.view', 'units.view', 'units.manage', 'academic_years.view',
            'academic_periods.view', 'academic_periods.manage',
            'users.view', 'users.manage', 'users.activate', 'users.reset_password',
            // M4 new ones:
            'assignments.view', 'assignments.manage', 'assignments.validate',
            'assignments.import', 'assignments.export',
            'workloads.view', 'workloads.export', 'duties.view'
        ];
        foreach (['admin_smp', 'admin_sma'] as $role_code) {
            if (isset($role_ids[$role_code])) {
                foreach ($admin_unit_perms as $ap) {
                    if (isset($perm_ids[$ap])) {
                        $mapping[] = ['role_id' => $role_ids[$role_code], 'permission_id' => $perm_ids[$ap]];
                    }
                }
            }
        }

        // Guru uses the personal teacher portal. Global assignment/workload
        // permissions belong to administrative roles and are intentionally not
        // granted here. The complete Guru allow-list is synchronized by the
        // role-separation migration after portal permissions are registered.

        // 6. viewer_yayasan mapping (read-only)
        if (isset($role_ids['viewer_yayasan'])) {
            $vy_perms = [
                'dashboard.view', 'units.view', 'academic_years.view', 'academic_periods.view',
                'users.view', 'roles.view', 'permissions.view', 'settings.view', 'audit.view',
                'assignments.view', 'workloads.view', 'duties.view'
            ];
            foreach ($vy_perms as $vp) {
                if (isset($perm_ids[$vp])) {
                    $mapping[] = ['role_id' => $role_ids['viewer_yayasan'], 'permission_id' => $perm_ids[$vp]];
                }
            }
        }

        // Preserve permissions from earlier/later modules and add only missing
        // mappings. Seeders must be safe to run repeatedly in any release order.
        foreach ($mapping as $m) {
            $exists = $db->table('role_permissions')
                ->where('role_id', $m['role_id'])
                ->where('permission_id', $m['permission_id'])
                ->countAllResults() > 0;
            if (! $exists) {
                $m['created_at'] = date('Y-m-d H:i:s');
                $db->table('role_permissions')->insert($m);
            }
        }
    }
}
