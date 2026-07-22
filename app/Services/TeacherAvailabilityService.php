<?php

namespace App\Services;

use App\Models\TeacherAvailabilityRuleModel;
use Config\Database;

class TeacherAvailabilityService
{
    private TeacherAvailabilityRuleModel $ruleModel;
    private $db;

    public function __construct()
    {
        $this->ruleModel = new TeacherAvailabilityRuleModel();
        $this->db        = Database::connect();
    }

    public function isTeacherAvailable(int $teacherId, int $academicPeriodId, int $dayOfWeek, int $slotNumber): bool
    {
        // 1. Check explicit availability rules in teacher_availability_rules
        $rule = $this->ruleModel
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
            ->first();

        if ($rule && (string)$rule['availability_status'] === 'UNAVAILABLE') {
            return false;
        }

        return true;
    }

    public function getCrossUnitTeacherScheduleOccupancy(int $teacherId, int $academicPeriodId, int $dayOfWeek, int $slotNumber, ?int $excludeScheduleVersionId = null): array
    {
        // Search across all schedule_versions for the same academic_period_id (both SMP & SMA)
        $builder = $this->db->table('schedule_entries se')
            ->select('se.*, sd.school_unit_id, sv.code as version_code, su.name as unit_name, c.name as class_name, s.name as subject_name')
            ->join('schedule_versions sv', 'sv.id = se.schedule_version_id')
            ->join('schedule_day_slots sds', 'sds.id = se.day_slot_id')
            ->join('schedule_days sd', 'sd.id = sds.day_id')
            ->join('school_units su', 'su.id = sd.school_unit_id')
            ->join('classrooms c', 'c.id = se.classroom_id')
            ->join('subjects s', 's.id = se.subject_id')
            ->where('sv.academic_period_id', $academicPeriodId)
            ->where('sd.day_of_week', $dayOfWeek)
            ->where('sds.slot_number', $slotNumber)
            ->groupStart()
                ->where('se.teacher_id', $teacherId)
                ->orWhere('se.second_teacher_id', $teacherId)
            ->groupEnd();

        if ($excludeScheduleVersionId !== null) {
            $builder->where('se.schedule_version_id !=', $excludeScheduleVersionId);
        }

        return $builder->get()->getResultArray();
    }
}
