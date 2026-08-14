<?php

namespace App\Services;

use App\Models\SubjectModel;
use App\Models\SubjectAliasModel;
use App\Models\SubjectUnitAvailabilityModel;
use Config\Database;

class SubjectService
{
    /**
     * Categories constants for validation
     */
    public const CATEGORIES = [
        'WAJIB',
        'PILIHAN',
        'MUATAN_LOKAL',
        'KOKURIKULER',
        'EKSTRAKURIKULER',
        'OTHER',
    ];

    /**
     * Get subjects list with unit filter and aliases
     */
    public static function getSubjects(array $filters = [], int $perPage = 20): array
    {
        $subjectModel = new SubjectModel();
        $builder = $subjectModel->where('subjects.deleted_at IS NULL');

        if (!empty($filters['unit_id'])) {
            $builder->groupStart()
                ->whereIn('subjects.id', function ($sub) use ($filters) {
                    return $sub->select('subject_id')
                        ->from('subject_unit_availability')
                        ->where('unit_id', $filters['unit_id'])
                        ->where('is_available', 1);
                })
                ->groupEnd();
        } elseif (!empty($filters['unit_ids'])) {
            $builder->whereIn('subjects.id', function ($sub) use ($filters) {
                return $sub->select('subject_id')->from('subject_unit_availability')
                    ->whereIn('unit_id', $filters['unit_ids'])->where('is_available', 1);
            });
        }

        if (!empty($filters['category'])) {
            $builder->where('subjects.category', $filters['category']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $builder->where('subjects.is_active', (int)$filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $builder->groupStart()
                ->like('subjects.code', $search)
                ->orLike('subjects.name', $search)
                ->orLike('subjects.short_name', $search)
                ->groupEnd();
        }

        $subjects = $builder->orderBy('subjects.sort_order', 'ASC')
            ->orderBy('subjects.name', 'ASC')
            ->paginate($perPage);

        $aliasModel = new SubjectAliasModel();
        $availModel = new SubjectUnitAvailabilityModel();

        foreach ($subjects as &$s) {
            $s['aliases'] = $aliasModel->where('subject_id', $s['id'])->findAll();
            $availModel->select('subject_unit_availability.*, school_units.code as unit_code, school_units.name as unit_name')
                ->join('school_units', 'school_units.id = subject_unit_availability.unit_id')
                ->where('subject_id', $s['id']);
            if (session()->get('logged_in')) {
                $availModel->whereIn('subject_unit_availability.unit_id', UnitScopeService::accessibleUnitIds());
            }
            $s['unit_availabilities'] = $availModel->findAll();
        }

        return [
            'data'  => $subjects,
            'pager' => $subjectModel->pager,
        ];
    }

    /**
     * Create global subject with unit availability and aliases
     */
    public static function createSubject(array $data, array $unitIds = [], array $aliases = []): array
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $subjectModel = new SubjectModel();

            // Validate Category
            if (!in_array($data['category'] ?? '', self::CATEGORIES, true)) {
                throw new \InvalidArgumentException('Kategori mata pelajaran tidak valid.');
            }

            // Code Uniqueness Check
            $code = strtoupper(trim($data['code']));
            $existing = $subjectModel->where('code', $code)->where('deleted_at IS NULL')->first();
            if ($existing) {
                throw new \InvalidArgumentException('Kode mata pelajaran ' . $code . ' sudah digunakan.');
            }

            $data['code']            = $code;
            $data['normalized_name'] = TeacherDuplicateDetectionService::normalizeName($data['name']);
            $data['revision_number'] = 1;
            $data['is_active']       = isset($data['is_active']) ? (int)$data['is_active'] : 1;
            $data['created_by']      = session()->get('user_id');

            $subjectId = $subjectModel->insert($data);
            $subject   = $subjectModel->find($subjectId);

            // Unit availability
            $availModel = new SubjectUnitAvailabilityModel();
            foreach ($unitIds as $uId) {
                $availModel->insert([
                    'subject_id'   => $subjectId,
                    'unit_id'      => $uId,
                    'is_available' => 1,
                    'created_by'   => session()->get('user_id'),
                ]);
            }

            // Aliases
            $aliasModel = new SubjectAliasModel();
            foreach ($aliases as $aliasName) {
                $aliasNameTrim = trim($aliasName);
                if ($aliasNameTrim !== '') {
                    $aliasModel->insert([
                        'subject_id'       => $subjectId,
                        'alias_name'       => $aliasNameTrim,
                        'normalized_alias' => TeacherDuplicateDetectionService::normalizeName($aliasNameTrim),
                        'source'           => 'MANUAL',
                        'is_active'        => 1,
                    ]);
                }
            }

            AuditService::log('subjects', 'CREATE', 'Subject', $subjectId, null, $subject, 'Create global subject master');

            $db->transCommit();
            return $subject;
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Create subject failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update subject with Optimistic Locking check
     */
    public static function updateSubject(string $uuid, array $data, array $unitIds = [], array $aliases = []): array
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $subjectModel = new SubjectModel();
            $existing = $subjectModel->where('uuid', $uuid)->where('deleted_at IS NULL')->first();

            if (!$existing) {
                throw new \RuntimeException('Data mata pelajaran tidak ditemukan.');
            }

            // Optimistic locking
            if (isset($data['revision_number']) && (int)$data['revision_number'] !== (int)$existing['revision_number']) {
                throw new \RuntimeException('Stale Data Error: Mata pelajaran ini telah diperbarui oleh pengguna lain. Silakan muat ulang halaman.');
            }

            // Code uniqueness check
            if (!empty($data['code'])) {
                $code = strtoupper(trim($data['code']));
                if ($code !== $existing['code']) {
                    $dup = $subjectModel->where('code', $code)->where('id !=', $existing['id'])->where('deleted_at IS NULL')->first();
                    if ($dup) {
                        throw new \InvalidArgumentException('Kode mata pelajaran ' . $code . ' sudah digunakan.');
                    }
                    $data['code'] = $code;
                }
            }

            $data['normalized_name']        = TeacherDuplicateDetectionService::normalizeName($data['name'] ?? $existing['name']);
            $data['counts_in_report']       = isset($data['counts_in_report']) ? (int)$data['counts_in_report'] : 0;
            $data['counts_as_teaching_load'] = isset($data['counts_as_teaching_load']) ? (int)$data['counts_as_teaching_load'] : 0;
            $newRevision                    = ((int)$existing['revision_number']) + 1;

            $allowedFields = [
                'code', 'name', 'short_name', 'category', 'default_report_name',
                'counts_in_report', 'counts_as_teaching_load', 'normalized_name',
                'is_active'
            ];

            $updateData = array_intersect_key($data, array_flip($allowedFields));
            $updateData['revision_number'] = $newRevision;
            $updateData['updated_by']      = is_cli() ? 1 : (session()->has('user_id') ? session()->get('user_id') : null);
            $updateData['updated_at']      = date('Y-m-d H:i:s');

            $db->table('subjects')
                ->where('id', $existing['id'])
                ->where('revision_number', $existing['revision_number'])
                ->update($updateData);
            if ($db->affectedRows() !== 1) {
                throw new \RuntimeException('Data mata pelajaran telah diubah oleh pengguna lain. Silakan muat ulang halaman.');
            }

            // Sync availability
            $availModel = new SubjectUnitAvailabilityModel();
            $availModel->where('subject_id', $existing['id']);
            if (!is_cli() && session()->has('logged_in') && session()->get('logged_in')) {
                $availModel->whereIn('unit_id', UnitScopeService::accessibleUnitIds());
            }
            $availModel->delete();
            foreach ($unitIds as $uId) {
                $availModel->insert([
                    'subject_id'   => $existing['id'],
                    'unit_id'      => $uId,
                    'is_available' => 1,
                    'created_by'   => is_cli() ? 1 : (session()->has('user_id') ? session()->get('user_id') : null),
                ]);
            }

            // Sync aliases
            $aliasModel = new SubjectAliasModel();
            $aliasModel->where('subject_id', $existing['id'])->delete();
            foreach ($aliases as $aliasName) {
                $aliasNameTrim = trim($aliasName);
                if ($aliasNameTrim !== '') {
                    $aliasModel->insert([
                        'subject_id'       => $existing['id'],
                        'alias_name'       => $aliasNameTrim,
                        'normalized_alias' => TeacherDuplicateDetectionService::normalizeName($aliasNameTrim),
                        'source'           => 'MANUAL',
                        'is_active'        => 1,
                    ]);
                }
            }

            $updated = $subjectModel->find($existing['id']);
            AuditService::log('subjects', 'UPDATE', 'Subject', $existing['id'], $existing, $updated, 'Update subject master data');

            $db->transCommit();
            return $updated;
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Update subject failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
