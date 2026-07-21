<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherUnitAssignmentModel extends Model
{
    protected $table            = 'teacher_unit_assignments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'teacher_id', 'unit_id', 'academic_period_id', 'assignment_type',
        'is_primary', 'valid_from', 'valid_until', 'status', 'notes',
        'created_by', 'updated_by'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
