<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\IsolatedDatabaseTestTrait;
use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone5Seeder;
use App\Models\ScheduleVersionModel;
use App\Services\DeterministicGreedyScheduleGenerator;
use App\Services\ScheduleConflictDetectionService;
use App\Services\ScheduleRequirementSyncService;
use App\Services\ScheduleScoringService;
use Config\Database;

/**
 * @internal
 */
final class ScheduleGeneratorTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

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
            'is_primary_teacher'     => 1,
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

        $repeatResult = $generator->generate($versionId, $userId, 0);
        $projection = static function (array $rows): array {
            $normalized = array_map(static fn (array $row): array => [
                'day_slot_id' => (int) $row['day_slot_id'],
                'schedule_requirement_id' => (int) $row['schedule_requirement_id'],
                'classroom_id' => (int) $row['classroom_id'],
                'teacher_id' => (int) $row['teacher_id'],
                'second_teacher_id' => (int) ($row['second_teacher_id'] ?? 0),
                'subject_id' => (int) $row['subject_id'],
                'room_id' => (int) ($row['room_id'] ?? 0),
            ], $rows);
            usort($normalized, static fn (array $a, array $b): int => $a <=> $b);
            return $normalized;
        };
        $firstEntries = $db->table('schedule_candidate_entries')->where('candidate_id', (int) $genResult['candidate_id'])->get()->getResultArray();
        $repeatEntries = $db->table('schedule_candidate_entries')->where('candidate_id', (int) $repeatResult['candidate_id'])->get()->getResultArray();
        $this->assertSame($projection($firstEntries), $projection($repeatEntries), 'Strategi dan input yang sama harus menghasilkan penempatan identik.');

        // 3. Apply Candidate
        $candidateId = (int)$genResult['candidate_id'];
        try {
            $generator->applyCandidate($candidateId, $userId, 999);
            $this->fail('Kandidat dengan revisi kedaluwarsa seharusnya ditolak.');
        } catch (\RuntimeException $e) {
            $this->assertSame(409, $e->getCode());
        }
        $this->assertSame(0, $db->table('schedule_entries')->where('schedule_version_id', $versionId)->countAllResults());
        $this->assertSame(0, (int) $db->table('schedule_generation_candidates')->where('id', $candidateId)->get()->getRowArray()['is_applied']);

        // A previous audit may retain a foreign key to an entry that the
        // explicit apply is about to replace. History must survive without
        // blocking the replacement transaction.
        $legacy = $firstEntries[0];
        $db->table('schedule_entries')->insert([
            'uuid' => '10000000-0000-4000-8000-000000000090',
            'schedule_version_id' => $versionId,
            'day_slot_id' => $legacy['day_slot_id'],
            'schedule_requirement_id' => $legacy['schedule_requirement_id'],
            'classroom_id' => $legacy['classroom_id'],
            'teacher_id' => $legacy['teacher_id'],
            'subject_id' => $legacy['subject_id'],
            'is_locked' => 0,
            'created_at' => $now,
        ]);
        $legacyEntryId = (int) $db->insertID();
        $legacyFingerprint = hash('sha256', 'legacy-generator-test');
        $db->table('schedule_conflicts')->insert([
            'uuid' => '10000000-0000-4000-8000-000000000091',
            'fingerprint' => $legacyFingerprint,
            'schedule_version_id' => $versionId,
            'conflict_code' => 'LEGACY_TEST',
            'conflict_type' => 'LEGACY_TEST',
            'severity' => 'MEDIUM',
            'description' => 'Historical conflict retained during apply.',
            'status' => 'ACTIVE',
            'detected_at' => $now,
            'active_generation_scope' => 'CURRENT',
            'primary_entry_id' => $legacyEntryId,
            'is_resolved' => 0,
            'created_at' => $now,
        ]);

        $applyResult = $generator->applyCandidate($candidateId, $userId, 1);
        $this->assertEquals('success', $applyResult['status']);
        $this->assertGreaterThan(0, $applyResult['applied_entries']);
        $this->assertSame(2, (int) $applyResult['new_revision']);
        $this->assertSame(1, $db->table('schedule_revision_history')
            ->where('schedule_version_id', $versionId)
            ->where('action', 'APPLY_GENERATED_CANDIDATE')
            ->countAllResults());
        $retainedConflict = $db->table('schedule_conflicts')->where('fingerprint', $legacyFingerprint)->get()->getRowArray();
        $this->assertNotEmpty($retainedConflict);
        $this->assertNull($retainedConflict['primary_entry_id']);
        $this->assertSame('RESOLVED', $retainedConflict['status']);

        try {
            $generator->applyCandidate($candidateId, $userId, 2);
            $this->fail('Kandidat yang sudah diterapkan seharusnya ditolak.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('sudah pernah diterapkan', $e->getMessage());
        }

        // 4. Score Calculation
        $scoringService = new ScheduleScoringService();
        $scoreResult    = $scoringService->calculateScore($versionId);
        $this->assertArrayHasKey('total_score', $scoreResult);

        $db->table('schedule_requirements')->where('schedule_version_id', $versionId)
            ->update(['required_weekly_hours' => 6.0]);
        try {
            $generator->generate($versionId, $userId, 0);
            $this->fail('Dataset mustahil harus dihentikan oleh capacity preflight.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('kapasitas tidak cukup', $e->getMessage());
        }
    }

    public function testGeneratorKeepsTwoJpTogetherAndPrefersThreeJpTogether(): void
    {
        $generator = new DeterministicGreedyScheduleGenerator();
        $method = new \ReflectionMethod($generator, 'buildJpBlocks');
        $method->setAccessible(true);

        $this->assertSame([2], $method->invoke($generator, 2));
        $this->assertSame([3], $method->invoke($generator, 3));
        $this->assertSame([2, 2], $method->invoke($generator, 4));
        $this->assertSame([2, 2, 1], $method->invoke($generator, 5));
    }

    public function testGeneratorAvoidsBreakSplitAndPrioritizesHeavySubjectsInMorning(): void
    {
        $generator = new DeterministicGreedyScheduleGenerator();
        $crossesBreak = new \ReflectionMethod($generator, 'blockCrossesIntermission');
        $crossesBreak->setAccessible(true);
        $isHeavy = new \ReflectionMethod($generator, 'isMorningPrioritySubject');
        $isHeavy->setAccessible(true);
        $rank = new \ReflectionMethod($generator, 'blockTimePreferenceRank');
        $rank->setAccessible(true);

        $blockBeforeBreak = [['slot_number' => 4], ['slot_number' => 5]];
        $blockAcrossBreak = [['slot_number' => 5], ['slot_number' => 6]];
        $blockAfterBreak = [['slot_number' => 6], ['slot_number' => 7]];
        $blockLate = [['slot_number' => 8], ['slot_number' => 9]];

        $this->assertFalse($crossesBreak->invoke($generator, $blockBeforeBreak, 5));
        $this->assertTrue($crossesBreak->invoke($generator, $blockAcrossBreak, 5));
        $this->assertTrue($isHeavy->invoke($generator, ['code' => 'MTK', 'name' => 'Matematika']));
        $this->assertLessThan(
            $rank->invoke($generator, $blockAfterBreak, true, 5),
            $rank->invoke($generator, $blockBeforeBreak, true, 5)
        );
        $this->assertLessThan(
            $rank->invoke($generator, $blockLate, true, 5),
            $rank->invoke($generator, $blockAfterBreak, true, 5)
        );
        $this->assertGreaterThanOrEqual(900, $rank->invoke($generator, $blockAcrossBreak, true, 5));
    }

    public function testContiguousBlockCounterNeverCombinesSeparatedSlotsOrDays(): void
    {
        $detector = new ScheduleConflictDetectionService();
        $method = new \ReflectionMethod($detector, 'countContiguousPairs');
        $method->setAccessible(true);

        $this->assertSame(1, $method->invoke($detector, [1 => [1, 2]]));
        $this->assertSame(0, $method->invoke($detector, [1 => [1, 3]]));
        $this->assertSame(1, $method->invoke($detector, [1 => [1, 2, 3]]));
        $this->assertSame(2, $method->invoke($detector, [1 => [1, 2], 4 => [6, 7]]));
        $this->assertSame(0, $method->invoke($detector, [1 => [1], 2 => [2]]));
    }
}
