<?php

namespace App\Services;

use Config\Database;

class CurriculumCoverageService
{
    public static function analyze(int $curriculumVersionId, int $unitId, int $subjectId, int $gradeLevelId): array
    {
        UnitScopeService::assertUnit($unitId); UnitScopeService::assertSubjectInUnit($subjectId, $unitId);
        $db = Database::connect();
        $objectives = $db->table('learning_objectives_tp tp')->select('tp.id,tp.uuid,tp.code,tp.statement,tp.unit_id,cp.phase')
            ->join('learning_outcomes_cp cp', 'cp.id=tp.learning_outcome_id')->where('cp.curriculum_version_id', $curriculumVersionId)
            ->where('cp.subject_id', $subjectId)->where('cp.grade_level_id', $gradeLevelId)
            ->groupStart()->where('tp.unit_id IS NULL')->orWhere('tp.unit_id', $unitId)->groupEnd()->get()->getResultArray();
        $adaptedParentIds = array_values(array_filter(array_map('intval', array_column(
            $db->table('learning_objectives_tp')->select('parent_objective_id')->where('unit_id', $unitId)->where('parent_objective_id IS NOT NULL')->get()->getResultArray(),
            'parent_objective_id'
        ))));
        if ($adaptedParentIds !== []) $objectives = array_values(array_filter($objectives, static fn (array $row): bool => !in_array((int) $row['id'], $adaptedParentIds, true)));
        $ids = array_map('intval', array_column($objectives, 'id'));
        $usage = [];
        if ($ids !== []) {
            $rows = $db->table('learning_sequence_items i')->select('i.learning_objective_id, COUNT(*) usage_count')
                ->join('learning_sequences_atp a', 'a.id=i.learning_sequence_id')->where('a.curriculum_version_id', $curriculumVersionId)
                ->where('a.unit_id', $unitId)->where('a.subject_id', $subjectId)->where('a.grade_level_id', $gradeLevelId)
                ->whereIn('i.learning_objective_id', $ids)->groupBy('i.learning_objective_id')->get()->getResultArray();
            $usage = array_column($rows, 'usage_count', 'learning_objective_id');
        }
        $missing = []; $duplicates = [];
        foreach ($objectives as $objective) {
            $count = (int) ($usage[$objective['id']] ?? 0);
            if ($count === 0) $missing[] = $objective;
            if ($count > 1) $duplicates[] = $objective + ['usage_count' => $count];
        }
        $atpCount = $db->table('learning_sequences_atp')->where(['curriculum_version_id'=>$curriculumVersionId,'unit_id'=>$unitId,'subject_id'=>$subjectId,'grade_level_id'=>$gradeLevelId])->countAllResults();
        $packCount = $db->table('subject_learning_packs')->where(['curriculum_version_id'=>$curriculumVersionId,'unit_id'=>$unitId,'subject_id'=>$subjectId,'grade_level_id'=>$gradeLevelId])->countAllResults();
        return ['total_objectives'=>count($objectives),'covered_objectives'=>count($objectives)-count($missing),'missing'=>$missing,'duplicates'=>$duplicates,
            'coverage_percent'=>count($objectives) === 0 ? 0.0 : round((count($objectives)-count($missing))*100/count($objectives),2),'atp_count'=>$atpCount,'learning_pack_count'=>$packCount,
            'is_complete'=>count($objectives)>0 && $missing===[] && $duplicates===[] && $atpCount>0 && $packCount>0];
    }
}
