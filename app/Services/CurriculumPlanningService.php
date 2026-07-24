<?php

namespace App\Services;

use App\Models\CurriculumPlanningSettingModel;
use Config\Database;

class CurriculumPlanningService
{
    private const DEFAULTS = [
        'teaching_days_per_week' => 5,
        'daily_jp_capacity' => 9.0,
        'teacher_minimum_hours' => 24.0,
        'teacher_maximum_hours' => 40.0,
        'allow_custom_hours' => 1,
        'notes' => null,
    ];

    public static function getSettings(int $versionId, int $unitId): array
    {
        $row = (new CurriculumPlanningSettingModel())
            ->where('curriculum_version_id', $versionId)
            ->where('unit_id', $unitId)
            ->first();

        return array_merge(self::DEFAULTS, $row ?: []);
    }

    public static function saveSettings(int $versionId, int $unitId, array $input): array
    {
        $days = (int) ($input['teaching_days_per_week'] ?? 5);
        $dailyCapacity = (float) ($input['daily_jp_capacity'] ?? 9);
        $minimum = (float) ($input['teacher_minimum_hours'] ?? 24);
        $maximum = (float) ($input['teacher_maximum_hours'] ?? 40);

        if ($days < 1 || $days > 7) {
            throw new \InvalidArgumentException('Jumlah hari belajar harus antara 1 sampai 7 hari.');
        }
        if ($dailyCapacity <= 0 || $dailyCapacity > 20) {
            throw new \InvalidArgumentException('Kapasitas JP per hari harus lebih dari 0 dan maksimal 20 JP.');
        }
        if ($minimum < 0 || $maximum <= 0 || $minimum > $maximum) {
            throw new \InvalidArgumentException('Batas minimum dan maksimum beban guru tidak valid.');
        }

        $model = new CurriculumPlanningSettingModel();
        $existing = $model->where('curriculum_version_id', $versionId)->where('unit_id', $unitId)->first();
        $data = [
            'curriculum_version_id' => $versionId,
            'unit_id' => $unitId,
            'teaching_days_per_week' => $days,
            'daily_jp_capacity' => $dailyCapacity,
            'teacher_minimum_hours' => $minimum,
            'teacher_maximum_hours' => $maximum,
            'allow_custom_hours' => !empty($input['allow_custom_hours']) ? 1 : 0,
            'notes' => trim((string) ($input['notes'] ?? '')) ?: null,
            'updated_by' => session()->get('user_id'),
        ];

        if ($existing) {
            $model->update($existing['id'], $data);
            return $model->find($existing['id']);
        }

        $data['created_by'] = session()->get('user_id');
        $id = $model->insert($data, true);
        return $model->find($id);
    }

    public static function buildOverview(int $versionId, int $academicPeriodId, int $unitId): array
    {
        $db = Database::connect();
        $settings = self::getSettings($versionId, $unitId);
        $weeklyCapacity = (float) $settings['teaching_days_per_week'] * (float) $settings['daily_jp_capacity'];

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
            ->select('cs.*, s.name AS subject_name')
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
            $summary['teacher_demand_hours'] += $demand;

            if ($structure['effective_source'] !== 'OFFICIAL') {
                $summary['custom_count']++;
            }
            if (in_array($structure['category'], ['KEGIATAN_TETAP', 'PENGEMBANGAN_DIRI'], true)) {
                $summary['activity_count']++;
            }
            if ($effective > 1 && empty($structure['block_pattern_json'])) {
                $summary['missing_block_pattern_count']++;
            }
        }

        foreach ($gradeRows as &$row) {
            $row['remaining_capacity'] = $weeklyCapacity - $row['effective_hours'];
            $row['status'] = $row['subjects'] === 0 ? 'EMPTY' : ($row['remaining_capacity'] < 0 ? 'OVER' : 'READY');
        }
        unset($row);

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

        $summary['allocated_hours'] = $allocatedHours;
        $summary['unallocated_hours'] = max(0, $summary['teacher_demand_hours'] - $allocatedHours);
        $summary['allocation_percent'] = $summary['teacher_demand_hours'] > 0
            ? min(100, round(($allocatedHours / $summary['teacher_demand_hours']) * 100, 1))
            : 0;

        return [
            'settings' => $settings,
            'weekly_capacity' => $weeklyCapacity,
            'grades' => array_values($gradeRows),
            'summary' => $summary,
            'assignment_version' => $assignmentVersion,
        ];
    }
}
