<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\IsolatedDatabaseTestTrait;
use App\Database\Seeds\CoreSeeder;
use Config\Database;

/**
 * @internal
 */
final class Milestone4MigrationTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    public function testAllElevenMilestone4TablesExist(): void
    {
        $db = Database::connect($this->DBGroup);

        $tables = [
            'assignment_versions',
            'teaching_assignment_groups',
            'teaching_assignments',
            'additional_duty_types',
            'teacher_additional_duties',
            'workload_policies',
            'teacher_workload_snapshots',
            'assignment_validation_results',
            'assignment_revision_history',
            'assignment_import_batches',
            'assignment_import_rows',
        ];

        foreach ($tables as $table) {
            $this->assertTrue($db->tableExists($table), "Table {$table} should exist in database schema.");
        }
    }

    public function testTableStructureAndFieldTypes(): void
    {
        $db = Database::connect($this->DBGroup);

        $fields = $db->getFieldData('assignment_versions');
        $fieldNames = array_column($fields, 'name');
        $this->assertContains('id', $fieldNames);
        $this->assertContains('uuid', $fieldNames);
        $this->assertContains('academic_period_id', $fieldNames);
        $this->assertContains('curriculum_version_id', $fieldNames);
        $this->assertContains('workflow_status', $fieldNames);
        $this->assertContains('revision_number', $fieldNames);

        $dutyFields = $db->getFieldData('teacher_additional_duties');
        $dutyNames = array_column($dutyFields, 'name');
        $this->assertContains('teacher_id', $dutyNames);
        $this->assertContains('duty_type_id', $dutyNames);
        $this->assertContains('workload_hours', $dutyNames);
    }
}
