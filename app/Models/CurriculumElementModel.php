<?php
namespace App\Models;
class CurriculumElementModel extends EducationFoundationModel
{
    protected $table = 'curriculum_elements';
    protected $allowedFields = ['uuid','learning_outcome_id','code','name','description','sort_order','created_by','updated_by'];
}

