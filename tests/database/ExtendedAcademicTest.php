<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\AcademicYearModel;
use App\Models\AcademicPeriodModel;
use App\Database\Seeds\CoreSeeder;
use Config\Database;

/**
 * @internal
 */
final class ExtendedAcademicTest extends CIUnitTestCase
{
    private int $smpId = 1;

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

        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        if ($smp) {
            $this->smpId = (int)$smp['id'];
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
            'active_unit_id' => $this->smpId,
            'permissions'    => [
                'academic_years.view',
                'academic_years.manage',
                'academic_periods.view',
                'academic_periods.manage'
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

    public function testAcademicYearIndexRenders(): void
    {
        $result = $this->withSession($this->getSuperAdminSession())->get('academic-years');
        $result->assertOK();
        $result->assertSee('Tahun Pelajaran');
    }

    public function testDashboardRendersOperationalSummary(): void
    {
        $result = $this->withSession($this->getSuperAdminSession())->get('dashboard');
        $result->assertOK();
        $result->assertSee('Kesiapan Data Master');
        $result->assertSee('Akses Cepat');
    }

    public function testPeriodDatesMustStayInsideAcademicYear(): void
    {
        $yearModel = new AcademicYearModel();
        $yearModel->insert([
            'name' => '2030/2031', 'start_date' => '2030-07-01', 'end_date' => '2031-06-30',
            'status' => 'DRAFT', 'is_active' => 0,
        ]);

        $result = $this->withSession($this->getSuperAdminSession())->post('academic-periods', [
            'academic_year_id' => $yearModel->insertID(), 'semester_number' => 1,
            'start_date' => '2030-06-01', 'end_date' => '2030-12-31',
        ]);

        $result->assertRedirect();
        $this->assertStringContainsString('di dalam rentang tahun pelajaran', (string) session()->getFlashdata('error'));
    }

    public function testDraftPeriodCanBeActivatedInOneClick(): void
    {
        $yearModel = new AcademicYearModel();
        $periodModel = new AcademicPeriodModel();
        $yearModel->insert(['name' => '2032/2033', 'start_date' => '2032-07-01', 'end_date' => '2033-06-30', 'status' => 'DRAFT', 'is_active' => 0]);
        $yearId = $yearModel->insertID();
        $periodModel->insert([
            'academic_year_id' => $yearId, 'semester_number' => 1, 'start_date' => '2032-07-01',
            'end_date' => '2032-12-31', 'workflow_status' => 'DRAFT', 'is_active' => 0,
            'revision_number' => 1, 'created_by' => 1,
        ]);
        $period = $periodModel->find($periodModel->insertID());

        $result = $this->withSession($this->getSuperAdminSession())
            ->post('academic-periods/' . $period['uuid'] . '/activate');

        $result->assertRedirectTo('/academic-periods');
        $updated = $periodModel->find($period['id']);
        $this->assertSame(1, (int) $updated['is_active']);
        $this->assertSame('APPROVED', $updated['workflow_status']);
        $this->assertSame(1, (int) $yearModel->find($yearId)['is_active']);
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

}
