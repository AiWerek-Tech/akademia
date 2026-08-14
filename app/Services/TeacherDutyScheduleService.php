<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use RuntimeException;

class TeacherDutyScheduleService
{
    /** @var BaseConnection|null */
    private $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?: Database::connect();
    }

    /**
     * Day mapping configuration
     */
    public const DAYS_MAP = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
    ];

    /**
     * Special constraints for specific teachers by full_name pattern.
     * Day numbers: 1=Senin, 2=Selasa, 3=Rabu, 4=Kamis, 5=Jumat
     */
    public function getTeacherAllowedDays(string $teacherName): array
    {
        $normalized = mb_strtolower(trim($teacherName));

        if (str_contains($normalized, 'anike wetipo')) {
            return [1, 2, 5]; // Senin, Selasa, Jumat
        }

        if (str_contains($normalized, 'arike siep')) {
            return [1, 4, 5]; // Senin, Kamis, Jumat
        }

        if (str_contains($normalized, 'natalia tabuni')) {
            return [2, 3]; // Selasa, Rabu
        }

        // All other teachers can be scheduled Monday to Friday
        return [1, 2, 3, 4, 5];
    }

    /**
     * Calculate daily teaching hours (load / jumlah masuk kelas) for each teacher per day (1 to 5)
     * across SMP and SMA for the specified academic year.
     *
     * Returns: [ teacher_id => [ day_of_week (1-5) => total_jp ] ]
     */
    public function getDailyTeachingLoads(int $academicYearId): array
    {
        $teachers = $this->db->table('teachers')
            ->select('id')
            ->where('is_active', 1)
            ->where('deleted_at IS NULL')
            ->get()->getResultArray();

        $loads = [];
        foreach ($teachers as $t) {
            $tId = (int) $t['id'];
            $loads[$tId] = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        }

        // Query teaching entries from schedule_entries linked to academic_year_id
        $entries = $this->db->table('schedule_entries se')
            ->select('se.teacher_id, se.second_teacher_id, sd.day_of_week, COUNT(*) as total_slots')
            ->join('schedule_day_slots sds', 'sds.id = se.day_slot_id')
            ->join('schedule_days sd', 'sd.id = sds.day_id')
            ->join('schedule_versions sv', 'sv.id = se.schedule_version_id')
            ->join('academic_periods ap', 'ap.id = sv.academic_period_id')
            ->where('ap.academic_year_id', $academicYearId)
            ->groupBy(['se.teacher_id', 'sd.day_of_week'])
            ->get()->getResultArray();

        foreach ($entries as $row) {
            $tId = (int) $row['teacher_id'];
            $day = (int) $row['day_of_week'];
            $count = (int) $row['total_slots'];

            if (isset($loads[$tId][$day])) {
                $loads[$tId][$day] += $count;
            }

            // Also check second_teacher_id if set
            if (!empty($row['second_teacher_id'])) {
                $stId = (int) $row['second_teacher_id'];
                if (isset($loads[$stId][$day])) {
                    $loads[$stId][$day] += $count;
                }
            }
        }

        return $loads;
    }

    /**
     * Get duty schedule list for an academic year organized by day (1 to 5)
     */
    public function getDutyMatrix(int $academicYearId): array
    {
        $loads = $this->getDailyTeachingLoads($academicYearId);

        $dutyRows = $this->db->table('teacher_duty_schedules tds')
            ->select('tds.*, t.full_name AS teacher_name, t.nip, t.employee_number AS teacher_code, t.title_prefix, t.degree_suffix')
            ->join('teachers t', 't.id = tds.teacher_id')
            ->where('tds.academic_year_id', $academicYearId)
            ->orderBy('tds.day_of_week', 'ASC')
            ->orderBy('t.full_name', 'ASC')
            ->get()->getResultArray();

        $matrix = [
            1 => ['day_name' => 'Senin', 'duties' => []],
            2 => ['day_name' => 'Selasa', 'duties' => []],
            3 => ['day_name' => 'Rabu', 'duties' => []],
            4 => ['day_name' => 'Kamis', 'duties' => []],
            5 => ['day_name' => 'Jumat', 'duties' => []],
        ];

        foreach ($dutyRows as $row) {
            $day = (int) $row['day_of_week'];
            $tId = (int) $row['teacher_id'];

            // Format teacher name with degree suffix
            $rawName = rtrim(trim((string) $row['teacher_name']), ',');
            $prefix = trim((string) ($row['title_prefix'] ?? ''));
            $suffix = trim((string) ($row['degree_suffix'] ?? ''));

            if (!empty($suffix) && !str_contains($rawName, $suffix)) {
                $rawName .= ', ' . $suffix;
            }
            if (!empty($prefix) && !str_contains($rawName, $prefix)) {
                $rawName = $prefix . ' ' . $rawName;
            }

            $row['teacher_name'] = $rawName;
            $row['teaching_load_today'] = $loads[$tId][$day] ?? 0;
            $row['allowed_days'] = $this->getTeacherAllowedDays($row['teacher_name']);
            if (isset($matrix[$day])) {
                $matrix[$day]['duties'][] = $row;
            }
        }

        return $matrix;
    }

    /**
     * Map active teacher positions / additional duties (Kepala Sekolah, Wakil, Chaplain, Wali Kelas, etc.)
     * Returns: [ teacher_id => [ position_code_1, position_code_2, ... ] ]
     */
    public function getTeacherPositionsMap(int $academicYearId): array
    {
        $map = [];

        // 1. Query structural duties from teacher_additional_duties
        $duties = $this->db->table('teacher_additional_duties tad')
            ->select('tad.teacher_id, adt.code as duty_code')
            ->join('additional_duty_types adt', 'adt.id = tad.duty_type_id')
            ->where('tad.status', 'ACTIVE')
            ->where('tad.deleted_at IS NULL')
            ->get()->getResultArray();

        foreach ($duties as $d) {
            $tId = (int) $d['teacher_id'];
            $code = strtoupper(trim($d['duty_code']));
            if (!isset($map[$tId])) {
                $map[$tId] = [];
            }
            if (!in_array($code, $map[$tId], true)) {
                $map[$tId][] = $code;
            }
        }

        // 2. Query homeroom teachers from classrooms.homeroom_teacher_id
        $homerooms = $this->db->table('classrooms')
            ->select('homeroom_teacher_id')
            ->where('homeroom_teacher_id IS NOT NULL')
            ->where('deleted_at IS NULL')
            ->get()->getResultArray();

        foreach ($homerooms as $hr) {
            $tId = (int) $hr['homeroom_teacher_id'];
            if (!isset($map[$tId])) {
                $map[$tId] = [];
            }
            if (!in_array('HOMEROOM_TEACHER', $map[$tId], true)) {
                $map[$tId][] = 'HOMEROOM_TEACHER';
            }
        }

        return $map;
    }

    /**
     * Smart Auto-Generate Duty Schedule for Academic Year
     *
     * @param int $academicYearId
     * @param string $quotaMode 'auto' (calculated from active teachers & school days) or 'manual'
     * @param int $quotaPerDay Target quota if manual mode
     * @param array $excludedDutyCodes Duty codes to exclude (e.g. ['HEADMASTER', 'VICE_PRINCIPAL', 'CHAPLAIN', 'HOMEROOM_TEACHER'])
     * @param int|null $actorId
     * @return array Summary of generated assignments
     */
    public function generate(
        int $academicYearId,
        string $quotaMode = 'auto',
        int $quotaPerDay = 3,
        array $excludedDutyCodes = [],
        ?int $actorId = null
    ): array {
        $teachers = $this->db->table('teachers')
            ->select('id, full_name, nip, employee_number AS code')
            ->where('is_active', 1)
            ->where('deleted_at IS NULL')
            ->orderBy('full_name', 'ASC')
            ->get()->getResultArray();

        if (empty($teachers)) {
            throw new RuntimeException('Tidak ada data guru aktif untuk dijadwalkan piket.');
        }

        // Filter out teachers who hold any excluded position / additional duty
        $positionsMap = $this->getTeacherPositionsMap($academicYearId);
        $normalizedExcludedCodes = array_map('strtoupper', array_map('trim', $excludedDutyCodes));

        $eligibleTeachers = [];
        foreach ($teachers as $t) {
            $tId = (int) $t['id'];
            $tPositions = $positionsMap[$tId] ?? [];

            $isExcluded = false;
            foreach ($tPositions as $pos) {
                if (in_array($pos, $normalizedExcludedCodes, true)) {
                    $isExcluded = true;
                    break;
                }
            }

            if (!$isExcluded) {
                $eligibleTeachers[] = $t;
            }
        }

        if (empty($eligibleTeachers)) {
            throw new RuntimeException('Tidak ada guru yang memenuhi syarat setelah penyaringan jabatan.');
        }

        $dailyLoads = $this->getDailyTeachingLoads($academicYearId);
        $totalTeachers = count($eligibleTeachers);
        $totalDays = count(self::DAYS_MAP); // 5 days

        if ($quotaMode === 'auto') {
            // Calculate proportional quota per day based on total eligible teachers and days
            $targetQuotaPerDay = (int) ceil($totalTeachers / $totalDays);
            $targetQuotaPerDay = max(1, min(10, $targetQuotaPerDay));
        } else {
            $targetQuotaPerDay = max(1, min(10, $quotaPerDay));
        }

        // Track how many duty assignments each teacher receives across the week
        $dutyCounts = [];
        foreach ($eligibleTeachers as $t) {
            $dutyCounts[(int) $t['id']] = 0;
        }

        // Start database transaction
        $this->db->transException(true)->transStart();

        // Clear existing duty schedule for this academic year before generating
        $this->db->table('teacher_duty_schedules')
            ->where('academic_year_id', $academicYearId)
            ->delete();

        $now = date('Y-m-d H:i:s');
        $generatedCount = 0;
        $daySummaries = [];

        foreach (self::DAYS_MAP as $dayOfWeek => $dayName) {
            // Target quota for Friday is 2 teachers, Monday-Thursday is $targetQuotaPerDay (default 3)
            $quotaForThisDay = ($dayOfWeek === 5) ? 2 : $targetQuotaPerDay;

            // Find eligible candidate teachers for this day
            $candidates = [];

            foreach ($eligibleTeachers as $t) {
                $tId = (int) $t['id'];
                $allowedDays = $this->getTeacherAllowedDays($t['full_name']);

                // Check constraint: Is teacher allowed to be on duty on this day?
                if (!in_array($dayOfWeek, $allowedDays, true)) {
                    continue;
                }

                $loadToday = $dailyLoads[$tId][$dayOfWeek] ?? 0;
                $assignedCountSoFar = $dutyCounts[$tId] ?? 0;

                $candidates[] = [
                    'teacher'            => $t,
                    'load_today'         => $loadToday,
                    'assigned_so_far'    => $assignedCountSoFar,
                    'allowed_days_count' => count($allowedDays),
                ];
            }

            // Sort candidates by:
            // 1. Assigned duty count so far ASC (teachers with 0 duties so far MUST be picked first!)
            // 2. Allowed days count ASC (teachers with fewer available days, e.g. 2-3 days, prioritized on their valid days)
            // 3. Teaching load today ASC (0 JP / jam kosong prioritized!)
            // 4. Teacher name ASC (stability)
            usort($candidates, static function (array $a, array $b): int {
                if ($a['assigned_so_far'] !== $b['assigned_so_far']) {
                    return $a['assigned_so_far'] <=> $b['assigned_so_far'];
                }
                if ($a['allowed_days_count'] !== $b['allowed_days_count']) {
                    return $a['allowed_days_count'] <=> $b['allowed_days_count'];
                }
                if ($a['load_today'] !== $b['load_today']) {
                    return $a['load_today'] <=> $b['load_today'];
                }
                return strcmp($a['teacher']['full_name'], $b['teacher']['full_name']);
            });

            // Select top $quotaForThisDay candidates for this day
            $selected = array_slice($candidates, 0, $quotaForThisDay);

            foreach ($selected as $item) {
                $tId = (int) $item['teacher']['id'];

                $this->db->table('teacher_duty_schedules')->insert([
                    'uuid'             => UuidService::v4(),
                    'academic_year_id' => $academicYearId,
                    'teacher_id'       => $tId,
                    'day_of_week'      => $dayOfWeek,
                    'day_name'         => $dayName,
                    'duty_role'        => 'GURU_PIKET',
                    'notes'            => 'Generasi Otomatis (Beban mengajar hari ' . $dayName . ': ' . $item['load_today'] . ' JP)',
                    'created_by'       => $actorId,
                    'updated_by'       => $actorId,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);

                $dutyCounts[$tId]++;
                $generatedCount++;
            }

            $daySummaries[$dayName] = count($selected);
        }

        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            throw new RuntimeException('Gagal menyusun jadwal piket secara otomatis.');
        }

        AuditService::log('duty_schedules', 'generate', 'AcademicYear', $academicYearId, null, [
            'total_generated'      => $generatedCount,
            'quota_mode'           => $quotaMode,
            'target_quota_per_day' => $targetQuotaPerDay,
            'excluded_duty_codes'  => $excludedDutyCodes,
        ], 'Membuat generasi otomatis Jadwal Piket Guru Sekolah Satu Atap');

        return [
            'total_generated'      => $generatedCount,
            'quota_mode'           => $quotaMode,
            'target_quota_per_day' => $targetQuotaPerDay,
            'excluded_duty_codes'  => $excludedDutyCodes,
            'day_summaries'        => $daySummaries,
        ];
    }

    /**
     * Add a single teacher duty manually
     */
    public function addDuty(
        int $academicYearId,
        int $teacherId,
        int $dayOfWeek,
        string $dutyRole = 'GURU_PIKET',
        ?string $notes = null,
        ?int $actorId = null
    ): int {
        $teacher = $this->db->table('teachers')
            ->select('id, full_name')
            ->where('id', $teacherId)
            ->where('is_active', 1)
            ->get()->getRowArray();

        if (!$teacher) {
            throw new RuntimeException('Data guru tidak ditemukan atau tidak aktif.');
        }

        if (!isset(self::DAYS_MAP[$dayOfWeek])) {
            throw new RuntimeException('Hari tidak valid.');
        }

        // Validate teacher constraint
        $allowedDays = $this->getTeacherAllowedDays($teacher['full_name']);
        if (!in_array($dayOfWeek, $allowedDays, true)) {
            $allowedNames = array_map(fn($d) => self::DAYS_MAP[$d], $allowedDays);
            throw new RuntimeException('Guru ' . $teacher['full_name'] . ' hanya dapat dijadwalkan piket pada hari: ' . implode(', ', $allowedNames) . '.');
        }

        $exists = $this->db->table('teacher_duty_schedules')
            ->where('academic_year_id', $academicYearId)
            ->where('teacher_id', $teacherId)
            ->where('day_of_week', $dayOfWeek)
            ->get()->getRowArray();

        if ($exists) {
            throw new RuntimeException('Guru ' . $teacher['full_name'] . ' sudah terdaftar piket pada hari ' . self::DAYS_MAP[$dayOfWeek] . '.');
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('teacher_duty_schedules')->insert([
            'uuid'             => UuidService::v4(),
            'academic_year_id' => $academicYearId,
            'teacher_id'       => $teacherId,
            'day_of_week'      => $dayOfWeek,
            'day_name'         => self::DAYS_MAP[$dayOfWeek],
            'duty_role'        => trim($dutyRole) ?: 'GURU_PIKET',
            'notes'            => trim((string)$notes) ?: null,
            'created_by'       => $actorId,
            'updated_by'       => $actorId,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        $id = (int) $this->db->insertID();

        AuditService::log('duty_schedules', 'add_duty', 'TeacherDutySchedule', $id, null, [
            'academic_year_id' => $academicYearId,
            'teacher_id'       => $teacherId,
            'day_of_week'      => $dayOfWeek,
        ], 'Menambahkan tugas piket guru ' . $teacher['full_name'] . ' pada hari ' . self::DAYS_MAP[$dayOfWeek]);

        return $id;
    }

    /**
     * Delete a single teacher duty
     */
    public function deleteDuty(int $dutyId): void
    {
        $duty = $this->db->table('teacher_duty_schedules')->where('id', $dutyId)->get()->getRowArray();
        if (!$duty) {
            throw new RuntimeException('Tugas piket tidak ditemukan.');
        }

        $this->db->table('teacher_duty_schedules')->where('id', $dutyId)->delete();

        AuditService::log('duty_schedules', 'delete_duty', 'TeacherDutySchedule', $dutyId, $duty, null, 'Menghapus tugas piket guru');
    }

    /**
     * Clear all duty schedules for an academic year
     */
    public function clearSchedule(int $academicYearId): void
    {
        $this->db->table('teacher_duty_schedules')
            ->where('academic_year_id', $academicYearId)
            ->delete();

        AuditService::log('duty_schedules', 'clear_schedule', 'AcademicYear', $academicYearId, null, null, 'Mengosongkan seluruh jadwal piket guru');
    }
}
