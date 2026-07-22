<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ResetAdminPasswordSeeder extends Seeder
{
    public function run()
    {
        $db = $this->db;

        $username = 'admin';
        $password = 'Admin123!@#456';

        $db->table('users')->where('username', $username)->update([
            'password_hash'        => password_hash($password, PASSWORD_BCRYPT),
            'is_active'            => 1,
            'must_change_password' => 0,
            'failed_login_count'   => 0,
            'locked_until'         => null,
            'updated_at'           => date('Y-m-d H:i:s')
        ]);

        $db->table('login_attempts')->truncate();
        echo "Password admin berhasil direset ke: {$password}\n";
    }
}
