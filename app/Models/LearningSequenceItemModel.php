<?php
namespace App\Models;
class LearningSequenceItemModel extends EducationFoundationModel
{
    protected $table = 'learning_sequence_items';
    protected $allowedFields = ['uuid','learning_sequence_id','learning_objective_id','sort_order','estimated_hours','notes','created_by','updated_by'];
}

