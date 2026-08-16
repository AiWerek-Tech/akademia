<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Config\RolePermissions;

class CoreSeeder extends Seeder
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

        // 1. Seed school_units
        $units = [
            [
                'code'       => 'SMP',
                'name'       => 'SMP Advent Sogokmo',
                'short_name' => 'SMP',
                'level'      => 'SMP',
                'timezone'   => 'Asia/Jayapura',
                'is_active'  => 1,
            ],
            [
                'code'       => 'SMA',
                'name'       => 'SMA Advent Sogokmo',
                'short_name' => 'SMA',
                'level'      => 'SMA',
                'timezone'   => 'Asia/Jayapura',
                'is_active'  => 1,
            ]
        ];

        foreach ($units as $unit) {
            $existing = $db->table('school_units')->where('code', $unit['code'])->get()->getRowArray();
            if ($existing) {
                $db->table('school_units')->where('id', $existing['id'])->update([
                    'name'       => $unit['name'],
                    'short_name' => $unit['short_name'],
                    'level'      => $unit['level'],
                    'timezone'   => $unit['timezone'],
                    'is_active'  => $unit['is_active'],
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            } else {
                $unit['uuid']       = $this->generateUuid();
                $unit['created_at'] = date('Y-m-d H:i:s');
                $db->table('school_units')->insert($unit);
            }
        }

        // 2. Seed roles
        $roles = [
            ['code' => 'super_admin', 'name' => 'Super Admin', 'description' => 'Akses penuh ke seluruh sistem', 'scope' => 'GLOBAL', 'is_system' => 1],
            ['code' => 'kepala_sekolah', 'name' => 'Kepala Sekolah', 'description' => 'Melihat data akademik dan persetujuan periode', 'scope' => 'GLOBAL', 'is_system' => 1],
            ['code' => 'wakasek_kurikulum', 'name' => 'Wakasek Kurikulum', 'description' => 'Mengelola jadwal dan tahun pelajaran', 'scope' => 'GLOBAL', 'is_system' => 1],
            ['code' => 'admin_smp', 'name' => 'Admin SMP', 'description' => 'Akses administratif unit SMP', 'scope' => 'UNIT', 'is_system' => 1],
            ['code' => 'admin_sma', 'name' => 'Admin SMA', 'description' => 'Akses administratif unit SMA', 'scope' => 'UNIT', 'is_system' => 1],
            ['code' => 'tata_usaha', 'name' => 'Tata Usaha', 'description' => 'Membantu pengelolaan administratif', 'scope' => 'GLOBAL', 'is_system' => 1],
            ['code' => 'guru', 'name' => 'Guru', 'description' => 'Akses guru pengajar', 'scope' => 'GLOBAL', 'is_system' => 1],
            ['code' => 'siswa', 'name' => 'Siswa', 'description' => 'Akses mandiri layanan akademik siswa', 'scope' => 'UNIT', 'is_system' => 1],
            ['code' => 'viewer_yayasan', 'name' => 'Viewer Yayasan', 'description' => 'Akses read-only seluruh sistem', 'scope' => 'GLOBAL', 'is_system' => 1],
        ];

        foreach ($roles as $role) {
            $existing = $db->table('roles')->where('code', $role['code'])->get()->getRowArray();
            if ($existing) {
                $db->table('roles')->where('id', $existing['id'])->update([
                    'name'        => $role['name'],
                    'description' => $role['description'],
                    'scope'       => $role['scope'],
                    'is_system'   => $role['is_system'],
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
            } else {
                $role['created_at'] = date('Y-m-d H:i:s');
                $db->table('roles')->insert($role);
            }
        }

        // 3. Seed permissions
        $permissions = [
            // Dashboard
            ['code' => 'dashboard.view', 'module' => 'dashboard', 'name' => 'View Dashboard', 'description' => 'Melihat halaman dashboard utama'],
            
            // Units
            ['code' => 'units.view', 'module' => 'units', 'name' => 'View Units', 'description' => 'Melihat profil unit sekolah'],
            ['code' => 'units.manage', 'module' => 'units', 'name' => 'Manage Units', 'description' => 'Mengedit profil unit sekolah'],
            
            // Academic Years
            ['code' => 'academic_years.view', 'module' => 'academic_years', 'name' => 'View Academic Years', 'description' => 'Melihat daftar tahun pelajaran'],
            ['code' => 'academic_years.manage', 'module' => 'academic_years', 'name' => 'Manage Academic Years', 'description' => 'Membuat/mengedit tahun pelajaran'],
            
            // Academic Periods
            ['code' => 'academic_periods.view', 'module' => 'academic_periods', 'name' => 'View Academic Periods', 'description' => 'Melihat daftar periode akademik'],
            ['code' => 'academic_periods.manage', 'module' => 'academic_periods', 'name' => 'Manage Academic Periods', 'description' => 'Membuat/mengedit periode akademik'],
            
            // Users
            ['code' => 'users.view', 'module' => 'users', 'name' => 'View Users', 'description' => 'Melihat daftar pengguna'],
            ['code' => 'users.manage', 'module' => 'users', 'name' => 'Manage Users', 'description' => 'Membuat/mengedit pengguna dan hak akses'],
            ['code' => 'users.activate', 'module' => 'users', 'name' => 'Activate/Deactivate Users', 'description' => 'Mengaktifkan atau menonaktifkan pengguna'],
            ['code' => 'users.reset_password', 'module' => 'users', 'name' => 'Reset Password Users', 'description' => 'Mereset password pengguna lain'],

            // Student self-service baseline (also maintained by ElectiveModuleSeeder).
            ['code' => 'electives.selection.submit', 'module' => 'electives', 'name' => 'Submit Own Selection', 'description' => 'Menyimpan dan mengirim pilihan mata pelajaran sendiri'],
            ['code' => 'electives.change.request', 'module' => 'electives', 'name' => 'Request Selection Change', 'description' => 'Mengajukan perubahan pilihan mata pelajaran sendiri'],
            
            // Roles & Permissions
            ['code' => 'roles.view', 'module' => 'roles', 'name' => 'View Roles', 'description' => 'Melihat daftar role'],
            ['code' => 'roles.manage', 'module' => 'roles', 'name' => 'Manage Roles', 'description' => 'Membuat/mengedit role'],
            ['code' => 'permissions.view', 'module' => 'roles', 'name' => 'View Permissions', 'description' => 'Melihat daftar permission'],
            ['code' => 'permissions.assign', 'module' => 'roles', 'name' => 'Assign Permissions', 'description' => 'Mengatur pemetaan permission ke role'],
            
            // Settings
            ['code' => 'settings.view', 'module' => 'settings', 'name' => 'View Settings', 'description' => 'Melihat pengaturan aplikasi'],
            ['code' => 'settings.manage', 'module' => 'settings', 'name' => 'Manage Settings', 'description' => 'Mengubah pengaturan aplikasi'],
            
            // Audit Log
            ['code' => 'audit.view', 'module' => 'audit', 'name' => 'View Audit Log', 'description' => 'Melihat catatan audit log sistem'],
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

        // Fetch all roles & permissions to map role_permissions
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

        // Define mapping
        $mapping = [];

        // 1. super_admin gets everything
        if (isset($role_ids['super_admin'])) {
            foreach ($perms_db as $p) {
                $mapping[] = ['role_id' => $role_ids['super_admin'], 'permission_id' => $p['id']];
            }
        }

        // 2. kepala_sekolah mapping
        if (isset($role_ids['kepala_sekolah'])) {
            $ks_perms = [
                'dashboard.view', 'units.view', 'academic_periods.view',
                'users.view', 'audit.view'
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
                'academic_periods.view', 'academic_periods.manage'
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
            'users.view', 'users.manage', 'users.activate', 'users.reset_password'
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

        // Operational permissions added after the initial RBAC milestone.
        // Fresh installations run all migrations before this seeder, so these
        // grants must also live here (migration-time roles may not exist yet).
        $operationalRolePermissions = [
            'admin_smp' => [
                'students.view', 'students.manage',
                'attendances.view', 'attendances.record', 'attendances.admin',
                'academic_calendar.view', 'academic_calendar.manage',
            ],
            'admin_sma' => [
                'students.view', 'students.manage',
                'attendances.view', 'attendances.record', 'attendances.admin',
                'academic_calendar.view', 'academic_calendar.manage',
            ],
            'wakasek_kurikulum' => [
                'students.view', 'attendances.view', 'attendances.record', 'attendances.admin',
                'academic_calendar.view', 'academic_calendar.manage',
            ],
            'kepala_sekolah' => [
                'students.view', 'attendances.view', 'attendances.admin',
                'academic_calendar.view', 'academic_calendar.manage',
            ],
            'tata_usaha' => [
                'dashboard.view', 'units.view', 'academic_years.view', 'academic_periods.view',
                'students.view', 'students.manage',
                'users.view', 'users.manage', 'users.activate', 'users.reset_password',
                'attendances.view', 'academic_calendar.view',
            ],
            'viewer_yayasan' => [
                'students.view', 'attendances.view', 'academic_calendar.view',
            ],
            'siswa' => [
                'dashboard.view', 'academic_calendar.view',
                'electives.selection.submit', 'electives.change.request',
            ],
        ];
        foreach ($operationalRolePermissions as $roleCode => $permissionCodes) {
            if (!isset($role_ids[$roleCode])) {
                continue;
            }
            foreach ($permissionCodes as $permissionCode) {
                if (isset($perm_ids[$permissionCode])) {
                    $mapping[] = [
                        'role_id' => $role_ids[$roleCode],
                        'permission_id' => $perm_ids[$permissionCode],
                    ];
                }
            }
        }

        // 5. Personal role mappings use the canonical least-privilege sets.
        foreach (RolePermissions::managedRoles() as $roleCode => $permissionCodes) {
            if (!isset($role_ids[$roleCode])) {
                continue;
            }
            foreach ($permissionCodes as $permissionCode) {
                if (isset($perm_ids[$permissionCode])) {
                    $mapping[] = [
                        'role_id' => $role_ids[$roleCode],
                        'permission_id' => $perm_ids[$permissionCode],
                    ];
                }
            }
        }

        // 6. viewer_yayasan mapping (read-only)
        if (isset($role_ids['viewer_yayasan'])) {
            $vy_perms = [
                'dashboard.view', 'units.view', 'academic_years.view', 'academic_periods.view',
                'users.view', 'roles.view', 'permissions.view', 'settings.view', 'audit.view'
            ];
            foreach ($vy_perms as $vp) {
                if (isset($perm_ids[$vp])) {
                    $mapping[] = ['role_id' => $role_ids['viewer_yayasan'], 'permission_id' => $perm_ids[$vp]];
                }
            }
        }

        foreach ($mapping as $m) {
            $exists = $db->table('role_permissions')
                ->where('role_id', $m['role_id'])
                ->where('permission_id', $m['permission_id'])
                ->get()->getRowArray();
            if (!$exists) {
                $m['created_at'] = date('Y-m-d H:i:s');
                $db->table('role_permissions')->insert($m);
            }
        }

        // 4. Seed feature_flags
        $flags = [
            ['code' => 'curriculum', 'name' => 'Struktur Kurikulum', 'description' => 'Fitur struktur kurikulum dan jam mingguan', 'enabled' => 0],
            ['code' => 'assignments', 'name' => 'Penugasan Guru', 'description' => 'Fitur matriks penugasan guru dan beban kerja', 'enabled' => 0],
            ['code' => 'workload', 'name' => 'Beban Kerja', 'description' => 'Fitur perhitungan beban kerja guru', 'enabled' => 0],
            ['code' => 'scheduling', 'name' => 'Editor Jadwal', 'description' => 'Fitur pengeditan jadwal dan konflik', 'enabled' => 0],
            ['code' => 'automatic_scheduler', 'name' => 'Auto Generator Jadwal', 'description' => 'Fitur pembuatan jadwal otomatis menggunakan heuristik', 'enabled' => 0],
            ['code' => 'duty_roster', 'name' => 'Piket Harian', 'description' => 'Fitur generator piket harian dan jurnal piket', 'enabled' => 0],
            ['code' => 'documents', 'name' => 'Dokumen Resmi', 'description' => 'Fitur generate SK dan cetak PDF', 'enabled' => 0],
            ['code' => 'legacy_import', 'name' => 'Import Legacy Data', 'description' => 'Fitur migrasi data dari sistem lama', 'enabled' => 0],
            ['code' => 'teacher_portal', 'name' => 'Portal Guru', 'description' => 'Halaman dashboard khusus guru', 'enabled' => 0],
            ['code' => 'public_verification', 'name' => 'Verifikasi Publik', 'description' => 'Halaman verifikasi jadwal via QR Code', 'enabled' => 0],
            ['code' => 'authentication', 'name' => 'Authentication', 'description' => 'Fitur login, logout dan manajemen session', 'enabled' => 1],
            ['code' => 'user_management', 'name' => 'User Management', 'description' => 'Fitur pengelolaan user dan RBAC', 'enabled' => 1],
            ['code' => 'academic_periods', 'name' => 'Academic Periods', 'description' => 'Fitur tahun pelajaran dan periode akademik', 'enabled' => 1],
        ];

        foreach ($flags as $flag) {
            $existing = $db->table('feature_flags')->where('code', $flag['code'])->get()->getRowArray();
            if ($existing) {
                $db->table('feature_flags')->where('id', $existing['id'])->update([
                    'name'        => $flag['name'],
                    'description' => $flag['description'],
                    'enabled'     => $flag['enabled'],
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
            } else {
                $flag['created_at'] = date('Y-m-d H:i:s');
                $db->table('feature_flags')->insert($flag);
            }
        }

        // 5. Seed application_settings
        $settings = [
            ['setting_group' => 'general', 'setting_key' => 'app_name', 'setting_value' => 'IALOS Education', 'value_type' => 'string', 'is_public' => 1],
            ['setting_group' => 'general', 'setting_key' => 'tagline', 'setting_value' => 'Perencanaan Akademik Terpadu SMP–SMA', 'value_type' => 'string', 'is_public' => 1],
            ['setting_group' => 'general', 'setting_key' => 'timezone', 'setting_value' => 'Asia/Jayapura', 'value_type' => 'string', 'is_public' => 1],
            ['setting_group' => 'security', 'setting_key' => 'max_login_attempts', 'setting_value' => '5', 'value_type' => 'int', 'is_public' => 0],
            ['setting_group' => 'security', 'setting_key' => 'login_window_minutes', 'setting_value' => '15', 'value_type' => 'int', 'is_public' => 0],
            ['setting_group' => 'security', 'setting_key' => 'lockout_minutes', 'setting_value' => '15', 'value_type' => 'int', 'is_public' => 0],
            ['setting_group' => 'security', 'setting_key' => 'session_timeout_minutes', 'setting_value' => '120', 'value_type' => 'int', 'is_public' => 0],
            ['setting_group' => 'academic', 'setting_key' => 'default_unit', 'setting_value' => null, 'value_type' => 'string', 'is_public' => 1],
            ['setting_group' => 'academic', 'setting_key' => 'default_period', 'setting_value' => null, 'value_type' => 'string', 'is_public' => 1],
        ];

        foreach ($settings as $setting) {
            $existing = $db->table('application_settings')
                ->where('setting_group', $setting['setting_group'])
                ->where('setting_key', $setting['setting_key'])
                ->get()
                ->getRowArray();
            if ($existing) {
                // Do not overwrite user setting value to remain idempotent and preserve settings modifications
                $db->table('application_settings')->where('id', $existing['id'])->update([
                    'value_type' => $setting['value_type'],
                    'is_public'  => $setting['is_public'],
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            } else {
                $setting['created_at'] = date('Y-m-d H:i:s');
                $db->table('application_settings')->insert($setting);
            }
        }
    }
}
