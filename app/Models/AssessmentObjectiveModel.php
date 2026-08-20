<?php

namespace App\Models;

class AssessmentObjectiveModel extends EducationFoundationModel
{
    protected $table = 'assessment_objectives';
    protected $allowedFields = [
        'uuid', 'assessment_id', 'learning_objective_id', 'sequence_order',
        'created_by', 'updated_by',
    ];
}