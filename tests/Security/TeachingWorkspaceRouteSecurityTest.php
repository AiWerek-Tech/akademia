<?php

namespace Tests\Security;

use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone2MasterSeeder;
use App\Services\UuidService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Tests\Support\IsolatedDatabaseTestTrait;

/**
 * Teaching Workspace (Phase 5) Route Security Test Suite.
 *
 * Verifies authentication, permission gating, unit boundary isolation,
 * and session ownership enforcement on the /teaching/* routes.
 *
 * @internal
 */
class TeachingWorkspaceRouteSecurityTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace   = 'App';
    protected $seed        = CoreSeeder::class;

    private int $smpUnitId;
    private int $smaUnitId;
    private int $periodId;
    private int $ownTeacherId = 0;

    protected function setUp(): void
    {
        parent::setUp();
        (new Milestone2MasterSeeder(new Database()))->run();
        $this->setupTestUsers();
    }

    private function setupTestUsers(): void
    {
        $db = Database::connect($this->DBGroup);

        $this->smpUnitId = (int) $db->table('school_units')->where('code', 'SMP')->get()->getRowArray()['id'];
        $this->smaUnitId = (int) $db->table('school_units')->where('code', 'SMA')->get()->getRowArray()['id'];

        $period = $db->table('academic_periods')->where('is_active', 1)->get()->getRowArray();
        if (!$period) {
            $year = $db->table('academic_years')->where('name', '2026/2027')->get()->getRowArray();
            if (!$year) {
                $db->table('academic_years')->insert([
                    'uuid'       => '40000000-0000-4000-8000-000000000002',
                    'name'       => '2026/2027',
                    'start_date' => '2026-07-01',
                    'end_date'   => '2027-06-30',
                    'status'     => 'APPROVED',
                    'is_active'  => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $year = ['id' => $db->insertID()];
            }
            $db->table('academic_periods')->insert([
                'uuid'             => '40000000-0000-4000-8000-000000000003',
                'academic_year_id' => $year['id'],
                'semester_number'  => 1,
                'name'             => 'Ganjil 2026/2027',
                'start_date'       => '2026-07-01',
                'end_date'         => '2026-12-31',
                'workflow_status'  => 'OPEN',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
            $this->periodId = (int) $db->insertID();
        } else {
            $this->periodId = (int) $period['id'];
        }

        // 1. Super Admin (full access, SMA default unit)
        $this->ensureUser(1, 'admin', 'Super Admin');
        // 2. Viewer (no roles / permissions)
        $this->ensureUser(2, 'viewer', 'Viewer User');
        // 3. SMP-only admin
        $this->ensureUser(3, 'smp_admin', 'SMP Admin');
        // 4. SMA-only admin
        $this->ensureUser(4, 'sma_admin', 'SMA Admin');
        // 5. Guru linked to a teacher record (SMA)
        $this->ensureUser(5, 'guru_a', 'Guru A');

        $this->assignRole(1, 'super_admin', null);
        $this->assignRole(3, 'admin_smp', null);
        $this->assignRole(4, 'admin_sma', null);
        $this->assignRole(5, 'guru', null);

        $this->grantUnit(1, $this->smpUnitId, true);
        $this->grantUnit(1, $this->smaUnitId, false);
        $this->grantUnit(2, $this->smpUnitId, true);
        $this->grantUnit(3, $this->smpUnitId, true);
        $this->grantUnit(4, $this->smaUnitId, true);
        $this->grantUnit(5, $this->smaUnitId, true);

        // Link teacher record to guru user (user 5) so ownership resolves via users.teacher_id.
        $teacher = $db->table('teachers')->where('primary_unit_id', $this->smaUnitId)->where('full_name', 'Guru A')->get()->getRowArray();
        if (!$teacher) {
            $db->table('teachers')->insert([
                'uuid'             => '40000000-0000-4000-8000-000000000001',
                'full_name'        => 'Guru A',
                'normalized_name'  => 'GURU A',
                'employment_status'=> 'ACTIVE',
                'primary_unit_id'  => $this->smaUnitId,
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
            $teacher = $db->table('teachers')->where('uuid', '40000000-0000-4000-8000-000000000001')->get()->getRowArray();
        }
        $db->table('users')->where('id', 5)->update(['teacher_id' => $teacher['id']]);
        $this->ownTeacherId = (int) $teacher['id'];
    }

    private function ensureUser(int $id, string $username, string $fullName): void
    {
        $db = Database::connect($this->DBGroup);
        if (!$db->table('users')->where('id', $id)->get()->getRowArray()) {
            $db->table('users')->insert([
                'id'                   => $id,
                'uuid'                 => sprintf('4%011d-0000-4000-8000-000000000000', $id),
                'username'             => $username,
                'email'                => $username . '@test.com',
                'full_name'            => $fullName,
                'password_hash'        => password_hash('SomePass12345!', PASSWORD_BCRYPT),
                'is_active'            => 1,
                'must_change_password' => 0,
                'created_at'           => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function assignRole(int $userId, string $roleCode, ?int $unitId): void
    {
        $db = Database::connect($this->DBGroup);
        $role = $db->table('roles')->where('code', $roleCode)->get()->getRowArray();
        if (!$role) {
            $this->fail("Role {$roleCode} not seeded.");
        }
        $exists = $db->table('user_roles')
            ->where('user_id', $userId)
            ->where('role_id', $role['id'])
            ->get()->getRowArray();
        if (!$exists) {
            $db->table('user_roles')->insert([
                'user_id' => $userId,
                'role_id' => $role['id'],
                'unit_id' => $unitId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function grantUnit(int $userId, int $unitId, bool $isDefault): void
    {
        $db = Database::connect($this->DBGroup);
        if (!$db->table('user_unit_access')->where('user_id', $userId)->where('unit_id', $unitId)->get()->getRowArray()) {
            $db->table('user_unit_access')->insert([
                'user_id' => $userId,
                'unit_id' => $unitId,
                'access_level' => 'ADMIN',
                'is_default' => $isDefault ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function createSession(int $unitId, int $teacherId): array
    {
        $db = Database::connect($this->DBGroup);
        $uuid = UuidService::v4();
        $db->table('learning_sessions')->insert([
            'uuid'               => $uuid,
            'academic_period_id' => $this->periodId,
            'unit_id'            => $unitId,
            'teacher_id'         => $teacherId,
            'classroom_id'       => 0,
            'subject_id'         => 0,
            'session_date'       => date('Y-m-d'),
            'meeting_number'     => 1,
            'jp_count'           => 2,
            'status'             => 'PLANNED',
            'revision_number'    => 1,
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);
        return $db->table('learning_sessions')->where('uuid', $uuid)->get()->getRowArray();
    }

    private function sessionLogin(int $userId, int $unitId, string $roleCode): array
    {
        return [
            'logged_in'       => true,
            'user_id'         => $userId,
            'auth_timestamp'  => time(),
            'username'        => 'user' . $userId,
            'role_code'       => $roleCode,
            'all_role_codes'  => [$roleCode],
            'active_role'     => $roleCode,
            'active_unit_id'  => $unitId,
            'active_period_id'=> $this->periodId,
            'unit_access'     => [$unitId],
        ];
    }

    public function testGuestRedirectedToLogin(): void
    {
        $result = $this->withSession([])->get('teaching/today');
        $result->assertRedirectTo(base_url('login'));
    }

    public function testUserWithoutPermissionGets403(): void
    {
        $result = $this->withSession($this->sessionLogin(2, $this->smpUnitId, 'viewer'))->get('teaching/today');
        $result->assertStatus(403);
    }

    public function testSuperAdminReachesTeachingWorkspace(): void
    {
        $result = $this->withSession($this->sessionLogin(1, $this->smaUnitId, 'super_admin'))->get('teaching/today');
        $result->assertStatus(200);
    }

    public function testAdminSmpCannotOpenSessionInSmaUnit(): void
    {
        $session = $this->createSession($this->smaUnitId, 0);

        $result = $this->withSession($this->sessionLogin(3, $this->smpUnitId, 'admin_smp'))
            ->get('teaching/session/' . $session['uuid']);

        $result->assertRedirectTo(base_url('teaching/today'));
        $result->assertSessionHas('error');
    }

    public function testSuperAdminCanOpenSessionInAnyAccessibleUnit(): void
    {
        $session = $this->createSession($this->smaUnitId, 0);

        $result = $this->withSession($this->sessionLogin(1, $this->smaUnitId, 'super_admin'))
            ->get('teaching/session/' . $session['uuid']);

        $result->assertStatus(200);
    }

    public function testGuruCannotStartAnotherTeachersSession(): void
    {
        $session = $this->createSession($this->smaUnitId, 999999);

        $result = $this->withSession($this->sessionLogin(5, $this->smaUnitId, 'guru'))
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post('teaching/session/' . $session['uuid'] . '/start');

        $result->assertStatus(403);
        $result->assertJSONFragment(['status' => 'error']);
    }

    public function testGuruCanStartOwnSession(): void
    {
        $session = $this->createSession($this->smaUnitId, $this->ownTeacherId);

        $result = $this->withSession($this->sessionLogin(5, $this->smaUnitId, 'guru'))
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post('teaching/session/' . $session['uuid'] . '/start');

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
    }

    public function testPermissionFilterRejectsMutationWithoutTeachingPermission(): void
    {
        $session = $this->createSession($this->smpUnitId, 0);

        // Viewer has no teaching.teach permission → 403 at the route filter.
        $result = $this->withSession($this->sessionLogin(2, $this->smpUnitId, 'viewer'))
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post('teaching/session/' . $session['uuid'] . '/start');

        $result->assertStatus(403);
    }
}
