<?php

namespace App\Services;

use App\Models\CurriculumStructureModel;
use App\Models\ClassroomModel;
use Config\Database;

class CurriculumResolutionService
{
    /**
     * Resolve effective curriculum structure for a given classroom in a curriculum version
     */
    public static function resolveClassroomStructure(int $versionId, int $classroomId): array
    {
        $db = Database::connect();
        $classroomModel = new ClassroomModel();
        $classroom = $classroomModel->find($classroomId);

        if (!$classroom) {
            throw new \InvalidArgumentException('Kelas tidak ditemukan (ID: ' . $classroomId . ').');
        }

        $unitId       = (int)$classroom['unit_id'];
        $gradeLevelId = (int)$classroom['grade_level_id'];

        $structureModel = new CurriculumStructureModel();

        // 1. Fetch Grade Defaults (classroom_id IS NULL)
        $gradeDefaults = $structureModel->where('curriculum_version_id', $versionId)
            ->where('unit_id', $unitId)
            ->where('grade_level_id', $gradeLevelId)
            ->where('classroom_id IS NULL')
            ->where('status', 'ACTIVE')
            ->findAll();

        // 2. Fetch Classroom Overrides (classroom_id = $classroomId)
        $overrides = $structureModel->where('curriculum_version_id', $versionId)
            ->where('classroom_id', $classroomId)
            ->where('status', 'ACTIVE')
            ->findAll();

        $overrideBySubject = [];
        foreach ($overrides as $o) {
            $overrideBySubject[$o['subject_id']] = $o;
        }

        $resolved = [];
        $processedSubjectIds = [];

        // 3. Resolve grade defaults vs overrides
        foreach ($gradeDefaults as $gd) {
            $subjectId = (int)$gd['subject_id'];
            $processedSubjectIds[] = $subjectId;

            if (isset($overrideBySubject[$subjectId])) {
                $item = $overrideBySubject[$subjectId];
                $item['resolution_source'] = 'CLASSROOM_OVERRIDE';
                $item['is_override'] = true;
                $resolved[] = $item;
            } else {
                $item = $gd;
                $item['resolution_source'] = 'GRADE_DEFAULT';
                $item['is_override'] = false;
                $resolved[] = $item;
            }
        }

        // 4. Handle subjects that exist ONLY in classroom override (not in grade default)
        foreach ($overrides as $o) {
            $subjectId = (int)$o['subject_id'];
            if (!in_array($subjectId, $processedSubjectIds, true)) {
                $item = $o;
                $item['resolution_source'] = 'CLASSROOM_OVERRIDE';
                $item['is_override'] = true;
                $resolved[] = $item;
            }
        }

        return [
            'classroom'  => $classroom,
            'version_id' => $versionId,
            'structures' => $resolved,
            'total_jp'   => array_sum(array_column($resolved, 'effective_weekly_hours')),
        ];
    }
}
