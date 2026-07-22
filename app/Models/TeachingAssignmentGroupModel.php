<?php

namespace App\Models;

use CodeIgniter\Model;

class TeachingAssignmentGroupModel extends Model
{
    protected $table            = 'teaching_assignment_groups';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'assignment_version_id',
        'curriculum_structure_id',
        'allocation_mode',
        'required_weekly_hours',
        'allocated_weekly_hours',
        'workload_calculation_mode',
        'status',
        'notes',
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
