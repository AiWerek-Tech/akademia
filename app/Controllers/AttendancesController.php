<?php

namespace App\Controllers;

use App\Services\AttendanceService;
use App\Services\AuditService;
use App\Services\UnitScopeService;
use App\Exceptions\AuthorizationException;
use Config\Database;

class AttendancesController extends BaseController
{
    private AttendanceService $attendanceService;

    public function __construct()
    {
        $this->attendanceService = new AttendanceService();
    }

    private function scopedUnits(?int $requestedUnitId): int|array
    {
        if ($requestedUnitId !== null && $requestedUnitId > 0) {
            UnitScopeService::assertUnit($requestedUnitId);
            return $requestedUnitId;
        }

        $allowed = UnitScopeService::accessibleUnitIds();
        if ($allowed === []) {
            throw new AuthorizationException('Akun tidak memiliki akses ke unit sekolah.');
        }
        return $allowed;
    }

    private function assertSessionUnit(array $session): void
    {
        UnitScopeService::assertUnit((int) ($session['unit_id'] ?? 0));
    }

    /**
     * Executive Superadmin Professional Attendance Dashboard & Monitoring
     */
    public function index()
    {
        if (!has_permission('attendances.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses ke Monitoring Presensi.');
        }

        $activePeriod = get_active_period();
        if (!$activePeriod) {
            return redirect()->to('/dashboard')->with('error', 'Tidak ada periode akademik aktif.');
        }

        $db = Database::connect();

        // Filters
        $unitId      = ($u = (int) $this->request->getGet('unit_id')) > 0 ? $u : null;
        $classroomId = ($c = (int) $this->request->getGet('classroom_id')) > 0 ? $c : null;
        $subjectId   = ($s = (int) $this->request->getGet('subject_id')) > 0 ? $s : null;
        $startDate   = trim((string) $this->request->getGet('start_date'));
        $endDate     = trim((string) $this->request->getGet('end_date'));
        $sessionType = strtoupper(trim((string) $this->request->getGet('session_type')));

        $scopedUnits = $this->scopedUnits($unitId);
        if ($classroomId !== null) {
            UnitScopeService::assertClassroom($classroomId);
        }
        if ($subjectId !== null) {
            UnitScopeService::assertSubject($subjectId);
        }

        $analytics = $this->attendanceService->getExecutiveAnalytics(
            (int) $activePeriod['id'],
            $scopedUnits,
            $classroomId,
            $subjectId,
            $startDate,
            $endDate,
            $sessionType ?: null
        );

        // Fetch Dropdown options
        $allowedUnitIds = UnitScopeService::accessibleUnitIds();
        $units      = UnitScopeService::accessibleUnits();
        $classrooms = $db->table('classrooms')->where('academic_period_id', (int) $activePeriod['id'])->whereIn('unit_id', $allowedUnitIds)->where('deleted_at IS NULL')->orderBy('name', 'ASC')->get()->getResultArray();
        $subjects   = $db->table('subjects')->where('is_active', 1)->orderBy('name', 'ASC')->get()->getResultArray();

        return view('attendances/index', array_merge($analytics, [
            'title'             => 'Monitoring Presensi & Jurnal Mengajar',
            'breadcrumb_active' => 'Presensi Siswa',
            'activePeriod'      => $activePeriod,
            'units'             => $units,
            'classrooms'        => $classrooms,
            'subjects'          => $subjects,
            'selectedUnit'      => $unitId,
            'selectedClassroom' => $classroomId,
            'selectedSubject'   => $subjectId,
            'startDate'         => $startDate,
            'endDate'           => $endDate,
            'selectedSessionType' => $sessionType,
        ]));
    }

    /**
     * Detailed View of an Attendance Session
     */
    public function show($id)
    {
        if (!has_permission('attendances.view')) {
            return redirect()->to('/dashboard')->with('error', 'Tidak ada hak akses melihat detail presensi.');
        }

        $details = $this->attendanceService->getSessionDetails((int)$id);
        if (!$details) {
            return redirect()->to('/attendances')->with('error', 'Sesi presensi tidak ditemukan.');
        }
        $this->assertSessionUnit($details['session']);

        return view('attendances/show', array_merge($details, [
            'title'             => 'Detail Presensi & Jurnal Kelas',
            'breadcrumb_active' => 'Presensi Siswa',
        ]));
    }

    /**
     * Verify / Confirm an Attendance Session
     */
    public function verify($id)
    {
        if (!has_permission('attendances.admin')) {
            return redirect()->to('/attendances')->with('error', 'Hanya Superadmin/Wakasek yang dapat memverifikasi jurnal presensi.');
        }

        $db = Database::connect();
        $session = $db->table('attendance_sessions')->where('id', (int)$id)->where('deleted_at IS NULL')->get()->getRowArray();
        if (!$session) {
            return redirect()->to('/attendances')->with('error', 'Sesi presensi tidak ditemukan.');
        }
        $this->assertSessionUnit($session);
        if (strtoupper((string) $session['status']) === 'DRAFT') {
            return redirect()->back()->with('error', 'Draft harus dikirim oleh pencatat sebelum dapat diverifikasi.');
        }

        $now = date('Y-m-d H:i:s');
        $userId = (int) session()->get('user_id');
        $db->table('attendance_sessions')
            ->where('id', (int)$id)
            ->update([
                'status'     => 'VERIFIED',
                'verified_at' => $now,
                'verified_by' => $userId,
                'locked_at' => $now,
                'updated_by' => $userId,
                'updated_at' => $now,
            ]);
        AuditService::log('attendance','VERIFY_SESSION','AttendanceSession',(int)$id,$session,['status'=>'VERIFIED','verified_at'=>$now],'Sesi presensi diverifikasi dan dikunci');

        return redirect()->back()->with('success', 'Sesi jurnal presensi telah disahkan (VERIFIED).');
    }

    public function reopen($id)
    {
        if (!has_permission('attendances.admin')) {
            return redirect()->to('/attendances')->with('error', 'Hanya pengelola presensi yang dapat membuka sesi.');
        }
        $db = Database::connect();
        $session = $db->table('attendance_sessions')->where('id', (int) $id)->where('deleted_at IS NULL')->get()->getRowArray();
        if (!$session) {
            return redirect()->to('/attendances')->with('error', 'Sesi tidak ditemukan.');
        }
        $this->assertSessionUnit($session);
        $db->table('attendance_sessions')->where('id', (int) $id)->update([
            'status' => 'SUBMITTED', 'verified_at' => null, 'verified_by' => null, 'locked_at' => null,
            'revision_number' => (int) ($session['revision_number'] ?? 1) + 1,
            'updated_by' => (int) session()->get('user_id'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        AuditService::log('attendance','REOPEN_SESSION','AttendanceSession',(int)$id,$session,['status'=>'SUBMITTED'],'Sesi dibuka untuk koreksi');
        return redirect()->back()->with('success', 'Sesi dibuka kembali untuk koreksi dan wajib diverifikasi ulang.');
    }

    /**
     * Bulk Verify Selected Attendance Sessions
     */
    public function bulkVerify()
    {
        if (!has_permission('attendances.admin')) {
            return redirect()->to('/attendances')->with('error', 'Hanya Superadmin/Wakasek yang dapat memverifikasi presensi.');
        }

        $sessionIds = $this->request->getPost('session_ids');
        if (empty($sessionIds) || !is_array($sessionIds)) {
            return redirect()->back()->with('error', 'Pilih minimal satu sesi presensi untuk diverifikasi.');
        }

        $normalizedIds = array_values(array_unique(array_filter(array_map('intval', $sessionIds))));
        if ($normalizedIds === []) {
            return redirect()->back()->with('error', 'ID sesi presensi tidak valid.');
        }
        $allowedUnitIds = UnitScopeService::accessibleUnitIds();
        $db = Database::connect();
        $eligibleIds = array_map('intval', array_column($db->table('attendance_sessions')
            ->select('id')
            ->whereIn('id', $normalizedIds)
            ->whereIn('unit_id', $allowedUnitIds)
            ->where('status !=', 'DRAFT')
            ->where('deleted_at IS NULL')
            ->get()->getResultArray(), 'id'));
        if (count($eligibleIds) !== count($normalizedIds)) {
            return $this->response->setStatusCode(403)->setBody(
                view('errors/html/error_403', ['message' => 'Sebagian sesi berada di luar cakupan unit Anda.'])
            );
        }

        $now = date('Y-m-d H:i:s');
        $userId = (int) session()->get('user_id');
        $db->table('attendance_sessions')
            ->whereIn('id', $eligibleIds)
            ->update([
                'status'     => 'VERIFIED',
                'verified_at' => $now,
                'verified_by' => $userId,
                'locked_at' => $now,
                'updated_by' => $userId,
                'updated_at' => $now,
            ]);
        AuditService::log('attendance','BULK_VERIFY','AttendanceSession',null,null,['session_ids'=>$eligibleIds,'verified_at'=>$now],'Verifikasi massal sesi presensi');

        return redirect()->back()->with('success', count($eligibleIds) . ' sesi presensi berhasil disahkan (VERIFIED).');
    }

    /**
     * Export Attendance Data to CSV
     */
    public function export()
    {
        if (!has_permission('attendances.admin')) {
            return redirect()->to('/attendances')->with('error', 'Tidak ada akses ekspor data presensi.');
        }

        $activePeriod = get_active_period();
        $unitId       = ($u = (int) $this->request->getGet('unit_id')) > 0 ? $u : null;
        $classroomId  = ($c = (int) $this->request->getGet('classroom_id')) > 0 ? $c : null;
        $subjectId    = ($s = (int) $this->request->getGet('subject_id')) > 0 ? $s : null;
        $startDate    = trim((string) $this->request->getGet('start_date'));
        $endDate      = trim((string) $this->request->getGet('end_date'));
        $sessionType  = strtoupper(trim((string) $this->request->getGet('session_type')));

        if ($classroomId !== null) UnitScopeService::assertClassroom($classroomId);
        if ($subjectId !== null) UnitScopeService::assertSubject($subjectId);

        $analytics = $this->attendanceService->getExecutiveAnalytics(
            (int) ($activePeriod['id'] ?? 0),
            $this->scopedUnits($unitId),
            $classroomId,
            $subjectId,
            $startDate,
            $endDate,
            $sessionType ?: null
        );

        $filename = 'rekap_presensi_siswa_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID Sesi', 'Tanggal', 'Jenis Sesi', 'Pertemuan Ke', 'Unit', 'Kelas', 'Kegiatan/Mata Pelajaran', 'Pencatat', 'Pokok Bahasan', 'Status Sesi']);

        foreach ($analytics['sessions'] as $sess) {
            fputcsv($output, [
                $sess['id'],
                $sess['attendance_date'],
                AttendanceService::sessionTypeLabel((string) ($sess['session_type'] ?? 'SUBJECT')),
                $sess['meeting_number'],
                $sess['unit_name'],
                $sess['classroom_name'],
                $sess['subject_name'],
                $sess['teacher_name'],
                $sess['topic'] ?? '',
                $sess['status'],
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Executive Print Report - Unit / School Overall Attendance Summary
     */
    public function printUnitReport()
    {
        if (!has_permission('attendances.view')) {
            return redirect()->to('/dashboard')->with('error', 'Tidak ada hak akses mencetak laporan.');
        }

        $activePeriod = get_active_period();
        $unitId       = ($u = (int) $this->request->getGet('unit_id')) > 0 ? $u : null;
        $classroomId  = ($c = (int) $this->request->getGet('classroom_id')) > 0 ? $c : null;
        $subjectId    = ($s = (int) $this->request->getGet('subject_id')) > 0 ? $s : null;
        $startDate    = trim((string) $this->request->getGet('start_date'));
        $endDate      = trim((string) $this->request->getGet('end_date'));
        $sessionType  = strtoupper(trim((string) $this->request->getGet('session_type')));

        if ($classroomId !== null) UnitScopeService::assertClassroom($classroomId);
        if ($subjectId !== null) UnitScopeService::assertSubject($subjectId);

        $analytics = $this->attendanceService->getExecutiveAnalytics(
            (int) ($activePeriod['id'] ?? 0),
            $this->scopedUnits($unitId),
            $classroomId,
            $subjectId,
            $startDate,
            $endDate,
            $sessionType ?: null
        );

        $db   = Database::connect();
        $unit = $unitId > 0 ? $db->table('school_units')->where('id', $unitId)->get()->getRowArray() : null;

        return view('attendances/print_unit_report', array_merge($analytics, [
            'activePeriod' => $activePeriod,
            'unit'         => $unit,
            'startDate'    => $startDate,
            'endDate'      => $endDate,
        ]));
    }

    /**
     * Print Blank Attendance Roster Sheet for Offline Paper Use
     */
    public function printBlankSheet()
    {
        if (!has_permission('attendances.view')) {
            return redirect()->to('/dashboard')->with('error', 'Tidak ada hak akses mencetak lembar presensi.');
        }

        $classroomId = (int) $this->request->getGet('classroom_id');
        if ($classroomId <= 0) {
            return redirect()->to('/attendances')->with('error', 'Pilih Rombel / Kelas terlebih dahulu.');
        }
        UnitScopeService::assertClassroom($classroomId);

        $db        = Database::connect();
        $classroom = $db->table('classrooms c')
            ->select('c.*, su.name as unit_name, gl.name as grade_level_name')
            ->join('school_units su', 'su.id = c.unit_id')
            ->join('grade_levels gl', 'gl.id = c.grade_level_id')
            ->where('c.id', $classroomId)->get()->getRowArray();

        if (!$classroom) {
            return redirect()->to('/attendances')->with('error', 'Kelas tidak ditemukan.');
        }

        $students     = $this->attendanceService->getClassroomStudents($classroomId);
        $activePeriod = get_active_period();

        return view('attendances/print_blank_sheet', [
            'classroom'    => $classroom,
            'students'     => $students,
            'activePeriod' => $activePeriod,
        ]);
    }
}
