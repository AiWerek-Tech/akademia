<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Services\UuidService;

class AcademicPeriodModel extends Model
{
    protected $table            = 'academic_periods';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid', 'academic_year_id', 'semester_number', 'name', 'start_date', 'end_date',
        'workflow_status', 'is_active', 'approved_by', 'approved_at', 'locked_by', 'locked_at',
        'revision_number', 'notes', 'created_by', 'updated_by'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $beforeInsert = ['generateUuid'];

    protected function generateUuid(array $data)
    {
        if (!isset($data['data']['uuid'])) {
            $data['data']['uuid'] = UuidService::v4();
        }
        return $data;
    }
}
