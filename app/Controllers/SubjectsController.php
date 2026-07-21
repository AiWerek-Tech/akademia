<?php

namespace App\Controllers;

use App\Services\SubjectService;
use App\Services\MasterExportService;
use App\Models\SchoolUnitModel;
use App\Models\SubjectModel;
use App\Models\SubjectAliasModel;
use App\Models\SubjectUnitAvailabilityModel;

class SubjectsController extends BaseController
{
    public function index()
    {
        if (!has_permission('subjects.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $activeUnitId = session()->get('active_unit_id');
        $filters = [
            'unit_id'   => $this->request->getGet('unit_id') ?? $activeUnitId,
            'category'  => $this->request->getGet('category'),
            'is_active' => $this->request->getGet('is_active'),
            'search'    => $this->request->getGet('search'),
        ];

        $result = SubjectService::getSubjects($filters);
        $unitModel = new SchoolUnitModel();
        $units = $unitModel->where('is_active', 1)->findAll();

        return view('subjects/index', [
            'title'             => 'Master Mata Pelajaran Global',
            'breadcrumb_active' => 'Mata Pelajaran',
            'subjects'          => $result['data'],
            'pager'             => $result['pager'],
            'units'             => $units,
            'categories'        => SubjectService::CATEGORIES,
            'filters'           => $filters,
        ]);
    }

    public function create()
    {
        if (!has_permission('subjects.manage')) {
            return redirect()->to('/subjects')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $unitModel = new SchoolUnitModel();
        $units = $unitModel->where('is_active', 1)->findAll();

        return view('subjects/create', [
            'title'             => 'Tambah Mata Pelajaran',
            'breadcrumb_active' => 'Tambah Mapel',
            'units'             => $units,
            'categories'        => SubjectService::CATEGORIES,
        ]);
    }

    public function store()
    {
        if (!has_permission('subjects.manage')) {
            return redirect()->to('/subjects')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $rules = [
            'code'       => 'required|min_length[2]|max_length[30]',
            'name'       => 'required|min_length[3]|max_length[150]',
            'short_name' => 'required|max_length[50]',
            'category'   => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $data = $this->request->getPost();
            $unitIds = (array)($this->request->getPost('unit_ids') ?? []);
            $aliases = array_filter(explode(',', (string)$this->request->getPost('aliases')));

            SubjectService::createSubject($data, $unitIds, $aliases);

            return redirect()->to('/subjects')->with('success', 'Mata pelajaran berhasil ditambahkan.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(string $uuid)
    {
        if (!has_permission('subjects.manage')) {
            return redirect()->to('/subjects')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $subjectModel = new SubjectModel();
        $subject = $subjectModel->where('uuid', $uuid)->where('deleted_at IS NULL')->first();

        if (!$subject) {
            return redirect()->to('/subjects')->with('error', 'Data mata pelajaran tidak ditemukan.');
        }

        $unitModel = new SchoolUnitModel();
        $units = $unitModel->where('is_active', 1)->findAll();

        $availModel = new SubjectUnitAvailabilityModel();
        $assignedUnitIds = array_column($availModel->where('subject_id', $subject['id'])->findAll(), 'unit_id');

        $aliasModel = new SubjectAliasModel();
        $aliases = array_column($aliasModel->where('subject_id', $subject['id'])->findAll(), 'alias_name');

        return view('subjects/edit', [
            'title'             => 'Edit Mata Pelajaran - ' . $subject['name'],
            'breadcrumb_active' => 'Edit Mapel',
            'subject'           => $subject,
            'units'             => $units,
            'categories'        => SubjectService::CATEGORIES,
            'assignedUnitIds'   => $assignedUnitIds,
            'aliasesStr'        => implode(', ', $aliases),
        ]);
    }

    public function update(string $uuid)
    {
        if (!has_permission('subjects.manage')) {
            return redirect()->to('/subjects')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $rules = [
            'code'            => 'required|min_length[2]|max_length[30]',
            'name'            => 'required|min_length[3]|max_length[150]',
            'category'        => 'required',
            'revision_number' => 'required|numeric',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $data = $this->request->getPost();
            $unitIds = (array)($this->request->getPost('unit_ids') ?? []);
            $aliases = array_filter(explode(',', (string)$this->request->getPost('aliases')));

            SubjectService::updateSubject($uuid, $data, $unitIds, $aliases);

            return redirect()->to('/subjects')->with('success', 'Mata pelajaran berhasil diperbarui.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function export()
    {
        if (!has_permission('subjects.export')) {
            return redirect()->to('/subjects')->with('error', 'Anda tidak memiliki hak akses export.');
        }

        try {
            $filePath = MasterExportService::exportExcel('SUBJECTS', [
                'unit_id' => $this->request->getGet('unit_id') ?? session()->get('active_unit_id'),
            ]);

            return $this->response->download($filePath, null);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
