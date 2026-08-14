<?php

namespace App\Services;

use App\Models\AcademicCalendarDayModel;
use App\Models\AcademicCalendarModel;
use App\Models\AcademicCalendarRuleModel;
use App\Models\AcademicYearModel;
use Config\Database;

/**
 * Deterministic, profile-driven academic calendar rule engine.
 *
 * Precedence: work-week profile -> official rules -> school rules -> manual
 * overrides. Source documents are reference inputs; no date is silently reused
 * for another academic year.
 */
class AcademicCalendarGeneratorService
{
    private $db;
    private AcademicCalendarModel $calendarModel;
    private AcademicCalendarDayModel $dayModel;
    private AcademicCalendarRuleModel $ruleModel;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->calendarModel = new AcademicCalendarModel();
        $this->dayModel = new AcademicCalendarDayModel();
        $this->ruleModel = new AcademicCalendarRuleModel();
    }

    public function getProfiles(): array
    {
        return $this->db->table('academic_calendar_profiles')
            ->where('is_active', 1)->orderBy('is_default', 'DESC')->orderBy('name')->get()->getResultArray();
    }

    public function generate(
        int $academicYearId,
        ?int $unitId,
        string $calendarName,
        ?string $dinasRefNumber = null,
        ?string $dinasRefDate = null,
        ?int $profileId = null,
        array $targets = []
    ): array {
        $year = (new AcademicYearModel())->find($academicYearId);
        if (!$year) {
            return ['success' => false, 'message' => 'Tahun ajaran tidak ditemukan.'];
        }
        if ($this->calendarModel->where('academic_year_id', $academicYearId)->where('unit_id', $unitId)->first()) {
            return ['success' => false, 'message' => 'Kalender untuk tahun ajaran dan unit ini sudah ada. Edit atau generate ulang kalender draft tersebut.'];
        }

        $profile = $this->resolveProfile($profileId, $unitId);
        if (!$profile) {
            return ['success' => false, 'message' => 'Profil kalender aktif tidak tersedia.'];
        }

        $operatingPolicy = (new AcademicOperatingSettingsService())->resolve($academicYearId, $unitId);
        if (!empty($operatingPolicy['conflict'])) {
            return ['success' => false, 'message' => 'Hari sekolah SMP dan SMA berbeda. Samakan pengaturan operasional atau buat kalender per unit.'];
        }
        $profile['working_days_json'] = json_encode($operatingPolicy['working_days']);
        $profile['effective_week_min_days'] = (int) $operatingPolicy['effective_week_min_days'];
        $targets = array_filter($targets, static fn ($value) => $value !== null && $value !== '');
        try {
            $this->db->transException(true)->transStart();
            $calendarId = (int) $this->calendarModel->insert([
            'academic_year_id' => $academicYearId,
            'unit_id' => $unitId,
            'unit_scope_key' => $unitId ?? 0,
            'profile_id' => (int) $profile['id'],
            'name' => $calendarName,
            'dinas_reference_number' => $dinasRefNumber ?: null,
            'dinas_reference_date' => $dinasRefDate ?: null,
            'status' => 'DRAFT',
            'effective_week_min_days' => (int) $profile['effective_week_min_days'],
            'working_days_json_snapshot' => json_encode($operatingPolicy['working_days']),
            'working_day_source' => $operatingPolicy['source'],
            'working_day_codes' => $operatingPolicy['working_day_codes'],
            'operating_setting_revision' => (int) $operatingPolicy['revision_number'],
            'target_hes_sem1' => $targets['hes_sem1'] ?? null,
            'target_hes_sem2' => $targets['hes_sem2'] ?? null,
            'target_heb_sem1' => $targets['heb_sem1'] ?? null,
            'target_heb_sem2' => $targets['heb_sem2'] ?? null,
            'validation_status' => 'PENDING',
            'created_by' => session()->get('user_id'),
            ], true);

            $this->seedOfficialRules($calendarId, $year, $profile);
            $this->rebuild($calendarId, false);
            $this->db->transComplete();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Academic calendar generation failed: {message}', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Kalender gagal dibuat. Periksa apakah kalender untuk unit/tahun tersebut sudah ada dan coba lagi.'];
        }

        return ['success' => true, 'calendar_id' => $calendarId, 'message' => 'Kalender berhasil dibuat sebagai draft dan telah divalidasi.'];
    }

    public function preview(int $academicYearId, ?int $unitId, ?int $profileId = null): array
    {
        $year = (new AcademicYearModel())->find($academicYearId);
        $profile = $this->resolveProfile($profileId, $unitId);
        if (!$year || !$profile) {
            return ['success' => false, 'message' => 'Tahun ajaran atau profil kalender tidak valid.'];
        }
        $operatingPolicy = (new AcademicOperatingSettingsService())->resolve($academicYearId, $unitId);
        if (!empty($operatingPolicy['conflict'])) {
            return ['success' => false, 'message' => 'Konfigurasi hari sekolah antarunit berbeda.', 'unit_policies' => $operatingPolicy['unit_policies']];
        }
        $profile['working_days_json'] = json_encode($operatingPolicy['working_days']);
        $profile['effective_week_min_days'] = (int) $operatingPolicy['effective_week_min_days'];
        $rules = $this->adjustRulesForProfile($this->officialRuleDefinitions($year), $profile);
        $days = $this->buildMatrix($year, $profile, $rules, []);
        $metrics = $this->calculateMetrics($days, $year, (int) $profile['effective_week_min_days']);
        return [
            'success' => true,
            'profile' => $profile['name'],
            'metrics' => $metrics,
            'targets' => $operatingPolicy['compare_official_targets'] ? $this->officialTargets($year) : [],
            'warnings' => $operatingPolicy['compare_official_targets'] ? $this->targetWarnings($metrics, $this->officialTargets($year)) : [],
            'working_day_source' => $operatingPolicy['source'],
            'rule_count' => count($rules),
        ];
    }

    public function rebuild(int $calendarId, bool $preserveManualOverrides = true): array
    {
        $calendar = $this->calendarModel->find($calendarId);
        if (!$calendar || $calendar['status'] === 'ARCHIVED') {
            return ['success' => false, 'message' => 'Kalender tidak ditemukan atau telah diarsipkan.'];
        }
        if ($calendar['status'] === 'ACTIVE') {
            return ['success' => false, 'message' => 'Kalender aktif tidak dapat digenerate ulang. Kembalikan ke draft terlebih dahulu.'];
        }
        $year = (new AcademicYearModel())->find((int) $calendar['academic_year_id']);
        $profile = $this->resolveProfile((int) ($calendar['profile_id'] ?? 0), $calendar['unit_id'] ? (int) $calendar['unit_id'] : null);
        if (!$year || !$profile) {
            return ['success' => false, 'message' => 'Konteks tahun ajaran atau profil kalender tidak lengkap.'];
        }
        $operatingPolicy = (new AcademicOperatingSettingsService())->resolve((int) $calendar['academic_year_id'], $calendar['unit_id'] ? (int) $calendar['unit_id'] : null);
        if (!empty($operatingPolicy['conflict'])) {
            return ['success' => false, 'message' => 'Hari sekolah antarunit berbeda. Kalender gabungan tidak dapat dihitung secara aman.'];
        }
        $profile['working_days_json'] = json_encode($operatingPolicy['working_days']);
        $profile['effective_week_min_days'] = (int) $operatingPolicy['effective_week_min_days'];
        $this->calendarModel->update($calendarId, [
            'effective_week_min_days' => (int) $operatingPolicy['effective_week_min_days'],
            'working_days_json_snapshot' => json_encode($operatingPolicy['working_days']),
            'working_day_source' => $operatingPolicy['source'],
            'operating_setting_revision' => (int) $operatingPolicy['revision_number'],
        ]);
        $rules = $this->ruleModel->where('calendar_id', $calendarId)->where('is_enabled', 1)
            ->orderBy('priority', 'ASC')->orderBy('start_date', 'ASC')->findAll();
        $manual = [];
        if ($preserveManualOverrides) {
            foreach ($this->dayModel->where('calendar_id', $calendarId)->where('is_manual_override', 1)->findAll() as $row) {
                $manual[$row['date']] = $row;
            }
        }
        $days = $this->buildMatrix($year, $profile, $rules, $manual);

        $this->db->transException(true)->transStart();
        $this->dayModel->where('calendar_id', $calendarId)->delete();
        $batch = [];
        foreach ($days as $day) {
            $batch[] = ['calendar_id' => $calendarId] + $day;
        }
        if ($batch !== []) {
            $this->dayModel->insertBatch($batch, 250);
        }
        $this->syncEventsFromRules($calendarId, $rules);
        $metrics = $this->recalculateMetrics($calendarId, $year);
        $validation = $this->validate($calendarId, $metrics);
        $this->calendarModel->update($calendarId, [
            'generated_at' => date('Y-m-d H:i:s'),
            'updated_by' => session()->get('user_id'),
            'validation_status' => $validation['status'],
            'validation_summary_json' => json_encode($validation, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        $this->db->transComplete();
        return ['success' => true, 'message' => 'Kalender berhasil digenerate ulang.', 'validation' => $validation];
    }

    private function resolveProfile(?int $profileId, ?int $unitId): ?array
    {
        $builder = $this->db->table('academic_calendar_profiles')->where('is_active', 1);
        if ($profileId) {
            return $builder->where('id', $profileId)->get()->getRowArray();
        }
        if ($unitId) {
            $unitProfile = $builder->where('unit_id', $unitId)->orderBy('is_default', 'DESC')->get()->getRowArray();
            if ($unitProfile) {
                return $unitProfile;
            }
        }
        return $this->db->table('academic_calendar_profiles')->where('is_active', 1)->where('is_default', 1)->get()->getRowArray();
    }

    private function buildMatrix(array $year, array $profile, array $rules, array $manual): array
    {
        $workingDays = array_values(array_unique(array_map('intval', json_decode($profile['working_days_json'], true) ?: [1, 2, 3, 4, 5])));
        $sabbath = (int) ($profile['sabbath_day'] ?? 0);
        $days = [];
        $current = new \DateTimeImmutable($year['start_date']);
        $end = new \DateTimeImmutable($year['end_date']);
        while ($current <= $end) {
            $dow = (int) $current->format('N');
            $working = in_array($dow, $workingDays, true);
            $days[$current->format('Y-m-d')] = [
                'date' => $current->format('Y-m-d'),
                'day_of_week' => $dow,
                'day_type_code' => $working ? 'HEB' : ($dow === $sabbath ? 'SABAT' : 'MINGGU'),
                'is_school_effective' => $working ? 1 : 0,
                'is_learning_effective' => $working ? 1 : 0,
                'event_title' => null,
                'source_layer' => 'PROFILE',
                'source_rule_id' => null,
                'is_manual_override' => 0,
                'custom_bg_color' => null,
                'custom_text_color' => null,
            ];
            $current = $current->modify('+1 day');
        }
        usort($rules, static fn (array $a, array $b): int => ((int) ($a['priority'] ?? 50)) <=> ((int) ($b['priority'] ?? 50)));
        foreach ($rules as $rule) {
            $from = max((string) $year['start_date'], (string) $rule['start_date']);
            $to = min((string) $year['end_date'], (string) $rule['end_date']);
            if ($from > $to) {
                continue;
            }
            $date = new \DateTimeImmutable($from);
            $rangeEnd = new \DateTimeImmutable($to);
            while ($date <= $rangeEnd) {
                $key = $date->format('Y-m-d');
                if (isset($days[$key])) {
                    $working = in_array((int) $days[$key]['day_of_week'], $workingDays, true);
                    $requestedEffective = (int) $rule['is_school_effective'] === 1 || (int) $rule['is_learning_effective'] === 1;
                    if (!$requestedEffective || $working) {
                        $days[$key]['day_type_code'] = $rule['day_type_code'];
                        $days[$key]['is_school_effective'] = $working ? (int) $rule['is_school_effective'] : 0;
                        $days[$key]['is_learning_effective'] = $working ? (int) $rule['is_learning_effective'] : 0;
                        $days[$key]['event_title'] = $rule['title'];
                        $days[$key]['source_layer'] = $rule['source_layer'] ?? 'SCHOOL';
                        $days[$key]['source_rule_id'] = isset($rule['id']) ? (int) $rule['id'] : null;
                    }
                }
                $date = $date->modify('+1 day');
            }
        }
        foreach ($manual as $date => $override) {
            if (isset($days[$date])) {
                foreach (['day_type_code', 'is_school_effective', 'is_learning_effective', 'event_title', 'custom_bg_color', 'custom_text_color'] as $field) {
                    $days[$date][$field] = $override[$field] ?? null;
                }
                $days[$date]['source_layer'] = 'MANUAL';
                $days[$date]['source_rule_id'] = null;
                $days[$date]['is_manual_override'] = 1;
            }
        }
        return $days;
    }

    private function seedOfficialRules(int $calendarId, array $year, array $profile): void
    {
        $rows = [];
        $now = date('Y-m-d H:i:s');
        foreach ($this->adjustRulesForProfile($this->officialRuleDefinitions($year), $profile) as $rule) {
            $rows[] = ['calendar_id' => $calendarId, 'created_by' => session()->get('user_id'), 'created_at' => $now, 'updated_at' => $now] + $rule;
        }
        if ($rows !== []) {
            $this->db->table('academic_calendar_rules')->insertBatch($rows);
        }
    }

    public function initializeExistingCalendar(int $calendarId): array
    {
        $calendar = $this->calendarModel->find($calendarId);
        if (!$calendar) {
            return ['success' => false, 'message' => 'Kalender tidak ditemukan.'];
        }
        $year = (new AcademicYearModel())->find((int) $calendar['academic_year_id']);
        if (!$year) {
            return ['success' => false, 'message' => 'Tahun ajaran kalender tidak ditemukan.'];
        }
        $targets = $this->officialTargets($year);
        if ($targets !== []) {
            $this->calendarModel->update($calendarId, [
                'target_hes_sem1' => $targets['hes_sem1'], 'target_hes_sem2' => $targets['hes_sem2'],
                'target_heb_sem1' => $targets['heb_sem1'], 'target_heb_sem2' => $targets['heb_sem2'],
            ]);
        }
        if ($this->ruleModel->where('calendar_id', $calendarId)->countAllResults() === 0) {
            $profile = $this->resolveProfile((int) ($calendar['profile_id'] ?? 0), $calendar['unit_id'] ? (int) $calendar['unit_id'] : null);
            if ($profile) {
                $this->seedOfficialRules($calendarId, $year, $profile);
            }
        }
        return $calendar['status'] === 'DRAFT'
            ? $this->rebuild($calendarId, true)
            : ['success' => true, 'message' => 'Kalender aktif dipertahankan; aturan tersedia untuk audit.'];
    }

    private function officialRuleDefinitions(array $year): array
    {
        if (substr($year['start_date'], 0, 4) !== '2026' || substr($year['end_date'], 0, 4) !== '2027') {
            return [];
        }
        $rules = [];
        $add = static function (string $title, string $code, string $from, string $to, string $source, int $priority, int $hes, int $heb) use (&$rules): void {
            $rules[] = ['title' => $title, 'day_type_code' => $code, 'start_date' => $from, 'end_date' => $to, 'source_layer' => $source, 'priority' => $priority, 'is_school_effective' => $hes, 'is_learning_effective' => $heb, 'is_enabled' => 1, 'notes' => null];
        };
        foreach ([
            ['Hari Kemerdekaan RI', '2026-08-17'], ['Maulid Nabi Muhammad SAW', '2026-08-25'],
            ['HUT GKI di Tanah Papua', '2026-10-26'], ['Cuti Bersama Masa Advent', '2026-12-01'],
            ['Cuti Bersama Natal', '2026-12-24'], ['Hari Raya Natal', '2026-12-25'], ['Natal Hari Kedua', '2026-12-26'],
            ['Tahun Baru Masehi', '2027-01-01'], ['Isra Mikraj', '2027-01-05'],
            ['Hari Pekabaran Injil di Tanah Papua', '2027-02-05'], ['Tahun Baru Imlek', '2027-02-06'],
            ['Hari Raya Nyepi', '2027-03-09'], ['Idul Fitri Hari Pertama', '2027-03-10'], ['Idul Fitri Hari Kedua', '2027-03-11'],
            ['Wafat Yesus Kristus', '2027-03-26'], ['Hari Raya Paskah', '2027-03-28'], ['Cuti Bersama Paskah', '2027-03-29'],
            ['Hari Pekabaran Injil Papua Pegunungan', '2027-04-20'], ['Hari Buruh Internasional', '2027-05-01'],
            ['Kenaikan Yesus Kristus', '2027-05-06'], ['Idul Adha', '2027-05-17'], ['Hari Raya Waisak', '2027-05-20'],
            ['Hari Lahir Pancasila', '2027-06-01'], ['Tahun Baru Islam', '2027-06-06'],
        ] as [$title, $date]) {
            $add($title, str_contains($title, 'Cuti Bersama') ? 'CB' : 'LU', $date, $date, 'DINAS', 90, 0, 0);
        }
        $add('Masa Pengenalan Lingkungan Sekolah', 'MPLS', '2026-07-13', '2026-07-15', 'DINAS', 50, 1, 0);
        $add('Asesmen Formatif Semester 1', 'PTS', '2026-09-14', '2026-09-19', 'DINAS', 50, 1, 0);
        $add('Asesmen Sumatif Semester 1', 'PAS', '2026-12-02', '2026-12-08', 'DINAS', 50, 1, 0);
        $add('Penerimaan Rapor Semester 1', 'R1', '2026-12-19', '2026-12-19', 'DINAS', 50, 1, 0);
        $add('Libur Semester 1', 'LS1', '2026-12-21', '2027-01-05', 'DINAS', 80, 0, 0);
        $add('Asesmen Formatif Semester 2', 'PTS', '2027-03-15', '2027-03-20', 'DINAS', 50, 1, 0);
        $add('Penilaian Sumatif Akhir Jenjang', 'US', '2027-06-02', '2027-06-09', 'DINAS', 50, 1, 0);
        $add('Penerimaan Rapor Semester 2', 'R2', '2027-06-19', '2027-06-19', 'DINAS', 50, 1, 0);
        $add('Libur Akhir Tahun Pelajaran', 'LS2', '2027-06-21', '2027-07-12', 'DINAS', 80, 0, 0);
        return array_values(array_filter(array_map(static function (array $rule) use ($year): ?array {
            $rule['start_date'] = max($rule['start_date'], $year['start_date']);
            $rule['end_date'] = min($rule['end_date'], $year['end_date']);
            return $rule['start_date'] <= $rule['end_date'] ? $rule : null;
        }, $rules)));
    }

    private function adjustRulesForProfile(array $rules, array $profile): array
    {
        $workingDays = array_map('intval', json_decode($profile['working_days_json'], true) ?: [1, 2, 3, 4, 5]);
        foreach ($rules as &$rule) {
            if ((int) $rule['is_school_effective'] !== 1 || $rule['start_date'] !== $rule['end_date']) {
                continue;
            }
            $original = $rule['start_date'];
            $date = new \DateTimeImmutable($original);
            for ($attempt = 0; $attempt < 7 && !in_array((int) $date->format('N'), $workingDays, true); $attempt++) {
                $date = $date->modify('-1 day');
            }
            if ($date->format('Y-m-d') !== $original) {
                $rule['start_date'] = $date->format('Y-m-d');
                $rule['end_date'] = $date->format('Y-m-d');
                $rule['source_layer'] = 'SCHOOL_ADJUSTMENT';
                $rule['priority'] = max(85, (int) $rule['priority']);
                $rule['notes'] = "Tanggal referensi Dinas {$original} otomatis digeser ke hari sekolah terdekat sebelumnya.";
            }
        }
        unset($rule);
        return $rules;
    }

    public function normalizeRuleDates(int $calendarId): void
    {
        $calendar = $this->calendarModel->find($calendarId);
        $profile = $calendar ? $this->resolveProfile((int) ($calendar['profile_id'] ?? 0), $calendar['unit_id'] ? (int) $calendar['unit_id'] : null) : null;
        if (!$calendar || !$profile || $calendar['status'] !== 'DRAFT') {
            return;
        }
        $policy = (new AcademicOperatingSettingsService())->resolve((int) $calendar['academic_year_id'], $calendar['unit_id'] ? (int) $calendar['unit_id'] : null);
        if (!empty($policy['conflict'])) {
            return;
        }
        $profile['working_days_json'] = json_encode($policy['working_days']);
        foreach ($this->getRules($calendarId) as $rule) {
            $adjusted = $this->adjustRulesForProfile([$rule], $profile)[0];
            if ($adjusted['start_date'] !== $rule['start_date']) {
                $this->ruleModel->update((int) $rule['id'], [
                    'start_date' => $adjusted['start_date'], 'end_date' => $adjusted['end_date'],
                    'source_layer' => $adjusted['source_layer'], 'priority' => $adjusted['priority'], 'notes' => $adjusted['notes'],
                ]);
            }
        }
        $this->rebuild($calendarId, true);
    }

    private function officialTargets(array $year): array
    {
        return substr($year['start_date'], 0, 4) === '2026' && substr($year['end_date'], 0, 4) === '2027'
            ? ['hes_sem1' => 134, 'hes_sem2' => 123, 'heb_sem1' => 118, 'heb_sem2' => 115]
            : [];
    }

    public function recalculateMetrics(int $calendarId, ?array $year = null): array
    {
        $calendar = $this->calendarModel->find($calendarId);
        $year ??= $calendar ? (new AcademicYearModel())->find((int) $calendar['academic_year_id']) : null;
        if (!$calendar || !$year) {
            return [];
        }
        $days = [];
        foreach ($this->dayModel->where('calendar_id', $calendarId)->orderBy('date')->findAll() as $day) {
            $days[$day['date']] = $day;
        }
        $metrics = $this->calculateMetrics($days, $year, (int) ($calendar['effective_week_min_days'] ?? 3));
        $this->calendarModel->update($calendarId, [
            'total_hes_sem1' => $metrics['hes_sem1'], 'total_hes_sem2' => $metrics['hes_sem2'],
            'total_heb_sem1' => $metrics['heb_sem1'], 'total_heb_sem2' => $metrics['heb_sem2'],
            'total_effective_weeks_sem1' => $metrics['effective_weeks_sem1'],
            'total_effective_weeks_sem2' => $metrics['effective_weeks_sem2'],
        ]);
        return $metrics;
    }

    private function calculateMetrics(array $days, array $year, int $minDays): array
    {
        $periods = $this->db->table('academic_periods')->where('academic_year_id', $year['id'])
            ->orderBy('semester_number', 'ASC')->get()->getResultArray();
        $sem1End = $periods[0]['end_date'] ?? date('Y-12-31', strtotime($year['start_date']));
        $result = ['hes_sem1' => 0, 'hes_sem2' => 0, 'heb_sem1' => 0, 'heb_sem2' => 0, 'effective_weeks_sem1' => 0, 'effective_weeks_sem2' => 0];
        $weeks = [1 => [], 2 => []];
        foreach ($days as $day) {
            $semester = $day['date'] <= $sem1End ? 1 : 2;
            $result['hes_sem' . $semester] += (int) $day['is_school_effective'];
            $result['heb_sem' . $semester] += (int) $day['is_learning_effective'];
            if ((int) $day['is_learning_effective'] === 1) {
                $weekKey = (new \DateTimeImmutable($day['date']))->modify('monday this week')->format('Y-m-d');
                $weeks[$semester][$weekKey] = ($weeks[$semester][$weekKey] ?? 0) + 1;
            }
        }
        foreach ([1, 2] as $semester) {
            $result['effective_weeks_sem' . $semester] = count(array_filter($weeks[$semester], static fn (int $count): bool => $count >= max(1, $minDays)));
        }
        return $result;
    }

    public function validate(int $calendarId, ?array $metrics = null): array
    {
        $calendar = $this->calendarModel->find($calendarId);
        if (!$calendar) {
            return ['status' => 'ERROR', 'errors' => ['Kalender tidak ditemukan.'], 'warnings' => []];
        }
        $year = (new AcademicYearModel())->find((int) $calendar['academic_year_id']);
        $errors = [];
        $warnings = [];
        $rules = $this->ruleModel->where('calendar_id', $calendarId)->where('is_enabled', 1)->findAll();
        foreach ($rules as $rule) {
            if ($rule['start_date'] > $rule['end_date']) {
                $errors[] = "Rentang aturan {$rule['title']} terbalik.";
            }
            if ($rule['start_date'] < $year['start_date'] || $rule['end_date'] > $year['end_date']) {
                $errors[] = "Aturan {$rule['title']} berada di luar tahun ajaran.";
            }
            if ((int) $rule['is_school_effective'] === 1) {
                $appliedDays = $this->dayModel->where('calendar_id', $calendarId)
                    ->where('source_rule_id', $rule['id'])->where('is_school_effective', 1)->countAllResults();
                if ($appliedDays === 0) {
                    $warnings[] = "Kegiatan {$rule['title']} tidak jatuh pada hari sekolah profil aktif. Pindahkan tanggalnya sebelum kalender digunakan.";
                }
            }
        }
        if ($rules === []) {
            $warnings[] = 'Belum ada aturan kegiatan/libur. Kalender hanya menggunakan pola hari sekolah profil.';
        }
        $metrics ??= $this->recalculateMetrics($calendarId, $year);
        $targets = [
            'hes_sem1' => $calendar['target_hes_sem1'], 'hes_sem2' => $calendar['target_hes_sem2'],
            'heb_sem1' => $calendar['target_heb_sem1'], 'heb_sem2' => $calendar['target_heb_sem2'],
        ];
        $warnings = array_merge($warnings, $this->targetWarnings($metrics, $targets));
        return ['status' => $errors !== [] ? 'ERROR' : ($warnings !== [] ? 'WARNING' : 'VALID'), 'errors' => $errors, 'warnings' => $warnings, 'checked_at' => date('c')];
    }

    private function targetWarnings(array $metrics, array $targets): array
    {
        $labels = ['hes_sem1' => 'HES Semester 1', 'hes_sem2' => 'HES Semester 2', 'heb_sem1' => 'HEB Semester 1', 'heb_sem2' => 'HEB Semester 2'];
        $warnings = [];
        foreach ($labels as $key => $label) {
            if (($targets[$key] ?? null) !== null && (int) $targets[$key] !== (int) ($metrics[$key] ?? 0)) {
                $warnings[] = "$label hasil sistem {$metrics[$key]}, target referensi {$targets[$key]}. Perbedaan dapat terjadi karena profil WMVAA 5 hari dan kalender Dinas 6 hari.";
            }
        }
        return $warnings;
    }

    public function updateDay(int $calendarId, string $date, string $dayTypeCode, ?string $eventTitle = null): bool
    {
        $type = $this->db->table('academic_calendar_event_types')->where('code', $dayTypeCode)->get()->getRowArray();
        $calendar = $this->calendarModel->find($calendarId);
        $year = $calendar ? (new AcademicYearModel())->find((int) $calendar['academic_year_id']) : null;
        if (!$type || !$calendar || !$year || $calendar['status'] !== 'DRAFT' || $date < $year['start_date'] || $date > $year['end_date']) {
            return false;
        }
        $updated = $this->dayModel->where('calendar_id', $calendarId)->where('date', $date)->set([
            'day_type_code' => $dayTypeCode,
            'is_school_effective' => (int) $type['is_school_effective'],
            'is_learning_effective' => (int) $type['is_learning_effective'],
            'event_title' => $eventTitle,
            'source_layer' => 'MANUAL', 'source_rule_id' => null, 'is_manual_override' => 1,
        ])->update();
        if ($updated) {
            $metrics = $this->recalculateMetrics($calendarId, $year);
            $validation = $this->validate($calendarId, $metrics);
            $this->calendarModel->update($calendarId, ['validation_status' => $validation['status'], 'validation_summary_json' => json_encode($validation, JSON_UNESCAPED_UNICODE)]);
        }
        return (bool) $updated;
    }

    public function resetDayOverride(int $calendarId, string $date): bool
    {
        $calendar = $this->calendarModel->find($calendarId);
        if (!$calendar || $calendar['status'] !== 'DRAFT') {
            return false;
        }
        $changed = $this->dayModel->where('calendar_id', $calendarId)->where('date', $date)
            ->where('is_manual_override', 1)->set(['is_manual_override' => 0, 'source_layer' => 'PROFILE', 'source_rule_id' => null])->update();
        if ($changed) {
            $this->rebuild($calendarId, true);
        }
        return (bool) $changed;
    }

    private function syncEventsFromRules(int $calendarId, array $rules): void
    {
        $this->db->table('academic_calendar_events')->where('calendar_id', $calendarId)->where('notes', 'GENERATED_FROM_RULE')->delete();
        $rows = [];
        $now = date('Y-m-d H:i:s');
        foreach ($rules as $rule) {
            $rows[] = ['calendar_id' => $calendarId, 'title' => $rule['title'], 'start_date' => $rule['start_date'], 'end_date' => $rule['end_date'], 'category' => $rule['source_layer'], 'notes' => 'GENERATED_FROM_RULE', 'created_at' => $now, 'updated_at' => $now];
        }
        if ($rows !== []) {
            $this->db->table('academic_calendar_events')->insertBatch($rows);
        }
    }

    public function getRules(int $calendarId): array
    {
        return $this->ruleModel->where('calendar_id', $calendarId)->orderBy('start_date')->orderBy('priority', 'DESC')->findAll();
    }

    public function getEventTypes(): array
    {
        return $this->db->table('academic_calendar_event_types')->orderBy('sort_order')->get()->getResultArray();
    }

    public function getCalendarGrid(int $calendarId): array
    {
        $types = [];
        foreach ($this->getEventTypes() as $type) {
            $types[$type['code']] = $type;
        }
        $grid = [];
        foreach ($this->dayModel->where('calendar_id', $calendarId)->orderBy('date')->findAll() as $day) {
            $ym = substr($day['date'], 0, 7);
            if (!isset($grid[$ym])) {
                $grid[$ym] = ['month_name' => $this->indonesianMonthName((int) substr($ym, 5, 2)), 'days' => [], 'hes' => 0, 'heb' => 0];
            }
            $type = $types[$day['day_type_code']] ?? [];
            $grid[$ym]['days'][(int) substr($day['date'], 8, 2)] = [
                'date' => $day['date'], 'type_code' => $day['day_type_code'], 'type_name' => $type['name'] ?? $day['day_type_code'],
                'event_title' => $day['event_title'], 'source_layer' => $day['source_layer'] ?? 'PROFILE',
                'is_manual_override' => (int) ($day['is_manual_override'] ?? 0),
                'bg_color' => $day['custom_bg_color'] ?: ($type['bg_color'] ?? '#ffffff'),
                'text_color' => $day['custom_text_color'] ?: ($type['text_color'] ?? '#111827'),
            ];
            $grid[$ym]['hes'] += (int) $day['is_school_effective'];
            $grid[$ym]['heb'] += (int) $day['is_learning_effective'];
        }
        return $grid;
    }

    public function getActiveDay(int $academicYearId, int $unitId, string $date): ?array
    {
        $calendar = $this->db->table('academic_calendars')->groupStart()->where('unit_id', $unitId)->orWhere('unit_id IS NULL')->groupEnd()
            ->where('academic_year_id', $academicYearId)->where('status', 'ACTIVE')->orderBy('unit_id', 'DESC')->get()->getRowArray();
        return $calendar ? $this->dayModel->where('calendar_id', $calendar['id'])->where('date', $date)->first() : null;
    }

    private function indonesianMonthName(int $month): string
    {
        return [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'][$month] ?? '';
    }
}
