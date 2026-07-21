<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Services\UuidService;

class TeacherModel extends Model
{
    protected $table            = 'teachers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid', 'employee_number', 'nip', 'nik', 'full_name', 'normalized_name',
        'title_prefix', 'degree_suffix', 'gender', 'birth_place', 'birth_date',
        'phone', 'email', 'address', 'employment_status', 'employment_type',
        'hire_date', 'termination_date', 'primary_unit_id', 'photo_path',
        'notes', 'is_active', 'profile_status', 'revision_number',
        'created_by', 'updated_by'
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
