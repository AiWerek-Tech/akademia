<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone4Seeder;
use Config\Database;

/**
 * @internal
 */
final class Milestone4SeederTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    public function testSeederRunsAndIsIdempotent(): void
    {
        $db = Database::connect($this->DBGroup);
        $seeder = new Milestone4Seeder(new Database());
        $seeder->run();

        // Check feature flags
        $assignFlag = $db->table('feature_flags')->where('code', 'assignments')->get()->getRowArray();
        $this->assertNotNull($assignFlag);
        $this->assertEquals(1, (int)$assignFlag['enabled']);

        // Check permissions registered
        $perm = $db->table('permissions')->where('code', 'assignments.view')->get()->getRowArray();
        $this->assertNotNull($perm);

        $permWorkload = $db->table('permissions')->where('code', 'workloads.view')->get()->getRowArray();
        $this->assertNotNull($permWorkload);

        // Check duty types seeded
        $duty = $db->table('additional_duty_types')->where('code', 'HOMEROOM_TEACHER')->get()->getRowArray();
        $this->assertNotNull($duty);

        // Run seeder again to verify idempotency
        $seeder->run();
        $count = $db->table('additional_duty_types')->where('code', 'HOMEROOM_TEACHER')->countAllResults();
        $this->assertEquals(1, $count);
    }
}
