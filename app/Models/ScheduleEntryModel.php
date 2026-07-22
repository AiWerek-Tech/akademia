<?php

namespace App\Models;

use CodeIgniter\Model;

class ScheduleEntryModel extends Model
{
    protected $table            = 'schedule_entries';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'schedule_version_id',
        'day_slot_id',
        'schedule_requirement_id',
        'classroom_id',
        'teacher_id',
        'second_teacher_id',
        'subject_id',
        'room_id',
        'is_locked',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
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
