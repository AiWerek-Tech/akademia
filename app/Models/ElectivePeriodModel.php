<?php

namespace App\Models;

use App\Services\UuidService;
use CodeIgniter\Model;

class ElectivePeriodModel extends Model
{
    protected $table = 'elective_periods';
    protected $returnType = 'array';
    protected $allowedFields = [
        'uuid', 'unit_id', 'academic_year_id', 'curriculum_version_id', 'title', 'selection_type', 'source_grade', 'target_grade',
        'selection_start_at', 'selection_end_at', 'min_primary_choices', 'max_primary_choices',
        'max_backup_choices', 'minimum_subjects_offered', 'allow_changes', 'change_deadline',
        'status', 'notes', 'revision_number', 'created_by', 'updated_by',
    ];
    protected $useTimestamps = true;
    protected $beforeInsert = ['generateUuid'];

    protected function generateUuid(array $data): array
    {
        $data['data']['uuid'] ??= UuidService::v4();
        return $data;
    }
}
