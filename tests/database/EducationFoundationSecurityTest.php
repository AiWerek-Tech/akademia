<?php
namespace Tests\Database;
use App\Database\Seeds\CoreSeeder; use App\Exceptions\ConcurrencyException; use App\Services\LearningObjectiveService; use CodeIgniter\Test\CIUnitTestCase; use Tests\Support\EducationFoundationFixtureTrait; use Tests\Support\IsolatedDatabaseTestTrait;
final class EducationFoundationSecurityTest extends CIUnitTestCase { use IsolatedDatabaseTestTrait, EducationFoundationFixtureTrait; protected $migrate=true; protected $namespace='App'; protected $seed=CoreSeeder::class; protected function setUp():void { parent::setUp(); $this->seedEducationFoundationFixture(); }
public function testCrossUnitAdaptationIsDenied():void { $tp=$this->createTestObjective(); $this->expectException(\App\Exceptions\AuthorizationException::class); LearningObjectiveService::adapt($tp['uuid'],['unit_id'=>$this->otherUnitId,'source_level'=>'SCHOOL','statement'=>'lintas unit']); }
public function testStaleRevisionRaisesConcurrencyConflict():void { $tp=LearningObjectiveService::adapt($this->createTestObjective()['uuid'],['unit_id'=>$this->unitId,'source_level'=>'SCHOOL','statement'=>'v1']); LearningObjectiveService::update($tp['uuid'],['statement'=>'v2','revision_number'=>1]); $this->expectException(ConcurrencyException::class); LearningObjectiveService::update($tp['uuid'],['statement'=>'stale','revision_number'=>1]); }
}
