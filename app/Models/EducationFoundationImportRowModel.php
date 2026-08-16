<?php
namespace App\Models;
class EducationFoundationImportRowModel extends EducationFoundationModel
{
    protected $table = 'education_foundation_import_rows';
    protected $allowedFields = ['uuid','import_batch_id','row_number','entity_type','payload_json','status','errors_json','warnings_json','target_entity_id','created_by','updated_by'];
}
