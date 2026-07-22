<?php

namespace App\Controllers;

use App\Services\DeterministicGreedyScheduleGenerator;
use CodeIgniter\HTTP\ResponseInterface;

class ScheduleGeneratorController extends BaseController
{
    private DeterministicGreedyScheduleGenerator $generator;

    public function __construct()
    {
        $this->generator = new DeterministicGreedyScheduleGenerator();
    }

    public function run(int $versionId): ResponseInterface
    {
        $userId = (int)session()->get('user_id');

        try {
            $result = $this->generator->generate($versionId, $userId);
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

    public function applyCandidate(int $candidateId): ResponseInterface
    {
        $userId = (int)session()->get('user_id');

        try {
            $result = $this->generator->applyCandidate($candidateId, $userId);
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
