<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone5Seeder;
use App\Services\UnitScopeService;
use Config\Database;

/**
 * @internal
 */
final class ScheduleSecurityTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    public function testUnitScopeServiceBoundaries(): void
    {
        $this->seed(Milestone5Seeder::class);
        $db = Database::connect($this->DBGroup);
        $now = date('Y-m-d H:i:s');

        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        $sma = $db->table('school_units')->where('code', 'SMA')->get()->getRowArray();

        $this->assertNotEmpty($smp);
        $this->assertNotEmpty($sma);

        // Insert test user
        $db->table('users')->insert([
            'uuid'          => '10000000-0000-4000-8000-000000000099',
            'username'      => 'unituser',
            'full_name'     => 'Unit Access User',
            'password_hash' => password_hash('secret123', PASSWORD_BCRYPT),
            'is_active'     => 1,
            'created_at'    => $now,
        ]);
        $userId = (int)$db->insertID();

        // Assign access to SMP only
        $db->table('user_unit_access')->insert([
            'user_id'    => $userId,
            'unit_id'    => $smp['id'],
            'created_at' => $now,
        ]);

        $accessibleIds = UnitScopeService::accessibleUnitIds($userId);
        $this->assertContains((int)$smp['id'], $accessibleIds);
        $this->assertNotContains((int)$sma['id'], $accessibleIds);

        $accessibleUnits = UnitScopeService::accessibleUnits($userId);
        $this->assertCount(1, $accessibleUnits);
        $this->assertEquals('SMP', $accessibleUnits[0]['code']);
    }
}
