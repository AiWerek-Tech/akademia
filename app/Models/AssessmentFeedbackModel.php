<?php

namespace App\Models;

class AssessmentFeedbackModel extends EducationFoundationModel
{
    protected $table = 'assessment_feedback';
    protected $allowedFields = [
        'uuid', 'attempt_id', 'student_id', 'content', 'feedback_type',
        'created_by', 'updated_by',
    ];
}