<?php

namespace App\Models;

use CodeIgniter\Model;

class ScheduleLockModel extends Model
{
    protected $table            = 'schedule_locks';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'schedule_version_id',
        'lock_target_type',
        'target_id',
        'reason',
        'locked_by',
        'locked_at',
        'created_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function findByUuid(string $uuid): ?array
    {
        return $this->where('uuid', $uuid)->first();
    }
}
