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
            $userIdToSync = (int) $existing['id'];
            $db->table('users')->where('id', $userIdToSync)->update([
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
            $userIdToSync = (int) $db->insertID();
            echo "Akun 'admin' berhasil dibuat!\n";
        }

        $existingRole = $db->table('user_roles')->where('user_id', $userIdToSync)->where('role_id', $role['id'])->get()->getRowArray();
        if (!$existingRole) {
            $db->table('user_roles')->insert([
                'user_id'    => $userIdToSync,
                'role_id'    => $role['id'],
                'unit_id'    => null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        foreach ($units as $u) {
            $hasUnitAccess = $db->table('user_unit_access')
                ->where('user_id', $userIdToSync)
                ->where('unit_id', $u['id'])
                ->get()
                ->getRowArray();

            if (!$hasUnitAccess) {
                $db->table('user_unit_access')->insert([
                    'user_id'      => $userIdToSync,
                    'unit_id'      => $u['id'],
                    'access_level' => 'ADMIN',
                    'is_default'   => ($u['code'] === 'SMP') ? 1 : 0,
                    'created_at'   => date('Y-m-d H:i:s')
                ]);
            }
        }
    }
}
