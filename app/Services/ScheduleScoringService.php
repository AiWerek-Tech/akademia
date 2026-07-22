<?php

namespace App\Services;

use Config\Database;

class ScheduleScoringService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function calculateScore(int $scheduleVersionId): array
    {
        $entries = $this->db->table('schedule_entries se')
            ->select('se.*, sds.slot_number, sd.day_of_week, sr.preferred_room_id')
            ->join('schedule_day_slots sds', 'sds.id = se.day_slot_id')
            ->join('schedule_days sd', 'sd.id = sds.day_id')
            ->join('schedule_requirements sr', 'sr.id = se.schedule_requirement_id')
            ->where('se.schedule_version_id', $scheduleVersionId)
            ->get()->getResultArray();

        $hardScore = 0;
        $softScore = 1000; // Base score

        // 1. Preferred Room Bonus (+10 per entry)
        $preferredRoomBonus = 0;
        foreach ($entries as $e) {
            if ($e['preferred_room_id'] && (int)$e['preferred_room_id'] === (int)$e['room_id']) {
                $preferredRoomBonus += 10;
            }
        }
        $softScore += $preferredRoomBonus;

        // 2. Teacher Max Daily Hours Penalty (-20 for every day with > 5 hours)
        $teacherDailyLoad = [];
        foreach ($entries as $e) {
            $tId = (int)$e['teacher_id'];
            $day = (int)$e['day_of_week'];
            $teacherDailyLoad[$tId][$day] = ($teacherDailyLoad[$tId][$day] ?? 0) + 1;
        }

        $teacherOverloadPenalty = 0;
        foreach ($teacherDailyLoad as $tId => $days) {
            foreach ($days as $day => $hours) {
                if ($hours > 5) {
                    $teacherOverloadPenalty += ($hours - 5) * 20;
                }
            }
        }
        $softScore -= $teacherOverloadPenalty;

        // 3. Classroom Gap Penalty (-15 per gap slot)
        $classDailySlots = [];
        foreach ($entries as $e) {
            $cId  = (int)$e['classroom_id'];
            $day  = (int)$e['day_of_week'];
            $slot = (int)$e['slot_number'];
            $classDailySlots[$cId][$day][] = $slot;
        }

        $gapPenalty = 0;
        foreach ($classDailySlots as $cId => $days) {
            foreach ($days as $day => $slots) {
                sort($slots);
                $minSlot = min($slots);
                $maxSlot = max($slots);
                $totalSpan = ($maxSlot - $minSlot + 1);
                $actualSlots = count(array_unique($slots));
                $gaps = $totalSpan - $actualSlots;
                if ($gaps > 0) {
                    $gapPenalty += $gaps * 15;
                }
            }
        }
        $softScore -= $gapPenalty;

        $totalScore = max(0, $softScore);

        return [
            'hard_score'             => $hardScore,
            'soft_score'             => $softScore,
            'total_score'            => $totalScore,
            'preferred_room_bonus'   => $preferredRoomBonus,
            'teacher_overload_penalty' => $teacherOverloadPenalty,
            'gap_penalty'            => $gapPenalty,
        ];
    }
}
