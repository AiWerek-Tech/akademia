<?php
namespace App\Models;
class SubjectLearningPackModel extends EducationFoundationModel
{
    protected $table = 'subject_learning_packs';
    protected $allowedFields = [
        'uuid','curriculum_version_id','unit_id','subject_id','grade_level_id','phase','code','name','description',
        'source_type','source_id','source_locator','parent_pack_id','status','revision_number',
        'validated_by','validated_at','reviewed_by','reviewed_at','approved_by','approved_at','locked_by','locked_at',
        'created_by','updated_by',
    ];
}
