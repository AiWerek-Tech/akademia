<?php

namespace App\Services;

use App\Models\InterventionModel;
use App\Models\MasteryRecordModel;
use Config\Database;
use RuntimeException;

/**
 * Drives evidence-backed TP (Tujuan Pembelajaran) mastery:
 * criterion aggregation, mastery record upserts, and intervention
 * recommendation based on mastery status.
 */
class MasteryService
{
    public const RESULT_NEEDS_SUPPORT = 'NEEDS_SUPPORT';
    public const RESULT_DEVELOPING    = 'DEVELOPING';
    public const RESULT_ACHIEVED      = 'ACHIEVED';
    public const RESULT_ADVANCED      = 'ADVANCED';

    public const ALLOWED_RESULTS = [
        self::RESULT_NEEDS_SUPPORT,
        self::RESULT_DEVELOPING,
        self::RESULT_ACHIEVED,
        self::RESULT_ADVANCED,
    ];

    public const INTERVENTION_REMEDIAL      = 'REMEDIAL';
    public const INTERVENTION_REINFORCEMENT = 'REINFORCEMENT';
    public const INTERVENTION_ENRICHMENT    = 'ENRICHMENT';

    public const INTERVENTION_RECOMMENDED = 'RECOMMENDED';
    public const INTERVENTION_APPROVED    = 'APPROVED';
    public const INTERVENTION_COMPLETED   = 'COMPLETED';
    public const INTERVENTION_CANCELLED   = 'CANCELLED';

    public const TYPE_DIAGNOSTIC = 'DIAGNOSTIC';
    public const TYPE_FORMATIVE  = 'FORMATIVE';
    public const TYPE_SUMMATIVE  = 'SUMMATIVE';

    /**
     * Assessment types that may write mastery attainment. Diagnostic measures
     * starting readiness and must not become part of the final grade.
     */
    private const ATTAINMENT_TYPES = [self::TYPE_FORMATIVE, self::TYPE_SUMMATIVE];

    private const STATUS_PRIORITY = [
        self::RESULT_NEEDS_SUPPORT => 0,
        self::RESULT_DEVELOPING    => 1,
        self::RESULT_ACHIEVED      => 2,
        self::RESULT_ADVANCED      => 3,
    ];

    private $db;
    private MasteryRecordModel $masteryModel;
    private InterventionModel $interventionModel;

    public function __construct()
    {
        $this->db                = Database::connect();
        $this->masteryModel      = new MasteryRecordModel();
        $this->interventionModel = new InterventionModel();
    }

    /**
     * Whether an assessment type contributes to mastery attainment. Diagnostic
     * assessments are informational only (starting point / readiness).
     */
    public static function isAttainmentType(string $type): bool
    {
        return in_array(strtoupper($type), self::ATTAINMENT_TYPES, true);
    }

    /**
     * Aggregates per-criterion statuses into a single TP mastery result.
     * Any NEEDS_SUPPORT dominates; then DEVELOPING; then ACHIEVED/ADVANCED.
     */
    public function deriveStatusFromCriteria(array $criterionStatuses): string
    {
        if ($criterionStatuses === []) {
            return self::RESULT_DEVELOPING;
        }

        $worst = null;
        foreach ($criterionStatuses as $status) {
            $status = strtoupper((string) $status);
            if (! isset(self::STATUS_PRIORITY[$status])) {
                continue;
            }
            if ($worst === null || self::STATUS_PRIORITY[$status] < self::STATUS_PRIORITY[$worst]) {
                $worst = $status;
            }
        }

        if ($worst === null) {
            return self::RESULT_DEVELOPING;
        }

        if ($worst === self::RESULT_ACHIEVED) {
            $allAdvanced = true;
            foreach ($criterionStatuses as $status) {
                if (strtoupper((string) $status) !== self::RESULT_ADVANCED) {
                    $allAdvanced = false;
                    break;
                }
            }
            return $allAdvanced ? self::RESULT_ADVANCED : self::RESULT_ACHIEVED;
        }

        return $worst;
    }

    /**
     * Derives a criterion status from a score ratio or rubric level when the
     * teacher did not pick one explicitly.
     */
    public function deriveCriterionStatus(?float $score, ?float $maxScore, ?int $levelIndex): ?string
    {
        if ($levelIndex !== null && $levelIndex >= 0 && $levelIndex <= 3) {
            // Rubric level_index is 0-based: level 0 = lowest … level 3 = highest.
            return self::ALLOWED_RESULTS[$levelIndex];
        }

        if ($score !== null && $maxScore !== null && $maxScore > 0) {
            $ratio = $score / $maxScore;
            if ($ratio >= 0.9) {
                return self::RESULT_ADVANCED;
            }
            if ($ratio >= 0.7) {
                return self::RESULT_ACHIEVED;
            }
            if ($ratio >= 0.5) {
                return self::RESULT_DEVELOPING;
            }
            return self::RESULT_NEEDS_SUPPORT;
        }

        return null;
    }

    /**
     * Recomputes mastery records for every student/TP covered by an assessment,
     * using criterion results tied to that TP. Opens suggested interventions for
     * students who have not mastered the TP yet.
     */
    public function syncFromAssessment(int $assessmentId, int $userId): array
    {
        $assessment = $this->db->table('assessments')->where('id', $assessmentId)->get()->getRowArray();
        if (! $assessment) {
            throw new RuntimeException('Assessment tidak ditemukan.');
        }

        $maxScore = $assessment['max_score'] !== null ? (float) $assessment['max_score'] : null;
        $assessmentType = strtoupper((string) ($assessment['assessment_type'] ?? ''));

        if (! self::isAttainmentType($assessmentType)) {
            // DIAGNOSTIC measures starting readiness: it never becomes part of
            // the final grade, so it must not write mastery attainment.
            return ['updated' => 0, 'interventions' => 0];
        }

        $objectiveRows = $this->db->table('assessment_objectives ao')
            ->select('ao.learning_objective_id, lot.code as tp_code, lot.statement as tp_name')
            ->join('learning_objectives_tp lot', 'lot.id = ao.learning_objective_id', 'left')
            ->where('ao.assessment_id', $assessmentId)
            ->get()->getResultArray();
        $objectiveIds = array_column($objectiveRows, 'learning_objective_id');

        // Criteria grouped by TP they assess (criteria without a TP do not drive mastery)
        $criteriaByObjective = [];
        if ($objectiveIds !== []) {
            $criteriaRows = $this->db->table('assessment_criteria')
                ->where('assessment_id', $assessmentId)
                ->whereIn('learning_objective_id', $objectiveIds)
                ->get()->getResultArray();
            foreach ($criteriaRows as $criterion) {
                $criteriaByObjective[(int) $criterion['learning_objective_id']][] = $criterion;
            }
        }

        $attempts = $this->db->table('assessment_attempts aa')
            ->select('aa.id as attempt_id, aa.student_id, aa.classroom_id, es.full_name, es.current_grade')
            ->join('elective_students es', 'es.id = aa.student_id', 'left')
            ->where('aa.assessment_id', $assessmentId)
            ->get()->getResultArray();

        $resultRows = [];
        if ($attempts !== []) {
            $attemptIds = array_column($attempts, 'attempt_id');
            $resultRows = $this->db->table('criterion_results cr')
                ->select('cr.attempt_id, cr.criterion_id, cr.level_index, cr.score, cr.status')
                ->join('assessment_criteria ac', 'ac.id = cr.criterion_id', 'left')
                ->whereIn('cr.attempt_id', $attemptIds)
                ->where('ac.assessment_id', $assessmentId)
                ->get()->getResultArray();
        }

        $resultsByAttempt = [];
        foreach ($resultRows as $row) {
            $resultsByAttempt[(int) $row['attempt_id']][(int) $row['criterion_id']] = $row;
        }

        $summary = ['updated' => 0, 'interventions' => 0];
        foreach ($attempts as $attempt) {
            $studentId    = (int) $attempt['student_id'];
            $attemptId    = (int) $attempt['attempt_id'];
            $attemptResults = $resultsByAttempt[$attemptId] ?? [];

            foreach ($objectiveRows as $objective) {
                $objectiveId = (int) $objective['learning_objective_id'];
                $criteria    = $criteriaByObjective[$objectiveId] ?? [];
                if ($criteria === []) {
                    continue;
                }

                $statuses = [];
                $failing  = [];
                foreach ($criteria as $criterion) {
                    $criterionId = (int) $criterion['id'];
                    $result      = $attemptResults[$criterionId] ?? null;
                    if (! $result) {
                        continue;
                    }
                    $status = $result['status'] ?: $this->deriveCriterionStatus(
                        $result['score'] !== null ? (float) $result['score'] : null,
                        $maxScore,
                        $result['level_index'] !== null ? (int) $result['level_index'] : null
                    );
                    if ($status) {
                        $statuses[] = $status;
                    }
                    if (in_array($status, [self::RESULT_NEEDS_SUPPORT, self::RESULT_DEVELOPING], true)) {
                        $failing[] = ['criterion_id' => $criterionId, 'criterion' => $criterion['criterion'], 'status' => $status];
                    }
                }

                $this->upsertMastery($studentId, $objectiveId, $this->deriveStatusFromCriteria($statuses), $userId, [
                    'source_attempt_id' => $attemptId,
                    'source'            => $assessmentType,
                    'version'           => null,
                ]);

                $summary['updated']++;
                if ($this->recommendInterventionForPair($studentId, $objectiveId, $userId, $failing)) {
                    $summary['interventions']++;
                }
            }
        }

        return $summary;
    }

    /**
     * Mastery board: students x TP matrix for a classroom/subject, with current
     * mastery status, latest evidence, and pending interventions.
     */
    public function board(int $unitId, int $periodId, int $classroomId, int $subjectId, ?int $objectiveId = null): array
    {
        $unitScopedIds = $this->objectiveIdsInScope($unitId, $subjectId);

        if ($unitScopedIds === []) {
            $objectiveRows = [];
        } else {
            $objectiveRows = $this->db->table('learning_objectives_tp lot')
                ->select('lot.id, lot.code, lot.statement as name')
                ->join('learning_outcomes_cp lo', 'lo.id = lot.learning_outcome_id', 'left')
                ->whereIn('lot.id', $unitScopedIds)
                ->orderBy('lot.code', 'ASC')
                ->get()->getResultArray();
        }
        if ($objectiveId) {
            $objectiveRows = array_values(array_filter($objectiveRows, static fn (array $o): bool => (int) $o['id'] === $objectiveId));
        }

        $students = $this->db->table('elective_students')
            ->where('classroom_id', $classroomId)
            ->where('is_active', 1)
            ->orderBy('full_name', 'ASC')
            ->get()->getResultArray();

        $objectiveIds = array_column($objectiveRows, 'id');
        $studentIds   = array_column($students, 'id');

        $masteryByPair = [];
        if ($objectiveIds !== [] && $studentIds !== []) {
            $masteryRows = $this->db->table('mastery_records')
                ->whereIn('student_id', $studentIds)
                ->whereIn('learning_objective_id', $objectiveIds)
                ->get()->getResultArray();
            foreach ($masteryRows as $row) {
                $masteryByPair[(int) $row['student_id'] . ':' . (int) $row['learning_objective_id']] = $row;
            }
        }

        $interventionsByPair = [];
        if ($studentIds !== []) {
            $interventionRows = $this->db->table('interventions')
                ->whereIn('student_id', $studentIds)
                ->whereIn('status', [self::INTERVENTION_RECOMMENDED, self::INTERVENTION_APPROVED])
                ->get()->getResultArray();
            foreach ($interventionRows as $row) {
                $interventionsByPair[(int) $row['student_id'] . ':' . (int) $row['learning_objective_id']] = $row;
            }
        }

        $matrix = [];
        foreach ($students as $student) {
            $studentId  = (int) $student['id'];
            $objectiveRow = [];
            foreach ($objectiveRows as $objective) {
                $objectiveId = (int) $objective['id'];
                $pairKey     = $studentId . ':' . $objectiveId;
                $objectiveRow[] = [
                    'objective'      => $objective,
                    'mastery'        => $masteryByPair[$pairKey] ?? null,
                    'intervention'   => $interventionsByPair[$pairKey] ?? null,
                ];
            }
            $matrix[] = [
                'student'    => $student,
                'objectives' => $objectiveRow,
            ];
        }

        return [
            'classroom_id' => $classroomId,
            'subject_id'   => $subjectId,
            'objectives'   => $objectiveRows,
            'students'     => $students,
            'matrix'       => $matrix,
        ];
    }

    /**
     * Manual mastery override (teacher evidence-backed adjustment).
     */
    public function setMastery(int $studentId, int $objectiveId, string $result, int $userId, array $opts = []): void
    {
        $result = strtoupper($result);
        if (! in_array($result, self::ALLOWED_RESULTS, true)) {
            throw new RuntimeException('Hasil mastery tidak valid.');
        }

        $this->upsertMastery($studentId, $objectiveId, $result, $userId, [
            'source'            => 'MANUAL',
            'evidence_id'       => $opts['evidence_id'] ?? null,
            'source_attempt_id' => $opts['source_attempt_id'] ?? null,
            'confidence'        => isset($opts['confidence']) ? (int) $opts['confidence'] : null,
            'notes'             => $opts['notes'] ?? null,
            'version'           => null,
        ]);
    }

    public function listInterventions(array $filters = []): array
    {
        $builder = $this->db->table('interventions i')
            ->select('i.*, es.full_name, es.student_number, es.classroom_id, c.name as classroom_name, lot.code as tp_code, lot.statement as tp_name, ac.criterion as criterion_text')
            ->join('elective_students es', 'es.id = i.student_id', 'left')
            ->join('classrooms c', 'c.id = es.classroom_id', 'left')
            ->join('learning_objectives_tp lot', 'lot.id = i.learning_objective_id', 'left')
            ->join('assessment_criteria ac', 'ac.id = i.criterion_id', 'left');

        if (! empty($filters['unit_id'])) {
            $builder->join('school_units su', 'su.id = c.unit_id', 'left')
                ->where('c.unit_id', $filters['unit_id']);
        }
        if (! empty($filters['classroom_id'])) {
            $builder->where('es.classroom_id', $filters['classroom_id']);
        }
        if (! empty($filters['status'])) {
            $builder->where('i.status', $filters['status']);
        }
        if (! empty($filters['intervention_type'])) {
            $builder->where('i.intervention_type', $filters['intervention_type']);
        }

        return $builder->orderBy('i.created_at', 'DESC')->get()->getResultArray();
    }

    /**
     * Transitions an intervention: APPROVED -> COMPLETED, or CANCELLED anytime.
     */
    public function updateIntervention(int $id, string $status, int $userId, array $opts = []): void
    {
        $status = strtoupper($status);
        if (! in_array($status, [self::INTERVENTION_APPROVED, self::INTERVENTION_COMPLETED, self::INTERVENTION_CANCELLED], true)) {
            throw new RuntimeException('Status intervensi tidak valid.');
        }

        $existing = $this->interventionModel->find($id);
        if (! $existing) {
            throw new RuntimeException('Intervensi tidak ditemukan.');
        }

        $update = [
            'status'      => $status,
            'updated_by'  => $userId,
            'outcome'     => $opts['outcome'] ?? $existing['outcome'],
            'completed_at'=> $status === self::INTERVENTION_COMPLETED
                ? ($opts['completed_at'] ?? date('Y-m-d H:i:s'))
                : $existing['completed_at'],
        ];

        $this->interventionModel->update($id, $update);
        AuditService::log('mastery', 'INTERVENTION_' . $status, 'Intervention', $id, $existing, $update, 'Transisi status intervensi');
    }

    /**
     * Batch scan: recommends interventions for every unmastered TP in scope.
     */
    public function recommendInterventions(int $unitId, int $periodId, ?int $classroomId = null): array
    {
        $builder = $this->db->table('mastery_records mr')
            ->select('mr.student_id, mr.learning_objective_id, mr.result, es.classroom_id')
            ->join('elective_students es', 'es.id = mr.student_id', 'left')
            ->join('classrooms c', 'c.id = es.classroom_id', 'left')
            ->whereIn('mr.result', [self::RESULT_NEEDS_SUPPORT, self::RESULT_DEVELOPING])
            ->where('c.unit_id', $unitId);

        if ($classroomId) {
            $builder->where('es.classroom_id', $classroomId);
        }

        $rows = $builder->get()->getResultArray();
        $created = 0;
        foreach ($rows as $row) {
            if ($this->recommendInterventionForPair((int) $row['student_id'], (int) $row['learning_objective_id'], 0, $this->failingCriteriaForPair((int) $row['student_id'], (int) $row['learning_objective_id']))) {
                $created++;
            }
        }

        return ['recommended' => $created, 'reviewed' => count($rows)];
    }

    public function masterySummary(int $unitId, int $periodId, int $classroomId, int $subjectId): array
    {
        $objectiveIds = $this->objectiveIdsInScope($unitId, $subjectId);
        $summary = array_fill_keys(self::ALLOWED_RESULTS, 0);
        $summary['TOTAL'] = 0;

        if ($objectiveIds === []) {
            return $summary;
        }

        $rows = $this->db->table('mastery_records mr')
            ->select('mr.result')
            ->join('elective_students es', 'es.id = mr.student_id', 'left')
            ->where('es.classroom_id', $classroomId)
            ->whereIn('mr.learning_objective_id', $objectiveIds)
            ->where('es.is_active', 1)
            ->get()->getResultArray();

        foreach ($rows as $row) {
            $result = strtoupper((string) $row['result']);
            if (isset($summary[$result])) {
                $summary[$result]++;
            }
            $summary['TOTAL']++;
        }

        return $summary;
    }

    // ----------------------------------------------------------------
    // Internals
    // ----------------------------------------------------------------

    private function objectiveIdsInScope(int $unitId, int $subjectId): array
    {
        $rows = $this->db->table('learning_objectives_tp lot')
            ->select('lot.id')
            ->join('learning_outcomes_cp lo', 'lo.id = lot.learning_outcome_id', 'left')
            ->groupStart()
                ->where('lot.unit_id', $unitId)
                ->orWhere('lot.unit_id', null)
            ->groupEnd()
            ->where('lo.subject_id', $subjectId)
            ->get()->getResultArray();

        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }

    private function upsertMastery(int $studentId, int $objectiveId, string $result, int $userId, array $opts): void
    {
        $existing = $this->masteryModel
            ->where('student_id', $studentId)
            ->where('learning_objective_id', $objectiveId)
            ->first();

        $version = ($existing ? (int) $existing['version'] : 0) + 1;

        if ($existing) {
            $this->masteryModel->update($existing['id'], [
                'criterion_id'      => $opts['criterion_id'] ?? $existing['criterion_id'],
                'evidence_id'       => array_key_exists('evidence_id', $opts) ? $opts['evidence_id'] : $existing['evidence_id'],
                'source_attempt_id' => array_key_exists('source_attempt_id', $opts) ? $opts['source_attempt_id'] : $existing['source_attempt_id'],
                'result'            => $result,
                'source'            => $opts['source'] ?? $existing['source'],
                'confidence'        => array_key_exists('confidence', $opts) ? $opts['confidence'] : $existing['confidence'],
                'notes'             => array_key_exists('notes', $opts) ? $opts['notes'] : $existing['notes'],
                'version'           => $version,
                'updated_by'        => $userId,
            ]);
            $id = (int) $existing['id'];
        } else {
            $id = (int) $this->masteryModel->insert([
                'uuid'                  => UuidService::v4(),
                'student_id'            => $studentId,
                'learning_objective_id' => $objectiveId,
                'criterion_id'          => $opts['criterion_id'] ?? null,
                'evidence_id'           => $opts['evidence_id'] ?? null,
                'source_attempt_id'     => $opts['source_attempt_id'] ?? null,
                'result'                => $result,
                'source'                => $opts['source'] ?? 'ASSESSMENT',
                'confidence'            => $opts['confidence'] ?? null,
                'notes'                 => $opts['notes'] ?? null,
                'version'               => $version,
                'created_by'            => $userId,
                'updated_by'            => $userId,
            ]);
        }

        AuditService::log('mastery', 'UPSERT_MASTERY', 'MasteryRecord', $id, $existing, ['result' => $result, 'version' => $version], 'Perbarui mastery TP');
    }

    /**
     * Failing (unmastered) criteria for a student/TP pair across FORMATIVE and
     * SUMMATIVE assessments, used to target remediation at the specific
     * criterion instead of the TP as a whole.
     *
     * @return list<array{criterion_id:int, criterion:string, status:string}>
     */
    private function failingCriteriaForPair(int $studentId, int $objectiveId): array
    {
        $rows = $this->db->table('criterion_results cr')
            ->select('cr.criterion_id, ac.criterion, cr.level_index, cr.score, cr.status, a.max_score, a.assessment_type')
            ->join('assessment_criteria ac', 'ac.id = cr.criterion_id', 'left')
            ->join('assessment_attempts aa', 'aa.id = cr.attempt_id', 'left')
            ->join('assessments a', 'a.id = aa.assessment_id', 'left')
            ->where('aa.student_id', $studentId)
            ->where('ac.learning_objective_id', $objectiveId)
            ->whereIn('a.assessment_type', self::ATTAINMENT_TYPES)
            ->get()->getResultArray();

        $failing = [];
        foreach ($rows as $row) {
            $status = $row['status'] ?: $this->deriveCriterionStatus(
                $row['score'] !== null ? (float) $row['score'] : null,
                $row['max_score'] !== null ? (float) $row['max_score'] : null,
                $row['level_index'] !== null ? (int) $row['level_index'] : null
            );
            if (in_array($status, [self::RESULT_NEEDS_SUPPORT, self::RESULT_DEVELOPING], true)) {
                $failing[] = ['criterion_id' => (int) $row['criterion_id'], 'criterion' => $row['criterion'], 'status' => $status];
            }
        }

        usort($failing, static fn (array $a, array $b): int => self::STATUS_PRIORITY[$a['status']] <=> self::STATUS_PRIORITY[$b['status']]);

        return $failing;
    }

    /**
     * Manually creates an intervention (REMEDIAL, REINFORCEMENT, or ENRICHMENT)
     * for a student-TP pair. Returns the new intervention id.
     */
    public function createIntervention(int $studentId, int $objectiveId, string $type, string $plannedActivity, int $userId, array $opts = []): int
    {
        if (! in_array($type, [self::INTERVENTION_REMEDIAL, self::INTERVENTION_REINFORCEMENT, self::INTERVENTION_ENRICHMENT], true)) {
            throw new \InvalidArgumentException('Jenis intervensi tidak valid.');
        }
        if (trim($plannedActivity) === '') {
            throw new \InvalidArgumentException('Rencana kegiatan wajib diisi.');
        }

        $mastery = $this->masteryModel
            ->where('student_id', $studentId)
            ->where('learning_objective_id', $objectiveId)
            ->first();
        if (! $mastery) {
            throw new \InvalidArgumentException('Mastery TP untuk siswa ini belum tersedia.');
        }

        $criterionId = $opts['criterion_id'] ?? null;
        if (! empty($criterionId)) {
            $exists = $this->db->table('learning_objective_criteria')
                ->where('id', $criterionId)
                ->where('learning_objective_id', $objectiveId)
                ->countAllResults();
            if ($exists === 0) {
                throw new \InvalidArgumentException('Kriteria tidak cocok dengan TP yang dipilih.');
            }
        }

        $id = $this->interventionModel->insert([
            'uuid'                  => UuidService::v4(),
            'student_id'            => $studentId,
            'learning_objective_id' => $objectiveId,
            'criterion_id'          => $criterionId ?: null,
            'trigger_evidence_id'   => $mastery['evidence_id'],
            'intervention_type'     => $type,
            'planned_activity'      => trim($plannedActivity),
            'status'                => self::INTERVENTION_APPROVED,
            'created_by'            => $userId,
            'updated_by'            => $userId,
        ]);

        $this->db->table('audit_logs')->insert([
            'unit_id'     => $opts['unit_id'] ?? null,
            'user_id'     => $userId,
            'action'      => 'INTERVENTION_CREATE',
            'entity_type' => 'intervention',
            'entity_id'   => $id,
            'details'     => json_encode(['student_id' => $studentId, 'learning_objective_id' => $objectiveId, 'type' => $type]),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        return (int) $id;
    }

    private function recommendInterventionForPair(int $studentId, int $objectiveId, int $userId, array $failingCriteria = []): bool
    {
        $mastery = $this->masteryModel
            ->where('student_id', $studentId)
            ->where('learning_objective_id', $objectiveId)
            ->first();
        if (! $mastery || ! in_array($mastery['result'], [self::RESULT_NEEDS_SUPPORT, self::RESULT_DEVELOPING], true)) {
            return false;
        }

        $active = $this->interventionModel
            ->where('student_id', $studentId)
            ->where('learning_objective_id', $objectiveId)
            ->whereIn('status', [self::INTERVENTION_RECOMMENDED, self::INTERVENTION_APPROVED])
            ->first();
        if ($active) {
            return false;
        }

        if ($failingCriteria === []) {
            $failingCriteria = $this->failingCriteriaForPair($studentId, $objectiveId);
        }
        $targetCriterion = $failingCriteria[0] ?? null;

        $type = $mastery['result'] === self::RESULT_NEEDS_SUPPORT
            ? self::INTERVENTION_REMEDIAL
            : self::INTERVENTION_REINFORCEMENT;

        $template = $mastery['result'] === self::RESULT_NEEDS_SUPPORT
            ? 'Program remedial dan bimbingan ulang untuk mencapai TP.'
            : 'Penguatan materi dan latihan lanjutan untuk menguasai TP.';

        if ($targetCriterion !== null && ! empty($targetCriterion['criterion'])) {
            $template .= ' Fokus kriteria: ' . $targetCriterion['criterion'];
        }

        $this->interventionModel->insert([
            'uuid'                  => UuidService::v4(),
            'student_id'            => $studentId,
            'learning_objective_id' => $objectiveId,
            'criterion_id'          => $targetCriterion !== null ? $targetCriterion['criterion_id'] : null,
            'trigger_evidence_id'   => $mastery['evidence_id'],
            'intervention_type'     => $type,
            'planned_activity'      => $template,
            'status'                => self::INTERVENTION_RECOMMENDED,
            'created_by'            => $userId,
            'updated_by'            => $userId,
        ]);

        return true;
    }
}