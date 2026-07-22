<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherAdditionalDutyModel extends Model
{
    protected $table            = 'teacher_additional_duties';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $deletedField     = 'deleted_at';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'assignment_version_id',
        'academic_period_id',
        'unit_id',
        'teacher_id',
        'duty_type_id',
        'title_override',
        'workload_hours',
        'valid_from',
        'valid_until',
        'reference_number',
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
