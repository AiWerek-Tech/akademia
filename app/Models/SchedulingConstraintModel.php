<?php

namespace App\Models;

use CodeIgniter\Model;

class SchedulingConstraintModel extends Model
{
    protected $table            = 'scheduling_constraints';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'school_unit_id',
        'code',
        'name',
        'constraint_type',
        'severity',
        'weight',
        'is_enabled',
        'configuration_json',
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
