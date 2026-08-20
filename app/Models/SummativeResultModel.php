<?php

namespace App\Models;

class SummativeResultModel extends EducationFoundationModel
{
    protected $table = 'summative_results';
    protected $allowedFields = [
        'uuid', 'unit_id', 'academic_period_id', 'subject_id', 'student_id',
        'reporting_policy_id', 'calculation_method', 'raw_score', 'grade_label',
        'detail_json', 'status', 'validated_by', 'validated_at',
        'created_by', 'updated_by',
    ];
}