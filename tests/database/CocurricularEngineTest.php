<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\CocurricularService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use InvalidArgumentException;
use RuntimeException;
use Tests\Support\EducationFoundationFixtureTrait;
use Tests\Support\IsolatedDatabaseTestTrait;

/**
 * Phase 7 — Cocurricular & Character Engine Test Suite.
 *
 * Verifies schema, permissions, program lifecycle with interdisciplinary
 * junctions, session scheduling, formative monitoring, summative evidence,
 * per-student × dimension results, INPUT→PROCESS→OUTPUT→OUTCOME evaluation,
 * report aggregation, and the optional 7KAIH habits/check-ins.
 *
 * @internal
 */
final class CocurricularEngineTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;
    use EducationFoundationFixtureTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    private CocurricularService $service;
    protected int $teacherId;
    protected int $classroomId;
    protected array $studentIds = [];
    protected array $dimensionIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEducationFoundationFixture();
        $this->service = new CocurricularService();

        $teacher = $this->db->table('teachers')->where('primary_unit_id', $this->unitId)->get()->getRowArray();
        if (! $teacher) {
            $this->db->table('teachers')->insert([
                'uuid'            => '51000000-0000-4000-8000-000000000001',
                'full_name'       => 'Guru Kokurikuler Test',
                'normalized_name' => 'GURU KOKURIKULER TEST',
                'employment_status'=> 'ACTIVE',
                'primary_unit_id' => $this->unitId,
                'is_active'       => 1,
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
            $this->teacherId = (int) $this->db->insertID();
        } else {
            $this->teacherId = (int) $teacher['id'];
        }

        $classroom = $this->db->table('classrooms')->where('unit_id', $this->unitId)->get()->getRowArray();
        if (! $classroom) {
            $this->db->table('classrooms')->insert([
                'uuid'              => '51000000-0000-4000-8000-000000000002',
                'academic_period_id'=> $this->periodId,
                'unit_id'           => $this->unitId,
                'name'              => 'X-KOKUR',
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
        foreach (['Siswa Kokur Alpha', 'Siswa Kokur Beta'] as $i => $fullName) {
            $existing = $this->db->table('elective_students')->where('full_name', $fullName)->get()->getRowArray();
            if ($existing) {
                $this->studentIds[] = (int) $existing['id'];
                continue;
            }
            $this->db->table('elective_students')->insert([
                'uuid'            => sprintf('51000000-0000-4000-8000-00000000000%d', 3 + $i),
                'academic_year_id'=> $yearId,
                'unit_id'         => $this->unitId,
                'classroom_id'    => $this->classroomId,
                'student_number'  => 'PK' . ($i + 1),
                'full_name'       => $fullName,
                'current_grade'   => 'X',
                'is_active'       => 1,
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
            $this->studentIds[] = (int) $this->db->insertID();
        }

        $this->dimensionIds = array_map(
            'intval',
            array_column($this->db->table('graduate_profile_dimensions')->orderBy('sort_order', 'ASC')->get()->getResultArray(), 'id')
        );
    }

    private function createProgramFixture(): int
    {
        $tp = $this->createTestObjective($this->createTestOutcome('CP-KOKUR-A'), 'TP-KOKUR-A');

        return $this->service->createProgram($this->unitId, $this->periodId, [
            'code'            => 'P5-TEST',
            'title'           => 'Pameran Karya Projek Kewirausahaan',
            'program_type'    => 'COCURRICULAR',
            'theme'           => 'Kewirausahaan & Gotong Royong',
            'rationale'       => 'Menguatkan profil lulusan melalui pengalaman nyata.',
            'objective'       => 'Siswa mampu merancang dan memamerkan karya.',
            'annual_minutes'  => 1200,
            'delivery_model'  => 'PROJECT',
            'start_date'      => '2026-08-01',
            'end_date'        => '2026-12-15',
            'description'     => 'Program unggulan sekolah.',
            'dimension_ids'   => array_slice($this->dimensionIds, 0, 2),
            'subject_ids'     => [$this->subjectId],
            'objective_ids'   => [$tp['id']],
            'teacher_ids'     => [$this->teacherId],
            'classroom_ids'   => [$this->classroomId],
            'partners'        => [
                ['name' => 'Universitas X', 'partner_type' => 'UNIVERSITY', 'role' => 'Mentor', 'contact' => 'mentor@x.test'],
            ],
            'resources'       => [
                ['name' => 'Kantong Belanja', 'resource_type' => 'ALAT', 'quantity' => 40, 'notes' => 'bahan prakarya'],
            ],
        ], 1);
    }

    public function testPhase7SchemaIsAvailable(): void
    {
        foreach ([
            'cocurricular_programs', 'cocurricular_program_dimensions',
            'cocurricular_program_subjects', 'cocurricular_program_objectives',
            'cocurricular_program_teachers', 'cocurricular_program_classes',
            'cocurricular_program_partners', 'cocurricular_program_resources',
            'cocurricular_sessions', 'cocurricular_observations',
            'cocurricular_evidences', 'cocurricular_student_results',
            'cocurricular_evaluations', 'cocurricular_habits',
            'cocurricular_habit_checkins',
        ] as $table) {
            $this->assertTrue($this->db->tableExists($table), "Table {$table} should exist.");
        }
    }

    public function testPhase7PermissionsAndFeatureFlagsAreSeeded(): void
    {
        foreach (['cocurricular.view', 'cocurricular.manage'] as $code) {
            $this->assertGreaterThan(
                0,
                $this->db->table('permissions')->where('code', $code)->countAllResults(),
                "Permission {$code} should be seeded."
            );
        }

        $flag = $this->db->table('feature_flags')->where('code', 'ialos_phase7_cocurricular')->get()->getRowArray();
        $this->assertNotNull($flag, 'Phase 7 cocurricular flag should be seeded.');
        $this->assertSame(1, (int) $flag['enabled']);

        $kaih = $this->db->table('feature_flags')->where('code', 'ialos_7kahi')->get()->getRowArray();
        $this->assertNotNull($kaih, '7KAIH flag should be seeded.');
        $this->assertContains((int) $kaih['enabled'], [0, 1]);
    }

    public function testCreateProgramWithJunctionsAndPartners(): void
    {
        $programId = $this->createProgramFixture();

        $program = $this->db->table('cocurricular_programs')->where('id', $programId)->get()->getRowArray();
        $this->assertSame('P5-TEST', $program['code']);
        $this->assertSame('COCURRICULAR', $program['program_type']);
        $this->assertSame('PROJECT', $program['delivery_model']);
        $this->assertSame('DRAFT', $program['status']);

        $this->assertSame(2, (int) $this->db->table('cocurricular_program_dimensions')->where('program_id', $programId)->countAllResults());
        $this->assertSame(1, (int) $this->db->table('cocurricular_program_subjects')->where('program_id', $programId)->countAllResults());
        $this->assertSame(1, (int) $this->db->table('cocurricular_program_objectives')->where('program_id', $programId)->countAllResults());
        $this->assertSame(1, (int) $this->db->table('cocurricular_program_teachers')->where('program_id', $programId)->countAllResults());
        $this->assertSame(1, (int) $this->db->table('cocurricular_program_classes')->where('program_id', $programId)->countAllResults());
        $this->assertSame(1, (int) $this->db->table('cocurricular_program_partners')->where('program_id', $programId)->countAllResults());
        $this->assertSame(1, (int) $this->db->table('cocurricular_program_resources')->where('program_id', $programId)->countAllResults());
    }

    public function testProgramDetailAndJunctionReaders(): void
    {
        $programId = $this->createProgramFixture();
        $detail = $this->service->programDetail($programId);

        $this->assertSame('Pameran Karya Projek Kewirausahaan', $detail['program']['title']);
        $this->assertCount(2, $detail['dimensions']);
        $this->assertCount(1, $detail['subjects']);
        $this->assertCount(1, $detail['objectives']);
        $this->assertCount(1, $detail['teachers']);
        $this->assertCount(1, $detail['classes']);
        $this->assertCount(1, $detail['partners']);
        $this->assertCount(1, $detail['resources']);
        $this->assertSame('Universitas X', $detail['partners'][0]['name']);
    }

    public function testUpdateProgramResyncsJunctions(): void
    {
        $programId = $this->createProgramFixture();

        $this->service->updateProgram($programId, $this->unitId, [
            'title'           => 'Judul Baru',
            'program_type'    => 'EXTRACURRICULAR',
            'delivery_model'  => 'BLOCK',
            'dimension_ids'   => array_slice($this->dimensionIds, 0, 1),
            'subject_ids'     => [],
            'objective_ids'   => [],
            'teacher_ids'     => [$this->teacherId],
            'classroom_ids'   => [$this->classroomId],
            'partners'        => [],
            'resources'       => [],
        ], 1);

        $program = $this->db->table('cocurricular_programs')->where('id', $programId)->get()->getRowArray();
        $this->assertSame('Judul Baru', $program['title']);
        $this->assertSame('EXTRACURRICULAR', $program['program_type']);
        $this->assertSame(1, (int) $this->db->table('cocurricular_program_dimensions')->where('program_id', $programId)->countAllResults());
        $this->assertSame(0, (int) $this->db->table('cocurricular_program_subjects')->where('program_id', $programId)->countAllResults());
        $this->assertSame(0, (int) $this->db->table('cocurricular_program_partners')->where('program_id', $programId)->countAllResults());
    }

    public function testTransitionLifecycleAndInvalidTransitions(): void
    {
        $programId = $this->createProgramFixture();

        $this->service->transition($programId, $this->unitId, 'ACTIVE', 1);
        $this->assertSame('ACTIVE', $this->db->table('cocurricular_programs')->where('id', $programId)->get()->getRowArray()['status']);

        $this->service->transition($programId, $this->unitId, 'COMPLETED', 1);
        $this->assertSame('COMPLETED', $this->db->table('cocurricular_programs')->where('id', $programId)->get()->getRowArray()['status']);

        $this->expectException(InvalidArgumentException::class);
        $this->service->transition($programId, $this->unitId, 'DRAFT', 1);
    }

    public function testCannotUpdateOrDeleteNonDraftProgram(): void
    {
        $programId = $this->createProgramFixture();
        $this->service->transition($programId, $this->unitId, 'ACTIVE', 1);

        try {
            $this->service->updateProgram($programId, $this->unitId, ['title' => 'X'], 1);
            $this->fail('Updating an ACTIVE program must throw.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('DRAFT', $e->getMessage());
        }

        $this->expectException(RuntimeException::class);
        $this->service->deleteProgram($programId, $this->unitId, 1);
    }

    public function testSessionLifecycle(): void
    {
        $programId = $this->createProgramFixture();

        $sessionId = $this->service->addSession($programId, $this->unitId, [
            'classroom_id' => $this->classroomId,
            'teacher_id'   => $this->teacherId,
            'title'        => 'Kick-off Projek',
            'session_date' => '2026-09-01',
            'start_time'   => '07:30',
            'end_time'     => '09:30',
            'mode'         => 'BLOCK',
            'notes'        => 'Pembagian kelompok.',
        ], 1);

        $session = $this->db->table('cocurricular_sessions')->where('id', $sessionId)->get()->getRowArray();
        $this->assertSame('PLAN', $session['status']);
        $this->assertSame('BLOCK', $session['mode']);

        $this->service->executeSession($sessionId, $programId, $this->unitId, 1);
        $this->assertSame('EXECUTED', $this->db->table('cocurricular_sessions')->where('id', $sessionId)->get()->getRowArray()['status']);
        $this->assertNotNull($this->db->table('cocurricular_sessions')->where('id', $sessionId)->get()->getRowArray()['executed_at']);

        $this->expectException(RuntimeException::class);
        $this->service->executeSession($sessionId, $programId, $this->unitId, 1);
    }

    public function testCannotUpdateExecutedSession(): void
    {
        $programId = $this->createProgramFixture();
        $sessionId = $this->service->addSession($programId, $this->unitId, [
            'title'        => 'Sesi A',
            'session_date' => '2026-09-02',
        ], 1);
        $this->service->executeSession($sessionId, $programId, $this->unitId, 1);

        $this->expectException(RuntimeException::class);
        $this->service->updateSession($sessionId, $programId, $this->unitId, ['title' => 'Berubah'], 1);
    }

    public function testObservationLifecycle(): void
    {
        $programId = $this->createProgramFixture();

        $observationId = $this->service->addObservation($programId, $this->unitId, [
            'student_id'       => $this->studentIds[0],
            'teacher_id'       => $this->teacherId,
            'observation_type' => 'OBSERVATION',
            'dimension_id'     => $this->dimensionIds[0],
            'notes'            => 'Terlibat aktif dalam diskusi.',
            'rating'           => 4,
            'observed_on'      => '2026-09-10',
        ], 1);

        $row = $this->db->table('cocurricular_observations')->where('id', $observationId)->get()->getRowArray();
        $this->assertSame('OBSERVATION', $row['observation_type']);
        $this->assertSame(4, (int) $row['rating']);

        $this->service->updateObservation($observationId, $programId, $this->unitId, ['notes' => 'Catatan diperbarui'], 1);
        $this->assertSame('Catatan diperbarui', $this->db->table('cocurricular_observations')->where('id', $observationId)->get()->getRowArray()['notes']);

        $this->assertCount(1, $this->service->observations($programId));
        $this->service->deleteObservation($observationId, $programId, $this->unitId);
        $this->assertCount(0, $this->service->observations($programId));
    }

    public function testInvalidObservationRatingRejected(): void
    {
        $programId = $this->createProgramFixture();
        $this->expectException(InvalidArgumentException::class);
        $this->service->addObservation($programId, $this->unitId, [
            'student_id' => $this->studentIds[0],
            'notes'      => 'Catatan',
            'rating'     => 9,
        ], 1);
    }

    public function testEvidenceLifecycle(): void
    {
        $programId = $this->createProgramFixture();

        $evidenceId = $this->service->addEvidence($programId, $this->unitId, [
            'student_id'    => $this->studentIds[0],
            'dimension_id'  => $this->dimensionIds[0],
            'title'         => 'Foto Pameran',
            'evidence_type' => 'PRODUCT',
            'description'   => 'Hasil karya siswa.',
        ], 1, ['path' => 'uploads/cocurricular/foto.jpg', 'meta' => ['name' => 'foto.jpg', 'size' => 100, 'type' => 'image/jpeg']]);

        $row = $this->db->table('cocurricular_evidences')->where('id', $evidenceId)->get()->getRowArray();
        $this->assertSame('PRODUCT', $row['evidence_type']);
        $this->assertSame('uploads/cocurricular/foto.jpg', $row['file_path']);
        $this->assertNotNull($row['meta_json']);

        $fetched = $this->service->evidence($evidenceId);
        $this->assertSame($evidenceId, (int) $fetched['id']);

        $this->assertCount(1, $this->service->evidences($programId));
        $this->service->deleteEvidence($evidenceId, $programId, $this->unitId);
        $this->assertCount(0, $this->service->evidences($programId));
    }

    public function testSaveResultsMatrixUpsertsPerStudentDimension(): void
    {
        $programId = $this->createProgramFixture();
        $dims = array_slice($this->dimensionIds, 0, 2);

        $saved = $this->service->saveResults($programId, $this->unitId, [
            'results' => [
                $this->studentIds[0] => [
                    $dims[0] => ['level' => 'PROFICIENT', 'note' => 'Bagus'],
                    $dims[1] => 'DEVELOPING',
                ],
                $this->studentIds[1] => [
                    $dims[0] => 'EMERGING',
                ],
            ],
        ], 1);

        $this->assertSame(3, $saved);
        $this->assertSame(3, (int) $this->db->table('cocurricular_student_results')->where('program_id', $programId)->countAllResults());

        $savedAgain = $this->service->saveResults($programId, $this->unitId, [
            'results' => [
                $this->studentIds[0] => [
                    $dims[0] => ['level' => 'EXEMPLARY', 'note' => 'Ditingkatkan'],
                ],
            ],
        ], 1);
        $this->assertSame(1, $savedAgain);
        $this->assertSame(3, (int) $this->db->table('cocurricular_student_results')->where('program_id', $programId)->countAllResults());

        $row = $this->db->table('cocurricular_student_results')
            ->where('program_id', $programId)
            ->where('student_id', $this->studentIds[0])
            ->where('dimension_id', $dims[0])
            ->get()->getRowArray();
        $this->assertSame('EXEMPLARY', $row['level']);
    }

    public function testEvaluationLifecycleAcrossAspects(): void
    {
        $programId = $this->createProgramFixture();

        foreach (['INPUT', 'PROCESS', 'OUTPUT', 'OUTCOME'] as $aspect) {
            $this->service->addEvaluation($programId, $this->unitId, [
                'aspect'    => $aspect,
                'indicator' => "Indikator {$aspect}",
                'finding'   => "Temuan {$aspect}",
                'rating'    => 4,
            ], 1);
        }

        $this->assertCount(4, $this->service->evaluations($programId));

        $evals = $this->service->evaluations($programId);
        $this->service->updateEvaluation((int) $evals[0]['id'], $programId, $this->unitId, [
            'indicator' => 'Indikator INPUT v2',
            'finding'   => 'Temuan baru',
            'rating'    => 5,
        ], 1);
        $this->assertSame('Indikator INPUT v2', $this->db->table('cocurricular_evaluations')->where('id', $evals[0]['id'])->get()->getRowArray()['indicator']);

        $this->service->deleteEvaluation((int) $evals[0]['id'], $programId, $this->unitId);
        $this->assertCount(3, $this->service->evaluations($programId));
    }

    public function testReportAggregatesDimensionsAndDistribution(): void
    {
        $programId = $this->createProgramFixture();
        $dims = array_slice($this->dimensionIds, 0, 2);

        $this->service->saveResults($programId, $this->unitId, [
            'results' => [
                $this->studentIds[0] => [$dims[0] => 'PROFICIENT', $dims[1] => 'DEVELOPING'],
                $this->studentIds[1] => [$dims[0] => 'EXEMPLARY', $dims[1] => 'DEVELOPING'],
            ],
        ], 1);

        $report = $this->service->report($programId);
        $this->assertSame($programId, (int) $report['program']['id']);
        $this->assertCount(2, $report['dimensions']);
        $this->assertCount(2, $report['summary']);

        $rowsByStudent = [];
        foreach ($report['rows'] as $r) {
            $rowsByStudent[(int) $r['student']['id']] = $r;
        }
        $this->assertArrayHasKey($this->studentIds[0], $rowsByStudent);
        $this->assertArrayHasKey($this->studentIds[1], $rowsByStudent);

        $summary = $report['summary'][0];
        $this->assertSame(1, $summary['distribution']['PROFICIENT']);
        $this->assertSame(1, $summary['distribution']['EXEMPLARY']);
        $this->assertSame(count($report['rows']), array_sum(array_map('intval', $summary['distribution'])));

        $row = $rowsByStudent[$this->studentIds[0]];
        $this->assertSame('PROFICIENT', $row['dimensions'][0]['level']);
        $this->assertSame('DEVELOPING', $row['dimensions'][1]['level']);
    }

    public function testUnitBoundaryEnforced(): void
    {
        $programId = $this->createProgramFixture();

        $this->expectException(RuntimeException::class);
        $this->service->transition($programId, $this->otherUnitId, 'ACTIVE', 1);
    }

    public function testHabitsAndCheckinsLifecycle(): void
    {
        $habitId = $this->service->saveHabit($this->unitId, [
            'code'             => 'BANGUN_PAGI',
            'name'             => 'Bangun Pagi',
            'description'      => 'Tidur tepat waktu & bangun pagi.',
            'icon'             => 'sunrise',
            'weekly_challenge' => 'Bangun sebelum 05.00 selama seminggu.',
            'sort_order'       => 1,
            'enabled'          => 1,
        ], 1);

        $habit = $this->db->table('cocurricular_habits')->where('id', $habitId)->get()->getRowArray();
        $this->assertSame('BANGUN_PAGI', $habit['code']);
        $this->assertSame(1, (int) $habit['enabled']);

        $this->expectException(InvalidArgumentException::class);
        $this->service->saveHabit($this->unitId, ['code' => 'BANGUN_PAGI', 'name' => 'Duplikat'], 1);
    }

    public function testCheckinSaveAndRead(): void
    {
        $habitId = $this->service->saveHabit($this->unitId, ['name' => 'Kebiasaan Baru'], 1);
        $week = '2026-09-07';

        $saved = $this->service->saveCheckins($this->unitId, $week, [
            'checkins' => [
                $this->studentIds[0] => [
                    $habitId => ['status' => 'DONE', 'note' => 'Semua hari berhasil'],
                    // habit 2 is not required
                ],
                $this->studentIds[1] => [
                    $habitId => 'PARTIAL',
                ],
            ],
        ], $this->teacherId, 1);

        $this->assertSame(2, $saved);

        $checkins = $this->service->checkins($this->unitId, $week);
        $this->assertCount(1, $checkins['habits']);
        $studentIdsInList = array_map('intval', array_column($checkins['students'], 'id'));
        $this->assertContains($this->studentIds[0], $studentIdsInList);
        $this->assertContains($this->studentIds[1], $studentIdsInList);
        $this->assertSame('DONE', $checkins['checkins'][$habitId . ':' . $this->studentIds[0]]['status']);
        $this->assertSame('PARTIAL', $checkins['checkins'][$habitId . ':' . $this->studentIds[1]]['status']);

        // Upsert keeps a single row per habit × student × week.
        $this->service->saveCheckins($this->unitId, $week, [
            'checkins' => [
                $this->studentIds[0] => [$habitId => ['status' => 'SKIP', 'note' => 'Izin keluarga']],
            ],
        ], $this->teacherId, 1);
        $this->assertSame(1, (int) $this->db->table('cocurricular_habit_checkins')
            ->where('habit_id', $habitId)->where('student_id', $this->studentIds[0])->where('checkin_week', $week)
            ->countAllResults());
        $this->assertSame('SKIP', $this->db->table('cocurricular_habit_checkins')
            ->where('habit_id', $habitId)->where('student_id', $this->studentIds[0])->where('checkin_week', $week)
            ->get()->getRowArray()['status']);
    }

    public function testInvalidCheckinStatusRejected(): void
    {
        $habitId = $this->service->saveHabit($this->unitId, ['name' => 'Kebiasaan X'], 1);

        $this->expectException(InvalidArgumentException::class);
        $this->service->saveCheckins($this->unitId, '2026-09-07', [
            'checkins' => [
                $this->studentIds[0] => [$habitId => 'MAYBE'],
            ],
        ], $this->teacherId, 1);
    }

    public function testDimensionDescriptorRubricStructure(): void
    {
        $rubric = $this->service->dimensionDescriptorRubric();
        $this->assertArrayHasKey('BERIMAN_BERTAKWA', $rubric);
        $this->assertArrayHasKey('BERNALAR_KRITIS', $rubric);
        $this->assertArrayHasKey('GOTONG_ROYONG', $rubric);
        $this->assertNotEmpty($rubric['BERNALAR_KRITIS']['EMERGING']);
        $this->assertNotEmpty($rubric['BERNALAR_KRITIS']['EXEMPLARY']);
    }

    public function testGenerateStudentNarrativeAndIpooHealth(): void
    {
        $programId = $this->createProgramFixture();
        $studentId = $this->studentIds[0];

        // Save student results
        $this->service->saveResults($programId, $this->unitId, [
            'results' => [
                $studentId => [
                    $this->dimensionIds[0] => ['level' => 'EXEMPLARY', 'note' => 'Sangat aktif memimpin diskusi'],
                    $this->dimensionIds[1] => ['level' => 'DEVELOPING', 'note' => 'Perlu dorongan bicara'],
                ],
            ],
        ], 1);

        // Add observation and evidence
        $this->service->addObservation($programId, $this->unitId, [
            'student_id'   => $studentId,
            'dimension_id' => $this->dimensionIds[0],
            'rating'       => 5,
            'notes'        => 'Menyelesaikan solusi proyek mandiri',
        ], 1);

        $this->service->addEvidence($programId, $this->unitId, [
            'student_id'   => $studentId,
            'title'        => 'Karya Proyek Test',
            'evidence_type'=> 'PROJECT',
        ], 1);

        // Add evaluations for IPOO
        $this->service->addEvaluation($programId, $this->unitId, [
            'aspect'    => 'INPUT',
            'indicator' => 'Ketersediaan Modul',
            'rating'    => 5,
        ], 1);
        $this->service->addEvaluation($programId, $this->unitId, [
            'aspect'    => 'OUTPUT',
            'indicator' => 'Kualitas Produk Siswa',
            'rating'    => 4,
        ], 1);

        // 1. Validate narrative output
        $narrative = $this->service->generateStudentNarrative($programId, $studentId);
        $this->assertNotEmpty($narrative);
        $this->assertStringContainsString('Ananda', $narrative);
        $this->assertStringContainsString('Sangat Berkembang', $narrative);

        // 2. Validate IPOO calculation
        $ipoo = $this->service->calculateIpooHealth($programId);
        $this->assertArrayHasKey('aspects', $ipoo);
        $this->assertArrayHasKey('overall_score', $ipoo);
        $this->assertGreaterThan(0, $ipoo['overall_percent']);
        $this->assertNotEmpty($ipoo['status']['label']);
    }
}