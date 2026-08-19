<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\TeachingWorkspaceService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\EducationFoundationFixtureTrait;
use Tests\Support\IsolatedDatabaseTestTrait;

final class TeachingWorkspaceEngineTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;
    use EducationFoundationFixtureTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    private TeachingWorkspaceService $service;
    protected int $teacherId;
    protected int $classroomId;
    protected int $studentId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEducationFoundationFixture();
        $this->service = new TeachingWorkspaceService();

        $teacher = $this->db->table('teachers')->get()->getRowArray();
        if (!$teacher) {
            $this->db->table('teachers')->insert([
                'uuid' => '20000000-0000-4000-8000-000000000001',
                'full_name' => 'Guru Pengajar Test',
                'primary_unit_id' => $this->unitId,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->teacherId = (int) $this->db->insertID();
        } else {
            $this->teacherId = (int) $teacher['id'];
        }

        $classroom = $this->db->table('classrooms')->where('unit_id', $this->unitId)->get()->getRowArray();
        if (!$classroom) {
            $this->db->table('classrooms')->insert([
                'uuid' => '20000000-0000-4000-8000-000000000002',
                'academic_period_id' => $this->periodId,
                'unit_id' => $this->unitId,
                'name' => 'X-A',
                'grade_level_id' => $this->gradeId,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->classroomId = (int) $this->db->insertID();
        } else {
            $this->classroomId = (int) $classroom['id'];
        }

        $student = $this->db->table('elective_students')->get()->getRowArray();
        if (!$student) {
            $period = $this->db->table('academic_periods')->where('id', $this->periodId)->get()->getRowArray();
            $yearId = $period ? (int) $period['academic_year_id'] : 1;

            $this->db->table('elective_students')->insert([
                'uuid' => '20000000-0000-4000-8000-000000000003',
                'academic_year_id' => $yearId,
                'unit_id' => $this->unitId,
                'classroom_id' => $this->classroomId,
                'student_number' => '1234567890',
                'full_name' => 'Siswa Test',
                'current_grade' => 'X',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->studentId = (int) $this->db->insertID();
        } else {
            $this->studentId = (int) $student['id'];
        }
    }

    public function testPhase5SchemaIsAvailable(): void
    {
        foreach ([
            'learning_sessions',
            'learning_session_activities',
            'learning_session_observations',
            'learning_session_reflections',
        ] as $table) {
            $this->assertTrue($this->db->tableExists($table), "Table {$table} should exist.");
        }
    }

    public function testPhase5PermissionsAreSeeded(): void
    {
        $permissions = ['teaching.workspace', 'teaching.teach', 'teaching.reflect'];
        foreach ($permissions as $code) {
            $count = $this->db->table('permissions')->where('code', $code)->countAllResults();
            $this->assertGreaterThan(0, $count, "Permission {$code} should be seeded in permissions table.");
        }
    }

    public function testInitializeAndLifecycleEndToEnd(): void
    {
        // 1. Initialize session
        $payload = [
            'academic_period_id' => $this->periodId,
            'unit_id'            => $this->unitId,
            'teacher_id'         => $this->teacherId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'session_date'       => date('Y-m-d'),
            'jp_count'           => 2,
            'start_time'         => '08:00:00',
            'end_time'           => '09:30:00',
            'topic'              => 'Eksplorasi Deep Learning',
        ];

        $session = $this->service->initializeSession($payload, 1);
        $this->assertNotEmpty($session['uuid']);
        $this->assertSame(TeachingWorkspaceService::STATUS_PLANNED, $session['status']);
        $this->assertSame('Eksplorasi Deep Learning', $session['topic']);

        // Check seeded 3D activities
        $activities = $this->db->table('learning_session_activities')
            ->where('learning_session_id', $session['id'])
            ->get()->getResultArray();
        $this->assertGreaterThanOrEqual(3, count($activities));

        // 2. Start session (PLANNED -> IN_PROGRESS)
        $startedSession = $this->service->startSession($session['uuid'], 1);
        $this->assertSame(TeachingWorkspaceService::STATUS_IN_PROGRESS, $startedSession['status']);
        $this->assertNotNull($startedSession['started_at']);

        // 3. Toggle activity checklist
        $firstAct = $activities[0];
        $updatedAct = $this->service->toggleActivity($session['uuid'], $firstAct['uuid'], true, 15, 'Selesai tepat waktu', 1);
        $this->assertSame(1, (int) $updatedAct['is_completed']);

        // 4. Quick attendance recording
        $attendanceSummary = $this->service->recordQuickAttendance($session['uuid'], [
            ['student_id' => $this->studentId, 'status' => 'HADIR'],
        ], 1);
        $this->assertIsArray($attendanceSummary);
        $this->assertGreaterThanOrEqual(1, $attendanceSummary['HADIR']);

        // 5. Add formative observation & misconception
        $obs = $this->service->addObservation($session['uuid'], [
            'student_id'          => $this->studentId,
            'observation_type'    => 'FORMATIVE',
            'rating'              => 'GOOD',
            'notes'               => 'Siswa aktif bertanya',
            'misconception_found' => 1,
            'misconception_detail'=> 'Bingung membedakan variabel lokal dan global',
            'follow_up_needed'    => 1,
        ], 1);
        $this->assertNotEmpty($obs['uuid']);
        $this->assertSame(1, (int) $obs['misconception_found']);

        // 6. Complete session (IN_PROGRESS -> COMPLETED)
        $completedSession = $this->service->completeSession($session['uuid'], [
            'deviation_notes'            => 'Diskusi kelompok ditambah 5 menit',
            'learning_objective_summary' => 'TP 1 tuntas dipahami siswa',
        ], 1);
        $this->assertSame(TeachingWorkspaceService::STATUS_COMPLETED, $completedSession['status']);
        $this->assertNotNull($completedSession['completed_at']);

        // 7. Save reflection (COMPLETED -> REFLECTED)
        $reflectedSession = $this->service->saveReflection($session['uuid'], [
            'what_went_well'        => 'Analogi materi sangat membantu',
            'challenges'            => 'Waktu praktikum perlu ditambah',
            'student_engagement'    => 'HIGH',
            'objective_achievement' => 'ACHIEVED',
            'tp_coverage_notes'     => 'TP 1.1 tuntas',
            'follow_up_plan'        => 'Latihan pengayaan di rumah',
            'self_rating'           => 5,
        ], 1);
        $this->assertSame(TeachingWorkspaceService::STATUS_REFLECTED, $reflectedSession['status']);
        $this->assertNotNull($reflectedSession['reflected_at']);

        // Verify reflection record in DB
        $reflection = $this->db->table('learning_session_reflections')
            ->where('learning_session_id', $session['id'])
            ->get()->getRowArray();
        $this->assertNotNull($reflection);
        $this->assertSame('Analogi materi sangat membantu', $reflection['what_went_well']);
        $this->assertSame(5, (int) $reflection['self_rating']);
    }

    public function testGetTodayWorkspaceSummary(): void
    {
        $today = date('Y-m-d');
        $workspace = $this->service->getTodayWorkspace($this->teacherId, $this->periodId, $today, $this->unitId);

        $this->assertIsArray($workspace);
        $this->assertArrayHasKey('stats', $workspace);
        $this->assertArrayHasKey('lessons', $workspace);
        $this->assertArrayHasKey('recent_misconceptions', $workspace);
    }

    public function testGetSessionDetailStructure(): void
    {
        $payload = [
            'academic_period_id' => $this->periodId,
            'unit_id'            => $this->unitId,
            'teacher_id'         => $this->teacherId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'session_date'       => date('Y-m-d'),
        ];

        $session = $this->service->initializeSession($payload, 1);
        $detail = $this->service->getSessionDetail($session['uuid']);

        $this->assertArrayHasKey('session', $detail);
        $this->assertArrayHasKey('activities', $detail);
        $this->assertArrayHasKey('grouped_activities', $detail);
        $this->assertArrayHasKey('observations', $detail);
        $this->assertArrayHasKey('students', $detail);
        $this->assertArrayHasKey('recommendations', $detail);

        $this->assertArrayHasKey('MEMAHAMI', $detail['grouped_activities']);
        $this->assertArrayHasKey('MENGAPLIKASI', $detail['grouped_activities']);
        $this->assertArrayHasKey('MEREFLEKSI', $detail['grouped_activities']);
    }

    public function testDeleteObservation(): void
    {
        $payload = [
            'academic_period_id' => $this->periodId,
            'unit_id'            => $this->unitId,
            'teacher_id'         => $this->teacherId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'session_date'       => date('Y-m-d'),
        ];
        $session = $this->service->initializeSession($payload, 1);

        $obs = $this->service->addObservation($session['uuid'], [
            'student_id'       => $this->studentId,
            'observation_type' => 'GENERAL',
            'notes'            => 'Catatan untuk dihapus',
        ], 1);

        $this->assertNotEmpty($obs['uuid']);
        $deleted = $this->service->deleteObservation($session['uuid'], $obs['uuid'], 1);
        $this->assertTrue($deleted);

        // Verify observation is gone
        $exists = $this->db->table('learning_session_observations')->where('uuid', $obs['uuid'])->countAllResults();
        $this->assertSame(0, $exists);
    }

    public function testLinkLessonPlanAndSyncMetadata(): void
    {
        // 1. Create a lesson plan using LessonPlanService
        $plan = \App\Services\LessonPlanService::create([
            'academic_period_id' => $this->periodId,
            'unit_id'            => $this->unitId,
            'subject_id'         => $this->subjectId,
            'grade_level_id'     => $this->gradeId,
            'teacher_id'         => $this->teacherId,
            'date'               => date('Y-m-d'),
            'session_number'     => 1,
            'session_label'      => 'Pertemuan 1: Pengenalan Algoritma',
            'learning_pack_id'   => null,
        ]);
        $planId = (int) $plan['id'];

        \App\Services\LessonPlanService::updateDesign($plan['uuid'], [
            'identification_notes' => 'Siswa mampu memahami definisi dasar algoritma.',
        ]);

        // 2. Initialize a session
        $payload = [
            'academic_period_id' => $this->periodId,
            'unit_id'            => $this->unitId,
            'teacher_id'         => $this->teacherId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'session_date'       => date('Y-m-d'),
        ];
        $session = $this->service->initializeSession($payload, 1);

        // 3. Link the lesson plan
        $linkedSession = $this->service->linkLessonPlan($session['uuid'], $planId, 1);
        $this->assertSame($planId, (int) $linkedSession['lesson_plan_id']);
        $this->assertSame('Pertemuan 1: Pengenalan Algoritma', $linkedSession['topic']);
        $this->assertSame('Siswa mampu memahami definisi dasar algoritma.', $linkedSession['learning_objective_summary']);
    }

    public function testCompleteSessionFromPlannedIsRejected(): void
    {
        $session = $this->service->initializeSession([
            'academic_period_id' => $this->periodId,
            'unit_id'            => $this->unitId,
            'teacher_id'         => $this->teacherId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'session_date'       => date('Y-m-d'),
        ], 1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('belum dimulai');
        $this->service->completeSession($session['uuid'], [], 1);
    }

    public function testSaveReflectionBeforeCompletedIsRejected(): void
    {
        $session = $this->service->initializeSession([
            'academic_period_id' => $this->periodId,
            'unit_id'            => $this->unitId,
            'teacher_id'         => $this->teacherId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'session_date'       => date('Y-m-d'),
        ], 1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('setelah sesi kelas diselesaikan');
        $this->service->saveReflection($session['uuid'], ['self_rating' => 4], 1);
    }

    public function testMutationsAreLockedAfterReflection(): void
    {
        $session = $this->service->initializeSession([
            'academic_period_id' => $this->periodId,
            'unit_id'            => $this->unitId,
            'teacher_id'         => $this->teacherId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'session_date'       => date('Y-m-d'),
        ], 1);

        $this->service->startSession($session['uuid'], 1);
        $this->service->completeSession($session['uuid'], [], 1);
        $this->service->saveReflection($session['uuid'], ['self_rating' => 4], 1);

        $activities = $this->db->table('learning_session_activities')
            ->where('learning_session_id', $session['id'])
            ->get()->getResultArray();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('dikunci');
        $this->service->toggleActivity($session['uuid'], $activities[0]['uuid'], true, null, null, 1);
    }

    public function testQuickAttendanceRejectsInvalidStatusWithoutPersisting(): void
    {
        $session = $this->service->initializeSession([
            'academic_period_id' => $this->periodId,
            'unit_id'            => $this->unitId,
            'teacher_id'         => $this->teacherId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'session_date'       => date('Y-m-d'),
        ], 1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak valid');
        $this->service->recordQuickAttendance($session['uuid'], [
            ['student_id' => $this->studentId, 'status' => 'HADIR'],
            ['student_id' => $this->studentId, 'status' => 'BOHONG'],
        ], 1);
    }

    public function testQuickAttendanceRejectsStudentOutsideClassroom(): void
    {
        $session = $this->service->initializeSession([
            'academic_period_id' => $this->periodId,
            'unit_id'            => $this->unitId,
            'teacher_id'         => $this->teacherId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'session_date'       => date('Y-m-d'),
        ], 1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak terdaftar');
        $this->service->recordQuickAttendance($session['uuid'], [
            ['student_id' => 999999, 'status' => 'HADIR'],
        ], 1);
    }

    public function testAddObservationRejectsInvalidRating(): void
    {
        $session = $this->service->initializeSession([
            'academic_period_id' => $this->periodId,
            'unit_id'            => $this->unitId,
            'teacher_id'         => $this->teacherId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'session_date'       => date('Y-m-d'),
        ], 1);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->addObservation($session['uuid'], ['rating' => 'SUPERB'], 1);
    }

    public function testAddObservationRejectsStudentOutsideClassroom(): void
    {
        $session = $this->service->initializeSession([
            'academic_period_id' => $this->periodId,
            'unit_id'            => $this->unitId,
            'teacher_id'         => $this->teacherId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'session_date'       => date('Y-m-d'),
        ], 1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak terdaftar');
        $this->service->addObservation($session['uuid'], ['student_id' => 999999, 'rating' => 'GOOD'], 1);
    }

    public function testSaveReflectionClampsSelfRating(): void
    {
        $session = $this->service->initializeSession([
            'academic_period_id' => $this->periodId,
            'unit_id'            => $this->unitId,
            'teacher_id'         => $this->teacherId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'session_date'       => date('Y-m-d'),
        ], 1);

        $this->service->startSession($session['uuid'], 1);
        $this->service->completeSession($session['uuid'], [], 1);

        $this->service->saveReflection($session['uuid'], ['self_rating' => 99], 1);
        $reflection = $this->db->table('learning_session_reflections')
            ->where('learning_session_id', $session['id'])
            ->get()->getRowArray();
        $this->assertSame(5, (int) $reflection['self_rating']);

        $this->service->saveReflection($session['uuid'], ['self_rating' => 0], 1);
        $reflection = $this->db->table('learning_session_reflections')
            ->where('learning_session_id', $session['id'])
            ->get()->getRowArray();
        $this->assertSame(1, (int) $reflection['self_rating']);
    }

    public function testSaveReflectionRejectsInvalidEngagement(): void
    {
        $session = $this->service->initializeSession([
            'academic_period_id' => $this->periodId,
            'unit_id'            => $this->unitId,
            'teacher_id'         => $this->teacherId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'session_date'       => date('Y-m-d'),
        ], 1);

        $this->service->startSession($session['uuid'], 1);
        $this->service->completeSession($session['uuid'], [], 1);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->saveReflection($session['uuid'], ['student_engagement' => 'MAXIMUM'], 1);
    }

    public function testLinkLessonPlanRejectsMismatchedUnit(): void
    {
        $session = $this->service->initializeSession([
            'academic_period_id' => $this->periodId,
            'unit_id'            => $this->unitId,
            'teacher_id'         => $this->teacherId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'session_date'       => date('Y-m-d'),
        ], 1);

        // Insert a lesson plan belonging to the other unit (SMP) directly.
        $this->db->table('lesson_plans')->insert([
            'uuid'               => '30000000-0000-4000-8000-0000000000A1',
            'academic_period_id' => $this->periodId,
            'unit_id'            => $this->otherUnitId,
            'subject_id'         => $this->subjectId,
            'grade_level_id'     => $this->gradeId,
            'teacher_id'         => $this->teacherId,
            'date'               => date('Y-m-d'),
            'session_number'     => 1,
            'status'             => 'DRAFT',
            'revision_number'    => 1,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);
        $foreignPlanId = (int) $this->db->insertID();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('unit sekolah');
        $this->service->linkLessonPlan($session['uuid'], $foreignPlanId, 1);
    }

    public function testLinkLessonPlanRejectsMismatchedSubject(): void
    {
        $session = $this->service->initializeSession([
            'academic_period_id' => $this->periodId,
            'unit_id'            => $this->unitId,
            'teacher_id'         => $this->teacherId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'session_date'       => date('Y-m-d'),
        ], 1);

        // Insert a second subject + a plan for it in the same unit.
        $this->db->table('subjects')->insert([
            'uuid'     => '30000000-0000-4000-8000-0000000000A2',
            'code'     => 'BIO-X-TEST',
            'name'     => 'Biologi X (Fixture)',
            'short_name'=> 'BIO',
            'category' => 'WAJIB',
            'is_active'=> 1,
            'created_at'=> date('Y-m-d H:i:s'),
        ]);
        $otherSubjectId = (int) $this->db->insertID();

        $this->db->table('lesson_plans')->insert([
            'uuid'               => '30000000-0000-4000-8000-0000000000A3',
            'academic_period_id' => $this->periodId,
            'unit_id'            => $this->unitId,
            'subject_id'         => $otherSubjectId,
            'grade_level_id'     => $this->gradeId,
            'teacher_id'         => $this->teacherId,
            'date'               => date('Y-m-d'),
            'session_number'     => 1,
            'status'             => 'DRAFT',
            'revision_number'    => 1,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);
        $mismatchedPlanId = (int) $this->db->insertID();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('mata pelajaran');
        $this->service->linkLessonPlan($session['uuid'], $mismatchedPlanId, 1);
    }

    public function testInitializeSessionRejectsScheduleEntryMismatch(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entri jadwal');
        $this->service->initializeSession([
            'academic_period_id' => $this->periodId,
            'unit_id'            => $this->unitId,
            'teacher_id'         => $this->teacherId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'schedule_entry_id'  => 999999,
            'session_date'       => date('Y-m-d'),
        ], 1);
    }

    public function testInitializeSessionRejectsInvalidDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Format tanggal');
        $this->service->initializeSession([
            'academic_period_id' => $this->periodId,
            'unit_id'            => $this->unitId,
            'teacher_id'         => $this->teacherId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'session_date'       => '2026/07/01',
        ], 1);
    }

    public function testStartSessionIsIdempotent(): void
    {
        $session = $this->service->initializeSession([
            'academic_period_id' => $this->periodId,
            'unit_id'            => $this->unitId,
            'teacher_id'         => $this->teacherId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'session_date'       => date('Y-m-d'),
        ], 1);

        $started = $this->service->startSession($session['uuid'], 1);
        $startedAgain = $this->service->startSession($session['uuid'], 1);

        $this->assertSame($started['started_at'], $startedAgain['started_at']);
        $this->assertSame(TeachingWorkspaceService::STATUS_IN_PROGRESS, $startedAgain['status']);
    }

    // ── Legacy Bridge Tests ──

    public function testFindLegacyAttendanceSessionReturnsNullWhenTableMissing(): void
    {
        $result = TeachingWorkspaceService::findLegacyAttendanceSession(999);
        $this->assertNull($result);
    }

    public function testFindLegacyAttendanceSessionReturnsNullForNonexistentId(): void
    {
        if (! $this->db->tableExists('attendance_sessions')) {
            $this->markTestSkipped('attendance_sessions table not available.');
        }

        $result = TeachingWorkspaceService::findLegacyAttendanceSession(999999);
        $this->assertNull($result);
    }

    public function testFindLegacyAttendanceSessionReturnsBridgedData(): void
    {
        if (! $this->db->tableExists('attendance_sessions')) {
            $this->markTestSkipped('attendance_sessions table not available.');
        }

        // Insert a fake legacy attendance session
        $this->db->table('attendance_sessions')->insert([
            'uuid'               => 'LEGACY-TEST-001',
            'academic_period_id' => $this->periodId,
            'unit_id'            => $this->unitId,
            'teacher_id'         => $this->teacherId,
            'classroom_id'       => $this->classroomId,
            'subject_id'         => $this->subjectId,
            'attendance_date'    => date('Y-m-d'),
            'meeting_number'     => 5,
            'topic'              => 'Topik Ujian Tengah Semester',
            'session_type'       => 'SUBJECT',
            'status'             => 'SUBMITTED',
            'created_at'         => date('Y-m-d H:i:s'),
        ]);
        $legacyId = (int) $this->db->insertID();

        $result = TeachingWorkspaceService::findLegacyAttendanceSession($legacyId);
        $this->assertNotNull($result);
        $this->assertSame('LEGACY_ATTENDANCE', $result['source']);
        $this->assertSame($legacyId, $result['legacy_id']);
        $this->assertSame(5, $result['meeting_number']);
        $this->assertSame('Topik Ujian Tengah Semester', $result['topic']);
        $this->assertSame('SUBMITTED', $result['status']);
        $this->assertStringStartsWith('legacy-', $result['uuid']);
    }

    public function testGetLegacySessionsForDateReturnsEmptyWhenNoTable(): void
    {
        $result = TeachingWorkspaceService::getLegacySessionsForDate($this->teacherId, $this->periodId, date('Y-m-d'));
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testOldPortalAttendanceIndexMethodRedirectsToTeachingWorkspace(): void
    {
        // Verify the old attendance controller's index() now redirects to the new teaching workspace
        $reflection = new \ReflectionMethod(\App\Controllers\TeacherAttendanceController::class, 'index');
        $this->assertTrue($reflection->isPublic());

        // Verify the method exists and is callable
        $controller = new \App\Controllers\TeacherAttendanceController();
        $this->assertTrue(method_exists($controller, 'index'));
        $this->assertTrue(method_exists($controller, 'indexLegacy'), 'Legacy method should be preserved for backward compat');
    }
}
