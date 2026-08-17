<?php
namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\DigitalKspService;
use App\Services\KspDocumentGeneratorService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\EducationFoundationFixtureTrait;
use Tests\Support\IsolatedDatabaseTestTrait;
use Tests\Support\KspPhase2FixtureTrait;

final class KspSecurityTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait,EducationFoundationFixtureTrait,KspPhase2FixtureTrait,FeatureTestTrait;
    protected $migrate=true;protected $namespace='App';protected $seed=CoreSeeder::class;private array $pilot;
    protected function setUp():void{parent::setUp();$this->pilot=$this->seedKspPhase2Fixture();}
    public function testGuestCannotOpenKsp():void{$this->withSession([])->get('education/ksp')->assertRedirectTo(base_url('login'));}
    public function testCrossUnitUuidIsRejected():void
    {
        $sma=$this->activateSmaAdminScope();
        $this->expectException(\Throwable::class);DigitalKspService::byUuid($this->pilot['version']['uuid']);
    }
    public function testStaleRevisionAndLockedMutationAreRejected():void
    {
        try{DigitalKspService::transition($this->pilot['version']['uuid'],'REVIEW',999);$this->fail('Stale revision accepted');}catch(\Throwable $e){$this->assertNotEmpty($e->getMessage());}
        $this->completeWorkflow($this->pilot['version']['uuid']);$this->expectException(\RuntimeException::class);DigitalKspService::addContext($this->pilot['version']['uuid'],['context_type'=>'INTERNAL','title'=>'Locked','summary'=>'No']);
    }
    public function testCrossUnitDocumentExportIsRejected():void
    {
        $this->completeWorkflow($this->pilot['version']['uuid']);$document=KspDocumentGeneratorService::generate($this->pilot['version']['uuid'],'PDF');$this->activateSmaAdminScope();
        $this->expectException(\Throwable::class);KspDocumentGeneratorService::file($this->pilot['version']['uuid'],$document['uuid']);
    }
    public function testRolePermissionMatrixSeparatesResponsibilities():void
    {
        $expected=['super_admin'=>['view','manage','review','approve','lock','export'],'admin_smp'=>['view','manage','export'],'admin_sma'=>['view','manage','export'],'wakasek_kurikulum'=>['view','manage','review','export'],'kepala_sekolah'=>['view','review','approve','lock','export'],'guru'=>['view'],'viewer_yayasan'=>['view','export']];
        foreach($expected as $role=>$suffixes){$actual=array_column($this->db->table('role_permissions rp')->select('p.code')->join('permissions p','p.id=rp.permission_id')->join('roles r','r.id=rp.role_id')->where('r.code',$role)->like('p.code','ksp.','after')->get()->getResultArray(),'code');$this->assertEqualsCanonicalizing(array_map(static fn($s)=>'ksp.'.$s,$suffixes),$actual,$role);}
    }
    private function activateSmaAdminScope():array
    {
        $sma=$this->db->table('school_units')->where('code','SMA')->get()->getRowArray();$super=$this->db->table('roles')->where('code','super_admin')->get()->getRowArray();$admin=$this->db->table('roles')->where('code','admin_sma')->get()->getRowArray();
        $this->db->table('user_roles')->where(['user_id'=>1,'role_id'=>$super['id']])->delete();if($this->db->table('user_roles')->where(['user_id'=>1,'role_id'=>$admin['id'],'unit_id'=>$sma['id']])->countAllResults()===0)$this->db->table('user_roles')->insert(['user_id'=>1,'role_id'=>$admin['id'],'unit_id'=>$sma['id'],'created_at'=>date('Y-m-d H:i:s')]);
        $this->db->table('user_unit_access')->where(['user_id'=>1,'unit_id'=>$this->pilot['unit']['id']])->delete();session()->set(['active_unit_id'=>(int)$sma['id'],'role_code'=>'admin_sma','all_role_codes'=>['admin_sma']]);return $sma;
    }
}
