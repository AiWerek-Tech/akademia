<?php
namespace Tests\Database;
use App\Database\Seeds\CoreSeeder; use App\Database\Seeds\EducationFoundationSeeder; use CodeIgniter\Test\CIUnitTestCase; use Config\Database; use Tests\Support\IsolatedDatabaseTestTrait;
final class EducationFoundationSeederTest extends CIUnitTestCase { use IsolatedDatabaseTestTrait; protected $migrate=true; protected $namespace='App'; protected $seed=CoreSeeder::class;
public function testSeederIsIdempotentAndSeedsExactlyEightDimensions():void { $s=new EducationFoundationSeeder(new Database()); $s->run(); $s->run(); $this->assertSame(8,$this->db->table('graduate_profile_dimensions')->countAllResults()); $this->assertSame(18,$this->db->table('permissions')->where('module','education_foundation')->countAllResults()); $this->assertSame(1,$this->db->table('feature_flags')->where('code','ialos_education_foundation')->countAllResults()); }
public function testNationalReferenceManagePermissionIsRestrictedToPlatformAdmin():void { (new EducationFoundationSeeder(new Database()))->run(); $permission=$this->db->table('permissions')->where('code','regulations.manage')->get()->getRowArray(); $roles=$this->db->table('role_permissions rp')->select('r.code')->join('roles r','r.id=rp.role_id')->where('rp.permission_id',$permission['id'])->get()->getResultArray(); $this->assertSame(['super_admin'],array_column($roles,'code')); }
}
