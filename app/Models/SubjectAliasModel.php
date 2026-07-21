<?php

namespace App\Models;

use CodeIgniter\Model;

class SubjectAliasModel extends Model
{
    protected $table            = 'subject_aliases';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'subject_id', 'alias_code', 'alias_name', 'normalized_alias',
        'source', 'unit_id', 'is_active'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
