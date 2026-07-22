<?php

namespace App\Controllers;

use Config\Database;
use App\Services\UnitScopeService;

class Home extends BaseController
{
    public function index()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        $db = Database::connect();
        
        $unitIds = UnitScopeService::accessibleUnitIds();
        $totalUnits = count($unitIds);
        $totalUsers = $db->table('users')->where('is_active', 1)->where('deleted_at IS NULL')->countAllResults();

        $activePeriod = get_active_period();
        $activePeriodStr = $activePeriod 
            ? "T.A " . $activePeriod['year_name'] . " (" . ($activePeriod['semester_number'] == 1 ? 'Ganjil' : 'Genap') . ")" 
            : 'Belum ditentukan';

        $masterCounts = ['teachers' => 0, 'subjects' => 0, 'classrooms' => 0, 'rooms' => 0];
        if ($unitIds !== []) {
            $masterCounts['teachers'] = $db->table('teachers t')
                ->select('t.id')->distinct()
                ->join('teacher_unit_assignments tua', 'tua.teacher_id = t.id', 'left')
                ->where('t.is_active', 1)->where('t.deleted_at IS NULL')
                ->groupStart()->whereIn('t.primary_unit_id', $unitIds)->orWhereIn('tua.unit_id', $unitIds)->groupEnd()
                ->countAllResults();
            $masterCounts['subjects'] = $db->table('subject_unit_availability sua')
                ->select('sua.subject_id')->distinct()->join('subjects s', 's.id = sua.subject_id')
                ->whereIn('sua.unit_id', $unitIds)->where('sua.is_available', 1)->where('s.is_active', 1)
                ->where('s.deleted_at IS NULL')->countAllResults();
            $masterCounts['classrooms'] = $db->table('classrooms')->whereIn('unit_id', $unitIds)
                ->where('is_active', 1)->where('deleted_at IS NULL')->countAllResults();
            $masterCounts['rooms'] = $db->table('rooms')->groupStart()->whereIn('unit_id', $unitIds)->orWhere('shared_between_units', 1)->groupEnd()
                ->where('is_active', 1)->where('deleted_at IS NULL')->countAllResults();
        }

        $readiness = [
            ['label' => 'Unit sekolah dapat diakses', 'ready' => $totalUnits > 0, 'href' => 'settings/units'],
            ['label' => 'Periode akademik aktif', 'ready' => $activePeriod !== null, 'href' => 'academic-periods'],
            ['label' => 'Guru aktif tersedia', 'ready' => $masterCounts['teachers'] > 0, 'href' => 'teachers'],
            ['label' => 'Mata pelajaran tersedia', 'ready' => $masterCounts['subjects'] > 0, 'href' => 'subjects'],
            ['label' => 'Rombel aktif tersedia', 'ready' => $masterCounts['classrooms'] > 0, 'href' => 'classrooms'],
            ['label' => 'Ruangan aktif tersedia', 'ready' => $masterCounts['rooms'] > 0, 'href' => 'rooms'],
        ];
        $readinessDone = count(array_filter($readiness, static fn (array $item): bool => $item['ready']));

        $recentAudits = $db->table('audit_logs al')
            ->select('al.*, u.username')
            ->join('users u', 'u.id = al.user_id', 'left')
            ->orderBy('al.created_at', 'DESC')
            ->limit(8)
            ->get()
            ->getResultArray();

        return view('dashboard', [
            'title'             => 'Dashboard Utama',
            'breadcrumb_active' => 'Dashboard',
            'totalUsers'        => $totalUsers,
            'totalUnits'        => $totalUnits,
            'activePeriodStr'   => $activePeriodStr,
            'recentAudits'      => $recentAudits,
            'activePeriod'      => $activePeriod,
            'masterCounts'      => $masterCounts,
            'readiness'         => $readiness,
            'readinessDone'     => $readinessDone,
        ]);
    }
}
