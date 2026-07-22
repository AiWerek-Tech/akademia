<?php

namespace App\Models;

use CodeIgniter\Model;

class ScheduleFixedActivityModel extends Model
{
    protected $table            = 'schedule_fixed_activities';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'schedule_version_id',
        'day_slot_id',
        'school_unit_id',
        'classroom_id',
        'title',
        'activity_type',
        'description',
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
