<?php

namespace App\Models;

use CodeIgniter\Model;

class CurriculumImportBatchModel extends Model
{
    protected $table            = 'curriculum_import_batches';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'curriculum_version_id',
        'source_filename',
        'source_hash',
        'source_mime',
        'source_size',
        'status',
        'total_rows',
        'valid_rows',
        'warning_rows',
        'error_rows',
        'applied_rows',
        'created_by',
        'created_at',
        'applied_by',
        'applied_at',
        'rolled_back_by',
        'rolled_back_at',
        'notes',
    ];

    // Dates
    protected $useTimestamps = false;
}
