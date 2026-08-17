<?php

namespace App\Database\Seeds;

use App\Services\AuditService;
use App\Services\UuidService;
use CodeIgniter\Database\Seeder;
use RuntimeException;

class Phase2KspPilotSeeder extends Seeder
{
    public function run()
    {
        (new DigitalKspSeeder($this->config))->run();
        (new NumeracyImprovementActionSeeder($this->config))->run();
        $unit=$this->db->table('school_units')->where('code','SMP')->get()->getRowArray();
        $version=$this->db->table('ksp_versions')->where(['unit_id'=>$unit['id'],'code'=>'KSP-SMP-2026-2027'])->get()->getRowArray();
        if(!$version) throw new RuntimeException('Pilot KSP SMP tidak ditemukan.');
        $period=$this->db->table('academic_periods')->where('id',$version['academic_period_id'])->get()->getRowArray();
        if(trim((string)$period['name'])==='')$this->db->table('academic_periods')->where('id',$period['id'])->update(['name'=>'Semester Ganjil 2026/2027','updated_at'=>date('Y-m-d H:i:s')]);
        $now=date('Y-m-d H:i:s'); $this->db->transBegin();
        try {
            $context=$this->firstOrInsert('school_context_snapshots',['ksp_version_id'=>$version['id'],'title'=>'Karakteristik SMP Advent Sogokmo'],['context_type'=>'LOCAL_CONTEXT','summary'=>'Sekolah mengembangkan numerasi kontekstual melalui literasi finansial yang sesuai lingkungan belajar SMP.','finding'=>'Capaian numerasi memerlukan penguatan aktivitas kontekstual.','source_type'=>'INTERNAL_ASSESSMENT','source_reference'=>'pilot://phase2/context'],$now);
            $vision=$this->firstOrInsert('ksp_vision_mission_goals',['ksp_version_id'=>$version['id'],'statement_type'=>'VISION'],['statement'=>'Menjadi komunitas belajar yang berkarakter, bernalar kritis, dan cakap menghadapi konteks kehidupan nyata.','sort_order'=>1],$now);
            $mission=$this->firstOrInsert('ksp_vision_mission_goals',['ksp_version_id'=>$version['id'],'statement_type'=>'MISSION'],['statement'=>'Menyelenggarakan pembelajaran kontekstual, kolaboratif, aman, dan berbasis bukti.','sort_order'=>2],$now);
            $goal=$this->firstOrInsert('ksp_vision_mission_goals',['ksp_version_id'=>$version['id'],'statement_type'=>'GOAL'],['statement'=>'Mencapai penguasaan target numerasi minimal 80% melalui integrasi literasi finansial pada Matematika VII–VIII.','measure'=>'Persentase peserta didik mencapai target penguasaan numerasi','target_value'=>'≥80%','sort_order'=>3],$now);
            foreach([
                ['INTRACURRICULAR','REGULAR','Matematika VII–VIII','Pembelajaran numerasi dan literasi finansial terintegrasi dalam kegiatan intrakurikuler.'],
                ['COCURRICULAR','PROJECT','Proyek Anggaran Kontekstual','Proyek lintas konteks untuk merencanakan dan mengevaluasi anggaran sederhana.'],
                ['EXTRACURRICULAR','CLUB','Klub Numerasi','Pengayaan numerasi berbasis masalah kehidupan sehari-hari.'],
            ] as $org) $this->firstOrInsert('ksp_learning_organizations',['ksp_version_id'=>$version['id'],'category'=>$org[0]],['delivery_model'=>$org[1],'title'=>$org[2],'description'=>$org[3]],$now);
            foreach($this->db->table('ksp_section_statuses')->where('ksp_version_id',$version['id'])->get()->getResultArray() as $section) {
                if($version['status']==='DRAFT' && ((int)$section['completion_percent']!==100 || !$section['notes'])) $this->db->table('ksp_section_statuses')->where('id',$section['id'])->update(['completion_percent'=>100,'status'=>'COMPLETE','notes'=>'Pilot Phase 2: bagian telah diisi dari data terstruktur dan ditinjau untuk final closure.','revision_number'=>(int)$section['revision_number']+1,'updated_at'=>$now]);
            }
            $evaluation=$this->db->table('ksp_evaluations')->where(['ksp_version_id'=>$version['id'],'objective'=>'Peningkatan capaian numerasi'])->get()->getRowArray();
            $action=$this->db->table('improvement_actions')->where('ksp_evaluation_id',$evaluation['id'])->get()->getRowArray();
            $section=$this->db->table('ksp_section_statuses')->where(['ksp_version_id'=>$version['id'],'section_code'=>'LEARNING_PLAN'])->get()->getRowArray();
            foreach([
                ['SCHOOL_CONTEXT',$context['uuid'],'RAPOR_PENDIDIKAN','Ringkasan indikator pilot','2026-08-01','Data agregat sintetis untuk acceptance; tidak memuat data peserta didik.','pilot://phase2/rapor-pendidikan'],
                ['VISION_MISSION_GOAL',$goal['uuid'],'MEETING_DECISION','Rapat pengembangan KSP','2026-08-05','Keputusan pilot penyelarasan tujuan numerasi.','pilot://phase2/meeting-goal'],
                ['EVALUATION',$evaluation['uuid'],'INTERNAL_ASSESSMENT','Evaluasi numerasi internal','2026-08-07','Ringkasan evaluasi agregat sintetis untuk pilot.','pilot://phase2/evaluation'],
                ['IMPROVEMENT_ACTION',$action['uuid'],'TEACHER_OBSERVATION','Observasi guru Matematika','2026-08-10','Observasi kebutuhan aktivitas numerasi kontekstual.','pilot://phase2/action'],
                ['KSP_SECTION',$section['uuid'],'LOCAL_CONTEXT','Konteks perencanaan pembelajaran','2026-08-12','Referensi konteks lokal untuk rancangan pembelajaran.','pilot://phase2/learning-plan'],
            ] as $e) $this->firstOrInsertEvidence($version,$e,$now);
            $regulation=$this->firstOrInsert('regulations',['code'=>'IALOS-KSP-QA'],['title'=>'IALOS Digital KSP Phase 2 Quality Standard','authority'=>'WMVAA internal governance','regulation_type'=>'SCHOOL_POLICY','status'=>'ACTIVE','published_at'=>'2026-08-17','effective_from'=>'2026-08-17','description'=>'Kebijakan mutu internal non-legal untuk acceptance Digital KSP Phase 2.'],$now);
            $regVersion=$this->db->table('regulation_versions')->where(['regulation_id'=>$regulation['id'],'version_number'=>1])->get()->getRowArray();
            if(!$regVersion){$row=['uuid'=>UuidService::v4(),'regulation_id'=>$regulation['id'],'version_number'=>1,'document_url'=>'docs/ialos/phase2/ksp-compliance.md','document_hash'=>hash('sha256','IALOS-KSP-QA-v1-2026-08-17'),'mime_type'=>'text/markdown','notes'=>'Internal quality rule provenance; not a legal source.','is_published'=>1,'published_at'=>$now,'created_at'=>$now];$this->db->table('regulation_versions')->insert($row);$regVersion=$row+['id'=>$this->db->insertID()];}
            foreach([
                ['SP-KSP-LEARNING-PLAN-001','SCHOOL_POLICY','COMPLETENESS','WARNING','SECTION_MIN_PERCENT',['minimum_percent'=>100],['section_code'=>'LEARNING_PLAN'],'Lengkapi bagian Learning Planning sampai 100%.'],
                ['GL-KSP-EVIDENCE-001','GUIDELINE','EVIDENCE','WARNING','EVIDENCE_COUNT_MIN',['minimum_count'=>5],[],'Tambahkan evidence lintas elemen KSP dengan provenance yang jelas.'],
                ['SP-KSP-ACTION-001','SCHOOL_POLICY','IMPROVEMENT','ERROR','ENTITY_COUNT_MIN',['minimum_count'=>1],['entity'=>'IMPROVEMENT_ACTION'],'Tetapkan minimal satu improvement action dari hasil evaluasi.'],
            ] as $rule) $this->firstOrInsertRule($regVersion,$rule,$now);
            if($this->db->transStatus()===false) throw new RuntimeException('Pilot Phase 2 gagal disimpan.');
            $this->db->transCommit(); AuditService::log('ksp','SEED_PHASE2_PILOT','KspVersion',(int)$version['id'],null,['code'=>$version['code'],'synthetic_evidence'=>true],'Phase 2 closure pilot',$version['uuid']);
        } catch(\Throwable $e){$this->db->transRollback();throw $e;}
    }

    private function firstOrInsert(string $table,array $key,array $data,string $now): array
    {
        $row=$this->db->table($table)->where($key)->get()->getRowArray(); if($row)return $row;
        $record=['uuid'=>UuidService::v4()]+$key+$data+['revision_number'=>1,'created_at'=>$now];
        if(in_array($table,['regulations'],true)) unset($record['revision_number']);
        $this->db->table($table)->insert($record); return $record+['id'=>$this->db->insertID()];
    }

    private function firstOrInsertEvidence(array $version,array $e,string $now): void
    {
        $key=['ksp_version_id'=>$version['id'],'target_type'=>$e[0],'target_uuid'=>$e[1],'evidence_type'=>$e[2],'reference_uri'=>$e[6]];
        if($this->db->table('ksp_evidence')->where($key)->countAllResults()===0)$this->db->table('ksp_evidence')->insert(['uuid'=>UuidService::v4()]+$key+['source_name'=>$e[3],'evidence_date'=>$e[4],'description'=>$e[5],'created_at'=>$now]);
    }

    private function firstOrInsertRule(array $version,array $r,string $now): void
    {
        $key=['regulation_version_id'=>$version['id'],'rule_code'=>$r[0]]; if($this->db->table('regulation_rules')->where($key)->countAllResults()>0)return;
        $this->db->table('regulation_rules')->insert(['uuid'=>UuidService::v4()]+$key+['rule_class'=>$r[1],'category'=>$r[2],'severity'=>$r[3],'evaluator_key'=>$r[4],'expected_json'=>json_encode($r[5]),'parameters_json'=>json_encode($r[6]),'suggested_action'=>$r[7],'is_active'=>1,'created_at'=>$now]);
    }
}
