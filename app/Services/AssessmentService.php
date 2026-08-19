<?php

namespace App\Services;

use App\Models\AssessmentAttemptModel;
use App\Models\AssessmentCriterionModel;
use App\Models\AssessmentEvidenceModel;
use App\Models\AssessmentFeedbackModel;
use App\Models\AssessmentItemModel;
use App\Models\AssessmentModel;
use App\Models\AssessmentObjectiveModel;
use App\Models\CriterionResultModel;
use Config\Database;
use RuntimeException;

/**
 * Assessment authoring and grading: create/update/publish/close assessments,
 * gradebook entry (attempts + criterion results), and evidence/feedback capture.
 * Mastery derivation is delegated to MasteryService.
 */
class AssessmentService
{
    public const TYPE_DIAGNOSTIC = 'DIAGNOSTIC';
    public const TYPE_FORMATIVE   = 'FORMATIVE';
    public const TYPE_SUMMATIVE   = 'SUMMATIVE';

    public const ALLOWED_TYPES = [
        self::TYPE_DIAGNOSTIC,
        self::TYPE_FORMATIVE,
        self::TYPE_SUMMATIVE,
    ];

    public const ALLOWED_FORMS = [
        'ANGKA', 'RUBRIK', 'OBSERVASI', 'ESSAY', 'PERFORMANCE',
        'PRODUCT', 'PROJECT', 'ORAL', 'PORTFOLIO',
    ];

    public const STATUS_DRAFT     = 'DRAFT';
    public const STATUS_PUBLISHED = 'PUBLISHED';
    public const STATUS_CLOSED    = 'CLOSED';

    public const ALLOWED_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_CLOSED,
    ];

    private $db;
    private AssessmentModel $assessmentModel;
    private AssessmentObjectiveModel $objectiveModel;
    private AssessmentCriterionModel $criterionModel;
    private AssessmentItemModel $itemModel;
    private AssessmentAttemptModel $attemptModel;
    private CriterionResultModel $criterionResultModel;
    private AssessmentEvidenceModel $evidenceModel;
    private AssessmentFeedbackModel $feedbackModel;
    private MasteryService $masteryService;

    public function __construct()
    {
        $this->db                   = Database::connect();
        $this->assessmentModel      = new AssessmentModel();
        $this->objectiveModel       = new AssessmentObjectiveModel();
        $this->criterionModel       = new AssessmentCriterionModel();
        $this->itemModel            = new AssessmentItemModel();
        $this->attemptModel         = new AssessmentAttemptModel();
        $this->criterionResultModel = new CriterionResultModel();
        $this->evidenceModel        = new AssessmentEvidenceModel();
        $this->feedbackModel        = new AssessmentFeedbackModel();
        $this->masteryService       = new MasteryService();
    }

    public function list(int $unitId, int $periodId, array $filters = []): array
    {
        $builder = $this->db->table('assessments a')
            ->select('a.*, c.name as classroom_name, s.name as subject_name, t.full_name as teacher_name,
                      COUNT(DISTINCT ao.learning_objective_id) as tp_count,
                      COUNT(DISTINCT aa.id) as graded_count')
            ->join('classrooms c', 'c.id = a.classroom_id', 'left')
            ->join('subjects s', 's.id = a.subject_id', 'left')
            ->join('teachers t', 't.id = a.teacher_id', 'left')
            ->join('assessment_objectives ao', 'ao.assessment_id = a.id', 'left')
            ->join('assessment_attempts aa', 'aa.assessment_id = a.id', 'left')
            ->where('a.unit_id', $unitId)
            ->where('a.academic_period_id', $periodId);

        if (! empty($filters['classroom_id'])) {
            $builder->where('a.classroom_id', $filters['classroom_id']);
        }
        if (! empty($filters['subject_id'])) {
            $builder->where('a.subject_id', $filters['subject_id']);
        }
        if (! empty($filters['assessment_type'])) {
            $builder->where('a.assessment_type', $filters['assessment_type']);
        }
        if (! empty($filters['status'])) {
            $builder->where('a.status', $filters['status']);
        }
        if (! empty($filters['teacher_id'])) {
            $builder->where('a.teacher_id', $filters['teacher_id']);
        }

        return $builder->groupBy('a.id')
            ->orderBy('a.assessment_date', 'DESC')
            ->orderBy('a.created_at', 'DESC')
            ->get()->getResultArray();
    }

    public function detail(int $id): array
    {
        $assessment = $this->assessmentModel->find($id);
        if (! $assessment) {
            throw new RuntimeException('Assessment tidak ditemukan.');
        }

        $assessment['objectives'] = $this->db->table('assessment_objectives ao')
            ->select('ao.*, lot.code as tp_code, lot.statement as tp_name')
            ->join('learning_objectives_tp lot', 'lot.id = ao.learning_objective_id', 'left')
            ->where('ao.assessment_id', $id)
            ->orderBy('ao.sequence_order', 'ASC')
            ->get()->getResultArray();

        $assessment['criteria'] = $this->db->table('assessment_criteria ac')
            ->select('ac.*, lot.code as tp_code')
            ->join('learning_objectives_tp lot', 'lot.id = ac.learning_objective_id', 'left')
            ->where('ac.assessment_id', $id)
            ->orderBy('ac.sequence_order', 'ASC')
            ->get()->getResultArray();

        $assessment['items'] = $this->itemModel->where('assessment_id', $id)->orderBy('sequence_order', 'ASC')->findAll();

        return $assessment;
    }

    public function create(array $payload, int $userId): int
    {
        $payload['assessment_type'] = strtoupper($payload['assessment_type'] ?? self::TYPE_FORMATIVE);
        $payload['assessment_form'] = strtoupper($payload['assessment_form'] ?? 'ANGKA');
        $this->assertPayload($payload);

        $this->db->transBegin();
        try {
            $assessmentId = (int) $this->assessmentModel->insert([
                'uuid'                    => UuidService::v4(),
                'unit_id'                 => (int) $payload['unit_id'],
                'academic_period_id'      => (int) $payload['academic_period_id'],
                'classroom_id'            => (int) $payload['classroom_id'],
                'subject_id'              => (int) $payload['subject_id'],
                'teacher_id'              => ! empty($payload['teacher_id']) ? (int) $payload['teacher_id'] : null,
                'lesson_plan_assessment_id' => ! empty($payload['lesson_plan_assessment_id']) ? (int) $payload['lesson_plan_assessment_id'] : null,
                'learning_session_id'     => ! empty($payload['learning_session_id']) ? (int) $payload['learning_session_id'] : null,
                'title'                   => trim($payload['title']),
                'assessment_type'         => $payload['assessment_type'],
                'assessment_form'         => $payload['assessment_form'],
                'assessment_date'         => $payload['assessment_date'],
                'status'                  => self::STATUS_DRAFT,
                'max_score'               => $payload['max_score'] !== '' && $payload['max_score'] !== null ? $payload['max_score'] : null,
                'rubric_json'             => $payload['rubric_json'] ?? null,
                'description'             => $payload['description'] ?? null,
                'revision_number'         => 1,
                'created_by'              => $userId,
                'updated_by'              => $userId,
            ]);

            $this->storeObjectives((int) $assessmentId, $payload['objective_ids'] ?? [], $userId);
            $this->storeCriteria((int) $assessmentId, $payload['criteria'] ?? [], $userId);
            $this->storeItems((int) $assessmentId, $payload['items'] ?? [], $userId);

            $this->db->transCommit();

            AuditService::log('assessment', 'CREATE_ASSESSMENT', 'Assessment', $assessmentId, null, ['title' => $payload['title']], 'Membuat assessment baru');
            return $assessmentId;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw new RuntimeException('Gagal membuat assessment: ' . $e->getMessage());
        }
    }

    public function update(int $id, array $payload, int $userId): void
    {
        $assessment = $this->assessmentModel->find($id);
        if (! $assessment) {
            throw new RuntimeException('Assessment tidak ditemukan.');
        }
        if ($assessment['status'] === self::STATUS_CLOSED) {
            throw new RuntimeException('Assessment yang sudah ditutup tidak dapat diubah.');
        }

        $this->db->transBegin();
        try {
            $this->assessmentModel->update($id, [
                'title'                   => trim($payload['title'] ?? $assessment['title']),
                'assessment_type'         => strtoupper($payload['assessment_type'] ?? $assessment['assessment_type']),
                'assessment_form'         => strtoupper($payload['assessment_form'] ?? $assessment['assessment_form']),
                'assessment_date'         => $payload['assessment_date'] ?? $assessment['assessment_date'],
                'max_score'               => array_key_exists('max_score', $payload) && $payload['max_score'] !== '' ? $payload['max_score'] : $assessment['max_score'],
                'rubric_json'             => array_key_exists('rubric_json', $payload) ? ($payload['rubric_json'] ?: null) : $assessment['rubric_json'],
                'description'             => array_key_exists('description', $payload) ? ($payload['description'] ?: null) : $assessment['description'],
                'revision_number'         => ((int) $assessment['revision_number']) + 1,
                'updated_by'              => $userId,
            ]);

            $this->objectiveModel->where('assessment_id', $id)->delete();
            $this->criterionModel->where('assessment_id', $id)->delete();
            $this->itemModel->where('assessment_id', $id)->delete();

            $this->storeObjectives($id, $payload['objective_ids'] ?? [], $userId);
            $this->storeCriteria($id, $payload['criteria'] ?? [], $userId);
            $this->storeItems($id, $payload['items'] ?? [], $userId);

            $this->db->transCommit();
            AuditService::log('assessment', 'UPDATE_ASSESSMENT', 'Assessment', $id, $assessment, ['revision' => (int) $assessment['revision_number'] + 1], 'Memperbarui assessment');
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw new RuntimeException('Gagal memperbarui assessment: ' . $e->getMessage());
        }
    }

    public function transition(int $id, string $target, int $userId): void
    {
        $target = strtoupper($target);
        $assessment = $this->assessmentModel->find($id);
        if (! $assessment) {
            throw new RuntimeException('Assessment tidak ditemukan.');
        }

        $allowedNext = [
            self::STATUS_DRAFT     => [self::STATUS_PUBLISHED],
            self::STATUS_PUBLISHED => [self::STATUS_CLOSED],
            self::STATUS_CLOSED    => [],
        ];

        if (! in_array($target, $allowedNext[$assessment['status']] ?? [], true)) {
            throw new RuntimeException('Transisi status tidak valid: ' . $assessment['status'] . ' -> ' . $target);
        }

        $update = ['status' => $target, 'updated_by' => $userId];
        if ($target === self::STATUS_PUBLISHED) {
            $update['published_at'] = date('Y-m-d H:i:s');
        }
        if ($target === self::STATUS_CLOSED) {
            $update['closed_at'] = date('Y-m-d H:i:s');
        }

        $this->assessmentModel->update($id, $update);
        AuditService::log('assessment', 'TRANSITION', 'Assessment', $id, ['status' => $assessment['status']], ['status' => $target], "Transisi status ke {$target}");
    }

    public function delete(int $id, int $userId): void
    {
        $assessment = $this->assessmentModel->find($id);
        if (! $assessment) {
            throw new RuntimeException('Assessment tidak ditemukan.');
        }
        if ($assessment['status'] !== self::STATUS_DRAFT) {
            throw new RuntimeException('Hanya assessment berstatus DRAFT yang dapat dihapus.');
        }

        $this->db->transBegin();
        try {
            $this->objectiveModel->where('assessment_id', $id)->delete();
            $this->criterionModel->where('assessment_id', $id)->delete();
            $this->itemModel->where('assessment_id', $id)->delete();
            $this->assessmentModel->delete($id);
            $this->db->transCommit();
            AuditService::log('assessment', 'DELETE_ASSESSMENT', 'Assessment', $id, $assessment, null, 'Menghapus assessment draft');
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw new RuntimeException('Gagal menghapus assessment: ' . $e->getMessage());
        }
    }

    public function gradebook(int $id): array
    {
        $assessment = $this->detail($id);

        $students = $this->db->table('elective_students')
            ->where('classroom_id', $assessment['classroom_id'])
            ->where('is_active', 1)
            ->orderBy('full_name', 'ASC')
            ->get()->getResultArray();

        $attemptRows = $this->attemptModel
            ->where('assessment_id', $id)
            ->get()->getResultArray();
        $attemptByStudent = [];
        foreach ($attemptRows as $attempt) {
            $attemptByStudent[(int) $attempt['student_id']] = $attempt;
        }

        $attemptIds = array_column($attemptRows, 'id');
        $resultsByAttempt = [];
        if ($attemptIds !== []) {
            $resultRows = $this->criterionResultModel
                ->whereIn('attempt_id', $attemptIds)
                ->get()->getResultArray();
            foreach ($resultRows as $row) {
                $resultsByAttempt[(int) $row['attempt_id']][(int) $row['criterion_id']] = $row;
            }
        }

        $maxScore = $assessment['max_score'] !== null ? (float) $assessment['max_score'] : null;
        $rows = [];
        foreach ($students as $student) {
            $attempt = $attemptByStudent[(int) $student['id']] ?? null;
            $criteriaRows = [];
            foreach ($assessment['criteria'] as $criterion) {
                $result = $attempt ? ($resultsByAttempt[(int) $attempt['id']][(int) $criterion['id']] ?? null) : null;
                if ($result) {
                    $result['status'] = $result['status'] ?: $this->masteryService->deriveCriterionStatus(
                        $result['score'] !== null ? (float) $result['score'] : null,
                        $maxScore,
                        $result['level_index'] !== null ? (int) $result['level_index'] : null
                    );
                }
                $criteriaRows[] = [
                    'criterion' => $criterion,
                    'result'    => $result,
                ];
            }
            $rows[] = [
                'student'  => $student,
                'attempt'  => $attempt,
                'criteria' => $criteriaRows,
            ];
        }

        return [
            'assessment' => $assessment,
            'students'   => $students,
            'rows'       => $rows,
        ];
    }

    /**
     * Saves the gradebook: upserts attempts + criterion results, then
     * re-syncs mastery records for the assessment.
     */
    public function saveGradebook(int $id, array $rows, int $userId): array
    {
        $assessment = $this->assessmentModel->find($id);
        if (! $assessment) {
            throw new RuntimeException('Assessment tidak ditemukan.');
        }
        if ($assessment['status'] === self::STATUS_CLOSED) {
            throw new RuntimeException('Assessment yang sudah ditutup tidak dapat dinilai ulang.');
        }

        $this->db->transBegin();
        try {
            foreach ($rows as $row) {
                $studentId = (int) ($row['student_id'] ?? 0);
                if ($studentId <= 0) {
                    continue;
                }

                $attempt = $this->attemptModel
                    ->where('assessment_id', $id)
                    ->where('student_id', $studentId)
                    ->first();

                $attemptData = [
                    'classroom_id' => (int) $assessment['classroom_id'],
                    'score'        => ($row['score'] ?? '') !== '' ? $row['score'] : null,
                    'is_complete'  => ! empty($row['is_complete']) ? 1 : 0,
                    'submitted_at' => $row['submitted_at'] ?? date('Y-m-d H:i:s'),
                    'updated_by'   => $userId,
                ];

                if ($attempt) {
                    $this->attemptModel->update($attempt['id'], $attemptData);
                    $attemptId = (int) $attempt['id'];
                } else {
                    $attemptId = (int) $this->attemptModel->insert($attemptData + [
                        'uuid'           => UuidService::v4(),
                        'assessment_id'  => $id,
                        'student_id'     => $studentId,
                        'revision_number'=> 1,
                        'created_by'     => $userId,
                    ]);
                }

                foreach (($row['criteria'] ?? []) as $criterionId => $criterionInput) {
                    $criterionId = (int) $criterionId;
                    if ($criterionId <= 0) {
                        continue;
                    }

                    $existingResult = $this->criterionResultModel
                        ->where('attempt_id', $attemptId)
                        ->where('criterion_id', $criterionId)
                        ->first();

                    $levelIndex = ($criterionInput['level_index'] ?? '') !== '' ? (int) $criterionInput['level_index'] : null;
                    $score      = ($criterionInput['score'] ?? '') !== '' ? $criterionInput['score'] : null;
                    $status     = ($criterionInput['status'] ?? '') !== '' ? $criterionInput['status'] : null;

                    if (! $status) {
                        $status = $this->masteryService->deriveCriterionStatus(
                            $score !== null ? (float) $score : null,
                            $assessment['max_score'] !== null ? (float) $assessment['max_score'] : null,
                            $levelIndex
                        );
                    }

                    $resultData = [
                        'level_index' => $levelIndex,
                        'score'       => $score,
                        'status'      => $status ? strtoupper($status) : null,
                        'notes'       => $criterionInput['notes'] ?? null,
                        'updated_by'  => $userId,
                    ];

                    if ($existingResult) {
                        $this->criterionResultModel->update($existingResult['id'], $resultData);
                    } else {
                        $this->criterionResultModel->insert($resultData + [
                            'uuid'        => UuidService::v4(),
                            'attempt_id'  => $attemptId,
                            'criterion_id'=> $criterionId,
                            'created_by'  => $userId,
                        ]);
                    }
                }
            }

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw new RuntimeException('Gagal menyimpan nilai: ' . $e->getMessage());
        }

        $summary = $this->masteryService->syncFromAssessment($id, $userId);
        AuditService::log('assessment', 'SAVE_GRADEBOOK', 'Assessment', $id, null, ['students' => count($rows)], 'Menyimpan nilai assessment');

        return $summary;
    }

    public function addEvidence(array $data, int $userId): int
    {
        $studentId = (int) ($data['student_id'] ?? 0);
        if ($studentId <= 0) {
            throw new RuntimeException('Siswa wajib dipilih.');
        }
        if (empty($data['title'])) {
            throw new RuntimeException('Judul bukti wajib diisi.');
        }

        $this->db->transBegin();
        try {
            $id = (int) $this->evidenceModel->insert([
                'uuid'                  => UuidService::v4(),
                'student_id'            => $studentId,
                'attempt_id'            => ! empty($data['attempt_id']) ? (int) $data['attempt_id'] : null,
                'learning_objective_id' => ! empty($data['learning_objective_id']) ? (int) $data['learning_objective_id'] : null,
                'criterion_id'          => ! empty($data['criterion_id']) ? (int) $data['criterion_id'] : null,
                'profile_dimension_id'  => ! empty($data['profile_dimension_id']) ? (int) $data['profile_dimension_id'] : null,
                'cocurricular_objective_id' => ! empty($data['cocurricular_objective_id']) ? (int) $data['cocurricular_objective_id'] : null,
                'evidence_type'         => strtoupper($data['evidence_type'] ?? 'FILE'),
                'title'                 => trim($data['title']),
                'content'               => $data['content'] ?? null,
                'file_path'             => $data['file_path'] ?? null,
                'meta_json'             => isset($data['meta_json']) ? json_encode($data['meta_json']) : null,
                'captured_at'           => $data['captured_at'] ?? date('Y-m-d H:i:s'),
                'created_by'            => $userId,
                'updated_by'            => $userId,
            ]);
            $this->db->transCommit();
            AuditService::log('assessment', 'ADD_EVIDENCE', 'AssessmentEvidence', $id, null, ['student_id' => $studentId], 'Menambahkan bukti belajar');
            return $id;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw new RuntimeException('Gagal menambahkan bukti: ' . $e->getMessage());
        }
    }

    public function addFeedback(array $data, int $userId): int
    {
        if (empty($data['attempt_id']) || empty($data['student_id'])) {
            throw new RuntimeException('Data umpan balik tidak lengkap.');
        }
        if (empty(trim($data['content'] ?? ''))) {
            throw new RuntimeException('Isi umpan balik wajib diisi.');
        }

        $id = (int) $this->feedbackModel->insert([
            'uuid'          => UuidService::v4(),
            'attempt_id'    => (int) $data['attempt_id'],
            'student_id'    => (int) $data['student_id'],
            'content'       => trim($data['content']),
            'feedback_type' => strtoupper($data['feedback_type'] ?? 'MANUAL'),
            'created_by'    => $userId,
            'updated_by'    => $userId,
        ]);

        AuditService::log('assessment', 'ADD_FEEDBACK', 'AssessmentFeedback', $id, null, ['attempt_id' => (int) $data['attempt_id']], 'Menambahkan umpan balik');
        return $id;
    }

    public function evidenceForStudent(int $studentId, ?int $objectiveId = null): array
    {
        $builder = $this->evidenceModel->where('student_id', $studentId)->orderBy('captured_at', 'DESC');
        if ($objectiveId) {
            $builder->where('learning_objective_id', $objectiveId);
        }
        return $builder->findAll();
    }

    // ----------------------------------------------------------------
    // Internals
    // ----------------------------------------------------------------

    private function assertPayload(array $payload): void
    {
        if (empty($payload['title'])) {
            throw new RuntimeException('Judul assessment wajib diisi.');
        }
        if (empty($payload['assessment_date'])) {
            throw new RuntimeException('Tanggal assessment wajib diisi.');
        }
        if (empty($payload['classroom_id']) || empty($payload['subject_id'])) {
            throw new RuntimeException('Kelas dan mapel wajib dipilih.');
        }
        if (! in_array($payload['assessment_type'], self::ALLOWED_TYPES, true)) {
            throw new RuntimeException('Tipe assessment tidak valid.');
        }
        if (! in_array($payload['assessment_form'], self::ALLOWED_FORMS, true)) {
            throw new RuntimeException('Bentuk assessment tidak valid.');
        }
    }

    private function storeObjectives(int $assessmentId, array $objectiveIds, int $userId): void
    {
        $order = 1;
        foreach ($objectiveIds as $objectiveId) {
            $objectiveId = (int) $objectiveId;
            if ($objectiveId <= 0) {
                continue;
            }
            $this->objectiveModel->insert([
                'uuid'                  => UuidService::v4(),
                'assessment_id'         => $assessmentId,
                'learning_objective_id' => $objectiveId,
                'sequence_order'        => $order++,
                'created_by'            => $userId,
                'updated_by'            => $userId,
            ]);
        }
    }

    private function storeCriteria(int $assessmentId, array $criteria, int $userId): void
    {
        $order = 1;
        foreach ($criteria as $criterion) {
            if (empty($criterion['criterion'])) {
                continue;
            }
            $this->criterionModel->insert([
                'uuid'                  => UuidService::v4(),
                'assessment_id'         => $assessmentId,
                'learning_objective_id' => ! empty($criterion['learning_objective_id']) ? (int) $criterion['learning_objective_id'] : null,
                'criterion'             => trim($criterion['criterion']),
                'weight'                => ($criterion['weight'] ?? '') !== '' ? $criterion['weight'] : 1.0,
                'sequence_order'        => $order++,
                'rubric_levels_json'    => ! empty($criterion['rubric_levels_json']) ? $criterion['rubric_levels_json'] : null,
                'created_by'            => $userId,
                'updated_by'            => $userId,
            ]);
        }
    }

    private function storeItems(int $assessmentId, array $items, int $userId): void
    {
        $order = 1;
        foreach ($items as $item) {
            if (empty($item['prompt'])) {
                continue;
            }
            $this->itemModel->insert([
                'uuid'           => UuidService::v4(),
                'assessment_id'  => $assessmentId,
                'item_type'      => strtoupper($item['item_type'] ?? 'ESSAY'),
                'prompt'         => trim($item['prompt']),
                'answer_key'     => $item['answer_key'] ?? null,
                'max_score'      => ($item['max_score'] ?? '') !== '' ? $item['max_score'] : null,
                'sequence_order' => $order++,
                'created_by'     => $userId,
                'updated_by'     => $userId,
            ]);
        }
    }
}