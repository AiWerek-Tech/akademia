<?php
namespace Tests\Database;
use App\Database\Seeds\CoreSeeder; use App\Database\Seeds\EducationFoundationSeeder; use CodeIgniter\Test\CIUnitTestCase; use Config\Database; use Tests\Support\IsolatedDatabaseTestTrait;
final class GraduateProfileDimensionTest extends CIUnitTestCase { use IsolatedDatabaseTestTrait; protected $migrate=true; protected $namespace='App'; protected $seed=CoreSeeder::class;
public function testEightOfficialDimensionsHaveStableOrder():void { (new EducationFoundationSeeder(new Database()))->run(); $rows=$this->db->table('graduate_profile_dimensions')->orderBy('sort_order')->get()->getResultArray(); $this->assertSame(['FAITH','CITIZENSHIP','CRITICAL_REASONING','CREATIVITY','COLLABORATION','INDEPENDENCE','HEALTH','COMMUNICATION'],array_column($rows,'code')); }
}

