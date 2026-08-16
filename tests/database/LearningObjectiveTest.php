<?php
namespace Tests\Database;
use App\Database\Seeds\CoreSeeder; use App\Services\LearningObjectiveService; use CodeIgniter\Test\CIUnitTestCase; use Tests\Support\EducationFoundationFixtureTrait; use Tests\Support\IsolatedDatabaseTestTrait;
final class LearningObjectiveTest extends CIUnitTestCase { use IsolatedDatabaseTestTrait, EducationFoundationFixtureTrait; protected $migrate=true; protected $namespace='App'; protected $seed=CoreSeeder::class; protected function setUp():void { parent::setUp(); $this->seedEducationFoundationFixture(); }
public function testNationalObjectiveAndCriterionAreSemanticRecords():void { $tp=$this->createTestObjective(); $criterion=LearningObjectiveService::addCriterion($tp['uuid'],['description'=>'Menjelaskan hasil dekomposisi secara runtut.','sort_order'=>1]); $this->assertNull($tp['unit_id']); $this->assertSame('NATIONAL',$tp['source_level']); $this->assertSame($tp['id'],$criterion['learning_objective_id']); }
}
