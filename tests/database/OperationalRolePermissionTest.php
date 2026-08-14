<?php

namespace Tests\Database;

use App\Database\Seeds\CoreSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\IsolatedDatabaseTestTrait;

final class OperationalRolePermissionTest extends CIUnitTestCase
{
    use IsolatedDatabaseTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';
    protected $seed = CoreSeeder::class;

    public function testOperationalStudentPermissionGrants(): void
    {
        foreach (['super_admin', 'admin_smp', 'admin_sma', 'tata_usaha'] as $roleCode) {
            $this->assertTrue($this->hasGrant($roleCode, 'students.manage'), $roleCode);
            $this->assertTrue($this->hasGrant($roleCode, 'students.view'), $roleCode);
        }
        $this->assertTrue($this->hasGrant('viewer_yayasan', 'students.view'));
        $this->assertFalse($this->hasGrant('viewer_yayasan', 'students.manage'));
    }

    public function testOperationsAndFoundationCannotVerifyAttendance(): void
    {
        foreach (['tata_usaha', 'viewer_yayasan'] as $roleCode) {
            $this->assertTrue($this->hasGrant($roleCode, 'attendances.view'));
            $this->assertFalse($this->hasGrant($roleCode, 'attendances.admin'));
        }
    }

    public function testTataUsahaCanManageOnlyThroughControllerRoleBoundary(): void
    {
        foreach (['users.view', 'users.manage', 'users.activate', 'users.reset_password'] as $permissionCode) {
            $this->assertTrue($this->hasGrant('tata_usaha', $permissionCode), $permissionCode);
        }
        foreach (['dashboard.view', 'units.view', 'academic_years.view', 'academic_periods.view'] as $permissionCode) {
            $this->assertTrue($this->hasGrant('tata_usaha', $permissionCode), $permissionCode);
        }
    }

    public function testStudentRoleHasDashboardAndCalendarBaseline(): void
    {
        $this->assertTrue($this->hasGrant('siswa', 'dashboard.view'));
        $this->assertTrue($this->hasGrant('siswa', 'academic_calendar.view'));
        $this->assertTrue($this->hasGrant('siswa', 'electives.selection.submit'));
    }

    private function hasGrant(string $roleCode, string $permissionCode): bool
    {
        return Database::connect($this->DBGroup)->table('role_permissions rp')
            ->join('roles r', 'r.id = rp.role_id')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->where('r.code', $roleCode)->where('p.code', $permissionCode)
            ->countAllResults() > 0;
    }
}
