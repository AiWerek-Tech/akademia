<?php

namespace App\Services;

use Config\Database;
use InvalidArgumentException;
use RuntimeException;

class DigitalKspService
{
    private const SECTIONS = ['CHARACTERISTICS','VISION_MISSION_GOALS','ORGANIZATION','INTRACURRICULAR','COCURRICULAR','EXTRACURRICULAR','LEARNING_PLAN','EVALUATION','APPENDICES'];
    private const TRANSITIONS = ['DRAFT'=>['REVIEW'],'REVIEW'=>['DRAFT','APPROVED'],'APPROVED'=>['LOCKED'],'LOCKED'=>['SUPERSEDED'],'SUPERSEDED'=>[]];

    public static function createVersion(array $data): array
    {
        EducationFoundationService::requireFields($data,['unit_id','academic_period_id','code','title']);
        $unitId=UnitScopeService::resolveUnit($data['unit_id']);
        $db=Database::connect(); $db->transBegin();
        try {
            $record=['uuid'=>UuidService::v4(),'unit_id'=>$unitId,'academic_period_id'=>(int)$data['academic_period_id'],'code'=>strtoupper(trim($data['code'])),'title'=>trim($data['title']),'status'=>'DRAFT','revision_number'=>1,'created_by'=>EducationFoundationService::actorId(),'created_at'=>date('Y-m-d H:i:s')];
            $db->table('ksp_versions')->insert($record); $id=(int)$db->insertID();
            foreach(self::SECTIONS as $section) $db->table('ksp_section_statuses')->insert(['uuid'=>UuidService::v4(),'ksp_version_id'=>$id,'section_code'=>$section,'completion_percent'=>0,'status'=>'EMPTY','revision_number'=>1,'created_by'=>EducationFoundationService::actorId(),'created_at'=>date('Y-m-d H:i:s')]);
            AuditService::log('ksp','CREATE_VERSION','KspVersion',$id,null,$record,null,$record['uuid']);
            if ($db->transStatus() === false) throw new RuntimeException('Versi KSP gagal disimpan.');
            $db->transCommit(); return self::byUuid($record['uuid']);
        } catch (\Throwable $e) { $db->transRollback(); throw $e; }
    }

    public static function scoped(): array
    {
        return Database::connect()->table('ksp_versions k')->select('k.*,su.code AS unit_code,su.name AS unit_name,ap.name AS period_name')
            ->join('school_units su','su.id=k.unit_id')->join('academic_periods ap','ap.id=k.academic_period_id')
            ->whereIn('k.unit_id',UnitScopeService::accessibleUnitIds() ?: [0])->orderBy('k.id','DESC')->get()->getResultArray();
    }

    public static function byUuid(string $uuid): array
    {
        $row=Database::connect()->table('ksp_versions')->where('uuid',$uuid)->get()->getRowArray();
        if(!$row) throw new InvalidArgumentException('Versi KSP tidak ditemukan.');
        UnitScopeService::assertUnit((int)$row['unit_id']); return $row;
    }

    public static function detail(string $uuid): array
    {
        $version=self::byUuid($uuid); $db=Database::connect(); $id=(int)$version['id'];
        return ['version'=>$version,'sections'=>$db->table('ksp_section_statuses')->where('ksp_version_id',$id)->orderBy('id')->get()->getResultArray(),'readiness'=>self::readiness($id)];
    }

    public static function context(string $uuid): array { $v=self::byUuid($uuid); return Database::connect()->table('school_context_snapshots')->where('ksp_version_id',$v['id'])->orderBy('id')->get()->getResultArray(); }
    public static function goals(string $uuid): array { $v=self::byUuid($uuid); return Database::connect()->table('ksp_vision_mission_goals')->where('ksp_version_id',$v['id'])->orderBy('sort_order')->get()->getResultArray(); }
    public static function organizations(string $uuid): array { $v=self::byUuid($uuid); return Database::connect()->table('ksp_learning_organizations')->where('ksp_version_id',$v['id'])->orderBy('category')->get()->getResultArray(); }
    public static function evaluations(string $uuid): array { $v=self::byUuid($uuid); return Database::connect()->table('ksp_evaluations')->where('ksp_version_id',$v['id'])->orderBy('id','DESC')->get()->getResultArray(); }

    public static function addContext(string $uuid,array $data): array
    {
        EducationFoundationService::requireFields($data,['context_type','title','summary']); $v=self::mutable($uuid);
        return self::insertChild('school_context_snapshots',$v,['context_type'=>strtoupper($data['context_type']),'title'=>trim($data['title']),'summary'=>trim($data['summary']),'finding'=>$data['finding']??null,'source_type'=>$data['source_type']??null,'source_reference'=>$data['source_reference']??null],'ADD_CONTEXT','CHARACTERISTICS');
    }

    public static function addGoal(string $uuid,array $data): array
    {
        EducationFoundationService::requireFields($data,['statement_type','statement']); $type=strtoupper($data['statement_type']);
        if(!in_array($type,['VISION','MISSION','GOAL'],true)) throw new InvalidArgumentException('Jenis pernyataan KSP tidak valid.');
        $v=self::mutable($uuid); return self::insertChild('ksp_vision_mission_goals',$v,['statement_type'=>$type,'statement'=>trim($data['statement']),'measure'=>$data['measure']??null,'target_value'=>$data['target_value']??null,'graduate_profile_dimension_id'=>!empty($data['graduate_profile_dimension_id'])?(int)$data['graduate_profile_dimension_id']:null,'sort_order'=>(int)($data['sort_order']??1)],'ADD_GOAL','VISION_MISSION_GOALS');
    }

    public static function addOrganization(string $uuid,array $data): array
    {
        EducationFoundationService::requireFields($data,['category','delivery_model','title','description']); $v=self::mutable($uuid);
        return self::insertChild('ksp_learning_organizations',$v,['category'=>strtoupper($data['category']),'delivery_model'=>strtoupper($data['delivery_model']),'title'=>trim($data['title']),'description'=>trim($data['description']),'annual_minutes'=>!empty($data['annual_minutes'])?(int)$data['annual_minutes']:null],'ADD_ORGANIZATION','ORGANIZATION');
    }

    public static function addEvaluation(string $uuid,array $data): array
    {
        EducationFoundationService::requireFields($data,['evaluation_period','objective']); $v=self::mutable($uuid);
        return self::insertChild('ksp_evaluations',$v,['evaluation_period'=>trim($data['evaluation_period']),'objective'=>trim($data['objective']),'target_value'=>$data['target_value']??null,'actual_value'=>$data['actual_value']??null,'finding'=>$data['finding']??null,'root_cause'=>$data['root_cause']??null,'decision'=>$data['decision']??null,'status'=>'DRAFT'],'ADD_EVALUATION','EVALUATION');
    }

    public static function updateSection(string $uuid, string $sectionCode, array $data): array
    {
        $version = self::mutable($uuid);
        $sectionCode = strtoupper($sectionCode);
        if (!in_array($sectionCode, self::SECTIONS, true)) throw new InvalidArgumentException('Bagian KSP tidak valid.');
        $percent = (int) ($data['completion_percent'] ?? -1);
        if ($percent < 0 || $percent > 100) throw new InvalidArgumentException('Persentase kelengkapan harus 0 sampai 100.');
        $current = Database::connect()->table('ksp_section_statuses')->where(['ksp_version_id'=>$version['id'],'section_code'=>$sectionCode])->get()->getRowArray();
        if (!$current) throw new InvalidArgumentException('Bagian KSP tidak ditemukan.');
        $current['revision_number'] = (int) ($data['revision_number'] ?? 0);
        $updated = EducationFoundationService::atomicUpdate('ksp_section_statuses', $current, [
            'completion_percent'=>$percent,
            'status'=>$percent === 100 ? 'COMPLETE' : ($percent > 0 ? 'IN_PROGRESS' : 'EMPTY'),
            'notes'=>trim((string) ($data['notes'] ?? '')) ?: null,
        ]);
        AuditService::log('ksp','UPDATE_SECTION','KspSectionStatus',(int)$current['id'],$current,$updated,null,$current['uuid']);
        return $updated;
    }

    public static function transition(string $uuid,string $target,int $revision): array
    {
        $current=self::byUuid($uuid); $target=strtoupper($target);
        if(!in_array($target,self::TRANSITIONS[$current['status']]??[],true)) throw new InvalidArgumentException('Transisi status KSP tidak valid.');
        if($target==='REVIEW' && self::readiness((int)$current['id'])['overall']<75) throw new InvalidArgumentException('KSP minimal 75% lengkap sebelum diajukan review.');
        $changes=['status'=>$target,'updated_by'=>EducationFoundationService::actorId()]; $now=date('Y-m-d H:i:s');
        if($target==='REVIEW'){$changes['reviewed_by']=EducationFoundationService::actorId();$changes['reviewed_at']=$now;}
        if($target==='APPROVED'){$changes['approved_by']=EducationFoundationService::actorId();$changes['approved_at']=$now;}
        if($target==='LOCKED'){$changes['locked_by']=EducationFoundationService::actorId();$changes['locked_at']=$now;}
        $current['revision_number']=$revision; $updated=EducationFoundationService::atomicUpdate('ksp_versions',$current,$changes);
        AuditService::log('ksp',$target.'_VERSION','KspVersion',(int)$current['id'],$current,$updated,null,$uuid); return $updated;
    }

    public static function readiness(int $versionId): array
    {
        $rows=Database::connect()->table('ksp_section_statuses')->where('ksp_version_id',$versionId)->get()->getResultArray();
        $overall=$rows===[]?0:(int)round(array_sum(array_map('intval',array_column($rows,'completion_percent')))/count($rows));
        return ['overall'=>$overall,'completed'=>count(array_filter($rows,static fn(array $r):bool=>(int)$r['completion_percent']===100)),'total'=>count($rows)];
    }

    private static function mutable(string $uuid): array
    {
        $v=self::byUuid($uuid); if($v['status'] !== 'DRAFT') throw new RuntimeException('Hanya versi KSP berstatus DRAFT yang dapat diubah.'); return $v;
    }

    private static function insertChild(string $table,array $version,array $data,string $action,string $section): array
    {
        $record=['uuid'=>UuidService::v4(),'ksp_version_id'=>$version['id']] + $data + ['revision_number'=>1,'created_by'=>EducationFoundationService::actorId(),'created_at'=>date('Y-m-d H:i:s')];
        $db=Database::connect(); $db->transBegin();
        try { $db->table($table)->insert($record); $id=(int)$db->insertID(); self::refreshSection((int)$version['id'],$section); AuditService::log('ksp',$action,$table,$id,null,$record,null,$record['uuid']); $db->transCommit(); return $db->table($table)->where('id',$id)->get()->getRowArray(); }
        catch(\Throwable $e){$db->transRollback();throw $e;}
    }

    private static function refreshSection(int $versionId,string $section): void
    {
        $db=Database::connect(); $percent=0;
        if($section==='CHARACTERISTICS') $percent=$db->table('school_context_snapshots')->where('ksp_version_id',$versionId)->countAllResults()>0?100:0;
        elseif($section==='VISION_MISSION_GOALS') { $types=array_unique(array_column($db->table('ksp_vision_mission_goals')->select('statement_type')->where('ksp_version_id',$versionId)->get()->getResultArray(),'statement_type')); $percent=(int)round(count(array_intersect(['VISION','MISSION','GOAL'],$types))/3*100); }
        elseif($section==='ORGANIZATION') $percent=$db->table('ksp_learning_organizations')->where('ksp_version_id',$versionId)->countAllResults()>0?100:0;
        elseif($section==='EVALUATION') $percent=$db->table('ksp_evaluations')->where('ksp_version_id',$versionId)->countAllResults()>0?100:0;
        $db->table('ksp_section_statuses')->where(['ksp_version_id'=>$versionId,'section_code'=>$section])->update(['completion_percent'=>$percent,'status'=>$percent===100?'COMPLETE':($percent>0?'IN_PROGRESS':'EMPTY'),'revision_number'=>new \CodeIgniter\Database\RawSql('revision_number + 1'),'updated_by'=>EducationFoundationService::actorId(),'updated_at'=>date('Y-m-d H:i:s')]);
    }
}
