<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Services\UuidService;

class TeacherModel extends Model
{
    protected $table            = 'teachers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid', 'employee_number', 'nip', 'nik', 'full_name', 'normalized_name',
        'title_prefix', 'degree_suffix', 'teacher_initial', 'color_code', 'gender', 'birth_place', 'birth_date',
        'phone', 'email', 'address', 'employment_status', 'employment_type',
        'hire_date', 'termination_date', 'primary_unit_id', 'photo_path',
        'notes', 'is_active', 'profile_status', 'revision_number',
        'created_by', 'updated_by'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $beforeInsert = ['generateUuid', 'deriveSchedulePresentation'];
    protected $beforeUpdate = ['deriveSchedulePresentation'];

    protected function generateUuid(array $data)
    {
        if (!isset($data['data']['uuid'])) {
            $data['data']['uuid'] = UuidService::v4();
        }
        return $data;
    }

    protected function deriveSchedulePresentation(array $event): array
    {
        $row = $event['data'] ?? [];
        if (! isset($row['full_name']) || trim((string) $row['full_name']) === '') {
            return $event;
        }

        $parts = preg_split('/\s+/u', trim((string) $row['full_name']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (! array_key_exists('teacher_initial', $row) || trim((string) $row['teacher_initial']) === '') {
            $event['data']['teacher_initial'] = mb_strtoupper(
                mb_substr((string) ($parts[0] ?? ''), 0, 1)
                . mb_substr((string) ($parts[1] ?? ''), 0, 1)
            );
        }

        if (! array_key_exists('color_code', $row) || trim((string) $row['color_code']) === '') {
            $palette = ['#DBEAFE', '#DCFCE7', '#FEF3C7', '#FCE7F3', '#EDE9FE', '#CFFAFE', '#FFEDD5', '#E0E7FF'];
            $event['data']['color_code'] = $palette[(int) sprintf('%u', crc32(mb_strtolower(trim((string) $row['full_name'])))) % count($palette)];
        }

        return $event;
    }
}
