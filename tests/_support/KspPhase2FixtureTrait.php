<?php

namespace Tests\Support;

use App\Database\Seeds\Phase2KspPilotSeeder;
use Config\Database;

trait KspPhase2FixtureTrait
{
    protected function seedKspPhase2Fixture(): array
    {
        $this->seedEducationFoundationFixture();
        (new Phase2KspPilotSeeder(new Database()))->run();
        $unit=$this->db->table('school_units')->where('code','SMP')->get()->getRowArray();
        $period=$this->db->table('academic_periods ap')->select('ap.*')->join('academic_years ay','ay.id=ap.academic_year_id')->where(['ay.name'=>'2026/2027','ap.semester_number'=>1])->get()->getRowArray();
        $version=$this->db->table('ksp_versions')->where(['unit_id'=>$unit['id'],'academic_period_id'=>$period['id'],'code'=>'KSP-SMP-2026-2027'])->get()->getRowArray();
        // Services intentionally commit their own transactions. Reset only the
        // reusable test pilot so migrateOnce/seedOnce cannot leak workflow or
        // immutable output state between test methods.
        $this->db->table('ksp_generated_documents')->where('ksp_version_id',$version['id'])->delete();
        $runIds=array_column($this->db->table('ksp_compliance_runs')->select('id')->where('ksp_version_id',$version['id'])->get()->getResultArray(),'id');
        if($runIds)$this->db->table('ksp_compliance_results')->whereIn('compliance_run_id',$runIds)->delete();
        $this->db->table('ksp_compliance_runs')->where('ksp_version_id',$version['id'])->delete();
        $this->db->table('ksp_versions')->where('id',$version['id'])->update(['status'=>'DRAFT','revision_number'=>1,'reviewed_by'=>null,'reviewed_at'=>null,'approved_by'=>null,'approved_at'=>null,'locked_by'=>null,'locked_at'=>null,'updated_by'=>null,'updated_at'=>null]);
        $version=$this->db->table('ksp_versions')->where('id',$version['id'])->get()->getRowArray();
        $role=$this->db->table('roles')->where('code','super_admin')->get()->getRowArray();
        if($this->db->table('user_roles')->where(['user_id'=>1,'role_id'=>$role['id']])->countAllResults()===0)$this->db->table('user_roles')->insert(['user_id'=>1,'role_id'=>$role['id'],'unit_id'=>$unit['id'],'created_at'=>date('Y-m-d H:i:s')]);
        if($this->db->table('user_unit_access')->where(['user_id'=>1,'unit_id'=>$unit['id']])->countAllResults()===0)$this->db->table('user_unit_access')->insert(['user_id'=>1,'unit_id'=>$unit['id'],'is_default'=>0,'created_at'=>date('Y-m-d H:i:s')]);
        session()->set(['logged_in'=>true,'user_id'=>1,'role_code'=>'super_admin','all_role_codes'=>['super_admin'],'active_unit_id'=>(int)$unit['id'],'active_period_id'=>(int)$period['id'],'must_change_password'=>0]);
        return ['unit'=>$unit,'period'=>$period,'version'=>$version,'session'=>session()->get()];
    }

    protected function completeWorkflow(string $uuid): array
    {
        $version=\App\Services\DigitalKspService::byUuid($uuid);
        foreach(['REVIEW','APPROVED','LOCKED'] as $target){$version=\App\Services\DigitalKspService::transition($uuid,$target,(int)$version['revision_number']);}
        return $version;
    }
}
