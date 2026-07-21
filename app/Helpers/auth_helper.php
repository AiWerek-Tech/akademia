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
        
        $permissions = $session->get('permissions') ?? [];
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
        return $db->table('user_unit_access uua')
            ->select('su.*, uua.is_default')
            ->join('school_units su', 'su.id = uua.unit_id')
            ->where('uua.user_id', $userId)
            ->where('su.is_active', 1)
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
