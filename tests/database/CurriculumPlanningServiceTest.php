<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone2MasterSeeder;
use App\Database\Seeds\Milestone3CurriculumSeeder;
use App\Services\CurriculumPlanningService;
use App\Services\CurriculumStructureService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

final class CurriculumPlanningServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    private function getOrCreateVersionAndUnit(): array
    {
        $db = Database::connect($this->DBGroup);

        $unit = $db->table('school_units')->where('is_active', 1)->orderBy('id', 'ASC')->get(1)->getRowArray();
        if (!$unit) {
            (new Milestone2MasterSeeder(new Database()))->run();
            $unit = $db->table('school_units')->where('is_active', 1)->orderBy('id', 'ASC')->get(1)->getRowArray();
        }

        $version = $db->table('curriculum_versions')->orderBy('id', 'ASC')->get(1)->getRowArray();
        if (!$version) {
            $period = $db->table('academic_periods')->orderBy('id', 'ASC')->get(1)->getRowArray();
            if (!$period) {
                $year = $db->table('academic_years')->orderBy('id', 'ASC')->get(1)->getRowArray();
                if (!$year) {
                    $db->table('academic_years')->insert([
                        'uuid'       => '30000000-0000-0000-0000-000000000000',
                        'name'       => '2026/2027',
                        'start_date' => '2026-07-01',
                        'end_date'   => '2027-06-30',
                        'status'     => 'ACTIVE',
                        'is_active'  => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    $yearId = $db->insertID();
                } else {
                    $yearId = $year['id'];
                }

                $db->table('academic_periods')->insert([
                    'uuid'               => '30000000-0000-0000-0000-000000000001',
                    'academic_year_id'   => $yearId,
                    'semester_number'    => 1,
                    'name'               => 'Periode Uji',
                    'start_date'         => '2026-07-01',
                    'end_date'           => '2026-12-31',
                    'workflow_status'    => 'OPEN',
                    'is_active'          => 1,
                    'created_at'         => date('Y-m-d H:i:s'),
                ]);
                $period = $db->table('academic_periods')->where('id', $db->insertID())->get()->getRowArray();
            }

            $db->table('curriculum_versions')->insert([
                'uuid' => '30000000-0000-0000-0000-000000000002',
                'academic_period_id' => $period['id'],
                'code' => 'CURR-TEST',
                'name' => 'Kurikulum Testing',
                'workflow_status' => 'DRAFT',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $version = $db->table('curriculum_versions')->where('id', $db->insertID())->get()->getRowArray();
        }

        return [$version, $unit];
    }

    protected function setUp(): void
    {
        parent::setUp();
        session()->set(['user_id' => 1, 'logged_in' => true, 'active_role' => 'super_admin']);
    }

    public function testFiveDaySettingsAndOverviewAreCalculated(): void
    {
        [$version, $unit] = $this->getOrCreateVersionAndUnit();

        $saved = CurriculumPlanningService::saveSettings((int) $version['id'], (int) $unit['id'], [
            'teaching_days_per_week' => 5,
            'selected_day_codes'     => ['MON', 'TUE', 'WED', 'THU', 'FRI'],
            'daily_jp_capacity'      => 9,
            'allow_custom_hours'     => 1,
            'notes'                  => 'Sekolah lima hari',
        ]);

        $this->assertSame(5, (int) $saved['teaching_days_per_week']);
        $this->assertSame(['MON', 'TUE', 'WED', 'THU', 'FRI'], $saved['selected_day_codes']);
        $this->assertSame(9.0, (float) $saved['daily_jp_capacity']);

        $overview = CurriculumPlanningService::buildOverview(
            (int) $version['id'],
            (int) $version['academic_period_id'],
            (int) $unit['id']
        );

        $this->assertSame(45.0, $overview['weekly_capacity']);
        $this->assertArrayHasKey('required_total_hours', $overview['summary']);
        $this->assertArrayHasKey('estimated_teacher_fte', $overview['summary']);
        $this->assertArrayHasKey('allocation_percent', $overview['summary']);
    }

    public function testDayCodesValidationRejectsMismatch(): void
    {
        [$version, $unit] = $this->getOrCreateVersionAndUnit();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/sama dengan jumlah hari/i');

        CurriculumPlanningService::saveSettings((int)$version['id'], (int)$unit['id'], [
            'teaching_days_per_week' => 5,
            'selected_day_codes'     => ['MON', 'TUE', 'WED'],
        ]);
    }

    public function testDayCodesValidationRejectsDuplicates(): void
    {
        [$version, $unit] = $this->getOrCreateVersionAndUnit();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/duplikat/i');

        CurriculumPlanningService::saveSettings((int)$version['id'], (int)$unit['id'], [
            'teaching_days_per_week' => 5,
            'selected_day_codes'     => ['MON', 'TUE', 'WED', 'THU', 'MON'],
        ]);
    }

    public function testDayCodesValidationRejectsInvalidCode(): void
    {
        [$version, $unit] = $this->getOrCreateVersionAndUnit();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/tidak valid/i');

        CurriculumPlanningService::saveSettings((int)$version['id'], (int)$unit['id'], [
            'teaching_days_per_week' => 5,
            'selected_day_codes'     => ['MON', 'TUE', 'WED', 'THU', 'INVALID'],
        ]);
    }

    public function testLockedVersionRejectsSettingsUpdate(): void
    {
        [$version, $unit] = $this->getOrCreateVersionAndUnit();
        $db = Database::connect($this->DBGroup);
        $db->table('curriculum_versions')->where('id', $version['id'])->update(['workflow_status' => 'LOCKED']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/terkunci/i');

        try {
            CurriculumPlanningService::saveSettings((int)$version['id'], (int)$unit['id'], [
                'teaching_days_per_week' => 5,
                'selected_day_codes'     => ['MON', 'TUE', 'WED', 'THU', 'FRI'],
            ]);
        } finally {
            $db->table('curriculum_versions')->where('id', $version['id'])->update(['workflow_status' => 'DRAFT']);
        }
    }

    public function testStaleRevisionRejectsSettingsUpdate(): void
    {
        [$version, $unit] = $this->getOrCreateVersionAndUnit();

        CurriculumPlanningService::saveSettings((int)$version['id'], (int)$unit['id'], [
            'teaching_days_per_week' => 5,
            'selected_day_codes'     => ['MON', 'TUE', 'WED', 'THU', 'FRI'],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Stale Data Error/i');

        CurriculumPlanningService::saveSettings((int)$version['id'], (int)$unit['id'], [
            'teaching_days_per_week' => 5,
            'selected_day_codes'     => ['MON', 'TUE', 'WED', 'THU', 'FRI'],
            'revision_number'        => 999,
        ]);
    }

    public function testWorkloadPolicyResolutionFallbackAndExplicit(): void
    {
        [$version, $unit] = $this->getOrCreateVersionAndUnit();

        $res = CurriculumPlanningService::resolveWorkloadPolicy((int)$version['academic_period_id'], (int)$unit['id']);
        $this->assertArrayHasKey('status', $res);
        $this->assertGreaterThan(0, $res['target_total_hours']);
        $this->assertGreaterThan(0, $res['minimum_teaching_hours']);
    }

    public function testCustomHoursRequiresReason(): void
    {
        [$version, $unit] = $this->getOrCreateVersionAndUnit();
        $db = Database::connect($this->DBGroup);
        $structure = $db->table('curriculum_structures')->where('curriculum_version_id', $version['id'])->get()->getRowArray();

        if (!$structure) {
            $this->markTestSkipped('No structure available for test');
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/alasan/i');

        CurriculumStructureService::updateEffectiveHours($structure['uuid'], [
            'effective_source'    => 'CUSTOM',
            'custom_weekly_hours' => 6.0,
            'adjustment_reason'   => '',
            'revision_number'     => $structure['revision_number'],
        ]);
    }

    public function testCustomHoursNegativeValueIsRejected(): void
    {
        [$version, $unit] = $this->getOrCreateVersionAndUnit();
        $db = Database::connect($this->DBGroup);
        $structure = $db->table('curriculum_structures')->where('curriculum_version_id', $version['id'])->get()->getRowArray();

        if (!$structure) {
            $this->markTestSkipped('No structure available for test');
        }

        $this->expectException(\InvalidArgumentException::class);

        CurriculumStructureService::updateEffectiveHours($structure['uuid'], [
            'effective_source'    => 'CUSTOM',
            'custom_weekly_hours' => -2.0,
            'adjustment_reason'   => 'Penyesuaian negatif',
            'revision_number'     => $structure['revision_number'],
        ]);
    }

    public function testReturnToOfficialEffectiveSourceSuccess(): void
    {
        [$version, $unit] = $this->getOrCreateVersionAndUnit();
        $db = Database::connect($this->DBGroup);
        $structure = $db->table('curriculum_structures')->where('curriculum_version_id', $version['id'])->get()->getRowArray();

        if (!$structure) {
            $this->markTestSkipped('No structure available for test');
        }

        $updated = CurriculumStructureService::updateEffectiveHours($structure['uuid'], [
            'effective_source'    => 'OFFICIAL',
            'adjustment_reason'   => 'Kembali ke standar resmi',
            'revision_number'     => $structure['revision_number'],
        ]);

        $this->assertSame('OFFICIAL', $updated['effective_source']);
        $this->assertEquals($structure['official_weekly_hours'], $updated['effective_weekly_hours']);
    }
}
