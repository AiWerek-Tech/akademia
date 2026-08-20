<?php

namespace App\Models;

class AssessmentCriterionModel extends EducationFoundationModel
{
    protected $table = 'assessment_criteria';
    protected $allowedFields = [
        'uuid', 'assessment_id', 'learning_objective_id', 'criterion',
        'weight', 'sequence_order', 'rubric_levels_json', 'created_by', 'updated_by',
    ];
}