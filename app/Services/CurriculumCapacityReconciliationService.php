<?php

namespace App\Services;

use Config\Database;

class CurriculumCapacityReconciliationService
{
    private const TRIM_STEP = 0.5;
    private const MIN_OFFICIAL_HOURS = 1.0;

    public static function buildTrimPreview(int $versionId, int $unitId): array
    {
        $matrix = CurriculumStructureService::getMatrixView($versionId, $unitId);
        $plans = [];

        foreach ($matrix['grades'] as $grade) {
            $gradeId = (int) $grade['id'];
            $breakdown = $matrix['breakdown_by_grade'][$gradeId] ?? null;
            if (!$breakdown) {
                continue;
            }

            $overage = max(0.0, (float) $breakdown['diff']);
            $plans[$gradeId] = [
                'grade_id'        => $gradeId,
                'grade_code'      => $grade['code'],
                'grade_name'      => $grade['name'],
                'official_total'  => (float) $breakdown['official_total'],
                'custom_total'    => (float) $breakdown['custom_total'],
                'effective_total' => (float) $breakdown['effective_total'],
                'max_capacity'    => (float) $breakdown['max_capacity'],
                'overage'         => $overage,
                'status'          => $breakdown['status'],
                'recommendations' => [],
                'can_apply'       => false,
                'message'         => $overage > 0
                    ? 'Kelebihan jam terdeteksi, tetapi belum ada mapel resmi yang aman untuk dipotong.'
                    : 'Kapasitas tingkat ini sudah aman.',
            ];

            if ($overage <= 0) {
                continue;
            }

            $recommendations = self::recommendTrims($versionId, $unitId, $gradeId, $overage);
            $covered = array_sum(array_column($recommendations, 'trim_hours'));
            $plans[$gradeId]['recommendations'] = $recommendations;
            $plans[$gradeId]['can_apply'] = abs($covered - $overage) < 0.01 || $covered >= $overage;
            $plans[$gradeId]['message'] = $plans[$gradeId]['can_apply']
                ? 'Rekomendasi pemotongan siap diterapkan.'
                : 'Kelebihan jam lebih besar dari total jam resmi yang aman dipotong. Silakan sesuaikan manual.';
        }

        return [
            'max_capacity'   => (float) $matrix['max_capacity'],
            'minutes_per_jp' => (int) $matrix['minutes_per_jp'],
            'plans'          => $plans,
        ];
    }

    public static function recommendTrims(int $versionId, int $unitId, int $gradeLevelId, float $overage): array
    {
        $remaining = self::roundToStep($overage);
        if ($remaining <= 0) {
            return [];
        }

        $db = Database::connect();
        $rows = $db->table('curriculum_structures cs')
            ->select('cs.id, cs.uuid, cs.subject_id, cs.official_weekly_hours, cs.category, cs.schedule_priority, s.code AS subject_code, s.name AS subject_name, s.category AS subject_category')
            ->join('subjects s', 's.id = cs.subject_id', 'left')
            ->where('cs.curriculum_version_id', $versionId)
            ->where('cs.unit_id', $unitId)
            ->where('cs.grade_level_id', $gradeLevelId)
            ->where('cs.classroom_id IS NULL')
            ->where('cs.effective_source', 'OFFICIAL')
            ->where('cs.status', 'ACTIVE')
            ->where('cs.deleted_at IS NULL')
            ->where('cs.official_weekly_hours >', self::MIN_OFFICIAL_HOURS)
            ->get()
            ->getResultArray();

        usort($rows, static function (array $a, array $b): int {
            $priorityA = self::categoryTrimPriority((string) ($a['category'] ?: $a['subject_category'] ?: ''));
            $priorityB = self::categoryTrimPriority((string) ($b['category'] ?: $b['subject_category'] ?: ''));
            if ($priorityA !== $priorityB) {
                return $priorityB <=> $priorityA;
            }

            $hoursA = (float) $a['official_weekly_hours'];
            $hoursB = (float) $b['official_weekly_hours'];
            if (abs($hoursA - $hoursB) > 0.01) {
                return $hoursB <=> $hoursA;
            }

            return ((int) ($a['schedule_priority'] ?? 0)) <=> ((int) ($b['schedule_priority'] ?? 0));
        });

        $recommendations = [];
        foreach ($rows as $row) {
            if ($remaining <= 0) {
                break;
            }

            $current = (float) $row['official_weekly_hours'];
            $maxTrim = self::roundToStep($current - self::MIN_OFFICIAL_HOURS);
            if ($maxTrim <= 0) {
                continue;
            }

            $trim = min($remaining, $maxTrim);
            $trim = self::roundToStep($trim);
            if ($trim <= 0) {
                continue;
            }

            $newHours = max(self::MIN_OFFICIAL_HOURS, $current - $trim);
            $recommendations[] = [
                'structure_uuid' => $row['uuid'],
                'subject_id'     => (int) $row['subject_id'],
                'subject_code'   => $row['subject_code'],
                'subject_name'   => $row['subject_name'],
                'category'       => $row['category'] ?: $row['subject_category'],
                'current_hours'  => $current,
                'trim_hours'     => $trim,
                'new_hours'      => $newHours,
                'reason'         => 'Mengurangi jam resmi untuk menyesuaikan total JP dengan kapasitas sekolah.',
            ];

            $remaining = self::roundToStep($remaining - $trim);
        }

        return $recommendations;
    }

    private static function categoryTrimPriority(string $category): int
    {
        $category = strtoupper($category);
        return match ($category) {
            'OTHER', 'PENGEMBANGAN_DIRI', 'EKSTRAKURIKULER' => 50,
            'KOKURIKULER', 'MUATAN_LOKAL' => 40,
            'INTRAKURIKULER' => 10,
            default => 30,
        };
    }

    private static function roundToStep(float $value): float
    {
        return round($value / self::TRIM_STEP) * self::TRIM_STEP;
    }
}
