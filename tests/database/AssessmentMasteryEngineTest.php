<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\AssessmentService;
use App\Services\MasteryService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use RuntimeException;
use Tests\Support\EducationFoundationFixtureTrait;
use Tests\Support\IsolatedDatabaseTestTrait;

/**
 * Phase 6 — Assessment & Mastery Engine Test Suite.
 *
 * Verifies schema, permissions, assessment lifecycle, gradebook entry,
 * evidence-backed mastery derivation, and intervention recommendation.
 *
 * @internal
 */
final class AssessmentMasteryEngineTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;
    use EducationFoundationFixtureTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    private AssessmentService $assessmentService;
    private MasteryService $masteryService;
    protected int $teacherId;
    protected int $classroomId;
    protected array $studentIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEducationFoundationFixture();
        $this->assessmentService = new AssessmentService();
        $this->masteryService    = new MasteryService();

        $teacher = $this->db->table('teachers')->get()->getRowArray();
        if (!$teacher) {
            $this->db->table('teachers')->insert([
                'uuid'             => '50000000-0000-4000-8000-000000000001',
                'full_name'        => 'Guru Asesmen Test',
                'primary_unit_id'  => $this->unitId,
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
            $this->teacherId = (int) $this->db->insertID();
        } else {
            $this->teacherId = (int) $teacher['id'];
        }

        $classroom = $this->db->table('classrooms')->where('unit_id', $this->unitId)->get()->getRowArray();
        if (!$classroom) {
            $this->db->table('classrooms')->insert([
                'uuid'              => '50000000-0000-4000-8000-000000000002',
                'academic_period_id'=> $this->periodId,
                'unit_id'           => $this->unitId,
                'name'              => 'XI-A',
                'grade_level_id'    => $this->gradeId,
                'is_active'         => 1,
                'created_at'        => date('Y-m-d H:i:s'),
            ]);
            $this->classroomId = (int) $this->db->insertID();
        } else {
            $this->classroomId = (int) $classroom['id'];
        }

        $period = $this->db->table('academic_periods')->where('id', $this->periodId)->get()->getRowArray();
        $yearId = $period ? (int) $period['academic_year_id'] : 1;
        $this->studentIds = [];
        foreach (['Siswa Alpha', 'Siswa Beta'] as $i => $fullName) {
            $existing = $this->db->table('elective_students')
                ->where('full_name', $fullName)->get()->getRowArray();
            if ($existing) {
                $this->studentIds[] = (int) $existing['id'];
                continue;
            }
            $this->db->table('elective_students')->insert([
                'uuid'            => sprintf('50000000-0000-4000-8000-00000000000%d', 3 + $i),
                'academic_year_id'=> $yearId,
                'unit_id'         => $this->unitId,
                'classroom_id'    => $this->classroomId,
                'student_number'  => '2026' . ($i + 1),
                'full_name'       => $fullName,
                'current_grade'   => 'XI',
                'is_active'       => 1,
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
            $this->studentIds[] = (int) $this->db->insertID();
        }
    }

    private function createAssessmentFixture(array $criteriaStatuses): array
    {
        $tp1 = $this->createTestObjective($this->createTestOutcome('CP-ENGINE-A'), 'TP-ENGINE-A');
        $tp2 = $this->createTestObjective($this->createTestOutcome('CP-ENGINE-B'), 'TP-ENGINE-B');

        $assessmentId = $this->assessmentService->create([
            'unit_id'            => $this->unitId,
            'academic_period_id' => $this->periodId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'teacher_id'         => $this->teacherId,
            'title'              => 'Sumatif Engine Test',
            'assessment_type'    => 'SUMMATIVE',
            'assessment_form'    => 'RUBRIK',
            'assessment_date'    => date('Y-m-d'),
            'max_score'          => 100,
            'objective_ids'      => [$tp1['id'], $tp2['id']],
            'criteria'           => [
                ['criterion' => 'Mampu menguraikan masalah', 'learning_objective_id' => $tp1['id'], 'weight' => 1],
                ['criterion' => 'Mampu menyusun solusi',      'learning_objective_id' => $tp2['id'], 'weight' => 1],
            ],
            'items'              => [
                ['prompt' => 'Uraikan masalah yang diberikan', 'item_type' => 'ESSAY', 'max_score' => 50],
            ],
        ], 1);

        $criteria = $this->db->table('assessment_criteria')
            ->where('assessment_id', $assessmentId)
            ->orderBy('sequence_order', 'ASC')
            ->get()->getResultArray();

        $this->assessmentService->transition($assessmentId, 'PUBLISHED', 1);

        $rows = [];
        foreach ($this->studentIds as $i => $studentId) {
            $statuses = $criteriaStatuses[$i] ?? [];
            $criteriaInput = [];
            foreach ($criteria as $j => $criterion) {
                $criteriaInput[(int) $criterion['id']] = ['status' => $statuses[$j] ?? 'DEVELOPING'];
            }
            $rows[] = [
                'student_id'  => $studentId,
                'score'       => $statuses === ['ACHIEVED', 'ADVANCED'] ? 90 : 45,
                'is_complete' => 1,
                'criteria'    => $criteriaInput,
            ];
        }

        $this->assessmentService->saveGradebook($assessmentId, $rows, 1);

        return ['id' => $assessmentId, 'criteria' => $criteria];
    }

    public function testPhase6SchemaIsAvailable(): void
    {
        foreach ([
            'assessments', 'assessment_objectives', 'assessment_criteria',
            'assessment_items', 'assessment_attempts', 'criterion_results',
            'assessment_evidence', 'assessment_feedback', 'mastery_records',
            'interventions', 'reporting_policies',
        ] as $table) {
            $this->assertTrue($this->db->tableExists($table), "Table {$table} should exist.");
        }
    }

    public function testPhase6PermissionsAndFeatureFlagAreSeeded(): void
    {
        foreach (['assessment.view', 'assessment.manage', 'assessment.mastery'] as $code) {
            $this->assertGreaterThan(
                0,
                $this->db->table('permissions')->where('code', $code)->countAllResults(),
                "Permission {$code} should be seeded."
            );
        }

        $flag = $this->db->table('feature_flags')->where('code', 'ialos_phase6_assessment')->get()->getRowArray();
        $this->assertNotNull($flag, 'Phase 6 feature flag should be seeded.');
        $this->assertSame(1, (int) $flag['enabled']);
    }

    public function testDeriveStatusFromCriteria(): void
    {
        $this->assertSame('ADVANCED', $this->masteryService->deriveStatusFromCriteria(['ADVANCED', 'ADVANCED']));
        $this->assertSame('ACHIEVED', $this->masteryService->deriveStatusFromCriteria(['ACHIEVED', 'ACHIEVED']));
        $this->assertSame('ACHIEVED', $this->masteryService->deriveStatusFromCriteria(['ACHIEVED', 'ADVANCED']));
        $this->assertSame('DEVELOPING', $this->masteryService->deriveStatusFromCriteria(['ACHIEVED', 'DEVELOPING']));
        $this->assertSame('NEEDS_SUPPORT', $this->masteryService->deriveStatusFromCriteria(['ACHIEVED', 'NEEDS_SUPPORT']));
        $this->assertSame('DEVELOPING', $this->masteryService->deriveStatusFromCriteria([]));
    }

    public function testCreateAssessmentEndToEnd(): void
    {
        $tp = $this->createTestObjective($this->createTestOutcome('CP-ENGINE-C'), 'TP-ENGINE-C');
        $assessmentId = $this->assessmentService->create([
            'unit_id'            => $this->unitId,
            'academic_period_id' => $this->periodId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'teacher_id'         => $this->teacherId,
            'title'              => 'Diagnostik Awal',
            'assessment_type'    => 'DIAGNOSTIC',
            'assessment_form'    => 'OBSERVASI',
            'assessment_date'    => date('Y-m-d'),
            'max_score'          => '',
            'objective_ids'      => [$tp['id']],
            'criteria'           => [
                ['criterion' => 'Keterlibatan aktivitas', 'learning_objective_id' => $tp['id'], 'weight' => 1],
            ],
            'items'              => [],
        ], 1);

        $assessment = $this->db->table('assessments')->where('id', $assessmentId)->get()->getRowArray();
        $this->assertSame('DIAGNOSTIC', $assessment['assessment_type']);
        $this->assertSame('OBSERVASI', $assessment['assessment_form']);
        $this->assertSame('DRAFT', $assessment['status']);
        $this->assertSame(1, (int) $this->db->table('assessment_objectives')->where('assessment_id', $assessmentId)->countAllResults());
        $this->assertSame(1, (int) $this->db->table('assessment_criteria')->where('assessment_id', $assessmentId)->countAllResults());
    }

    public function testTransitionLifecycleAndInvalidTransitions(): void
    {
        $assessmentId = $this->createAssessmentFixture([[], []])['id'];

        // PUBLISHED → CLOSED is valid
        $this->assessmentService->transition($assessmentId, 'CLOSED', 1);
        $row = $this->db->table('assessments')->where('id', $assessmentId)->get()->getRowArray();
        $this->assertSame('CLOSED', $row['status']);
        $this->assertNotNull($row['closed_at']);

        // CLOSED → anything must fail
        $this->expectException(RuntimeException::class);
        $this->assessmentService->transition($assessmentId, 'PUBLISHED', 1);
    }

    public function testCannotDeletePublishedAssessment(): void
    {
        $assessmentId = $this->createAssessmentFixture([[], []])['id'];
        $this->expectException(RuntimeException::class);
        $this->assessmentService->delete($assessmentId, 1);
    }

    public function testSaveGradebookDerivesMasteryAndInterventions(): void
    {
        $data = $this->createAssessmentFixture([
            ['ACHIEVED', 'ADVANCED'],   // student 1 → TP1 ACHIEVED, TP2 ADVANCED
            ['NEEDS_SUPPORT', 'DEVELOPING'], // student 2 → TP1 NEEDS_SUPPORT, TP2 DEVELOPING
        ]);

        $tps = $this->db->table('assessment_objectives')->where('assessment_id', $data['id'])->get()->getResultArray();
        $this->assertCount(2, $tps);

        $mastery = $this->db->table('mastery_records')->whereIn('student_id', $this->studentIds)->get()->getResultArray();
        $this->assertCount(4, $mastery, 'Each student should have one mastery record per covered TP.');

        $alpha = $this->db->table('mastery_records')
            ->where('student_id', $this->studentIds[0])
            ->where('learning_objective_id', (int) $tps[0]['learning_objective_id'])
            ->get()->getRowArray();
        $this->assertSame('ACHIEVED', $alpha['result']);

        $alphaTp2 = $this->db->table('mastery_records')
            ->where('student_id', $this->studentIds[0])
            ->where('learning_objective_id', (int) $tps[1]['learning_objective_id'])
            ->get()->getRowArray();
        $this->assertSame('ADVANCED', $alphaTp2['result']);

        // Interventions: REMEDIAL for NEEDS_SUPPORT, REINFORCEMENT for DEVELOPING
        $interventions = $this->db->table('interventions')
            ->whereIn('student_id', $this->studentIds)
            ->get()->getResultArray();
        $this->assertCount(2, $interventions);
        $types = array_column($interventions, 'intervention_type');
        sort($types);
        $this->assertSame(['REINFORCEMENT', 'REMEDIAL'], $types);
    }

    public function testMasteryBoardReturnsMatrix(): void
    {
        $this->createAssessmentFixture([
            ['ACHIEVED', 'ACHIEVED'],
            ['NEEDS_SUPPORT', 'DEVELOPING'],
        ]);

        $board = $this->masteryService->board($this->unitId, $this->periodId, $this->classroomId, $this->subjectId);
        $this->assertCount(2, $board['objectives']);
        $this->assertCount(2, $board['matrix']);

        $first = $board['matrix'][0]['objectives'][0];
        $this->assertNotEmpty($first['mastery']);
        $this->assertSame('ACHIEVED', $first['mastery']['result']);
    }

    public function testSetMasteryManualAndBatchRecommend(): void
    {
        $tp = $this->createTestObjective($this->createTestOutcome('CP-ENGINE-D'), 'TP-ENGINE-D');
        $this->masteryService->setMastery($this->studentIds[0], (int) $tp['id'], 'NEEDS_SUPPORT', 1, ['notes' => 'Perlu remedial']);

        $result = $this->masteryService->recommendInterventions($this->unitId, $this->periodId, $this->classroomId);
        $this->assertSame(1, $result['recommended']);

        $intervention = $this->db->table('interventions')
            ->where('student_id', $this->studentIds[0])
            ->where('learning_objective_id', (int) $tp['id'])
            ->get()->getRowArray();
        $this->assertNotNull($intervention);
        $this->assertSame('REMEDIAL', $intervention['intervention_type']);
        $this->assertSame('RECOMMENDED', $intervention['status']);

        // Approve → COMPLETED flow
        $this->masteryService->updateIntervention((int) $intervention['id'], 'APPROVED', 1);
        $this->masteryService->updateIntervention((int) $intervention['id'], 'COMPLETED', 1, ['outcome' => 'Tuntas']);

        $completed = $this->db->table('interventions')->where('id', $intervention['id'])->get()->getRowArray();
        $this->assertSame('COMPLETED', $completed['status']);
        $this->assertSame('Tuntas', $completed['outcome']);
        $this->assertNotNull($completed['completed_at']);
    }

    public function testReportingPolicyVersioning(): void
    {
        $db = $this->db;
        $db->table('reporting_policies')->insert([
            'uuid'               => '50000000-0000-4000-8000-0000000000a1',
            'unit_id'            => $this->unitId,
            'academic_period_id' => $this->periodId,
            'subject_id'         => $this->subjectId,
            'policy_name'        => 'Kebijakan v1',
            'calculation_method' => 'AVERAGE',
            'is_active'          => 1,
            'version'            => 1,
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        $db->table('reporting_policies')->insert([
            'uuid'               => '50000000-0000-4000-8000-0000000000a2',
            'unit_id'            => $this->unitId,
            'academic_period_id' => $this->periodId,
            'subject_id'         => $this->subjectId,
            'policy_name'        => 'Kebijakan v2',
            'calculation_method' => 'WEIGHTED',
            'is_active'          => 1,
            'version'            => 2,
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        $this->assertSame(
            2,
            (int) $db->table('reporting_policies')
                ->where('unit_id', $this->unitId)
                ->where('academic_period_id', $this->periodId)
                ->where('subject_id', $this->subjectId)
                ->countAllResults()
        );
    }

    public function testDiagnosticDoesNotWriteMasteryOrInterventions(): void
    {
        $tp = $this->createTestObjective($this->createTestOutcome('CP-ENGINE-E'), 'TP-ENGINE-E');

        $assessmentId = $this->assessmentService->create([
            'unit_id'            => $this->unitId,
            'academic_period_id' => $this->periodId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'teacher_id'         => $this->teacherId,
            'title'              => 'Diagnostik Refinement',
            'assessment_type'    => 'DIAGNOSTIC',
            'assessment_form'    => 'ANGKA',
            'assessment_date'    => date('Y-m-d'),
            'max_score'          => 100,
            'objective_ids'      => [$tp['id']],
            'criteria'           => [
                ['criterion' => 'Kesiapan awal', 'learning_objective_id' => $tp['id'], 'weight' => 1],
            ],
            'items'              => [],
        ], 1);

        $this->assessmentService->transition($assessmentId, 'PUBLISHED', 1);

        $criterion = $this->db->table('assessment_criteria')->where('assessment_id', $assessmentId)->get()->getRowArray();
        $rows = [];
        foreach ($this->studentIds as $studentId) {
            $rows[] = [
                'student_id'  => $studentId,
                'score'       => 30,
                'is_complete' => 1,
                'criteria'    => [(int) $criterion['id'] => ['status' => 'NEEDS_SUPPORT']],
            ];
        }
        $this->assessmentService->saveGradebook($assessmentId, $rows, 1);

        $this->assertSame(0, (int) $this->db->table('mastery_records')->whereIn('student_id', $this->studentIds)->countAllResults());
        $this->assertSame(0, (int) $this->db->table('interventions')->whereIn('student_id', $this->studentIds)->countAllResults());

        $results = $this->db->table('criterion_results cr')
            ->join('assessment_attempts aa', 'aa.id = cr.attempt_id', 'left')
            ->whereIn('aa.student_id', $this->studentIds)
            ->countAllResults();
        $this->assertSame(count($this->studentIds), $results, 'Diagnostic still records per-criterion results.');
    }

    public function testInterventionTargetsFailingCriterion(): void
    {
        $tp = $this->createTestObjective($this->createTestOutcome('CP-ENGINE-F'), 'TP-ENGINE-F');

        $assessmentId = $this->assessmentService->create([
            'unit_id'            => $this->unitId,
            'academic_period_id' => $this->periodId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'teacher_id'         => $this->teacherId,
            'title'              => 'Sumatif Rubrik',
            'assessment_type'    => 'SUMMATIVE',
            'assessment_form'    => 'RUBRIK',
            'assessment_date'    => date('Y-m-d'),
            'max_score'          => 100,
            'objective_ids'      => [$tp['id']],
            'criteria'           => [
                [
                    'criterion'           => 'Mampu menguraikan masalah',
                    'learning_objective_id' => $tp['id'],
                    'weight'              => 1,
                    'rubric_levels_json'  => json_encode([
                        ['level_index' => 0, 'label' => 'Cukup', 'score' => 2, 'description' => 'Mulai memahami'],
                        ['level_index' => 1, 'label' => 'Mahir', 'score' => 3, 'description' => 'Menguraikan dengan lengkap'],
                    ]),
                ],
            ],
            'items'              => [],
        ], 1);

        $this->assessmentService->transition($assessmentId, 'PUBLISHED', 1);

        $criterion = $this->db->table('assessment_criteria')->where('assessment_id', $assessmentId)->get()->getRowArray();
        $this->assertNotNull($criterion['rubric_levels_json'], 'Rubric levels should be persisted.');

        $this->assessmentService->saveGradebook($assessmentId, [
            [
                'student_id'  => $this->studentIds[0],
                'score'       => 40,
                'is_complete' => 1,
                'criteria'    => [(int) $criterion['id'] => ['status' => 'NEEDS_SUPPORT']],
            ],
        ], 1);

        $intervention = $this->db->table('interventions')
            ->where('student_id', $this->studentIds[0])
            ->where('learning_objective_id', (int) $tp['id'])
            ->get()->getRowArray();
        $this->assertNotNull($intervention);
        $this->assertSame((int) $criterion['id'], (int) $intervention['criterion_id'], 'Intervention should target the failing criterion.');
        $this->assertStringContainsString('Mampu menguraikan masalah', $intervention['planned_activity']);

        $listed = $this->masteryService->listInterventions(['unit_id' => $this->unitId]);
        $this->assertNotEmpty($listed);
        $this->assertSame('Mampu menguraikan masalah', $listed[0]['criterion_text']);
    }

    public function testEvidenceStoresProfileDimensionAlignment(): void
    {
        $data = $this->createAssessmentFixture([['ACHIEVED', 'ADVANCED'], []]);
        $dimension = $this->db->table('graduate_profile_dimensions')->get()->getRowArray();
        $this->assertNotNull($dimension, 'Graduate profile dimension should be seeded.');

        $attempt = $this->db->table('assessment_attempts')
            ->where('assessment_id', $data['id'])
            ->where('student_id', $this->studentIds[0])
            ->get()->getRowArray();
        $this->assertNotNull($attempt);

        $this->assessmentService->addEvidence([
            'student_id'           => $this->studentIds[0],
            'attempt_id'           => (int) $attempt['id'],
            'learning_objective_id'=> '',
            'criterion_id'         => (int) $data['criteria'][0]['id'],
            'profile_dimension_id' => (int) $dimension['id'],
            'cocurricular_objective_id' => '',
            'evidence_type'        => 'FILE',
            'title'                => 'Portofolio karya',
            'content'              => 'Laporan proyek',
        ], 1);

        $evidence = $this->db->table('assessment_evidence')
            ->where('student_id', $this->studentIds[0])
            ->where('attempt_id', (int) $attempt['id'])
            ->get()->getRowArray();
        $this->assertNotNull($evidence);
        $this->assertSame((int) $dimension['id'], (int) $evidence['profile_dimension_id']);
    }
}