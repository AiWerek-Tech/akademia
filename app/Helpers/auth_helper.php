<?php

if (!function_exists('has_permission')) {
    /**
     * Checks if the currently logged-in user has the specified permission code
     */
    function has_permission(string $code): bool
    {
        $session = session();
        if (!$session->get('logged_in')) {
            return false;
        }

        static $permissionCache = [];
        if (ENVIRONMENT === 'testing') {
            $permissionCache = [];
        }
        $userId = (int) $session->get('user_id');
        $unitId = $session->get('active_unit_id');
        $cacheKey = $userId . ':' . (string) $unitId;
        if (!array_key_exists($cacheKey, $permissionCache)) {
            $permissionCache[$cacheKey] = (new \App\Models\UserModel())
                ->getPermissions($userId, $unitId ? (int) $unitId : null);
            $session->set('permissions', $permissionCache[$cacheKey]);
        }
        $permissions = $permissionCache[$cacheKey];
        return in_array($code, $permissions, true);
    }
}
if (!function_exists('active_user_name')) {
    /**
     * Gets the full name of the logged-in user
     */
    function active_user_name(): string
    {
        return session()->get('full_name') ?? 'Guest';
    }
}

if (!function_exists('active_user_role')) {
    /**
     * Gets the main role name of the logged-in user
     */
    function active_user_role(): string
    {
        return session()->get('role_name') ?? 'Visitor';
    }
}

if (!function_exists('is_super_admin')) {
    /**
     * Checks if a user has the super_admin or superadmin role
     */
    function is_super_admin(?int $userId = null): bool
    {
        $session = session();
        if ($userId === null) {
            $roleCode = $session->get('role_code');
            if ($roleCode !== null) {
                return in_array($roleCode, ['superadmin', 'super_admin'], true);
            }
            $userId = (int) $session->get('user_id');
        }

        if ($userId <= 0) {
            return false;
        }

        static $superAdminCache = [];
        if (ENVIRONMENT === 'testing') {
            $superAdminCache = [];
        }
        if (!array_key_exists($userId, $superAdminCache)) {
            $db = \Config\Database::connect();
            $row = $db->table('user_roles ur')
                ->join('roles r', 'r.id = ur.role_id')
                ->where('ur.user_id', $userId)
                ->whereIn('r.code', ['superadmin', 'super_admin'])
                ->get()
                ->getRowArray();

            $superAdminCache[$userId] = ($row !== null);
        }

        return $superAdminCache[$userId];
    }
}

if (!function_exists('get_user_units')) {
    /**
     * Gets all school units the user has access to
     */
    function get_user_units(): array
    {
        $session = session();
        $userId = $session->get('user_id');
        if (!$userId) {
            return [];
        }
        $db = \Config\Database::connect();

        if (is_super_admin((int) $userId)) {
            return $db->table('school_units')
                ->where('is_active', 1)
                ->where('deleted_at IS NULL')
                ->orderBy('name', 'ASC')
                ->get()
                ->getResultArray();
        }

        return $db->table('user_unit_access uua')
            ->select('su.*, uua.is_default')
            ->join('school_units su', 'su.id = uua.unit_id')
            ->where('uua.user_id', $userId)
            ->where('su.is_active', 1)
            ->where('su.deleted_at IS NULL')
            ->get()
            ->getResultArray();
    }
}

if (!function_exists('get_active_unit')) {
    /**
     * Gets the active school unit row
     */
    function get_active_unit(): ?array
    {
        $activeUnitId = session()->get('active_unit_id');
        if (!$activeUnitId) {
            return null;
        }
        $db = \Config\Database::connect();
        return $db->table('school_units')->where('id', $activeUnitId)->get()->getRowArray();
    }
}

if (!function_exists('get_academic_periods')) {
    /**
     * Gets all academic periods registered
     */
    function get_academic_periods(): array
    {
        $db = \Config\Database::connect();
        return $db->table('academic_periods ap')
            ->select('ap.*, ay.name as year_name')
            ->join('academic_years ay', 'ay.id = ap.academic_year_id')
            ->orderBy('ay.name', 'DESC')
            ->orderBy('ap.semester_number', 'DESC')
            ->get()
            ->getResultArray();
    }
}

if (!function_exists('get_active_period')) {
    /**
     * Gets the active academic period row
     */
    function get_active_period(): ?array
    {
        $activePeriodId = session()->get('active_period_id');
        if (!$activePeriodId) {
            return null;
        }
        $db = \Config\Database::connect();
        return $db->table('academic_periods ap')
            ->select('ap.*, ay.name as year_name')
            ->join('academic_years ay', 'ay.id = ap.academic_year_id')
            ->where('ap.id', $activePeriodId)
            ->get()
            ->getRowArray();
    }
}

if (!function_exists('has_role')) {
    /**
     * Checks if the currently logged-in user has any of the specified role codes.
     * Checks against all_role_codes stored in session.
     */
    function has_role(string ...$codes): bool
    {
        $allRoles = session()->get('all_role_codes') ?? [];
        if (empty($allRoles)) {
            // Fallback to single role_code
            $singleRole = session()->get('role_code');
            $allRoles = $singleRole ? [$singleRole] : [];
        }
        foreach ($codes as $code) {
            if (in_array($code, $allRoles, true)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('is_kepala_sekolah')) {
    /**
     * Checks if the logged-in user has the kepala_sekolah role
     */
    function is_kepala_sekolah(): bool
    {
        return has_role('kepala_sekolah');
    }
}

if (!function_exists('is_wali_kelas')) {
    /**
     * Checks if the logged-in user has the wali_kelas role
     */
    function is_wali_kelas(): bool
    {
        return has_role('wali_kelas');
    }
}

if (!function_exists('is_guru')) {
    /**
     * Checks if the logged-in user has the guru role
     */
    function is_guru(): bool
    {
        return has_role('guru');
    }
}

if (!function_exists('get_teacher_id')) {
    /**
     * Gets the teacher_id linked to the current user (or null)
     */
    function get_teacher_id(): ?int
    {
        $id = session()->get('teacher_id');
        return $id ? (int) $id : null;
    }
}

if (!function_exists('get_classroom_id')) {
    /**
     * Gets the classroom_id linked to the current user (or null)
     */
    function get_classroom_id(): ?int
    {
        $id = session()->get('classroom_id');
        return $id ? (int) $id : null;
    }
}
