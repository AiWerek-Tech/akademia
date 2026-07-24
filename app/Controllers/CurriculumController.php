<?php

namespace App\Controllers;

use App\Services\CurriculumVersionService;
use App\Services\CurriculumStructureService;
use App\Services\CurriculumValidationService;
use App\Services\CurriculumWorkflowService;
use App\Services\CurriculumReconciliationService;
use App\Services\CurriculumResolutionService;
use App\Services\CurriculumExportService;
use App\Services\CurriculumPlanningService;
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
            'is_active'          => $this->request->getGet('is_active'),
            'search'             => $this->request->getGet('search'),
        ];

        $versions = CurriculumVersionService::getVersions($filters);

        $periods = Database::connect()->table('academic_periods ap')
            ->select('ap.*, ay.name AS year_name')
            ->join('academic_years ay', 'ay.id = ap.academic_year_id', 'left')
            ->orderBy('ap.start_date', 'DESC')->get()->getResultArray();

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

        $periods = Database::connect()->table('academic_periods ap')
            ->select('ap.*, ay.name AS year_name')
            ->join('academic_years ay', 'ay.id = ap.academic_year_id', 'left')
            ->where('ap.is_active', 1)
            ->orderBy('ap.start_date', 'DESC')->get()->getResultArray();

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
        $planning = CurriculumPlanningService::buildOverview(
            (int) $version['id'],
            (int) $version['academic_period_id'],
            (int) $unitId
        );

        $unitModel = new SchoolUnitModel();
        $gradeModel = new GradeLevelModel();
        $subjectModel = new SubjectModel();
        $classroomModel = new ClassroomModel();
        $roomTypeModel = new RoomTypeModel();

        return view('curriculum/versions/show', [
            'version'     => $version,
            'structures'  => $structures['data'],
            'validation'  => $validation,
            'planning'    => $planning,
            'units'       => UnitScopeService::accessibleUnits(),
            'grades'      => $gradeModel->where('unit_id', $unitId)->where('is_active', 1)->findAll(),
            'subjects'    => $subjectModel->select('subjects.*')->join('subject_unit_availability sua', 'sua.subject_id = subjects.id')->where('sua.unit_id', $unitId)->where('sua.is_available', 1)->where('subjects.is_active', 1)->findAll(),
            'classrooms'  => $classroomModel->where('academic_period_id', $version['academic_period_id'])->where('unit_id', $unitId)->findAll(),
            'room_types'  => $roomTypeModel->where('is_active', 1)->findAll(),
            'filters'     => $filters,
        ]);
    }

    public function savePlanningSettings(string $uuid)
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
            CurriculumPlanningService::saveSettings((int) $version['id'], (int) $unitId, [
                'teaching_days_per_week' => $this->request->getPost('teaching_days_per_week'),
                'selected_day_codes'     => $this->request->getPost('selected_day_codes'),
                'daily_jp_capacity'      => $this->request->getPost('daily_jp_capacity'),
                'workload_policy_id'     => $this->request->getPost('workload_policy_id'),
                'allow_custom_hours'     => $this->request->getPost('allow_custom_hours'),
                'revision_number'        => $this->request->getPost('revision_number'),
                'notes'                  => $this->request->getPost('notes'),
            ]);

            return redirect()->to('/curriculum/' . $uuid . '?unit_id=' . $unitId)
                ->with('success', 'Parameter perencanaan berhasil disimpan dan seluruh indikator telah dihitung ulang.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
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

            $weeklyHours = $this->request->getPost('weekly_hours');
            $source = strtoupper((string) $data['effective_source']);
            $data['official_weekly_hours'] = $source === 'OFFICIAL' ? $weeklyHours : null;
            $data['custom_weekly_hours'] = $source === 'CUSTOM' ? $weeklyHours : null;
            $data['manual_weekly_hours'] = $source === 'MANUAL' ? $weeklyHours : null;

            CurriculumStructureService::createStructure($data);
            return redirect()->to('/curriculum/' . $uuid)->with('success', 'Struktur mata pelajaran berhasil ditambahkan.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function activate(string $uuid)
    {
        if (!has_permission('curriculum.manage') && !has_permission('curriculum.approve')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk mengaktifkan kurikulum.');
        }

        try {
            $this->assertVersionScope($uuid);
            CurriculumWorkflowService::setActiveVersion($uuid);
            return redirect()->to('/curriculum/' . $uuid)->with('success', 'Kurikulum berhasil diaktifkan. Versi aktif sebelumnya pada periode ini otomatis dinonaktifkan.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function deleteStructure(string $versionUuid, string $structureUuid)
    {
        if (!has_permission('curriculum.manage')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus struktur kurikulum.');
        }

        try {
            $this->assertVersionScope($versionUuid);
            $version = CurriculumVersionService::getVersionByUuid($versionUuid);
            $structure = Database::connect()->table('curriculum_structures')
                ->where('uuid', $structureUuid)->where('deleted_at IS NULL')->get()->getRowArray();
            if (!$version || !$structure || (int) $structure['curriculum_version_id'] !== (int) $version['id']) {
                throw new \RuntimeException('Struktur kurikulum tidak ditemukan pada versi ini.');
            }
            UnitScopeService::assertUnit((int) $structure['unit_id']);
            CurriculumStructureService::deleteStructure($structureUuid, 'Dihapus melalui halaman struktur kurikulum');
            return redirect()->back()->with('success', 'Mata pelajaran berhasil dihapus dari struktur kurikulum.');
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
