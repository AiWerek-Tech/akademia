<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentAttendanceModel extends Model
{
    protected $table            = 'student_attendances';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'session_id',
        'student_id',
        'status',
        'arrival_time',
        'late_minutes',
        'notes',
        'source_session_id',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
