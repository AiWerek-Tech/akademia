<?php

namespace App\Models;

use CodeIgniter\Model;

class SubjectUnitAvailabilityModel extends Model
{
    protected $table            = 'subject_unit_availability';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'subject_id', 'unit_id', 'is_available', 'default_category',
        'report_name_override', 'sort_order', 'notes',
        'created_by', 'updated_by'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
