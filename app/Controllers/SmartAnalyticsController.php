<?php

namespace App\Controllers;

use App\Services\AdaptiveModeService;
use App\Services\CurriculumLineageService;
use App\Services\DifferentiationService;
use App\Services\MasteryHeatmapService;
use App\Services\NarrativeService;
use App\Services\ReflectionTrendsService;
use App\Services\RemediationPackagerService;
use App\Services\RubricGeneratorService;
use Config\Database;

/**
 * SmartAnalyticsController — Handles all smart/enhanced analytics & pedagogical engines
 * across Phases 1–6 (Lineage Graph, Mastery Heatmap, Reflection Trends, Remedial Packager, Narrative Drafter, Rubric Generator).
 */
class SmartAnalyticsController extends BaseController
{
    // ====================================================================
    // PHASE 1: CURRICULUM LINEAGE GRAPH
    // ====================================================================

    /**
     * Lineage Graph page — interactive visualisation of the curriculum DAG.
     */
    public function lineageGraph()
    {
        $unitId    = (int) session()->get('active_unit_id');
        $subjectId = (int) ($this->request->getGet('subject_id') ?: 0) ?: null;

        $lineageService = new CurriculumLineageService();
        $subjects = $lineageService->getSubjectsWithLineage($unitId);

        $graphData = null;
        if ($subjectId) {
            $graphData = $lineageService->buildLineageGraph($unitId, $subjectId);
        }

        return view('smart/lineage_graph', [
            'title'             => 'Peta Lineage Kurikulum',
            'breadcrumb_active' => 'Lineage Graph',
            'subjects'          => $subjects,
            'selectedSubject'   => $subjectId,
            'graphData'         => $graphData,
        ]);
    }

    /**
     * AJAX endpoint: returns lineage graph data as JSON for D3.js rendering.
     */
    public function lineageGraphData()
    {
        $unitId    = (int) session()->get('active_unit_id');
        $subjectId = (int) ($this->request->getGet('subject_id') ?: 0);

        if (!$subjectId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Subject ID required']);
        }

        $lineageService = new CurriculumLineageService();
        $data = $lineageService->buildLineageGraph($unitId, $subjectId);

        return $this->response->setJSON(['status' => 'success', 'data' => $data]);
    }

    // ====================================================================
    // PHASE 6: MASTERY HEATMAP
    // ====================================================================

    /**
     * Mastery Heatmap page — interactive colour-coded matrix of students × TP.
     */
    public function masteryHeatmap()
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;

        $classroomId = (int) ($this->request->getGet('classroom_id') ?: 0);
        $subjectId   = (int) ($this->request->getGet('subject_id') ?: 0);

        $heatmapData = null;
        $chartData   = null;

        if ($classroomId && $subjectId) {
            $heatmapService = new MasteryHeatmapService();
            $heatmapData = $heatmapService->buildHeatmap($unitId, $periodId, $classroomId, $subjectId);
            $chartData   = $heatmapService->distributionChartData(
                $heatmapData['distribution'],
                $heatmapData['objectives']
            );
        }

        // Get classrooms and subjects for filters
        $db = Database::connect();
        $classrooms = $db->table('classrooms')
            ->select('id, name')
            ->where('unit_id', $unitId)
            ->where('academic_period_id', $periodId)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        $subjects = $db->table('subjects')
            ->select('id, name')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        return view('smart/mastery_heatmap', [
            'title'             => 'Mastery Heatmap TP',
            'breadcrumb_active' => 'Mastery Heatmap',
            'heatmapData'       => $heatmapData,
            'chartData'         => $chartData,
            'classrooms'        => $classrooms,
            'subjects'          => $subjects,
            'selectedClassroom' => $classroomId,
            'selectedSubject'   => $subjectId,
        ]);
    }

    // ====================================================================
    // PHASE 5: REFLECTION TRENDS
    // ====================================================================

    /**
     * Reflection Trends dashboard — aggregated teaching reflection analytics.
     */
    public function reflectionTrends()
    {
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;
        $userId   = (int) session()->get('user_id');
        $db       = Database::connect();

        // Resolve teacher_id from user/session
        $teacherId = (int) (get_teacher_id() ?? session()->get('teacher_id') ?? 0);
        if ($teacherId <= 0 && $userId > 0) {
            $user = $db->table('users')->where('id', $userId)->get()->getRowArray();
            if (!empty($user['teacher_id'])) {
                $teacherId = (int) $user['teacher_id'];
            } elseif (!empty($user['email'])) {
                $teacher = $db->table('teachers')->where('email', $user['email'])->where('deleted_at IS NULL')->get()->getRowArray();
                if ($teacher) {
                    $teacherId = (int) $teacher['id'];
                }
            }
        }

        // For superadmin/management: allow teacher selection
        $selectedTeacher = null;
        $teachers = [];
        if (has_role('super_admin', 'superadmin', 'admin_smp', 'admin_sma', 'wakasek_kurikulum', 'kepala_sekolah')) {
            $unitId = (int) session()->get('active_unit_id');
            if ($db->tableExists('teachers')) {
                $teachers = $db->table('teachers')
                    ->select('id, full_name')
                    ->where('deleted_at IS NULL')
                    ->orderBy('full_name', 'ASC')
                    ->get()->getResultArray();
            }

            $selectedTeacher = (int) ($this->request->getGet('teacher_id') ?: 0);
            if ($selectedTeacher > 0) {
                $teacherId = $selectedTeacher;
            } elseif (!empty($teachers)) {
                $teacherId = (int) $teachers[0]['id'];
                $selectedTeacher = $teacherId;
            }
        }

        $subjectId = (int) ($this->request->getGet('subject_id') ?: 0) ?: null;

        $trends = null;
        if ($teacherId > 0) {
            $trendsService = new ReflectionTrendsService();
            $trends = $trendsService->buildTrends($teacherId, $periodId, $subjectId);
        }

        return view('smart/reflection_trends', [
            'title'             => 'Tren Refleksi Mengajar',
            'breadcrumb_active' => 'Tren Refleksi',
            'trends'            => $trends,
            'teachers'          => $teachers,
            'selectedTeacher'   => $selectedTeacher,
            'subjectId'         => $subjectId,
            'teacherId'         => $teacherId,
        ]);
    }

    // ====================================================================
    // PHASE 6: SMART REMEDIAL PACKAGER
    // ====================================================================

    /**
     * Remedial package view/builder.
     */
    public function remedialPackage()
    {
        $studentId   = (int) ($this->request->getGet('student_id') ?: 0);
        $objectiveId = (int) ($this->request->getGet('objective_id') ?: 0);

        $package = null;
        if ($studentId && $objectiveId) {
            $packager = new RemediationPackagerService();
            $package = $packager->buildPackage($studentId, $objectiveId);
        }

        return view('smart/remedial_package', [
            'title'             => 'Paket Remedial Terarah',
            'breadcrumb_active' => 'Paket Remedial',
            'package'           => $package,
            'studentId'         => $studentId,
            'objectiveId'       => $objectiveId,
        ]);
    }

    // ====================================================================
    // PHASE 6: NARRATIVE REPORT DRAFTER
    // ====================================================================

    /**
     * Narrative report drafter view.
     */
    public function narrativeDrafter()
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;

        $classroomId = (int) ($this->request->getGet('classroom_id') ?: 0);
        $subjectId   = (int) ($this->request->getGet('subject_id') ?: 0);
        $studentId   = (int) ($this->request->getGet('student_id') ?: 0);

        $narrativeData = null;
        if ($studentId && $subjectId) {
            $narrativeService = new NarrativeService();
            $narrativeData = $narrativeService->generateStudentNarrative($studentId, $subjectId, $periodId);
        }

        $db = Database::connect();
        $classrooms = $db->table('classrooms')
            ->select('id, name')
            ->where('unit_id', $unitId)
            ->where('academic_period_id', $periodId)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        $subjects = $db->table('subjects')
            ->select('id, name')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        $students = [];
        if ($classroomId) {
            $students = $db->table('elective_students')
                ->select('id, full_name, student_number')
                ->where('classroom_id', $classroomId)
                ->where('is_active', 1)
                ->orderBy('full_name', 'ASC')
                ->get()->getResultArray();
        }

        return view('smart/narrative_drafter', [
            'title'             => 'Penyusun Draf Narasi Rapor',
            'breadcrumb_active' => 'Draf Narasi Rapor',
            'narrativeData'     => $narrativeData,
            'classrooms'        => $classrooms,
            'subjects'          => $subjects,
            'students'          => $students,
            'selectedClassroom' => $classroomId,
            'selectedSubject'   => $subjectId,
            'selectedStudent'   => $studentId,
        ]);
    }

    // ====================================================================
    // PHASE 4: RUBRIC GENERATOR & DIFFERENTIATION ASSISTANT (AJAX)
    // ====================================================================

    /**
     * AJAX endpoint to generate a standardized 4-tier rubric for a TP.
     */
    public function generateRubricAjax()
    {
        $tpText = (string) $this->request->getPost('tp_text');
        if (trim($tpText) === '') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Teks TP tidak boleh kosong']);
        }

        $generator = new RubricGeneratorService();
        $rubric = $generator->generateForObjective($tpText);

        return $this->response->setJSON(['status' => 'success', 'data' => $rubric]);
    }

    /**
     * AJAX endpoint to generate differentiated instruction strategies.
     */
    public function generateDifferentiationAjax()
    {
        $topic   = (string) $this->request->getPost('topic');
        $subject = (string) ($this->request->getPost('subject') ?: 'Umum');
        $phase   = (string) ($this->request->getPost('phase') ?: 'E');

        if (trim($topic) === '') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Topik/TP tidak boleh kosong']);
        }

        $diffService = new DifferentiationService();
        $strategies = $diffService->generateStrategies($topic, $subject, $phase);

        return $this->response->setJSON(['status' => 'success', 'data' => $strategies]);
    }

    /**
     * AJAX endpoint to suggest unplugged/offline learning alternatives.
     */
    public function generateAdaptiveAjax()
    {
        $concept = (string) $this->request->getPost('concept');
        $activity = (string) ($this->request->getPost('activity') ?: '');

        if (trim($concept) === '') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Konsep/Topik tidak boleh kosong']);
        }

        $adaptiveService = new AdaptiveModeService();
        $alternative = $adaptiveService->getUnpluggedAlternative($concept, $activity);

        return $this->response->setJSON(['status' => 'success', 'data' => $alternative]);
    }
}
