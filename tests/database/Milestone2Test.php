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
use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone2MasterSeeder;
use Config\Database;

/**
 * @internal
 */
final class Milestone2Test extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed milestone 2 master data
        $seeder = new Milestone2MasterSeeder(new \Config\Database());
        $seeder->run();

        $this->ensureSuperAdminExists();
    }

    private function ensureSuperAdminExists(): void
    {
        $db = Database::connect($this->DBGroup);
        
        // Ensure super_admin role exists
        $adminRole = $db->table('roles')->where('code', 'super_admin')->get()->getRowArray();
        if (!$adminRole) {
            $db->table('roles')->insert([
                'code' => 'super_admin',
                'name' => 'Super Admin',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $user = $db->table('users')->where('username', 'admin')->get()->getRowArray();
        if (!$user) {
            $db->table('users')->insert([
                'id'                   => 1,
                'uuid'                 => \App\Services\UuidService::v4(),
                'username'             => 'admin',
                'email'                => 'admin@test.com',
                'full_name'            => 'Super Admin',
                'password_hash'        => password_hash('SomePass12345!', PASSWORD_BCRYPT),
                'is_active'            => 1,
                'must_change_password' => 0,
                'created_at'           => date('Y-m-d H:i:s'),
            ]);
        }
        
        // Map user to super_admin role
        $db->table('user_roles')->where('user_id', 1)->delete();
        $db->table('user_roles')->insert([
            'user_id' => 1,
            'role_id' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        if ($smp) {
            $access = $db->table('user_unit_access')
                ->where('user_id', 1)
                ->where('unit_id', $smp['id'])
                ->get()
                ->getRowArray();
            if (!$access) {
                $db->table('user_unit_access')->insert([
                    'user_id'      => 1,
                    'unit_id'      => $smp['id'],
                    'access_level' => 'ADMIN',
                    'is_default'   => 1,
                    'created_at'   => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    private function getSuperAdminSession(): array
    {
        return [
            'logged_in'      => true,
            'user_id'        => 1,
            'username'       => 'admin',
            'role_code'      => 'super_admin',
            'active_unit_id' => 1,
            'permissions'    => [
                'teachers.view',
                'teachers.manage',
                'teachers.verify',
                'teachers.import',
                'teachers.export',
                'duplicates.view',
                'duplicates.resolve',
                'subjects.view',
                'subjects.manage',
                'grade_levels.view',
                'grade_levels.manage',
                'classrooms.view',
                'classrooms.manage',
                'rooms.view',
                'rooms.manage'
            ]
        ];
    }

    public function testTeacherCreationAndDuplicateDetection(): void
    {
        $db = Database::connect($this->DBGroup);
        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();

        // 1. Create a Teacher
        $teacherData = [
            'full_name'         => 'Budi Sudarsono',
            'nip'               => '198001012005011001',
            'gender'            => 'L',
            'employment_status' => 'GURU_TETAP',
            'primary_unit_id'   => $smp['id'],
        ];

        $teacherResult = TeacherService::createTeacher($teacherData);
        $this->assertIsArray($teacherResult);
        $teacherId = $teacherResult['teacher']['id'];

        $teacherModel = new TeacherModel();
        $teacher = $teacherModel->find($teacherId);
        $this->assertEquals('Budi Sudarsono', $teacher['full_name']);

        // 2. Scan for duplicates using Budi Sudarsono's name (similar name)
        $potentialDuplicate = [
            'full_name' => 'Budi Sudarsono M.Pd',
            'nip'       => '198001012005011001',
        ];

        $duplicates = TeacherDuplicateDetectionService::scanForDuplicates($potentialDuplicate);
        $this->assertNotEmpty($duplicates);
        $this->assertGreaterThanOrEqual(80, $duplicates[0]['score']);
    }

    public function testSubjectCreationAndAlias(): void
    {
        // Create Subject
        $subjectData = [
            'code'                    => 'BIN-SMP',
            'name'                    => 'Bahasa Indonesia',
            'short_name'              => 'B. Indo',
            'category'                => 'WAJIB',
            'counts_in_report'        => 1,
            'counts_as_teaching_load' => 1,
            'active'                  => 1,
        ];

        $subject = SubjectService::createSubject($subjectData);
        $this->assertIsArray($subject);
        $subjectId = $subject['id'];

        $subjectModel = new SubjectModel();
        $foundSubject = $subjectModel->find($subjectId);
        $this->assertEquals('BIN-SMP', $foundSubject['code']);
    }

    public function testRoomCreationAndValidation(): void
    {
        $db = Database::connect($this->DBGroup);
        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();

        // Create Room
        $roomData = [
            'code'                 => 'R701',
            'name'                 => 'Ruang Kelas VII A',
            'room_type_id'         => 1, // CLASSROOM
            'unit_id'              => $smp['id'],
            'shared_between_units' => 0,
            'capacity'             => 36,
            'is_active'            => 1,
        ];

        $room = RoomService::createRoom($roomData);
        $this->assertIsArray($room);
        $roomId = $room['id'];

        $roomModel = new RoomModel();
        $foundRoom = $roomModel->find($roomId);
        $this->assertEquals('R701', $foundRoom['code']);
    }

    public function testClassroomCopyPeriod(): void
    {
        $db = Database::connect($this->DBGroup);
        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        
        // Find existing grade level
        $grade = $db->table('grade_levels')->where('unit_id', $smp['id'])->get()->getRowArray();

        // Insert academic year
        $db->table('academic_years')->insert([
            'id' => 1,
            'uuid' => \App\Services\UuidService::v4(),
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'APPROVED',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Insert source period
        $db->table('academic_periods')->insert([
            'id' => 10,
            'uuid' => \App\Services\UuidService::v4(),
            'academic_year_id' => 1,
            'semester_number' => 1,
            'name' => '2026/2027 Ganjil',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'workflow_status' => 'OPEN',
            'is_active' => 1,
        ]);

        // Insert target period
        $db->table('academic_periods')->insert([
            'id' => 11,
            'uuid' => \App\Services\UuidService::v4(),
            'academic_year_id' => 1,
            'semester_number' => 2,
            'name' => '2026/2027 Genap',
            'start_date' => '2027-01-01',
            'end_date' => '2027-06-30',
            'workflow_status' => 'DRAFT',
            'is_active' => 0,
        ]);

        // Create classroom in period 10
        $classroomData = [
            'academic_period_id' => 10,
            'unit_id'            => $smp['id'],
            'grade_level_id'     => $grade['id'],
            'code'               => 'VII-A-TEST',
            'name'               => 'Kelas VII A Test',
            'capacity'           => 36,
            'is_active'          => 1,
        ];
        $classroom = ClassroomService::createClassroom($classroomData);
        $this->assertIsArray($classroom);
        $classId = $classroom['id'];

        // Perform Copy Period
        $result = ClassroomService::applyCopyPeriod(10, 11, $smp['id']);
        $this->assertEquals(1, $result['copied_count']);

        // Check if copy exists in target period 11
        $classroomModel = new ClassroomModel();
        $copiedClass = $classroomModel->where('academic_period_id', 11)->where('code', 'VII-A-TEST')->first();
        $this->assertNotEmpty($copiedClass);
        $this->assertEquals('Kelas VII A Test', $copiedClass['name']);
    }
}
