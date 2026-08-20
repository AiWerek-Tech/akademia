<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

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

    public function __construct()
    {
        $this->db = Database::connect();
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
            ->select('rs.*, es.full_name as student_name, c.name as classroom_name')
            ->join('elective_students es', 'es.id = rs.student_id', 'left')
            ->join('classrooms c', 'c.id = es.classroom_id', 'left')
            ->where('rs.id', $snapshotId)
            ->get()->getRowArray();

        if (! $snapshot) {
            return null;
        }

        // Load subject results with narratives
        $snapshot['subjects'] = $this->db->table('report_subject_results rsr')
            ->select('rsr.*, s.name as subject_name, t.full_name as teacher_name')
            ->join('subjects s', 's.id = rsr.subject_id', 'left')
            ->join('teachers t', 't.id = rsr.teacher_id', 'left')
            ->where('rsr.snapshot_id', $snapshotId)
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        foreach ($snapshot['subjects'] as &$subject) {
            $subject['narratives'] = $this->db->table('report_narratives')
                ->where('subject_result_id', $subject['id'])
                ->orderBy('narrative_type', 'ASC')
                ->get()->getResultArray();
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
            ->where('student_id', $snapshot['student_id'])
            ->where('academic_period_id', $snapshot['academic_period_id'])
            ->orderBy('category', 'ASC')
            ->orderBy('created_at', 'DESC')
            ->get()->getResultArray();

        return $snapshot;
    }

    /**
     * Generate a report snapshot for one student.
     * Pulls data from assessment, mastery, and summative tables.
     */
    public function generateSnapshot(int $unitId, int $periodId, int $studentId, int $userId): int
    {
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
                'uuid'              => UuidService::v4(),
                'unit_id'           => $unitId,
                'academic_period_id' => $periodId,
                'student_id'        => $studentId,
                'snapshot_type'     => self::TYPE_SEMESTER,
                'status'            => self::STATUS_DRAFT,
                'created_at'        => $now,
                'updated_at'        => $now,
                'created_by'        => $userId,
                'updated_by'        => $userId,
            ]);
            $snapshotId = (int) $this->db->insertID();
        }

        // Delete old subject results for fresh generation
        $this->db->table('report_subject_results')
            ->where('snapshot_id', $snapshotId)
            ->delete();

        // Get active assignments for this student's unit
        $assignments = $this->db->table('assignments a')
            ->select('a.id, a.subject_id, a.teacher_id')
            ->where('a.unit_id', $unitId)
            ->where('a.academic_period_id', $periodId)
            ->where('a.status', 'ACTIVE')
            ->get()->getResultArray();

        foreach ($assignments as $assignment) {
            $subjectId = (int) $assignment['subject_id'];
            $teacherId = (int) $assignment['teacher_id'];

            // Get summative result for this student in this subject
            $summative = $this->db->table('summative_results sr')
                ->where('sr.student_id', $studentId)
                ->where('sr.unit_id', $unitId)
                ->where('sr.academic_period_id', $periodId)
                ->where('sr.subject_id', $subjectId)
                ->orderBy('sr.id', 'DESC')
                ->get()->getRowArray();

            $finalScore = $summative ? (float) ($summative['final_score'] ?? 0) : null;
            $predicate  = $summative ? ($summative['predikat'] ?? null) : null;

            // Auto-calculate predicate if not set
            if ($finalScore !== null && ! $predicate) {
                $predicate = $this->scoreToPredicate($finalScore);
            }

            // Get mastery data
            $masteryData = $this->db->table('mastery_records mr')
                ->join('assessment_objectives ao', 'ao.id = mr.objective_id', 'left')
                ->where('mr.student_id', $studentId)
                ->where('ao.subject_id', $subjectId)
                ->get()->getResultArray();

            $totalObjectives = count($masteryData);
            $masteredCount = 0;
            foreach ($masteryData as $m) {
                if (($m['level'] ?? '') === 'MASTERED' || (int) ($m['score'] ?? 0) >= 75) {
                    $masteredCount++;
                }
            }
            $masteryPct = $totalObjectives > 0 ? round($masteredCount / $totalObjectives * 100, 1) : null;

            $this->db->table('report_subject_results')->insert([
                'uuid'             => UuidService::v4(),
                'snapshot_id'      => $snapshotId,
                'subject_id'       => $subjectId,
                'teacher_id'       => $teacherId ?: null,
                'final_score'      => $finalScore,
                'final_predicate'  => $predicate,
                'tp_coverage_pct'  => null,
                'mastery_pct'      => $masteryPct,
                'attendance_pct'   => null,
                'detail_json'      => null,
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
     * Publish a snapshot.
     */
    public function publishSnapshot(int $snapshotId, int $userId): void
    {
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
        $subjectName = $subjectResult['subject_name'] ?? 'mata pelajaran ini';
        $score = $subjectResult['final_score'];
        $predicate = $subjectResult['final_predicate'] ?? '-';
        $mastery = $subjectResult['mastery_pct'];

        $lines = [];

        if ($score !== null) {
            $lines[] = "Nilai akhir {$subjectName}: {$score} (Predikat {$predicate}).";
        }

        if ($mastery !== null) {
            if ($mastery >= 75) {
                $lines[] = "Tingkat penguasaan kompetensi mencapai {$mastery}%, menunjukkan hasil yang baik.";
            } elseif ($mastery >= 50) {
                $lines[] = "Tingkat penguasaan kompetensi mencapai {$mastery}%, perlu penguatan pada beberapa aspek.";
            } else {
                $lines[] = "Tingkat penguasaan kompetensi baru mencapai {$mastery}%, diperlukan tindak lanjut intensif.";
            }
        }

        if (empty($lines)) {
            return "Belum ada data penilaian yang cukup untuk {$subjectName}.";
        }

        return implode(' ', $lines);
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
            $predicates = array_column($results, 'final_predicate');
            $masteries = array_filter(array_column($results, 'mastery_pct'), fn($v) => $v !== null);

            $subjectSummary[$name] = [
                'student_count'    => count($results),
                'avg_score'        => ! empty($scores) ? round(array_sum($scores) / count($scores), 1) : null,
                'predicate_dist'   => array_count_values($predicates),
                'avg_mastery'      => ! empty($masteries) ? round(array_sum($masteries) / count($masteries), 1) : null,
            ];
        }

        return [
            'students' => $snapshots,
            'summary'  => $subjectSummary,
        ];
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
