<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\SchoolUnitModel;
use App\Services\AuditService;
use App\Services\UuidService;
use App\Services\UnitScopeService;
use App\Services\TeacherAccountProvisioningService;
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
            ->select("users.*, t.full_name as teacher_name, c.name as classroom_name, GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ', ') as role_names, GROUP_CONCAT(DISTINCT r.code ORDER BY r.code SEPARATOR ',') as role_codes")
            ->join('user_unit_access uua', 'uua.user_id = users.id')
            ->join('user_roles ur', 'ur.user_id = users.id', 'left')
            ->join('roles r', 'r.id = ur.role_id', 'left')
            ->join('teachers t', 't.id = users.teacher_id', 'left')
            ->join('classrooms c', 'c.id = users.classroom_id', 'left')
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
        $allowedRoleCodes = $this->allowedManagedRoleCodes();
        $actorIsSuperAdmin = $this->actorIsSuperAdmin();
        foreach ($users as &$user) {
            $targetRoleCodes = array_values(array_filter(explode(',', (string) ($user['role_codes'] ?? ''))));
            $user['can_manage'] = $actorIsSuperAdmin || array_diff($targetRoleCodes, $allowedRoleCodes) === [];
        }
        unset($user);

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
        $roles = $this->manageableRoles($roleModel->findAll());

        $units = UnitScopeService::accessibleUnits();
        $unitIds = array_map('intval', array_column($units, 'id'));

        $db = Database::connect();
        $teachers = $db->table('teachers t')
            ->select('t.*,
                      (SELECT id FROM users u WHERE u.teacher_id = t.id AND u.deleted_at IS NULL LIMIT 1) as linked_user_id,
                      (SELECT c.id FROM classrooms c WHERE c.homeroom_teacher_id = t.id AND c.deleted_at IS NULL LIMIT 1) as homeroom_classroom_id,
                      (SELECT c.name FROM classrooms c WHERE c.homeroom_teacher_id = t.id AND c.deleted_at IS NULL LIMIT 1) as homeroom_classroom_name')
            ->join('teacher_unit_assignments tua', 'tua.teacher_id = t.id', 'left')
            ->where('t.is_active', 1)
            ->where('t.deleted_at IS NULL')
            ->groupStart()->whereIn('t.primary_unit_id', $unitIds ?: [0])->orWhereIn('tua.unit_id', $unitIds ?: [0])->groupEnd()
            ->groupBy('t.id')
            ->orderBy('t.full_name', 'ASC')
            ->get()
            ->getResultArray();

        $classrooms = $db->table('classrooms c')
            ->select('c.*, t.full_name as homeroom_teacher_name')
            ->join('teachers t', 't.id = c.homeroom_teacher_id', 'left')
            ->where('c.is_active', 1)
            ->where('c.deleted_at IS NULL')
            ->whereIn('c.unit_id', $unitIds ?: [0])
            ->orderBy('c.name', 'ASC')
            ->get()
            ->getResultArray();

        $selectedTeacherId = $this->request->getGet('teacher_id');

        return view('users/create', [
            'title'             => 'Tambah Pengguna Baru',
            'breadcrumb_active' => 'Tambah Pengguna',
            'roles'             => $roles,
            'units'             => $units,
            'teachers'          => $teachers,
            'classrooms'        => $classrooms,
            'selectedTeacherId' => $selectedTeacherId ? (int)$selectedTeacherId : null
        ]);
    }

    public function store()
    {
        if (!has_permission('users.manage')) {
            return redirect()->to('/users')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $rules = [
            'username'              => 'required|regex_match[/^[a-zA-Z0-9._-]+$/]|min_length[3]|max_length[100]|is_unique[users.username]',
            'email'                 => 'permit_empty|valid_email|max_length[150]|is_unique[users.email]',
            'full_name'             => 'required|min_length[3]|max_length[150]',
            'password'              => 'required|min_length[12]|regex_match[/[A-Z]/]|regex_match[/[a-z]/]|regex_match[/[0-9]/]|regex_match[/[\W]/]',
            'roles'                 => 'required',
            'units'                 => 'required',
            'is_active'             => 'required|in_list[0,1]',
            'must_change_password'  => 'required|in_list[0,1]',
            'must_change_username'  => 'permit_empty|in_list[0,1]',
            'teacher_id'            => 'permit_empty',
            'classroom_id'          => 'permit_empty'
        ];

        $messages = [
            'username' => [
                'regex_match' => 'Username hanya boleh berisi huruf, angka, titik (.), strip (-), dan garis bawah (_).'
            ],
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

            $teacherId = $this->request->getPost('teacher_id');
            $classroomId = $this->request->getPost('classroom_id');
            $email = trim($this->request->getPost('email') ?? '');
            $roleIds = (array)$this->request->getPost('roles');
            $this->assertRoleAssignments($roleIds);
            $unitIds = UnitScopeService::assertUnits((array) $this->request->getPost('units'));
            [$teacherId, $classroomId] = $this->normalizeEntityLinks($roleIds, $teacherId, $classroomId, $email, $unitIds);

            $userData = [
                'username'             => trim($this->request->getPost('username')),
                'email'                => !empty($email) ? $email : null,
                'full_name'            => trim($this->request->getPost('full_name')),
                'password_hash'        => $hashedPassword,
                'is_active'            => (int)$this->request->getPost('is_active'),
                'must_change_password' => (int)$this->request->getPost('must_change_password'),
                'must_change_username' => $this->request->getPost('must_change_username') === null
                    ? 1
                    : (int)$this->request->getPost('must_change_username'),
                'teacher_id'           => !empty($teacherId) ? (int)$teacherId : null,
                'classroom_id'         => !empty($classroomId) ? (int)$classroomId : null,
                'created_by'           => session()->get('user_id')
            ];

            $userModel->insert($userData);
            $newUserId = $userModel->insertID();

            // Insert roles
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
        $roles = $this->manageableRoles($roleModel->findAll());

        $units = UnitScopeService::accessibleUnits();
        $unitIds = array_map('intval', array_column($units, 'id'));

        $db = Database::connect();
        $userRoles = array_column($db->table('user_roles')->where('user_id', $user['id'])->get()->getResultArray(), 'role_id');
        $userUnits = array_column($db->table('user_unit_access')->where('user_id', $user['id'])->get()->getResultArray(), 'unit_id');

        $teachers = $db->table('teachers t')->select('t.*')
            ->join('teacher_unit_assignments tua', 'tua.teacher_id = t.id', 'left')
            ->where('t.is_active', 1)->where('t.deleted_at IS NULL')
            ->groupStart()->whereIn('t.primary_unit_id', $unitIds ?: [0])->orWhereIn('tua.unit_id', $unitIds ?: [0])->groupEnd()
            ->groupBy('t.id')->orderBy('t.full_name', 'ASC')->get()->getResultArray();
        $classrooms = $db->table('classrooms')->whereIn('unit_id', $unitIds ?: [0])
            ->where('is_active', 1)->where('deleted_at IS NULL')->orderBy('name', 'ASC')->get()->getResultArray();

        return view('users/edit', [
            'title'             => 'Edit Pengguna',
            'breadcrumb_active' => 'Edit Pengguna',
            'user'              => $user,
            'roles'             => $roles,
            'units'             => $units,
            'userRoles'         => $userRoles,
            'userUnits'         => $userUnits,
            'teachers'          => $teachers,
            'classrooms'        => $classrooms
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
            'username'              => "required|regex_match[/^[a-zA-Z0-9._-]+$/]|min_length[3]|max_length[100]|is_unique[users.username,id,{$user['id']}]",
            'email'                 => "permit_empty|valid_email|max_length[150]|is_unique[users.email,id,{$user['id']}]",
            'full_name'             => 'required|min_length[3]|max_length[150]',
            'roles'                 => 'required',
            'units'                 => 'required',
            'is_active'             => 'required|in_list[0,1]',
            'must_change_password'  => 'required|in_list[0,1]',
            'must_change_username'  => 'permit_empty|in_list[0,1]'
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

            $teacherId = $this->request->getPost('teacher_id');
            $classroomId = $this->request->getPost('classroom_id');
            $email = trim($this->request->getPost('email') ?? '');
            $roleIds = (array)$this->request->getPost('roles');
            $this->assertRoleAssignments($roleIds);
            $unitIds = UnitScopeService::assertUnits((array) $this->request->getPost('units'));
            [$teacherId, $classroomId] = $this->normalizeEntityLinks($roleIds, $teacherId, $classroomId, $email, $unitIds);

            $userData = [
                'username'             => trim($this->request->getPost('username')),
                'email'                => !empty($email) ? $email : null,
                'full_name'            => trim($this->request->getPost('full_name')),
                'is_active'            => (int)$this->request->getPost('is_active'),
                'must_change_password' => (int)$this->request->getPost('must_change_password'),
                'must_change_username' => $this->request->getPost('must_change_username') === null
                    ? (int)($user['must_change_username'] ?? 0)
                    : (int)$this->request->getPost('must_change_username'),
                'teacher_id'           => !empty($teacherId) ? (int)$teacherId : null,
                'classroom_id'         => !empty($classroomId) ? (int)$classroomId : null,
                'updated_at'           => date('Y-m-d H:i:s')
            ];

            if (!empty($password)) {
                $userData['password_hash'] = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            }

            $userModel->update($user['id'], $userData);

            // Sync Roles
            $db->table('user_roles')->where('user_id', $user['id'])->delete();
            foreach ($roleIds as $rId) {
                $db->table('user_roles')->insert([
                    'user_id' => $user['id'],
                    'role_id' => (int)$rId
                ]);
            }

            // Sync Units
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
        if (!has_permission('users.reset_password')) {
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

        // Temporary reset credentials are deliberately easy to type. They are
        // random per account and cannot be retained after the next login.
        $newPassword = TeacherAccountProvisioningService::temporaryPassword();

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

    public function provisionTeachers()
    {
        if (!has_permission('users.manage') || !$this->actorIsSuperAdmin()) {
            return redirect()->to('/users')->with('error', 'Provisioning massal akun guru hanya dapat dilakukan oleh superadmin.');
        }

        try {
            $result = TeacherAccountProvisioningService::provisionAll((int) session()->get('user_id'));
            AuditService::log(
                'users', 'provision_teacher_accounts', 'User', null, null,
                ['created' => $result['created'], 'reconciled' => $result['reconciled']],
                'Superadmin menjalankan provisioning akun guru massal'
            );
            if ($result['credentials'] === []) {
                return redirect()->to('/users')->with('success', "Tidak ada akun baru. {$result['reconciled']} akun guru sudah direkonsiliasi.");
            }

            $handle = fopen('php://temp', 'w+b');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['ID Guru', 'Nama Guru', 'Username Sementara', 'Password Sementara', 'Unit', 'Role']);
            foreach ($result['credentials'] as $credential) {
                fputcsv($handle, [
                    $credential['teacher_id'], $credential['full_name'], $credential['username'],
                    $credential['temporary_password'], $credential['units'], $credential['roles'],
                ]);
            }
            rewind($handle);
            $csv = stream_get_contents($handle);
            fclose($handle);

            return $this->response
                ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
                ->setHeader('Content-Disposition', 'attachment; filename="kredensial-guru-' . date('Ymd-His') . '.csv"')
                ->setBody($csv);
        } catch (\Throwable $e) {
            return redirect()->to('/users')->with('error', 'Provisioning akun guru gagal: ' . $e->getMessage());
        }
    }

    /**
     * Resolves safe teacher/classroom linkage for personal roles. Existing
     * accounts without a profile remain editable, while deterministic links
     * are filled automatically from the teacher email or homeroom record.
     *
     * @return array{0:?int,1:?int}
     */
    private function normalizeEntityLinks(array $roleIds, $teacherId, $classroomId, string $email, array $unitIds): array
    {
        $db = Database::connect();
        $teacherId = !empty($teacherId) ? (int) $teacherId : null;
        $classroomId = !empty($classroomId) ? (int) $classroomId : null;
        $roleCodes = $roleIds === [] ? [] : array_column(
            $db->table('roles')->select('code')->whereIn('id', array_map('intval', $roleIds))->get()->getResultArray(),
            'code'
        );

        if (in_array('wali_kelas', $roleCodes, true) && $classroomId) {
            $classroom = $db->table('classrooms')
                ->select('id, homeroom_teacher_id')->where('id', $classroomId)
                ->whereIn('unit_id', $unitIds ?: [0])
                ->where('is_active', 1)->where('deleted_at IS NULL')->get()->getRowArray();
            if (!$classroom) {
                throw new \RuntimeException('Rombel Wali Kelas tidak valid atau sudah tidak aktif.');
            }
            $homeroomTeacherId = (int) ($classroom['homeroom_teacher_id'] ?? 0);
            if ($homeroomTeacherId > 0 && $teacherId && $teacherId !== $homeroomTeacherId) {
                throw new \RuntimeException('Profil guru tidak sama dengan wali kelas resmi pada rombel yang dipilih.');
            }
            if ($homeroomTeacherId > 0) {
                $teacherId = $homeroomTeacherId;
            }
        }

        if (!$teacherId && $email !== '' && array_intersect($roleCodes, ['guru', 'wali_kelas'])) {
            $matches = $db->table('teachers')->select('id')->where('email', $email)
                ->where('is_active', 1)->where('deleted_at IS NULL')->limit(2)->get()->getResultArray();
            if (count($matches) === 1) {
                $teacherId = (int) $matches[0]['id'];
            }
        }

        if ($teacherId) {
            $teacherInScope = $db->table('teachers t')->select('t.id')->distinct()
                ->join('teacher_unit_assignments tua', 'tua.teacher_id = t.id', 'left')
                ->where('t.id', $teacherId)->where('t.deleted_at IS NULL')
                ->groupStart()->whereIn('t.primary_unit_id', $unitIds ?: [0])->orWhereIn('tua.unit_id', $unitIds ?: [0])->groupEnd()
                ->countAllResults() > 0;
            if (!$teacherInScope) {
                throw new \RuntimeException('Profil guru berada di luar unit akses yang dipilih.');
            }
        }

        return [$teacherId, $classroomId];
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

        if (!$this->actorIsSuperAdmin()) {
            $allowed = $this->allowedManagedRoleCodes();
            foreach ($roles as $role) {
                if (!in_array((string) $role['code'], $allowed, true)) {
                    throw new \RuntimeException('Anda tidak dapat memberikan peran ' . $role['name'] . '.');
                }
            }
        }
    }

    private function assertCanManageUser(int $targetUserId): void
    {
        UnitScopeService::assertUser($targetUserId);
        $targetRoles = Database::connect()->table('user_roles ur')
            ->select('r.code, r.name')
            ->join('roles r', 'r.id = ur.role_id')
            ->where('ur.user_id', $targetUserId)
            ->get()->getResultArray();
        if (!$this->actorIsSuperAdmin()) {
            $allowed = $this->allowedManagedRoleCodes();
            foreach ($targetRoles as $role) {
                if (!in_array((string) $role['code'], $allowed, true)) {
                    throw new \RuntimeException('Anda tidak dapat mengelola akun dengan peran ' . $role['name'] . '.');
                }
            }
        }
    }

    /** @return list<string> */
    private function allowedManagedRoleCodes(): array
    {
        if ($this->actorIsSuperAdmin()) {
            return array_column(Database::connect()->table('roles')->select('code')->get()->getResultArray(), 'code');
        }
        if (has_role('admin_smp', 'admin_sma')) {
            return ['guru', 'wali_kelas', 'siswa', 'tata_usaha'];
        }
        if (has_role('wakasek_kurikulum', 'tata_usaha')) {
            return ['guru', 'wali_kelas', 'siswa'];
        }

        return [];
    }

    private function manageableRoles(array $roles): array
    {
        $allowed = $this->allowedManagedRoleCodes();
        return array_values(array_filter(
            $roles,
            static fn (array $role): bool => in_array((string) $role['code'], $allowed, true)
        ));
    }
}
