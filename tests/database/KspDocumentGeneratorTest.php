<?php
namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\KspDocumentGeneratorService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\EducationFoundationFixtureTrait;
use Tests\Support\IsolatedDatabaseTestTrait;
use Tests\Support\KspPhase2FixtureTrait;

final class KspDocumentGeneratorTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait,EducationFoundationFixtureTrait,KspPhase2FixtureTrait;
    protected $migrate=true;protected $namespace='App';protected $seed=CoreSeeder::class;private array $pilot;
    protected function setUp():void{parent::setUp();$this->pilot=$this->seedKspPhase2Fixture();}
    public function testGeneratesOpenableDocxAndPdfWithProvenance():void
    {
        \App\Services\KspComplianceService::preview($this->pilot['version']['uuid']);$this->completeWorkflow($this->pilot['version']['uuid']);
        $docx=KspDocumentGeneratorService::generate($this->pilot['version']['uuid'],'DOCX');$pdf=KspDocumentGeneratorService::generate($this->pilot['version']['uuid'],'PDF');
        foreach([$docx,$pdf] as $row){foreach(['source_revision','source_snapshot_hash','generated_at','document_hash','storage_reference'] as $field)$this->assertNotEmpty($row[$field]);$this->assertArrayHasKey('generated_by',$row);$file=KspDocumentGeneratorService::file($this->pilot['version']['uuid'],$row['uuid']);$this->assertSame($row['document_hash'],hash_file('sha256',$file['path']));}
        $docxFile=KspDocumentGeneratorService::file($this->pilot['version']['uuid'],$docx['uuid'])['path'];$zip=new \ZipArchive();$this->assertTrue($zip->open($docxFile)===true);$xml=$zip->getFromName('word/document.xml');$zip->close();$this->assertStringContainsString('KSP SMP Tahun Pelajaran 2026/2027',$xml);$this->assertStringContainsString('Evidence Index',$xml);
        $pdfFile=KspDocumentGeneratorService::file($this->pilot['version']['uuid'],$pdf['uuid'])['path'];$this->assertSame('%PDF',file_get_contents($pdfFile,false,null,0,4));$this->assertGreaterThan(1000,filesize($pdfFile));
    }
    public function testHistoricalOutputsAreNeverOverwritten():void
    {
        $this->completeWorkflow($this->pilot['version']['uuid']);$one=KspDocumentGeneratorService::generate($this->pilot['version']['uuid'],'PDF');$two=KspDocumentGeneratorService::generate($this->pilot['version']['uuid'],'PDF');
        $this->assertNotSame($one['uuid'],$two['uuid']);$this->assertNotSame($one['storage_reference'],$two['storage_reference']);$this->assertCount(2,KspDocumentGeneratorService::all($this->pilot['version']['uuid']));
    }
    public function testRejectsGenerationBeforeApproval():void
    {
        $this->expectException(\RuntimeException::class);KspDocumentGeneratorService::generate($this->pilot['version']['uuid'],'PDF');
    }
}
