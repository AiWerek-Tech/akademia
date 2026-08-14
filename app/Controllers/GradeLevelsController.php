<?php

namespace App\Controllers;

use App\Services\GradeLevelService;
use App\Services\UnitScopeService;
use App\Models\SchoolUnitModel;
use App\Models\GradeLevelModel;

class GradeLevelsController extends BaseController
{
    public function index()
    {
        if (!has_permission('grade_levels.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses.');
        }

        try {
            $query = $this->request->getGet();
            $unitId = array_key_exists('unit_id', $query) && $query['unit_id'] === ''
                ? null
                : UnitScopeService::resolveUnit($query['unit_id'] ?? null);
        } catch (\Throwable $e) {
            return redirect()->to('/dashboard')->with('error', $e->getMessage());
        }

        $gradeLevels = GradeLevelService::getGradeLevels($unitId ? (int)$unitId : null, UnitScopeService::accessibleUnitIds());
        $units = UnitScopeService::accessibleUnits();

        return view('grade_levels/index', [
            'title'             => 'Tingkat Kelas & Fase Pendidikan',
            'breadcrumb_active' => 'Tingkat Kelas',
            'gradeLevels'       => $gradeLevels,
            'units'             => $units,
            'selectedUnitId'    => $unitId,
        ]);
    }

    public function create()
    {
        if (!has_permission('grade_levels.manage')) {
            return redirect()->to('/grade-levels')->with('error', 'Anda tidak memiliki hak akses.');
        }
        return view('grade_levels/create', [
            'title'             => 'Tambah Tingkat Kelas',
            'breadcrumb_active' => 'Tambah Tingkat',
            'units'             => UnitScopeService::accessibleUnits(),
            'activeUnitId'      => UnitScopeService::resolveUnit(),
        ]);
    }

    public function store()
    {
        if (!has_permission('grade_levels.manage')) {
            return redirect()->to('/grade-levels')->with('error', 'Anda tidak memiliki hak akses.');
        }
        $rules = [
            'unit_id' => 'required|integer', 'grade_number' => 'required|integer|greater_than[0]',
            'code' => 'required|min_length[1]|max_length[20]', 'name' => 'required|min_length[2]|max_length[50]',
            'phase' => 'required|max_length[10]', 'sort_order' => 'permit_empty|integer|greater_than_equal_to[0]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        try {
            $data = $this->request->getPost();
            $data['unit_id'] = UnitScopeService::resolveUnit($data['unit_id'] ?? null);
            GradeLevelService::createGradeLevel($data);
            return redirect()->to('/grade-levels')->with('success', 'Tingkat kelas berhasil ditambahkan.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(string $uuid)
    {
        if (!has_permission('grade_levels.manage')) {
            return redirect()->to('/grade-levels')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $model = new GradeLevelModel();
        $gradeLevel = $model->where('uuid', $uuid)->first();

        if (!$gradeLevel) {
            return redirect()->to('/grade-levels')->with('error', 'Tingkat kelas tidak ditemukan.');
        }
        try {
            UnitScopeService::assertUnit((int) $gradeLevel['unit_id']);
        } catch (\Throwable $e) {
            return redirect()->to('/grade-levels')->with('error', $e->getMessage());
        }

        return view('grade_levels/edit', [
            'title'             => 'Edit Tingkat Kelas - ' . $gradeLevel['name'],
            'breadcrumb_active' => 'Edit Tingkat',
            'gradeLevel'        => $gradeLevel,
        ]);
    }

    public function update(string $uuid)
    {
        if (!has_permission('grade_levels.manage')) {
            return redirect()->to('/grade-levels')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $rules = [
            'name'  => 'required|min_length[2]|max_length[50]',
            'phase' => 'required|max_length[10]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $gradeLevel = (new GradeLevelModel())->where('uuid', $uuid)->first();
            if (!$gradeLevel) {
                throw new \RuntimeException('Tingkat kelas tidak ditemukan.');
            }
            UnitScopeService::assertUnit((int) $gradeLevel['unit_id']);
            GradeLevelService::updateGradeLevel($uuid, $this->request->getPost());
            return redirect()->to('/grade-levels')->with('success', 'Tingkat kelas berhasil diperbarui.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }
}
