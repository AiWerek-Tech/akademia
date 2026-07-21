<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Services\UuidService;

class MasterImportBatchModel extends Model
{
    protected $table            = 'master_import_batches';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid', 'import_type', 'source_filename', 'source_hash', 'source_mime',
        'source_size', 'status', 'total_rows', 'valid_rows', 'warning_rows',
        'error_rows', 'applied_rows', 'created_by', 'applied_by', 'applied_at',
        'rolled_back_by', 'rolled_back_at', 'notes'
    ];

    protected $useTimestamps = false;
    protected $createdField  = 'created_at';

    protected $beforeInsert = ['generateUuid'];

    protected function generateUuid(array $data)
    {
        if (!isset($data['data']['uuid'])) {
            $data['data']['uuid'] = UuidService::v4();
        }
        if (!isset($data['data']['created_at'])) {
            $data['data']['created_at'] = date('Y-m-d H:i:s');
        }
        return $data;
    }
}
