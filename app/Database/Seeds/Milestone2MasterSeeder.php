<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Milestone2MasterSeeder extends Seeder
{
    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function run()
    {
        $db = $this->db;

        // 1. Seed Grade Levels for SMP and SMA
        $smpUnit = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        $smaUnit = $db->table('school_units')->where('code', 'SMA')->get()->getRowArray();

        if ($smpUnit) {
            $smpGrades = [
                ['grade_number' => 7, 'code' => 'VII', 'name' => 'Tingkat VII', 'phase' => 'D', 'sort_order' => 10],
                ['grade_number' => 8, 'code' => 'VIII', 'name' => 'Tingkat VIII', 'phase' => 'D', 'sort_order' => 20],
                ['grade_number' => 9, 'code' => 'IX', 'name' => 'Tingkat IX', 'phase' => 'D', 'sort_order' => 30],
            ];

            foreach ($smpGrades as $g) {
                $existing = $db->table('grade_levels')
                    ->where('unit_id', $smpUnit['id'])
                    ->where('grade_number', $g['grade_number'])
                    ->get()->getRowArray();

                if ($existing) {
                    $db->table('grade_levels')->where('id', $existing['id'])->update([
                        'code'       => $g['code'],
                        'name'       => $g['name'],
                        'phase'      => $g['phase'],
                        'sort_order' => $g['sort_order'],
                        'is_active'  => 1,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                } else {
                    $g['uuid']       = $this->generateUuid();
                    $g['unit_id']    = $smpUnit['id'];
                    $g['is_active']  = 1;
                    $g['created_at'] = date('Y-m-d H:i:s');
                    $db->table('grade_levels')->insert($g);
                }
            }
        }

        if ($smaUnit) {
            $smaGrades = [
                ['grade_number' => 10, 'code' => 'X', 'name' => 'Tingkat X', 'phase' => 'E', 'sort_order' => 10],
                ['grade_number' => 11, 'code' => 'XI', 'name' => 'Tingkat XI', 'phase' => 'F', 'sort_order' => 20],
                ['grade_number' => 12, 'code' => 'XII', 'name' => 'Tingkat XII', 'phase' => 'F', 'sort_order' => 30],
            ];

            foreach ($smaGrades as $g) {
                $existing = $db->table('grade_levels')
                    ->where('unit_id', $smaUnit['id'])
                    ->where('grade_number', $g['grade_number'])
                    ->get()->getRowArray();

                if ($existing) {
                    $db->table('grade_levels')->where('id', $existing['id'])->update([
                        'code'       => $g['code'],
                        'name'       => $g['name'],
                        'phase'      => $g['phase'],
                        'sort_order' => $g['sort_order'],
                        'is_active'  => 1,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                } else {
                    $g['uuid']       = $this->generateUuid();
                    $g['unit_id']    = $smaUnit['id'];
                    $g['is_active']  = 1;
                    $g['created_at'] = date('Y-m-d H:i:s');
                    $db->table('grade_levels')->insert($g);
                }
            }
        }

        // 2. Seed Room Types
        $roomTypes = [
            ['code' => 'CLASSROOM', 'name' => 'Ruang Kelas', 'description' => 'Ruang kelas umum untuk proses belajar mengajar', 'is_specialized' => 0],
            ['code' => 'LAB_COMPUTER', 'name' => 'Laboratorium Komputer', 'description' => 'Laboratorium Komputer', 'is_specialized' => 1],
            ['code' => 'LAB_SCIENCE', 'name' => 'Laboratorium IPA', 'description' => 'Laboratorium Sains (Fisika, Kimia, Biologi)', 'is_specialized' => 1],
            ['code' => 'LIBRARY', 'name' => 'Perpustakaan', 'description' => 'Perpustakaan Sekolah', 'is_specialized' => 1],
            ['code' => 'CHAPEL', 'name' => 'Kapel / Tempat Ibadah', 'description' => 'Ruang ibadah / keagamaan', 'is_specialized' => 1],
            ['code' => 'HALL', 'name' => 'Aula / Serbaguna', 'description' => 'Aula / Ruang Pertemuan Serbaguna', 'is_specialized' => 1],
            ['code' => 'OFFICE', 'name' => 'Ruang Perkantoran', 'description' => 'Kantor TU, Guru, atau Pimpinan', 'is_specialized' => 0],
            ['code' => 'OUTDOOR', 'name' => 'Lapangan / Outdoor', 'description' => 'Area / Lapangan Olahraga / Luar ruangan', 'is_specialized' => 1],
            ['code' => 'OTHER', 'name' => 'Lainnya', 'description' => 'Fasilitas / Ruangan Lainnya', 'is_specialized' => 0],
        ];

        foreach ($roomTypes as $rt) {
            $existing = $db->table('room_types')->where('code', $rt['code'])->get()->getRowArray();
            if ($existing) {
                $db->table('room_types')->where('id', $existing['id'])->update([
                    'name'           => $rt['name'],
                    'description'    => $rt['description'],
                    'is_specialized' => $rt['is_specialized'],
                    'is_active'      => 1,
                    'updated_at'     => date('Y-m-d H:i:s'),
                ]);
            } else {
                $rt['is_active']  = 1;
                $rt['created_at'] = date('Y-m-d H:i:s');
                $db->table('room_types')->insert($rt);
            }
        }

        // 3. Seed Permissions
        $permissions = [
            // Teachers
            ['code' => 'teachers.view', 'module' => 'teachers', 'name' => 'View Teachers', 'description' => 'Melihat data master guru'],
            ['code' => 'teachers.manage', 'module' => 'teachers', 'name' => 'Manage Teachers', 'description' => 'Menambah dan mengedit data guru'],
            ['code' => 'teachers.verify', 'module' => 'teachers', 'name' => 'Verify Teacher Profile', 'description' => 'Memverifikasi status kelengkapan profil guru'],
            ['code' => 'teachers.import', 'module' => 'teachers', 'name' => 'Import Teachers', 'description' => 'Mengimpor data guru dari Excel'],
            ['code' => 'teachers.export', 'module' => 'teachers', 'name' => 'Export Teachers', 'description' => 'Mengekspor data guru ke Excel/PDF'],

            // Subjects
            ['code' => 'subjects.view', 'module' => 'subjects', 'name' => 'View Subjects', 'description' => 'Melihat data master mata pelajaran'],
            ['code' => 'subjects.manage', 'module' => 'subjects', 'name' => 'Manage Subjects', 'description' => 'Menambah dan mengedit mata pelajaran'],
            ['code' => 'subjects.import', 'module' => 'subjects', 'name' => 'Import Subjects', 'description' => 'Mengimpor data mata pelajaran dari Excel'],
            ['code' => 'subjects.export', 'module' => 'subjects', 'name' => 'Export Subjects', 'description' => 'Mengekspor data mata pelajaran ke Excel/PDF'],

            // Grade Levels
            ['code' => 'grade_levels.view', 'module' => 'grade_levels', 'name' => 'View Grade Levels', 'description' => 'Melihat tingkat kelas'],
            ['code' => 'grade_levels.manage', 'module' => 'grade_levels', 'name' => 'Manage Grade Levels', 'description' => 'Mengedit nama/fase tingkat kelas'],
            ['code' => 'grade_levels.import', 'module' => 'grade_levels', 'name' => 'Import Grade Levels', 'description' => 'Mengimpor dan memperbarui tingkat kelas melalui staging Excel'],

            // Classrooms
            ['code' => 'classrooms.view', 'module' => 'classrooms', 'name' => 'View Classrooms', 'description' => 'Melihat daftar kelas/rombel'],
            ['code' => 'classrooms.manage', 'module' => 'classrooms', 'name' => 'Manage Classrooms', 'description' => 'Menambah, mengedit, dan me-copy kelas'],
            ['code' => 'classrooms.import', 'module' => 'classrooms', 'name' => 'Import Classrooms', 'description' => 'Mengimpor kelas/rombel dari Excel'],
            ['code' => 'classrooms.export', 'module' => 'classrooms', 'name' => 'Export Classrooms', 'description' => 'Mengekspor kelas/rombel ke Excel/PDF'],

            // Rooms
            ['code' => 'rooms.view', 'module' => 'rooms', 'name' => 'View Rooms', 'description' => 'Melihat daftar ruang sekolah'],
            ['code' => 'rooms.manage', 'module' => 'rooms', 'name' => 'Manage Rooms', 'description' => 'Menambah dan mengedit ruang sekolah'],
            ['code' => 'rooms.import', 'module' => 'rooms', 'name' => 'Import Rooms', 'description' => 'Mengimpor ruang sekolah dari Excel'],
            ['code' => 'rooms.export', 'module' => 'rooms', 'name' => 'Export Rooms', 'description' => 'Mengekspor ruang sekolah ke Excel/PDF'],

            // Duplicates
            ['code' => 'duplicates.view', 'module' => 'duplicates', 'name' => 'View Duplicates', 'description' => 'Melihat daftar review duplikasi master'],
            ['code' => 'duplicates.resolve', 'module' => 'duplicates', 'name' => 'Resolve Duplicates', 'description' => 'Melakukan merge/keep separate pada review duplikasi'],
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

        // 4. Map Permissions to Roles
        $rolePermissionMap = [
            'super_admin' => [
                'teachers.view', 'teachers.manage', 'teachers.verify', 'teachers.import', 'teachers.export',
                'subjects.view', 'subjects.manage', 'subjects.import', 'subjects.export',
                'grade_levels.view', 'grade_levels.manage', 'grade_levels.import',
                'classrooms.view', 'classrooms.manage', 'classrooms.import', 'classrooms.export',
                'rooms.view', 'rooms.manage', 'rooms.import', 'rooms.export',
                'duplicates.view', 'duplicates.resolve',
            ],
            'kepala_sekolah' => [
                'teachers.view', 'teachers.verify', 'teachers.export',
                'subjects.view', 'subjects.export',
                'grade_levels.view',
                'classrooms.view', 'classrooms.export',
                'rooms.view', 'rooms.export',
                'duplicates.view', 'duplicates.resolve',
            ],
            'wakasek_kurikulum' => [
                'teachers.view', 'teachers.manage', 'teachers.import', 'teachers.export',
                'subjects.view', 'subjects.manage', 'subjects.import', 'subjects.export',
                'grade_levels.view', 'grade_levels.manage', 'grade_levels.import',
                'classrooms.view', 'classrooms.manage', 'classrooms.import', 'classrooms.export',
                'rooms.view', 'rooms.manage', 'rooms.import', 'rooms.export',
                'duplicates.view', 'duplicates.resolve',
            ],
            'admin_smp' => [
                'teachers.view', 'teachers.manage', 'teachers.import', 'teachers.export',
                'subjects.view', 'subjects.manage', 'subjects.import', 'subjects.export',
                'grade_levels.view', 'grade_levels.manage', 'grade_levels.import',
                'classrooms.view', 'classrooms.manage', 'classrooms.import', 'classrooms.export',
                'rooms.view', 'rooms.manage', 'rooms.import', 'rooms.export',
                'duplicates.view', 'duplicates.resolve',
            ],
            'admin_sma' => [
                'teachers.view', 'teachers.manage', 'teachers.import', 'teachers.export',
                'subjects.view', 'subjects.manage', 'subjects.import', 'subjects.export',
                'grade_levels.view', 'grade_levels.manage', 'grade_levels.import',
                'classrooms.view', 'classrooms.manage', 'classrooms.import', 'classrooms.export',
                'rooms.view', 'rooms.manage', 'rooms.import', 'rooms.export',
                'duplicates.view', 'duplicates.resolve',
            ],
            'tata_usaha' => [
                'teachers.view', 'teachers.manage',
                'classrooms.view', 'rooms.view',
            ],
            'guru' => [
                'teachers.view',
            ],
            'viewer_yayasan' => [
                'teachers.view', 'subjects.view', 'grade_levels.view', 'classrooms.view', 'rooms.view', 'duplicates.view',
            ],
        ];

        foreach ($rolePermissionMap as $roleCode => $permCodes) {
            $role = $db->table('roles')->where('code', $roleCode)->get()->getRowArray();
            if (!$role) {
                continue;
            }

            foreach ($permCodes as $permCode) {
                $perm = $db->table('permissions')->where('code', $permCode)->get()->getRowArray();
                if (!$perm) {
                    continue;
                }

                $exists = $db->table('role_permissions')
                    ->where('role_id', $role['id'])
                    ->where('permission_id', $perm['id'])
                    ->get()->getRowArray();

                if (!$exists) {
                    $db->table('role_permissions')->insert([
                        'role_id'       => $role['id'],
                        'permission_id' => $perm['id'],
                        'created_at'    => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }
    }
}
