<?php

namespace Tests\Feature;

use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone2MasterSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Tests\Support\IsolatedDatabaseTestTrait;

final class SmartAnalyticsRoutesTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace   = 'App';
    protected $seed        = CoreSeeder::class;

    private int $unitId;
    private int $periodId;

    protected function setUp(): void
    {
        parent::setUp();
        (new Milestone2MasterSeeder(new Database()))->run();

        $db = Database::connect($this->DBGroup);
        $unit = $db->table('school_units')->get()->getRowArray();
        $this->unitId = $unit ? (int) $unit['id'] : 1;

        $period = $db->table('academic_periods')->where('is_active', 1)->get()->getRowArray();
        $this->periodId = $period ? (int) $period['id'] : 1;

        // Ensure user 1 exists as super_admin
        if (!$db->table('users')->where('id', 1)->get()->getRowArray()) {
            $db->table('users')->insert([
                'id'                   => 1,
                'uuid'                 => '10000000-0000-4000-8000-000000000001',
                'username'             => 'superadmin',
                'password_hash'        => password_hash('password', PASSWORD_DEFAULT),
                'email'                => 'admin@school.test',
                'full_name'            => 'Super Admin',
                'user_type'            => 'STAFF',
                'is_active'            => 1,
                'must_change_password' => 0,
                'created_at'           => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function sessionLogin(string $roleCode = 'super_admin'): array
    {
        return [
            'logged_in'        => true,
            'user_id'          => 1,
            'auth_timestamp'   => time(),
            'username'         => 'superadmin',
            'role_code'        => $roleCode,
            'all_role_codes'   => [$roleCode],
            'active_role'      => $roleCode,
            'active_unit_id'   => $this->unitId,
            'active_period_id' => $this->periodId,
            'unit_access'      => [$this->unitId],
        ];
    }

    public function testLineageGraphPageRendersSuccessfully(): void
    {
        $result = $this->withSession($this->sessionLogin())->get('smart/lineage-graph');
        $result->assertStatus(200);
        $result->assertSee('Peta Lineage Kurikulum');
    }

    public function testMasteryHeatmapPageRendersSuccessfully(): void
    {
        $result = $this->withSession($this->sessionLogin())->get('smart/mastery-heatmap');
        $result->assertStatus(200);
        $result->assertSee('Mastery Heatmap TP');
    }

    public function testReflectionTrendsPageRendersSuccessfully(): void
    {
        $result = $this->withSession($this->sessionLogin())->get('smart/reflection-trends');
        $result->assertStatus(200);
        $result->assertSee('Tren Refleksi Mengajar');
    }

    public function testNarrativeDrafterPageRendersSuccessfully(): void
    {
        $result = $this->withSession($this->sessionLogin())->get('smart/narrative-drafter');
        $result->assertStatus(200);
        $result->assertSee('Penyusun Draf Narasi Rapor');
    }

    public function testRemedialPackagePageRendersSuccessfully(): void
    {
        $result = $this->withSession($this->sessionLogin())->get('smart/remedial-package');
        $result->assertStatus(200);
        $result->assertSee('Paket Remedial Terarah');
    }

    public function testSummativeIndexRendersSuccessfully(): void
    {
        $result = $this->withSession($this->sessionLogin())->get('summative');
        $result->assertStatus(200);
    }
}
