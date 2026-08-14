<?php

namespace App\Services;

use App\Models\TeacherAvailabilityRuleModel;
use Config\Database;

class TeacherAvailabilityService
{
    private TeacherAvailabilityRuleModel $ruleModel;
    private TeacherScheduleSubstitutionService $substitutionService;
    private $db;

    public function __construct()
    {
        $this->ruleModel = new TeacherAvailabilityRuleModel();
        $this->db        = Database::connect();
        $this->substitutionService = new TeacherScheduleSubstitutionService();
    }

    public function isTeacherAvailable(int $teacherId, int $academicPeriodId, int $dayOfWeek, int $slotNumber): bool
    {
        $teacherId = $this->substitutionService->resolveResourceTeacherId($teacherId, $academicPeriodId);

        // 1. Check explicit availability rules in teacher_availability_rules
        $rules = $this->ruleModel
            ->where('teacher_id', $teacherId)
            ->where('academic_period_id', $academicPeriodId)
            ->groupStart()
                ->where('day_of_week', $dayOfWeek)
                ->orWhere('day_of_week IS NULL')
            ->groupEnd()
            ->groupStart()
                ->where('slot_number', $slotNumber)
                ->orWhere('slot_number IS NULL')
            ->groupEnd()
            ->findAll();

        $resolvedStatus = null;
        $resolvedSpecificity = -1;
        foreach ($rules as $rule) {
            $specificity = ($rule['day_of_week'] !== null ? 1 : 0)
                + ($rule['slot_number'] !== null ? 1 : 0);
            $status = strtoupper((string) ($rule['availability_status'] ?? 'UNAVAILABLE'));
            if ($specificity > $resolvedSpecificity
                || ($specificity === $resolvedSpecificity && $status === 'UNAVAILABLE')) {
                $resolvedSpecificity = $specificity;
                $resolvedStatus = $status;
            }
        }

        return $resolvedStatus !== 'UNAVAILABLE';
    }

    public function getCrossUnitTeacherScheduleOccupancy(int $teacherId, int $academicPeriodId, int $dayOfWeek, int $slotNumber, ?int $excludeScheduleVersionId = null): array
    {
        $resourceTeacherIds = $this->substitutionService->teacherIdsSharingResource($teacherId, $academicPeriodId);

        // Compare actual time ranges. SMP and SMA can use different JP numbering,
        // so matching slot_number alone produces both missed and false conflicts.
        $targetSlot = null;
        if ($excludeScheduleVersionId !== null) {
            $targetSlot = $this->db->table('schedule_day_slots sds')
                ->select('sds.start_time, sds.end_time')
                ->join('schedule_days sd', 'sd.id = sds.day_id')
                ->where('sds.schedule_version_id', $excludeScheduleVersionId)
                ->where('sd.day_of_week', $dayOfWeek)
                ->where('sds.slot_number', $slotNumber)
                ->get()->getRowArray();
        }

        // Search the current source-of-truth version in every other unit. A
        // production candidate is still DRAFT before approval, so filtering
        // only `is_active = 1` silently misses real cross-unit collisions.
        $excludedUnitId = null;
        if ($excludeScheduleVersionId !== null) {
            $excludedVersion = $this->db->table('schedule_versions')
                ->select('unit_id')->where('id', $excludeScheduleVersionId)->get()->getRowArray();
            $excludedUnitId = isset($excludedVersion['unit_id']) ? (int) $excludedVersion['unit_id'] : null;
        }
        $versionBuilder = $this->db->table('schedule_versions')
            ->select('id, unit_id, is_active, revision_number')
            ->where('academic_period_id', $academicPeriodId)
            ->where('workflow_status !=', 'ARCHIVED')
            ->orderBy('is_active', 'DESC')->orderBy('revision_number', 'DESC')->orderBy('id', 'DESC');
        if ($excludedUnitId !== null) {
            $versionBuilder->where('unit_id !=', $excludedUnitId);
        }
        $selectedVersionIds = [];
        $selectedUnits = [];
        foreach ($versionBuilder->get()->getResultArray() as $version) {
            $unitId = (int) $version['unit_id'];
            if (!isset($selectedUnits[$unitId])) {
                $selectedUnits[$unitId] = true;
                $selectedVersionIds[] = (int) $version['id'];
            }
        }
        if ($selectedVersionIds === []) {
            return [];
        }

        $builder = $this->db->table('schedule_entries se')
            ->select('se.*, sd.school_unit_id, sv.code as version_code, su.name as unit_name, c.name as class_name, s.name as subject_name')
            ->join('schedule_versions sv', 'sv.id = se.schedule_version_id')
            ->join('schedule_day_slots sds', 'sds.id = se.day_slot_id')
            ->join('schedule_days sd', 'sd.id = sds.day_id')
            ->join('school_units su', 'su.id = sd.school_unit_id')
            ->join('classrooms c', 'c.id = se.classroom_id')
            ->join('subjects s', 's.id = se.subject_id', 'left')
            ->where('sv.academic_period_id', $academicPeriodId)
            ->whereIn('se.schedule_version_id', $selectedVersionIds)
            ->where('sd.day_of_week', $dayOfWeek)
            ->groupStart()
                ->whereIn('se.teacher_id', $resourceTeacherIds)
                ->orWhereIn('se.second_teacher_id', $resourceTeacherIds)
            ->groupEnd();

        if ($targetSlot && ! empty($targetSlot['start_time']) && ! empty($targetSlot['end_time'])) {
            $builder->where('sds.start_time <', $targetSlot['end_time'])
                ->where('sds.end_time >', $targetSlot['start_time']);
        } else {
            $builder->where('sds.slot_number', $slotNumber);
        }

        if ($excludeScheduleVersionId !== null) {
            $builder->where('se.schedule_version_id !=', $excludeScheduleVersionId);
        }

        return $builder->get()->getResultArray();
    }
}
