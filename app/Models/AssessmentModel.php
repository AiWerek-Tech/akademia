<?php

namespace App\Models;

class AssessmentModel extends EducationFoundationModel
{
    protected $table = 'assessments';
    protected $allowedFields = [
        'uuid', 'unit_id', 'academic_period_id', 'classroom_id', 'subject_id',
        'teacher_id', 'lesson_plan_assessment_id', 'learning_session_id',
        'title', 'assessment_type', 'assessment_form', 'assessment_date',
        'status', 'max_score', 'rubric_json', 'description', 'revision_number',
        'published_at', 'closed_at', 'created_by', 'updated_by',
    ];
}