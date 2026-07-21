<?php

namespace App\Controllers;

use App\Services\TeacherService;
use App\Services\TeacherDuplicateDetectionService;
use App\Services\TeacherMergeService;
use App\Services\MasterExportService;
use App\Services\UnitScopeService;
use App\Models\SchoolUnitModel;
use App\Models\TeacherModel;
use App\Models\TeacherUnitAssignmentModel;
use App\Models\TeacherQualificationModel;

class TeachersController extends BaseController
{
    public function index()
    {
        if (!has_permission('teachers.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses.');
        }

        try {
            $unitId = UnitScopeService::resolveUnit($this->request->getGet('unit_id'));
        } catch (\Throwable $e) {
            return redirect()->to('/dashboard')->with('error', $e->getMessage());
        }

        $filters = [
            'unit_id'        => $unitId,
            'is_active'      => $this->request->getGet('is_active'),
            'profile_status' => $this->request->getGet('profile_status'),
            'search'         => $this->request->getGet('search'),
        ];

        $result = TeacherService::getTeachers($filters);
        $units = UnitScopeService::accessibleUnits();

        return view('teachers/index', [
            'title'             => 'Master Guru Global',
            'breadcrumb_active' => 'Master Guru',
            'teachers'          => $result['data'],
            'pager'             => $result['pager'],
            'units'             => $units,
            'filters'           => $filters,
        ]);
    }

    public function create()
    {
        if (!has_permission('teachers.manage')) {
            return redirect()->to('/teachers')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $units = UnitScopeService::accessibleUnits();

        return view('teachers/create', [
            'title'             => 'Tambah Data Guru',
            'breadcrumb_active' => 'Tambah Guru',
            'units'             => $units,
        ]);
    }

    public function store()
    {
        if (!has_permission('teachers.manage')) {
            return redirect()->to('/teachers')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $rules = [
            'full_name'         => 'required|min_length[3]|max_length[150]',
            'employment_status' => 'required|max_length[50]',
            'primary_unit_id'   => 'required|numeric',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $data = $this->request->getPost();
            $unitIds = (array)($this->request->getPost('additional_units') ?? []);
            if (!empty($data['primary_unit_id'])) {
                $unitIds[] = (int)$data['primary_unit_id'];
            }
            $unitIds = array_values(array_unique(array_filter($unitIds)));
            $unitIds = UnitScopeService::assertUnits($unitIds);

            $res = TeacherService::createTeacher($data, $unitIds);

            if (!empty($res['duplicate_matches'])) {
                return redirect()->to('/teachers/' . $res['teacher']['uuid'])
                    ->with('warning', 'Data guru berhasil disimpan. Terdeteksi calon duplikat yang membutuhkan peninjauan.');
            }

            return redirect()->to('/teachers')->with('success', 'Data guru berhasil ditambahkan.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(string $uuid)
    {
        if (!has_permission('teachers.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $teacherModel = new TeacherModel();
        $teacher = $teacherModel->where('uuid', $uuid)->where('deleted_at IS NULL')->first();

        if (!$teacher) {
            return redirect()->to('/teachers')->with('error', 'Data guru tidak ditemukan.');
        }
        try {
            UnitScopeService::assertTeacher((int) $teacher['id']);
        } catch (\Throwable $e) {
            return redirect()->to('/teachers')->with('error', $e->getMessage());
        }

        $assignmentModel = new TeacherUnitAssignmentModel();
        $assignments = $assignmentModel->select('teacher_unit_assignments.*, school_units.name as unit_name, school_units.code as unit_code')
            ->join('school_units', 'school_units.id = teacher_unit_assignments.unit_id')
            ->where('teacher_id', $teacher['id'])
            ->findAll();

        $qualModel = new TeacherQualificationModel();
        $qualifications = $qualModel->where('teacher_id', $teacher['id'])->findAll();

        $completeness = \App\Services\TeacherProfileCompletenessService::evaluate($teacher, $assignments, $qualifications);

        return view('teachers/show', [
            'title'             => 'Detail Profil Guru - ' . $teacher['full_name'],
            'breadcrumb_active' => 'Detail Guru',
            'teacher'           => $teacher,
            'assignments'       => $assignments,
            'qualifications'    => $qualifications,
            'completeness'      => $completeness,
        ]);
    }

    public function edit(string $uuid)
    {
        if (!has_permission('teachers.manage')) {
            return redirect()->to('/teachers')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $teacherModel = new TeacherModel();
        $teacher = $teacherModel->where('uuid', $uuid)->where('deleted_at IS NULL')->first();

        if (!$teacher) {
            return redirect()->to('/teachers')->with('error', 'Data guru tidak ditemukan.');
        }
        try {
            UnitScopeService::assertTeacherManage((int) $teacher['id']);
        } catch (\Throwable $e) {
            return redirect()->to('/teachers')->with('error', $e->getMessage());
        }

        $units = UnitScopeService::accessibleUnits();

        $assignmentModel = new TeacherUnitAssignmentModel();
        $assignedUnitIds = array_column($assignmentModel->where('teacher_id', $teacher['id'])->findAll(), 'unit_id');

        return view('teachers/edit', [
            'title'             => 'Edit Data Guru - ' . $teacher['full_name'],
            'breadcrumb_active' => 'Edit Guru',
            'teacher'           => $teacher,
            'units'             => $units,
            'assignedUnitIds'   => $assignedUnitIds,
        ]);
    }

    public function update(string $uuid)
    {
        if (!has_permission('teachers.manage')) {
            return redirect()->to('/teachers')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $rules = [
            'full_name'         => 'required|min_length[3]|max_length[150]',
            'employment_status' => 'required|max_length[50]',
            'revision_number'   => 'required|numeric',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $data = $this->request->getPost();
            $unitIds = (array)($this->request->getPost('additional_units') ?? []);
            if (!empty($data['primary_unit_id'])) {
                $unitIds[] = (int)$data['primary_unit_id'];
            }
            $unitIds = array_values(array_unique(array_filter($unitIds)));
            $unitIds = UnitScopeService::assertUnits($unitIds);

            $teacher = (new TeacherModel())->where('uuid', $uuid)->where('deleted_at IS NULL')->first();
            if (!$teacher) {
                throw new \RuntimeException('Data guru tidak ditemukan.');
            }
            UnitScopeService::assertTeacherManage((int) $teacher['id']);

            TeacherService::updateTeacher($uuid, $data, $unitIds);

            return redirect()->to('/teachers/' . $uuid)->with('success', 'Data guru berhasil diperbarui.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function verify(string $uuid)
    {
        if (!has_permission('teachers.verify')) {
            return redirect()->to('/teachers')->with('error', 'Anda tidak memiliki hak akses verifikasi.');
        }

        try {
            $teacher = (new TeacherModel())->where('uuid', $uuid)->where('deleted_at IS NULL')->first();
            if (!$teacher) {
                throw new \RuntimeException('Data guru tidak ditemukan.');
            }
            UnitScopeService::assertTeacherManage((int) $teacher['id']);
            TeacherService::verifyTeacher($uuid);
            return redirect()->back()->with('success', 'Profil guru berhasil diverifikasi.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function export()
    {
        if (!has_permission('teachers.export')) {
            return redirect()->to('/teachers')->with('error', 'Anda tidak memiliki hak akses export.');
        }

        try {
            $unitId = UnitScopeService::resolveUnit($this->request->getGet('unit_id'));
            $filePath = MasterExportService::exportExcel('TEACHERS', [
                'unit_id' => $unitId,
            ]);

            return $this->response->download($filePath, null);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
