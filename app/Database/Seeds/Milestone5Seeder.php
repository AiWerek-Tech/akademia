<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Milestone5Seeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // 1. Permissions
        $permissions = [
            ['code' => 'schedules.view', 'module' => 'schedules', 'name' => 'View Schedules', 'description' => 'Melihat data dan kisi jadwal pelajaran'],
            ['code' => 'schedules.manage', 'module' => 'schedules', 'name' => 'Manage Schedules', 'description' => 'Membuat dan mengedit versi jadwal, hari, slot, dan entri jadwal'],
            ['code' => 'schedules.validate', 'module' => 'schedules', 'name' => 'Validate Schedules', 'description' => 'Memvalidasi kelengkapan dan konflik versi jadwal'],
            ['code' => 'schedules.review', 'module' => 'schedules', 'name' => 'Review Schedules', 'description' => 'Meninjau versi jadwal pelajaran'],
            ['code' => 'schedules.approve', 'module' => 'schedules', 'name' => 'Approve Schedules', 'description' => 'Setujui versi jadwal pelajaran'],
            ['code' => 'schedules.lock', 'module' => 'schedules', 'name' => 'Lock Schedules', 'description' => 'Kunci versi atau entri jadwal pelajaran'],
            ['code' => 'schedules.generate', 'module' => 'schedules', 'name' => 'Generate Schedules', 'description' => 'Menjalankan generator jadwal otomatis'],
            ['code' => 'schedules.import', 'module' => 'schedules', 'name' => 'Import Schedules', 'description' => 'Mengimpor entri jadwal dari Excel/CSV'],
            ['code' => 'schedules.export', 'module' => 'schedules', 'name' => 'Export Schedules', 'description' => 'Mengekspor jadwal ke Excel/PDF'],
            ['code' => 'schedules.revise', 'module' => 'schedules', 'name' => 'Revise Schedules', 'description' => 'Mengubah entri jadwal dengan kontrol konkurensi revisi'],
            ['code' => 'availability.view', 'module' => 'availability', 'name' => 'View Availability', 'description' => 'Melihat aturan ketersediaan guru, kelas, dan ruang'],
            ['code' => 'availability.manage', 'module' => 'availability', 'name' => 'Manage Availability', 'description' => 'Mengatur aturan ketersediaan guru, kelas, dan ruang'],
            ['code' => 'constraints.view', 'module' => 'constraints', 'name' => 'View Constraints', 'description' => 'Melihat batasan penjadwalan (hard/soft)'],
            ['code' => 'constraints.manage', 'module' => 'constraints', 'name' => 'Manage Constraints', 'description' => 'Mengonfigurasi bobot dan status batasan penjadwalan'],
        ];

        $permissionMap = [];
        foreach ($permissions as $perm) {
            $existing = $this->db->table('permissions')->where('code', $perm['code'])->get()->getRowArray();
            if ($existing) {
                $permissionId = (int)$existing['id'];
            } else {
                $this->db->table('permissions')->insert(array_merge($perm, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
                $permissionId = (int)$this->db->insertID();
            }
            $permissionMap[$perm['code']] = $permissionId;
        }

        // Attach permissions to roles
        $roles = $this->db->table('roles')->get()->getResultArray();
        foreach ($roles as $role) {
            $roleId = (int)$role['id'];
            $roleCode = strtoupper((string)($role['code'] ?? ''));

            foreach ($permissionMap as $permCode => $permId) {
                $shouldAttach = false;
                if (in_array($roleCode, [
                    'ADMIN', 'SUPERADMIN', 'SUPER_ADMIN', 'KURIKULUM',
                    'WAKASEK_KURIKULUM', 'ADMIN_SMP', 'ADMIN_SMA',
                ], true)) {
                    $shouldAttach = true;
                } elseif ($roleCode === 'KEPALA_SEKOLAH') {
                    $shouldAttach = ! in_array($permCode, ['schedules.manage', 'schedules.import', 'availability.manage', 'constraints.manage'], true);
                } elseif ($roleCode === 'TATA_USAHA') {
                    $shouldAttach = in_array($permCode, ['schedules.view', 'schedules.export', 'availability.view'], true);
                } elseif ($roleCode === 'VIEWER_YAYASAN') {
                    $shouldAttach = in_array($permCode, ['schedules.view', 'schedules.export'], true);
                } elseif (($roleCode === 'GURU' || $roleCode === 'TEACHER') && in_array($permCode, ['schedules.view', 'availability.view'], true)) {
                    $shouldAttach = true;
                }

                if ($shouldAttach) {
                    $exists = $this->db->table('role_permissions')
                        ->where('role_id', $roleId)
                        ->where('permission_id', $permId)
                        ->countAllResults() > 0;
                    if (!$exists) {
                        $this->db->table('role_permissions')->insert([
                            'role_id'       => $roleId,
                            'permission_id' => $permId,
                            'created_at'    => $now,
                        ]);
                    }
                }
            }
        }

        // 2. Default Scheduling Constraints
        $defaultConstraints = [
            [
                'code' => 'HARD_TEACHER_DOUBLE_BOOKING',
                'name' => 'Mencegah bentrok jam mengajar guru pada slot yang sama',
                'constraint_type' => 'HARD',
                'severity' => 'CRITICAL',
                'weight' => 100,
                'is_enabled' => 1,
            ],
            [
                'code' => 'HARD_CLASSROOM_DOUBLE_BOOKING',
                'name' => 'Mencegah bentrok jadwal rombel/kelas pada slot yang sama',
                'constraint_type' => 'HARD',
                'severity' => 'CRITICAL',
                'weight' => 100,
                'is_enabled' => 1,
            ],
            [
                'code' => 'HARD_ROOM_DOUBLE_BOOKING',
                'name' => 'Mencegah pemakaian ruang yang sama oleh dua kelas sekaligus',
                'constraint_type' => 'HARD',
                'severity' => 'CRITICAL',
                'weight' => 100,
                'is_enabled' => 1,
            ],
            [
                'code' => 'HARD_CROSS_UNIT_TEACHER_DOUBLE_BOOKING',
                'name' => 'Mencegah bentrok guru mengajar lintas unit (SMP–SMA) pada slot sama',
                'constraint_type' => 'HARD',
                'severity' => 'CRITICAL',
                'weight' => 100,
                'is_enabled' => 1,
            ],
            [
                'code' => 'HARD_TEACHER_UNAVAILABLE',
                'name' => 'Mencegah penempatan jadwal pada waktu ketidaktersediaan guru',
                'constraint_type' => 'HARD',
                'severity' => 'CRITICAL',
                'weight' => 100,
                'is_enabled' => 1,
            ],
            [
                'code' => 'HARD_ROOM_UNAVAILABLE',
                'name' => 'Mencegah penggunaan ruang yang sedang tidak tersedia',
                'constraint_type' => 'HARD',
                'severity' => 'CRITICAL',
                'weight' => 100,
                'is_enabled' => 1,
            ],
            [
                'code' => 'HARD_ROOM_TYPE_MISMATCH',
                'name' => 'Memastikan jenis ruang sesuai dengan kebutuhan mata pelajaran (contoh: Lab)',
                'constraint_type' => 'HARD',
                'severity' => 'HIGH',
                'weight' => 90,
                'is_enabled' => 1,
            ],
            [
                'code' => 'SOFT_TEACHER_MAX_DAILY_HOURS',
                'name' => 'Meratakan beban mengajar harian guru (maksimal jam per hari)',
                'constraint_type' => 'SOFT',
                'severity' => 'HIGH',
                'weight' => 10,
                'is_enabled' => 1,
            ],
            [
                'code' => 'SOFT_TEACHER_CONSECUTIVE_SLOTS',
                'name' => 'Membatasi jam mengajar berurutan tanpa istirahat bagi guru',
                'constraint_type' => 'SOFT',
                'severity' => 'MEDIUM',
                'weight' => 5,
                'is_enabled' => 1,
            ],
            [
                'code' => 'SOFT_CLASSROOM_GAP_MINIMIZATION',
                'name' => 'Meminimalkan jam kosong (gap) pada jadwal belajar rombel',
                'constraint_type' => 'SOFT',
                'severity' => 'MEDIUM',
                'weight' => 5,
                'is_enabled' => 1,
            ],
            [
                'code' => 'SOFT_PREFERRED_ROOM',
                'name' => 'Memprioritaskan penggunaan ruang pilihan utama mata pelajaran',
                'constraint_type' => 'SOFT',
                'severity' => 'LOW',
                'weight' => 3,
                'is_enabled' => 1,
            ],
        ];

        foreach ($defaultConstraints as $c) {
            $existing = $this->db->table('scheduling_constraints')->where('code', $c['code'])->get()->getRowArray();
            if (!$existing) {
                $this->db->table('scheduling_constraints')->insert(array_merge($c, [
                    'uuid'       => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }
}
