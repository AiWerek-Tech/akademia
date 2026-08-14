<?php

namespace App\Controllers;

use App\Services\DeterministicGreedyScheduleGenerator;
use App\Models\ScheduleVersionModel;
use App\Services\UnitScopeService;
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
            $version = (new ScheduleVersionModel())->find($versionId);
            if (! $version) {
                throw new \RuntimeException('Versi jadwal tidak ditemukan.');
            }
            if (!empty($version['unit_id'])) {
                UnitScopeService::assertUnit((int) $version['unit_id']);
            }
            if ((string) ($version['workflow_status'] ?? '') !== 'DRAFT') {
                throw new \RuntimeException('Penyusunan otomatis hanya dapat dijalankan pada jadwal berstatus DRAFT.');
            }
            // Greedy placement is deterministic per strategy, but a single
            // ordering can consume a legal slot needed by a later requirement.
            // Generate several bounded variants and keep the best one.
            $candidates = [];
            for ($strategy = 0; $strategy < 24; $strategy++) {
                $candidates[] = $this->generator->generate($versionId, $userId, $strategy);
            }
            usort($candidates, static function (array $a, array $b): int {
                $aGap = (int) ($a['unplaced_requirements'] ?? PHP_INT_MAX);
                $bGap = (int) ($b['unplaced_requirements'] ?? PHP_INT_MAX);
                if ($aGap !== $bGap) return $aGap <=> $bGap;

                $aConflicts = (int) ($a['conflicts_count'] ?? count($a['conflicts'] ?? []));
                $bConflicts = (int) ($b['conflicts_count'] ?? count($b['conflicts'] ?? []));
                if ($aConflicts !== $bConflicts) return $aConflicts <=> $bConflicts;

                return (int) ($b['total_placed_slots'] ?? 0) <=> (int) ($a['total_placed_slots'] ?? 0);
            });
            $result = $candidates[0];
            $result['applied'] = false;
            $result['apply_message'] = 'Kandidat berhasil dibuat. Tinjau hasilnya, lalu terapkan secara eksplisit jika sudah lengkap dan bebas konflik kritis.';
            return $this->response->setJSON([
                'status'    => 'success',
                'data'      => $result,
                'csrf_hash' => csrf_hash(),
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status'    => 'error',
                'message'   => $e->getMessage(),
                'csrf_hash' => csrf_hash(),
            ])->setStatusCode(400);
        }
    }

    public function applyCandidate(int $candidateId): ResponseInterface
    {
        $userId = (int)session()->get('user_id');
        $currentRevision = filter_var(
            $this->request->getPost('current_revision'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        try {
            if ($currentRevision === false || $currentRevision === null) {
                throw new \InvalidArgumentException('Revisi jadwal wajib disertakan. Muat ulang halaman lalu coba lagi.');
            }
            $db = \Config\Database::connect();
            $version = $db->table('schedule_generation_candidates sgc')
                ->select('sv.unit_id')
                ->join('schedule_generation_runs sgr', 'sgr.id = sgc.generation_run_id')
                ->join('schedule_versions sv', 'sv.id = sgr.schedule_version_id')
                ->where('sgc.id', $candidateId)->get()->getRowArray();
            if (! $version) {
                throw new \RuntimeException('Kandidat jadwal tidak ditemukan.');
            }
            if (!empty($version['unit_id'])) {
                UnitScopeService::assertUnit((int) $version['unit_id']);
            }
            $result = $this->generator->applyCandidate($candidateId, $userId, (int) $currentRevision);
            return $this->response->setJSON([
                'status'    => 'success',
                'data'      => $result,
                'csrf_hash' => csrf_hash(),
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status'    => 'error',
                'message'   => $e->getMessage(),
                'csrf_hash' => csrf_hash(),
            ])->setStatusCode(400);
        }
    }
}
