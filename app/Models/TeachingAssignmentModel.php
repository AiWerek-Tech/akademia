<?php

namespace App\Models;

use CodeIgniter\Model;

class TeachingAssignmentModel extends Model
{
    protected $table            = 'teaching_assignments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true; // Enabled soft deletes as we have deleted_at nullable
    protected $deletedField     = 'deleted_at';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'assignment_version_id',
        'curriculum_structure_id',
        'academic_period_id',
        'unit_id',
        'grade_level_id',
        'classroom_id',
        'subject_id',
        'teacher_id',
        'assignment_role',
        'assigned_weekly_hours',
        'workload_weekly_hours',
        'source_weekly_hours',
        'allocation_percentage',
        'is_primary_teacher',
        'team_group_uuid',
        'notes',
        'status',
        'revision_number',
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'updated_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
