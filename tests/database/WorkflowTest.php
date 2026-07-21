<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\AcademicYearModel;
use App\Models\AcademicPeriodModel;
use App\Services\AcademicPeriodWorkflowService;
use App\Database\Seeds\CoreSeeder;

/**
 * @internal
 */
final class WorkflowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock session and permissions for helper checks
        $session = session();
        $session->set([
            'logged_in'   => true,
            'user_id'     => 1,
            'permissions' => [
                'academic_periods.validate',
                'academic_periods.review',
                'academic_periods.approve',
                'academic_periods.lock'
            ]
        ]);
    }

    public function testWorkflowTransitionsAndLocking(): void
    {
        $yearModel = new AcademicYearModel();
        $periodModel = new AcademicPeriodModel();

        // 1. Create dummy Academic Year
        $yearModel->insert([
            'name'       => '2028/2029',
            'start_date' => '2028-07-01',
            'end_date'   => '2029-06-30',
            'status'     => 'DRAFT',
            'is_active'  => 0
        ]);
        $yearId = $yearModel->insertID();

        // 2. Create dummy Academic Period in DRAFT
        $periodModel->insert([
            'academic_year_id' => $yearId,
            'semester_number'  => 1,
            'start_date'       => '2028-07-01',
            'end_date'         => '2028-12-31',
            'is_active'        => 0,
            'workflow_status'  => 'DRAFT',
            'revision_number'  => 1,
            'created_by'       => 1
        ]);
        $periodId = $periodModel->insertID();

        // 3. Test allowed transition: DRAFT -> VALIDATED
        $res = AcademicPeriodWorkflowService::transition($periodId, 'VALIDATED', 1, 'Validating draft period');
        $this->assertTrue($res);

        $period = $periodModel->find($periodId);
        $this->assertEquals('VALIDATED', $period['workflow_status']);
        $this->assertEquals(2, $period['revision_number']); // Incremented!

        // 4. Test stale update (optimistic locking failure)
        // Trying to transition using stale revision number (1 instead of 2)
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Data telah diperbarui oleh pengguna lain');
        
        AcademicPeriodWorkflowService::transition($periodId, 'REVIEWED', 1, 'Stale transition request');
    }
}
