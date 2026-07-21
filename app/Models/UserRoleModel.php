<?php

namespace App\Models;

use CodeIgniter\Model;

class UserRoleModel extends Model
{
    protected $table            = 'user_roles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id', 'role_id', 'unit_id', 'valid_from', 'valid_until', 'assigned_by'
    ];

    protected $useTimestamps = false; // Manually track created_at
}
