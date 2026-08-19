<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
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

        foreach (['', '/overview', '/design', '/stages', '/activities', '/assessments'] as $path) {
            $this->withSession(session()->get())->get('lesson-plans/' . $plan['uuid'] . $path)->assertOK();
        }
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
