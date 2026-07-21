<?php

namespace App\Controllers;

use App\Models\AcademicYearModel;
use App\Services\AuditService;
use Config\Database;

class AcademicYearController extends BaseController
{
    public function index()
    {
        if (!has_permission('academic_years.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $yearModel = new AcademicYearModel();
        $years = $yearModel->orderBy('name', 'DESC')->findAll();

        return view('academic_years/index', [
            'title'             => 'Daftar Tahun Pelajaran',
            'breadcrumb_active' => 'Tahun Pelajaran',
            'years'             => $years
        ]);
    }

    public function create()
    {
        if (!has_permission('academic_years.manage')) {
            return redirect()->to('/academic-periods')->with('error', 'Anda tidak memiliki hak akses.');
        }

        return view('academic_years/create', [
            'title'             => 'Tambah Tahun Pelajaran',
            'breadcrumb_active' => 'Tambah Tahun Pelajaran'
        ]);
    }

    public function store()
    {
        if (!has_permission('academic_years.manage')) {
            return redirect()->to('/academic-periods')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $rules = [
            'name'       => 'required|regex_match[/^\d{4}\/\d{4}$/]|is_unique[academic_years.name]',
            'start_date' => 'required|valid_date',
            'end_date'   => 'required|valid_date',
        ];

        $messages = [
            'name' => [
                'regex_match' => 'Format nama tahun pelajaran harus YYYY/YYYY (contoh: 2026/2027).',
                'is_unique'   => 'Tahun pelajaran dengan nama tersebut sudah terdaftar.'
            ]
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $startDate = $this->request->getPost('start_date');
        $endDate = $this->request->getPost('end_date');

        if (strtotime($startDate) >= strtotime($endDate)) {
            return redirect()->back()->withInput()->with('error', 'Tanggal mulai harus sebelum tanggal selesai.');
        }

        $yearModel = new AcademicYearModel();
        $userId = session()->get('user_id');

        $data = [
            'name'       => $this->request->getPost('name'),
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'status'     => 'DRAFT',
            'is_active'  => 0,
            'created_by' => $userId
        ];

        $yearModel->insert($data);
        $newId = $yearModel->insertID();

        // Audit log
        AuditService::log(
            'academic_years',
            'create',
            'AcademicYear',
            $newId,
            null,
            $data,
            'Tahun pelajaran baru ditambahkan'
        );

        return redirect()->to('/academic-periods')->with('success', 'Tahun pelajaran baru berhasil ditambahkan.');
    }

    public function edit(string $uuid)
    {
        if (!has_permission('academic_years.manage')) {
            return redirect()->to('/academic-periods')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $yearModel = new AcademicYearModel();
        $year = $yearModel->where('uuid', $uuid)->first();

        if (!$year) {
            return redirect()->to('/academic-periods')->with('error', 'Tahun pelajaran tidak ditemukan.');
        }

        return view('academic_years/edit', [
            'title'             => 'Edit Tahun Pelajaran',
            'breadcrumb_active' => 'Edit Tahun Pelajaran',
            'year'              => $year
        ]);
    }

    public function update(string $uuid)
    {
        if (!has_permission('academic_years.manage')) {
            return redirect()->to('/academic-periods')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $yearModel = new AcademicYearModel();
        $year = $yearModel->where('uuid', $uuid)->first();

        if (!$year) {
            return redirect()->to('/academic-periods')->with('error', 'Tahun pelajaran tidak ditemukan.');
        }

        $rules = [
            'name'       => "required|regex_match[/^\d{4}\/\d{4}$/]|is_unique[academic_years.name,id,{$year['id']}]",
            'start_date' => 'required|valid_date',
            'end_date'   => 'required|valid_date',
            'status'     => 'required|in_list[DRAFT,ACTIVE,ARCHIVED]'
        ];

        $messages = [
            'name' => [
                'regex_match' => 'Format nama tahun pelajaran harus YYYY/YYYY (contoh: 2026/2027).'
            ]
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $startDate = $this->request->getPost('start_date');
        $endDate = $this->request->getPost('end_date');

        if (strtotime($startDate) >= strtotime($endDate)) {
            return redirect()->back()->withInput()->with('error', 'Tanggal mulai harus sebelum tanggal selesai.');
        }

        $before = $year;
        $after = [
            'name'       => $this->request->getPost('name'),
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'status'     => $this->request->getPost('status'),
            'updated_by' => session()->get('user_id')
        ];

        $yearModel->update($year['id'], $after);

        // Audit log
        AuditService::log(
            'academic_years',
            'update',
            'AcademicYear',
            $year['id'],
            $before,
            $after,
            'Tahun pelajaran diperbarui'
        );

        return redirect()->to('/academic-periods')->with('success', 'Tahun pelajaran berhasil diperbarui.');
    }

    public function activate(string $uuid)
    {
        if (!has_permission('academic_years.manage')) {
            return redirect()->to('/academic-periods')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $db = Database::connect();
        $db->transBegin();

        try {
            $yearModel = new AcademicYearModel();
            $year = $yearModel->where('uuid', $uuid)->first();

            if (!$year) {
                throw new \RuntimeException('Tahun pelajaran tidak ditemukan.');
            }

            // Set all other years to inactive
            $db->table('academic_years')->where('id !=', $year['id'])->update(['is_active' => 0]);
            
            // Set this year to active
            $db->table('academic_years')->where('id', $year['id'])->update([
                'is_active' => 1,
                'status'    => 'ACTIVE'
            ]);

            $db->transCommit();

            // Log Audit
            AuditService::log(
                'academic_years',
                'activate',
                'AcademicYear',
                $year['id'],
                ['is_active' => 0],
                ['is_active' => 1],
                'Tahun pelajaran diaktifkan secara global'
            );

            return redirect()->to('/academic-periods')->with('success', "Tahun pelajaran {$year['name']} berhasil diaktifkan.");
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->to('/academic-periods')->with('error', 'Gagal mengaktifkan tahun pelajaran: ' . $e->getMessage());
        }
    }
}
