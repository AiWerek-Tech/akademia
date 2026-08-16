<?php
namespace App\Models;
class SubjectLearningPackModel extends EducationFoundationModel
{
    protected $table = 'subject_learning_packs';
    protected $allowedFields = ['uuid','curriculum_version_id','unit_id','subject_id','grade_level_id','code','name','description','status','revision_number','created_by','updated_by'];
}

