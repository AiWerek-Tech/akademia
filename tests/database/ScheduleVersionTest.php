<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\IsolatedDatabaseTestTrait;
use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone5Seeder;
use App\Models\ScheduleVersionModel;
use App\Services\ScheduleWorkflowService;
use Config\Database;

/**
 * @internal
 */
final class ScheduleVersionTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    private function createFixtureData(): array
    {
        $db = Database::connect($this->DBGroup);
        $now = date('Y-m-d H:i:s');
        $fixtureSuffix = strtoupper(substr(hash('sha256', $this->name()), 0, 8));

        $db->table('users')->insert([
            'uuid'          => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
            'username'      => 'usr_' . mt_rand(1000, 9999),
            'full_name'     => 'Version Admin User',
            'password_hash' => password_hash('secret123', PASSWORD_BCRYPT),
            'is_active'     => 1,
            'created_at'    => $now,
        ]);
        $userId = (int)$db->insertID();

        $db->table('academic_years')->insert([
            'uuid'       => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
            'name'       => '2026/2027-' . $fixtureSuffix,
            'start_date' => '2026-07-01',
            'end_date'   => '2027-06-30',
            'is_active'  => 1,
            'created_at' => $now,
        ]);
        $ayId = (int)$db->insertID();

        $db->table('academic_periods')->insert([
            'uuid'             => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
            'academic_year_id' => $ayId,
            'semester_number'  => 1,
            'start_date'       => '2026-07-01',
            'end_date'         => '2026-12-31',
            'created_at'       => $now,
        ]);
        $apId = (int)$db->insertID();

        $db->table('curriculum_versions')->insert([
            'uuid'               => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
            'academic_period_id' => $apId,
            'code'               => 'CURR-' . mt_rand(100, 999),
            'name'               => 'Curriculum V1',
            'created_at'         => $now,
        ]);
        $cvId = (int)$db->insertID();

        $db->table('assignment_versions')->insert([
            'uuid'                  => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
            'academic_period_id'    => $apId,
            'curriculum_version_id' => $cvId,
            'code'                  => 'ASSIGN-' . mt_rand(100, 999),
            'name'                  => 'Assignment V1',
            'created_at'            => $now,
        ]);
        $avId = (int)$db->insertID();

        return [
            'userId' => $userId,
            'apId'   => $apId,
            'cvId'   => $cvId,
            'avId'   => $avId,
        ];
    }

    public function testCreateScheduleVersion(): void
    {
        $this->seed(Milestone5Seeder::class);
        $fx = $this->createFixtureData();

        $model = new ScheduleVersionModel();
        $id = $model->insert([
            'uuid'                  => '10000000-0000-4000-8000-000000000001',
            'academic_period_id'    => $fx['apId'],
            'curriculum_version_id' => $fx['cvId'],
            'assignment_version_id' => $fx['avId'],
            'code'                  => 'SCH-V1',
            'name'                  => 'Schedule Version 1',
            'workflow_status'       => 'DRAFT',
            'revision_number'       => 1,
            'is_active'             => 0,
            'created_by'            => $fx['userId'],
            'updated_by'            => $fx['userId'],
        ]);

        $this->assertGreaterThan(0, $id);
        $version = $model->find($id);
        $this->assertEquals('SCH-V1', $version['code']);
    }

    public function testDuplicateScheduleVersionCode(): void
    {
        $this->seed(Milestone5Seeder::class);
        $fx = $this->createFixtureData();
        $db = Database::connect($this->DBGroup);

        $db->table('schedule_versions')->insert([
            'uuid'                  => '10000000-0000-4000-8000-000000000002',
            'academic_period_id'    => $fx['apId'],
            'curriculum_version_id' => $fx['cvId'],
            'assignment_version_id' => $fx['avId'],
            'code'                  => 'SCH-UNIQUE',
            'name'                  => 'Schedule Unique 1',
            'workflow_status'       => 'DRAFT',
            'revision_number'       => 1,
            'is_active'             => 0,
            'created_by'            => $fx['userId'],
            'updated_by'            => $fx['userId'],
        ]);

        $this->expectException(\CodeIgniter\Database\Exceptions\DatabaseException::class);
        $db->table('schedule_versions')->insert([
            'uuid'                  => '10000000-0000-4000-8000-000000000003',
            'academic_period_id'    => $fx['apId'],
            'curriculum_version_id' => $fx['cvId'],
            'assignment_version_id' => $fx['avId'],
            'code'                  => 'SCH-UNIQUE',
            'name'                  => 'Schedule Unique Duplicate',
            'workflow_status'       => 'DRAFT',
            'revision_number'       => 1,
            'is_active'             => 0,
            'created_by'            => $fx['userId'],
            'updated_by'            => $fx['userId'],
        ]);
    }

    public function testScheduleVersionWorkflowAndOptimisticConcurrency(): void
    {
        $this->seed(Milestone5Seeder::class);
        $fx = $this->createFixtureData();
        $db = Database::connect($this->DBGroup);

        $db->table('schedule_versions')->insert([
            'uuid'                  => '10000000-0000-4000-8000-000000000004',
            'academic_period_id'    => $fx['apId'],
            'curriculum_version_id' => $fx['cvId'],
            'assignment_version_id' => $fx['avId'],
            'code'                  => 'SCH-FLOW',
            'name'                  => 'Schedule Workflow Test',
            'workflow_status'       => 'DRAFT',
            'revision_number'       => 1,
            'is_active'             => 0,
            'created_by'            => $fx['userId'],
            'updated_by'            => $fx['userId'],
        ]);
        $versionId = (int)$db->insertID();
        $userId    = $fx['userId'];

        $workflowService = new ScheduleWorkflowService();

        // DRAFT -> VALIDATED (revision 1 -> 2)
        $resVal = $workflowService->transitionState($versionId, 'VALIDATED', $userId, 1, 'Validated rules');
        $this->assertEquals('success', $resVal['status']);

        // VALIDATED -> REVIEWED (revision 2 -> 3)
        $resRev = $workflowService->transitionState($versionId, 'REVIEWED', $userId, 2, 'Reviewed layout');
        $this->assertEquals('success', $resRev['status']);

        // REVIEWED -> APPROVED (revision 3 -> 4)
        $resApp = $workflowService->transitionState($versionId, 'APPROVED', $userId, 3, 'Approved schedule');
        $this->assertEquals('success', $resApp['status']);

        // APPROVED -> LOCKED (revision 4 -> 5)
        $resLoc = $workflowService->transitionState($versionId, 'LOCKED', $userId, 4, 'Locked schedule');
        $this->assertEquals('success', $resLoc['status']);

        // Test Locked Invalid Transition
        $this->expectException(\InvalidArgumentException::class);
        $workflowService->transitionState($versionId, 'DRAFT', $userId, 5, 'Attempt unlock');
    }
}
