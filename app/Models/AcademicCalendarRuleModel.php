<?php

namespace App\Models;

use CodeIgniter\Model;

class AcademicCalendarRuleModel extends Model
{
    protected $table = 'academic_calendar_rules';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'calendar_id', 'title', 'day_type_code', 'start_date', 'end_date',
        'source_layer', 'priority', 'is_school_effective',
        'is_learning_effective', 'is_enabled', 'notes', 'created_by',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
