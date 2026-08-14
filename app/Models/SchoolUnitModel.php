<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Services\UuidService;

class SchoolUnitModel extends Model
{
    protected $table            = 'school_units';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid', 'code', 'name', 'short_name', 'level', 'npsn', 
        'address', 'phone', 'email', 'head_name', 'head_identifier',
        'document_city', 'decree_prefix', 'logo_path', 'header_line_1',
        'header_line_2', 'header_line_3', 'header_line_4', 'logo_right_path',
        'timezone', 'is_active', 'created_by', 'updated_by'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $beforeInsert = ['generateUuid'];

    protected function generateUuid(array $data)
    {
        if (!isset($data['data']['uuid'])) {
            $data['data']['uuid'] = UuidService::v4();
        }
        return $data;
    }
}
