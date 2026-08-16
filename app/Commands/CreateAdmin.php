<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\UuidService;
use App\Services\AuditService;
use Config\Database;

class CreateAdmin extends BaseCommand
{
    protected $group       = 'Akademia';
    protected $name        = 'akademia:create-admin';
    protected $description = 'Create a new Super Admin user for IALOS Education';
    protected $usage       = 'akademia:create-admin';
    protected $arguments   = [];
    protected $options     = [];

    public function run(array $params)
    {
        CLI::write('==============================================', 'cyan');
        CLI::write('    WMVAA AKADEMIA - CREATE SUPER ADMIN       ', 'cyan');
        CLI::write('==============================================', 'cyan');

        $db = Database::connect();

        // 1. Prompt Username
        do {
            $username = trim(CLI::prompt('Username'));
            if (empty($username)) {
                CLI::error('Username cannot be empty.');
                continue;
            }
            if (strlen($username) < 4) {
                CLI::error('Username must be at least 4 characters.');
                continue;
            }
            // Check unique
            $existing = $db->table('users')->where('username', $username)->get()->getRowArray();
            if ($existing) {
                CLI::error('Username already exists.');
                $username = '';
            }
        } while (empty($username));

        // 2. Prompt Full Name
        do {
            $fullName = trim(CLI::prompt('Full Name'));
            if (empty($fullName)) {
                CLI::error('Full Name cannot be empty.');
            }
        } while (empty($fullName));

        // 3. Prompt Email (Optional)
        do {
            $email = trim(CLI::prompt('Email (Optional)'));
            $emailValid = true;
            if (!empty($email)) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    CLI::error('Invalid email format.');
                    $emailValid = false;
                } else {
                    $existing = $db->table('users')->where('email', $email)->get()->getRowArray();
                    if ($existing) {
                        CLI::error('Email already exists.');
                        $emailValid = false;
                    }
                }
            } else {
                $email = null;
            }
        } while (!$emailValid);

        // 4. Prompt Password (masked input if supported, fallback to plain if not)
        $passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{12,}$/';
        do {
            $password = CLI::prompt('Password (min 12 chars, must include Upper, Lower, Number, Symbol)', '', 'required');
            if (!preg_match($passwordRegex, $password)) {
                CLI::error('Password does not meet complexity requirements.');
                $password = '';
                continue;
            }

            $passwordConfirm = CLI::prompt('Confirm Password', '', 'required');
            if ($password !== $passwordConfirm) {
                CLI::error('Passwords do not match.');
                $password = '';
            }
        } while (empty($password));

        // 5. Ask if password change is required on first login
        $mustChangeOpt = CLI::prompt('Force password change on first login? (y/n)', 'y');
        $mustChange = (strtolower($mustChangeOpt) === 'y' || strtolower($mustChangeOpt) === 'yes');

        // Execute user creation
        $db->transBegin();
        try {
            // Find super_admin role
            $role = $db->table('roles')->where('code', 'super_admin')->get()->getRowArray();
            if (!$role) {
                throw new \RuntimeException('Role "super_admin" not found. Please run CoreSeeder first.');
            }

            // Find SMP and SMA units
            $units = $db->table('school_units')->get()->getResultArray();
            if (empty($units)) {
                throw new \RuntimeException('No school units found. Please run CoreSeeder first.');
            }

            $userId = UuidService::v4();
            $userData = [
                'uuid'                 => $userId,
                'username'             => $username,
                'email'                => $email,
                'full_name'            => $fullName,
                'password_hash'        => password_hash($password, PASSWORD_BCRYPT),
                'is_active'            => 1,
                'must_change_password' => $mustChange ? 1 : 0,
                'created_at'           => date('Y-m-d H:i:s')
            ];

            $db->table('users')->insert($userData);
            $newUserId = $db->insertID();

            // Assign super_admin role (global/all units)
            $db->table('user_roles')->insert([
                'user_id'    => $newUserId,
                'role_id'    => $role['id'],
                'unit_id'    => null, // Global
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Assign unit access to SMP and SMA
            foreach ($units as $unit) {
                $db->table('user_unit_access')->insert([
                    'user_id'      => $newUserId,
                    'unit_id'      => $unit['id'],
                    'access_level' => 'ADMIN',
                    'is_default'   => ($unit['code'] === 'SMP') ? 1 : 0,
                    'created_at'   => date('Y-m-d H:i:s')
                ]);
            }

            $db->transCommit();

            // Log audit
            AuditService::log(
                'users',
                'create_admin_cli',
                'User',
                $newUserId,
                null,
                ['username' => $username, 'email' => $email, 'full_name' => $fullName, 'must_change_password' => $mustChange],
                'Super admin created via CLI command'
            );

            CLI::write('==============================================', 'green');
            CLI::write('Super Admin created successfully!', 'green');
            CLI::write("Username: {$username}", 'green');
            CLI::write("Must Change Password: " . ($mustChange ? 'Yes' : 'No'), 'green');
            CLI::write('==============================================', 'green');

        } catch (\Exception $e) {
            $db->transRollback();
            CLI::error('Failed to create Super Admin: ' . $e->getMessage());
        }
    }
}
