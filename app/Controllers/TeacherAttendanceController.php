<?php

namespace App\Controllers;

use App\Services\AttendanceService;
use App\Services\AuditService;
use App\Services\UnitScopeService;
use Config\Database;

/**
 * @deprecated This controller is a backward-compatibility stub.
 *
 * All teacher attendance functionality has been consolidated into
 * the Teaching Workspace (TeachingWorkspaceController). The old
 * `/portal/attendance/*` routes redirect to `/teaching/*` so that
 * any bookmarks or external links continue to work.
 *
 * Only the print methods remain fully functional because they are
 * still referenced by other admin attendance views (attendances/index,
 * attendances/show) and the legacy bridge view.
 */
class TeacherAttendanceController extends BaseController
{
    private AttendanceService $attendanceService;

    public function __construct()
    {
        $this->attendanceService = new AttendanceService();
    }

    // ----------------------------------------------------------------
    // Redirect stubs — old routes → new Teaching Workspace
    // ----------------------------------------------------------------

    /**
     * GET /portal/attendance → /teaching/today
     */
    public function index()
    {
        $date = trim((string) $this->request->getGet('date')) ?: date('Y-m-d');
        return redirect()->to(base_url('teaching/today?date=' . urlencode($date)))
            ->with('info', 'Halaman Absensi & Jurnal telah dipindahkan ke Ruang Mengajar (Teaching Workspace).');
    }

    /**
     * Backward-compatibility alias for legacy index.
     */
    public function indexLegacy()
    {
        return $this->index();
    }

    /**
     * GET /portal/attendance/record → /teaching/today
     */
    public function form($sessionId = null)
    {
        if (empty($sessionId)) {
            $date = trim((string) $this->request->getGet('date')) ?: date('Y-m-d');
            return redirect()->to(base_url('teaching/today?date=' . urlencode($date)))
                ->with('info', 'Pencatatan presensi baru kini melalui Ruang Mengajar.');
        }

        // For existing legacy sessions, show a bridge view
        $legacySession = \App\Services\TeachingWorkspaceService::findLegacyAttendanceSession((int) $sessionId);
        if ($legacySession) {
            return view('teaching/legacy_bridge', [
                'title'             => 'Data Presensi Lama',
                'breadcrumb_active' => 'Data Presensi Lama',
                'legacySession'     => $legacySession,
            ]);
        }

        return redirect()->to('/teaching/today')->with('error', 'Data presensi tidak ditemukan.');
    }

    /**
     * POST /portal/attendance/save → redirect to teaching workspace
     */
    public function save()
    {
        return redirect()->to(base_url('teaching/today'))
            ->with('info', 'Penyimpanan presensi kini melalui Ruang Mengajar. Gunakan "Mulai Pembelajaran" pada jadwal yang sesuai.');
    }

    /**
     * POST /portal/attendance/session/:id/delete → archive via legacy bridge
     */
    public function delete($id)
    {
        if (!has_permission('attendances.record') && !has_permission('attendances.admin')) {
            return redirect()->to('/teaching/today')->with('error', 'Akses ditolak.');
        }

        $db = Database::connect();
        $session = $db->table('attendance_sessions')
            ->where('id', (int) $id)
            ->where('deleted_at IS NULL')
            ->get()->getRowArray();

        if (!$session) {
            return redirect()->to('/teaching/today')->with('error', 'Sesi tidak ditemukan.');
        }

        if (strtoupper((string) $session['status']) === 'VERIFIED' || !empty($session['locked_at'])) {
            return redirect()->to('/teaching/today')->with('error', 'Sesi terverifikasi telah dikunci.');
        }

        $db->table('attendance_sessions')->where('id', (int) $id)->update([
            'source_key' => 'ARCHIVED:' . (int) $id . ':' . $session['source_key'],
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_by' => (int) session()->get('user_id'),
        ]);

        AuditService::log('attendance', 'ARCHIVE_SESSION', 'AttendanceSession', (int) $id, $session, null, 'Sesi presensi diarsipkan');
        return redirect()->to('/teaching/today')->with('success', 'Sesi dipindahkan ke arsip.');
    }

    // ----------------------------------------------------------------
    // Print methods — kept functional (still used by admin attendance
    // views and the legacy bridge view)
    // ----------------------------------------------------------------

    /**
     * GET /portal/attendance/session/:id/print
     */
    public function printJournal($id)
    {
        if (!has_permission('teacher_attendance.view') && !has_permission('attendances.view')) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        $details = $this->attendanceService->getSessionDetails((int) $id);
        if (!$details) {
            return redirect()->to('/teaching/today')->with('error', 'Sesi tidak ditemukan.');
        }

        $session = $details['session'];
        $executive = has_permission('attendances.view') && !is_guru() && !is_wali_kelas();
        if ($executive) {
            UnitScopeService::assertUnit((int) $session['unit_id']);
        } else {
            $teacherId = (int) session()->get('teacher_id');
            if ($teacherId <= 0) {
                $userId = (int) session()->get('user_id');
                if ($userId > 0) {
                    $user = Database::connect()->table('users')->select('teacher_id')->where('id', $userId)->get()->getRowArray();
                    $teacherId = (int) ($user['teacher_id'] ?? 0);
                }
            }
            if ($teacherId > 0 && !$this->attendanceService->teacherCanRecordType(
                $teacherId,
                (int) $session['academic_period_id'],
                (int) $session['classroom_id'],
                (string) $session['session_type'],
                !empty($session['subject_id']) ? (int) $session['subject_id'] : null,
                (string) $session['attendance_date'],
                false
            )) {
                return $this->response->setStatusCode(403)->setBody(
                    view('errors/html/error_403', ['message' => 'Dokumen tersebut berada di luar tanggung jawab Anda.'])
                );
            }
        }

        return view('teacher_portal/print_journal', $details);
    }

    /**
     * GET /portal/attendance/recap/print
     */
    public function printRecap()
    {
        if (!has_permission('teacher_attendance.view') && !has_permission('attendances.view')) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        $classroomId = (int) $this->request->getGet('classroom_id');
        $subjectId = (int) $this->request->getGet('subject_id');
        $period = get_active_period();
        if (!$period || $classroomId <= 0 || $subjectId <= 0) {
            return redirect()->to('/teaching/today')->with('error', 'Kelas dan mata pelajaran wajib dipilih.');
        }

        $executive = has_permission('attendances.view') && !is_guru() && !is_wali_kelas();
        if ($executive) {
            UnitScopeService::assertClassroom($classroomId);
        } else {
            $teacherId = (int) session()->get('teacher_id');
            if ($teacherId <= 0) {
                $userId = (int) session()->get('user_id');
                if ($userId > 0) {
                    $user = Database::connect()->table('users')->select('teacher_id')->where('id', $userId)->get()->getRowArray();
                    $teacherId = (int) ($user['teacher_id'] ?? 0);
                }
            }
            if ($teacherId > 0 && !$this->attendanceService->teacherHasActiveAssignment(
                $teacherId, (int) $period['id'], $classroomId, $subjectId, date('Y-m-d')
            )) {
                return $this->response->setStatusCode(403)->setBody(
                    view('errors/html/error_403', ['message' => 'Rekap tersebut berada di luar penugasan Anda.'])
                );
            }
        }

        try {
            return view('teacher_portal/print_recap', $this->attendanceService->calculateClassroomRecap(
                $classroomId, $subjectId, (int) $period['id']
            ) + ['activePeriod' => $period]);
        } catch (\Throwable $e) {
            return redirect()->to('/teaching/today')->with('error', $e->getMessage());
        }
    }

    // ----------------------------------------------------------------
    // Private helpers (removed — were only used by deleted methods)
    // ----------------------------------------------------------------
}
