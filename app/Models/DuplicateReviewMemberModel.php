<?php

namespace App\Models;

use CodeIgniter\Model;

class DuplicateReviewMemberModel extends Model
{
    protected $table            = 'duplicate_review_members';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'group_id', 'source_type', 'source_reference', 'entity_id', 'snapshot_json'
    ];

    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
}
