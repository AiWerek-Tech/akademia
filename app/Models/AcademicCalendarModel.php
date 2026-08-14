<?php

namespace App\Models;

use CodeIgniter\Model;

class AcademicCalendarModel extends Model
{
    protected $table            = 'academic_calendars';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid', 'academic_year_id', 'unit_id', 'unit_scope_key', 'profile_id', 'name',
        'dinas_reference_number', 'dinas_reference_date',
        'status', 'effective_week_min_days', 'working_days_json_snapshot',
        'working_day_source', 'operating_setting_revision',
        'target_hes_sem1', 'target_hes_sem2', 'target_heb_sem1', 'target_heb_sem2',
        'validation_status', 'validation_summary_json', 'generated_at',
        'total_hes_sem1', 'total_hes_sem2',
        'total_heb_sem1', 'total_heb_sem2',
        'total_effective_weeks_sem1', 'total_effective_weeks_sem2',
        'created_by', 'updated_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $beforeInsert = ['generateUuid'];

    protected function generateUuid(array $data): array
    {
        if (!isset($data['data']['uuid'])) {
            $data['data']['uuid'] = \App\Services\UuidService::v4();
        }
        return $data;
    }
}
