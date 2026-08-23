<?php

namespace App\Controllers;

use App\Services\UniversalSyncService;
use Config\Database;
use Exception;

/**
 * Phase 11 — System Integration & Mobile Sync Admin Controller.
 *
 * Web Admin panel for monitoring sync versioning, active mobile sessions,
 * file storage registry, and triggering manual delta updates.
 */
class SystemIntegrationController extends BaseController
{
    private UniversalSyncService $syncService;

    public function __construct(?UniversalSyncService $syncService = null)
    {
        $this->syncService = $syncService ?? new UniversalSyncService();
    }

    public function index()
    {
        $unitId = (int) session()->get('active_unit_id');
        if (! $unitId) {
            $unit = Database::connect()->table('school_units')->where('is_active', 1)->get()->getRowArray();
            $unitId = $unit ? (int) $unit['id'] : 1;
        }

        $overview = $this->syncService->getSyncOverview($unitId);
        $files    = $this->syncService->listFiles($unitId, null, 15);

        return view('system/sync_dashboard', [
            'title'             => 'Integrasi & Sync Mobile',
            'breadcrumb_active' => 'Sync Mobile',
            'overview'          => $overview,
            'files'             => $files,
            'unitId'            => $unitId,
            'tables'            => UniversalSyncService::SUPPORTED_SYNC_TABLES,
        ]);
    }

    public function bumpVersion(string $tableName)
    {
        if (! in_array($tableName, UniversalSyncService::SUPPORTED_SYNC_TABLES, true)) {
            return redirect()->back()->with('error', 'Tabel tidak valid.');
        }

        $unitId = (int) session()->get('active_unit_id');
        if (! $unitId) {
            $unit = Database::connect()->table('school_units')->where('is_active', 1)->get()->getRowArray();
            $unitId = $unit ? (int) $unit['id'] : 1;
        }

        $newVer = $this->syncService->incrementVersion($tableName, $unitId);

        return redirect()->back()->with('success', "Versi tabel [{$tableName}] berhasil dinaikkan ke versi {$newVer}. Seluruh aplikasi mobile akan memperbarui tabel ini pada sinkronisasi berikutnya.");
    }

    public function revokeSession(int $id)
    {
        $revoked = $this->syncService->revokeSessionById($id);

        if (! $revoked) {
            return redirect()->back()->with('error', 'Sesi mobile tidak ditemukan.');
        }

        return redirect()->back()->with('success', 'Sesi mobile berhasil dicabut.');
    }
}
