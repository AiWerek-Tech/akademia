<?php
namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\KspEvidenceService;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\EducationFoundationFixtureTrait;
use Tests\Support\IsolatedDatabaseTestTrait;
use Tests\Support\KspPhase2FixtureTrait;

final class KspEvidenceTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait,EducationFoundationFixtureTrait,KspPhase2FixtureTrait;
    protected $migrate=true;protected $namespace='App';protected $seed=CoreSeeder::class;private array $pilot;
    protected function setUp():void{parent::setUp();$this->pilot=$this->seedKspPhase2Fixture();}
    public function testReferenceEvidenceSupportsEveryTargetType():void
    {
        $rows=KspEvidenceService::all($this->pilot['version']['uuid']);$this->assertCount(5,$rows);$this->assertEqualsCanonicalizing(KspEvidenceService::TARGETS,array_values(array_unique(array_column($rows,'target_type'))));
        foreach($rows as $r){$this->assertContains($r['evidence_type'],KspEvidenceService::TYPES);$this->assertNotEmpty($r['source_name']);$this->assertNotEmpty($r['evidence_date']);$this->assertNotEmpty($r['description']);$this->assertNotEmpty($r['reference_uri']);}
    }
    public function testFileEvidenceStoresHashAndPrivateReference():void
    {
        $context=$this->db->table('school_context_snapshots')->where('ksp_version_id',$this->pilot['version']['id'])->get()->getRowArray();$tmp=tempnam(sys_get_temp_dir(),'ksp');file_put_contents($tmp,'synthetic pilot evidence');
        $file=new class($tmp,'pilot.txt','text/plain',filesize($tmp),UPLOAD_ERR_OK) extends UploadedFile { public function isValid():bool{return true;} public function move(string $targetPath,?string $name=null,bool $overwrite=false){if(!is_dir($targetPath))mkdir($targetPath,0750,true);$name=$name??$this->getName();$destination=rtrim($targetPath,'/\\').DIRECTORY_SEPARATOR.$name;rename($this->path,$destination);$this->hasMoved=true;$this->name=$name;return true;} };
        $row=KspEvidenceService::attach($this->pilot['version']['uuid'],['target_type'=>'SCHOOL_CONTEXT','target_uuid'=>$context['uuid'],'evidence_type'=>'OTHER','source'=>'Acceptance fixture','date'=>'2026-08-17','description'=>'Synthetic file fixture'],$file);
        $this->assertSame(hash('sha256','synthetic pilot evidence'),$row['file_hash']);$this->assertStringStartsWith('uploads/ksp-evidence/',$row['storage_reference']);$this->assertFileExists(KspEvidenceService::file($this->pilot['version']['uuid'],$row['uuid'])['path']);
    }
    public function testRejectsCrossKspTargetAndLockedMutation():void
    {
        $other=\App\Services\DigitalKspService::createVersion(['unit_id'=>$this->pilot['unit']['id'],'academic_period_id'=>$this->pilot['period']['id'],'code'=>'KSP-OTHER','title'=>'Other']);$target=$this->db->table('ksp_section_statuses')->where('ksp_version_id',$other['id'])->get()->getRowArray();
        try{KspEvidenceService::attach($this->pilot['version']['uuid'],['target_type'=>'KSP_SECTION','target_uuid'=>$target['uuid'],'evidence_type'=>'OTHER','source'=>'x','date'=>'2026-08-17','description'=>'x','reference'=>'pilot://x']);$this->fail('Cross target accepted');}catch(\InvalidArgumentException $e){$this->assertStringContainsStringIgnoringCase('target',$e->getMessage());}
        $this->completeWorkflow($this->pilot['version']['uuid']);$this->expectException(\RuntimeException::class);KspEvidenceService::attach($this->pilot['version']['uuid'],['target_type'=>'KSP_SECTION','target_uuid'=>$this->db->table('ksp_section_statuses')->where('ksp_version_id',$this->pilot['version']['id'])->get()->getRowArray()['uuid'],'evidence_type'=>'OTHER','source'=>'x','date'=>'2026-08-17','description'=>'x','reference'=>'pilot://locked']);
    }
}
