<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherScheduleSubstitutionModel extends Model
{
    protected $table = 'teacher_schedule_substitutions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'uuid', 'academic_period_id', 'absent_teacher_id', 'substitute_teacher_id',
        'effective_from', 'effective_to', 'status', 'notes',
        'created_by', 'updated_by', 'created_at', 'updated_at',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
