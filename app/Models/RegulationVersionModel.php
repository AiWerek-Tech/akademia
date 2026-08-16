<?php
namespace App\Models;
class RegulationVersionModel extends EducationFoundationModel
{
    protected $table = 'regulation_versions';
    protected $allowedFields = ['uuid','regulation_id','version_number','document_url','document_hash','mime_type','notes','is_published','published_at','created_by','updated_by'];
}

