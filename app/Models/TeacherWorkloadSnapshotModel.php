<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherWorkloadSnapshotModel extends Model
{
    protected $table            = 'teacher_workload_snapshots';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'assignment_version_id',
        'teacher_id',
        'academic_period_id',
        'unit_id',
        'teaching_assigned_hours',
        'teaching_workload_hours',
        'additional_duty_hours',
        'total_workload_hours',
        'policy_id',
        'policy_minimum',
        'policy_target',
        'policy_maximum',
        'shortage_hours',
        'overload_hours',
        'status',
        'details_json',
        'calculated_at',
        'calculated_by',
    ];

    protected $useTimestamps = false; // Manually handled dates for snapshots
}
