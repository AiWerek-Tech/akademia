<?php
namespace Tests\Database;
use App\Database\Seeds\CoreSeeder; use App\Services\EducationFoundationImportService; use CodeIgniter\Test\CIUnitTestCase; use Tests\Support\EducationFoundationFixtureTrait; use Tests\Support\IsolatedDatabaseTestTrait;
final class EducationFoundationImportTest extends CIUnitTestCase { use IsolatedDatabaseTestTrait, EducationFoundationFixtureTrait; protected $migrate=true; protected $namespace='App'; protected $seed=CoreSeeder::class; protected function setUp():void { parent::setUp(); $this->seedEducationFoundationFixture(); }
public function testInvalidRowsRemainStagedAndCannotApply():void { $batch=EducationFoundationImportService::stage($this->unitId,$this->versionId,'invalid.json',[['entity_type'=>'CP','code'=>'CP-BROKEN']]); $this->assertSame('INVALID',$batch['status']); $this->assertSame(0,$this->db->table('learning_outcomes_cp')->countAllResults()); $this->expectException(\RuntimeException::class); EducationFoundationImportService::apply($batch['uuid']); }
public function testValidCpImportAppliesAtomically():void { $batch=EducationFoundationImportService::stage($this->unitId,$this->versionId,'valid.json',[['entity_type'=>'CP','subject_id'=>$this->subjectId,'grade_level_id'=>$this->gradeId,'code'=>'CP-IMPORT','phase'=>'E','statement'=>'CP hasil controlled import']]); $applied=EducationFoundationImportService::apply($batch['uuid']); $this->assertSame('APPLIED',$applied['status']); $this->assertSame(1,$this->db->table('learning_outcomes_cp')->where('code','CP-IMPORT')->countAllResults()); }
}

