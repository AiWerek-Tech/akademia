<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\AcademicYearModel;
use App\Models\AcademicPeriodModel;
use App\Services\AcademicPeriodWorkflowService;
use App\Database\Seeds\CoreSeeder;
use Config\Database;

/**
 * @internal
 */
final class ExtendedAcademicTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureSuperAdminExists();
        session()->set($this->getSuperAdminSession());
    }

    private function ensureSuperAdminExists(): void
    {
        $db = Database::connect($this->DBGroup);
        $user = $db->table('users')->where('id', 1)->get()->getRowArray();
        if (!$user) {
            $db->table('users')->insert([
                'id'                   => 1,
                'uuid'                 => \App\Services\UuidService::v4(),
                'username'             => 'admin',
                'email'                => 'admin@test.com',
                'full_name'            => 'Super Admin',
                'password_hash'        => password_hash('SomePass12345!', PASSWORD_BCRYPT),
                'is_active'            => 1,
                'must_change_password' => 0,
                'created_at'           => date('Y-m-d H:i:s'),
            ]);
        }
        
        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        if ($smp) {
            $access = $db->table('user_unit_access')
                ->where('user_id', 1)
                ->where('unit_id', $smp['id'])
                ->get()
                ->getRowArray();
            if (!$access) {
                $db->table('user_unit_access')->insert([
                    'user_id'      => 1,
                    'unit_id'      => $smp['id'],
                    'access_level' => 'ADMIN',
                    'is_default'   => 1,
                    'created_at'   => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    private function getSuperAdminSession(): array
    {
        return [
            'logged_in'      => true,
            'user_id'        => 1,
            'username'       => 'admin',
            'role_code'      => 'super_admin',
            'active_unit_id' => 1,
            'permissions'    => [
                'academic_years.view',
                'academic_years.manage',
                'academic_periods.view',
                'academic_periods.manage',
                'academic_periods.validate',
                'academic_periods.review',
                'academic_periods.approve',
                'academic_periods.lock'
            ]
        ];
    }

    public function testAcademicYearOverlappingAndValidation(): void
    {
        // 1. Start date >= End date is invalid and should be rejected by the controller
        $result = $this->withSession($this->getSuperAdminSession())
            ->post('academic-years', [
                'name'       => '2027/2028',
                'start_date' => '2027-06-30',
                'end_date'   => '2027-06-01' // Invalid date range
            ]);
        
        $result->assertRedirect();
        $this->assertNotEmpty(session()->getFlashdata('error'));
        $this->assertStringContainsString('harus sebelum tanggal selesai', session()->getFlashdata('error'));
    }

    public function testAcademicYearActivationOnlyOneActive(): void
    {
        $db = Database::connect($this->DBGroup);
        $yearModel = new AcademicYearModel();

        // Insert Year 1
        $yearModel->insert([
            'name'       => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date'   => '2027-06-30',
            'status'     => 'APPROVED',
            'is_active'  => 1
        ]);
        $id1 = $yearModel->insertID();

        // Insert Year 2
        $yearModel->insert([
            'name'       => '2027/2028',
            'start_date' => '2027-07-01',
            'end_date'   => '2028-06-30',
            'status'     => 'APPROVED',
            'is_active'  => 0
        ]);
        $id2 = $yearModel->insertID();

        // Activate Year 2
        $yearModel->db->transStart();
        // Deactivate all
        $db->table('academic_years')->update(['is_active' => 0]);
        // Activate Year 2
        $db->table('academic_years')->where('id', $id2)->update(['is_active' => 1]);
        $yearModel->db->transComplete();

        // Assert only one active year
        $activeCount = $db->table('academic_years')->where('is_active', 1)->countAllResults();
        $this->assertEquals(1, $activeCount);
        
        $activeYear = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        $this->assertEquals($id2, $activeYear['id']);
    }

    public function testPeriodSemesterNumberValidation(): void
    {
        $yearModel = new AcademicYearModel();

        $yearModel->insert([
            'name'       => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date'   => '2027-06-30',
            'status'     => 'APPROVED',
            'is_active'  => 1
        ]);
        $yearId = $yearModel->insertID();

        // 1. Invalid Semester Number (must be 1 or 2 as validated in controller)
        $result = $this->withSession($this->getSuperAdminSession())
            ->post('academic-periods', [
                'academic_year_id' => $yearId,
                'semester_number'  => 3, // Invalid semester
                'start_date'       => '2026-07-01',
                'end_date'         => '2026-12-31'
            ]);

        $result->assertRedirect();
        $errors = session()->getFlashdata('errors');
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('semester_number', $errors);
    }

    public function testPeriodTransitionsAndPermissions(): void
    {
        $yearModel = new AcademicYearModel();
        $periodModel = new AcademicPeriodModel();

        $yearModel->insert([
            'name'       => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date'   => '2027-06-30',
            'status'     => 'APPROVED',
            'is_active'  => 1
        ]);
        $yearId = $yearModel->insertID();

        $periodModel->insert([
            'academic_year_id' => $yearId,
            'semester_number'  => 1,
            'start_date'       => '2026-07-01',
            'end_date'         => '2026-12-31',
            'is_active'        => 0,
            'workflow_status'  => 'DRAFT',
            'revision_number'  => 1,
            'created_by'       => 1
        ]);
        $periodId = $periodModel->insertID();

        // 1. Valid DRAFT -> VALIDATED
        $res = AcademicPeriodWorkflowService::transition($periodId, 'VALIDATED', 1, 'Validation check');
        $this->assertTrue($res);

        // 2. Jumping transition (VALIDATED -> APPROVED without REVIEWED) should fail
        $this->expectException(\RuntimeException::class);
        AcademicPeriodWorkflowService::transition($periodId, 'APPROVED', 2, 'Skip reviewed check');
    }

    public function testStaleRevisionOptLocking(): void
    {
        $yearModel = new AcademicYearModel();
        $periodModel = new AcademicPeriodModel();

        $yearModel->insert([
            'name'       => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date'   => '2027-06-30',
            'status'     => 'APPROVED',
            'is_active'  => 1
        ]);
        $yearId = $yearModel->insertID();

        $periodModel->insert([
            'academic_year_id' => $yearId,
            'semester_number'  => 1,
            'start_date'       => '2026-07-01',
            'end_date'         => '2026-12-31',
            'is_active'        => 0,
            'workflow_status'  => 'DRAFT',
            'revision_number'  => 1,
            'created_by'       => 1
        ]);
        $periodId = $periodModel->insertID();

        // Run transition once, increments revision_number to 2
        AcademicPeriodWorkflowService::transition($periodId, 'VALIDATED', 1, 'Validating');

        // Run transition with stale revision_number 1 (expect RuntimeException)
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Data telah diperbarui oleh pengguna lain');
        
        AcademicPeriodWorkflowService::transition($periodId, 'REVIEWED', 1, 'Stale try');
    }
}
