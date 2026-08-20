<?php

namespace App\Models;

class MasteryRecordModel extends EducationFoundationModel
{
    protected $table = 'mastery_records';
    protected $allowedFields = [
        'uuid', 'student_id', 'learning_objective_id', 'criterion_id',
        'evidence_id', 'source_attempt_id', 'result', 'source', 'confidence',
        'notes', 'version', 'created_by', 'updated_by',
    ];
}