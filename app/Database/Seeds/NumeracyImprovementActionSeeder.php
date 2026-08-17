<?php

namespace App\Database\Seeds;

use App\Services\AuditService;
use App\Services\UuidService;
use CodeIgniter\Database\Seeder;
use RuntimeException;

class NumeracyImprovementActionSeeder extends Seeder
{
    private const SECTIONS = ['CHARACTERISTICS','VISION_MISSION_GOALS','ORGANIZATION','INTRACURRICULAR','COCURRICULAR','EXTRACURRICULAR','LEARNING_PLAN','EVALUATION','APPENDICES'];

    public function run()
    {
        $unit=$this->db->table('school_units')->where(['code'=>'SMP','is_active'=>1])->get()->getRowArray();
        $period=$this->db->table('academic_periods ap')->select('ap.*')->join('academic_years ay','ay.id=ap.academic_year_id')->where(['ay.name'=>'2026/2027','ap.semester_number'=>1])->get()->getRowArray();
        if(!$unit || !$period) throw new RuntimeException('Unit SMP atau Semester Ganjil 2026/2027 tidak ditemukan.');
        $now=date('Y-m-d H:i:s'); $changed=false; $this->db->transBegin();
        try {
            $version=$this->db->table('ksp_versions')->where(['unit_id'=>$unit['id'],'academic_period_id'=>$period['id'],'code'=>'KSP-SMP-2026-2027'])->get()->getRowArray();
            if(!$version) {
                $record=['uuid'=>UuidService::v4(),'unit_id'=>$unit['id'],'academic_period_id'=>$period['id'],'code'=>'KSP-SMP-2026-2027','title'=>'KSP SMP Tahun Pelajaran 2026/2027','status'=>'DRAFT','revision_number'=>1,'created_at'=>$now];
                $this->db->table('ksp_versions')->insert($record); $version=$record+['id'=>$this->db->insertID()];
                foreach(self::SECTIONS as $section) $this->db->table('ksp_section_statuses')->insert(['uuid'=>UuidService::v4(),'ksp_version_id'=>$version['id'],'section_code'=>$section,'completion_percent'=>0,'status'=>'EMPTY','revision_number'=>1,'created_at'=>$now]);
                $changed=true;
            }
            $evaluation=$this->db->table('ksp_evaluations')->where(['ksp_version_id'=>$version['id'],'objective'=>'Peningkatan capaian numerasi'])->get()->getRowArray();
            if(!$evaluation) {
                $record=['uuid'=>UuidService::v4(),'ksp_version_id'=>$version['id'],'evaluation_period'=>'Semester Ganjil 2026/2027','objective'=>'Peningkatan capaian numerasi','target_value'=>'≥80% target mastery','finding'=>'Numeracy attainment below target.','root_cause'=>'Limited contextual numeracy activities.','decision'=>'INTEGRATE_FINANCIAL_LITERACY','status'=>'DRAFT','revision_number'=>1,'created_at'=>$now];
                $this->db->table('ksp_evaluations')->insert($record); $evaluation=$record+['id'=>$this->db->insertID()];
                $this->db->table('ksp_section_statuses')->where(['ksp_version_id'=>$version['id'],'section_code'=>'EVALUATION'])->update(['completion_percent'=>100,'status'=>'COMPLETE','revision_number'=>2,'updated_at'=>$now]);
                $changed=true;
            }
            $action=$this->db->table('improvement_actions')->where(['ksp_evaluation_id'=>$evaluation['id'],'title'=>'Integrate financial literacy activities into Mathematics VII–VIII.'])->get()->getRowArray();
            if(!$action) {
                $record=['uuid'=>UuidService::v4(),'ksp_evaluation_id'=>$evaluation['id'],'unit_id'=>$unit['id'],'title'=>'Integrate financial literacy activities into Mathematics VII–VIII.','description'=>'Embed contextual financial literacy tasks in Mathematics learning activities for Grades VII and VIII.','owner_role_code'=>'wakasek_kurikulum','due_date'=>'2026-10-31','success_indicator'=>'≥80% target mastery.','status'=>'IN_PROGRESS','revision_number'=>1,'created_at'=>$now];
                $this->db->table('improvement_actions')->insert($record); $action=$record+['id'=>$this->db->insertID()];
                $changed=true;
            }
            if($this->db->transStatus()===false) throw new RuntimeException('Data tindak lanjut numerasi gagal disimpan.');
            $this->db->transCommit();
            if($changed) AuditService::log('ksp','SEED_NUMERACY_IMPROVEMENT','ImprovementAction',(int)$action['id'],null,$action,'User-approved pilot record',$action['uuid']);
        } catch(\Throwable $e) { $this->db->transRollback(); throw $e; }
    }
}
