<?php

namespace Tests\Support;

use App\Database\Seeds\EducationFoundationSeeder;
use App\Database\Seeds\Milestone2MasterSeeder;
use App\Database\Seeds\Milestone3CurriculumSeeder;
use Config\Database;

trait EducationFoundationFixtureTrait
{
    protected int $unitId;
    protected int $otherUnitId;
    protected int $periodId;
    protected int $versionId;
    protected int $subjectId;
    protected int $gradeId;

    protected function seedEducationFoundationFixture(): void
    {
        (new Milestone2MasterSeeder(new Database()))->run();
        (new Milestone3CurriculumSeeder(new Database()))->run();
        (new EducationFoundationSeeder(new Database()))->run();
        $db=Database::connect($this->DBGroup);
        $this->unitId=(int)$db->table('school_units')->where('code','SMA')->get()->getRowArray()['id'];
        $this->otherUnitId=(int)$db->table('school_units')->where('code','SMP')->get()->getRowArray()['id'];
        $period=$db->table('academic_periods')->where('is_active',1)->get()->getRowArray();
        if (!$period) {
            $year=$db->table('academic_years')->where('name','2026/2027')->get()->getRowArray();
            if (!$year) { $db->table('academic_years')->insert(['uuid'=>'10000000-0000-4000-8000-000000000010','name'=>'2026/2027','start_date'=>'2026-07-01','end_date'=>'2027-06-30','status'=>'APPROVED','is_active'=>1,'created_at'=>date('Y-m-d H:i:s')]); $year=['id'=>$db->insertID()]; }
            $db->table('academic_periods')->insert(['uuid'=>'10000000-0000-4000-8000-000000000011','academic_year_id'=>$year['id'],'semester_number'=>1,'name'=>'Ganjil 2026/2027','start_date'=>'2026-07-01','end_date'=>'2026-12-31','workflow_status'=>'OPEN','is_active'=>1,'created_at'=>date('Y-m-d H:i:s')]);
            $this->periodId=(int)$db->insertID();
        } else $this->periodId=(int)$period['id'];
        $grade=$db->table('grade_levels')->where(['unit_id'=>$this->unitId,'code'=>'X'])->get()->getRowArray();
        $this->gradeId=(int)$grade['id'];
        $subject=$db->table('subjects')->where('code','INF-X-TEST')->get()->getRowArray();
        if (!$subject) {
            $db->table('subjects')->insert(['uuid'=>'10000000-0000-4000-8000-000000000001','code'=>'INF-X-TEST','name'=>'Informatika X (Fixture)','short_name'=>'INF','category'=>'WAJIB','is_active'=>1,'created_at'=>date('Y-m-d H:i:s')]);
            $this->subjectId=(int)$db->insertID();
            $db->table('subject_unit_availability')->insert(['subject_id'=>$this->subjectId,'unit_id'=>$this->unitId,'is_available'=>1]);
        } else $this->subjectId=(int)$subject['id'];
        $version=$db->table('curriculum_versions')->where('code','IALOS-TEST')->get()->getRowArray();
        if (!$version) {
            $db->table('curriculum_versions')->insert(['uuid'=>'10000000-0000-4000-8000-000000000002','academic_period_id'=>$this->periodId,'code'=>'IALOS-TEST','name'=>'IALOS Controlled Test','revision_number'=>1,'workflow_status'=>'DRAFT','is_active'=>0,'created_at'=>date('Y-m-d H:i:s')]);
            $this->versionId=(int)$db->insertID();
        } else $this->versionId=(int)$version['id'];
        $user=$db->table('users')->where('id',1)->get()->getRowArray();
        if (!$user) {
            $db->table('users')->insert(['id'=>1,'uuid'=>'10000000-0000-4000-8000-000000000012','username'=>'ialos_test','email'=>'ialos@example.test','full_name'=>'IALOS Test','password_hash'=>password_hash('TestPass12345!',PASSWORD_BCRYPT),'is_active'=>1,'must_change_password'=>0,'created_at'=>date('Y-m-d H:i:s')]);
        }
        if ($db->table('user_unit_access')->where(['user_id'=>1,'unit_id'=>$this->unitId])->countAllResults()===0) $db->table('user_unit_access')->insert(['user_id'=>1,'unit_id'=>$this->unitId,'created_at'=>date('Y-m-d H:i:s')]);
        session()->set(['user_id'=>1,'active_unit_id'=>$this->unitId,'active_period_id'=>$this->periodId]);
    }

    protected function createTestOutcome(?string $code=null): array
    {
        $code ??= 'CP-' . strtoupper(substr(hash('sha256', static::class . ':' . $this->name()), 0, 10));
        return \App\Services\LearningOutcomeService::create(['curriculum_version_id'=>$this->versionId,'subject_id'=>$this->subjectId,'grade_level_id'=>$this->gradeId,'code'=>$code,'phase'=>'E','statement'=>'Peserta didik mampu menerapkan berpikir komputasional.']);
    }

    protected function createTestObjective(?array $outcome=null,?string $code=null): array
    {
        $outcome ??= $this->createTestOutcome();
        $code ??= 'TP-' . strtoupper(substr(hash('sha256', static::class . ':' . $this->name()), 0, 10));
        return \App\Services\LearningObjectiveService::createNational(['learning_outcome_id'=>$outcome['id'],'code'=>$code,'statement'=>'Menganalisis persoalan dengan dekomposisi.','status'=>'DRAFT']);
    }

}
