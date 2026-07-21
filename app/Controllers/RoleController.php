<?php

namespace App\Controllers;

use App\Models\RoleModel;
use App\Services\AuditService;
use Config\Database;

class RoleController extends BaseController
{
    public function index()
    {
        if (!has_permission('roles.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $roleModel = new RoleModel();
        $roles = $roleModel->findAll();

        $db = Database::connect();
        
        // Fetch permission count per role
        foreach ($roles as &$r) {
            $count = $db->table('role_permissions')->where('role_id', $r['id'])->countAllResults();
            $r['permission_count'] = $count;
        }

        return view('roles/index', [
            'title'             => 'Manajemen Peran & Wewenang',
            'breadcrumb_active' => 'Role Management',
            'roles'             => $roles
        ]);
    }

    public function permissions(int $roleId)
    {
        if (!has_permission('roles.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $roleModel = new RoleModel();
        $role = $roleModel->find($roleId);

        if (!$role) {
            return redirect()->to('/roles')->with('error', 'Peran tidak ditemukan.');
        }

        $db = Database::connect();

        // Get all permissions
        $permissions = $db->table('permissions')->orderBy('module', 'ASC')->orderBy('code', 'ASC')->get()->getResultArray();

        // Get active permissions for this role
        $activePerms = array_column(
            $db->table('role_permissions')->where('role_id', $roleId)->get()->getResultArray(),
            'permission_id'
        );

        return view('roles/permissions', [
            'title'             => 'Pengaturan Wewenang: ' . esc($role['name']),
            'breadcrumb_active' => 'Wewenang Peran',
            'role'              => $role,
            'permissions'       => $permissions,
            'activePerms'       => $activePerms
        ]);
    }

    public function updatePermissions(int $roleId)
    {
        if (!has_permission('roles.manage')) {
            return redirect()->to('/roles')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $roleModel = new RoleModel();
        $role = $roleModel->find($roleId);

        if (!$role) {
            return redirect()->to('/roles')->with('error', 'Peran tidak ditemukan.');
        }

        // Prevent modification of Super Admin permissions to avoid lockout
        if (in_array($role['code'], ['superadmin', 'super_admin'], true)) {
            return redirect()->to('/roles')->with('error', 'Wewenang peran Super Admin bersifat mutlak dan tidak dapat diubah.');
        }

        $db = Database::connect();
        $db->transBegin();

        try {
            // Get current active permissions for audit
            $beforePerms = array_column(
                $db->table('role_permissions')->where('role_id', $roleId)->get()->getResultArray(),
                'permission_id'
            );

            // Delete existing
            $db->table('role_permissions')->where('role_id', $roleId)->delete();

            // Insert new ones
            $permissionIds = (array)$this->request->getPost('permissions');
            foreach ($permissionIds as $pId) {
                $db->table('role_permissions')->insert([
                    'role_id'       => $roleId,
                    'permission_id' => (int)$pId
                ]);
            }

            $db->transCommit();

            // Log Audit
            AuditService::log(
                'roles',
                'update_permissions',
                'Role',
                $roleId,
                ['permissions' => $beforePerms],
                ['permissions' => $permissionIds],
                "Wewenang peran {$role['code']} diperbarui"
            );

            return redirect()->to('/roles')->with('success', "Wewenang peran {$role['name']} berhasil diperbarui.");
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Gagal memperbarui wewenang: ' . $e->getMessage());
        }
    }
}
