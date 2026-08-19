<?php

namespace App\Controllers;

use App\Services\AttendanceService;
use App\Services\AuditService;
use App\Services\PortalUnitScopeService;
use App\Services\UnitScopeService;
use Config\Database;

class TeacherAttendanceController extends BaseController
{
    private AttendanceService $attendanceService;

    public function __construct()
    {
        $this->attendanceService = new AttendanceService();
    }

    private function resolveTeacherId(): int
    {
        $teacherId = (int) session()->get('teacher_id');
        $userId = (int) session()->get('user_id');
        if ($teacherId > 0 || $userId <= 0) {
            return $teacherId;
        }
        $user = Database::connect()->table('users')->select('teacher_id')->where('id', $userId)->get()->getRowArray();
        $teacherId = (int) ($user['teacher_id'] ?? 0);
        if ($teacherId > 0) {
            session()->set('teacher_id', $teacherId);
        }
        return $teacherId;
    }

    private function isAttendanceAdmin(): bool
    {
        return has_permission('attendances.admin');
    }

    private function forbidden(string $message)
    {
        return $this->response->setStatusCode(403)->setBody(view('errors/html/error_403', ['message' => $message]));
    }

    private function validDate(string $date, array $period): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $errors = \DateTimeImmutable::getLastErrors();
        return $parsed !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $date >= (string) ($period['start_date'] ?? '')
            && $date <= (string) ($period['end_date'] ?? '');
    }

    public function index()
    {
        // Phase 5 consolidation: redirect to new Teaching Workspace
        $date = trim((string) $this->request->getGet('date')) ?: date('Y-m-d');
        $teacherId = $this->resolveTeacherId();
        $url = base_url('teaching/today?date=' . urlencode($date));
        if ($teacherId > 0) {
            $url .= '&teacher_id=' . $teacherId;
        }
        return redirect()->to($url)->with('info', 'Halaman Absensi & Jurnal telah dipindahkan ke Ruang Mengajar (Teaching Workspace). Semua data presensi sebelumnya tetap tersimpan dan dapat diakses dari sana.');
    }

    /**
     * @deprecated Use TeachingWorkspaceController::initSession() or ::session() instead.
     */
    public function indexLegacy()
    {
        if (!has_permission('teacher_attendance.view') && !has_permission('attendances.record')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki akses ke Absensi & Jurnal.');
        }
        $teacherId = $this->resolveTeacherId();
        $period = get_active_period();
        if (!$period) {
            return redirect()->to('/dashboard')->with('error', 'Tidak ada periode akademik aktif.');
        }
        if ($teacherId <= 0 && !$this->isAttendanceAdmin()) {
            return redirect()->to('/dashboard')->with('error', 'Akun belum ditautkan ke profil guru.');
        }

        $date = trim((string) $this->request->getGet('date')) ?: date('Y-m-d');
        if (!$this->validDate($date, $period)) {
            $date = min(max(date('Y-m-d'), (string) $period['start_date']), (string) $period['end_date']);
        }
        $unitScope = PortalUnitScopeService::resolve((string) $this->request->getGet('unit_scope'), $teacherId);
        $unitId = ($unitScope['selected'] ?? 'all') !== 'all' ? (int) $unitScope['selected'] : null;
        $workspace = $this->attendanceService->getDailyWorkspace($teacherId, (int) $period['id'], $date, $unitId);

        $db = Database::connect();
        $recent = $db->table('attendance_sessions ats')
            ->select("ats.*,c.name classroom_name,COALESCE(s.name,ats.topic,'Kegiatan Kelas') subject_name,COALESCE(s.code,ats.routine_code,'KELAS') subject_code,su.name unit_name")
            ->join('classrooms c', 'c.id=ats.classroom_id')
            ->join('subjects s', 's.id=ats.subject_id', 'left')
            ->join('school_units su', 'su.id=ats.unit_id')
            ->where('ats.academic_period_id', (int) $period['id'])->where('ats.deleted_at IS NULL');
        if (!$this->isAttendanceAdmin()) {
            $recent->groupStart()->where('ats.teacher_id', $teacherId)
                ->orWhereIn('ats.classroom_id', static function ($builder) use ($teacherId) {
                    return $builder->select('id')->from('classrooms')->where('homeroom_teacher_id', $teacherId)->where('deleted_at IS NULL');
                })->groupEnd();
        }
        if ($unitId) {
            $recent->where('ats.unit_id', $unitId);
        }
        $recentSessions = $recent->orderBy('ats.attendance_date', 'DESC')->orderBy('ats.id', 'DESC')->limit(20)->get()->getResultArray();

        return view('teacher_portal/attendance_index', [
            'title' => 'Absensi & Jurnal', 'breadcrumb_active' => 'Absensi & Jurnal',
            'activePeriod' => $period, 'unitScope' => $unitScope, 'selectedDate' => $date,
            'workspace' => $workspace, 'recentSessions' => $recentSessions,
        ]);
    }

    public function form($sessionId = null)
    {
        // Phase 5: redirect new attendance creation to Teaching Workspace
        if (empty($sessionId)) {
            $date = trim((string) $this->request->getGet('date')) ?: date('Y-m-d');
            return redirect()->to(base_url('teaching/today?date=' . urlencode($date)))
                ->with('info', 'Pencatatan presensi baru kini melalui Ruang Mengajar. Klik \"Mulai Pembelajaran\" pada jadwal yang sesuai.');
        }

        // For existing legacy sessions, show a bridge view that links to new system
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
     * @deprecated Internal use — legacy form handler kept for backward compat.
     */
    public function formLegacy($sessionId = null)
    {
        if (!has_permission('attendances.record') && !has_permission('teacher_attendance.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak menginput presensi.');
        }
        $teacherId = $this->resolveTeacherId();
        $period = get_active_period();
        if (!$period || ($teacherId <= 0 && !$this->isAttendanceAdmin())) {
            return redirect()->to('/dashboard')->with('error', 'Periode aktif atau profil guru belum tersedia.');
        }

        $db = Database::connect();
        $session = null;
        $type = strtoupper(trim((string) $this->request->getGet('type')) ?: 'SUBJECT');
        $date = trim((string) $this->request->getGet('date')) ?: date('Y-m-d');
        $classroomId = (int) $this->request->getGet('classroom_id');
        $subjectId = (int) $this->request->getGet('subject_id');
        $scheduleEntryId = (int) $this->request->getGet('schedule_entry_id');
        $meeting = max(1, (int) $this->request->getGet('meeting_number'));
        $details = null;

        if ((int) $sessionId > 0) {
            $details = $this->attendanceService->getSessionDetails((int) $sessionId);
            if (!$details) {
                return redirect()->to('/portal/attendance')->with('error', 'Sesi presensi tidak ditemukan.');
            }
            $session = $details['session'];
            $type = (string) ($session['session_type'] ?? 'SUBJECT');
            $date = (string) $session['attendance_date'];
            $classroomId = (int) $session['classroom_id'];
            $subjectId = (int) ($session['subject_id'] ?? 0);
            $scheduleEntryId = (int) ($session['schedule_entry_id'] ?? 0);
            $meeting = (int) $session['meeting_number'];
            if (!$this->attendanceService->teacherCanRecordType($teacherId, (int) $period['id'], $classroomId, $type, $subjectId ?: null, $date, $this->isAttendanceAdmin())) {
                return $this->forbidden('Sesi tersebut berada di luar tanggung jawab Anda.');
            }
        } elseif (!$this->validDate($date, $period)) {
            return redirect()->to('/portal/attendance')->with('error', 'Tanggal berada di luar periode aktif.');
        }

        if (!$session && $scheduleEntryId > 0) {
            $dayNumber = (int) (new \DateTimeImmutable($date))->format('N');
            $entry = $db->table('schedule_entries se')
                ->select('se.id,se.classroom_id,se.subject_id,sds.start_time,sds.end_time')
                ->join('schedule_versions sv', 'sv.id=se.schedule_version_id')
                ->join('schedule_day_slots sds', 'sds.id=se.day_slot_id')
                ->join('schedule_days sd', 'sd.id=sds.day_id')
                ->where('se.id', $scheduleEntryId)->where('sv.academic_period_id', (int) $period['id'])
                ->whereIn('sv.workflow_status', ['APPROVED', 'LOCKED'])->where('sd.day_of_week', $dayNumber)
                ->get()->getRowArray();
            if (!$entry) {
                return redirect()->to('/portal/attendance?date=' . urlencode($date))->with('error', 'Slot jadwal tidak valid untuk tanggal tersebut.');
            }
            $type = 'SUBJECT';
            $classroomId = (int) $entry['classroom_id'];
            $subjectId = (int) $entry['subject_id'];
        }

        if (!in_array($type, AttendanceService::SESSION_TYPES, true)) {
            return redirect()->to('/portal/attendance')->with('error', 'Jenis sesi tidak valid.');
        }
        if ($classroomId > 0 && !$this->attendanceService->teacherCanRecordType($teacherId, (int) $period['id'], $classroomId, $type, $subjectId ?: null, $date, $this->isAttendanceAdmin())) {
            return $this->forbidden('Kelas atau kegiatan tersebut berada di luar tanggung jawab Anda.');
        }
        if (!$session && $type === 'SUBJECT' && $classroomId > 0 && $scheduleEntryId <= 0 && !$this->isAttendanceAdmin()) {
            $contextClass = $db->table('classrooms')->select('unit_id')->where('id', $classroomId)->get()->getRowArray();
            $settings = $contextClass ? $this->attendanceService->getOperatingSettings((int) $period['id'], (int) $contextClass['unit_id']) : [];
            if (empty($settings['allow_off_schedule_subject'])) {
                return redirect()->to('/portal/attendance?date=' . urlencode($date))->with('error', 'Absensi mapel harus dimulai dari jadwal resmi hari tersebut.');
            }
        }

        $sourceKey = $classroomId > 0
            ? $this->attendanceService->buildSourceKey($type, (int) $period['id'], $classroomId, $date, $subjectId ?: null, $scheduleEntryId ?: null, $meeting)
            : '';
        if (!$session && $sourceKey !== '') {
            $existing = $db->table('attendance_sessions')->where('source_key', $sourceKey)->where('deleted_at IS NULL')->get()->getRowArray();
            if ($existing) {
                return redirect()->to('/portal/attendance/session/' . $existing['id']);
            }
        }

        $roster = $details['roster'] ?? [];
        if (!$session && $classroomId > 0) {
            foreach ($this->attendanceService->getSuggestedRoster($classroomId, $date, $type) as $student) {
                $roster[] = [
                    'student_id' => (int) $student['id'], 'student_number' => $student['student_number'],
                    'full_name' => $student['full_name'], 'status' => $student['suggested_status'] ?? 'HADIR',
                    'notes' => $student['suggested_notes'] ?? '', 'arrival_time' => null, 'late_minutes' => 0,
                    'source_session_id' => $student['source_session_id'] ?? null,
                ];
            }
        }
        $counts = array_fill_keys(AttendanceService::STUDENT_STATUSES, 0);
        foreach ($roster as $row) {
            $status = strtoupper((string) ($row['status'] ?? 'HADIR'));
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }
        $classroom = $classroomId ? $db->table('classrooms c')->select('c.*,su.name unit_name')->join('school_units su', 'su.id=c.unit_id')->where('c.id', $classroomId)->get()->getRowArray() : null;
        $subject = $subjectId ? $db->table('subjects')->where('id', $subjectId)->get()->getRowArray() : null;
        $assignments = $this->attendanceService->getTeacherAssignments($teacherId, (int) $period['id']);
        $homerooms = $db->table('classrooms c')->select('c.*,su.name unit_name')->join('school_units su', 'su.id=c.unit_id')
            ->where('c.academic_period_id', (int) $period['id'])->where('c.homeroom_teacher_id', $teacherId)
            ->where('c.deleted_at IS NULL')->get()->getResultArray();

        return view('teacher_portal/attendance_form', [
            'title' => $session ? 'Perbarui Absensi & Jurnal' : 'Catat ' . AttendanceService::sessionTypeLabel($type),
            'breadcrumb_active' => 'Absensi & Jurnal', 'activePeriod' => $period,
            'session' => $session, 'sessionType' => $type, 'sessionTypeLabel' => AttendanceService::sessionTypeLabel($type),
            'sourceKey' => $sourceKey, 'scheduleEntryId' => $scheduleEntryId,
            'roster' => $roster, 'counts' => $counts, 'classroom' => $classroom, 'subject' => $subject,
            'classroomId' => $classroomId, 'subjectId' => $subjectId, 'date' => $date, 'meetingNum' => $meeting,
            'teachingAssignments' => $assignments['teaching_assignments'], 'homerooms' => $homerooms,
            'isLocked' => $session && (strtoupper((string) $session['status']) === 'VERIFIED' || !empty($session['locked_at'])),
        ]);
    }

    public function save()
    {
        if (!has_permission('attendances.record') && !has_permission('teacher_attendance.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak menginput presensi.');
        }
        $teacherId = $this->resolveTeacherId();
        $period = get_active_period();
        if (!$period) {
            return redirect()->to('/dashboard')->with('error', 'Tidak ada periode akademik aktif.');
        }
        $sessionId = (int) $this->request->getPost('session_id');
        $classroomId = (int) $this->request->getPost('classroom_id');
        $subjectId = (int) $this->request->getPost('subject_id');
        $type = strtoupper((string) $this->request->getPost('session_type'));
        $date = trim((string) $this->request->getPost('attendance_date'));
        $meeting = max(1, (int) $this->request->getPost('meeting_number'));
        $scheduleEntryId = (int) $this->request->getPost('schedule_entry_id');
        if (!$this->validDate($date, $period) || !in_array($type, AttendanceService::SESSION_TYPES, true) || $classroomId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Konteks kelas, jenis sesi, atau tanggal tidak valid.');
        }
        $db = Database::connect();
        $existing = $sessionId ? $db->table('attendance_sessions')->where('id', $sessionId)->where('deleted_at IS NULL')->get()->getRowArray() : null;
        if ($sessionId && !$existing) {
            return redirect()->to('/portal/attendance')->with('error', 'Sesi tidak ditemukan.');
        }
        if ($existing) {
            $classroomId = (int) $existing['classroom_id'];
            $subjectId = (int) ($existing['subject_id'] ?? 0);
            $type = (string) $existing['session_type'];
            $scheduleEntryId = (int) ($existing['schedule_entry_id'] ?? 0);
            $date = (string) $existing['attendance_date'];
            $meeting = (int) $existing['meeting_number'];
        }
        if (!$this->attendanceService->teacherCanRecordType($teacherId, (int) $period['id'], $classroomId, $type, $subjectId ?: null, $date, $this->isAttendanceAdmin())) {
            return $this->forbidden('Anda tidak berwenang mencatat sesi ini.');
        }
        $classroom = $db->table('classrooms')->where('id', $classroomId)->where('deleted_at IS NULL')->get()->getRowArray();
        if (!$classroom) {
            return redirect()->back()->withInput()->with('error', 'Kelas tidak valid.');
        }
        if (!$existing && $type === 'SUBJECT' && $scheduleEntryId <= 0 && !$this->isAttendanceAdmin() && empty($this->attendanceService->getOperatingSettings((int) $period['id'], (int) $classroom['unit_id'])['allow_off_schedule_subject'])) {
            return redirect()->to('/portal/attendance?date=' . urlencode($date))->with('error', 'Input mapel di luar jadwal dinonaktifkan.');
        }
        $workflow = $this->request->getPost('submit_action') === 'draft' ? 'DRAFT' : 'SUBMITTED';
        $typeLabel = AttendanceService::sessionTypeLabel($type);
        $sessionTimes = ['start_time'=>null,'end_time'=>null];
        if ($scheduleEntryId > 0) {
            $slot = $db->table('schedule_entries se')->select('sds.start_time,sds.end_time')->join('schedule_day_slots sds','sds.id=se.day_slot_id')->where('se.id',$scheduleEntryId)->get()->getRowArray();
            if ($slot) $sessionTimes=$slot;
        } elseif ($type === 'MORNING_ASSEMBLY') {
            $sessionTimes=$this->attendanceService->getSessionWindow((int)$period['id'],(int)$classroom['unit_id'],$date,$type);
        } elseif ($type === 'AFTERNOON_ASSEMBLY') {
            $sessionTimes=$this->attendanceService->getSessionWindow((int)$period['id'],(int)$classroom['unit_id'],$date,$type);
        }
        $sessionData = [
            'id' => $sessionId ?: null, 'unit_id' => (int) $classroom['unit_id'],
            'academic_period_id' => (int) $period['id'], 'classroom_id' => $classroomId,
            'subject_id' => $subjectId ?: null, 'teacher_id' => $teacherId ?: ($existing['teacher_id'] ?? null),
            'schedule_entry_id' => $scheduleEntryId ?: null, 'session_type' => $type,
            'routine_code' => $type === 'MORNING_ASSEMBLY' ? 'APEL_PAGI' : ($type === 'AFTERNOON_ASSEMBLY' ? 'APEL_SIANG' : ($type === 'CLASSROOM' ? 'KELAS' : null)),
            'source_type' => $scheduleEntryId ? 'SCHEDULE' : ($type === 'SUBJECT' ? 'MANUAL_ASSIGNMENT' : 'HOMEROOM'),
            'source_key' => $existing['source_key'] ?? $this->attendanceService->buildSourceKey($type, (int) $period['id'], $classroomId, $date, $subjectId ?: null, $scheduleEntryId ?: null, $meeting),
            'attendance_date' => $date, 'meeting_number' => $meeting,
            'start_time' => $sessionTimes['start_time'], 'end_time' => $sessionTimes['end_time'],
            'topic' => trim((string) $this->request->getPost('topic')) ?: $typeLabel,
            'teaching_summary' => trim((string) $this->request->getPost('teaching_summary')),
            'learning_objectives' => trim((string) $this->request->getPost('learning_objectives')),
            'learning_activity' => trim((string) $this->request->getPost('learning_activity')),
            'assessment_summary' => trim((string) $this->request->getPost('assessment_summary')),
            'follow_up' => trim((string) $this->request->getPost('follow_up')),
            'status' => $workflow, 'revision_number' => (int) $this->request->getPost('revision_number'),
        ];
        foreach ($sessionData as $value) {
            if (is_string($value) && mb_strlen($value) > 10000) {
                return redirect()->back()->withInput()->with('error', 'Isi jurnal melebihi batas yang diizinkan.');
            }
        }
        $rawRoster = $this->request->getPost('roster');
        if (!is_array($rawRoster)) {
            return redirect()->back()->withInput()->with('error', 'Daftar siswa tidak valid.');
        }
        $records = [];
        foreach ($rawRoster as $studentId => $value) {
            $records[] = [
                'student_id' => (int) $studentId, 'status' => strtoupper((string) ($value['status'] ?? 'HADIR')),
                'notes' => trim((string) ($value['notes'] ?? '')), 'arrival_time' => trim((string) ($value['arrival_time'] ?? '')),
                'late_minutes' => (int) ($value['late_minutes'] ?? 0),
                'source_session_id' => !empty($value['source_session_id']) ? (int) $value['source_session_id'] : null,
            ];
        }
        try {
            $saved = $this->attendanceService->saveSession($sessionData, $records, (int) session()->get('user_id'));
            AuditService::log('attendance', $sessionId ? 'UPDATE_SESSION' : 'CREATE_SESSION', 'AttendanceSession', (int)$saved['session']['id'], $existing, ['session_type'=>$type,'status'=>$workflow,'attendance_date'=>$date,'classroom_id'=>$classroomId,'subject_id'=>$subjectId ?: null,'roster_count'=>count($records)], 'Presensi dan jurnal disimpan');
            $message = $workflow === 'DRAFT' ? 'Draft presensi tersimpan.' : 'Presensi dan jurnal berhasil dikirim.';
            return redirect()->to('/portal/attendance/session/' . $saved['session']['id'])->with('success', $message);
        } catch (\Throwable $e) {
            log_message('error', 'Attendance save failed: {message}', ['message' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function delete($id)
    {
        if (!has_permission('attendances.record') && !has_permission('attendances.admin')) {
            return redirect()->to('/portal/attendance')->with('error', 'Akses ditolak.');
        }
        $db = Database::connect();
        $session = $db->table('attendance_sessions')->where('id', (int) $id)->where('deleted_at IS NULL')->get()->getRowArray();
        if (!$session) {
            return redirect()->to('/portal/attendance')->with('error', 'Sesi tidak ditemukan.');
        }
        if (!$this->attendanceService->teacherCanRecordType($this->resolveTeacherId(), (int) $session['academic_period_id'], (int) $session['classroom_id'], (string) $session['session_type'], !empty($session['subject_id']) ? (int) $session['subject_id'] : null, (string) $session['attendance_date'], $this->isAttendanceAdmin())) {
            return $this->forbidden('Anda tidak dapat menghapus sesi tersebut.');
        }
        if (strtoupper((string) $session['status']) === 'VERIFIED' || !empty($session['locked_at'])) {
            return $this->forbidden('Sesi terverifikasi telah dikunci dan tidak dapat dihapus.');
        }
        $db->table('attendance_sessions')->where('id', (int) $id)->update(['source_key'=>'ARCHIVED:' . (int)$id . ':' . $session['source_key'], 'deleted_at' => date('Y-m-d H:i:s'), 'updated_by' => (int) session()->get('user_id')]);
        AuditService::log('attendance','ARCHIVE_SESSION','AttendanceSession',(int)$id,$session,null,'Sesi presensi diarsipkan');
        return redirect()->to('/portal/attendance')->with('success', 'Sesi dipindahkan ke arsip.');
    }

    public function printJournal($id)
    {
        if (!has_permission('teacher_attendance.view') && !has_permission('attendances.view')) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }
        $details = $this->attendanceService->getSessionDetails((int) $id);
        if (!$details) {
            return redirect()->to('/portal/attendance')->with('error', 'Sesi tidak ditemukan.');
        }
        $session = $details['session'];
        $executive = has_permission('attendances.view') && !is_guru() && !is_wali_kelas();
        if ($executive) {
            UnitScopeService::assertUnit((int) $session['unit_id']);
        } elseif (!$this->attendanceService->teacherCanRecordType($this->resolveTeacherId(), (int) $session['academic_period_id'], (int) $session['classroom_id'], (string) $session['session_type'], !empty($session['subject_id']) ? (int) $session['subject_id'] : null, (string) $session['attendance_date'], false)) {
            return $this->forbidden('Dokumen tersebut berada di luar tanggung jawab Anda.');
        }
        return view('teacher_portal/print_journal', $details);
    }

    public function printRecap()
    {
        if (!has_permission('teacher_attendance.view') && !has_permission('attendances.view')) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }
        $classroomId = (int) $this->request->getGet('classroom_id');
        $subjectId = (int) $this->request->getGet('subject_id');
        $period = get_active_period();
        if (!$period || $classroomId <= 0 || $subjectId <= 0) {
            return redirect()->to('/portal/attendance')->with('error', 'Kelas dan mata pelajaran wajib dipilih.');
        }
        $executive = has_permission('attendances.view') && !is_guru() && !is_wali_kelas();
        if ($executive) {
            UnitScopeService::assertClassroom($classroomId);
        } elseif (!$this->attendanceService->teacherHasActiveAssignment($this->resolveTeacherId(), (int) $period['id'], $classroomId, $subjectId, date('Y-m-d'))) {
            return $this->forbidden('Rekap tersebut berada di luar penugasan Anda.');
        }
        try {
            return view('teacher_portal/print_recap', $this->attendanceService->calculateClassroomRecap($classroomId, $subjectId, (int) $period['id']) + ['activePeriod' => $period]);
        } catch (\Throwable $e) {
            return redirect()->to('/portal/attendance')->with('error', $e->getMessage());
        }
    }
}
