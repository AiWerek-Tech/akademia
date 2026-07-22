<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Services\TeacherWorkloadCalculationService;
use App\Models\WorkloadPolicyModel;
use App\Models\TeachingAssignmentModel;
use App\Models\TeacherAdditionalDutyModel;
use App\Models\AdditionalDutyTypeModel;
use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone2MasterSeeder;
use App\Database\Seeds\Milestone3CurriculumSeeder;
use App\Database\Seeds\Milestone4Seeder;
use Config\Database;

/**
 * @internal
 */
final class WorkloadCalculationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    private int $smpId = 1;
    private int $periodId = 1;
    private int $teacherId = 1;
    private int $versionId = 1;
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

        $period = $db->table('academic_periods')->where('is_active', 1)->get()->getRowArray();
        if (!$period) {
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

        $teacher = $db->table('teachers')->where('is_active', 1)->get()->getRowArray();
        if (!$teacher) {
            $db->table('teachers')->insert([
                'uuid'            => '00000000-0000-0000-0000-000000000001',
                'primary_unit_id' => $this->smpId,
                'nip'             => '199001012020011001',
                'full_name'       => 'Guru Workload Test',
                'is_active'       => 1,
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
            $this->teacherId = $db->insertID();
        } else {
            $this->teacherId = (int)$teacher['id'];
        }

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
            $currId = $db->insertID();
        } else {
            $currId = (int)$curriculum['id'];
        }

        $version = $db->table('assignment_versions')->get()->getRowArray();
        if (!$version) {
            $db->table('assignment_versions')->insert([
                'uuid'                  => '00000000-0000-0000-0000-000000000361',
                'academic_period_id'    => $this->periodId,
                'curriculum_version_id' => $currId,
                'code'                  => 'VER-CALC-01',
                'name'                  => 'Version Calc Test',
                'workflow_status'       => 'DRAFT',
                'revision_number'       => 1
            ]);
            $this->versionId = $db->insertID();
        } else {
            $this->versionId = (int)$version['id'];
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
            $classId = $db->insertID();
        } else {
            $classId = (int)$classroom['id'];
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
            $subjectId = $db->insertID();
        } else {
            $subjectId = (int)$subject['id'];
        }

        $structure = $db->table('curriculum_structures')->get()->getRowArray();
        if (!$structure) {
            $db->table('curriculum_structures')->insert([
                'curriculum_version_id' => $currId,
                'unit_id'               => $this->smpId,
                'grade_level_id'        => $gradeId,
                'classroom_id'          => $classId,
                'subject_id'            => $subjectId,
                'official_weekly_hours' => 4.00,
                'effective_weekly_hours'=> 4.00,
                'effective_source'      => 'OFFICIAL',
                'category'              => 'INTRAKURIKULER',
                'status'                => 'ACTIVE'
            ]);
            $this->structureId = $db->insertID();
        } else {
            $this->structureId = (int)$structure['id'];
        }
    }

    public function testWorkloadCalculationUnderloadAndWithinTarget(): void
    {
        $policyModel = new WorkloadPolicyModel();
        $policyModel->insert([
            'uuid'                   => '00000000-0000-0000-0000-000000000350',
            'academic_period_id'     => $this->periodId,
            'unit_id'                => $this->smpId,
            'minimum_teaching_hours' => 18.00,
            'maximum_total_hours'    => 30.00,
            'is_active'              => 1,
            'priority'               => 1
        ]);

        // 1. Initial calculation with 0 hours assigned
        $calc = TeacherWorkloadCalculationService::calculate($this->teacherId, $this->versionId, $this->periodId, $this->smpId);
        $this->assertEquals('UNDERLOAD', $calc['status']);
        $this->assertEquals(18.00, $calc['shortage_hours']);

        // 2. Add assignment of 10 JP
        (new TeachingAssignmentModel())->insert([
            'uuid'                    => '00000000-0000-0000-0000-000000000450',
            'assignment_version_id'   => $this->versionId,
            'curriculum_structure_id' => $this->structureId,
            'academic_period_id'      => $this->periodId,
            'unit_id'                 => $this->smpId,
            'grade_level_id'          => 1,
            'classroom_id'            => 1,
            'subject_id'              => 1,
            'teacher_id'              => $this->teacherId,
            'assignment_role'         => 'PRIMARY',
            'assigned_weekly_hours'   => 10.00,
            'workload_weekly_hours'   => 10.00,
            'source_weekly_hours'     => 10.00,
            'is_primary_teacher'      => 1,
            'status'                  => 'ACTIVE'
        ]);

        // 3. Add duty of 10 JP
        $dutyType = (new AdditionalDutyTypeModel())->where('code', 'HOMEROOM_TEACHER')->first();
        (new TeacherAdditionalDutyModel())->insert([
            'uuid'                  => '00000000-0000-0000-0000-000000000550',
            'assignment_version_id' => $this->versionId,
            'academic_period_id'    => $this->periodId,
            'unit_id'               => $this->smpId,
            'teacher_id'            => $this->teacherId,
            'duty_type_id'          => $dutyType['id'],
            'workload_hours'        => 10.00,
            'status'                => 'ACTIVE'
        ]);

        // Recalculate: total is 10 + 10 = 20 JP, which is between 18 and 30
        $calcAfter = TeacherWorkloadCalculationService::calculate($this->teacherId, $this->versionId, $this->periodId, $this->smpId);
        $this->assertEquals('WITHIN_TARGET', $calcAfter['status']);
        $this->assertEquals(0.00, $calcAfter['shortage_hours']);
        $this->assertEquals(0.00, $calcAfter['overload_hours']);
    }
}
