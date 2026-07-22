<?php

namespace App\Models;

use CodeIgniter\Model;

class AssignmentValidationResultModel extends Model
{
    protected $table            = 'assignment_validation_results';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'assignment_version_id',
        'teaching_assignment_id',
        'teacher_id',
        'validation_code',
        'severity',
        'message',
        'details_json',
        'is_resolved',
        'resolved_by',
        'resolved_at',
        'created_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = ''; // No updated_at field on this table
}
