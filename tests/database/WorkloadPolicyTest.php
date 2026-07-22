<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\WorkloadPolicyModel;
use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone2MasterSeeder;
use App\Database\Seeds\Milestone3CurriculumSeeder;
use App\Database\Seeds\Milestone4Seeder;
use Config\Database;

/**
 * @internal
 */
final class WorkloadPolicyTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    private int $smpId = 1;
    private int $periodId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        (new Milestone2MasterSeeder(new Database()))->run();
        (new Milestone3CurriculumSeeder(new Database()))->run();
        (new Milestone4Seeder(new Database()))->run();

        $db = Database::connect($this->DBGroup);

        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        $this->smpId = $smp ? (int)$smp['id'] : 1;

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
    }

    public function testWorkloadPolicyCreationAndPriority(): void
    {
        $policyModel = new WorkloadPolicyModel();

        $pId = $policyModel->insert([
            'uuid'                   => '00000000-0000-0000-0000-000000000340',
            'academic_period_id'     => $this->periodId,
            'unit_id'                => $this->smpId,
            'employment_status'      => 'GURU_TETAP',
            'minimum_teaching_hours' => 24.00,
            'maximum_teaching_hours' => 37.50,
            'target_total_hours'     => 24.00,
            'maximum_total_hours'    => 40.00,
            'is_active'              => 1,
            'priority'               => 10
        ]);

        $this->assertGreaterThan(0, $pId);
        $policy = $policyModel->find($pId);
        $this->assertEquals(24.00, (float)$policy['minimum_teaching_hours']);
        $this->assertEquals(10, (int)$policy['priority']);
    }
}
