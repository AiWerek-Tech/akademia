<?php
namespace Tests\Database;
use App\Database\Seeds\CoreSeeder; use App\Services\LearningObjectiveService; use CodeIgniter\Test\CIUnitTestCase; use Tests\Support\EducationFoundationFixtureTrait; use Tests\Support\IsolatedDatabaseTestTrait;
final class LearningObjectiveAdaptationTest extends CIUnitTestCase { use IsolatedDatabaseTestTrait, EducationFoundationFixtureTrait; protected $migrate=true; protected $namespace='App'; protected $seed=CoreSeeder::class; protected function setUp():void { parent::setUp(); $this->seedEducationFoundationFixture(); }
public function testSchoolAdaptationPreservesNationalLineage():void { $national=$this->createTestObjective(); $adapted=LearningObjectiveService::adapt($national['uuid'],['unit_id'=>$this->unitId,'source_level'=>'SCHOOL','code'=>'TP-INF-01-SMA','statement'=>'Konteks sekolah']); $this->assertSame($national['id'],$adapted['parent_objective_id']); $this->assertSame($this->unitId,(int)$adapted['unit_id']); $this->assertSame('SCHOOL',$adapted['source_level']); }
public function testPublishedNationalObjectiveIsImmutable():void { $tp=$this->createTestObjective(); $this->db->table('learning_objectives_tp')->where('id',$tp['id'])->update(['status'=>'PUBLISHED']); $this->expectException(\RuntimeException::class); LearningObjectiveService::update($tp['uuid'],['statement'=>'ubah','revision_number'=>1]); }
}

