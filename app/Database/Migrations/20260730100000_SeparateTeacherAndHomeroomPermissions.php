<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Replaces accumulated legacy grants with exact role-specific allow-lists.
 */
class SeparateTeacherAndHomeroomPermissions extends Migration
{
    public function up()
    {
        $this->ensureManagedRoles();
        $this->ensureManagedPermissions();
        // Keep a historical migration deterministic. New personal permissions
        // are added by their own later migration and must not be pulled into
        // this release through a mutable runtime config class.
        $guruPermissions = [
            'dashboard.view',
            'teacher_portal.view',
            'teacher_assignment_document.view',
            'teacher_duty_schedule.view',
            'teacher_schedule.view',
            'teacher_workload.view',
        ];
        $this->syncManagedRoles([
            'guru' => $guruPermissions,
            'wali_kelas' => array_merge($guruPermissions, [
                'class_students.view',
                'class_schedule.view',
                'class_electives.manage',
            ]),
        ]);
    }

    public function down()
    {
        // Restore the mappings from the release immediately before this fix.
        $this->syncManagedRoles([
            'guru' => [
                'dashboard.view',
                'assignments.view',
                'availability.view',
                'curriculum.view',
                'duties.view',
                'schedules.view',
                'teachers.view',
                'teacher_portal.view',
                'teacher_schedule.view',
                'teacher_workload.view',
                'workloads.view',
            ],
            'wali_kelas' => [
                'dashboard.view',
                'class_students.view',
                'class_electives.manage',
                'class_schedule.view',
                'students.view',
                'classrooms.view',
                'electives.view',
                'teacher_portal.view',
                'teacher_workload.view',
                'teacher_schedule.view',
            ],
        ], false);
    }

    /**
     * @param array<string, list<string>> $rolePermissions
     */
    private function syncManagedRoles(array $rolePermissions, bool $requireAllPermissions = true): void
    {
        $now = date('Y-m-d H:i:s');

        foreach ($rolePermissions as $roleCode => $permissionCodes) {
            $role = $this->db->table('roles')->where('code', $roleCode)->get()->getRowArray();
            if (!$role) {
                continue;
            }

            $permissionRows = $this->db->table('permissions')
                ->select('id, code')
                ->whereIn('code', $permissionCodes)
                ->get()
                ->getResultArray();

            $permissionIdsByCode = [];
            foreach ($permissionRows as $permission) {
                $permissionIdsByCode[$permission['code']] = (int) $permission['id'];
            }

            $missing = array_values(array_diff($permissionCodes, array_keys($permissionIdsByCode)));
            if ($requireAllPermissions && $missing !== []) {
                throw new \RuntimeException(
                    sprintf('Permission role %s belum lengkap: %s', $roleCode, implode(', ', $missing))
                );
            }

            $this->db->transException(true)->transStart();
            $this->db->table('role_permissions')->where('role_id', (int) $role['id'])->delete();

            foreach ($permissionCodes as $permissionCode) {
                if (!isset($permissionIdsByCode[$permissionCode])) {
                    continue;
                }
                $this->db->table('role_permissions')->insert([
                    'role_id'       => (int) $role['id'],
                    'permission_id' => $permissionIdsByCode[$permissionCode],
                    'created_at'    => $now,
                ]);
            }
            $this->db->transComplete();
        }
    }

    private function ensureManagedRoles(): void
    {
        $definitions = [
            'guru' => [
                'name' => 'Guru',
                'description' => 'Akses personal guru pengajar',
                'scope' => 'GLOBAL',
            ],
            'wali_kelas' => [
                'name' => 'Wali Kelas',
                'description' => 'Akses personal guru dan kelas binaan',
                'scope' => 'UNIT',
            ],
        ];

        foreach ($definitions as $code => $definition) {
            $exists = $this->db->table('roles')->where('code', $code)->countAllResults() > 0;
            if (!$exists) {
                $this->db->table('roles')->insert([
                    'code'        => $code,
                    'name'        => $definition['name'],
                    'description' => $definition['description'],
                    'scope'       => $definition['scope'],
                    'is_system'   => 1,
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    private function ensureManagedPermissions(): void
    {
        $definitions = [
            ['code' => 'dashboard.view', 'module' => 'dashboard', 'name' => 'View Dashboard', 'description' => 'Melihat halaman dashboard utama'],
            ['code' => 'teacher_portal.view', 'module' => 'teacher_portal', 'name' => 'View Teacher Portal', 'description' => 'Melihat portal personal guru'],
            ['code' => 'teacher_assignment_document.view', 'module' => 'teacher_portal', 'name' => 'View Personal Assignment Decree', 'description' => 'Melihat dan mencetak SK pembagian tugas milik guru yang login'],
            ['code' => 'teacher_duty_schedule.view', 'module' => 'teacher_portal', 'name' => 'View Personal Duty Schedule', 'description' => 'Melihat dan mencetak jadwal piket milik guru yang login'],
            ['code' => 'teacher_schedule.view', 'module' => 'teacher_portal', 'name' => 'View Read-only Schedule', 'description' => 'Melihat jadwal resmi secara read-only dengan prioritas guru'],
            ['code' => 'teacher_workload.view', 'module' => 'teacher_portal', 'name' => 'View Personal Workload', 'description' => 'Melihat penugasan dan beban mengajar pribadi'],
            ['code' => 'class_students.view', 'module' => 'homeroom_portal', 'name' => 'View Own Class Students', 'description' => 'Melihat peserta didik pada kelas binaan'],
            ['code' => 'class_schedule.view', 'module' => 'homeroom_portal', 'name' => 'View Own Class Schedule', 'description' => 'Melihat jadwal kelas binaan'],
            ['code' => 'class_electives.manage', 'module' => 'homeroom_portal', 'name' => 'Manage Own Class Electives', 'description' => 'Mengelola pilihan mapel kelas binaan'],
        ];

        foreach ($definitions as $definition) {
            $exists = $this->db->table('permissions')->where('code', $definition['code'])->countAllResults() > 0;
            if (!$exists) {
                $definition['created_at'] = date('Y-m-d H:i:s');
                $this->db->table('permissions')->insert($definition);
            }
        }
    }
}
