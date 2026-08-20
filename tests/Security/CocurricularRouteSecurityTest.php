<?php

namespace Tests\Security;

use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone2MasterSeeder;
use App\Services\CocurricularService;
use App\Services\FeatureFlagService;
use App\Services\UuidService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Tests\Support\IsolatedDatabaseTestTrait;

/**
 * Cocurricular & Character (Phase 7) Route Security Test Suite.
 *
 * Verifies authentication, permission gating, unit boundary isolation, and
 * the optional 7KAIH feature flag gate on /cocurricular/* routes.
 *
 * @internal
 */
class CocurricularRouteSecurityTest extends CIUnitTestCase
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
        if (! $period) {
            $year = $db->table('academic_years')->where('name', '2026/2027')->get()->getRowArray();
            if (! $year) {
                $db->table('academic_years')->insert([
                    'uuid'       => '60000000-0000-4000-8000-000000000002',
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
                'uuid'             => '60000000-0000-4000-8000-000000000003',
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

        $this->ensureUser(1, 'admin', 'Super Admin');
        $this->ensureUser(2, 'viewer', 'Viewer User');
        $this->ensureUser(3, 'smp_admin', 'SMP Admin');
        $this->ensureUser(4, 'sma_admin', 'SMA Admin');
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

        $teacher = $db->table('teachers')->where('primary_unit_id', $this->smaUnitId)->where('full_name', 'Guru A')->get()->getRowArray();
        if (! $teacher) {
            $db->table('teachers')->insert([
                'uuid'              => '60000000-0000-4000-8000-000000000001',
                'full_name'         => 'Guru A',
                'normalized_name'   => 'GURU A',
                'employment_status' => 'ACTIVE',
                'primary_unit_id'   => $this->smaUnitId,
                'is_active'         => 1,
                'created_at'        => date('Y-m-d H:i:s'),
            ]);
            $teacher = $db->table('teachers')->where('uuid', '60000000-0000-4000-8000-000000000001')->get()->getRowArray();
        }
        $db->table('users')->where('id', 5)->update(['teacher_id' => $teacher['id']]);

        $dimensions = $db->table('graduate_profile_dimensions')->get()->getResultArray();
        if ($dimensions === []) {
            $db->table('graduate_profile_dimensions')->insert([
                'uuid'       => '60000000-0000-4000-8000-000000000007',
                'code'       => 'COLLABORATION',
                'name'       => 'Kolaborasi',
                'sort_order' => 1,
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Ensure Phase 7 permissions exist and are mapped to test roles
        $now = date('Y-m-d H:i:s');
        foreach (['cocurricular.view', 'cocurricular.manage'] as $permCode) {
            $perm = $db->table('permissions')->where('code', $permCode)->get()->getRowArray();
            if (!$perm) {
                $db->table('permissions')->insert([
                    'code'        => $permCode,
                    'module'      => 'cocurricular',
                    'name'        => $permCode,
                    'created_at'  => $now,
                ]);
                $perm = $db->table('permissions')->where('code', $permCode)->get()->getRowArray();
            }
            foreach (['admin_smp', 'admin_sma', 'super_admin', 'guru'] as $rCode) {
                $r = $db->table('roles')->where('code', $rCode)->get()->getRowArray();
                if ($r && $perm) {
                    $hasRolePerm = $db->table('role_permissions')
                        ->where('role_id', $r['id'])
                        ->where('permission_id', $perm['id'])
                        ->countAllResults() > 0;
                    if (!$hasRolePerm) {
                        $db->table('role_permissions')->insert([
                            'role_id'       => $r['id'],
                            'permission_id' => $perm['id'],
                            'created_at'    => $now,
                        ]);
                    }
                }
            }
        }
    }

    private function createProgram(int $unitId): array
    {
        $db     = Database::connect($this->DBGroup);
        $service = new CocurricularService();

        $dimension = $db->table('graduate_profile_dimensions')->get()->getRowArray();
        $classroom = $db->table('classrooms')->where('unit_id', $unitId)->get()->getRowArray();
        if (! $classroom) {
            $grade = $db->table('grade_levels')->where('unit_id', $unitId)->get()->getRowArray();
            $db->table('classrooms')->insert([
                'uuid'               => UuidService::v4(),
                'academic_period_id' => $this->periodId,
                'unit_id'            => $unitId,
                'name'               => 'Sec-Kokur',
                'grade_level_id'     => $grade ? (int) $grade['id'] : null,
                'is_active'          => 1,
                'created_at'         => date('Y-m-d H:i:s'),
            ]);
            $classroom = $db->table('classrooms')->where('unit_id', $unitId)->get()->getRowArray();
        }

        $programId = $service->createProgram($unitId, $this->periodId, [
            'code'           => 'SEC-' . $unitId,
            'title'          => 'Program Keamanan Kokurikuler',
            'program_type'   => 'FIXED_SCHOOL_ACTIVITY',
            'delivery_model' => 'WEEKLY',
            'dimension_ids'  => $dimension ? [(int) $dimension['id']] : [],
            'classroom_ids'  => [(int) $classroom['id']],
        ], 1);

        return $db->table('cocurricular_programs')->where('id', $programId)->get()->getRowArray();
    }

    public function testGuestRedirectedToLogin(): void
    {
        $this->withSession([])->get('cocurricular')->assertRedirectTo(base_url('login'));
    }

    public function testUserWithoutPermissionGets403(): void
    {
        $this->withSession($this->sessionLogin(2, $this->smpUnitId, 'viewer'))
            ->get('cocurricular')
            ->assertStatus(403);
    }

    public function testSuperAdminReachesIndex(): void
    {
        $this->withSession($this->sessionLogin(1, $this->smaUnitId, 'super_admin'))
            ->get('cocurricular')
            ->assertStatus(200);
    }

    public function testGuruWithCocurricularPermissionReachesIndex(): void
    {
        $this->withSession($this->sessionLogin(5, $this->smaUnitId, 'guru'))
            ->get('cocurricular')
            ->assertStatus(200);
    }

    public function testGuruCannotReachCreateWithoutManage(): void
    {
        // Remove cocurricular.manage from the guru role to prove the route gate.
        $db = Database::connect($this->DBGroup);
        $guruRole = $db->table('roles')->where('code', 'guru')->get()->getRowArray();
        $perm = $db->table('permissions')->where('code', 'cocurricular.manage')->get()->getRowArray();
        if ($guruRole && $perm) {
            $db->table('role_permissions')->where('role_id', $guruRole['id'])->where('permission_id', $perm['id'])->delete();
        }

        $this->withSession($this->sessionLogin(5, $this->smaUnitId, 'guru'))
            ->get('cocurricular/create')
            ->assertStatus(403);
    }

    public function testAdminSmaReachesDetail(): void
    {
        $program = $this->createProgram($this->smaUnitId);

        $this->withSession($this->sessionLogin(4, $this->smaUnitId, 'admin_sma'))
            ->get('cocurricular/' . $program['id'])
            ->assertStatus(200);
    }

    public function testAdminSmpCannotOpenSmaProgram(): void
    {
        $program = $this->createProgram($this->smaUnitId);

        $response = $this->withSession($this->sessionLogin(3, $this->smpUnitId, 'admin_smp'))
            ->get('cocurricular/' . $program['id']);
        $response->assertRedirectTo(base_url('cocurricular'));
        $response->assertSessionHas('error');
    }

    public function testViewerCannotAccessEvidenceFile(): void
    {
        $this->withSession($this->sessionLogin(2, $this->smpUnitId, 'viewer'))
            ->get('cocurricular/evidence-file/1')
            ->assertStatus(403);
    }

    public function test7kaihRoutesBlockedWhenFlagDisabled(): void
    {
        FeatureFlagService::set('ialos_7kahi', false);

        $this->withSession($this->sessionLogin(4, $this->smaUnitId, 'admin_sma'))
            ->get('cocurricular/habits')
            ->assertStatus(403);
        $this->withSession($this->sessionLogin(4, $this->smaUnitId, 'admin_sma'))
            ->get('cocurricular/checkins')
            ->assertStatus(403);
    }

    public function test7kaihRoutesReachableWhenFlagEnabled(): void
    {
        FeatureFlagService::set('ialos_7kahi', true);

        $this->withSession($this->sessionLogin(4, $this->smaUnitId, 'admin_sma'))
            ->get('cocurricular/habits')
            ->assertStatus(200);
        $this->withSession($this->sessionLogin(4, $this->smaUnitId, 'admin_sma'))
            ->get('cocurricular/checkins')
            ->assertStatus(200);
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

    private function ensureUser(int $id, string $username, string $fullName): void
    {
        $db = Database::connect($this->DBGroup);
        if (! $db->table('users')->where('id', $id)->get()->getRowArray()) {
            $db->table('users')->insert([
                'id'                   => $id,
                'uuid'                 => sprintf('6%011d-0000-4000-8000-000000000000', $id),
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
        if (! $role) {
            $this->fail("Role {$roleCode} not seeded.");
        }
        $exists = $db->table('user_roles')
            ->where('user_id', $userId)
            ->where('role_id', $role['id'])
            ->get()->getRowArray();
        if (! $exists) {
            $db->table('user_roles')->insert([
                'user_id'   => $userId,
                'role_id'   => $role['id'],
                'unit_id'   => $unitId,
                'created_at'=> date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function grantUnit(int $userId, int $unitId, bool $isDefault): void
    {
        $db = Database::connect($this->DBGroup);
        if (! $db->table('user_unit_access')->where('user_id', $userId)->where('unit_id', $unitId)->get()->getRowArray()) {
            $db->table('user_unit_access')->insert([
                'user_id'     => $userId,
                'unit_id'     => $unitId,
                'access_level'=> 'ADMIN',
                'is_default'  => $isDefault ? 1 : 0,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }
    }
}