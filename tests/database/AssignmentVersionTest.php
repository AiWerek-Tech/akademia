<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\IsolatedDatabaseTestTrait;
use App\Services\AssignmentWorkflowService;
use App\Models\AssignmentVersionModel;
use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone2MasterSeeder;
use App\Database\Seeds\Milestone3CurriculumSeeder;
use App\Database\Seeds\Milestone4Seeder;
use Config\Database;

/**
 * @internal
 */
final class AssignmentVersionTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    private int $periodId = 1;
    private int $curriculumVersionId = 1;    protected function setUp(): void
    {

        parent::setUp();

        // Reset transStatus which may have been set to false by a
        // failing query in a previous test class (CI4's transStatus
        // flag persists across transaction boundaries on the shared
        // DB connection).
        $this->db->resetTransStatus();

        (new Milestone2MasterSeeder(new Database()))->run();
        (new Milestone3CurriculumSeeder(new Database()))->run();
        (new Milestone4Seeder(new Database()))->run();

        $db = Database::connect($this->DBGroup);

        // Ensure user 1 exists
        $user = $db->table('users')->where('id', 1)->get()->getRowArray();
        if (!$user) {
            $db->table('users')->insert([
                'id'                   => 1,
                'uuid'                 => '00000000-0000-0000-0000-000000000099',
                'username'             => 'admin',
                'email'                => 'admin@test.com',
                'full_name'            => 'Super Admin',
                'password_hash'        => password_hash('TestPass12345!', PASSWORD_BCRYPT),
                'is_active'            => 1,
                'must_change_password' => 0,
                'created_at'           => date('Y-m-d H:i:s'),
            ]);
        }

        // Ensure super_admin role assignment
        $superAdminRole = $db->table('roles')->where('code', 'super_admin')->get()->getRowArray();
        if ($superAdminRole) {
            $existing = $db->table('user_roles')->where('user_id', 1)->where('role_id', $superAdminRole['id'])->get()->getRowArray();
            if (!$existing) {
                $db->table('user_roles')->insert([
                    'user_id' => 1,
                    'role_id' => $superAdminRole['id'],
                ]);
            }
        }

        // Ensure academic period exists
        $period = $db->table('academic_periods')->where('is_active', 1)->get()->getRowArray();
        if (!$period) {
            $year = $db->table('academic_years')->get()->getRowArray();
            if (!$year) {
                $db->table('academic_years')->insert([
                    'uuid'       => '00000000-0000-0000-0000-000000000001',
                    'name'       => '2026/2027',
                    'start_date' => '2026-07-01',
                    'end_date'   => '2027-06-30',
                    'status'     => 'ACTIVE',
                    'is_active'  => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $yearId = $db->insertID();
            } else {
                $yearId = (int)$year['id'];
            }
            $db->table('academic_periods')->insert([
                'uuid'             => '00000000-0000-0000-0000-000000000001',
                'academic_year_id' => $yearId,
                'semester_number'  => 1,
                'name'             => 'Ganjil 2026/2027',
                'start_date'       => '2026-07-01',
                'end_date'         => '2026-12-31',
                'workflow_status'  => 'OPEN',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
            $this->periodId = $db->insertID();
        } else {
            $this->periodId = (int)$period['id'];
        }

        // Ensure curriculum version exists
        $curriculum = $db->table('curriculum_versions')->where('is_active', 1)->get()->getRowArray();
        if ($curriculum) {
            $this->curriculumVersionId = (int)$curriculum['id'];
        } else {
            $db->table('curriculum_versions')->insert([
                'uuid' => '00000000-0000-0000-0000-000000000200',
                'academic_period_id' => $this->periodId,
                'code' => 'CURR-TEST-VER',
                'name' => 'Kurikulum Test Version',
                'workflow_status' => 'APPROVED',
                'is_active' => 1
            ]);
            $this->curriculumVersionId = $db->insertID();
        }

        session()->set([
            'user_id'     => 1,
            'logged_in'   => true,
            'active_role' => 'super_admin',
            'permissions' => ['assignments.view', 'assignments.manage', 'assignments.validate', 'assignments.review', 'assignments.approve', 'assignments.lock', 'assignments.revise'],
        ]);
    }

    public function testVersionLifecycleAndTransitions(): void
    {
        $versionModel = new AssignmentVersionModel();

        $vId = $versionModel->insert([
            'uuid'                  => '00000000-0000-0000-0000-000000000301',
            'academic_period_id'    => $this->periodId,
            'curriculum_version_id' => $this->curriculumVersionId,
            'code'                  => 'VER-LIFE-01',
            'name'                  => 'Version Life Cycle 1',
            'workflow_status'       => 'DRAFT',
            'revision_number'       => 1
        ]);

        $this->assertGreaterThan(0, $vId);

        // Step-by-step workflow transitions
        $this->assertTrue(AssignmentWorkflowService::transition($vId, 'VALIDATED', 1));
        $this->assertTrue(AssignmentWorkflowService::transition($vId, 'REVIEWED', 2));
        $this->assertTrue(AssignmentWorkflowService::transition($vId, 'APPROVED', 3));
        $this->assertTrue(AssignmentWorkflowService::transition($vId, 'LOCKED', 4));

        $ver = $versionModel->find($vId);
        $this->assertEquals('LOCKED', $ver['workflow_status']);
        $this->assertEquals(1, (int)$ver['is_active']);
    }

    public function testOptimisticLockingStaleRevisionFails(): void
    {
        $versionModel = new AssignmentVersionModel();

        $vId = $versionModel->insert([
            'uuid'                  => '00000000-0000-0000-0000-000000000302',
            'academic_period_id'    => $this->periodId,
            'curriculum_version_id' => $this->curriculumVersionId,
            'code'                  => 'VER-STALE-01',
            'name'                  => 'Stale Lock Test',
            'workflow_status'       => 'DRAFT',
            'revision_number'       => 1
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Data telah diperbarui oleh pengguna lain');

        // Transition with wrong revision number 99
        AssignmentWorkflowService::transition($vId, 'VALIDATED', 99);
    }
}
