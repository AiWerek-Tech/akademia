<?php

namespace App\Controllers;

use Config\Database;

class Home extends BaseController
{
    public function index()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        $db = Database::connect();
        
        // Live database statistics
        $totalUsers = $db->table('users')->countAll();
        $totalUnits = $db->table('school_units')->where('is_active', 1)->countAllResults();
        
        // Active period description
        $activePeriod = get_active_period();
        $activePeriodStr = $activePeriod 
            ? "T.A " . $activePeriod['year_name'] . " (" . ($activePeriod['semester_number'] == 1 ? 'Ganjil' : 'Genap') . ")" 
            : 'Belum ditentukan';

        // Fetch 5 latest audit logs
        $recentAudits = $db->table('audit_logs al')
            ->select('al.*, u.username')
            ->join('users u', 'u.id = al.user_id', 'left')
            ->orderBy('al.created_at', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        return view('dashboard', [
            'title'             => 'Dashboard Utama',
            'breadcrumb_active' => 'Dashboard',
            'totalUsers'        => $totalUsers,
            'totalUnits'        => $totalUnits,
            'activePeriodStr'   => $activePeriodStr,
            'recentAudits'      => $recentAudits
        ]);
    }
}
