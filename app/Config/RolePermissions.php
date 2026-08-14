<?php

namespace Config;

/**
 * Canonical permission sets for tightly scoped operational roles.
 *
 * These roles intentionally use an exact allow-list. Broad administrative
 * permissions must be assigned to an administrative role instead of being
 * inherited by Guru or Wali Kelas.
 */
final class RolePermissions
{
    public const GURU = [
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
    ];

    public const WALI_KELAS = [
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
        'class_students.view',
        'class_schedule.view',
        'class_electives.manage',
    ];

    /**
     * Baseline permissions installed for personal roles. Superadmin may
     * customize the persisted grants; data ownership is enforced separately
     * by the personal portal controllers.
     *
     * @return array<string, list<string>>
     */
    public static function managedRoles(): array
    {
        return [
            'guru'       => self::GURU,
            'wali_kelas' => self::WALI_KELAS,
        ];
    }

    /**
     * Permissions that must never be granted through Guru/Wali Kelas.
     *
     * This list is also useful for regression tests and permission audits.
     */
    public const ADMINISTRATIVE_PERMISSIONS = [
        'teachers.view',
        'curriculum.view',
        'curriculum.import',
        'assignments.view',
        'duties.view',
        'workloads.view',
        'availability.view',
        'schedules.view',
        'students.view',
        'classrooms.view',
        'electives.view',
        'attendances.view',
        'attendances.admin',
    ];
}
