<?php

namespace App\Controllers;

use App\Models\AcademicPeriodModel;
use App\Models\AcademicYearModel;
use App\Services\AuditService;
use Config\Database;

class AcademicPeriodController extends BaseController
{
    public function index()
    {
        if (!has_permission('academic_periods.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $db = Database::connect();
        
        $state = strtoupper(trim((string) $this->request->getGet('state')));
        $yearId = (int) $this->request->getGet('academic_year_id');

        $years = $db->table('academic_years')
            ->orderBy('name', 'DESC')
            ->get()
            ->getResultArray();

        $periodBuilder = $db->table('academic_periods ap')
            ->select('ap.*, ay.name as year_name')
            ->join('academic_years ay', 'ay.id = ap.academic_year_id');
        if ($yearId > 0) {
            $periodBuilder->where('ap.academic_year_id', $yearId);
        }
        if ($state === 'ACTIVE') {
            $periodBuilder->where('ap.is_active', 1);
        } elseif ($state === 'INACTIVE') {
            $periodBuilder->where('ap.is_active', 0)->where('ap.workflow_status !=', 'ARCHIVED');
        } elseif ($state === 'ARCHIVED') {
            $periodBuilder->where('ap.workflow_status', 'ARCHIVED');
        }
        $periods = $periodBuilder
            ->orderBy('ay.name', 'DESC')
            ->orderBy('ap.semester_number', 'DESC')
            ->get()
            ->getResultArray();

        return view('academic_periods/index', [
            'title'             => 'Tahun Pelajaran & Periode Akademik',
            'breadcrumb_active' => 'Tahun Pelajaran',
            'years'             => $years,
            'periods'           => $periods,
            'filters'           => ['academic_year_id' => $yearId ?: '', 'state' => $state],
        ]);
    }

    public function create()
    {
        if (!has_permission('academic_periods.manage')) {
            return redirect()->to('/academic-periods')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $yearModel = new AcademicYearModel();
        $years = $yearModel->orderBy('name', 'DESC')->findAll();

        return view('academic_periods/create', [
            'title'             => 'Tambah Periode Akademik',
            'breadcrumb_active' => 'Tambah Tahun Pelajaran',
            'years'             => $years
        ]);
    }

    public function store()
    {
        if (!has_permission('academic_periods.manage')) {
            return redirect()->to('/academic-periods')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $rules = [
            'academic_year_id' => 'required|is_not_unique[academic_years.id]',
            'semester_number'  => 'required|in_list[1,2]',
            'start_date'       => 'required|valid_date',
            'end_date'         => 'required|valid_date',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $yearId = (int)$this->request->getPost('academic_year_id');
        $semester = (int)$this->request->getPost('semester_number');
        $startDate = $this->request->getPost('start_date');
        $endDate = $this->request->getPost('end_date');

        if (strtotime($startDate) >= strtotime($endDate)) {
            return redirect()->back()->withInput()->with('error', 'Tanggal mulai harus sebelum tanggal selesai.');
        }

        $year = (new AcademicYearModel())->find($yearId);
        if (!$year || $startDate < $year['start_date'] || $endDate > $year['end_date']) {
            return redirect()->back()->withInput()->with('error', 'Tanggal periode harus berada di dalam rentang tahun pelajaran yang dipilih.');
        }

        $periodModel = new AcademicPeriodModel();

        // Unique check
        $exists = $periodModel->where('academic_year_id', $yearId)
            ->where('semester_number', $semester)
            ->first();

        if ($exists) {
            return redirect()->back()->withInput()->with('error', 'Periode akademik untuk semester tersebut sudah ada.');
        }
        if ($this->periodOverlaps($yearId, $startDate, $endDate)) {
            return redirect()->back()->withInput()->with('error', 'Rentang tanggal periode bertumpang tindih dengan periode lain pada tahun pelajaran yang sama.');
        }

        $userId = session()->get('user_id');

        $data = [
            'academic_year_id' => $yearId,
            'semester_number'  => $semester,
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'is_active'        => 0,
            'workflow_status'  => 'APPROVED',
            'revision_number'  => 1,
            'created_by'       => $userId
        ];

        $periodModel->insert($data);
        $newId = $periodModel->insertID();

        // Audit log
        AuditService::log(
            'academic_periods',
            'create',
            'AcademicPeriod',
            $newId,
            null,
            $data,
            'Periode akademik baru ditambahkan'
        );

        return redirect()->to('/academic-periods')->with('success', 'Periode akademik baru berhasil ditambahkan.');
    }

    public function show(string $uuid)
    {
        if (!has_permission('academic_periods.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $db = Database::connect();

        $period = $db->table('academic_periods ap')
            ->select('ap.*, ay.name as year_name, uc.username as creator_name, uu.username as updater_name, ua.username as approver_name, ul.username as locker_name')
            ->join('academic_years ay', 'ay.id = ap.academic_year_id')
            ->join('users uc', 'uc.id = ap.created_by', 'left')
            ->join('users uu', 'uu.id = ap.updated_by', 'left')
            ->join('users ua', 'ua.id = ap.approved_by', 'left')
            ->join('users ul', 'ul.id = ap.locked_by', 'left')
            ->where('ap.uuid', $uuid)
            ->get()
            ->getRowArray();

        if (!$period) {
            return redirect()->to('/academic-periods')->with('error', 'Periode akademik tidak ditemukan.');
        }

        return view('academic_periods/show', [
            'title'             => 'Detail Periode Akademik',
            'breadcrumb_active' => 'Detail Tahun Pelajaran',
            'period'            => $period
        ]);
    }

    public function edit(string $uuid)
    {
        if (!has_permission('academic_periods.manage')) {
            return redirect()->to('/academic-periods')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $periodModel = new AcademicPeriodModel();
        $period = $periodModel->where('uuid', $uuid)->first();

        if (!$period) {
            return redirect()->to('/academic-periods')->with('error', 'Periode akademik tidak ditemukan.');
        }

        $yearModel = new AcademicYearModel();
        $year = $yearModel->find($period['academic_year_id']);

        return view('academic_periods/edit', [
            'title'             => 'Edit Periode Akademik',
            'breadcrumb_active' => 'Edit Tahun Pelajaran',
            'period'            => $period,
            'year'              => $year
        ]);
    }

    public function update(string $uuid)
    {
        if (!has_permission('academic_periods.manage')) {
            return redirect()->to('/academic-periods')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $periodModel = new AcademicPeriodModel();
        $period = $periodModel->where('uuid', $uuid)->first();

        if (!$period) {
            return redirect()->to('/academic-periods')->with('error', 'Periode akademik tidak ditemukan.');
        }

        $rules = [
            'start_date'      => 'required|valid_date',
            'end_date'        => 'required|valid_date',
            'revision_number' => 'required|integer'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $startDate = $this->request->getPost('start_date');
        $endDate = $this->request->getPost('end_date');
        $revision = (int)$this->request->getPost('revision_number');

        if (strtotime($startDate) >= strtotime($endDate)) {
            return redirect()->back()->withInput()->with('error', 'Tanggal mulai harus sebelum tanggal selesai.');
        }

        $year = (new AcademicYearModel())->find($period['academic_year_id']);
        if (!$year || $startDate < $year['start_date'] || $endDate > $year['end_date']) {
            return redirect()->back()->withInput()->with('error', 'Tanggal periode harus berada di dalam rentang tahun pelajaran.');
        }
        if ($this->periodOverlaps((int) $period['academic_year_id'], $startDate, $endDate, (int) $period['id'])) {
            return redirect()->back()->withInput()->with('error', 'Rentang tanggal periode bertumpang tindih dengan periode semester lain.');
        }

        // Optimistic locking
        if ((int)$period['revision_number'] !== $revision) {
            return redirect()->back()->withInput()->with('error', 'Data telah diperbarui oleh pengguna lain. Silakan muat ulang halaman.');
        }

        $before = $period;
        $after = [
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'revision_number' => $period['revision_number'] + 1,
            'updated_by'      => session()->get('user_id'),
            'updated_at'      => date('Y-m-d H:i:s')
        ];

        $db = Database::connect();
        $db->table('academic_periods')
            ->where('id', $period['id'])
            ->where('revision_number', $revision)
            ->update($after);
        if ($db->affectedRows() !== 1) {
            return redirect()->back()->withInput()->with('error', 'Data telah diperbarui oleh pengguna lain. Silakan muat ulang halaman.');
        }

        // Audit log
        AuditService::log(
            'academic_periods',
            'update',
            'AcademicPeriod',
            $period['id'],
            $before,
            $after,
            'Metadata periode akademik diperbarui'
        );

        return redirect()->to("/academic-periods/{$uuid}")->with('success', 'Metadata periode akademik berhasil diperbarui.');
    }

    public function activate(string $uuid)
    {
        if (!has_permission('academic_periods.manage')) {
            return redirect()->to('/academic-periods')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $db = Database::connect();
        $db->transBegin();

        try {
            $periodModel = new AcademicPeriodModel();
            $period = $periodModel->where('uuid', $uuid)->first();

            if (!$period) {
                throw new \RuntimeException('Periode akademik tidak ditemukan.');
            }

            if ($period['workflow_status'] === 'ARCHIVED') {
                throw new \RuntimeException('Periode yang telah diarsipkan tidak dapat diaktifkan.');
            }

            $beforeStatus = $period['workflow_status'];
            $activationData = [
                'is_active'      => 1,
                'updated_at'     => date('Y-m-d H:i:s'),
                'updated_by'     => session()->get('user_id'),
                'revision_number'=> ((int) $period['revision_number']) + 1,
            ];
            $activationData['workflow_status'] = 'APPROVED';

            // Deactivate all periods
            $db->table('academic_periods')->where('id !=', $period['id'])->update(['is_active' => 0]);

            $db->table('academic_periods')->where('id', $period['id'])->update($activationData);

            $db->table('academic_years')->where('id !=', $period['academic_year_id'])->update(['is_active' => 0]);
            $db->table('academic_years')->where('id', $period['academic_year_id'])->update(['is_active' => 1, 'status' => 'ACTIVE']);

            $db->transCommit();

            // Refresh session if active period matches
            session()->set('active_period_id', $period['id']);

            // Log Audit
            AuditService::log(
                'academic_periods',
                'activate',
                'AcademicPeriod',
                $period['id'],
                ['is_active' => (int) $period['is_active'], 'workflow_status' => $beforeStatus],
                ['is_active' => 1, 'workflow_status' => $activationData['workflow_status'] ?? $beforeStatus],
                'Periode akademik diaktifkan langsung'
            );

            return redirect()->to('/academic-periods')->with('success', 'Periode berhasil diaktifkan. Periode sebelumnya dinonaktifkan otomatis.');
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->to('/academic-periods')->with('error', 'Gagal mengaktifkan periode akademik: ' . $e->getMessage());
        }
    }

    private function periodOverlaps(int $yearId, string $startDate, string $endDate, ?int $exceptId = null): bool
    {
        $builder = Database::connect()->table('academic_periods')
            ->where('academic_year_id', $yearId)
            ->where('start_date <=', $endDate)
            ->where('end_date >=', $startDate);
        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }
        return $builder->countAllResults() > 0;
    }
}
