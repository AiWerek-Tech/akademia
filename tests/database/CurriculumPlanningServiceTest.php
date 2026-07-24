<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone2MasterSeeder;
use App\Database\Seeds\Milestone3CurriculumSeeder;
use App\Services\CurriculumPlanningService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

final class CurriculumPlanningServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        (new Milestone2MasterSeeder(new Database()))->run();
        (new Milestone3CurriculumSeeder(new Database()))->run();
        session()->set(['user_id' => 1, 'logged_in' => true, 'active_role' => 'super_admin']);
    }

    public function testFiveDaySettingsAndOverviewAreCalculated(): void
    {
        $db = Database::connect($this->DBGroup);
        $unit = $db->table('school_units')->where('is_active', 1)->orderBy('id', 'ASC')->get(1)->getRowArray();
        $year = $db->table('academic_years')->orderBy('id', 'ASC')->get(1)->getRowArray();
        if (!$year) {
            $db->table('academic_years')->insert([
                'uuid' => '20000000-0000-0000-0000-000000000000',
                'name' => '2026/2027',
                'start_date' => '2026-07-01',
                'end_date' => '2027-06-30',
                'status' => 'ACTIVE',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $year = $db->table('academic_years')->where('id', $db->insertID())->get()->getRowArray();
        }
        $period = $db->table('academic_periods')->orderBy('id', 'ASC')->get(1)->getRowArray();
        if (!$period) {
            $db->table('academic_periods')->insert([
                'uuid' => '20000000-0000-0000-0000-000000000001',
                'academic_year_id' => $year['id'],
                'semester_number' => 1,
                'name' => 'Periode Uji Perencanaan',
                'start_date' => '2026-07-01',
                'end_date' => '2026-12-31',
                'workflow_status' => 'OPEN',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $period = $db->table('academic_periods')->where('id', $db->insertID())->get()->getRowArray();
        }

        $db->table('curriculum_versions')->insert([
            'uuid' => '20000000-0000-0000-0000-000000000002',
            'academic_period_id' => $period['id'],
            'code' => 'PLAN-TEST',
            'name' => 'Kurikulum Uji Perencanaan',
            'workflow_status' => 'DRAFT',
            'is_active' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $version = $db->table('curriculum_versions')->where('id', $db->insertID())->get()->getRowArray();

        $saved = CurriculumPlanningService::saveSettings((int) $version['id'], (int) $unit['id'], [
            'teaching_days_per_week' => 5,
            'daily_jp_capacity' => 9,
            'teacher_minimum_hours' => 24,
            'teacher_maximum_hours' => 40,
            'allow_custom_hours' => 1,
            'notes' => 'Sekolah lima hari',
        ]);

        $this->assertSame(5, (int) $saved['teaching_days_per_week']);
        $this->assertSame(9.0, (float) $saved['daily_jp_capacity']);

        $overview = CurriculumPlanningService::buildOverview(
            (int) $version['id'],
            (int) $version['academic_period_id'],
            (int) $unit['id']
        );

        $this->assertSame(45.0, $overview['weekly_capacity']);
        $this->assertArrayHasKey('teacher_demand_hours', $overview['summary']);
        $this->assertArrayHasKey('allocation_percent', $overview['summary']);
        $this->assertNotEmpty($overview['grades']);
        foreach ($overview['grades'] as $grade) {
            $this->assertSame(45.0, $grade['capacity']);
        }
    }

    public function testInvalidTeacherLimitsAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CurriculumPlanningService::saveSettings(1, 1, [
            'teaching_days_per_week' => 5,
            'daily_jp_capacity' => 9,
            'teacher_minimum_hours' => 40,
            'teacher_maximum_hours' => 24,
        ]);
    }
}
