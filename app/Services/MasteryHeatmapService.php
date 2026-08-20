<?php

namespace App\Services;

use Config\Database;

/**
 * MasteryHeatmapService — Drives the interactive Mastery TP Heatmap visualisation.
 *
 * Builds a JSON-ready matrix of students × TP objectives with colour-coded
 * mastery levels, growth velocity indicators, and class-level distribution
 * statistics for the heatmap view.
 */
class MasteryHeatmapService
{
    /** Mastery result → numeric score for velocity calculation. */
    private const RESULT_SCORES = [
        'NEEDS_SUPPORT' => 0,
        'DEVELOPING'    => 1,
        'ACHIEVED'      => 2,
        'ADVANCED'      => 3,
    ];

    /** Mastery result → CSS colour class mapping for the heatmap cells. */
    public const RESULT_COLORS = [
        'NEEDS_SUPPORT' => ['bg' => '#dc3545', 'text' => '#fff',    'label' => 'Perlu Pendampingan', 'class' => 'heatmap-needs-support'],
        'DEVELOPING'    => ['bg' => '#ffc107', 'text' => '#212529', 'label' => 'Sedang Berkembang',   'class' => 'heatmap-developing'],
        'ACHIEVED'      => ['bg' => '#198754', 'text' => '#fff',    'label' => 'Tercapai',            'class' => 'heatmap-achieved'],
        'ADVANCED'      => ['bg' => '#0d6efd', 'text' => '#fff',    'label' => 'Melampaui',           'class' => 'heatmap-advanced'],
    ];

    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Build the heatmap data structure for a specific classroom + subject combination.
     *
     * Returns an array with keys:
     *   - students    → ordered list of student info
     *   - objectives  → ordered list of TP objectives (columns)
     *   - matrix      → [student_id][objective_id] => { result, score, velocity, updated_at }
     *   - distribution → [objective_id] => { NEEDS_SUPPORT: n, DEVELOPING: n, ACHIEVED: n, ADVANCED: n }
     *   - classSummary → overall class mastery percentages
     */
    public function buildHeatmap(int $unitId, int $periodId, int $classroomId, int $subjectId): array
    {
        // 1. Get objectives (TPs) for this subject
        $objectives = [];
        if ($this->db->tableExists('learning_objectives_tp')) {
            $objectives = $this->db->table('learning_objectives_tp lo')
                ->select('lo.id, lo.code, lo.statement as name, lo.phase')
                ->join('learning_outcomes_cp lout', 'lout.id = lo.learning_outcome_id', 'left')
                ->where('lout.subject_id', $subjectId)
                ->orderBy('lo.code', 'ASC')
                ->get()->getResultArray();
        }

        if (empty($objectives) && $this->db->tableExists('assessment_objectives')) {
            // Fallback: query objectives via assessment_objectives
            $objectives = $this->db->table('assessment_objectives ao')
                ->select('ao.learning_objective_id as id, ao.name, ao.code')
                ->join('assessments a', 'a.id = ao.assessment_id')
                ->where('a.unit_id', $unitId)
                ->where('a.period_id', $periodId)
                ->where('a.subject_id', $subjectId)
                ->groupBy('ao.learning_objective_id')
                ->orderBy('ao.code', 'ASC')
                ->get()->getResultArray();
        }

        // 2. Get students in this classroom
        $students = $this->db->table('elective_students')
            ->select('id, full_name, student_number')
            ->where('classroom_id', $classroomId)
            ->where('is_active', 1)
            ->orderBy('full_name', 'ASC')
            ->get()->getResultArray();

        // 3. Get mastery records
        $objectiveIds = array_column($objectives, 'id');
        $studentIds   = array_column($students, 'id');

        $masteryRecords = [];
        if (!empty($objectiveIds) && !empty($studentIds)) {
            $records = $this->db->table('mastery_records')
                ->select('student_id, learning_objective_id, result, updated_at')
                ->whereIn('student_id', $studentIds)
                ->whereIn('learning_objective_id', $objectiveIds)
                ->get()->getResultArray();

            foreach ($records as $r) {
                $masteryRecords[$r['student_id']][$r['learning_objective_id']] = $r;
            }
        }

        // 4. Build matrix
        $matrix = [];
        $distribution = [];

        foreach ($objectives as $obj) {
            $distribution[$obj['id']] = [
                'NEEDS_SUPPORT' => 0,
                'DEVELOPING'    => 0,
                'ACHIEVED'      => 0,
                'ADVANCED'      => 0,
                'UNASSESSED'    => 0,
            ];
        }

        foreach ($students as $student) {
            foreach ($objectives as $obj) {
                $record = $masteryRecords[$student['id']][$obj['id']] ?? null;
                $result = $record['result'] ?? null;

                $cell = [
                    'result'     => $result,
                    'score'      => $result ? (self::RESULT_SCORES[$result] ?? 0) : null,
                    'updated_at' => $record['updated_at'] ?? null,
                    'color'      => $result ? (self::RESULT_COLORS[$result] ?? null) : null,
                ];

                $matrix[$student['id']][$obj['id']] = $cell;

                if ($result && isset($distribution[$obj['id']][$result])) {
                    $distribution[$obj['id']][$result]++;
                } else {
                    $distribution[$obj['id']]['UNASSESSED']++;
                }
            }
        }

        // 5. Calculate class summary
        $totalCells = count($students) * count($objectives);
        $classSummary = [
            'total_cells'       => $totalCells,
            'assessed'          => 0,
            'achieved_rate'     => 0,
            'needs_support_pct' => 0,
            'developing_pct'    => 0,
            'achieved_pct'      => 0,
            'advanced_pct'      => 0,
        ];

        if ($totalCells > 0) {
            $counts = ['NEEDS_SUPPORT' => 0, 'DEVELOPING' => 0, 'ACHIEVED' => 0, 'ADVANCED' => 0];
            foreach ($distribution as $objDist) {
                foreach (['NEEDS_SUPPORT', 'DEVELOPING', 'ACHIEVED', 'ADVANCED'] as $r) {
                    $counts[$r] += $objDist[$r];
                }
            }
            $assessed = array_sum($counts);
            $classSummary['assessed'] = $assessed;
            if ($assessed > 0) {
                $classSummary['needs_support_pct'] = round($counts['NEEDS_SUPPORT'] / $assessed * 100, 1);
                $classSummary['developing_pct']    = round($counts['DEVELOPING'] / $assessed * 100, 1);
                $classSummary['achieved_pct']      = round($counts['ACHIEVED'] / $assessed * 100, 1);
                $classSummary['advanced_pct']      = round($counts['ADVANCED'] / $assessed * 100, 1);
                $classSummary['achieved_rate']     = round(($counts['ACHIEVED'] + $counts['ADVANCED']) / $assessed * 100, 1);
            }
        }

        // 6. Student-level aggregation (row summary)
        $studentSummaries = [];
        foreach ($students as $student) {
            $rowResults = [];
            foreach ($objectives as $obj) {
                $cell = $matrix[$student['id']][$obj['id']] ?? null;
                if ($cell && $cell['result']) {
                    $rowResults[] = self::RESULT_SCORES[$cell['result']] ?? 0;
                }
            }
            $avgScore = count($rowResults) > 0 ? round(array_sum($rowResults) / count($rowResults), 2) : null;
            $achievedCount = count(array_filter($rowResults, fn($s) => $s >= 2));
            $totalObjectives = count($objectives);

            $studentSummaries[$student['id']] = [
                'avg_score'       => $avgScore,
                'achieved_count'  => $achievedCount,
                'total_objectives' => $totalObjectives,
                'completion_pct'  => $totalObjectives > 0 ? round($achievedCount / $totalObjectives * 100, 1) : 0,
            ];
        }

        return [
            'students'         => $students,
            'objectives'       => $objectives,
            'matrix'           => $matrix,
            'distribution'     => $distribution,
            'classSummary'     => $classSummary,
            'studentSummaries' => $studentSummaries,
            'resultColors'     => self::RESULT_COLORS,
        ];
    }

    /**
     * Build column distribution chart data for ApexCharts.
     */
    public function distributionChartData(array $distribution, array $objectives): array
    {
        $categories = [];
        $series = [
            ['name' => 'Perlu Pendampingan', 'data' => [], 'color' => '#dc3545'],
            ['name' => 'Sedang Berkembang',   'data' => [], 'color' => '#ffc107'],
            ['name' => 'Tercapai',            'data' => [], 'color' => '#198754'],
            ['name' => 'Melampaui',           'data' => [], 'color' => '#0d6efd'],
        ];

        foreach ($objectives as $obj) {
            $categories[] = $obj['code'] ?? ('TP-' . $obj['id']);
            $dist = $distribution[$obj['id']] ?? [];
            $series[0]['data'][] = $dist['NEEDS_SUPPORT'] ?? 0;
            $series[1]['data'][] = $dist['DEVELOPING'] ?? 0;
            $series[2]['data'][] = $dist['ACHIEVED'] ?? 0;
            $series[3]['data'][] = $dist['ADVANCED'] ?? 0;
        }

        return ['categories' => $categories, 'series' => $series];
    }
}
