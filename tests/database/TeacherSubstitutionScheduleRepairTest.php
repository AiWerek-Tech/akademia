<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\TeacherSubstitutionScheduleRepairService;
use App\Services\ScheduleConflictDetectionService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\IsolatedDatabaseTestTrait;

/** @internal */
final class TeacherSubstitutionScheduleRepairTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;
    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    public function testGeneratesMinimalCrossUnitRepairAndAppliesAtomically(): void
    {
        $f = $this->fixture();
        $service = new TeacherSubstitutionScheduleRepairService();
        $preview = $service->generate($f['substitution'], $f['user']);

        $this->assertSame('READY', $preview['candidate']['status']);
        $this->assertSame(1, (int) $preview['candidate']['critical_before']);
        $this->assertSame(0, (int) $preview['candidate']['critical_after']);
        $this->assertCount(1, $preview['changes']); // chooses the truly minimal free-slot move

        $result = $service->apply((int) $preview['candidate']['id'], $f['substitution'], $f['user']);
        $this->assertSame(1, $result['moved_entries']);
        $db = Database::connect($this->DBGroup);
        $absent = $db->table('schedule_entries')->where('id', $f['absent_entry'])->get()->getRowArray();
        $this->assertSame((string) $f['absent'], (string) $absent['teacher_id']);
        $this->assertSame('APPLIED', $db->table('teacher_substitution_repair_candidates')->where('id', $preview['candidate']['id'])->get()->getRowArray()['status']);
        $this->assertSame(0, (new ScheduleConflictDetectionService())->detectConflicts($f['smp_version'])['critical_conflicts']);
    }

    public function testRejectsStaleCandidateWithoutMovingAnyEntry(): void
    {
        $f = $this->fixture();
        $service = new TeacherSubstitutionScheduleRepairService();
        $preview = $service->generate($f['substitution'], $f['user']);
        $db = Database::connect($this->DBGroup);
        $db->table('schedule_versions')->where('id', $f['smp_version'])->update(['revision_number' => 99]);

        try {
            $service->apply((int) $preview['candidate']['id'], $f['substitution'], $f['user']);
            $this->fail('Kandidat kedaluwarsa seharusnya ditolak.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('kedaluwarsa', $e->getMessage());
        }
        $entry = $db->table('schedule_entries')->where('id', $f['absent_entry'])->get()->getRowArray();
        $this->assertSame((string) $f['monday_slot'], (string) $entry['day_slot_id']);
        $this->assertSame('READY', $db->table('teacher_substitution_repair_candidates')->where('id', $preview['candidate']['id'])->get()->getRowArray()['status']);
    }

    private function fixture(): array
    {
        $db = Database::connect($this->DBGroup); $now = date('Y-m-d H:i:s');
        $userRow = $db->table('users')->select('id')->get()->getRowArray();
        if (!$userRow) {
            $db->table('users')->insert(['uuid'=>'30000000-0000-4000-8000-000000000001','username'=>'repair_test','full_name'=>'Repair Test','password_hash'=>password_hash('TestPass123!',PASSWORD_BCRYPT),'is_active'=>1,'must_change_password'=>0,'created_at'=>$now]);
            $user = (int) $db->insertID();
        } else {
            $user = (int) $userRow['id'];
        }
        $units = $db->table('school_units')->whereIn('code', ['SMP','SMA'])->get()->getResultArray();
        $unitByCode=[];foreach($units as $u)$unitByCode[$u['code']]=(int)$u['id'];
        $teachers=[];foreach(['Absent','Substitute','Other'] as $i=>$name){$db->table('teachers')->insert(['uuid'=>sprintf('31000000-0000-4000-8000-%012d',$i+1),'full_name'=>$name,'normalized_name'=>strtoupper($name),'primary_unit_id'=>$unitByCode[$i===1?'SMA':'SMP'],'is_active'=>1,'created_at'=>$now]);$teachers[]=(int)$db->insertID();}
        [$absent,$substitute,$other]=$teachers;
        $db->table('academic_years')->insert(['uuid'=>'31000000-0000-4000-8000-000000000010','name'=>'2042/2043','start_date'=>'2042-07-01','end_date'=>'2043-06-30','created_at'=>$now]);$year=(int)$db->insertID();
        $db->table('academic_periods')->insert(['uuid'=>'31000000-0000-4000-8000-000000000011','academic_year_id'=>$year,'semester_number'=>1,'name'=>'Ganjil','start_date'=>'2042-07-01','end_date'=>'2042-12-31','created_at'=>$now]);$period=(int)$db->insertID();
        $db->table('curriculum_versions')->insert(['uuid'=>'31000000-0000-4000-8000-000000000012','academic_period_id'=>$period,'code'=>'CUR-REPAIR','name'=>'Repair','created_at'=>$now]);$curr=(int)$db->insertID();
        $db->table('assignment_versions')->insert(['uuid'=>'31000000-0000-4000-8000-000000000013','academic_period_id'=>$period,'curriculum_version_id'=>$curr,'code'=>'ASS-REPAIR','name'=>'Repair','created_at'=>$now]);$assign=(int)$db->insertID();
        $db->table('subjects')->insert(['uuid'=>'31000000-0000-4000-8000-000000000014','code'=>'TST-R','name'=>'Mapel Uji','created_at'=>$now]);$subject=(int)$db->insertID();
        $class=[];$grade=[];foreach(['SMP','SMA'] as $i=>$code){$db->table('grade_levels')->insert(['uuid'=>sprintf('31000000-0000-4000-8000-%012d',20+$i),'unit_id'=>$unitByCode[$code],'code'=>'G'.$i,'name'=>'Grade '.$i,'created_at'=>$now]);$grade[$code]=(int)$db->insertID();$db->table('classrooms')->insert(['uuid'=>sprintf('31000000-0000-4000-8000-%012d',30+$i),'academic_period_id'=>$period,'unit_id'=>$unitByCode[$code],'grade_level_id'=>$grade[$code],'code'=>'C'.$i,'name'=>'Class '.$code,'created_at'=>$now]);$class[$code]=(int)$db->insertID();}
        $version=[];foreach(['SMP','SMA'] as $i=>$code){$db->table('schedule_versions')->insert(['uuid'=>sprintf('31000000-0000-4000-8000-%012d',40+$i),'academic_period_id'=>$period,'unit_id'=>$unitByCode[$code],'curriculum_version_id'=>$curr,'assignment_version_id'=>$assign,'code'=>'SCH-'.$code,'name'=>'Schedule '.$code,'workflow_status'=>'DRAFT','revision_number'=>1,'is_active'=>1,'created_by'=>$user,'updated_by'=>$user]);$version[$code]=(int)$db->insertID();}
        $slots=[];foreach(['SMP','SMA'] as $code){foreach([[1,'Senin'],[2,'Selasa']] as $d){$db->table('schedule_days')->insert(['uuid'=>sprintf('31000000-0000-4000-8%03d-%012d',$version[$code],$d[0]+($code==='SMA'?100:0)),'school_unit_id'=>$unitByCode[$code],'day_of_week'=>$d[0],'day_name'=>$d[1],'is_school_day'=>1,'created_at'=>$now]);$day=(int)$db->insertID();$db->table('schedule_day_slots')->insert(['uuid'=>sprintf('32000000-0000-4000-8%03d-%012d',$version[$code],$d[0]+($code==='SMA'?100:0)),'schedule_version_id'=>$version[$code],'day_id'=>$day,'slot_number'=>1,'start_time'=>'07:30:00','end_time'=>'08:15:00','slot_type'=>'LESSON','created_at'=>$now]);$slots[$code][$d[0]]=(int)$db->insertID();}}
        $req=[];foreach([[$version['SMP'],$class['SMP'],$absent],[$version['SMP'],$class['SMP'],$other],[$version['SMA'],$class['SMA'],$substitute]] as $i=>$x){$db->table('schedule_requirements')->insert(['uuid'=>sprintf('33000000-0000-4000-8000-%012d',$i+1),'schedule_version_id'=>$x[0],'classroom_id'=>$x[1],'subject_id'=>$subject,'teacher_id'=>$x[2],'required_weekly_hours'=>1,'created_at'=>$now]);$req[$i]=(int)$db->insertID();}
        $entryData=[[$version['SMP'],$slots['SMP'][1],$req[0],$class['SMP'],$absent],[$version['SMP'],$slots['SMP'][2],$req[1],$class['SMP'],$other],[$version['SMA'],$slots['SMA'][1],$req[2],$class['SMA'],$substitute]];$entryIds=[];foreach($entryData as $i=>$x){$db->table('schedule_entries')->insert(['uuid'=>sprintf('34000000-0000-4000-8000-%012d',$i+1),'schedule_version_id'=>$x[0],'day_slot_id'=>$x[1],'schedule_requirement_id'=>$x[2],'classroom_id'=>$x[3],'teacher_id'=>$x[4],'subject_id'=>$subject,'is_locked'=>0,'created_at'=>$now]);$entryIds[]=(int)$db->insertID();}
        $db->table('teacher_schedule_substitutions')->insert(['uuid'=>'35000000-0000-4000-8000-000000000001','academic_period_id'=>$period,'absent_teacher_id'=>$absent,'substitute_teacher_id'=>$substitute,'effective_from'=>date('Y-m-d',strtotime('-1 day')),'effective_to'=>date('Y-m-d',strtotime('+1 month')),'status'=>'ACTIVE','created_at'=>$now]);$substitution=(int)$db->insertID();
        return ['user'=>$user,'substitution'=>$substitution,'absent'=>$absent,'absent_entry'=>$entryIds[0],'monday_slot'=>$slots['SMP'][1],'tuesday_slot'=>$slots['SMP'][2],'smp_version'=>$version['SMP']];
    }
}
