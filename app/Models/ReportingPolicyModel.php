<?php

namespace App\Models;

class ReportingPolicyModel extends EducationFoundationModel
{
    protected $table = 'reporting_policies';
    protected $allowedFields = [
        'uuid', 'unit_id', 'academic_period_id', 'subject_id', 'policy_name',
        'calculation_method', 'config_json', 'is_active', 'version',
        'created_by', 'updated_by',
    ];
}