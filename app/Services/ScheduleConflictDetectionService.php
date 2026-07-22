<?php

namespace App\Services;

use App\Models\ScheduleConflictModel;
use App\Models\ScheduleEntryModel;
use App\Models\ScheduleRequirementModel;
use App\Models\ScheduleVersionModel;
use Config\Database;

class ScheduleConflictDetectionService
{
    private ScheduleConflictModel $conflictModel;
    private ScheduleEntryModel $entryModel;
    private ScheduleRequirementModel $requirementModel;
    private ScheduleVersionModel $versionModel;
    private TeacherAvailabilityService $teacherAvailabilityService;
    private RoomAvailabilityService $roomAvailabilityService;
    private $db;

    public function __construct()
    {
        $this->conflictModel              = new ScheduleConflictModel();
        $this->entryModel                 = new ScheduleEntryModel();
        $this->requirementModel           = new ScheduleRequirementModel();
        $this->versionModel               = new ScheduleVersionModel();
        $this->teacherAvailabilityService = new TeacherAvailabilityService();
        $this->roomAvailabilityService    = new RoomAvailabilityService();
        $this->db                         = Database::connect();
    }

    public function detectConflicts(int $scheduleVersionId): array
    {
        $version = $this->versionModel->find($scheduleVersionId);
        if (!$version) {
            throw new \RuntimeException("Schedule version ID {$scheduleVersionId} not found.");
        }

        $academicPeriodId = (int)$version['academic_period_id'];

        // Clear existing conflicts for this version
        $this->conflictModel->where('schedule_version_id', $scheduleVersionId)->delete();

        $entries = $this->db->table('schedule_entries se')
            ->select('se.*, sds.slot_number, sds.slot_type, sd.day_of_week, sd.day_name, u.full_name as teacher_name, c.name as class_name, s.name as subject_name, r.name as room_name, r.room_type_id')
            ->join('schedule_day_slots sds', 'sds.id = se.day_slot_id')
            ->join('schedule_days sd', 'sd.id = sds.day_id')
            ->join('users u', 'u.id = se.teacher_id')
            ->join('classrooms c', 'c.id = se.classroom_id')
            ->join('subjects s', 's.id = se.subject_id')
            ->join('rooms r', 'r.id = se.room_id', 'left')
            ->where('se.schedule_version_id', $scheduleVersionId)
            ->get()->getResultArray();

        $conflictsFound = [];

        // 1. Check Teacher Double Booking (same version)
        $teacherSlotMap = [];
        foreach ($entries as $entry) {
            $slotId    = (int)$entry['day_slot_id'];
            $teacherId = (int)$entry['teacher_id'];
            $key       = "{$slotId}_{$teacherId}";

            if (isset($teacherSlotMap[$key])) {
                $prev = $teacherSlotMap[$key];
                $conflictsFound[] = [
                    'schedule_version_id'  => $scheduleVersionId,
                    'conflict_type'        => 'TEACHER_DOUBLE_BOOKING',
                    'severity'             => 'CRITICAL',
                    'description'          => "Guru {$entry['teacher_name']} mengajar di dua kelas sekaligus ({$prev['class_name']} dan {$entry['class_name']}) pada {$entry['day_name']} slot {$entry['slot_number']}.",
                    'entity_type'          => 'TEACHER',
                    'entity_id'            => $teacherId,
                    'primary_entry_id'     => $prev['id'],
                    'conflicting_entry_id' => $entry['id'],
                ];
            } else {
                $teacherSlotMap[$key] = $entry;
            }

            if ($entry['second_teacher_id']) {
                $secondTeacherId = (int)$entry['second_teacher_id'];
                $key2 = "{$slotId}_{$secondTeacherId}";
                if (isset($teacherSlotMap[$key2])) {
                    $prev = $teacherSlotMap[$key2];
                    $conflictsFound[] = [
                        'schedule_version_id'  => $scheduleVersionId,
                        'conflict_type'        => 'TEACHER_DOUBLE_BOOKING',
                        'severity'             => 'CRITICAL',
                        'description'          => "Guru tim mengajar di dua tempat pada slot yang sama pada {$entry['day_name']} slot {$entry['slot_number']}.",
                        'entity_type'          => 'TEACHER',
                        'entity_id'            => $secondTeacherId,
                        'primary_entry_id'     => $prev['id'],
                        'conflicting_entry_id' => $entry['id'],
                    ];
                } else {
                    $teacherSlotMap[$key2] = $entry;
                }
            }
        }

        // 2. Check Classroom Double Booking (same version)
        $classSlotMap = [];
        foreach ($entries as $entry) {
            $slotId  = (int)$entry['day_slot_id'];
            $classId = (int)$entry['classroom_id'];
            $key     = "{$slotId}_{$classId}";

            if (isset($classSlotMap[$key])) {
                $prev = $classSlotMap[$key];
                $conflictsFound[] = [
                    'schedule_version_id'  => $scheduleVersionId,
                    'conflict_type'        => 'CLASSROOM_DOUBLE_BOOKING',
                    'severity'             => 'CRITICAL',
                    'description'          => "Kelas {$entry['class_name']} memiliki dua mata pelajaran pada slot yang sama pada {$entry['day_name']} slot {$entry['slot_number']}.",
                    'entity_type'          => 'CLASSROOM',
                    'entity_id'            => $classId,
                    'primary_entry_id'     => $prev['id'],
                    'conflicting_entry_id' => $entry['id'],
                ];
            } else {
                $classSlotMap[$key] = $entry;
            }
        }

        // 3. Check Room Double Booking (same version)
        $roomSlotMap = [];
        foreach ($entries as $entry) {
            if (!$entry['room_id']) {
                continue;
            }
            $slotId = (int)$entry['day_slot_id'];
            $roomId = (int)$entry['room_id'];
            $key    = "{$slotId}_{$roomId}";

            if (isset($roomSlotMap[$key])) {
                $prev = $roomSlotMap[$key];
                $conflictsFound[] = [
                    'schedule_version_id'  => $scheduleVersionId,
                    'conflict_type'        => 'ROOM_DOUBLE_BOOKING',
                    'severity'             => 'CRITICAL',
                    'description'          => "Ruang {$entry['room_name']} digunakan oleh kelas {$prev['class_name']} dan {$entry['class_name']} pada {$entry['day_name']} slot {$entry['slot_number']}.",
                    'entity_type'          => 'ROOM',
                    'entity_id'            => $roomId,
                    'primary_entry_id'     => $prev['id'],
                    'conflicting_entry_id' => $entry['id'],
                ];
            } else {
                $roomSlotMap[$key] = $entry;
            }
        }

        // 4. Check Cross-Unit Teacher Double Booking
        foreach ($entries as $entry) {
            $teacherId  = (int)$entry['teacher_id'];
            $dayOfWeek  = (int)$entry['day_of_week'];
            $slotNumber = (int)$entry['slot_number'];

            $otherUnitOccupancy = $this->teacherAvailabilityService->getCrossUnitTeacherScheduleOccupancy(
                $teacherId,
                $academicPeriodId,
                $dayOfWeek,
                $slotNumber,
                $scheduleVersionId
            );

            if ($otherUnitOccupancy !== []) {
                $other = $otherUnitOccupancy[0];
                $conflictsFound[] = [
                    'schedule_version_id'  => $scheduleVersionId,
                    'conflict_type'        => 'CROSS_UNIT_TEACHER_DOUBLE_BOOKING',
                    'severity'             => 'CRITICAL',
                    'description'          => "Guru {$entry['teacher_name']} mengajar di unit lain ({$other['unit_name']} - Kelas {$other['class_name']}) pada hari {$entry['day_name']} slot {$slotNumber}.",
                    'entity_type'          => 'TEACHER',
                    'entity_id'            => $teacherId,
                    'primary_entry_id'     => $entry['id'],
                    'conflicting_entry_id' => $other['id'],
                ];
            }
        }

        // 5. Check Teacher Availability Rules
        foreach ($entries as $entry) {
            $teacherId  = (int)$entry['teacher_id'];
            $dayOfWeek  = (int)$entry['day_of_week'];
            $slotNumber = (int)$entry['slot_number'];

            if (!$this->teacherAvailabilityService->isTeacherAvailable($teacherId, $academicPeriodId, $dayOfWeek, $slotNumber)) {
                $conflictsFound[] = [
                    'schedule_version_id'  => $scheduleVersionId,
                    'conflict_type'        => 'TEACHER_UNAVAILABLE',
                    'severity'             => 'CRITICAL',
                    'description'          => "Guru {$entry['teacher_name']} tidak bersedia mengajar pada {$entry['day_name']} slot {$slotNumber}.",
                    'entity_type'          => 'TEACHER',
                    'entity_id'            => $teacherId,
                    'primary_entry_id'     => $entry['id'],
                    'conflicting_entry_id' => null,
                ];
            }
        }

        // 6. Check Room Availability Rules
        foreach ($entries as $entry) {
            if (!$entry['room_id']) {
                continue;
            }
            $roomId     = (int)$entry['room_id'];
            $dayOfWeek  = (int)$entry['day_of_week'];
            $slotNumber = (int)$entry['slot_number'];

            if (!$this->roomAvailabilityService->isRoomAvailable($roomId, $academicPeriodId, $dayOfWeek, $slotNumber)) {
                $conflictsFound[] = [
                    'schedule_version_id'  => $scheduleVersionId,
                    'conflict_type'        => 'ROOM_UNAVAILABLE',
                    'severity'             => 'CRITICAL',
                    'description'          => "Ruang {$entry['room_name']} sedang tidak dapat digunakan pada {$entry['day_name']} slot {$slotNumber}.",
                    'entity_type'          => 'ROOM',
                    'entity_id'            => $roomId,
                    'primary_entry_id'     => $entry['id'],
                    'conflicting_entry_id' => null,
                ];
            }
        }

        // 7. Check Unmet Hours Requirements
        $requirements = $this->requirementModel->where('schedule_version_id', $scheduleVersionId)->findAll();
        foreach ($requirements as $req) {
            $reqId = (int)$req['id'];
            $requiredHours = (float)$req['required_weekly_hours'];

            $placedCount = $this->entryModel
                ->where('schedule_version_id', $scheduleVersionId)
                ->where('schedule_requirement_id', $reqId)
                ->countAllResults();

            if ((float)$placedCount < $requiredHours) {
                $diff = $requiredHours - (float)$placedCount;
                $conflictsFound[] = [
                    'schedule_version_id'  => $scheduleVersionId,
                    'conflict_type'        => 'UNMET_HOURS',
                    'severity'             => 'HIGH',
                    'description'          => "Kebutuhan jam pelajaran (ID #{$reqId}) kurang {$diff} slot dari total kebutuhan {$requiredHours} jam.",
                    'entity_type'          => 'REQUIREMENT',
                    'entity_id'            => $reqId,
                    'primary_entry_id'     => null,
                    'conflicting_entry_id' => null,
                ];
            }
        }

        // Insert conflicts into database
        $now = date('Y-m-d H:i:s');
        foreach ($conflictsFound as $conflict) {
            $conflict['uuid']       = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
            $conflict['created_at'] = $now;
            $conflict['updated_at'] = $now;
            $this->conflictModel->insert($conflict);
        }

        $criticalCount = count(array_filter($conflictsFound, fn($c) => $c['severity'] === 'CRITICAL'));

        return [
            'total_conflicts'    => count($conflictsFound),
            'critical_conflicts' => $criticalCount,
            'conflicts'          => $conflictsFound,
        ];
    }
}
