<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Phase12PilotDataSeeder;
use App\Services\SystemDiagnosticsService;
use App\Services\UniversalSyncService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\EducationFoundationFixtureTrait;
use Tests\Support\IsolatedDatabaseTestTrait;

/**
 * Phase 12 — End-to-End Operational Lifecycle & Production Hardening Test Suite.
 *
 * Verifies full end-to-end operational pipeline:
 *   - Phase 12 Pilot Data Seeder (Informatika Fase E & Pathfinder Club).
 *   - Cross-module relational integrity (Curriculum -> Lesson Plan -> Workspace -> Mastery -> Ekstra -> Rapor -> Mutu -> Sync).
 *   - Full Mobile Sync Manifest consistency for seeded data.
 *   - System Diagnostics Engine & Web Admin Dashboard.
 *
 * @internal
 */
final class EndToEndLifecycleTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;
    use EducationFoundationFixtureTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    private SystemDiagnosticsService $diagService;
    private UniversalSyncService $syncService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEducationFoundationFixture();

        // Run Phase 12 Pilot Data Seeder
        $seeder = new Phase12PilotDataSeeder($this->dbConfig ?? new \Config\Database());
        $seeder->setSilent(true);
        $seeder->run();

        $this->diagService = new SystemDiagnosticsService($this->db);
        $this->syncService = new UniversalSyncService($this->db);

        // Ensure permissions are seeded for test user
        $perms = [
            'sync.view'         => 'View Sync Registry and Mobile Logs',
            'sync.manage'       => 'Manage Sync Versions and Gateways',
            'api.mobile_access' => 'Access Mobile Sync API',
        ];

        foreach ($perms as $code => $name) {
            $p = $this->db->table('permissions')->where('code', $code)->get()->getRowArray();
            if (! $p) {
                $this->db->table('permissions')->insert([
                    'code'        => $code,
                    'module'      => 'system',
                    'name'        => $name,
                    'description' => $name,
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
                $permId = (int) $this->db->insertID();
            } else {
                $permId = (int) $p['id'];
            }

            $rolePerm = $this->db->table('role_permissions')->where(['role_id' => 1, 'permission_id' => $permId])->get()->getRowArray();
            if (! $rolePerm) {
                $this->db->table('role_permissions')->insert([
                    'role_id'       => 1,
                    'permission_id' => $permId,
                ]);
            }
        }

        if ($this->db->table('user_roles')->where(['user_id' => 1, 'role_id' => 1])->countAllResults() === 0) {
            $this->db->table('user_roles')->insert(['user_id' => 1, 'role_id' => 1]);
        }
    }

    public function testPhase12PilotSeederExecutesSuccessfully(): void
    {
        // 1. Verify Subject
        $subject = $this->db->table('subjects')->where('code', 'INF-X')->get()->getRowArray();
        $this->assertNotNull($subject);
        $this->assertSame('Informatika Kelas X', $subject['name']);

        // 2. Verify Subject Learning Pack
        $pack = $this->db->table('subject_learning_packs')->where('subject_id', $subject['id'])->get()->getRowArray();
        $this->assertNotNull($pack);
        $this->assertSame('PUBLISHED', $pack['status']);

        // 3. Verify Lesson Plan
        $lp = $this->db->table('lesson_plans')->where('learning_pack_id', $pack['id'])->get()->getRowArray();
        $this->assertNotNull($lp);
        $this->assertSame('Modul Ajar: Algoritma Python Dasar', $lp['session_label']);

        // 4. Verify Pathfinder Extracurricular
        $ekstra = $this->db->table('extracurricular_programs')->where('code', 'PATHFINDER-01')->get()->getRowArray();
        $this->assertNotNull($ekstra);
        $this->assertSame('ACTIVE', $ekstra['status']);

        // Verify members
        $memberCount = $this->db->table('extracurricular_members')->where('program_id', $ekstra['id'])->countAllResults();
        $this->assertGreaterThanOrEqual(3, $memberCount);
    }

    public function testCrossModuleMasteryAndLineageIntegrity(): void
    {
        // 1. Verify TP and Mastery Records
        $tp = $this->db->table('learning_objectives_tp')->where('code', 'TP-INF-X-01')->get()->getRowArray();
        $this->assertNotNull($tp);

        $masteryList = $this->db->table('mastery_records')->where('learning_objective_id', $tp['id'])->get()->getResultArray();
        $this->assertNotEmpty($masteryList);
        $this->assertGreaterThanOrEqual(3, count($masteryList));

        // 2. Verify Teacher Reflection
        $teacher = $this->db->table('teachers')->where('email', 'guru.informatika@wmvaa.test')->get()->getRowArray();
        $this->assertNotNull($teacher);

        $ref = $this->db->table('teacher_reflections')->where('teacher_id', $teacher['id'])->get()->getRowArray();
        $this->assertNotNull($ref);
        $this->assertSame('POST_LESSON', $ref['reflection_type']);
        $this->assertSame('PUBLISHED', $ref['status']);
    }

    public function testUniversalSyncManifestContainsSeededPilotData(): void
    {
        $teacher = $this->db->table('teachers')->where('email', 'guru.informatika@wmvaa.test')->get()->getRowArray();
        $this->assertNotNull($teacher);

        $payload = $this->syncService->syncAll((int) $teacher['id'], (int) $teacher['primary_unit_id'], UniversalSyncService::ROLE_GURU);

        $this->assertArrayHasKey('versions', $payload);
        $this->assertArrayHasKey('tables', $payload);

        $tables = $payload['tables'];

        // Ekstrakurikuler table must contain Pathfinder
        $this->assertNotEmpty($tables[UniversalSyncService::TABLE_EKSTRA]);
        $titles = array_column($tables[UniversalSyncService::TABLE_EKSTRA], 'title');
        $this->assertContains('Klub Kepanduan Pathfinder Club', $titles);

        // Refleksi table must contain teacher reflection
        $this->assertNotEmpty($tables[UniversalSyncService::TABLE_REFLEKSI]);
        $reflectionTypes = array_column($tables[UniversalSyncService::TABLE_REFLEKSI], 'reflection_type');
        $this->assertContains('POST_LESSON', $reflectionTypes);
    }

    public function testSystemDiagnosticsEngineIntegrityAudit(): void
    {
        $report = $this->diagService->runFullDiagnostic($this->unitId);

        $this->assertArrayHasKey('overview', $report);
        $this->assertArrayHasKey('database_integrity', $report);
        $this->assertArrayHasKey('curriculum_lineage', $report);
        $this->assertArrayHasKey('mobile_sync_health', $report);
        $this->assertArrayHasKey('storage_health', $report);
        $this->assertArrayHasKey('module_readiness', $report);

        // Database integrity status must be HEALTHY with 0 orphan issues
        $this->assertSame('HEALTHY', $report['database_integrity']['status']);
        $this->assertEmpty($report['database_integrity']['issues']);
        $this->assertSame(4, $report['database_integrity']['healthy_checks']);

        // Module readiness must have 12 phases
        $this->assertCount(12, $report['module_readiness']);
        $this->assertSame('ONLINE', $report['module_readiness']['Phase 12 — Production Hardening']['status']);
    }

    public function testSystemDiagnosticsWebAdminAndApi(): void
    {
        $sessionData = [
            'user_id'          => 1,
            'active_unit_id'   => $this->unitId,
            'active_period_id' => $this->periodId,
            'logged_in'        => true,
            'user_roles'       => ['superadmin'],
        ];

        // 1. Web Admin Dashboard View
        $res = $this->withSession($sessionData)->get('system/diagnostics');
        $res->assertOK();
        $res->assertSee('Diagnostik');
        $res->assertSee('Kesiapan');

        // 2. JSON API Health Endpoint
        $apiRes = $this->get('api/v1/system/diagnostics?unit_id=' . $this->unitId);
        $apiRes->assertOK();
        $apiJson = json_decode($apiRes->getJSON(), true);
        $this->assertSame('success', $apiJson['status']);
        $this->assertArrayHasKey('module_readiness', $apiJson['data']);

        // 3. Purge Sessions Action
        $purgeRes = $this->withSession($sessionData)->post('system/diagnostics/purge-sessions');
        $purgeRes->assertRedirect();
    }
}
