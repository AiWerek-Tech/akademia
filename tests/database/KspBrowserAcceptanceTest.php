<?php
namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\KspComplianceService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\EducationFoundationFixtureTrait;
use Tests\Support\IsolatedDatabaseTestTrait;
use Tests\Support\KspPhase2FixtureTrait;

final class KspBrowserAcceptanceTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait,EducationFoundationFixtureTrait,KspPhase2FixtureTrait,FeatureTestTrait;
    protected $migrate=true;protected $namespace='App';protected $seed=CoreSeeder::class;private array $pilot;
    protected function setUp():void{parent::setUp();$this->pilot=$this->seedKspPhase2Fixture();}
    public function testPilotPagesRenderAsSeparateIntegratedDestinations():void
    {
        $paths=['','/context','/vision-goals','/organization','/evaluation','/evidence','/compliance','/documents'];foreach($paths as $path)$this->withSession(session()->get())->get('education/ksp/'.$this->pilot['version']['uuid'].$path)->assertOK();
    }
    public function testPilotReadinessComplianceAndWorkflowCloseEndToEnd():void
    {
        $detail=\App\Services\DigitalKspService::detail($this->pilot['version']['uuid']);$this->assertSame(100,$detail['readiness']['overall']);$this->assertCount(5,\App\Services\KspEvidenceService::all($this->pilot['version']['uuid']));$this->assertSame('PASS',KspComplianceService::preview($this->pilot['version']['uuid'])['run']['overall_status']);$final=$this->completeWorkflow($this->pilot['version']['uuid']);$this->assertSame('LOCKED',$final['status']);
    }
}
