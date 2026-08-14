<?php

namespace Tests\Security;

use CodeIgniter\Test\CIUnitTestCase;

final class AcademicCalendarCrudContractTest extends CIUnitTestCase
{
    public function testCalendarMutationsArePostOnlyAndExposeCompleteCrud(): void
    {
        $routes = (string) file_get_contents(APPPATH . 'Config/Routes.php');
        foreach ([
            "academic-calendar/(:num)/rules/(:num)/update",
            "academic-calendar/(:num)/rules/(:num)/delete",
            "academic-calendar/(:num)/events/(:num)/update",
            "academic-calendar/(:num)/events/(:num)/delete",
            "academic-calendar/(:num)/update-day",
            "academic-calendar/(:num)/reset-day",
        ] as $route) {
            $this->assertStringContainsString("\$routes->post('{$route}'", $routes);
            $this->assertStringNotContainsString("\$routes->get('{$route}'", $routes);
        }
        $this->assertStringContainsString("\$routes->get('settings/academic-operations'", $routes);
        $this->assertStringContainsString("\$routes->post('settings/academic-operations'", $routes);
    }

    public function testCalendarEditorUsesServerSideWorkspaceTabsAndEditActions(): void
    {
        $view = (string) file_get_contents(APPPATH . 'Views/academic_calendar/editor.php');
        $this->assertStringContainsString("['summary','rules','calendar','programs']", $view);
        $this->assertStringContainsString('btn-edit-rule', $view);
        $this->assertStringContainsString('btn-edit-event', $view);
        $this->assertStringContainsString('Kembalikan ke Generator', $view);
        $this->assertStringContainsString('$per=10', $view);
    }

    public function testRelevantOperationalModulesConsumeCalendarPolicy(): void
    {
        $schedule = (string) file_get_contents(APPPATH . 'Services/ScheduleSetupService.php');
        $attendance = (string) file_get_contents(APPPATH . 'Services/AttendanceService.php');
        $dashboard = (string) file_get_contents(APPPATH . 'Controllers/Home.php');
        $curriculum = (string) file_get_contents(APPPATH . 'Services/CurriculumPlanningService.php');
        $this->assertStringContainsString('AcademicOperatingSettingsService', $schedule);
        $this->assertStringContainsString('getActiveDay', $attendance);
        $this->assertStringContainsString('getActiveDay', $dashboard);
        $this->assertStringContainsString('applyOperatingDayOverride', $curriculum);
    }
}
