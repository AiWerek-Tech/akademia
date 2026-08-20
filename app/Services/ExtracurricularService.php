<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Phase 8 — Extracurricular Service.
 *
 * Manages extracurricular programs, membership, attendance,
 * competencies, achievements, and annual evaluations.
 */
class ExtracurricularService
{
    // Status constants
    public const STATUS_DRAFT     = 'DRAFT';
    public const STATUS_ACTIVE    = 'ACTIVE';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const ALLOWED_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ACTIVE,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    // Member status
    public const MEMBER_ACTIVE = 'ACTIVE';
    public const MEMBER_LEFT   = 'LEFT';

    // Member roles
    public const ROLE_MEMBER = 'MEMBER';
    public const ROLE_COACH  = 'COACH';
    public const ROLE_LEADER = 'LEADER';

    // Attendance
    public const ATTENDANCE_PRESENT = 'PRESENT';
    public const ATTENDANCE_ABSENT  = 'ABSENT';
    public const ATTENDANCE_EXCUSED = 'EXCUSED';
    public const ATTENDANCE_LATE    = 'LATE';

    public const ALLOWED_ATTENDANCE = [
        self::ATTENDANCE_PRESENT,
        self::ATTENDANCE_ABSENT,
        self::ATTENDANCE_EXCUSED,
        self::ATTENDANCE_LATE,
    ];

    // Session status
    public const SESSION_PLANNED   = 'PLANNED';
    public const SESSION_COMPLETED = 'COMPLETED';
    public const SESSION_CANCELLED = 'CANCELLED';

    // Competency types
    public const COMPETENCY_QUALITATIVE = 'QUALITATIVE';
    public const COMPETENCY_QUANTITATIVE = 'QUANTITATIVE';

    // Categories
    public const ALLOWED_CATEGORIES = ['CLUB', 'SPORTS', 'ARTS', 'SCOUT', 'COMMUNITY_SERVICE', 'ACADEMIC_OLYMPIAD', 'OTHER'];

    // Ratings
    public const ALLOWED_RATINGS = ['EXCELLENT', 'GOOD', 'SATISFACTORY', 'NEEDS_IMPROVEMENT'];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?: Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    // ================================================================
    // PROGRAMS
    // ================================================================

    /**
     * List programs with optional filters.
     */
    public function listPrograms(int $unitId, int $periodId, array $filters = []): array
    {
        $builder = $this->db->table('extracurricular_programs p')
            ->select('p.*, t.full_name as coach_name')
            ->join('teachers t', 't.id = p.coach_teacher_id', 'left')
            ->where('p.unit_id', $unitId)
            ->where('p.academic_period_id', $periodId)
            ->orderBy('p.title', 'ASC');

        if (! empty($filters['status'])) {
            $builder->where('p.status', $filters['status']);
        }
        if (! empty($filters['category'])) {
            $builder->where('p.category', $filters['category']);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Detail of a single program with stats.
     */
    public function detailProgram(int $id): ?array
    {
        $program = $this->db->table('extracurricular_programs p')
            ->select('p.*, t.full_name as coach_name')
            ->join('teachers t', 't.id = p.coach_teacher_id', 'left')
            ->where('p.id', $id)
            ->get()->getRowArray();

        if (! $program) {
            return null;
        }

        $program['member_count'] = $this->db->table('extracurricular_members')
            ->where('program_id', $id)
            ->where('status', self::MEMBER_ACTIVE)
            ->countAllResults();

        $program['session_count'] = $this->db->table('extracurricular_sessions')
            ->where('program_id', $id)
            ->countAllResults();

        $program['competency_count'] = $this->db->table('extracurricular_competencies')
            ->where('program_id', $id)
            ->where('is_active', 1)
            ->countAllResults();

        return $program;
    }

    /**
     * Create a new extracurricular program.
     */
    public function createProgram(array $data, int $userId): int
    {
        $uuid = UuidService::v4();
        $now  = date('Y-m-d H:i:s');

        $this->db->table('extracurricular_programs')->insert([
            'uuid'              => $uuid,
            'unit_id'           => $data['unit_id'],
            'academic_period_id' => $data['academic_period_id'],
            'code'              => $data['code'] ?? null,
            'title'             => $data['title'],
            'category'          => $data['category'] ?? 'CLUB',
            'rationale'         => $data['rationale'] ?? null,
            'objective'         => $data['objective'] ?? null,
            'description'       => $data['description'] ?? null,
            'coach_teacher_id'  => ! empty($data['coach_teacher_id']) ? $data['coach_teacher_id'] : null,
            'management_notes'  => $data['management_notes'] ?? null,
            'funding_source'    => $data['funding_source'] ?? null,
            'funding_amount'    => $data['funding_amount'] ?? null,
            'max_members'       => $data['max_members'] ?? null,
            'meeting_day'       => $data['meeting_day'] ?? null,
            'meeting_time'      => $data['meeting_time'] ?? null,
            'location'          => $data['location'] ?? null,
            'status'            => $data['status'] ?? self::STATUS_DRAFT,
            'created_at'        => $now,
            'updated_at'        => $now,
            'created_by'        => $userId,
            'updated_by'        => $userId,
        ]);

        $id = (int) $this->db->insertID();
        AuditService::log('extracurricular', 'CREATE', 'ExtracurricularProgram', $id, null, ['title' => $data['title']], 'Membuat program ekstrakurikuler');

        return $id;
    }

    /**
     * Update an existing program.
     */
    public function updateProgram(int $id, array $data, int $userId): void
    {
        $now = date('Y-m-d H:i:s');

        $this->db->table('extracurricular_programs')
            ->where('id', $id)
            ->update([
                'code'              => $data['code'] ?? null,
                'title'             => $data['title'],
                'category'          => $data['category'] ?? 'CLUB',
                'rationale'         => $data['rationale'] ?? null,
                'objective'         => $data['objective'] ?? null,
                'description'       => $data['description'] ?? null,
                'coach_teacher_id'  => ! empty($data['coach_teacher_id']) ? $data['coach_teacher_id'] : null,
                'management_notes'  => $data['management_notes'] ?? null,
                'funding_source'    => $data['funding_source'] ?? null,
                'funding_amount'    => $data['funding_amount'] ?? null,
                'max_members'       => $data['max_members'] ?? null,
                'meeting_day'       => $data['meeting_day'] ?? null,
                'meeting_time'      => $data['meeting_time'] ?? null,
                'location'          => $data['location'] ?? null,
                'status'            => $data['status'] ?? self::STATUS_DRAFT,
                'updated_at'        => $now,
                'updated_by'        => $userId,
            ]);

        AuditService::log('extracurricular', 'UPDATE', 'ExtracurricularProgram', $id, null, ['title' => $data['title'] ?? ''], 'Memperbarui program ekstrakurikuler');
    }

    /**
     * Transition program status.
     */
    public function transitionProgram(int $id, string $targetStatus, int $userId): void
    {
        if (! in_array($targetStatus, self::ALLOWED_STATUSES, true)) {
            throw new \InvalidArgumentException('Status tidak valid: ' . $targetStatus);
        }

        $this->db->table('extracurricular_programs')
            ->where('id', $id)
            ->update([
                'status'     => $targetStatus,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $userId,
            ]);

        AuditService::log('extracurricular', 'TRANSITION', 'ExtracurricularProgram', $id, null, ['status' => $targetStatus], 'Transisi status program');
    }

    /**
     * Soft-delete a program (and cascade to children).
     */
    public function deleteProgram(int $id, int $userId): void
    {
        $program = $this->detailProgram($id);
        if (! $program) {
            throw new \RuntimeException('Program tidak ditemukan.');
        }

        $now = date('Y-m-d H:i:s');

        // Soft-delete program
        $this->db->table('extracurricular_programs')
            ->where('id', $id)
            ->update([
                'deleted_at' => $now,
                'updated_at' => $now,
                'updated_by' => $userId,
            ]);

        // Cascade: remove all active members
        $this->db->table('extracurricular_members')
            ->where('program_id', $id)
            ->where('status', self::MEMBER_ACTIVE)
            ->update(['status' => self::MEMBER_LEFT, 'leave_date' => date('Y-m-d'), 'updated_at' => $now, 'updated_by' => $userId]);

        AuditService::log('extracurricular', 'DELETE', 'ExtracurricularProgram', $id, $program, null, 'Menghapus program: ' . $program['title']);
    }

    /**
     * Delete a session permanently.
     */
    public function deleteSession(int $sessionId, int $userId): void
    {
        $session = $this->db->table('extracurricular_sessions')->where('id', $sessionId)->get()->getRowArray();
        if (! $session) {
            throw new \RuntimeException('Sesi tidak ditemukan.');
        }

        // Delete attendance records first
        $this->db->table('extracurricular_attendance')
            ->where('session_id', $sessionId)
            ->delete();

        $this->db->table('extracurricular_sessions')
            ->where('id', $sessionId)
            ->delete();

        AuditService::log('extracurricular', 'DELETE', 'ExtracurricularSession', $sessionId, $session, null, 'Menghapus sesi');
    }

    /**
     * Delete a competency permanently.
     */
    public function deleteCompetency(int $competencyId, int $userId): void
    {
        $this->db->table('extracurricular_competencies')
            ->where('id', $competencyId)
            ->update(['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => $userId]);

        AuditService::log('extracurricular', 'DEACTIVATE', 'ExtracurricularCompetency', $competencyId, null, [], 'Menonaktifkan kompetensi');
    }

    // ================================================================
    // MEMBERS
    // ================================================================

    /**
     * List active members for a program.
     */
    public function listMembers(int $programId): array
    {
        return $this->db->table('extracurricular_members m')
            ->select('m.*, s.full_name as student_name, c.name as classroom_name')
            ->join('elective_students s', 's.id = m.student_id', 'left')
            ->join('classrooms c', 'c.id = s.classroom_id', 'left')
            ->where('m.program_id', $programId)
            ->orderBy('s.full_name', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Add a member to a program.
     */
    public function addMember(array $data, int $userId): int
    {
        $exists = $this->db->table('extracurricular_members')
            ->where('program_id', $data['program_id'])
            ->where('student_id', $data['student_id'])
            ->where('status', self::MEMBER_ACTIVE)
            ->countAllResults();

        if ($exists > 0) {
            throw new \InvalidArgumentException('Siswa sudah terdaftar sebagai anggota aktif.');
        }

        $uuid = UuidService::v4();
        $now  = date('Y-m-d H:i:s');

        $this->db->table('extracurricular_members')->insert([
            'uuid'       => $uuid,
            'program_id' => $data['program_id'],
            'student_id' => $data['student_id'],
            'role'       => $data['role'] ?? self::ROLE_MEMBER,
            'join_date'  => $data['join_date'] ?? date('Y-m-d'),
            'status'     => self::MEMBER_ACTIVE,
            'notes'      => $data['notes'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $id = (int) $this->db->insertID();
        AuditService::log('extracurricular', 'ADD_MEMBER', 'ExtracurricularMember', $id, null, ['program_id' => $data['program_id']], 'Menambahkan anggota ekstrakurikuler');

        return $id;
    }

    /**
     * Remove a member from a program.
     */
    public function removeMember(int $memberId, int $userId): void
    {
        $this->db->table('extracurricular_members')
            ->where('id', $memberId)
            ->update([
                'status'     => self::MEMBER_LEFT,
                'leave_date' => date('Y-m-d'),
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $userId,
            ]);

        AuditService::log('extracurricular', 'REMOVE_MEMBER', 'ExtracurricularMember', $memberId, null, [], 'Mengeluarkan anggota ekstrakurikuler');
    }

    /**
     * Students in unit not yet members of the program.
     */
    public function availableStudents(int $unitId, int $programId): array
    {
        $memberRows = $this->db->table('extracurricular_members')
            ->select('student_id')
            ->where('program_id', $programId)
            ->where('status', self::MEMBER_ACTIVE)
            ->get()
            ->getResultArray();
        $memberStudentIds = array_map('intval', array_column($memberRows, 'student_id'));

        $builder = $this->db->table('elective_students s')
            ->select('s.id, s.full_name, c.name as classroom_name')
            ->join('classrooms c', 'c.id = s.classroom_id', 'left')
            ->where('s.unit_id', $unitId)
            ->where('s.is_active', 1)
            ->orderBy('s.full_name', 'ASC');

        if (! empty($memberStudentIds)) {
            $builder->whereNotIn('s.id', $memberStudentIds);
        }

        return $builder->get()->getResultArray();
    }

    // ================================================================
    // SESSIONS & ATTENDANCE
    // ================================================================

    /**
     * List sessions for a program.
     */
    public function listSessions(int $programId): array
    {
        return $this->db->table('extracurricular_sessions')
            ->where('program_id', $programId)
            ->orderBy('session_date', 'DESC')
            ->orderBy('start_time', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Create a session.
     */
    public function createSession(array $data, int $userId): int
    {
        $uuid = UuidService::v4();
        $now  = date('Y-m-d H:i:s');

        $this->db->table('extracurricular_sessions')->insert([
            'uuid'         => $uuid,
            'program_id'   => $data['program_id'],
            'session_date' => $data['session_date'],
            'start_time'   => $data['start_time'] ?? null,
            'end_time'     => $data['end_time'] ?? null,
            'location'     => $data['location'] ?? null,
            'topic'        => $data['topic'] ?? null,
            'notes'        => $data['notes'] ?? null,
            'status'       => self::SESSION_PLANNED,
            'created_at'   => $now,
            'updated_at'   => $now,
            'created_by'   => $userId,
            'updated_by'   => $userId,
        ]);

        $id = (int) $this->db->insertID();
        AuditService::log('extracurricular', 'CREATE', 'ExtracurricularSession', $id, null, ['date' => $data['session_date']], 'Membuat sesi ekstrakurikuler');

        return $id;
    }

    /**
     * Get attendance for a session.
     */
    public function sessionAttendance(int $sessionId): array
    {
        return $this->db->table('extracurricular_attendance a')
            ->select('a.*, m.student_id, s.full_name as student_name, c.name as classroom_name')
            ->join('extracurricular_members m', 'm.id = a.member_id', 'left')
            ->join('elective_students s', 's.id = m.student_id', 'left')
            ->join('classrooms c', 'c.id = s.classroom_id', 'left')
            ->where('a.session_id', $sessionId)
            ->orderBy('s.full_name', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Save attendance for a session (batch upsert).
     */
    public function saveAttendance(int $sessionId, array $records, int $userId): int
    {
        $count = 0;
        $now   = date('Y-m-d H:i:s');

        foreach ($records as $record) {
            $memberId = (int) ($record['member_id'] ?? 0);
            $status   = $record['status'] ?? self::ATTENDANCE_ABSENT;
            $notes    = $record['notes'] ?? null;

            if ($memberId <= 0 || ! in_array($status, self::ALLOWED_ATTENDANCE, true)) {
                continue;
            }

            $existing = $this->db->table('extracurricular_attendance')
                ->where('session_id', $sessionId)
                ->where('member_id', $memberId)
                ->get()->getRowArray();

            if ($existing) {
                $this->db->table('extracurricular_attendance')
                    ->where('id', $existing['id'])
                    ->update(['status' => $status, 'notes' => $notes, 'updated_at' => $now, 'updated_by' => $userId]);
            } else {
                $this->db->table('extracurricular_attendance')->insert([
                    'uuid'       => UuidService::v4(),
                    'session_id' => $sessionId,
                    'member_id'  => $memberId,
                    'status'     => $status,
                    'notes'      => $notes,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }
            $count++;
        }

        AuditService::log('extracurricular', 'SAVE_ATTENDANCE', 'ExtracurricularAttendance', $sessionId, null, ['count' => $count], 'Menyimpan kehadiran sesi');

        return $count;
    }

    /**
     * Attendance summary for a program.
     */
    public function attendanceSummary(int $programId): array
    {
        $totalSessions = $this->db->table('extracurricular_sessions')
            ->where('program_id', $programId)
            ->where('status', self::SESSION_COMPLETED)
            ->countAllResults();

        $totalMembers = $this->db->table('extracurricular_members')
            ->where('program_id', $programId)
            ->where('status', self::MEMBER_ACTIVE)
            ->countAllResults();

        $present = $this->db->table('extracurricular_attendance a')
            ->join('extracurricular_sessions s', 's.id = a.session_id', 'inner')
            ->where('s.program_id', $programId)
            ->where('a.status', self::ATTENDANCE_PRESENT)
            ->countAllResults();

        $totalPossible = $totalSessions * $totalMembers;

        return [
            'total_sessions' => $totalSessions,
            'total_members'  => $totalMembers,
            'present'        => $present,
            'attendance_rate' => $totalPossible > 0 ? round(($present / $totalPossible) * 100, 1) : 0,
        ];
    }

    // ================================================================
    // COMPETENCIES & ACHIEVEMENTS
    // ================================================================

    /**
     * List competencies for a program.
     */
    public function listCompetencies(int $programId): array
    {
        return $this->db->table('extracurricular_competencies')
            ->where('program_id', $programId)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('code', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Add a competency.
     */
    public function addCompetency(array $data, int $userId): int
    {
        $uuid = UuidService::v4();
        $now  = date('Y-m-d H:i:s');

        $this->db->table('extracurricular_competencies')->insert([
            'uuid'            => $uuid,
            'program_id'      => $data['program_id'],
            'code'            => $data['code'],
            'name'            => $data['name'],
            'description'     => $data['description'] ?? null,
            'assessment_type' => $data['assessment_type'] ?? self::COMPETENCY_QUALITATIVE,
            'sort_order'      => $data['sort_order'] ?? 0,
            'is_active'       => 1,
            'created_at'      => $now,
            'updated_at'      => $now,
            'created_by'      => $userId,
            'updated_by'      => $userId,
        ]);

        $id = (int) $this->db->insertID();
        AuditService::log('extracurricular', 'CREATE', 'ExtracurricularCompetency', $id, null, ['code' => $data['code']], 'Menambahkan kompetensi ekstrakurikuler');

        return $id;
    }

    /**
     * Achievements for a program with member info.
     */
    public function listAchievements(int $programId): array
    {
        return $this->db->table('extracurricular_achievements a')
            ->select('a.*, m.student_id, s.full_name as student_name, c.name as classroom_name, comp.name as competency_name, t.full_name as assessor_name')
            ->join('extracurricular_members m', 'm.id = a.member_id', 'left')
            ->join('elective_students s', 's.id = m.student_id', 'left')
            ->join('classrooms c', 'c.id = s.classroom_id', 'left')
            ->join('extracurricular_competencies comp', 'comp.id = a.competency_id', 'left')
            ->join('teachers t', 't.id = a.assessed_by', 'left')
            ->where('m.program_id', $programId)
            ->orderBy('a.achieved_date', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Record an achievement.
     */
    public function addAchievement(array $data, int $userId): int
    {
        $uuid = UuidService::v4();
        $now  = date('Y-m-d H:i:s');

        $this->db->table('extracurricular_achievements')->insert([
            'uuid'          => $uuid,
            'member_id'     => $data['member_id'],
            'competency_id' => $data['competency_id'] ?? null,
            'achieved_date' => $data['achieved_date'] ?? date('Y-m-d'),
            'level'         => $data['level'] ?? null,
            'score'         => $data['score'] ?? null,
            'remarks'       => $data['remarks'] ?? null,
            'evidence_url'  => $data['evidence_url'] ?? null,
            'assessed_by'   => $data['assessed_by'] ?? null,
            'created_at'    => $now,
            'updated_at'    => $now,
            'created_by'    => $userId,
            'updated_by'    => $userId,
        ]);

        $id = (int) $this->db->insertID();
        AuditService::log('extracurricular', 'CREATE', 'ExtracurricularAchievement', $id, null, ['member_id' => $data['member_id']], 'Mencatat pencapaian ekstrakurikuler');

        return $id;
    }

    // ================================================================
    // EVALUATIONS
    // ================================================================

    /**
     * List evaluations for a program.
     */
    public function listEvaluations(int $programId): array
    {
        return $this->db->table('extracurricular_evaluations')
            ->where('program_id', $programId)
            ->orderBy('evaluation_period', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Save an evaluation (upsert by program_id + period).
     */
    public function saveEvaluation(array $data, int $userId): int
    {
        $existing = $this->db->table('extracurricular_evaluations')
            ->where('program_id', $data['program_id'])
            ->where('evaluation_period', $data['evaluation_period'])
            ->get()->getRowArray();

        $now = date('Y-m-d H:i:s');

        $payload = [
            'input_data'       => $data['input_data'] ?? null,
            'process_data'     => $data['process_data'] ?? null,
            'output_data'      => $data['output_data'] ?? null,
            'outcome_data'     => $data['outcome_data'] ?? null,
            'findings'         => $data['findings'] ?? null,
            'recommendations'  => $data['recommendations'] ?? null,
            'overall_rating'   => $data['overall_rating'] ?? null,
            'updated_at'       => $now,
            'updated_by'       => $userId,
        ];

        if ($existing) {
            $this->db->table('extracurricular_evaluations')
                ->where('id', $existing['id'])
                ->update($payload);

            AuditService::log('extracurricular', 'UPDATE', 'ExtracurricularEvaluation', $existing['id'], null, [], 'Memperbarui evaluasi ekstrakurikuler');
            return (int) $existing['id'];
        }

        $this->db->table('extracurricular_evaluations')->insert(array_merge([
            'uuid'              => UuidService::v4(),
            'program_id'        => $data['program_id'],
            'evaluation_period' => $data['evaluation_period'],
            'created_at'        => $now,
            'created_by'        => $userId,
        ], $payload));

        $id = (int) $this->db->insertID();
        AuditService::log('extracurricular', 'CREATE', 'ExtracurricularEvaluation', $id, null, [], 'Membuat evaluasi ekstrakurikuler');

        return $id;
    }

    // ================================================================
    // DASHBOARD STATS
    // ================================================================

    public function stats(int $unitId, int $periodId): array
    {
        $programs = $this->db->table('extracurricular_programs')
            ->where('unit_id', $unitId)
            ->where('academic_period_id', $periodId)
            ->countAllResults();

        $active = $this->db->table('extracurricular_programs')
            ->where('unit_id', $unitId)
            ->where('academic_period_id', $periodId)
            ->where('status', self::STATUS_ACTIVE)
            ->countAllResults();

        $totalMembers = $this->db->table('extracurricular_members m')
            ->join('extracurricular_programs p', 'p.id = m.program_id', 'inner')
            ->where('p.unit_id', $unitId)
            ->where('p.academic_period_id', $periodId)
            ->where('m.status', self::MEMBER_ACTIVE)
            ->countAllResults();

        return [
            'total_programs' => $programs,
            'active_programs' => $active,
            'total_members'  => $totalMembers,
        ];
    }

    // ================================================================
    // QUALITATIVE REPORTING
    // ================================================================

    /**
     * Generate qualitative report for a student in a program.
     */
    public function studentReport(int $programId, int $studentId): array
    {
        $program = $this->detailProgram($programId);
        if (! $program) {
            return [];
        }

        // Member info
        $member = $this->db->table('extracurricular_members m')
            ->select('m.*, s.full_name as student_name, c.name as classroom_name')
            ->join('elective_students s', 's.id = m.student_id', 'left')
            ->join('classrooms c', 'c.id = s.classroom_id', 'left')
            ->where('m.program_id', $programId)
            ->where('m.student_id', $studentId)
            ->get()->getRowArray();

        if (! $member) {
            return [];
        }

        // Attendance stats
        $totalSessions = $this->db->table('extracurricular_sessions')
            ->where('program_id', $programId)
            ->where('status', self::SESSION_COMPLETED)
            ->countAllResults();

        $attendanceRecords = $this->db->table('extracurricular_attendance')
            ->where('member_id', $member['id'])
            ->get()->getResultArray();

        $presentCount = 0;
        $lateCount = 0;
        $absentCount = 0;
        $excusedCount = 0;
        foreach ($attendanceRecords as $a) {
            switch ($a['status']) {
                case self::ATTENDANCE_PRESENT: $presentCount++; break;
                case self::ATTENDANCE_LATE:    $lateCount++; break;
                case self::ATTENDANCE_ABSENT:  $absentCount++; break;
                case self::ATTENDANCE_EXCUSED: $excusedCount++; break;
            }
        }

        $attendanceRate = $totalSessions > 0 ? round(($presentCount + $lateCount) / $totalSessions * 100, 1) : 0;

        // Achievements
        $achievements = $this->db->table('extracurricular_achievements a')
            ->select('a.*, comp.name as competency_name')
            ->join('extracurricular_competencies comp', 'comp.id = a.competency_id', 'left')
            ->where('a.member_id', $member['id'])
            ->orderBy('a.achieved_date', 'DESC')
            ->get()->getResultArray();

        // Competency coverage
        $totalCompetencies = $this->db->table('extracurricular_competencies')
            ->where('program_id', $programId)
            ->where('is_active', 1)
            ->countAllResults();

        $achievedCompetencies = count(array_unique(array_column($achievements, 'competency_id')));

        return [
            'program'              => $program,
            'member'               => $member,
            'total_sessions'       => $totalSessions,
            'present_count'        => $presentCount,
            'late_count'           => $lateCount,
            'absent_count'         => $absentCount,
            'excused_count'        => $excusedCount,
            'attendance_rate'      => $attendanceRate,
            'achievements'         => $achievements,
            'total_competencies'   => $totalCompetencies,
            'achieved_competencies' => $achievedCompetencies,
            'competency_coverage'  => $totalCompetencies > 0 ? round($achievedCompetencies / $totalCompetencies * 100, 1) : 0,
        ];
    }

    /**
     * List all student reports for a program.
     */
    public function listStudentReports(int $programId): array
    {
        $members = $this->listMembers($programId);
        $reports = [];

        foreach ($members as $m) {
            if ($m['status'] !== self::MEMBER_ACTIVE) {
                continue;
            }
            $report = $this->studentReport($programId, (int) $m['student_id']);
            if (! empty($report)) {
                $reports[] = $report;
            }
        }

        return $reports;
    }

    // ================================================================
    // SCHEDULE CONFLICT VALIDATION
    // ================================================================

    /**
     * Check if an extracurricular session conflicts with the unit's schedule.
     * Returns list of conflicts (empty = no conflict).
     */
    public function checkScheduleConflict(int $unitId, string $sessionDate, ?string $startTime, ?string $endTime): array
    {
        if (! $startTime || ! $endTime) {
            return []; // No time specified, cannot check
        }

        $dayOfWeek = (int) date('w', strtotime($sessionDate));
        // Convert PHP Sunday=0 to MySQL Monday=1 convention
        $mysqlDay = $dayOfWeek === 0 ? 7 : $dayOfWeek;

        // Find the active schedule version for this unit
        $version = $this->db->table('schedule_versions')
            ->where('school_unit_id', $unitId)
            ->whereIn('workflow_status', ['PUBLISHED', 'LOCKED'])
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();

        if (! $version) {
            return []; // No published schedule, no conflict possible
        }

        // Find the day_id for this day of week
        $day = $this->db->table('schedule_days')
            ->where('school_unit_id', $unitId)
            ->where('day_of_week', $mysqlDay)
            ->get()->getRowArray();

        if (! $day) {
            return []; // No school day for this day
        }

        // Find overlapping day_slots
        $overlappingSlots = $this->db->table('schedule_day_slots')
            ->where('schedule_version_id', $version['id'])
            ->where('day_id', $day['id'])
            ->where('start_time <', $endTime)
            ->where('end_time >', $startTime)
            ->get()->getResultArray();

        if (empty($overlappingSlots)) {
            return [];
        }

        $slotIds = array_column($overlappingSlots, 'id');

        // Find schedule entries (actual classes) in those slots
        $conflicts = $this->db->table('schedule_entries se')
            ->select('se.*, sub.name as subject_name, t.full_name as teacher_name, cr.name as classroom_name, sds.label as slot_label, sds.start_time as slot_start, sds.end_time as slot_end')
            ->join('schedule_day_slots sds', 'sds.id = se.day_slot_id', 'inner')
            ->join('classrooms cr', 'cr.id = se.classroom_id', 'left')
            ->join('teachers t', 't.id = se.teacher_id', 'left')
            ->join('subjects sub', 'sub.id = se.subject_id', 'left')
            ->where('se.schedule_version_id', $version['id'])
            ->whereIn('se.day_slot_id', $slotIds)
            ->get()->getResultArray();

        return $conflicts;
    }

    // ================================================================
    // CAPACITY VALIDATION
    // ================================================================

    /**
     * Check if a program has capacity for a new member.
     */
    public function hasCapacity(int $programId): bool
    {
        $program = $this->detailProgram($programId);
        if (! $program) {
            return false;
        }

        if (empty($program['max_members'])) {
            return true; // No limit
        }

        $currentMembers = $this->db->table('extracurricular_members')
            ->where('program_id', $programId)
            ->where('status', self::MEMBER_ACTIVE)
            ->countAllResults();

        return $currentMembers < (int) $program['max_members'];
    }

    // ================================================================
    // SMART ENHANCEMENTS: NARRATIVE, ANALYTICS & IPOO HEALTH
    // ================================================================

    /**
     * Generate automated Kurikulum Merdeka / K13 qualitative report card narrative for a student.
     */
    public function generateStudentNarrative(int $programId, int $studentId): string
    {
        $program = $this->detailProgram($programId);
        if (! $program) {
            return 'Program ekstrakurikuler tidak ditemukan.';
        }

        $student = $this->db->table('elective_students')->where('id', $studentId)->get()->getRowArray();
        $studentName = $student['full_name'] ?? 'Peserta Didik';

        $member = $this->db->table('extracurricular_members')
            ->where('program_id', $programId)
            ->where('student_id', $studentId)
            ->get()->getRowArray();

        if (! $member) {
            return "Ananda {$studentName} belum terdaftar aktif dalam kegiatan ekstrakurikuler {$program['title']}.";
        }

        // Attendance rate
        $totalSessions = (int) $this->db->table('extracurricular_sessions')->where('program_id', $programId)->countAllResults();
        $presentCount = (int) $this->db->table('extracurricular_attendance ea')
            ->join('extracurricular_sessions es', 'es.id = ea.session_id', 'inner')
            ->where('es.program_id', $programId)
            ->where('ea.member_id', $member['id'])
            ->where('ea.status', self::ATTENDANCE_PRESENT)
            ->countAllResults();

        $attPercent = $totalSessions > 0 ? round(($presentCount / $totalSessions) * 100) : 100;

        // Achievements & Competencies
        $achievements = $this->db->table('extracurricular_achievements ea')
            ->select('ea.*, ec.name as competency_name')
            ->join('extracurricular_competencies ec', 'ec.id = ea.competency_id', 'left')
            ->where('ea.member_id', $member['id'])
            ->orderBy('ea.achieved_date', 'DESC')
            ->get()->getResultArray();

        $compCount = count($achievements);
        $role = $member['role'] ?? self::ROLE_MEMBER;
        $roleLabel = match ($role) {
            self::ROLE_LEADER => 'sebagai ketua/pemimpin regu',
            self::ROLE_COACH  => 'sebagai asisten pembina',
            default           => 'sebagai anggota aktif',
        };

        // Attendance sentence
        if ($attPercent >= 90) {
            $attDesc = "menunjukkan dedikasi dan kedisiplinan yang sangat tinggi (kehadiran {$attPercent}%)";
        } elseif ($attPercent >= 75) {
            $attDesc = "menunjukkan partisipasi dan keaktifan yang baik (kehadiran {$attPercent}%)";
        } else {
            $attDesc = "mengikuti kegiatan secara cukup aktif (kehadiran {$attPercent}%)";
        }

        // Achievement & skills sentence
        $skillDesc = '';
        if (! empty($achievements)) {
            $topAchievements = array_slice($achievements, 0, 2);
            $names = [];
            foreach ($topAchievements as $ach) {
                if (! empty($ach['remarks'])) {
                    $names[] = $ach['remarks'];
                } elseif (! empty($ach['competency_name'])) {
                    $names[] = 'penguasaan kompetensi ' . $ach['competency_name'];
                }
            }
            if (! empty($names)) {
                $skillDesc = ' Berhasil meraih pencapaian dalam ' . implode(' serta ', $names) . '.';
            }
        } else {
            $skillDesc = ' Telah mempraktikkan keterampilan dasar dan kerja sama tim secara positif.';
        }

        // Closing recommendation
        $recommendation = match ($program['category']) {
            'SPORTS'            => ' Disarankan untuk terus mempertahankan sportivitas, stamina, dan teknik bertanding.',
            'ARTS'              => ' Disarankan untuk terus mengasah kepekaan estetika, ekspresi diri, dan daya cipta seni.',
            'SCOUT'             => ' Disarankan untuk terus mengamalkan Dasa Darma dan mengasah keterampilan kepanduan (Pathfinder).',
            'ACADEMIC_OLYMPIAD' => ' Disarankan untuk terus memperdalam eksplorasi konsep dan kemampuan bernalar tingkat tinggi.',
            default             => ' Disarankan untuk terus konsisten mengembangkan minat, bakat, dan karakter kepemimpinan.',
        };

        return "Ananda {$studentName} {$roleLabel} dalam kegiatan {$program['title']} {$attDesc}.{$skillDesc} {$recommendation}";
    }

    /**
     * Calculate comprehensive session attendance trends and stats.
     */
    public function calculateAttendanceStats(int $programId): array
    {
        $sessions = $this->db->table('extracurricular_sessions')
            ->where('program_id', $programId)
            ->orderBy('session_date', 'ASC')
            ->get()->getResultArray();

        $activeMembersCount = (int) $this->db->table('extracurricular_members')
            ->where('program_id', $programId)
            ->where('status', self::MEMBER_ACTIVE)
            ->countAllResults();

        $sessionTrends = [];
        $totalPresentAll = 0;
        $totalRecordsAll = 0;

        foreach ($sessions as $s) {
            $records = $this->db->table('extracurricular_attendance')
                ->where('session_id', $s['id'])
                ->get()->getResultArray();

            $pCount = 0;
            $eCount = 0;
            $aCount = 0;
            $lCount = 0;

            foreach ($records as $r) {
                match ($r['status']) {
                    self::ATTENDANCE_PRESENT => $pCount++,
                    self::ATTENDANCE_EXCUSED => $eCount++,
                    self::ATTENDANCE_LATE    => $lCount++,
                    default                  => $aCount++,
                };
            }

            $recTotal = count($records);
            $pct = $recTotal > 0 ? round(($pCount / $recTotal) * 100, 1) : 0;

            $totalPresentAll += $pCount;
            $totalRecordsAll += $recTotal;

            $sessionTrends[] = [
                'session_id'   => (int) $s['id'],
                'date'         => $s['session_date'],
                'topic'        => $s['topic'] ?? ('Sesi ' . $s['session_date']),
                'present'      => $pCount,
                'excused'      => $eCount,
                'late'         => $lCount,
                'absent'       => $aCount,
                'total'        => $recTotal,
                'percentage'   => $pct,
            ];
        }

        $overallAverage = $totalRecordsAll > 0 ? round(($totalPresentAll / $totalRecordsAll) * 100, 1) : 0;

        return [
            'total_sessions'       => count($sessions),
            'active_members'       => $activeMembersCount,
            'overall_attendance_pct'=> $overallAverage,
            'session_trends'       => $sessionTrends,
        ];
    }

    /**
     * Calculate 4-Pillar IPOO (Input-Process-Output-Outcome) Program Quality Health Score.
     */
    public function calculateIpooHealth(int $programId): array
    {
        $program = $this->detailProgram($programId);
        if (! $program) {
            return [
                'overall_score'   => 0,
                'overall_percent' => 0,
                'status'          => ['label' => 'TIDAK DITEMUKAN', 'class' => 'bg-secondary text-white'],
                'aspects'         => [],
            ];
        }

        // 1. INPUT Score (Coach assigned, funding, location)
        $inputPoints = 0;
        if (! empty($program['coach_teacher_id'])) $inputPoints += 2;
        if (! empty($program['location'])) $inputPoints += 1.5;
        if (! empty($program['rationale']) && ! empty($program['objective'])) $inputPoints += 1.5;
        $inputRating = round(max(1.0, min(5.0, $inputPoints)), 1);

        // 2. PROCESS Score (Session cadence & Attendance)
        $attStats = $this->calculateAttendanceStats($programId);
        $processScore = 1.0;
        if ($attStats['total_sessions'] >= 8) {
            $processScore += 2.0;
        } elseif ($attStats['total_sessions'] >= 4) {
            $processScore += 1.5;
        } elseif ($attStats['total_sessions'] >= 1) {
            $processScore += 1.0;
        }
        $processScore += ($attStats['overall_attendance_pct'] / 100) * 2.0;
        $processRating = round(max(1.0, min(5.0, $processScore)), 1);

        // 3. OUTPUT Score (Competencies defined & Achievements logged)
        $compCount = (int) $this->db->table('extracurricular_competencies')->where('program_id', $programId)->countAllResults();
        $achCount = (int) $this->db->table('extracurricular_achievements ea')
            ->join('extracurricular_members em', 'em.id = ea.member_id', 'inner')
            ->where('em.program_id', $programId)
            ->countAllResults();

        $outputScore = 1.0;
        if ($compCount >= 3) $outputScore += 2.0;
        elseif ($compCount >= 1) $outputScore += 1.0;

        if ($achCount >= 5) $outputScore += 2.0;
        elseif ($achCount >= 1) $outputScore += 1.0;
        $outputRating = round(max(1.0, min(5.0, $outputScore)), 1);

        // 4. OUTCOME Score (Evaluations recorded)
        $evals = $this->listEvaluations($programId);
        $outcomeScore = 3.0;
        if (! empty($evals)) {
            $sum = 0;
            foreach ($evals as $ev) {
                $sum += match ($ev['overall_rating'] ?? 'GOOD') {
                    'EXCELLENT'        => 5.0,
                    'GOOD'             => 4.0,
                    'SATISFACTORY'     => 3.0,
                    default            => 2.0,
                };
            }
            $outcomeScore = $sum / count($evals);
        }
        $outcomeRating = round(max(1.0, min(5.0, $outcomeScore)), 1);

        // Weighted Overall: Input (20%), Process (30%), Output (25%), Outcome (25%)
        $overallScore = round(($inputRating * 0.20) + ($processRating * 0.30) + ($outputRating * 0.25) + ($outcomeRating * 0.25), 1);
        $overallPercent = round(($overallScore / 5.0) * 100);

        $status = match (true) {
            $overallScore >= 4.2 => ['label' => 'SANGAT SEHAT', 'class' => 'bg-success text-white'],
            $overallScore >= 3.5 => ['label' => 'BAIK & EFISIEN', 'class' => 'bg-primary text-white'],
            $overallScore >= 2.5 => ['label' => 'CUKUP', 'class' => 'bg-warning text-dark'],
            default              => ['label' => 'PERLU OPTIMALISASI', 'class' => 'bg-danger text-white'],
        };

        return [
            'overall_score'   => $overallScore,
            'overall_percent' => $overallPercent,
            'status'          => $status,
            'aspects'         => [
                'INPUT'   => ['rating' => $inputRating, 'percent' => round(($inputRating / 5) * 100), 'label' => 'Kesiapan Sumber Daya & Pembina'],
                'PROCESS' => ['rating' => $processRating, 'percent' => round(($processRating / 5) * 100), 'label' => 'Konsistensi Sesi & Presensi (' . $attStats['overall_attendance_pct'] . '%)'],
                'OUTPUT'  => ['rating' => $outputRating, 'percent' => round(($outputRating / 5) * 100), 'label' => 'Kompetensi & Prestasi (' . $achCount . ' Capaian)'],
                'OUTCOME' => ['rating' => $outcomeRating, 'percent' => round(($outcomeRating / 5) * 100), 'label' => 'Dampak & Evaluasi Mutu'],
            ],
            'attendance_stats' => $attStats,
        ];
    }
}

