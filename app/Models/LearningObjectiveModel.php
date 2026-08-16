<?php
namespace App\Models;
class LearningObjectiveModel extends EducationFoundationModel
{
    protected $table = 'learning_objectives_tp';
    protected $allowedFields = ['uuid','learning_outcome_id','curriculum_element_id','unit_id','parent_objective_id','source_level','code','statement','rationale','status','revision_number','created_by','updated_by'];
}

