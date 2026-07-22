<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone5Seeder;
use App\Models\ScheduleEntryModel;
use App\Services\ScheduleConflictDetectionService;
use Config\Database;

/**
 * @internal
 */
final class ScheduleConflictDetectionTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    public function testConflictDetectionService(): void
    {
        $this->seed(Milestone5Seeder::class);
        $db = Database::connect($this->DBGroup);
        $now = date('Y-m-d H:i:s');

        // Insert test user
        $db->table('users')->insert([
            'uuid'          => '10000000-0000-4000-8000-000000000099',
            'username'      => 'testadmin',
            'full_name'     => 'Test Admin User',
            'password_hash' => password_hash('secret123', PASSWORD_BCRYPT),
            'is_active'     => 1,
            'created_at'    => $now,
        ]);
        $userId = (int)$db->insertID();

        // Insert academic year & period
        $db->table('academic_years')->insert([
            'uuid'       => '10000000-0000-4000-8000-000000000010',
            'name'       => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date'   => '2027-06-30',
            'is_active'  => 1,
            'created_at' => $now,
        ]);
        $ayId = (int)$db->insertID();

        $db->table('academic_periods')->insert([
            'uuid'             => '10000000-0000-4000-8000-000000000011',
            'academic_year_id' => $ayId,
            'semester_number'  => 1,
            'start_date'       => '2026-07-01',
            'end_date'         => '2026-12-31',
            'created_at'       => $now,
        ]);
        $apId = (int)$db->insertID();

        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        $smpId = (int)$smp['id'];

        $db->table('room_types')->insert([
            'code'       => 'CLASSROOM',
            'name'       => 'Ruang Kelas',
            'created_at' => $now,
        ]);
        $roomTypeId = (int)$db->insertID();

        $db->table('grade_levels')->insert([
            'uuid'       => '10000000-0000-4000-8000-000000000020',
            'unit_id'    => $smpId,
            'code'       => '7',
            'name'       => 'Kelas 7',
            'created_at' => $now,
        ]);
        $glId = (int)$db->insertID();

        $db->table('classrooms')->insert([
            'uuid'               => '10000000-0000-4000-8000-000000000021',
            'academic_period_id' => $apId,
            'unit_id'            => $smpId,
            'grade_level_id'     => $glId,
            'code'               => '7A',
            'name'               => 'Kelas 7A',
            'created_at'         => $now,
        ]);
        $class1Id = (int)$db->insertID();

        $db->table('classrooms')->insert([
            'uuid'               => '10000000-0000-4000-8000-000000000022',
            'academic_period_id' => $apId,
            'unit_id'            => $smpId,
            'grade_level_id'     => $glId,
            'code'               => '7B',
            'name'               => 'Kelas 7B',
            'created_at'         => $now,
        ]);
        $class2Id = (int)$db->insertID();

        $db->table('subjects')->insert([
            'uuid'       => '10000000-0000-4000-8000-000000000023',
            'code'       => 'MAT-7',
            'name'       => 'Matematika 7',
            'created_at' => $now,
        ]);
        $subjectId = (int)$db->insertID();

        $db->table('curriculum_versions')->insert([
            'uuid'               => '10000000-0000-4000-8000-000000000012',
            'academic_period_id' => $apId,
            'code'               => 'CURR-TEST',
            'name'               => 'Test Curriculum',
            'created_at'         => $now,
        ]);
        $cvId = (int)$db->insertID();

        $db->table('assignment_versions')->insert([
            'uuid'                  => '10000000-0000-4000-8000-000000000013',
            'academic_period_id'    => $apId,
            'curriculum_version_id' => $cvId,
            'code'                  => 'ASSIGN-TEST',
            'name'                  => 'Test Assignment Version',
            'created_at'            => $now,
        ]);
        $avId = (int)$db->insertID();

        $db->table('schedule_versions')->insert([
            'uuid'                  => '10000000-0000-4000-8000-000000000001',
            'academic_period_id'    => $apId,
            'curriculum_version_id' => $cvId,
            'assignment_version_id' => $avId,
            'code'                  => 'SCH-TEST-001',
            'name'                  => 'Test Schedule Version 1',
            'workflow_status'       => 'DRAFT',
            'revision_number'       => 1,
            'is_active'             => 0,
            'created_by'            => $userId,
            'updated_by'            => $userId,
        ]);
        $versionId = (int)$db->insertID();

        $db->table('schedule_days')->insert([
            'uuid'           => '10000000-0000-4000-8000-000000000030',
            'school_unit_id' => $smpId,
            'day_of_week'    => 1,
            'day_name'       => 'Senin',
            'is_school_day'  => 1,
            'created_at'     => $now,
        ]);
        $dayId = (int)$db->insertID();

        $db->table('schedule_day_slots')->insert([
            'uuid'                => "10000000-0000-4000-8000-000000000031",
            'schedule_version_id' => $versionId,
            'day_id'              => $dayId,
            'slot_number'         => 1,
            'start_time'          => '07:30:00',
            'end_time'            => '08:15:00',
            'slot_type'           => 'LESSON',
            'created_at'          => $now,
        ]);
        $daySlotId = (int)$db->insertID();

        $db->table('schedule_requirements')->insert([
            'uuid'                       => '10000000-0000-4000-8000-000000000040',
            'schedule_version_id'        => $versionId,
            'classroom_id'               => $class1Id,
            'subject_id'                 => $subjectId,
            'teacher_id'                 => $userId,
            'required_weekly_hours'      => 2.0,
            'consecutive_slots_required' => 1,
            'created_at'                 => $now,
        ]);
        $reqId = (int)$db->insertID();

        // Insert TWO conflicting entries for the SAME teacher on the SAME day_slot
        $db->table('schedule_entries')->insert([
            'uuid'                    => '10000000-0000-4000-8000-000000000051',
            'schedule_version_id'     => $versionId,
            'day_slot_id'             => $daySlotId,
            'schedule_requirement_id' => $reqId,
            'classroom_id'            => $class1Id,
            'teacher_id'              => $userId,
            'subject_id'              => $subjectId,
            'created_at'              => $now,
        ]);

        $db->table('schedule_entries')->insert([
            'uuid'                    => '10000000-0000-4000-8000-000000000052',
            'schedule_version_id'     => $versionId,
            'day_slot_id'             => $daySlotId,
            'schedule_requirement_id' => $reqId,
            'classroom_id'            => $class2Id,
            'teacher_id'              => $userId,
            'subject_id'              => $subjectId,
            'created_at'              => $now,
        ]);

        $detector = new ScheduleConflictDetectionService();
        $report   = $detector->detectConflicts($versionId);

        $this->assertGreaterThan(0, $report['total_conflicts']);
        $this->assertGreaterThan(0, $report['critical_conflicts']);
    }
}
