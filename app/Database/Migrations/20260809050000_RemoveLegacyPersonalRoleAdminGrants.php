<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\RolePermissions;

/**
 * Removes grants accumulated before personal portals were separated.
 * Superadmin can still customize these roles afterwards from the UI.
 */
class RemoveLegacyPersonalRoleAdminGrants extends Migration
{
    public function up()
    {
        foreach (RolePermissions::managedRoles() as $roleCode => $allowedCodes) {
            $role = $this->db->table('roles')->where('code', $roleCode)->get()->getRowArray();
            if (!$role) {
                continue;
            }

            $allowedPermissionIds = array_map('intval', array_column(
                $this->db->table('permissions')->select('id')->whereIn('code', $allowedCodes)->get()->getResultArray(),
                'id'
            ));
            $builder = $this->db->table('role_permissions')->where('role_id', (int) $role['id']);
            if ($allowedPermissionIds !== []) {
                $builder->whereNotIn('permission_id', $allowedPermissionIds);
            }
            $builder->delete();
        }
    }

    public function down()
    {
        // Legacy administrative grants are intentionally not restored.
    }
}
