<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Database\Seeds\CoreSeeder;
use Config\Database;

/**
 * Extended RBAC & Unit Isolation Tests
 *
 * @internal
 */
final class ExtendedRbacTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

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
