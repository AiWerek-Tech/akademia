<?php

namespace App\Controllers;

use App\Services\ScheduleImportService;
use App\Services\UnitScopeService;
use Config\Database;
use CodeIgniter\HTTP\ResponseInterface;

class ScheduleImportsController extends BaseController
{
    private ScheduleImportService $importService;

    public function __construct()
    {
        $this->importService = new ScheduleImportService();
    }

    public function stage(int $versionId): ResponseInterface
    {
        $fileName = (string)($this->request->getPost('file_name') ?? 'schedule_import.csv');
        $rowsJson = (string)$this->request->getPost('rows_json');
        $rowsData = json_decode($rowsJson, true) ?? [];
        $userId   = (int)session()->get('user_id');

        try {
            $version = Database::connect()->table('schedule_versions')->where('id', $versionId)->get()->getRowArray();
            if (! $version) {
                throw new \RuntimeException('Versi jadwal tidak ditemukan.');
            }
            if (empty($version['unit_id'])) {
                throw new \RuntimeException('Import jadwal gabungan belum didukung dengan aman. Gunakan versi per unit.');
            }
            UnitScopeService::assertUnit((int) $version['unit_id']);
            $result = $this->importService->stageBatch($versionId, $fileName, $rowsData, $userId);
            return $this->response->setJSON([
                'status' => 'success',
                'data'   => $result,
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ])->setStatusCode(400);
        }
    }

    public function apply(int $batchId): ResponseInterface
    {
        $userId = (int)session()->get('user_id');
        $currentRevision = filter_var($this->request->getPost('current_revision'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        try {
            $batch = Database::connect()->table('schedule_import_batches sib')
                ->select('sv.unit_id')->join('schedule_versions sv', 'sv.id = sib.schedule_version_id')
                ->where('sib.id', $batchId)->get()->getRowArray();
            if (! $batch) {
                throw new \RuntimeException('Batch import jadwal tidak ditemukan.');
            }
            if ($currentRevision === false || $currentRevision === null) {
                throw new \InvalidArgumentException('Revisi jadwal wajib disertakan. Muat ulang halaman lalu coba lagi.');
            }
            if (empty($batch['unit_id'])) {
                throw new \RuntimeException('Import jadwal gabungan belum didukung dengan aman. Gunakan versi per unit.');
            }
            UnitScopeService::assertUnit((int) $batch['unit_id']);
            $result = $this->importService->applyBatch($batchId, $userId, (int) $currentRevision);
            return $this->response->setJSON([
                'status' => 'success',
                'data'   => $result,
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ])->setStatusCode(400);
        }
    }
}
