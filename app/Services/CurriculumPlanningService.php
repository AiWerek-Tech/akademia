<?php

namespace App\Services;

use App\Models\CurriculumPlanningSettingModel;
use App\Models\WorkloadPolicyModel;
use Config\Database;

class CurriculumPlanningService
{
    public const ALLOWED_DAY_CODES = ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'];

    public const PRESET_DAY_CODES = [
        5 => ['MON', 'TUE', 'WED', 'THU', 'FRI'],
        6 => ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'],
        7 => ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'],
    ];

    private const DEFAULTS = [
        'teaching_days_per_week' => 5,
        'selected_day_codes'     => ['MON', 'TUE', 'WED', 'THU', 'FRI'],
        'daily_jp_capacity'      => 9.0,
        'daily_jp_capacities'    => ['MON' => 9.0, 'TUE' => 9.0, 'WED' => 9.0, 'THU' => 9.0, 'FRI' => 7.0],
        'minutes_per_jp'         => 40,
        'start_time_jp1'         => '07:30',
        'allow_custom_hours'     => 1,
        'workload_policy_id'     => null,
        'teacher_minimum_hours'  => 24.0,
        'teacher_maximum_hours'  => 40.0,
        'notes'                  => null,
        'revision_number'        => 1,
    ];

    public static function getSettings(int $versionId, int $unitId): array
    {
        $row = (new CurriculumPlanningSettingModel())
            ->where('curriculum_version_id', $versionId)
            ->where('unit_id', $unitId)
            ->first();

        $version = Database::connect()->table('curriculum_versions')->where('id', $versionId)->get()->getRowArray();
        $academicPeriodId = (int)($version['academic_period_id'] ?? 0);
        $unit = Database::connect()->table('school_units')->where('id', $unitId)->get()->getRowArray();
        $defaultMinutes = ($unit && strtoupper((string)$unit['code']) === 'SMA') ? 45 : 40;

        if (!$row) {
            $days = ($unit && strtoupper((string)$unit['code']) === 'SMA') ? 6 : 5;
            $dayCodes = self::PRESET_DAY_CODES[$days] ?? self::PRESET_DAY_CODES[5];

            $data = self::DEFAULTS;
            $data['curriculum_version_id'] = $versionId;
            $data['unit_id'] = $unitId;
            $data['teaching_days_per_week'] = $days;
            $data['minutes_per_jp'] = $defaultMinutes;
            $data['selected_day_codes'] = $dayCodes;
            $data['selected_day_codes_json'] = json_encode($dayCodes);
            $data['daily_jp_capacities'] = self::defaultDayCapacities($dayCodes, (float)$data['daily_jp_capacity']);
            $data['daily_jp_capacities_json'] = json_encode($data['daily_jp_capacities']);

            $policyInfo = self::resolveWorkloadPolicy($academicPeriodId, $unitId);
            $data['teacher_minimum_hours'] = (float)($policyInfo['minimum_teaching_hours'] ?? 24.0);
            $data['teacher_maximum_hours'] = (float)($policyInfo['maximum_total_hours'] ?? 40.0);
            return self::applyOperatingDayOverride($data, $academicPeriodId, $unitId);
        }

        $selectedCodes = !empty($row['selected_day_codes_json'])
            ? json_decode($row['selected_day_codes_json'], true)
            : (self::PRESET_DAY_CODES[(int)$row['teaching_days_per_week']] ?? self::PRESET_DAY_CODES[5]);

        $row['selected_day_codes'] = is_array($selectedCodes) ? array_values($selectedCodes) : self::PRESET_DAY_CODES[5];
        $rawCapacities = !empty($row['daily_jp_capacities_json'])
            ? json_decode($row['daily_jp_capacities_json'], true)
            : null;
        $merged = array_merge(self::DEFAULTS, $row);
        if (empty($merged['minutes_per_jp']) || (int)$merged['minutes_per_jp'] <= 0) {
            $merged['minutes_per_jp'] = $defaultMinutes;
        }
        $merged['daily_jp_capacities'] = self::normalizeDayCapacities(
            $merged['selected_day_codes'],
            is_array($rawCapacities) ? $rawCapacities : null,
            (float)($merged['daily_jp_capacity'] ?? 9.0)
        );

        $policyInfo = self::resolveWorkloadPolicy($academicPeriodId, $unitId, $merged['workload_policy_id'] ?? null);
        $merged['teacher_minimum_hours'] = (float)($policyInfo['minimum_teaching_hours'] ?? 24.0);
        $merged['teacher_maximum_hours'] = (float)($policyInfo['maximum_total_hours'] ?? 40.0);

        return self::applyOperatingDayOverride($merged, $academicPeriodId, $unitId);
    }

    private static function applyOperatingDayOverride(array $settings, int $academicPeriodId, int $unitId): array
    {
        if ($academicPeriodId <= 0 || $unitId <= 0 || !Database::connect()->tableExists('academic_operating_settings')) {
            return $settings;
        }
        $period = Database::connect()->table('academic_periods')->select('academic_year_id')->where('id', $academicPeriodId)->get()->getRowArray();
        if (!$period) return $settings;
        $policy = (new AcademicOperatingSettingsService())->resolve((int) $period['academic_year_id'], $unitId);
        if (($policy['source'] ?? '') === 'GLOBAL_CUSTOM') {
            $settings['teaching_days_per_week'] = count($policy['working_day_codes']);
            $settings['selected_day_codes'] = $policy['working_day_codes'];
            $settings['selected_day_codes_json'] = json_encode($policy['working_day_codes']);
            $settings['daily_jp_capacities'] = self::normalizeDayCapacities(
                $policy['working_day_codes'],
                $settings['daily_jp_capacities'] ?? null,
                (float) ($settings['daily_jp_capacity'] ?? 9)
            );
            $settings['operating_day_source'] = 'GLOBAL_CUSTOM';
        } else {
            $settings['operating_day_source'] = 'CURRICULUM';
        }
        return $settings;
    }

    public static function saveSettings(int $versionId, int $unitId, array $input): array
    {
        $db = Database::connect();
        $version = $db->table('curriculum_versions')->where('id', $versionId)->get()->getRowArray();
        if (!$version) {
            throw new \InvalidArgumentException('Versi kurikulum tidak ditemukan.');
        }

        if (in_array(strtoupper((string)$version['workflow_status']), ['LOCKED', 'APPROVED', 'ARCHIVED'], true)) {
            throw new \RuntimeException('Versi kurikulum telah terkunci (LOCKED/APPROVED/ARCHIVED) dan tidak dapat diubah.');
        }

        $days = (int) ($input['teaching_days_per_week'] ?? 5);
        $dailyCapacity = (float) ($input['daily_jp_capacity'] ?? 9);
        $minutesPerJp = (int) ($input['minutes_per_jp'] ?? 40);

        if ($days < 1 || $days > 7) {
            throw new \InvalidArgumentException('Jumlah hari belajar harus antara 1 sampai 7 hari.');
        }
        if ($dailyCapacity <= 0 || $dailyCapacity > 20) {
            throw new \InvalidArgumentException('Kapasitas JP per hari harus lebih dari 0 dan maksimal 20 JP.');
        }
        if ($minutesPerJp < 15 || $minutesPerJp > 120) {
            throw new \InvalidArgumentException('Durasi 1 JP harus antara 15 sampai 120 menit.');
        }

        $rawDayCodes = $input['selected_day_codes'] ?? null;
        if (is_string($rawDayCodes)) {
            $dayCodes = array_map('trim', explode(',', $rawDayCodes));
        } elseif (is_array($rawDayCodes)) {
            $dayCodes = array_values(array_map('trim', $rawDayCodes));
        } else {
            $dayCodes = self::PRESET_DAY_CODES[$days] ?? self::PRESET_DAY_CODES[5];
        }

        $dayCodes = array_values(array_filter($dayCodes));

        if (count($dayCodes) !== $days) {
            throw new \InvalidArgumentException("Jumlah kode hari yang dipilih (" . count($dayCodes) . ") harus sama dengan jumlah hari belajar per minggu ({$days}).");
        }

        if (count($dayCodes) !== count(array_unique($dayCodes))) {
            throw new \InvalidArgumentException('Kode hari tidak boleh duplikat.');
        }

        foreach ($dayCodes as $code) {
            if (!in_array(strtoupper($code), self::ALLOWED_DAY_CODES, true)) {
                throw new \InvalidArgumentException('Kode hari tidak valid: ' . $code);
            }
        }
        $dayCodes = array_values(array_map('strtoupper', $dayCodes));

        $dayCapacities = self::normalizeDayCapacities(
            $dayCodes,
            $input['daily_jp_capacities'] ?? null,
            $dailyCapacity
        );
        foreach ($dayCapacities as $code => $capacity) {
            if ($capacity <= 0 || $capacity > 20) {
                throw new \InvalidArgumentException("Kapasitas JP hari {$code} harus lebih dari 0 dan maksimal 20 JP.");
            }
        }

        $model = new CurriculumPlanningSettingModel();
        $existing = $model->where('curriculum_version_id', $versionId)->where('unit_id', $unitId)->first();

        if ($existing && isset($input['revision_number']) && (int)$input['revision_number'] !== (int)$existing['revision_number']) {
            throw new \RuntimeException('Stale Data Error: Pengaturan perencanaan telah diubah oleh pengguna lain. Silakan muat ulang.');
        }

        $policyId = !empty($input['workload_policy_id']) ? (int)$input['workload_policy_id'] : null;

        $startTimeJp1 = trim((string)($input['start_time_jp1'] ?? '07:30'));
        if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $startTimeJp1)) {
            $startTimeJp1 = '07:30';
        }
        if (strlen($startTimeJp1) > 5) {
            $startTimeJp1 = substr($startTimeJp1, 0, 5);
        }

        $data = [
            'curriculum_version_id'   => $versionId,
            'unit_id'                 => $unitId,
            'workload_policy_id'     => $policyId,
            'teaching_days_per_week' => $days,
            'selected_day_codes_json'=> json_encode($dayCodes),
            'daily_jp_capacity'      => $dailyCapacity,
            'daily_jp_capacities_json' => json_encode($dayCapacities),
            'minutes_per_jp'         => $minutesPerJp,
            'start_time_jp1'         => $startTimeJp1,
            'allow_custom_hours'     => !empty($input['allow_custom_hours']) ? 1 : 0,
            'notes'                  => trim((string) ($input['notes'] ?? '')) ?: null,
            'updated_by'              => session()->has('user_id') ? session()->get('user_id') : 1,
        ];

        if ($existing) {
            $data['revision_number'] = (int)$existing['revision_number'] + 1;
            $model->update($existing['id'], $data);
            return self::getSettings($versionId, $unitId);
        }

        $data['revision_number'] = 1;
        $data['created_by'] = session()->has('user_id') ? session()->get('user_id') : 1;
        $id = $model->insert($data, true);
        return self::getSettings($versionId, $unitId);
    }

    public static function resolveWorkloadPolicy(int $academicPeriodId, int $unitId, ?int $explicitPolicyId = null): array
    {
        $db = Database::connect();

        if ($explicitPolicyId) {
            $policy = $db->table('workload_policies')
                ->where('id', $explicitPolicyId)
                ->where('is_active', 1)
                ->get()->getRowArray();
            if ($policy) {
                return [
                    'policy_id'              => (int)$policy['id'],
                    'policy_source'          => 'EXPLICIT',
                    'status'                 => 'FOUND',
                    'minimum_teaching_hours' => (float)$policy['minimum_teaching_hours'],
                    'target_total_hours'     => (float)$policy['target_total_hours'],
                    'maximum_total_hours'    => (float)$policy['maximum_total_hours'],
                ];
            }
        }

        $policy = $db->table('workload_policies')
            ->where('academic_period_id', $academicPeriodId)
            ->where('is_active', 1)
            ->groupStart()
                ->where('unit_id', $unitId)
                ->orWhere('unit_id', null)
            ->groupEnd()
            ->orderBy('unit_id IS NOT NULL', 'DESC', false)
            ->orderBy('priority', 'DESC')
            ->get(1)->getRowArray();

        if ($policy) {
            return [
                'policy_id'              => (int)$policy['id'],
                'policy_source'          => $policy['unit_id'] ? 'UNIT_SPECIFIC' : 'GLOBAL',
                'status'                 => 'FOUND',
                'minimum_teaching_hours' => (float)$policy['minimum_teaching_hours'],
                'target_total_hours'     => (float)$policy['target_total_hours'],
                'maximum_total_hours'    => (float)$policy['maximum_total_hours'],
            ];
        }

        return [
            'policy_id'              => null,
            'policy_source'          => 'DEFAULT_FALLBACK',
            'status'                 => 'NO_POLICY',
            'minimum_teaching_hours' => 24.0,
            'target_total_hours'     => 40.0,
            'maximum_total_hours'    => 40.0,
        ];
    }

    public static function computeWeeklyCapacity(array $settings): float
    {
        $capacities = $settings['daily_jp_capacities'] ?? null;
        $activeCodes = $settings['selected_day_codes'] ?? null;

        if (is_array($capacities) && $capacities !== []) {
            if (is_array($activeCodes) && $activeCodes !== []) {
                $activeCodesUpper = array_map(static fn($c) => strtoupper((string)$c), $activeCodes);
                $sum = 0.0;
                foreach ($capacities as $code => $val) {
                    if (in_array(strtoupper((string)$code), $activeCodesUpper, true)) {
                        $sum += (float)$val;
                    }
                }
                return $sum;
            }
            return array_reduce($capacities, static fn(float $sum, $value): float => $sum + (float)$value, 0.0);
        }

        return (float)($settings['teaching_days_per_week'] ?? 5) * (float)($settings['daily_jp_capacity'] ?? 9.0);
    }

    public static function computeAcademicRoutineReservation(int $unitId, array $activeDayCodes): float
    {
        $db = Database::connect();
        $active = array_values(array_unique(array_map(static fn($code): string => strtoupper((string) $code), $activeDayCodes)));
        if ($active === []) {
            $active = ['MON', 'TUE', 'WED', 'THU', 'FRI'];
        }

        $dayAliases = [
            'MONDAY' => 'MON', 'SENIN' => 'MON',
            'TUESDAY' => 'TUE', 'SELASA' => 'TUE',
            'WEDNESDAY' => 'WED', 'RABU' => 'WED',
            'THURSDAY' => 'THU', 'KAMIS' => 'THU',
            'FRIDAY' => 'FRI', 'JUMAT' => 'FRI',
            'SATURDAY' => 'SAT', 'SABTU' => 'SAT',
            'SUNDAY' => 'SUN', 'MINGGU' => 'SUN',
        ];
        $query = $db->table('school_routine_activities')
            ->groupStart()->where('unit_id', $unitId)->orWhere('unit_id IS NULL')->groupEnd()
            ->where('is_active', 1)
            ->where('placement_zone', 'ACADEMIC_JP')
            ->where('locked_period_start IS NOT NULL')
            ->where('deleted_at IS NULL')
            ->get()->getResultArray();

        $reserved = [];
        foreach ($query as $routine) {
            $rawDay = strtoupper(trim((string) ($routine['default_day'] ?? 'ALL_DAYS')));
            $days = in_array($rawDay, ['', 'ALL_DAYS'], true)
                ? $active
                : [($dayAliases[$rawDay] ?? $rawDay)];
            $start = max(1, (int) $routine['locked_period_start']);
            $end = (int) ($routine['locked_period_end'] ?? 0);
            if ($end < $start) {
                $end = $start + max(1, (int) ceil((float) ($routine['default_duration_jp'] ?? 1))) - 1;
            }
            foreach ($days as $day) {
                if (! in_array($day, $active, true)) {
                    continue;
                }
                for ($slot = $start; $slot <= $end; $slot++) {
                    $reserved[$day . ':' . $slot] = true;
                }
            }
        }

        return (float) count($reserved);
    }

    public static function defaultDayCapacities(array $dayCodes, float $defaultCapacity): array
    {
        $result = [];
        $normalized = array_values(array_map(static fn($code) => strtoupper((string)$code), $dayCodes));
        foreach ($normalized as $code) {
            $result[$code] = $defaultCapacity;
        }

        if (in_array('FRI', $normalized, true) && $defaultCapacity >= 8.0) {
            $result['FRI'] = 7.0;
        }

        return $result;
    }

    public static function normalizeDayCapacities(array $dayCodes, mixed $rawCapacities, float $defaultCapacity): array
    {
        $defaults = self::defaultDayCapacities($dayCodes, $defaultCapacity);
        if (!is_array($rawCapacities)) {
            return $defaults;
        }

        $result = [];
        foreach ($dayCodes as $index => $code) {
            $code = strtoupper((string)$code);
            $rawValue = $rawCapacities[$code] ?? $rawCapacities[strtolower($code)] ?? $rawCapacities[$index] ?? null;
            $result[$code] = ($rawValue === null || $rawValue === '') ? $defaults[$code] : (float)$rawValue;
        }

        return $result;
    }

    public static function buildOverview(int $versionId, int $academicPeriodId, int $unitId): array
    {
        $db = Database::connect();
        $settings = self::getSettings($versionId, $unitId);
        $policyInfo = self::resolveWorkloadPolicy($academicPeriodId, $unitId, $settings['workload_policy_id'] ?? null);

        $grossWeeklyCapacity = self::computeWeeklyCapacity($settings);
        $routineReservation = self::computeAcademicRoutineReservation(
            $unitId,
            $settings['selected_day_codes'] ?? ['MON', 'TUE', 'WED', 'THU', 'FRI']
        );
        $weeklyCapacity = max(0.0, $grossWeeklyCapacity - $routineReservation);

        $grades = $db->table('grade_levels')
            ->select('id, code, name, grade_number')
            ->where('unit_id', $unitId)
            ->where('is_active', 1)
            ->orderBy('grade_number', 'ASC')
            ->get()->getResultArray();

        $classCounts = [];
        $classRows = $db->table('classrooms')
            ->select('grade_level_id, COUNT(*) AS classroom_count')
            ->where('academic_period_id', $academicPeriodId)
            ->where('unit_id', $unitId)
            ->where('is_active', 1)
            ->where('deleted_at IS NULL')
            ->groupBy('grade_level_id')
            ->get()->getResultArray();
        foreach ($classRows as $row) {
            $classCounts[(int) $row['grade_level_id']] = (int) $row['classroom_count'];
        }

        $structures = $db->table('curriculum_structures cs')
            ->select('cs.*, s.name AS subject_name, s.code AS subject_code')
            ->join('subjects s', 's.id = cs.subject_id', 'left')
            ->where('cs.curriculum_version_id', $versionId)
            ->where('cs.unit_id', $unitId)
            ->where('cs.status', 'ACTIVE')
            ->where('cs.deleted_at IS NULL')
            ->get()->getResultArray();

        $gradeRows = [];
        foreach ($grades as $grade) {
            $gradeRows[(int) $grade['id']] = [
                'grade_id' => (int) $grade['id'],
                'code' => $grade['code'],
                'name' => $grade['name'],
                'classrooms' => $classCounts[(int) $grade['id']] ?? 0,
                'subjects' => 0,
                'official_hours' => 0.0,
                'effective_hours' => 0.0,
                'teacher_demand_hours' => 0.0,
                'capacity' => $weeklyCapacity,
                'remaining_capacity' => $weeklyCapacity,
                'status' => 'EMPTY',
            ];
        }

        $summary = [
            'structure_count' => count($structures),
            'official_hours' => 0.0,
            'effective_hours' => 0.0,
            'required_total_hours' => 0.0,
            'teacher_demand_hours' => 0.0,
            'custom_count' => 0,
            'activity_count' => 0,
            'missing_block_pattern_count' => 0,
            'classroom_count' => array_sum($classCounts),
        ];

        foreach ($structures as $structure) {
            $gradeId = (int) $structure['grade_level_id'];
            if (!isset($gradeRows[$gradeId])) {
                continue;
            }

            $official = (float) ($structure['official_weekly_hours'] ?? 0);
            $effective = (float) $structure['effective_weekly_hours'];
            $multiplier = !empty($structure['classroom_id']) ? 1 : max(1, $gradeRows[$gradeId]['classrooms']);
            $demand = !empty($structure['counts_as_teaching_load']) ? $effective * $multiplier : 0.0;

            $gradeRows[$gradeId]['subjects']++;
            $gradeRows[$gradeId]['official_hours'] += $official;
            $gradeRows[$gradeId]['effective_hours'] += $effective;
            $gradeRows[$gradeId]['teacher_demand_hours'] += $demand;
            $summary['official_hours'] += $official;
            $summary['effective_hours'] += $effective;
            $summary['required_total_hours'] += $demand;
            $summary['teacher_demand_hours'] += $demand;

            if ($structure['effective_source'] !== 'OFFICIAL') {
                $summary['custom_count']++;
            }
            if (in_array($structure['category'], ['PENGEMBANGAN_DIRI', 'KOKURIKULER'], true)) {
                $summary['activity_count']++;
            }
            if ($effective > 1 && empty($structure['block_pattern_json'])) {
                $summary['missing_block_pattern_count']++;
            }
        }

        foreach ($gradeRows as &$row) {
            $row['remaining_capacity'] = $weeklyCapacity - $row['effective_hours'];
            $row['status'] = $row['subjects'] === 0
                ? 'EMPTY'
                : (abs($row['remaining_capacity']) < 0.01 ? 'BALANCED' : ($row['remaining_capacity'] < 0 ? 'OVER' : 'UNDER'));
        }
        unset($row);

        // Teacher capacity and assignment calculations
        $teacherCountRow = $db->table('teacher_unit_assignments')
            ->select('COUNT(DISTINCT teacher_id) as total')
            ->where('unit_id', $unitId)
            ->where('status', 'ACTIVE')
            ->get()->getRowArray();
        $assignedTeacherCount = (int) ($teacherCountRow['total'] ?? 0);

        $targetHours = $policyInfo['target_total_hours'] > 0 ? $policyInfo['target_total_hours'] : 40.0;
        $minimumHours = $policyInfo['minimum_teaching_hours'] > 0 ? $policyInfo['minimum_teaching_hours'] : 24.0;

        $availableTeacherCapacity = $assignedTeacherCount * $targetHours;
        $estimatedTeacherFte = $targetHours > 0 ? round($summary['required_total_hours'] / $targetHours, 2) : 0.0;
        $estimatedFteAtMinimum = $minimumHours > 0 ? round($summary['required_total_hours'] / $minimumHours, 2) : 0.0;

        $assignmentVersion = $db->table('assignment_versions')
            ->where('curriculum_version_id', $versionId)
            ->orderBy('is_active', 'DESC')
            ->orderBy('id', 'DESC')
            ->get(1)->getRowArray();
        $allocatedHours = 0.0;
        if ($assignmentVersion) {
            $allocated = $db->table('teaching_assignments')
                ->selectSum('assigned_weekly_hours', 'total')
                ->where('assignment_version_id', $assignmentVersion['id'])
                ->where('unit_id', $unitId)
                ->where('status', 'ACTIVE')
                ->get()->getRowArray();
            $allocatedHours = (float) ($allocated['total'] ?? 0);
        }

        $summary['available_teacher_capacity']    = $availableTeacherCapacity;
        $summary['estimated_teacher_fte']         = $estimatedTeacherFte;
        $summary['estimated_fte_at_minimum_load'] = $estimatedFteAtMinimum;
        $summary['assigned_teacher_count']        = $assignedTeacherCount;
        $summary['allocated_hours']               = $allocatedHours;
        $summary['unallocated_hours']             = max(0, $summary['required_total_hours'] - $allocatedHours);
        $summary['allocation_percent']            = $summary['required_total_hours'] > 0
            ? min(100, round(($allocatedHours / $summary['required_total_hours']) * 100, 1))
            : 0;
        $summary['specialization_gap']            = round($summary['required_total_hours'] - $availableTeacherCapacity, 2);

        return [
            'settings'            => $settings,
            'policy'              => $policyInfo,
            'weekly_capacity'     => $weeklyCapacity,
            'grades'              => array_values($gradeRows),
            'summary'             => $summary,
            'assignment_version' => $assignmentVersion,
        ];
    }
}
