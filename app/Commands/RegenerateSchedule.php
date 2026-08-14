<?php

namespace App\Commands;

use App\Services\DeterministicGreedyScheduleGenerator;
use App\Services\ScheduleConflictDetectionService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class RegenerateSchedule extends BaseCommand
{
    protected $group = 'Akademia';
    protected $name = 'akademia:schedule-regenerate';
    protected $description = 'Generate bounded schedule variants and optionally apply the best complete candidate.';
    protected $usage = 'akademia:schedule-regenerate <version-id> [--apply] [--user-id 1]';
    protected $arguments = ['version-id' => 'Numeric schedule version ID.'];
    protected $options = [
        '--apply' => 'Apply the best candidate when it is complete and conflict-free.',
        '--user-id' => 'Audit user ID (default: 1).',
    ];

    public function run(array $params): int
    {
        $versionId = isset($params[0]) ? (int) $params[0] : 0;
        $userId = (int) (CLI::getOption('user-id') ?: 1);
        $shouldApply = CLI::getOption('apply') !== null;
        if ($versionId <= 0) {
            CLI::error('Version ID wajib berupa angka positif.');
            return EXIT_ERROR;
        }

        $db = Database::connect();
        $version = $db->table('schedule_versions')->where('id', $versionId)->get()->getRowArray();
        if (! $version || (string) $version['workflow_status'] !== 'DRAFT') {
            CLI::error('Versi jadwal tidak ditemukan atau tidak berstatus DRAFT.');
            return EXIT_ERROR;
        }

        $generator = new DeterministicGreedyScheduleGenerator();
        $candidates = [];
        try {
            for ($strategy = 0; $strategy < 24; $strategy++) {
                $candidates[] = $generator->generate($versionId, $userId, $strategy);
            }
            usort($candidates, static function (array $a, array $b): int {
                $gap = (int) ($a['unplaced_requirements'] ?? PHP_INT_MAX)
                    <=> (int) ($b['unplaced_requirements'] ?? PHP_INT_MAX);
                return $gap !== 0 ? $gap
                    : ((int) ($b['total_placed_slots'] ?? 0) <=> (int) ($a['total_placed_slots'] ?? 0));
            });
            $best = $candidates[0];
            CLI::write(sprintf(
                'Best candidate #%d: %d slot, %d requirement belum ditempatkan.',
                (int) $best['candidate_id'],
                (int) $best['total_placed_slots'],
                (int) $best['unplaced_requirements']
            ));
            if (! $shouldApply) {
                return EXIT_SUCCESS;
            }
            if ((int) $best['unplaced_requirements'] !== 0) {
                CLI::error('Kandidat tidak lengkap; apply dibatalkan.');
                return EXIT_ERROR;
            }

            $applied = $generator->applyCandidate(
                (int) $best['candidate_id'],
                $userId,
                (int) $version['revision_number']
            );
            $audit = (new ScheduleConflictDetectionService())->detectConflicts($versionId);
            CLI::write(sprintf(
                'Applied: %d entry. Audit: %d konflik, %d kritis.',
                (int) ($applied['applied_entries'] ?? 0),
                (int) ($audit['total_conflicts'] ?? 0),
                (int) ($audit['critical_conflicts'] ?? 0)
            ), 'green');
            return (int) ($audit['critical_conflicts'] ?? 0) === 0 ? EXIT_SUCCESS : EXIT_ERROR;
        } catch (\Throwable $e) {
            CLI::error($e->getMessage());
            return EXIT_ERROR;
        }
    }
}
