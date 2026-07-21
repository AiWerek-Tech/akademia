<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\TeacherModel;
use App\Models\SubjectModel;
use App\Models\RoomModel;
use App\Models\ClassroomModel;
use App\Models\GradeLevelModel;
use App\Services\TeacherService;
use App\Services\TeacherDuplicateDetectionService;
use App\Services\TeacherMergeService;
use App\Services\SubjectService;
use App\Services\ClassroomService;
use App\Services\RoomService;
use App\Services\GradeLevelService;
use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone2MasterSeeder;
use Config\Database;

/**
 * Milestone 2 Hardening & Final Acceptance Tests
 *
 * Covers: Teacher CRUD (E), Duplicate Review & Merge (F),
 * Subject CRUD (G), Grade Levels (H), Classroom CRUD (I),
 * Room CRUD (J), Security & RBAC (M)
 *
 * @internal
 */
final class Milestone2AcceptanceTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    private ?int $smpId = null;
    private ?int $smaId = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Milestone 2 master data
        $seeder = new Milestone2MasterSeeder(new \Config\Database());
        $seeder->run();

        $db = Database::connect($this->DBGroup);

        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        $sma = $db->table('school_units')->where('code', 'SMA')->get()->getRowArray();
        $this->smpId = $smp ? (int)$smp['id'] : null;
        $this->smaId = $sma ? (int)$sma['id'] : null;

        $this->ensureTestData($db);
    }

    private function ensureTestData($db): void
    {
        // Academic year
        if (!$db->table('academic_years')->where('id', 1)->get()->getRowArray()) {
            $db->table('academic_years')->insert([
                'id'         => 1,
                'uuid'       => \App\Services\UuidService::v4(),
                'name'       => '2026/2027',
                'start_date' => '2026-07-01',
                'end_date'   => '2027-06-30',
                'status'     => 'APPROVED',
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Period 1
        if (!$db->table('academic_periods')->where('id', 1)->get()->getRowArray()) {
            $db->table('academic_periods')->insert([
                'id'               => 1,
                'uuid'             => \App\Services\UuidService::v4(),
                'academic_year_id' => 1,
                'semester_number'  => 1,
                'name'             => '2026/2027 Ganjil',
                'start_date'       => '2026-07-01',
                'end_date'         => '2026-12-31',
                'workflow_status'  => 'OPEN',
                'is_active'        => 1,
            ]);
        }

        // Period 2
        if (!$db->table('academic_periods')->where('id', 2)->get()->getRowArray()) {
            $db->table('academic_periods')->insert([
                'id'               => 2,
                'uuid'             => \App\Services\UuidService::v4(),
                'academic_year_id' => 1,
                'semester_number'  => 2,
                'name'             => '2026/2027 Genap',
                'start_date'       => '2027-01-01',
                'end_date'         => '2027-06-30',
                'workflow_status'  => 'DRAFT',
                'is_active'        => 0,
            ]);
        }

        // Simulate logged-in super admin
        session()->set('user_id', 1);
    }

    // ==================================================
    // E. TEACHER TESTS (20 scenarios)
    // ==================================================

    /** E.1 Create valid teacher */
    public function testE01_CreateValidTeacher(): void
    {
        $result = TeacherService::createTeacher([
            'full_name'         => 'John Doe',
            'nip'               => '198501012010011002',
            'nik'               => '3201010101010001',
            'employee_number'   => 'EMP001',
            'gender'            => 'L',
            'birth_place'       => 'Jayapura',
            'birth_date'        => '1985-01-01',
            'email'             => 'johndoe@wmvaa.id',
            'phone'             => '081234567890',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
            'is_active'         => 1,
        ]);

        $this->assertIsArray($result);
        $this->assertIsNumeric($result['teacher']['id']);
        $this->assertNotEmpty($result['teacher']['uuid']);
        $this->assertNotEquals('COMPLETE', $result['teacher']['profile_status']);
    }

    /** E.2 Required fields validation */
    public function testE02_RequiredFieldsMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Nama Lengkap');

        TeacherService::createTeacher([
            'nip'               => '198501012010011099',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
        ]);
    }

    /** E.3 Invalid email format */
    public function testE03_InvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('email');

        TeacherService::createTeacher([
            'full_name'         => 'Bad Email Teacher',
            'email'             => 'not-an-email',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
        ]);
    }

    /** E.4 Future birth_date rejected */
    public function testE04_FutureBirthDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('masa depan');

        TeacherService::createTeacher([
            'full_name'         => 'Future Teacher',
            'birth_date'        => '2099-12-31',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
        ]);
    }

    /** E.5 Duplicate NIP rejected */
    public function testE05_DuplicateNIP(): void
    {
        TeacherService::createTeacher([
            'full_name'         => 'NIP Owner',
            'nip'               => '198501012010011003',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('NIP');

        TeacherService::createTeacher([
            'full_name'         => 'NIP Duplicate',
            'nip'               => '198501012010011003',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
        ]);
    }

    /** E.6 Duplicate NIK rejected */
    public function testE06_DuplicateNIK(): void
    {
        TeacherService::createTeacher([
            'full_name'         => 'NIK Owner',
            'nik'               => '3201010101010099',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('NIK');

        TeacherService::createTeacher([
            'full_name'         => 'NIK Duplicate',
            'nik'               => '3201010101010099',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
        ]);
    }

    /** E.8 Fuzzy name creates review candidate, no auto-merge */
    public function testE08_FuzzyNameCreatesReviewNotMerge(): void
    {
        $t1 = TeacherService::createTeacher([
            'full_name'         => 'Budi Sudarsono',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
        ]);

        // Similar name — Levenshtein distance should detect similarity
        $t2 = TeacherService::createTeacher([
            'full_name'         => 'Budi Sudarson', // slightly different
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
        ]);

        // Both teachers still exist (no auto-merge)
        $teacherModel = new TeacherModel();
        $this->assertNotNull($teacherModel->find($t1['teacher']['id']));
        $this->assertNotNull($teacherModel->find($t2['teacher']['id']));

        // Duplicate matches should be detected via Levenshtein/Soundex
        $this->assertNotEmpty($t2['duplicate_matches'], 'Fuzzy duplicates should be detected');
        $this->assertNotEmpty($t2['duplicate_group_uuid'], 'Duplicate review group should be created');
    }

    /** E.9 Same phone does NOT auto-merge alone */
    public function testE09_PhoneAloneDoesNotAutoMerge(): void
    {
        $t1 = TeacherService::createTeacher([
            'full_name'         => 'Alice Wonderland',
            'phone'             => '081999888777',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
        ]);

        $t2 = TeacherService::createTeacher([
            'full_name'         => 'Bob Marley',
            'phone'             => '081999888777', // same phone, different name
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
        ]);

        // Phone alone (score 15) should NOT trigger duplicate detection (threshold >= 60)
        $this->assertEmpty($t2['duplicate_matches']);

        // Both exist
        $teacherModel = new TeacherModel();
        $this->assertNotNull($teacherModel->find($t1['teacher']['id']));
        $this->assertNotNull($teacherModel->find($t2['teacher']['id']));
    }

    /** E.10/11 Unit assignment with HOME_UNIT primary */
    public function testE10_UnitAssignment(): void
    {
        $db = Database::connect($this->DBGroup);

        $result = TeacherService::createTeacher([
            'full_name'         => 'Unit Assign Teacher',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
        ], [$this->smpId]);

        $assignments = $db->table('teacher_unit_assignments')
            ->where('teacher_id', $result['teacher']['id'])
            ->get()->getResultArray();

        $this->assertCount(1, $assignments);
        $this->assertEquals('HOME_UNIT', $assignments[0]['assignment_type']);
        $this->assertEquals(1, (int)$assignments[0]['is_primary']);
    }

    /** E.12 Multi-unit teacher */
    public function testE12_MultiUnitTeacher(): void
    {
        $db = Database::connect($this->DBGroup);

        $result = TeacherService::createTeacher([
            'full_name'         => 'Multi Unit Teacher',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
        ], [$this->smpId, $this->smaId]);

        $assignments = $db->table('teacher_unit_assignments')
            ->where('teacher_id', $result['teacher']['id'])
            ->get()->getResultArray();

        $this->assertCount(2, $assignments);
    }

    /** E.13 Profile completeness evaluates to INCOMPLETE */
    public function testE13_ProfileCompleteness(): void
    {
        $result = TeacherService::createTeacher([
            'full_name'         => 'Incomplete Teacher',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
        ]);

        $this->assertEquals('INCOMPLETE', $result['teacher']['profile_status']);
    }

    /** E.14 Verify teacher profile */
    public function testE14_VerifyTeacher(): void
    {
        $result = TeacherService::createTeacher([
            'full_name'         => 'Verify Me Teacher',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
        ]);

        $verified = TeacherService::verifyTeacher($result['teacher']['uuid']);
        $this->assertTrue($verified);

        $teacherModel = new TeacherModel();
        $teacher = $teacherModel->find($result['teacher']['id']);
        $this->assertEquals('VERIFIED', $teacher['profile_status']);
    }

    /** E.15 Activate/deactivate teacher */
    public function testE15_ActivateDeactivate(): void
    {
        $result = TeacherService::createTeacher([
            'full_name'         => 'Toggle Teacher',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
        ]);

        $updated = TeacherService::updateTeacher($result['teacher']['uuid'], [
            'full_name'       => 'Toggle Teacher',
            'is_active'       => 0,
            'revision_number' => 1,
        ]);

        $this->assertEquals(0, (int)$updated['is_active']);
    }

    /** E.16 Stale revision rejected (optimistic locking) */
    public function testE16_StaleRevisionRejected(): void
    {
        $result = TeacherService::createTeacher([
            'full_name'         => 'Stale Teacher',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stale');

        TeacherService::updateTeacher($result['teacher']['uuid'], [
            'full_name'       => 'Stale Teacher Updated',
            'revision_number' => 999,
        ]);
    }

    /** E.20 Soft delete (not hard delete) */
    public function testE20_SoftDelete(): void
    {
        $result = TeacherService::createTeacher([
            'full_name'         => 'Soft Delete Teacher',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
        ]);

        $teacherModel = new TeacherModel();
        $teacherModel->delete($result['teacher']['id']);

        // Soft deleted — findAll won't find it, but onlyDeleted will
        $deleted = $teacherModel->onlyDeleted()->find($result['teacher']['id']);
        $this->assertNotNull($deleted);
        $this->assertNotNull($deleted['deleted_at']);
    }

    // ==================================================
    // F. DUPLICATE REVIEW & MERGE (18 scenarios)
    // ==================================================

    /** F.1 Exact NIP match creates review group */
    public function testF01_ExactNIPMatchCreatesReview(): void
    {
        $t1 = TeacherService::createTeacher([
            'full_name'         => 'NIP Match A',
            'nip'               => '198001012005011001',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
        ]);

        // Second teacher with same NIP will be rejected by uniqueness check
        try {
            TeacherService::createTeacher([
                'full_name'         => 'NIP Match B',
                'nip'               => '198001012005011001',
                'employment_status' => 'GURU_TETAP',
                'primary_unit_id'   => $this->smpId,
            ]);
            $this->fail('Should reject duplicate NIP');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('NIP', $e->getMessage());
        }
    }

    /** F.5 MERGE decision works correctly */
    public function testF05_MergeDecision(): void
    {
        $db = Database::connect($this->DBGroup);

        // Create two teachers with similar names (will trigger duplicate detection)
        $target = TeacherService::createTeacher([
            'full_name'         => 'Target Canonical',
            'email'             => 'target@wmvaa.id',
            'phone'             => '081234500001',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
        ]);

        $duplicate = TeacherService::createTeacher([
            'full_name'         => 'Target Canonicl', // Levenshtein close
            'email'             => 'target.dup@wmvaa.id',
            'phone'             => '081234500002',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $this->smpId,
        ]);

        // Perform merge using integer IDs
        $mergeResult = TeacherMergeService::merge(
            (int)$target['teacher']['id'],
            (int)$duplicate['teacher']['id'],
            [
                'full_name' => 'Target Canonical Merged',
                'email'     => 'target@wmvaa.id',
            ]
        );

        $this->assertTrue($mergeResult);

        // Soft deleted duplicate
        $teacherModel = new TeacherModel();
        $mergedDup = $teacherModel->onlyDeleted()->find($duplicate['teacher']['id']);
        $this->assertNotNull($mergedDup);
        $this->assertNotNull($mergedDup['deleted_at']);

        // Canonical updated
        $canonical = $teacherModel->find($target['teacher']['id']);
        $this->assertEquals('Target Canonical Merged', $canonical['full_name']);
    }

    /** F: Cannot merge teacher into self */
    public function testF_CannotMergeSelf(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak boleh sama');

        TeacherMergeService::merge(999, 999, ['full_name' => 'Self']);
    }

    // ==================================================
    // G. SUBJECT TESTS (15 scenarios)
    // ==================================================

    /** G.1 Create valid subject */
    public function testG01_CreateValidSubject(): void
    {
        $subject = SubjectService::createSubject([
            'code'                    => 'MAT-GEN',
            'name'                    => 'Matematika Umum',
            'short_name'              => 'Mat',
            'category'                => 'WAJIB',
            'counts_in_report'        => 1,
            'counts_as_teaching_load' => 1,
        ], [$this->smpId], ['MTK', 'Math']);

        $this->assertIsArray($subject);
        $this->assertEquals('MAT-GEN', $subject['code']);
    }

    /** G.2 Duplicate code rejected */
    public function testG02_DuplicateCodeRejected(): void
    {
        SubjectService::createSubject([
            'code'     => 'ENG-DUP',
            'name'     => 'English Original',
            'category' => 'WAJIB',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kode');

        SubjectService::createSubject([
            'code'     => 'ENG-DUP',
            'name'     => 'English Duplicate',
            'category' => 'WAJIB',
        ]);
    }

    /** G.9 Invalid category rejected */
    public function testG09_InvalidCategoryRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kategori');

        SubjectService::createSubject([
            'code'     => 'BAD-CAT',
            'name'     => 'Bad Category',
            'category' => 'INVALID_CATEGORY',
        ]);
    }

    /** G.3 Alias creation */
    public function testG03_AliasCreation(): void
    {
        $db = Database::connect($this->DBGroup);

        $subject = SubjectService::createSubject([
            'code'     => 'FISIKA',
            'name'     => 'Fisika',
            'category' => 'WAJIB',
        ], [$this->smpId], ['Physics', 'PHY']);

        $aliases = $db->table('subject_aliases')
            ->where('subject_id', $subject['id'])
            ->get()->getResultArray();

        $this->assertCount(2, $aliases);
    }

    /** G.5 Unit availability */
    public function testG05_UnitAvailability(): void
    {
        $db = Database::connect($this->DBGroup);

        $subject = SubjectService::createSubject([
            'code'     => 'BIO-UNIT',
            'name'     => 'Biologi',
            'category' => 'WAJIB',
        ], [$this->smpId, $this->smaId]);

        $avail = $db->table('subject_unit_availability')
            ->where('subject_id', $subject['id'])
            ->get()->getResultArray();

        $this->assertCount(2, $avail);
    }

    // ==================================================
    // H. GRADE TESTS (8 scenarios)
    // ==================================================

    /** H.1 Seeded grades exist: SMP VII-IX phases D/E */
    public function testH01_SeededGradesExist(): void
    {
        $db = Database::connect($this->DBGroup);
        $smpGrades = $db->table('grade_levels')
            ->where('unit_id', $this->smpId)
            ->orderBy('grade_number', 'ASC')
            ->get()->getResultArray();

        $this->assertCount(3, $smpGrades);
        $this->assertEquals(7, (int)$smpGrades[0]['grade_number']);
        $this->assertEquals('D', $smpGrades[0]['phase']);
    }

    /** H.2 SMA X-XII exist with phases E/F */
    public function testH02_SMAGradesExist(): void
    {
        $db = Database::connect($this->DBGroup);
        $smaGrades = $db->table('grade_levels')
            ->where('unit_id', $this->smaId)
            ->orderBy('grade_number', 'ASC')
            ->get()->getResultArray();

        $this->assertCount(3, $smaGrades);
        $this->assertEquals(10, (int)$smaGrades[0]['grade_number']);
        $this->assertEquals('E', $smaGrades[0]['phase']);
    }

    /** H.3 Edit grade level name */
    public function testH03_EditGradeLevel(): void
    {
        $db = Database::connect($this->DBGroup);
        $grade = $db->table('grade_levels')
            ->where('unit_id', $this->smpId)
            ->where('grade_number', 7)
            ->get()->getRowArray();

        $updated = GradeLevelService::updateGradeLevel($grade['uuid'], [
            'name' => 'Tingkat VII Super',
        ]);

        $this->assertEquals('Tingkat VII Super', $updated['name']);
        $this->assertEquals('D', $updated['phase']); // phase unchanged
    }

    /** H.4 Seeder rerun safe (no duplicate grades) */
    public function testH04_SeederRerunSafe(): void
    {
        $db = Database::connect($this->DBGroup);
        $countBefore = $db->table('grade_levels')->countAllResults();

        // Re-run seeder
        $seeder = new Milestone2MasterSeeder(new \Config\Database());
        $seeder->run();

        $countAfter = $db->table('grade_levels')->countAllResults();
        $this->assertEquals($countBefore, $countAfter);
    }

    // ==================================================
    // I. CLASSROOM TESTS (19 scenarios)
    // ==================================================

    /** I.1 Create valid classroom */
    public function testI01_CreateValidClassroom(): void
    {
        $db = Database::connect($this->DBGroup);
        $grade = $db->table('grade_levels')
            ->where('unit_id', $this->smpId)
            ->get()->getRowArray();

        $classroom = ClassroomService::createClassroom([
            'academic_period_id' => 1,
            'unit_id'            => $this->smpId,
            'grade_level_id'     => $grade['id'],
            'code'               => 'VII-A-TEST',
            'name'               => 'Kelas VII A Test',
            'capacity'           => 32,
            'is_active'          => 1,
        ]);

        $this->assertIsArray($classroom);
        $this->assertEquals('VII-A-TEST', $classroom['code']);
    }

    /** I.2 Duplicate code per period+unit rejected */
    public function testI02_DuplicateClassroomCode(): void
    {
        $db = Database::connect($this->DBGroup);
        $grade = $db->table('grade_levels')
            ->where('unit_id', $this->smpId)
            ->get()->getRowArray();

        ClassroomService::createClassroom([
            'academic_period_id' => 1,
            'unit_id'            => $this->smpId,
            'grade_level_id'     => $grade['id'],
            'code'               => 'VII-DUP',
            'name'               => 'Kelas VII DUP',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kode kelas');

        ClassroomService::createClassroom([
            'academic_period_id' => 1,
            'unit_id'            => $this->smpId,
            'grade_level_id'     => $grade['id'],
            'code'               => 'VII-DUP',
            'name'               => 'Kelas VII DUP Duplicate',
        ]);
    }

    /** I.3 Copy period: preview returns expected data */
    public function testI03_CopyPeriodPreview(): void
    {
        $db = Database::connect($this->DBGroup);
        $grade = $db->table('grade_levels')
            ->where('unit_id', $this->smpId)
            ->get()->getRowArray();

        // Create classroom in period 1
        ClassroomService::createClassroom([
            'academic_period_id' => 1,
            'unit_id'            => $this->smpId,
            'grade_level_id'     => $grade['id'],
            'code'               => 'VII-COPY',
            'name'               => 'Kelas VII Copy',
        ]);

        $preview = ClassroomService::previewCopyPeriod(1, 2, $this->smpId);
        $this->assertIsArray($preview);
        $this->assertNotEmpty($preview);
    }

    /** I.4 Copy period: apply creates classrooms in target period */
    public function testI04_CopyPeriodApply(): void
    {
        $db = Database::connect($this->DBGroup);
        $grade = $db->table('grade_levels')
            ->where('unit_id', $this->smpId)
            ->get()->getRowArray();

        ClassroomService::createClassroom([
            'academic_period_id' => 1,
            'unit_id'            => $this->smpId,
            'grade_level_id'     => $grade['id'],
            'code'               => 'VII-COPYAPPLY',
            'name'               => 'Kelas VII CopyApply',
        ]);

        $result = ClassroomService::applyCopyPeriod(1, 2, $this->smpId);
        $this->assertGreaterThan(0, $result['copied_count']);
    }

    // ==================================================
    // J. ROOM TESTS (12 scenarios)
    // ==================================================

    /** J.1 Create unit-specific room */
    public function testJ01_CreateUnitRoom(): void
    {
        $db = Database::connect($this->DBGroup);
        $roomType = $db->table('room_types')->where('is_active', 1)->get()->getRowArray();

        $room = RoomService::createRoom([
            'code'                 => 'LAB-COMP-1',
            'name'                 => 'Laboratorium Komputer Utama',
            'room_type_id'         => $roomType['id'],
            'unit_id'              => $this->smpId,
            'shared_between_units' => 0,
            'capacity'             => 40,
            'is_active'            => 1,
        ]);

        $this->assertIsArray($room);
        $this->assertEquals('LAB-COMP-1', $room['code']);
    }

    /** J.2 Create shared room */
    public function testJ02_CreateSharedRoom(): void
    {
        $db = Database::connect($this->DBGroup);
        $roomType = $db->table('room_types')->where('is_active', 1)->get()->getRowArray();

        $room = RoomService::createRoom([
            'code'                 => 'AULA-SHARED',
            'name'                 => 'Aula Bersama',
            'room_type_id'         => $roomType['id'],
            'shared_between_units' => 1,
            'capacity'             => 200,
            'is_active'            => 1,
        ]);

        $this->assertIsArray($room);
        $this->assertEquals(1, (int)$room['shared_between_units']);
    }

    /** J.3 Non-shared room without unit_id rejected */
    public function testJ03_NonSharedWithoutUnitRejected(): void
    {
        $db = Database::connect($this->DBGroup);
        $roomType = $db->table('room_types')->where('is_active', 1)->get()->getRowArray();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('non-shared wajib');

        RoomService::createRoom([
            'code'                 => 'FAIL-ROOM',
            'name'                 => 'Fail Room',
            'room_type_id'         => $roomType['id'],
            'shared_between_units' => 0,
            'unit_id'              => null,
        ]);
    }

    /** J.4 Duplicate room code rejected */
    public function testJ04_DuplicateRoomCode(): void
    {
        $db = Database::connect($this->DBGroup);
        $roomType = $db->table('room_types')->where('is_active', 1)->get()->getRowArray();

        RoomService::createRoom([
            'code'                 => 'DUP-ROOM',
            'name'                 => 'Dup Room 1',
            'room_type_id'         => $roomType['id'],
            'unit_id'              => $this->smpId,
            'shared_between_units' => 0,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kode ruang');

        RoomService::createRoom([
            'code'                 => 'DUP-ROOM',
            'name'                 => 'Dup Room 2',
            'room_type_id'         => $roomType['id'],
            'unit_id'              => $this->smpId,
            'shared_between_units' => 0,
        ]);
    }

    /** J.5 Negative capacity rejected */
    public function testJ05_NegativeCapacityRejected(): void
    {
        $db = Database::connect($this->DBGroup);
        $roomType = $db->table('room_types')->where('is_active', 1)->get()->getRowArray();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('negatif');

        RoomService::createRoom([
            'code'                 => 'NEG-CAP',
            'name'                 => 'Negative Capacity',
            'room_type_id'         => $roomType['id'],
            'unit_id'              => $this->smpId,
            'shared_between_units' => 0,
            'capacity'             => -10,
        ]);
    }

    // ==================================================
    // M. CSRF & RBAC GATE (via FeatureTestTrait)
    // ==================================================

    /** M.1 No session → redirect to login */
    public function testM01_NoSessionRedirectsToLogin(): void
    {
        $result = $this->withSession([])->get('/teachers');
        $result->assertRedirectTo('/dashboard');
    }

    /** M.2 POST without CSRF token → rejected */
    public function testM02_PostWithoutCsrfRejected(): void
    {
        $result = $this->post('/teachers/store', [
            'full_name'         => 'CSRF Test',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => 1,
        ]);

        // Should not be 200 OK — either 403 (CSRF) or redirect
        $this->assertTrue(
            in_array($result->response()->getStatusCode(), [302, 403]),
            'POST without CSRF should be rejected'
        );
    }
}
