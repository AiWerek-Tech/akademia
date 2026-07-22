<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone5Seeder;
use App\Models\ScheduleVersionModel;
use App\Services\DeterministicGreedyScheduleGenerator;
use App\Services\ScheduleRequirementSyncService;
use App\Services\ScheduleScoringService;
use Config\Database;

/**
 * @internal
 */
final class ScheduleGeneratorTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    public function testScheduleGeneratorAndScoring(): void
    {
        $this->seed(Milestone5Seeder::class);
        $db = Database::connect($this->DBGroup);
        $now = date('Y-m-d H:i:s');

        // Insert test user
        $db->table('users')->insert([
            'uuid'          => '10000000-0000-4000-8000-000000000099',
            'username'      => 'testadmin',
            'full_name'     => 'Test Admin User',
            'password_hash' => password_hash('secret123', PASSWORD_BCRYPT),
            'is_active'     => 1,
            'created_at'    => $now,
        ]);
        $userId = (int)$db->insertID();

        // Insert teacher master record
        $db->table('teachers')->insert([
            'uuid'            => '10000000-0000-4000-8000-000000000098',
            'full_name'       => 'Test Teacher User',
            'normalized_name' => 'TEST TEACHER USER',
            'created_at'      => $now,
        ]);
        $teacherMasterId = (int)$db->insertID();

        // Insert academic year & period
        $db->table('academic_years')->insert([
            'uuid'       => '10000000-0000-4000-8000-000000000010',
            'name'       => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date'   => '2027-06-30',
            'is_active'  => 1,
            'created_at' => $now,
        ]);
        $ayId = (int)$db->insertID();

        $db->table('academic_periods')->insert([
            'uuid'             => '10000000-0000-4000-8000-000000000011',
            'academic_year_id' => $ayId,
            'semester_number'  => 1,
            'start_date'       => '2026-07-01',
            'end_date'         => '2026-12-31',
            'created_at'       => $now,
        ]);
        $apId = (int)$db->insertID();

        // Get SMP Unit ID
        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        $smpId = (int)$smp['id'];

        // Insert room_type
        $db->table('room_types')->insert([
            'code'       => 'CLASSROOM',
            'name'       => 'Ruang Kelas',
            'created_at' => $now,
        ]);
        $roomTypeId = (int)$db->insertID();

        // Insert grade level, classroom, subject, room
        $db->table('grade_levels')->insert([
            'uuid'       => '10000000-0000-4000-8000-000000000020',
            'unit_id'    => $smpId,
            'code'       => '7',
            'name'       => 'Kelas 7',
            'created_at' => $now,
        ]);
        $glId = (int)$db->insertID();

        $db->table('classrooms')->insert([
            'uuid'               => '10000000-0000-4000-8000-000000000021',
            'academic_period_id' => $apId,
            'unit_id'            => $smpId,
            'grade_level_id'     => $glId,
            'code'               => '7A',
            'name'               => 'Kelas 7A',
            'created_at'         => $now,
        ]);
        $classId = (int)$db->insertID();

        $db->table('subjects')->insert([
            'uuid'       => '10000000-0000-4000-8000-000000000022',
            'code'       => 'MAT-7',
            'name'       => 'Matematika 7',
            'created_at' => $now,
        ]);
        $subjectId = (int)$db->insertID();

        $db->table('rooms')->insert([
            'uuid'         => '10000000-0000-4000-8000-000000000023',
            'unit_id'      => $smpId,
            'room_type_id' => $roomTypeId,
            'code'         => 'R-101',
            'name'         => 'Ruang 101',
            'created_at'   => $now,
        ]);
        $roomId = (int)$db->insertID();

        // Insert curriculum version & structure
        $db->table('curriculum_versions')->insert([
            'uuid'               => '10000000-0000-4000-8000-000000000012',
            'academic_period_id' => $apId,
            'code'               => 'CURR-TEST',
            'name'               => 'Test Curriculum',
            'created_at'         => $now,
        ]);
        $cvId = (int)$db->insertID();

        $db->table('curriculum_structures')->insert([
            'uuid'                  => '10000000-0000-4000-8000-000000000024',
            'curriculum_version_id' => $cvId,
            'unit_id'               => $smpId,
            'grade_level_id'        => $glId,
            'classroom_id'          => $classId,
            'subject_id'            => $subjectId,
            'effective_weekly_hours'=> 4.0,
            'created_at'            => $now,
        ]);
        $structId = (int)$db->insertID();

        // Insert assignment version, group, assignment
        $db->table('assignment_versions')->insert([
            'uuid'                  => '10000000-0000-4000-8000-000000000013',
            'academic_period_id'    => $apId,
            'curriculum_version_id' => $cvId,
            'code'                  => 'ASSIGN-TEST',
            'name'                  => 'Test Assignment Version',
            'created_at'            => $now,
        ]);
        $avId = (int)$db->insertID();

        $db->table('teaching_assignment_groups')->insert([
            'uuid'                   => '10000000-0000-4000-8000-000000000025',
            'assignment_version_id'  => $avId,
            'curriculum_structure_id'=> $structId,
            'allocation_mode'        => 'SINGLE_TEACHER',
            'required_weekly_hours'  => 4.0,
            'status'                 => 'ACTIVE',
            'created_at'             => $now,
        ]);
        $groupId = (int)$db->insertID();

        $db->table('teaching_assignments')->insert([
            'uuid'                   => '10000000-0000-4000-8000-000000000026',
            'assignment_version_id'  => $avId,
            'curriculum_structure_id'=> $structId,
            'academic_period_id'     => $apId,
            'unit_id'                => $smpId,
            'grade_level_id'         => $glId,
            'classroom_id'           => $classId,
            'subject_id'             => $subjectId,
            'teacher_id'             => $teacherMasterId,
            'assignment_role'        => 'PRIMARY',
            'assigned_weekly_hours'  => 4.0,
            'workload_weekly_hours'  => 4.0,
            'source_weekly_hours'    => 4.0,
            'status'                 => 'ACTIVE',
            'created_at'             => $now,
        ]);

        // Insert schedule version
        $db->table('schedule_versions')->insert([
            'uuid'                  => '10000000-0000-4000-8000-000000000001',
            'academic_period_id'    => $apId,
            'curriculum_version_id' => $cvId,
            'assignment_version_id' => $avId,
            'code'                  => 'SCH-TEST-001',
            'name'                  => 'Test Schedule Version 1',
            'workflow_status'       => 'DRAFT',
            'revision_number'       => 1,
            'is_active'             => 0,
            'created_by'            => $userId,
            'updated_by'            => $userId,
        ]);
        $versionId = (int)$db->insertID();

        // Insert schedule day & slot
        $db->table('schedule_days')->insert([
            'uuid'           => '10000000-0000-4000-8000-000000000030',
            'school_unit_id' => $smpId,
            'day_of_week'    => 1,
            'day_name'       => 'Senin',
            'is_school_day'  => 1,
            'created_at'     => $now,
        ]);
        $dayId = (int)$db->insertID();

        for ($s = 1; $s <= 4; $s++) {
            $db->table('schedule_day_slots')->insert([
                'uuid'                => "10000000-0000-4000-8000-00000000003{$s}",
                'schedule_version_id' => $versionId,
                'day_id'              => $dayId,
                'slot_number'         => $s,
                'start_time'          => sprintf('%02d:00:00', 7 + $s),
                'end_time'            => sprintf('%02d:45:00', 7 + $s),
                'slot_type'           => 'LESSON',
                'created_at'          => $now,
            ]);
        }

        // 1. Sync Requirements
        $syncService = new ScheduleRequirementSyncService();
        $syncResult  = $syncService->syncFromAssignments($versionId);
        $this->assertEquals('success', $syncResult['status']);
        $this->assertGreaterThan(0, $syncResult['synced_count']);

        // 2. Generate Schedule
        $generator = new DeterministicGreedyScheduleGenerator();
        $genResult = $generator->generate($versionId, $userId);

        $this->assertEquals('success', $genResult['status']);
        $this->assertGreaterThan(0, $genResult['total_placed_slots']);

        // 3. Apply Candidate
        $candidateId = (int)$genResult['candidate_id'];
        $applyResult = $generator->applyCandidate($candidateId, $userId);
        $this->assertEquals('success', $applyResult['status']);
        $this->assertGreaterThan(0, $applyResult['applied_entries']);

        // 4. Score Calculation
        $scoringService = new ScheduleScoringService();
        $scoreResult    = $scoringService->calculateScore($versionId);
        $this->assertArrayHasKey('total_score', $scoreResult);
    }
}
