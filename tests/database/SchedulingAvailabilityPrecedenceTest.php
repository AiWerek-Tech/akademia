<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\TeacherAvailabilityService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\IsolatedDatabaseTestTrait;
use Config\Database;

/** @internal */
final class SchedulingAvailabilityPrecedenceTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    public function testSpecificRuleOverridesWildcardAndUnavailableWinsTie(): void
    {
        $db = Database::connect($this->DBGroup);
        $now = date('Y-m-d H:i:s');
        $db->table('teachers')->insert([
            'uuid' => '23000000-0000-4000-8000-000000000001',
            'full_name' => 'Guru Availability',
            'normalized_name' => 'GURU AVAILABILITY',
            'created_at' => $now,
        ]);
        $teacherId = (int) $db->insertID();
        $db->table('academic_years')->insert([
            'uuid' => '23000000-0000-4000-8000-000000000002',
            'name' => '2030/2031',
            'start_date' => '2030-07-01',
            'end_date' => '2031-06-30',
            'created_at' => $now,
        ]);
        $yearId = (int) $db->insertID();
        $db->table('academic_periods')->insert([
            'uuid' => '23000000-0000-4000-8000-000000000003',
            'academic_year_id' => $yearId,
            'semester_number' => 1,
            'start_date' => '2030-07-01',
            'end_date' => '2030-12-31',
            'created_at' => $now,
        ]);
        $periodId = (int) $db->insertID();

        $db->table('teacher_availability_rules')->insertBatch([
            [
                'uuid' => '23000000-0000-4000-8000-000000000004',
                'teacher_id' => $teacherId,
                'academic_period_id' => $periodId,
                'day_of_week' => null,
                'slot_number' => null,
                'availability_status' => 'UNAVAILABLE',
                'created_at' => $now,
            ],
            [
                'uuid' => '23000000-0000-4000-8000-000000000005',
                'teacher_id' => $teacherId,
                'academic_period_id' => $periodId,
                'day_of_week' => 1,
                'slot_number' => 2,
                'availability_status' => 'AVAILABLE',
                'created_at' => $now,
            ],
        ]);

        $service = new TeacherAvailabilityService();
        $this->assertTrue($service->isTeacherAvailable($teacherId, $periodId, 1, 2));
        $this->assertFalse($service->isTeacherAvailable($teacherId, $periodId, 2, 2));

        $db->table('teacher_availability_rules')->insert([
            'uuid' => '23000000-0000-4000-8000-000000000006',
            'teacher_id' => $teacherId,
            'academic_period_id' => $periodId,
            'day_of_week' => 1,
            'slot_number' => 2,
            'availability_status' => 'UNAVAILABLE',
            'created_at' => $now,
        ]);
        $this->assertFalse($service->isTeacherAvailable($teacherId, $periodId, 1, 2));

        $smpId = (int) $db->table('school_units')->where('code', 'SMP')->get()->getRowArray()['id'];
        $smaId = (int) $db->table('school_units')->where('code', 'SMA')->get()->getRowArray()['id'];
        $gradeIds = [];
        $classIds = [];
        $versionIds = [];
        $dayIds = [];
        foreach ([[$smpId, '7'], [$smaId, '11']] as [$unitId, $code]) {
            $db->table('grade_levels')->insert([
                'uuid' => sprintf('23000000-0000-4000-8000-%012d', 100 + $unitId),
                'unit_id' => $unitId,
                'code' => $code,
                'name' => 'Kelas ' . $code,
                'created_at' => $now,
            ]);
            $gradeIds[$unitId] = (int) $db->insertID();
            $db->table('classrooms')->insert([
                'uuid' => sprintf('23000000-0000-4000-8000-%012d', 200 + $unitId),
                'academic_period_id' => $periodId,
                'unit_id' => $unitId,
                'grade_level_id' => $gradeIds[$unitId],
                'code' => 'ROOM-' . $unitId,
                'name' => 'Rombel ' . $unitId,
                'created_at' => $now,
            ]);
            $classIds[$unitId] = (int) $db->insertID();
        }
        $db->table('subjects')->insert([
            'uuid' => '23000000-0000-4000-8000-000000000007',
            'code' => 'TIME-OVERLAP',
            'name' => 'Uji Waktu',
            'created_at' => $now,
        ]);
        $subjectId = (int) $db->insertID();
        $db->table('curriculum_versions')->insert([
            'uuid' => '23000000-0000-4000-8000-000000000011',
            'academic_period_id' => $periodId,
            'code' => 'TIME-CURR',
            'name' => 'Time Curriculum',
            'created_at' => $now,
        ]);
        $curriculumId = (int) $db->insertID();
        $db->table('assignment_versions')->insert([
            'uuid' => '23000000-0000-4000-8000-000000000012',
            'academic_period_id' => $periodId,
            'curriculum_version_id' => $curriculumId,
            'code' => 'TIME-ASSIGN',
            'name' => 'Time Assignment',
            'created_at' => $now,
        ]);
        $assignmentId = (int) $db->insertID();
        foreach ([[$smpId, 0, 'TARGET'], [$smaId, 1, 'ACTIVE']] as [$unitId, $active, $suffix]) {
            $db->table('schedule_versions')->insert([
                'uuid' => sprintf('23000000-0000-4000-8000-%012d', 300 + $unitId),
                'academic_period_id' => $periodId,
                'unit_id' => $unitId,
                'curriculum_version_id' => $curriculumId,
                'assignment_version_id' => $assignmentId,
                'code' => 'TIME-' . $suffix,
                'name' => 'Time ' . $suffix,
                'workflow_status' => $active ? 'APPROVED' : 'DRAFT',
                'revision_number' => 1,
                'is_active' => $active,
            ]);
            $versionIds[$unitId] = (int) $db->insertID();
            $db->table('schedule_days')->insert([
                'uuid' => sprintf('23000000-0000-4000-8000-%012d', 400 + $unitId),
                'school_unit_id' => $unitId,
                'day_of_week' => 1,
                'day_name' => 'Senin',
                'is_school_day' => 1,
                'created_at' => $now,
            ]);
            $dayIds[$unitId] = (int) $db->insertID();
        }
        $db->table('schedule_day_slots')->insert([
            'uuid' => '23000000-0000-4000-8000-000000000008',
            'schedule_version_id' => $versionIds[$smpId],
            'day_id' => $dayIds[$smpId],
            'slot_number' => 1,
            'start_time' => '07:30:00',
            'end_time' => '08:15:00',
            'slot_type' => 'LESSON',
            'created_at' => $now,
        ]);
        $db->table('schedule_day_slots')->insert([
            'uuid' => '23000000-0000-4000-8000-000000000009',
            'schedule_version_id' => $versionIds[$smaId],
            'day_id' => $dayIds[$smaId],
            'slot_number' => 3,
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'slot_type' => 'LESSON',
            'created_at' => $now,
        ]);
        $otherSlotId = (int) $db->insertID();
        $db->table('schedule_entries')->insert([
            'uuid' => '23000000-0000-4000-8000-000000000010',
            'schedule_version_id' => $versionIds[$smaId],
            'day_slot_id' => $otherSlotId,
            'schedule_requirement_id' => null,
            'classroom_id' => $classIds[$smaId],
            'teacher_id' => $teacherId,
            'subject_id' => $subjectId,
            'created_at' => $now,
        ]);

        $occupancy = $service->getCrossUnitTeacherScheduleOccupancy(
            $teacherId,
            $periodId,
            1,
            1,
            $versionIds[$smpId]
        );
        $this->assertCount(1, $occupancy, 'Waktu tumpang tindih harus terdeteksi walau nomor JP berbeda.');
    }
}
