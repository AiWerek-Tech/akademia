<?php

namespace App\Controllers;

use App\Services\AssignmentImportService;
use App\Models\AssignmentImportBatchModel;
use App\Models\AssignmentImportRowModel;
use App\Models\AssignmentVersionModel;

class AssignmentsImportController extends BaseController
{
    public function index()
    {
        if (!has_permission('assignments.import')) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        $batchModel = new AssignmentImportBatchModel();
        if (!in_array(session()->get('role_code'), ['superadmin', 'super_admin'], true)) {
            $batchModel->where('assignment_import_batches.created_by', session()->get('user_id'));
        }
        $batches = $batchModel->select('assignment_import_batches.*, assignment_versions.code as version_code')
                             ->join('assignment_versions', 'assignment_versions.id = assignment_import_batches.assignment_version_id', 'left')
                             ->orderBy('assignment_import_batches.id', 'DESC')
                             ->findAll();

        return view('assignments/imports/index', [
            'batches' => $batches
        ]);
    }

    public function template()
    {
        if (!has_permission('assignments.import')) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        try {
            $path = AssignmentImportService::generateTemplate();
            return $this->response->download($path, null)->inline();
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function upload()
    {
        if (!has_permission('assignments.import')) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $versionId = (int)$this->request->getPost('assignment_version_id');
        $file = $this->request->getFile('import_file');

        $rules = [
            'assignment_version_id' => 'required|integer',
            'import_file' => 'uploaded[import_file]|max_size[import_file,10240]|ext_in[import_file,xlsx,xls,csv]|mime_in[import_file,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv,text/plain,application/csv]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $batch = AssignmentImportService::processUpload($versionId, $file);
            return redirect()->to('/assignments/imports/' . $batch['uuid'])->with('success', 'File berhasil diunggah dan divalidasi ke staging.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function showBatch(string $uuid)
    {
        if (!has_permission('assignments.import')) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        $batchModel = new AssignmentImportBatchModel();
        $rowModel = new AssignmentImportRowModel();

        $batch = $batchModel->where('uuid', $uuid)->first();
        if (!$batch) {
            return redirect()->to('/assignments/imports')->with('error', 'Batch import tidak ditemukan.');
        }
        if (!in_array(session()->get('role_code'), ['superadmin', 'super_admin'], true)
            && (int) $batch['created_by'] !== (int) session()->get('user_id')) {
            return redirect()->to('/assignments/imports')->with('error', 'Anda tidak memiliki akses ke batch import tersebut.');
        }

        $rows = $rowModel->where('batch_id', $batch['id'])->findAll();

        return view('assignments/imports/show', [
            'batch' => $batch,
            'rows'  => $rows
        ]);
    }

    public function apply(string $uuid)
    {
        if (!has_permission('assignments.import')) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        try {
            $this->assertBatchOwnership($uuid);
            $result = AssignmentImportService::applyBatch($uuid);
            return redirect()->to('/assignments/imports/' . $uuid)->with('success', "Berhasil menerapkan {$result['applied_rows']} data penugasan.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function rollback(string $uuid)
    {
        if (!has_permission('assignments.import')) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        try {
            $this->assertBatchOwnership($uuid);
            AssignmentImportService::rollbackBatch($uuid);
            return redirect()->to('/assignments/imports/' . $uuid)->with('success', 'Staging batch berhasil di-rollback.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function assertBatchOwnership(string $uuid): void
    {
        $batch = (new AssignmentImportBatchModel())->where('uuid', $uuid)->first();
        if (!$batch) {
            throw new \RuntimeException('Batch import tidak ditemukan.');
        }
        if (!in_array(session()->get('role_code'), ['superadmin', 'super_admin'], true)
            && (int) $batch['created_by'] !== (int) session()->get('user_id')) {
            throw new \RuntimeException('Anda tidak memiliki akses ke batch import tersebut.');
        }
    }
}
