<?php

namespace App\Models;

class LearningSessionReflectionModel extends EducationFoundationModel
{
    protected $table = 'learning_session_reflections';
    protected $allowedFields = [
        'uuid', 'learning_session_id', 'what_went_well', 'challenges',
        'student_engagement', 'objective_achievement', 'tp_coverage_notes',
        'follow_up_plan', 'next_session_notes', 'self_rating',
        'created_by', 'updated_by',
    ];
}
