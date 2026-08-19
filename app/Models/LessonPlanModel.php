<?php

namespace App\Models;

class LessonPlanModel extends EducationFoundationModel
{
    protected $table = 'lesson_plans';
    protected $allowedFields = [
        'uuid', 'academic_period_id', 'unit_id', 'subject_id', 'grade_level_id',
        'class_id', 'teacher_id', 'schedule_entry_id', 'learning_pack_id', 'learning_unit_id',
        'date', 'session_number', 'session_label', 'source_type', 'source_locator', 'parent_plan_id',
        'status', 'revision_number',
        'identification_notes', 'learner_readiness', 'material_characteristics',
        'pedagogical_practice', 'learning_partnership', 'learning_environment',
        'digital_utilization', 'interdisciplinary_notes', 'graduate_profile_dimensions',
        'validated_by', 'validated_at', 'approved_by', 'approved_at',
        'created_by', 'updated_by',
    ];
}
