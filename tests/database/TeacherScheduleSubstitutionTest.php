<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\TeacherAvailabilityService;
use App\Services\TeacherScheduleSubstitutionService;
use App\Services\TeacherScheduleSubstitutionManagementService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\IsolatedDatabaseTestTrait;

/** @internal */
final class TeacherScheduleSubstitutionTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    public function testSubstituteBecomesSchedulingResourceButOwnerIdentityStaysSeparate(): void
    {
        $db = Database::connect($this->DBGroup);
        $now = date('Y-m-d H:i:s');
        $teacherIds = [];
        foreach ([
            ['uuid' => '24000000-0000-4000-8000-000000000001', 'full_name' => 'Guru Cuti', 'normalized_name' => 'GURU CUTI', 'teacher_initial' => 'GC', 'color_code' => '#112233'],
            ['uuid' => '24000000-0000-4000-8000-000000000002', 'full_name' => 'Guru Pengganti', 'normalized_name' => 'GURU PENGGANTI', 'teacher_initial' => 'GP', 'color_code' => '#445566'],
        ] as $teacher) {
            $db->table('teachers')->insert($teacher + ['created_at' => $now]);
            $teacherIds[] = (int) $db->insertID();
        }
        [$absentId, $substituteId] = $teacherIds;

        $db->table('academic_years')->insert([
            'uuid' => '24000000-0000-4000-8000-000000000003', 'name' => '2040/2041',
            'start_date' => '2040-07-01', 'end_date' => '2041-06-30', 'created_at' => $now,
        ]);
        $yearId = (int) $db->insertID();
        $db->table('academic_periods')->insert([
            'uuid' => '24000000-0000-4000-8000-000000000004', 'academic_year_id' => $yearId,
            'semester_number' => 1, 'start_date' => '2040-07-01', 'end_date' => '2040-12-31', 'created_at' => $now,
        ]);
        $periodId = (int) $db->insertID();
        $db->table('teacher_schedule_substitutions')->insert([
            'uuid' => '24000000-0000-4000-8000-000000000005',
            'academic_period_id' => $periodId,
            'absent_teacher_id' => $absentId,
            'substitute_teacher_id' => $substituteId,
            'effective_from' => date('Y-m-d', strtotime('-1 day')),
            'effective_to' => date('Y-m-d', strtotime('+2 months')),
            'status' => 'ACTIVE',
            'created_at' => $now,
        ]);

        $substitutions = new TeacherScheduleSubstitutionService();
        $this->assertSame($substituteId, $substitutions->resolveResourceTeacherId($absentId, $periodId));
        $this->assertSame([$absentId, $substituteId], $substitutions->teacherIdsSharingResource($absentId, $periodId));

        $owner = $db->table('teachers')->where('id', $absentId)->get()->getRowArray();
        $this->assertSame('GC', $owner['teacher_initial']);
        $this->assertSame('#112233', $owner['color_code']);

        $db->table('teacher_availability_rules')->insert([
            'uuid' => '24000000-0000-4000-8000-000000000006',
            'teacher_id' => $substituteId,
            'academic_period_id' => $periodId,
            'day_of_week' => 1,
            'slot_number' => 2,
            'availability_status' => 'UNAVAILABLE',
            'created_at' => $now,
        ]);
        $this->assertFalse((new TeacherAvailabilityService())->isTeacherAvailable($absentId, $periodId, 1, 2));
    }

    public function testManagementRejectsOverlapAndCyclicSubstitution(): void
    {
        $db = Database::connect($this->DBGroup);
        $now = date('Y-m-d H:i:s');
        $teacherIds = [];
        foreach (['Guru A', 'Guru B', 'Guru C'] as $index => $name) {
            $db->table('teachers')->insert([
                'uuid' => sprintf('25000000-0000-4000-8000-%012d', $index + 1),
                'full_name' => $name,
                'normalized_name' => mb_strtoupper($name),
                'is_active' => 1,
                'created_at' => $now,
            ]);
            $teacherIds[] = (int) $db->insertID();
        }
        [$teacherA, $teacherB, $teacherC] = $teacherIds;
        $db->table('academic_years')->insert([
            'uuid' => '25000000-0000-4000-8000-000000000010', 'name' => '2026/2027',
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'created_at' => $now,
        ]);
        $yearId = (int) $db->insertID();
        $db->table('academic_periods')->insert([
            'uuid' => '25000000-0000-4000-8000-000000000011', 'academic_year_id' => $yearId,
            'semester_number' => 1, 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'created_at' => $now,
        ]);
        $periodId = (int) $db->insertID();
        $service = new TeacherScheduleSubstitutionManagementService();
        $first = $service->save(null, [
            'academic_period_id' => $periodId, 'absent_teacher_id' => $teacherA,
            'substitute_teacher_id' => $teacherB, 'effective_from' => '2026-08-01',
            'effective_to' => '2026-10-01', 'status' => 'ACTIVE', 'notes' => 'Cuti',
        ], 0);
        $this->assertSame('ACTIVE', $first['status']);

        try {
            $service->save(null, [
                'academic_period_id' => $periodId, 'absent_teacher_id' => $teacherA,
                'substitute_teacher_id' => $teacherC, 'effective_from' => '2026-09-01',
                'effective_to' => '2026-11-01', 'status' => 'ACTIVE',
            ], 0);
            $this->fail('Rentang aktif yang beririsan seharusnya ditolak.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('beririsan', $e->getMessage());
        }

        try {
            $service->save(null, [
                'academic_period_id' => $periodId, 'absent_teacher_id' => $teacherB,
                'substitute_teacher_id' => $teacherA, 'effective_from' => '2026-08-01',
                'effective_to' => '2026-10-01', 'status' => 'ACTIVE',
            ], 0);
            $this->fail('Siklus substitusi seharusnya ditolak.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('siklus', $e->getMessage());
        }

        $disabled = $service->save((int) $first['id'], array_merge($first, ['status' => 'INACTIVE']), 0);
        $this->assertSame('INACTIVE', $disabled['status']);
    }
}
