<?php

namespace App\Models;

use CodeIgniter\Model;

class ClassroomAvailabilityRuleModel extends Model
{
    protected $table            = 'classroom_availability_rules';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'classroom_id',
        'academic_period_id',
        'day_of_week',
        'slot_number',
        'availability_status',
        'reason',
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
