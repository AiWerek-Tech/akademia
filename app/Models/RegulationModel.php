<?php
namespace App\Models;
class RegulationModel extends EducationFoundationModel
{
    protected $table = 'regulations';
    protected $allowedFields = ['uuid','code','title','authority','regulation_type','status','published_at','effective_from','effective_until','description','created_by','updated_by'];
}

