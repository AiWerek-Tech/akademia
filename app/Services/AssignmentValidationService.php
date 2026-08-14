<?php

namespace App\Services;

use App\Models\AssignmentVersionModel;
use App\Models\TeachingAssignmentModel;
use App\Models\TeacherAdditionalDutyModel;
use App\Models\AssignmentValidationResultModel;
use App\Models\CurriculumStructureModel;
use App\Models\TeacherModel;
use App\Models\ClassroomModel;
use App\Models\AdditionalDutyTypeModel;

class AssignmentValidationService
{
    /**
     * Run all validation checks for an assignment version.
     * Returns true if there are NO errors or blockers.
     */
    public static function validate(int $versionId, ?int $actorId = null): bool
    {
        $versionModel = new AssignmentVersionModel();
        $version = $versionModel->find($versionId);
        if (!$version) {
            return false;
        }

        $validationResultModel = new AssignmentValidationResultModel();
        
        // 1. Delete previous validation results for this version
        $validationResultModel->where('assignment_version_id', $versionId)->delete();

        $errorsCount = 0;
        $results = [];

        // 2. Load dependencies
        $assignmentModel = new TeachingAssignmentModel();
        $dutyModel = new TeacherAdditionalDutyModel();
        $structureModel = new CurriculumStructureModel();
        $teacherModel = new TeacherModel();
        $classroomModel = new ClassroomModel();
        $dutyTypeModel = new AdditionalDutyTypeModel();

        // Get all structures for this curriculum version
        $structures = $structureModel->where('curriculum_version_id', $version['curriculum_version_id'])
                                     ->where('status', 'ACTIVE')
                                     ->findAll();

        // Get all teaching assignments for this version
        $assignments = $assignmentModel->where('assignment_version_id', $versionId)->findAll();
        
        // Get all additional duties for this version
        $duties = $dutyModel->where('assignment_version_id', $versionId)->findAll();

        // Track allocations per structure ID
        $allocatedHours = [];
        $primaryTeachersPerStructure = [];
        foreach ($assignments as $a) {
            $structId = (int)$a['curriculum_structure_id'];
            if (!isset($allocatedHours[$structId])) {
                $allocatedHours[$structId] = 0.00;
            }
            $allocatedHours[$structId] += (float)$a['assigned_weekly_hours'];

            if ((int)$a['is_primary_teacher'] === 1) {
                if (!isset($primaryTeachersPerStructure[$structId])) {
                    $primaryTeachersPerStructure[$structId] = [];
                }
                $primaryTeachersPerStructure[$structId][] = $a['teacher_id'];
            }
        }

        // --- SECTION A: STRUCTURE-LEVEL VALIDATIONS ---
        foreach ($structures as $s) {
            $structId = (int)$s['id'];
            $effectiveJP = (float)$s['effective_weekly_hours'];
            $totalAllocated = $allocatedHours[$structId] ?? 0.00;

            // Check if any teacher is assigned
            if ($effectiveJP > 0 && $totalAllocated == 0) {
                $results[] = [
                    'assignment_version_id'  => $versionId,
                    'teaching_assignment_id' => null,
                    'teacher_id'             => null,
                    'validation_code'        => 'MISSING_TEACHER',
                    'severity'               => 'ERROR',
                    'message'                => "Belum ada guru yang ditugaskan untuk kelas {$s['classroom_id']} mapel {$s['subject_id']}.",
                    'details_json'           => json_encode(['structure_id' => $structId])
                ];
                $errorsCount++;
            }

            // Check over-allocation
            if ($totalAllocated > $effectiveJP) {
                $results[] = [
                    'assignment_version_id'  => $versionId,
                    'teaching_assignment_id' => null,
                    'teacher_id'             => null,
                    'validation_code'        => 'OVER_ALLOCATED_STRUCTURE',
                    'severity'               => 'ERROR',
                    'message'                => "Alokasi jam guru ({$totalAllocated} JP) melebihi kebutuhan struktur ({$effectiveJP} JP) untuk kelas {$s['classroom_id']} mapel {$s['subject_id']}.",
                    'details_json'           => json_encode(['structure_id' => $structId, 'allocated' => $totalAllocated, 'required' => $effectiveJP])
                ];
                $errorsCount++;
            }

            // Check under-allocation
            if ($totalAllocated > 0 && $totalAllocated < $effectiveJP) {
                $results[] = [
                    'assignment_version_id'  => $versionId,
                    'teaching_assignment_id' => null,
                    'teacher_id'             => null,
                    'validation_code'        => 'UNDER_ALLOCATED_STRUCTURE',
                    'severity'               => 'WARNING',
                    'message'                => "Alokasi jam guru ({$totalAllocated} JP) kurang dari kebutuhan struktur ({$effectiveJP} JP) untuk kelas {$s['classroom_id']} mapel {$s['subject_id']}.",
                    'details_json'           => json_encode(['structure_id' => $structId, 'allocated' => $totalAllocated, 'required' => $effectiveJP])
                ];
            }
        }

        // --- SECTION A2: SCHEDULE CAPACITY RECONCILIATION ---
        // Assignment totals must fit the actual academic grid after fixed
        // routine activities reserve JP slots. This is intentionally separate
        // from curriculum allocation: routines do not reduce the mapel need,
        // but they do reduce where that need can be scheduled.
        $scheduleCapacity = ScheduleCapacityService::forAssignmentVersion($versionId);
        if (!empty($scheduleCapacity['has_schedule'])) {
            $requiredByClass = [];
            foreach ($structures as $s) {
                $classId = (int) ($s['classroom_id'] ?? 0);
                if ($classId > 0) {
                    $requiredByClass[$classId] = ($requiredByClass[$classId] ?? 0) + (float) $s['effective_weekly_hours'];
                }
            }
            foreach ($requiredByClass as $classId => $requiredJP) {
                $capacity = $scheduleCapacity['classrooms'][$classId] ?? null;
                if (!$capacity) continue;
                $requiredBlocks = (int) ceil($requiredJP / 2);
                $slotShortage = $requiredJP - (float) $capacity['available_slots'];
                $blockShortage = $requiredBlocks - (int) $capacity['available_2jp_blocks'];
                if ($slotShortage <= 0 && $blockShortage <= 0) continue;
                $shortage = max(0, $slotShortage);
                $results[] = [
                    'assignment_version_id' => $versionId,
                    'teaching_assignment_id' => null,
                    'teacher_id' => null,
                    'validation_code' => $slotShortage > 0 ? 'SCHEDULE_CAPACITY_EXCEEDED' : 'SCHEDULE_2JP_BLOCK_CAPACITY_EXCEEDED',
                    'severity' => 'ERROR',
                    'message' => $slotShortage > 0
                        ? "Kapasitas jadwal {$capacity['classroom_name']} kurang {$shortage} JP setelah {$capacity['routine_jp']} JP kegiatan rutin akademik dikunci."
                        : "Kapasitas blok 2 JP {$capacity['classroom_name']} kurang {$blockShortage} blok setelah kegiatan rutin akademik dikunci.",
                    'details_json' => json_encode([
                        'classroom_id' => $classId,
                        'required_jp' => $requiredJP,
                        'available_slots' => $capacity['available_slots'],
                        'routine_jp' => $capacity['routine_jp'],
                        'available_2jp_blocks' => $capacity['available_2jp_blocks'],
                        'required_2jp_blocks' => $requiredBlocks,
                    ])
                ];
                $errorsCount++;
            }
        }

        // --- SECTION B: ASSIGNMENT-LEVEL VALIDATIONS ---
        $duplicateGuard = [];
        $homeroomsPerClassroom = [];

        foreach ($assignments as $a) {
            $teacherId = (int)$a['teacher_id'];
            $teacher = $teacherModel->find($teacherId);

            // 1. Inactive teacher check
            if (!$teacher || (int)$teacher['is_active'] === 0) {
                $results[] = [
                    'assignment_version_id'  => $versionId,
                    'teaching_assignment_id' => $a['id'],
                    'teacher_id'             => $teacherId,
                    'validation_code'        => 'INACTIVE_TEACHER',
                    'severity'               => 'BLOCKER',
                    'message'                => "Guru {$a['teacher_id']} yang ditugaskan berstatus tidak aktif.",
                    'details_json'           => json_encode(['assignment_id' => $a['id']])
                ];
                $errorsCount++;
            }

            // 2. Unit mismatch check
            if ($teacher) {
                $db = \Config\Database::connect();
                $assignmentMatch = $db->table('teacher_unit_assignments')
                                      ->where('teacher_id', $teacherId)
                                      ->where('unit_id', $a['unit_id'])
                                      ->where('status', 'ACTIVE')
                                      ->get()
                                      ->getRowArray();
                if (!$assignmentMatch) {
                    $results[] = [
                        'assignment_version_id'  => $versionId,
                        'teaching_assignment_id' => $a['id'],
                        'teacher_id'             => $teacherId,
                        'validation_code'        => 'TEACHER_UNIT_MISMATCH',
                        'severity'               => 'ERROR',
                        'message'                => "Guru {$teacher['full_name']} tidak terdaftar pada unit {$a['unit_id']}.",
                        'details_json'           => json_encode(['assignment_id' => $a['id'], 'unit_id' => $a['unit_id']])
                    ];
                    $errorsCount++;
                }
            }

            // 3. Zero or negative hours check
            if ((float)$a['assigned_weekly_hours'] <= 0) {
                $results[] = [
                    'assignment_version_id'  => $versionId,
                    'teaching_assignment_id' => $a['id'],
                    'teacher_id'             => $teacherId,
                    'validation_code'        => 'ZERO_ASSIGNED_HOURS',
                    'severity'               => 'ERROR',
                    'message'                => "Jumlah jam mengajar pada penugasan {$a['id']} tidak boleh nol atau negatif.",
                    'details_json'           => json_encode(['assigned_hours' => $a['assigned_weekly_hours']])
                ];
                $errorsCount++;
            }

            // 4. Duplicate assignment check
            $dupKey = "{$teacherId}-{$a['classroom_id']}-{$a['subject_id']}";
            if (isset($duplicateGuard[$dupKey])) {
                $teacherName = $teacher ? $teacher['full_name'] : $teacherId;
                $results[] = [
                    'assignment_version_id'  => $versionId,
                    'teaching_assignment_id' => $a['id'],
                    'teacher_id'             => $teacherId,
                    'validation_code'        => 'DUPLICATE_ASSIGNMENT',
                    'severity'               => 'ERROR',
                    'message'                => "Guru {$teacherName} ditugaskan ganda pada rombel {$a['classroom_id']} mapel {$a['subject_id']}.",
                    'details_json'           => json_encode(['assignment_id' => $a['id']])
                ];
                $errorsCount++;
            }
            $duplicateGuard[$dupKey] = true;
        }

        // --- SECTION C: ADDITIONAL DUTY VALIDATIONS ---
        $dutyHoldersCount = [];
        foreach ($duties as $d) {
            $dutyTypeId = (int)$d['duty_type_id'];
            if (!isset($dutyHoldersCount[$dutyTypeId])) {
                $dutyHoldersCount[$dutyTypeId] = 0;
            }
            $dutyHoldersCount[$dutyTypeId]++;

            $dutyType = $dutyTypeModel->find($dutyTypeId);
            if ($dutyType && $dutyType['maximum_holders'] !== null && $dutyHoldersCount[$dutyTypeId] > (int)$dutyType['maximum_holders']) {
                $results[] = [
                    'assignment_version_id'  => $versionId,
                    'teaching_assignment_id' => null,
                    'teacher_id'             => $d['teacher_id'],
                    'validation_code'        => 'DUTY_MAX_HOLDERS_EXCEEDED',
                    'severity'               => 'ERROR',
                    'message'                => "Jumlah pemegang tugas tambahan {$dutyType['name']} melebihi batas maksimum ({$dutyType['maximum_holders']} orang).",
                    'details_json'           => json_encode(['duty_type_id' => $dutyTypeId, 'max' => $dutyType['maximum_holders']])
                ];
                $errorsCount++;
            }

            // If Homeroom Teacher: track homeroom teacher per classroom
            if ($dutyType && $dutyType['code'] === 'HOMEROOM_TEACHER') {
                // Get homeroom details or parse classroom_id from duty overrides or context
                // For validation simplicity, if a homeroom duty is created, it should link to classroom_id.
                // We'll validate homeroom count during calculation.
            }
        }

        // --- SECTION D: TEACHER-LEVEL WORKLOAD POLICIES ---
        // Calculate workloads and snapshots
        $teacherModel = new TeacherModel();
        $teachers = $teacherModel->where('is_active', 1)->findAll();
        foreach ($teachers as $t) {
            $teacherId = (int)$t['id'];
            $calc = TeacherWorkloadCalculationService::calculate($teacherId, $versionId, (int)$version['academic_period_id']);

            if ($calc['status'] === 'NO_POLICY') {
                $results[] = [
                    'assignment_version_id'  => $versionId,
                    'teaching_assignment_id' => null,
                    'teacher_id'             => $teacherId,
                    'validation_code'        => 'NO_WORKLOAD_POLICY',
                    'severity'               => 'WARNING',
                    'message'                => "Guru {$t['full_name']} tidak memiliki kebijakan beban kerja aktif.",
                    'details_json'           => json_encode(['teacher_id' => $teacherId])
                ];
            } elseif ($calc['status'] === 'UNDERLOAD') {
                $results[] = [
                    'assignment_version_id'  => $versionId,
                    'teaching_assignment_id' => null,
                    'teacher_id'             => $teacherId,
                    'validation_code'        => 'TEACHER_UNDERLOAD',
                    'severity'               => 'WARNING',
                    'message'                => "Beban kerja guru {$t['full_name']} ({$calc['total_workload_hours']} JP) di bawah minimum kebijakan ({$calc['policy_minimum']} JP).",
                    'details_json'           => json_encode(['teacher_id' => $teacherId, 'workload' => $calc['total_workload_hours'], 'min' => $calc['policy_minimum']])
                ];
            } elseif ($calc['status'] === 'OVERLOAD') {
                $results[] = [
                    'assignment_version_id'  => $versionId,
                    'teaching_assignment_id' => null,
                    'teacher_id'             => $teacherId,
                    'validation_code'        => 'TEACHER_OVERLOAD',
                    'severity'               => 'WARNING',
                    'message'                => "Beban kerja guru {$t['full_name']} ({$calc['total_workload_hours']} JP) melebihi batas maksimum kebijakan ({$calc['policy_maximum']} JP).",
                    'details_json'           => json_encode(['teacher_id' => $teacherId, 'workload' => $calc['total_workload_hours'], 'max' => $calc['policy_maximum']])
                ];
            }
        }

        // 3. Save all results
        foreach ($results as $res) {
            $res['created_at'] = date('Y-m-d H:i:s');
            $validationResultModel->insert($res);
        }

        // Return true if NO BLOCKER or ERROR results exist
        $hasErrors = $validationResultModel->where('assignment_version_id', $versionId)
                                           ->groupStart()
                                               ->where('severity', 'ERROR')
                                               ->orWhere('severity', 'BLOCKER')
                                           ->groupEnd()
                                           ->countAllResults() > 0;

        return !$hasErrors;
    }
}
