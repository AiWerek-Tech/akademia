<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use App\Services\ExtracurricularService;

/**
 * Phase 9 — Reporting & Portfolio Service.
 *
 * Generates semester report snapshots, manages narratives,
 * and handles student portfolio collections.
 */
class ReportingService
{
    // Snapshot status
    public const STATUS_DRAFT     = 'DRAFT';
    public const STATUS_LOCKED    = 'LOCKED';
    public const STATUS_PUBLISHED = 'PUBLISHED';

    public const ALLOWED_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_LOCKED,
        self::STATUS_PUBLISHED,
    ];

    // Snapshot type
    public const TYPE_SEMESTER = 'SEMESTER';
    public const TYPE_ANNUAL   = 'ANNUAL';

    // Narrative source
    public const SOURCE_TEACHER = 'TEACHER';
    public const SOURCE_AI      = 'AI';

    // Narrative type
    public const NARRATIVE_SUBJECT  = 'SUBJECT';
    public const NARRATIVE_GENERAL  = 'GENERAL';
    public const NARRATIVE_COCURRIC = 'COCURRICULAR';
    public const NARRATIVE_EXTRACUR = 'EXTRACURRICULAR';

    // Portfolio categories
    public const PORTFOLIO_BEST_WORK     = 'BEST_WORK';
    public const PORTFOLIO_GROWTH        = 'GROWTH';
    public const PORTFOLIO_PROJECT       = 'PROJECT';
    public const PORTFOLIO_COCURRICULAR  = 'COCURRICULAR';
    public const PORTFOLIO_EXTRACURRICULAR = 'EXTRACURRICULAR';
    public const PORTFOLIO_REFLECTION    = 'REFLECTION';

    public const PORTFOLIO_CATEGORIES = [
        self::PORTFOLIO_BEST_WORK,
        self::PORTFOLIO_GROWTH,
        self::PORTFOLIO_PROJECT,
        self::PORTFOLIO_COCURRICULAR,
        self::PORTFOLIO_EXTRACURRICULAR,
        self::PORTFOLIO_REFLECTION,
    ];

    // Predicate mapping
    public const PREDICATE_MAP = [
        'A' => ['min' => 90, 'max' => 100],
        'B' => ['min' => 75, 'max' => 89.99],
        'C' => ['min' => 50, 'max' => 74.99],
        'D' => ['min' => 0,  'max' => 49.99],
    ];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?: Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    // ================================================================
    // REPORTING POLICIES
    // ================================================================

    public function listPolicies(int $unitId, int $periodId): array
    {
        return $this->db->table('reporting_policies rp')
            ->select('rp.*, s.name as subject_name')
            ->join('subjects s', 's.id = rp.subject_id', 'left')
            ->where('rp.unit_id', $unitId)
            ->where('rp.academic_period_id', $periodId)
            ->orderBy('rp.policy_name', 'ASC')
            ->get()->getResultArray();
    }

    public function createPolicy(array $data, int $userId): int
    {
        $uuid = UuidService::v4();
        $now  = date('Y-m-d H:i:s');

        $this->db->table('reporting_policies')->insert([
            'uuid'              => $uuid,
            'unit_id'           => $data['unit_id'],
            'academic_period_id' => $data['academic_period_id'],
            'subject_id'        => $data['subject_id'] ?? null,
            'policy_name'       => $data['name'],
            'calculation_method' => $data['method'] ?? 'AVERAGE',
            'config_json'       => $data['config_json'] ?? null,
            'is_active'         => 1,
            'version'           => 1,
            'created_at'        => $now,
            'updated_at'        => $now,
            'created_by'        => $userId,
            'updated_by'        => $userId,
        ]);

        return (int) $this->db->insertID();
    }

    // ================================================================
    // REPORT SNAPSHOTS
    // ================================================================

    public function listSnapshots(int $unitId, int $periodId, array $filters = []): array
    {
        $builder = $this->db->table('report_snapshots rs')
            ->select('rs.*, es.full_name as student_name, c.name as classroom_name')
            ->join('elective_students es', 'es.id = rs.student_id', 'left')
            ->join('classrooms c', 'c.id = es.classroom_id', 'left')
            ->where('rs.unit_id', $unitId)
            ->where('rs.academic_period_id', $periodId)
            ->orderBy('es.full_name', 'ASC');

        if (! empty($filters['status'])) {
            $builder->where('rs.status', $filters['status']);
        }

        return $builder->get()->getResultArray();
    }

    public function snapshotDetail(int $snapshotId): ?array
    {
        $snapshot = $this->db->table('report_snapshots rs')
            ->select('rs.*, es.full_name as student_name, es.student_number, es.current_grade, c.name as classroom_name, c.id as classroom_id, su.name as unit_name, su.code as unit_code, ap.name as period_name, ap.semester_number, ay.name as academic_year_name')
            ->join('elective_students es', 'es.id = rs.student_id', 'left')
            ->join('classrooms c', 'c.id = es.classroom_id', 'left')
            ->join('school_units su', 'su.id = rs.unit_id', 'left')
            ->join('academic_periods ap', 'ap.id = rs.academic_period_id', 'left')
            ->join('academic_years ay', 'ay.id = ap.academic_year_id', 'left')
            ->where('rs.id', $snapshotId)
            ->get()->getRowArray();

        if (! $snapshot) {
            return null;
        }

        $studentId = (int) $snapshot['student_id'];
        $periodId  = (int) $snapshot['academic_period_id'];
        $unitId    = (int) $snapshot['unit_id'];

        // Load subject results with narratives
        $snapshot['subjects'] = $this->db->table('report_subject_results rsr')
            ->select('rsr.*, s.name as subject_name, s.code as subject_code, s.category as subject_category, t.full_name as teacher_name')
            ->join('subjects s', 's.id = rsr.subject_id', 'left')
            ->join('teachers t', 't.id = rsr.teacher_id', 'left')
            ->where('rsr.snapshot_id', $snapshotId)
            ->orderBy('s.category', 'ASC')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        foreach ($snapshot['subjects'] as &$subject) {
            $subject['narratives'] = $this->db->table('report_narratives')
                ->where('subject_result_id', $subject['id'])
                ->orderBy('narrative_type', 'ASC')
                ->get()->getResultArray();

            if (empty($subject['narratives'])) {
                $subject['auto_narrative'] = $this->generateDraftNarrative($subject);
            } else {
                $subject['auto_narrative'] = $subject['narratives'][0]['content'] ?? '';
            }
        }
        unset($subject);

        // Load general narratives
        $snapshot['general_narratives'] = $this->db->table('report_narratives rn')
            ->select('rn.*, rsr.subject_id')
            ->join('report_subject_results rsr', 'rsr.id = rn.subject_result_id', 'inner')
            ->where('rsr.snapshot_id', $snapshotId)
            ->where('rn.narrative_type', 'GENERAL')
            ->get()->getResultArray();

        // Portfolio items
        $snapshot['portfolio'] = $this->db->table('portfolio_collections')
            ->where('student_id', $studentId)
            ->where('academic_period_id', $periodId)
            ->orderBy('is_highlighted', 'DESC')
            ->orderBy('category', 'ASC')
            ->orderBy('created_at', 'DESC')
            ->get()->getResultArray();

        // Phase 8: Extracurricular Integration
        $extService = new ExtracurricularService($this->db);
        $snapshot['extracurriculars'] = $extService->getStudentExtracurricularReportCardData($studentId, $periodId);

        // Phase 7: Cocurricular / P5 Integration
        $cocurricularProjects = $this->db->table('cocurricular_student_results csr')
            ->select('csr.*, cp.title as program_title, cp.theme as program_theme, gpd.name as dimension_name, gpd.code as dimension_code')
            ->join('cocurricular_programs cp', 'cp.id = csr.program_id', 'inner')
            ->join('graduate_profile_dimensions gpd', 'gpd.id = csr.dimension_id', 'left')
            ->where('csr.student_id', $studentId)
            ->where('cp.academic_period_id', $periodId)
            ->get()->getResultArray();
        $snapshot['cocurriculars'] = $cocurricularProjects;

        // Attendance Recap
        $attRecords = $this->db->table('student_attendances sa')
            ->join('attendance_sessions asess', 'asess.id = sa.session_id', 'inner')
            ->where('sa.student_id', $studentId)
            ->where('asess.academic_period_id', $periodId)
            ->get()->getResultArray();

        $hadir = 0; $sakit = 0; $izin = 0; $alpa = 0; $terlambat = 0;
        foreach ($attRecords as $ar) {
            $st = strtoupper($ar['status'] ?? 'HADIR');
            if ($st === 'HADIR' || $st === 'PRESENT') $hadir++;
            elseif ($st === 'SAKIT' || $st === 'SICK') $sakit++;
            elseif ($st === 'IZIN' || $st === 'EXCUSED') $izin++;
            elseif ($st === 'TERLAMBAT' || $st === 'LATE') $terlambat++;
            else $alpa++;
        }
        $snapshot['attendance_summary'] = [
            'hadir'     => $hadir,
            'sakit'     => $sakit,
            'izin'      => $izin,
            'alpa'      => $alpa,
            'terlambat' => $terlambat,
            'total'     => count($attRecords),
        ];

        // Homeroom teacher (Wali Kelas) & Headmaster
        $homeroomTeacher = null;
        if (! empty($snapshot['classroom_id'])) {
            $homeroomTeacher = $this->db->table('teachers t')
                ->join('classrooms c', 'c.homeroom_teacher_id = t.id', 'inner')
                ->where('c.id', $snapshot['classroom_id'])
                ->select('t.full_name, t.nip')
                ->get()->getRowArray();
        }
        $snapshot['homeroom_teacher'] = $homeroomTeacher['full_name'] ?? 'Wali Kelas';
        $snapshot['headmaster'] = 'Kepala Satuan Pendidikan';

        return $snapshot;
    }

    /**
     * Generate a report snapshot for one student.
     * Pulls data from teaching assignments (subject list), validated summative
     * results, mastery records, and attendance — mirroring the blueprint flow
     * Teaching → Evidence → Assessment → TP Attainment → Summative → Rapor.
     */
    public function generateSnapshot(int $unitId, int $periodId, int $studentId, int $userId): int
    {
        $student = $this->db->table('elective_students')
            ->where('id', $studentId)
            ->where('unit_id', $unitId)
            ->get()->getRowArray();
        if (! $student) {
            throw new \InvalidArgumentException('Siswa tidak ditemukan pada unit ini.');
        }
        $classroomId = (int) ($student['classroom_id'] ?? 0);

        // Upsert snapshot
        $existing = $this->db->table('report_snapshots')
            ->where('unit_id', $unitId)
            ->where('academic_period_id', $periodId)
            ->where('student_id', $studentId)
            ->where('snapshot_type', self::TYPE_SEMESTER)
            ->get()->getRowArray();

        $now = date('Y-m-d H:i:s');

        if ($existing) {
            $snapshotId = (int) $existing['id'];
            $this->db->table('report_snapshots')
                ->where('id', $snapshotId)
                ->update([
                    'status'     => self::STATUS_DRAFT,
                    'updated_at' => $now,
                    'updated_by' => $userId,
                ]);
        } else {
            $this->db->table('report_snapshots')->insert([
                'uuid'               => UuidService::v4(),
                'unit_id'            => $unitId,
                'academic_period_id' => $periodId,
                'student_id'         => $studentId,
                'snapshot_type'      => self::TYPE_SEMESTER,
                'status'             => self::STATUS_DRAFT,
                'created_at'         => $now,
                'updated_at'         => $now,
                'created_by'         => $userId,
                'updated_by'         => $userId,
            ]);
            $snapshotId = (int) $this->db->insertID();
        }

        // Delete old subject results for fresh generation
        $this->db->table('report_subject_results')
            ->where('snapshot_id', $snapshotId)
            ->delete();

        // Subjects & teachers from teaching assignments (this student's classroom)
        $assignments = $this->db->table('teaching_assignments ta')
            ->select('ta.subject_id, ta.teacher_id, ta.assignment_role')
            ->where('ta.unit_id', $unitId)
            ->where('ta.academic_period_id', $periodId)
            ->where('ta.status', 'ACTIVE')
            ->where('ta.classroom_id', $classroomId)
            ->orderBy('ta.id', 'ASC')
            ->get()->getResultArray();

        $teacherBySubject = [];
        foreach ($assignments as $a) {
            $sid = (int) $a['subject_id'];
            if ($sid <= 0) {
                continue;
            }
            // Prefer the primary teacher, keep the first one otherwise
            if (! isset($teacherBySubject[$sid]) || $a['assignment_role'] === 'PRIMARY') {
                $teacherBySubject[$sid] = (int) ($a['teacher_id'] ?? 0);
            }
        }

        $subjectIds = array_keys($teacherBySubject);

        // Fallback: subjects made available to the unit (e.g. student has no classroom mapping)
        if ($subjectIds === []) {
            $available = $this->db->table('subject_unit_availability sua')
                ->select('sua.subject_id')
                ->where('sua.unit_id', $unitId)
                ->where('sua.is_available', 1)
                ->get()->getResultArray();
            foreach ($available as $av) {
                $subjectIds[] = (int) $av['subject_id'];
            }
        }

        foreach ($subjectIds as $subjectId) {
            // Validated summative result preferred; fall back to the latest record
            $summative = $this->db->table('summative_results sr')
                ->where('sr.student_id', $studentId)
                ->where('sr.unit_id', $unitId)
                ->where('sr.academic_period_id', $periodId)
                ->where('sr.subject_id', $subjectId)
                ->orderBy('CASE WHEN sr.status = ' . $this->db->escape('VALIDATED') . ' THEN 0 ELSE 1 END', 'ASC')
                ->orderBy('sr.id', 'DESC')
                ->get()->getRowArray();

            $finalScore = $summative ? (($summative['raw_score'] ?? null) !== null ? (float) $summative['raw_score'] : null) : null;
            $predicate  = $summative ? ($summative['grade_label'] ?? null) : null;

            // Auto-calculate predicate if not set
            if ($finalScore !== null && ! $predicate) {
                $predicate = $this->scoreToPredicate($finalScore);
            }

            // Mastery & TP coverage for this subject
            [$masteryPct, $tpCoveragePct, $totalObjectives, $masteredCount] = $this->subjectMasteryStats($studentId, $unitId, $subjectId);

            // Attendance rate for this subject
            $attendancePct = $this->subjectAttendancePct($studentId, $classroomId, $unitId, $periodId, $subjectId);

            $this->db->table('report_subject_results')->insert([
                'uuid'             => UuidService::v4(),
                'snapshot_id'      => $snapshotId,
                'subject_id'       => $subjectId,
                'teacher_id'       => ! empty($teacherBySubject[$subjectId]) ? $teacherBySubject[$subjectId] : null,
                'final_score'      => $finalScore,
                'final_predicate'  => $predicate,
                'tp_coverage_pct'  => $tpCoveragePct,
                'mastery_pct'      => $masteryPct,
                'attendance_pct'   => $attendancePct,
                'detail_json'      => json_encode([
                    'total_objectives' => $totalObjectives,
                    'mastered_count'   => $masteredCount,
                    'summative_status' => $summative['status'] ?? null,
                ]),
                'created_at'       => $now,
                'updated_at'       => $now,
                'created_by'       => $userId,
                'updated_by'       => $userId,
            ]);
        }

        AuditService::log('reporting', 'GENERATE', 'ReportSnapshot', $snapshotId, null, ['student_id' => $studentId], 'Generate laporan semester');

        return $snapshotId;
    }

    /**
     * Mastery & TP coverage per subject for one student.
     * Mastery records point to learning objectives; objectives are mapped to a
     * subject through the assessments that cover them.
     *
     * @return array{0: ?float, 1: ?float, 2: int, 3: int}
     */
    private function subjectMasteryStats(int $studentId, int $unitId, int $subjectId): array
    {
        $rows = $this->db->table('mastery_records mr')
            ->select('mr.learning_objective_id, mr.result')
            ->join('assessment_objectives ao', 'ao.learning_objective_id = mr.learning_objective_id')
            ->join('assessments a', 'a.id = ao.assessment_id')
            ->where('mr.student_id', $studentId)
            ->where('a.unit_id', $unitId)
            ->where('a.subject_id', $subjectId)
            ->orderBy('mr.id', 'DESC')
            ->get()->getResultArray();

        // Latest result per objective
        $latestByObjective = [];
        foreach ($rows as $r) {
            $oid = (int) $r['learning_objective_id'];
            if (! isset($latestByObjective[$oid])) {
                $latestByObjective[$oid] = (string) $r['result'];
            }
        }

        $masteredCount = 0;
        foreach ($latestByObjective as $result) {
            if (in_array($result, ['ACHIEVED', 'ADVANCED'], true)) {
                $masteredCount++;
            }
        }
        $masteryPct = $latestByObjective !== [] ? round($masteredCount / count($latestByObjective) * 100, 1) : null;

        // Total objectives published/closed for this subject
        $totalRows = $this->db->table('assessment_objectives ao')
            ->select('ao.learning_objective_id')
            ->distinct()
            ->join('assessments a', 'a.id = ao.assessment_id')
            ->where('a.unit_id', $unitId)
            ->where('a.subject_id', $subjectId)
            ->whereIn('a.status', ['PUBLISHED', 'CLOSED'])
            ->get()->getResultArray();
        $totalObj = count($totalRows);

        $tpCoveragePct = $totalObj > 0 ? round(count($latestByObjective) / $totalObj * 100, 1) : null;

        return [$masteryPct, $tpCoveragePct, $totalObj, $masteredCount];
    }

    /**
     * Attendance rate for a student in one subject/classroom/period.
     * Present = HADIR + TERLAMBAT + DISPENSASI (consistent with AttendanceService).
     */
    private function subjectAttendancePct(int $studentId, int $classroomId, int $unitId, int $periodId, int $subjectId): ?float
    {
        if ($classroomId <= 0) {
            return null;
        }

        $total = (int) $this->db->table('student_attendances sa')
            ->join('attendance_sessions s', 's.id = sa.session_id')
            ->where('sa.student_id', $studentId)
            ->where('s.unit_id', $unitId)
            ->where('s.academic_period_id', $periodId)
            ->where('s.classroom_id', $classroomId)
            ->where('s.subject_id', $subjectId)
            ->countAllResults();

        if ($total === 0) {
            return null;
        }

        $present = (int) $this->db->table('student_attendances sa')
            ->join('attendance_sessions s', 's.id = sa.session_id')
            ->where('sa.student_id', $studentId)
            ->where('s.unit_id', $unitId)
            ->where('s.academic_period_id', $periodId)
            ->where('s.classroom_id', $classroomId)
            ->where('s.subject_id', $subjectId)
            ->whereIn('sa.status', ['HADIR', 'TERLAMBAT', 'DISPENSASI'])
            ->countAllResults();

        return round($present / $total * 100, 1);
    }

    /**
     * Lock a snapshot (prevent edits).
     */
    public function lockSnapshot(int $snapshotId, int $userId): void
    {
        $this->db->table('report_snapshots')
            ->where('id', $snapshotId)
            ->update([
                'status'     => self::STATUS_LOCKED,
                'locked_by'  => $userId,
                'locked_at'  => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $userId,
            ]);

        AuditService::log('reporting', 'LOCK', 'ReportSnapshot', $snapshotId, null, [], 'Mengunci laporan');
    }

    /**
     * Publish a snapshot. Per blueprint §10, guru wajib mereview (menyetujui)
     * narasi semua mata pelajaran sebelum laporan diterbitkan.
     */
    public function publishSnapshot(int $snapshotId, int $userId): void
    {
        $snapshot = $this->db->table('report_snapshots')
            ->where('id', $snapshotId)
            ->get()->getRowArray();
        if (! $snapshot) {
            throw new \RuntimeException('Snapshot laporan tidak ditemukan.');
        }

        $subjectResults = $this->db->table('report_subject_results')
            ->where('snapshot_id', $snapshotId)
            ->get()->getResultArray();

        if ($subjectResults !== []) {
            $srIds = array_map(static fn ($sr): int => (int) $sr['id'], $subjectResults);
            $approved = $this->db->table('report_narratives')
                ->select('subject_result_id')
                ->whereIn('subject_result_id', $srIds)
                ->where('status', 'APPROVED')
                ->get()->getResultArray();
            $approvedSr = array_map(static fn ($n): int => (int) $n['subject_result_id'], $approved);

            $unreviewed = array_values(array_filter($srIds, static fn ($id): bool => ! in_array($id, $approvedSr, true)));
            if ($unreviewed !== []) {
                $count = count($unreviewed);
                throw new \RuntimeException("Terdapat {$count} mata pelajaran yang narasinya belum direview. Narasi wajib disetujui sebelum laporan diterbitkan.");
            }
        }

        $this->db->table('report_snapshots')
            ->where('id', $snapshotId)
            ->update([
                'status'       => self::STATUS_PUBLISHED,
                'published_at' => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
                'updated_by'   => $userId,
            ]);

        AuditService::log('reporting', 'PUBLISH', 'ReportSnapshot', $snapshotId, null, [], 'Menerbitkan laporan');
    }

    // ================================================================
    // NARRATIVES
    // ================================================================

    public function saveNarrative(int $subjectResultId, string $type, string $content, string $source, int $userId): int
    {
        $existing = $this->db->table('report_narratives')
            ->where('subject_result_id', $subjectResultId)
            ->where('narrative_type', $type)
            ->get()->getRowArray();

        $now = date('Y-m-d H:i:s');

        if ($existing) {
            $this->db->table('report_narratives')
                ->where('id', $existing['id'])
                ->update([
                    'content'    => $content,
                    'source'     => $source,
                    'updated_at' => $now,
                    'updated_by' => $userId,
                ]);
            return (int) $existing['id'];
        }

        $this->db->table('report_narratives')->insert([
            'uuid'              => UuidService::v4(),
            'subject_result_id' => $subjectResultId,
            'narrative_type'    => $type,
            'content'           => $content,
            'source'            => $source,
            'status'            => 'DRAFT',
            'created_at'        => $now,
            'updated_at'        => $now,
            'created_by'        => $userId,
            'updated_by'        => $userId,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * Generate a draft narrative for a subject result based on performance data.
     */
    public function generateDraftNarrative(array $subjectResult): string
    {
        $subjectName = $subjectResult['subject_name'] ?? 'Mata Pelajaran';
        $score = $subjectResult['final_score'] !== null ? (float) $subjectResult['final_score'] : 0.0;
        $predicate = $subjectResult['final_predicate'] ?? '-';
        $mastery = $subjectResult['mastery_pct'] !== null ? (float) $subjectResult['mastery_pct'] : null;

        $desc = match (true) {
            $score >= 90 || $predicate === 'A' => "Menunjukkan penguasaan yang sangat baik dan mendalam dalam seluruh tujuan pembelajaran {$subjectName}, berpartisipasi aktif dalam kegiatan pembelajaran serta mampu menerapkan konsep secara mandiri dan kritis.",
            $score >= 80 || $predicate === 'B' => "Menunjukkan pemahaman yang baik dan konsisten dalam capaian pembelajaran {$subjectName}. Mampu menyelesaikan penugasan dan asesmen dengan hasil memuaskan.",
            $score >= 70 || $predicate === 'C' => "Menunjukkan pemahaman yang cukup dalam materi dasar {$subjectName}. Perlu latihan mandiri tambahan pada penguatan konsep-konsep inti.",
            default                             => "Memerlukan pendampingan dan bimbingan belajar lebih intensif untuk mencapai ketuntasan tujuan pembelajaran {$subjectName}.",
        };

        if ($mastery !== null && $mastery >= 75) {
            $desc .= " Tingkat ketuntasan indikator kompetensi telah mencapai {$mastery}%.";
        }

        return $desc;
    }

    /**
     * Generate report snapshots for all active students in a classroom.
     */
    public function generateClassSnapshots(int $unitId, int $periodId, int $classroomId, int $userId): int
    {
        $students = $this->db->table('elective_students')
            ->where('unit_id', $unitId)
            ->where('classroom_id', $classroomId)
            ->where('is_active', 1)
            ->get()->getResultArray();

        $count = 0;
        foreach ($students as $s) {
            $this->generateSnapshot($unitId, $periodId, (int) $s['id'], $userId);
            $count++;
        }

        AuditService::log('reporting', 'BULK_GENERATE', 'Classroom', $classroomId, null, ['count' => $count], 'Bulk generate rapor kelas');

        return $count;
    }

    /**
     * Publish all report snapshots for active students in a classroom.
     */
    public function publishClassSnapshots(int $unitId, int $periodId, int $classroomId, int $userId): int
    {
        $students = $this->db->table('elective_students')
            ->where('unit_id', $unitId)
            ->where('classroom_id', $classroomId)
            ->where('is_active', 1)
            ->get()->getResultArray();

        $studentIds = array_map('intval', array_column($students, 'id'));
        if (empty($studentIds)) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('report_snapshots')
            ->where('unit_id', $unitId)
            ->where('academic_period_id', $periodId)
            ->whereIn('student_id', $studentIds)
            ->update([
                'status'       => self::STATUS_PUBLISHED,
                'published_at' => $now,
                'updated_at'   => $now,
                'updated_by'   => $userId,
            ]);

        $count = count($studentIds);
        AuditService::log('reporting', 'BULK_PUBLISH', 'Classroom', $classroomId, null, ['count' => $count], 'Bulk publish rapor kelas');

        return $count;
    }

    /**
     * Top KPI stats for reporting dashboard.
     */
    public function reportingStats(int $unitId, int $periodId, ?int $classroomId = null): array
    {
        $studentQuery = $this->db->table('elective_students')
            ->where('unit_id', $unitId)
            ->where('is_active', 1);
        if ($classroomId) {
            $studentQuery->where('classroom_id', $classroomId);
        }
        $totalStudents = (int) $studentQuery->countAllResults();

        $snapQuery = $this->db->table('report_snapshots rs')
            ->join('elective_students es', 'es.id = rs.student_id', 'inner')
            ->where('rs.unit_id', $unitId)
            ->where('rs.academic_period_id', $periodId);
        if ($classroomId) {
            $snapQuery->where('es.classroom_id', $classroomId);
        }
        $totalSnapshots = (int) $snapQuery->countAllResults();

        $draftQuery = $this->db->table('report_snapshots rs')
            ->join('elective_students es', 'es.id = rs.student_id', 'inner')
            ->where('rs.unit_id', $unitId)
            ->where('rs.academic_period_id', $periodId)
            ->where('rs.status', self::STATUS_DRAFT);
        if ($classroomId) {
            $draftQuery->where('es.classroom_id', $classroomId);
        }
        $draftCount = (int) $draftQuery->countAllResults();

        $lockedQuery = $this->db->table('report_snapshots rs')
            ->join('elective_students es', 'es.id = rs.student_id', 'inner')
            ->where('rs.unit_id', $unitId)
            ->where('rs.academic_period_id', $periodId)
            ->where('rs.status', self::STATUS_LOCKED);
        if ($classroomId) {
            $lockedQuery->where('es.classroom_id', $classroomId);
        }
        $lockedCount = (int) $lockedQuery->countAllResults();

        $publishedQuery = $this->db->table('report_snapshots rs')
            ->join('elective_students es', 'es.id = rs.student_id', 'inner')
            ->where('rs.unit_id', $unitId)
            ->where('rs.academic_period_id', $periodId)
            ->where('rs.status', self::STATUS_PUBLISHED);
        if ($classroomId) {
            $publishedQuery->where('es.classroom_id', $classroomId);
        }
        $publishedCount = (int) $publishedQuery->countAllResults();

        $avgQuery = $this->db->table('report_subject_results rsr')
            ->join('report_snapshots rs', 'rs.id = rsr.snapshot_id', 'inner')
            ->join('elective_students es', 'es.id = rs.student_id', 'inner')
            ->where('rs.unit_id', $unitId)
            ->where('rs.academic_period_id', $periodId)
            ->selectAvg('rsr.final_score', 'overall_avg');
        if ($classroomId) {
            $avgQuery->where('es.classroom_id', $classroomId);
        }
        $avgRow = $avgQuery->get()->getRowArray();

        $overallAvg = $avgRow && $avgRow['overall_avg'] !== null ? round((float) $avgRow['overall_avg'], 1) : 0;

        return [
            'total_students'  => $totalStudents,
            'total_snapshots' => $totalSnapshots,
            'draft_count'     => $draftCount,
            'locked_count'    => $lockedCount,
            'published_count' => $publishedCount,
            'overall_avg'     => $overallAvg,
            'readiness_pct'   => $totalStudents > 0 ? round(($publishedCount / $totalStudents) * 100) : 0,
        ];
    }

    /**
     * Approve (review) a narrative. Blueprint §10: guru wajib review sebelum publish.
     */
    public function approveNarrative(int $narrativeId, int $userId): void
    {
        $narrative = $this->db->table('report_narratives')
            ->where('id', $narrativeId)
            ->get()->getRowArray();
        if (! $narrative) {
            throw new \InvalidArgumentException('Narasi tidak ditemukan.');
        }
        if ($narrative['status'] === 'APPROVED') {
            return;
        }

        $this->db->table('report_narratives')
            ->where('id', $narrativeId)
            ->update([
                'status'     => 'APPROVED',
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $userId,
            ]);

        AuditService::log('reporting', 'APPROVE_NARRATIVE', 'ReportNarrative', $narrativeId,
            ['status' => $narrative['status']], ['status' => 'APPROVED'], 'Menyetujui narasi rapor');
    }

    // ================================================================
    // PORTFOLIO
    // ================================================================

    public function listPortfolio(int $studentId, int $periodId, ?string $category = null): array
    {
        $builder = $this->db->table('portfolio_collections')
            ->where('student_id', $studentId)
            ->where('academic_period_id', $periodId)
            ->orderBy('is_highlighted', 'DESC')
            ->orderBy('created_at', 'DESC');

        if ($category) {
            $builder->where('category', $category);
        }

        return $builder->get()->getResultArray();
    }

    public function addPortfolioItem(array $data, int $userId): int
    {
        $uuid = UuidService::v4();
        $now  = date('Y-m-d H:i:s');

        $this->db->table('portfolio_collections')->insert([
            'uuid'              => $uuid,
            'student_id'        => $data['student_id'],
            'unit_id'           => $data['unit_id'],
            'academic_period_id' => $data['academic_period_id'],
            'category'          => $data['category'] ?? self::PORTFOLIO_BEST_WORK,
            'title'             => $data['title'],
            'description'       => $data['description'] ?? null,
            'source_type'       => $data['source_type'] ?? null,
            'source_id'         => $data['source_id'] ?? null,
            'file_url'          => $data['file_url'] ?? null,
            'is_highlighted'    => $data['is_highlighted'] ?? 0,
            'created_at'        => $now,
            'updated_at'        => $now,
            'created_by'        => $userId,
            'updated_by'        => $userId,
        ]);

        $id = (int) $this->db->insertID();
        AuditService::log('reporting', 'ADD_PORTFOLIO', 'PortfolioCollection', $id, null, ['title' => $data['title']], 'Menambah item portofolio');

        return $id;
    }

    public function deletePortfolioItem(int $itemId, int $userId): void
    {
        $this->db->table('portfolio_collections')
            ->where('id', $itemId)
            ->delete();

        AuditService::log('reporting', 'DELETE_PORTFOLIO', 'PortfolioCollection', $itemId, null, [], 'Menghapus item portofolio');
    }

    public function toggleHighlight(int $itemId, int $userId): void
    {
        $item = $this->db->table('portfolio_collections')
            ->where('id', $itemId)
            ->get()->getRowArray();

        if ($item) {
            $this->db->table('portfolio_collections')
                ->where('id', $itemId)
                ->update([
                    'is_highlighted' => $item['is_highlighted'] ? 0 : 1,
                    'updated_at'     => date('Y-m-d H:i:s'),
                    'updated_by'     => $userId,
                ]);
        }
    }

    /**
     * Suggest portfolio items drawn from existing evidence sources
     * (blueprint §11 — portfolio menarik evidence terpilih tanpa duplikasi file):
     * assessment evidence, cocurricular evidence, and extracurricular achievements.
     *
     * @return array<int, array<string, mixed>>
     */
    public function suggestPortfolioItems(int $studentId, int $unitId, int $periodId): array
    {
        $now = date('Y-m-d H:i:s');
        $candidates = [];

        // 1. Assessment evidence (attempt → assessment)
        $assessments = $this->db->table('assessment_evidence ae')
            ->select('ae.id, ae.title, ae.evidence_type, ae.file_path, ae.meta_json, ae.captured_at, a.subject_id, s.name AS subject_name, a.title AS assessment_title')
            ->join('assessment_attempts at', 'at.id = ae.attempt_id', 'left')
            ->join('assessments a', 'a.id = at.assessment_id', 'left')
            ->join('subjects s', 's.id = a.subject_id', 'left')
            ->where('ae.student_id', $studentId)
            ->orderBy('ae.captured_at', 'DESC')
            ->limit(200)
            ->get()->getResultArray();
        foreach ($assessments as $row) {
            $subjectName = $row['subject_name'] ?? ($row['assessment_title'] ?? '');
            $candidates[] = [
                'key'          => 'ASSESSMENT_EVIDENCE:' . $row['id'],
                'category'     => strtoupper($row['evidence_type'] ?? '') === 'PROJECT' ? self::PORTFOLIO_PROJECT : self::PORTFOLIO_BEST_WORK,
                'title'        => $row['title'] ?: (trim("Bukti " . $subjectName) ?: 'Bukti Belajar'),
                'description'  => trim("Bukti belajar ({$row['evidence_type']})" . ($subjectName !== '' ? " — {$subjectName}" : '')),
                'file_url'     => $row['file_path'] ?: null,
                'source_type'  => 'ASSESSMENT_EVIDENCE',
                'source_id'    => (int) $row['id'],
                'captured_at'  => $row['captured_at'] ?? $now,
            ];
        }

        // 2. Cocurricular evidence
        $coco = $this->db->table('cocurricular_evidences ce')
            ->select('ce.id, ce.title, ce.evidence_type, ce.description, ce.file_path, ce.meta_json, ce.captured_at, cp.title AS program_title')
            ->join('cocurricular_programs cp', 'cp.id = ce.program_id', 'left')
            ->where('ce.student_id', $studentId)
            ->orderBy('ce.captured_at', 'DESC')
            ->limit(200)
            ->get()->getResultArray();
        foreach ($coco as $row) {
            $candidates[] = [
                'key'          => 'COCURRICULAR_EVIDENCE:' . $row['id'],
                'category'     => self::PORTFOLIO_COCURRICULAR,
                'title'        => $row['title'] ?: ($row['program_title'] ?? 'Bukti Kokurikuler'),
                'description'  => $row['description'] ?: ($row['program_title'] ? 'Bukti program ' . $row['program_title'] : null),
                'file_url'     => $row['file_path'] ?: null,
                'source_type'  => 'COCURRICULAR_EVIDENCE',
                'source_id'    => (int) $row['id'],
                'captured_at'  => $row['captured_at'] ?? $now,
            ];
        }

        // 3. Extracurricular achievements (member → program → competency)
        $achieve = $this->db->table('extracurricular_achievements ea')
            ->select('ea.id, ea.achieved_date, ea.level, ea.score, ea.remarks, ea.evidence_url, em.student_id, ep.title AS program_title, ec.name AS competency_name')
            ->join('extracurricular_members em', 'em.id = ea.member_id', 'inner')
            ->join('extracurricular_programs ep', 'ep.id = em.program_id', 'left')
            ->join('extracurricular_competencies ec', 'ec.id = ea.competency_id', 'left')
            ->where('em.student_id', $studentId)
            ->orderBy('ea.achieved_date', 'DESC')
            ->limit(200)
            ->get()->getResultArray();
        foreach ($achieve as $row) {
            $program = $row['program_title'] ?? 'Ekstrakurikuler';
            $comp    = $row['competency_name'] ? " — {$row['competency_name']}" : '';
            $candidates[] = [
                'key'          => 'EXTRACURRICULAR_ACHIEVEMENT:' . $row['id'],
                'category'     => self::PORTFOLIO_EXTRACURRICULAR,
                'title'        => trim("Pencapaian {$program}{$comp}"),
                'description'  => $row['remarks'] ?: (($row['level'] ?? '') ? "Level {$row['level']}" : null),
                'file_url'     => $row['evidence_url'] ?: null,
                'source_type'  => 'EXTRACURRICULAR_ACHIEVEMENT',
                'source_id'    => (int) $row['id'],
                'captured_at'  => $row['achieved_date'] ?? $now,
            ];
        }

        return $candidates;
    }

    /**
     * Import selected suggested items into the portfolio collection.
     * Existing items (same source_type + source_id) are skipped — references only,
     * no file duplication (blueprint §11).
     *
     * @param array<int, string> $keys
     */
    public function importPortfolioItems(int $studentId, int $unitId, int $periodId, int $userId, array $keys): int
    {
        $candidates = $this->suggestPortfolioItems($studentId, $unitId, $periodId);
        $byKey      = [];
        foreach ($candidates as $c) {
            $byKey[$c['key']] = $c;
        }

        $imported = 0;
        foreach ($keys as $key) {
            if (! isset($byKey[$key])) {
                continue;
            }
            $candidate = $byKey[$key];

            $exists = $this->db->table('portfolio_collections')
                ->where('student_id', $studentId)
                ->where('academic_period_id', $periodId)
                ->where('source_type', $candidate['source_type'])
                ->where('source_id', $candidate['source_id'])
                ->countAllResults();

            if ($exists) {
                continue;
            }

            $this->db->table('portfolio_collections')->insert([
                'uuid'               => UuidService::v4(),
                'student_id'         => $studentId,
                'unit_id'            => $unitId,
                'academic_period_id' => $periodId,
                'category'           => $candidate['category'],
                'title'              => $candidate['title'],
                'description'        => $candidate['description'] ?? null,
                'source_type'        => $candidate['source_type'],
                'source_id'          => $candidate['source_id'],
                'file_url'           => $candidate['file_url'] ?? null,
                'is_highlighted'     => 0,
                'created_at'         => date('Y-m-d H:i:s'),
                'updated_at'         => date('Y-m-d H:i:s'),
                'created_by'         => $userId,
                'updated_by'         => $userId,
            ]);

            AuditService::log('reporting', 'IMPORT_PORTFOLIO', 'PortfolioCollection', (int) $this->db->insertID(),
                null, ['source_type' => $candidate['source_type'], 'source_id' => $candidate['source_id']], 'Mengimpor bukti ke portofolio');
            $imported++;
        }

        return $imported;
    }

    // ================================================================
    // CLASS DASHBOARD
    // ================================================================

    /**
     * Class-level analytics: average scores, predicate distribution, mastery rates.
     */
    public function classAnalytics(int $unitId, int $periodId): array
    {
        // Get all snapshots for this unit/period
        $snapshots = $this->db->table('report_snapshots rs')
            ->select('rs.*, es.full_name as student_name')
            ->join('elective_students es', 'es.id = rs.student_id', 'left')
            ->where('rs.unit_id', $unitId)
            ->where('rs.academic_period_id', $periodId)
            ->get()->getResultArray();

        if (empty($snapshots)) {
            return ['students' => [], 'summary' => []];
        }

        $snapshotIds = array_column($snapshots, 'id');

        // Get all subject results
        $allResults = $this->db->table('report_subject_results rsr')
            ->select('rsr.*, s.name as subject_name')
            ->join('subjects s', 's.id = rsr.subject_id', 'left')
            ->whereIn('rsr.snapshot_id', $snapshotIds)
            ->get()->getResultArray();

        // Group by subject
        $bySubject = [];
        foreach ($allResults as $r) {
            $subjName = $r['subject_name'] ?? 'Unknown';
            $bySubject[$subjName][] = $r;
        }

        $subjectSummary = [];
        foreach ($bySubject as $name => $results) {
            $scores = array_filter(array_column($results, 'final_score'), fn($v) => $v !== null);
            $predicates = array_filter(array_column($results, 'final_predicate'), fn($v) => ! empty($v));
            $masteries = array_filter(array_column($results, 'mastery_pct'), fn($v) => $v !== null);

            $subjectSummary[$name] = [
                'student_count'    => count($results),
                'avg_score'        => ! empty($scores) ? round(array_sum($scores) / count($scores), 1) : null,
                'predicate_dist'   => ! empty($predicates) ? array_count_values($predicates) : [],
                'avg_mastery'      => ! empty($masteries) ? round(array_sum($masteries) / count($masteries), 1) : null,
            ];
        }

        return [
            'students' => $snapshots,
            'summary'  => $subjectSummary,
        ];
    }

    // ================================================================
    // PROMOTION / GRADUATION DECISION SUPPORT (blueprint §12)
    // ================================================================

    /**
     * Decision support for promotion/graduation per student:
     * subject completion, attendance alerts, competency progress, and
     * unresolved interventions. Keputusan akhir tetap milik sekolah.
     *
     * @return array{rows: array, summary: array}
     */
    public function promotionReadiness(int $unitId, int $periodId, ?int $classroomId = null): array
    {
        $students = $this->db->table('elective_students es')
            ->select('es.id, es.full_name, es.student_number, es.current_grade, c.name AS classroom_name')
            ->join('classrooms c', 'c.id = es.classroom_id', 'left')
            ->where('es.unit_id', $unitId)
            ->where('es.is_active', 1)
            ->groupStart()
                ->where('es.academic_year_id', $this->periodYearId($periodId))
                ->orGroupStart()
                    ->where('es.academic_year_id', 0)
                ->groupEnd()
            ->groupEnd()
            ->orderBy('c.name', 'ASC')
            ->orderBy('es.full_name', 'ASC');

        if ($classroomId) {
            $students->where('es.classroom_id', $classroomId);
        }

        $studentRows = $students->get()->getResultArray();

        if ($studentRows === []) {
            return ['rows' => [], 'summary' => ['READY' => 0, 'REVIEW' => 0, 'ATTENTION' => 0]];
        }

        $rows = [];
        foreach ($studentRows as $student) {
            $sid = (int) $student['id'];

            // Latest snapshot for this student (any status, latest first)
            $snapshot = $this->db->table('report_snapshots')
                ->where('unit_id', $unitId)
                ->where('academic_period_id', $periodId)
                ->where('student_id', $sid)
                ->where('snapshot_type', self::TYPE_SEMESTER)
                ->orderBy('id', 'DESC')
                ->get()->getRowArray();

            $subjectResults = [];
            if ($snapshot) {
                $subjectResults = $this->db->table('report_subject_results')
                    ->where('snapshot_id', (int) $snapshot['id'])
                    ->get()->getResultArray();
            }

            $totalSubjects  = count($subjectResults);
            $gradedSubjects = 0;
            $sumScores      = 0.0;
            $masteries      = [];
            $attendances    = [];
            foreach ($subjectResults as $sr) {
                if ($sr['final_score'] !== null) {
                    $gradedSubjects++;
                    $sumScores += (float) $sr['final_score'];
                }
                if ($sr['mastery_pct'] !== null) {
                    $masteries[] = (float) $sr['mastery_pct'];
                }
                if ($sr['attendance_pct'] !== null) {
                    $attendances[] = (float) $sr['attendance_pct'];
                }
            }

            $completionPct = $totalSubjects > 0 ? round($gradedSubjects / $totalSubjects * 100, 1) : 0.0;
            $avgScore      = $gradedSubjects > 0 ? round($sumScores / $gradedSubjects, 1) : null;
            $avgMastery    = $masteries !== [] ? round(array_sum($masteries) / count($masteries), 1) : null;
            $avgAttendance = $attendances !== [] ? round(array_sum($attendances) / count($attendances), 1) : null;

            // Unresolved interventions (not completed / cancelled)
            $unresolved = (int) $this->db->table('interventions')
                ->where('student_id', $sid)
                ->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
                ->countAllResults();

            $alerts = [];
            if ($totalSubjects === 0) {
                $alerts[] = 'Belum ada data laporan';
            } elseif ($completionPct < 100) {
                $alerts[] = 'Nilai belum lengkap';
            }
            if ($avgAttendance !== null && $avgAttendance < 75) {
                $alerts[] = 'Kehadiran rendah';
            }
            if ($unresolved > 0) {
                $alerts[] = $unresolved . ' intervensi belum tuntas';
            }

            $status = 'READY';
            if ($alerts !== []) {
                $attention = $totalSubjects > 0
                    && (($avgAttendance !== null && $avgAttendance < 75) || $unresolved > 2 || $completionPct < 50);
                $status = $attention ? 'ATTENTION' : 'REVIEW';
            }

            $rows[] = [
                'student'          => $student,
                'total_subjects'   => $totalSubjects,
                'graded_subjects'  => $gradedSubjects,
                'completion_pct'   => $completionPct,
                'avg_score'        => $avgScore,
                'avg_mastery'      => $avgMastery,
                'avg_attendance'   => $avgAttendance,
                'unresolved'       => $unresolved,
                'alerts'           => $alerts,
                'status'           => $status,
                'snapshot_status'  => $snapshot ? $snapshot['status'] : null,
            ];
        }

        $summary = ['READY' => 0, 'REVIEW' => 0, 'ATTENTION' => 0];
        foreach ($rows as $r) {
            $summary[$r['status']]++;
        }

        return ['rows' => $rows, 'summary' => $summary];
    }

    private function periodYearId(int $periodId): int
    {
        $period = $this->db->table('academic_periods')->select('academic_year_id')->where('id', $periodId)->get()->getRowArray();
        return (int) ($period['academic_year_id'] ?? 0);
    }

    // ================================================================
    // HELPERS
    // ================================================================

    private function scoreToPredicate(float $score): string
    {
        foreach (self::PREDICATE_MAP as $pred => $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                return $pred;
            }
        }
        return 'D';
    }
}
