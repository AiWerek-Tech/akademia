<?php

namespace App\Services;

use Config\Database;

/**
 * CurriculumLineageService — Builds the DAG (Directed Acyclic Graph) of
 * curriculum lineage from Regulasi → CP → TP → ATP → Learning Pack → RPP.
 *
 * Provides:
 *   - Full lineage graph data for D3.js/Mermaid visualisation
 *   - Coverage gap detection (CP without TP, TP without ATP, etc.)
 *   - Health score calculation per subject
 */
class CurriculumLineageService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Build the full lineage graph for a subject within a unit.
     *
     * Returns:
     *   - nodes: array of { id, type, label, code, status }
     *   - edges: array of { source, target, relation }
     *   - gaps:  array of detected coverage issues
     *   - health: overall health score 0–100
     */
    public function buildLineageGraph(int $unitId, ?int $subjectId = null): array
    {
        $nodes = [];
        $edges = [];
        $gaps  = [];

        // 1. Regulations (root level)
        $regulations = [];
        if ($this->db->tableExists('regulations')) {
            $regulations = $this->db->table('regulations')
                ->select('id, code, title, regulation_type as type')
                ->orderBy('code', 'ASC')
                ->get()->getResultArray();

            foreach ($regulations as $reg) {
                $nodes[] = [
                    'id'    => 'reg-' . $reg['id'],
                    'type'  => 'regulation',
                    'label' => $reg['title'],
                    'code'  => $reg['code'],
                    'group' => 'regulation',
                ];
            }
        }

        // 2. Learning Outcomes (CP — Capaian Pembelajaran)
        $outcomes = [];
        if ($this->db->tableExists('learning_outcomes_cp')) {
            $cpQuery = $this->db->table('learning_outcomes_cp lo')
                ->select('lo.id, lo.code, lo.statement as name, lo.phase, lo.subject_id, lo.curriculum_source_id, s.name as subject_name')
                ->join('subjects s', 's.id = lo.subject_id', 'left');

            if ($subjectId) {
                $cpQuery->where('lo.subject_id', $subjectId);
            }

            $outcomes = $cpQuery->orderBy('lo.code', 'ASC')->get()->getResultArray();

            foreach ($outcomes as $cp) {
                $nodes[] = [
                    'id'      => 'cp-' . $cp['id'],
                    'type'    => 'learning_outcome',
                    'label'   => $cp['name'] ?: $cp['code'],
                    'code'    => $cp['code'],
                    'phase'   => $cp['phase'] ?? '',
                    'subject' => $cp['subject_name'] ?? '',
                    'group'   => 'cp',
                ];

                // Edge: regulation → CP
                if (!empty($regulations)) {
                    $edges[] = [
                        'source'   => 'reg-' . $regulations[0]['id'],
                        'target'   => 'cp-' . $cp['id'],
                        'relation' => 'derives',
                    ];
                }
            }
        }

        // 3. Learning Objectives (TP — Tujuan Pembelajaran)
        $objectives = [];
        if ($this->db->tableExists('learning_objectives_tp')) {
            $tpQuery = $this->db->table('learning_objectives_tp lo')
                ->select('lo.id, lo.code, lo.statement as name, lo.learning_outcome_id, lo.status');

            $cpIds = array_column($outcomes, 'id');
            if (!empty($cpIds)) {
                $tpQuery->whereIn('lo.learning_outcome_id', $cpIds);
            } elseif ($subjectId) {
                $tpQuery->where('1', '0');
            }

            $objectives = $tpQuery->orderBy('lo.code', 'ASC')->get()->getResultArray();

            foreach ($objectives as $tp) {
                $nodes[] = [
                    'id'     => 'tp-' . $tp['id'],
                    'type'   => 'learning_objective',
                    'label'  => $tp['name'] ?: $tp['code'],
                    'code'   => $tp['code'],
                    'status' => $tp['status'] ?? 'draft',
                    'group'  => 'tp',
                ];

                if (!empty($tp['learning_outcome_id'])) {
                    $edges[] = [
                        'source'   => 'cp-' . $tp['learning_outcome_id'],
                        'target'   => 'tp-' . $tp['id'],
                        'relation' => 'breaks_down',
                    ];
                }
            }
        }

        // 4. Learning Sequences (ATP — Alur Tujuan Pembelajaran)
        $tpIds = array_column($objectives, 'id');
        if (!empty($tpIds) && $this->db->tableExists('learning_sequence_items') && $this->db->tableExists('learning_sequences_atp')) {
            $atpItems = $this->db->table('learning_sequence_items lsi')
                ->select('lsi.id, lsi.learning_sequence_id, lsi.learning_objective_id, lsi.sequence_order, ls.code as seq_code, ls.title as seq_title')
                ->join('learning_sequences_atp ls', 'ls.id = lsi.learning_sequence_id')
                ->whereIn('lsi.learning_objective_id', $tpIds)
                ->orderBy('lsi.sequence_order', 'ASC')
                ->get()->getResultArray();

            $seenSequences = [];
            foreach ($atpItems as $item) {
                $seqNodeId = 'atp-' . $item['learning_sequence_id'];
                if (!in_array($seqNodeId, $seenSequences, true)) {
                    $nodes[] = [
                        'id'    => $seqNodeId,
                        'type'  => 'learning_sequence',
                        'label' => $item['seq_title'] ?: $item['seq_code'],
                        'code'  => $item['seq_code'],
                        'group' => 'atp',
                    ];
                    $seenSequences[] = $seqNodeId;
                }

                $edges[] = [
                    'source'   => 'tp-' . $item['learning_objective_id'],
                    'target'   => $seqNodeId,
                    'relation' => 'sequenced_in',
                ];
            }
        }

        // 5. Lesson Plans (RPP)
        $plans = [];
        if ($subjectId && $this->db->tableExists('lesson_plans')) {
            $plans = $this->db->table('lesson_plans lp')
                ->select('lp.id, lp.title, lp.status')
                ->where('lp.subject_id', $subjectId)
                ->where('lp.unit_id', $unitId)
                ->orderBy('lp.title', 'ASC')
                ->get()->getResultArray();

            foreach ($plans as $plan) {
                $nodes[] = [
                    'id'     => 'rpp-' . $plan['id'],
                    'type'   => 'lesson_plan',
                    'label'  => $plan['title'],
                    'code'   => 'RPP-' . $plan['id'],
                    'status' => $plan['status'] ?? 'draft',
                    'group'  => 'rpp',
                ];
            }

            // Link RPP → TP via lesson_plan_objectives if exists
            $rppIds = array_column($plans, 'id');
            if (!empty($rppIds) && $this->db->tableExists('lesson_plan_objectives')) {
                $rppObjectives = $this->db->table('lesson_plan_objectives')
                    ->select('lesson_plan_id, learning_objective_id')
                    ->whereIn('lesson_plan_id', $rppIds)
                    ->get()->getResultArray();

                foreach ($rppObjectives as $link) {
                    $edges[] = [
                        'source'   => 'tp-' . $link['learning_objective_id'],
                        'target'   => 'rpp-' . $link['lesson_plan_id'],
                        'relation' => 'implemented_in',
                    ];
                }
            }
        }

        // 6. Gap detection
        $gaps = $this->detectGaps($nodes, $edges, $objectives, $outcomes);

        // 7. Health score
        $health = $this->calculateHealth($gaps, count($outcomes), count($objectives));

        return [
            'nodes'  => $nodes,
            'edges'  => $edges,
            'gaps'   => $gaps,
            'health' => $health,
            'stats'  => [
                'regulations' => count($regulations),
                'outcomes'    => count($outcomes),
                'objectives'  => count($objectives),
                'plans'       => count($plans),
            ],
        ];
    }

    /**
     * Detect coverage gaps in the lineage.
     */
    private function detectGaps(array $nodes, array $edges, array $objectives, array $outcomes): array
    {
        $gaps = [];

        // Check CPs without any TP
        $cpWithTp = array_unique(array_column(
            array_filter($edges, fn($e) => $e['relation'] === 'breaks_down'),
            'source'
        ));
        foreach ($outcomes as $cp) {
            if (!in_array('cp-' . $cp['id'], $cpWithTp, true)) {
                $gaps[] = [
                    'type'     => 'cp_without_tp',
                    'severity' => 'warning',
                    'message'  => 'CP "' . ($cp['code'] ?? $cp['name']) . '" belum memiliki TP turunan',
                    'node_id'  => 'cp-' . $cp['id'],
                ];
            }
        }

        // Check TPs without RPP
        $tpWithRpp = array_unique(array_column(
            array_filter($edges, fn($e) => $e['relation'] === 'implemented_in'),
            'source'
        ));
        foreach ($objectives as $tp) {
            if (!in_array('tp-' . $tp['id'], $tpWithRpp, true)) {
                $gaps[] = [
                    'type'     => 'tp_without_rpp',
                    'severity' => 'info',
                    'message'  => 'TP "' . ($tp['code'] ?? $tp['name']) . '" belum memiliki RPP terkait',
                    'node_id'  => 'tp-' . $tp['id'],
                ];
            }
        }

        return $gaps;
    }

    /**
     * Calculate a health score from 0–100.
     */
    private function calculateHealth(array $gaps, int $cpCount, int $tpCount): array
    {
        $total  = $cpCount + $tpCount;
        if ($total === 0) {
            return ['score' => 100, 'label' => 'Sempurna', 'color' => 'success'];
        }

        $warnings = count(array_filter($gaps, fn($g) => $g['severity'] === 'warning'));
        $infos    = count(array_filter($gaps, fn($g) => $g['severity'] === 'info'));

        $penalty = ($warnings * 10) + ($infos * 3);
        $score   = max(0, 100 - $penalty);

        if ($score >= 90) {
            return ['score' => $score, 'label' => 'Sangat Baik', 'color' => 'success'];
        }
        if ($score >= 70) {
            return ['score' => $score, 'label' => 'Baik', 'color' => 'primary'];
        }
        if ($score >= 50) {
            return ['score' => $score, 'label' => 'Perlu Perhatian', 'color' => 'warning'];
        }

        return ['score' => $score, 'label' => 'Perlu Perbaikan', 'color' => 'danger'];
    }

    /**
     * Get subjects available for lineage analysis in a unit.
     */
    public function getSubjectsWithLineage(int $unitId): array
    {
        if (!$this->db->tableExists('learning_outcomes_cp')) {
            return $this->db->table('subjects s')
                ->select('s.id, s.name, s.code')
                ->where('s.is_active', 1)
                ->orderBy('s.name', 'ASC')
                ->get()->getResultArray();
        }

        $subjects = $this->db->table('subjects s')
            ->select('s.id, s.name, s.code')
            ->join('learning_outcomes_cp lo', 'lo.subject_id = s.id', 'inner')
            ->where('s.is_active', 1)
            ->groupBy('s.id')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        if (empty($subjects)) {
            return $this->db->table('subjects s')
                ->select('s.id, s.name, s.code')
                ->where('s.is_active', 1)
                ->orderBy('s.name', 'ASC')
                ->get()->getResultArray();
        }

        return $subjects;
    }
}
