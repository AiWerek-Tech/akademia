<?php

namespace App\Services;

use App\Models\LearningSequenceItemModel;
use App\Models\LearningSequenceModel;
use Config\Database;
use InvalidArgumentException;
use RuntimeException;

class LearningSequenceService
{
    private const TRANSITIONS = ['DRAFT' => 'VALIDATED', 'VALIDATED' => 'REVIEWED', 'REVIEWED' => 'APPROVED', 'APPROVED' => 'LOCKED', 'LOCKED' => 'ARCHIVED'];

    public static function create(array $data): array
    {
        EducationFoundationService::requireFields($data, ['curriculum_version_id','unit_id','subject_id','grade_level_id','code','name','phase']);
        UnitScopeService::assertUnit((int) $data['unit_id']);
        UnitScopeService::assertSubjectInUnit((int) $data['subject_id'], (int) $data['unit_id']);
        $record = array_intersect_key($data, array_flip(['curriculum_version_id','unit_id','subject_id','grade_level_id','parent_sequence_id','code','name','phase','description'])) + [
            'uuid' => UuidService::v4(), 'workflow_status' => 'DRAFT', 'revision_number' => 1, 'created_by' => EducationFoundationService::actorId(),
        ];
        $record['code'] = strtoupper(trim($record['code'])); $record['phase'] = strtoupper(trim($record['phase']));
        $id = (new LearningSequenceModel())->insert($record, true);
        AuditService::log('education_foundation', 'CREATE_ATP', 'LearningSequence', (int) $id, null, $record);
        return (new LearningSequenceModel())->find($id);
    }

    public static function addItem(string $sequenceUuid, string $objectiveUuid, array $data = []): array
    {
        $sequence = EducationFoundationService::byUuid('learning_sequences_atp', $sequenceUuid);
        UnitScopeService::assertUnit((int) $sequence['unit_id']); self::assertMutable($sequence);
        $objective = EducationFoundationService::byUuid('learning_objectives_tp', $objectiveUuid);
        $cp = Database::connect()->table('learning_outcomes_cp')->where('id', $objective['learning_outcome_id'])->get()->getRowArray();
        if (!$cp || (int) $cp['subject_id'] !== (int) $sequence['subject_id'] || (int) $cp['grade_level_id'] !== (int) $sequence['grade_level_id'] || strtoupper($cp['phase']) !== strtoupper($sequence['phase'])) {
            throw new InvalidArgumentException('TP tidak cocok dengan mapel, fase, atau tingkat ATP.');
        }
        if ($objective['unit_id'] !== null && (int) $objective['unit_id'] !== (int) $sequence['unit_id']) throw new InvalidArgumentException('TP adaptasi berasal dari unit berbeda.');
        $b = Database::connect()->table('learning_sequence_items')->where('learning_sequence_id', $sequence['id']);
        if ($b->where('learning_objective_id', $objective['id'])->countAllResults() > 0) throw new InvalidArgumentException('TP sudah ada dalam ATP ini.');
        $max = Database::connect()->table('learning_sequence_items')->selectMax('sort_order')->where('learning_sequence_id', $sequence['id'])->get()->getRowArray();
        $record = ['uuid' => UuidService::v4(), 'learning_sequence_id' => $sequence['id'], 'learning_objective_id' => $objective['id'],
            'sort_order' => (int) ($data['sort_order'] ?? ((int) ($max['sort_order'] ?? 0) + 1)), 'estimated_hours' => $data['estimated_hours'] ?? null,
            'notes' => $data['notes'] ?? null, 'created_by' => EducationFoundationService::actorId()];
        $id = (new LearningSequenceItemModel())->insert($record, true);
        AuditService::log('education_foundation', 'ADD_ATP_ITEM', 'LearningSequenceItem', (int) $id, null, $record);
        return (new LearningSequenceItemModel())->find($id);
    }

    public static function transition(string $uuid, string $targetStatus, int $revisionNumber): array
    {
        $current = EducationFoundationService::byUuid('learning_sequences_atp', $uuid);
        UnitScopeService::assertUnit((int) $current['unit_id']);
        $target = strtoupper($targetStatus);
        if ((self::TRANSITIONS[$current['workflow_status']] ?? null) !== $target) throw new InvalidArgumentException("Transisi {$current['workflow_status']} ke {$target} tidak diizinkan.");
        if ($target === 'VALIDATED' && Database::connect()->table('learning_sequence_items')->where('learning_sequence_id', $current['id'])->countAllResults() === 0) throw new InvalidArgumentException('ATP kosong tidak dapat divalidasi.');
        $prefix = strtolower($target); $actor = EducationFoundationService::actorId();
        $updated = EducationFoundationService::atomicUpdate('learning_sequences_atp', $current, ['workflow_status' => $target, $prefix . '_by' => $actor, $prefix . '_at' => date('Y-m-d H:i:s'), 'revision_number' => $revisionNumber]);
        AuditService::log('education_foundation', $target . '_ATP', 'LearningSequence', (int) $current['id'], $current, $updated);
        return $updated;
    }

    public static function update(string $uuid, array $data): array
    {
        $current = EducationFoundationService::byUuid('learning_sequences_atp', $uuid); UnitScopeService::assertUnit((int) $current['unit_id']); self::assertMutable($current);
        $updated = EducationFoundationService::atomicUpdate('learning_sequences_atp', $current, array_intersect_key($data, array_flip(['name','description','revision_number'])));
        AuditService::log('education_foundation', 'UPDATE_ATP', 'LearningSequence', (int) $current['id'], $current, $updated);
        return $updated;
    }

    public static function clone(string $uuid, array $overrides = []): array
    {
        $db=Database::connect(); $db->transBegin();
        try {
            $source = EducationFoundationService::byUuid('learning_sequences_atp', $uuid); UnitScopeService::assertUnit((int) $source['unit_id']);
            $copy = self::create([
                'curriculum_version_id' => $overrides['curriculum_version_id'] ?? $source['curriculum_version_id'], 'unit_id' => $overrides['unit_id'] ?? $source['unit_id'],
                'subject_id' => $source['subject_id'], 'grade_level_id' => $source['grade_level_id'], 'parent_sequence_id' => $source['id'],
                'code' => $overrides['code'] ?? ($source['code'] . '-COPY'), 'name' => $overrides['name'] ?? ($source['name'] . ' (Salinan)'),
                'phase' => $source['phase'], 'description' => $source['description'],
            ]);
            $items = $db->table('learning_sequence_items')->where('learning_sequence_id', $source['id'])->orderBy('sort_order')->get()->getResultArray();
            foreach ($items as $item) {
                $objective = $db->table('learning_objectives_tp')->where('id', $item['learning_objective_id'])->get()->getRowArray();
                self::addItem($copy['uuid'], $objective['uuid'], $item);
            }
            $db->transCommit(); return $copy;
        } catch (\Throwable $e) { $db->transRollback(); throw $e; }
    }

    public static function scoped(): array
    {
        $ids = UnitScopeService::accessibleUnitIds(); if ($ids === []) return [];
        return Database::connect()->table('learning_sequences_atp a')->select('a.*, s.name subject_name, gl.name grade_name')
            ->join('subjects s', 's.id=a.subject_id')->join('grade_levels gl', 'gl.id=a.grade_level_id')->whereIn('a.unit_id', $ids)->orderBy('a.updated_at', 'DESC')->get()->getResultArray();
    }

    private static function assertMutable(array $sequence): void
    {
        if (in_array($sequence['workflow_status'], ['LOCKED','ARCHIVED'], true)) throw new RuntimeException('ATP terkunci/diarsipkan bersifat immutable. Klon untuk membuat revisi.');
    }
}
