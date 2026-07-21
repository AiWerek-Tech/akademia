<?php

namespace App\Controllers;

use App\Services\CurriculumVersionService;
use App\Services\CurriculumStructureService;
use App\Services\CurriculumValidationService;
use App\Services\CurriculumWorkflowService;
use App\Services\CurriculumReconciliationService;
use App\Services\CurriculumResolutionService;
use App\Services\CurriculumExportService;
use App\Services\UnitScopeService;
use App\Models\SchoolUnitModel;
use App\Models\AcademicPeriodModel;
use App\Models\GradeLevelModel;
use App\Models\SubjectModel;
use App\Models\ClassroomModel;
use App\Models\RoomTypeModel;
use Config\Database;

class CurriculumController extends BaseController
{
    public function index()
    {
        if (!has_permission('curriculum.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses untuk melihat kurikulum.');
        }

        $filters = [
            'academic_period_id' => $this->request->getGet('academic_period_id'),
            'workflow_status'    => $this->request->getGet('workflow_status'),
            'search'             => $this->request->getGet('search'),
        ];

        $versions = CurriculumVersionService::getVersions($filters);

        $periodModel = new AcademicPeriodModel();
        $periods = $periodModel->orderBy('id', 'DESC')->findAll();

        return view('curriculum/versions/index', [
            'versions' => $versions['data'],
            'periods'  => $periods,
            'filters'  => $filters,
        ]);
    }

    public function create()
    {
        if (!has_permission('curriculum.manage')) {
            return redirect()->to('/curriculum')->with('error', 'Anda tidak memiliki hak akses untuk membuat kurikulum.');
        }

        $periodModel = new AcademicPeriodModel();
        $periods = $periodModel->orderBy('id', 'DESC')->findAll();

        return view('curriculum/versions/create', [
            'periods' => $periods,
        ]);
    }

    public function store()
    {
        if (!has_permission('curriculum.manage')) {
            return redirect()->to('/curriculum')->with('error', 'Hak akses ditolak.');
        }

        $rules = [
            'academic_period_id' => 'required|numeric',
            'code'               => 'required|min_length[3]|max_length[50]',
            'name'               => 'required|min_length[3]|max_length[150]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $data = [
                'academic_period_id' => $this->request->getPost('academic_period_id'),
                'code'               => $this->request->getPost('code'),
                'name'               => $this->request->getPost('name'),
                'description'        => $this->request->getPost('description'),
                'source_reference'   => $this->request->getPost('source_reference'),
            ];

            $version = CurriculumVersionService::createVersion($data);

            return redirect()->to('/curriculum/' . $version['uuid'])->with('success', 'Versi kurikulum berhasil dibuat.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(string $uuid)
    {
        if (!has_permission('curriculum.view')) {
            return redirect()->to('/dashboard')->with('error', 'Hak akses ditolak.');
        }

        $version = CurriculumVersionService::getVersionByUuid($uuid);
        if (!$version) {
            return redirect()->to('/curriculum')->with('error', 'Versi kurikulum tidak ditemukan.');
        }

        try {
            $unitId = UnitScopeService::resolveUnit($this->request->getGet('unit_id'));
        } catch (\Throwable $e) {
            return redirect()->to('/curriculum')->with('error', $e->getMessage());
        }
        $gradeId = $this->request->getGet('grade_level_id');
        $classroomId = $this->request->getGet('classroom_id');

        $filters = [
            'unit_id'        => $unitId,
            'grade_level_id' => $gradeId,
            'classroom_id'   => $classroomId,
        ];

        $structures = CurriculumStructureService::getStructures($version['id'], $filters);
        $validation = CurriculumValidationService::validateVersion($version['id']);

        $unitModel = new SchoolUnitModel();
        $gradeModel = new GradeLevelModel();
        $subjectModel = new SubjectModel();
        $classroomModel = new ClassroomModel();
        $roomTypeModel = new RoomTypeModel();

        return view('curriculum/versions/show', [
            'version'     => $version,
            'structures'  => $structures['data'],
            'validation'  => $validation,
            'units'       => UnitScopeService::accessibleUnits(),
            'grades'      => $gradeModel->where('unit_id', $unitId)->where('is_active', 1)->findAll(),
            'subjects'    => $subjectModel->select('subjects.*')->join('subject_unit_availability sua', 'sua.subject_id = subjects.id')->where('sua.unit_id', $unitId)->where('sua.is_available', 1)->where('subjects.is_active', 1)->findAll(),
            'classrooms'  => $classroomModel->where('academic_period_id', $version['academic_period_id'])->where('unit_id', $unitId)->findAll(),
            'room_types'  => $roomTypeModel->where('is_active', 1)->findAll(),
            'filters'     => $filters,
        ]);
    }

    public function storeStructure(string $uuid)
    {
        if (!has_permission('curriculum.manage')) {
            return redirect()->to('/curriculum/' . $uuid)->with('error', 'Hak akses ditolak.');
        }

        $version = CurriculumVersionService::getVersionByUuid($uuid);
        if (!$version) {
            return redirect()->to('/curriculum')->with('error', 'Versi kurikulum tidak ditemukan.');
        }

        try {
            $unitId = UnitScopeService::resolveUnit($this->request->getPost('unit_id'));
            $data = [
                'curriculum_version_id'   => $version['id'],
                'unit_id'                 => $unitId,
                'grade_level_id'          => $this->request->getPost('grade_level_id'),
                'classroom_id'            => $this->request->getPost('classroom_id'),
                'subject_id'              => $this->request->getPost('subject_id'),
                'effective_source'        => $this->request->getPost('effective_source'),
                'official_weekly_hours'   => $this->request->getPost('official_weekly_hours'),
                'custom_weekly_hours'     => $this->request->getPost('custom_weekly_hours'),
                'manual_weekly_hours'     => $this->request->getPost('manual_weekly_hours'),
                'category'                => $this->request->getPost('category'),
                'block_pattern_json'      => $this->request->getPost('block_pattern_json'),
                'minimum_days'            => $this->request->getPost('minimum_days'),
                'maximum_daily_hours'     => $this->request->getPost('maximum_daily_hours'),
                'counts_in_report'        => $this->request->getPost('counts_in_report'),
                'counts_as_teaching_load' => $this->request->getPost('counts_as_teaching_load'),
                'required_room_type_id'   => $this->request->getPost('required_room_type_id'),
                'schedule_priority'       => $this->request->getPost('schedule_priority'),
                'adjustment_reason'       => $this->request->getPost('adjustment_reason'),
                'notes'                   => $this->request->getPost('notes'),
            ];

            CurriculumStructureService::createStructure($data);
            return redirect()->to('/curriculum/' . $uuid)->with('success', 'Struktur mata pelajaran berhasil ditambahkan.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function workflowAction(string $uuid, string $action)
    {
        $action = strtoupper(trim($action));

        switch ($action) {
            case 'VALIDATE':
                if (!has_permission('curriculum.validate')) return redirect()->back()->with('error', 'Hak akses ditolak.');
                break;
            case 'REVIEW':
                if (!has_permission('curriculum.review')) return redirect()->back()->with('error', 'Hak akses ditolak.');
                break;
            case 'APPROVE':
                if (!has_permission('curriculum.approve')) return redirect()->back()->with('error', 'Hak akses ditolak.');
                break;
            case 'LOCK':
                if (!has_permission('curriculum.lock')) return redirect()->back()->with('error', 'Hak akses ditolak.');
                break;
            case 'ACTIVATE':
                if (!has_permission('curriculum.approve')) return redirect()->back()->with('error', 'Hak akses ditolak.');
                break;
            default:
                if (!has_permission('curriculum.manage')) return redirect()->back()->with('error', 'Hak akses ditolak.');
        }

        try {
            $this->assertVersionScope($uuid);
            $reason = $this->request->getPost('reason');

            if ($action === 'ACTIVATE') {
                CurriculumWorkflowService::setActiveVersion($uuid);
                return redirect()->to('/curriculum/' . $uuid)->with('success', 'Versi kurikulum telah diaktifkan untuk periode ini.');
            } else {
                CurriculumWorkflowService::transition($uuid, $action, $reason);
                return redirect()->to('/curriculum/' . $uuid)->with('success', "Status versi kurikulum berhasil diubah ke {$action}.");
            }
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function reconciliation(string $uuid)
    {
        if (!has_permission('curriculum.view')) {
            return redirect()->to('/dashboard')->with('error', 'Hak akses ditolak.');
        }

        $version = CurriculumVersionService::getVersionByUuid($uuid);
        if (!$version) {
            return redirect()->to('/curriculum')->with('error', 'Versi kurikulum tidak ditemukan.');
        }

        try {
            $this->assertVersionScope($uuid);
        } catch (\Throwable $e) {
            return redirect()->to('/curriculum')->with('error', $e->getMessage());
        }

        $recon = CurriculumReconciliationService::reconcileVersion($version['id']);

        return view('curriculum/reconciliation/index', [
            'version' => $version,
            'recon'   => $recon,
        ]);
    }

    public function export(string $uuid)
    {
        if (!has_permission('curriculum.export')) {
            return redirect()->to('/curriculum/' . $uuid)->with('error', 'Hak akses ditolak untuk ekspor.');
        }

        try {
            $this->assertVersionScope($uuid);
            $filePath = CurriculumExportService::exportExcel($uuid);
            return $this->response->download($filePath, null)->setFileName(basename($filePath));
        } catch (\Throwable $e) {
            return redirect()->to('/curriculum/' . $uuid)->with('error', $e->getMessage());
        }
    }

    private function assertVersionScope(string $uuid): void
    {
        $version = CurriculumVersionService::getVersionByUuid($uuid);
        if (!$version) {
            throw new \RuntimeException('Versi kurikulum tidak ditemukan.');
        }

        $rows = Database::connect()->table('curriculum_structures')
            ->select('unit_id')
            ->where('curriculum_version_id', $version['id'])
            ->where('deleted_at IS NULL')
            ->groupBy('unit_id')
            ->get()
            ->getResultArray();
        $unitIds = array_map('intval', array_column($rows, 'unit_id'));
        if ($unitIds !== []) {
            UnitScopeService::assertUnits($unitIds);
        }
    }
}
