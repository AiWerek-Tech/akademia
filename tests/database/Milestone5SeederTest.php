<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\IsolatedDatabaseTestTrait;
use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone5Seeder;
use Config\Database;

/**
 * @internal
 */
final class Milestone5SeederTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    public function testSeederPopulatesPermissionsAndConstraints(): void
    {
        $db = Database::connect($this->DBGroup);

        $this->seed(Milestone5Seeder::class);

        // Check permissions
        $permissions = [
            'schedules.view',
            'schedules.manage',
            'schedules.validate',
            'schedules.review',
            'schedules.approve',
            'schedules.lock',
            'schedules.generate',
            'schedules.import',
            'schedules.export',
            'schedules.revise',
            'availability.view',
            'availability.manage',
            'constraints.view',
            'constraints.manage',
        ];

        foreach ($permissions as $code) {
            $count = $db->table('permissions')->where('code', $code)->countAllResults();
            $this->assertGreaterThan(0, $count, "Permission {$code} should exist in permissions table.");
        }

        // Check constraints
        $constraints = [
            'HARD_TEACHER_DOUBLE_BOOKING',
            'HARD_CLASSROOM_DOUBLE_BOOKING',
            'HARD_ROOM_DOUBLE_BOOKING',
            'HARD_CROSS_UNIT_TEACHER_DOUBLE_BOOKING',
            'HARD_TEACHER_UNAVAILABLE',
            'HARD_ROOM_UNAVAILABLE',
            'HARD_ROOM_TYPE_MISMATCH',
            'SOFT_TEACHER_MAX_DAILY_HOURS',
            'SOFT_TEACHER_CONSECUTIVE_SLOTS',
            'SOFT_CLASSROOM_GAP_MINIMIZATION',
            'SOFT_PREFERRED_ROOM',
        ];

        foreach ($constraints as $code) {
            $count = $db->table('scheduling_constraints')->where('code', $code)->countAllResults();
            $this->assertGreaterThan(0, $count, "Constraint {$code} should exist in scheduling_constraints table.");
        }
    }
}
