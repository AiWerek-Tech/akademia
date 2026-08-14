<?php

namespace App\Services;

/**
 * Encodes the hard SMA rules from Permendikdasmen 13/2025 separately
 * from school policy (backup choices, capacity, and approval workflow).
 */
class ElectiveComplianceService
{
    public function assessPeriod(array $period, array $offerings): array
    {
        $errors = [];
        $warnings = [];
        $type = strtoupper((string) ($period['selection_type'] ?? 'FASE_F_ELECTIVE'));
        $open = array_values(array_filter($offerings, static fn (array $row): bool => (int) ($row['is_open'] ?? 0) === 1));

        $isFaseFElective = $type === 'FASE_F_ELECTIVE';
        $minimumOffered = $isFaseFElective
            ? max(7, (int) ($period['minimum_subjects_offered'] ?? 7))
            : max(1, (int) ($period['minimum_subjects_offered'] ?? 1));
        if ($isFaseFElective
            && ((int) ($period['min_primary_choices'] ?? 0) !== 4 || (int) ($period['max_primary_choices'] ?? 0) !== 5)) {
            $errors[] = 'Pilihan utama wajib ditetapkan 4 sampai 5 mata pelajaran.';
        }
        if (count($open) < $minimumOffered) {
            $errors[] = "Mapel aktif baru " . count($open) . "; sedikitnya {$minimumOffered} wajib disediakan.";
        }

        if (($period['selection_start_at'] ?? '') >= ($period['selection_end_at'] ?? '')) {
            $errors[] = 'Akhir periode pemilihan harus sesudah waktu mulai.';
        }
        if (empty($period['curriculum_version_id'])) {
            $errors[] = 'Versi kurikulum yang menjadi dasar penawaran belum ditetapkan.';
        }
        if (isset($period['curriculum_status']) && ! in_array($period['curriculum_status'], ['APPROVED', 'LOCKED'], true)) {
            $errors[] = 'Versi kurikulum harus tetap berstatus disetujui atau dikunci.';
        }
        if ((int) ($period['allow_changes'] ?? 0) === 1 && empty($period['change_deadline'])) {
            $errors[] = 'Batas perubahan wajib ditetapkan ketika perubahan pilihan diizinkan.';
        }

        foreach ($open as $offering) {
            $name = (string) ($offering['subject_name'] ?? ('Mapel #' . ($offering['subject_id'] ?? '?')));
            $subjectCode = strtoupper(trim((string) ($offering['subject_code'] ?? '')));
            $isPrakarya = $subjectCode === 'PRAK'
                || str_contains(mb_strtolower($name), 'prakarya dan kewirausahaan');
            if ((int) ($offering['maximum_students'] ?? 0) < (int) ($offering['minimum_students'] ?? 0)) {
                $errors[] = "{$name}: kapasitas maksimum lebih kecil dari minimum peminat.";
            }
            if ((float) ($offering['weekly_hours'] ?? 0) <= 0) {
                $errors[] = "{$name}: alokasi JP mingguan harus lebih dari nol.";
            }
            if (empty($offering['teacher_id'])) {
                $errors[] = "{$name}: guru pengampu wajib ditetapkan sebelum publikasi.";
            }
            if (array_key_exists('teacher_available', $offering) && (int) $offering['teacher_available'] !== 1) {
                $errors[] = "{$name}: guru pengampu tidak lagi aktif pada unit ini.";
            }
            if (array_key_exists('subject_is_active', $offering) && (int) $offering['subject_is_active'] !== 1) {
                $errors[] = "{$name}: master mapel tidak lagi aktif.";
            }
            if ($type === 'FASE_F_ELECTIVE' && array_key_exists('subject_category', $offering) && ($offering['subject_category'] ?? '') !== 'PILIHAN') {
                $errors[] = "{$name}: master mapel bukan kategori PILIHAN.";
            }
            if (array_key_exists('in_curriculum', $offering) && (int) $offering['in_curriculum'] !== 1) {
                $errors[] = "{$name}: belum tercantum pada struktur kurikulum tingkat tujuan.";
            }
        }

        return [
            'compliant' => $errors === [],
            'errors' => array_values(array_unique($errors)),
            'warnings' => array_values(array_unique($warnings)),
            'open_offerings' => count($open),
            'required_offerings' => $minimumOffered,
        ];
    }

    public function validateSelection(
        array $primaryIds,
        array $backupIds,
        array $period,
        array $availableOfferingIds,
        bool $requireComplete = true,
        array $weeklyHoursByOffering = []
    ): array {
        $errors = [];
        $type = strtoupper((string) ($period['selection_type'] ?? 'FASE_F_ELECTIVE'));
        $primaryIds = array_values(array_map('intval', $primaryIds));
        $backupIds = array_values(array_map('intval', $backupIds));
        $all = array_merge($primaryIds, $backupIds);

        if ($requireComplete && (count($primaryIds) < (int) $period['min_primary_choices'] || count($primaryIds) > (int) $period['max_primary_choices'])) {
            $errors[] = 'Jumlah pilihan utama tidak sesuai ketentuan periode.';
        }
        if (! $requireComplete && count($primaryIds) > (int) $period['max_primary_choices']) {
            $errors[] = 'Jumlah pilihan utama melebihi ketentuan periode.';
        }
        if (count($backupIds) > (int) $period['max_backup_choices']) {
            $errors[] = 'Jumlah pilihan cadangan melebihi ketentuan periode.';
        }
        if (count($all) !== count(array_unique($all))) {
            $errors[] = 'Mata pelajaran yang sama tidak boleh dipilih lebih dari sekali.';
        }
        if (array_diff($all, array_map('intval', $availableOfferingIds)) !== []) {
            $errors[] = 'Terdapat mata pelajaran yang tidak tersedia pada periode ini.';
        }

        return $errors;
    }
}
