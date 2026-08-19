<?php

namespace App\Models;

class AssessmentRubricModel extends EducationFoundationModel
{
    protected $table = 'lesson_plan_assessment_rubrics';
    protected $allowedFields = [
        'uuid', 'lesson_plan_assessment_id', 'criterion_description',
        'rubric_levels', 'sequence_order', 'created_by', 'updated_by',
    ];
}
