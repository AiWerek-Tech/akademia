<?php

namespace App\Services;

use App\Models\AssignmentVersionModel;
use App\Models\TeachingAssignmentModel;
use App\Models\CurriculumStructureModel;
use App\Models\ClassroomModel;
use App\Models\SubjectModel;
use App\Models\TeacherModel;

class AssignmentMatrixService
{
    /**
     * Generate the requirement matrix for an assignment version.
     */
    public static function getMatrix(int $versionId, ?int $unitId = null): array
    {
        $versionModel = new AssignmentVersionModel();
        $version = $versionModel->find($versionId);
        if (!$version) {
            return [];
        }

        $classroomModel = new ClassroomModel();
        $structureModel = new CurriculumStructureModel();
        $assignmentModel = new TeachingAssignmentModel();
        $subjectModel = new SubjectModel();
        $teacherModel = new TeacherModel();

        // 1. Fetch classrooms of the academic period
        $classroomModel->where('academic_period_id', $version['academic_period_id'])
            ->where('is_active', 1);
        if ($unitId !== null) {
            $classroomModel->where('unit_id', $unitId);
        }
        $classrooms = $classroomModel->findAll();

        // 2. Fetch all active subjects
        $subjects = $subjectModel->where('is_active', 1)->findAll();
        $subjectsById = [];
        foreach ($subjects as $sub) {
            $subjectsById[$sub['id']] = $sub;
        }

        // 3. Fetch structures for this curriculum version
        $structures = $structureModel->where('curriculum_version_id', $version['curriculum_version_id'])
                                     ->where('status', 'ACTIVE')
                                     ->findAll();

        // Group structures by subject_id & classroom_id, and subject_id & grade_level_id
        $gradeDefaults = [];
        $classroomOverrides = [];

        foreach ($structures as $s) {
            $subId = (int)$s['subject_id'];
            if ($s['classroom_id'] === null) {
                $gradeId = (int)$s['grade_level_id'];
                $gradeDefaults[$gradeId][$subId] = $s;
            } else {
                $classId = (int)$s['classroom_id'];
                $classroomOverrides[$classId][$subId] = $s;
            }
        }

        // 4. Fetch assignments for this version
        $assignmentModel->where('assignment_version_id', $versionId);
        if ($unitId !== null) {
            $assignmentModel->where('unit_id', $unitId);
        }
        $assignments = $assignmentModel->findAll();
        
        // Group assignments by classroom_id & subject_id
        $assignmentsMap = [];
        foreach ($assignments as $a) {
            $classId = (int)$a['classroom_id'];
            $subId = (int)$a['subject_id'];
            $assignmentsMap[$classId][$subId][] = $a;
        }

        // Fetch teachers for name lookup
        $teachers = $teacherModel->findAll();
        $teachersById = [];
        foreach ($teachers as $t) {
            $teachersById[$t['id']] = $t;
        }

        $matrix = [];

        // 5. Build matrix per classroom & subject slot
        foreach ($classrooms as $c) {
            $classId = (int)$c['id'];
            $gradeId = (int)$c['grade_level_id'];
            $unitId = (int)$c['unit_id'];

            // Find all subjects that have defaults or overrides for this classroom/grade
            $availableSubjectIds = [];
            if (isset($gradeDefaults[$gradeId])) {
                $availableSubjectIds = array_merge($availableSubjectIds, array_keys($gradeDefaults[$gradeId]));
            }
            if (isset($classroomOverrides[$classId])) {
                $availableSubjectIds = array_merge($availableSubjectIds, array_keys($classroomOverrides[$classId]));
            }
            $availableSubjectIds = array_unique($availableSubjectIds);

            foreach ($availableSubjectIds as $subId) {
                // Resolve structure using override or fallback to grade default
                $structureRow = null;
                if (isset($classroomOverrides[$classId][$subId])) {
                    $structureRow = $classroomOverrides[$classId][$subId];
                } elseif (isset($gradeDefaults[$gradeId][$subId])) {
                    $structureRow = $gradeDefaults[$gradeId][$subId];
                }

                if (!$structureRow) {
                    continue;
                }

                $effectiveJP = (float)$structureRow['effective_weekly_hours'];
                
                // Get allocated assignments
                $allocations = $assignmentsMap[$classId][$subId] ?? [];
                
                $assignedJP = 0.00;
                $assignedTeachers = [];
                foreach ($allocations as $a) {
                    $assignedJP += (float)$a['assigned_weekly_hours'];
                    $tId = (int)$a['teacher_id'];
                    $assignedTeachers[] = [
                        'id'                    => $tId,
                        'full_name'             => $teachersById[$tId]['full_name'] ?? "ID {$tId}",
                        'assigned_weekly_hours' => (float)$a['assigned_weekly_hours'],
                        'workload_weekly_hours' => (float)$a['workload_weekly_hours'],
                        'assignment_role'       => $a['assignment_role'],
                        'is_primary'            => (int)$a['is_primary_teacher'] === 1
                    ];
                }

                $remainingJP = $effectiveJP - $assignedJP;

                // Determine allocation mode
                $allocationMode = 'UNASSIGNED';
                if (count($assignedTeachers) === 1) {
                    $allocationMode = 'SINGLE_TEACHER';
                } elseif (count($assignedTeachers) > 1) {
                    // Check if it's team teaching or split hours
                    $isTeam = false;
                    foreach ($allocations as $a) {
                        if ($a['team_group_uuid'] !== null) {
                            $isTeam = true;
                            break;
                        }
                    }
                    $allocationMode = $isTeam ? 'TEAM_TEACHING' : 'SPLIT_HOURS';
                }

                // Determine validation status
                $validationStatus = 'MATCHED';
                if ($assignedJP == 0) {
                    $validationStatus = 'UNASSIGNED';
                } elseif ($assignedJP > $effectiveJP) {
                    $validationStatus = 'OVER_ALLOCATED';
                } elseif ($assignedJP < $effectiveJP) {
                    $validationStatus = 'UNDER_ALLOCATED';
                }

                $matrix[] = [
                    'unit_id'                => $unitId,
                    'classroom_id'           => $classId,
                    'classroom_name'         => $c['name'],
                    'subject_id'             => $subId,
                    'subject_code'           => $subjectsById[$subId]['code'] ?? '',
                    'subject_name'           => $subjectsById[$subId]['name'] ?? '',
                    'curriculum_structure_id'=> $structureRow['id'],
                    'effective_weekly_hours' => $effectiveJP,
                    'assigned_weekly_hours'  => $assignedJP,
                    'remaining_weekly_hours' => $remainingJP,
                    'teachers'               => $assignedTeachers,
                    'allocation_mode'        => $allocationMode,
                    'validation_status'      => $validationStatus
                ];
            }
        }

        return $matrix;
    }
}
