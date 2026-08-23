<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Phase 10 — Quality & AI Copilot Service.
 *
 * Manages teacher reflections, supervision records, and AI copilot governance.
 */
class QualityService
{
    // Reflection types
    public const REFLECTION_POST_LESSON  = 'POST_LESSON';
    public const REFLECTION_PERIODIC     = 'PERIODIC';
    public const REFLECTION_ANNUAL       = 'ANNUAL';

    public const ALLOWED_REFLECTION_TYPES = [
        self::REFLECTION_POST_LESSON,
        self::REFLECTION_PERIODIC,
        self::REFLECTION_ANNUAL,
    ];

    // Observation types
    public const OBS_CLASSROOM  = 'CLASSROOM';
    public const OBS_PEER       = 'PEER';
    public const OBS_VIRTUAL    = 'VIRTUAL';
    public const OBS_WALKTHROUGH = 'WALKTHROUGH';

    public const ALLOWED_OBSERVATION_TYPES = [
        self::OBS_CLASSROOM,
        self::OBS_PEER,
        self::OBS_VIRTUAL,
        self::OBS_WALKTHROUGH,
    ];

    // Ratings
    public const RATING_EXCELLENT = 'EXCELLENT';
    public const RATING_GOOD      = 'GOOD';
    public const RATING_SATISFACTORY = 'SATISFACTORY';
    public const RATING_NEEDS_IMPROVEMENT = 'NEEDS_IMPROVEMENT';

    public const ALLOWED_RATINGS = [
        self::RATING_EXCELLENT,
        self::RATING_GOOD,
        self::RATING_SATISFACTORY,
        self::RATING_NEEDS_IMPROVEMENT,
    ];

    // AI status
    public const AI_NONE     = 'NONE';
    public const AI_DRAFTED  = 'DRAFTED';
    public const AI_ACCEPTED = 'ACCEPTED';
    public const AI_REJECTED = 'REJECTED';

    // AI Feedback options per Blueprint §10.8
    public const FEEDBACK_USEFUL           = 'USEFUL';
    public const FEEDBACK_NEEDS_CORRECTION = 'NEEDS_CORRECTION';
    public const FEEDBACK_INAPPROPRIATE    = 'INAPPROPRIATE';
    public const FEEDBACK_WRONG_ALIGNMENT  = 'WRONG_ALIGNMENT';

    public const ALLOWED_FEEDBACK_RATINGS = [
        self::FEEDBACK_USEFUL,
        self::FEEDBACK_NEEDS_CORRECTION,
        self::FEEDBACK_INAPPROPRIATE,
        self::FEEDBACK_WRONG_ALIGNMENT,
    ];

    // AI copilot prompt types
    public const PROMPT_REFLECTION_DRAFT    = 'reflection_draft';
    public const PROMPT_SUPERVISION_SUMMARY = 'supervision_summary';
    public const PROMPT_IMPROVEMENT_IDEA    = 'improvement_idea';
    public const PROMPT_LEARNING_TIPS       = 'learning_tips';
    public const PROMPT_CLASS_SUMMARY       = 'class_summary';

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    // ================================================================
    // TEACHER REFLECTIONS
    // ================================================================

    public function listReflections(int $teacherId, int $periodId): array
    {
        return $this->db->table('teacher_reflections tr')
            ->select('tr.*, s.name as subject_name, c.name as classroom_name')
            ->join('subjects s', 's.id = tr.subject_id', 'left')
            ->join('classrooms c', 'c.id = tr.classroom_id', 'left')
            ->where('tr.teacher_id', $teacherId)
            ->where('tr.academic_period_id', $periodId)
            ->orderBy('tr.created_at', 'DESC')
            ->get()->getResultArray();
    }

    public function allReflections(int $unitId, int $periodId, array $filters = []): array
    {
        $builder = $this->db->table('teacher_reflections tr')
            ->select('tr.*, t.full_name as teacher_name, s.name as subject_name, c.name as classroom_name')
            ->join('teachers t', 't.id = tr.teacher_id', 'left')
            ->join('subjects s', 's.id = tr.subject_id', 'left')
            ->join('classrooms c', 'c.id = tr.classroom_id', 'left')
            ->where('tr.unit_id', $unitId)
            ->where('tr.academic_period_id', $periodId)
            ->orderBy('tr.created_at', 'DESC');

        if (! empty($filters['teacher_id'])) {
            $builder->where('tr.teacher_id', (int) $filters['teacher_id']);
        }
        if (! empty($filters['status'])) {
            $builder->where('tr.status', $filters['status']);
        }

        return $builder->get()->getResultArray();
    }

    public function detailReflection(int $id): ?array
    {
        return $this->db->table('teacher_reflections tr')
            ->select('tr.*, t.full_name as teacher_name, s.name as subject_name, c.name as classroom_name')
            ->join('teachers t', 't.id = tr.teacher_id', 'left')
            ->join('subjects s', 's.id = tr.subject_id', 'left')
            ->join('classrooms c', 'c.id = tr.classroom_id', 'left')
            ->where('tr.id', $id)
            ->get()->getRowArray();
    }

    public function createReflection(array $data, int $userId): int
    {
        $uuid = UuidService::v4();
        $now  = date('Y-m-d H:i:s');

        $this->db->table('teacher_reflections')->insert([
            'uuid'              => $uuid,
            'teacher_id'        => $data['teacher_id'],
            'unit_id'           => $data['unit_id'],
            'academic_period_id' => $data['academic_period_id'],
            'subject_id'        => $data['subject_id'] ?? null,
            'classroom_id'      => $data['classroom_id'] ?? null,
            'reflection_type'   => $data['reflection_type'] ?? self::REFLECTION_POST_LESSON,
            'what_went_well'    => $data['what_went_well'] ?? null,
            'what_to_improve'   => $data['what_to_improve'] ?? null,
            'next_steps'        => $data['next_steps'] ?? null,
            'status'            => 'DRAFT',
            'created_at'        => $now,
            'updated_at'        => $now,
            'created_by'        => $userId,
            'updated_by'        => $userId,
        ]);

        $id = (int) $this->db->insertID();
        AuditService::log('quality', 'CREATE', 'TeacherReflection', $id, null, ['teacher_id' => $data['teacher_id']], 'Membuat refleksi guru');

        return $id;
    }

    public function updateReflection(int $id, array $data, int $userId): void
    {
        $now = date('Y-m-d H:i:s');
        $payload = ['updated_at' => $now, 'updated_by' => $userId];

        foreach (['what_went_well', 'what_to_improve', 'next_steps', 'status', 'ai_draft', 'ai_status'] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        $this->db->table('teacher_reflections')->where('id', $id)->update($payload);
    }

    public function reflectionStats(int $unitId, int $periodId): array
    {
        $total = $this->db->table('teacher_reflections')
            ->where('unit_id', $unitId)
            ->where('academic_period_id', $periodId)
            ->countAllResults();

        $published = $this->db->table('teacher_reflections')
            ->where('unit_id', $unitId)
            ->where('academic_period_id', $periodId)
            ->where('status', 'PUBLISHED')
            ->countAllResults();

        $withAi = $this->db->table('teacher_reflections')
            ->where('unit_id', $unitId)
            ->where('academic_period_id', $periodId)
            ->where('ai_status !=', self::AI_NONE)
            ->countAllResults();

        return [
            'total'      => $total,
            'published'  => $published,
            'with_ai'    => $withAi,
            'draft'      => $total - $published,
        ];
    }

    public function reflectionTypeBreakdown(int $unitId, int $periodId): array
    {
        $records = $this->db->table('teacher_reflections')
            ->select('reflection_type, COUNT(*) as count')
            ->where('unit_id', $unitId)
            ->where('academic_period_id', $periodId)
            ->groupBy('reflection_type')
            ->get()->getResultArray();

        $result = [
            self::REFLECTION_POST_LESSON => 0,
            self::REFLECTION_PERIODIC    => 0,
            self::REFLECTION_ANNUAL      => 0,
        ];

        foreach ($records as $r) {
            if (isset($result[$r['reflection_type']])) {
                $result[$r['reflection_type']] = (int) $r['count'];
            }
        }

        return $result;
    }

    // ================================================================
    // SUPERVISION
    // ================================================================

    public function listSupervisions(int $unitId, int $periodId, array $filters = []): array
    {
        $builder = $this->db->table('supervision_records sr')
            ->select('sr.*, t.full_name as teacher_name, sup.full_name as supervisor_name, s.name as subject_name')
            ->join('teachers t', 't.id = sr.teacher_id', 'left')
            ->join('teachers sup', 'sup.id = sr.supervisor_id', 'left')
            ->join('subjects s', 's.id = sr.subject_id', 'left')
            ->where('sr.unit_id', $unitId)
            ->where('sr.academic_period_id', $periodId)
            ->orderBy('sr.observation_date', 'DESC');

        if (! empty($filters['teacher_id'])) {
            $builder->where('sr.teacher_id', (int) $filters['teacher_id']);
        }
        if (! empty($filters['status'])) {
            $builder->where('sr.status', $filters['status']);
        }
        if (! empty($filters['follow_up'])) {
            $builder->where('sr.follow_up_needed', 1);
        }

        return $builder->get()->getResultArray();
    }

    public function detailSupervision(int $id): ?array
    {
        return $this->db->table('supervision_records sr')
            ->select('sr.*, t.full_name as teacher_name, sup.full_name as supervisor_name, s.name as subject_name, c.name as classroom_name')
            ->join('teachers t', 't.id = sr.teacher_id', 'left')
            ->join('teachers sup', 'sup.id = sr.supervisor_id', 'left')
            ->join('subjects s', 's.id = sr.subject_id', 'left')
            ->join('classrooms c', 'c.id = sr.classroom_id', 'left')
            ->where('sr.id', $id)
            ->get()->getRowArray();
    }

    public function createSupervision(array $data, int $userId): int
    {
        $uuid = UuidService::v4();
        $now  = date('Y-m-d H:i:s');

        $this->db->table('supervision_records')->insert([
            'uuid'              => $uuid,
            'teacher_id'        => $data['teacher_id'],
            'supervisor_id'     => $data['supervisor_id'],
            'unit_id'           => $data['unit_id'],
            'academic_period_id' => $data['academic_period_id'],
            'observation_date'  => $data['observation_date'],
            'subject_id'        => $data['subject_id'] ?? null,
            'classroom_id'      => $data['classroom_id'] ?? null,
            'observation_type'  => $data['observation_type'] ?? self::OBS_CLASSROOM,
            'strengths'         => $data['strengths'] ?? null,
            'areas_for_growth'  => $data['areas_for_growth'] ?? null,
            'recommendations'   => $data['recommendations'] ?? null,
            'overall_rating'    => $data['overall_rating'] ?? null,
            'follow_up_needed'  => $data['follow_up_needed'] ?? 0,
            'follow_up_notes'   => $data['follow_up_notes'] ?? null,
            'status'            => 'DRAFT',
            'created_at'        => $now,
            'updated_at'        => $now,
            'created_by'        => $userId,
            'updated_by'        => $userId,
        ]);

        $id = (int) $this->db->insertID();
        AuditService::log('quality', 'CREATE', 'SupervisionRecord', $id, null, ['teacher_id' => $data['teacher_id']], 'Membuat catatan supervisi');

        return $id;
    }

    public function updateSupervision(int $id, array $data, int $userId): void
    {
        $now = date('Y-m-d H:i:s');
        $payload = ['updated_at' => $now, 'updated_by' => $userId];

        foreach (['strengths', 'areas_for_growth', 'recommendations', 'overall_rating', 'follow_up_needed', 'follow_up_notes', 'status'] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        $this->db->table('supervision_records')->where('id', $id)->update($payload);
    }

    public function supervisionStats(int $unitId, int $periodId): array
    {
        $total = $this->db->table('supervision_records')
            ->where('unit_id', $unitId)
            ->where('academic_period_id', $periodId)
            ->countAllResults();

        $followUp = $this->db->table('supervision_records')
            ->where('unit_id', $unitId)
            ->where('academic_period_id', $periodId)
            ->where('follow_up_needed', 1)
            ->countAllResults();

        $completed = $this->db->table('supervision_records')
            ->where('unit_id', $unitId)
            ->where('academic_period_id', $periodId)
            ->where('status', 'COMPLETED')
            ->countAllResults();

        return [
            'total'     => $total,
            'completed' => $completed,
            'follow_up' => $followUp,
            'draft'     => $total - $completed,
        ];
    }

    public function supervisionRatingBreakdown(int $unitId, int $periodId): array
    {
        $records = $this->db->table('supervision_records')
            ->select('overall_rating, COUNT(*) as count')
            ->where('unit_id', $unitId)
            ->where('academic_period_id', $periodId)
            ->groupBy('overall_rating')
            ->get()->getResultArray();

        $result = [
            self::RATING_EXCELLENT         => 0,
            self::RATING_GOOD              => 0,
            self::RATING_SATISFACTORY      => 0,
            self::RATING_NEEDS_IMPROVEMENT => 0,
        ];

        foreach ($records as $r) {
            if (!empty($r['overall_rating']) && isset($result[$r['overall_rating']])) {
                $result[$r['overall_rating']] = (int) $r['count'];
            }
        }

        return $result;
    }

    // ================================================================
    // AI COPILOT
    // ================================================================

    /**
     * Generate an AI draft using rule-based logic (no external API).
     */
    public function generateAiDraft(string $promptType, array $context, int $userId): array
    {
        $output = match ($promptType) {
            self::PROMPT_REFLECTION_DRAFT    => $this->draftReflection($context),
            self::PROMPT_SUPERVISION_SUMMARY  => $this->draftSupervisionSummary($context),
            self::PROMPT_IMPROVEMENT_IDEA     => $this->suggestImprovement($context),
            self::PROMPT_LEARNING_TIPS        => $this->suggestLearningTips($context),
            self::PROMPT_CLASS_SUMMARY        => $this->draftClassSummary($context),
            default                           => 'Tipe prompt tidak dikenali.',
        };

        $uuid = UuidService::v4();
        $now  = date('Y-m-d H:i:s');
        $contextJson = json_encode($context);

        $this->db->table('ai_copilot_outputs')->insert([
            'uuid'            => $uuid,
            'user_id'         => $userId,
            'prompt_type'     => $promptType,
            'context_json'    => $contextJson,
            'context_hash'    => hash('sha256', $contextJson),
            'input_prompt'    => $promptType,
            'output_text'     => $output,
            'model_provider'  => 'rule_engine',
            'approval_status' => 'DRAFT',
            'created_at'      => $now,
            'updated_at'      => $now,
            'created_by'      => $userId,
            'updated_by'      => $userId,
        ]);

        $id = (int) $this->db->insertID();
        AuditService::log('quality', 'AI_GENERATE', 'AiCopilotOutput', $id, null, ['prompt_type' => $promptType], 'AI copilot generate draft');

        return ['id' => $id, 'output' => $output];
    }

    public function acceptAiOutput(int $id, ?string $humanEdit, int $userId): void
    {
        $this->db->table('ai_copilot_outputs')
            ->where('id', $id)
            ->update([
                'approval_status' => 'ACCEPTED',
                'human_edit'      => $humanEdit,
                'updated_at'      => date('Y-m-d H:i:s'),
                'updated_by'      => $userId,
            ]);
    }

    public function rejectAiOutput(int $id, ?string $reason, int $userId): void
    {
        $this->db->table('ai_copilot_outputs')
            ->where('id', $id)
            ->update([
                'approval_status' => 'REJECTED',
                'human_edit'      => $reason,
                'updated_at'      => date('Y-m-d H:i:s'),
                'updated_by'      => $userId,
            ]);
    }

    public function submitFeedback(int $id, string $rating, ?string $notes, int $userId): void
    {
        $this->db->table('ai_copilot_outputs')
            ->where('id', $id)
            ->update([
                'feedback_rating' => $rating,
                'feedback_notes'  => $notes,
                'updated_at'      => date('Y-m-d H:i:s'),
                'updated_by'      => $userId,
            ]);
    }

    public function aiStats(int $userId): array
    {
        $total = $this->db->table('ai_copilot_outputs')->where('user_id', $userId)->countAllResults();
        $accepted = $this->db->table('ai_copilot_outputs')->where('user_id', $userId)->where('approval_status', 'ACCEPTED')->countAllResults();
        $rejected = $this->db->table('ai_copilot_outputs')->where('user_id', $userId)->where('approval_status', 'REJECTED')->countAllResults();

        return [
            'total'    => $total,
            'accepted' => $accepted,
            'rejected' => $rejected,
            'pending'  => $total - $accepted - $rejected,
        ];
    }

    public function aiAdoptionBreakdown(int $userId = 0, ?int $unitId = null): array
    {
        $builder = $this->db->table('ai_copilot_outputs');
        if ($userId > 0) {
            $builder->where('user_id', $userId);
        }

        $total = $builder->countAllResults();
        $builder2 = $this->db->table('ai_copilot_outputs');
        if ($userId > 0) {
            $builder2->where('user_id', $userId);
        }
        $accepted = $builder2->where('approval_status', 'ACCEPTED')->countAllResults();

        $builder3 = $this->db->table('ai_copilot_outputs');
        if ($userId > 0) {
            $builder3->where('user_id', $userId);
        }
        $rejected = $builder3->where('approval_status', 'REJECTED')->countAllResults();

        $bFeedbackUseful = $this->db->table('ai_copilot_outputs')->where('feedback_rating', self::FEEDBACK_USEFUL);
        if ($userId > 0) {
            $bFeedbackUseful->where('user_id', $userId);
        }
        $feedbackUseful = $bFeedbackUseful->countAllResults();

        $bFeedbackCorrection = $this->db->table('ai_copilot_outputs')->where('feedback_rating', self::FEEDBACK_NEEDS_CORRECTION);
        if ($userId > 0) {
            $bFeedbackCorrection->where('user_id', $userId);
        }
        $feedbackCorrection = $bFeedbackCorrection->countAllResults();

        return [
            'total'             => $total,
            'accepted'          => $accepted,
            'rejected'          => $rejected,
            'pending'           => max(0, $total - $accepted - $rejected),
            'feedback_useful'   => $feedbackUseful,
            'feedback_correct'  => $feedbackCorrection,
            'acceptance_rate'   => $total > 0 ? round(($accepted / $total) * 100) : 0,
        ];
    }

    // ================================================================
    // KSP EVALUATION (uses existing table)
    // ================================================================

    public function listKspEvaluations(int $kspVersionId): array
    {
        return $this->db->table('ksp_evaluations')
            ->where('ksp_version_id', $kspVersionId)
            ->orderBy('created_at', 'DESC')
            ->get()->getResultArray();
    }

    // ================================================================
    // AI DRAFT ENGINES (Rule-based, Explainable Kurikulum Merdeka)
    // ================================================================

    private function draftReflection(array $ctx): string
    {
        $subject    = !empty($ctx['subject']) ? $ctx['subject'] : 'Mata Pelajaran';
        $mastery    = isset($ctx['mastery_pct']) && $ctx['mastery_pct'] !== '' ? (int) $ctx['mastery_pct'] : null;
        $attendance = isset($ctx['attendance_pct']) && $ctx['attendance_pct'] !== '' ? (int) $ctx['attendance_pct'] : null;

        $lines = [];
        $lines[] = "🎯 Refleksi Pembelajaran Berdiferensiasi — {$subject}:";

        if ($mastery !== null) {
            if ($mastery >= 85) {
                $lines[] = "• Capaian Penguasaan: Sangat tinggi ({$mastery}%). Sebagian besar peserta didik telah mencapai kriteria ketercapaian tujuan pembelajaran (KKTP) dan siap melangkah ke materi pengayaan berbasis proyek (PjBL).";
                $lines[] = "• Yang Berjalan Baik: Strategi diskusi interaktif dan pemanfaatan media visual terbukti efektif meningkatkan motivasi dan pemahaman konseptual siswa.";
                $lines[] = "• Yang Perlu Diperbaiki: Perlu tantangan berpikir tingkat tinggi (HOTS) tambahan agar murid yang cepat selesai tetap terstimulasi secara optimal.";
            } elseif ($mastery >= 70) {
                $lines[] = "• Capaian Penguasaan: Cukup baik ({$mastery}%). Murid memahami konsep inti, namun variasi kecepatan belajar antar kelompok tampak nyata.";
                $lines[] = "• Yang Berjalan Baik: Aktivitas kolaboratif kelompok heterogen membantu tutor sebaya berjalan dinamis.";
                $lines[] = "• Yang Perlu Diperbaiki: Perlu diferensiasi proses belajar melalui penyediaan lembar kerja bertingkat (scaffolded worksheets) bagi siswa yang membutuhkan bantuan tambahan.";
            } else {
                $lines[] = "• Capaian Penguasaan: Perlu intervensi terstruktur ({$mastery}%). Sejumlah peserta didik mengalami kesulitan pada pemahaman prasyarat materi.";
                $lines[] = "• Yang Berjalan Baik: Partisipasi kehadiran dan antusiasme awal pembelajaran cukup positif.";
                $lines[] = "• Yang Perlu Diperbaiki: Ritme penyampaian materi perlu disesuaikan, dan penjelasan analogi dunia nyata perlu diperbanyak sebelum masuk ke latihan mandiri.";
            }
        } else {
            $lines[] = "• Yang Berjalan Baik: Suasana kelas kondusif, interaksi dua arah antara pendidik dan murid terjalin terbuka, serta kesepakatan kelas dihormati bersama.";
            $lines[] = "• Yang Perlu Diperbaiki: Perlu pemetaan profil belajar murid di awal sesi untuk mengoptimalkan variasi gaya belajar auditori, visual, dan kinestetik.";
        }

        if ($attendance !== null && $attendance < 85) {
            $lines[] = "• Catatan Presensi: Tingkat kehadiran berada pada angka {$attendance}%. Disarankan berkoordinasi dengan wali kelas dan guru BK untuk mitigasi ketertinggalan materi peserta didik.";
        }

        $lines[] = "• Langkah Tindak Lanjut: Berikan asesmen formatif reflektif singkat di awal pertemuan berikutnya, sediakan materi pengayaan bagi siswa tuntas, dan berikan klinik belajar remedial bagi yang membutuhkan.";
        $lines[] = "\n💡 [Dasar Analisis AI: Berdasarkan data mata pelajaran '{$subject}', penguasaan formatif " . ($mastery !== null ? "{$mastery}%" : '—') . ", dan presensi " . ($attendance !== null ? "{$attendance}%" : '—') . "].";

        return implode("\n", $lines);
    }

    private function draftSupervisionSummary(array $ctx): string
    {
        $teacher = !empty($ctx['teacher_name']) ? $ctx['teacher_name'] : 'Guru Pengampu';
        $rating  = $ctx['rating'] ?? null;
        $subject = !empty($ctx['subject']) ? " ({$ctx['subject']})" : '';

        $lines = [];
        $lines[] = "📋 Lembar Rangkuman Observasi Akademik Pembelajaran — {$teacher}{$subject}:";

        if ($rating === self::RATING_EXCELLENT) {
            $lines[] = "• Kekuatan: Pendidik menunjukkan penguasaan pedagogik dan materi yang luar biasa. Penerapan pembelajaran berpusat pada siswa (student-centered learning) terwujud nyata dengan teknik fasilitasi yang inspiratif.";
            $lines[] = "• Praktik Baik: Diferensiasi proses dan produk diterapkan secara natural. Umpan balik formatif diberikan secara langsung (real-time) dan membangun.";
            $lines[] = "• Rekomendasi: Disarankan mendokumentasikan model pembelajaran ini sebagai Praktik Baik (Best Practice) untuk dibagikan dalam Komunitas Belajar (Kombel) sekolah.";
        } elseif ($rating === self::RATING_GOOD) {
            $lines[] = "• Kekuatan: Guru memiliki penguasaan materi yang matang, pengelolaan kelas tertib, dan mampu membangun komunikasi yang hangat serta inklusif bersama siswa.";
            $lines[] = "• Area Pertumbuhan: Dapat lebih mengeksplorasi pertanyaan pemantik berbasis pemecahan masalah (Problem-Based Learning) untuk memicu nalar kritis siswa.";
            $lines[] = "• Rekomendasi: Terus kembangkan variasi asesmen formatif informal selama proses pembelajaran dan integrasikan pemanfaatan platform digital edukatif.";
        } elseif ($rating === self::RATING_SATISFACTORY) {
            $lines[] = "• Kekuatan: Guru menyampaikan materi sesuai dengan modul ajar / RPP dan jadwal yang ditetapkan.";
            $lines[] = "• Area Pertumbuhan: Interaksi masih didominasi oleh penjelasan satu arah. Keterlibatan aktif peserta didik perlu ditingkatkan melalui kerja kelompok dan presentasi singkat.";
            $lines[] = "• Rekomendasi: Susun aktivitas belajar yang lebih variatif dengan alokasi waktu tanya jawab yang proporsional, didampingi supervisi klinis lanjutan.";
        } else {
            $lines[] = "• Kekuatan: Kehadiran dan kesiapan awal pengajaran cukup baik.";
            $lines[] = "• Area Pertumbuhan: Pengelolaan kelas dan keselarasan antara tujuan pembelajaran dengan asesmen perlu penyelarasan mendalam.";
            $lines[] = "• Rekomendasi: Jadwalkan sesi pendampingan (mentoring) bersama rekan sejawat (peer teacher) atau wakasek kurikulum untuk penguatan perencanaan dan manajemen kelas.";
        }

        $lines[] = "\n💡 [Dasar Rekomendasi AI: Berdasarkan observasi kinerja pengajaran '{$teacher}', predikat '{$rating}', dan indikator kompetensi pedagogik guru].";

        return implode("\n", $lines);
    }

    private function suggestImprovement(array $ctx): string
    {
        $area = !empty($ctx['area']) ? $ctx['area'] : 'Proses Pembelajaran';
        
        $lines = [];
        $lines[] = "🚀 Strategi Peningkatan Kualitas & Inovasi: {$area}";
        $lines[] = "1. Pembelajaran Berbasis Masalah (PBL): Hubungkan kompetensi dasar dengan fenomena nyata atau kasus kontekstual di lingkungan sekitar peserta didik.";
        $lines[] = "2. Scaffolding & Diferensiasi Konten: Sediakan ragam sumber belajar (buku teks, infografis ringkas, dan video singkat) sesuai keragaman gaya belajar siswa.";
        $lines[] = "3. Asesmen Formatif Berkelanjutan: Gunakan exit-ticket atau kuis refleksi interaktif 5 menit sebelum kelas berakhir untuk memantau pemahaman siswa.";
        $lines[] = "4. Integrasi Profil Pelajar Pancasila: Selipkan pembiasaan dimensi Bernalar Kritis, Gotong Royong, dan Mandiri dalam penugasan kelompok.";
        $lines[] = "\n💡 [Dasar Rekomendasi AI: Berdasarkan fokus area perbaikan '{$area}' dengan pendekatan Kurikulum Merdeka].";

        return implode("\n", $lines);
    }

    private function suggestLearningTips(array $ctx): string
    {
        $subject = !empty($ctx['subject']) ? $ctx['subject'] : 'Mata Pelajaran';
        
        $lines = [];
        $lines[] = "✨ Tips Pedagogik & Aktivitas Belajar Bermakna: {$subject}";
        $lines[] = "• Apersepsi Menggugah: Awali pertemuan dengan analogi sehari-hari atau cerita tantangan singkat yang mengundang rasa ingin tahu (curiosity).";
        $lines[] = "• Eksplorasi Terpandu (Guided Inquiry): Berikan kesempatan kepada siswa untuk menemukan pola atau rumus konsep secara mandiri sebelum diberi penjelasan formal.";
        $lines[] = "• Kolaborasi Kolaboratif: Terapkan teknik Think-Pair-Share atau Jigsaw untuk mendorong partisipasi merata di antara seluruh murid.";
        $lines[] = "• Refleksi Metakognitif: Ajak siswa menuliskan '1 Hal yang Baru Saya Pahami' dan '1 Pertanyaan yang Masih Saya Miliki' di akhir sesi.";
        $lines[] = "\n💡 [Dasar Rekomendasi AI: Berdasarkan karakteristik pedagogik materi '{$subject}'].";

        return implode("\n", $lines);
    }

    private function draftClassSummary(array $ctx): string
    {
        $className     = !empty($ctx['class_name']) ? $ctx['class_name'] : 'Rombongan Belajar';
        $avgScore      = isset($ctx['avg_score']) && $ctx['avg_score'] !== '' ? (float) $ctx['avg_score'] : null;
        $totalStudents = isset($ctx['total_students']) && $ctx['total_students'] !== '' ? (int) $ctx['total_students'] : 0;

        $lines = [];
        $lines[] = "📊 Ringkasan Mutu Akademik Rombel {$className} ({$totalStudents} Peserta Didik):";

        if ($avgScore !== null) {
            $lines[] = "• Rata-rata Capaian Kelas: {$avgScore}/100.";
            if ($avgScore >= 80) {
                $lines[] = "• Analisis Dinamika: Performa akademik rombel tergolong sangat unggul. Budaya belajar saling mendukung (peer-learning) berjalan kondusif.";
                $lines[] = "• Rekomendasi Lanjutan: Siapkan proyek integrasi antar-mata pelajaran untuk memperdalam pemahaman lintas disiplin ilmu.";
            } elseif ($avgScore >= 70) {
                $lines[] = "• Analisis Dinamika: Performa rombel berada pada tingkat baik dan stabil. Sebagian besar siswa mencapai ambang batas kompetensi yang diharapkan.";
                $lines[] = "• Rekomendasi Lanjutan: Pertahankan ritme pembelajaran dan berikan perhatian khusus pada 15-20% siswa yang mendekati ambang batas ketuntasan.";
            } else {
                $lines[] = "• Analisis Dinamika: Rata-rata kelas berada di bawah target optimal. Diperlukan evaluasi menyeluruh terhadap kecepatan materi dan metode asesmen yang digunakan.";
                $lines[] = "• Rekomendasi Lanjutan: Rancang sesi penguatan konsep inti (remedial teaching) secara tematik dan libatkan wali kelas untuk monitoring kebiasaan belajar di rumah.";
            }
        } else {
            $lines[] = "• Analisis Dinamika: Pengelolaan kelas berjalan tertib dengan keterlibatan aktif peserta didik dalam diskusi kelas.";
            $lines[] = "• Rekomendasi: Lakukan pencatatan berkala terhadap capaian asesmen formatif untuk memantau kurva perkembangan rombel.";
        }

        $lines[] = "\n💡 [Dasar Rekomendasi AI: Berdasarkan rekapitulasi data rombel '{$className}', rata-rata nilai " . ($avgScore !== null ? $avgScore : '—') . ", dan kapasitas {$totalStudents} murid].";

        return implode("\n", $lines);
    }
}
