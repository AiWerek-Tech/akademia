<?php

namespace Tests\Security;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\RolePermissions;

final class AttendanceSecurityContractTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testPersonalRolesDoNotReceiveExecutiveAttendanceMonitoring(): void
    {
        foreach (RolePermissions::managedRoles() as $permissions) {
            $this->assertContains('teacher_attendance.view', $permissions);
            $this->assertContains('attendances.record', $permissions);
            $this->assertNotContains('attendances.view', $permissions);
            $this->assertNotContains('attendances.admin', $permissions);
        }
    }

    public function testAttendanceAndCalendarMutationsUsePostRoutes(): void
    {
        $routes = (string) file_get_contents(APPPATH . 'Config/Routes.php');

        $this->assertStringContainsString("\$routes->post('portal/attendance/session/(:num)/delete'", $routes);
        $this->assertStringContainsString("\$routes->post('attendances/(:num)/verify'", $routes);
        $this->assertStringContainsString("\$routes->post('attendances/(:num)/reopen'", $routes);
        $this->assertStringContainsString("\$routes->post('settings/attendance'", $routes);
        $this->assertStringNotContainsString("\$routes->get('portal/attendance/session/(:num)/delete'", $routes);
        $this->assertStringNotContainsString("\$routes->get('attendances/(:num)/verify'", $routes);
    }

    public function testCalendarRoutesUseFullSessionSafetyFilters(): void
    {
        $filters = (new \Config\Filters())->filters;
        foreach (['auth', 'unit_access', 'password_change_required'] as $filter) {
            $patterns = $filters[$filter]['before'] ?? [];
            $this->assertContains('academic-calendar', $patterns);
            $this->assertContains('academic-calendar/*', $patterns);
            $this->assertContains('attendances', $patterns);
            $this->assertContains('attendances/*', $patterns);
        }
    }

    public function testUnauthenticatedAttendanceAndCalendarRoutesRedirectToLogin(): void
    {
        $this->withSession([])->get('academic-calendar')->assertRedirectTo(base_url('login'));
        $this->withSession([])->get('attendances')->assertRedirectTo(base_url('login'));
        $this->withSession([])->get('portal/attendance')->assertRedirectTo(base_url('login'));
    }

    public function testOwnershipAndRosterValidationRemainEnforced(): void
    {
        $controller = (string) file_get_contents(APPPATH . 'Controllers/TeacherAttendanceController.php');
        $service = (string) file_get_contents(APPPATH . 'Services/AttendanceService.php');

        $this->assertStringContainsString('teacherHasActiveAssignment(', $controller);
        $this->assertStringContainsString('teacherCanRecordType(', $controller);
        $this->assertStringContainsString('UnitScopeService::assertUnit', $controller);
        $this->assertStringContainsString('teacherIdsSharingResource', $service);
        $this->assertStringContainsString('$allowedStudentIds !== $submittedStudentIds', $service);
    }
}
