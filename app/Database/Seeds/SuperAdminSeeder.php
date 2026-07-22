<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Services\UuidService;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        $db = $this->db;

        $username = 'admin';
        $password = 'Admin123!@#456';
        $fullName = 'Super Administrator';
        $email    = 'admin@wmvaa.id';

        $role = $db->table('roles')->where('code', 'super_admin')->get()->getRowArray();
        if (!$role) {
            echo "Role super_admin tidak ditemukan!\n";
            return;
        }

        $units = $db->table('school_units')->get()->getResultArray();
        $existing = $db->table('users')->where('username', $username)->get()->getRowArray();

        if ($existing) {
            $db->table('users')->where('id', $existing['id'])->update([
                'password_hash'        => password_hash($password, PASSWORD_BCRYPT),
                'is_active'            => 1,
                'must_change_password' => 0,
                'updated_at'           => date('Y-m-d H:i:s')
            ]);
            echo "Akun 'admin' diperbarui dengan password baru.\n";
        } else {
            $userId = UuidService::v4();
            $userData = [
                'uuid'                 => $userId,
                'username'             => $username,
                'email'                => $email,
                'full_name'            => $fullName,
                'password_hash'        => password_hash($password, PASSWORD_BCRYPT),
                'is_active'            => 1,
                'must_change_password' => 0,
                'created_at'           => date('Y-m-d H:i:s')
            ];
            $db->table('users')->insert($userData);
            $newUserId = $db->insertID();

            $db->table('user_roles')->insert([
                'user_id'    => $newUserId,
                'role_id'    => $role['id'],
                'unit_id'    => null,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            foreach ($units as $u) {
                $db->table('user_unit_access')->insert([
                    'user_id'      => $newUserId,
                    'unit_id'      => $u['id'],
                    'access_level' => 'ADMIN',
                    'is_default'   => ($u['code'] === 'SMP') ? 1 : 0,
                    'created_at'   => date('Y-m-d H:i:s')
                ]);
            }
            echo "Akun 'admin' berhasil dibuat!\n";
        }
    }
}
