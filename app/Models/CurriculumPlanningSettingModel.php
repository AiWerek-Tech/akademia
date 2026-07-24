<?php

namespace App\Models;

use CodeIgniter\Model;

class CurriculumPlanningSettingModel extends Model
{
    protected $table = 'curriculum_planning_settings';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'curriculum_version_id',
        'unit_id',
        'teaching_days_per_week',
        'daily_jp_capacity',
        'teacher_minimum_hours',
        'teacher_maximum_hours',
        'allow_custom_hours',
        'notes',
        'created_by',
        'updated_by',
    ];
}
