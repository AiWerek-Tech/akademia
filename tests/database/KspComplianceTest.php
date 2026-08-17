<?php
namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\KspComplianceService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\EducationFoundationFixtureTrait;
use Tests\Support\IsolatedDatabaseTestTrait;
use Tests\Support\KspPhase2FixtureTrait;

final class KspComplianceTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait,EducationFoundationFixtureTrait,KspPhase2FixtureTrait;
    protected $migrate=true; protected $namespace='App'; protected $seed=CoreSeeder::class; private array $pilot;
    protected function setUp():void{parent::setUp();$this->pilot=$this->seedKspPhase2Fixture();}

    public function testPreviewPersistsTypedResultsAndProvenance():void
    {
        $preview=KspComplianceService::preview($this->pilot['version']['uuid']);
        $this->assertSame('PASS',$preview['run']['overall_status']); $this->assertGreaterThanOrEqual(4,$preview['run']['total_count']);
        foreach($preview['results'] as $r){foreach(['rule_code','regulation_version','expected','actual','severity','source_reference','provenance_json','suggested_action'] as $field)$this->assertNotSame('',(string)$r[$field]);$this->assertContains($r['result_status'],KspComplianceService::RESULT_STATUSES);$this->assertContains($r['rule_class'],KspComplianceService::RULE_CLASSES);}
        $legal=array_values(array_filter($preview['results'],static fn($r)=>$r['rule_class']==='LEGAL_REQUIRED'));
        $this->assertSame('NOT_APPLICABLE',$legal[0]['result_status']); $this->assertSame('LEGAL_REGISTRY_UNCONFIGURED',$legal[0]['rule_code']);
    }

    public function testPreviewHistoryIsImmutable():void
    {
        $before=$this->db->table('ksp_compliance_runs')->where('ksp_version_id',$this->pilot['version']['id'])->countAllResults();$one=KspComplianceService::preview($this->pilot['version']['uuid']);$two=KspComplianceService::preview($this->pilot['version']['uuid']);
        $this->assertNotSame($one['run']['uuid'],$two['run']['uuid']);$this->assertSame($before+2,$this->db->table('ksp_compliance_runs')->where('ksp_version_id',$this->pilot['version']['id'])->countAllResults());
    }

    public function testLegalClassCannotBeAttachedToInternalPolicy():void
    {
        $version=$this->db->table('regulation_versions rv')->select('rv.uuid')->join('regulations r','r.id=rv.regulation_id')->where('r.code','IALOS-KSP-QA')->get()->getRowArray();
        $this->expectException(\InvalidArgumentException::class);KspComplianceService::registerRule($version['uuid'],['rule_code'=>'FAKE-LEGAL','rule_class'=>'LEGAL_REQUIRED','category'=>'LEGAL','severity'=>'ERROR','evaluator_key'=>'STATUS_IN','expected'=>['statuses'=>['LOCKED']],'suggested_action'=>'Do not use']);
    }
}
