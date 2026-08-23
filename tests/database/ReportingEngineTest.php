<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\ReportingService;
use App\Services\ExtracurricularService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\EducationFoundationFixtureTrait;
use Tests\Support\IsolatedDatabaseTestTrait;

/**
 * Phase 9 — Reporting & Portfolio Engine Test Suite.
 *
 * Verifies report snapshots, subject results aggregation, cross-phase
 * integration (Fase 7 & 8), narrative drafting/approval, student portfolio,
 * class analytics, promotion readiness, and official print view rendering.
 *
 * @internal
 */
final class ReportingEngineTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;
    use EducationFoundationFixtureTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    private ReportingService $service;
    protected int $teacherId;
    protected int $studentId;
    protected int $classroomId;
    protected int $subjectId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEducationFoundationFixture();
        $this->service = new ReportingService($this->db);

        // Teacher
        $teacher = $this->db->table('teachers')->where('primary_unit_id', $this->unitId)->get()->getRowArray();
        if (! $teacher) {
            $this->db->table('teachers')->insert([
                'uuid'             => '91000000-0000-4000-8000-000000000001',
                'full_name'        => 'Guru Rapor Test',
                'normalized_name'  => 'GURU RAPOR TEST',
                'employment_status'=> 'ACTIVE',
                'primary_unit_id'  => $this->unitId,
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
            $this->teacherId = (int) $this->db->insertID();
        } else {
            $this->teacherId = (int) $teacher['id'];
        }

        $period = $this->db->table('academic_periods')->where('id', $this->periodId)->get()->getRowArray();
        $yearId = $period ? (int) $period['academic_year_id'] : 1;

        // Classroom
        $classroom = $this->db->table('classrooms')->where('unit_id', $this->unitId)->get()->getRowArray();
        if (! $classroom) {
            $this->db->table('classrooms')->insert([
                'uuid'                => '92000000-0000-4000-8000-000000000002',
                'unit_id'             => $this->unitId,
                'academic_period_id'  => $this->periodId,
                'grade_level_id'      => $this->gradeId,
                'name'                => 'Kelas 10-A Test',
                'homeroom_teacher_id' => $this->teacherId,
                'is_active'           => 1,
                'created_at'          => date('Y-m-d H:i:s'),
            ]);
            $this->classroomId = (int) $this->db->insertID();
        } else {
            $this->classroomId = (int) $classroom['id'];
        }

        // Student
        $student = $this->db->table('elective_students')->where('unit_id', $this->unitId)->get()->getRowArray();
        if (! $student) {
            $this->db->table('elective_students')->insert([
                'uuid'             => '93000000-0000-4000-8000-000000000003',
                'unit_id'          => $this->unitId,
                'academic_year_id' => $yearId,
                'classroom_id'     => $this->classroomId,
                'student_number'   => 'NIS-TEST-9001',
                'full_name'        => 'Siswa Rapor Percontohan',
                'current_grade'    => 10,
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
            $this->studentId = (int) $this->db->insertID();
        } else {
            $this->studentId = (int) $student['id'];
            $this->db->table('elective_students')->where('id', $this->studentId)->update([
                'classroom_id' => $this->classroomId,
                'is_active'    => 1,
            ]);
        }
    }

    public function testCreateAndListPolicies(): void
    {
        $policyId = $this->service->createPolicy([
            'unit_id'            => $this->unitId,
            'academic_period_id' => $this->periodId,
            'subject_id'         => $this->subjectId,
            'name'               => 'Kebijakan Penilaian Matematika',
            'method'             => 'WEIGHTED',
            'config_json'        => json_encode(['formative' => 40, 'summative' => 60]),
        ], 1);

        $this->assertGreaterThan(0, $policyId);

        $policies = $this->service->listPolicies($this->unitId, $this->periodId);
        $this->assertNotEmpty($policies);
        $this->assertSame('Kebijakan Penilaian Matematika', $policies[0]['policy_name']);
    }

    public function testGenerateSnapshotAndDetailEnrichment(): void
    {
        // 1. Generate snapshot
        $snapshotId = $this->service->generateSnapshot(
            $this->unitId,
            $this->periodId,
            $this->studentId,
            1
        );

        $this->assertGreaterThan(0, $snapshotId);

        // 2. Fetch enriched detail
        $detail = $this->service->snapshotDetail($snapshotId);
        $this->assertNotNull($detail);
        $this->assertSame($this->studentId, (int) $detail['student_id']);
        $this->assertSame('Siswa Rapor Percontohan', $detail['student_name']);
        $this->assertArrayHasKey('subjects', $detail);
        $this->assertArrayHasKey('extracurriculars', $detail);
        $this->assertArrayHasKey('cocurriculars', $detail);
        $this->assertArrayHasKey('attendance_summary', $detail);
        $this->assertArrayHasKey('homeroom_teacher', $detail);
        $this->assertSame('DRAFT', $detail['status']);
    }

    public function testGenerateClassSnapshotsAndBulkPublish(): void
    {
        $period = $this->db->table('academic_periods')->where('id', $this->periodId)->get()->getRowArray();
        $yearId = $period ? (int) $period['academic_year_id'] : 1;

        // Add second student to classroom
        $this->db->table('elective_students')->insert([
            'uuid'             => \App\Services\UuidService::v4(),
            'unit_id'          => $this->unitId,
            'academic_year_id' => $yearId,
            'classroom_id'     => $this->classroomId,
            'student_number'   => 'NIS-TEST-' . mt_rand(10000, 99999),
            'full_name'        => 'Siswa Rapor Kedua',
            'current_grade'    => 10,
            'is_active'        => 1,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);
        $secondStudentId = (int) $this->db->insertID();
        $this->assertGreaterThan(0, $secondStudentId);

        // Bulk generate
        $count = $this->service->generateClassSnapshots(
            $this->unitId,
            $this->periodId,
            $this->classroomId,
            1
        );

        $this->assertGreaterThanOrEqual(2, $count);

        // Bulk publish
        $publishedCount = $this->service->publishClassSnapshots(
            $this->unitId,
            $this->periodId,
            $this->classroomId,
            1
        );

        $this->assertSame($count, $publishedCount);

        // Verify status
        $snapshots = $this->service->listSnapshots($this->unitId, $this->periodId, ['status' => 'PUBLISHED']);
        $this->assertGreaterThanOrEqual(2, count($snapshots));
    }

    public function testSmartNarrativeGeneration(): void
    {
        $sampleResultHigh = [
            'subject_name'    => 'Fisika Terapan',
            'final_score'     => 92,
            'final_predicate' => 'A',
            'mastery_pct'     => 95,
        ];
        $narrativeHigh = $this->service->generateDraftNarrative($sampleResultHigh);
        $this->assertStringContainsString('Menunjukkan penguasaan yang sangat baik', $narrativeHigh);
        $this->assertStringContainsString('95%', $narrativeHigh);

        $sampleResultNeedsSupport = [
            'subject_name'    => 'Biologi Molekuler',
            'final_score'     => 62,
            'final_predicate' => 'D',
            'mastery_pct'     => 58,
        ];
        $narrativeLow = $this->service->generateDraftNarrative($sampleResultNeedsSupport);
        $this->assertStringContainsString('Memerlukan pendampingan dan bimbingan belajar', $narrativeLow);
    }

    public function testLockSnapshot(): void
    {
        $snapshotId = $this->service->generateSnapshot(
            $this->unitId,
            $this->periodId,
            $this->studentId,
            1
        );

        $this->service->lockSnapshot($snapshotId, 1);

        $detail = $this->service->snapshotDetail($snapshotId);
        $this->assertSame('LOCKED', $detail['status']);
    }

    public function testPortfolioManagement(): void
    {
        // 1. Add portfolio item
        $itemId = $this->service->addPortfolioItem([
            'student_id'         => $this->studentId,
            'unit_id'            => $this->unitId,
            'academic_period_id' => $this->periodId,
            'category'           => 'PROJECT',
            'title'              => 'Desain Roket Air Sains Terapan',
            'description'        => 'Juara 1 Lomba Roket Sains Sekolah',
            'file_url'           => 'https://drive.google.com/test-doc',
            'is_highlighted'     => 1,
        ], 1);

        $this->assertGreaterThan(0, $itemId);

        // 2. List portfolio
        $items = $this->service->listPortfolio($this->studentId, $this->periodId);
        $this->assertNotEmpty($items);
        $this->assertSame('Desain Roket Air Sains Terapan', $items[0]['title']);
        $this->assertSame(1, (int) $items[0]['is_highlighted']);

        // 3. Toggle highlight
        $this->service->toggleHighlight($itemId, 1);
        $itemsUpdated = $this->service->listPortfolio($this->studentId, $this->periodId);
        $this->assertSame(0, (int) $itemsUpdated[0]['is_highlighted']);

        // 4. Delete item
        $this->service->deletePortfolioItem($itemId, 1);
        $itemsAfterDelete = $this->service->listPortfolio($this->studentId, $this->periodId);
        $this->assertEmpty($itemsAfterDelete);
    }

    public function testReportingStatsAndClassAnalytics(): void
    {
        // Generate snapshot
        $this->service->generateSnapshot(
            $this->unitId,
            $this->periodId,
            $this->studentId,
            1
        );

        $stats = $this->service->reportingStats($this->unitId, $this->periodId);
        $this->assertArrayHasKey('total_students', $stats);
        $this->assertArrayHasKey('draft_count', $stats);
        $this->assertArrayHasKey('overall_avg', $stats);
        $this->assertGreaterThan(0, $stats['total_students']);

        $analytics = $this->service->classAnalytics($this->unitId, $this->periodId);
        $this->assertArrayHasKey('students', $analytics);
        $this->assertArrayHasKey('summary', $analytics);

        $readiness = $this->service->promotionReadiness($this->unitId, $this->periodId, $this->classroomId);
        $this->assertArrayHasKey('rows', $readiness);
        $this->assertArrayHasKey('summary', $readiness);
        $this->assertNotEmpty($readiness['rows']);
    }

    public function testPrintReportCardViewAndJsonApi(): void
    {
        $snapshotId = $this->service->generateSnapshot(
            $this->unitId,
            $this->periodId,
            $this->studentId,
            1
        );

        // Test Print View
        $result = $this->withSession([
            'user_id'          => 1,
            'active_unit_id'   => $this->unitId,
            'active_period_id' => $this->periodId,
            'logged_in'        => true,
        ])->get("reporting/{$snapshotId}/print");

        $result->assertOK();
        $result->assertSee('Laporan Capaian Hasil Belajar');
        $result->assertSee('Siswa Rapor Percontohan');

        // Test JSON API
        $apiResult = $this->withSession([
            'user_id'          => 1,
            'active_unit_id'   => $this->unitId,
            'active_period_id' => $this->periodId,
            'logged_in'        => true,
        ])->get("reporting/student/{$this->studentId}/card");

        $apiResult->assertOK();
        $apiResult->assertJSONFragment(['status' => 'success']);
    }
}