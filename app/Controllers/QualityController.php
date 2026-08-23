<?php

namespace App\Controllers;

use App\Services\QualityService;
use App\Services\UnitScopeService;
use Config\Database;
use Exception;

class QualityController extends BaseController
{
    private QualityService $qualityService;

    public function __construct(?QualityService $qualityService = null)
    {
        $this->qualityService = $qualityService ?? new QualityService();
    }

    // ================================================================
    // DASHBOARD
    // ================================================================

    public function index()
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;
        $userId   = (int) session()->get('user_id');

        $reflectionStats  = $this->qualityService->reflectionStats($unitId, $periodId);
        $supervisionStats = $this->qualityService->supervisionStats($unitId, $periodId);
        $aiStats          = $this->qualityService->aiStats($userId);
        $ratingBreakdown  = $this->qualityService->supervisionRatingBreakdown($unitId, $periodId);
        $typeBreakdown    = $this->qualityService->reflectionTypeBreakdown($unitId, $periodId);
        $aiAdoption       = $this->qualityService->aiAdoptionBreakdown($userId);

        return view('quality/index', [
            'title'             => 'Kualitas & AI Copilot',
            'breadcrumb_active' => 'Kualitas',
            'reflectionStats'   => $reflectionStats,
            'supervisionStats'  => $supervisionStats,
            'aiStats'           => $aiStats,
            'ratingBreakdown'   => $ratingBreakdown,
            'typeBreakdown'     => $typeBreakdown,
            'aiAdoption'        => $aiAdoption,
        ]);
    }

    // ================================================================
    // REFLECTIONS
    // ================================================================

    public function reflections()
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;

        $filters = [
            'teacher_id' => $this->request->getGet('teacher_id') ?: '',
            'status'     => $this->request->getGet('status') ?: '',
        ];

        $reflections = $this->qualityService->allReflections($unitId, $periodId, $filters);
        $teachers = Database::connect()->table('teachers')->where('is_active', 1)->orderBy('full_name', 'ASC')->get()->getResultArray();

        return view('quality/reflections', [
            'title'             => 'Refleksi Guru',
            'breadcrumb_active' => 'Refleksi',
            'reflections'       => $reflections,
            'teachers'          => $teachers,
            'filters'           => $filters,
            'statuses'          => ['DRAFT', 'PUBLISHED'],
            'types'             => QualityService::ALLOWED_REFLECTION_TYPES,
        ]);
    }

    public function createReflection()
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;

        $teachers = Database::connect()->table('teachers')->where('is_active', 1)->orderBy('full_name', 'ASC')->get()->getResultArray();
        $subjects = Database::connect()->table('subjects')->orderBy('name', 'ASC')->get()->getResultArray();
        $classrooms = Database::connect()->table('classrooms')->where('unit_id', $unitId)->orderBy('name', 'ASC')->get()->getResultArray();

        return view('quality/reflection_form', [
            'title'             => 'Buat Refleksi',
            'breadcrumb_active' => 'Refleksi Baru',
            'teachers'          => $teachers,
            'subjects'          => $subjects,
            'classrooms'        => $classrooms,
            'types'             => QualityService::ALLOWED_REFLECTION_TYPES,
            'isEdit'            => false,
        ]);
    }

    public function storeReflection()
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;
        $userId   = (int) session()->get('user_id');

        try {
            $id = $this->qualityService->createReflection([
                'teacher_id'        => (int) $this->request->getPost('teacher_id'),
                'unit_id'           => $unitId,
                'academic_period_id' => $periodId,
                'subject_id'        => $this->request->getPost('subject_id') ?: null,
                'classroom_id'      => $this->request->getPost('classroom_id') ?: null,
                'reflection_type'   => $this->request->getPost('reflection_type') ?? QualityService::REFLECTION_POST_LESSON,
                'what_went_well'    => $this->request->getPost('what_went_well'),
                'what_to_improve'   => $this->request->getPost('what_to_improve'),
                'next_steps'        => $this->request->getPost('next_steps'),
            ], $userId);

            return redirect()->to(base_url('quality/reflection/' . $id))->with('success', 'Refleksi berhasil dibuat.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function detailReflection(int $id)
    {
        $reflection = $this->qualityService->detailReflection($id);
        if (! $reflection) {
            return redirect()->to(base_url('quality/reflections'))->with('error', 'Refleksi tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $reflection['unit_id']);

        return view('quality/reflection_detail', [
            'title'             => 'Detail Refleksi',
            'breadcrumb_active' => 'Detail Refleksi',
            'reflection'        => $reflection,
        ]);
    }

    public function updateReflection(int $id)
    {
        $userId = (int) session()->get('user_id');
        $this->qualityService->updateReflection($id, [
            'what_went_well'  => $this->request->getPost('what_went_well'),
            'what_to_improve' => $this->request->getPost('what_to_improve'),
            'next_steps'      => $this->request->getPost('next_steps'),
            'status'          => $this->request->getPost('status'),
        ], $userId);

        return redirect()->back()->with('success', 'Refleksi berhasil diperbarui.');
    }

    public function publishReflection(int $id)
    {
        $userId = (int) session()->get('user_id');
        $this->qualityService->updateReflection($id, ['status' => 'PUBLISHED'], $userId);
        return redirect()->back()->with('success', 'Refleksi berhasil dipublikasikan.');
    }

    // ================================================================
    // SUPERVISION
    // ================================================================

    public function supervisions()
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;

        $filters = [
            'teacher_id' => $this->request->getGet('teacher_id') ?: '',
            'status'     => $this->request->getGet('status') ?: '',
            'follow_up'  => $this->request->getGet('follow_up') ?: '',
        ];

        $records = $this->qualityService->listSupervisions($unitId, $periodId, $filters);
        $teachers = Database::connect()->table('teachers')->where('is_active', 1)->orderBy('full_name', 'ASC')->get()->getResultArray();

        return view('quality/supervisions', [
            'title'             => 'Supervisi',
            'breadcrumb_active' => 'Supervisi',
            'records'           => $records,
            'teachers'          => $teachers,
            'filters'           => $filters,
            'statuses'          => ['DRAFT', 'COMPLETED'],
            'ratings'           => QualityService::ALLOWED_RATINGS,
        ]);
    }

    public function createSupervision()
    {
        $unitId   = (int) session()->get('active_unit_id');
        $teachers = Database::connect()->table('teachers')->where('is_active', 1)->orderBy('full_name', 'ASC')->get()->getResultArray();
        $subjects = Database::connect()->table('subjects')->orderBy('name', 'ASC')->get()->getResultArray();
        $classrooms = Database::connect()->table('classrooms')->where('unit_id', $unitId)->orderBy('name', 'ASC')->get()->getResultArray();

        return view('quality/supervision_form', [
            'title'             => 'Buat Catatan Supervisi',
            'breadcrumb_active' => 'Supervisi Baru',
            'teachers'          => $teachers,
            'subjects'          => $subjects,
            'classrooms'        => $classrooms,
            'observationTypes'  => QualityService::ALLOWED_OBSERVATION_TYPES,
            'ratings'           => QualityService::ALLOWED_RATINGS,
        ]);
    }

    public function storeSupervision()
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;
        $userId   = (int) session()->get('user_id');

        // Resolve supervisor's teacher ID from user_id (supervisor_id FK → teachers.id)
        $db = Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $supervisorId = (int) $this->request->getPost('teacher_id'); // default: supervised teacher is also the supervisor
        try {
            // Check if teachers table has user_id column
            $columns = array_column($db->getFieldData('teachers'), 'name');
            if (in_array('user_id', $columns)) {
                $supervisorTeacher = $db->table('teachers')
                    ->select('id')
                    ->groupStart()
                        ->where('user_id', $userId)
                        ->orWhere('id', $userId)
                    ->groupEnd()
                    ->where('is_active', 1)
                    ->get()->getRowArray();
                if ($supervisorTeacher) {
                    $supervisorId = (int) $supervisorTeacher['id'];
                }
            } else {
                // teachers table has no user_id — try direct ID match
                $supervisorTeacher = $db->table('teachers')
                    ->select('id')
                    ->where('id', $userId)
                    ->where('is_active', 1)
                    ->get()->getRowArray();
                if ($supervisorTeacher) {
                    $supervisorId = (int) $supervisorTeacher['id'];
                }
            }
        } catch (\Exception $e) {
            // Silently fall back to the teacher_id from the form
        }

        try {
            $id = $this->qualityService->createSupervision([
                'teacher_id'        => (int) $this->request->getPost('teacher_id'),
                'supervisor_id'     => $supervisorId,
                'unit_id'           => $unitId,
                'academic_period_id' => $periodId,
                'observation_date'  => $this->request->getPost('observation_date'),
                'subject_id'        => $this->request->getPost('subject_id') ?: null,
                'classroom_id'      => $this->request->getPost('classroom_id') ?: null,
                'observation_type'  => $this->request->getPost('observation_type') ?? QualityService::OBS_CLASSROOM,
                'strengths'         => $this->request->getPost('strengths'),
                'areas_for_growth'  => $this->request->getPost('areas_for_growth'),
                'recommendations'   => $this->request->getPost('recommendations'),
                'overall_rating'    => $this->request->getPost('overall_rating'),
                'follow_up_needed'  => (int) $this->request->getPost('follow_up_needed'),
                'follow_up_notes'   => $this->request->getPost('follow_up_notes'),
            ], $userId);

            return redirect()->to(base_url('quality/supervisions/' . $id))->with('success', 'Catatan supervisi berhasil dibuat.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function detailSupervision(int $id)
    {
        $record = $this->qualityService->detailSupervision($id);
        if (! $record) {
            return redirect()->to(base_url('quality/supervisions'))->with('error', 'Catatan tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $record['unit_id']);

        return view('quality/supervision_detail', [
            'title'             => 'Detail Supervisi',
            'breadcrumb_active' => 'Detail Supervisi',
            'record'            => $record,
            'ratings'           => QualityService::ALLOWED_RATINGS,
        ]);
    }

    public function updateSupervision(int $id)
    {
        $userId   = (int) session()->get('user_id');
        $postData = $this->request->getPost();

        // Only include fields that are actually present in the POST data
        $fields = ['strengths', 'areas_for_growth', 'recommendations', 'overall_rating', 'follow_up_needed', 'follow_up_notes', 'status'];
        $payload = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $postData)) {
                $payload[$field] = $field === 'follow_up_needed' ? (int) $postData[$field] : $postData[$field];
            }
        }

        $this->qualityService->updateSupervision($id, $payload, $userId);

        return redirect()->back()->with('success', 'Catatan supervisi berhasil diperbarui.');
    }

    public function printSupervision(int $id)
    {
        $record = $this->qualityService->detailSupervision($id);
        if (! $record) {
            return redirect()->to(base_url('quality/supervisions'))->with('error', 'Catatan supervisi tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $record['unit_id']);

        $db = Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $unit = $db->table('school_units')->where('id', $record['unit_id'])->get()->getRowArray();
        $period = $db->table('academic_periods')->where('id', $record['academic_period_id'])->get()->getRowArray();

        return view('quality/supervision_print', [
            'title'  => 'Laporan Supervisi Akademik — ' . ($record['teacher_name'] ?? 'Guru'),
            'record' => $record,
            'unit'   => $unit,
            'period' => $period,
        ]);
    }

    // ================================================================
    // AI COPILOT
    // ================================================================

    public function copilot()
    {
        $userId = (int) session()->get('user_id');
        $stats  = $this->qualityService->aiStats($userId);

        return view('quality/copilot', [
            'title'             => 'AI Copilot',
            'breadcrumb_active' => 'AI Copilot',
            'stats'             => $stats,
            'promptTypes'       => [
                QualityService::PROMPT_REFLECTION_DRAFT   => 'Draft Refleksi',
                QualityService::PROMPT_SUPERVISION_SUMMARY => 'Ringkasan Supervisi',
                QualityService::PROMPT_IMPROVEMENT_IDEA    => 'Ide Perbaikan',
                QualityService::PROMPT_LEARNING_TIPS       => 'Tips Pembelajaran',
                QualityService::PROMPT_CLASS_SUMMARY       => 'Ringkasan Kelas',
            ],
        ]);
    }

    public function generateDraft()
    {
        $userId = (int) session()->get('user_id');
        $promptType = $this->request->getPost('prompt_type') ?? '';

        $context = [];
        foreach (['subject', 'teacher_name', 'rating', 'area', 'class_name', 'mastery_pct', 'attendance_pct', 'avg_score', 'total_students'] as $key) {
            $val = $this->request->getPost($key);
            if ($val !== null && $val !== '') {
                $context[$key] = $val;
            }
        }

        try {
            $result = $this->qualityService->generateAiDraft($promptType, $context, $userId);
            return redirect()->back()->with('ai_result', $result['output'])->with('ai_id', $result['id']);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal generate draft: ' . $e->getMessage());
        }
    }

    public function acceptDraft(int $id)
    {
        $userId = (int) session()->get('user_id');
        $this->qualityService->acceptAiOutput($id, $this->request->getPost('human_edit'), $userId);
        return redirect()->back()->with('success', 'Draft berhasil diterima.');
    }

    public function rejectDraft(int $id)
    {
        $userId = (int) session()->get('user_id');
        $this->qualityService->rejectAiOutput($id, $this->request->getPost('reason'), $userId);
        return redirect()->back()->with('success', 'Draft ditolak.');
    }

    public function feedback(int $id)
    {
        $userId = (int) session()->get('user_id');
        $this->qualityService->submitFeedback($id, $this->request->getPost('feedback_rating'), $this->request->getPost('feedback_notes'), $userId);
        return redirect()->back()->with('success', 'Feedback tersimpan.');
    }

    // ================================================================
    // KSP EVALUATION DASHBOARD
    // ================================================================

    public function kspDashboard()
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;

        $db = Database::connect();
        $evaluations = $db->table('ksp_evaluations ke')
            ->join('ksp_versions kv', 'kv.id = ke.ksp_version_id')
            ->where('kv.unit_id', $unitId)
            ->orderBy('ke.created_at', 'DESC')
            ->get()->getResultArray();

        $actions = $db->table('improvement_actions ia')
            ->join('users u', 'u.id = ia.owner_user_id', 'left')
            ->where('ia.unit_id', $unitId)
            ->select('ia.*, u.full_name AS owner_name')
            ->orderBy('ia.created_at', 'DESC')
            ->get()->getResultArray();

        return view('quality/ksp_dashboard', [
            'title'             => 'Evaluasi KSP',
            'breadcrumb_active' => 'Evaluasi KSP',
            'evaluations'       => $evaluations,
            'actions'           => $actions,
        ]);
    }

    // ================================================================
    // QUALITY REPORT
    // ================================================================

    public function qualityReport()
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;

        $reflectionStats  = $this->qualityService->reflectionStats($unitId, $periodId);
        $supervisionStats = $this->qualityService->supervisionStats($unitId, $periodId);
        $ratingBreakdown  = $this->qualityService->supervisionRatingBreakdown($unitId, $periodId);
        $typeBreakdown    = $this->qualityService->reflectionTypeBreakdown($unitId, $periodId);
        $aiAdoption       = $this->qualityService->aiAdoptionBreakdown(0);

        return view('quality/quality_report', [
            'title'             => 'Laporan Mutu & Kualitas',
            'breadcrumb_active' => 'Laporan Mutu',
            'reflectionStats'   => $reflectionStats,
            'supervisionStats'  => $supervisionStats,
            'ratingBreakdown'   => $ratingBreakdown,
            'typeBreakdown'     => $typeBreakdown,
            'aiAdoption'        => $aiAdoption,
        ]);
    }
}
