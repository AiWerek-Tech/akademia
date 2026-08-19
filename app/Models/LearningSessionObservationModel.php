<?php

namespace App\Models;

class LearningSessionObservationModel extends EducationFoundationModel
{
    protected $table = 'learning_session_observations';
    protected $allowedFields = [
        'uuid', 'learning_session_id', 'student_id', 'observation_type',
        'rating', 'notes', 'misconception_found', 'misconception_detail',
        'follow_up_needed', 'created_by', 'updated_by',
    ];
}
