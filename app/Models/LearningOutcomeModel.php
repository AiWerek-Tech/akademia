<?php
namespace App\Models;
class LearningOutcomeModel extends EducationFoundationModel
{
    protected $table = 'learning_outcomes_cp';
    protected $allowedFields = ['uuid','curriculum_version_id','curriculum_source_id','subject_id','grade_level_id','code','phase','statement','status','revision_number','created_by','updated_by'];
}

