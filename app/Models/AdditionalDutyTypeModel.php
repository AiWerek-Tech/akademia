<?php

namespace App\Models;

use CodeIgniter\Model;

class AdditionalDutyTypeModel extends Model
{
    protected $table            = 'additional_duty_types';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'code',
        'name',
        'category',
        'default_workload_hours',
        'maximum_holders',
        'requires_unit',
        'requires_period',
        'counts_toward_workload',
        'is_active',
        'sort_order',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
