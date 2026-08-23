<?php

namespace App\Controllers;

use App\Services\SystemDiagnosticsService;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

/**
 * Phase 12 — System Diagnostics & Production Hardening Web Admin Controller.
 */
class SystemDiagnosticsController extends BaseController
{
    private SystemDiagnosticsService $diagService;

    public function __construct(?SystemDiagnosticsService $diagService = null)
    {
        $this->diagService = $diagService ?? new SystemDiagnosticsService();
    }

    public function index()
    {
        $unitId = (int) session()->get('active_unit_id');
        if (! $unitId) {
            $unit = Database::connect()->table('school_units')->where('is_active', 1)->get()->getRowArray();
            $unitId = $unit ? (int) $unit['id'] : 1;
        }

        $report = $this->diagService->runFullDiagnostic($unitId);

        return view('system/diagnostics', [
            'title'             => 'Diagnostik & Kesehatan Sistem',
            'breadcrumb_active' => 'Diagnostik Sistem',
            'report'            => $report,
            'unitId'            => $unitId,
        ]);
    }

    public function purgeSessions()
    {
        $purgedCount = $this->diagService->purgeExpiredSessions(30);

        return redirect()->back()->with('success', "Pembersihan selesai. Sebanyak {$purgedCount} sesi mobile kedaluwarsa berhasil dihapus.");
    }

    public function apiHealthReport(): ResponseInterface
    {
        $unitId = (int) ($this->request->getVar('unit_id') ?? 1);
        $report = $this->diagService->runFullDiagnostic($unitId);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $report,
        ]);
    }
}
