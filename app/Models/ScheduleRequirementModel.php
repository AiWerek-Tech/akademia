<?php

namespace App\Models;

use CodeIgniter\Model;

class ScheduleRequirementModel extends Model
{
    protected $table            = 'schedule_requirements';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'schedule_version_id',
        'teaching_assignment_group_id',
        'teaching_assignment_id',
        'classroom_id',
        'subject_id',
        'teacher_id',
        'second_teacher_id',
        'required_weekly_hours',
        'consecutive_slots_required',
        'preferred_room_id',
        'required_room_type',
        'is_team_teaching',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function findByUuid(string $uuid): ?array
    {
        return $this->where('uuid', $uuid)->first();
    }
}
