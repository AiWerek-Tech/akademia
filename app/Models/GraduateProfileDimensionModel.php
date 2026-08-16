<?php
namespace App\Models;
class GraduateProfileDimensionModel extends EducationFoundationModel
{
    protected $table = 'graduate_profile_dimensions';
    protected $allowedFields = ['uuid','code','name','description','sort_order','is_active','created_by','updated_by'];
}

