<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\UuidService;
use App\Services\WaliKelasAccessService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\IsolatedDatabaseTestTrait;
use Config\Database;

final class WaliKelasElectiveScopeTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

    protected $migrate = true;
    protected $migrateOnce = true;
    protected $refresh = false;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    public function testWaliKelasOnlyReceivesPeriodsMatchingOwnGradeAndAcademicYear(): void
    {
        helper('auth');
        $db = Database::connect($this->DBGroup);
        $now = date('Y-m-d H:i:s');
        $suffix = substr(str_replace('-', '', UuidService::v4()), 0, 8);
        $unit = $db->table('school_units')->where('level', 'SMA')->get()->getRowArray();

        $db->table('academic_years')->insert([
            'uuid' => UuidService::v4(),
            'name' => 'WK-' . $suffix,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'ACTIVE',
            'is_active' => 1,
            'created_at' => $now,
        ]);
        $yearId = (int) $db->insertID();

        $db->table('academic_periods')->insert([
            'uuid' => UuidService::v4(),
            'academic_year_id' => $yearId,
            'semester_number' => 1,
            'name' => 'Ganjil ' . $suffix,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'workflow_status' => 'APPROVED',
            'is_active' => 1,
            'created_at' => $now,
        ]);
        $academicPeriodId = (int) $db->insertID();

        $gradeLevel = $db->table('grade_levels')
            ->where('unit_id', $unit['id'])
            ->where('grade_number', 12)
            ->get()->getRowArray();
        $createdGradeLevel = !$gradeLevel;
        if (!$gradeLevel) {
            $db->table('grade_levels')->insert([
                'uuid' => UuidService::v4(),
                'unit_id' => $unit['id'],
                'grade_number' => 12,
                'code' => 'XII-' . $suffix,
                'name' => 'Kelas XII ' . $suffix,
                'phase' => 'F',
                'sort_order' => 12,
                'is_active' => 1,
                'created_at' => $now,
            ]);
            $gradeLevelId = (int) $db->insertID();
        } else {
            $gradeLevelId = (int) $gradeLevel['id'];
        }

        $db->table('classrooms')->insert([
            'uuid' => UuidService::v4(),
            'academic_period_id' => $academicPeriodId,
            'unit_id' => $unit['id'],
            'grade_level_id' => $gradeLevelId,
            'code' => 'XII-' . $suffix,
            'name' => 'Kelas XII ' . $suffix,
            'status' => 'ACTIVE',
            'is_active' => 1,
            'created_at' => $now,
        ]);
        $classroomId = (int) $db->insertID();

        $db->table('users')->insert([
            'uuid' => UuidService::v4(),
            'username' => 'wali-scope-' . $suffix,
            'full_name' => 'Wali Scope Test',
            'password_hash' => password_hash('Test123!', PASSWORD_BCRYPT),
            'is_active' => 1,
            'classroom_id' => $classroomId,
            'created_at' => $now,
        ]);
        $userId = (int) $db->insertID();

        $role = $db->table('roles')->where('code', 'wali_kelas')->get()->getRowArray();
        $db->table('user_roles')->insert([
            'user_id' => $userId,
            'role_id' => $role['id'],
            'unit_id' => $unit['id'],
            'created_at' => $now,
        ]);
        $db->table('user_unit_access')->insert([
            'user_id' => $userId,
            'unit_id' => $unit['id'],
            'access_level' => 'MEMBER',
            'is_default' => 1,
            'created_at' => $now,
        ]);

        foreach ([11, 12] as $grade) {
            $db->table('elective_periods')->insert([
                'uuid' => UuidService::v4(),
                'unit_id' => $unit['id'],
                'academic_year_id' => $yearId,
                'title' => 'Pemilihan Kelas ' . $grade,
                'source_grade' => $grade,
                'target_grade' => $grade,
                'selection_start_at' => '2026-07-01 00:00:00',
                'selection_end_at' => '2026-07-31 23:59:59',
                'status' => 'DRAFT',
                'created_at' => $now,
            ]);
        }

        try {
            session()->set([
                'logged_in' => true,
                'user_id' => $userId,
                'role_code' => 'wali_kelas',
                'all_role_codes' => ['wali_kelas'],
                'active_unit_id' => (int) $unit['id'],
                'active_period_id' => $academicPeriodId,
            ]);

            $builder = $db->table('elective_periods ep')->select('ep.*');
            $periods = WaliKelasAccessService::scopeElectivePeriods($builder)
                ->orderBy('ep.source_grade')
                ->get()->getResultArray();

            $this->assertCount(1, $periods);
            $this->assertSame(12, (int) $periods[0]['source_grade']);
            $this->assertTrue(WaliKelasAccessService::canAccessElectivePeriod($periods[0]));

            $gradeEleven = $db->table('elective_periods')
                ->where('academic_year_id', $yearId)
                ->where('source_grade', 11)
                ->get()->getRowArray();
            $this->assertFalse(WaliKelasAccessService::canAccessElectivePeriod($gradeEleven));
        } finally {
            session()->destroy();
            $db->table('elective_periods')->where('academic_year_id', $yearId)->delete();
            $db->table('user_roles')->where('user_id', $userId)->delete();
            $db->table('user_unit_access')->where('user_id', $userId)->delete();
            $db->table('users')->where('id', $userId)->delete();
            $db->table('classrooms')->where('id', $classroomId)->delete();
            if ($createdGradeLevel) {
                $db->table('grade_levels')->where('id', $gradeLevelId)->delete();
            }
            $db->table('academic_periods')->where('id', $academicPeriodId)->delete();
            $db->table('academic_years')->where('id', $yearId)->delete();
        }
    }
}
