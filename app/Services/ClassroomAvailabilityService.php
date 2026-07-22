<?php

namespace App\Services;

use App\Models\ClassroomAvailabilityRuleModel;

class ClassroomAvailabilityService
{
    private ClassroomAvailabilityRuleModel $ruleModel;

    public function __construct()
    {
        $this->ruleModel = new ClassroomAvailabilityRuleModel();
    }

    public function isClassroomAvailable(int $classroomId, int $academicPeriodId, int $dayOfWeek, int $slotNumber): bool
    {
        $rule = $this->ruleModel
            ->where('classroom_id', $classroomId)
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
}
