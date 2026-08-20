<?php

namespace App\Models;

class CriterionResultModel extends EducationFoundationModel
{
    protected $table = 'criterion_results';
    protected $allowedFields = [
        'uuid', 'attempt_id', 'criterion_id', 'level_index', 'score',
        'status', 'notes', 'created_by', 'updated_by',
    ];
}