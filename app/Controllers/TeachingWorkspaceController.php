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

        $date = $this->request->getGet('date') ?: date('Y-m-d');

        // Resolve teacher id from logged in user (users.teacher_id or session)
        $teacherId = $this->resolveOwnTeacherId();
        $teacher = $teacherId > 0
            ? $db->table('teachers')->where('id', $teacherId)->where('is_active', 1)->get()->getRowArray()
            : null;

        // If admin/superadmin with no teacher record, allow picking a teacher or default to first active teacher
        $teachersList = [];
        if (!$teacher && $this->isManagementRole()) {
            $teachersList = $db->table('teachers')
                ->where('primary_unit_id', $activeUnitId)
                ->where('is_active', 1)
                ->orderBy('full_name', 'ASC')
                ->get()->getResultArray();

            $selectedTeacherId = (int) ($this->request->getGet('teacher_id') ?: ($teachersList[0]['id'] ?? 0));
            if ($selectedTeacherId > 0) {
                $teacherId = $selectedTeacherId;
            }
        }

        $workspace = $this->teachingService->getTodayWorkspace($teacherId, $periodId, $date, $activeUnitId);

        return view('teaching/today', [
            'title'             => 'Ruang Mengajar (Today Workspace)',
            'breadcrumb_active' => 'Today Workspace',
            'date'              => $date,
            'workspace'         => $workspace,
            'teacher'           => $teacher,
            'teachersList'      => $teachersList,
            'currentTeacherId'  => $teacherId,
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