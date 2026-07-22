<?php

namespace App\Services;

use App\Models\GradeLevelModel;
use App\Models\ClassroomModel;
use Config\Database;

class GradeLevelService
{
    public static function createGradeLevel(array $data): array
    {
        $model = new GradeLevelModel();
        $code = strtoupper(trim((string) $data['code']));
        $duplicate = $model->where('unit_id', (int) $data['unit_id'])->where('code', $code)->first();
        if ($duplicate) {
            throw new \InvalidArgumentException('Kode tingkat ' . $code . ' sudah digunakan pada unit tersebut.');
        }

        $payload = [
            'unit_id'      => (int) $data['unit_id'],
            'grade_number' => (int) $data['grade_number'],
            'code'         => $code,
            'name'         => trim((string) $data['name']),
            'phase'        => strtoupper(trim((string) $data['phase'])),
            'sort_order'   => isset($data['sort_order']) && $data['sort_order'] !== '' ? (int) $data['sort_order'] : (int) $data['grade_number'],
            'is_active'    => isset($data['is_active']) ? (int) $data['is_active'] : 1,
            'created_by'   => session()->get('user_id'),
        ];

        $id = $model->insert($payload, true);
        if (!$id) {
            throw new \RuntimeException('Tingkat kelas gagal disimpan: ' . implode('; ', $model->errors()));
        }
        $created = $model->find($id);
        AuditService::log('grade_levels', 'CREATE', 'GradeLevel', $id, null, $created, 'Create grade level');
        return $created;
    }

    /**
     * Get grade levels scoped by unit
     */
    public static function getGradeLevels(?int $unitId = null, array $unitIds = []): array
    {
        $model = new GradeLevelModel();
        $builder = $model->select('grade_levels.*, school_units.name as unit_name, school_units.code as unit_code')
            ->join('school_units', 'school_units.id = grade_levels.unit_id');

        if ($unitId !== null) {
            $builder->where('grade_levels.unit_id', $unitId);
        } elseif ($unitIds !== []) {
            $builder->whereIn('grade_levels.unit_id', $unitIds);
        }

        return $builder->orderBy('school_units.id', 'ASC')
            ->orderBy('grade_levels.sort_order', 'ASC')
            ->orderBy('grade_levels.grade_number', 'ASC')
            ->findAll();
    }

    /**
     * Update grade level (name, phase, sort_order, is_active). Unit ID is immutable after classroom use.
     */
    public static function updateGradeLevel(string $uuid, array $data): array
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $model = new GradeLevelModel();
            $existing = $model->where('uuid', $uuid)->first();

            if (!$existing) {
                throw new \RuntimeException('Tingkat kelas tidak ditemukan.');
            }

            // Unit immutability check: if unit_id in request differs and classrooms exist
            if (isset($data['unit_id']) && (int)$data['unit_id'] !== (int)$existing['unit_id']) {
                $classroomModel = new ClassroomModel();
                $usageCount = $classroomModel->where('grade_level_id', $existing['id'])->countAllResults();
                if ($usageCount > 0) {
                    throw new \InvalidArgumentException('Unit sekolah pada tingkat kelas ini tidak dapat diubah karena telah digunakan oleh ' . $usageCount . ' kelas/rombel.');
                }
            }

            $updatePayload = [
                'name'       => $data['name'] ?? $existing['name'],
                'phase'      => $data['phase'] ?? $existing['phase'],
                'sort_order' => isset($data['sort_order']) ? (int)$data['sort_order'] : $existing['sort_order'],
                'is_active'  => isset($data['is_active']) ? (int)$data['is_active'] : $existing['is_active'],
                'updated_by' => session()->get('user_id'),
            ];

            $model->update($existing['id'], $updatePayload);
            $updated = $model->find($existing['id']);

            AuditService::log('grade_levels', 'UPDATE', 'GradeLevel', $existing['id'], $existing, $updated, 'Update grade level');

            $db->transCommit();
            return $updated;
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Update grade level failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
