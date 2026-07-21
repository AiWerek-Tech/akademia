<?php

namespace App\Models;

use CodeIgniter\Model;

class MasterImportRowModel extends Model
{
    protected $table            = 'master_import_rows';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'batch_id', 'row_number', 'entity_type', 'raw_data_json',
        'normalized_data_json', 'proposed_action', 'target_entity_id',
        'validation_status', 'validation_messages_json',
        'admin_decision', 'decision_reason'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
