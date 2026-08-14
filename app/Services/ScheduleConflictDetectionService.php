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
    private TeacherScheduleSubstitutionService $substitutionService;
    private RoomAvailabilityService $roomAvailabilityService;
    private $db;

    public function __construct()
    {
        $this->conflictModel              = new ScheduleConflictModel();
        $this->entryModel                 = new ScheduleEntryModel();
        $this->requirementModel           = new ScheduleRequirementModel();
        $this->versionModel               = new ScheduleVersionModel();
        $this->teacherAvailabilityService = new TeacherAvailabilityService();
        $this->substitutionService         = new TeacherScheduleSubstitutionService();
        $this->roomAvailabilityService    = new RoomAvailabilityService();
        $this->db                         = Database::connect();
    }

    public function detectConflicts(int $scheduleVersionId): array
    {
        if (! $this->db->transBegin()) {
            throw new \RuntimeException('Tidak dapat memulai transaksi audit konflik.');
        }

        try {
            $locked = $this->db->query(
                'SELECT id FROM schedule_versions WHERE id = ? FOR UPDATE',
                [$scheduleVersionId]
            )->getRowArray();
            if (! $locked) {
                throw new \RuntimeException("Schedule version ID {$scheduleVersionId} not found.");
            }

            $result = $this->detectConflictsLocked($scheduleVersionId);
            if (! $this->db->transCommit()) {
                throw new \RuntimeException('Commit audit konflik gagal.');
            }
            return $result;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    private function detectConflictsLocked(int $scheduleVersionId): array
    {
        $version = $this->versionModel->find($scheduleVersionId);
        if (!$version) {
            throw new \RuntimeException("Schedule version ID {$scheduleVersionId} not found.");
        }

        $academicPeriodId = (int)$version['academic_period_id'];

        // Retain conflict history. Findings detected again below are reopened.
        $this->db->table('schedule_conflicts')
            ->where('schedule_version_id', $scheduleVersionId)
            ->where('is_resolved', 0)
            ->update(['is_resolved' => 1, 'status' => 'RESOLVED', 'updated_at' => date('Y-m-d H:i:s')]);

        $entries = $this->db->table('schedule_entries se')
            ->select('se.*, sds.slot_number, sds.slot_type, sds.start_time, sds.end_time, sd.day_of_week, sd.day_name, u.full_name as teacher_name, c.name as class_name, s.name as subject_name, r.name as room_name, r.room_type_id')
            ->join('schedule_day_slots sds', 'sds.id = se.day_slot_id')
            ->join('schedule_days sd', 'sd.id = sds.day_id')
            ->join('teachers u', 'u.id = se.teacher_id', 'left')
            ->join('classrooms c', 'c.id = se.classroom_id')
            ->join('subjects s', 's.id = se.subject_id', 'left')
            ->join('rooms r', 'r.id = se.room_id', 'left')
            ->where('se.schedule_version_id', $scheduleVersionId)
            ->get()->getResultArray();

        $conflictsFound = [];

        // 1. Check Teacher Double Booking (same version)
        $teacherSlotMap = [];
        foreach ($entries as $entry) {
            $slotId    = (int)$entry['day_slot_id'];
            $teacherId = (int)$entry['teacher_id'];
            $teacherResourceId = $this->substitutionService->resolveResourceTeacherId($teacherId, $academicPeriodId);
            $key       = "{$slotId}_{$teacherResourceId}";

            if (isset($teacherSlotMap[$key])) {
                $prev = $teacherSlotMap[$key];
                $conflictsFound[] = [
                    'schedule_version_id'  => $scheduleVersionId,
                    'conflict_type'        => 'TEACHER_DOUBLE_BOOKING',
                    'severity'             => 'CRITICAL',
                    'description'          => "Guru {$entry['teacher_name']} mengajar di dua kelas sekaligus ({$prev['class_name']} dan {$entry['class_name']}) pada {$entry['day_name']} slot {$entry['slot_number']}.",
                    'entity_type'          => 'TEACHER',
                    'entity_id'            => $teacherResourceId,
                    'primary_entry_id'     => $prev['id'],
                    'conflicting_entry_id' => $entry['id'],
                ];
            } else {
                $teacherSlotMap[$key] = $entry;
            }

            if ($entry['second_teacher_id']) {
                $secondTeacherId = (int)$entry['second_teacher_id'];
                $secondTeacherResourceId = $this->substitutionService->resolveResourceTeacherId($secondTeacherId, $academicPeriodId);
                $key2 = "{$slotId}_{$secondTeacherResourceId}";
                if (isset($teacherSlotMap[$key2])) {
                    $prev = $teacherSlotMap[$key2];
                    $conflictsFound[] = [
                        'schedule_version_id'  => $scheduleVersionId,
                        'conflict_type'        => 'TEACHER_DOUBLE_BOOKING',
                        'severity'             => 'CRITICAL',
                        'description'          => "Guru tim mengajar di dua tempat pada slot yang sama pada {$entry['day_name']} slot {$entry['slot_number']}.",
                        'entity_type'          => 'TEACHER',
                        'entity_id'            => $secondTeacherResourceId,
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

        // 2b. Avoid assigning one teacher multiple different subjects in the
        // same classroom on one day; this keeps the learning rhythm varied.
        $teacherClassDaySubjects = [];
        foreach ($entries as $entry) {
            $subjectId = (int) ($entry['subject_id'] ?? 0);
            if ($subjectId <= 0) continue;
            $primaryResourceId = $this->substitutionService->resolveResourceTeacherId(
                (int) $entry['teacher_id'],
                $academicPeriodId
            );
            $key = $primaryResourceId . '_' . (int) $entry['classroom_id'] . '_' . (int) $entry['day_of_week'];
            if (!empty($teacherClassDaySubjects[$key]) && !isset($teacherClassDaySubjects[$key][$subjectId])) {
                $conflictsFound[] = [
                    'schedule_version_id' => $scheduleVersionId,
                    'conflict_type' => 'TEACHER_CLASS_SUBJECT_REPEAT',
                    'severity' => 'MEDIUM',
                    'description' => "Guru {$entry['teacher_name']} mendapat lebih dari satu mata pelajaran di kelas {$entry['class_name']} pada {$entry['day_name']}.",
                    'entity_type' => 'TEACHER', 'entity_id' => $primaryResourceId,
                    'primary_entry_id' => null, 'conflicting_entry_id' => (int) $entry['id'],
                ];
            }
            $teacherClassDaySubjects[$key][$subjectId] = true;
            if (!empty($entry['second_teacher_id'])) {
                $secondResourceId = $this->substitutionService->resolveResourceTeacherId(
                    (int) $entry['second_teacher_id'],
                    $academicPeriodId
                );
                $secondKey = $secondResourceId . '_' . (int) $entry['classroom_id'] . '_' . (int) $entry['day_of_week'];
                if (!empty($teacherClassDaySubjects[$secondKey]) && !isset($teacherClassDaySubjects[$secondKey][$subjectId])) {
                    $conflictsFound[] = [
                        'schedule_version_id' => $scheduleVersionId,
                        'conflict_type' => 'TEACHER_CLASS_SUBJECT_REPEAT', 'severity' => 'MEDIUM',
                        'description' => "Guru tim mendapat lebih dari satu mata pelajaran di kelas {$entry['class_name']} pada {$entry['day_name']}.",
                        'entity_type' => 'TEACHER', 'entity_id' => $secondResourceId,
                        'primary_entry_id' => null, 'conflicting_entry_id' => (int) $entry['id'],
                    ];
                }
                $teacherClassDaySubjects[$secondKey][$subjectId] = true;
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
            $teacherResourceId = $this->substitutionService->resolveResourceTeacherId($teacherId, $academicPeriodId);
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
                    'entity_id'            => $teacherResourceId,
                    'primary_entry_id'     => $entry['id'],
                    'conflicting_entry_id' => $other['id'],
                ];
            }

            if (!empty($entry['second_teacher_id'])) {
                $secondTeacherId = (int) $entry['second_teacher_id'];
                $secondTeacherResourceId = $this->substitutionService->resolveResourceTeacherId($secondTeacherId, $academicPeriodId);
                $otherUnitOccupancy = $this->teacherAvailabilityService->getCrossUnitTeacherScheduleOccupancy(
                    $secondTeacherId, $academicPeriodId, $dayOfWeek, $slotNumber, $scheduleVersionId
                );
                if ($otherUnitOccupancy !== []) {
                    $other = $otherUnitOccupancy[0];
                    $conflictsFound[] = [
                        'schedule_version_id' => $scheduleVersionId,
                        'conflict_type' => 'CROSS_UNIT_TEACHER_DOUBLE_BOOKING',
                        'severity' => 'CRITICAL',
                        'description' => "Guru tim mengajar di unit lain ({$other['unit_name']} - Kelas {$other['class_name']}) pada hari {$entry['day_name']} slot {$slotNumber}.",
                        'entity_type' => 'TEACHER', 'entity_id' => $secondTeacherResourceId,
                        'primary_entry_id' => $entry['id'], 'conflicting_entry_id' => $other['id'],
                    ];
                }
            }
        }

        // 5. Check Teacher Availability Rules
        foreach ($entries as $entry) {
            $teacherId  = (int)$entry['teacher_id'];
            $teacherResourceId = $this->substitutionService->resolveResourceTeacherId($teacherId, $academicPeriodId);
            $dayOfWeek  = (int)$entry['day_of_week'];
            $slotNumber = (int)$entry['slot_number'];

            if (!$this->teacherAvailabilityService->isTeacherAvailable($teacherId, $academicPeriodId, $dayOfWeek, $slotNumber)) {
                $conflictsFound[] = [
                    'schedule_version_id'  => $scheduleVersionId,
                    'conflict_type'        => 'TEACHER_UNAVAILABLE',
                    'severity'             => 'CRITICAL',
                    'description'          => "Guru {$entry['teacher_name']} tidak bersedia mengajar pada {$entry['day_name']} slot {$slotNumber}.",
                    'entity_type'          => 'TEACHER',
                    'entity_id'            => $teacherResourceId,
                    'primary_entry_id'     => $entry['id'],
                    'conflicting_entry_id' => null,
                ];
            }
            if (!empty($entry['second_teacher_id']) && !$this->teacherAvailabilityService->isTeacherAvailable((int) $entry['second_teacher_id'], $academicPeriodId, $dayOfWeek, $slotNumber)) {
                $secondTeacherResourceId = $this->substitutionService->resolveResourceTeacherId((int) $entry['second_teacher_id'], $academicPeriodId);
                $conflictsFound[] = [
                    'schedule_version_id' => $scheduleVersionId,
                    'conflict_type' => 'TEACHER_UNAVAILABLE', 'severity' => 'CRITICAL',
                    'description' => "Guru tim tidak bersedia mengajar pada {$entry['day_name']} slot {$slotNumber}.",
                    'entity_type' => 'TEACHER', 'entity_id' => $secondTeacherResourceId,
                    'primary_entry_id' => $entry['id'], 'conflicting_entry_id' => null,
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
        $baseLessonCapacity = (int) $this->db->table('schedule_day_slots')
            ->where('schedule_version_id', $scheduleVersionId)->where('slot_type', 'LESSON')->countAllResults();
        $fixedByClassroom = [];
        $fixedRows = $this->db->table('schedule_fixed_activities')
            ->select('id, classroom_id, day_slot_id, title')
            ->where('schedule_version_id', $scheduleVersionId)->get()->getResultArray();
        foreach ($fixedRows as $fixedRow) {
            $classId = (int) $fixedRow['classroom_id'];
            if ($classId > 0) $fixedByClassroom[$classId] = ($fixedByClassroom[$classId] ?? 0) + 1;
        }

        // A fixed activity occupies the classroom slot just like a lesson.
        // This must be audited explicitly: otherwise the weekly JP count can
        // look complete while the printed grid hides one lesson underneath a
        // routine such as SID, Chapel, or the flag ceremony.
        $fixedCollisionKeys = [];
        foreach ($fixedRows as $fixedRow) {
            $fixedClassId = (int) $fixedRow['classroom_id'];
            $fixedSlotId = (int) $fixedRow['day_slot_id'];
            foreach ($entries as $entry) {
                // Subject-less entries are the teacher/workload projection of
                // the fixed routine itself (for example WORKED), not a lesson
                // competing with that routine.
                if ((int) ($entry['subject_id'] ?? 0) <= 0) {
                    continue;
                }
                if ((int) $entry['day_slot_id'] !== $fixedSlotId) {
                    continue;
                }
                if ($fixedClassId > 0 && (int) $entry['classroom_id'] !== $fixedClassId) {
                    continue;
                }

                $collisionKey = (int) $entry['id'] . ':' . (int) $fixedRow['id'];
                if (isset($fixedCollisionKeys[$collisionKey])) {
                    continue;
                }
                $fixedCollisionKeys[$collisionKey] = true;
                $activityTitle = trim((string) ($fixedRow['title'] ?? 'Kegiatan tetap'));
                $conflictsFound[] = [
                    'schedule_version_id' => $scheduleVersionId,
                    'conflict_type' => 'CLASS_FIXED_ACTIVITY_COLLISION',
                    'severity' => 'CRITICAL',
                    'description' => "Mapel {$entry['subject_name']} pada {$entry['class_name']} bertabrakan dengan kegiatan tetap {$activityTitle} pada {$entry['day_name']} slot {$entry['slot_number']}.",
                    'entity_type' => 'CLASSROOM',
                    'entity_id' => (int) $entry['classroom_id'],
                    'primary_entry_id' => (int) $entry['id'],
                    'conflicting_entry_id' => null,
                ];
            }
        }
        $requiredByClassroom = [];
        $entriesByRequirement = [];
        foreach ($entries as $entry) {
            $entryRequirementId = (int) ($entry['schedule_requirement_id'] ?? 0);
            if ($entryRequirementId > 0) {
                $entriesByRequirement[$entryRequirementId][] = $entry;
            }
        }
        $availableTeachingDays = (int) ($this->db->table('schedule_day_slots sds')
            ->select('COUNT(DISTINCT sd.day_of_week) AS total', false)
            ->join('schedule_days sd', 'sd.id = sds.day_id')
            ->where('sds.schedule_version_id', $scheduleVersionId)
            ->where('sds.slot_type', 'LESSON')
            ->get()->getRowArray()['total'] ?? 0);

        foreach ($requirements as $req) {
            $reqId = (int)$req['id'];
            $requiredHours = (float)$req['required_weekly_hours'];
            $classId = (int) $req['classroom_id'];
            $requiredByClassroom[$classId] = ($requiredByClassroom[$classId] ?? 0) + (int) ceil($requiredHours);

            $placedCount = $this->entryModel
                ->where('schedule_version_id', $scheduleVersionId)
                ->where('schedule_requirement_id', $reqId)
                ->countAllResults();

            if ((float)$placedCount < $requiredHours) {
                $diff = $requiredHours - (float)$placedCount;
                $reqMeta = $this->db->table('schedule_requirements sr')
                    ->select('s.name as subject_name, c.name as class_name, t.full_name as teacher_name')
                    ->join('subjects s', 's.id = sr.subject_id')
                    ->join('classrooms c', 'c.id = sr.classroom_id')
                    ->join('teachers t', 't.id = sr.teacher_id', 'left')
                    ->where('sr.id', $reqId)->get()->getRowArray();
                $reqLabel = !empty($reqMeta['subject_name']) ? "Mapel {$reqMeta['subject_name']} pada {$reqMeta['class_name']}" : "kebutuhan jadwal #{$reqId}";
                $conflictsFound[] = [
                    'schedule_version_id'  => $scheduleVersionId,
                    'conflict_type'        => 'UNMET_HOURS',
                    'severity'             => 'HIGH',
                    'description'          => "Jam pelajaran {$reqLabel} kurang {$diff} slot dari total kebutuhan {$requiredHours} jam.",
                    'entity_type'          => 'REQUIREMENT',
                    'entity_id'            => $reqId,
                    'primary_entry_id'     => null,
                    'conflicting_entry_id' => null,
                ];
            } elseif ((float)$placedCount > $requiredHours) {
                $diff = (float)$placedCount - $requiredHours;
                $reqMeta = $this->db->table('schedule_requirements sr')
                    ->select('s.name as subject_name, c.name as class_name, t.full_name as teacher_name')
                    ->join('subjects s', 's.id = sr.subject_id')
                    ->join('classrooms c', 'c.id = sr.classroom_id')
                    ->join('teachers t', 't.id = sr.teacher_id', 'left')
                    ->where('sr.id', $reqId)->get()->getRowArray();
                $reqLabel = !empty($reqMeta['subject_name'])
                    ? "Mapel {$reqMeta['subject_name']} pada {$reqMeta['class_name']}"
                    : "kebutuhan jadwal #{$reqId}";
                $conflictsFound[] = [
                    'schedule_version_id'  => $scheduleVersionId,
                    'conflict_type'        => 'OVERSCHEDULED_HOURS',
                    'severity'             => 'CRITICAL',
                    'description'          => "Jam pelajaran {$reqLabel} berlebih {$diff} slot dari total kebutuhan {$requiredHours} jam.",
                    'entity_type'          => 'REQUIREMENT',
                    'entity_id'            => $reqId,
                    'primary_entry_id'     => null,
                    'conflicting_entry_id' => null,
                ];
            }

            // Weekly hours are represented by indivisible 2-JP teaching
            // blocks. A 3-JP requirement is valid as 3 contiguous slots or
            // as 2+1, but never as three isolated single periods.
            $integerHours = (int) ceil($requiredHours);
            $requiredPairs = intdiv($integerHours, 2);
            if ($placedCount === $integerHours && $requiredPairs > 0) {
                $slotsByDayForRequirement = [];
                foreach ($entriesByRequirement[$reqId] ?? [] as $placedEntry) {
                    $slotsByDayForRequirement[(int) $placedEntry['day_of_week']][] = (int) $placedEntry['slot_number'];
                }

                $availablePairs = $this->countContiguousPairs($slotsByDayForRequirement);

                if ($availablePairs < $requiredPairs) {
                    $sample = ($entriesByRequirement[$reqId] ?? [])[0] ?? [];
                    $subjectName = $sample['subject_name'] ?? ('#' . (int) $req['subject_id']);
                    $className = $sample['class_name'] ?? ('#' . $classId);
                    $conflictsFound[] = [
                        'schedule_version_id' => $scheduleVersionId,
                        'conflict_type' => 'CONTIGUOUS_JP_BLOCK_BROKEN',
                        'severity' => 'CRITICAL',
                        'description' => "Mapel {$subjectName} pada {$className} memiliki {$integerHours} JP, tetapi blok 2 JP terpecah menjadi jam tunggal. Susun kembali sebagai blok berurutan" . ($integerHours === 3 ? ' 3 JP atau 2+1.' : '.'),
                        'entity_type' => 'REQUIREMENT',
                        'entity_id' => $reqId,
                        'primary_entry_id' => null,
                        'conflicting_entry_id' => null,
                    ];
                }

                if ($integerHours === 4 && $availableTeachingDays >= 2) {
                    $days = array_map('intval', array_keys($slotsByDayForRequirement));
                    sort($days);
                    $validTwoByTwo = count($days) === 2;
                    foreach ($slotsByDayForRequirement as $numbers) {
                        sort($numbers);
                        $validTwoByTwo = $validTwoByTwo
                            && count($numbers) === 2
                            && $numbers[1] === $numbers[0] + 1;
                    }
                    if (!$validTwoByTwo) {
                        $sample = ($entriesByRequirement[$reqId] ?? [])[0] ?? [];
                        $subjectName = $sample['subject_name'] ?? ('#' . (int) $req['subject_id']);
                        $className = $sample['class_name'] ?? ('#' . $classId);
                        $conflictsFound[] = [
                            'schedule_version_id' => $scheduleVersionId,
                            'conflict_type' => 'FOUR_JP_BLOCK_PATTERN_INVALID',
                            'severity' => 'CRITICAL',
                            'description' => "Mapel {$subjectName} pada {$className} memiliki 4 JP dan wajib dibagi menjadi 2+2 pada dua hari berbeda.",
                            'entity_type' => 'REQUIREMENT',
                            'entity_id' => $reqId,
                            'primary_entry_id' => null,
                            'conflicting_entry_id' => null,
                        ];
                    }
                }
            }
        }

        foreach ($requiredByClassroom as $classId => $requiredHours) {
            $availableHours = $baseLessonCapacity - ($fixedByClassroom[$classId] ?? 0);
            if ($requiredHours > $availableHours) {
                $classroom = $this->db->table('classrooms')->select('name')->where('id', $classId)->get()->getRowArray();
                $conflictsFound[] = [
                    'schedule_version_id' => $scheduleVersionId,
                    'conflict_type' => 'CLASSROOM_WEEKLY_CAPACITY_EXCEEDED', 'severity' => 'CRITICAL',
                    'description' => "Kelas {$classroom['name']} membutuhkan {$requiredHours} JP, tetapi hanya tersedia {$availableHours} slot lesson efektif. Tambahkan " . ($requiredHours - $availableHours) . ' slot, kurangi beban mapel, atau pindahkan kegiatan rutin.',
                    'entity_type' => 'CLASSROOM', 'entity_id' => (int) $classId,
                    'primary_entry_id' => null, 'conflicting_entry_id' => null,
                ];
            }
        }

        // A weekly JP total can fit numerically while still being impossible
        // to place using the generator's mandatory 2-JP contiguous blocks.
        // Report that distinction explicitly instead of leaving the user with
        // a misleading UNMET_HOURS result.
        $requiredBlocksByClassroom = [];
        foreach ($requirements as $req) {
            $classId = (int) $req['classroom_id'];
            $hours = (int) ceil((float) $req['required_weekly_hours']);
            $requiredBlocksByClassroom[$classId] = ($requiredBlocksByClassroom[$classId] ?? 0) + (int) floor($hours / 2);
        }
        if ($requiredBlocksByClassroom !== []) {
            $slotRows = $this->db->table('schedule_day_slots sds')
                ->select('sds.id, sds.slot_number, sd.day_of_week')
                ->join('schedule_days sd', 'sd.id = sds.day_id')
                ->where('sds.schedule_version_id', $scheduleVersionId)
                ->where('sds.slot_type', 'LESSON')
                ->orderBy('sd.day_of_week', 'ASC')->orderBy('sds.slot_number', 'ASC')
                ->get()->getResultArray();
            $slotsByDay = [];
            foreach ($slotRows as $slotRow) {
                $slotsByDay[(int) $slotRow['day_of_week']][] = $slotRow;
            }
            $fixedKeys = [];
            foreach ($fixedRows as $fixedRow) {
                $fixedKeys[(int) $fixedRow['classroom_id'] . ':' . (int) $fixedRow['day_slot_id']] = true;
            }
            foreach ($requiredBlocksByClassroom as $classId => $requiredBlocks) {
                $availableBlocks = 0;
                foreach ($slotsByDay as $dayRows) {
                    $freeRun = 0;
                    foreach ($dayRows as $slotRow) {
                        $key = (int) $classId . ':' . (int) $slotRow['id'];
                        if (isset($fixedKeys[$key])) {
                            $availableBlocks += intdiv($freeRun, 2);
                            $freeRun = 0;
                        } else {
                            $freeRun++;
                        }
                    }
                    $availableBlocks += intdiv($freeRun, 2);
                }
                if ($requiredBlocks > $availableBlocks) {
                    $classroom = $this->db->table('classrooms')->select('name')->where('id', $classId)->get()->getRowArray();
                    $className = $classroom['name'] ?? ('#' . $classId);
                    $conflictsFound[] = [
                        'schedule_version_id' => $scheduleVersionId,
                        'conflict_type' => 'CONTIGUOUS_2JP_CAPACITY_EXCEEDED', 'severity' => 'CRITICAL',
                        'description' => "Kelas {$className} membutuhkan {$requiredBlocks} blok 2 JP, tetapi hanya tersedia {$availableBlocks} pasangan slot berurutan. Total JP bisa tampak cukup, namun susunan slot/rutin belum menyediakan blok yang cukup.",
                        'entity_type' => 'CLASSROOM', 'entity_id' => (int) $classId,
                        'primary_entry_id' => null, 'conflicting_entry_id' => null,
                    ];
                }
            }
        }

        // 8. Check spacing between meetings of the same requirement.
        $requirementDays = [];
        foreach ($entries as $entry) {
            $reqId = (int) ($entry['schedule_requirement_id'] ?? 0);
            if ($reqId > 0) $requirementDays[$reqId][(int) $entry['day_of_week']] = true;
        }

        // 9. Check spacing by actual classroom + subject, not only by
        // requirement. A curriculum can create multiple requirements for the
        // same subject, which previously allowed a next-day repeat to slip in.
        $classSubjectDays = [];
        foreach ($entries as $entry) {
            $classId = (int) $entry['classroom_id'];
            $subjectId = (int) ($entry['subject_id'] ?? 0);
            if ($subjectId > 0) {
                $classSubjectDays[$classId][$subjectId][(int) $entry['day_of_week']] = true;
            }
        }
        $requirementCountsByClassSubject=[];
        foreach($requirements as $requirement){
            $key=(int)$requirement['classroom_id'].':'.(int)$requirement['subject_id'];
            $requirementCountsByClassSubject[$key]=($requirementCountsByClassSubject[$key]??0)+1;
        }
        foreach ($classSubjectDays as $classId => $subjectSets) {
            foreach ($subjectSets as $subjectId => $daySet) {
                // A single requirement is already evaluated by the more
                // precise requirement-spacing rule below. Avoid displaying
                // the same pedagogical note twice under a second code.
                if (($requirementCountsByClassSubject[$classId.':'.$subjectId] ?? 0) <= 1) continue;
                $days = array_keys($daySet);
                sort($days);
                for ($i = 1, $count = count($days); $i < $count; $i++) {
                    if (($days[$i] - $days[$i - 1]) < 2) {
                        $sample = $this->db->table('schedule_entries se')
                            ->select('se.id, c.name as class_name, s.name as subject_name')
                            ->join('classrooms c', 'c.id = se.classroom_id')
                            ->join('subjects s', 's.id = se.subject_id', 'left')
                            ->where('se.schedule_version_id', $scheduleVersionId)
                            ->where('se.classroom_id', $classId)->where('se.subject_id', $subjectId)
                            ->orderBy('se.id', 'ASC')->get()->getRowArray();
                        $conflictsFound[] = [
                            'schedule_version_id' => $scheduleVersionId,
                            'conflict_type' => 'CLASS_SUBJECT_MEETINGS_TOO_CLOSE', 'severity' => 'MEDIUM',
                            'description' => "Mapel {$sample['subject_name']} pada kelas {$sample['class_name']} diulang pada hari berurutan. Sisakan minimal satu hari jeda.",
                            'entity_type' => 'SUBJECT', 'entity_id' => (int) $subjectId,
                            'primary_entry_id' => $sample['id'] ?? null, 'conflicting_entry_id' => null,
                        ];
                        break;
                    }
                }
            }
        }
        foreach ($requirementDays as $reqId => $daySet) {
            $days = array_keys($daySet);
            sort($days);
            for ($i = 1, $count = count($days); $i < $count; $i++) {
                if (($days[$i] - $days[$i - 1]) < 2) {
                    $reqMeta = $this->db->table('schedule_requirements sr')
                        ->select('s.name as subject_name, c.name as class_name, t.full_name as teacher_name')
                        ->join('subjects s', 's.id = sr.subject_id')
                        ->join('classrooms c', 'c.id = sr.classroom_id')
                        ->join('teachers t', 't.id = sr.teacher_id', 'left')
                        ->where('sr.id', $reqId)->get()->getRowArray();
                    $reqLabel = !empty($reqMeta['subject_name']) ? "Mapel {$reqMeta['subject_name']} pada {$reqMeta['class_name']}" : "kebutuhan jadwal #{$reqId}";
                    $conflictsFound[] = [
                        'schedule_version_id' => $scheduleVersionId,
                        'conflict_type' => 'SUBJECT_MEETINGS_TOO_CLOSE', 'severity' => 'HIGH',
                        'description' => "Pertemuan {$reqLabel} terlalu berdekatan; sisakan minimal satu hari jeda bila kapasitas memungkinkan.",
                        'entity_type' => 'REQUIREMENT', 'entity_id' => (int) $reqId,
                        'primary_entry_id' => null, 'conflicting_entry_id' => null,
                    ];
                    break;
                }
            }
        }

        // Persist current conflicts with an atomically unique canonical
        // fingerprint. The version row lock serializes two concurrent audits.
        $now = date('Y-m-d H:i:s');
        $entryMeta = [];
        foreach ($entries as $entry) {
            $entryMeta[(int) $entry['id']] = $entry;
        }
        foreach ($conflictsFound as $conflict) {
            $normalized = $this->normalizeConflict($conflict, $entryMeta, $now);
            $this->db->query(
                'INSERT INTO schedule_conflicts '
                . '(uuid, fingerprint, schedule_version_id, generation_run_id, conflict_code, conflict_type, severity, description, entity_type, entity_id, teacher_id, classroom_id, room_id, day_identity, start_time, end_time, status, detected_at, active_generation_scope, primary_entry_id, conflicting_entry_id, is_resolved, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?) '
                . 'ON DUPLICATE KEY UPDATE severity = VALUES(severity), description = VALUES(description), status = \'ACTIVE\', detected_at = VALUES(detected_at), is_resolved = 0, updated_at = VALUES(updated_at)',
                [
                    UuidService::v4(), $normalized['fingerprint'], $scheduleVersionId, null,
                    $normalized['conflict_code'], $conflict['conflict_type'], $conflict['severity'], $conflict['description'],
                    $conflict['entity_type'] ?? null, $conflict['entity_id'] ?? null,
                    $normalized['teacher_id'], $normalized['classroom_id'], $normalized['room_id'],
                    $normalized['day_identity'], $normalized['start_time'], $normalized['end_time'],
                    'ACTIVE', $now, 'CURRENT', $conflict['primary_entry_id'] ?? null,
                    $conflict['conflicting_entry_id'] ?? null, $now, $now,
                ]
            );
        }

        $criticalCount = count(array_filter($conflictsFound, fn($c) => $c['severity'] === 'CRITICAL'));

        return [
            'total_conflicts'    => count($conflictsFound),
            'critical_conflicts' => $criticalCount,
            'conflicts'          => $conflictsFound,
        ];
    }

    private function normalizeConflict(array $conflict, array $entryMeta, string $detectedAt): array
    {
        $entryIds = array_values(array_filter(array_map('intval', [
            $conflict['primary_entry_id'] ?? 0,
            $conflict['conflicting_entry_id'] ?? 0,
        ])));
        sort($entryIds, SORT_NUMERIC);

        $meta = null;
        foreach ($entryIds as $entryId) {
            if (isset($entryMeta[$entryId])) {
                $meta = $entryMeta[$entryId];
                break;
            }
        }
        $entityType = strtoupper((string) ($conflict['entity_type'] ?? ''));
        $entityId = ! empty($conflict['entity_id']) ? (int) $conflict['entity_id'] : null;
        $teacherId = $entityType === 'TEACHER' ? $entityId : null;
        $classroomId = $entityType === 'CLASSROOM' ? $entityId : null;
        $roomId = $entityType === 'ROOM' ? $entityId : null;
        $dayIdentity = $meta ? (string) ($meta['day_of_week'] ?? '') : null;
        $startTime = $meta['start_time'] ?? null;
        $endTime = $meta['end_time'] ?? null;
        $canonical = [
            'schedule_version_id' => (int) $conflict['schedule_version_id'],
            'conflict_code' => (string) $conflict['conflict_type'],
            'entry_ids' => $entryIds,
            'resource' => [$entityType, $entityId],
            'day' => $dayIdentity,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];

        return [
            'fingerprint' => hash('sha256', json_encode($canonical, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
            'conflict_code' => (string) $conflict['conflict_type'],
            'teacher_id' => $teacherId,
            'classroom_id' => $classroomId,
            'room_id' => $roomId,
            'day_identity' => $dayIdentity,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'detected_at' => $detectedAt,
        ];
    }

    /**
     * Counts non-overlapping 2-JP blocks without combining different days or
     * jumping across a missing lesson period.
     */
    private function countContiguousPairs(array $slotsByDay): int
    {
        $pairs = 0;
        foreach ($slotsByDay as $slotNumbers) {
            sort($slotNumbers, SORT_NUMERIC);
            $runLength = 0;
            $previousSlot = null;
            foreach ($slotNumbers as $slotNumber) {
                if ($previousSlot !== null && $slotNumber === $previousSlot + 1) {
                    $runLength++;
                } else {
                    $pairs += intdiv($runLength, 2);
                    $runLength = 1;
                }
                $previousSlot = $slotNumber;
            }
            $pairs += intdiv($runLength, 2);
        }

        return $pairs;
    }
}
