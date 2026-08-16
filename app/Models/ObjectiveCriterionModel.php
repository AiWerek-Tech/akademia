<?php
namespace App\Models;
class ObjectiveCriterionModel extends EducationFoundationModel
{
    protected $table = 'objective_criteria';
    protected $allowedFields = ['uuid','learning_objective_id','description','sort_order','created_by','updated_by'];
}

