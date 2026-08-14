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
        $rules = $this->ruleModel
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
}
