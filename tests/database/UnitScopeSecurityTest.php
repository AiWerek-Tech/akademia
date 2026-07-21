<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\UnitScopeService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;
use RuntimeException;

/** @internal */
final class UnitScopeSecurityTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    public function testUserCannotSelectAnUnassignedUnit(): void
    {
        $db = Database::connect($this->DBGroup);
        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        $sma = $db->table('school_units')->where('code', 'SMA')->get()->getRowArray();

        $db->table('users')->insert([
            'uuid' => \App\Services\UuidService::v4(),
            'username' => 'scope_test',
            'email' => 'scope-test@example.test',
            'full_name' => 'Scope Test',
            'password_hash' => password_hash('ScopeTestPassword!2026', PASSWORD_BCRYPT),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $userId = (int) $db->insertID();
        $db->table('user_unit_access')->insert([
            'user_id' => $userId,
            'unit_id' => $smp['id'],
            'is_default' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        session()->set([
            'logged_in' => true,
            'user_id' => $userId,
            'active_unit_id' => (int) $smp['id'],
        ]);

        $this->assertSame((int) $smp['id'], UnitScopeService::resolveUnit($smp['id']));
        $this->assertSame([(int) $smp['id']], UnitScopeService::accessibleUnitIds());

        $this->expectException(RuntimeException::class);
        UnitScopeService::resolveUnit($sma['id']);
    }
}
