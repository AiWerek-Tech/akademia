<?php

namespace App\Models;

use CodeIgniter\Model;

class CurriculumVersionModel extends Model
{
    protected $table            = 'curriculum_versions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'academic_period_id',
        'code',
        'name',
        'description',
        'source_reference',
        'revision_number',
        'workflow_status',
        'is_active',
        'validated_by',
        'validated_at',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'locked_by',
        'locked_at',
        'archived_by',
        'archived_at',
        'previous_version_id',
        'change_summary',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
