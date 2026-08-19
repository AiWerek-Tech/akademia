<?php

namespace App\Models;

class ActivityResourceModel extends EducationFoundationModel
{
    protected $table = 'lesson_plan_activity_resources';
    protected $allowedFields = [
        'uuid', 'lesson_plan_activity_id', 'learning_resource_id',
        'custom_description', 'quantity', 'is_required',
        'created_by', 'updated_by',
    ];
}
