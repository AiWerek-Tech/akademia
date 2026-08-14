<?php

namespace App\Models;

use App\Services\UuidService;
use CodeIgniter\Model;

class ElectiveOfferingModel extends Model
{
    protected $table = 'elective_offerings';
    protected $returnType = 'array';
    protected $allowedFields = [
        'uuid', 'elective_period_id', 'subject_id', 'teacher_id', 'minimum_students',
        'maximum_students', 'weekly_hours', 'description', 'study_relevance',
        'prerequisites', 'is_open', 'is_approved', 'approved_at', 'approved_by', 'revision_number', 'created_by', 'updated_by',
    ];
    protected $useTimestamps = true;
    protected $beforeInsert = ['generateUuid'];

    protected function generateUuid(array $data): array
    {
        $data['data']['uuid'] ??= UuidService::v4();
        return $data;
    }
}
