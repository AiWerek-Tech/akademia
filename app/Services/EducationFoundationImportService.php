<?php

namespace App\Services;

use App\Models\EducationFoundationImportBatchModel;
use App\Models\EducationFoundationImportRowModel;
use Config\Database;
use InvalidArgumentException;
use RuntimeException;

class EducationFoundationImportService
{
    private const TYPES = ['CP','TP'];

    public static function stage(int $unitId, int $curriculumVersionId, string $filename, array $rows): array
    {
        UnitScopeService::assertUnit($unitId);
        if ($rows === []) throw new InvalidArgumentException('Import tidak berisi baris data.');
        $hash = hash('sha256', json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if (Database::connect()->table('education_foundation_import_batches')->where(['unit_id'=>$unitId,'source_hash'=>$hash])->countAllResults() > 0) throw new InvalidArgumentException('Berkas dengan isi yang sama sudah pernah diunggah pada unit ini.');
        $batchRecord = ['uuid'=>UuidService::v4(),'unit_id'=>$unitId,'curriculum_version_id'=>$curriculumVersionId,
            'source_filename'=>basename($filename),'source_hash'=>$hash,'status'=>'VALIDATING','total_rows'=>count($rows),'created_by'=>EducationFoundationService::actorId()];
        $db=Database::connect(); $db->transBegin();
        try {
            $batchId=(new EducationFoundationImportBatchModel())->insert($batchRecord,true); $valid=0; $invalid=0;
            foreach (array_values($rows) as $index=>$row) {
                $type=strtoupper(trim((string)($row['entity_type'] ?? ''))); $errors=self::validateRow($type,$row);
                if ($errors===[]) $valid++; else $invalid++;
                (new EducationFoundationImportRowModel())->insert(['uuid'=>UuidService::v4(),'import_batch_id'=>$batchId,'row_number'=>$index+1,'entity_type'=>$type ?: 'UNKNOWN',
                    'payload_json'=>json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'status'=>$errors===[]?'VALID':'ERROR','errors_json'=>$errors===[]?null:json_encode($errors),'created_by'=>EducationFoundationService::actorId()]);
            }
            $db->table('education_foundation_import_batches')->where('id',$batchId)->update(['status'=>$invalid===0?'READY':'INVALID','valid_rows'=>$valid,'error_rows'=>$invalid,'updated_at'=>date('Y-m-d H:i:s')]);
            AuditService::log('education_foundation','STAGE_IMPORT','EducationFoundationImportBatch',(int)$batchId,null,$batchRecord,null,$batchRecord['uuid']);
            $db->transCommit(); return (new EducationFoundationImportBatchModel())->find($batchId);
        } catch (\Throwable $e) { $db->transRollback(); throw $e; }
    }

    public static function preview(string $batchUuid): array
    {
        $batch=EducationFoundationService::byUuid('education_foundation_import_batches',$batchUuid); UnitScopeService::assertUnit((int)$batch['unit_id']);
        $rows=Database::connect()->table('education_foundation_import_rows')->where('import_batch_id',$batch['id'])->orderBy('row_number')->get()->getResultArray();
        foreach ($rows as &$row) $row['payload']=json_decode($row['payload_json'],true);
        return ['batch'=>$batch,'rows'=>$rows];
    }

    public static function apply(string $batchUuid): array
    {
        $preview=self::preview($batchUuid); $batch=$preview['batch'];
        if ($batch['status']!=='READY' || (int)$batch['error_rows']>0) throw new RuntimeException('Batch belum valid dan tidak dapat diaplikasikan.');
        $db=Database::connect(); $db->transBegin(); $applied=0;
        try {
            foreach ($preview['rows'] as $row) {
                $data=$row['payload'];
                if ($row['entity_type']==='CP') {
                    $created=LearningOutcomeService::create(array_merge($data,['curriculum_version_id'=>$batch['curriculum_version_id']]));
                } else {
                    $created=LearningObjectiveService::create(array_merge($data,['unit_id'=>$batch['unit_id']]));
                }
                $db->table('education_foundation_import_rows')->where('id',$row['id'])->update(['status'=>'APPLIED','target_entity_id'=>$created['id'],'updated_at'=>date('Y-m-d H:i:s')]); $applied++;
            }
            $changes=['status'=>'APPLIED','applied_rows'=>$applied,'applied_by'=>EducationFoundationService::actorId(),'applied_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')];
            $db->table('education_foundation_import_batches')->where('id',$batch['id'])->update($changes);
            AuditService::log('education_foundation','APPLY_IMPORT','EducationFoundationImportBatch',(int)$batch['id'],$batch,$changes,null,$batch['uuid']);
            $db->transCommit(); return EducationFoundationService::byUuid('education_foundation_import_batches',$batchUuid);
        } catch (\Throwable $e) { $db->transRollback(); throw $e; }
    }

    private static function validateRow(string $type,array $row): array
    {
        if (!in_array($type,self::TYPES,true)) return ['entity_type harus CP atau TP'];
        $required=$type==='CP'?['subject_id','grade_level_id','code','phase','statement']:['learning_outcome_id','source_level','code','statement']; $errors=[];
        foreach ($required as $field) if (!isset($row[$field]) || trim((string)$row[$field])==='') $errors[]="{$field} wajib diisi";
        if ($type==='TP' && strtoupper((string)($row['source_level']??''))==='NATIONAL') $errors[]='Import unit tidak boleh membuat TP nasional';
        return $errors;
    }
}
