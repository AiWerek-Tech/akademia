<?php

namespace App\Services;

use App\Models\ScheduleDayModel;
use App\Models\ScheduleDaySlotModel;
use Config\Database;

class ScheduleSetupService
{
    private $db;

    private const DAYS = [
        'MON' => [1, 'Senin'],
        'TUE' => [2, 'Selasa'],
        'WED' => [3, 'Rabu'],
        'THU' => [4, 'Kamis'],
        'FRI' => [5, 'Jumat'],
        'SAT' => [6, 'Sabtu'],
        'SUN' => [7, 'Minggu'],
    ];

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function initialize(int $scheduleVersionId, int $unitId): array
    {
        // Automatically sync requirements from teaching assignments & curriculum first
        (new ScheduleRequirementSyncService())->syncFromAssignments($scheduleVersionId);

        $version = $this->db->table('schedule_versions')->where('id', $scheduleVersionId)->get()->getRowArray();
        if (! $version) {
            throw new \RuntimeException('Versi jadwal tidak ditemukan.');
        }

        $periodId = (int)$version['academic_period_id'];
        $planningRows = [];
        if (!empty($version['curriculum_version_id'])) {
            $pQuery = $this->db->table('curriculum_planning_settings')
                ->where('curriculum_version_id', $version['curriculum_version_id']);
            if ($unitId > 0) {
                $pQuery->where('unit_id', $unitId);
            }
            $planningRows = $pQuery->get()->getResultArray();
        }
        if (empty($planningRows) && $unitId > 0) {
            $planningRows = $this->db->table('curriculum_planning_settings')
                ->where('unit_id', $unitId)->get()->getResultArray();
        }
        if (empty($planningRows)) {
            // Combined schedule: fetch planning settings for all active curriculum versions in the period
            $planningRows = $this->db->table('curriculum_planning_settings cps')
                ->join('curriculum_versions cv', 'cv.id = cps.curriculum_version_id')
                ->where('cv.academic_period_id', $periodId)
                ->where('cv.is_active', 1)
                ->get()->getResultArray();
            if (empty($planningRows)) {
                $planningRows = $this->db->table('curriculum_planning_settings cps')
                    ->join('curriculum_versions cv', 'cv.id = cps.curriculum_version_id')
                    ->where('cv.academic_period_id', $periodId)
                    ->get()->getResultArray();
            }
        }

        $dayCodes = [];
        $mergedCapacities = [];
        $minutesPerJp = 40;
        $startTimeJp1 = '07:30';

        if (!empty($planningRows)) {
            $minutesPerJp = (int)($planningRows[0]['minutes_per_jp'] ?? 40);
            $startTimeJp1 = (string)($planningRows[0]['start_time_jp1'] ?? '07:30');

            foreach ($planningRows as $p) {
                $codes = json_decode((string)($p['selected_day_codes_json'] ?? '[]'), true);
                if (is_array($codes)) {
                    foreach ($codes as $cCode) {
                        $cCode = strtoupper((string)$cCode);
                        if (!in_array($cCode, $dayCodes, true)) {
                            $dayCodes[] = $cCode;
                        }
                    }
                }
                $rawCap = !empty($p['daily_jp_capacities_json'])
                    ? json_decode((string)$p['daily_jp_capacities_json'], true)
                    : null;
                $pCap = CurriculumPlanningService::normalizeDayCapacities(
                    $codes ?: ['MON', 'TUE', 'WED', 'THU', 'FRI'],
                    is_array($rawCap) ? $rawCap : null,
                    (float)($p['daily_jp_capacity'] ?? 8)
                );
                foreach ($pCap as $dCode => $val) {
                    $mergedCapacities[$dCode] = max((float)($mergedCapacities[$dCode] ?? 0), (float)$val);
                }
            }
        }

        if (empty($dayCodes)) {
            $dayCodes = ['MON', 'TUE', 'WED', 'THU', 'FRI'];
        }
        $period = $this->db->table('academic_periods')->select('academic_year_id')->where('id', $periodId)->get()->getRowArray();
        $operatingPolicy = (new AcademicOperatingSettingsService())->resolve((int) ($period['academic_year_id'] ?? 0), $unitId > 0 ? $unitId : null);
        if (!empty($operatingPolicy['conflict'])) {
            throw new \RuntimeException('Hari sekolah antarunit berbeda. Gunakan jadwal per unit atau samakan pengaturan operasional akademik.');
        }
        $dayCodes = $operatingPolicy['working_day_codes'];
        $dayCapacities = CurriculumPlanningService::normalizeDayCapacities(
            $dayCodes,
            !empty($mergedCapacities) ? $mergedCapacities : null,
            8.0
        );

        // Fetch intermission breaks from Master Routine Activities for unit/all
        $breakRoutineQuery = $this->db->table('school_routine_activities')
            ->where('is_active', 1)
            ->where('placement_zone', 'INTERMISSION_BREAK');
        if ($unitId > 0) {
            $breakRoutineQuery->groupStart()
                ->where('unit_id', $unitId)
                ->orWhere('unit_id IS NULL')
            ->groupEnd();
        }
        $breakRoutines = $breakRoutineQuery->get()->getResultArray();

        $breakOffsets = [];
        foreach ($breakRoutines as $br) {
            $afterSlot = (int)($br['placement_sequence'] ?? 5);
            $duration  = (int)($br['duration_minutes'] ?: 15);
            $breakOffsets[$afterSlot] = ($breakOffsets[$afterSlot] ?? 0) + $duration;
        }

        $dayModel = new ScheduleDayModel();
        $slotModel = new ScheduleDaySlotModel();

        $allowedDayNumbers = array_values(array_map(static fn (string $code): int => self::DAYS[$code][0], $dayCodes));
        $obsoleteSlots = $this->db->table('schedule_day_slots sds')
            ->select('sds.id, sd.day_of_week')
            ->join('schedule_days sd', 'sd.id = sds.day_id')
            ->where('sds.schedule_version_id', $scheduleVersionId)
            ->whereNotIn('sd.day_of_week', $allowedDayNumbers)
            ->get()->getResultArray();
        if ($obsoleteSlots !== []) {
            $obsoleteIds = array_map('intval', array_column($obsoleteSlots, 'id'));
            if ($this->db->table('schedule_entries')->whereIn('day_slot_id', $obsoleteIds)->countAllResults() > 0) {
                throw new \RuntimeException('Jadwal masih berisi pelajaran pada hari yang dinonaktifkan. Pindahkan entri tersebut sebelum sinkronisasi hari sekolah.');
            }
            $this->db->table('schedule_candidate_entries')->whereIn('day_slot_id', $obsoleteIds)->delete();
            $this->db->table('schedule_fixed_activities')->whereIn('day_slot_id', $obsoleteIds)->delete();
            $this->db->table('schedule_day_slots')->whereIn('id', $obsoleteIds)->delete();
        }

        // For combined schedules (unitId=0), resolve to the first accessible
        // unit for the schedule_days FK.  Days are conceptual (Mon-Fri) and
        // already shared across units via day_of_week, so we just need a valid
        // FK reference.
        $dayUnitId = $unitId;
        if ($dayUnitId <= 0) {
            $accessibleIds = UnitScopeService::accessibleUnitIds();
            $dayUnitId = $accessibleIds[0] ?? 0;
            if ($dayUnitId <= 0) {
                // Fallback: pick any active unit
                $anyUnit = $this->db->table('school_units')
                    ->where('is_active', 1)->where('deleted_at IS NULL')
                    ->orderBy('id', 'ASC')->get()->getRowArray();
                $dayUnitId = (int) ($anyUnit['id'] ?? 0);
            }
            if ($dayUnitId <= 0) {
                throw new \RuntimeException('Tidak dapat menentukan unit sekolah untuk inisialisasi hari jadwal.');
            }
        }

        $this->db->transException(true)->transStart();

        // Purge old un-applied candidate entries & runs for this schedule version
        // to prevent FK constraint errors when day slots are updated or deleted
        $oldRunRows = $this->db->table('schedule_generation_runs')
            ->select('id')
            ->where('schedule_version_id', $scheduleVersionId)
            ->get()->getResultArray();

        if (!empty($oldRunRows)) {
            $runIds = array_map('intval', array_column($oldRunRows, 'id'));
            $oldCandidateRows = $this->db->table('schedule_generation_candidates')
                ->select('id')
                ->whereIn('generation_run_id', $runIds)
                ->where('is_applied', 0)
                ->get()->getResultArray();

            if (!empty($oldCandidateRows)) {
                $candidateIds = array_map('intval', array_column($oldCandidateRows, 'id'));
                $this->db->table('schedule_candidate_entries')
                    ->whereIn('candidate_id', $candidateIds)
                    ->delete();
                $this->db->table('schedule_generation_candidates')
                    ->whereIn('id', $candidateIds)
                    ->delete();
            }

            foreach ($runIds as $rId) {
                $remaining = $this->db->table('schedule_generation_candidates')
                    ->where('generation_run_id', $rId)
                    ->countAllResults();
                if ($remaining === 0) {
                    $this->db->table('schedule_generation_runs')
                        ->where('id', $rId)
                        ->delete();
                }
            }
        }

        $createdDays = 0;
        $createdSlots = 0;

        foreach ($dayCodes as $dayCode) {
            if (! isset(self::DAYS[$dayCode])) {
                continue;
            }

            [$dayNumber, $dayName] = self::DAYS[$dayCode];

            // For combined schedules, try to find existing day from ANY unit first
            if ($unitId <= 0) {
                $day = $dayModel->where('day_of_week', $dayNumber)
                    ->where('is_school_day', 1)
                    ->orderBy('id', 'ASC')->first();
            } else {
                $day = $dayModel->where('school_unit_id', $unitId)
                    ->where('day_of_week', $dayNumber)->first();
            }

            if (! $day) {
                $dayModel->insert([
                    'uuid'           => UuidService::v4(),
                    'school_unit_id' => $dayUnitId,
                    'day_of_week'    => $dayNumber,
                    'day_name'       => $dayName,
                    'is_school_day'  => 1,
                ]);
                $dayId = (int) $dayModel->insertID();
                $createdDays++;
            } else {
                $dayId = (int) $day['id'];
                if ((int) $day['is_school_day'] !== 1) {
                    $dayModel->update($dayId, ['is_school_day' => 1]);
                }
            }

            $defaultCapacity = !empty($planningRows) ? (float) ($planningRows[0]['daily_jp_capacity'] ?? 8) : 8.0;
            $slotCount = max(1, min(20, (int) floor((float) ($dayCapacities[$dayCode] ?? $defaultCapacity))));

            // Safely delete excess slots for this day if capacity reduced
            $excessSlots = $this->db->table('schedule_day_slots')
                ->select('id')
                ->where('schedule_version_id', $scheduleVersionId)
                ->where('day_id', $dayId)
                ->where('slot_number >', $slotCount)
                ->get()->getResultArray();

            if (!empty($excessSlots)) {
                $excessIds = array_map('intval', array_column($excessSlots, 'id'));
                $this->db->table('schedule_candidate_entries')->whereIn('day_slot_id', $excessIds)->delete();
                $this->db->table('schedule_entries')->whereIn('day_slot_id', $excessIds)->delete();
                $this->db->table('schedule_fixed_activities')->whereIn('day_slot_id', $excessIds)->delete();
                $this->db->table('schedule_day_slots')->whereIn('id', $excessIds)->delete();
            }

            for ($slot = 1; $slot <= $slotCount; $slot++) {
                [$startTime, $endTime] = $this->slotTime($slot, $minutesPerJp, $startTimeJp1, $breakOffsets);

                $exists = $slotModel->where('schedule_version_id', $scheduleVersionId)
                    ->where('day_id', $dayId)->where('slot_number', $slot)->first();
                if ($exists) {
                    if ($exists['start_time'] !== $startTime || $exists['end_time'] !== $endTime) {
                        $slotModel->update($exists['id'], [
                            'start_time' => $startTime,
                            'end_time'   => $endTime,
                        ]);
                    }
                    continue;
                }

                $slotModel->insert([
                    'uuid'                => UuidService::v4(),
                    'schedule_version_id' => $scheduleVersionId,
                    'day_id'              => $dayId,
                    'slot_number'         => $slot,
                    'start_time'          => $startTime,
                    'end_time'            => $endTime,
                    'slot_type'           => 'LESSON',
                    'label'               => 'JP ' . $slot,
                ]);
                $createdSlots++;
            }
        }

        $sync = (new ScheduleRequirementSyncService())->syncFromAssignments($scheduleVersionId);
        $routineSync = (new RoutineActivityScheduleSyncService())->syncForVersion($scheduleVersionId);
        $this->db->transComplete();

        return [
            'created_days'  => $createdDays,
            'created_slots' => $createdSlots,
            'requirements'  => $sync,
            'routines'      => $routineSync,
        ];
    }

    private function slotTime(int $slot, int $minutesPerJp, string $startTimeJp1 = '07:30', array $breakOffsets = []): array
    {
        $parts = explode(':', $startTimeJp1);
        $startHour = isset($parts[0]) ? (int)$parts[0] : 7;
        $startMin  = isset($parts[1]) ? (int)$parts[1] : 30;
        $baseMinutes = ($startHour * 60) + $startMin;

        // Calculate total break minutes before this slot
        $totalBreakMinutes = 0;
        foreach ($breakOffsets as $afterSlot => $breakDuration) {
            if ($slot > (int)$afterSlot) {
                $totalBreakMinutes += (int)$breakDuration;
            }
        }

        $slotStartMinutes = $baseMinutes + (($slot - 1) * $minutesPerJp) + $totalBreakMinutes;
        $slotEndMinutes   = $slotStartMinutes + $minutesPerJp;

        return [
            $this->formatMinutes($slotStartMinutes),
            $this->formatMinutes($slotEndMinutes)
        ];
    }

    private function formatMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);
    }
}
