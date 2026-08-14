<?php

namespace App\Services;

use Config\Database;

class AcademicOperatingSettingsService
{
    public const DAY_MAP = ['MON' => 1, 'TUE' => 2, 'WED' => 3, 'THU' => 4, 'FRI' => 5, 'SAT' => 6, 'SUN' => 7];

    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function resolve(int $academicYearId, ?int $unitId): array
    {
        if ($unitId === null) {
            $policies = [];
            foreach ($this->db->table('school_units')->select('id, code, name')->where('is_active', 1)->where('deleted_at IS NULL')->orderBy('id')->get()->getResultArray() as $unit) {
                $policies[] = $this->resolve($academicYearId, (int) $unit['id']) + ['unit_code' => $unit['code'], 'unit_name' => $unit['name']];
            }
            if ($policies === []) {
                return $this->defaults('DEFAULT');
            }
            $signatures = array_unique(array_map(static fn (array $policy): string => implode(',', $policy['working_days']), $policies));
            $base = $policies[0];
            $base['source'] = count($signatures) === 1 ? 'CURRICULUM_ALL_UNITS' : 'UNIT_CONFLICT';
            $base['conflict'] = count($signatures) > 1;
            $base['unit_policies'] = $policies;
            return $base;
        }

        $setting = $this->db->table('academic_operating_settings')
            ->where('academic_year_id', $academicYearId)->where('unit_scope_key', $unitId)->get()->getRowArray();
        if ($setting && strtoupper((string) $setting['source_mode']) === 'CUSTOM') {
            $days = $this->normalizeDays(json_decode((string) $setting['custom_working_days_json'], true));
            return [
                'working_days' => $days ?: [1, 2, 3, 4, 5],
                'working_day_codes' => $this->numbersToCodes($days ?: [1, 2, 3, 4, 5]),
                'source' => 'GLOBAL_CUSTOM', 'conflict' => false,
                'effective_week_min_days' => (int) $setting['effective_week_min_days'],
                'compare_official_targets' => (int) $setting['compare_official_targets'] === 1,
                'non_school_event_policy' => $setting['non_school_event_policy'],
                'revision_number' => (int) $setting['revision_number'],
                'setting' => $setting,
            ];
        }

        $planning = $this->db->table('curriculum_planning_settings cps')
            ->select('cps.*, cv.id as version_id, cv.workflow_status')
            ->join('curriculum_versions cv', 'cv.id = cps.curriculum_version_id')
            ->join('academic_periods ap', 'ap.id = cv.academic_period_id')
            ->where('ap.academic_year_id', $academicYearId)->where('cps.unit_id', $unitId)
            ->orderBy("CASE WHEN cv.workflow_status IN ('APPROVED','LOCKED') THEN 0 ELSE 1 END", '', false)
            ->orderBy('cv.id', 'DESC')->get()->getRowArray();
        if ($planning) {
            $codes = json_decode((string) $planning['selected_day_codes_json'], true);
            $days = $this->codesToNumbers(is_array($codes) ? $codes : []);
            return [
                'working_days' => $days ?: [1, 2, 3, 4, 5],
                'working_day_codes' => $this->numbersToCodes($days ?: [1, 2, 3, 4, 5]),
                'source' => 'CURRICULUM', 'conflict' => false,
                'effective_week_min_days' => (int) ($setting['effective_week_min_days'] ?? 3),
                'compare_official_targets' => (int) ($setting['compare_official_targets'] ?? 0) === 1,
                'non_school_event_policy' => $setting['non_school_event_policy'] ?? 'PREVIOUS_WORKING_DAY',
                'revision_number' => (int) ($setting['revision_number'] ?? 1),
                'setting' => $setting, 'planning' => $planning,
            ];
        }
        return $this->defaults('DEFAULT');
    }

    public function save(int $academicYearId, int $unitId, array $input, int $userId): array
    {
        $mode = strtoupper((string) ($input['source_mode'] ?? 'CURRICULUM'));
        if (!in_array($mode, ['CURRICULUM', 'CUSTOM'], true)) {
            throw new \InvalidArgumentException('Sumber hari sekolah tidak valid.');
        }
        $days = $this->normalizeDays($input['working_days'] ?? []);
        if ($mode === 'CUSTOM' && $days === []) {
            throw new \InvalidArgumentException('Pilih minimal satu hari sekolah untuk mode khusus.');
        }
        $min = (int) ($input['effective_week_min_days'] ?? 3);
        if ($min < 1 || $min > 7) {
            throw new \InvalidArgumentException('Minimal hari untuk minggu efektif harus 1–7.');
        }
        $existing = $this->db->table('academic_operating_settings')->where('academic_year_id', $academicYearId)->where('unit_scope_key', $unitId)->get()->getRowArray();
        $now = date('Y-m-d H:i:s');
        $data = [
            'academic_year_id' => $academicYearId, 'unit_id' => $unitId, 'unit_scope_key' => $unitId,
            'source_mode' => $mode, 'custom_working_days_json' => $mode === 'CUSTOM' ? json_encode($days) : null,
            'effective_week_min_days' => $min,
            'compare_official_targets' => !empty($input['compare_official_targets']) ? 1 : 0,
            'non_school_event_policy' => 'PREVIOUS_WORKING_DAY',
            'notes' => trim((string) ($input['notes'] ?? '')) ?: null,
            'updated_by' => $userId, 'updated_at' => $now,
            'revision_number' => (int) ($existing['revision_number'] ?? 0) + 1,
        ];
        if ($existing) {
            $this->db->table('academic_operating_settings')->where('id', $existing['id'])->update($data);
        } else {
            $data['created_by'] = $userId; $data['created_at'] = $now;
            $this->db->table('academic_operating_settings')->insert($data);
        }
        return $this->resolve($academicYearId, $unitId);
    }

    private function defaults(string $source): array
    {
        return ['working_days' => [1,2,3,4,5], 'working_day_codes' => ['MON','TUE','WED','THU','FRI'], 'source' => $source, 'conflict' => false, 'effective_week_min_days' => 3, 'compare_official_targets' => false, 'non_school_event_policy' => 'PREVIOUS_WORKING_DAY', 'revision_number' => 1, 'setting' => null];
    }

    private function normalizeDays($days): array
    {
        if (!is_array($days)) return [];
        $days = array_values(array_unique(array_filter(array_map('intval', $days), static fn (int $day): bool => $day >= 1 && $day <= 7)));
        sort($days); return $days;
    }

    private function codesToNumbers(array $codes): array
    {
        return $this->normalizeDays(array_map(static fn ($code): int => self::DAY_MAP[strtoupper((string) $code)] ?? 0, $codes));
    }

    private function numbersToCodes(array $days): array
    {
        $reverse = array_flip(self::DAY_MAP);
        return array_values(array_map(static fn (int $day): string => $reverse[$day], $days));
    }
}
