<?php

namespace App\Models;

use CodeIgniter\Model;

class ScheduleConflictModel extends Model
{
    protected $table            = 'schedule_conflicts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'schedule_version_id',
        'conflict_type',
        'severity',
        'description',
        'entity_type',
        'entity_id',
        'primary_entry_id',
        'conflicting_entry_id',
        'is_resolved',
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
