<?php

namespace App\Models;

use CodeIgniter\Model;

class AssignmentRevisionHistoryModel extends Model
{
    protected $table            = 'assignment_revision_history';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'assignment_version_id',
        'entity_type',
        'entity_id',
        'revision_number',
        'action',
        'before_json',
        'after_json',
        'change_reason',
        'actor_id',
        'created_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
}
