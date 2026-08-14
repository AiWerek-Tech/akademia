<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\AttendanceService;
use App\Services\UuidService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\IsolatedDatabaseTestTrait;

final class IntegratedAttendanceWorkflowTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;
    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    public function testMorningSubjectAndJournalShareOneValidatedLedger(): void
    {
        $db=Database::connect($this->DBGroup);$now=date('Y-m-d H:i:s');
        $unit=$db->table('school_units')->where('code','SMP')->get()->getRowArray();$unitId=(int)$unit['id'];
        $db->table('academic_years')->insert(['uuid'=>UuidService::v4(),'name'=>'2050/2051','start_date'=>'2050-07-01','end_date'=>'2051-06-30','created_at'=>$now]);$yearId=(int)$db->insertID();
        $db->table('academic_periods')->insert(['uuid'=>UuidService::v4(),'academic_year_id'=>$yearId,'semester_number'=>1,'name'=>'Ganjil','start_date'=>'2050-07-01','end_date'=>'2050-12-31','created_at'=>$now]);$periodId=(int)$db->insertID();
        $db->table('grade_levels')->insert(['uuid'=>UuidService::v4(),'unit_id'=>$unitId,'code'=>'VII-ATT','name'=>'VII Attendance','created_at'=>$now]);$gradeId=(int)$db->insertID();
        $db->table('teachers')->insert(['uuid'=>UuidService::v4(),'full_name'=>'Guru Attendance','normalized_name'=>'guru attendance','employment_status'=>'ACTIVE','primary_unit_id'=>$unitId,'is_active'=>1,'created_at'=>$now]);$teacherId=(int)$db->insertID();
        $db->table('classrooms')->insert(['uuid'=>UuidService::v4(),'academic_period_id'=>$periodId,'unit_id'=>$unitId,'grade_level_id'=>$gradeId,'code'=>'VII-ATT','name'=>'Kelas VII Attendance','homeroom_teacher_id'=>$teacherId,'created_at'=>$now]);$classId=(int)$db->insertID();
        $db->table('subjects')->insert(['uuid'=>UuidService::v4(),'code'=>'ATT-SUB','name'=>'Mapel Attendance','normalized_name'=>'mapel attendance','created_at'=>$now]);$subjectId=(int)$db->insertID();
        $studentIds=[];foreach(['Alpha','Beta'] as $index=>$name){$db->table('elective_students')->insert(['uuid'=>UuidService::v4(),'unit_id'=>$unitId,'academic_year_id'=>$yearId,'classroom_id'=>$classId,'student_number'=>'ATT-'.($index+1),'full_name'=>$name,'current_grade'=>7,'is_active'=>1,'created_at'=>$now,'updated_at'=>$now]);$studentIds[]=(int)$db->insertID();}

        $db->table('curriculum_versions')->insert(['uuid'=>UuidService::v4(),'academic_period_id'=>$periodId,'code'=>'CUR-ATT','name'=>'Kurikulum Attendance','workflow_status'=>'APPROVED','created_at'=>$now]);$curriculumId=(int)$db->insertID();
        $db->table('assignment_versions')->insert(['uuid'=>UuidService::v4(),'academic_period_id'=>$periodId,'curriculum_version_id'=>$curriculumId,'code'=>'ASS-ATT','name'=>'Tugas Attendance','workflow_status'=>'APPROVED','created_at'=>$now]);$assignmentVersionId=(int)$db->insertID();
        $db->table('schedule_versions')->insert(['uuid'=>UuidService::v4(),'academic_period_id'=>$periodId,'unit_id'=>$unitId,'curriculum_version_id'=>$curriculumId,'assignment_version_id'=>$assignmentVersionId,'code'=>'SCH-ATT','name'=>'Jadwal Attendance','workflow_status'=>'APPROVED','revision_number'=>1,'created_at'=>$now]);$scheduleVersionId=(int)$db->insertID();
        $date='2050-07-04';$dayNumber=(int)date('N',strtotime($date));
        $db->table('schedule_days')->insert(['uuid'=>UuidService::v4(),'school_unit_id'=>$unitId,'day_of_week'=>$dayNumber,'day_name'=>'Hari Uji','is_school_day'=>1,'created_at'=>$now]);$dayId=(int)$db->insertID();
        $db->table('schedule_day_slots')->insert(['uuid'=>UuidService::v4(),'schedule_version_id'=>$scheduleVersionId,'day_id'=>$dayId,'slot_number'=>1,'start_time'=>'08:00:00','end_time'=>'08:40:00','slot_type'=>'LESSON','created_at'=>$now]);$slotId=(int)$db->insertID();
        $db->table('schedule_entries')->insert(['uuid'=>UuidService::v4(),'schedule_version_id'=>$scheduleVersionId,'day_slot_id'=>$slotId,'classroom_id'=>$classId,'teacher_id'=>$teacherId,'subject_id'=>$subjectId,'created_at'=>$now]);

        $service=new AttendanceService();
        $workspace=$service->getDailyWorkspace($teacherId,$periodId,$date,$unitId);
        $this->assertCount(1,$workspace['lessons']);$this->assertCount(1,$workspace['morning']);$this->assertSame('Mapel Attendance',$workspace['lessons'][0]['subject_name']);
        $base=['unit_id'=>$unitId,'academic_period_id'=>$periodId,'classroom_id'=>$classId,'teacher_id'=>$teacherId,'attendance_date'=>$date,'meeting_number'=>1,'status'=>'SUBMITTED'];
        $morning=$service->saveSession($base+['session_type'=>'MORNING_ASSEMBLY','subject_id'=>null,'topic'=>'Apel Pagi','source_key'=>$service->buildSourceKey('MORNING_ASSEMBLY',$periodId,$classId,$date,null,null,1)], [
            ['student_id'=>$studentIds[0],'status'=>'HADIR','notes'=>''],['student_id'=>$studentIds[1],'status'=>'SAKIT','notes'=>'Surat orang tua'],
        ],1);
        $this->assertSame('MORNING_ASSEMBLY',$morning['session']['session_type']);
        $this->assertSame('Guru Attendance',$morning['session']['teacher_name']);
        $suggested=$service->getSuggestedRoster($classId,$date,'SUBJECT');
        $this->assertSame('SAKIT',$suggested[1]['suggested_status']);

        $subject=$service->saveSession($base+['session_type'=>'SUBJECT','subject_id'=>$subjectId,'topic'=>'Persamaan','learning_objectives'=>'Memahami persamaan','learning_activity'=>'Diskusi','assessment_summary'=>'Kuis','follow_up'=>'Remedial','source_key'=>$service->buildSourceKey('SUBJECT',$periodId,$classId,$date,$subjectId,null,1)], [
            ['student_id'=>$studentIds[0],'status'=>'TERLAMBAT','late_minutes'=>8,'arrival_time'=>'08:08','notes'=>'Transportasi'],
            ['student_id'=>$studentIds[1],'status'=>'SAKIT','notes'=>'Dikonfirmasi dari apel','source_session_id'=>$morning['session']['id']],
        ],1);
        $this->assertSame('SUBJECT',$subject['session']['session_type']);
        $this->assertSame(1,$subject['counts']['TERLAMBAT']);
        $this->assertSame('Memahami persamaan',$subject['session']['learning_objectives']);
        helper('auth');
        session()->set(['logged_in'=>true,'user_id'=>1,'teacher_id'=>$teacherId,'role_code'=>'guru','role_name'=>'Guru']);
        $indexHtml=view('teacher_portal/attendance_index',[
            'title'=>'Absensi','activePeriod'=>['name'=>'Ganjil','start_date'=>'2050-07-01','end_date'=>'2050-12-31'],
            'unitScope'=>['selected'=>'all','label'=>'Semua Unit','units'=>[['id'=>$unitId,'code'=>'SMP','name'=>'SMP']]],'selectedDate'=>$date,
            'workspace'=>['morning'=>[],'classroom'=>[],'lessons'=>[],'afternoon'=>[],'calendar_day'=>null],
            'recentSessions'=>[],
        ]);
        $this->assertStringContainsString('ABSENSI TERPADU',$indexHtml);
        $formHtml=view('teacher_portal/attendance_form',[
            'title'=>'Presensi','activePeriod'=>['start_date'=>'2050-07-01','end_date'=>'2050-12-31'],
            'session'=>$subject['session'],'sessionType'=>'SUBJECT','sessionTypeLabel'=>'Absensi Mata Pelajaran',
            'sourceKey'=>$subject['session']['source_key'],'scheduleEntryId'=>0,'roster'=>$subject['roster'],'counts'=>$subject['counts'],
            'classroom'=>['name'=>'Kelas VII Attendance','unit_name'=>'SMP'],'subject'=>['name'=>'Mapel Attendance'],
            'classroomId'=>$classId,'subjectId'=>$subjectId,'date'=>$date,'meetingNum'=>1,
            'teachingAssignments'=>[],'homerooms'=>[],'isLocked'=>false,
        ]);
        $this->assertStringContainsString('Jurnal pembelajaran',$formHtml);
        $this->expectException(\RuntimeException::class);
        $service->saveSession($base+['session_type'=>'SUBJECT','subject_id'=>$subjectId,'topic'=>'Duplikat','source_key'=>$subject['session']['source_key']], [
            ['student_id'=>$studentIds[0],'status'=>'HADIR'],['student_id'=>$studentIds[1],'status'=>'HADIR'],
        ],1);
    }
}
