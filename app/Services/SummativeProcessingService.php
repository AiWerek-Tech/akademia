<?php

namespace App\Services;

use App\Models\SummativeResultModel;
use Config\Database;
use RuntimeException;

/**
 * Phase 6 — Summative Processing Engine.
 *
 * Turns evidence-backed mastery records into a final attainment score per
 * student/subject/period using the school's active reporting policy
 * (blueprint §8 & §9). Results start as DRAFT and require a teacher
 * validation step before being treated as final.
 */
class SummativeProcessingService
{
    public const METHOD_AVERAGE     = 'AVERAGE';
    public const METHOD_LATEST      = 'LATEST';
    public const METHOD_WEIGHTED    = 'WEIGHTED';
    public const METHOD_PROFICIENCY = 'PROFICIENCY';

    public const ALLOWED_METHODS = [
        self::METHOD_AVERAGE,
        self::METHOD_LATEST,
        self::METHOD_WEIGHTED,
        self::METHOD_PROFICIENCY,
    ];

    public const STATUS_DRAFT     = 'DRAFT';
    public const STATUS_VALIDATED = 'VALIDATED';

    private const DEFAULT_RESULT_SCORES = [
        MasteryService::RESULT_NEEDS_SUPPORT => 60,
        MasteryService::RESULT_DEVELOPING    => 75,
        MasteryService::RESULT_ACHIEVED      => 88,
        MasteryService::RESULT_ADVANCED      => 95,
    ];

    private const DEFAULT_CUTOFFS = [
        ['min' => 90, 'label' => 'A'],
        ['min' => 80, 'label' => 'B'],
        ['min' => 70, 'label' => 'C'],
        ['min' => 60, 'label' => 'D'],
        ['min' => 0,  'label' => 'E'],
    ];

    private $db;
    private SummativeResultModel $model;

    public function __construct()
    {
        $this->db    = Database::connect();
        $this->model = new SummativeResultModel();
    }

    /**
     * Latest reporting policy for a subject in scope (active flag preferred,
     * latest version as fallback).
     */
    public function activePolicy(int $unitId, int $periodId, int $subjectId): ?array
    {
        $row = $this->db->table('reporting_policies')
            ->where('unit_id', $unitId)
            ->where('academic_period_id', $periodId)
            ->where('subject_id', $subjectId)
            ->where('is_active', 1)
            ->orderBy('version', 'DESC')
            ->get()->getRowArray();

        if ($row) {
            return $row;
        }

        return $this->db->table('reporting_policies')
            ->where('unit_id', $unitId)
            ->where('academic_period_id', $periodId)
            ->where('subject_id', $subjectId)
            ->orderBy('version', 'DESC')
            ->get()->getRowArray() ?: null;
    }

    /**
     * Processes mastery → attainment for a whole subject. Validated results
     * are locked and never overwritten.
     */
    public function processSubject(int $unitId, int $periodId, int $subjectId, int $userId): array
    {
        $policy = $this->activePolicy($unitId, $periodId, $subjectId);
        $method = $policy ? strtoupper((string) $policy['calculation_method']) : self::METHOD_AVERAGE;
        if (! in_array($method, self::ALLOWED_METHODS, true)) {
            $method = self::METHOD_AVERAGE;
        }

        $config = $policy && ! empty($policy['config_json']) ? json_decode($policy['config_json'], true) : [];
        if (! is_array($config)) {
            $config = [];
        }

        $scoreMap = array_merge(self::DEFAULT_RESULT_SCORES, (array) ($config['result_scores'] ?? []));
        $cutoffs  = self::DEFAULT_CUTOFFS;
        if (! empty($config['grade_cutoffs']) && is_array($config['grade_cutoffs'])) {
            $cutoffs = $config['grade_cutoffs'];
        }

        $tpIds = $this->tpIdsForSubject($unitId, $subjectId);
        if ($tpIds === []) {
            return ['students' => 0, 'tps' => 0];
        }

        $tps = $this->db->table('learning_objectives_tp lot')
            ->select('lot.id, lot.code, lot.statement')
            ->whereIn('lot.id', $tpIds)
            ->orderBy('lot.code', 'ASC')
            ->get()->getResultArray();

        $weights = $this->tpWeights($unitId, $periodId, $subjectId, $tpIds);

        $students = $this->db->table('elective_students')
            ->where('unit_id', $unitId)
            ->where('is_active', 1)
            ->orderBy('full_name', 'ASC')
            ->get()->getResultArray();

        $processed = 0;
        foreach ($students as $student) {
            $studentId = (int) $student['id'];

            $masteryRows = $this->db->table('mastery_records')
                ->where('student_id', $studentId)
                ->whereIn('learning_objective_id', $tpIds)
                ->get()->getResultArray();
            $byTp = [];
            foreach ($masteryRows as $mr) {
                $byTp[(int) $mr['learning_objective_id']] = $mr;
            }

            $details = [];
            foreach ($tps as $tp) {
                $mr      = $byTp[(int) $tp['id']] ?? null;
                $result  = $mr ? strtoupper((string) $mr['result']) : null;
                $details[] = [
                    'tp_id'     => (int) $tp['id'],
                    'tp_code'   => $tp['code'],
                    'tp'        => $tp['statement'],
                    'result'    => $result,
                    'score'     => $result !== null ? ($scoreMap[$result] ?? null) : null,
                    'updated_at'=> $mr ? $mr['updated_at'] : null,
                ];
            }

            $raw = $this->compute($details, $method, $weights);
            if ($raw === null) {
                continue; // no mastery data for this student
            }

            $existing = $this->model->where('unit_id', $unitId)
                ->where('academic_period_id', $periodId)
                ->where('subject_id', $subjectId)
                ->where('student_id', $studentId)
                ->first();
            if ($existing && $existing['status'] === self::STATUS_VALIDATED) {
                continue; // validated results are locked
            }

            $data = [
                'uuid'                => UuidService::v4(),
                'unit_id'             => $unitId,
                'academic_period_id'  => $periodId,
                'subject_id'          => $subjectId,
                'student_id'          => $studentId,
                'reporting_policy_id' => $policy ? (int) $policy['id'] : null,
                'calculation_method'  => $method,
                'raw_score'           => $raw,
                'grade_label'         => $this->gradeLabel($raw, $cutoffs),
                'detail_json'         => json_encode(['tps' => $details, 'result_scores' => $scoreMap, 'method' => $method]),
                'status'              => self::STATUS_DRAFT,
                'created_by'          => $userId,
                'updated_by'          => $userId,
            ];

            if ($existing) {
                $this->model->update($existing['id'], $data);
            } else {
                $this->model->insert($data);
            }
            $processed++;
        }

        AuditService::log('assessment', 'SUMMATIVE_PROCESS', 'Subject', $subjectId, null, ['students' => $processed, 'method' => $method], 'Memproses nilai sumatif');

        return ['students' => $processed, 'tps' => count($tps)];
    }

    public function processAll(int $unitId, int $periodId, int $userId): array
    {
        $subjects = $this->db->table('subjects')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        $totals = ['students' => 0, 'subjects' => 0];
        foreach ($subjects as $subject) {
            $r = $this->processSubject($unitId, $periodId, (int) $subject['id'], $userId);
            $totals['students'] += $r['students'];
            if ($r['students'] > 0) {
                $totals['subjects']++;
            }
        }

        return $totals;
    }

    public function subjectStatus(int $unitId, int $periodId): array
    {
        return $this->db->table('summative_results sr')
            ->select('sr.subject_id, s.name as subject_name, COUNT(*) as total, '
                . 'SUM(CASE WHEN sr.status = ' . $this->db->escape(self::STATUS_VALIDATED) . ' THEN 1 ELSE 0 END) as validated, '
                . 'MAX(sr.updated_at) as processed_at')
            ->join('subjects s', 's.id = sr.subject_id', 'left')
            ->where('sr.unit_id', $unitId)
            ->where('sr.academic_period_id', $periodId)
            ->groupBy('sr.subject_id')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();
    }

    public function results(int $unitId, int $periodId, int $subjectId, int $classroomId = 0): array
    {
        $builder = $this->db->table('summative_results sr')
            ->select('sr.id, sr.student_id, sr.calculation_method, sr.raw_score, sr.grade_label, sr.detail_json, sr.status, sr.validated_by, sr.validated_at, es.full_name, es.student_number, c.name as classroom_name')
            ->join('elective_students es', 'es.id = sr.student_id', 'left')
            ->join('classrooms c', 'c.id = es.classroom_id', 'left')
            ->where('sr.unit_id', $unitId)
            ->where('sr.academic_period_id', $periodId)
            ->where('sr.subject_id', $subjectId);

        if ($classroomId > 0) {
            $builder->where('es.classroom_id', $classroomId);
        }

        return $builder->orderBy('c.name', 'ASC')->orderBy('es.full_name', 'ASC')->get()->getResultArray();
    }

    public function validate(int $id, int $userId): void
    {
        $existing = $this->model->find($id);
        if (! $existing) {
            throw new RuntimeException('Hasil sumatif tidak ditemukan.');
        }
        if ($existing['status'] === self::STATUS_VALIDATED) {
            throw new RuntimeException('Hasil sumatif sudah tervalidasi.');
        }

        $this->model->update($id, [
            'status'       => self::STATUS_VALIDATED,
            'validated_by' => $userId,
            'validated_at' => date('Y-m-d H:i:s'),
            'updated_by'   => $userId,
        ]);
        AuditService::log('assessment', 'SUMMATIVE_VALIDATE', 'SummativeResult', $id, $existing, ['validated_by' => $userId], 'Validasi hasil sumatif');
    }

    public function reopen(int $id, int $userId): void
    {
        $existing = $this->model->find($id);
        if (! $existing) {
            throw new RuntimeException('Hasil sumatif tidak ditemukan.');
        }

        $this->model->update($id, [
            'status'       => self::STATUS_DRAFT,
            'validated_by' => null,
            'validated_at' => null,
            'updated_by'   => $userId,
        ]);
        AuditService::log('assessment', 'SUMMATIVE_REOPEN', 'SummativeResult', $id, $existing, null, 'Membuka kembali hasil sumatif');
    }

    // ----------------------------------------------------------------
    // Internals
    // ----------------------------------------------------------------

    private function tpIdsForSubject(int $unitId, int $subjectId): array
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

    /**
     * WEIGHTED weight per TP = number of criteria across attainment
     * assessments tied to that TP (fallback 1).
     */
    private function tpWeights(int $unitId, int $periodId, int $subjectId, array $tpIds): array
    {
        $rows = $this->db->table('assessment_criteria ac')
            ->select('ac.learning_objective_id, COUNT(*) as cnt')
            ->join('assessments a', 'a.id = ac.assessment_id', 'left')
            ->where('a.unit_id', $unitId)
            ->where('a.academic_period_id', $periodId)
            ->where('a.subject_id', $subjectId)
            ->whereIn('a.assessment_type', [MasteryService::TYPE_FORMATIVE, MasteryService::TYPE_SUMMATIVE])
            ->whereIn('ac.learning_objective_id', $tpIds)
            ->groupBy('ac.learning_objective_id')
            ->get()->getResultArray();

        $weights = [];
        foreach ($rows as $row) {
            $weights[(int) $row['learning_objective_id']] = max(1, (int) $row['cnt']);
        }

        return $weights;
    }

    private function compute(array $details, string $method, array $weights): ?float
    {
        $scores = [];
        foreach ($details as $d) {
            if ($d['result'] !== null && $d['score'] !== null) {
                $scores[] = ['score' => (float) $d['score'], 'tp_id' => (int) $d['tp_id'], 'updated_at' => $d['updated_at']];
            }
        }
        if ($scores === []) {
            return null;
        }

        switch ($method) {
            case self::METHOD_LATEST:
                usort($scores, static fn (array $a, array $b): int => strcmp((string) ($b['updated_at'] ?? ''), (string) ($a['updated_at'] ?? '')));
                return round((float) $scores[0]['score'], 2);

            case self::METHOD_WEIGHTED:
                $totalW = 0.0;
                $totalS = 0.0;
                foreach ($scores as $s) {
                    $w      = $weights[$s['tp_id']] ?? 1;
                    $totalW += $w;
                    $totalS += $s['score'] * $w;
                }

                return $totalW > 0 ? round($totalS / $totalW, 2) : null;

            case self::METHOD_PROFICIENCY:
                $achieved = count(array_filter($details, static fn (array $d): bool => in_array($d['result'], [MasteryService::RESULT_ACHIEVED, MasteryService::RESULT_ADVANCED], true)));

                return round(($achieved / count($details)) * 100, 2);

            case self::METHOD_AVERAGE:
            default:
                return round(array_sum(array_column($scores, 'score')) / count($scores), 2);
        }
    }

    private function gradeLabel(float $raw, array $cutoffs): string
    {
        foreach ($cutoffs as $c) {
            if ($raw >= (float) $c['min']) {
                return (string) $c['label'];
            }
        }

        return 'E';
    }
}