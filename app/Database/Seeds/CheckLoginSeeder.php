<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CheckLoginSeeder extends Seeder
{
    public function run()
    {
        $db = $this->db;

        $adminUser = $db->table('users')->where('username', 'admin')->get()->getRowArray();
        echo "--- USER ADMIN RECORD ---\n";
        print_r($adminUser);

        $attempts = $db->table('login_attempts')->orderBy('id', 'DESC')->get(10)->getResultArray();
        echo "\n--- RECENT LOGIN ATTEMPTS ---\n";
        print_r($attempts);

        // Reset lockout if any
        $db->table('users')->where('username', 'admin')->update([
            'failed_login_count' => 0,
            'locked_until'       => null,
            'is_active'          => 1,
            'must_change_password' => 0,
        ]);
        $db->table('login_attempts')->truncate();
        echo "\nReset all lockout timers and login attempts successfully!\n";
    }
}
