<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherIdentifierModel extends Model
{
    protected $table            = 'teacher_identifiers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'teacher_id', 'identifier_type', 'identifier_value',
        'issuing_authority', 'is_primary', 'is_verified'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
