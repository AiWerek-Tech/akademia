<?php

namespace App\Models;

use CodeIgniter\Model;

class AcademicCalendarDayModel extends Model
{
    protected $table            = 'academic_calendar_days';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'calendar_id', 'date', 'day_of_week', 'day_type_code',
        'is_school_effective', 'is_learning_effective',
        'event_title', 'source_layer', 'source_rule_id', 'is_manual_override',
        'custom_bg_color', 'custom_text_color',
    ];

    protected $useTimestamps = false;
}
