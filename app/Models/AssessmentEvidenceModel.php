<?php

namespace App\Models;

class AssessmentEvidenceModel extends EducationFoundationModel
{
    protected $table = 'assessment_evidence';
    protected $allowedFields = [
        'uuid', 'student_id', 'attempt_id', 'learning_objective_id',
        'criterion_id', 'profile_dimension_id', 'cocurricular_objective_id',
        'evidence_type', 'title', 'content', 'file_path',
        'meta_json', 'captured_at', 'created_by', 'updated_by',
    ];
}