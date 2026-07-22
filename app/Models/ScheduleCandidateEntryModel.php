<?php

namespace App\Models;

use CodeIgniter\Model;

class ScheduleCandidateEntryModel extends Model
{
    protected $table            = 'schedule_candidate_entries';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'candidate_id',
        'day_slot_id',
        'schedule_requirement_id',
        'classroom_id',
        'teacher_id',
        'second_teacher_id',
        'subject_id',
        'room_id',
        'score_contribution',
        'created_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
}
