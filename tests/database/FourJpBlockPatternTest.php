<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\ScheduleConflictDetectionService;
use App\Services\UuidService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\IsolatedDatabaseTestTrait;

final class FourJpBlockPatternTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    public function testFourJpOnOneDayIsCriticalAndTwoByTwoIsValid(): void
    {
        $db = Database::connect($this->DBGroup);
        $now = date('Y-m-d H:i:s');
        $unit = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        $unitId = (int)$unit['id'];

        $db->table('academic_years')->insert(['uuid'=>UuidService::v4(),'name'=>'2044/2045','start_date'=>'2044-07-01','end_date'=>'2045-06-30','created_at'=>$now]);
        $yearId = (int)$db->insertID();
        $db->table('academic_periods')->insert(['uuid'=>UuidService::v4(),'academic_year_id'=>$yearId,'semester_number'=>1,'name'=>'Ganjil','start_date'=>'2044-07-01','end_date'=>'2044-12-31','created_at'=>$now]);
        $periodId = (int)$db->insertID();
        $db->table('curriculum_versions')->insert(['uuid'=>UuidService::v4(),'academic_period_id'=>$periodId,'code'=>'CUR-4JP','name'=>'Kurikulum 4 JP','created_at'=>$now]);
        $curriculumId = (int)$db->insertID();
        $db->table('assignment_versions')->insert(['uuid'=>UuidService::v4(),'academic_period_id'=>$periodId,'curriculum_version_id'=>$curriculumId,'code'=>'ASS-4JP','name'=>'Tugas 4 JP','created_at'=>$now]);
        $assignmentId = (int)$db->insertID();
        $db->table('grade_levels')->insert(['uuid'=>UuidService::v4(),'unit_id'=>$unitId,'code'=>'VIII-T','name'=>'Kelas VIII Test','created_at'=>$now]);
        $gradeId = (int)$db->insertID();
        $db->table('classrooms')->insert(['uuid'=>UuidService::v4(),'academic_period_id'=>$periodId,'unit_id'=>$unitId,'grade_level_id'=>$gradeId,'code'=>'VIII-T','name'=>'Kelas VIII Test','created_at'=>$now]);
        $classId = (int)$db->insertID();
        $db->table('subjects')->insert(['uuid'=>UuidService::v4(),'code'=>'IPA-4JP-T','name'=>'IPA Empat JP','short_name'=>'IPA','created_at'=>$now]);
        $subjectId = (int)$db->insertID();
        $db->table('teachers')->insert(['uuid'=>UuidService::v4(),'full_name'=>'Guru IPA Test','normalized_name'=>'guru ipa test','employment_status'=>'ACTIVE','primary_unit_id'=>$unitId,'is_active'=>1,'created_at'=>$now]);
        $teacherId = (int)$db->insertID();
        $db->table('schedule_versions')->insert(['uuid'=>UuidService::v4(),'academic_period_id'=>$periodId,'unit_id'=>$unitId,'curriculum_version_id'=>$curriculumId,'assignment_version_id'=>$assignmentId,'code'=>'SCH-4JP','name'=>'Jadwal 4 JP','workflow_status'=>'DRAFT','revision_number'=>1,'created_at'=>$now]);
        $versionId = (int)$db->insertID();

        $slots = [];
        foreach ([[1, 'Senin'], [4, 'Kamis']] as [$dayNumber, $dayName]) {
            $db->table('schedule_days')->insert(['uuid'=>UuidService::v4(),'school_unit_id'=>$unitId,'day_of_week'=>$dayNumber,'day_name'=>$dayName,'is_school_day'=>1,'created_at'=>$now]);
            $dayId = (int)$db->insertID();
            for ($slot = 1; $slot <= 4; $slot++) {
                $db->table('schedule_day_slots')->insert(['uuid'=>UuidService::v4(),'schedule_version_id'=>$versionId,'day_id'=>$dayId,'slot_number'=>$slot,'start_time'=>sprintf('%02d:00:00',7+$slot),'end_time'=>sprintf('%02d:40:00',7+$slot),'slot_type'=>'LESSON','created_at'=>$now]);
                $slots[$dayNumber][$slot] = (int)$db->insertID();
            }
        }
        $db->table('schedule_requirements')->insert(['uuid'=>UuidService::v4(),'schedule_version_id'=>$versionId,'classroom_id'=>$classId,'subject_id'=>$subjectId,'teacher_id'=>$teacherId,'required_weekly_hours'=>4,'consecutive_slots_required'=>1,'created_at'=>$now]);
        $requirementId = (int)$db->insertID();
        $entryIds = [];
        for ($slot = 1; $slot <= 4; $slot++) {
            $db->table('schedule_entries')->insert(['uuid'=>UuidService::v4(),'schedule_version_id'=>$versionId,'day_slot_id'=>$slots[1][$slot],'schedule_requirement_id'=>$requirementId,'classroom_id'=>$classId,'teacher_id'=>$teacherId,'subject_id'=>$subjectId,'created_at'=>$now]);
            $entryIds[] = (int)$db->insertID();
        }

        $detector = new ScheduleConflictDetectionService();
        $before = $detector->detectConflicts($versionId);
        $this->assertContains('FOUR_JP_BLOCK_PATTERN_INVALID', array_column($before['conflicts'], 'conflict_type'));

        $db->table('schedule_entries')->where('id', $entryIds[2])->update(['day_slot_id'=>$slots[4][1]]);
        $db->table('schedule_entries')->where('id', $entryIds[3])->update(['day_slot_id'=>$slots[4][2]]);
        $after = $detector->detectConflicts($versionId);
        $this->assertNotContains('FOUR_JP_BLOCK_PATTERN_INVALID', array_column($after['conflicts'], 'conflict_type'));
        $this->assertSame(0, (int)$after['critical_conflicts']);

        $db->table('schedule_fixed_activities')->insert([
            'uuid'=>UuidService::v4(),
            'schedule_version_id'=>$versionId,
            'day_slot_id'=>$slots[1][1],
            'school_unit_id'=>$unitId,
            'classroom_id'=>$classId,
            'title'=>'SID Test',
            'activity_type'=>'WORSHIP',
            'created_at'=>$now,
        ]);
        $withFixedActivity = $detector->detectConflicts($versionId);
        $this->assertContains('CLASS_FIXED_ACTIVITY_COLLISION', array_column($withFixedActivity['conflicts'], 'conflict_type'));

        $db->table('schedule_fixed_activities')->where('schedule_version_id', $versionId)->delete();
        $withoutFixedActivity = $detector->detectConflicts($versionId);
        $this->assertNotContains('CLASS_FIXED_ACTIVITY_COLLISION', array_column($withoutFixedActivity['conflicts'], 'conflict_type'));
    }
}
