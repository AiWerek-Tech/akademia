<?php

namespace App\Services;

use App\Models\RoutineActivityModel;
use App\Models\ScheduleFixedActivityModel;
use App\Models\ScheduleEntryModel;
use App\Models\ScheduleVersionModel;
use Config\Database;

class RoutineActivityScheduleSyncService
{
    private $db;
    private RoutineActivityModel $routineModel;
    private ScheduleFixedActivityModel $fixedModel;
    private ScheduleEntryModel $entryModel;
    private ScheduleVersionModel $versionModel;

    public function __construct()
    {
        $this->db           = Database::connect();
        $this->routineModel = new RoutineActivityModel();
        $this->fixedModel   = new ScheduleFixedActivityModel();
        $this->entryModel   = new ScheduleEntryModel();
        $this->versionModel = new ScheduleVersionModel();
    }

    /**
     * Synchronize Routine Activities into a Schedule Version's Fixed Activities & Locked Entries.
     */
    public function syncForVersion(int $scheduleVersionId): array
    {
        $version = $this->versionModel->find($scheduleVersionId);
        if (!$version) {
            throw new \RuntimeException("Schedule version ID {$scheduleVersionId} not found.");
        }

        $unitId = !empty($version['unit_id']) ? (int)$version['unit_id'] : null;

        // 1. Fetch active routine activities applicable to this unit or all units
        $builder = $this->routineModel->where('is_active', 1);
        if ($unitId > 0) {
            $builder->groupStart()
                ->where('unit_id', $unitId)
                ->orWhere('unit_id IS NULL')
                ->groupEnd();
        }
        $routines = $builder->findAll();

        // 2. Fetch classrooms for this unit / period
        $classroomsBuilder = $this->db->table('classrooms')
            ->where('is_active', 1)
            ->where('deleted_at IS NULL');
        if ($unitId > 0) {
            $classroomsBuilder->where('unit_id', $unitId);
        }
        if (!empty($version['academic_period_id'])) {
            $classroomsBuilder->where('academic_period_id', (int)$version['academic_period_id']);
        }
        $classrooms = $classroomsBuilder->get()->getResultArray();

        // 3. Fetch day slots for this schedule version
        $daySlots = $this->db->table('schedule_day_slots sds')
            ->select('sds.*, sd.day_of_week, sd.day_name')
            ->join('schedule_days sd', 'sd.id = sds.day_id')
            ->where('sds.schedule_version_id', $scheduleVersionId)
            ->get()->getResultArray();

        // Map day_of_week (1=Mon, 2=Tue, 3=Wed, 4=Thu, 5=Fri, 6=Sat) & slot_number
        $daySlotMap = [];
        foreach ($daySlots as $ds) {
            $dayOfWeek = (int)$ds['day_of_week'];
            $slotNum   = (int)$ds['slot_number'];
            $daySlotMap[$dayOfWeek][$slotNum] = $ds;
        }

        $dayCodeMap = [
            'MONDAY'    => 1,
            'TUESDAY'   => 2,
            'WEDNESDAY' => 3,
            'THURSDAY'  => 4,
            'FRIDAY'    => 5,
            'SATURDAY'  => 6,
        ];

        $syncedFixedCount   = 0;
        $syncedEntriesCount = 0;

        $this->db->transException(true)->transBegin();

        try {
            // Remove only fixed rows that were materialized from a known routine.
            // Older code deleted every fixed activity and even academic subjects
            // with a matching name/code, which could silently destroy real data.
            $allRoutineQuery = $this->db->table('school_routine_activities')->select('code');
            if ($unitId > 0) {
                $allRoutineQuery->groupStart()->where('unit_id', $unitId)->orWhere('unit_id IS NULL')->groupEnd();
            }
            $knownRoutineCodes = array_values(array_filter(array_map(
                static fn (array $row): string => trim((string) ($row['code'] ?? '')),
                $allRoutineQuery->get()->getResultArray()
            )));
            $oldFixedRows = $this->db->table('schedule_fixed_activities')
                ->select('id, day_slot_id, classroom_id, description')
                ->where('schedule_version_id', $scheduleVersionId)
                ->get()->getResultArray();
            $oldRoutineFixedIds = [];
            foreach ($oldFixedRows as $oldFixed) {
                $materializedCode = trim(explode(' | ', (string) ($oldFixed['description'] ?? ''), 2)[0]);
                if ($materializedCode === '' || ! in_array($materializedCode, $knownRoutineCodes, true)) {
                    continue;
                }
                $oldRoutineFixedIds[] = (int) $oldFixed['id'];
                $this->db->table('schedule_entries')
                    ->where('schedule_version_id', $scheduleVersionId)
                    ->where('day_slot_id', (int) $oldFixed['day_slot_id'])
                    ->where('classroom_id', (int) $oldFixed['classroom_id'])
                    ->where('is_locked', 1)
                    ->where('schedule_requirement_id IS NULL')
                    ->where('subject_id IS NULL')
                    ->delete();
            }
            if ($oldRoutineFixedIds !== []) {
                $this->db->table('schedule_fixed_activities')->whereIn('id', $oldRoutineFixedIds)->delete();
            }

            foreach ($routines as $routine) {
            $zone = $routine['placement_zone'] ?? 'ACADEMIC_JP';

            // Only routines assigned to ACADEMIC_JP zone belong inside JP 1..N slot cells.
            // PRE_ACADEMIC, INTERMISSION_BREAK, and POST_ACADEMIC are non-academic banner rows.
            if ($zone !== 'ACADEMIC_JP') {
                continue;
            }

            if (empty($routine['locked_period_start'])) {
                continue;
            }

            $routineDay = strtoupper((string)($routine['default_day'] ?? ''));
            $targetDays = [];

            if ($routineDay === 'ALL_DAYS' || empty($routineDay)) {
                $targetDays = [1, 2, 3, 4, 5]; // Mon - Fri
            } elseif (isset($dayCodeMap[$routineDay])) {
                $targetDays = [$dayCodeMap[$routineDay]];
            }

            $startP     = (int)$routine['locked_period_start'];
            $endP       = (int)($routine['locked_period_end'] ?: $startP);
            $durationJp = (float)($routine['default_duration_jp'] ?: 1.0);
            if ($endP < $startP && $durationJp > 1.0) {
                $endP = $startP + (int)ceil($durationJp) - 1;
            }

            foreach ($classrooms as $c) {
                $classId   = (int)$c['id'];
                $homeroomId= !empty($c['homeroom_teacher_id']) ? (int)$c['homeroom_teacher_id'] : null;

                // Resolve teacher ID if routine counts as teaching load
                $teacherId = null;
                if ((int)($routine['counts_as_teaching_load'] ?? 0) === 1) {
                    $strategy = $routine['assignment_strategy'] ?? 'NONE';
                    if ($strategy === 'SPECIFIC_TEACHER' && !empty($routine['specific_teacher_id'])) {
                        $teacherId = (int)$routine['specific_teacher_id'];
                    } elseif ($strategy === 'ROLE_BASED' && ($routine['assignment_role_default'] === 'Wali Kelas' || empty($routine['assignment_role_default']))) {
                        $teacherId = $homeroomId;
                    }
                }

                foreach ($targetDays as $dayOfWeek) {
                    for ($p = $startP; $p <= $endP; $p++) {
                        $ds = $daySlotMap[$dayOfWeek][$p] ?? null;
                        if (!$ds) {
                            continue;
                        }

                        $daySlotId = (int)$ds['id'];

                        // Insert fixed activity record
                        $fixedUnitId = $unitId ?: (!empty($c['unit_id']) ? (int)$c['unit_id'] : null);
                        $this->fixedModel->insert([
                            'uuid'                => UuidService::v4(),
                            'schedule_version_id' => $scheduleVersionId,
                            'day_slot_id'         => $daySlotId,
                            'school_unit_id'      => $fixedUnitId,
                            'classroom_id'        => $classId,
                            'title'               => $routine['name'],
                            'activity_type'       => $routine['activity_type'],
                            'description'         => $routine['code'] . ' | ' . ($routine['notes'] ?? ''),
                        ]);
                        $syncedFixedCount++;

                        // If routine counts as teaching load & teacher assigned, lock in schedule_entries
                        if ($teacherId > 0) {
                            // Check if entry exists for this slot & classroom
                            $existingEntry = $this->db->table('schedule_entries')
                                ->where('schedule_version_id', $scheduleVersionId)
                                ->where('day_slot_id', $daySlotId)
                                ->where('classroom_id', $classId)
                                ->get()->getRowArray();

                            if (!$existingEntry) {
                                $this->entryModel->insert([
                                    'uuid'                    => UuidService::v4(),
                                    'schedule_version_id'     => $scheduleVersionId,
                                    'day_slot_id'             => $daySlotId,
                                    'schedule_requirement_id' => null,
                                    'classroom_id'            => $classId,
                                    'teacher_id'              => $teacherId,
                                    'subject_id'              => null,
                                    'is_locked'               => 1,
                                    'created_by'              => session()->get('user_id'),
                                ]);
                                $syncedEntriesCount++;
                            }
                        }
                    }
                }
            }
            }

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }

        return [
            'synced_fixed'   => $syncedFixedCount,
            'synced_entries' => $syncedEntriesCount,
            'routines_count' => count($routines),
        ];
    }
}
