<?php

namespace App\Models;

use CodeIgniter\Model;

class ScheduleGenerationRunModel extends Model
{
    protected $table            = 'schedule_generation_runs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'schedule_version_id',
        'generator_strategy',
        'status',
        'started_at',
        'completed_at',
        'execution_time_ms',
        'score',
        'total_requirements',
        'placed_requirements',
        'unplaced_requirements',
        'hard_conflicts_count',
        'soft_conflicts_count',
        'configuration_json',
        'log_output',
        'created_by',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function findByUuid(string $uuid): ?array
    {
        return $this->where('uuid', $uuid)->first();
    }
}
