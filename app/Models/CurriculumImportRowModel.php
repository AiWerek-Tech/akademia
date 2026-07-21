<?php

namespace App\Models;

use CodeIgniter\Model;

class CurriculumImportRowModel extends Model
{
    protected $table            = 'curriculum_import_rows';
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
        'mapped_unit_id',
        'mapped_grade_level_id',
        'mapped_classroom_id',
        'mapped_subject_id',
        'official_hours',
        'custom_hours',
        'manual_hours',
        'effective_source',
        'category',
        'block_pattern_json',
        'proposed_action',
        'validation_status',
        'validation_messages_json',
        'admin_decision',
        'decision_reason',
        'created_at',
        'updated_at',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
