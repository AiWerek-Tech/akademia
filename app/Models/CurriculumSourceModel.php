<?php
namespace App\Models;
class CurriculumSourceModel extends EducationFoundationModel
{
    protected $table = 'curriculum_sources';
    protected $allowedFields = ['uuid','regulation_version_id','code','title','source_type','issuer','source_url','source_hash','published_at','status','metadata_json','created_by','updated_by'];
}

