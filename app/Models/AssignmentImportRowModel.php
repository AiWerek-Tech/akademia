<?php

namespace App\Models;

use CodeIgniter\Model;

class AssignmentImportRowModel extends Model
{
    protected $table            = 'assignment_import_rows';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'batch_id',
        'row_number',
        'raw_data_json',
        'normalized_data_json',
        'source_unit',
        'source_grade',
        'source_classroom',
        'source_subject',
        'source_teacher',
        'mapped_unit_id',
        'mapped_grade_level_id',
        'mapped_classroom_id',
        'mapped_subject_id',
        'mapped_teacher_id',
        'assigned_weekly_hours',
        'workload_weekly_hours',
        'allocation_mode',
        'assignment_role',
        'proposed_action',
        'validation_status',
        'validation_messages_json',
        'admin_decision',
        'decision_reason',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
