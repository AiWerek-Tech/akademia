<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds entity linkage columns to users table and new permissions
 * for multi-role RBAC (Kepala Sekolah, Wali Kelas, Guru).
 */
class AddMultiRoleEntityLinkage extends Migration
{
    public function up()
    {
        // 1. Add linkage columns sequentially. classroom_id references
        // teacher_id in its AFTER clause, so a combined ALTER is not portable
        // across the production and test database drivers.
        if (!$this->columnExists('users', 'teacher_id')) {
            $this->db->query(
                'ALTER TABLE users ADD COLUMN teacher_id BIGINT UNSIGNED NULL AFTER updated_by'
            );
        }

        if (!$this->columnExists('users', 'classroom_id')) {
            $this->db->query(
                'ALTER TABLE users ADD COLUMN classroom_id BIGINT UNSIGNED NULL AFTER teacher_id'
            );
        }

        // 2. Add indexes for the new FK columns
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_users_teacher_id ON users (teacher_id)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_users_classroom_id ON users (classroom_id)');

        // 3. Insert wali_kelas role if not exists
        $existing = $this->db->table('roles')->where('code', 'wali_kelas')->get()->getRowArray();
        if (!$existing) {
            $this->db->table('roles')->insert([
                'code'        => 'wali_kelas',
                'name'        => 'Wali Kelas',
                'description' => 'Mengelola pemilihan mapel dan data siswa kelas bimbingannya',
                'scope'       => 'UNIT',
                'is_system'   => 1,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        // 4. Insert new permissions for multi-role support
        $newPermissions = [
            // Teacher Portal
            ['code' => 'teacher_portal.view', 'module' => 'teacher_portal', 'name' => 'View Teacher Portal', 'description' => 'Melihat halaman Portal Guru (jadwal & beban pribadi)'],
            ['code' => 'teacher_workload.view', 'module' => 'teacher_portal', 'name' => 'View Personal Workload', 'description' => 'Melihat rincian beban jam mengajar pribadi'],
            ['code' => 'teacher_schedule.view', 'module' => 'teacher_portal', 'name' => 'View Personal Schedule', 'description' => 'Melihat jadwal mengajar pribadi'],

            // Wali Kelas
            ['code' => 'class_electives.manage', 'module' => 'electives', 'name' => 'Manage Class Electives', 'description' => 'Mengelola pemilihan mapel siswa di kelas bimbingan'],
            ['code' => 'class_students.view', 'module' => 'classrooms', 'name' => 'View Class Students', 'description' => 'Melihat daftar siswa kelas bimbingan'],
            ['code' => 'class_schedule.view', 'module' => 'schedules', 'name' => 'View Class Schedule', 'description' => 'Melihat jadwal kelas bimbingan'],

            // Kepala Sekolah Reports
            ['code' => 'reports.view', 'module' => 'reports', 'name' => 'View Reports', 'description' => 'Melihat laporan eksekutif unit sekolah'],
            ['code' => 'reports.export', 'module' => 'reports', 'name' => 'Export Reports', 'description' => 'Mengunduh/export laporan dalam format PDF/Excel'],

            // Existing modules read-only for non-admin roles
            ['code' => 'teachers.view', 'module' => 'teachers', 'name' => 'View Teachers', 'description' => 'Melihat daftar guru'],
            ['code' => 'students.view', 'module' => 'students', 'name' => 'View Students', 'description' => 'Melihat daftar peserta didik'],
            ['code' => 'classrooms.view', 'module' => 'classrooms', 'name' => 'View Classrooms', 'description' => 'Melihat daftar kelas/rombel'],
            ['code' => 'rooms.view', 'module' => 'rooms', 'name' => 'View Rooms', 'description' => 'Melihat daftar ruangan sekolah'],
        ];

        foreach ($newPermissions as $perm) {
            $exists = $this->db->table('permissions')->where('code', $perm['code'])->get()->getRowArray();
            if (!$exists) {
                $perm['created_at'] = date('Y-m-d H:i:s');
                $this->db->table('permissions')->insert($perm);
            }
        }

        // 5. Map permissions to roles
        $this->mapPermissionsToRoles();
    }

    private function mapPermissionsToRoles()
    {
        $db = $this->db;

        // Fetch role IDs
        $roleRows = $db->table('roles')->get()->getResultArray();
        $roleIds = [];
        foreach ($roleRows as $r) {
            $roleIds[$r['code']] = (int) $r['id'];
        }

        // Fetch permission IDs
        $permRows = $db->table('permissions')->get()->getResultArray();
        $permIds = [];
        foreach ($permRows as $p) {
            $permIds[$p['code']] = (int) $p['id'];
        }

        // Super admin gets ALL new permissions
        if (isset($roleIds['super_admin'])) {
            foreach ($permIds as $code => $permId) {
                $this->insertRolePermission($roleIds['super_admin'], $permId);
            }
        }

        // Kepala Sekolah permissions
        if (isset($roleIds['kepala_sekolah'])) {
            $ksPerms = [
                'dashboard.view', 'units.view',
                'academic_years.view', 'academic_periods.view',
                'teachers.view', 'students.view', 'classrooms.view', 'rooms.view',
                'curriculum.view', 'assignments.view', 'workload.view',
                'schedules.view', 'schedules.export',
                'electives.view',
                'reports.view', 'reports.export',
                'users.view', 'audit.view',
            ];
            foreach ($ksPerms as $code) {
                if (isset($permIds[$code])) {
                    $this->insertRolePermission($roleIds['kepala_sekolah'], $permIds[$code]);
                }
            }
        }

        // Wali Kelas permissions
        if (isset($roleIds['wali_kelas'])) {
            $wkPerms = [
                'dashboard.view',
                'class_students.view', 'class_electives.manage', 'class_schedule.view',
                'students.view', 'classrooms.view',
                'electives.view',
                'teacher_portal.view', 'teacher_workload.view', 'teacher_schedule.view',
            ];
            foreach ($wkPerms as $code) {
                if (isset($permIds[$code])) {
                    $this->insertRolePermission($roleIds['wali_kelas'], $permIds[$code]);
                }
            }
        }

        // Guru permissions
        if (isset($roleIds['guru'])) {
            $guruPerms = [
                'dashboard.view',
                'teacher_portal.view', 'teacher_workload.view', 'teacher_schedule.view',
            ];
            foreach ($guruPerms as $code) {
                if (isset($permIds[$code])) {
                    $this->insertRolePermission($roleIds['guru'], $permIds[$code]);
                }
            }
        }
    }

    private function insertRolePermission(int $roleId, int $permId): void
    {
        $exists = $this->db->table('role_permissions')
            ->where('role_id', $roleId)
            ->where('permission_id', $permId)
            ->get()->getRowArray();
        if (!$exists) {
            $this->db->table('role_permissions')->insert([
                'role_id'       => $roleId,
                'permission_id' => $permId,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down()
    {
        // Remove columns
        if ($this->columnExists('users', 'classroom_id')) {
            $this->db->query('ALTER TABLE users DROP COLUMN classroom_id');
        }
        if ($this->columnExists('users', 'teacher_id')) {
            $this->db->query('ALTER TABLE users DROP COLUMN teacher_id');
        }

        // Remove wali_kelas role permissions first, then role
        $waliKelas = $this->db->table('roles')->where('code', 'wali_kelas')->get()->getRowArray();
        if ($waliKelas) {
            $this->db->table('role_permissions')->where('role_id', $waliKelas['id'])->delete();
            $this->db->table('user_roles')->where('role_id', $waliKelas['id'])->delete();
            $this->db->table('roles')->where('id', $waliKelas['id'])->delete();
        }

        // Remove new permissions
        $newCodes = [
            'teacher_portal.view', 'teacher_workload.view', 'teacher_schedule.view',
            'class_electives.manage', 'class_students.view', 'class_schedule.view',
            'reports.view', 'reports.export',
            'teachers.view', 'students.view', 'classrooms.view', 'rooms.view',
        ];
        foreach ($newCodes as $code) {
            $perm = $this->db->table('permissions')->where('code', $code)->get()->getRowArray();
            if ($perm) {
                $this->db->table('role_permissions')->where('permission_id', $perm['id'])->delete();
                $this->db->table('permissions')->where('id', $perm['id'])->delete();
            }
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        $row = $this->db->query(
            'SELECT 1 FROM information_schema.COLUMNS ' .
            'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
            [$table, $column]
        )->getRowArray();

        return $row !== null;
    }
}
