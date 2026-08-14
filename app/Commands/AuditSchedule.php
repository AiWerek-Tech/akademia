<?php

namespace App\Commands;

use App\Services\ScheduleConflictDetectionService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AuditSchedule extends BaseCommand
{
    protected $group = 'Akademia';
    protected $name = 'akademia:schedule-audit';
    protected $description = 'Audit a schedule version and return a failing exit code for critical conflicts.';
    protected $usage = 'akademia:schedule-audit <version-id>';

    public function run(array $params): int
    {
        $versionId = isset($params[0]) ? (int) $params[0] : 0;
        if ($versionId <= 0) {
            CLI::error('Version ID wajib berupa angka positif.');
            return EXIT_ERROR;
        }

        try {
            $audit = (new ScheduleConflictDetectionService())->detectConflicts($versionId);
            foreach ($audit['conflicts'] ?? [] as $conflict) {
                CLI::write(sprintf('[%s] %s: %s',
                    $conflict['severity'] ?? 'UNKNOWN',
                    $conflict['conflict_type'] ?? 'UNKNOWN',
                    $conflict['description'] ?? ''
                ));
            }
            CLI::write(sprintf(
                'Audit versi #%d: %d konflik, %d kritis.',
                $versionId,
                (int) ($audit['total_conflicts'] ?? 0),
                (int) ($audit['critical_conflicts'] ?? 0)
            ));
            return (int) ($audit['critical_conflicts'] ?? 0) === 0 ? EXIT_SUCCESS : EXIT_ERROR;
        } catch (\Throwable $e) {
            CLI::error($e->getMessage());
            return EXIT_ERROR;
        }
    }
}
