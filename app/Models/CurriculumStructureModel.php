<?php

namespace App\Models;

use CodeIgniter\Model;

class CurriculumStructureModel extends Model
{
    protected $table            = 'curriculum_structures';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'curriculum_version_id',
        'unit_id',
        'grade_level_id',
        'classroom_id',
        'subject_id',
        'official_weekly_hours',
        'custom_weekly_hours',
        'manual_weekly_hours',
        'effective_weekly_hours',
        'effective_source',
        'category',
        'block_pattern_json',
        'minimum_days',
        'maximum_daily_hours',
        'counts_in_report',
        'counts_as_teaching_load',
        'required_room_type_id',
        'schedule_priority',
        'adjustment_reason',
        'legal_reference',
        'notes',
        'status',
        'revision_number',
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'updated_by',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
