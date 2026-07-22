<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Database\Seeds\CoreSeeder;
use App\Database\Seeds\Milestone5Seeder;
use App\Services\ScheduleExportService;
use App\Services\ScheduleImportService;
use Config\Database;

/**
 * @internal
 */
final class ScheduleImportExportTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    public function testImportStagingAndApplyAndExport(): void
    {
        $this->seed(Milestone5Seeder::class);
        $db = Database::connect($this->DBGroup);
        $now = date('Y-m-d H:i:s');

        // Insert test user
        $db->table('users')->insert([
            'uuid'          => '10000000-0000-4000-8000-000000000099',
            'username'      => 'GURU001',
            'full_name'     => 'Guru Pengajar',
            'password_hash' => password_hash('secret123', PASSWORD_BCRYPT),
            'is_active'     => 1,
            'created_at'    => $now,
        ]);
        $userId = (int)$db->insertID();

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

        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        $smpId = (int)$smp['id'];

        $db->table('room_types')->insert([
            'code'       => 'CLASSROOM',
            'name'       => 'Ruang Kelas',
            'created_at' => $now,
        ]);
        $roomTypeId = (int)$db->insertID();

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

        $db->table('curriculum_versions')->insert([
            'uuid'               => '10000000-0000-4000-8000-000000000012',
            'academic_period_id' => $apId,
            'code'               => 'CURR-TEST',
            'name'               => 'Test Curriculum',
            'created_at'         => $now,
        ]);
        $cvId = (int)$db->insertID();

        $db->table('assignment_versions')->insert([
            'uuid'                  => '10000000-0000-4000-8000-000000000013',
            'academic_period_id'    => $apId,
            'curriculum_version_id' => $cvId,
            'code'                  => 'ASSIGN-TEST',
            'name'                  => 'Test Assignment Version',
            'created_at'            => $now,
        ]);
        $avId = (int)$db->insertID();

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

        $db->table('schedule_days')->insert([
            'uuid'           => '10000000-0000-4000-8000-000000000030',
            'school_unit_id' => $smpId,
            'day_of_week'    => 1,
            'day_name'       => 'Senin',
            'is_school_day'  => 1,
            'created_at'     => $now,
        ]);
        $dayId = (int)$db->insertID();

        $db->table('schedule_day_slots')->insert([
            'uuid'                => "10000000-0000-4000-8000-000000000031",
            'schedule_version_id' => $versionId,
            'day_id'              => $dayId,
            'slot_number'         => 1,
            'start_time'          => '07:30:00',
            'end_time'            => '08:15:00',
            'slot_type'           => 'LESSON',
            'created_at'          => $now,
        ]);

        $rowsData = [
            [
                'day_code'     => 'Senin',
                'slot_number'  => 1,
                'class_code'   => '7A',
                'teacher_code' => 'GURU001',
                'subject_code' => 'MAT-7',
                'room_code'    => '',
            ]
        ];

        // 1. Stage Import Batch
        $importService = new ScheduleImportService();
        $stageResult   = $importService->stageBatch($versionId, 'test_schedule.csv', $rowsData, $userId);
        $this->assertEquals('success', $stageResult['status']);
        $this->assertEquals(1, $stageResult['valid_rows']);

        // 2. Apply Import Batch
        $batchId     = (int)$stageResult['batch_id'];
        $applyResult = $importService->applyBatch($batchId, $userId);
        $this->assertEquals('success', $applyResult['status']);
        $this->assertEquals(1, $applyResult['applied_entries']);

        // 3. Export Reports
        $exportService = new ScheduleExportService();
        $classGrid     = $exportService->getGridForClassroom($versionId, $classId);
        $this->assertArrayHasKey('entry_map', $classGrid);

        $teacherGrid   = $exportService->getGridForTeacher($versionId, $userId);
        $this->assertArrayHasKey('entries', $teacherGrid);
    }
}
