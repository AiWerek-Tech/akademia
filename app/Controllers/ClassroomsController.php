<?php

namespace App\Controllers;

use App\Services\ClassroomService;
use App\Services\GradeLevelService;
use App\Services\RoomService;
use App\Services\TeacherService;
use App\Services\MasterExportService;
use App\Models\SchoolUnitModel;
use App\Models\AcademicPeriodModel;
use App\Models\ClassroomModel;

class ClassroomsController extends BaseController
{
    public function index()
    {
        if (!has_permission('classrooms.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $activeUnitId   = session()->get('active_unit_id');
        $activePeriodId = session()->get('active_period_id');

        $filters = [
            'unit_id'            => $this->request->getGet('unit_id') ?? $activeUnitId,
            'academic_period_id' => $this->request->getGet('academic_period_id') ?? $activePeriodId,
            'grade_level_id'     => $this->request->getGet('grade_level_id'),
            'is_active'          => $this->request->getGet('is_active'),
            'search'             => $this->request->getGet('search'),
        ];

        $result = ClassroomService::getClassrooms($filters);

        $unitModel = new SchoolUnitModel();
        $units = $unitModel->where('is_active', 1)->findAll();

        $periodModel = new AcademicPeriodModel();
        $periods = $periodModel->orderBy('id', 'DESC')->findAll();

        $gradeLevels = GradeLevelService::getGradeLevels($filters['unit_id'] ? (int)$filters['unit_id'] : null);

        return view('classrooms/index', [
            'title'             => 'Master Kelas / Rombel',
            'breadcrumb_active' => 'Kelas & Rombel',
            'classrooms'        => $result['data'],
            'pager'             => $result['pager'],
            'units'             => $units,
            'periods'           => $periods,
            'gradeLevels'       => $gradeLevels,
            'filters'           => $filters,
        ]);
    }

    public function create()
    {
        if (!has_permission('classrooms.manage')) {
            return redirect()->to('/classrooms')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $activeUnitId   = session()->get('active_unit_id');
        $activePeriodId = session()->get('active_period_id');

        $unitModel = new SchoolUnitModel();
        $units = $unitModel->where('is_active', 1)->findAll();

        $periodModel = new AcademicPeriodModel();
        $periods = $periodModel->orderBy('id', 'DESC')->findAll();

        $gradeLevels = GradeLevelService::getGradeLevels($activeUnitId ? (int)$activeUnitId : null);
        $rooms       = RoomService::getRooms(['unit_id' => $activeUnitId], 1000)['data'];
        $teachers    = TeacherService::getTeachers(['unit_id' => $activeUnitId, 'is_active' => 1], 1000)['data'];

        return view('classrooms/create', [
            'title'             => 'Tambah Kelas / Rombel Baru',
            'breadcrumb_active' => 'Tambah Rombel',
            'units'             => $units,
            'periods'           => $periods,
            'gradeLevels'       => $gradeLevels,
            'rooms'             => $rooms,
            'teachers'          => $teachers,
            'activeUnitId'      => $activeUnitId,
            'activePeriodId'    => $activePeriodId,
        ]);
    }

    public function store()
    {
        if (!has_permission('classrooms.manage')) {
            return redirect()->to('/classrooms')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $rules = [
            'academic_period_id' => 'required|numeric',
            'unit_id'            => 'required|numeric',
            'grade_level_id'     => 'required|numeric',
            'code'               => 'required|min_length[2]|max_length[30]',
            'name'               => 'required|min_length[3]|max_length[100]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            ClassroomService::createClassroom($this->request->getPost());
            return redirect()->to('/classrooms')->with('success', 'Kelas/Rombel berhasil dibuat.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(string $uuid)
    {
        if (!has_permission('classrooms.manage')) {
            return redirect()->to('/classrooms')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $model = new ClassroomModel();
        $classroom = $model->where('uuid', $uuid)->where('deleted_at IS NULL')->first();

        if (!$classroom) {
            return redirect()->to('/classrooms')->with('error', 'Kelas/Rombel tidak ditemukan.');
        }

        $unitModel = new SchoolUnitModel();
        $units = $unitModel->where('is_active', 1)->findAll();

        $periodModel = new AcademicPeriodModel();
        $periods = $periodModel->orderBy('id', 'DESC')->findAll();

        $gradeLevels = GradeLevelService::getGradeLevels((int)$classroom['unit_id']);
        $rooms       = RoomService::getRooms(['unit_id' => $classroom['unit_id']], 1000)['data'];
        $teachers    = TeacherService::getTeachers(['unit_id' => $classroom['unit_id'], 'is_active' => 1], 1000)['data'];

        return view('classrooms/edit', [
            'title'             => 'Edit Kelas / Rombel - ' . $classroom['name'],
            'breadcrumb_active' => 'Edit Rombel',
            'classroom'         => $classroom,
            'units'             => $units,
            'periods'           => $periods,
            'gradeLevels'       => $gradeLevels,
            'rooms'             => $rooms,
            'teachers'          => $teachers,
        ]);
    }

    public function update(string $uuid)
    {
        if (!has_permission('classrooms.manage')) {
            return redirect()->to('/classrooms')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $rules = [
            'code'            => 'required|min_length[2]|max_length[30]',
            'name'            => 'required|min_length[3]|max_length[100]',
            'revision_number' => 'required|numeric',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            ClassroomService::updateClassroom($uuid, $this->request->getPost());
            return redirect()->to('/classrooms')->with('success', 'Kelas/Rombel berhasil diperbarui.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function copyPeriodView()
    {
        if (!has_permission('classrooms.manage')) {
            return redirect()->to('/classrooms')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $activeUnitId   = session()->get('active_unit_id');
        $activePeriodId = session()->get('active_period_id');

        $unitModel = new SchoolUnitModel();
        $units = $unitModel->where('is_active', 1)->findAll();

        $periodModel = new AcademicPeriodModel();
        $periods = $periodModel->orderBy('id', 'DESC')->findAll();

        $sourcePeriodId = (int)($this->request->getGet('source_period_id') ?? 0);
        $targetPeriodId = (int)($this->request->getGet('target_period_id') ?? $activePeriodId);
        $unitId         = (int)($this->request->getGet('unit_id') ?? $activeUnitId);

        $preview = [];
        if ($sourcePeriodId > 0 && $targetPeriodId > 0 && $unitId > 0) {
            $preview = ClassroomService::previewCopyPeriod($sourcePeriodId, $targetPeriodId, $unitId);
        }

        return view('classrooms/copy_period', [
            'title'             => 'Copy Rombel Antar Periode',
            'breadcrumb_active' => 'Copy Period Rombel',
            'units'             => $units,
            'periods'           => $periods,
            'sourcePeriodId'    => $sourcePeriodId,
            'targetPeriodId'    => $targetPeriodId,
            'unitId'            => $unitId,
            'preview'           => $preview,
        ]);
    }

    public function applyCopyPeriod()
    {
        if (!has_permission('classrooms.manage')) {
            return redirect()->to('/classrooms')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $sourcePeriodId = (int)$this->request->getPost('source_period_id');
        $targetPeriodId = (int)$this->request->getPost('target_period_id');
        $unitId         = (int)$this->request->getPost('unit_id');
        $copyHomeroom   = (bool)$this->request->getPost('copy_homeroom');

        if ($sourcePeriodId === $targetPeriodId) {
            return redirect()->back()->with('error', 'Periode sumber dan periode target tidak boleh sama.');
        }

        try {
            $res = ClassroomService::applyCopyPeriod($sourcePeriodId, $targetPeriodId, $unitId, $copyHomeroom);
            return redirect()->to('/classrooms?academic_period_id=' . $targetPeriodId . '&unit_id=' . $unitId)
                ->with('success', 'Copy rombel berhasil: ' . $res['copied_count'] . ' kelas dibuat, ' . $res['skipped_count'] . ' kelas dilewati (sudah ada).');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function export()
    {
        if (!has_permission('classrooms.export')) {
            return redirect()->to('/classrooms')->with('error', 'Anda tidak memiliki hak akses export.');
        }

        try {
            $filePath = MasterExportService::exportExcel('CLASSROOMS', [
                'unit_id'            => $this->request->getGet('unit_id') ?? session()->get('active_unit_id'),
                'academic_period_id' => $this->request->getGet('academic_period_id') ?? session()->get('active_period_id'),
            ]);

            return $this->response->download($filePath, null);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
