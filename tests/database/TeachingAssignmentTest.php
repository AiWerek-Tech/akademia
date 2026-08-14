<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\IsolatedDatabaseTestTrait;
use App\Models\TeachingAssignmentModel;
use App\Models\AssignmentVersionModel;
use App\Services\AssignmentValidationService;
use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone2MasterSeeder;
use App\Database\Seeds\Milestone3CurriculumSeeder;
use App\Database\Seeds\Milestone4Seeder;
use Config\Database;

/**
 * @internal
 */
final class TeachingAssignmentTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    private int $smpId = 1;
    private int $periodId = 1;
    private int $teacherId = 1;
    private int $curriculumVersionId = 1;
    private int $subjectId = 1;
    private int $classId = 1;
    private int $structureId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        (new Milestone2MasterSeeder(new Database()))->run();
        (new Milestone3CurriculumSeeder(new Database()))->run();
        (new Milestone4Seeder(new Database()))->run();

        $db = Database::connect($this->DBGroup);

        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        $this->smpId = $smp ? (int)$smp['id'] : 1;

        // 1. Ensure academic year
        $year = $db->table('academic_years')->get()->getRowArray();
        if (!$year) {
            $db->table('academic_years')->insert([
                'uuid'       => '00000000-0000-0000-0000-000000000001',
                'name'       => '2026/2027',
                'start_date' => '2026-07-01',
                'end_date'   => '2027-06-30',
                'status'     => 'ACTIVE',
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $yearId = $db->insertID();
        } else {
            $yearId = (int)$year['id'];
        }

        // 2. Ensure academic period
        $period = $db->table('academic_periods')->get()->getRowArray();
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

        // 3. Ensure curriculum version
        $curriculum = $db->table('curriculum_versions')->get()->getRowArray();
        if (!$curriculum) {
            $db->table('curriculum_versions')->insert([
                'uuid'               => '00000000-0000-0000-0000-000000000200',
                'academic_period_id' => $this->periodId,
                'code'               => 'CURR-TEST-VER',
                'name'               => 'Kurikulum Test Version',
                'workflow_status'    => 'APPROVED',
                'is_active'          => 1,
                'created_at'         => date('Y-m-d H:i:s'),
            ]);
            $this->curriculumVersionId = $db->insertID();
        } else {
            $this->curriculumVersionId = (int)$curriculum['id'];
        }

        $teacher = $db->table('teachers')->get()->getRowArray();
        if (!$teacher) {
            $db->table('teachers')->insert([
                'uuid'            => '00000000-0000-0000-0000-000000000001',
                'primary_unit_id' => $this->smpId,
                'nip'             => '199001012020011001',
                'full_name'       => 'Guru Test',
                'is_active'       => 1,
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
            $this->teacherId = $db->insertID();
        } else {
            $this->teacherId = (int)$teacher['id'];
        }

        $tAccess = $db->table('teacher_unit_assignments')
                      ->where('teacher_id', $this->teacherId)
                      ->where('unit_id', $this->smpId)
                      ->get()
                      ->getRowArray();
        if (!$tAccess) {
            $db->table('teacher_unit_assignments')->insert([
                'teacher_id'      => $this->teacherId,
                'unit_id'         => $this->smpId,
                'assignment_type' => 'HOME_UNIT',
                'status'          => 'ACTIVE',
                'is_primary'      => 1,
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
        }

        $grade = $db->table('grade_levels')->get()->getRowArray();
        if (!$grade) {
            $db->table('grade_levels')->insert([
                'uuid'         => '00000000-0000-0000-0000-000000000001',
                'unit_id'      => $this->smpId,
                'grade_number' => 7,
                'code'         => 'VII',
                'name'         => 'Kelas VII',
                'phase'        => 'D',
                'sort_order'   => 1,
                'is_active'    => 1,
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
            $gradeId = $db->insertID();
        } else {
            $gradeId = (int)$grade['id'];
        }

        $classroom = $db->table('classrooms')->get()->getRowArray();
        if (!$classroom) {
            $db->table('classrooms')->insert([
                'uuid'               => '00000000-0000-0000-0000-000000000001',
                'unit_id'            => $this->smpId,
                'academic_period_id' => $this->periodId,
                'grade_level_id'     => $gradeId,
                'code'               => '7A',
                'name'               => 'VII-A',
                'is_active'          => 1,
                'created_at'         => date('Y-m-d H:i:s'),
            ]);
            $this->classId = $db->insertID();
        } else {
            $this->classId = (int)$classroom['id'];
        }

        $subject = $db->table('subjects')->get()->getRowArray();
        if (!$subject) {
            $db->table('subjects')->insert([
                'uuid'       => '00000000-0000-0000-0000-000000000001',
                'code'       => 'MAT',
                'name'       => 'Matematika',
                'category'   => 'UMUM',
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->subjectId = $db->insertID();
        } else {
            $this->subjectId = (int)$subject['id'];
        }

        // Create structure slot in curriculum
        $db->table('curriculum_structures')->insert([
            'curriculum_version_id' => $this->curriculumVersionId,
            'unit_id'               => $this->smpId,
            'grade_level_id'        => $gradeId,
            'classroom_id'          => $this->classId,
            'subject_id'            => $this->subjectId,
            'official_weekly_hours' => 4.00,
            'effective_weekly_hours'=> 4.00,
            'effective_source'      => 'OFFICIAL',
            'category'              => 'INTRAKURIKULER',
            'status'                => 'ACTIVE'
        ]);
        $this->structureId = $db->insertID();
    }

    public function testSingleTeacherAssignmentCreationAndValidation(): void
    {
        $versionModel = new AssignmentVersionModel();
        $vId = $versionModel->insert([
            'uuid'                  => '00000000-0000-0000-0000-000000000310',
            'academic_period_id'    => $this->periodId,
            'curriculum_version_id' => $this->curriculumVersionId,
            'code'                  => 'ASS-SINGLE-01',
            'name'                  => 'Single Teacher Test',
            'workflow_status'       => 'DRAFT',
            'revision_number'       => 1
        ]);

        $assignmentModel = new TeachingAssignmentModel();
        $aId = $assignmentModel->insert([
            'uuid'                    => '00000000-0000-0000-0000-000000000410',
            'assignment_version_id'   => $vId,
            'curriculum_structure_id' => $this->structureId,
            'academic_period_id'      => $this->periodId,
            'unit_id'                 => $this->smpId,
            'grade_level_id'          => 1,
            'classroom_id'            => $this->classId,
            'subject_id'              => $this->subjectId,
            'teacher_id'              => $this->teacherId,
            'assignment_role'         => 'PRIMARY',
            'assigned_weekly_hours'   => 4.00,
            'workload_weekly_hours'   => 4.00,
            'source_weekly_hours'     => 4.00,
            'is_primary_teacher'      => 1,
            'status'                  => 'ACTIVE'
        ]);

        $this->assertGreaterThan(0, $aId);
        $isValid = AssignmentValidationService::validate($vId);
        $this->assertTrue($isValid);
    }
}
