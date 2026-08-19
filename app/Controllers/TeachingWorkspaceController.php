<?php

namespace App\Controllers;

use App\Exceptions\AuthorizationException;
use App\Services\TeachingWorkspaceService;
use App\Services\UnitScopeService;
use Config\Database;
use Exception;

class TeachingWorkspaceController extends BaseController
{
    private TeachingWorkspaceService $teachingService;

    public function __construct()
    {
        $this->teachingService = new TeachingWorkspaceService();
    }

    /**
     * Today Workspace Dashboard.
     */
    public function today()
    {
        $db = Database::connect();
        $userId = (int) session()->get('user_id');
        $activeUnitId = (int) session()->get('active_unit_id');

        $activePeriod = get_active_period();
        $periodId = $activePeriod ? (int) $activePeriod['id'] : 0;
        if ($periodId <= 0) {
            $periodRow = $db->table('academic_periods')->where('is_active', 1)->get()->getRowArray();
            if ($periodRow) {
                $periodId = (int) $periodRow['id'];
            }
        }

        $date = $this->request->getGet('date') ?: date('Y-m-d');

        // Resolve teacher id from logged in user (users.teacher_id or session)
        $teacherId = $this->resolveOwnTeacherId();
        $teacher = $teacherId > 0
            ? $db->table('teachers')->where('id', $teacherId)->where('is_active', 1)->get()->getRowArray()
            : null;

        // If management role (superadmin, wakasek, admin_smp/sma), allow switching/selecting any teacher
        $teachersList = [];
        if ($this->isManagementRole()) {
            $tQuery = $db->table('teachers')->where('is_active', 1);
            if ($activeUnitId > 0) {
                $tQuery->groupStart()
                    ->where('primary_unit_id', $activeUnitId)
                    ->orWhere('primary_unit_id IS NULL')
                    ->groupEnd();
            }
            $teachersList = $tQuery->orderBy('full_name', 'ASC')->get()->getResultArray();

            if (empty($teachersList)) {
                $teachersList = $db->table('teachers')->where('is_active', 1)->orderBy('full_name', 'ASC')->get()->getResultArray();
            }

            $selectedTeacherId = (int) $this->request->getGet('teacher_id');
            if ($selectedTeacherId > 0) {
                $teacherId = $selectedTeacherId;
            } elseif ($teacherId <= 0 && !empty($teachersList)) {
                $teacherId = (int) $teachersList[0]['id'];
            }

            if ($teacherId > 0) {
                $teacher = $db->table('teachers')->where('id', $teacherId)->get()->getRowArray();
            }
        }

        $workspace = $this->teachingService->getTodayWorkspace($teacherId, $periodId, $date, $activeUnitId);

        // Pre-fetch available lesson plans per subject for plan linking on the dashboard.
        $subjectIds = array_unique(array_map(static fn (array $l): int => (int) $l['subject_id'], $workspace['lessons']));
        $availablePlans = [];
        if ($subjectIds !== []) {
            $planRows = $db->table('lesson_plans')
                ->where('unit_id', $activeUnitId)
                ->whereIn('subject_id', $subjectIds)
                ->whereIn('status', ['READY', 'IN_PROGRESS'])
                ->orderBy('subject_id', 'ASC')
                ->orderBy('session_number', 'ASC')
                ->get()->getResultArray();
            foreach ($planRows as $plan) {
                $key = (int) $plan['subject_id'];
                $availablePlans[$key][] = $plan;
            }
        }

        return view('teaching/today', [
            'title'             => 'Ruang Mengajar (Today Workspace)',
            'breadcrumb_active' => 'Today Workspace',
            'date'              => $date,
            'workspace'         => $workspace,
            'teacher'           => $teacher,
            'teachersList'      => $teachersList,
            'currentTeacherId'  => $teacherId,
            'availablePlans'    => $availablePlans,
        ]);
    }

    /**
     * Initializes or enters a session from a schedule card.
     */
    public function initSession()
    {
        $userId = (int) session()->get('user_id');
        $activeUnitId = (int) session()->get('active_unit_id');
        $activePeriod = get_active_period();
        $periodId = $activePeriod ? (int) $activePeriod['id'] : 0;

        $teacherId = (int) $this->request->getPost('teacher_id');
        $classroomId = (int) $this->request->getPost('classroom_id');
        $subjectId = (int) $this->request->getPost('subject_id');

        try {
            UnitScopeService::assertUnit($activeUnitId);
            UnitScopeService::assertClassroom($classroomId);
            UnitScopeService::assertSubjectInUnit($subjectId, $activeUnitId);
            UnitScopeService::assertTeacher($teacherId);

            // A teacher-level account may only initialize their own sessions.
            if (!$this->isManagementRole()) {
                $ownTeacherId = $this->resolveOwnTeacherId();
                if ($ownTeacherId <= 0 || $teacherId !== $ownTeacherId) {
                    throw new AuthorizationException('Anda hanya dapat menyiapkan sesi mengajar untuk diri Anda sendiri.');
                }
            }

            $payload = [
                'academic_period_id' => $periodId,
                'unit_id'            => $activeUnitId,
                'teacher_id'         => $teacherId,
                'classroom_id'       => $classroomId,
                'subject_id'         => $subjectId,
                'session_date'       => $this->request->getPost('session_date') ?: date('Y-m-d'),
                'schedule_entry_id'  => $this->request->getPost('schedule_entry_id') ?: null,
                'lesson_plan_id'     => $this->request->getPost('lesson_plan_id') ?: null,
                'start_time'         => $this->request->getPost('start_time') ?: null,
                'end_time'           => $this->request->getPost('end_time') ?: null,
                'jp_count'           => (int) ($this->request->getPost('jp_count') ?: 2),
                'topic'              => $this->request->getPost('topic') ?: null,
            ];

            $session = $this->teachingService->initializeSession($payload, $userId);
            return redirect()->to(base_url('teaching/session/' . $session['uuid']));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memulai sesi: ' . $e->getMessage());
        }
    }

    /**
     * Teaching Mode Screen (In-class distraction-free interface).
     */
    public function session(string $uuid)
    {
        try {
            $data = $this->teachingService->getSessionDetail($uuid);
            $this->assertSessionAccess($data['session']);

            // Fetch available lesson plans for switching
            $db = Database::connect();
            $planQuery = $db->table('lesson_plans')
                ->where('subject_id', (int) $data['session']['subject_id'])
                ->where('unit_id', (int) $data['session']['unit_id'])
                ->whereIn('status', ['READY', 'IN_PROGRESS', 'COMPLETED']);
            if ((int) ($data['session']['grade_level_id'] ?? 0) > 0) {
                $planQuery->where('grade_level_id', (int) $data['session']['grade_level_id']);
            }
            $availablePlans = $planQuery->orderBy('session_number', 'ASC')->get()->getResultArray();

            return view('teaching/session', array_merge($data, [
                'title'             => 'Teaching Mode: ' . ($data['session']['subject_name'] ?? 'Kelas'),
                'breadcrumb_active' => 'Teaching Mode',
                'availablePlans'    => $availablePlans,
            ]));
        } catch (Exception $e) {
            if ($e instanceof AuthorizationException) {
                return redirect()->to(base_url('teaching/today'))->with('error', $e->getMessage());
            }
            return redirect()->to(base_url('teaching/today'))->with('error', $e->getMessage());
        }
    }

    /**
     * Starts the session (PLANNED -> IN_PROGRESS).
     */
    public function startSession(string $uuid)
    {
        $userId = (int) session()->get('user_id');

        try {
            $session = $this->teachingService->getSessionByUuid($uuid);
            $this->assertSessionAccess($session);

            $session = $this->teachingService->startSession($uuid, $userId);
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'success', 'session' => $session]);
            }
            return redirect()->back()->with('success', 'Sesi pembelajaran telah dimulai. Selamat mengajar!');
        } catch (Exception $e) {
            if ($this->request->isAJAX()) {
                return $this->jsonError($e);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Completes the teaching session (IN_PROGRESS -> COMPLETED).
     */
    public function completeSession(string $uuid)
    {
        $userId = (int) session()->get('user_id');
        $data = [
            'deviation_notes'            => $this->request->getPost('deviation_notes'),
            'learning_objective_summary' => $this->request->getPost('learning_objective_summary'),
        ];

        try {
            $session = $this->teachingService->getSessionByUuid($uuid);
            $this->assertSessionAccess($session);

            $session = $this->teachingService->completeSession($uuid, $data, $userId);
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status'   => 'success',
                    'session'  => $session,
                    'redirect' => base_url('teaching/session/' . $uuid . '/reflect'),
                ]);
            }
            return redirect()->to(base_url('teaching/session/' . $uuid . '/reflect'))
                ->with('success', 'Sesi pembelajaran selesai! Silakan lengkapi jurnal refleksi.');
        } catch (Exception $e) {
            if ($this->request->isAJAX()) {
                return $this->jsonError($e);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Post-session Reflection Form.
     */
    public function reflectView(string $uuid)
    {
        try {
            $data = $this->teachingService->getSessionDetail($uuid);
            $this->assertSessionAccess($data['session']);

            return view('teaching/reflect', [
                'title'             => 'Refleksi Sesi Pembelajaran',
                'breadcrumb_active' => 'Refleksi Pembelajaran',
                'session'           => $data['session'],
                'reflection'        => $data['reflection'],
                'observations'      => $data['observations'],
                'activities'        => $data['activities'],
            ]);
        } catch (Exception $e) {
            if ($e instanceof AuthorizationException) {
                return redirect()->to(base_url('teaching/today'))->with('error', $e->getMessage());
            }
            return redirect()->to(base_url('teaching/today'))->with('error', $e->getMessage());
        }
    }

    /**
     * Saves post-session reflection (COMPLETED -> REFLECTED).
     */
    public function saveReflection(string $uuid)
    {
        $userId = (int) session()->get('user_id');
        $data = [
            'what_went_well'        => $this->request->getPost('what_went_well'),
            'challenges'            => $this->request->getPost('challenges'),
            'student_engagement'    => $this->request->getPost('student_engagement'),
            'objective_achievement' => $this->request->getPost('objective_achievement'),
            'tp_coverage_notes'     => $this->request->getPost('tp_coverage_notes'),
            'follow_up_plan'        => $this->request->getPost('follow_up_plan'),
            'next_session_notes'    => $this->request->getPost('next_session_notes'),
            'self_rating'           => $this->request->getPost('self_rating'),
        ];

        try {
            $session = $this->teachingService->getSessionByUuid($uuid);
            $this->assertSessionAccess($session);

            $this->teachingService->saveReflection($uuid, $data, $userId);
            return redirect()->to(base_url('teaching/today'))->with('success', 'Refleksi pembelajaran berhasil disimpan. Sesi mengajar telah tercatat sempurna!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Toggles activity completion in checklist (AJAX).
     */
    public function toggleActivity(string $uuid)
    {
        $userId = (int) session()->get('user_id');
        $activityUuid = $this->request->getPost('activity_uuid');
        $isCompleted = (bool) $this->request->getPost('is_completed');
        $actualMinutes = $this->request->getPost('actual_minutes') ? (int) $this->request->getPost('actual_minutes') : null;
        $notes = $this->request->getPost('notes');

        try {
            $session = $this->teachingService->getSessionByUuid($uuid);
            $this->assertSessionAccess($session);

            $updated = $this->teachingService->toggleActivity($uuid, $activityUuid, $isCompleted, $actualMinutes, $notes, $userId);
            return $this->response->setJSON(['status' => 'success', 'activity' => $updated]);
        } catch (Exception $e) {
            return $this->jsonError($e);
        }
    }

    /**
     * Adds formative observation / misconception (AJAX).
     */
    public function addObservation(string $uuid)
    {
        $userId = (int) session()->get('user_id');
        $data = [
            'student_id'          => $this->request->getPost('student_id'),
            'observation_type'    => $this->request->getPost('observation_type'),
            'rating'              => $this->request->getPost('rating'),
            'notes'               => $this->request->getPost('notes'),
            'misconception_found' => $this->request->getPost('misconception_found'),
            'misconception_detail'=> $this->request->getPost('misconception_detail'),
            'follow_up_needed'    => $this->request->getPost('follow_up_needed'),
        ];

        try {
            $session = $this->teachingService->getSessionByUuid($uuid);
            $this->assertSessionAccess($session);

            $obs = $this->teachingService->addObservation($uuid, $data, $userId);
            return $this->response->setJSON(['status' => 'success', 'observation' => $obs]);
        } catch (Exception $e) {
            return $this->jsonError($e);
        }
    }

    /**
     * Deletes formative observation (AJAX).
     */
    public function deleteObservation(string $uuid, string $obsUuid)
    {
        $userId = (int) session()->get('user_id');

        try {
            $session = $this->teachingService->getSessionByUuid($uuid);
            $this->assertSessionAccess($session);

            $success = $this->teachingService->deleteObservation($uuid, $obsUuid, $userId);
            return $this->response->setJSON(['status' => $success ? 'success' : 'error']);
        } catch (Exception $e) {
            return $this->jsonError($e);
        }
    }

    /**
     * Saves quick student attendance from Teaching Mode (AJAX).
     */
    public function quickAttendance(string $uuid)
    {
        $userId = (int) session()->get('user_id');

        // Support both JSON body and form-encoded POST
        $attendanceRaw = $this->request->getPost('attendance');
        if (!is_array($attendanceRaw)) {
            $json = $this->request->getJSON(true);
            $attendanceRaw = $json['attendance'] ?? null;
        }

        if (!is_array($attendanceRaw)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Format presensi tidak valid.']);
        }

        try {
            $session = $this->teachingService->getSessionByUuid($uuid);
            $this->assertSessionAccess($session);

            $summary = $this->teachingService->recordQuickAttendance($uuid, $attendanceRaw, $userId);
            return $this->response->setJSON(['status' => 'success', 'summary' => $summary]);
        } catch (Exception $e) {
            return $this->jsonError($e);
        }
    }

    /**
     * Links a lesson plan to current session.
     */
    public function linkPlan(string $uuid)
    {
        $userId = (int) session()->get('user_id');
        $lessonPlanId = (int) $this->request->getPost('lesson_plan_id');

        try {
            $session = $this->teachingService->getSessionByUuid($uuid);
            $this->assertSessionAccess($session);

            $this->teachingService->linkLessonPlan($uuid, $lessonPlanId, $userId);
            return redirect()->back()->with('success', 'Rencana Pembelajaran (RPP) berhasil dihubungkan ke sesi ini.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ================================================================
    // ATTENDANCE HISTORY & REPORTS
    // ================================================================

    /**
     * GET /teaching/attendance/history
     * Lists past attendance sessions with optional filters.
     */
    public function attendanceHistory()
    {
        $userId = (int) session()->get('user_id');
        $activeUnitId = (int) session()->get('active_unit_id');
        $activePeriod = get_active_period();
        $periodId = $activePeriod ? (int) $activePeriod['id'] : 0;

        $teacherId = $this->resolveOwnTeacherId();
        $dateFrom = $this->request->getGet('from') ?: '';
        $dateTo = $this->request->getGet('to') ?: '';
        $classroomId = (int) $this->request->getGet('classroom_id');

        $db = Database::connect();

        // Get teacher's classrooms for the filter dropdown
        $myClassrooms = [];
        if ($teacherId > 0) {
            $myClassrooms = $db->table('classrooms c')
                ->select('c.id, c.name, su.name as unit_name')
                ->join('school_units su', 'su.id = c.unit_id')
                ->where('c.academic_period_id', $periodId)
                ->groupStart()
                    ->where('c.homeroom_teacher_id', $teacherId)
                ->orWhereIn('c.id', function ($builder) use ($teacherId, $periodId) {
                    $builder->select('se.classroom_id')
                        ->from('schedule_entries se')
                        ->join('schedule_versions sv', 'sv.id = se.schedule_version_id')
                        ->where('se.teacher_id', $teacherId)
                        ->where('sv.academic_period_id', $periodId)
                        ->where('sv.workflow_status IN', ['APPROVED', 'LOCKED'])
                        ->groupBy('se.classroom_id');
                })
                ->groupEnd()
                ->where('c.deleted_at IS NULL')
                ->orderBy('c.name')
                ->get()->getResultArray();
        }

        // Build query
        $builder = $db->table('attendance_sessions as')
            ->select('as.id, as.attendance_date, as.session_type, as.meeting_number, as.topic, as.status,
                     c.name as classroom_name, COALESCE(s.name, as.topic, as.routine_code) as subject_name,
                     t.full_name as teacher_name, as.teacher_id')
            ->join('classrooms c', 'c.id = as.classroom_id')
            ->join('subjects s', 's.id = as.subject_id', 'left')
            ->join('teachers t', 't.id = as.teacher_id', 'left')
            ->where('as.academic_period_id', $periodId)
            ->where('as.deleted_at IS NULL')
            ->orderBy('as.attendance_date', 'DESC')
            ->orderBy('as.id', 'DESC');

        if (!$this->isManagementRole() && $teacherId > 0) {
            $builder->groupStart()
                ->where('as.teacher_id', $teacherId)
                ->orWhereIn('as.classroom_id', array_column($myClassrooms, 'id'))
                ->groupEnd();
        }
        if ($dateFrom !== '') {
            $builder->where('as.attendance_date >=', $dateFrom);
        }
        if ($dateTo !== '') {
            $builder->where('as.attendance_date <=', $dateTo);
        }
        if ($classroomId > 0) {
            $builder->where('as.classroom_id', $classroomId);
        }

        $sessions = $builder->limit(100)->get()->getResultArray();

        return view('teaching/attendance_history', [
            'title'             => 'Riwayat Absensi',
            'breadcrumb_active' => 'Riwayat Absensi',
            'sessions'          => $sessions,
            'myClassrooms'      => $myClassrooms,
            'filters'           => [
                'from'        => $dateFrom,
                'to'          => $dateTo,
                'classroom_id' => $classroomId,
            ],
        ]);
    }

    /**
     * GET /teaching/attendance/recap
     * Attendance recap matrix per classroom.
     */
    public function attendanceRecap()
    {
        $activePeriod = get_active_period();
        $periodId = $activePeriod ? (int) $activePeriod['id'] : 0;
        $teacherId = $this->resolveOwnTeacherId();
        $classroomId = (int) $this->request->getGet('classroom_id');
        $subjectId = (int) $this->request->getGet('subject_id');

        $db = Database::connect();

        // Get teacher's classrooms
        $myClassrooms = [];
        $mySubjects = [];
        if ($teacherId > 0) {
            $myClassrooms = $db->table('classrooms c')
                ->select('c.id, c.name')
                ->where('c.academic_period_id', $periodId)
                ->groupStart()
                    ->where('c.homeroom_teacher_id', $teacherId)
                ->orWhereIn('c.id', function ($builder) use ($teacherId, $periodId) {
                    $builder->select('se.classroom_id')
                        ->from('schedule_entries se')
                        ->join('schedule_versions sv', 'sv.id = se.schedule_version_id')
                        ->where('se.teacher_id', $teacherId)
                        ->where('sv.academic_period_id', $periodId)
                        ->where('sv.workflow_status IN', ['APPROVED', 'LOCKED'])
                        ->groupBy('se.classroom_id');
                })
                ->groupEnd()
                ->where('c.deleted_at IS NULL')
                ->orderBy('c.name')
                ->get()->getResultArray();

            $mySubjects = $db->table('schedule_entries se')
                ->select('DISTINCT s.id, s.name')
                ->join('subjects s', 's.id = se.subject_id')
                ->join('schedule_versions sv', 'sv.id = se.schedule_version_id')
                ->where('se.teacher_id', $teacherId)
                ->where('sv.academic_period_id', $periodId)
                ->where('sv.workflow_status IN', ['APPROVED', 'LOCKED'])
                ->orderBy('s.name')
                ->get()->getResultArray();
        }

        $recapData = null;
        if ($classroomId > 0 && $subjectId > 0 && $periodId > 0) {
            try {
                $attendanceService = new \App\Services\AttendanceService();
                $recapData = $attendanceService->calculateClassroomRecap($classroomId, $subjectId, $periodId);
            } catch (\Throwable $e) {
                // Silently handle — show empty state
            }
        }

        return view('teaching/attendance_recap', [
            'title'             => 'Rekapan Absensi',
            'breadcrumb_active' => 'Rekapan Absensi',
            'myClassrooms'      => $myClassrooms,
            'mySubjects'        => $mySubjects,
            'recapData'         => $recapData,
            'filters'           => [
                'classroom_id' => $classroomId,
                'subject_id'   => $subjectId,
            ],
        ]);
    }

    /**
     * GET /teaching/attendance/offline
     * Form for teachers who teach offline and want to record attendance after the fact.
     */
    public function offlineAttendance()
    {
        $activePeriod = get_active_period();
        $periodId = $activePeriod ? (int) $activePeriod['id'] : 0;
        $teacherId = $this->resolveOwnTeacherId();
        $activeUnitId = (int) session()->get('active_unit_id');

        $db = Database::connect();
        $attendanceService = new \App\Services\AttendanceService();

        // Get teacher's assignments
        $assignments = $teacherId > 0 ? $attendanceService->getTeacherAssignments($teacherId, $periodId) : [];
        $teachingAssignments = $assignments['teaching_assignments'] ?? [];

        // Get homerooms
        $homerooms = [];
        if ($teacherId > 0) {
            $homerooms = $db->table('classrooms c')
                ->select('c.id, c.name, su.name as unit_name')
                ->join('school_units su', 'su.id = c.unit_id')
                ->where('c.academic_period_id', $periodId)
                ->where('c.homeroom_teacher_id', $teacherId)
                ->where('c.deleted_at IS NULL')
                ->get()->getResultArray();
        }

        // Get selected classroom students if classroom_id provided
        $classroomId = (int) $this->request->getGet('classroom_id');
        $roster = [];
        if ($classroomId > 0) {
            $roster = $attendanceService->getSuggestedRoster($classroomId, date('Y-m-d'), 'SUBJECT');
        }

        return view('teaching/offline_attendance', [
            'title'               => 'Input Absensi Offline',
            'breadcrumb_active'   => 'Input Absensi Offline',
            'teachingAssignments' => $teachingAssignments,
            'homerooms'           => $homerooms,
            'roster'              => $roster,
            'classroomId'         => $classroomId,
        ]);
    }

    /**
     * POST /teaching/attendance/offline/save
     * Saves offline attendance data.
     */
    public function saveOfflineAttendance()
    {
        $userId = (int) session()->get('user_id');
        $teacherId = $this->resolveOwnTeacherId();
        $period = get_active_period();
        if (!$period) {
            return redirect()->back()->with('error', 'Tidak ada periode akademik aktif.');
        }

        $classroomId = (int) $this->request->getPost('classroom_id');
        $subjectId = (int) $this->request->getPost('subject_id');
        $date = $this->request->getPost('attendance_date') ?: date('Y-m-d');
        $meetingNumber = max(1, (int) $this->request->getPost('meeting_number'));
        $topic = $this->request->getPost('topic') ?: '';

        if ($classroomId <= 0 || $subjectId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Kelas dan mata pelajaran wajib dipilih.');
        }

        $db = Database::connect();
        $classroom = $db->table('classrooms')->where('id', $classroomId)->where('deleted_at IS NULL')->get()->getRowArray();
        if (!$classroom) {
            return redirect()->back()->withInput()->with('error', 'Kelas tidak ditemukan.');
        }

        $attendanceService = new \App\Services\AttendanceService();

        $sessionData = [
            'unit_id'            => (int) $classroom['unit_id'],
            'academic_period_id' => (int) $period['id'],
            'classroom_id'       => $classroomId,
            'subject_id'         => $subjectId,
            'teacher_id'         => $teacherId ?: null,
            'session_type'       => 'SUBJECT',
            'source_type'        => 'MANUAL_ASSIGNMENT',
            'source_key'         => $attendanceService->buildSourceKey('SUBJECT', (int) $period['id'], $classroomId, $date, $subjectId, null, $meetingNumber),
            'attendance_date'    => $date,
            'meeting_number'     => $meetingNumber,
            'topic'              => $topic,
            'status'             => 'SUBMITTED',
        ];

        $rawRoster = $this->request->getPost('roster');
        if (!is_array($rawRoster)) {
            return redirect()->back()->withInput()->with('error', 'Data presensi siswa tidak valid.');
        }

        $records = [];
        foreach ($rawRoster as $studentId => $value) {
            $records[] = [
                'student_id' => (int) $studentId,
                'status'     => strtoupper((string) ($value['status'] ?? 'HADIR')),
                'notes'      => trim((string) ($value['notes'] ?? '')),
            ];
        }

        try {
            $saved = $attendanceService->saveSession($sessionData, $records, $userId);
            AuditService::log('attendance', 'CREATE_SESSION', 'AttendanceSession', (int) $saved['session']['id'], null, [
                'session_type' => 'SUBJECT', 'status' => 'SUBMITTED',
                'attendance_date' => $date, 'classroom_id' => $classroomId,
                'subject_id' => $subjectId, 'roster_count' => count($records),
            ], 'Absensi offline disimpan');
            return redirect()->to(base_url('teaching/attendance/history'))->with('success', 'Absensi berhasil disimpan. ' . count($records) . ' siswa tercatat.');
        } catch (\Throwable $e) {
            log_message('error', 'Offline attendance save failed: {message}', ['message' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    // ---- Private helpers ----

    /**
     * Enforces unit scope and session ownership for a session row.
     */
    private function assertSessionAccess(array $session): void
    {
        UnitScopeService::assertUnit((int) ($session['unit_id'] ?? 0));

        if ($this->isManagementRole()) {
            return;
        }

        $sessionTeacherId = (int) ($session['teacher_id'] ?? 0);
        if ($sessionTeacherId <= 0) {
            return;
        }

        $ownTeacherId = $this->resolveOwnTeacherId();
        if ($ownTeacherId > 0 && $ownTeacherId === $sessionTeacherId) {
            return;
        }

        throw new AuthorizationException('Anda hanya dapat mengelola sesi pembelajaran milik Anda sendiri.');
    }

    private function isManagementRole(): bool
    {
        return has_role('super_admin', 'superadmin', 'wakasek_kurikulum', 'admin_smp', 'admin_sma');
    }

    private function resolveOwnTeacherId(): int
    {
        $userId = (int) session()->get('user_id');
        if ($userId <= 0) {
            return 0;
        }

        $teacherId = (int) (session()->get('teacher_id') ?? 0);
        if ($teacherId > 0) {
            return $teacherId;
        }

        $user = Database::connect()->table('users')
            ->select('teacher_id')
            ->where('id', $userId)
            ->where('is_active', 1)
            ->get()->getRowArray();

        $teacherId = $user ? (int) $user['teacher_id'] : 0;
        if ($teacherId > 0) {
            session()->set('teacher_id', $teacherId);
        }

        return $teacherId;
    }

    private function jsonError(Exception $e)
    {
        $status = $e instanceof AuthorizationException ? 403 : 400;
        return $this->response->setStatusCode($status)->setJSON([
            'status'  => 'error',
            'message' => $e->getMessage(),
        ]);
    }
}