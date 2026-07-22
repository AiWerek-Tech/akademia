<?php

namespace App\Models;

use CodeIgniter\Model;

class ScheduleRevisionHistoryModel extends Model
{
    protected $table            = 'schedule_revision_history';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'schedule_version_id',
        'revision_number',
        'action',
        'changes_json',
        'performed_by',
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
