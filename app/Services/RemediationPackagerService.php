<?php

namespace App\Services;

use Config\Database;

/**
 * RemediationPackagerService — Generates structured remedial packages
 * for students needing support on specific learning objectives (TP).
 *
 * Bundles:
 *   - Targeted learning objective & failing criteria
 *   - Concept explanation & common misconception alerts
 *   - Step-by-step scaffolding exercises
 *   - Follow-up re-assessment criterion checklist
 */
class RemediationPackagerService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Package a complete remedial worksheet and plan for a student on a given TP.
     *
     * @param int $studentId   Student ID
     * @param int $objectiveId Learning Objective ID
     * @return array Remedial package containing diagnostic details, scaffolding exercises, and reassessment criteria
     */
    public function buildPackage(int $studentId, int $objectiveId): array
    {
        // 1. Get student info
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

        // 2. Get objective info
        $objective = null;
        if ($this->db->tableExists('learning_objectives_tp')) {
            $objective = $this->db->table('learning_objectives_tp lo')
                ->select('lo.id, lo.code, lo.statement as name, lo.phase, lo.learning_outcome_id, lout.statement as cp_name, s.name as subject_name')
                ->join('learning_outcomes_cp lout', 'lout.id = lo.learning_outcome_id', 'left')
                ->join('subjects s', 's.id = lout.subject_id', 'left')
                ->where('lo.id', $objectiveId)
                ->get()->getRowArray();
        }

        // 3. Get latest mastery record
        $mastery = $this->db->table('mastery_records')
            ->where('student_id', $studentId)
            ->where('learning_objective_id', $objectiveId)
            ->get()->getRowArray();

        // 4. Get related criteria
        $criteria = [];
        if ($this->db->tableExists('objective_criteria')) {
            $criteria = $this->db->table('objective_criteria')
                ->where('learning_objective_id', $objectiveId)
                ->orderBy('sort_order', 'ASC')
                ->get()->getResultArray();
        }

        // 5. Get learning unit concepts & misconceptions from Subject Learning Pack if available
        $concepts = [];
        if ($this->db->tableExists('learning_unit_concepts') && $this->db->tableExists('learning_units')) {
            $concepts = $this->db->table('learning_unit_concepts luc')
                ->select('luc.id, luc.concept_name, luc.explanation, luc.misconception, luc.misconception_remedy')
                ->join('learning_units lu', 'lu.id = luc.learning_unit_id')
                ->where('lu.learning_objective_id', $objectiveId)
                ->get()->getResultArray();
        }

        // Fallback concept explanation if not found in learning pack
        if (empty($concepts)) {
            $concepts = [
                [
                    'concept_name'         => 'Konsep Esensial ' . ($objective['code'] ?? 'TP'),
                    'explanation'          => 'Penjelasan inti materi berfokus pada penguasaan fondasi: ' . ($objective['name'] ?? ''),
                    'misconception'        => 'Kekeliruan umum pemahaman konsep langkah awal.',
                    'misconception_remedy' => 'Gunakan analogi konkret dan latihan terpandu sebelum mengerjakan soal mandiri.',
                ],
            ];
        }

        // 6. Generate 3-step scaffolding activities
        $scaffoldingSteps = [
            [
                'step'        => 1,
                'title'       => 'Klarifikasi & Re-Eksplanasi Konsep',
                'duration'    => '15 Menit',
                'type'        => 'Guided Discussion',
                'description' => 'Guru/Tutor sebaya menjelaskan kembali konsep kunci menggunakan bagan sederhana dan membedah miskonsepsi yang dialami siswa.',
                'deliverable' => 'Siswa merangkum 3 poin penting materi dalam kartu pemahaman.',
            ],
            [
                'step'        => 2,
                'title'       => 'Latihan Mandiri Terbimbing (Guided Practice)',
                'duration'    => '20 Menit',
                'type'        => 'Structured Worksheet',
                'description' => 'Siswa menyelesaikan 3–5 butir soal latihan berjenjang (dari level mengingat hingga menerapkan) dengan bimbingan bertahap.',
                'deliverable' => 'Lembar kerja remedial yang sudah terverifikasi kunci jawabannya.',
            ],
            [
                'step'        => 3,
                'title'       => 'Re-Asesmen Formatif (Exit Check)',
                'duration'    => '10 Menit',
                'type'        => 'Mini Assessment',
                'description' => 'Siswa mengerjakan 1–2 soal unjuk kerja untuk memvalidasi kenaikan tingkat ketercapaian kompetensi dari NEEDS_SUPPORT menjadi minimal DEVELOPING atau ACHIEVED.',
                'deliverable' => 'Bukti pencapaian baru yang dicatat pada papan Mastery TP.',
            ],
        ];

        return [
            'student'          => $student,
            'objective'        => $objective,
            'mastery'          => $mastery,
            'criteria'         => $criteria,
            'concepts'         => $concepts,
            'scaffoldingSteps' => $scaffoldingSteps,
            'package_code'     => 'REMEDIAL-' . ($studentId) . '-TP' . ($objectiveId),
            'generated_at'     => date('Y-m-d H:i:s'),
        ];
    }
}
