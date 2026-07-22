<?php

namespace App\Controllers;

use App\Services\ClassroomService;
use App\Services\GradeLevelService;
use App\Services\RoomService;
use App\Services\TeacherService;
use App\Services\MasterExportService;
use App\Services\UnitScopeService;
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

        $activePeriodId = session()->get('active_period_id');

        try {
            $query = $this->request->getGet();
            $unitId = array_key_exists('unit_id', $query) && $query['unit_id'] === ''
                ? null
                : UnitScopeService::resolveUnit($query['unit_id'] ?? null);
        } catch (\Throwable $e) {
            return redirect()->to('/dashboard')->with('error', $e->getMessage());
        }

        $filters = [
            'unit_id'            => $unitId,
            'unit_ids'           => UnitScopeService::accessibleUnitIds(),
            'academic_period_id' => $this->request->getGet('academic_period_id') ?? $activePeriodId,
            'grade_level_id'     => $this->request->getGet('grade_level_id'),
            'is_active'          => $this->request->getGet('is_active'),
            'search'             => $this->request->getGet('search'),
        ];

        $result = ClassroomService::getClassrooms($filters);

        $units = UnitScopeService::accessibleUnits();

        $periodModel = new AcademicPeriodModel();
        $periods = $periodModel->orderBy('id', 'DESC')->findAll();

        $gradeLevels = GradeLevelService::getGradeLevels($filters['unit_id'] ? (int)$filters['unit_id'] : null, $filters['unit_ids']);

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

        $activeUnitId   = UnitScopeService::resolveUnit();
        $activePeriodId = session()->get('active_period_id');

        $units = UnitScopeService::accessibleUnits();

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
            'capacity'           => 'permit_empty|integer|greater_than_equal_to[0]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $data = $this->request->getPost();
            $data['unit_id'] = UnitScopeService::resolveUnit($data['unit_id'] ?? null);
            ClassroomService::createClassroom($data);
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
        try {
            UnitScopeService::assertClassroom((int) $classroom['id']);
        } catch (\Throwable $e) {
            return redirect()->to('/classrooms')->with('error', $e->getMessage());
        }

        $units = UnitScopeService::accessibleUnits();

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
            'capacity'        => 'permit_empty|integer|greater_than_equal_to[0]',
            'revision_number' => 'required|numeric',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $classroom = (new ClassroomModel())->where('uuid', $uuid)->where('deleted_at IS NULL')->first();
            if (!$classroom) {
                throw new \RuntimeException('Kelas/Rombel tidak ditemukan.');
            }
            UnitScopeService::assertClassroom((int) $classroom['id']);
            $data = $this->request->getPost();
            if (isset($data['unit_id'])) {
                $data['unit_id'] = UnitScopeService::resolveUnit($data['unit_id']);
            }
            ClassroomService::updateClassroom($uuid, $data);
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

        $activeUnitId   = UnitScopeService::resolveUnit();
        $activePeriodId = session()->get('active_period_id');

        $units = UnitScopeService::accessibleUnits();

        $periodModel = new AcademicPeriodModel();
        $periods = $periodModel->orderBy('id', 'DESC')->findAll();

        $sourcePeriodId = (int)($this->request->getGet('source_period_id') ?? 0);
        $targetPeriodId = (int)($this->request->getGet('target_period_id') ?? $activePeriodId);
        try {
            $unitId = UnitScopeService::resolveUnit($this->request->getGet('unit_id') ?? $activeUnitId);
        } catch (\Throwable $e) {
            return redirect()->to('/classrooms')->with('error', $e->getMessage());
        }

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
        try {
            $unitId = UnitScopeService::resolveUnit($this->request->getPost('unit_id'));
        } catch (\Throwable $e) {
            return redirect()->to('/classrooms/copy-period')->with('error', $e->getMessage());
        }
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
            $unitId = UnitScopeService::resolveUnit($this->request->getGet('unit_id'));
            $filePath = MasterExportService::exportExcel('CLASSROOMS', [
                'unit_id'            => $unitId,
                'academic_period_id' => $this->request->getGet('academic_period_id') ?? session()->get('active_period_id'),
            ]);

            return $this->response->download($filePath, null);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
