<?php

namespace App\Controllers;

use App\Exceptions\ConcurrencyException;
use App\Services\LessonPlanService;
use Config\Database;

class LessonPlanController extends BaseController
{
    public function index()
    {
        $db = Database::connect();
        $userId = session()->get('user_id');
        $unitId = (int) session()->get('active_unit_id');

        $plans = $db->table('lesson_plans lp')
            ->select('lp.*, s.name subject_name, gl.name grade_name, t.full_name teacher_name')
            ->join('subjects s', 's.id=lp.subject_id')
            ->join('grade_levels gl', 'gl.id=lp.grade_level_id')
            ->join('teachers t', 't.id=lp.teacher_id', 'left')
            ->where('lp.unit_id', $unitId)
            ->orderBy('lp.date', 'DESC')
            ->orderBy('lp.session_number', 'ASC')
            ->get()->getResultArray();

        return view('lesson_plans/index', [
            'title' => 'Rencana Pembelajaran',
            'breadcrumb_active' => 'Lesson Plans',
            'plans' => $plans,
        ]);
    }

    public function create()
    {
        $db = Database::connect();
        $unitId = (int) session()->get('active_unit_id');

        $subjects = $db->table('subjects su')
            ->join('subject_unit_availability sua', 'sua.subject_id=su.id')
            ->where('sua.unit_id', $unitId)
            ->where('sua.is_available', 1)
            ->get()->getResultArray();
        $gradeLevels = $db->table('grade_levels')->where('unit_id', $unitId)->get()->getResultArray();
        $classes = $db->table('classrooms')->where('unit_id', $unitId)->get()->getResultArray();
        $teachers = $db->table('teachers')->where('primary_unit_id', $unitId)->where('is_active', 1)->get()->getResultArray();
        $packs = $db->table('subject_learning_packs')->where('unit_id', $unitId)->get()->getResultArray();

        return view('lesson_plans/create', [
            'title' => 'Buat Rencana Pembelajaran',
            'breadcrumb_active' => 'Lesson Plans',
            'subjects' => $subjects,
            'gradeLevels' => $gradeLevels,
            'classes' => $classes,
            'teachers' => $teachers,
            'packs' => $packs,
        ]);
    }

    public function store()
    {
        try {
            $plan = LessonPlanService::create($this->request->getPost());
            return redirect()->to('lesson-plans/' . $plan['uuid'])->with('success', 'Rencana pembelajaran berhasil dibuat.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function detail(string $uuid)
    {
        return $this->page($uuid, 'overview');
    }

    public function design(string $uuid)
    {
        return $this->page($uuid, 'design');
    }

    public function stages(string $uuid)
    {
        return $this->page($uuid, 'stages');
    }

    public function activities(string $uuid)
    {
        return $this->page($uuid, 'activities');
    }

    public function assessments(string $uuid)
    {
        return $this->page($uuid, 'assessments');
    }

    public function updateDesign(string $uuid)
    {
        return $this->run(fn () => LessonPlanService::updateDesign($uuid, $this->request->getPost()), $uuid, 'design', 'Desain pembelajaran berhasil diperbarui.');
    }

    public function addObjective(string $uuid)
    {
        return $this->run(fn () => LessonPlanService::addObjective($uuid, (string) $this->request->getPost('objective_uuid')), $uuid, 'overview', 'TP berhasil ditambahkan.');
    }

    public function addStage(string $uuid)
    {
        return $this->run(fn () => LessonPlanService::addStage($uuid, $this->request->getPost()), $uuid, 'stages', 'Tahap pembelajaran berhasil ditambahkan.');
    }

    public function addActivity(string $uuid)
    {
        return $this->run(fn () => LessonPlanService::addActivity($uuid, $this->request->getPost()), $uuid, 'activities', 'Aktivitas berhasil ditambahkan.');
    }

    public function addAssessment(string $uuid)
    {
        return $this->run(fn () => LessonPlanService::addAssessment($uuid, $this->request->getPost()), $uuid, 'assessments', 'Asesmen berhasil ditambahkan.');
    }

    public function addRubric(string $uuid, string $assessmentUuid)
    {
        return $this->run(fn () => LessonPlanService::addRubric($assessmentUuid, $this->request->getPost()), $uuid, 'assessments', 'Rubrik berhasil ditambahkan.');
    }

    public function linkActivityResource(string $uuid, string $activityUuid)
    {
        return $this->run(fn () => LessonPlanService::linkActivityResource($activityUuid, $this->request->getPost()), $uuid, 'activities', 'Resource berhasil ditautkan.');
    }

    public function validatePlan(string $uuid)
    {
        $result = LessonPlanService::validatePlan($uuid);
        return $this->response->setJSON($result);
    }

    public function transition(string $uuid)
    {
        $target = strtoupper((string) $this->request->getPost('target_status'));
        $permMap = ['READY' => 'lesson_plans.manage', 'IN_PROGRESS' => 'lesson_plans.manage', 'COMPLETED' => 'lesson_plans.manage', 'REFLECTED' => 'lesson_plans.manage'];
        $perm = $permMap[$target] ?? '';
        if ($perm === '' || ! has_permission($perm)) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'error' => 'Hak akses tidak cukup.']);
        }
        if ($target === 'READY') {
            $validation = LessonPlanService::validatePlan($uuid);
            if (! $validation['valid']) {
                return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'errors' => $validation['errors'], 'error' => 'Rencana belum lengkap.']);
            }
        }
        return $this->run(fn () => LessonPlanService::transition($uuid, $target, (int) $this->request->getPost('revision_number')), $uuid, 'overview', 'Status rencana pembelajaran berhasil diperbarui.');
    }

    public function clonePlan(string $uuid)
    {
        try {
            $cloned = LessonPlanService::clone($uuid, [
                'teacher_id' => (int) session()->get('user_id'),
                'date' => $this->request->getPost('date') ?? date('Y-m-d'),
                'session_number' => (int) ($this->request->getPost('session_number') ?? 1),
            ]);
            return redirect()->to('lesson-plans/' . $cloned['uuid'])->with('success', 'Rencana pembelajaran berhasil dikloning.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    // ---- Private helpers ----

    private function page(string $uuid, string $section)
    {
        $plan = $this->plan($uuid);
        $db = Database::connect();
        $planId = (int) $plan['id'];

        $data = [
            'title' => $plan['session_label'] ?? 'Rencana Pembelajaran',
            'breadcrumb_active' => 'Lesson Plans',
            'section' => $section,
            'plan' => $plan,
            'summary' => LessonPlanService::summary($uuid),
            'objectives' => $db->table('lesson_plan_objectives lpo')
                ->join('learning_objectives_tp tp', 'tp.id=lpo.learning_objective_id')
                ->where('lpo.lesson_plan_id', $planId)
                ->orderBy('lpo.sequence_order')
                ->get()->getResultArray(),
            'stages' => $db->table('lesson_plan_stages')
                ->where('lesson_plan_id', $planId)
                ->orderBy('sequence_order')
                ->get()->getResultArray(),
            'activities' => $db->table('lesson_plan_activities')
                ->where('lesson_plan_id', $planId)
                ->orderBy('sequence_order')
                ->get()->getResultArray(),
            'assessments' => $db->table('lesson_plan_assessments')
                ->where('lesson_plan_id', $planId)
                ->orderBy('sequence_order')
                ->get()->getResultArray(),
            'packActivities' => $plan['learning_pack_id']
                ? $db->table('learning_activities')->where('learning_pack_id', (int) $plan['learning_pack_id'])->get()->getResultArray()
                : [],
            'stages_for_select' => $db->table('lesson_plan_stages')->where('lesson_plan_id', $planId)->get()->getResultArray(),
            'canManage' => has_permission('lesson_plans.manage') && ! in_array($plan['status'], ['COMPLETED', 'REFLECTED'], true),
            'objectiveOptions' => $db->table('learning_objectives_tp')
                ->orderBy('code')
                ->limit(100)
                ->get()->getResultArray(),
        ];

        return view('lesson_plans/detail', $data);
    }

    private function plan(string $uuid): array
    {
        $db = Database::connect();
        $plan = $db->table('lesson_plans lp')
            ->select('lp.*, s.name subject_name, gl.name grade_name, t.full_name teacher_name, cv.code pack_code, lu.title unit_title')
            ->join('subjects s', 's.id=lp.subject_id')
            ->join('grade_levels gl', 'gl.id=lp.grade_level_id')
            ->join('teachers t', 't.id=lp.teacher_id', 'left')
            ->leftJoin('subject_learning_packs pk', 'pk.id=lp.learning_pack_id')
            ->leftJoin('curriculum_versions cv', 'cv.id=pk.curriculum_version_id')
            ->leftJoin('learning_units lu', 'lu.id=lp.learning_unit_id')
            ->where('lp.uuid', $uuid)
            ->get()->getRowArray();
        if (! $plan) {
            throw new \RuntimeException('Rencana pembelajaran tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $plan['unit_id']);
        return $plan;
    }

    private function run(callable $operation, string $uuid, string $section, string $message)
    {
        try {
            $operation();
            return redirect()->to('lesson-plans/' . $uuid . '/' . $section)->with('success', $message);
        } catch (\Throwable $e) {
            if ($e instanceof ConcurrencyException) {
                return $this->response->setStatusCode(409)->setJSON(['ok' => false, 'error' => $e->getMessage()]);
            }
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }
}
