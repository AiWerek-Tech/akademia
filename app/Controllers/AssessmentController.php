<?php

namespace App\Controllers;

use App\Exceptions\AuthorizationException;
use App\Services\AssessmentService;
use App\Services\MasteryService;
use App\Services\UnitScopeService;
use Config\Database;
use Exception;

class AssessmentController extends BaseController
{
    private AssessmentService $assessmentService;
    private MasteryService $masteryService;

    public function __construct()
    {
        $this->assessmentService = new AssessmentService();
        $this->masteryService    = new MasteryService();
    }

    /**
     * Assessment list with filters.
     */
    public function index()
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;

        $filters = [
            'classroom_id'   => (int) ($this->request->getGet('classroom_id') ?: 0),
            'subject_id'     => (int) ($this->request->getGet('subject_id') ?: 0),
            'assessment_type'=> $this->request->getGet('assessment_type') ?: '',
            'status'         => $this->request->getGet('status') ?: '',
            'teacher_id'     => $this->isManagementRole() ? 0 : $this->resolveOwnTeacherId(),
        ];

        $assessments = $this->assessmentService->list($unitId, $periodId, $filters);
        $db = Database::connect();

        return view('assessment/index', [
            'title'             => 'Assessment & Penilaian',
            'breadcrumb_active' => 'Assessment',
            'assessments'       => $assessments,
            'classrooms'        => $this->classroomsForUnit($unitId, $periodId),
            'subjects'          => $this->subjectsForUnit($unitId),
            'filters'           => $filters,
            'types'             => AssessmentService::ALLOWED_TYPES,
            'statuses'          => AssessmentService::ALLOWED_STATUSES,
            'db'                => $db,
        ]);
    }

    /**
     * New assessment form.
     */
    public function create()
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;

        return view('assessment/create', [
            'title'             => 'Buat Assessment',
            'breadcrumb_active' => 'Buat Assessment',
            'classrooms'        => $this->classroomsForUnit($unitId, $periodId),
            'subjects'          => $this->subjectsForUnit($unitId),
            'tps'               => $this->tpsForUnit($unitId),
            'teachers'          => $this->teachersForUnit($unitId),
            'types'             => AssessmentService::ALLOWED_TYPES,
            'forms'             => AssessmentService::ALLOWED_FORMS,
            'is_management'     => $this->isManagementRole(),
        ]);
    }

    public function store()
    {
        $unitId = (int) session()->get('active_unit_id');
        $period = get_active_period();
        $userId = (int) session()->get('user_id');

        try {
            $teacherId = (int) ($this->request->getPost('teacher_id') ?: 0);
            if (! $this->isManagementRole()) {
                $teacherId = $this->resolveOwnTeacherId();
            }

            $assessmentId = $this->assessmentService->create([
                'unit_id'            => $unitId,
                'academic_period_id' => $period ? (int) $period['id'] : 0,
                'classroom_id'       => $this->request->getPost('classroom_id'),
                'subject_id'         => $this->request->getPost('subject_id'),
                'teacher_id'         => $teacherId,
                'title'              => $this->request->getPost('title'),
                'assessment_type'    => $this->request->getPost('assessment_type'),
                'assessment_form'    => $this->request->getPost('assessment_form'),
                'assessment_date'    => $this->request->getPost('assessment_date'),
                'max_score'          => $this->request->getPost('max_score'),
                'description'        => $this->request->getPost('description'),
                'objective_ids'      => $this->request->getPost('objective_ids') ?: [],
                'criteria'           => $this->request->getPost('criteria') ?: [],
                'items'              => $this->request->getPost('items') ?: [],
            ], $userId);

            return redirect()->to(base_url('assessment/' . $assessmentId))->with('success', 'Assessment berhasil dibuat.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal membuat assessment: ' . $e->getMessage());
        }
    }

    public function edit(int $id)
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;

        try {
            $assessment = $this->assertAssessmentAccess($id);
        } catch (Exception $e) {
            return redirect()->to(base_url('assessment'))->with('error', $e->getMessage());
        }

        return view('assessment/edit', [
            'title'             => 'Edit Assessment',
            'breadcrumb_active' => 'Edit Assessment',
            'assessment'        => $assessment,
            'classrooms'        => $this->classroomsForUnit($unitId, $periodId),
            'subjects'          => $this->subjectsForUnit($unitId),
            'tps'               => $this->tpsForUnit($unitId),
            'teachers'          => $this->teachersForUnit($unitId),
            'types'             => AssessmentService::ALLOWED_TYPES,
            'forms'             => AssessmentService::ALLOWED_FORMS,
            'is_management'     => $this->isManagementRole(),
        ]);
    }

    public function update(int $id)
    {
        $userId = (int) session()->get('user_id');
        try {
            $this->assertAssessmentAccess($id);
            $this->assessmentService->update($id, [
                'title'           => $this->request->getPost('title'),
                'assessment_type' => $this->request->getPost('assessment_type'),
                'assessment_form' => $this->request->getPost('assessment_form'),
                'assessment_date' => $this->request->getPost('assessment_date'),
                'max_score'       => $this->request->getPost('max_score'),
                'description'     => $this->request->getPost('description'),
                'objective_ids'   => $this->request->getPost('objective_ids') ?: [],
                'criteria'        => $this->request->getPost('criteria') ?: [],
                'items'           => $this->request->getPost('items') ?: [],
            ], $userId);

            return redirect()->to(base_url('assessment/' . $id))->with('success', 'Assessment berhasil diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui assessment: ' . $e->getMessage());
        }
    }

    public function detail(int $id)
    {
        try {
            $assessment = $this->assertAssessmentAccess($id);
        } catch (Exception $e) {
            return redirect()->to(base_url('assessment'))->with('error', $e->getMessage());
        }

        $db     = Database::connect();
        $period = get_active_period();

        $attemptCount = $db->table('assessment_attempts')->where('assessment_id', $id)->countAllResults();
        $studentCount = $db->table('elective_students')->where('classroom_id', $assessment['classroom_id'])->where('is_active', 1)->countAllResults();
        $attemptIds   = $db->table('assessment_attempts')->select('id')->where('assessment_id', $id)->get()->getResultArray();
        $attemptIds   = array_column($attemptIds, 'id');
        $evidenceCount = $attemptIds === [] ? 0 : $db->table('assessment_evidence')->whereIn('attempt_id', $attemptIds)->countAllResults();
        $feedbackCount = $attemptIds === [] ? 0 : $db->table('assessment_feedback')->whereIn('attempt_id', $attemptIds)->countAllResults();

        return view('assessment/detail', [
            'title'             => $assessment['title'],
            'breadcrumb_active' => 'Detail Assessment',
            'assessment'        => $assessment,
            'attempt_count'     => $attemptCount,
            'student_count'     => $studentCount,
            'evidence_count'    => $evidenceCount,
            'feedback_count'    => $feedbackCount,
            'period'            => $period,
        ]);
    }

    public function transition(int $id)
    {
        $userId = (int) session()->get('user_id');
        try {
            $this->assertAssessmentAccess($id);
            $target = (string) $this->request->getPost('status');
            $this->assessmentService->transition($id, $target, $userId);
            return redirect()->to(base_url('assessment/' . $id))->with('success', 'Status assessment diperbarui ke ' . $target . '.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengubah status: ' . $e->getMessage());
        }
    }

    public function destroy(int $id)
    {
        $userId = (int) session()->get('user_id');
        try {
            $this->assertAssessmentAccess($id);
            $this->assessmentService->delete($id, $userId);
            return redirect()->to(base_url('assessment'))->with('success', 'Assessment draft dihapus.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }

    public function gradebook(int $id)
    {
        try {
            $this->assertAssessmentAccess($id);
            $data = $this->assessmentService->gradebook($id);
        } catch (Exception $e) {
            return redirect()->to(base_url('assessment'))->with('error', $e->getMessage());
        }

        return view('assessment/gradebook', [
            'title'             => 'Penilaian — ' . $data['assessment']['title'],
            'breadcrumb_active' => 'Gradebook',
            'assessment'        => $data['assessment'],
            'students'          => $data['students'],
            'rows'              => $data['rows'],
        ]);
    }

    public function saveGradebook(int $id)
    {
        $userId = (int) session()->get('user_id');
        try {
            $this->assertAssessmentAccess($id);
            $summary = $this->assessmentService->saveGradebook($id, $this->request->getPost('students') ?: [], $userId);
            return redirect()->to(base_url('assessment/' . $id . '/gradebook'))
                ->with('success', 'Nilai tersimpan. Mastery diperbarui: ' . $summary['updated'] . ' record, ' . $summary['interventions'] . ' rekomendasi intervensi baru.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan nilai: ' . $e->getMessage());
        }
    }

    public function addEvidence(int $id)
    {
        $userId = (int) session()->get('user_id');
        try {
            $this->assertAssessmentAccess($id);
            $this->assessmentService->addEvidence([
                'student_id'            => $this->request->getPost('student_id'),
                'attempt_id'            => $this->request->getPost('attempt_id'),
                'learning_objective_id' => $this->request->getPost('learning_objective_id'),
                'evidence_type'         => $this->request->getPost('evidence_type'),
                'title'                 => $this->request->getPost('title'),
                'content'               => $this->request->getPost('content'),
            ], $userId);

            return redirect()->back()->with('success', 'Bukti belajar ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan bukti: ' . $e->getMessage());
        }
    }

    public function addFeedback(int $id)
    {
        $userId = (int) session()->get('user_id');
        try {
            $this->assertAssessmentAccess($id);
            $this->assessmentService->addFeedback([
                'attempt_id'    => $this->request->getPost('attempt_id'),
                'student_id'    => $this->request->getPost('student_id'),
                'content'       => $this->request->getPost('content'),
                'feedback_type' => $this->request->getPost('feedback_type'),
            ], $userId);

            return redirect()->back()->with('success', 'Umpan balik ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan umpan balik: ' . $e->getMessage());
        }
    }

    /**
     * Mastery board per classroom/subject.
     */
    public function mastery()
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;

        $classroomId = (int) ($this->request->getGet('classroom_id') ?: 0);
        $subjectId   = (int) ($this->request->getGet('subject_id') ?: 0);
        $objectiveId = (int) ($this->request->getGet('objective_id') ?: 0);

        $board = $this->masteryService->board($unitId, $periodId, $classroomId, $subjectId, $objectiveId ?: null);
        $summary = $classroomId > 0 && $subjectId > 0
            ? $this->masteryService->masterySummary($unitId, $periodId, $classroomId, $subjectId)
            : [];

        return view('assessment/mastery', [
            'title'             => 'Mastery TP (Tujuan Pembelajaran)',
            'breadcrumb_active' => 'Mastery TP',
            'board'             => $board,
            'summary'           => $summary,
            'classrooms'        => $this->classroomsForUnit($unitId, $periodId),
            'subjects'          => $this->subjectsForUnit($unitId),
            'tps'               => $this->tpsForUnit($unitId),
            'results'           => MasteryService::ALLOWED_RESULTS,
            'selectedClassroom' => $classroomId,
            'selectedSubject'   => $subjectId,
            'selectedObjective' => $objectiveId,
        ]);
    }

    public function setMastery()
    {
        $userId = (int) session()->get('user_id');
        try {
            $this->masteryService->setMastery(
                (int) $this->request->getPost('student_id'),
                (int) $this->request->getPost('objective_id'),
                (string) $this->request->getPost('result'),
                $userId,
                [
                    'notes'   => $this->request->getPost('notes'),
                    'evidence_id' => (int) ($this->request->getPost('evidence_id') ?: 0) ?: null,
                ]
            );
            return redirect()->back()->with('success', 'Mastery TP diperbarui secara manual.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui mastery: ' . $e->getMessage());
        }
    }

    public function recommendInterventions()
    {
        $unitId    = (int) session()->get('active_unit_id');
        $period    = get_active_period();
        $periodId  = $period ? (int) $period['id'] : 0;
        $classroom = (int) ($this->request->getPost('classroom_id') ?: 0) ?: null;

        try {
            $result = $this->masteryService->recommendInterventions($unitId, $periodId, $classroom);
            return redirect()->back()->with('success', 'Rekomendasi intervensi: ' . $result['recommended'] . ' baru dari ' . $result['reviewed'] . ' record.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal merekomendasikan intervensi: ' . $e->getMessage());
        }
    }

    public function interventions()
    {
        $unitId = (int) session()->get('active_unit_id');

        $filters = [
            'unit_id'           => $unitId,
            'classroom_id'      => (int) ($this->request->getGet('classroom_id') ?: 0),
            'status'            => $this->request->getGet('status') ?: '',
            'intervention_type' => $this->request->getGet('intervention_type') ?: '',
        ];

        $interventions = $this->masteryService->listInterventions($filters);

        return view('assessment/interventions', [
            'title'             => 'Intervensi Belajar',
            'breadcrumb_active' => 'Intervensi',
            'interventions'     => $interventions,
            'classrooms'        => $this->classroomsForUnit($unitId, 0),
            'statuses'          => [MasteryService::INTERVENTION_RECOMMENDED, MasteryService::INTERVENTION_APPROVED, MasteryService::INTERVENTION_COMPLETED, MasteryService::INTERVENTION_CANCELLED],
            'types'             => [MasteryService::INTERVENTION_REMEDIAL, MasteryService::INTERVENTION_REINFORCEMENT, MasteryService::INTERVENTION_ENRICHMENT],
            'filters'           => $filters,
        ]);
    }

    public function updateIntervention(int $id)
    {
        $userId = (int) session()->get('user_id');
        try {
            $this->masteryService->updateIntervention(
                $id,
                (string) $this->request->getPost('status'),
                $userId,
                ['outcome' => $this->request->getPost('outcome')]
            );
            return redirect()->to(base_url('interventions'))->with('success', 'Intervensi diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui intervensi: ' . $e->getMessage());
        }
    }

    public function policies()
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;

        $db = Database::connect();
        $policies = $db->table('reporting_policies rp')
            ->select('rp.*, s.name as subject_name')
            ->join('subjects s', 's.id = rp.subject_id', 'left')
            ->where('rp.unit_id', $unitId)
            ->where('rp.academic_period_id', $periodId)
            ->orderBy('rp.version', 'DESC')
            ->get()->getResultArray();

        return view('assessment/policies', [
            'title'             => 'Kebijakan Pelaporan',
            'breadcrumb_active' => 'Kebijakan Pelaporan',
            'policies'          => $policies,
            'subjects'          => $this->subjectsForUnit($unitId),
            'methods'           => ['AVERAGE', 'LATEST', 'WEIGHTED', 'PROFICIENCY'],
        ]);
    }

    public function savePolicies()
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;
        $userId   = (int) session()->get('user_id');

        $subjectId = (int) ($this->request->getPost('subject_id') ?: 0);
        $name      = trim((string) $this->request->getPost('policy_name'));
        if ($name === '' || $periodId <= 0 || $unitId <= 0) {
            return redirect()->back()->with('error', 'Data kebijakan tidak lengkap.');
        }

        $db = Database::connect();
        $versionQuery = $db->table('reporting_policies')
            ->where('unit_id', $unitId)
            ->where('academic_period_id', $periodId);
        if ($subjectId > 0) {
            $versionQuery->where('subject_id', $subjectId);
        } else {
            $versionQuery->where('subject_id IS NULL');
        }
        $lastVersion = (int) ($versionQuery->selectMax('version')->get()->getRow()->version ?? 0);

        try {
            $configJson = $this->request->getPost('config_json') ?: null;
            $db->table('reporting_policies')->insert([
                'uuid'               => \App\Services\UuidService::v4(),
                'unit_id'            => $unitId,
                'academic_period_id' => $periodId,
                'subject_id'         => $subjectId ?: null,
                'policy_name'        => $name,
                'calculation_method' => strtoupper((string) $this->request->getPost('calculation_method')),
                'config_json'        => $configJson,
                'is_active'          => 1,
                'version'            => $lastVersion + 1,
                'created_by'         => $userId,
                'updated_by'         => $userId,
            ]);
            \App\Services\AuditService::log('assessment', 'CREATE_POLICY', 'ReportingPolicy', null, null, ['policy_name' => $name], 'Membuat versi kebijakan pelaporan');
            return redirect()->to(base_url('reporting-policies'))->with('success', 'Kebijakan pelaporan versi ' . ($lastVersion + 1) . ' disimpan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan kebijakan: ' . $e->getMessage());
        }
    }

    // ----------------------------------------------------------------
    // Internals
    // ----------------------------------------------------------------

    private function assertAssessmentAccess(int $id): array
    {
        $assessment = $this->assessmentService->detail($id);
        $unitId     = (int) session()->get('active_unit_id');

        UnitScopeService::assertUnit((int) $assessment['unit_id']);

        if ($this->isManagementRole()) {
            return $assessment;
        }

        $ownTeacherId = $this->resolveOwnTeacherId();
        if ($ownTeacherId > 0 && $ownTeacherId === (int) ($assessment['teacher_id'] ?? 0)) {
            return $assessment;
        }

        throw new AuthorizationException('Anda hanya dapat mengelola assessment milik Anda sendiri.');
    }

    private function isManagementRole(): bool
    {
        return has_role('super_admin', 'superadmin', 'wakasek_kurikulum', 'admin_smp', 'admin_sma');
    }

    private function resolveOwnTeacherId(): int
    {
        $userId = (int) session()->get('user_id');
        if ($userId <= 0) {
            return 0;
        }

        $teacherId = (int) (session()->get('teacher_id') ?? 0);
        if ($teacherId > 0) {
            return $teacherId;
        }

        $user = Database::connect()->table('users')
            ->select('teacher_id')
            ->where('id', $userId)
            ->where('is_active', 1)
            ->get()->getRowArray();

        $teacherId = $user ? (int) $user['teacher_id'] : 0;
        if ($teacherId > 0) {
            session()->set('teacher_id', $teacherId);
        }

        return $teacherId;
    }

    private function classroomsForUnit(int $unitId, int $periodId): array
    {
        $builder = Database::connect()->table('classrooms')
            ->where('unit_id', $unitId)
            ->orderBy('name', 'ASC');
        if ($periodId > 0) {
            $builder->where('academic_period_id', $periodId);
        }
        return $builder->get()->getResultArray();
    }

    private function subjectsForUnit(int $unitId): array
    {
        return Database::connect()->table('subjects s')
            ->select('s.*')
            ->join('subject_unit_availability sua', 'sua.subject_id = s.id', 'left')
            ->where('s.is_active', 1)
            ->where('sua.unit_id', $unitId)
            ->where('sua.is_available', 1)
            ->groupBy('s.id')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();
    }

    private function tpsForUnit(int $unitId): array
    {
        return Database::connect()->table('learning_objectives_tp lot')
            ->select('lot.id, lot.code, lot.statement as name, lot.unit_id, lo.subject_id, s.name as subject_name')
            ->join('learning_outcomes_cp lo', 'lo.id = lot.learning_outcome_id', 'left')
            ->join('subjects s', 's.id = lo.subject_id', 'left')
            ->where('lot.unit_id', $unitId)
            ->orderBy('lo.subject_id', 'ASC')
            ->orderBy('lot.code', 'ASC')
            ->get()->getResultArray();
    }

    private function teachersForUnit(int $unitId): array
    {
        return Database::connect()->table('teachers')
            ->where('primary_unit_id', $unitId)
            ->where('is_active', 1)
            ->orderBy('full_name', 'ASC')
            ->get()->getResultArray();
    }
}