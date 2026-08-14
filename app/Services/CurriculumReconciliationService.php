<?php

namespace App\Services;

use App\Models\CurriculumVersionModel;
use App\Models\CurriculumStructureModel;
use App\Models\GradeLevelModel;
use App\Models\SchoolUnitModel;

class CurriculumReconciliationService
{
    /**
     * Compute weekly hours reconciliation per grade level and unit for a curriculum version
     */
    public static function reconcileVersion(int $versionId): array
    {
        $versionModel   = new CurriculumVersionModel();
        $structureModel = new CurriculumStructureModel();
        $gradeModel     = new GradeLevelModel();
        $unitModel      = new SchoolUnitModel();

        $version = $versionModel->find($versionId);
        if (!$version) {
            throw new \InvalidArgumentException('Versi kurikulum tidak ditemukan.');
        }

        $units = $unitModel->where('is_active', 1)->findAll();
        $grades = $gradeModel->where('is_active', 1)->orderBy('grade_number', 'ASC')->findAll();

        $structures = $structureModel->where('curriculum_version_id', $versionId)
            ->where('status', 'ACTIVE')
            ->findAll();

        // Group structures by grade_level_id & unit_id (grade defaults only: classroom_id IS NULL)
        $groupedDefaults = [];
        $groupedOverrides = [];

        foreach ($structures as $s) {
            $gId = (int)$s['grade_level_id'];
            $uId = (int)$s['unit_id'];
            $key = "{$uId}_{$gId}";

            if (empty($s['classroom_id'])) {
                if (!isset($groupedDefaults[$key])) {
                    $groupedDefaults[$key] = [];
                }
                $groupedDefaults[$key][] = $s;
            } else {
                if (!isset($groupedOverrides[$key])) {
                    $groupedOverrides[$key] = [];
                }
                $groupedOverrides[$key][] = $s;
            }
        }

        $reconciliationList = [];
        $grandOfficial  = 0.0;
        $grandCustom    = 0.0;
        $grandManual    = 0.0;
        $grandEffective = 0.0;
        $grandErrors    = 0;
        $grandWarnings  = 0;

        foreach ($grades as $g) {
            $gId = (int)$g['id'];
            $uId = (int)$g['unit_id'];
            $key = "{$uId}_{$gId}";

            $unit = array_filter($units, fn($u) => (int)$u['id'] === $uId);
            $unitName = !empty($unit) ? reset($unit)['code'] : "Unit {$uId}";

            $items = $groupedDefaults[$key] ?? [];

            $totOfficial  = 0.0;
            $totCustom    = 0.0;
            $totManual    = 0.0;
            $totEffective = 0.0;
            $warningCount = 0;
            $errorCount   = 0;
            $unresolved   = 0;

            foreach ($items as $it) {
                $off = !empty($it['official_weekly_hours']) ? (float)$it['official_weekly_hours'] : 0.0;
                $cus = !empty($it['custom_weekly_hours']) ? (float)$it['custom_weekly_hours'] : 0.0;
                $man = !empty($it['manual_weekly_hours']) ? (float)$it['manual_weekly_hours'] : 0.0;
                $eff = (float)$it['effective_weekly_hours'];

                $totOfficial  += $off;
                $totCustom    += $cus;
                $totManual    += $man;
                $totEffective += $eff;

                $src = strtoupper(trim($it['effective_source'] ?? 'OFFICIAL'));
                if (in_array($src, ['CUSTOM', 'MANUAL'], true)) {
                    if (empty(trim($it['adjustment_reason'] ?? ''))) {
                        $errorCount++;
                        $unresolved++;
                    } else {
                        $warningCount++;
                    }
                }
            }

            // Determine status
            $status = 'MATCHED';
            if ($errorCount > 0) {
                $status = 'ERROR';
            } elseif ($unresolved > 0) {
                $status = 'NEEDS_REVIEW';
            } elseif (abs($totEffective - $totOfficial) > 0.01) {
                $status = 'DIFFERENT_ACCEPTED';
            }

            $settings = CurriculumPlanningService::getSettings($versionId, $uId);
            $weeklyCapacity = CurriculumPlanningService::computeWeeklyCapacity($settings);
            $capacityDiff = $totEffective - $weeklyCapacity;
            $capacityStatus = abs($capacityDiff) < 0.01 ? 'BALANCED' : ($capacityDiff > 0 ? 'OVER' : 'UNDER');

            $reconciliationList[] = [
                'unit_id'         => $uId,
                'unit_code'       => $unitName,
                'grade_level_id'  => $gId,
                'grade_code'      => $g['code'],
                'grade_name'      => $g['name'],
                'subject_count'   => count($items),
                'total_official'  => $totOfficial,
                'total_custom'    => $totCustom,
                'total_manual'    => $totManual,
                'total_effective' => $totEffective,
                'max_capacity'    => $weeklyCapacity,
                'capacity_diff'   => $capacityDiff,
                'capacity_status' => $capacityStatus,
                'minutes_per_jp'  => (int)($settings['minutes_per_jp'] ?? 40),
                'warning_count'   => $warningCount,
                'error_count'     => $errorCount,
                'unresolved_rows' => $unresolved,
                'status'          => $status,
                'override_count'  => count($groupedOverrides[$key] ?? []),
            ];

            $grandOfficial  += $totOfficial;
            $grandCustom    += $totCustom;
            $grandManual    += $totManual;
            $grandEffective += $totEffective;
            $grandErrors    += $errorCount;
            $grandWarnings  += $warningCount;
        }

        return [
            'version_id'      => $versionId,
            'version_code'    => $version['code'],
            'period_id'       => $version['academic_period_id'],
            'summary'         => [
                'grand_official'  => $grandOfficial,
                'grand_custom'    => $grandCustom,
                'grand_manual'    => $grandManual,
                'grand_effective' => $grandEffective,
                'grand_errors'    => $grandErrors,
                'grand_warnings'  => $grandWarnings,
            ],
            'reconciliation'  => $reconciliationList,
        ];
    }
}
