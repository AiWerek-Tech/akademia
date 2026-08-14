<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\IsolatedDatabaseTestTrait;
use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone5Seeder;
use App\Models\ScheduleEntryModel;
use App\Services\ScheduleConflictDetectionService;
use App\Services\TeacherAvailabilityService;
use Config\Database;

/**
 * @internal
 */
final class ScheduleConflictDetectionTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

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

        $db->table('teachers')->insert([
            'uuid'            => '10000000-0000-4000-8000-000000000098',
            'full_name'       => 'Guru Konflik Jadwal',
            'normalized_name' => 'guru konflik jadwal',
            'employment_status' => 'ACTIVE',
            'primary_unit_id' => $smpId,
            'is_active'       => 1,
            'created_at'      => $now,
        ]);
        $teacherId = (int)$db->insertID();

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
            'unit_id'               => $smpId,
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

        $db->table('schedule_day_slots')->insert([
            'uuid'                => '10000000-0000-4000-8000-000000000032',
            'schedule_version_id' => $versionId,
            'day_id'              => $dayId,
            'slot_number'         => 2,
            'start_time'          => '08:15:00',
            'end_time'            => '09:00:00',
            'slot_type'           => 'LESSON',
            'created_at'          => $now,
        ]);
        $secondDaySlotId = (int)$db->insertID();

        $db->table('schedule_requirements')->insert([
            'uuid'                       => '10000000-0000-4000-8000-000000000040',
            'schedule_version_id'        => $versionId,
            'classroom_id'               => $class1Id,
            'subject_id'                 => $subjectId,
            'teacher_id'                 => $teacherId,
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
            'teacher_id'              => $teacherId,
            'subject_id'              => $subjectId,
            'created_at'              => $now,
        ]);

        // A third placement for a two-hour requirement must be a blocking
        // overscheduling conflict even though no weekly hour is unmet.
        $db->table('schedule_entries')->insert([
            'uuid'                    => '10000000-0000-4000-8000-000000000053',
            'schedule_version_id'     => $versionId,
            'day_slot_id'             => $secondDaySlotId,
            'schedule_requirement_id' => $reqId,
            'classroom_id'            => $class1Id,
            'teacher_id'              => $teacherId,
            'subject_id'              => $subjectId,
            'created_at'              => $now,
        ]);

        $db->table('schedule_entries')->insert([
            'uuid'                    => '10000000-0000-4000-8000-000000000052',
            'schedule_version_id'     => $versionId,
            'day_slot_id'             => $daySlotId,
            'schedule_requirement_id' => $reqId,
            'classroom_id'            => $class2Id,
            'teacher_id'              => $teacherId,
            'subject_id'              => $subjectId,
            'created_at'              => $now,
        ]);

        $detector = new ScheduleConflictDetectionService();
        $report   = $detector->detectConflicts($versionId);

        $this->assertGreaterThan(0, $report['total_conflicts']);
        $this->assertGreaterThan(0, $report['critical_conflicts']);
        $this->assertContains('OVERSCHEDULED_HOURS', array_column($report['conflicts'], 'conflict_type'));

        $storedAfterFirstAudit = $db->table('schedule_conflicts')
            ->where('schedule_version_id', $versionId)->countAllResults();
        $firstRows = $db->table('schedule_conflicts')
            ->where('schedule_version_id', $versionId)->get()->getResultArray();
        $fingerprints = array_column($firstRows, 'fingerprint');
        $this->assertNotEmpty($fingerprints);
        $this->assertCount(count($fingerprints), array_unique($fingerprints));
        foreach ($firstRows as $row) {
            $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $row['fingerprint']);
            $this->assertNotEmpty($row['conflict_code']);
            $this->assertSame('ACTIVE', $row['status']);
        }

        $detector->detectConflicts($versionId);
        $this->assertSame($storedAfterFirstAudit, $db->table('schedule_conflicts')
            ->where('schedule_version_id', $versionId)->countAllResults());
        $this->assertSame($storedAfterFirstAudit, $db->table('schedule_conflicts')
            ->where('schedule_version_id', $versionId)->where('is_resolved', 0)->countAllResults());

        $reopenTarget = $firstRows[0];
        $db->table('schedule_conflicts')->where('id', $reopenTarget['id'])->update([
            'is_resolved' => 1,
            'status'      => 'RESOLVED',
        ]);
        $detector->detectConflicts($versionId);
        $reopened = $db->table('schedule_conflicts')->where('id', $reopenTarget['id'])->get()->getRowArray();
        $this->assertSame($reopenTarget['fingerprint'], $reopened['fingerprint']);
        $this->assertSame('ACTIVE', $reopened['status']);
        $this->assertSame('0', (string) $reopened['is_resolved']);

        // A latest DRAFT schedule in another unit is already a real occupancy
        // during planning and must not be ignored merely because is_active=0.
        $sma = $db->table('school_units')->where('code', 'SMA')->get()->getRowArray();
        $smaId = (int) $sma['id'];
        $db->table('grade_levels')->insert([
            'uuid' => '10000000-0000-4000-8000-000000000060', 'unit_id' => $smaId,
            'code' => '10', 'name' => 'Kelas 10', 'created_at' => $now,
        ]);
        $smaGradeId = (int) $db->insertID();
        $db->table('classrooms')->insert([
            'uuid' => '10000000-0000-4000-8000-000000000061', 'academic_period_id' => $apId,
            'unit_id' => $smaId, 'grade_level_id' => $smaGradeId, 'code' => '10A',
            'name' => 'Kelas 10A', 'created_at' => $now,
        ]);
        $smaClassId = (int) $db->insertID();
        $db->table('schedule_versions')->insert([
            'uuid' => '10000000-0000-4000-8000-000000000062', 'academic_period_id' => $apId,
            'curriculum_version_id' => $cvId, 'assignment_version_id' => $avId, 'unit_id' => $smaId,
            'code' => 'SCH-SMA-DRAFT', 'name' => 'SMA Draft Terbaru', 'workflow_status' => 'DRAFT',
            'revision_number' => 3, 'is_active' => 0, 'created_by' => $userId, 'updated_by' => $userId,
        ]);
        $smaVersionId = (int) $db->insertID();
        $db->table('schedule_days')->insert([
            'uuid' => '10000000-0000-4000-8000-000000000063', 'school_unit_id' => $smaId,
            'day_of_week' => 1, 'day_name' => 'Senin', 'is_school_day' => 1, 'created_at' => $now,
        ]);
        $smaDayId = (int) $db->insertID();
        $db->table('schedule_day_slots')->insert([
            'uuid' => '10000000-0000-4000-8000-000000000064', 'schedule_version_id' => $smaVersionId,
            'day_id' => $smaDayId, 'slot_number' => 1, 'start_time' => '07:30:00',
            'end_time' => '08:15:00', 'slot_type' => 'LESSON', 'created_at' => $now,
        ]);
        $smaSlotId = (int) $db->insertID();
        $db->table('schedule_entries')->insert([
            'uuid' => '10000000-0000-4000-8000-000000000065', 'schedule_version_id' => $smaVersionId,
            'day_slot_id' => $smaSlotId, 'classroom_id' => $smaClassId, 'teacher_id' => $teacherId,
            'subject_id' => $subjectId, 'created_at' => $now,
        ]);
        $crossUnit = (new TeacherAvailabilityService())->getCrossUnitTeacherScheduleOccupancy(
            $teacherId, $apId, 1, 1, $versionId
        );
        $this->assertCount(1, $crossUnit);
        $this->assertSame((string) $smaVersionId, (string) $crossUnit[0]['schedule_version_id']);

        // A temporary substitute is the physical scheduling resource. Looking
        // up the absent teacher must therefore see the substitute's SMA slot.
        $db->table('teachers')->insert([
            'uuid' => '10000000-0000-4000-8000-000000000066',
            'full_name' => 'Guru Cuti Sementara',
            'normalized_name' => 'GURU CUTI SEMENTARA',
            'primary_unit_id' => $smpId,
            'is_active' => 1,
            'created_at' => $now,
        ]);
        $absentTeacherId = (int) $db->insertID();
        $db->table('teacher_schedule_substitutions')->insert([
            'uuid' => '10000000-0000-4000-8000-000000000067',
            'academic_period_id' => $apId,
            'absent_teacher_id' => $absentTeacherId,
            'substitute_teacher_id' => $teacherId,
            'effective_from' => date('Y-m-d', strtotime('-1 day')),
            'effective_to' => date('Y-m-d', strtotime('+2 months')),
            'status' => 'ACTIVE',
            'created_at' => $now,
        ]);
        $substitutedCrossUnit = (new TeacherAvailabilityService())->getCrossUnitTeacherScheduleOccupancy(
            $absentTeacherId, $apId, 1, 1, $versionId
        );
        $this->assertCount(1, $substitutedCrossUnit);
        $this->assertSame((string) $smaVersionId, (string) $substitutedCrossUnit[0]['schedule_version_id']);
    }
}
