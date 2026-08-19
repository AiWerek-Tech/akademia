<?php

namespace App\Models;

class LearningSessionModel extends EducationFoundationModel
{
    protected $table = 'learning_sessions';
    protected $allowedFields = [
        'uuid', 'academic_period_id', 'unit_id', 'teacher_id', 'classroom_id',
        'subject_id', 'grade_level_id', 'schedule_entry_id', 'lesson_plan_id',
        'attendance_session_id', 'session_date', 'meeting_number', 'jp_count',
        'start_time', 'end_time', 'actual_start_time', 'actual_end_time',
        'topic', 'learning_objective_summary', 'misconception_warnings',
        'deviation_notes', 'status', 'revision_number',
        'started_at', 'completed_at', 'reflected_at',
        'created_by', 'updated_by',
    ];
}
