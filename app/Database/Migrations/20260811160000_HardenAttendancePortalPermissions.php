<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Separates personal teacher attendance access from executive monitoring.
 */
final class HardenAttendancePortalPermissions extends Migration
{
    public function up()
    {
        $this->syncPersonalAttendancePermissions(false);
    }

    public function down()
    {
        $this->syncPersonalAttendancePermissions(true);
    }

    private function syncPersonalAttendancePermissions(bool $restoreExecutiveView): void
    {
        $roles = $this->db->table('roles')->select('id, code')
            ->whereIn('code', ['guru', 'wali_kelas'])->get()->getResultArray();
        $permissions = $this->db->table('permissions')->select('id, code')
            ->whereIn('code', ['teacher_attendance.view', 'attendances.record', 'attendances.view'])
            ->get()->getResultArray();
        $permissionIds = array_column($permissions, 'id', 'code');
        $now = date('Y-m-d H:i:s');

        foreach ($roles as $role) {
            $roleId = (int) $role['id'];
            if (isset($permissionIds['attendances.view'])) {
                $viewPermissionId = (int) $permissionIds['attendances.view'];
                $this->db->table('role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $viewPermissionId)
                    ->delete();

                if ($restoreExecutiveView) {
                    $this->insertGrant($roleId, $viewPermissionId, $now);
                }
            }

            foreach (['teacher_attendance.view', 'attendances.record'] as $code) {
                if (isset($permissionIds[$code])) {
                    $this->insertGrant($roleId, (int) $permissionIds[$code], $now);
                }
            }
        }
    }

    private function insertGrant(int $roleId, int $permissionId, string $now): void
    {
        $exists = $this->db->table('role_permissions')
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->countAllResults() > 0;
        if (!$exists) {
            $this->db->table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => $now,
            ]);
        }
    }
}
