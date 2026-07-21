<?php

namespace App\Models;

use CodeIgniter\Model;

class UserUnitAccessModel extends Model
{
    protected $table            = 'user_unit_access';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id', 'unit_id', 'access_level', 'is_default', 'created_by'
    ];

    protected $useTimestamps = false;
}
