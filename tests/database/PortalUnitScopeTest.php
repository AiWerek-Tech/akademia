<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use App\Services\PortalUnitScopeService;
use App\Services\TeacherScheduleReadService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\IsolatedDatabaseTestTrait;

/** @internal */
final class PortalUnitScopeTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    public function testAllAndSingleUnitScopesStayInsideUserAccess(): void
    {
        $db = Database::connect($this->DBGroup);
        $units = $db->table('school_units')->where('is_active', 1)->orderBy('id')->get()->getResultArray();
        $this->assertGreaterThanOrEqual(2, count($units));

        $db->table('users')->insert([
            'uuid' => '29000000-0000-4000-8000-000000000001',
            'username' => 'portal_unit_scope_test',
            'full_name' => 'Portal Unit Scope Test',
            'password_hash' => password_hash('TestPass123!', PASSWORD_BCRYPT),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $userId = (int) $db->insertID();
        foreach (array_slice($units, 0, 2) as $index => $unit) {
            $db->table('user_unit_access')->insert([
                'user_id' => $userId,
                'unit_id' => (int) $unit['id'],
                'access_level' => 'VIEW',
                'is_default' => $index === 0 ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        session()->set(['logged_in' => true, 'user_id' => $userId]);

        $all = PortalUnitScopeService::resolve('all');
        $this->assertTrue($all['isAll']);
        $this->assertCount(2, $all['unitIds']);

        $single = PortalUnitScopeService::resolve((string) $units[1]['code']);
        $this->assertFalse($single['isAll']);
        $this->assertSame([(int) $units[1]['id']], $single['unitIds']);

        $projection = (new TeacherScheduleReadService())->buildForUnits(0, $all['unitIds'], 0);
        $this->assertTrue($projection['isCombinedUnitScope']);
        $this->assertSame($all['unitIds'], $projection['unitIds']);
    }
}
