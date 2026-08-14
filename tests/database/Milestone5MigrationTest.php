<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\IsolatedDatabaseTestTrait;
use App\Database\Seeds\CoreSeeder;
use Config\Database;

/**
 * @internal
 */
final class Milestone5MigrationTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    public function testAllTwentyOneMilestone5TablesExist(): void
    {
        $db = Database::connect($this->DBGroup);

        $tables = [
            'schedule_versions',
            'schedule_days',
            'schedule_slot_templates',
            'schedule_slot_template_items',
            'schedule_day_slots',
            'schedule_requirements',
            'schedule_entries',
            'schedule_fixed_activities',
            'teacher_availability_rules',
            'classroom_availability_rules',
            'room_availability_rules',
            'scheduling_constraints',
            'schedule_conflicts',
            'schedule_generation_runs',
            'schedule_generation_candidates',
            'schedule_candidate_entries',
            'schedule_locks',
            'schedule_exceptions',
            'schedule_revision_history',
            'schedule_import_batches',
            'schedule_import_rows',
        ];

        foreach ($tables as $table) {
            $this->assertTrue($db->tableExists($table), "Table {$table} should exist in database schema.");
        }
    }
}
