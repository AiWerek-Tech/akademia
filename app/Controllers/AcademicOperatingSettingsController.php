<?php

namespace App\Controllers;

use App\Models\AcademicYearModel;
use App\Services\AcademicCalendarGeneratorService;
use App\Services\AcademicOperatingSettingsService;
use App\Services\UnitScopeService;
use Config\Database;

class AcademicOperatingSettingsController extends BaseController
{
    public function index()
    {
        if (!has_permission('academic_calendar.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Akses pengaturan akademik ditolak.');
        }
        $years = (new AcademicYearModel())->orderBy('start_date', 'DESC')->findAll();
        $yearId = (int) ($this->request->getGet('academic_year_id') ?: ($years[0]['id'] ?? 0));
        $units = UnitScopeService::accessibleUnits();
        $service = new AcademicOperatingSettingsService();
        $policies = [];
        foreach ($units as $unit) {
            $policies[(int) $unit['id']] = $service->resolve($yearId, (int) $unit['id']);
        }
        return view('academic_calendar/settings', [
            'title' => 'Pengaturan Operasional Akademik',
            'breadcrumb_active' => 'Pengaturan Operasional Akademik',
            'years' => $years, 'yearId' => $yearId, 'units' => $units, 'policies' => $policies,
        ]);
    }

    public function save()
    {
        if (!has_permission('academic_calendar.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }
        $yearId = (int) $this->request->getPost('academic_year_id');
        $unitId = (int) $this->request->getPost('unit_id');
        UnitScopeService::assertUnit($unitId);
        if (!(new AcademicYearModel())->find($yearId)) {
            return redirect()->back()->with('error', 'Tahun ajaran tidak valid.');
        }
        try {
            $policy = (new AcademicOperatingSettingsService())->save($yearId, $unitId, [
                'source_mode' => $this->request->getPost('source_mode'),
                'working_days' => $this->request->getPost('working_days') ?: [],
                'effective_week_min_days' => $this->request->getPost('effective_week_min_days'),
                'compare_official_targets' => $this->request->getPost('compare_official_targets'),
                'notes' => $this->request->getPost('notes'),
            ], (int) session()->get('user_id'));

            $db = Database::connect();
            $calendars = $db->table('academic_calendars')->select('id, status')
                ->where('academic_year_id', $yearId)
                ->groupStart()->where('unit_id', $unitId)->orWhere('unit_id IS NULL')->groupEnd()
                ->get()->getResultArray();
            $generator = new AcademicCalendarGeneratorService();
            foreach ($calendars as $calendar) {
                if ($calendar['status'] === 'DRAFT') {
                    $result = $generator->rebuild((int) $calendar['id'], true);
                    if (!$result['success']) {
                        $warning = ['status' => 'ERROR', 'errors' => [$result['message']], 'warnings' => [], 'checked_at' => date('c')];
                        $db->table('academic_calendars')->where('id', $calendar['id'])->update([
                            'validation_status' => 'ERROR',
                            'validation_summary_json' => json_encode($warning, JSON_UNESCAPED_UNICODE),
                        ]);
                    }
                } else {
                    $warning = ['status' => 'STALE', 'errors' => [], 'warnings' => ['Pengaturan hari sekolah berubah setelah kalender diaktifkan. Buat revisi draft sebelum digunakan.'], 'checked_at' => date('c')];
                    $db->table('academic_calendars')->where('id', $calendar['id'])->update([
                        'validation_status' => 'STALE',
                        'validation_summary_json' => json_encode($warning, JSON_UNESCAPED_UNICODE),
                    ]);
                }
            }
            return redirect()->to('settings/academic-operations?academic_year_id=' . $yearId)
                ->with('success', 'Pengaturan ' . implode(', ', $policy['working_day_codes']) . ' disimpan dan kalender draft telah disinkronkan.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }
}
