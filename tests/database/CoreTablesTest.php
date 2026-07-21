<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Database\Seeds\CoreSeeder;

/**
 * @internal
 */
final class CoreTablesTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = CoreSeeder::class;

    public function testCoreTablesPopulated(): void
    {
        $db = \Config\Database::connect($this->DBGroup);

        // Verify school units exist
        $smp = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
        $sma = $db->table('school_units')->where('code', 'SMA')->get()->getRowArray();

        $this->assertNotEmpty($smp);
        $this->assertNotEmpty($sma);
        $this->assertEquals('SMP', $smp['code']);
        $this->assertEquals('SMA', $sma['code']);

        // Verify core roles exist
        $rolesCount = $db->table('roles')->countAllResults();
        $this->assertGreaterThanOrEqual(4, $rolesCount); // superadmin, kepala_sekolah, wakasek_kurikulum, guru

        $superadmin = $db->table('roles')->where('code', 'super_admin')->get()->getRowArray();
        $this->assertNotEmpty($superadmin);

        // Verify permissions exist
        $permsCount = $db->table('permissions')->countAllResults();
        $this->assertGreaterThan(10, $permsCount);
    }
}
