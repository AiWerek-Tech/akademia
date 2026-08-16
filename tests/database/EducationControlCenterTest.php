<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\EducationControlCenterService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\EducationFoundationFixtureTrait;
use Tests\Support\IsolatedDatabaseTestTrait;

final class EducationControlCenterTest extends CIUnitTestCase
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
        $role = $this->db->table('roles')->where('code', 'super_admin')->get()->getRowArray();
        if ($role && $this->db->table('user_roles')->where(['user_id' => 1, 'role_id' => $role['id']])->countAllResults() === 0) {
            $this->db->table('user_roles')->insert(['user_id' => 1, 'role_id' => $role['id'], 'unit_id' => $this->unitId, 'created_at' => date('Y-m-d H:i:s')]);
        }
        session()->set([
            'logged_in' => true,
            'role_code' => 'super_admin',
            'all_role_codes' => ['super_admin'],
            'username' => 'ialos_test',
            'full_name' => 'IALOS Test',
            'must_change_password' => 0,
        ]);
    }

    public function testControlCenterBuildsRoleAwareMetrics(): void
    {
        $control = EducationControlCenterService::build();

        $this->assertSame('platform', $control['persona']);
        $this->assertSame(8, $control['metrics']['dimensions']);
        $this->assertGreaterThanOrEqual(1, $control['unit_count']);
        $this->assertArrayHasKey('sequence_statuses', $control);
    }

    public function testControlCenterAndDomainPagesRenderSeparately(): void
    {
        foreach ([
            'education',
            'references/regulations',
            'references/curriculum-sources',
            'references/graduate-profile',
            'curriculum/outcomes',
            'curriculum/objectives',
            'curriculum/sequences',
            'curriculum/coverage',
            'curriculum/learning-packs',
            'curriculum/education-imports',
        ] as $path) {
            $result = $this->withSession(session()->get())->get($path);
            $result->assertOK();
        }
    }
}
