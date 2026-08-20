<?php

namespace App\Services;

use Config\Database;

/**
 * NarrativeService — Generates evidence-backed narrative report card drafts
 * (Deskripsi Capaian Kompetensi Rapor) based on student mastery across all TPs.
 */
class NarrativeService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Generate competency narrative report draft for a student in a subject.
     *
     * @param int $studentId Student ID
     * @param int $subjectId Subject ID
     * @param int $periodId  Academic Period ID
     * @return array Narrative draft with highest strengths, areas for growth, and composite report paragraph
     */
    public function generateStudentNarrative(int $studentId, int $subjectId, int $periodId): array
    {
        // 1. Get student & subject
        $student = null;
        if ($this->db->tableExists('elective_students')) {
            $student = $this->db->table('elective_students')
                ->select('id, full_name, student_number')
                ->where('id', $studentId)
                ->get()->getRowArray();
        }
        if (!$student && $this->db->tableExists('students')) {
            $student = $this->db->table('students')
                ->select('id, full_name, student_number')
                ->where('id', $studentId)
                ->get()->getRowArray();
        }

        $subject = $this->db->table('subjects')
            ->select('id, name, code')
            ->where('id', $subjectId)
            ->get()->getRowArray();

        // 2. Get all objectives and mastery records
        $records = [];
        if ($this->db->tableExists('learning_objectives_tp')) {
            $records = $this->db->table('learning_objectives_tp lo')
                ->select('lo.id, lo.code, lo.statement as name, lo.phase, mr.result, mr.notes, mr.updated_at')
                ->join('learning_outcomes_cp lout', 'lout.id = lo.learning_outcome_id', 'left')
                ->join('mastery_records mr', 'mr.learning_objective_id = lo.id AND mr.student_id = ' . (int) $studentId, 'left')
                ->where('lout.subject_id', $subjectId)
                ->orderBy('lo.code', 'ASC')
                ->get()->getResultArray();
        }

        $achievedOrAbove = [];
        $needsImprovement = [];

        foreach ($records as $r) {
            $result = $r['result'] ?? null;
            if ($result === 'ADVANCED' || $result === 'ACHIEVED') {
                $achievedOrAbove[] = $r;
            } elseif ($result === 'DEVELOPING' || $result === 'NEEDS_SUPPORT') {
                $needsImprovement[] = $r;
            }
        }

        $studentName = $student['full_name'] ?? 'Ananda';

        // 3. Compose strengths clause
        $strengthsText = '';
        if (!empty($achievedOrAbove)) {
            $strongDescriptions = array_map(function($r) {
                return $r['name'] ?: $r['code'];
            }, array_slice($achievedOrAbove, 0, 2));

            $strengthsText = 'Menunjukkan penguasaan yang sangat baik dalam ' . implode(' serta ', $strongDescriptions) . '.';
        } else {
            $strengthsText = 'Menunjukkan partisipasi aktif dalam mengikuti kegiatan pembelajaran.';
        }

        // 4. Compose growth clause
        $growthText = '';
        if (!empty($needsImprovement)) {
            $growthDescriptions = array_map(function($r) {
                return $r['name'] ?: $r['code'];
            }, array_slice($needsImprovement, 0, 2));

            $growthText = 'Perlu pendampingan dan latihan lebih lanjut dalam ' . implode(' serta ', $growthDescriptions) . '.';
        } else {
            $growthText = 'Pertahankan konsistensi capaian belajar yang telah diraih dengan sangat memuaskan.';
        }

        // 5. Composite narrative paragraph
        $compositeNarrative = $studentName . ' ' . $strengthsText . ' ' . $growthText;

        return [
            'student'             => $student,
            'subject'             => $subject,
            'achieved_count'      => count($achievedOrAbove),
            'needs_growth_count'  => count($needsImprovement),
            'total_objectives'    => count($records),
            'strengths'           => $achievedOrAbove,
            'growth_areas'        => $needsImprovement,
            'draft_strengths'     => $strengthsText,
            'draft_growth'        => $growthText,
            'composite_narrative' => $compositeNarrative,
            'generated_at'        => date('Y-m-d H:i:s'),
        ];
    }
}
