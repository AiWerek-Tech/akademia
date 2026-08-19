<?php

namespace App\Services;

use App\Models\AttendanceSessionModel;
use App\Models\LearningSessionActivityModel;
use App\Models\LearningSessionModel;
use App\Models\LearningSessionObservationModel;
use App\Models\LearningSessionReflectionModel;
use App\Models\StudentAttendanceModel;
use Config\Database;
use InvalidArgumentException;
use RuntimeException;

class TeachingWorkspaceService
{
    public const STATUS_PLANNED     = 'PLANNED';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_COMPLETED   = 'COMPLETED';
    public const STATUS_REFLECTED   = 'REFLECTED';

    public const ALLOWED_STATUSES = [
        self::STATUS_PLANNED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_REFLECTED,
    ];

    public const ATTENDANCE_STATUSES = [
        'HADIR',
        'TERLAMBAT',
        'IZIN',
        'SAKIT',
        'ALPA',
        'DISPENSASI',
    ];

    public const OBSERVATION_TYPES = [
        'GENERAL',
        'FORMATIVE',
        'BEHAVIOR',
        'COGNITIVE',
        'AFFECTIVE',
        'PSYCHOMOTOR',
    ];

    public const OBSERVATION_RATINGS = [
        'EXCELLENT',
        'GOOD',
        'UNCERTAIN',
        'NEEDS_HELP',
    ];

    public const REFLECTION_ENGAGEMENTS = [
        'HIGH',
        'MODERATE',
        'LOW',
    ];

    public const REFLECTION_ACHIEVEMENTS = [
        'ACHIEVED',
        'PARTIAL',
        'NOT_ACHIEVED',
    ];

    private $db;
    private LearningSessionModel $sessionModel;
    private LearningSessionActivityModel $activityModel;
    private LearningSessionObservationModel $observationModel;
    private LearningSessionReflectionModel $reflectionModel;

    public function __construct()
    {
        $this->db               = Database::connect();
        $this->sessionModel     = new LearningSessionModel();
        $this->activityModel    = new LearningSessionActivityModel();
        $this->observationModel = new LearningSessionObservationModel();
        $this->reflectionModel  = new LearningSessionReflectionModel();
    }

    /**
     * Builds the teacher's daily workspace timeline with session statuses,
     * attention alerts, and lesson plan links.
     */
    public function getTodayWorkspace(int $teacherId, int $periodId, string $date, ?int $unitId = null): array
    {
        $attendanceService = new AttendanceService();
        $dailyData = $attendanceService->getDailyWorkspace($teacherId, $periodId, $date, $unitId);

        // Fetch all learning_sessions for this teacher & date & period
        $sessionBuilder = $this->db->table('learning_sessions ls')
            ->select('ls.*, lp.uuid as lesson_plan_uuid, lp.status as lesson_plan_status, s.name as subject_name, s.code as subject_code, c.name as classroom_name')
            ->join('subjects s', 's.id = ls.subject_id', 'left')
            ->join('classrooms c', 'c.id = ls.classroom_id', 'left')
            ->join('lesson_plans lp', 'lp.id = ls.lesson_plan_id', 'left')
            ->where('ls.academic_period_id', $periodId)
            ->where('ls.session_date', $date);

        if ($teacherId > 0) {
            $sessionBuilder->where('ls.teacher_id', $teacherId);
        }
        if ($unitId) {
            $sessionBuilder->where('ls.unit_id', $unitId);
        }

        $existingSessions = $sessionBuilder->get()->getResultArray();

        // Also fetch legacy attendance_sessions for the same date
        $legacySessions = self::getLegacySessionsForDate($teacherId, $periodId, $date, $unitId);

        $sessionsBySchedule = [];
        $sessionsByClassSubject = [];
        foreach ($existingSessions as $s) {
            if (!empty($s['schedule_entry_id'])) {
                $sessionsBySchedule[(int) $s['schedule_entry_id']] = $s;
            }
            $key = (int) $s['classroom_id'] . ':' . (int) $s['subject_id'];
            $sessionsByClassSubject[$key] = $s;
        }

        // Enrich lessons in dailyData
        $lessons = [];
        foreach ($dailyData['lessons'] as $lesson) {
            $scheduleId = !empty($lesson['schedule_entry_id']) ? (int) $lesson['schedule_entry_id'] : null;
            $classSubjectKey = (int) $lesson['classroom_id'] . ':' . (int) $lesson['subject_id'];
            
            $session = $scheduleId && isset($sessionsBySchedule[$scheduleId])
                ? $sessionsBySchedule[$scheduleId]
                : ($sessionsByClassSubject[$classSubjectKey] ?? null);

            // Find possible lesson plans for this subject + unit + grade
            $classroomGrade = $this->db->table('classrooms')
                ->select('grade_level_id')
                ->where('id', (int) $lesson['classroom_id'])
                ->get()->getRowArray();
            $gradeLevelId = $classroomGrade ? (int) $classroomGrade['grade_level_id'] : null;

            $planQuery = $this->db->table('lesson_plans lp')
                ->select('lp.id, lp.uuid, lp.session_number, lp.session_label, lp.status, lu.title as unit_title')
                ->join('learning_units lu', 'lu.id = lp.learning_unit_id', 'left')
                ->where('lp.subject_id', (int) $lesson['subject_id'])
                ->where('lp.unit_id', (int) $lesson['unit_id'])
                ->whereIn('lp.status', ['READY', 'IN_PROGRESS', 'COMPLETED']);
            if ($gradeLevelId > 0) {
                $planQuery->where('lp.grade_level_id', $gradeLevelId);
            }
            $availablePlans = $planQuery
                ->orderBy('lp.session_number', 'ASC')
                ->get()->getResultArray();

            $lesson['learning_session'] = $session;
            $lesson['available_plans'] = $availablePlans;
            $lessons[] = $lesson;
        }

        // Attention Center calculations
        $followUpCount = $this->db->table('learning_session_observations lso')
            ->join('learning_sessions ls', 'ls.id = lso.learning_session_id')
            ->where('ls.teacher_id', $teacherId)
            ->where('ls.academic_period_id', $periodId)
            ->where('lso.follow_up_needed', 1)
            ->countAllResults();

        $pendingReflections = $this->db->table('learning_sessions')
            ->where('teacher_id', $teacherId)
            ->where('academic_period_id', $periodId)
            ->where('status', self::STATUS_COMPLETED)
            ->countAllResults();

        $recentMisconceptions = $this->db->table('learning_session_observations lso')
            ->select('lso.*, ls.session_date, s.name as subject_name, st.full_name as student_name')
            ->join('learning_sessions ls', 'ls.id = lso.learning_session_id')
            ->join('subjects s', 's.id = ls.subject_id', 'left')
            ->join('elective_students st', 'st.id = lso.student_id', 'left')
            ->where('ls.teacher_id', $teacherId)
            ->where('lso.misconception_found', 1)
            ->orderBy('lso.created_at', 'DESC')
            ->limit(5)
            ->get()->getResultArray();

        $stats = [
            'total_lessons'       => count($lessons),
            'completed_count'     => count(array_filter($existingSessions, fn($s) => in_array($s['status'], [self::STATUS_COMPLETED, self::STATUS_REFLECTED]))),
            'in_progress_count'   => count(array_filter($existingSessions, fn($s) => $s['status'] === self::STATUS_IN_PROGRESS)),
            'planned_count'       => count(array_filter($existingSessions, fn($s) => $s['status'] === self::STATUS_PLANNED)),
            'pending_reflections' => $pendingReflections,
            'follow_up_needed'    => $followUpCount,
        ];

        return [
            'date'                 => $date,
            'calendar_day'         => $dailyData['calendar_day'],
            'lessons'              => $lessons,
            'morning'              => $dailyData['morning'],
            'classroom'            => $dailyData['classroom'],
            'afternoon'            => $dailyData['afternoon'],
            'stats'                => $stats,
            'recent_misconceptions'=> $recentMisconceptions,
        ];
    }

    /**
     * Initializes or finds an existing learning session for a class block.
     */
    public function initializeSession(array $payload, int $userId): array
    {
        $periodId        = (int) $payload['academic_period_id'];
        $unitId          = (int) $payload['unit_id'];
        $teacherId       = (int) $payload['teacher_id'];
        $classroomId     = (int) $payload['classroom_id'];
        $subjectId       = (int) $payload['subject_id'];
        $sessionDate     = $payload['session_date'] ?? date('Y-m-d');
        $scheduleEntryId = !empty($payload['schedule_entry_id']) ? (int) $payload['schedule_entry_id'] : null;
        $lessonPlanId    = !empty($payload['lesson_plan_id']) ? (int) $payload['lesson_plan_id'] : null;
        $jpCount         = !empty($payload['jp_count']) ? (int) $payload['jp_count'] : 2;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $sessionDate)) {
            throw new InvalidArgumentException('Format tanggal sesi tidak valid.');
        }
        if ($periodId <= 0 || $unitId <= 0 || $teacherId <= 0 || $classroomId <= 0 || $subjectId <= 0) {
            throw new InvalidArgumentException('Parameter sesi pembelajaran tidak lengkap.');
        }

        // A provided schedule entry must match the class block being initialized.
        if ($scheduleEntryId) {
            $entry = $this->db->table('schedule_entries se')
                ->select('se.classroom_id, se.subject_id, sv.unit_id, sv.academic_period_id')
                ->join('schedule_versions sv', 'sv.id = se.schedule_version_id')
                ->where('se.id', $scheduleEntryId)
                ->get()->getRowArray();
            if (
                !$entry
                || (int) $entry['classroom_id'] !== $classroomId
                || (int) $entry['subject_id'] !== $subjectId
                || (int) $entry['unit_id'] !== $unitId
                || (int) $entry['academic_period_id'] !== $periodId
            ) {
                throw new InvalidArgumentException('Entri jadwal tidak sesuai dengan kelas/mata pelajaran yang dipilih.');
            }
        }

        // Check if session already exists
        $builder = $this->sessionModel->where('academic_period_id', $periodId)
            ->where('classroom_id', $classroomId)
            ->where('subject_id', $subjectId)
            ->where('session_date', $sessionDate);

        if ($scheduleEntryId) {
            $builder->where('schedule_entry_id', $scheduleEntryId);
        }

        $existing = $builder->first();
        if ($existing) {
            // If lesson plan changed, link it
            if ($lessonPlanId && (int) ($existing['lesson_plan_id'] ?? 0) !== $lessonPlanId) {
                $this->linkLessonPlan($existing['uuid'], $lessonPlanId, $userId);
                return $this->getSessionByUuid($existing['uuid']);
            }
            return $existing;
        }

        // Get grade level
        $classroom = $this->db->table('classrooms')->where('id', $classroomId)->get()->getRowArray();
        $gradeLevelId = $classroom ? (int) ($classroom['grade_level_id'] ?? null) : null;

        // Calculate meeting number from prior sessions (same class + subject) up to this date.
        $previousMeetings = $this->sessionModel->where('academic_period_id', $periodId)
            ->where('classroom_id', $classroomId)
            ->where('subject_id', $subjectId)
            ->where('session_date <=', $sessionDate)
            ->countAllResults();
        $meetingNumber = $previousMeetings + 1;

        $uuid = UuidService::v4();
        $now = date('Y-m-d H:i:s');

        $sessionData = [
            'uuid'               => $uuid,
            'academic_period_id' => $periodId,
            'unit_id'            => $unitId,
            'teacher_id'         => $teacherId,
            'classroom_id'       => $classroomId,
            'subject_id'         => $subjectId,
            'grade_level_id'     => $gradeLevelId,
            'schedule_entry_id'  => $scheduleEntryId,
            'lesson_plan_id'     => $lessonPlanId,
            'session_date'       => $sessionDate,
            'meeting_number'     => $meetingNumber,
            'jp_count'           => $jpCount,
            'start_time'         => $payload['start_time'] ?? null,
            'end_time'           => $payload['end_time'] ?? null,
            'topic'              => $payload['topic'] ?? null,
            'status'             => self::STATUS_PLANNED,
            'revision_number'    => 1,
            'created_by'         => $userId,
            'updated_by'         => $userId,
            'created_at'         => $now,
            'updated_at'         => $now,
        ];

        $this->sessionModel->insert($sessionData);
        $sessionId = (int) $this->db->insertID();

        // If lesson plan provided, copy activities into session checklist
        if ($lessonPlanId) {
            $this->populateActivitiesFromPlan($sessionId, $lessonPlanId, $userId);
            $this->syncLessonPlanMetadata($sessionId, $lessonPlanId);
        } else {
            // Seed default 3D deep learning framework activities
            $this->seedDefaultActivities($sessionId, $userId);
        }

        AuditService::log('teaching', 'init_session', 'learning_sessions', $sessionId, null, $sessionData, $userId, $uuid);

        return $this->getSessionByUuid($uuid);
    }

    /**
     * Links a Lesson Plan (RPP) to a session and populates activities/misconceptions.
     */
    public function linkLessonPlan(string $sessionUuid, int $lessonPlanId, int $userId): array
    {
        $session = $this->getSessionByUuid($sessionUuid);
        $this->assertSessionMutable($session);

        $plan = $this->db->table('lesson_plans')->where('id', $lessonPlanId)->get()->getRowArray();
        if (!$plan) {
            throw new InvalidArgumentException('Rencana Pembelajaran (RPP) tidak ditemukan.');
        }
        if ((int) $plan['unit_id'] !== (int) $session['unit_id']) {
            throw new InvalidArgumentException('RPP tidak dapat dihubungkan: unit sekolah tidak sesuai dengan sesi ini.');
        }
        if ((int) $plan['subject_id'] !== (int) $session['subject_id']) {
            throw new InvalidArgumentException('RPP tidak dapat dihubungkan: mata pelajaran tidak sesuai dengan sesi ini.');
        }
        if (!empty($plan['grade_level_id']) && !empty($session['grade_level_id']) && (int) $plan['grade_level_id'] !== (int) $session['grade_level_id']) {
            throw new InvalidArgumentException('RPP tidak dapat dihubungkan: tingkat kelas tidak sesuai dengan rombel sesi ini.');
        }

        $now = date('Y-m-d H:i:s');
        $this->sessionModel->update($session['id'], [
            'lesson_plan_id'  => $lessonPlanId,
            'updated_by'      => $userId,
            'updated_at'      => $now,
        ]);

        // Remove uncompleted activities and re-populate from RPP
        $this->activityModel->where('learning_session_id', $session['id'])
            ->where('is_completed', 0)
            ->delete();

        $this->populateActivitiesFromPlan((int) $session['id'], $lessonPlanId, $userId);
        $this->syncLessonPlanMetadata((int) $session['id'], $lessonPlanId);

        AuditService::log('teaching', 'link_plan', 'learning_sessions', (int) $session['id'], null, ['lesson_plan_id' => $lessonPlanId], $userId, $sessionUuid);

        return $this->getSessionByUuid($sessionUuid);
    }

    /**
     * Starts the teaching session (PLANNED -> IN_PROGRESS).
     */
    public function startSession(string $sessionUuid, int $userId): array
    {
        $session = $this->getSessionByUuid($sessionUuid);
        if ($session['status'] === self::STATUS_COMPLETED || $session['status'] === self::STATUS_REFLECTED) {
            throw new RuntimeException('Sesi pembelajaran ini telah selesai dan tidak dapat dimulai kembali.');
        }

        $now = date('Y-m-d H:i:s');
        $currentTime = date('H:i:s');

        // Idempotent: a session already in progress keeps its recorded timestamps.
        if ($session['status'] === self::STATUS_IN_PROGRESS) {
            return $this->getSessionByUuid($sessionUuid);
        }

        // Check if attendance_session exists or create one
        $attendanceSessionId = $session['attendance_session_id'];
        if (!$attendanceSessionId) {
            $attendanceSessionId = $this->ensureAttendanceSession($session, $userId);
        }

        $this->sessionModel->update($session['id'], [
            'status'                => self::STATUS_IN_PROGRESS,
            'actual_start_time'     => $session['actual_start_time'] ?: $currentTime,
            'started_at'            => $session['started_at'] ?: $now,
            'attendance_session_id' => $attendanceSessionId,
            'updated_by'            => $userId,
            'updated_at'            => $now,
        ]);

        AuditService::log('teaching', 'start_session', 'learning_sessions', (int) $session['id'], ['status' => $session['status']], ['status' => self::STATUS_IN_PROGRESS], $userId, $sessionUuid);

        return $this->getSessionByUuid($sessionUuid);
    }

    /**
     * Completes the teaching session (IN_PROGRESS -> COMPLETED).
     */
    public function completeSession(string $sessionUuid, array $data, int $userId): array
    {
        $session = $this->getSessionByUuid($sessionUuid);

        if ($session['status'] === self::STATUS_COMPLETED || $session['status'] === self::STATUS_REFLECTED) {
            throw new RuntimeException('Sesi pembelajaran ini telah diselesaikan sebelumnya.');
        }
        if ($session['status'] !== self::STATUS_IN_PROGRESS) {
            throw new RuntimeException('Sesi belum dimulai. Mulailah kelas terlebih dahulu sebelum menyelesaikannya.');
        }

        $now = date('Y-m-d H:i:s');
        $currentTime = date('H:i:s');

        $updateData = [
            'status'                     => self::STATUS_COMPLETED,
            'actual_end_time'            => $currentTime,
            'completed_at'               => $now,
            'deviation_notes'            => $data['deviation_notes'] ?? $session['deviation_notes'],
            'learning_objective_summary' => $data['learning_objective_summary'] ?? $session['learning_objective_summary'],
            'updated_by'                 => $userId,
            'updated_at'                 => $now,
        ];

        $this->sessionModel->update($session['id'], $updateData);

        AuditService::log('teaching', 'complete_session', 'learning_sessions', (int) $session['id'], ['status' => $session['status']], $updateData, $userId, $sessionUuid);

        return $this->getSessionByUuid($sessionUuid);
    }

    /**
     * Saves post-session reflection (COMPLETED -> REFLECTED).
     */
    public function saveReflection(string $sessionUuid, array $reflectionData, int $userId): array
    {
        $session = $this->getSessionByUuid($sessionUuid);

        if ($session['status'] !== self::STATUS_COMPLETED && $session['status'] !== self::STATUS_REFLECTED) {
            throw new RuntimeException('Jurnal refleksi hanya dapat diisi setelah sesi kelas diselesaikan.');
        }

        $engagement = strtoupper(trim((string) ($reflectionData['student_engagement'] ?? 'HIGH')));
        if (!in_array($engagement, self::REFLECTION_ENGAGEMENTS, true)) {
            throw new InvalidArgumentException('Tingkat keterlibatan siswa tidak valid.');
        }

        $achievement = strtoupper(trim((string) ($reflectionData['objective_achievement'] ?? 'ACHIEVED')));
        if (!in_array($achievement, self::REFLECTION_ACHIEVEMENTS, true)) {
            throw new InvalidArgumentException('Status ketercapaian TP tidak valid.');
        }

        $rating = isset($reflectionData['self_rating']) && $reflectionData['self_rating'] !== ''
            ? (int) $reflectionData['self_rating']
            : 4;
        $rating = max(1, min(5, $rating));

        $now = date('Y-m-d H:i:s');
        $existingReflection = $this->reflectionModel->where('learning_session_id', $session['id'])->first();

        $dataToSave = [
            'learning_session_id'   => $session['id'],
            'what_went_well'        => $reflectionData['what_went_well'] ?? null,
            'challenges'            => $reflectionData['challenges'] ?? null,
            'student_engagement'    => $engagement,
            'objective_achievement' => $achievement,
            'tp_coverage_notes'     => $reflectionData['tp_coverage_notes'] ?? null,
            'follow_up_plan'        => $reflectionData['follow_up_plan'] ?? null,
            'next_session_notes'    => $reflectionData['next_session_notes'] ?? null,
            'self_rating'           => $rating,
            'updated_by'            => $userId,
            'updated_at'            => $now,
        ];

        if ($existingReflection) {
            $this->reflectionModel->update($existingReflection['id'], $dataToSave);
        } else {
            $dataToSave['uuid'] = UuidService::v4();
            $dataToSave['created_by'] = $userId;
            $dataToSave['created_at'] = $now;
            $this->reflectionModel->insert($dataToSave);
        }

        // Transition session to REFLECTED
        $this->sessionModel->update($session['id'], [
            'status'       => self::STATUS_REFLECTED,
            'reflected_at' => $now,
            'updated_by'   => $userId,
            'updated_at'   => $now,
        ]);

        AuditService::log('teaching', 'save_reflection', 'learning_session_reflections', (int) $session['id'], null, $dataToSave, $userId, $sessionUuid);

        return $this->getSessionByUuid($sessionUuid);
    }

    /**
     * Toggles completion of an activity in the teaching checklist.
     */
    public function toggleActivity(string $sessionUuid, string $activityUuid, bool $isCompleted, ?int $actualMinutes, ?string $notes, int $userId): array
    {
        $session = $this->getSessionByUuid($sessionUuid);
        $this->assertSessionMutable($session);

        $activity = $this->activityModel->where('uuid', $activityUuid)
            ->where('learning_session_id', $session['id'])
            ->first();

        if (!$activity) {
            throw new InvalidArgumentException('Aktivitas pembelajaran tidak ditemukan.');
        }

        $now = date('Y-m-d H:i:s');
        $update = [
            'is_completed'   => $isCompleted ? 1 : 0,
            'completed_at'   => $isCompleted ? $now : null,
            'actual_minutes' => $actualMinutes,
            'notes'          => $notes,
            'updated_by'     => $userId,
            'updated_at'     => $now,
        ];

        $this->activityModel->update($activity['id'], $update);

        return $this->activityModel->find($activity['id']);
    }

    /**
     * Adds a formative observation or misconception note for a student.
     */
    public function addObservation(string $sessionUuid, array $obsData, int $userId): array
    {
        $session = $this->getSessionByUuid($sessionUuid);
        $this->assertSessionMutable($session);

        $obsType = strtoupper(trim((string) ($obsData['observation_type'] ?? 'GENERAL')));
        if (!in_array($obsType, self::OBSERVATION_TYPES, true)) {
            throw new InvalidArgumentException('Tipe observasi tidak valid.');
        }

        $rating = strtoupper(trim((string) ($obsData['rating'] ?? 'GOOD')));
        if (!in_array($rating, self::OBSERVATION_RATINGS, true)) {
            throw new InvalidArgumentException('Tingkat pemahaman tidak valid.');
        }

        $now = date('Y-m-d H:i:s');
        $uuid = UuidService::v4();

        $data = [
            'uuid'                => $uuid,
            'learning_session_id' => $session['id'],
            'student_id'          => !empty($obsData['student_id']) ? (int) $obsData['student_id'] : null,
            'observation_type'    => $obsType,
            'rating'              => $rating,
            'notes'               => $obsData['notes'] ?? null,
            'misconception_found' => !empty($obsData['misconception_found']) ? 1 : 0,
            'misconception_detail'=> $obsData['misconception_detail'] ?? null,
            'follow_up_needed'    => !empty($obsData['follow_up_needed']) ? 1 : 0,
            'created_by'          => $userId,
            'updated_by'          => $userId,
            'created_at'          => $now,
            'updated_at'          => $now,
        ];

        // If the student is provided, verify it actually belongs to the session classroom.
        if (!empty($data['student_id']) && !$this->studentBelongsToClassroom((int) $data['student_id'], (int) $session['classroom_id'])) {
            throw new InvalidArgumentException('Siswa yang dipilih tidak terdaftar pada rombel sesi ini.');
        }

        $this->observationModel->insert($data);
        $obsId = (int) $this->db->insertID();

        AuditService::log('teaching', 'add_observation', 'learning_session_observations', $obsId, null, $data, $userId, $sessionUuid);

        return $this->observationModel->find($obsId);
    }

    /**
     * Deletes a formative observation.
     */
    public function deleteObservation(string $sessionUuid, string $obsUuid, int $userId): bool
    {
        $session = $this->getSessionByUuid($sessionUuid);
        $this->assertSessionMutable($session);

        $obs = $this->observationModel->where('uuid', $obsUuid)
            ->where('learning_session_id', $session['id'])
            ->first();

        if (!$obs) {
            return false;
        }

        $this->observationModel->delete($obs['id']);
        AuditService::log('teaching', 'delete_observation', 'learning_session_observations', (int) $obs['id'], $obs, null, $userId, $sessionUuid);

        return true;
    }

    /**
     * Records quick student attendance during the teaching session.
     */
    public function recordQuickAttendance(string $sessionUuid, array $attendanceRecords, int $userId): array
    {
        $session = $this->getSessionByUuid($sessionUuid);
        $this->assertSessionMutable($session);

        // Validate the whole batch before persisting anything.
        $normalized = [];
        foreach ($attendanceRecords as $record) {
            $studentId = (int) ($record['student_id'] ?? 0);
            $status    = strtoupper(trim((string) ($record['status'] ?? 'HADIR')));

            if ($studentId <= 0) {
                continue;
            }
            if (!in_array($status, self::ATTENDANCE_STATUSES, true)) {
                throw new InvalidArgumentException("Status presensi '{$status}' tidak valid.");
            }
            if (!$this->studentBelongsToClassroom($studentId, (int) $session['classroom_id'])) {
                throw new InvalidArgumentException('Terdapat siswa yang tidak terdaftar pada rombel sesi ini.');
            }

            $normalized[] = [
                'student_id' => $studentId,
                'status'     => $status,
                'notes'      => $record['notes'] ?? null,
            ];
        }

        $attendanceSessionId = $session['attendance_session_id'];
        if (!$attendanceSessionId) {
            $attendanceSessionId = $this->ensureAttendanceSession($session, $userId);
            $this->sessionModel->update($session['id'], ['attendance_session_id' => $attendanceSessionId]);
        }

        $now = date('Y-m-d H:i:s');
        $studentAttendanceModel = new StudentAttendanceModel();

        foreach ($normalized as $record) {
            $studentId = (int) $record['student_id'];
            $status    = $record['status'];
            $notes     = $record['notes'];

            $existing = $studentAttendanceModel->where('session_id', $attendanceSessionId)
                ->where('student_id', $studentId)
                ->first();

            if ($existing) {
                $studentAttendanceModel->update($existing['id'], [
                    'status'     => $status,
                    'notes'      => $notes,
                    'updated_at' => $now,
                ]);
            } else {
                $studentAttendanceModel->insert([
                    'session_id' => $attendanceSessionId,
                    'student_id' => $studentId,
                    'status'     => $status,
                    'notes'      => $notes,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        AuditService::log('teaching', 'quick_attendance', 'attendance_sessions', $attendanceSessionId, null, ['count' => count($normalized)], $userId, $sessionUuid);

        return $this->getAttendanceSummary($attendanceSessionId);
    }

    /**
     * Retrieves full session details for the Teaching Mode page.
     */
    public function getSessionDetail(string $uuid): array
    {
        $session = $this->db->table('learning_sessions ls')
            ->select('ls.*, s.name as subject_name, s.code as subject_code, c.name as classroom_name, gl.name as grade_name, t.full_name as teacher_name, su.name as unit_name, su.code as unit_code, lp.uuid as lesson_plan_uuid, lp.session_number as plan_session_number, lp.session_label as plan_session_label')
            ->join('subjects s', 's.id = ls.subject_id', 'left')
            ->join('classrooms c', 'c.id = ls.classroom_id', 'left')
            ->join('grade_levels gl', 'gl.id = ls.grade_level_id', 'left')
            ->join('teachers t', 't.id = ls.teacher_id', 'left')
            ->join('school_units su', 'su.id = ls.unit_id', 'left')
            ->join('lesson_plans lp', 'lp.id = ls.lesson_plan_id', 'left')
            ->where('ls.uuid', $uuid)
            ->get()->getRowArray();

        if (!$session) {
            throw new InvalidArgumentException('Sesi pembelajaran tidak ditemukan.');
        }

        // Activities grouped by stage
        $activities = $this->activityModel->where('learning_session_id', $session['id'])
            ->orderBy('sequence_order', 'ASC')
            ->findAll();

        $groupedActivities = [
            'MEMAHAMI'     => [],
            'MENGAPLIKASI' => [],
            'MEREFLEKSI'   => [],
            'OTHER'        => [],
        ];
        foreach ($activities as $act) {
            $stage = strtoupper((string) ($act['stage_type'] ?? 'OTHER'));
            if (!isset($groupedActivities[$stage])) {
                $stage = 'OTHER';
            }
            $groupedActivities[$stage][] = $act;
        }

        // Observations with student info
        $observations = $this->db->table('learning_session_observations lso')
            ->select('lso.*, st.student_number as nisn, st.full_name as student_name')
            ->join('elective_students st', 'st.id = lso.student_id', 'left')
            ->where('lso.learning_session_id', $session['id'])
            ->orderBy('lso.created_at', 'DESC')
            ->get()->getResultArray();

        // Reflection
        $reflection = $this->reflectionModel->where('learning_session_id', $session['id'])->first();

        // Student Roster & Attendance Statuses
        $students = $this->getClassroomStudentsWithAttendance((int) $session['classroom_id'], (int) ($session['attendance_session_id'] ?? 0));

        // Adaptive recommendations
        $recommendations = $this->getAdaptiveRecommendations($session);

        return [
            'session'            => $session,
            'activities'         => $activities,
            'grouped_activities' => $groupedActivities,
            'observations'       => $observations,
            'reflection'         => $reflection,
            'students'           => $students,
            'recommendations'    => $recommendations,
        ];
    }

    /**
     * Helpers & Internal Methods
     */
    public function getSessionByUuid(string $uuid): array
    {
        $session = $this->sessionModel->where('uuid', $uuid)->first();
        if (!$session) {
            throw new InvalidArgumentException('Sesi pembelajaran tidak ditemukan.');
        }
        return $session;
    }

    /**
     * Rejects any mutation on a session whose workflow is locked (REFLECTED).
     */
    private function assertSessionMutable(array $session): void
    {
        if (($session['status'] ?? '') === self::STATUS_REFLECTED) {
            throw new RuntimeException('Sesi telah dituntaskan dan dikunci; perubahan tidak diizinkan.');
        }
    }

    /**
     * Verifies a student belongs to the given classroom.
     */
    private function studentBelongsToClassroom(int $studentId, int $classroomId): bool
    {
        return $this->db->table('elective_students')
            ->where('id', $studentId)
            ->where('classroom_id', $classroomId)
            ->where('is_active', 1)
            ->countAllResults() > 0;
    }

    private function populateActivitiesFromPlan(int $sessionId, int $lessonPlanId, int $userId): void
    {
        $planActivities = $this->db->table('lesson_plan_activities lpa')
            ->select('lpa.*, lps.stage_type')
            ->join('lesson_plan_stages lps', 'lps.id = lpa.lesson_plan_stage_id', 'left')
            ->where('lpa.lesson_plan_id', $lessonPlanId)
            ->orderBy('lpa.sequence_order', 'ASC')
            ->get()->getResultArray();

        $now = date('Y-m-d H:i:s');
        $seq = 1;
        foreach ($planActivities as $pa) {
            $stage = $pa['stage_type'] ?: 'MENGAPLIKASI';
            $this->activityModel->insert([
                'uuid'                    => UuidService::v4(),
                'learning_session_id'     => $sessionId,
                'lesson_plan_activity_id' => $pa['id'],
                'lesson_plan_stage_id'    => $pa['lesson_plan_stage_id'],
                'stage_type'              => $stage,
                'title'                   => $pa['custom_title'] ?: 'Aktivitas Belajar #' . $seq,
                'description'             => $pa['custom_description'],
                'sequence_order'          => $seq++,
                'is_completed'            => 0,
                'actual_minutes'          => $pa['estimated_minutes'],
                'created_by'              => $userId,
                'updated_by'              => $userId,
                'created_at'              => $now,
                'updated_at'              => $now,
            ]);
        }
    }

    private function syncLessonPlanMetadata(int $sessionId, int $lessonPlanId): void
    {
        $plan = $this->db->table('lesson_plans lp')
            ->select('lp.*, lu.title as unit_title')
            ->join('learning_units lu', 'lu.id = lp.learning_unit_id', 'left')
            ->where('lp.id', $lessonPlanId)
            ->get()->getRowArray();

        if ($plan) {
            $misconceptions = '';
            if (!empty($plan['learning_unit_id']) && $this->db->tableExists('learning_misconceptions')) {
                $mcRows = $this->db->table('learning_misconceptions')
                    ->where('learning_unit_id', (int) $plan['learning_unit_id'])
                    ->get()->getResultArray();
                if (!empty($mcRows)) {
                    $misconceptions = implode("\n", array_map(fn($m) => '• ' . $m['title'] . (!empty($m['description']) ? ': ' . $m['description'] : ''), $mcRows));
                }
            }

            $this->sessionModel->update($sessionId, [
                'topic'                      => $plan['session_label'] ?: ($plan['unit_title'] ?? 'Sesi Belajar'),
                'learning_objective_summary' => $plan['identification_notes'] ?? null,
                'misconception_warnings'     => $misconceptions ?: null,
            ]);
        }
    }

    private function seedDefaultActivities(int $sessionId, int $userId): void
    {
        $defaults = [
            ['stage_type' => 'MEMAHAMI', 'title' => 'Apersepsi & Pengecekan Kesiapan Belajar', 'desc' => 'Menghubungkan materi sebelumnya dengan pertanyaan pemantik hari ini.', 'min' => 15],
            ['stage_type' => 'MEMAHAMI', 'title' => 'Eksplorasi Konsep Utama', 'desc' => 'Penyampaian gagasan kunci dan klarifikasi miskonsepsi awal.', 'min' => 20],
            ['stage_type' => 'MENGAPLIKASI', 'title' => 'Aktivitas / Praktik Mandiri & Kolaboratif', 'desc' => 'Siswa menyelesaikan tantangan atau lembar kerja sesuai instruksi.', 'min' => 35],
            ['stage_type' => 'MENGAPLIKASI', 'title' => 'Diskusi & Asesmen Formatif Berjalan', 'desc' => 'Observasi keliling guru untuk memantau pemahaman siswa.', 'min' => 10],
            ['stage_type' => 'MEREFLEKSI', 'title' => 'Refleksi Sesi & Exit Ticket', 'desc' => 'Siswa menyimpulkan satu hal terpenting yang dipelajari hari ini.', 'min' => 10],
        ];

        $now = date('Y-m-d H:i:s');
        $seq = 1;
        foreach ($defaults as $d) {
            $this->activityModel->insert([
                'uuid'                => UuidService::v4(),
                'learning_session_id' => $sessionId,
                'stage_type'          => $d['stage_type'],
                'title'               => $d['title'],
                'description'         => $d['desc'],
                'sequence_order'      => $seq++,
                'is_completed'        => 0,
                'actual_minutes'      => $d['min'],
                'created_by'          => $userId,
                'updated_by'          => $userId,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
        }
    }

    private function ensureAttendanceSession(array $session, int $userId): int
    {
        $attendanceSessionModel = new AttendanceSessionModel();
        $sourceKey = (new AttendanceService())->buildSourceKey(
            'SUBJECT',
            (int) $session['academic_period_id'],
            (int) $session['classroom_id'],
            $session['session_date'],
            (int) $session['subject_id'],
            !empty($session['schedule_entry_id']) ? (int) $session['schedule_entry_id'] : null,
            (int) $session['meeting_number']
        );

        $existing = $attendanceSessionModel->where('source_key', $sourceKey)->first();
        if ($existing) {
            return (int) $existing['id'];
        }

        $now = date('Y-m-d H:i:s');
        $insertData = [
            'uuid'               => UuidService::v4(),
            'academic_period_id' => (int) $session['academic_period_id'],
            'unit_id'            => (int) $session['unit_id'],
            'teacher_id'         => (int) $session['teacher_id'],
            'classroom_id'       => (int) $session['classroom_id'],
            'subject_id'         => (int) $session['subject_id'],
            'schedule_entry_id'  => !empty($session['schedule_entry_id']) ? (int) $session['schedule_entry_id'] : null,
            'attendance_date'    => $session['session_date'],
            'meeting_number'     => (int) $session['meeting_number'],
            'start_time'         => $session['start_time'],
            'end_time'           => $session['end_time'],
            'topic'              => $session['topic'],
            'status'             => 'SUBMITTED',
            'session_type'       => 'SUBJECT',
            'source_type'        => !empty($session['schedule_entry_id']) ? 'SCHEDULE' : 'MANUAL_ASSIGNMENT',
            'source_key'         => $sourceKey,
            'created_by'         => $userId,
            'updated_by'         => $userId,
            'created_at'         => $now,
            'updated_at'         => $now,
        ];

        $attendanceSessionModel->insert($insertData);
        return (int) $this->db->insertID();
    }

    private function getClassroomStudentsWithAttendance(int $classroomId, int $attendanceSessionId): array
    {
        $students = $this->db->table('elective_students es')
            ->select('es.id, es.student_number as nisn, es.full_name')
            ->where('es.classroom_id', $classroomId)
            ->where('es.is_active', 1)
            ->orderBy('es.full_name', 'ASC')
            ->get()->getResultArray();

        $attendanceMap = [];
        if ($attendanceSessionId > 0) {
            $records = $this->db->table('student_attendances')
                ->where('session_id', $attendanceSessionId)
                ->get()->getResultArray();
            foreach ($records as $r) {
                $attendanceMap[(int) $r['student_id']] = $r;
            }
        }

        foreach ($students as &$st) {
            $att = $attendanceMap[(int) $st['id']] ?? null;
            $st['attendance_status'] = $att ? $att['status'] : 'HADIR';
            $st['attendance_notes']  = $att ? $att['notes'] : null;
        }

        return $students;
    }

    private function getAttendanceSummary(int $attendanceSessionId): array
    {
        $records = $this->db->table('student_attendances')
            ->where('session_id', $attendanceSessionId)
            ->get()->getResultArray();

        $counts = [
            'HADIR'      => 0,
            'TERLAMBAT'  => 0,
            'IZIN'       => 0,
            'SAKIT'      => 0,
            'ALPA'       => 0,
            'DISPENSASI' => 0,
        ];
        foreach ($records as $r) {
            $st = strtoupper($r['status']);
            if (isset($counts[$st])) {
                $counts[$st]++;
            }
        }
        return $counts;
    }

    private function getAdaptiveRecommendations(array $session): array
    {
        $recommendations = [];

        // 1. Unplugged option recommendation
        $recommendations[] = [
            'type'        => 'INFO',
            'title'       => 'Opsi Mode Plugged & Unplugged',
            'description' => 'Jika lab komputer atau koneksi internet terkendala, gunakan aktivitas simulasi kartu peran atau kerja berpasangan.',
            'badge'       => 'Adaptif',
        ];

        // 2. Misconception warning if present
        if (!empty($session['misconception_warnings'])) {
            $recommendations[] = [
                'type'        => 'WARNING',
                'title'       => 'Peringatan Miskonsepsi Siswa',
                'description' => $session['misconception_warnings'],
                'badge'       => 'Pedagogi',
            ];
        }

        return $recommendations;
    }

    // ─────────────────────────────────────────────────────────
    // LEGACY BRIDGE — Read-only access to old attendance_sessions
    // ─────────────────────────────────────────────────────────

    /**
     * Finds a legacy attendance session by ID and returns it in a format
     * compatible with the new teaching workspace, or null if not found.
     * This allows old attendance data to remain readable after consolidation.
     */
    public static function findLegacyAttendanceSession(int $sessionId): ?array
    {
        $db = Database::connect();

        if (! $db->tableExists('attendance_sessions')) {
            return null;
        }

        $row = $db->table('attendance_sessions ats')
            ->select('ats.*, c.name classroom_name, s.name subject_name, s.code subject_code, su.name unit_name, su.id unit_id')
            ->join('classrooms c', 'c.id = ats.classroom_id', 'left')
            ->join('subjects s', 's.id = ats.subject_id', 'left')
            ->join('school_units su', 'su.id = ats.unit_id', 'left')
            ->where('ats.id', $sessionId)
            ->get()->getRowArray();

        if (! $row) {
            return null;
        }

        return [
            'uuid'               => 'legacy-' . $row['id'],
            'source'             => 'LEGACY_ATTENDANCE',
            'legacy_id'          => (int) $row['id'],
            'academic_period_id' => (int) $row['academic_period_id'],
            'unit_id'            => (int) $row['unit_id'],
            'teacher_id'         => (int) ($row['teacher_id'] ?? 0),
            'classroom_id'       => (int) $row['classroom_id'],
            'subject_id'         => (int) ($row['subject_id'] ?? 0),
            'session_date'       => $row['attendance_date'] ?? null,
            'meeting_number'     => (int) ($row['meeting_number'] ?? 1),
            'topic'              => $row['topic'] ?? null,
            'status'             => strtoupper($row['status'] ?? 'DRAFT'),
            'classroom_name'     => $row['classroom_name'] ?? null,
            'subject_name'       => $row['subject_name'] ?? null,
            'subject_code'       => $row['subject_code'] ?? null,
            'unit_name'          => $row['unit_name'] ?? null,
        ];
    }

    /**
     * Returns the legacy attendance sessions for a given teacher + date,
     * bridged into the new workspace timeline format.
     */
    public static function getLegacySessionsForDate(int $teacherId, int $periodId, string $date, ?int $unitId = null): array
    {
        $db = Database::connect();

        if (! $db->tableExists('attendance_sessions')) {
            return [];
        }

        $builder = $db->table('attendance_sessions ats')
            ->select('ats.*, c.name classroom_name, s.name subject_name, s.code subject_code, su.name unit_name, su.id unit_id')
            ->join('classrooms c', 'c.id = ats.classroom_id', 'left')
            ->join('subjects s', 's.id = ats.subject_id', 'left')
            ->join('school_units su', 'su.id = ats.unit_id', 'left')
            ->where('ats.academic_period_id', $periodId)
            ->where('ats.attendance_date', $date)
            ->where('ats.deleted_at IS NULL')
            ->where('ats.teacher_id', $teacherId);

        if ($unitId) {
            $builder->where('ats.unit_id', $unitId);
        }

        $rows = $builder->orderBy('ats.meeting_number', 'ASC')->get()->getResultArray();

        return array_map(static fn(array $row) => [
            'uuid'           => 'legacy-' . $row['id'],
            'source'         => 'LEGACY_ATTENDANCE',
            'legacy_id'      => (int) $row['id'],
            'classroom_name' => $row['classroom_name'] ?? null,
            'subject_name'   => $row['subject_name'] ?? null,
            'subject_code'   => $row['subject_code'] ?? null,
            'unit_name'      => $row['unit_name'] ?? null,
            'meeting_number' => (int) ($row['meeting_number'] ?? 1),
            'topic'          => $row['topic'] ?? null,
            'status'         => strtoupper($row['status'] ?? 'DRAFT'),
            'session_date'   => $row['attendance_date'] ?? null,
        ], $rows);
    }
}
