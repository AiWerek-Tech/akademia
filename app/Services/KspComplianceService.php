<?php

namespace App\Services;

use Config\Database;
use InvalidArgumentException;

class KspComplianceService
{
    public const RULE_CLASSES = ['LEGAL_REQUIRED','GUIDELINE','SCHOOL_POLICY'];
    public const RESULT_STATUSES = ['PASS','WARNING','FAIL','NOT_APPLICABLE'];
    private const EVALUATORS = ['SECTION_MIN_PERCENT','ENTITY_COUNT_MIN','EVIDENCE_COUNT_MIN','STATUS_IN'];

    public static function registerRule(string $regulationVersionUuid, array $data): array
    {
        EducationFoundationService::requireFields($data, ['rule_code','rule_class','category','severity','evaluator_key','expected','suggested_action']);
        $class = strtoupper(trim($data['rule_class']));
        $evaluator = strtoupper(trim($data['evaluator_key']));
        if (!in_array($class, self::RULE_CLASSES, true)) throw new InvalidArgumentException('Klasifikasi rule kepatuhan tidak valid.');
        if (!in_array($evaluator, self::EVALUATORS, true)) throw new InvalidArgumentException('Evaluator rule tidak diizinkan.');
        $db = Database::connect();
        $version = $db->table('regulation_versions rv')->select('rv.*,r.code AS regulation_code,r.regulation_type,r.status AS regulation_status')
            ->join('regulations r','r.id=rv.regulation_id')->where('rv.uuid',$regulationVersionUuid)->get()->getRowArray();
        if (!$version || (int)$version['is_published'] !== 1) throw new InvalidArgumentException('Rule hanya dapat ditautkan ke versi registry yang telah diterbitkan.');
        if ($class === 'LEGAL_REQUIRED' && strtoupper((string)$version['regulation_type']) !== 'LEGAL_REQUIRED') {
            throw new InvalidArgumentException('Rule legal wajib berasal dari entri Registry berjenis LEGAL_REQUIRED.');
        }
        $record = [
            'uuid'=>UuidService::v4(),'regulation_version_id'=>$version['id'],'rule_code'=>strtoupper(trim($data['rule_code'])),
            'rule_class'=>$class,'category'=>strtoupper(trim($data['category'])),'severity'=>strtoupper(trim($data['severity'])),
            'evaluator_key'=>$evaluator,'expected_json'=>self::json($data['expected']),
            'parameters_json'=>self::json($data['parameters'] ?? []),'suggested_action'=>trim($data['suggested_action']),
            'effective_from'=>$data['effective_from'] ?? null,'effective_until'=>$data['effective_until'] ?? null,
            'is_active'=>isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1,
            'created_by'=>EducationFoundationService::actorId(),'created_at'=>date('Y-m-d H:i:s'),
        ];
        $db->table('regulation_rules')->insert($record); $id=(int)$db->insertID();
        AuditService::log('ksp','REGISTER_COMPLIANCE_RULE','RegulationRule',$id,null,$record,null,$record['uuid']);
        return $db->table('regulation_rules')->where('id',$id)->get()->getRowArray();
    }

    public static function preview(string $kspUuid): array
    {
        $version = DigitalKspService::byUuid($kspUuid); $db=Database::connect();
        $period = $db->table('academic_periods')->where('id',$version['academic_period_id'])->get()->getRowArray();
        $asOf = $version['effective_from'] ?: ($period['start_date'] ?? date('Y-m-d'));
        $rules = $db->table('regulation_rules rr')->select('rr.*,rv.uuid AS regulation_version_uuid,rv.version_number,rv.document_url,rv.document_hash,r.code AS regulation_code,r.title AS regulation_title,r.authority')
            ->join('regulation_versions rv','rv.id=rr.regulation_version_id')->join('regulations r','r.id=rv.regulation_id')
            ->where(['rr.is_active'=>1,'rv.is_published'=>1])->groupStart()->where('rr.effective_from IS NULL')->orWhere('rr.effective_from <=',$asOf)->groupEnd()
            ->groupStart()->where('rr.effective_until IS NULL')->orWhere('rr.effective_until >=',$asOf)->groupEnd()->orderBy('rr.rule_class')->orderBy('rr.rule_code')->get()->getResultArray();
        $results=[]; $hasLegal=false;
        foreach($rules as $rule) {
            if ($rule['rule_class']==='LEGAL_REQUIRED') $hasLegal=true;
            $results[] = self::evaluate($version,$rule);
        }
        if (!$hasLegal) $results[] = self::legalRegistrySentinel();
        $counts=['PASS'=>0,'WARNING'=>0,'FAIL'=>0,'NOT_APPLICABLE'=>0];
        foreach($results as $result) $counts[$result['result_status']]++;
        $overall=$counts['FAIL']>0?'FAIL':($counts['WARNING']>0?'WARNING':(($counts['PASS']>0)?'PASS':'NOT_APPLICABLE'));
        $sourceRevision=self::sourceRevision($version,$rules);
        $db->transBegin();
        try {
            $run=['uuid'=>UuidService::v4(),'ksp_version_id'=>$version['id'],'source_revision'=>$sourceRevision,'overall_status'=>$overall,'total_count'=>count($results),'pass_count'=>$counts['PASS'],'warning_count'=>$counts['WARNING'],'fail_count'=>$counts['FAIL'],'not_applicable_count'=>$counts['NOT_APPLICABLE'],'generated_by'=>EducationFoundationService::actorId(),'generated_at'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')];
            $db->table('ksp_compliance_runs')->insert($run); $runId=(int)$db->insertID();
            foreach($results as $result) $db->table('ksp_compliance_results')->insert(['uuid'=>UuidService::v4(),'compliance_run_id'=>$runId]+$result+['created_at'=>date('Y-m-d H:i:s')]);
            AuditService::log('ksp','RUN_COMPLIANCE_PREVIEW','KspComplianceRun',$runId,null,$run,null,$run['uuid']);
            if($db->transStatus()===false) throw new \RuntimeException('Preview kepatuhan gagal disimpan.');
            $db->transCommit(); return self::runByUuid($run['uuid']);
        } catch(\Throwable $e) { $db->transRollback(); throw $e; }
    }

    public static function latest(string $kspUuid): ?array
    {
        $version=DigitalKspService::byUuid($kspUuid); $row=Database::connect()->table('ksp_compliance_runs')->where('ksp_version_id',$version['id'])->orderBy('id','DESC')->get()->getRowArray();
        return $row ? self::runByUuid($row['uuid']) : null;
    }

    private static function runByUuid(string $uuid): array
    {
        $db=Database::connect(); $run=$db->table('ksp_compliance_runs')->where('uuid',$uuid)->get()->getRowArray();
        return ['run'=>$run,'results'=>$db->table('ksp_compliance_results')->where('compliance_run_id',$run['id'])->orderBy('id')->get()->getResultArray()];
    }

    private static function evaluate(array $version,array $rule): array
    {
        $db=Database::connect(); $expected=json_decode($rule['expected_json'],true) ?: []; $params=json_decode($rule['parameters_json'] ?: '[]',true) ?: [];
        $actual=null; $passed=false; $applicable=true;
        switch($rule['evaluator_key']) {
            case 'SECTION_MIN_PERCENT':
                $code=strtoupper((string)($params['section_code']??'')); $row=$db->table('ksp_section_statuses')->where(['ksp_version_id'=>$version['id'],'section_code'=>$code])->get()->getRowArray();
                $applicable=(bool)$row; $actual=$row ? ['section_code'=>$code,'completion_percent'=>(int)$row['completion_percent']] : ['section_code'=>$code,'state'=>'MISSING'];
                $passed=$row && (int)$row['completion_percent'] >= (int)($expected['minimum_percent']??100); break;
            case 'ENTITY_COUNT_MIN':
                $map=['CONTEXT'=>'school_context_snapshots','GOAL'=>'ksp_vision_mission_goals','ORGANIZATION'=>'ksp_learning_organizations','EVALUATION'=>'ksp_evaluations','IMPROVEMENT_ACTION'=>'improvement_actions'];
                $entity=strtoupper((string)($params['entity']??'')); $table=$map[$entity]??null;
                if(!$table){$applicable=false;$actual=['entity'=>$entity,'state'=>'UNSUPPORTED'];break;}
                if($table==='improvement_actions') $count=$db->table('improvement_actions ia')->join('ksp_evaluations ke','ke.id=ia.ksp_evaluation_id')->where('ke.ksp_version_id',$version['id'])->countAllResults();
                else $count=$db->table($table)->where('ksp_version_id',$version['id'])->countAllResults();
                $actual=['entity'=>$entity,'count'=>$count]; $passed=$count >= (int)($expected['minimum_count']??1); break;
            case 'EVIDENCE_COUNT_MIN':
                $builder=$db->table('ksp_evidence')->where('ksp_version_id',$version['id']);
                if(!empty($params['target_type'])) $builder->where('target_type',strtoupper($params['target_type']));
                $count=$builder->countAllResults(); $actual=['count'=>$count,'target_type'=>$params['target_type']??'ANY']; $passed=$count >= (int)($expected['minimum_count']??1); break;
            case 'STATUS_IN':
                $allowed=array_map('strtoupper',$expected['statuses']??[]); $actual=['status'=>$version['status']]; $passed=in_array($version['status'],$allowed,true); break;
            default: $applicable=false; $actual=['state'=>'EVALUATOR_NOT_AVAILABLE'];
        }
        $status=$applicable?($passed?'PASS':($rule['severity']==='ERROR'?'FAIL':'WARNING')):'NOT_APPLICABLE';
        return [
            'regulation_rule_id'=>$rule['id'],'rule_code'=>$rule['rule_code'],'rule_class'=>$rule['rule_class'],
            'regulation_version'=>$rule['regulation_code'].'@v'.$rule['version_number'],'result_status'=>$status,
            'expected'=>self::json($expected),'actual'=>self::json($actual),'severity'=>$rule['severity'],
            'source_reference'=>$rule['document_url'] ?: 'Regulation Registry: '.$rule['regulation_code'],
            'provenance_json'=>self::json(['regulation_version_uuid'=>$rule['regulation_version_uuid'],'authority'=>$rule['authority'],'title'=>$rule['regulation_title'],'document_hash'=>$rule['document_hash']]),
            'suggested_action'=>$rule['suggested_action'],
        ];
    }

    private static function legalRegistrySentinel(): array
    {
        return ['regulation_rule_id'=>null,'rule_code'=>'LEGAL_REGISTRY_UNCONFIGURED','rule_class'=>'LEGAL_REQUIRED','regulation_version'=>'UNCONFIGURED','result_status'=>'NOT_APPLICABLE','expected'=>self::json(['verified_official_legal_rules'=>'required']),'actual'=>self::json(['active_verified_legal_rules'=>0]),'severity'=>'INFO','source_reference'=>'IALOS Regulation Registry safety control','provenance_json'=>self::json(['generated_by'=>'KspComplianceService','claim'=>'No legal compliance conclusion was made']),'suggested_action'=>'Daftarkan hanya sumber hukum resmi yang terverifikasi beserta versi dan rule terstruktur sebelum menilai kepatuhan legal.'];
    }

    private static function sourceRevision(array $version,array $rules): string
    {
        return 'ksp-r'.$version['revision_number'].'-'.substr(hash('sha256',self::json(array_map(static function($r){return [$r['uuid'],$r['updated_at']??$r['created_at'],$r['expected_json'],$r['parameters_json']];},$rules))),0,24);
    }
    private static function json($value): string { return json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); }
}
