<?php

namespace App\Models;

use CodeIgniter\Model;

class AcademicCalendarEventModel extends Model
{
    protected $table            = 'academic_calendar_events';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'calendar_id', 'title', 'start_date', 'end_date',
        'category', 'notes',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
