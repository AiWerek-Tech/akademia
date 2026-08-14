<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\UnitScopeService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\IsolatedDatabaseTestTrait;
use Config\Database;
use RuntimeException;

/** @internal */
final class UnitScopeSecurityTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    public function testUserCannotSelectAnUnassignedUnit(): void
    {
        $db = Database::connect($this->DBGroup);
        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        $sma = $db->table('school_units')->where('code', 'SMA')->get()->getRowArray();

        $db->table('users')->insert([
            'uuid' => \App\Services\UuidService::v4(),
            'username' => 'scope_test',
            'email' => 'scope-test@example.test',
            'full_name' => 'Scope Test',
            'password_hash' => password_hash('ScopeTestPassword!2026', PASSWORD_BCRYPT),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $userId = (int) $db->insertID();
        $db->table('user_unit_access')->insert([
            'user_id' => $userId,
            'unit_id' => $smp['id'],
            'is_default' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        session()->set([
            'logged_in' => true,
            'user_id' => $userId,
            'active_unit_id' => (int) $smp['id'],
        ]);

        $this->assertSame((int) $smp['id'], UnitScopeService::resolveUnit($smp['id']));
        $this->assertSame([(int) $smp['id']], UnitScopeService::accessibleUnitIds());

        $this->expectException(RuntimeException::class);
        UnitScopeService::resolveUnit($sma['id']);
    }

    public function testTeacherMustBelongToTheSelectedUnit(): void
    {
        $db = Database::connect($this->DBGroup);
        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        $sma = $db->table('school_units')->where('code', 'SMA')->get()->getRowArray();

        $db->table('users')->insert([
            'uuid' => \App\Services\UuidService::v4(),
            'username' => 'multi_scope_test',
            'email' => 'multi-scope-test@example.test',
            'full_name' => 'Multi Scope Test',
            'password_hash' => password_hash('ScopeTestPassword!2026', PASSWORD_BCRYPT),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $userId = (int) $db->insertID();
        foreach ([$smp['id'], $sma['id']] as $unitId) {
            $db->table('user_unit_access')->insert([
                'user_id' => $userId,
                'unit_id' => $unitId,
                'is_default' => (int) $unitId === (int) $smp['id'] ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $db->table('teachers')->insert([
            'uuid' => \App\Services\UuidService::v4(),
            'full_name' => 'Guru Khusus SMA',
            'normalized_name' => 'guru khusus sma',
            'primary_unit_id' => $sma['id'],
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $teacherId = (int) $db->insertID();
        $db->table('teacher_unit_assignments')->insert([
            'teacher_id' => $teacherId,
            'unit_id' => $sma['id'],
            'assignment_type' => 'TEACHING',
            'status' => 'ACTIVE',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        session()->set([
            'logged_in' => true,
            'user_id' => $userId,
            'active_unit_id' => (int) $smp['id'],
        ]);

        UnitScopeService::assertTeacherInUnit($teacherId, (int) $sma['id']);

        $this->expectException(RuntimeException::class);
        UnitScopeService::assertTeacherInUnit($teacherId, (int) $smp['id']);
    }
}
