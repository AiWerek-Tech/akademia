<?php

namespace App\Services;

use App\Models\TeachingAssignmentModel;
use App\Models\TeacherAdditionalDutyModel;
use App\Models\WorkloadPolicyModel;
use App\Models\TeacherWorkloadSnapshotModel;
use App\Models\TeacherModel;

class TeacherWorkloadCalculationService
{
    /**
     * Get the matching policy for a teacher.
     */
    public static function getPolicyForTeacher(int $teacherId, int $periodId, ?int $unitId = null): ?array
    {
        $teacherModel = new TeacherModel();
        $teacher = $teacherModel->find($teacherId);
        if (!$teacher) {
            return null;
        }

        $policyModel = new WorkloadPolicyModel();
        
        // Find policies for the period
        $builder = $policyModel->where('academic_period_id', $periodId)
                               ->where('is_active', 1)
                               ->orderBy('priority', 'DESC');
                               
        if ($unitId !== null) {
            // First check with specific unit_id, fallback to null unit_id
            $builder->groupStart()
                        ->where('unit_id', $unitId)
                        ->orWhere('unit_id', null)
                    ->groupEnd();
        } else {
            $builder->where('unit_id', null);
        }

        // Prefer a unit-specific policy over a global fallback at equal priority.
        $builder->orderBy('unit_id IS NOT NULL', 'DESC', false);
        
        $policies = $builder->findAll();

        // Evaluate priority and specificity
        foreach ($policies as $policy) {
            // Check employment status match
            if ($policy['employment_status'] !== null && strcasecmp($policy['employment_status'], $teacher['employment_status'] ?? '') !== 0) {
                continue;
            }
            // Check employment type match
            if ($policy['employment_type'] !== null && strcasecmp($policy['employment_type'], $teacher['employment_type'] ?? '') !== 0) {
                continue;
            }
            // Check teacher category match (if present in policy)
            if (isset($policy['teacher_category']) && $policy['teacher_category'] !== null) {
                // If the teacher model doesn't have a category field, we can skip or exact match if needed.
                // Assuming optional
            }
            return $policy;
        }

        return null;
    }

    /**
     * Calculate workload metrics for a teacher.
     */
    public static function calculate(int $teacherId, int $versionId, int $periodId, ?int $unitId = null): array
    {
        $assignmentModel = new TeachingAssignmentModel();
        $dutyModel = new TeacherAdditionalDutyModel();

        // 1. Calculate teaching assigned & workload hours
        $assignmentsQuery = $assignmentModel->where('assignment_version_id', $versionId)
                                             ->where('teacher_id', $teacherId)
                                             ->where('status', 'ACTIVE');
        if ($unitId !== null) {
            $assignmentsQuery->where('unit_id', $unitId);
        }
        $assignments = $assignmentsQuery->findAll();

        $teachingAssigned = 0.00;
        $teachingWorkload = 0.00;
        foreach ($assignments as $a) {
            $teachingAssigned += (float)$a['assigned_weekly_hours'];
            $teachingWorkload += (float)$a['workload_weekly_hours'];
        }

        // 2. Calculate additional duties workload hours
        $dutiesQuery = $dutyModel->where('assignment_version_id', $versionId)
                                 ->where('teacher_id', $teacherId)
                                 ->where('status', 'ACTIVE');
        if ($unitId !== null) {
            $dutiesQuery->groupStart()
                            ->where('unit_id', $unitId)
                            ->orWhere('unit_id', null)
                        ->groupEnd();
        }
        $duties = $dutiesQuery->findAll();

        $dutyHours = 0.00;
        foreach ($duties as $d) {
            $dutyHours += (float)($d['workload_hours'] ?? 0.00);
        }

        $totalWorkload = $teachingWorkload + $dutyHours;

        // 3. Match workload policy
        $policy = self::getPolicyForTeacher($teacherId, $periodId, $unitId);

        $policyMin = $policy ? (float)($policy['minimum_teaching_hours'] ?? 0.00) : null;
        $policyTarget = $policy ? (float)($policy['target_total_hours'] ?? 0.00) : null;
        $policyMax = $policy ? (float)($policy['maximum_total_hours'] ?? 0.00) : null;

        $shortage = 0.00;
        $overload = 0.00;
        $status = 'WITHIN_TARGET';

        if ($policy) {
            if ($policyMin !== null && $totalWorkload < $policyMin) {
                $status = 'UNDERLOAD';
                $shortage = $policyMin - $totalWorkload;
            } elseif ($policyMax !== null && $totalWorkload > $policyMax) {
                $status = 'OVERLOAD';
                $overload = $totalWorkload - $policyMax;
            }
        } else {
            $status = 'NO_POLICY';
        }

        return [
            'assignment_version_id'   => $versionId,
            'teacher_id'              => $teacherId,
            'academic_period_id'      => $periodId,
            'unit_id'                 => $unitId,
            'teaching_assigned_hours' => $teachingAssigned,
            'teaching_workload_hours' => $teachingWorkload,
            'additional_duty_hours'   => $dutyHours,
            'total_workload_hours'    => $totalWorkload,
            'policy_id'               => $policy ? $policy['id'] : null,
            'policy_minimum'          => $policyMin,
            'policy_target'           => $policyTarget,
            'policy_maximum'          => $policyMax,
            'shortage_hours'          => $shortage,
            'overload_hours'          => $overload,
            'status'                  => $status,
            'details_json'            => json_encode([
                'assignments_count' => count($assignments),
                'duties_count'      => count($duties),
                'policy_name'       => $policy ? ($policy['uuid'] ?? 'Matched Policy') : 'None'
            ]),
        ];
    }

    /**
     * Calculate and snapshot workload for a teacher.
     */
    public static function createSnapshot(int $teacherId, int $versionId, int $periodId, ?int $unitId = null, ?int $actorId = null): array
    {
        $metrics = self::calculate($teacherId, $versionId, $periodId, $unitId);
        $snapshotModel = new TeacherWorkloadSnapshotModel();

        // Check if snapshot already exists
        $existing = $snapshotModel->where('assignment_version_id', $versionId)
                                  ->where('teacher_id', $teacherId)
                                  ->where('unit_id', $unitId)
                                  ->first();

        $data = array_merge($metrics, [
            'calculated_at' => date('Y-m-d H:i:s'),
            'calculated_by' => $actorId
        ]);

        if ($existing) {
            $snapshotModel->update($existing['id'], $data);
            $data['id'] = $existing['id'];
        } else {
            $id = $snapshotModel->insert($data);
            $data['id'] = $id;
        }

        return $data;
    }

    /**
     * Recalculate snapshots for all active teachers in a version.
     */
    public static function recalculateAll(int $versionId, int $periodId, ?int $unitId = null, ?int $actorId = null): void
    {
        $db = \Config\Database::connect();
        $teacherModel = new TeacherModel();

        $assignmentQuery = $db->table('teaching_assignments')
            ->select('teacher_id')
            ->where('assignment_version_id', $versionId)
            ->where('status', 'ACTIVE');
        $dutyQuery = $db->table('teacher_additional_duties')
            ->select('teacher_id')
            ->where('assignment_version_id', $versionId)
            ->where('status', 'ACTIVE');

        if ($unitId !== null) {
            $assignmentQuery->where('unit_id', $unitId);
            $dutyQuery->groupStart()->where('unit_id', $unitId)->orWhere('unit_id', null)->groupEnd();
        }

        $teacherIds = array_values(array_unique(array_merge(
            array_map('intval', array_column($assignmentQuery->get()->getResultArray(), 'teacher_id')),
            array_map('intval', array_column($dutyQuery->get()->getResultArray(), 'teacher_id'))
        )));

        $teachers = $teacherIds === [] ? [] : $teacherModel
            ->whereIn('id', $teacherIds)
            ->where('is_active', 1)
            ->findAll();

        foreach ($teachers as $t) {
            self::createSnapshot((int)$t['id'], $versionId, $periodId, $unitId, $actorId);
        }
    }
}
