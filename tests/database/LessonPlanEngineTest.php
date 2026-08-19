<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\LearningPackService;
use App\Services\LessonPlanService;
use App\Services\SubjectLearningPackEngineService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use InvalidArgumentException;
use RuntimeException;
use Tests\Support\EducationFoundationFixtureTrait;
use Tests\Support\IsolatedDatabaseTestTrait;

final class LessonPlanEngineTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;
    use EducationFoundationFixtureTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEducationFoundationFixture();
    }

    public function testPhase4SchemaIsAvailable(): void
    {
        foreach ([
            'lesson_plans',
            'lesson_plan_objectives',
            'lesson_plan_stages',
            'lesson_plan_activities',
            'lesson_plan_assessments',
        ] as $table) {
            $this->assertTrue($this->db->tableExists($table), $table);
        }
    }

    public function testPhase4Iteration2SchemaIsAvailable(): void
    {
        foreach ([
            'lesson_plan_assessment_rubrics',
            'lesson_plan_activity_resources',
        ] as $table) {
            $this->assertTrue($this->db->tableExists($table), $table);
        }
        $this->assertTrue($this->db->fieldExists('graduate_profile_alignment', 'lesson_plan_activities'));
    }

    public function testCreateLessonPlanAndPopulateStructure(): void
    {
        $plan = LessonPlanService::create([
            'academic_period_id' => $this->periodId,
            'unit_id' => $this->unitId,
            'subject_id' => $this->subjectId,
            'grade_level_id' => $this->gradeId,
            'teacher_id' => 1,
            'date' => '2026-09-01',
            'session_number' => 1,
            'session_label' => 'Pertemuan 1 — Algoritma Dasar',
            'learning_pack_id' => null,
        ]);

        $this->assertSame('DRAFT', $plan['status']);
        $this->assertSame(1, (int) $plan['revision_number']);
        $this->assertSame('Pertemuan 1 — Algoritma Dasar', $plan['session_label']);

        // Add identification/design
        $updated = LessonPlanService::updateDesign($plan['uuid'], [
            'identification_notes' => 'Kelas X, fase E, TP berpikir komputasional',
            'learner_readiness' => 'Siswa belum pernah belajar algoritma',
            'pedagogical_practice' => 'Problem-based Learning',
        ]);
        $this->assertSame('Problem-based Learning', $updated['pedagogical_practice']);

        // Add objective
        $objective = $this->createTestObjective();
        LessonPlanService::addObjective($plan['uuid'], $objective['uuid'], ['role' => 'PRIMARY']);

        // Add stages
        $memahami = LessonPlanService::addStage($plan['uuid'], [
            'stage_type' => 'MEMAHAMI',
            'title' => 'Apersepsi',
            'estimated_minutes' => 10,
        ]);
        $mengaplikasi = LessonPlanService::addStage($plan['uuid'], [
            'stage_type' => 'MENGAPLIKASI',
            'title' => 'Praktik',
            'estimated_minutes' => 60,
        ]);
        $merefleksi = LessonPlanService::addStage($plan['uuid'], [
            'stage_type' => 'MEREFLEKSI',
            'title' => 'Refleksi',
            'estimated_minutes' => 15,
        ]);

        // Add activities
        $act1 = LessonPlanService::addActivity($plan['uuid'], [
            'lesson_plan_stage_uuid' => $memahami['uuid'],
            'custom_title' => 'Tanya jawab pemantik',
            'delivery_mode' => 'DISCUSSION',
            'grouping_mode' => 'WHOLE_CLASS',
            'estimated_minutes' => 10,
        ]);
        $act2 = LessonPlanService::addActivity($plan['uuid'], [
            'lesson_plan_stage_uuid' => $mengaplikasi['uuid'],
            'custom_title' => 'Pseudocode practice',
            'delivery_mode' => 'PRACTICE',
            'grouping_mode' => 'PAIR',
            'estimated_minutes' => 60,
        ]);

        // Add assessments
        LessonPlanService::addAssessment($plan['uuid'], [
            'assessment_purpose' => 'FORMATIVE',
            'recommended_method' => 'Observasi proses kelompok',
            'criteria_reference' => 'Kemampuan menuliskan pseudocode',
        ]);

        // Verify summary
        $summary = LessonPlanService::summary($plan['uuid']);
        $this->assertSame(1, $summary['objectives_count']);
        $this->assertSame(3, $summary['stages_count']);
        $this->assertSame(2, $summary['activities_count']);
        $this->assertSame(1, $summary['assessments_count']);
        $this->assertSame(70, $summary['total_estimated_minutes']);
        $this->assertEmpty($summary['warnings']);
    }

    public function testLessonPlanWorkflowTransitions(): void
    {
        $plan = $this->createMinimalPlan();

        $result = LessonPlanService::transition($plan['uuid'], 'READY', 1);
        $this->assertSame('READY', $result['status']);

        $result = LessonPlanService::transition($plan['uuid'], 'IN_PROGRESS', 2);
        $this->assertSame('IN_PROGRESS', $result['status']);

        $result = LessonPlanService::transition($plan['uuid'], 'COMPLETED', 3);
        $this->assertSame('COMPLETED', $result['status']);

        $result = LessonPlanService::transition($plan['uuid'], 'REFLECTED', 4);
        $this->assertSame('REFLECTED', $result['status']);
    }

    public function testInvalidTransitionIsRejected(): void
    {
        $plan = $this->createMinimalPlan();
        LessonPlanService::transition($plan['uuid'], 'READY', 1);
        LessonPlanService::transition($plan['uuid'], 'IN_PROGRESS', 2);
        LessonPlanService::transition($plan['uuid'], 'COMPLETED', 3);

        $this->expectException(InvalidArgumentException::class);
        LessonPlanService::transition($plan['uuid'], 'DRAFT', 4);
    }

    public function testCompletedPlanIsImmutable(): void
    {
        $plan = $this->createMinimalPlan();
        LessonPlanService::transition($plan['uuid'], 'READY', 1);
        LessonPlanService::transition($plan['uuid'], 'IN_PROGRESS', 2);
        LessonPlanService::transition($plan['uuid'], 'COMPLETED', 3);

        $this->expectException(RuntimeException::class);
        LessonPlanService::addStage($plan['uuid'], ['stage_type' => 'MEMAHAMI']);
    }

    public function testClonePlanPreservesStructure(): void
    {
        $plan = $this->createMinimalPlan();
        $objective = $this->createTestObjective();
        LessonPlanService::addObjective($plan['uuid'], $objective['uuid']);
        LessonPlanService::addStage($plan['uuid'], ['stage_type' => 'MEMAHAMI', 'title' => 'Apersepsi', 'estimated_minutes' => 10]);
        LessonPlanService::addActivity($plan['uuid'], ['custom_title' => 'Diskusi', 'delivery_mode' => 'DISCUSSION', 'estimated_minutes' => 30]);
        LessonPlanService::addAssessment($plan['uuid'], ['assessment_purpose' => 'FORMATIVE', 'recommended_method' => 'Observasi']);

        LessonPlanService::updateDesign($plan['uuid'], [
            'identification_notes' => 'Test notes',
            'pedagogical_practice' => 'PBL',
        ]);

        $cloned = LessonPlanService::clone($plan['uuid'], [
            'teacher_id' => 1,
            'date' => '2026-09-08',
        ]);

        $this->assertNotSame($plan['uuid'], $cloned['uuid']);
        $this->assertSame('DRAFT', $cloned['status']);
        $this->assertSame('PBL', $cloned['pedagogical_practice']);

        $summary = LessonPlanService::summary($cloned['uuid']);
        $this->assertSame(1, $summary['objectives_count']);
        $this->assertSame(1, $summary['stages_count']);
        $this->assertSame(1, $summary['activities_count']);
        $this->assertSame(1, $summary['assessments_count']);
    }

    public function testLessonPlanPagesRender(): void
    {
        $plan = $this->createMinimalPlan();

        foreach (['', '/overview', '/design', '/stages', '/activities', '/assessments', '/print'] as $path) {
            $this->withSession(session()->get())->get('lesson-plans/' . $plan['uuid'] . $path)->assertOK();
        }
    }

    public function testExportDocxReturnsDownloadableFile(): void
    {
        $plan = $this->createMinimalPlan();
        // Add some data so the DOCX has content
        LessonPlanService::updateDesign($plan['uuid'], ['identification_notes' => 'Kelas X']);
        LessonPlanService::addObjective($plan['uuid'], $this->createTestObjective()['uuid']);
        LessonPlanService::addStage($plan['uuid'], ['stage_type' => 'MEMAHAMI', 'title' => 'Apersepsi', 'estimated_minutes' => 10]);
        LessonPlanService::addActivity($plan['uuid'], ['custom_title' => 'Diskusi', 'delivery_mode' => 'DISCUSSION', 'estimated_minutes' => 30]);
        LessonPlanService::addAssessment($plan['uuid'], ['assessment_purpose' => 'FORMATIVE', 'recommended_method' => 'Observasi']);

        // Service generation produces a valid DOCX file
        $path = \App\Services\LessonPlanDocxService::generate($plan['uuid']);
        $this->assertFileExists($path);
        $this->assertGreaterThan(500, filesize($path));
        // Verify it's a valid ZIP (DOCX = ZIP)
        $this->assertSame('PK', file_get_contents($path, false, null, 0, 2));
        @unlink($path);

        // Controller endpoint is reachable (returns redirect or response)
        $this->withSession(session()->get())->get('lesson-plans/' . $plan['uuid'] . '/export-docx');
    }

    public function testExportPdfReturnsDownloadableFile(): void
    {
        $plan = $this->createMinimalPlan();
        LessonPlanService::updateDesign($plan['uuid'], ['identification_notes' => 'Kelas X']);
        LessonPlanService::addObjective($plan['uuid'], $this->createTestObjective()['uuid']);
        LessonPlanService::addStage($plan['uuid'], ['stage_type' => 'MEMAHAMI', 'title' => 'Apersepsi', 'estimated_minutes' => 10]);
        LessonPlanService::addActivity($plan['uuid'], ['custom_title' => 'Diskusi', 'delivery_mode' => 'DISCUSSION', 'estimated_minutes' => 30]);
        LessonPlanService::addAssessment($plan['uuid'], ['assessment_purpose' => 'FORMATIVE', 'recommended_method' => 'Observasi']);

        // Service generation produces a valid PDF file
        $path = \App\Services\LessonPlanPdfService::generate($plan['uuid']);
        $this->assertFileExists($path);
        $this->assertGreaterThan(500, filesize($path));
        // Verify it's a valid PDF (starts with %PDF)
        $this->assertStringStartsWith('%PDF', file_get_contents($path, false, null, 0, 5));
        @unlink($path);

        // Controller endpoint is reachable
        $this->withSession(session()->get())->get('lesson-plans/' . $plan['uuid'] . '/export-pdf');
    }

    public function testAddRubricToAssessment(): void
    {
        $plan = $this->createMinimalPlan();
        $assessment = LessonPlanService::addAssessment($plan['uuid'], [
            'assessment_purpose' => 'FORMATIVE',
            'recommended_method' => 'Rubrik penilaian',
        ]);

        $rubric = LessonPlanService::addRubric($assessment['uuid'], [
            'criterion_description' => 'Kemampuan menuliskan pseudocode',
            'rubric_levels' => [
                ['level' => 4, 'description' => 'Lengkap dan benar', 'score' => '90-100'],
                ['level' => 3, 'description' => 'Hampir lengkap', 'score' => '70-89'],
                ['level' => 2, 'description' => 'Sebagian benar', 'score' => '50-69'],
                ['level' => 1, 'description' => 'Belum memahami', 'score' => '0-49'],
            ],
            'sequence_order' => 1,
        ]);

        $this->assertGreaterThan(0, (int) $rubric['id']);
        $this->assertSame('Kemampuan menuliskan pseudocode', $rubric['criterion_description']);
        $levels = json_decode((string) $rubric['rubric_levels'], true);
        $this->assertCount(4, $levels);
        $this->assertSame(4, $levels[0]['level']);
    }

    public function testLinkActivityResource(): void
    {
        $plan = $this->createMinimalPlan();
        $activity = LessonPlanService::addActivity($plan['uuid'], [
            'custom_title' => 'Praktik spreadsheet',
            'delivery_mode' => 'PLUGGED',
            'estimated_minutes' => 45,
        ]);

        $resource = LessonPlanService::linkActivityResource($activity['uuid'], [
            'custom_description' => 'Komputer/laptop kelompok',
            'quantity' => 8,
            'is_required' => 1,
        ]);

        $this->assertGreaterThan(0, (int) $resource['id']);
        $this->assertSame('Komputer/laptop kelompok', $resource['custom_description']);
        $this->assertSame(8, (int) $resource['quantity']);
    }

    public function testPlanValidationFailsOnIncompletePlan(): void
    {
        $plan = $this->createMinimalPlan();
        $result = LessonPlanService::validatePlan($plan['uuid']);

        $this->assertFalse($result['valid']);
        $this->assertContains('identification_notes_missing', $result['errors']);
        $this->assertContains('no_objectives', $result['errors']);
        $this->assertContains('no_stages', $result['errors']);
        $this->assertContains('no_activities', $result['errors']);
        $this->assertContains('no_assessments', $result['errors']);
    }

    public function testPlanValidationPassesOnCompletePlan(): void
    {
        $plan = $this->createMinimalPlan();
        $objective = $this->createTestObjective();
        LessonPlanService::addObjective($plan['uuid'], $objective['uuid']);
        LessonPlanService::addStage($plan['uuid'], ['stage_type' => 'MEMAHAMI', 'estimated_minutes' => 10]);
        LessonPlanService::addActivity($plan['uuid'], ['custom_title' => 'Diskusi', 'delivery_mode' => 'DISCUSSION', 'estimated_minutes' => 30]);
        LessonPlanService::addAssessment($plan['uuid'], ['assessment_purpose' => 'FORMATIVE', 'recommended_method' => 'Observasi']);
        LessonPlanService::updateDesign($plan['uuid'], ['identification_notes' => 'Kelas X, fase E']);

        $result = LessonPlanService::validatePlan($plan['uuid']);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testListRubricsByAssessment(): void
    {
        $plan = $this->createMinimalPlan();
        $assessment = LessonPlanService::addAssessment($plan['uuid'], [
            'assessment_purpose' => 'FORMATIVE',
            'recommended_method' => 'Rubrik penilaian',
        ]);

        LessonPlanService::addRubric($assessment['uuid'], [
            'criterion_description' => 'Kriteria A',
            'rubric_levels' => [['level' => 4, 'description' => 'Baik']],
            'sequence_order' => 1,
        ]);
        LessonPlanService::addRubric($assessment['uuid'], [
            'criterion_description' => 'Kriteria B',
            'sequence_order' => 2,
        ]);

        $rubrics = LessonPlanService::listRubrics($assessment['uuid']);
        $this->assertCount(2, $rubrics);
        $this->assertSame('Kriteria A', $rubrics[0]['criterion_description']);
        $this->assertSame('Kriteria B', $rubrics[1]['criterion_description']);
    }

    public function testUpdateRubric(): void
    {
        $plan = $this->createMinimalPlan();
        $assessment = LessonPlanService::addAssessment($plan['uuid'], [
            'assessment_purpose' => 'SUMMATIVE',
            'recommended_method' => 'Tes tertulis',
        ]);
        $rubric = LessonPlanService::addRubric($assessment['uuid'], [
            'criterion_description' => 'Asal kriteria',
            'rubric_levels' => [['level' => 3, 'description' => 'Cukup']],
            'sequence_order' => 1,
        ]);

        $updated = LessonPlanService::updateRubric($rubric['uuid'], [
            'criterion_description' => 'Kriteria yang diperbarui',
            'rubric_levels' => [
                ['level' => 4, 'description' => 'Sangat baik'],
                ['level' => 3, 'description' => 'Baik'],
            ],
        ]);

        $this->assertSame('Kriteria yang diperbarui', $updated['criterion_description']);
        $levels = json_decode((string) $updated['rubric_levels'], true);
        $this->assertCount(2, $levels);
    }

    public function testDeleteRubric(): void
    {
        $plan = $this->createMinimalPlan();
        $assessment = LessonPlanService::addAssessment($plan['uuid'], [
            'assessment_purpose' => 'FORMATIVE',
            'recommended_method' => 'Observasi',
        ]);
        $rubric = LessonPlanService::addRubric($assessment['uuid'], [
            'criterion_description' => 'Kriteria sementara',
            'sequence_order' => 1,
        ]);

        LessonPlanService::deleteRubric($rubric['uuid']);

        $rubrics = LessonPlanService::listRubrics($assessment['uuid']);
        $this->assertCount(0, $rubrics);
    }

    public function testDeleteRubricOnCompletedPlanFails(): void
    {
        $plan = $this->createMinimalPlan();
        $assessment = LessonPlanService::addAssessment($plan['uuid'], [
            'assessment_purpose' => 'FORMATIVE',
            'recommended_method' => 'Observasi',
        ]);
        $rubric = LessonPlanService::addRubric($assessment['uuid'], [
            'criterion_description' => 'Kriteria',
            'sequence_order' => 1,
        ]);

        LessonPlanService::transition($plan['uuid'], 'READY', 1);
        LessonPlanService::transition($plan['uuid'], 'IN_PROGRESS', 2);
        LessonPlanService::transition($plan['uuid'], 'COMPLETED', 3);

        $this->expectException(RuntimeException::class);
        LessonPlanService::deleteRubric($rubric['uuid']);
    }

    public function testListActivityResources(): void
    {
        $plan = $this->createMinimalPlan();
        $activity = LessonPlanService::addActivity($plan['uuid'], [
            'custom_title' => 'Praktik',
            'delivery_mode' => 'PLUGGED',
        ]);

        LessonPlanService::linkActivityResource($activity['uuid'], [
            'custom_description' => 'Laptop',
            'quantity' => 5,
            'is_required' => 1,
        ]);
        LessonPlanService::linkActivityResource($activity['uuid'], [
            'custom_description' => 'Proyektor',
            'quantity' => 1,
            'is_required' => 0,
        ]);

        $resources = LessonPlanService::listActivityResources($activity['uuid']);
        $this->assertCount(2, $resources);
        $this->assertSame('Laptop', $resources[0]['custom_description']);
    }

    public function testUpdateActivityResource(): void
    {
        $plan = $this->createMinimalPlan();
        $activity = LessonPlanService::addActivity($plan['uuid'], [
            'custom_title' => 'Lab',
            'delivery_mode' => 'PRACTICE',
        ]);
        $res = LessonPlanService::linkActivityResource($activity['uuid'], [
            'custom_description' => 'Komputer lama',
            'quantity' => 10,
        ]);

        $updated = LessonPlanService::updateActivityResource($res['uuid'], [
            'custom_description' => 'Komputer baru',
            'quantity' => 12,
            'is_required' => 1,
        ]);

        $this->assertSame('Komputer baru', $updated['custom_description']);
        $this->assertSame(12, (int) $updated['quantity']);
        $this->assertSame(1, (int) $updated['is_required']);
    }

    public function testDeleteActivityResource(): void
    {
        $plan = $this->createMinimalPlan();
        $activity = LessonPlanService::addActivity($plan['uuid'], [
            'custom_title' => 'Workshop',
            'delivery_mode' => 'UNPLUGGED',
        ]);
        $res = LessonPlanService::linkActivityResource($activity['uuid'], [
            'custom_description' => 'Kertas',
            'quantity' => 20,
        ]);

        LessonPlanService::deleteActivityResource($res['uuid']);

        $resources = LessonPlanService::listActivityResources($activity['uuid']);
        $this->assertCount(0, $resources);
    }

    public function testClonePreservesRubricsAndResources(): void
    {
        $plan = $this->createMinimalPlan();
        LessonPlanService::updateDesign($plan['uuid'], ['identification_notes' => 'Clone test']);
        LessonPlanService::addObjective($plan['uuid'], $this->createTestObjective()['uuid']);
        LessonPlanService::addStage($plan['uuid'], ['stage_type' => 'MEMAHAMI', 'estimated_minutes' => 10]);
        $activity = LessonPlanService::addActivity($plan['uuid'], [
            'custom_title' => 'Diskusi',
            'delivery_mode' => 'DISCUSSION',
            'estimated_minutes' => 30,
        ]);
        LessonPlanService::linkActivityResource($activity['uuid'], [
            'custom_description' => 'LCD Projector',
            'quantity' => 1,
            'is_required' => 1,
        ]);
        $assessment = LessonPlanService::addAssessment($plan['uuid'], [
            'assessment_purpose' => 'FORMATIVE',
            'recommended_method' => 'Observasi',
        ]);
        LessonPlanService::addRubric($assessment['uuid'], [
            'criterion_description' => 'Kemampuan berpikir komputasional',
            'rubric_levels' => [['level' => 4, 'description' => 'Sangat baik']],
            'sequence_order' => 1,
        ]);

        $cloned = LessonPlanService::clone($plan['uuid'], [
            'teacher_id' => 1,
            'date' => '2026-09-08',
        ]);

        // Verify rubrics were cloned
        $clonedAssessments = $db = \Config\Database::connect()
            ->table('lesson_plan_assessments')
            ->where('lesson_plan_id', (int) $cloned['id'])
            ->get()->getResultArray();
        $this->assertCount(1, $clonedAssessments);

        $clonedRubrics = \Config\Database::connect()
            ->table('lesson_plan_assessment_rubrics')
            ->where('lesson_plan_assessment_id', (int) $clonedAssessments[0]['id'])
            ->get()->getResultArray();
        $this->assertCount(1, $clonedRubrics);
        $this->assertSame('Kemampuan berpikir komputasional', $clonedRubrics[0]['criterion_description']);

        // Verify activity resources were cloned
        $clonedActivities = \Config\Database::connect()
            ->table('lesson_plan_activities')
            ->where('lesson_plan_id', (int) $cloned['id'])
            ->get()->getResultArray();
        $clonedResources = \Config\Database::connect()
            ->table('lesson_plan_activity_resources')
            ->where('lesson_plan_activity_id', (int) $clonedActivities[0]['id'])
            ->get()->getResultArray();
        $this->assertCount(1, $clonedResources);
        $this->assertSame('LCD Projector', $clonedResources[0]['custom_description']);
    }

    public function testSummaryIncludesRubricAndResourceCounts(): void
    {
        $plan = $this->createMinimalPlan();
        $activity = LessonPlanService::addActivity($plan['uuid'], [
            'custom_title' => 'Praktik',
            'delivery_mode' => 'PRACTICE',
        ]);
        LessonPlanService::linkActivityResource($activity['uuid'], ['custom_description' => 'Monitor', 'quantity' => 5]);
        $assessment = LessonPlanService::addAssessment($plan['uuid'], [
            'assessment_purpose' => 'FORMATIVE',
            'recommended_method' => 'Rubrik',
        ]);
        LessonPlanService::addRubric($assessment['uuid'], [
            'criterion_description' => 'Kriteria 1',
            'sequence_order' => 1,
        ]);
        LessonPlanService::addRubric($assessment['uuid'], [
            'criterion_description' => 'Kriteria 2',
            'sequence_order' => 2,
        ]);

        $summary = LessonPlanService::summary($plan['uuid']);
        $this->assertSame(2, $summary['rubrics_count']);
        $this->assertSame(1, $summary['activity_resources_count']);
    }

    public function testAddActivityWithGraduateProfileAlignment(): void
    {
        $plan = $this->createMinimalPlan();
        $activity = LessonPlanService::addActivity($plan['uuid'], [
            'custom_title' => 'Projek KA',
            'delivery_mode' => 'PROJECT',
            'graduate_profile_alignment' => 'Berpikir komputasional — analisis algoritma',
        ]);

        $this->assertSame('Berpikir komputasional — analisis algoritma', $activity['graduate_profile_alignment']);
    }

    public function testUpdateActivityGraduateProfileAlignment(): void
    {
        $plan = $this->createMinimalPlan();
        $activity = LessonPlanService::addActivity($plan['uuid'], [
            'custom_title' => 'Diskusi',
            'delivery_mode' => 'DISCUSSION',
        ]);
        $this->assertNull($activity['graduate_profile_alignment']);

        $updated = LessonPlanService::updateActivity($activity['uuid'], [
            'graduate_profile_alignment' => 'Mandiri — pengelolaan diri dalam proyek',
        ]);
        $this->assertSame('Mandiri — pengelolaan diri dalam proyek', $updated['graduate_profile_alignment']);
    }

    public function testUpdateActivityRejectsOnCompletedPlan(): void
    {
        $plan = $this->createMinimalPlan();
        $activity = LessonPlanService::addActivity($plan['uuid'], [
            'custom_title' => 'Lab',
            'delivery_mode' => 'PRACTICE',
        ]);

        LessonPlanService::transition($plan['uuid'], 'READY', 1);
        LessonPlanService::transition($plan['uuid'], 'IN_PROGRESS', 2);
        LessonPlanService::transition($plan['uuid'], 'COMPLETED', 3);

        $this->expectException(RuntimeException::class);
        LessonPlanService::updateActivity($activity['uuid'], [
            'graduate_profile_alignment' => 'Should not work',
        ]);
    }

    public function testClonePreservesGraduateProfileAlignment(): void
    {
        $plan = $this->createMinimalPlan();
        LessonPlanService::updateDesign($plan['uuid'], ['identification_notes' => 'Clone GPA test']);
        LessonPlanService::addObjective($plan['uuid'], $this->createTestObjective()['uuid']);
        $activity = LessonPlanService::addActivity($plan['uuid'], [
            'custom_title' => 'Projek',
            'delivery_mode' => 'PROJECT',
            'graduate_profile_alignment' => 'Kreatif — desain solusi inovatif',
        ]);

        $cloned = LessonPlanService::clone($plan['uuid'], [
            'teacher_id' => 1,
            'date' => '2026-09-15',
        ]);

        $clonedActivities = \Config\Database::connect()
            ->table('lesson_plan_activities')
            ->where('lesson_plan_id', (int) $cloned['id'])
            ->get()->getResultArray();
        $this->assertCount(1, $clonedActivities);
        $this->assertSame('Kreatif — desain solusi inovatif', $clonedActivities[0]['graduate_profile_alignment']);
    }

    // ── Phase 3 auto-populate tests ──

    public function testPopulateFromPackCreatesObjectivesStagesActivitiesAndAssessments(): void
    {
        $objective = $this->createTestObjective();
        $pack = $this->createPack('POP-1', 'Populate Test Pack');
        LearningPackService::attachObjective($pack['uuid'], $objective['uuid']);

        $unit = SubjectLearningPackEngineService::createUnit($pack['uuid'], [
            'code' => 'POP-U1', 'title' => 'Unit Populasi', 'sequence_order' => 1,
        ]);
        SubjectLearningPackEngineService::mapUnitObjective($unit['uuid'], $objective['uuid'], ['role' => 'PRIMARY']);

        $activity = SubjectLearningPackEngineService::createActivity($unit['uuid'], [
            'code' => 'POP-A1', 'title' => 'Aktivitas Populasi',
            'delivery_mode' => 'PLUGGED', 'grouping_mode' => 'SMALL_GROUP', 'estimated_minutes' => 40,
        ]);
        SubjectLearningPackEngineService::addExperience($activity['uuid'], 'UNDERSTAND');
        SubjectLearningPackEngineService::addExperience($activity['uuid'], 'APPLY');

        $resource = SubjectLearningPackEngineService::createResource($pack['uuid'], [
            'resource_type' => 'DEVICE', 'title' => 'Laptop', 'device_count' => 5,
        ]);
        SubjectLearningPackEngineService::attachActivityResource($activity['uuid'], $resource['uuid']);

        SubjectLearningPackEngineService::addAssessmentReference([
            'learning_unit_uuid' => $unit['uuid'],
            'assessment_purpose' => 'FORMATIVE',
            'recommended_method' => 'Observasi langsung',
            'criteria_reference' => 'Kemampuan dekomposisi',
        ]);

        // Create lesson plan linked to this pack
        $plan = LessonPlanService::create([
            'academic_period_id' => $this->periodId,
            'unit_id' => $this->unitId,
            'subject_id' => $this->subjectId,
            'grade_level_id' => $this->gradeId,
            'teacher_id' => 1,
            'date' => '2026-09-01',
            'session_number' => 1,
            'learning_pack_id' => (int) $pack['id'],
        ]);

        // Populate from pack
        $populated = LessonPlanService::populateFromPack($plan['uuid']);
        $this->assertSame($plan['uuid'], $populated['uuid']);

        // Verify stages were created from experience types (UNDERSTAND→MEMAHAMI, APPLY→MENGAPLIKASI)
        $stages = $this->db->table('lesson_plan_stages')
            ->where('lesson_plan_id', (int) $plan['id'])
            ->orderBy('sequence_order')
            ->get()->getResultArray();
        $this->assertCount(2, $stages);
        $this->assertSame('MEMAHAMI', $stages[0]['stage_type']);
        $this->assertSame('MENGAPLIKASI', $stages[1]['stage_type']);

        // Verify objectives were created
        $objectives = $this->db->table('lesson_plan_objectives')
            ->where('lesson_plan_id', (int) $plan['id'])
            ->get()->getResultArray();
        $this->assertCount(1, $objectives);
        $this->assertSame((int) $objective['id'], (int) $objectives[0]['learning_objective_id']);
        $this->assertSame('PRIMARY', $objectives[0]['role']);

        // Verify activities were created with stage linkage
        $activities = $this->db->table('lesson_plan_activities')
            ->where('lesson_plan_id', (int) $plan['id'])
            ->get()->getResultArray();
        $this->assertCount(1, $activities);
        $this->assertSame('Aktivitas Populasi', $activities[0]['custom_title']);
        $this->assertSame((int) $activity['id'], (int) $activities[0]['learning_activity_id']);
        $this->assertNotNull($activities[0]['lesson_plan_stage_id']);
        $this->assertSame('PLUGGED', $activities[0]['delivery_mode']);
        $this->assertSame('SMALL_GROUP', $activities[0]['grouping_mode']);

        // Verify activity resources were linked
        $resources = $this->db->table('lesson_plan_activity_resources')
            ->where('lesson_plan_activity_id', (int) $activities[0]['id'])
            ->get()->getResultArray();
        $this->assertCount(1, $resources);
        $this->assertSame((int) $resource['id'], (int) $resources[0]['learning_resource_id']);
        $this->assertSame('Laptop', $resources[0]['custom_description']);

        // Verify assessments were created
        $assessments = $this->db->table('lesson_plan_assessments')
            ->where('lesson_plan_id', (int) $plan['id'])
            ->get()->getResultArray();
        $this->assertCount(1, $assessments);
        $this->assertSame('FORMATIVE', $assessments[0]['assessment_purpose']);
        $this->assertSame('Observasi langsung', $assessments[0]['recommended_method']);
        $this->assertSame('Kemampuan dekomposisi', $assessments[0]['criteria_reference']);

        // Verify summary reflects populated data
        $summary = LessonPlanService::summary($plan['uuid']);
        $this->assertSame(1, $summary['objectives_count']);
        $this->assertSame(2, $summary['stages_count']);
        $this->assertSame(1, $summary['activities_count']);
        $this->assertSame(1, $summary['assessments_count']);
        $this->assertSame(1, $summary['activity_resources_count']);
    }

    public function testPopulateFromPackWithSpecificUnit(): void
    {
        $objective = $this->createTestObjective();
        $pack = $this->createPack('POP-2', 'Multi Unit Pack');
        LearningPackService::attachObjective($pack['uuid'], $objective['uuid']);

        $unit1 = SubjectLearningPackEngineService::createUnit($pack['uuid'], [
            'code' => 'POP-U1', 'title' => 'Unit 1', 'sequence_order' => 1,
        ]);
        SubjectLearningPackEngineService::mapUnitObjective($unit1['uuid'], $objective['uuid']);
        $act1 = SubjectLearningPackEngineService::createActivity($unit1['uuid'], [
            'code' => 'POP-A1', 'title' => 'Aktivitas Unit 1',
            'delivery_mode' => 'PRACTICE', 'estimated_minutes' => 30,
        ]);
        SubjectLearningPackEngineService::addExperience($act1['uuid'], 'REFLECT');

        $unit2 = SubjectLearningPackEngineService::createUnit($pack['uuid'], [
            'code' => 'POP-U2', 'title' => 'Unit 2', 'sequence_order' => 2,
        ]);
        $act2 = SubjectLearningPackEngineService::createActivity($unit2['uuid'], [
            'code' => 'POP-A2', 'title' => 'Aktivitas Unit 2',
            'delivery_mode' => 'DISCUSSION', 'estimated_minutes' => 20,
        ]);
        SubjectLearningPackEngineService::addExperience($act2['uuid'], 'UNDERSTAND');

        $plan = LessonPlanService::create([
            'academic_period_id' => $this->periodId,
            'unit_id' => $this->unitId,
            'subject_id' => $this->subjectId,
            'grade_level_id' => $this->gradeId,
            'teacher_id' => 1,
            'date' => '2026-09-01',
            'learning_pack_id' => (int) $pack['id'],
        ]);

        // Populate only from unit 1
        LessonPlanService::populateFromPack($plan['uuid'], (int) $unit1['id']);

        // Only unit1's objective and activity should appear
        $objectives = $this->db->table('lesson_plan_objectives')
            ->where('lesson_plan_id', (int) $plan['id'])
            ->get()->getResultArray();
        $this->assertCount(1, $objectives);
        $this->assertSame((int) $objective['id'], (int) $objectives[0]['learning_objective_id']);

        $activities = $this->db->table('lesson_plan_activities')
            ->where('lesson_plan_id', (int) $plan['id'])
            ->get()->getResultArray();
        $this->assertCount(1, $activities);
        $this->assertSame('Aktivitas Unit 1', $activities[0]['custom_title']);
        $this->assertSame((int) $act1['id'], (int) $activities[0]['learning_activity_id']);

        // Only REFLECT→MEREFLEKSI stage should exist
        $stages = $this->db->table('lesson_plan_stages')
            ->where('lesson_plan_id', (int) $plan['id'])
            ->get()->getResultArray();
        $this->assertCount(1, $stages);
        $this->assertSame('MEREFLEKSI', $stages[0]['stage_type']);
    }

    public function testPopulateFromPackWithoutPackThrows(): void
    {
        $plan = $this->createMinimalPlan();

        $this->expectException(InvalidArgumentException::class);
        LessonPlanService::populateFromPack($plan['uuid']);
    }

    public function testPopulateFromPackOnCompletedPlanThrows(): void
    {
        $pack = $this->createPack('POP-4', 'Immutable Pack');
        $plan = LessonPlanService::create([
            'academic_period_id' => $this->periodId,
            'unit_id' => $this->unitId,
            'subject_id' => $this->subjectId,
            'grade_level_id' => $this->gradeId,
            'teacher_id' => 1,
            'date' => '2026-09-01',
            'learning_pack_id' => (int) $pack['id'],
        ]);

        LessonPlanService::transition($plan['uuid'], 'READY', 1);
        LessonPlanService::transition($plan['uuid'], 'IN_PROGRESS', 2);
        LessonPlanService::transition($plan['uuid'], 'COMPLETED', 3);

        $this->expectException(RuntimeException::class);
        LessonPlanService::populateFromPack($plan['uuid']);
    }

    private function createPack(string $code, string $name): array
    {
        return SubjectLearningPackEngineService::createPack([
            'curriculum_version_id' => $this->versionId,
            'unit_id' => $this->unitId,
            'subject_id' => $this->subjectId,
            'grade_level_id' => $this->gradeId,
            'phase' => 'E',
            'code' => $code,
            'name' => $name,
            'source_type' => 'CUSTOM',
        ]);
    }

    private function createMinimalPlan(): array
    {
        return LessonPlanService::create([
            'academic_period_id' => $this->periodId,
            'unit_id' => $this->unitId,
            'subject_id' => $this->subjectId,
            'grade_level_id' => $this->gradeId,
            'teacher_id' => 1,
            'date' => '2026-09-01',
            'session_number' => 1,
        ]);
    }
}
