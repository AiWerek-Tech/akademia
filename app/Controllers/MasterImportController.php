<?php

namespace App\Controllers;

use App\Services\MasterImportService;
use App\Models\MasterImportBatchModel;
use App\Models\MasterImportRowModel;

class MasterImportController extends BaseController
{
    public function index()
    {
        if (!has_permission('teachers.import') && !has_permission('subjects.import') && !has_permission('classrooms.import') && !has_permission('rooms.import')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses import.');
        }

        $batchModel = new MasterImportBatchModel();
        if (!in_array(session()->get('role_code'), ['superadmin', 'super_admin'], true)) {
            $batchModel->where('created_by', session()->get('user_id'));
        }
        $batches = $batchModel->orderBy('id', 'DESC')->paginate(20);

        return view('imports/index', [
            'title'             => 'Import Staging Master Data',
            'breadcrumb_active' => 'Import Master',
            'batches'           => $batches,
            'pager'             => $batchModel->pager,
        ]);
    }

    public function downloadTemplate(string $type)
    {
        try {
            $path = MasterImportService::generateTemplate($type);
            $filename = 'template_master_' . strtolower($type) . '.xlsx';
            return $this->response->download($path, null)->setFileName($filename);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function upload()
    {
        $type = $this->request->getPost('import_type');
        $permMap = [
            'TEACHERS'   => 'teachers.import',
            'SUBJECTS'   => 'subjects.import',
            'CLASSROOMS' => 'classrooms.import',
            'ROOMS'      => 'rooms.import',
        ];

        $requiredPerm = $permMap[strtoupper((string)$type)] ?? 'teachers.import';
        if (!has_permission($requiredPerm)) {
            return redirect()->to('/imports/master')->with('error', 'Anda tidak memiliki hak akses upload import.');
        }

        $rules = [
            'import_type' => 'required|in_list[TEACHERS,SUBJECTS,CLASSROOMS,ROOMS]',
            'file'        => 'uploaded[file]|max_size[file,10240]|ext_in[file,xlsx,xls,csv]|mime_in[file,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv,text/plain,application/csv]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $file = $this->request->getFile('file');
            $batch = MasterImportService::processUpload((string)$type, $file);

            return redirect()->to('/imports/master/' . $batch['uuid'])
                ->with('success', 'File berhasil di-upload dan diproses ke staging. Silakan periksa hasil validasi.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function showBatch(string $uuid)
    {
        $batchModel = new MasterImportBatchModel();
        $batch = $batchModel->where('uuid', $uuid)->first();

        if (!$batch) {
            return redirect()->to('/imports/master')->with('error', 'Batch import tidak ditemukan.');
        }
        if (!in_array(session()->get('role_code'), ['superadmin', 'super_admin'], true)
            && (int) $batch['created_by'] !== (int) session()->get('user_id')) {
            return redirect()->to('/imports/master')->with('error', 'Anda tidak memiliki akses ke batch import tersebut.');
        }

        $rowModel = new MasterImportRowModel();
        $rows = $rowModel->where('batch_id', $batch['id'])->orderBy('row_number', 'ASC')->paginate(50);

        return view('imports/show_batch', [
            'title'             => 'Detail Batch Staging Import #' . $batch['id'],
            'breadcrumb_active' => 'Detail Import Batch',
            'batch'             => $batch,
            'rows'              => $rows,
            'pager'             => $rowModel->pager,
        ]);
    }

    public function apply(string $uuid)
    {
        try {
            $batch = (new MasterImportBatchModel())->where('uuid', $uuid)->first();
            if (!$batch) {
                throw new \RuntimeException('Batch import tidak ditemukan.');
            }
            if (!in_array(session()->get('role_code'), ['superadmin', 'super_admin'], true)
                && (int) $batch['created_by'] !== (int) session()->get('user_id')) {
                throw new \RuntimeException('Anda tidak memiliki akses ke batch import tersebut.');
            }
            $res = MasterImportService::applyBatch($uuid);
            return redirect()->to('/imports/master/' . $uuid)
                ->with('success', 'Batch import berhasil diterapkan! Total ' . $res['applied_rows'] . ' data berhasil masuk ke database.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
