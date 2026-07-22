<?php

namespace App\Models;

use CodeIgniter\Model;

class ScheduleImportBatchModel extends Model
{
    protected $table            = 'schedule_import_batches';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'schedule_version_id',
        'file_name',
        'total_rows',
        'valid_rows',
        'error_rows',
        'status',
        'applied_at',
        'applied_by',
        'created_by',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function findByUuid(string $uuid): ?array
    {
        return $this->where('uuid', $uuid)->first();
    }
}
