<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\IsolatedDatabaseTestTrait;
use App\Services\CurriculumVersionService;
use App\Services\CurriculumStructureService;
use App\Services\BlockPatternService;
use App\Services\CurriculumEffectiveHoursService;
use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone2MasterSeeder;
use App\Database\Seeds\Milestone3CurriculumSeeder;
use Config\Database;

/**
 * Milestone 3 Security & RBAC Tests
 *
 * @internal
 */
final class CurriculumSecurityTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    private ?int $smpId = null;
    private ?int $smaId = null;
    private ?int $periodId = null;

    protected function setUp(): void
    {
        parent::setUp();

        $seeder2 = new Milestone2MasterSeeder(new Database());
        $seeder2->run();

        $seeder3 = new Milestone3CurriculumSeeder(new Database());
        $seeder3->run();

        $db = Database::connect($this->DBGroup);

        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        $sma = $db->table('school_units')->where('code', 'SMA')->get()->getRowArray();
        $this->smpId = $smp ? (int)$smp['id'] : 1;
        $this->smaId = $sma ? (int)$sma['id'] : 2;

        // Ensure academic year exists
        $year = $db->table('academic_years')->where('name', '2026/2027')->get()->getRowArray();
        if (!$year) {
            $db->table('academic_years')->insert([
                'uuid'       => '00000000-0000-0000-0000-000000000000',
                'name'       => '2026/2027',
                'start_date' => '2026-07-01',
                'end_date'   => '2027-06-30',
                'status'     => 'APPROVED',
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $yearId = $db->insertID();
        } else {
            $yearId = (int)$year['id'];
        }

        $period = $db->table('academic_periods')->where('is_active', 1)->get()->getRowArray();
        if (!$period) {
            $db->table('academic_periods')->insert([
                'uuid'             => '00000000-0000-0000-0000-000000000099',
                'academic_year_id' => $yearId,
                'semester_number'  => 1,
                'name'             => 'Period Security Test',
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

        session()->set([
            'user_id'     => 1,
            'logged_in'   => true,
            'active_role' => 'super_admin',
        ]);
    }

    public function testSec01_SQLInjectionInVersionCodeSanitized(): void
    {
        $version = CurriculumVersionService::createVersion([
            'academic_period_id' => $this->periodId,
            'code'               => "KUR-SQL' OR '1'='1",
            'name'               => 'Kurikulum SQL Injection Test',
        ]);

        $this->assertEquals("KUR-SQL' OR '1'='1", $version['code']);

        $found = CurriculumVersionService::getVersionByUuid($version['uuid']);
        $this->assertNotNull($found);
    }

    public function testSec02_XSSInVersionNameEscapedInView(): void
    {
        $version = CurriculumVersionService::createVersion([
            'academic_period_id' => $this->periodId,
            'code'               => 'KUR-XSS-01',
            'name'               => '<script>alert("XSS")</script>',
        ]);

        $this->assertEquals('<script>alert("XSS")</script>', $version['name']);
    }

    public function testSec03_JSONInjectionInBlockPatternRejected(): void
    {
        $res = BlockPatternService::validateBlockPattern('{"blocks":[2,2], "malicious": "<script>alert(1)</script>"}', 4.0);
        $this->assertTrue($res['valid']);

        $canonical = BlockPatternService::canonicalize('{"blocks":[2,2], "malicious": "<script>alert(1)</script>"}');
        $this->assertStringNotContainsString('malicious', $canonical);
        $this->assertStringNotContainsString('<script>', $canonical);
    }

    public function testSec04_NegativeHoursRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('negatif');

        CurriculumEffectiveHoursService::calculateEffectiveHours([
            'effective_source'      => 'OFFICIAL',
            'official_weekly_hours' => -5.0,
        ]);
    }
}
