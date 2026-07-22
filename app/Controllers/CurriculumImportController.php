<?php

namespace App\Controllers;

use App\Services\CurriculumImportService;
use App\Models\CurriculumImportBatchModel;
use App\Models\CurriculumImportRowModel;
use App\Models\CurriculumVersionModel;
use App\Services\UnitScopeService;
use Config\Database;

class CurriculumImportController extends BaseController
{
    public function index()
    {
        if (!has_permission('curriculum.import')) {
            return redirect()->to('/curriculum')->with('error', 'Hak akses ditolak untuk impor kurikulum.');
        }

        $batchModel = new CurriculumImportBatchModel();
        if (!in_array(session()->get('role_code'), ['superadmin', 'super_admin'], true)) {
            $batchModel->where('created_by', session()->get('user_id'));
        }
        $batches = $batchModel->orderBy('id', 'DESC')->findAll(50);

        $versions = Database::connect()->table('curriculum_versions cv')
            ->select('cv.id, cv.code, cv.name, cv.is_active, ap.name AS period_name')
            ->join('academic_periods ap', 'ap.id = cv.academic_period_id', 'left')
            ->where('cv.workflow_status !=', 'ARCHIVED')
            ->orderBy('cv.is_active', 'DESC')->orderBy('cv.id', 'DESC')->get()->getResultArray();

        return view('curriculum/imports/index', ['batches' => $batches, 'versions' => $versions]);
    }

    public function template()
    {
        if (!has_permission('curriculum.import')) {
            return redirect()->to('/curriculum')->with('error', 'Hak akses ditolak.');
        }

        try {
            $path = CurriculumImportService::generateTemplate();
            return $this->response->download($path, null)->setFileName('Template_Import_Kurikulum.xlsx');
        } catch (\Throwable $e) {
            return redirect()->to('/curriculum/imports')->with('error', $e->getMessage());
        }
    }

    public function upload()
    {
        if (!has_permission('curriculum.import')) {
            return redirect()->to('/curriculum')->with('error', 'Hak akses ditolak.');
        }

        $versionId = $this->request->getPost('curriculum_version_id');
        $file      = $this->request->getFile('import_file');

        if (!$versionId || !$file) {
            return redirect()->back()->withInput()->with('error', 'Pilih kurikulum tujuan dan file Excel terlebih dahulu.');
        }

        $rules = [
            'curriculum_version_id' => 'required|integer',
            'import_file' => 'uploaded[import_file]|max_size[import_file,10240]|ext_in[import_file,xlsx,xls,csv]|mime_in[import_file,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv,text/plain,application/csv]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $batch = CurriculumImportService::processUpload((int)$versionId, $file);
            return redirect()->to('/curriculum/imports/' . $batch['uuid'])->with('success', 'File selesai diperiksa. Tinjau hasilnya sebelum diterapkan.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function showBatch(string $uuid)
    {
        if (!has_permission('curriculum.import')) {
            return redirect()->to('/curriculum')->with('error', 'Hak akses ditolak.');
        }

        $batchModel = new CurriculumImportBatchModel();
        $rowModel   = new CurriculumImportRowModel();
        $versionModel = new CurriculumVersionModel();

        $batch = $batchModel->where('uuid', $uuid)->first();
        if (!$batch) {
            return redirect()->to('/curriculum/imports')->with('error', 'Batch import tidak ditemukan.');
        }
        if (!in_array(session()->get('role_code'), ['superadmin', 'super_admin'], true)
            && (int) $batch['created_by'] !== (int) session()->get('user_id')) {
            return redirect()->to('/curriculum/imports')->with('error', 'Anda tidak memiliki akses ke batch import tersebut.');
        }

        $version = !empty($batch['curriculum_version_id']) ? $versionModel->find($batch['curriculum_version_id']) : null;
        $rows = $rowModel->where('batch_id', $batch['id'])->orderBy('row_number', 'ASC')->paginate(100, 'curriculum_rows');

        return view('curriculum/imports/show_batch', [
            'batch'   => $batch,
            'version' => $version,
            'rows'    => $rows,
            'pager'   => $rowModel->pager,
        ]);
    }

    public function apply(string $uuid)
    {
        if (!has_permission('curriculum.import')) {
            return redirect()->to('/curriculum')->with('error', 'Hak akses ditolak.');
        }

        try {
            $batch = (new CurriculumImportBatchModel())->where('uuid', $uuid)->first();
            if (!$batch) {
                throw new \RuntimeException('Batch import tidak ditemukan.');
            }
            if (!in_array(session()->get('role_code'), ['superadmin', 'super_admin'], true)
                && (int) $batch['created_by'] !== (int) session()->get('user_id')) {
                throw new \RuntimeException('Anda tidak memiliki akses ke batch import tersebut.');
            }
            $result = CurriculumImportService::applyBatch($uuid);
            return redirect()->to('/curriculum/imports/' . $uuid)->with('success', "Impor selesai. {$result['inserted_rows']} data ditambahkan dan {$result['updated_rows']} data diperbarui.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
