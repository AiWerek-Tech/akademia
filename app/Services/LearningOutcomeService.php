<?php

namespace App\Services;

use App\Models\CurriculumElementModel;
use App\Models\LearningOutcomeModel;
use Config\Database;
use RuntimeException;

class LearningOutcomeService
{
    public static function create(array $data): array
    {
        EducationFoundationService::requireFields($data, ['curriculum_version_id','subject_id','grade_level_id','code','phase','statement']);
        $record = array_intersect_key($data, array_flip(['curriculum_version_id','curriculum_source_id','subject_id','grade_level_id','code','phase','statement','status'])) + [
            'uuid' => UuidService::v4(), 'revision_number' => 1, 'created_by' => EducationFoundationService::actorId(),
        ];
        $record['code'] = strtoupper(trim($record['code']));
        $record['phase'] = strtoupper(trim($record['phase']));
        $record['status'] = strtoupper($record['status'] ?? 'DRAFT');
        $id = (new LearningOutcomeModel())->insert($record, true);
        AuditService::log('education_foundation', 'CREATE_CP', 'LearningOutcome', (int) $id, null, $record);
        return (new LearningOutcomeModel())->find($id);
    }

    public static function update(string $uuid, array $data): array
    {
        $current = EducationFoundationService::byUuid('learning_outcomes_cp', $uuid);
        if (in_array($current['status'], ['PUBLISHED','LOCKED','ARCHIVED'], true)) {
            throw new RuntimeException('CP resmi yang telah diterbitkan tidak dapat diubah langsung.');
        }
        $allowed = array_intersect_key($data, array_flip(['statement','phase','status','curriculum_source_id','revision_number']));
        $updated = EducationFoundationService::atomicUpdate('learning_outcomes_cp', $current, $allowed);
        AuditService::log('education_foundation', 'UPDATE_CP', 'LearningOutcome', (int) $current['id'], $current, $updated);
        return $updated;
    }

    public static function addElement(string $outcomeUuid, array $data): array
    {
        EducationFoundationService::requireFields($data, ['code','name','sort_order']);
        $outcome = EducationFoundationService::byUuid('learning_outcomes_cp', $outcomeUuid);
        if (in_array($outcome['status'], ['PUBLISHED','LOCKED','ARCHIVED'], true)) {
            throw new RuntimeException('Elemen CP resmi yang telah diterbitkan tidak dapat diubah.');
        }
        $record = ['uuid' => UuidService::v4(), 'learning_outcome_id' => $outcome['id'], 'code' => strtoupper(trim($data['code'])),
            'name' => trim($data['name']), 'description' => $data['description'] ?? null, 'sort_order' => (int) $data['sort_order'],
            'created_by' => EducationFoundationService::actorId()];
        $id = (new CurriculumElementModel())->insert($record, true);
        return (new CurriculumElementModel())->find($id);
    }

    public static function hierarchy(array $filters = []): array
    {
        $builder = Database::connect()->table('learning_outcomes_cp cp')
            ->select('cp.*, s.name subject_name, gl.name grade_name')->join('subjects s', 's.id = cp.subject_id')->join('grade_levels gl', 'gl.id = cp.grade_level_id');
        foreach (['curriculum_version_id','subject_id','grade_level_id'] as $field) {
            if (!empty($filters[$field])) $builder->where('cp.' . $field, (int) $filters[$field]);
        }
        $rows = $builder->orderBy('s.name')->orderBy('gl.sort_order')->orderBy('cp.code')->get()->getResultArray();
        foreach ($rows as &$row) {
            $row['elements'] = Database::connect()->table('curriculum_elements')->where('learning_outcome_id', $row['id'])->orderBy('sort_order')->get()->getResultArray();
        }
        return $rows;
    }
}

