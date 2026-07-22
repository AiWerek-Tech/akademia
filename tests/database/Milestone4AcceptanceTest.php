<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Services\TeacherWorkloadCalculationService;
use App\Services\AssignmentValidationService;
use App\Services\AssignmentWorkflowService;
use App\Services\AssignmentMatrixService;
use App\Services\AssignmentImportService;
use App\Models\AssignmentVersionModel;
use App\Models\TeachingAssignmentModel;
use App\Models\TeacherAdditionalDutyModel;
use App\Models\WorkloadPolicyModel;
use App\Models\TeacherModel;
use App\Models\CurriculumVersionModel;
use App\Models\ClassroomModel;
use App\Models\SubjectModel;
use App\Models\AdditionalDutyTypeModel;
use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone2MasterSeeder;
use App\Database\Seeds\Milestone3CurriculumSeeder;
use App\Database\Seeds\Milestone4Seeder;
use Config\Database;

/**
 * Milestone 4 Assignments & Workloads Acceptance Tests
 *
 * @internal
 */
final class Milestone4AcceptanceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    private ?int $smpId = null;
    private ?int $periodId = null;
    private ?int $teacherId = null;
    private ?int $curriculumVersionId = null;
    private ?int $subjectId = null;
    private ?int $classId = null;
    private ?int $structureId = null;

    protected function setUp(): void
    {
        parent::setUp();

        $seeder2 = new Milestone2MasterSeeder(new Database());
        $seeder2->run();

        $seeder3 = new Milestone3CurriculumSeeder(new Database());
        $seeder3->run();

        $seeder4 = new Milestone4Seeder(new Database());
        $seeder4->run();

        $db = Database::connect($this->DBGroup);

        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        $this->smpId = $smp ? (int)$smp['id'] : 1;

        // Ensure academic year exists
        $year = $db->table('academic_years')->where('name', '2026/2027')->get()->getRowArray();
        if (!$year) {
            $db->table('academic_years')->insert([
                'uuid'       => '00000000-0000-0000-0000-000000000000',
                'name'       => '2026/2027',
                'start_date' => '2026-07-01',
                'end_date'   => '2027-06-30',
                'status'     => 'APPROVED',
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $yearId = $db->insertID();
        } else {
            $yearId = (int)$year['id'];
        }

        $period = $db->table('academic_periods')->where('is_active', 1)->get()->getRowArray();
        if (!$period) {
            $db->table('academic_periods')->insert([
                'uuid'             => '00000000-0000-0000-0000-000000000001',
                'academic_year_id' => $yearId,
                'semester_number'  => 1,
                'name'             => 'Ganjil 2026/2027',
                'start_date'       => '2026-07-01',
                'end_date'         => '2026-12-31',
                'workflow_status'  => 'OPEN',
                'is_active'        => 1,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
            $this->periodId = $db->insertID();
        } else {
            $this->periodId = (int)$period['id'];
        }

        // Ensure teacher exists
        $teacher = $db->table('teachers')->where('is_active', 1)->get()->getRowArray();
        if ($teacher) {
            $this->teacherId = (int)$teacher['id'];
        } else {
            $db->table('teachers')->insert([
                'uuid' => '00000000-0000-0000-0000-000000000100',
                'full_name' => 'John Doe M4 Test',
                'normalized_name' => 'john doe m4 test',
                'employment_status' => 'GURU_TETAP',
                'employment_type' => 'FULL_TIME',
                'primary_unit_id' => $this->smpId,
                'is_active' => 1,
            ]);
            $this->teacherId = $db->insertID();
        }

        // Add teacher unit assignment if missing
        $db->table('teacher_unit_assignments')
           ->where('teacher_id', $this->teacherId)
           ->where('unit_id', $this->smpId)
           ->delete();
        $db->table('teacher_unit_assignments')->insert([
            'teacher_id' => $this->teacherId,
            'unit_id' => $this->smpId,
            'status' => 'ACTIVE'
        ]);

        // Ensure curriculum version exists
        $curriculum = $db->table('curriculum_versions')->where('is_active', 1)->get()->getRowArray();
        if ($curriculum) {
            $this->curriculumVersionId = (int)$curriculum['id'];
        } else {
            $db->table('curriculum_versions')->insert([
                'uuid' => '00000000-0000-0000-0000-000000000200',
                'academic_period_id' => $this->periodId,
                'code' => 'CURR-TEST-M4',
                'name' => 'Kurikulum Test M4',
                'workflow_status' => 'APPROVED',
                'is_active' => 1
            ]);
            $this->curriculumVersionId = $db->insertID();
        }

        // Grade level
        $grade = $db->table('grade_levels')->where('unit_id', $this->smpId)->where('code', 'VII')->get()->getRowArray();
        $gradeId = $grade ? (int)$grade['id'] : 1;

        // Classrooms
        $classroom = $db->table('classrooms')->where('unit_id', $this->smpId)->where('academic_period_id', $this->periodId)->get()->getRowArray();
        if (!$classroom) {
            $db->table('classrooms')->insert([
                'uuid' => '00000000-0000-0000-0000-000000000030',
                'academic_period_id' => $this->periodId,
                'unit_id' => $this->smpId,
                'grade_level_id' => $gradeId,
                'code' => '7A',
                'name' => 'Kelas 7 A',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->classId = $db->insertID();
        } else {
            $this->classId = (int)$classroom['id'];
        }

        // Subjects
        $subject = $db->table('subjects')->where('code', 'MAT-SMP')->get()->getRowArray();
        if (!$subject) {
            $this->subjectId = $db->table('subjects')->insert([
                'uuid' => '00000000-0000-0000-0000-000000000040',
                'code' => 'MAT-SMP',
                'name' => 'Matematika SMP',
                'short_name' => 'MAT',
                'category' => 'WAJIB',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $this->subjectId = (int)$subject['id'];
        }

        // Ensure subject availability
        $avail = $db->table('subject_unit_availability')->where('subject_id', $this->subjectId)->where('unit_id', $this->smpId)->get()->getRowArray();
        if (!$avail) {
            $db->table('subject_unit_availability')->insert([
                'subject_id' => $this->subjectId,
                'unit_id' => $this->smpId
            ]);
        }

        // Create structure slot in curriculum
        $db->table('curriculum_structures')->insert([
            'curriculum_version_id' => $this->curriculumVersionId,
            'unit_id' => $this->smpId,
            'grade_level_id' => $gradeId,
            'classroom_id' => $this->classId,
            'subject_id' => $this->subjectId,
            'official_weekly_hours' => 4.00,
            'effective_weekly_hours' => 4.00,
            'effective_source' => 'OFFICIAL',
            'category' => 'INTRAKURIKULER',
            'status' => 'ACTIVE'
        ]);
        $this->structureId = $db->insertID();
    }

    /**
     * Test version creation, assignments save, matrix calculation, validation, and workflow transitions.
     */
    public function testAssignmentsWorkflowAndCalculations()
    {
        $versionModel = new AssignmentVersionModel();
        
        // 1. Create version in DRAFT status
        $vId = $versionModel->insert([
            'uuid' => '00000000-0000-0000-0000-000000000300',
            'academic_period_id' => $this->periodId,
            'curriculum_version_id' => $this->curriculumVersionId,
            'code' => 'TP-GANJIL-V1',
            'name' => 'Penugasan Ganjil V1',
            'workflow_status' => 'DRAFT',
            'revision_number' => 1
        ]);

        $this->assertGreaterThan(0, $vId);

        // 2. Query matrix - should show UNASSIGNED subject
        $matrix = AssignmentMatrixService::getMatrix($vId);
        $this->assertNotEmpty($matrix);
        $this->assertEquals('UNASSIGNED', $matrix[0]['validation_status']);

        // 3. Save teaching assignment
        $assignmentModel = new TeachingAssignmentModel();
        $aId = $assignmentModel->insert([
            'uuid' => '00000000-0000-0000-0000-000000000400',
            'assignment_version_id' => $vId,
            'curriculum_structure_id' => $this->structureId,
            'academic_period_id' => $this->periodId,
            'unit_id' => $this->smpId,
            'grade_level_id' => $matrix[0]['classroom_id'] ? 1 : 1,
            'classroom_id' => $this->classId,
            'subject_id' => $this->subjectId,
            'teacher_id' => $this->teacherId,
            'assignment_role' => 'PRIMARY',
            'assigned_weekly_hours' => 4.00,
            'workload_weekly_hours' => 4.00,
            'source_weekly_hours' => 4.00,
            'is_primary_teacher' => 1,
            'status' => 'ACTIVE'
        ]);

        $this->assertGreaterThan(0, $aId);

        // 4. Query matrix again - should show MATCHED
        $matrixAfter = AssignmentMatrixService::getMatrix($vId);
        $this->assertEquals('MATCHED', $matrixAfter[0]['validation_status']);

        // 5. Test validation engine
        $isValid = AssignmentValidationService::validate($vId);
        $this->assertTrue($isValid); // Should be completely valid now

        // 6. Test workload policy setup & calculation
        $policyModel = new WorkloadPolicyModel();
        $policyId = $policyModel->insert([
            'uuid' => '00000000-0000-0000-0000-000000000500',
            'academic_period_id' => $this->periodId,
            'unit_id' => $this->smpId,
            'minimum_teaching_hours' => 18.00,
            'maximum_total_hours' => 30.00,
            'is_active' => 1,
            'priority' => 1
        ]);

        $calc = TeacherWorkloadCalculationService::calculate($this->teacherId, $vId, $this->periodId, $this->smpId);
        // The teacher has only 4 JP assigned. Underload threshold is 18 JP.
        $this->assertEquals('UNDERLOAD', $calc['status']);
        $this->assertEquals(14.00, $calc['shortage_hours']);

        // Add additional duty to bump workload
        $dutyType = (new AdditionalDutyTypeModel())->where('code', 'HOMEROOM_TEACHER')->first();
        $dutyModel = new TeacherAdditionalDutyModel();
        $dutyModel->insert([
            'uuid' => '00000000-0000-0000-0000-000000000600',
            'assignment_version_id' => $vId,
            'academic_period_id' => $this->periodId,
            'unit_id' => $this->smpId,
            'teacher_id' => $this->teacherId,
            'duty_type_id' => $dutyType['id'],
            'workload_hours' => 16.00,
            'status' => 'ACTIVE'
        ]);

        // Recalculate - total is now 4 + 16 = 20 JP, which is between 18 and 30
        $calcAfter = TeacherWorkloadCalculationService::calculate($this->teacherId, $vId, $this->periodId, $this->smpId);
        $this->assertEquals('WITHIN_TARGET', $calcAfter['status']);
        $this->assertEquals(0.00, $calcAfter['shortage_hours']);

        // 7. Test workflow transitions
        $res = AssignmentWorkflowService::transition($vId, 'VALIDATED', 1);
        $this->assertTrue($res);

        $res = AssignmentWorkflowService::transition($vId, 'REVIEWED', 2);
        $this->assertTrue($res);

        $res = AssignmentWorkflowService::transition($vId, 'APPROVED', 3);
        $this->assertTrue($res);

        $res = AssignmentWorkflowService::transition($vId, 'LOCKED', 4);
        $this->assertTrue($res);

        // Active locked version check
        $verObj = $versionModel->find($vId);
        $this->assertEquals(1, (int)$verObj['is_active']);
        $this->assertEquals('LOCKED', $verObj['workflow_status']);

        // 8. Test revision clone creation
        $newVId = AssignmentWorkflowService::cloneVersion($vId, 'TP-GANJIL-V1-REV1', 'Penugasan Ganjil V1 Rev 1', null, 'Request revisi jam mengajar');
        $this->assertGreaterThan(0, $newVId);

        $newVerObj = $versionModel->find($newVId);
        $this->assertEquals('DRAFT', $newVerObj['workflow_status']);
        $this->assertEquals($vId, $newVerObj['previous_version_id']);
        $this->assertEquals('Request revisi jam mengajar', $newVerObj['change_summary']);
    }
}
