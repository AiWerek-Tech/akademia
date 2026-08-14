<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ElectiveModuleSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $permissions = [
            ['code' => 'electives.view', 'module' => 'electives', 'name' => 'View Electives', 'description' => 'Melihat rancangan dan kepatuhan mapel pilihan'],
            ['code' => 'electives.manage', 'module' => 'electives', 'name' => 'Manage Electives', 'description' => 'Mengelola periode dan penawaran mapel pilihan'],
            ['code' => 'electives.publish', 'module' => 'electives', 'name' => 'Publish Electives', 'description' => 'Mempublikasikan periode setelah pemeriksaan kepatuhan'],
            ['code' => 'electives.participants.manage', 'module' => 'electives', 'name' => 'Manage Elective Participants', 'description' => 'Mendaftarkan peserta pada periode pemilihan'],
            ['code' => 'electives.selection.manage', 'module' => 'electives', 'name' => 'Manage Student Selections', 'description' => 'Mengisi dan melihat pilihan atas nama siswa'],
            ['code' => 'electives.selection.submit', 'module' => 'electives', 'name' => 'Submit Own Selection', 'description' => 'Mengisi dan mengirim pilihan mata pelajaran sendiri'],
            ['code' => 'electives.selection.review', 'module' => 'electives', 'name' => 'Review Student Selections', 'description' => 'Melakukan review BK dan kurikulum'],
            ['code' => 'electives.change.request', 'module' => 'electives', 'name' => 'Request Elective Change', 'description' => 'Mengajukan perubahan pilihan sebelum batas regulasi'],
            ['code' => 'electives.change.approve', 'module' => 'electives', 'name' => 'Approve Elective Change', 'description' => 'Menilai ulang dan memutuskan perubahan pilihan'],
            ['code' => 'teacher_electives.view', 'module' => 'teacher_portal', 'name' => 'View Own Elective Students', 'description' => 'Melihat siswa yang memilih mapel pilihan yang diampu guru login'],
        ];
        foreach ($permissions as $permission) {
            $existing = $this->db->table('permissions')->where('code', $permission['code'])->get()->getRowArray();
            if (! $existing) {
                $this->db->table('permissions')->insert($permission + ['created_at' => $now, 'updated_at' => $now]);
            }
        }
        $studentRole = $this->db->table('roles')->where('code', 'siswa')->get()->getRowArray();
        if (! $studentRole) {
            $this->db->table('roles')->insert([
                'code' => 'siswa', 'name' => 'Siswa', 'description' => 'Akses mandiri pemilihan mata pelajaran',
                'scope' => 'UNIT', 'is_system' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        $permissionRows = $this->db->table('permissions')->whereIn('code', array_column($permissions, 'code'))->get()->getResultArray();
        foreach ($this->db->table('roles')->get()->getResultArray() as $role) {
            $code = strtoupper((string) $role['code']);
            foreach ($permissionRows as $permission) {
                $allowed = in_array($code, ['ADMIN', 'SUPERADMIN', 'SUPER_ADMIN', 'KURIKULUM', 'WAKASEK_KURIKULUM', 'ADMIN_SMA'], true)
                    || ($code === 'KEPALA_SEKOLAH' && in_array($permission['code'], ['electives.view', 'electives.publish'], true))
                    || ($code === 'GURU_BK' && in_array($permission['code'], ['electives.view', 'electives.selection.review', 'electives.change.approve'], true))
                    || (in_array($code, ['GURU', 'WALI_KELAS'], true) && $permission['code'] === 'teacher_electives.view')
                    || ($code === 'SISWA' && in_array($permission['code'], ['electives.selection.submit', 'electives.change.request'], true));
                if ($allowed && ! $this->db->table('role_permissions')->where([
                    'role_id' => $role['id'], 'permission_id' => $permission['id'],
                ])->countAllResults()) {
                    $this->db->table('role_permissions')->insert([
                        'role_id' => $role['id'], 'permission_id' => $permission['id'], 'created_at' => $now,
                    ]);
                }
            }
        }
    }
}
