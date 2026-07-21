<?php

namespace App\Models;

use CodeIgniter\Model;

class CurriculumValidationResultModel extends Model
{
    protected $table            = 'curriculum_validation_results';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'curriculum_version_id',
        'curriculum_structure_id',
        'validation_code',
        'severity',
        'message',
        'details_json',
        'is_resolved',
        'resolved_by',
        'resolved_at',
        'created_at',
    ];

    // Dates
    protected $useTimestamps = false; // Manually set created_at
}
