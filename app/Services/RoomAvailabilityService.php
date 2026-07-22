<?php

namespace App\Services;

use App\Models\RoomAvailabilityRuleModel;

class RoomAvailabilityService
{
    private RoomAvailabilityRuleModel $ruleModel;

    public function __construct()
    {
        $this->ruleModel = new RoomAvailabilityRuleModel();
    }

    public function isRoomAvailable(int $roomId, int $academicPeriodId, int $dayOfWeek, int $slotNumber): bool
    {
        $rule = $this->ruleModel
            ->where('room_id', $roomId)
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
