<?php

namespace App\Services;

use Config\Database;
use Dompdf\Dompdf;
use Dompdf\Options;
use InvalidArgumentException;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use RuntimeException;

class KspDocumentGeneratorService
{
    private const FORMATS=['DOCX','PDF'];

    public static function all(string $kspUuid): array
    {
        $version=DigitalKspService::byUuid($kspUuid);
        return Database::connect()->table('ksp_generated_documents d')->select('d.*,u.full_name AS generator_name')->join('users u','u.id=d.generated_by','left')->where('d.ksp_version_id',$version['id'])->orderBy('d.id','DESC')->get()->getResultArray();
    }

    public static function generate(string $kspUuid,string $format): array
    {
        $version=DigitalKspService::byUuid($kspUuid); $format=strtoupper($format);
        if(!in_array($format,self::FORMATS,true)) throw new InvalidArgumentException('Format dokumen KSP tidak didukung.');
        if(!in_array($version['status'],['APPROVED','LOCKED'],true)) throw new RuntimeException('Dokumen final hanya dapat dibuat dari KSP APPROVED atau LOCKED.');
        $snapshot=self::snapshot($version); $snapshotJson=json_encode($snapshot,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $snapshotHash=hash('sha256',$snapshotJson); $sourceRevision='ksp-r'.$version['revision_number'].'-'.substr($snapshotHash,0,24);
        $uuid=UuidService::v4(); $relative='exports/ksp/'.$version['uuid'].'/'.date('Ymd_His').'_'.$uuid.'.'.strtolower($format);
        $absolute=rtrim(WRITEPATH,'/\\').DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$relative);
        $directory=dirname($absolute); if(!is_dir($directory) && !mkdir($directory,0750,true) && !is_dir($directory)) throw new RuntimeException('Direktori keluaran KSP tidak dapat dibuat.');
        if($format==='DOCX') self::writeDocx($snapshot,$absolute,$sourceRevision); else self::writePdf($snapshot,$absolute,$sourceRevision);
        if(!is_file($absolute) || filesize($absolute)===0) throw new RuntimeException('Dokumen KSP gagal dibuat.');
        $record=['uuid'=>$uuid,'ksp_version_id'=>$version['id'],'format'=>$format,'source_revision'=>$sourceRevision,'source_snapshot_hash'=>$snapshotHash,'generated_by'=>EducationFoundationService::actorId(),'generated_at'=>date('Y-m-d H:i:s'),'document_hash'=>hash_file('sha256',$absolute),'storage_reference'=>$relative,'file_size'=>filesize($absolute),'created_at'=>date('Y-m-d H:i:s')];
        try {
            $db=Database::connect(); $db->table('ksp_generated_documents')->insert($record); $id=(int)$db->insertID();
            AuditService::log('ksp','GENERATE_'.$format,'KspGeneratedDocument',$id,null,$record,null,$uuid);
            return $db->table('ksp_generated_documents')->where('id',$id)->get()->getRowArray();
        } catch(\Throwable $e) { @unlink($absolute); throw $e; }
    }

    public static function file(string $kspUuid,string $documentUuid): array
    {
        $version=DigitalKspService::byUuid($kspUuid); $row=Database::connect()->table('ksp_generated_documents')->where(['uuid'=>$documentUuid,'ksp_version_id'=>$version['id']])->get()->getRowArray();
        if(!$row) throw new InvalidArgumentException('Dokumen tidak ditemukan pada KSP ini.');
        $path=rtrim(WRITEPATH,'/\\').DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$row['storage_reference']);
        $root=realpath(WRITEPATH); $resolved=realpath($path);
        if(!$resolved || strpos($resolved,$root)!==0 || !is_file($resolved)) throw new InvalidArgumentException('File dokumen tidak tersedia.');
        return ['row'=>$row,'path'=>$resolved];
    }

    public static function snapshot(array $version): array
    {
        DigitalKspService::byUuid($version['uuid']); $db=Database::connect(); $id=(int)$version['id'];
        $identity=$db->table('ksp_versions k')->select('k.*,su.code AS unit_code,su.name AS unit_name,ap.name AS period_name,ap.start_date AS period_start,ap.end_date AS period_end')->join('school_units su','su.id=k.unit_id')->join('academic_periods ap','ap.id=k.academic_period_id')->where('k.id',$id)->get()->getRowArray();
        $approval=[]; foreach(['reviewed_by'=>'reviewer','approved_by'=>'approver','locked_by'=>'locker'] as $field=>$label){ if($version[$field]){$user=$db->table('users')->select('full_name')->where('id',$version[$field])->get()->getRowArray();$approval[$label]=['name'=>$user['full_name']??('User #'.$version[$field]),'at'=>$version[str_replace('_by','_at',$field)]];}}
        $goals=$db->table('ksp_vision_mission_goals')->where('ksp_version_id',$id)->orderBy('sort_order')->get()->getResultArray();
        $orgs=$db->table('ksp_learning_organizations')->where('ksp_version_id',$id)->orderBy('category')->get()->getResultArray();
        $evaluations=$db->table('ksp_evaluations')->where('ksp_version_id',$id)->orderBy('id')->get()->getResultArray();
        $actions=$db->table('improvement_actions ia')->select('ia.*,ke.uuid AS evaluation_uuid,u.full_name AS owner_name')->join('ksp_evaluations ke','ke.id=ia.ksp_evaluation_id')->join('users u','u.id=ia.owner_user_id','left')->where('ke.ksp_version_id',$id)->orderBy('ia.due_date')->get()->getResultArray();
        $latest=KspComplianceService::latest($version['uuid']);
        return [
            'cover'=>['title'=>$identity['title'],'code'=>$identity['code'],'unit'=>$identity['unit_name'],'period'=>$identity['period_name']],
            'identity'=>$identity,'approval'=>$approval,
            'school_characteristics'=>$db->table('school_context_snapshots')->where('ksp_version_id',$id)->orderBy('id')->get()->getResultArray(),
            'vision'=>array_values(array_filter($goals,static fn($r)=>$r['statement_type']==='VISION')),
            'mission'=>array_values(array_filter($goals,static fn($r)=>$r['statement_type']==='MISSION')),
            'goals'=>array_values(array_filter($goals,static fn($r)=>$r['statement_type']==='GOAL')),
            'learning_organization'=>$orgs,
            'intracurricular'=>array_values(array_filter($orgs,static fn($r)=>$r['category']==='INTRACURRICULAR')),
            'cocurricular'=>array_values(array_filter($orgs,static fn($r)=>$r['category']==='COCURRICULAR')),
            'extracurricular'=>array_values(array_filter($orgs,static fn($r)=>$r['category']==='EXTRACURRICULAR')),
            'learning_planning'=>array_values(array_filter($db->table('ksp_section_statuses')->where('ksp_version_id',$id)->get()->getResultArray(),static fn($r)=>$r['section_code']==='LEARNING_PLAN')),
            'evaluation'=>$evaluations,'improvement_actions'=>$actions,
            'appendices'=>array_values(array_filter($db->table('ksp_section_statuses')->where('ksp_version_id',$id)->get()->getResultArray(),static fn($r)=>$r['section_code']==='APPENDICES')),
            'compliance_summary'=>$latest ?: ['run'=>['overall_status'=>'NOT_APPLICABLE'],'results'=>[]],
            'evidence_index'=>KspEvidenceService::all($version['uuid']),
        ];
    }

    private static function writeDocx(array $s,string $path,string $revision): void
    {
        $phpWord=new PhpWord(); $phpWord->getDocInfo()->setCreator('IALOS Education')->setTitle($s['cover']['title'])->setSubject('Digital KSP '.$revision)->setDescription('Generated from structured Digital KSP data; '.$revision);
        $phpWord->setDefaultFontName('Aptos'); $phpWord->setDefaultFontSize(10);
        $phpWord->addTitleStyle(1,['name'=>'Aptos Display','size'=>20,'bold'=>true,'color'=>'243B53'],['spaceBefore'=>240,'spaceAfter'=>140,'keepNext'=>true]);
        $phpWord->addTitleStyle(2,['name'=>'Aptos','size'=>14,'bold'=>true,'color'=>'5B4BDB'],['spaceBefore'=>180,'spaceAfter'=>100,'keepNext'=>true]);
        $section=$phpWord->addSection(['pageSizeW'=>11906,'pageSizeH'=>16838,'marginTop'=>(int)round(Converter::cmToTwip(2.2)),'marginBottom'=>(int)round(Converter::cmToTwip(2.2)),'marginLeft'=>(int)round(Converter::cmToTwip(2.4)),'marginRight'=>(int)round(Converter::cmToTwip(2.4))]);
        $header=$section->addHeader(); $header->addText('IALOS EDUCATION  |  DIGITAL KSP',['size'=>8,'bold'=>true,'color'=>'5B4BDB']);
        $footer=$section->addFooter(); $footer->addPreserveText('Source '.$revision.'  •  Halaman {PAGE} dari {NUMPAGES}',['size'=>8,'color'=>'66788A'],['alignment'=>'center']);
        $section->addTextBreak(5); $section->addText('IALOS EDUCATION',['size'=>12,'bold'=>true,'color'=>'5B4BDB']);
        $section->addText($s['cover']['title'],['name'=>'Aptos Display','size'=>28,'bold'=>true,'color'=>'102A43'],['spaceBefore'=>500,'spaceAfter'=>180]);
        $section->addText($s['cover']['unit'],['size'=>16,'color'=>'334E68']); $section->addText($s['cover']['period'],['size'=>12,'color'=>'627D98']);
        $section->addTextBreak(8); $section->addText($s['cover']['code'],['size'=>10,'bold'=>true,'color'=>'5B4BDB']); $section->addPageBreak();
        self::docxSection($section,'Identity',[$s['identity']],['code','title','unit_name','period_name','effective_from','effective_to','status']);
        self::docxSection($section,'Approval',array_map(static function($role,$r){return ['role'=>ucfirst($role),'name'=>$r['name'],'at'=>$r['at']];},array_keys($s['approval']),$s['approval']),['role','name','at']);
        self::docxSection($section,'School Characteristics',$s['school_characteristics'],['title','summary','finding','source_type','source_reference']);
        self::docxSection($section,'Vision',$s['vision'],['statement']); self::docxSection($section,'Mission',$s['mission'],['statement']); self::docxSection($section,'Goals',$s['goals'],['statement','measure','target_value']);
        self::docxSection($section,'Learning Organization',$s['learning_organization'],['category','title','description','delivery_model']);
        self::docxSection($section,'Intracurricular',$s['intracurricular'],['title','description']); self::docxSection($section,'Cocurricular',$s['cocurricular'],['title','description']); self::docxSection($section,'Extracurricular',$s['extracurricular'],['title','description']);
        self::docxSection($section,'Learning Planning',$s['learning_planning'],['section_code','completion_percent','status','notes']);
        self::docxSection($section,'Evaluation',$s['evaluation'],['evaluation_period','objective','target_value','actual_value','finding','root_cause','decision']);
        self::docxSection($section,'Improvement Actions',$s['improvement_actions'],['title','description','owner_name','owner_role_code','due_date','success_indicator','status']);
        self::docxSection($section,'Appendices',$s['appendices'],['section_code','status','notes']);
        $section->addTitle('Compliance Summary',1); $section->addText('Overall: '.($s['compliance_summary']['run']['overall_status']??'NOT_APPLICABLE'),['bold'=>true]); self::docxTable($section,$s['compliance_summary']['results'],['rule_code','rule_class','result_status','regulation_version','severity','suggested_action']);
        self::docxSection($section,'Evidence Index',$s['evidence_index'],['evidence_type','source_name','evidence_date','description','reference_uri','file_hash']);
        IOFactory::createWriter($phpWord,'Word2007')->save($path);
    }

    private static function docxSection($section,string $title,array $rows,array $columns): void { $section->addTitle($title,1); if(!$rows){$section->addText('Belum ada data terstruktur.',['italic'=>true,'color'=>'7B8794']);return;} self::docxTable($section,$rows,$columns); }
    private static function docxTable($section,array $rows,array $columns): void
    {
        if(!$rows)return;
        if(count($columns)>5){
            foreach($rows as $index=>$row){
                $section->addText('Record '.($index+1),['bold'=>true,'color'=>'5B4BDB'],['spaceBefore'=>100,'spaceAfter'=>50]);
                $table=$section->addTable(['borderSize'=>6,'borderColor'=>'D9E2EC','cellMargin'=>100,'cantSplit'=>true]);
                foreach($columns as $c){$table->addRow();$table->addCell(2200,['bgColor'=>'EEF2FF'])->addText(ucwords(str_replace('_',' ',$c)),['bold'=>true,'color'=>'334E68','size'=>9]);$table->addCell(6900)->addText((string)($row[$c]??'—'),['size'=>9]);}
                $section->addTextBreak();
            }
            return;
        }
        $table=$section->addTable(['borderSize'=>6,'borderColor'=>'D9E2EC','cellMargin'=>100]); $table->addRow(); foreach($columns as $c)$table->addCell()->addText(ucwords(str_replace('_',' ',$c)),['bold'=>true,'color'=>'334E68']); foreach($rows as $row){$table->addRow();foreach($columns as $c)$table->addCell()->addText((string)($row[$c]??'—'),['size'=>9]);} $section->addTextBreak();
    }

    private static function writePdf(array $s,string $path,string $revision): void
    {
        $options=new Options(); $options->set('isRemoteEnabled',false); $options->set('defaultFont','DejaVu Sans'); $dompdf=new Dompdf($options);
        $html='<html><head><meta charset="UTF-8"><style>@page{margin:65px 55px}body{font-family:DejaVu Sans;color:#18314f;font-size:10px}h1{font-size:22px;color:#243B53;border-bottom:2px solid #6c5ce7;padding-bottom:6px;page-break-after:avoid}h2{font-size:14px;color:#5B4BDB}table{border-collapse:collapse;table-layout:fixed;width:100%;margin:8px 0 18px;font-size:8.5px}th,td{border:1px solid #d9e2ec;padding:5px;vertical-align:top;overflow-wrap:anywhere;word-break:break-word}th{background:#eef2ff;text-align:left}.cover{padding-top:180px}.cover h1{font-size:32px;border:0}.muted{color:#627d98}.page{page-break-before:always}.footer{position:fixed;bottom:-42px;left:0;right:0;text-align:center;color:#7b8794;font-size:8px}</style></head><body><div class="footer">IALOS Education • '.self::h($revision).'</div><div class="cover"><div class="muted">IALOS EDUCATION · DIGITAL KSP</div><h1>'.self::h($s['cover']['title']).'</h1><h2>'.self::h($s['cover']['unit']).'</h2><p>'.self::h($s['cover']['period']).' · '.self::h($s['cover']['code']).'</p></div><div class="page">';
        $parts=[['Identity',[$s['identity']],['code','title','unit_name','period_name','effective_from','effective_to','status']],['Approval',array_map(static function($role,$r){return ['role'=>ucfirst($role),'name'=>$r['name'],'at'=>$r['at']];},array_keys($s['approval']),$s['approval']),['role','name','at']],['School Characteristics',$s['school_characteristics'],['title','summary','finding','source_type','source_reference']],['Vision',$s['vision'],['statement']],['Mission',$s['mission'],['statement']],['Goals',$s['goals'],['statement','measure','target_value']],['Learning Organization',$s['learning_organization'],['category','title','description','delivery_model']],['Intracurricular',$s['intracurricular'],['title','description']],['Cocurricular',$s['cocurricular'],['title','description']],['Extracurricular',$s['extracurricular'],['title','description']],['Learning Planning',$s['learning_planning'],['section_code','completion_percent','status','notes']],['Evaluation',$s['evaluation'],['evaluation_period','objective','target_value','actual_value','finding','root_cause','decision']],['Improvement Actions',$s['improvement_actions'],['title','description','owner_name','owner_role_code','due_date','success_indicator','status']],['Appendices',$s['appendices'],['section_code','status','notes']]];
        foreach($parts as $part)$html.=self::htmlSection($part[0],$part[1],$part[2]);
        $html.='<h1>Compliance Summary</h1><p><strong>Overall: '.self::h($s['compliance_summary']['run']['overall_status']??'NOT_APPLICABLE').'</strong></p>'.self::htmlTable($s['compliance_summary']['results'],['rule_code','rule_class','result_status','regulation_version','severity','suggested_action']);
        $html.=self::htmlSection('Evidence Index',$s['evidence_index'],['evidence_type','source_name','evidence_date','description','reference_uri','file_hash']).'</div></body></html>';
        $dompdf->loadHtml($html,'UTF-8'); $dompdf->setPaper('A4','portrait'); $dompdf->render(); file_put_contents($path,$dompdf->output());
    }
    private static function htmlSection(string $title,array $rows,array $columns): string { return '<h1>'.self::h($title).'</h1>'.($rows?self::htmlTable($rows,$columns):'<p class="muted"><em>Belum ada data terstruktur.</em></p>'); }
    private static function htmlTable(array $rows,array $columns): string { if(!$rows)return ''; $html='<table><thead><tr>';foreach($columns as $c)$html.='<th>'.self::h(ucwords(str_replace('_',' ',$c))).'</th>';$html.='</tr></thead><tbody>';foreach($rows as $row){$html.='<tr>';foreach($columns as $c)$html.='<td>'.nl2br(self::h((string)($row[$c]??'—'))).'</td>';$html.='</tr>'; }return $html.'</tbody></table>'; }
    private static function h(string $value): string { return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
}
