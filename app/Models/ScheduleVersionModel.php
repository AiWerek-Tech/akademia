<?php

namespace App\Models;

use CodeIgniter\Model;

class ScheduleVersionModel extends Model
{
    protected $table            = 'schedule_versions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'academic_period_id',
        'curriculum_version_id',
        'assignment_version_id',
        'code',
        'name',
        'description',
        'workflow_status',
        'revision_number',
        'is_active',
        'previous_version_id',
        'change_summary',
        'validated_by',
        'validated_at',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'locked_by',
        'locked_at',
        'archived_by',
        'archived_at',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function findByUuid(string $uuid): ?array
    {
        return $this->where('uuid', $uuid)->first();
    }
}
