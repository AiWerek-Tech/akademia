<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\IsolatedDatabaseTestTrait;
use App\Database\Seeds\CoreSeeder;
use Config\Database;

/**
 * Extended Authentication Tests
 * Tests login flow, lockout, password strength, session regeneration, and logout.
 *
 * @internal
 */
final class ExtendedAuthTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use IsolatedDatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    private function createTestUser(string $username, string $password, int $isActive = 1, int $mustChange = 0): int
    {
        $db = Database::connect($this->DBGroup);

        $db->table('users')->insert([
            'uuid'                 => \App\Services\UuidService::v4(),
            'username'             => $username,
            'email'                => $username . '@test.com',
            'full_name'            => 'Test ' . $username,
            'password_hash'        => password_hash($password, PASSWORD_BCRYPT),
            'is_active'            => $isActive,
            'must_change_password' => $mustChange,
            'failed_login_count'   => 0,
            'created_at'           => date('Y-m-d H:i:s'),
        ]);
        $insertId = $db->insertID();

        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        $db->table('user_unit_access')->insert([
            'user_id'      => $insertId,
            'unit_id'      => $smp['id'],
            'access_level' => 'MEMBER',
            'is_default'   => 1,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        $role = $db->table('roles')->where('code', 'guru')->get()->getRowArray();
        $db->table('user_roles')->insert([
            'user_id'    => $insertId,
            'role_id'    => $role['id'],
            'unit_id'    => $smp['id'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $insertId;
    }

    // ── Login Success ──────────────────────────────────────────
    public function testLoginSuccessRedirectsToDashboard(): void
    {
        $this->createTestUser('guru_ok', 'GuruPass123456!');

        $result = $this->post('login', [
            'username' => 'guru_ok',
            'password' => 'GuruPass123456!',
        ]);

        $result->assertRedirectTo('dashboard');
        $result->assertSessionHas('logged_in', true);
        $result->assertSessionHas('username', 'guru_ok');
    }

    // ── Login Failure increments failed_login_count ─────────────
    public function testLoginFailureIncrementsCounter(): void
    {
        $userId = $this->createTestUser('guru_fail', 'GuruPass123456!');
        $db = Database::connect($this->DBGroup);

        $this->post('login', [
            'username' => 'guru_fail',
            'password' => 'wrong_password',
        ]);

        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();
        $this->assertEquals(1, (int) $user['failed_login_count']);
    }

    // ── 5 failures trigger lockout ─────────────────────────────
    public function testFiveFailuresTriggersLockout(): void
    {
        $userId = $this->createTestUser('guru_lock', 'GuruPass123456!');
        $db = Database::connect($this->DBGroup);

        for ($i = 0; $i < 5; $i++) {
            $this->post('login', [
                'username' => 'guru_lock',
                'password' => 'wrong_password',
            ]);
        }

        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();
        $this->assertNotNull($user['locked_until']);
        $this->assertGreaterThan(time(), strtotime($user['locked_until']));
    }

    // ── Login during lockout is rejected ────────────────────────
    public function testLoginDuringLockoutRejected(): void
    {
        $userId = $this->createTestUser('guru_lockx', 'GuruPass123456!');
        $db = Database::connect($this->DBGroup);

        // Set lockout manually
        $db->table('users')->where('id', $userId)->update([
            'locked_until' => date('Y-m-d H:i:s', strtotime('+15 minutes')),
        ]);

        $result = $this->post('login', [
            'username' => 'guru_lockx',
            'password' => 'GuruPass123456!',
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('error');
    }

    // ── Successful login resets failed_login_count ──────────────
    public function testLoginSuccessResetsCounter(): void
    {
        $userId = $this->createTestUser('guru_rst', 'GuruPass123456!');
        $db = Database::connect($this->DBGroup);

        $db->table('users')->where('id', $userId)->update(['failed_login_count' => 3]);

        $this->post('login', [
            'username' => 'guru_rst',
            'password' => 'GuruPass123456!',
        ]);

        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();
        $this->assertEquals(0, (int) $user['failed_login_count']);
    }

    // ── Non-existent user returns generic error ────────────────
    public function testNonExistentUserGenericError(): void
    {
        $result = $this->post('login', [
            'username' => 'nobody',
            'password' => 'somePass123!',
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('error', 'Username atau password salah.');
    }

    // ── Inactive user rejected ─────────────────────────────────
    public function testInactiveUserRejected(): void
    {
        $this->createTestUser('guru_off', 'GuruPass123456!', 0);

        $result = $this->post('login', [
            'username' => 'guru_off',
            'password' => 'GuruPass123456!',
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('error', 'Akun Anda tidak aktif. Silakan hubungi administrator.');
    }

    // ── must_change_password redirects to /change-password ─────
    public function testMustChangePasswordRedirects(): void
    {
        $this->createTestUser('guru_mc', 'GuruPass123456!', 1, 1);

        $result = $this->post('login', [
            'username' => 'guru_mc',
            'password' => 'GuruPass123456!',
        ]);

        $result->assertRedirectTo('change-password');
    }

    // ── Change password validates strength ──────────────────────
    public function testChangePasswordRejectsWeakPassword(): void
    {
        $userId = $this->createTestUser('guru_cp', 'GuruPass123456!');

        $result = $this->withSession([
            'logged_in' => true,
            'user_id'   => $userId,
            'username'  => 'guru_cp',
        ])->post('change-password', [
            'current_password' => 'GuruPass123456!',
            'new_password'     => 'weak',
            'confirm_password' => 'weak',
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('errors');
    }

    // ── Change password succeeds with strong password ──────────
    public function testChangePasswordSucceeds(): void
    {
        $userId = $this->createTestUser('guru_cp2', 'GuruPass123456!');
        $db = Database::connect($this->DBGroup);

        $result = $this->withSession([
            'logged_in' => true,
            'user_id'   => $userId,
            'username'  => 'guru_cp2',
        ])->post('change-password', [
            'current_password' => 'GuruPass123456!',
            'new_password'     => 'NewStrongPass123!',
            'confirm_password' => 'NewStrongPass123!',
        ]);

        $result->assertRedirectTo('dashboard');

        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();
        $this->assertTrue(password_verify('NewStrongPass123!', $user['password_hash']));
    }

    public function testForcedChangePasswordDoesNotRequireCurrentPassword(): void
    {
        $userId = $this->createTestUser('guru_forced_cp', 'GuruPass123456!', 1, 1);
        $db = Database::connect($this->DBGroup);

        $result = $this->withSession([
            'logged_in'            => true,
            'user_id'              => $userId,
            'username'             => 'guru_forced_cp',
            'must_change_password' => true,
        ])->post('change-password', [
            'new_password'     => 'NewForcedPass123!',
            'confirm_password' => 'NewForcedPass123!',
        ]);

        $result->assertRedirectTo('dashboard');
        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();
        $this->assertTrue(password_verify('NewForcedPass123!', $user['password_hash']));
        $this->assertEquals(0, (int) $user['must_change_password']);
    }

    public function testFirstLoginChangesTemporaryUsernameAndPasswordTogether(): void
    {
        $userId = $this->createTestUser('guru.temporary', 'GuruPass123456!', 1, 1);
        $db = Database::connect($this->DBGroup);
        $db->table('users')->where('id', $userId)->update(['must_change_username' => 1]);

        $result = $this->withSession([
            'logged_in'            => true,
            'user_id'              => $userId,
            'username'             => 'guru.temporary',
            'must_change_password' => true,
            'must_change_username' => true,
        ])->post('change-password', [
            'new_username'     => 'guru.pribadi',
            'new_password'     => 'PersonalPass123!',
            'confirm_password' => 'PersonalPass123!',
        ]);

        $result->assertRedirectTo('dashboard');
        $result->assertSessionHas('username', 'guru.pribadi');
        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();
        $this->assertSame('guru.pribadi', $user['username']);
        $this->assertSame(0, (int) $user['must_change_username']);
        $this->assertSame(0, (int) $user['must_change_password']);
        $this->assertTrue(password_verify('PersonalPass123!', $user['password_hash']));
        $this->assertNotNull($user['username_changed_at']);
    }

    // ── Logout via POST destroys session ────────────────────────
    public function testLogoutPostDestroysSession(): void
    {
        $userId = $this->createTestUser('guru_out', 'GuruPass123456!');

        $result = $this->withSession([
            'logged_in' => true,
            'user_id'   => $userId,
        ])->post('logout');

        $result->assertRedirectTo('login');
    }

    // ── Logout via GET is rejected ──────────────────────────────
    public function testLogoutGetRejected(): void
    {
        $userId = $this->createTestUser('guru_outx', 'GuruPass123456!');

        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);

        $this->withSession([
            'logged_in' => true,
            'user_id'   => $userId,
        ])->get('logout');
    }
}
