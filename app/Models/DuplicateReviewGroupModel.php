<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Services\UuidService;

class DuplicateReviewGroupModel extends Model
{
    protected $table            = 'duplicate_review_groups';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid', 'entity_type', 'status', 'confidence_score', 'match_reasons_json',
        'decision', 'canonical_entity_id', 'decision_reason',
        'reviewed_by', 'reviewed_at'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $beforeInsert = ['generateUuid'];

    protected function generateUuid(array $data)
    {
        if (!isset($data['data']['uuid'])) {
            $data['data']['uuid'] = UuidService::v4();
        }
        return $data;
    }
}
