<?php

namespace App\Services;

use Config\Database;

/**
 * Single source of truth for weekly classroom capacity.
 * Academic routine rows are already materialized in schedule_fixed_activities;
 * each such row reserves one JP for that classroom.
 */
class ScheduleCapacityService
{
    public static function forAssignmentVersion(int $assignmentVersionId, ?int $unitId = null): array
    {
        $db = Database::connect();
        $query = $db->table('schedule_versions')
            ->where('assignment_version_id', $assignmentVersionId)
            ->orderBy('is_active', 'DESC')->orderBy('id', 'DESC');
        if ($unitId !== null) {
            $query->where('unit_id', $unitId);
        }
        $versions = $query->get()->getResultArray();
        if ($unitId !== null) {
            $version = $versions[0] ?? null;
            return $version ? self::forScheduleVersion((int) $version['id'], $assignmentVersionId) : [
                'schedule_version_id' => null,
                'base_slots' => 0,
                'classrooms' => [],
                'has_schedule' => false,
            ];
        }
        if ($versions !== []) {
            $merged = self::forScheduleVersion((int) $versions[0]['id'], $assignmentVersionId);
            foreach (array_slice($versions, 1) as $version) {
                $part = self::forScheduleVersion((int) $version['id'], $assignmentVersionId);
                $merged['classrooms'] += $part['classrooms'];
            }
            return $merged;
        }
        return [
            'schedule_version_id' => null,
            'base_slots' => 0,
            'classrooms' => [],
            'has_schedule' => false,
        ];
    }

    public static function forScheduleVersion(int $scheduleVersionId, ?int $assignmentVersionId = null): array
    {
        $db = Database::connect();
        $slots = $db->table('schedule_day_slots sds')
            ->select('sds.id, sds.slot_number, sd.day_of_week')
            ->join('schedule_days sd', 'sd.id = sds.day_id')
            ->where('sds.schedule_version_id', $scheduleVersionId)
            ->where('sds.slot_type', 'LESSON')
            ->orderBy('sd.day_of_week', 'ASC')->orderBy('sds.slot_number', 'ASC')
            ->get()->getResultArray();
        $slotsByDay = [];
        foreach ($slots as $slot) {
            $slotsByDay[(int) $slot['day_of_week']][] = $slot;
        }
        $fixed = $db->table('schedule_fixed_activities')
            ->where('schedule_version_id', $scheduleVersionId)->get()->getResultArray();
        $fixedByClass = [];
        $fixedKeys = [];
        foreach ($fixed as $row) {
            $classId = (int) ($row['classroom_id'] ?? 0);
            if ($classId <= 0) continue;
            $fixedByClass[$classId] = ($fixedByClass[$classId] ?? 0) + 1;
            $fixedKeys[$classId . ':' . (int) $row['day_slot_id']] = true;
        }

        $scheduleVersion = $db->table('schedule_versions')->select('unit_id')->where('id', $scheduleVersionId)->get()->getRowArray();
        $classQuery = $db->table('classrooms c')->select('c.id, c.name')->where('c.is_active', 1);
        if ($scheduleVersion && $scheduleVersion['unit_id'] !== null) {
            $classQuery->where('c.unit_id', (int) $scheduleVersion['unit_id']);
        }
        $classRows = $classQuery->get()->getResultArray();
        $baseSlots = count($slots);
        $result = [];
        foreach ($classRows as $class) {
            $classId = (int) $class['id'];
            $availableSlots = $baseSlots - ($fixedByClass[$classId] ?? 0);
            $pairs = 0;
            foreach ($slotsByDay as $daySlots) {
                $run = 0;
                foreach ($daySlots as $slot) {
                    if (isset($fixedKeys[$classId . ':' . (int) $slot['id']])) {
                        $pairs += intdiv($run, 2);
                        $run = 0;
                    } else {
                        $run++;
                    }
                }
                $pairs += intdiv($run, 2);
            }
            $result[$classId] = [
                'classroom_id' => $classId,
                'classroom_name' => $class['name'],
                'base_slots' => $baseSlots,
                'routine_jp' => (int) ($fixedByClass[$classId] ?? 0),
                'available_slots' => max(0, $availableSlots),
                'available_2jp_blocks' => $pairs,
            ];
        }

        return [
            'schedule_version_id' => $scheduleVersionId,
            'assignment_version_id' => $assignmentVersionId,
            'base_slots' => $baseSlots,
            'classrooms' => $result,
            'has_schedule' => true,
        ];
    }
}
