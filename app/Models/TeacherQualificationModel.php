<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherQualificationModel extends Model
{
    protected $table            = 'teacher_qualifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'teacher_id', 'qualification_level', 'field_of_study', 'institution',
        'graduation_year', 'certificate_number', 'is_highest', 'document_path',
        'created_by', 'updated_by'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
