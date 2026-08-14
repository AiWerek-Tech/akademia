<?php

namespace App\Controllers;

use Config\Database;
use Config\Services;
use App\Services\AuditService;

class AuthController extends BaseController
{
    public function login()
    {
        // If already logged in, redirect to dashboard
        if (session()->get('logged_in')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login');
    }

    public function attemptLogin()
    {
        $session = session();
        $request = Services::request();
        $db = Database::connect();

        // 1. CSRF and Validation Check
        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $username = trim($this->request->getPost('username'));
        $password = $this->request->getPost('password');
        $ip = $request->getIPAddress();
        $userAgent = substr($request->getUserAgent()->getAgentString(), 0, 255);

        // 2. IP Lockout Check (Max 5 failures in 15 minutes)
        $timeWindow = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        $failedIpAttempts = $db->table('login_attempts')
            ->where('ip_address', $ip)
            ->where('successful', 0)
            ->where('attempted_at >=', $timeWindow)
            ->countAllResults();

        if ($failedIpAttempts >= 5) {
            // Log attempt
            $db->table('login_attempts')->insert([
                'username'       => $username,
                'ip_address'     => $ip,
                'user_agent'     => $userAgent,
                'successful'     => 0,
                'failure_reason' => 'IP Lockout active',
                'attempted_at'   => date('Y-m-d H:i:s')
            ]);

            return redirect()->back()->withInput()->with('error', 'Terlahu banyak percobaan masuk gagal dari IP Anda. Silakan coba lagi dalam 15 menit.');
        }

        // 3. User Lookup
        $user = $db->table('users')->where('username', $username)->where('deleted_at', null)->get()->getRowArray();

        if (!$user) {
            // Log attempt
            $db->table('login_attempts')->insert([
                'username'       => $username,
                'ip_address'     => $ip,
                'user_agent'     => $userAgent,
                'successful'     => 0,
                'failure_reason' => 'User not found',
                'attempted_at'   => date('Y-m-d H:i:s')
            ]);

            // Fake verification to prevent timing attack
            password_verify($password, '$2y$10$abcdefghijklmnopqrstuvwxyzaaaaaaaaaaaaaaaaaaaaaaaaa');

            return redirect()->back()->withInput()->with('error', 'Username atau password salah.');
        }

        // 4. User Lockout Check
        if ($user['locked_until'] !== null && strtotime($user['locked_until']) > time()) {
            $minutesLeft = ceil((strtotime($user['locked_until']) - time()) / 60);

            // Log attempt
            $db->table('login_attempts')->insert([
                'username'       => $username,
                'user_id'        => $user['id'],
                'ip_address'     => $ip,
                'user_agent'     => $userAgent,
                'successful'     => 0,
                'failure_reason' => 'User account locked out',
                'attempted_at'   => date('Y-m-d H:i:s')
            ]);

            return redirect()->back()->withInput()->with('error', "Akun Anda sedang terkunci karena terlalu banyak percobaan salah. Silakan coba lagi dalam {$minutesLeft} menit.");
        }

        // 5. User active check
        if ((int)$user['is_active'] !== 1) {
            // Log attempt
            $db->table('login_attempts')->insert([
                'username'       => $username,
                'user_id'        => $user['id'],
                'ip_address'     => $ip,
                'user_agent'     => $userAgent,
                'successful'     => 0,
                'failure_reason' => 'Inactive user',
                'attempted_at'   => date('Y-m-d H:i:s')
            ]);

            return redirect()->back()->withInput()->with('error', 'Akun Anda tidak aktif. Silakan hubungi administrator.');
        }

        // 6. Verify Password
        if (password_verify($password, $user['password_hash'])) {
            // Success!
            
            // Check Unit Access
            $unitAccess = $db->table('user_unit_access')->where('user_id', $user['id'])->get()->getResultArray();
            if (empty($unitAccess)) {
                $db->table('login_attempts')->insert([
                    'username'       => $username,
                    'user_id'        => $user['id'],
                    'ip_address'     => $ip,
                    'user_agent'     => $userAgent,
                    'successful'     => 0,
                    'failure_reason' => 'No unit access assigned',
                    'attempted_at'   => date('Y-m-d H:i:s')
                ]);
                return redirect()->back()->withInput()->with('error', 'Anda tidak memiliki hak akses ke unit sekolah mana pun. Silakan hubungi administrator.');
            }

            // Reset failed login status
            $db->table('users')->where('id', $user['id'])->update([
                'failed_login_count' => 0,
                'locked_until'       => null,
                'last_login_at'      => date('Y-m-d H:i:s'),
                'last_login_ip'      => $ip
            ]);

            // Log attempt
            $db->table('login_attempts')->insert([
                'username'       => $username,
                'user_id'        => $user['id'],
                'ip_address'     => $ip,
                'user_agent'     => $userAgent,
                'successful'     => 1,
                'attempted_at'   => date('Y-m-d H:i:s')
            ]);

            // Log successful login audit
            AuditService::log('auth', 'login_success', 'User', $user['id']);

            // Session Regeneration (Fix Session Fixation)
            $session->regenerate(true);

            // Determine default unit context
            $defaultUnitId = null;
            foreach ($unitAccess as $ua) {
                if ((int)$ua['is_default'] === 1) {
                    $defaultUnitId = (int)$ua['unit_id'];
                    break;
                }
            }
            if ($defaultUnitId === null && !empty($unitAccess)) {
                $defaultUnitId = (int)$unitAccess[0]['unit_id'];
            }

            // Determine active period context
            $activePeriod = $db->table('academic_periods')->where('is_active', 1)->get()->getRowArray();
            $activePeriodId = $activePeriod ? (int)$activePeriod['id'] : null;

            $userModel = new \App\Models\UserModel();
            $roleContext = $userModel->getRoleContext((int) $user['id'], $defaultUnitId);
            $roleCode = $roleContext['primary']['code'];
            $roleName = $roleContext['primary']['name'];
            $allRoleCodes = $roleContext['codes'];

            // Fetch user permissions
            $permissions = $userModel->getPermissions((int)$user['id'], $defaultUnitId);

            // Set session variables
            $session->set([
                'user_id'            => (int)$user['id'],
                'user_uuid'          => $user['uuid'],
                'username'           => $user['username'],
                'full_name'          => $user['full_name'],
                'logged_in'          => true,
                'auth_timestamp'     => time(),
                'active_unit_id'     => $defaultUnitId,
                'active_period_id'   => $activePeriodId,
                'role_code'          => $roleCode,
                'role_name'          => $roleName,
                'all_role_codes'     => $allRoleCodes,
                'permissions'        => $permissions,
                'teacher_id'         => $user['teacher_id'] ?? null,
                'classroom_id'       => $user['classroom_id'] ?? null,
                'must_change_password' => (int)$user['must_change_password'] === 1,
                'must_change_username' => (int)($user['must_change_username'] ?? 0) === 1,
            ]);

            // Check if password change is forced
            if ((int)$user['must_change_password'] === 1 || (int)($user['must_change_username'] ?? 0) === 1) {
                return redirect()->to('/change-password');
            }

            return redirect()->to('/dashboard');
        } else {
            // Password fail
            $newFailedCount = (int)$user['failed_login_count'] + 1;
            $updateData = ['failed_login_count' => $newFailedCount];
            
            $lockoutTriggered = false;
            if ($newFailedCount >= 5) {
                $updateData['locked_until'] = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                $updateData['failed_login_count'] = 0; // Reset counter for next attempt cycle
                $lockoutTriggered = true;
            }

            $db->table('users')->where('id', $user['id'])->update($updateData);

            // Log attempt
            $db->table('login_attempts')->insert([
                'username'       => $username,
                'user_id'        => $user['id'],
                'ip_address'     => $ip,
                'user_agent'     => $userAgent,
                'successful'     => 0,
                'failure_reason' => $lockoutTriggered ? 'Lockout triggered' : 'Incorrect password',
                'attempted_at'   => date('Y-m-d H:i:s')
            ]);

            $message = $lockoutTriggered 
                ? 'Terlalu banyak percobaan salah. Akun Anda dikunci selama 15 menit.' 
                : 'Username atau password salah.';

            return redirect()->back()->withInput()->with('error', $message);
        }
    }

    public function logout()
    {
        // Logout must be POST only and requires CSRF (handled at filter/routing level)
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->back();
        }

        $session = session();
        $userId = $session->get('user_id');

        if ($userId) {
            AuditService::log('auth', 'logout', 'User', $userId);
        }

        $session->destroy();

        return redirect()->to('/login');
    }

    public function changePassword()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        return view('auth/change_password');
    }

    public function attemptChangePassword()
    {
        $session = session();
        $db = Database::connect();

        if (!$session->get('logged_in')) {
            return redirect()->to('/login');
        }

        $userId = $session->get('user_id');
        $isForcedChange = (bool) $session->get('must_change_password');
        $mustChangeUsername = (bool) $session->get('must_change_username');
        $isForcedChange = $isForcedChange || $mustChangeUsername;

        $rules = [
            'new_password'     => 'required|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{12,}$/]',
            'confirm_password' => 'required|matches[new_password]',
        ];

        // During first login/reset, the password was already verified by login,
        // so asking for it again is unnecessary and the form does not expose it.
        if (!$isForcedChange) {
            $rules['current_password'] = 'required';
        }
        if ($mustChangeUsername) {
            $rules['new_username'] = 'required|regex_match[/^[a-zA-Z0-9._-]+$/]|min_length[4]|max_length[50]';
        }

        $messages = [
            'new_password' => [
                'regex_match' => 'Password baru harus minimal 12 karakter dan mengandung huruf besar, huruf kecil, angka, dan simbol.'
            ]
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $currentPassword = (string) $this->request->getPost('current_password');
        $newPassword = $this->request->getPost('new_password');

        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();
        if (!$user) {
            return redirect()->to('/login');
        }

        // Verify the current password only for a normal, self-initiated change.
        if (!$isForcedChange && !password_verify($currentPassword, $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Password saat ini salah.');
        }

        // New password cannot be the same as current
        if (password_verify($newPassword, $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Password baru tidak boleh sama dengan password saat ini.');
        }

        $newUsername = trim((string) $this->request->getPost('new_username'));
        if ($mustChangeUsername) {
            if (strcasecmp($newUsername, (string) $user['username']) === 0) {
                return redirect()->back()->withInput()->with('error', 'Username baru harus berbeda dari username sementara.');
            }
            $duplicateUsername = $db->table('users')
                ->where('username', $newUsername)
                ->where('id !=', $userId)
                ->where('deleted_at IS NULL')
                ->get()->getRowArray();
            if ($duplicateUsername) {
                return redirect()->back()->withInput()->with('error', 'Username tersebut sudah digunakan. Silakan pilih username lain.');
            }
        }

        $updateData = [
            'password_hash'        => password_hash($newPassword, PASSWORD_BCRYPT),
            'must_change_password' => 0,
            'password_changed_at'  => date('Y-m-d H:i:s')
        ];
        if ($mustChangeUsername) {
            $updateData['username'] = $newUsername;
            $updateData['must_change_username'] = 0;
            $updateData['username_changed_at'] = date('Y-m-d H:i:s');
        }
        $db->table('users')->where('id', $userId)->update($updateData);

        // Log audit
        AuditService::log(
            'auth',
            'change_password',
            'User',
            $userId,
            ['must_change_password' => (int)$user['must_change_password'], 'must_change_username' => (int)($user['must_change_username'] ?? 0)],
            ['must_change_password' => 0, 'must_change_username' => 0],
            'User changed their own login credentials'
        );

        // Update session state
        $session->set('must_change_password', false);
        $session->set('must_change_username', false);
        if ($mustChangeUsername) {
            $session->set('username', $newUsername);
        }
        $session->set('auth_timestamp', time());
        $session->regenerate(true);

        return redirect()->to('/dashboard')->with('success', $mustChangeUsername ? 'Username dan password Anda berhasil diperbarui.' : 'Password Anda berhasil diperbarui.');
    }
}
