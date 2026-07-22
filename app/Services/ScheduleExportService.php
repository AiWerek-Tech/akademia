<?php

namespace App\Services;

use Config\Database;

class ScheduleExportService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getGridForClassroom(int $scheduleVersionId, int $classroomId): array
    {
        $days = $this->db->table('schedule_days sd')
            ->select('sd.*')
            ->join('schedule_day_slots sds', 'sds.day_id = sd.id')
            ->where('sds.schedule_version_id', $scheduleVersionId)
            ->where('sd.is_school_day', 1)
            ->groupBy('sd.id')
            ->orderBy('sd.day_of_week', 'ASC')
            ->get()->getResultArray();

        $slots = $this->db->table('schedule_day_slots')
            ->where('schedule_version_id', $scheduleVersionId)
            ->orderBy('slot_number', 'ASC')
            ->get()->getResultArray();

        $entries = $this->db->table('schedule_entries se')
            ->select('se.*, u.full_name as teacher_name, s.name as subject_name, s.color_label, r.name as room_name')
            ->join('users u', 'u.id = se.teacher_id')
            ->join('subjects s', 's.id = se.subject_id')
            ->join('rooms r', 'r.id = se.room_id', 'left')
            ->where('se.schedule_version_id', $scheduleVersionId)
            ->where('se.classroom_id', $classroomId)
            ->get()->getResultArray();

        $entryMap = [];
        foreach ($entries as $e) {
            $entryMap[(int)$e['day_slot_id']] = $e;
        }

        return [
            'schedule_version_id' => $scheduleVersionId,
            'classroom_id'        => $classroomId,
            'days'                => $days,
            'slots'               => $slots,
            'entry_map'           => $entryMap,
        ];
    }

    public function getGridForTeacher(int $scheduleVersionId, int $teacherId): array
    {
        $entries = $this->db->table('schedule_entries se')
            ->select('se.*, c.name as class_name, s.name as subject_name, s.color_label, r.name as room_name, sds.slot_number, sd.day_name, sd.day_of_week')
            ->join('schedule_day_slots sds', 'sds.id = se.day_slot_id')
            ->join('schedule_days sd', 'sd.id = sds.day_id')
            ->join('classrooms c', 'c.id = se.classroom_id')
            ->join('subjects s', 's.id = se.subject_id')
            ->join('rooms r', 'r.id = se.room_id', 'left')
            ->where('se.schedule_version_id', $scheduleVersionId)
            ->groupStart()
                ->where('se.teacher_id', $teacherId)
                ->orWhere('se.second_teacher_id', $teacherId)
            ->groupEnd()
            ->get()->getResultArray();

        return [
            'schedule_version_id' => $scheduleVersionId,
            'teacher_id'          => $teacherId,
            'entries'             => $entries,
        ];
    }
}
