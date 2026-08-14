<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Tests\Support\IsolatedDatabaseTestTrait;

/** @internal */
final class TeacherScheduleSubstitutionUiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use IsolatedDatabaseTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    public function testSuperAdminCanViewConfiguredSubstitution(): void
    {
        $db = Database::connect($this->DBGroup);
        $now = date('Y-m-d H:i:s');
        $unit = $db->table('school_units')->where('is_active', 1)->get()->getRowArray();
        $this->assertNotEmpty($unit);
        $db->table('users')->insert([
            'uuid' => '26000000-0000-4000-8000-000000000001', 'username' => 'substitution_admin',
            'full_name' => 'Substitution Admin', 'password_hash' => password_hash('TestPass123!', PASSWORD_BCRYPT),
            'is_active' => 1, 'must_change_password' => 0, 'created_at' => $now,
        ]);
        $userId = (int) $db->insertID();
        $role = $db->table('roles')->where('code', 'super_admin')->get()->getRowArray();
        $this->assertNotEmpty($role);
        $db->table('user_roles')->insert(['user_id' => $userId, 'role_id' => $role['id'], 'created_at' => $now]);
        $permission = $db->table('permissions')->where('code', 'schedules.view')->get()->getRowArray();
        if (! $permission) {
            $db->table('permissions')->insert([
                'code' => 'schedules.view', 'module' => 'schedules', 'name' => 'View Schedules',
                'description' => 'Melihat jadwal', 'created_at' => $now,
            ]);
            $permission = $db->table('permissions')->where('id', $db->insertID())->get()->getRowArray();
        }
        $db->table('role_permissions')->insert([
            'role_id' => $role['id'], 'permission_id' => $permission['id'], 'created_at' => $now,
        ]);
        $db->table('user_unit_access')->insert([
            'user_id' => $userId, 'unit_id' => $unit['id'], 'access_level' => 'ADMIN',
            'is_default' => 1, 'created_at' => $now,
        ]);

        $teacherIds = [];
        foreach (['Saray Contoh', 'Marthen Contoh'] as $index => $name) {
            $db->table('teachers')->insert([
                'uuid' => sprintf('26000000-0000-4000-8000-%012d', $index + 10),
                'full_name' => $name, 'normalized_name' => mb_strtoupper($name),
                'teacher_initial' => $index === 0 ? 'SC' : 'MC', 'color_code' => '#F4B183',
                'primary_unit_id' => $unit['id'], 'is_active' => 1, 'created_at' => $now,
            ]);
            $teacherIds[] = (int) $db->insertID();
        }
        $db->table('academic_years')->insert([
            'uuid' => '26000000-0000-4000-8000-000000000020', 'name' => '2035/2036',
            'start_date' => '2035-07-01', 'end_date' => '2036-06-30', 'created_at' => $now,
        ]);
        $yearId = (int) $db->insertID();
        $db->table('academic_periods')->insert([
            'uuid' => '26000000-0000-4000-8000-000000000021', 'academic_year_id' => $yearId,
            'semester_number' => 1, 'name' => 'Ganjil 2035/2036', 'start_date' => '2035-07-01',
            'end_date' => '2035-12-31', 'created_at' => $now,
        ]);
        $periodId = (int) $db->insertID();
        $db->table('teacher_schedule_substitutions')->insert([
            'uuid' => '26000000-0000-4000-8000-000000000022', 'academic_period_id' => $periodId,
            'absent_teacher_id' => $teacherIds[0], 'substitute_teacher_id' => $teacherIds[1],
            'effective_from' => '2035-08-01', 'effective_to' => '2035-10-01',
            'status' => 'ACTIVE', 'created_at' => $now,
        ]);

        $result = $this->withSession([
            'logged_in' => true, 'user_id' => $userId, 'username' => 'substitution_admin',
            'active_role' => 'super_admin', 'role_code' => 'super_admin',
            'active_unit_id' => (int) $unit['id'], 'unit_access' => [(int) $unit['id']],
            'auth_timestamp' => time(),
        ])->get('schedules/substitutions');
        $result->assertOK();
        $result->assertSee('Substitusi Guru Sementara');
        $result->assertSee('Saray Contoh');
        $result->assertSee('Marthen Contoh');
        $result->assertSee('Auto Repair');
        $result->assertSee('Belum dianalisis');
        $body = $result->getBody();
        $this->assertStringContainsString('menu-item has-submenu active open', $body);
        $this->assertStringContainsString('Penugasan &amp; Jadwal', $body);
        $this->assertStringContainsString('class="submenu-link active"', $body);
        $result->assertSee('Substitusi Guru');
    }
}
