<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Config\RolePermissions;

final class RolePermissionsTest extends CIUnitTestCase
{
    public function testGuruContainsOnlyPersonalPortalPermissions(): void
    {
        $this->assertSame([
            'dashboard.view',
            'teacher_portal.view',
            'teacher_assignment_document.view',
            'teacher_attendance.view',
            'teacher_duty_schedule.view',
            'teacher_electives.view',
            'teacher_schedule.view',
            'teacher_workload.view',
            'attendances.record',
            'academic_calendar.view',
        ], RolePermissions::GURU);

        $this->assertSame(
            [],
            array_values(array_intersect(RolePermissions::GURU, RolePermissions::ADMINISTRATIVE_PERMISSIONS))
        );
    }

    public function testWaliKelasAddsOnlyClassScopedCapabilities(): void
    {
        $this->assertSame(
            [
                'class_students.view',
                'class_schedule.view',
                'class_electives.manage',
            ],
            array_values(array_diff(RolePermissions::WALI_KELAS, RolePermissions::GURU))
        );

        $this->assertSame(
            [],
            array_values(array_intersect(RolePermissions::WALI_KELAS, RolePermissions::ADMINISTRATIVE_PERMISSIONS))
        );
    }
}
