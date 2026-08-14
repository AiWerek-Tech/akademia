<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\IsolatedDatabaseTestTrait;
use App\Database\Seeds\CoreSeeder;
use Config\Database;
use Config\RolePermissions;

/**
 * Extended RBAC & Unit Isolation Tests
 *
 * @internal
 */
final class ExtendedRbacTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use IsolatedDatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    private function createTestUserWithRole(string $username, string $roleCode, string $unitCode = 'SMP'): array
    {
        $db = Database::connect($this->DBGroup);

        $db->table('users')->insert([
            'uuid'                 => \App\Services\UuidService::v4(),
            'username'             => $username,
            'email'                => $username . '@test.com',
            'full_name'            => 'Test ' . $username,
            'password_hash'        => password_hash('SomePass123!', PASSWORD_BCRYPT),
            'is_active'            => 1,
            'must_change_password' => 0,
            'created_at'           => date('Y-m-d H:i:s'),
        ]);
        $insertId = $db->insertID();

        $unit = $db->table('school_units')->where('code', $unitCode)->get()->getRowArray();
        $db->table('user_unit_access')->insert([
            'user_id'      => $insertId,
            'unit_id'      => $unit['id'],
            'access_level' => ($roleCode === 'super_admin') ? 'ADMIN' : 'MEMBER',
            'is_default'   => 1,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        $role = $db->table('roles')->where('code', $roleCode)->get()->getRowArray();
        $db->table('user_roles')->insert([
            'user_id'    => $insertId,
            'role_id'    => $role['id'],
            'unit_id'    => ($role['scope'] === 'UNIT') ? $unit['id'] : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Build permissions array
        $userModel = new \App\Models\UserModel();
        $permissions = $userModel->getPermissions($insertId, $unit['id']);

        return [
            'id'          => $insertId,
            'role_id'     => $role['id'],
            'unit_id'     => $unit['id'],
            'permissions' => $permissions,
        ];
    }

    // ── Guest access to /users is denied by AuthFilter ─────────
    public function testGuestAccessRedirectsToLogin(): void
    {
        $result = $this->get('users');
        $result->assertRedirectTo('login');
    }

    // ── Guru cannot access user management ─────────────────────
    public function testGuruCannotManageUsers(): void
    {
        $user = $this->createTestUserWithRole('guru_rbac', 'guru');

        $result = $this->withSession([
            'logged_in'      => true,
            'user_id'        => $user['id'],
            'username'       => 'guru_rbac',
            'role_code'      => 'guru',
            'active_unit_id' => $user['unit_id'],
            'permissions'    => $user['permissions'],
        ])->get('users');

        $result->assertRedirectTo('dashboard');
        $result->assertSessionHas('error');
    }

    public function testGuruReceivesOnlyPersonalPortalPermissions(): void
    {
        $user = $this->createTestUserWithRole('guru_personal_only', 'guru');

        $actual = $user['permissions'];
        sort($actual);
        $expected = RolePermissions::GURU;
        sort($expected);

        $this->assertSame($expected, $actual);
        $this->assertSame([], array_values(array_intersect($actual, RolePermissions::ADMINISTRATIVE_PERMISSIONS)));
    }

    public function testSuperAdminCanCustomizeGuruPermissions(): void
    {
        $db = Database::connect($this->DBGroup);
        $super = $this->createTestUserWithRole('super_role_editor', 'super_admin');
        $guru = $db->table('roles')->where('code', 'guru')->get()->getRowArray();
        $dashboard = $db->table('permissions')->where('code', 'dashboard.view')->get()->getRowArray();

        $result = $this->withSession([
            'logged_in' => true,
            'user_id' => $super['id'],
            'username' => 'super_role_editor',
            'role_code' => 'super_admin',
            'all_role_codes' => ['super_admin'],
            'active_unit_id' => $super['unit_id'],
            'permissions' => $super['permissions'],
        ])->post('roles/' . $guru['id'] . '/permissions', [
            'permissions' => [$dashboard['id']],
        ]);

        $result->assertRedirectTo('roles');
        $actual = array_map('intval', array_column(
            $db->table('role_permissions')->where('role_id', $guru['id'])->get()->getResultArray(),
            'permission_id'
        ));
        $this->assertSame([(int) $dashboard['id']], $actual);
    }

    public function testPersonalRoleDashboardStaysPersonalWhenGivenOneAdministrativePermission(): void
    {
        $db = Database::connect($this->DBGroup);
        $guruUser = $this->createTestUserWithRole('guru_dashboard_guard', 'guru');
        $now = date('Y-m-d H:i:s');
        $db->table('teachers')->insert([
            'uuid' => \App\Services\UuidService::v4(),
            'full_name' => 'Guru Dashboard Guard',
            'normalized_name' => 'GURU DASHBOARD GUARD',
            'primary_unit_id' => $guruUser['unit_id'],
            'is_active' => 1,
            'created_at' => $now,
        ]);
        $teacherId = (int) $db->insertID();
        $db->table('users')->where('id', $guruUser['id'])->update(['teacher_id' => $teacherId]);
        $db->table('academic_years')->insert([
            'uuid' => \App\Services\UuidService::v4(),
            'name' => 'Dashboard Guard 2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'ACTIVE',
            'is_active' => 1,
            'created_at' => $now,
        ]);
        $yearId = (int) $db->insertID();
        $db->table('academic_periods')->insert([
            'uuid' => \App\Services\UuidService::v4(),
            'academic_year_id' => $yearId,
            'semester_number' => 1,
            'name' => 'Ganjil Dashboard Guard',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'workflow_status' => 'APPROVED',
            'is_active' => 1,
            'created_at' => $now,
        ]);
        $periodId = (int) $db->insertID();
        $guruRole = $db->table('roles')->where('code', 'guru')->get()->getRowArray();
        $teachersPermission = $db->table('permissions')->where('code', 'teachers.view')->get()->getRowArray();
        $db->table('role_permissions')->insert([
            'role_id' => $guruRole['id'],
            'permission_id' => $teachersPermission['id'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $result = $this->withSession([
            'logged_in' => true,
            'user_id' => $guruUser['id'],
            'username' => 'guru_dashboard_guard',
            'full_name' => 'Guru Dashboard Guard',
            'role_code' => 'guru',
            'role_name' => 'Guru',
            'all_role_codes' => ['guru'],
            'active_unit_id' => $guruUser['unit_id'],
            'active_period_id' => $periodId,
            'teacher_id' => $teacherId,
            'permissions' => array_merge($guruUser['permissions'], ['teachers.view']),
        ])->get('dashboard');

        $result->assertOK();
        $body = $result->getBody();
        $this->assertStringContainsString('Ruang Kerja Personal', $body);
        $this->assertStringContainsString('Dokumen &amp; Tugas Saya', $body);
        $this->assertStringContainsString('Tugas Mengajar Saya', $body);
        $this->assertStringNotContainsString('Kesiapan Data Master', $body);
        $this->assertStringNotContainsString('Aktivitas Terbaru', $body);
        $this->assertStringNotContainsString('Master Data', $body);
    }

    public function testWaliKelasReceivesPersonalAndOwnClassPermissionsOnly(): void
    {
        $user = $this->createTestUserWithRole('wali_scoped_only', 'wali_kelas');

        $actual = $user['permissions'];
        sort($actual);
        $expected = RolePermissions::WALI_KELAS;
        sort($expected);

        $this->assertSame($expected, $actual);
        $this->assertSame([], array_values(array_intersect($actual, RolePermissions::ADMINISTRATIVE_PERMISSIONS)));

        $dashboard = $this->withSession([
            'logged_in' => true,
            'user_id' => $user['id'],
            'username' => 'wali_scoped_only',
            'full_name' => 'Wali Scoped Only',
            'role_code' => 'wali_kelas',
            'role_name' => 'Wali Kelas',
            'all_role_codes' => ['wali_kelas'],
            'active_unit_id' => $user['unit_id'],
            'permissions' => $user['permissions'],
        ])->get('dashboard');
        $body = $dashboard->getBody();
        $this->assertStringContainsString('Kelas Binaan', $body);
        $this->assertStringContainsString('Tugas Mengajar Saya', $body);
        $this->assertStringNotContainsString('Kesiapan Data Master', $body);
        $this->assertStringNotContainsString('Aktivitas Terbaru', $body);
    }

    public function testGuruCannotOpenGlobalStudentMaster(): void
    {
        $user = $this->createTestUserWithRole('guru_no_students', 'guru');

        $result = $this->withSession([
            'logged_in'      => true,
            'user_id'        => $user['id'],
            'username'       => 'guru_no_students',
            'role_code'      => 'guru',
            'all_role_codes' => ['guru'],
            'active_unit_id' => $user['unit_id'],
            'permissions'    => $user['permissions'],
        ])->get('students');

        $result->assertStatus(403);
    }

    // ── SMP admin cannot switch to SMA unit ─────────────────────
    public function testSmpAdminCannotSwitchToSma(): void
    {
        $db = Database::connect($this->DBGroup);
        $smpUser = $this->createTestUserWithRole('admin_smp_t', 'admin_smp', 'SMP');
        $smaUnit = $db->table('school_units')->where('code', 'SMA')->get()->getRowArray();

        $result = $this->withSession([
            'logged_in'      => true,
            'user_id'        => $smpUser['id'],
            'username'       => 'admin_smp_t',
            'role_code'      => 'admin_smp',
            'active_unit_id' => $smpUser['unit_id'],
            'permissions'    => $smpUser['permissions'],
        ])->post('context/unit', [
            'unit_uuid' => $smaUnit['uuid'],
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('error');
    }

    // ── Wakasek can validate but NOT approve or lock ────────────
    public function testWakasekCanManageAcademicPeriodsDirectly(): void
    {
        $wakasek = $this->createTestUserWithRole('wakasek_t', 'wakasek_kurikulum');

        // Permission check via helper
        session()->set([
            'logged_in'      => true,
            'user_id'        => $wakasek['id'],
            'active_unit_id' => $wakasek['unit_id'],
            'permissions'    => $wakasek['permissions']
        ]);
        helper('auth');

        $this->assertTrue(has_permission('academic_periods.manage'));
        $this->assertFalse(has_permission('academic_periods.validate'));
        $this->assertFalse(has_permission('academic_periods.approve'));
        $this->assertFalse(has_permission('academic_periods.lock'));
    }

    // ── Kepala Sekolah can approve but NOT manage ──────────────
    public function testKepsekCanViewPeriodsWithoutWorkflowPermissions(): void
    {
        $kepsek = $this->createTestUserWithRole('kepsek_t', 'kepala_sekolah');

        session()->set([
            'logged_in'      => true,
            'user_id'        => $kepsek['id'],
            'active_unit_id' => $kepsek['unit_id'],
            'permissions'    => $kepsek['permissions']
        ]);
        helper('auth');

        $this->assertTrue(has_permission('academic_periods.view'));
        $this->assertFalse(has_permission('academic_periods.approve'));
        $this->assertFalse(has_permission('academic_periods.manage'));
    }
}
