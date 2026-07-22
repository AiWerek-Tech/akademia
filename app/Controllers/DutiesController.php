<?php

namespace App\Controllers;

use App\Models\TeacherAdditionalDutyModel;
use App\Models\AdditionalDutyTypeModel;
use App\Models\TeacherModel;
use App\Models\SchoolUnitModel;
use App\Models\AssignmentVersionModel;
use App\Services\UnitScopeService;
use App\Services\UuidService;

class DutiesController extends BaseController
{
    public function index()
    {
        if (!has_permission('duties.view')) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        $dutyModel = new TeacherAdditionalDutyModel();
        $allowedUnitIds = UnitScopeService::accessibleUnitIds();
        $dutyModel->select('teacher_additional_duties.*, teachers.full_name, additional_duty_types.name as duty_name, school_units.name as unit_name, assignment_versions.code as version_code')
                            ->join('teachers', 'teachers.id = teacher_additional_duties.teacher_id')
                            ->join('additional_duty_types', 'additional_duty_types.id = teacher_additional_duties.duty_type_id')
                            ->join('assignment_versions', 'assignment_versions.id = teacher_additional_duties.assignment_version_id')
                            ->join('school_units', 'school_units.id = teacher_additional_duties.unit_id', 'left')
                            ->where('teacher_additional_duties.status', 'ACTIVE')
                            ->groupStart()
                                ->where('teacher_additional_duties.unit_id IS NULL');
        if ($allowedUnitIds !== []) {
            $dutyModel->orWhereIn('teacher_additional_duties.unit_id', $allowedUnitIds);
        }
        $duties = $dutyModel->groupEnd()->orderBy('teacher_additional_duties.id', 'DESC')->findAll();

        return view('duties/index', [
            'duties' => $duties
        ]);
    }

    public function create()
    {
        if (!has_permission('duties.manage')) {
            return redirect()->to('/duties')->with('error', 'Akses ditolak.');
        }

        $teacherModel = new TeacherModel();
        $typeModel = new AdditionalDutyTypeModel();
        $unitModel = new SchoolUnitModel();
        $versionModel = new AssignmentVersionModel();

        $allowedUnitIds = UnitScopeService::accessibleUnitIds();
        $teachers = $allowedUnitIds === [] ? [] : $teacherModel->select('teachers.*')
            ->join('teacher_unit_assignments tua', 'tua.teacher_id = teachers.id')
            ->whereIn('tua.unit_id', $allowedUnitIds)
            ->where('tua.status', 'ACTIVE')
            ->where('teachers.is_active', 1)
            ->groupBy('teachers.id')->findAll();
        $types = $typeModel->where('is_active', 1)->findAll();
        $units = UnitScopeService::accessibleUnits();
        $versions = $versionModel->whereIn('workflow_status', ['DRAFT', 'VALIDATED', 'REVIEWED'])->findAll();

        return view('duties/create', [
            'teachers' => $teachers,
            'types'    => $types,
            'units'    => $units,
            'versions' => $versions
        ]);
    }

    public function store()
    {
        if (!has_permission('duties.manage')) {
            return redirect()->to('/duties')->with('error', 'Akses ditolak.');
        }

        $rules = [
            'assignment_version_id' => 'required|numeric',
            'teacher_id'            => 'required|numeric',
            'duty_type_id'          => 'required|numeric',
            'workload_hours'        => 'required|numeric|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $versionModel = new AssignmentVersionModel();
        $version = $versionModel->find($this->request->getPost('assignment_version_id'));
        if (!$version) {
            return redirect()->back()->withInput()->with('error', 'Versi penugasan target tidak ditemukan.');
        }
        if (in_array($version['workflow_status'], ['APPROVED', 'LOCKED', 'ARCHIVED'], true)) {
            return redirect()->back()->withInput()->with('error', 'Versi penugasan terkunci tidak dapat diubah.');
        }

        $dutyModel = new TeacherAdditionalDutyModel();

        $uuid = UuidService::v4();
        $data = [
            'uuid'                  => $uuid,
            'assignment_version_id' => $version['id'],
            'academic_period_id'    => $version['academic_period_id'],
            'unit_id'               => $this->request->getPost('unit_id') ?: null,
            'teacher_id'            => $this->request->getPost('teacher_id'),
            'duty_type_id'          => $this->request->getPost('duty_type_id'),
            'title_override'        => $this->request->getPost('title_override') ?: null,
            'workload_hours'        => $this->request->getPost('workload_hours'),
            'reference_number'      => $this->request->getPost('reference_number') ?: null,
            'notes'                 => $this->request->getPost('notes') ?: null,
            'status'                => 'ACTIVE',
            'created_by'            => session()->get('user_id'),
        ];

        try {
            $unitId = $this->request->getPost('unit_id')
                ? UnitScopeService::resolveUnit($this->request->getPost('unit_id'))
                : UnitScopeService::resolveUnit();
            UnitScopeService::assertTeacherInUnit(
                (int) $this->request->getPost('teacher_id'),
                $unitId,
                (int) $version['academic_period_id']
            );
            $data['unit_id'] = $unitId;
            $dutyModel->insert($data);
            return redirect()->to('/duties')->with('success', 'Tugas tambahan guru berhasil disimpan.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }
}
