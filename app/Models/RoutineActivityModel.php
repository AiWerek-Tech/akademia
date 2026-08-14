<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Services\UuidService;

class RoutineActivityModel extends Model
{
    protected $table            = 'school_routine_activities';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid', 'unit_id', 'code', 'name', 'short_name',
        'activity_type', 'default_duration_jp', 'duration_mode', 'duration_minutes', 'color_label',
        'is_locked_slot', 'default_day', 'default_period_number', 'locked_period_start', 'locked_period_end',
        'placement_zone', 'placement_sequence',
        'counts_as_teaching_load', 'assignment_role_default', 'assignment_strategy', 'specific_teacher_id',
        'notes', 'is_active', 'created_by', 'updated_by'
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
