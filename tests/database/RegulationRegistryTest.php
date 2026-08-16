<?php
namespace Tests\Database;
use App\Database\Seeds\CoreSeeder; use App\Services\RegulationRegistryService; use CodeIgniter\Test\CIUnitTestCase; use Tests\Support\IsolatedDatabaseTestTrait;
final class RegulationRegistryTest extends CIUnitTestCase { use IsolatedDatabaseTestTrait; protected $migrate=true; protected $namespace='App'; protected $seed=CoreSeeder::class;
public function testPublishedRegulationVersionIsImmutable():void { $r=RegulationRegistryService::createRegulation(['code'=>'PERMENDIKDAS-TEST','title'=>'Regulasi Test','authority'=>'Kemendikdasmen','regulation_type'=>'PERATURAN']); $v=RegulationRegistryService::addVersion($r['uuid'],['document_hash'=>str_repeat('a',64)]); $published=RegulationRegistryService::publishVersion($v['uuid']); $this->assertSame('1',(string)$published['is_published']); $this->expectException(\RuntimeException::class); RegulationRegistryService::updateVersion($v['uuid'],['notes'=>'mutasi terlarang']); }
}

