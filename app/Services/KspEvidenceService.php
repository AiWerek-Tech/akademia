<?php

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use InvalidArgumentException;
use RuntimeException;

class KspEvidenceService
{
    public const TYPES=['RAPOR_PENDIDIKAN','INTERNAL_ASSESSMENT','TEACHER_OBSERVATION','STUDENT_FEEDBACK','PARENT_FEEDBACK','FACILITY_DATA','LOCAL_CONTEXT','MEETING_DECISION','OTHER'];
    public const TARGETS=['SCHOOL_CONTEXT','VISION_MISSION_GOAL','EVALUATION','IMPROVEMENT_ACTION','KSP_SECTION'];
    private const TARGET_TABLES=['SCHOOL_CONTEXT'=>'school_context_snapshots','VISION_MISSION_GOAL'=>'ksp_vision_mission_goals','EVALUATION'=>'ksp_evaluations','KSP_SECTION'=>'ksp_section_statuses'];
    private const MIME_EXTENSIONS=['application/pdf'=>'pdf','application/vnd.openxmlformats-officedocument.wordprocessingml.document'=>'docx','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'=>'xlsx','text/csv'=>'csv','text/plain'=>'txt','image/png'=>'png','image/jpeg'=>'jpg'];

    public static function all(string $kspUuid): array
    {
        $version=DigitalKspService::byUuid($kspUuid);
        return Database::connect()->table('ksp_evidence e')->select('e.*,u.full_name AS creator_name')->join('users u','u.id=e.created_by','left')->where('e.ksp_version_id',$version['id'])->orderBy('e.id','DESC')->get()->getResultArray();
    }

    public static function attach(string $kspUuid,array $data,?UploadedFile $file=null): array
    {
        EducationFoundationService::requireFields($data,['target_type','target_uuid','evidence_type','source','date','description']);
        $version=DigitalKspService::byUuid($kspUuid);
        if($version['status']!=='DRAFT') throw new RuntimeException('Evidence hanya dapat ditambahkan pada KSP berstatus DRAFT.');
        $targetType=strtoupper(trim($data['target_type'])); $type=strtoupper(trim($data['evidence_type']));
        if(!in_array($targetType,self::TARGETS,true)) throw new InvalidArgumentException('Target evidence tidak valid.');
        if(!in_array($type,self::TYPES,true)) throw new InvalidArgumentException('Jenis evidence tidak valid.');
        self::assertTarget($version,$targetType,(string)$data['target_uuid']);
        $date=\DateTimeImmutable::createFromFormat('!Y-m-d',(string)$data['date']);
        if(!$date || $date->format('Y-m-d')!==$data['date']) throw new InvalidArgumentException('Tanggal evidence tidak valid.');
        $reference=trim((string)($data['reference']??''))?:null;
        if(!$reference && (!$file || !$file->isValid())) throw new InvalidArgumentException('Evidence wajib memiliki file atau reference.');
        $record=['uuid'=>UuidService::v4(),'ksp_version_id'=>$version['id'],'target_type'=>$targetType,'target_uuid'=>$data['target_uuid'],'evidence_type'=>$type,'source_name'=>trim($data['source']),'evidence_date'=>$data['date'],'description'=>trim($data['description']),'reference_uri'=>$reference,'created_by'=>EducationFoundationService::actorId(),'created_at'=>date('Y-m-d H:i:s')];
        $absolutePath=null;
        if($file && $file->isValid()) {
            if($file->getSize()>10*1024*1024) throw new InvalidArgumentException('Ukuran file evidence maksimal 10 MB.');
            $mime=$file->getMimeType(); if(!isset(self::MIME_EXTENSIONS[$mime])) throw new InvalidArgumentException('Tipe file evidence tidak diizinkan.');
            $directory=rtrim(WRITEPATH,'/\\').DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'ksp-evidence'.DIRECTORY_SEPARATOR.$record['uuid'];
            if(!is_dir($directory) && !mkdir($directory,0750,true) && !is_dir($directory)) throw new RuntimeException('Direktori evidence tidak dapat dibuat.');
            $filename='evidence.'.self::MIME_EXTENSIONS[$mime]; $file->move($directory,$filename);
            $absolutePath=$directory.DIRECTORY_SEPARATOR.$filename;
            $record += ['storage_reference'=>'uploads/ksp-evidence/'.$record['uuid'].'/'.$filename,'original_filename'=>$file->getClientName(),'mime_type'=>$mime,'file_size'=>filesize($absolutePath),'file_hash'=>hash_file('sha256',$absolutePath)];
        }
        $db=Database::connect();
        try {
            $db->table('ksp_evidence')->insert($record); $id=(int)$db->insertID();
            AuditService::log('ksp','ATTACH_EVIDENCE','KspEvidence',$id,null,$record,null,$record['uuid']);
            return $db->table('ksp_evidence')->where('id',$id)->get()->getRowArray();
        } catch(\Throwable $e) { if($absolutePath && is_file($absolutePath)) @unlink($absolutePath); throw $e; }
    }

    public static function file(string $kspUuid,string $evidenceUuid): array
    {
        $version=DigitalKspService::byUuid($kspUuid); $row=Database::connect()->table('ksp_evidence')->where(['uuid'=>$evidenceUuid,'ksp_version_id'=>$version['id']])->get()->getRowArray();
        if(!$row || !$row['storage_reference']) throw new InvalidArgumentException('File evidence tidak ditemukan pada KSP ini.');
        $path=rtrim(WRITEPATH,'/\\').DIRECTORY_SEPARATOR.str_replace(['/', '\\'],DIRECTORY_SEPARATOR,$row['storage_reference']);
        $root=realpath(WRITEPATH); $resolved=realpath($path);
        if(!$resolved || strpos($resolved,$root)!==0 || !is_file($resolved)) throw new InvalidArgumentException('File evidence tidak tersedia.');
        return ['row'=>$row,'path'=>$resolved];
    }

    private static function assertTarget(array $version,string $type,string $uuid): void
    {
        $db=Database::connect();
        if($type==='IMPROVEMENT_ACTION') {
            $exists=$db->table('improvement_actions ia')->join('ksp_evaluations ke','ke.id=ia.ksp_evaluation_id')->where(['ia.uuid'=>$uuid,'ke.ksp_version_id'=>$version['id']])->countAllResults();
        } else {
            $exists=$db->table(self::TARGET_TABLES[$type])->where(['uuid'=>$uuid,'ksp_version_id'=>$version['id']])->countAllResults();
        }
        if($exists!==1) throw new InvalidArgumentException('Target evidence tidak ditemukan pada versi KSP ini.');
    }
}
