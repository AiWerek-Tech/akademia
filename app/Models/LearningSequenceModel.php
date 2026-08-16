<?php
namespace App\Models;
class LearningSequenceModel extends EducationFoundationModel
{
    protected $table = 'learning_sequences_atp';
    protected $allowedFields = ['uuid','curriculum_version_id','unit_id','subject_id','grade_level_id','parent_sequence_id','code','name','phase','description','workflow_status','revision_number','validated_by','validated_at','reviewed_by','reviewed_at','approved_by','approved_at','locked_by','locked_at','archived_by','archived_at','created_by','updated_by'];
}

