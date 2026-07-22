<?php

namespace App\Models;

use CodeIgniter\Model;

class ScheduleGenerationCandidateModel extends Model
{
    protected $table            = 'schedule_generation_candidates';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'generation_run_id',
        'candidate_number',
        'score',
        'hard_score',
        'soft_score',
        'placed_count',
        'unplaced_count',
        'is_applied',
        'applied_at',
        'applied_by',
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
