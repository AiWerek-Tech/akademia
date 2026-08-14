<?php

namespace Tests\Security;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class ProductRouteSecurityTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testAssignmentDocumentRoutesAreRegisteredAndRequireLogin(): void
    {
        $this->withSession([])
            ->get('assignments/test-version/documents/sk')
            ->assertRedirectTo(base_url('login'));

        $this->withSession([])
            ->get('assignments/test-version/documents/teacher/1')
            ->assertRedirectTo(base_url('login'));
    }

    public function testRoutineAndDutyScheduleRoutesRequireLogin(): void
    {
        $this->withSession([])->get('routine-activities')->assertRedirectTo(base_url('login'));
        $this->withSession([])->get('duty-schedules')->assertRedirectTo(base_url('login'));
        $this->withSession([])->get('schedules/substitutions')->assertRedirectTo(base_url('login'));
        $this->withSession([])->post('schedules/substitutions/1/repair/analyze')->assertRedirectTo(base_url('login'));
        $this->withSession([])->post('schedules/substitutions/1/repair/1/apply')->assertRedirectTo(base_url('login'));
        $this->withSession([])->get('portal/assignment-document')->assertRedirectTo(base_url('login'));
        $this->withSession([])->get('portal/duty-schedule')->assertRedirectTo(base_url('login'));
        $this->withSession([])->get('portal/electives')->assertRedirectTo(base_url('login'));
        $this->withSession([])->get('portal/electives/export')->assertRedirectTo(base_url('login'));
    }

    public function testNewProductRoutesUseAllSessionSafetyFilters(): void
    {
        $filters = (new \Config\Filters())->filters;
        foreach (['auth', 'unit_access', 'password_change_required'] as $filter) {
            $patterns = $filters[$filter]['before'] ?? [];
            $this->assertContains('routine-activities', $patterns);
            $this->assertContains('routine-activities/*', $patterns);
            $this->assertContains('duty-schedules', $patterns);
            $this->assertContains('duty-schedules/*', $patterns);
        }
    }
}
