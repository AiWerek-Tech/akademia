<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\ExtracurricularService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\EducationFoundationFixtureTrait;
use Tests\Support\IsolatedDatabaseTestTrait;

/**
 * Phase 8 — Extracurricular & Character Engine Test Suite.
 *
 * Verifies program lifecycle, membership, capacity checks, session scheduling,
 * attendance tracking, competency standards, achievements/honors,
 * qualitative report card narrative drafter, and IPOO 4-pillar evaluations.
 *
 * @internal
 */
final class ExtracurricularEngineTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;
    use EducationFoundationFixtureTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    private ExtracurricularService $service;
    protected int $teacherId;
    protected int $studentId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEducationFoundationFixture();
        $this->service = new ExtracurricularService(\Config\Database::connect($this->DBGroup));

        $teacher = $this->db->table('teachers')->where('primary_unit_id', $this->unitId)->get()->getRowArray();
        if (! $teacher) {
            $this->db->table('teachers')->insert([
                'uuid'             => '81000000-0000-4000-8000-000000000001',
                'full_name'        => 'Pembina Ekskul Test',
                'normalized_name'  => 'PEMBINA EKSKUL TEST',
                'employment_status'=> 'ACTIVE',
                'primary_unit_id'  => $this->unitId,
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
            $this->teacherId = (int) $this->db->insertID();
        } else {
            $this->teacherId = (int) $teacher['id'];
        }

        $classroom = $this->db->table('classrooms')->where('unit_id', $this->unitId)->get()->getRowArray();
        if (! $classroom) {
            $this->db->table('classrooms')->insert([
                'uuid'              => '82000000-0000-4000-8000-000000000002',
                'academic_period_id'=> $this->periodId,
                'unit_id'           => $this->unitId,
                'name'              => 'X-EKSKUL',
                'grade_level_id'    => $this->gradeId,
                'is_active'         => 1,
                'created_at'        => date('Y-m-d H:i:s'),
            ]);
            $classroomId = (int) $this->db->insertID();
        } else {
            $classroomId = (int) $classroom['id'];
        }

        $period = $this->db->table('academic_periods')->where('id', $this->periodId)->get()->getRowArray();
        $yearId = $period ? (int) $period['academic_year_id'] : 1;

        $student = $this->db->table('elective_students')->where('unit_id', $this->unitId)->get()->getRowArray();
        if (! $student) {
            $this->db->table('elective_students')->insert([
                'uuid'             => '82000000-0000-4000-8000-000000000001',
                'academic_year_id' => $yearId,
                'classroom_id'     => $classroomId,
                'current_grade'    => 10,
                'full_name'        => 'Siswa Ekskul Test',
                'student_number'   => 'EKS-001',
                'unit_id'          => $this->unitId,
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
            $this->studentId = (int) $this->db->insertID();
        } else {
            $this->studentId = (int) $student['id'];
        }
    }

    public function testExtracurricularProgramLifecycle(): void
    {
        $programId = $this->service->createProgram([
            'unit_id'           => $this->unitId,
            'academic_period_id'=> $this->periodId,
            'code'              => 'EKS-PRAMUKA',
            'title'             => 'Gerakan Pramuka & Pathfinder',
            'category'          => 'SCOUT',
            'rationale'         => 'Membina kemandirian dan kecakapan hidup.',
            'objective'         => 'Mencetak kader pemimpin berkarakter.',
            'coach_teacher_id'  => $this->teacherId,
            'max_members'       => 40,
            'status'            => 'DRAFT',
        ], 1);

        $this->assertGreaterThan(0, $programId);
        $program = $this->service->detailProgram($programId);
        $this->assertSame('Gerakan Pramuka & Pathfinder', $program['title']);

        // Update
        $this->service->updateProgram($programId, [
            'title'             => 'Gerakan Pramuka & Pathfinder WMVAA',
            'category'          => 'SCOUT',
            'coach_teacher_id'  => $this->teacherId,
            'status'            => 'ACTIVE',
        ], 1);

        $updated = $this->service->detailProgram($programId);
        $this->assertSame('Gerakan Pramuka & Pathfinder WMVAA', $updated['title']);
    }

    public function testMembershipAndCapacity(): void
    {
        $programId = $this->service->createProgram([
            'unit_id'           => $this->unitId,
            'academic_period_id'=> $this->periodId,
            'title'             => 'Klub Futsal Prestasi',
            'category'          => 'SPORTS',
            'max_members'       => 2,
            'status'            => 'ACTIVE',
        ], 1);

        $this->assertTrue($this->service->hasCapacity($programId));

        $memberId = $this->service->addMember([
            'program_id' => $programId,
            'student_id' => $this->studentId,
            'role'       => 'LEADER',
            'join_date'  => date('Y-m-d'),
        ], 1);

        $this->assertGreaterThan(0, $memberId);
        $members = $this->service->listMembers($programId);
        $this->assertCount(1, $members);
        $this->assertSame('LEADER', $members[0]['role']);
    }

    public function testSessionAttendanceAndTrends(): void
    {
        $programId = $this->service->createProgram([
            'unit_id'           => $this->unitId,
            'academic_period_id'=> $this->periodId,
            'title'             => 'Vocal Group & Paduan Suara',
            'category'          => 'ARTS',
            'status'            => 'ACTIVE',
        ], 1);

        $memberId = $this->service->addMember([
            'program_id' => $programId,
            'student_id' => $this->studentId,
            'role'       => 'MEMBER',
        ], 1);

        // Add Session
        $sessionId = $this->service->createSession([
            'program_id'   => $programId,
            'session_date' => '2026-08-22',
            'start_time'   => '15:00',
            'end_time'     => '17:00',
            'topic'        => 'Latihan Pernapasan & Harmoni 4 Suara',
        ], 1);

        $this->assertGreaterThan(0, $sessionId);

        // Save Attendance
        $saved = $this->service->saveAttendance($sessionId, [
            [
                'member_id' => $memberId,
                'status'    => 'PRESENT',
                'notes'     => 'Hadir tepat waktu dan aktif bernyanyi',
            ],
        ], 1);

        $this->assertSame(1, $saved);

        // Verify Attendance Stats
        $stats = $this->service->calculateAttendanceStats($programId);
        $this->assertSame(1, $stats['total_sessions']);
        $this->assertSame(1, $stats['active_members']);
        $this->assertSame(100.0, (float) $stats['overall_attendance_pct']);
        $this->assertCount(1, $stats['session_trends']);
    }

    public function testCompetenciesAchievementsAndNarrative(): void
    {
        $programId = $this->service->createProgram([
            'unit_id'           => $this->unitId,
            'academic_period_id'=> $this->periodId,
            'title'             => 'Klub Robotik & Coding',
            'category'          => 'ACADEMIC_OLYMPIAD',
            'status'            => 'ACTIVE',
        ], 1);

        $memberId = $this->service->addMember([
            'program_id' => $programId,
            'student_id' => $this->studentId,
            'role'       => 'LEADER',
        ], 1);

        // Add Competency
        $compId = $this->service->addCompetency([
            'program_id'      => $programId,
            'code'            => 'ROB-01',
            'name'            => 'Algoritma Navigasi Line Follower',
            'assessment_type' => 'QUALITATIVE',
        ], 1);

        $this->assertGreaterThan(0, $compId);

        // Add Achievement
        $achId = $this->service->addAchievement([
            'member_id'     => $memberId,
            'competency_id' => $compId,
            'achieved_date' => date('Y-m-d'),
            'level'         => 'EXCELLENT',
            'score'         => 95.0,
            'remarks'       => 'Juara 1 Kontes Robotik Regional',
        ], 1);

        $this->assertGreaterThan(0, $achId);

        // Test Qualitative Report Narrative Generation
        $narrative = $this->service->generateStudentNarrative($programId, $this->studentId);
        $this->assertNotEmpty($narrative);
        $this->assertStringContainsString('Ananda', $narrative);
        $this->assertStringContainsString('Klub Robotik & Coding', $narrative);
        $this->assertStringContainsString('ketua/pemimpin', $narrative);
        $this->assertStringContainsString('Juara 1 Kontes Robotik Regional', $narrative);

        // Test IPOO Quality Calculation
        $ipoo = $this->service->calculateIpooHealth($programId);
        $this->assertArrayHasKey('overall_score', $ipoo);
        $this->assertArrayHasKey('aspects', $ipoo);
        $this->assertGreaterThan(0, $ipoo['overall_percent']);
        $this->assertNotEmpty($ipoo['status']['label']);
    }

    public function testStudentNarrativeEndpoint(): void
    {
        $programId = $this->service->createProgram([
            'unit_id'           => $this->unitId,
            'academic_period_id'=> $this->periodId,
            'title'             => 'Klub English Speech',
            'category'          => 'CLUB',
            'status'            => 'ACTIVE',
        ], 1);

        $this->service->addMember([
            'program_id' => $programId,
            'student_id' => $this->studentId,
            'role'       => 'MEMBER',
        ], 1);

        // Act with session authentication & permissions
        $result = $this->withSession([
            'logged_in'      => true,
            'user_id'        => 1,
            'active_unit_id' => $this->unitId,
            'permissions'    => ['extracurricular.view', 'extracurricular.manage'],
        ])->get("extracurricular/{$programId}/student-narrative/{$this->studentId}");

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
    }

    public function testReportCardDataAndCertificateGeneration(): void
    {
        $programId = $this->service->createProgram([
            'unit_id'           => $this->unitId,
            'academic_period_id'=> $this->periodId,
            'title'             => 'Klub Pramuka / Pathfinder',
            'category'          => 'SCOUT',
            'status'            => 'ACTIVE',
        ], 1);

        $memberId = $this->service->addMember([
            'program_id' => $programId,
            'student_id' => $this->studentId,
            'role'       => 'LEADER',
        ], 1);

        $compId = $this->service->addCompetency([
            'program_id'      => $programId,
            'code'            => 'SCOUT-HONOR-01',
            'name'            => 'Pioneering & Semaphore Master',
            'assessment_type' => 'QUALITATIVE',
        ], 1);

        $achId = $this->service->addAchievement([
            'member_id'     => $memberId,
            'competency_id' => $compId,
            'achieved_date' => date('Y-m-d'),
            'level'         => 'EXCELLENT',
            'score'         => 98.0,
            'remarks'       => 'Mastery Lencana Semaphore Tk. Utama',
        ], 1);

        // 1. Report card aggregator for Phase 9 integration
        $reportData = $this->service->getStudentExtracurricularReportCardData($this->studentId, $this->periodId);
        $this->assertNotEmpty($reportData);
        $this->assertEquals('Klub Pramuka / Pathfinder', $reportData[0]['program_title']);
        $this->assertEquals('A', $reportData[0]['predicate']);
        $this->assertNotEmpty($reportData[0]['narrative']);

        // 2. Certificate metadata generator
        $certData = $this->service->getCertificateData($programId, $this->studentId, $achId);
        $this->assertArrayHasKey('certificate_no', $certData);
        $this->assertArrayHasKey('verification_token', $certData);
        $this->assertEquals('Mastery Lencana Semaphore Tk. Utama', $certData['achievement']['remarks']);
    }

    public function testCertificateAndSummaryRoutes(): void
    {
        $programId = $this->service->createProgram([
            'unit_id'           => $this->unitId,
            'academic_period_id'=> $this->periodId,
            'title'             => 'Paduan Suara & Orkestra',
            'category'          => 'ARTS',
            'status'            => 'ACTIVE',
        ], 1);

        $this->service->addMember([
            'program_id' => $programId,
            'student_id' => $this->studentId,
            'role'       => 'MEMBER',
        ], 1);

        // 1. Certificate HTML view route
        $resultCert = $this->withSession([
            'logged_in'      => true,
            'user_id'        => 1,
            'active_unit_id' => $this->unitId,
            'permissions'    => ['extracurricular.view'],
        ])->get("extracurricular/{$programId}/certificate/{$this->studentId}");

        $resultCert->assertStatus(200);
        $resultCert->assertSee('SERTIFIKAT TANDA KECAKAPAN');

        // 2. Portal summary JSON route
        $resultSummary = $this->withSession([
            'logged_in'      => true,
            'user_id'        => 1,
            'active_unit_id' => $this->unitId,
            'permissions'    => ['extracurricular.view'],
        ])->get("extracurricular/student/{$this->studentId}/summary");

        $resultSummary->assertStatus(200);
        $resultSummary->assertJSONFragment(['status' => 'success']);
    }
}
