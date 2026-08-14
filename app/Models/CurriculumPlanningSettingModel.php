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
        'workload_policy_id',
        'teaching_days_per_week',
        'selected_day_codes_json',
        'daily_jp_capacity',
        'daily_jp_capacities_json',
        'minutes_per_jp',
        'start_time_jp1',
        'allow_custom_hours',
        'notes',
        'revision_number',
        'created_by',
        'updated_by',
    ];
}
