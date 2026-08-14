<?php

namespace App\Models;

use CodeIgniter\Model;

class AttendanceSessionModel extends Model
{
    protected $table            = 'attendance_sessions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'unit_id',
        'academic_period_id',
        'classroom_id',
        'subject_id',
        'teacher_id',
        'schedule_entry_id',
        'session_type',
        'routine_code',
        'source_type',
        'source_key',
        'attendance_date',
        'meeting_number',
        'start_time',
        'end_time',
        'topic',
        'teaching_summary',
        'learning_objectives',
        'learning_activity',
        'assessment_summary',
        'follow_up',
        'status',
        'revision_number',
        'submitted_at',
        'submitted_by',
        'verified_at',
        'verified_by',
        'locked_at',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
