<?php

namespace App\Models;

class InterventionModel extends EducationFoundationModel
{
    protected $table = 'interventions';
    protected $allowedFields = [
        'uuid', 'student_id', 'learning_objective_id', 'trigger_evidence_id',
        'intervention_type', 'planned_activity', 'scheduled_at', 'status',
        'outcome', 'completed_at', 'created_by', 'updated_by',
    ];
}