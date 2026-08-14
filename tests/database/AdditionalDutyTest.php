<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\IsolatedDatabaseTestTrait;
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
final class AdditionalDutyTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    private int $smpId = 1;
    private int $periodId = 1;
    private int $versionId = 1;

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
                'uuid'                  => '00000000-0000-0000-0000-000000000331',
                'academic_period_id'    => $this->periodId,
                'curriculum_version_id' => $currId,
                'code'                  => 'VER-DUTY-01',
                'name'                  => 'Version Duty Test',
                'workflow_status'       => 'DRAFT',
                'revision_number'       => 1
            ]);
            $this->versionId = $db->insertID();
        } else {
            $this->versionId = (int)$version['id'];
        }
    }

    public function testAdditionalDutyTypesSeededWithNullWorkload(): void
    {
        $typeModel = new AdditionalDutyTypeModel();
        $duty = $typeModel->where('code', 'HOMEROOM_TEACHER')->first();

        $this->assertNotNull($duty);
        $this->assertEquals('Wali Kelas', $duty['name']);
        $this->assertEquals('STRUKTURAL', $duty['category']);
    }

    public function testTeacherAdditionalDutyAssignment(): void
    {
        $db = Database::connect($this->DBGroup);
        $dutyType = (new AdditionalDutyTypeModel())->where('code', 'HOMEROOM_TEACHER')->first();
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
            $teacherId = $db->insertID();
        } else {
            $teacherId = (int)$teacher['id'];
        }

        $dutyModel = new TeacherAdditionalDutyModel();
        $dId = $dutyModel->insert([
            'uuid'                  => '00000000-0000-0000-0000-000000000330',
            'assignment_version_id' => $this->versionId,
            'academic_period_id'    => $this->periodId,
            'unit_id'               => $this->smpId,
            'teacher_id'            => $teacherId,
            'duty_type_id'          => $dutyType['id'],
            'title_override'        => 'Wali Kelas VII-A',
            'workload_hours'        => 12.00,
            'status'                => 'ACTIVE'
        ]);

        $this->assertGreaterThan(0, $dId);
        $duty = $dutyModel->find($dId);
        $this->assertEquals(12.00, (float)$duty['workload_hours']);
    }
}
