<?php

namespace App\Commands;

use App\Services\TeacherSubstitutionScheduleRepairService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class RepairTeacherSubstitutionSchedule extends BaseCommand
{
    protected $group = 'Scheduling';
    protected $name = 'schedule:repair-substitution';
    protected $description = 'Analisis atau terapkan kandidat Auto Repair substitusi guru.';
    protected $usage = 'schedule:repair-substitution <substitution_id> [--apply candidate_id] [--user user_id]';

    public function run(array $params)
    {
        $substitutionId = (int) ($params[0] ?? 0);
        $userId = (int) (CLI::getOption('user') ?: 1);
        if ($substitutionId < 1) {
            CLI::error('substitution_id wajib diisi.');
            return EXIT_ERROR;
        }
        try {
            $service = new TeacherSubstitutionScheduleRepairService();
            $apply = CLI::getOption('apply');
            $result = $apply !== null
                ? $service->apply((int) $apply, $substitutionId, $userId)
                : $service->generate($substitutionId, $userId);
            CLI::write(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'green');
            return EXIT_SUCCESS;
        } catch (\Throwable $e) {
            CLI::error($e->getMessage());
            return EXIT_ERROR;
        }
    }
}
