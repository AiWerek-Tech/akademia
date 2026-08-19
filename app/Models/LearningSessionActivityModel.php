<?php

namespace App\Models;

class LearningSessionActivityModel extends EducationFoundationModel
{
    protected $table = 'learning_session_activities';
    protected $allowedFields = [
        'uuid', 'learning_session_id', 'lesson_plan_activity_id', 'lesson_plan_stage_id',
        'stage_type', 'title', 'description', 'sequence_order',
        'is_completed', 'completed_at', 'actual_minutes', 'notes',
        'created_by', 'updated_by',
    ];
}
