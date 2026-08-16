<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\DigitalKspSeeder;
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
}
