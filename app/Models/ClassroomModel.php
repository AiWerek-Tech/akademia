<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Services\UuidService;

class ClassroomModel extends Model
{
    protected $table            = 'classrooms';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid', 'academic_period_id', 'unit_id', 'grade_level_id', 'code',
        'name', 'major', 'specialization', 'capacity', 'homeroom_teacher_id',
        'default_room_id', 'status', 'is_active', 'revision_number',
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
