<?php

namespace App\Models;

use CodeIgniter\Model;

class ScheduleDaySlotModel extends Model
{
    protected $table            = 'schedule_day_slots';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'schedule_version_id',
        'day_id',
        'slot_number',
        'start_time',
        'end_time',
        'slot_type',
        'label',
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
