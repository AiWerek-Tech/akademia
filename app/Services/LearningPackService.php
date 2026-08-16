<?php

namespace App\Services;

use App\Models\SubjectLearningPackModel;
use Config\Database;
use InvalidArgumentException;
use RuntimeException;

class LearningPackService
{
    public static function create(array $data): array
    {
        EducationFoundationService::requireFields($data, ['curriculum_version_id','unit_id','subject_id','grade_level_id','code','name']);
        UnitScopeService::assertUnit((int) $data['unit_id']); UnitScopeService::assertSubjectInUnit((int) $data['subject_id'], (int) $data['unit_id']);
        $record = array_intersect_key($data, array_flip(['curriculum_version_id','unit_id','subject_id','grade_level_id','code','name','description','status'])) +
            ['uuid'=>UuidService::v4(),'revision_number'=>1,'created_by'=>EducationFoundationService::actorId()];
        $record['code']=strtoupper(trim($record['code'])); $record['status']=strtoupper($record['status'] ?? 'DRAFT');
        $id=(new SubjectLearningPackModel())->insert($record,true); AuditService::log('education_foundation','CREATE_LEARNING_PACK','SubjectLearningPack',(int)$id,null,$record);
        return (new SubjectLearningPackModel())->find($id);
    }

    public static function attachObjective(string $packUuid, string $objectiveUuid): void
    {
        $pack=self::getScoped($packUuid); self::assertMutable($pack); $tp=EducationFoundationService::byUuid('learning_objectives_tp',$objectiveUuid);
        $cp=Database::connect()->table('learning_outcomes_cp')->where('id',$tp['learning_outcome_id'])->get()->getRowArray();
        if (!$cp || (int)$cp['subject_id']!==(int)$pack['subject_id'] || (int)$cp['grade_level_id']!==(int)$pack['grade_level_id'] || ($tp['unit_id']!==null && (int)$tp['unit_id']!==(int)$pack['unit_id'])) throw new InvalidArgumentException('TP tidak sesuai scope paket.');
        $key=['subject_learning_pack_id'=>$pack['id'],'learning_objective_id'=>$tp['id']];
        if (Database::connect()->table('subject_learning_pack_objectives')->where($key)->countAllResults()===0) Database::connect()->table('subject_learning_pack_objectives')->insert($key+['created_at'=>date('Y-m-d H:i:s')]);
    }

    public static function attachSequence(string $packUuid,string $sequenceUuid): void
    {
        $pack=self::getScoped($packUuid); self::assertMutable($pack); $atp=EducationFoundationService::byUuid('learning_sequences_atp',$sequenceUuid);
        foreach (['unit_id','subject_id','grade_level_id','curriculum_version_id'] as $f) if ((int)$atp[$f] !== (int)$pack[$f]) throw new InvalidArgumentException('ATP tidak sesuai scope paket.');
        $key=['subject_learning_pack_id'=>$pack['id'],'learning_sequence_id'=>$atp['id']];
        if (Database::connect()->table('subject_learning_pack_sequences')->where($key)->countAllResults()===0) Database::connect()->table('subject_learning_pack_sequences')->insert($key+['created_at'=>date('Y-m-d H:i:s')]);
    }

    public static function update(string $uuid,array $data): array
    {
        $current=self::getScoped($uuid); self::assertMutable($current);
        $updated=EducationFoundationService::atomicUpdate('subject_learning_packs',$current,array_intersect_key($data,array_flip(['name','description','status','revision_number'])));
        AuditService::log('education_foundation','UPDATE_LEARNING_PACK','SubjectLearningPack',(int)$current['id'],$current,$updated); return $updated;
    }

    public static function scoped(): array
    {
        $ids=UnitScopeService::accessibleUnitIds(); if ($ids===[]) return [];
        return Database::connect()->table('subject_learning_packs p')->select('p.*,s.name subject_name,gl.name grade_name')->join('subjects s','s.id=p.subject_id')->join('grade_levels gl','gl.id=p.grade_level_id')->whereIn('p.unit_id',$ids)->orderBy('p.updated_at','DESC')->get()->getResultArray();
    }

    private static function getScoped(string $uuid): array { $row=EducationFoundationService::byUuid('subject_learning_packs',$uuid); UnitScopeService::assertUnit((int)$row['unit_id']); return $row; }
    private static function assertMutable(array $row): void { if (in_array($row['status'],['LOCKED','ARCHIVED'],true)) throw new RuntimeException('Paket terkunci/diarsipkan tidak dapat diubah.'); }
}
