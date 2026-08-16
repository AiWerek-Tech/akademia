<?php

namespace App\Services;

use App\Models\LearningObjectiveModel;
use App\Models\ObjectiveCriterionModel;
use Config\Database;
use InvalidArgumentException;
use RuntimeException;

class LearningObjectiveService
{
    public static function createNational(array $data): array
    {
        $data['source_level'] = 'NATIONAL'; $data['unit_id'] = null; $data['parent_objective_id'] = null;
        return self::create($data);
    }

    public static function adapt(string $parentUuid, array $data): array
    {
        $parent = EducationFoundationService::byUuid('learning_objectives_tp', $parentUuid);
        $level = strtoupper($data['source_level'] ?? 'SCHOOL');
        if (!in_array($level, ['SCHOOL','TEACHER'], true)) throw new InvalidArgumentException('Level adaptasi harus SCHOOL atau TEACHER.');
        $unitId = UnitScopeService::resolveUnit($data['unit_id'] ?? null);
        if ($parent['unit_id'] !== null) {
            UnitScopeService::assertUnit((int) $parent['unit_id']);
            if ((int) $parent['unit_id'] !== $unitId) throw new InvalidArgumentException('Parent adaptasi berasal dari unit berbeda.');
        }
        if ($level === 'SCHOOL' && $parent['source_level'] !== 'NATIONAL') throw new InvalidArgumentException('Adaptasi sekolah harus diturunkan dari TP nasional.');
        return self::create([
            'learning_outcome_id' => $parent['learning_outcome_id'], 'curriculum_element_id' => $parent['curriculum_element_id'],
            'unit_id' => $unitId, 'parent_objective_id' => $parent['id'], 'source_level' => $level,
            'code' => $data['code'] ?? ($parent['code'] . '-' . $level), 'statement' => $data['statement'] ?? $parent['statement'],
            'rationale' => $data['rationale'] ?? null, 'status' => 'DRAFT',
        ]);
    }

    public static function create(array $data): array
    {
        EducationFoundationService::requireFields($data, ['learning_outcome_id','code','statement','source_level']);
        $level = strtoupper($data['source_level']);
        if (!in_array($level, ['NATIONAL','SCHOOL','TEACHER'], true)) throw new InvalidArgumentException('Source level TP tidak valid.');
        if ($level === 'NATIONAL' && (!empty($data['unit_id']) || !empty($data['parent_objective_id']))) throw new InvalidArgumentException('TP nasional tidak boleh memiliki unit atau parent.');
        if ($level !== 'NATIONAL' && (empty($data['unit_id']) || empty($data['parent_objective_id']))) throw new InvalidArgumentException('Adaptasi TP wajib memiliki unit dan parent.');
        if (!empty($data['unit_id'])) {
            UnitScopeService::assertUnit((int) $data['unit_id']);
            $parent=Database::connect()->table('learning_objectives_tp')->where('id',(int)$data['parent_objective_id'])->get()->getRowArray();
            if (!$parent) throw new InvalidArgumentException('Parent adaptasi TP tidak ditemukan.');
            if ($parent['unit_id']!==null) UnitScopeService::assertUnit((int)$parent['unit_id']);
            if ($parent['unit_id']!==null && (int)$parent['unit_id']!==(int)$data['unit_id']) throw new InvalidArgumentException('Parent adaptasi berasal dari unit berbeda.');
            if ($level==='SCHOOL' && $parent['source_level']!=='NATIONAL') throw new InvalidArgumentException('Adaptasi sekolah harus diturunkan dari TP nasional.');
        }
        $record = array_intersect_key($data, array_flip(['learning_outcome_id','curriculum_element_id','unit_id','parent_objective_id','source_level','code','statement','rationale','status'])) + [
            'uuid' => UuidService::v4(), 'revision_number' => 1, 'created_by' => EducationFoundationService::actorId(),
        ];
        $record['code'] = strtoupper(trim($record['code'])); $record['source_level'] = $level; $record['status'] = strtoupper($record['status'] ?? 'DRAFT');
        if ($record['status']==='PUBLISHED' && $level==='NATIONAL') {
            $cp=Database::connect()->table('learning_outcomes_cp')->where('id',$record['learning_outcome_id'])->get()->getRowArray();
            if (!$cp || empty($cp['curriculum_source_id'])) throw new InvalidArgumentException('TP nasional yang diterbitkan wajib berasal dari CP dengan sumber resmi.');
        }
        $id = (new LearningObjectiveModel())->insert($record, true);
        AuditService::log('education_foundation', 'CREATE_TP', 'LearningObjective', (int) $id, null, $record);
        return (new LearningObjectiveModel())->find($id);
    }

    public static function update(string $uuid, array $data): array
    {
        $current = EducationFoundationService::byUuid('learning_objectives_tp', $uuid);
        if ($current['source_level'] === 'NATIONAL' && in_array($current['status'], ['PUBLISHED','LOCKED','ARCHIVED'], true)) throw new RuntimeException('TP nasional terbit bersifat immutable; buat adaptasi.');
        if (!empty($current['unit_id'])) UnitScopeService::assertUnit((int) $current['unit_id']);
        $updated = EducationFoundationService::atomicUpdate('learning_objectives_tp', $current, array_intersect_key($data, array_flip(['statement','rationale','status','revision_number'])));
        AuditService::log('education_foundation', 'UPDATE_TP', 'LearningObjective', (int) $current['id'], $current, $updated);
        return $updated;
    }

    public static function addCriterion(string $objectiveUuid, array $data): array
    {
        EducationFoundationService::requireFields($data, ['description','sort_order']);
        $objective = EducationFoundationService::byUuid('learning_objectives_tp', $objectiveUuid);
        if (!empty($objective['unit_id'])) UnitScopeService::assertUnit((int) $objective['unit_id']);
        $record = ['uuid' => UuidService::v4(), 'learning_objective_id' => $objective['id'], 'description' => trim($data['description']),
            'sort_order' => (int) $data['sort_order'], 'created_by' => EducationFoundationService::actorId()];
        $id = (new ObjectiveCriterionModel())->insert($record, true);
        return (new ObjectiveCriterionModel())->find($id);
    }

    public static function scoped(array $filters = []): array
    {
        $ids = UnitScopeService::accessibleUnitIds();
        $b = Database::connect()->table('learning_objectives_tp tp')->select('tp.*, cp.subject_id, cp.grade_level_id, cp.phase')
            ->join('learning_outcomes_cp cp', 'cp.id = tp.learning_outcome_id')->groupStart()->where('tp.unit_id IS NULL');
        if ($ids !== []) $b->orWhereIn('tp.unit_id', $ids);
        $b->groupEnd();
        foreach (['subject_id','grade_level_id'] as $field) if (!empty($filters[$field])) $b->where('cp.' . $field, (int) $filters[$field]);
        return $b->orderBy('tp.code')->get()->getResultArray();
    }
}
