<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\SchoolUnitModel;
use App\Services\AuditService;
use App\Services\UuidService;
use App\Services\UnitScopeService;
use Config\Database;

class UserController extends BaseController
{
    public function index()
    {
        if (!has_permission('users.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $unitIds = UnitScopeService::accessibleUnitIds();
        $userModel = new UserModel();
        $users = $unitIds === [] ? [] : $userModel
            ->select('users.*')
            ->join('user_unit_access uua', 'uua.user_id = users.id')
            ->whereIn('uua.unit_id', $unitIds)
            ->groupBy('users.id')
            ->orderBy('users.full_name', 'ASC')
            ->findAll();
        if (!$this->actorIsSuperAdmin()) {
            $superUserIds = array_map('intval', array_column(Database::connect()->table('user_roles ur')
                ->select('ur.user_id')->join('roles r', 'r.id = ur.role_id')
                ->whereIn('r.code', ['superadmin', 'super_admin'])->get()->getResultArray(), 'user_id'));
            $users = array_values(array_filter($users, static fn ($user) => !in_array((int) $user['id'], $superUserIds, true)));
        }

        return view('users/index', [
            'title'             => 'Manajemen Pengguna',
            'breadcrumb_active' => 'User Management',
            'users'             => $users
        ]);
    }

    public function create()
    {
        if (!has_permission('users.manage')) {
            return redirect()->to('/users')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $roleModel = new RoleModel();
        $roles = $roleModel->findAll();
        if (!$this->actorIsSuperAdmin()) {
            $roles = array_values(array_filter($roles, static fn ($role) => !in_array($role['code'], ['superadmin', 'super_admin'], true)));
        }

        $units = UnitScopeService::accessibleUnits();

        return view('users/create', [
            'title'             => 'Tambah Pengguna Baru',
            'breadcrumb_active' => 'Tambah Pengguna',
            'roles'             => $roles,
            'units'             => $units
        ]);
    }

    public function store()
    {
        if (!has_permission('users.manage')) {
            return redirect()->to('/users')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $rules = [
            'username'              => 'required|alpha_numeric_space|min_length[3]|max_length[100]|is_unique[users.username]',
            'email'                 => 'required|valid_email|max_length[150]|is_unique[users.email]',
            'full_name'             => 'required|min_length[3]|max_length[150]',
            'password'              => 'required|min_length[12]|regex_match[/[A-Z]/]|regex_match[/[a-z]/]|regex_match[/[0-9]/]|regex_match[/[\W]/]',
            'roles'                 => 'required',
            'units'                 => 'required',
            'is_active'             => 'required|in_list[0,1]',
            'must_change_password'  => 'required|in_list[0,1]'
        ];

        $messages = [
            'password' => [
                'regex_match' => 'Kata sandi harus mengandung huruf besar, huruf kecil, angka, dan simbol khusus.'
            ]
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $db = Database::connect();
        $db->transBegin();

        try {
            $userModel = new UserModel();

            $rawPassword = $this->request->getPost('password');
            $hashedPassword = password_hash($rawPassword, PASSWORD_BCRYPT, ['cost' => 12]);

            $userData = [
                'username'             => $this->request->getPost('username'),
                'email'                => $this->request->getPost('email'),
                'full_name'            => $this->request->getPost('full_name'),
                'password_hash'        => $hashedPassword,
                'is_active'            => (int)$this->request->getPost('is_active'),
                'must_change_password' => (int)$this->request->getPost('must_change_password'),
                'created_by'           => session()->get('user_id')
            ];

            $userModel->insert($userData);
            $newUserId = $userModel->insertID();

            $unitIds = UnitScopeService::assertUnits((array) $this->request->getPost('units'));

            // Insert roles
            $roleIds = (array)$this->request->getPost('roles');
            $this->assertRoleAssignments($roleIds);
            foreach ($roleIds as $rId) {
                $db->table('user_roles')->insert([
                    'user_id' => $newUserId,
                    'role_id' => (int)$rId
                ]);
            }

            // Insert units
            foreach ($unitIds as $uId) {
                $db->table('user_unit_access')->insert([
                    'user_id'    => $newUserId,
                    'unit_id'    => (int)$uId,
                    'is_default' => 0
                ]);
            }

            // Set first unit as default
            if (!empty($unitIds)) {
                $db->table('user_unit_access')
                    ->where('user_id', $newUserId)
                    ->where('unit_id', (int)$unitIds[0])
                    ->update(['is_default' => 1]);
            }

            $db->transCommit();

            // Log Audit
            AuditService::log(
                'users',
                'create',
                'User',
                $newUserId,
                null,
                $userData,
                'Pengguna baru didaftarkan lewat panel admin'
            );

            return redirect()->to('/users')->with('success', 'Pengguna baru berhasil ditambahkan.');
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan pengguna: ' . $e->getMessage());
        }
    }

    public function edit(string $uuid)
    {
        if (!has_permission('users.manage')) {
            return redirect()->to('/users')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $userModel = new UserModel();
        $user = $userModel->where('uuid', $uuid)->first();

        if (!$user) {
            return redirect()->to('/users')->with('error', 'Pengguna tidak ditemukan.');
        }
        try {
            $this->assertCanManageUser((int) $user['id']);
        } catch (\Throwable $e) {
            return redirect()->to('/users')->with('error', $e->getMessage());
        }

        $roleModel = new RoleModel();
        $roles = $roleModel->findAll();
        if (!$this->actorIsSuperAdmin()) {
            $roles = array_values(array_filter($roles, static fn ($role) => !in_array($role['code'], ['superadmin', 'super_admin'], true)));
        }

        $units = UnitScopeService::accessibleUnits();

        $db = Database::connect();
        $userRoles = array_column($db->table('user_roles')->where('user_id', $user['id'])->get()->getResultArray(), 'role_id');
        $userUnits = array_column($db->table('user_unit_access')->where('user_id', $user['id'])->get()->getResultArray(), 'unit_id');

        return view('users/edit', [
            'title'             => 'Edit Pengguna',
            'breadcrumb_active' => 'Edit Pengguna',
            'user'              => $user,
            'roles'             => $roles,
            'units'             => $units,
            'userRoles'         => $userRoles,
            'userUnits'         => $userUnits
        ]);
    }

    public function update(string $uuid)
    {
        if (!has_permission('users.manage')) {
            return redirect()->to('/users')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $userModel = new UserModel();
        $user = $userModel->where('uuid', $uuid)->first();

        if (!$user) {
            return redirect()->to('/users')->with('error', 'Pengguna tidak ditemukan.');
        }
        try {
            $this->assertCanManageUser((int) $user['id']);
        } catch (\Throwable $e) {
            return redirect()->to('/users')->with('error', $e->getMessage());
        }

        $rules = [
            'username'              => "required|alpha_numeric_space|min_length[3]|max_length[100]|is_unique[users.username,id,{$user['id']}]",
            'email'                 => "required|valid_email|max_length[150]|is_unique[users.email,id,{$user['id']}]",
            'full_name'             => 'required|min_length[3]|max_length[150]',
            'roles'                 => 'required',
            'units'                 => 'required',
            'is_active'             => 'required|in_list[0,1]',
            'must_change_password'  => 'required|in_list[0,1]'
        ];

        // Validate password complexity if provided
        $password = $this->request->getPost('password');
        if (!empty($password)) {
            $rules['password'] = 'min_length[12]|regex_match[/[A-Z]/]|regex_match[/[a-z]/]|regex_match[/[0-9]/]|regex_match[/[\W]/]';
        }

        $messages = [
            'password' => [
                'regex_match' => 'Kata sandi baru harus mengandung huruf besar, huruf kecil, angka, dan simbol khusus.'
            ]
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $db = Database::connect();
        $db->transBegin();

        try {
            $before = $user;
            
            $userData = [
                'username'             => $this->request->getPost('username'),
                'email'                => $this->request->getPost('email'),
                'full_name'            => $this->request->getPost('full_name'),
                'is_active'            => (int)$this->request->getPost('is_active'),
                'must_change_password' => (int)$this->request->getPost('must_change_password'),
                'updated_at'           => date('Y-m-d H:i:s')
            ];

            if (!empty($password)) {
                $userData['password_hash'] = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            }

            $userModel->update($user['id'], $userData);

            // Sync Roles
            $db->table('user_roles')->where('user_id', $user['id'])->delete();
            $roleIds = (array)$this->request->getPost('roles');
            $this->assertRoleAssignments($roleIds);
            foreach ($roleIds as $rId) {
                $db->table('user_roles')->insert([
                    'user_id' => $user['id'],
                    'role_id' => (int)$rId
                ]);
            }

            // Sync Units
            $unitIds = UnitScopeService::assertUnits((array) $this->request->getPost('units'));
            $db->table('user_unit_access')->where('user_id', $user['id'])->delete();
            foreach ($unitIds as $uId) {
                $db->table('user_unit_access')->insert([
                    'user_id'    => $user['id'],
                    'unit_id'    => (int)$uId,
                    'is_default' => 0
                ]);
            }

            if (!empty($unitIds)) {
                $db->table('user_unit_access')
                    ->where('user_id', $user['id'])
                    ->where('unit_id', (int)$unitIds[0])
                    ->update(['is_default' => 1]);
            }

            $db->transCommit();

            // Log Audit
            AuditService::log(
                'users',
                'update',
                'User',
                $user['id'],
                $before,
                $userData,
                'Data pengguna diperbarui lewat panel admin'
            );

            return redirect()->to('/users')->with('success', 'Data pengguna berhasil diperbarui.');
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui pengguna: ' . $e->getMessage());
        }
    }

    public function resetPassword(string $uuid)
    {
        if (!has_permission('users.manage')) {
            return redirect()->to('/users')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $userModel = new UserModel();
        $user = $userModel->where('uuid', $uuid)->first();

        if (!$user) {
            return redirect()->to('/users')->with('error', 'Pengguna tidak ditemukan.');
        }
        try {
            $this->assertCanManageUser((int) $user['id']);
        } catch (\Throwable $e) {
            return redirect()->to('/users')->with('error', $e->getMessage());
        }

        // Generate strong random password
        $symbols = '@#$!%*?&';
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' . $symbols;
        $passwordChars = ['A', 'a', '1', '!'];
        while (count($passwordChars) < 16) {
            $passwordChars[] = $characters[random_int(0, strlen($characters) - 1)];
        }
        for ($i = count($passwordChars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$passwordChars[$i], $passwordChars[$j]] = [$passwordChars[$j], $passwordChars[$i]];
        }
        $newPassword = implode('', $passwordChars);

        $hashed = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        $userModel->update($user['id'], [
            'password_hash'        => $hashed,
            'must_change_password' => 1,
            'password_changed_at'  => date('Y-m-d H:i:s'),
            'updated_at'           => date('Y-m-d H:i:s')
        ]);

        // Audit log
        AuditService::log(
            'users',
            'reset_password',
            'User',
            $user['id'],
            null,
            null,
            'Reset kata sandi pengguna secara paksa oleh administrator'
        );

        return redirect()->to('/users')->with('success', "Kata sandi untuk {$user['username']} berhasil direset ke: {$newPassword}. Harap catat sandi ini sebelum menutup halaman.");
    }

    private function actorIsSuperAdmin(): bool
    {
        $userId = (int) session()->get('user_id');
        if ($userId <= 0) {
            return false;
        }

        return Database::connect()->table('user_roles ur')
            ->join('roles r', 'r.id = ur.role_id')
            ->where('ur.user_id', $userId)
            ->whereIn('r.code', ['superadmin', 'super_admin'])
            ->countAllResults() > 0;
    }

    private function assertRoleAssignments(array $roleIds): void
    {
        $roleIds = array_values(array_unique(array_filter(array_map('intval', $roleIds))));
        if ($roleIds === []) {
            throw new \RuntimeException('Minimal satu peran wajib dipilih.');
        }

        $roles = Database::connect()->table('roles')->whereIn('id', $roleIds)->get()->getResultArray();
        if (count($roles) !== count($roleIds)) {
            throw new \RuntimeException('Salah satu peran yang dipilih tidak valid.');
        }

        foreach ($roles as $role) {
            if (in_array($role['code'], ['superadmin', 'super_admin'], true) && !$this->actorIsSuperAdmin()) {
                throw new \RuntimeException('Hanya Super Admin yang dapat memberikan peran Super Admin.');
            }
        }
    }

    private function assertCanManageUser(int $targetUserId): void
    {
        UnitScopeService::assertUser($targetUserId);
        $isTargetSuperAdmin = Database::connect()->table('user_roles ur')
            ->join('roles r', 'r.id = ur.role_id')
            ->where('ur.user_id', $targetUserId)
            ->whereIn('r.code', ['superadmin', 'super_admin'])
            ->countAllResults() > 0;
        if ($isTargetSuperAdmin && !$this->actorIsSuperAdmin()) {
            throw new \RuntimeException('Hanya Super Admin yang dapat mengelola akun Super Admin.');
        }
    }
}
