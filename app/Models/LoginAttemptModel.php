<?php

namespace App\Models;

use CodeIgniter\Model;

class LoginAttemptModel extends Model
{
    protected $table            = 'login_attempts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'username', 'user_id', 'ip_address', 'user_agent', 'successful', 'failure_reason', 'attempted_at'
    ];

    protected $useTimestamps = false;
}
