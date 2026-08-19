<?php

namespace App\Models;

class AssessmentItemModel extends EducationFoundationModel
{
    protected $table = 'assessment_items';
    protected $allowedFields = [
        'uuid', 'assessment_id', 'item_type', 'prompt', 'answer_key',
        'max_score', 'sequence_order', 'created_by', 'updated_by',
    ];
}