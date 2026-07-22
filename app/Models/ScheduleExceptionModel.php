<?php

namespace App\Models;

use CodeIgniter\Model;

class ScheduleExceptionModel extends Model
{
    protected $table            = 'schedule_exceptions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'schedule_version_id',
        'schedule_entry_id',
        'exception_type',
        'reason',
        'approved_by',
        'approved_at',
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
