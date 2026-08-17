<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\DigitalKspSeeder;
use App\Database\Seeds\NumeracyImprovementActionSeeder;
use App\Services\DigitalKspService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Tests\Support\EducationFoundationFixtureTrait;
use Tests\Support\IsolatedDatabaseTestTrait;

final class DigitalKspTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;
    use EducationFoundationFixtureTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEducationFoundationFixture();
        (new DigitalKspSeeder(new Database()))->run();
        $role = $this->db->table('roles')->where('code', 'super_admin')->get()->getRowArray();
        if ($this->db->table('user_roles')->where(['user_id'=>1,'role_id'=>$role['id']])->countAllResults() === 0) {
            $this->db->table('user_roles')->insert(['user_id'=>1,'role_id'=>$role['id'],'unit_id'=>$this->unitId,'created_at'=>date('Y-m-d H:i:s')]);
        }
        session()->set(['logged_in'=>true,'user_id'=>1,'role_code'=>'super_admin','all_role_codes'=>['super_admin'],'active_unit_id'=>$this->unitId,'active_period_id'=>$this->periodId,'must_change_password'=>0]);
    }

    public function testCreatesScopedVersionAndSectionProgress(): void
    {
        $version = DigitalKspService::createVersion(['unit_id'=>$this->unitId,'academic_period_id'=>$this->periodId,'code'=>'KSP-TEST','title'=>'KSP Terpadu']);
        $this->assertSame('DRAFT', $version['status']);
        $this->assertCount(9, DigitalKspService::detail($version['uuid'])['sections']);

        DigitalKspService::addContext($version['uuid'], ['context_type'=>'INTERNAL','title'=>'Kondisi Sekolah','summary'=>'Ringkasan berbasis data.']);
        $detail = DigitalKspService::detail($version['uuid']);
        $characteristics = array_values(array_filter($detail['sections'], static fn(array $row): bool => $row['section_code'] === 'CHARACTERISTICS'))[0];
        $this->assertSame('100', (string) $characteristics['completion_percent']);
    }

    public function testRejectsPrematureReviewAndRendersSeparatePages(): void
    {
        $version = DigitalKspService::createVersion(['unit_id'=>$this->unitId,'academic_period_id'=>$this->periodId,'code'=>'KSP-WEB','title'=>'KSP Web']);
        $this->expectException(\InvalidArgumentException::class);
        DigitalKspService::transition($version['uuid'], 'REVIEW', 1);
    }

    public function testKspPagesUseExistingAuthenticatedApplication(): void
    {
        $version = DigitalKspService::createVersion(['unit_id'=>$this->unitId,'academic_period_id'=>$this->periodId,'code'=>'KSP-PAGES','title'=>'KSP Pages']);
        foreach (['education/ksp','education/ksp/'.$version['uuid'],'education/ksp/'.$version['uuid'].'/context','education/ksp/'.$version['uuid'].'/vision-goals','education/ksp/'.$version['uuid'].'/organization','education/ksp/'.$version['uuid'].'/evaluation'] as $path) {
            $this->withSession(session()->get())->get($path)->assertOK();
        }
    }

    public function testImprovementActionPreservesOwnerIndicatorAndLifecycle(): void
    {
        $version=DigitalKspService::createVersion(['unit_id'=>$this->unitId,'academic_period_id'=>$this->periodId,'code'=>'KSP-ACTION','title'=>'KSP Action']);
        $evaluation=DigitalKspService::addEvaluation($version['uuid'],['evaluation_period'=>'Semester Ganjil','objective'=>'Peningkatan capaian numerasi','target_value'=>'80%','finding'=>'Di bawah target','root_cause'=>'Aktivitas kontekstual terbatas']);
        $action=DigitalKspService::addImprovementAction($version['uuid'],$evaluation['uuid'],['title'=>'Integrasikan literasi finansial','owner_role_code'=>'wakasek_kurikulum','due_date'=>'2026-10-31','success_indicator'=>'≥80% target mastery','status'=>'IN_PROGRESS']);

        $this->assertSame('wakasek_kurikulum',$action['owner_role_code']);
        $this->assertSame('≥80% target mastery',$action['success_indicator']);
        $this->assertSame((string)$this->unitId,(string)$action['unit_id']);
        $updated=DigitalKspService::updateImprovementAction($version['uuid'],$action['uuid'],['revision_number'=>1,'status'=>'COMPLETED','outcome'=>'Target tercapai']);
        $this->assertSame('COMPLETED',$updated['status']);
        $this->assertSame('2',(string)$updated['revision_number']);
    }

    public function testNumeracyPilotSeederIsIdempotent(): void
    {
        $seeder=new NumeracyImprovementActionSeeder(new Database());
        $seeder->run(); $seeder->run();
        $version=$this->db->table('ksp_versions')->where('code','KSP-SMP-2026-2027')->get()->getRowArray();
        $evaluation=$this->db->table('ksp_evaluations')->where(['ksp_version_id'=>$version['id'],'objective'=>'Peningkatan capaian numerasi'])->get()->getRowArray();
        $this->assertSame(1,$this->db->table('improvement_actions')->where('ksp_evaluation_id',$evaluation['id'])->countAllResults());
    }
}
