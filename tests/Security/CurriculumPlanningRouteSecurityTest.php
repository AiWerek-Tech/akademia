<?php

namespace Tests\Security;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone2MasterSeeder;
use App\Database\Seeds\Milestone3CurriculumSeeder;
use Config\Database;

/**
 * Schedule & Curriculum Planning Route Security Test Suite
 *
 * @internal
 */
class CurriculumPlanningRouteSecurityTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace   = 'App';
    protected $seed        = CoreSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestUsers();
    }

    private function setupTestUsers(): void
    {
        $db = Database::connect($this->DBGroup);
        (new Milestone2MasterSeeder(new Database()))->run();
        (new Milestone3CurriculumSeeder(new Database()))->run();

        // 1. Super Admin User
        if (!$db->table('users')->where('id', 1)->get()->getRowArray()) {
            $db->table('users')->insert([
                'id'                   => 1,
                'uuid'                 => '11111111-1111-1111-1111-111111111111',
                'username'             => 'admin',
                'email'                => 'admin@test.com',
                'full_name'            => 'Super Admin',
                'password_hash'        => password_hash('SomePass12345!', PASSWORD_BCRYPT),
                'is_active'            => 1,
                'must_change_password' => 0,
                'created_at'           => date('Y-m-d H:i:s'),
            ]);
        }

        // 2. Restricted Viewer User (no permissions)
        if (!$db->table('users')->where('id', 2)->get()->getRowArray()) {
            $db->table('users')->insert([
                'id'                   => 2,
                'uuid'                 => '22222222-2222-2222-2222-222222222222',
                'username'             => 'viewer',
                'email'                => 'viewer@test.com',
                'full_name'            => 'Viewer User',
                'password_hash'        => password_hash('SomePass12345!', PASSWORD_BCRYPT),
                'is_active'            => 1,
                'must_change_password' => 0,
                'created_at'           => date('Y-m-d H:i:s'),
            ]);
        }

        // 3. SMP-only Admin User
        if (!$db->table('users')->where('id', 3)->get()->getRowArray()) {
            $db->table('users')->insert([
                'id'                   => 3,
                'uuid'                 => '33333333-3333-3333-3333-333333333333',
                'username'             => 'smp_admin',
                'email'                => 'smp_admin@test.com',
                'full_name'            => 'SMP Admin',
                'password_hash'        => password_hash('SomePass12345!', PASSWORD_BCRYPT),
                'is_active'            => 1,
                'must_change_password' => 0,
                'created_at'           => date('Y-m-d H:i:s'),
            ]);
        }

        // 4. SMA-only Admin User
        if (!$db->table('users')->where('id', 4)->get()->getRowArray()) {
            $db->table('users')->insert([
                'id'                   => 4,
                'uuid'                 => '44444444-4444-4444-4444-444444444444',
                'username'             => 'sma_admin',
                'email'                => 'sma_admin@test.com',
                'full_name'            => 'SMA Admin',
                'password_hash'        => password_hash('SomePass12345!', PASSWORD_BCRYPT),
                'is_active'            => 1,
                'must_change_password' => 0,
                'created_at'           => date('Y-m-d H:i:s'),
            ]);
        }

        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        $sma = $db->table('school_units')->where('code', 'SMA')->get()->getRowArray();

        // Attach superadmin role to user 1
        $superRole = $db->table('roles')->where('code', 'super_admin')->get()->getRowArray();
        if (!$superRole) {
            $superRole = $db->table('roles')->where('code', 'superadmin')->get()->getRowArray();
        }
        if ($superRole) {
            $ur = $db->table('user_roles')->where('user_id', 1)->where('role_id', $superRole['id'])->get()->getRowArray();
            if (!$ur) {
                $db->table('user_roles')->insert(['user_id' => 1, 'role_id' => $superRole['id'], 'created_at' => date('Y-m-d H:i:s')]);
            }
        }

        // Attach SMP unit access to user 2
        if ($smp) {
            $access2 = $db->table('user_unit_access')->where('user_id', 2)->where('unit_id', $smp['id'])->get()->getRowArray();
            if (!$access2) {
                $db->table('user_unit_access')->insert(['user_id' => 2, 'unit_id' => $smp['id'], 'access_level' => 'VIEW', 'is_default' => 1, 'created_at' => date('Y-m-d H:i:s')]);
            }
        }

        // Attach SMP unit access to user 3
        if ($smp) {
            $access3 = $db->table('user_unit_access')->where('user_id', 3)->where('unit_id', $smp['id'])->get()->getRowArray();
            if (!$access3) {
                $db->table('user_unit_access')->insert(['user_id' => 3, 'unit_id' => $smp['id'], 'access_level' => 'ADMIN', 'is_default' => 1, 'created_at' => date('Y-m-d H:i:s')]);
            }
        }

        // Attach SMA unit access to user 4
        if ($sma) {
            $access4 = $db->table('user_unit_access')->where('user_id', 4)->where('unit_id', $sma['id'])->get()->getRowArray();
            if (!$access4) {
                $db->table('user_unit_access')->insert(['user_id' => 4, 'unit_id' => $sma['id'], 'access_level' => 'ADMIN', 'is_default' => 1, 'created_at' => date('Y-m-d H:i:s')]);
            }
        }
    }

    public function testGuestAccessToSchedulesIsRedirectedToLogin(): void
    {
        $result = $this->withSession([])->get('schedules');
        $result->assertRedirectTo(base_url('login'));
    }

    public function testGuestAccessToCurriculumIsRedirectedToLogin(): void
    {
        $result = $this->withSession([])->get('curriculum');
        $result->assertRedirectTo(base_url('login'));
    }

    public function testAuthenticatedUserWithoutPermissionIsRedirected(): void
    {
        $result = $this->withSession([
            'logged_in'      => true,
            'user_id'        => 2,
            'auth_timestamp' => time(),
            'username'       => 'viewer',
            'role_code'      => 'viewer',
            'active_role'    => 'viewer',
            'active_unit_id' => 1,
            'unit_access'    => [1],
        ])->get('curriculum/create');

        $result->assertRedirectTo(base_url('curriculum'));
        $result->assertSessionHas('error');
    }

    public function testValidPermissionReachesController(): void
    {
        $result = $this->withSession([
            'logged_in'      => true,
            'user_id'        => 1,
            'username'       => 'admin',
            'role_code'      => 'super_admin',
            'active_role'    => 'super_admin',
            'active_unit_id' => 1,
            'unit_access'    => [1, 2],
        ])->get('curriculum');

        $result->assertStatus(200);
    }

    public function testAdminSmpAccessingSmaUnitIsRejected(): void
    {
        $result = $this->withSession([
            'logged_in'      => true,
            'user_id'        => 3,
            'auth_timestamp' => time(),
            'username'       => 'smp_admin',
            'role_code'      => 'admin_smp',
            'active_role'    => 'admin_smp',
            'active_unit_id' => 1,
            'unit_access'    => [1],
        ])->get('curriculum?unit_id=2');

        $result->assertRedirectTo(base_url('dashboard'));
        $result->assertSessionHas('error');
    }

    public function testAdminSmaAccessingSmpUnitIsRejected(): void
    {
        $result = $this->withSession([
            'logged_in'      => true,
            'user_id'        => 4,
            'auth_timestamp' => time(),
            'username'       => 'sma_admin',
            'role_code'      => 'admin_sma',
            'active_role'    => 'admin_sma',
            'active_unit_id' => 2,
            'unit_access'    => [2],
        ])->get('curriculum?unit_id=1');

        $result->assertRedirectTo(base_url('dashboard'));
        $result->assertSessionHas('error');
    }

    public function testAllRequiredFilterAliasesAreRegistered(): void
    {
        $filtersConfig = new \Config\Filters();
        $aliases = array_keys($filtersConfig->aliases);

        $this->assertContains('auth', $aliases);
        $this->assertContains('permission', $aliases);
        $this->assertContains('unit_access', $aliases);
        $this->assertContains('password_change_required', $aliases);
    }

    public function testSystemDoesNotExposeControllerBeforeAuth(): void
    {
        $result = $this->withSession([])->get('schedules');
        $result->assertRedirectTo(base_url('login'));
    }
}
