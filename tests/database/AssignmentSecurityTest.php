<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\IsolatedDatabaseTestTrait;
use App\Services\UnitScopeService;
use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone2MasterSeeder;
use App\Database\Seeds\Milestone3CurriculumSeeder;
use App\Database\Seeds\Milestone4Seeder;
use Config\Database;

/**
 * @internal
 */
final class AssignmentSecurityTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use IsolatedDatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    private int $smpId = 1;
    private int $smaId = 2;
    private int $smpUserId = 10;

    protected function setUp(): void
    {
        parent::setUp();

        (new Milestone2MasterSeeder(new Database()))->run();
        (new Milestone3CurriculumSeeder(new Database()))->run();
        (new Milestone4Seeder(new Database()))->run();

        $db = Database::connect($this->DBGroup);

        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        $this->smpId = $smp ? (int)$smp['id'] : 1;

        $sma = $db->table('school_units')->where('code', 'SMA')->get()->getRowArray();
        $this->smaId = $sma ? (int)$sma['id'] : 2;

        // Create SMP Admin user (only has access to SMP unit)
        $user = $db->table('users')->where('id', $this->smpUserId)->get()->getRowArray();
        if (!$user) {
            $db->table('users')->insert([
                'id'                   => $this->smpUserId,
                'uuid'                 => '00000000-0000-0000-0000-000000000999',
                'username'             => 'admin_smp',
                'email'                => 'adminsmp@test.com',
                'full_name'            => 'Admin SMP',
                'password_hash'        => password_hash('Pass12345!', PASSWORD_BCRYPT),
                'is_active'            => 1,
                'must_change_password' => 0,
                'created_at'           => date('Y-m-d H:i:s'),
            ]);
        }

        // Assign SMP unit access only
        $db->table('user_unit_access')->where('user_id', $this->smpUserId)->delete();
        $db->table('user_unit_access')->insert([
            'user_id'      => $this->smpUserId,
            'unit_id'      => $this->smpId,
            'access_level' => 'ADMIN',
            'is_default'   => 1,
        ]);

        // Assign admin role
        $role = $db->table('roles')->where('code', 'admin')->get()->getRowArray();
        if ($role) {
            $db->table('user_roles')->where('user_id', $this->smpUserId)->delete();
            $db->table('user_roles')->insert([
                'user_id' => $this->smpUserId,
                'role_id' => $role['id'],
                'unit_id' => $this->smpId,
            ]);
        }
    }

    public function testSmpAdminAccessingSmaUnitDirectlyFails(): void
    {
        session()->set([
            'logged_in'      => true,
            'user_id'        => $this->smpUserId,
            'username'       => 'admin_smp',
            'active_unit_id' => $this->smpId,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Anda tidak memiliki akses ke unit sekolah yang diminta.');

        UnitScopeService::assertUnit($this->smaId);
    }
}
