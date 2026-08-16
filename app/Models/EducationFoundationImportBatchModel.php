<?php
namespace App\Models;
class EducationFoundationImportBatchModel extends EducationFoundationModel
{
    protected $table = 'education_foundation_import_batches';
    protected $allowedFields = ['uuid','unit_id','curriculum_version_id','source_filename','source_hash','status','total_rows','valid_rows','error_rows','applied_rows','notes','applied_by','applied_at','created_by','updated_by'];
}

