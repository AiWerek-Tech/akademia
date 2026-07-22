<?php

namespace App\Models;

use CodeIgniter\Model;

class WorkloadPolicyModel extends Model
{
    protected $table            = 'workload_policies';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'academic_period_id',
        'unit_id',
        'employment_status',
        'employment_type',
        'teacher_category',
        'minimum_teaching_hours',
        'maximum_teaching_hours',
        'target_total_hours',
        'maximum_total_hours',
        'additional_duty_cap',
        'overload_warning_threshold',
        'underload_warning_threshold',
        'priority',
        'is_active',
        'revision_number',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
