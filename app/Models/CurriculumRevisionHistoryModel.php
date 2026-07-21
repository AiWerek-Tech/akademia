<?php

namespace App\Models;

use CodeIgniter\Model;

class CurriculumRevisionHistoryModel extends Model
{
    protected $table            = 'curriculum_revision_history';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'curriculum_version_id',
        'structure_id',
        'revision_number',
        'action',
        'before_json',
        'after_json',
        'change_reason',
        'actor_id',
        'created_at',
    ];

    // Dates
    protected $useTimestamps = false;
}
