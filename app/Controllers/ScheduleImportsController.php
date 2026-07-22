<?php

namespace App\Controllers;

use App\Services\ScheduleImportService;
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

        try {
            $result = $this->importService->applyBatch($batchId, $userId);
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
