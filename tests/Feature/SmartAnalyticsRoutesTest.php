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

        $this->ensureUser(1, 'admin', 'Super Admin');
        $this->assignRole(1, 'super_admin', null);
        $this->grantUnit(1, $this->unitId, true);
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

    private function sessionLogin(string $roleCode = 'super_admin'): array
    {
        return [
            'logged_in'        => true,
            'user_id'          => 1,
            'auth_timestamp'   => time(),
            'username'         => 'admin',
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

    public function testSaveNarrativeDraftSuccess(): void
    {
        $db = Database::connect($this->DBGroup);
        $student = $db->table('elective_students')->get()->getRowArray();
        $subject = $db->table('subjects')->get()->getRowArray();
        $classroom = $db->table('classrooms')->get()->getRowArray();

        $studentId = $student ? (int) $student['id'] : 1;
        $subjectId = $subject ? (int) $subject['id'] : 1;
        $classroomId = $classroom ? (int) $classroom['id'] : 1;

        $result = $this->withSession($this->sessionLogin())->post('smart/narrative-drafter/save', [
            'student_id'     => $studentId,
            'subject_id'     => $subjectId,
            'classroom_id'   => $classroomId,
            'narrative_text' => 'Peserta didik menunjukkan kemampuan analisis yang sangat baik dalam memahami algoritma.',
        ]);

        $result->assertStatus(200);
        $json = json_decode($result->getJSON(), true);
        $this->assertSame('success', $json['status']);
    }

    public function testCompleteRemedialUpdatesMastery(): void
    {
        $db = Database::connect($this->DBGroup);
        $student = $db->table('elective_students')->get()->getRowArray();
        $tp = $db->table('learning_objectives_tp')->get()->getRowArray();

        $studentId = $student ? (int) $student['id'] : 1;
        $tpId = $tp ? (int) $tp['id'] : 1;

        $result = $this->withSession($this->sessionLogin())->post('smart/remedial/complete', [
            'student_id'     => $studentId,
            'objective_id'   => $tpId,
            'new_result'     => 'ACHIEVED',
            'remedial_notes' => 'Lulus remedial mandiri dengan skor 85',
        ]);

        $result->assertRedirectTo(base_url('smart/mastery-heatmap'));
    }
}

