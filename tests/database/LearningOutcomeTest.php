<?php
namespace Tests\Database;
use App\Database\Seeds\CoreSeeder; use App\Services\LearningOutcomeService; use CodeIgniter\Test\CIUnitTestCase; use Tests\Support\EducationFoundationFixtureTrait; use Tests\Support\IsolatedDatabaseTestTrait;
final class LearningOutcomeTest extends CIUnitTestCase { use IsolatedDatabaseTestTrait, EducationFoundationFixtureTrait; protected $migrate=true; protected $namespace='App'; protected $seed=CoreSeeder::class; protected function setUp():void { parent::setUp(); $this->seedEducationFoundationFixture(); }
public function testOutcomeElementHierarchyAndAtomicRevision():void { $cp=$this->createTestOutcome(); $el=LearningOutcomeService::addElement($cp['uuid'],['code'=>'BK','name'=>'Berpikir Komputasional','sort_order'=>1]); $updated=LearningOutcomeService::update($cp['uuid'],['statement'=>'Pernyataan revisi','revision_number'=>1]); $this->assertSame($cp['id'],$el['learning_outcome_id']); $this->assertSame('2',(string)$updated['revision_number']); }
}

