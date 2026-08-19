<?php

namespace App\Models;

class AssessmentAttemptModel extends EducationFoundationModel
{
    protected $table = 'assessment_attempts';
    protected $allowedFields = [
        'uuid', 'assessment_id', 'student_id', 'classroom_id', 'score',
        'is_complete', 'submitted_at', 'revision_number', 'created_by', 'updated_by',
    ];
}