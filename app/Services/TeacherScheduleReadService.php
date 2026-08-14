<?php

namespace App\Services;

use Config\Database;

/**
 * Builds the read-only schedule projection used by the teacher portal.
 *
 * Administrative schedule editing remains in the scheduling module; this
 * service prefers the active version and clearly marks whether it is official.
 * Draft/review versions remain useful as read-only previews in the personal
 * portal while APPROVED/LOCKED versions are the only official schedules.
 */
class TeacherScheduleReadService
{
    private const SCOPES = ['teacher', 'classroom', 'grade', 'all'];

    public function buildForUnits(
        int $teacherId,
        array $unitIds,
        int $periodId,
        string $scope = 'teacher',
        ?int $classroomId = null,
        ?int $gradeLevelId = null
    ): array {
        $unitIds = array_values(array_unique(array_filter(array_map('intval', $unitIds))));
        if ($unitIds === []) {
            return $this->emptyProjection($scope, $classroomId, $gradeLevelId);
        }
        if (count($unitIds) === 1) {
            $projection = $this->build($teacherId, $unitIds[0], $periodId, $scope, $classroomId, $gradeLevelId);
            $projection['scheduleVersions'] = $projection['scheduleVersion'] ? [$projection['scheduleVersion']] : [];
            $projection['unitIds'] = $unitIds;
            $projection['isCombinedUnitScope'] = false;
            return $projection;
        }

        $db = Database::connect();
        $unitRows = $db->table('school_units')->whereIn('id', $unitIds)->get()->getResultArray();
        $unitMap = [];
        foreach ($unitRows as $unit) {
            $unitMap[(int) $unit['id']] = $unit;
        }
        $resourceUnitId = null;
        if ($scope === 'classroom' && $classroomId) {
            $resourceUnitId = (int) ($db->table('classrooms')->select('unit_id')->where('id', $classroomId)->get()->getRowArray()['unit_id'] ?? 0);
        } elseif ($scope === 'grade' && $gradeLevelId) {
            $resourceUnitId = (int) ($db->table('grade_levels')->select('unit_id')->where('id', $gradeLevelId)->get()->getRowArray()['unit_id'] ?? 0);
        }

        $result = $this->emptyProjection($scope, $classroomId, $gradeLevelId);
        $result['unitIds'] = $unitIds;
        $result['isCombinedUnitScope'] = true;
        $dayMap = [];
        foreach ($unitIds as $unitId) {
            if ($resourceUnitId && $unitId !== $resourceUnitId) {
                continue;
            }
            $part = $this->build($teacherId, $unitId, $periodId, $scope, $classroomId, $gradeLevelId);
            $unit = $unitMap[$unitId] ?? ['id' => $unitId, 'code' => '', 'name' => 'Unit'];
            $result['teacherInfo'] ??= $part['teacherInfo'];
            if ($part['scheduleVersion']) {
                $part['scheduleVersion']['unit_name'] = $unit['name'];
                $part['scheduleVersion']['unit_code'] = $unit['code'];
                $result['scheduleVersions'][] = $part['scheduleVersion'];
            }
            foreach ($part['days'] as $day) {
                $dayMap[(int) $day['day_number']] = $day;
            }
            foreach ($part['scheduleGrid'] as $dayNumber => $slots) {
                foreach ($slots as $slotNumber => $entries) {
                    foreach ($entries as $entry) {
                        $entry['unit_id'] = $unitId;
                        $entry['unit_name'] = $unit['name'];
                        $entry['unit_code'] = $unit['code'];
                        $result['scheduleGrid'][$dayNumber][$slotNumber][] = $entry;
                    }
                }
            }
            foreach ($part['assignments'] as $assignment) {
                $assignment['unit_name'] = $unit['name'];
                $assignment['unit_code'] = $unit['code'];
                $result['assignments'][] = $assignment;
            }
            foreach ($part['classrooms'] as $classroom) {
                $classroom['unit_name'] = $unit['name'];
                $classroom['unit_code'] = $unit['code'];
                $result['classrooms'][] = $classroom;
            }
            foreach ($part['gradeLevels'] as $grade) {
                $grade['unit_name'] = $unit['name'];
                $grade['unit_code'] = $unit['code'];
                $result['gradeLevels'][] = $grade;
            }
            $result['maxSlot'] = max($result['maxSlot'], $part['maxSlot']);
        }
        ksort($dayMap);
        $result['days'] = array_values($dayMap);
        $entries = [];
        foreach ($result['scheduleGrid'] as $slots) {
            foreach ($slots as $slotEntries) {
                array_push($entries, ...$slotEntries);
            }
        }
        $result['totalJp'] = $entries !== []
            ? count($entries)
            : array_sum(array_map(static fn (array $a): float => (float) ($a['assigned_weekly_hours'] ?? 0), $result['assignments']));
        $summaryRows = $entries !== [] ? $entries : $result['assignments'];
        $result['subjectCount'] = count(array_unique(array_filter(array_column($summaryRows, 'subject_id'))));
        $result['classroomCount'] = count(array_unique(array_filter(array_column($summaryRows, 'classroom_id'))));
        $result['scheduleVersion'] = $result['scheduleVersions'][0] ?? null;
        $result['scheduleIsOfficial'] = $result['scheduleVersions'] !== []
            && count(array_filter($result['scheduleVersions'], static fn (array $v): bool => in_array(strtoupper((string) $v['workflow_status']), ['APPROVED', 'LOCKED'], true))) === count($result['scheduleVersions']);
        return $result;
    }

    private function emptyProjection(string $scope, ?int $classroomId, ?int $gradeLevelId): array
    {
        return [
            'teacherInfo' => null, 'scheduleVersion' => null, 'scheduleVersions' => [],
            'scheduleIsOfficial' => false, 'assignmentVersion' => null,
            'scope' => $scope, 'selectedClassroomId' => $classroomId,
            'selectedGradeLevelId' => $gradeLevelId, 'classrooms' => [],
            'gradeLevels' => [], 'scheduleGrid' => [], 'days' => [], 'maxSlot' => 0,
            'totalJp' => 0, 'subjectCount' => 0, 'classroomCount' => 0,
            'assignments' => [], 'unitIds' => [], 'isCombinedUnitScope' => false,
        ];
    }

    public function build(
        int $teacherId,
        int $unitId,
        int $periodId,
        string $scope = 'teacher',
        ?int $classroomId = null,
        ?int $gradeLevelId = null
    ): array {
        if (!in_array($scope, self::SCOPES, true)) {
            $scope = 'teacher';
        }

        $db = Database::connect();
        $teacherInfo = $teacherId > 0
            ? $db->table('teachers')->where('id', $teacherId)->where('deleted_at IS NULL')->get()->getRowArray()
            : null;

        $classrooms = $db->table('classrooms c')
            ->select('c.id, c.name, c.code, c.grade_level_id, gl.grade_number, gl.name AS grade_name')
            ->join('grade_levels gl', 'gl.id = c.grade_level_id')
            ->where('c.unit_id', $unitId)
            ->where('c.is_active', 1)
            ->where('c.deleted_at IS NULL')
            ->orderBy('gl.sort_order', 'ASC')
            ->orderBy('c.name', 'ASC')
            ->get()
            ->getResultArray();

        $gradeLevels = $db->table('grade_levels')
            ->select('id, grade_number, code, name')
            ->where('unit_id', $unitId)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getResultArray();

        if ($scope === 'classroom') {
            $validClassroomIds = array_map('intval', array_column($classrooms, 'id'));
            if (!$classroomId || !in_array($classroomId, $validClassroomIds, true)) {
                $classroomId = $validClassroomIds[0] ?? null;
            }
        }

        if ($scope === 'grade') {
            $validGradeIds = array_map('intval', array_column($gradeLevels, 'id'));
            if (!$gradeLevelId || !in_array($gradeLevelId, $validGradeIds, true)) {
                $gradeLevelId = $validGradeIds[0] ?? null;
            }
        }

        $version = $db->table('schedule_versions')
            ->where('unit_id', $unitId)
            ->where('academic_period_id', $periodId)
            ->where('workflow_status !=', 'ARCHIVED')
            ->orderBy('is_active', 'DESC')
            ->orderBy("CASE WHEN workflow_status IN ('APPROVED','LOCKED') THEN 0 ELSE 1 END", 'ASC', false)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();
        $scheduleIsOfficial = $version
            && in_array(strtoupper((string) $version['workflow_status']), ['APPROVED', 'LOCKED'], true);

        $daysBuilder = $db->table('schedule_days sd')
            ->select('sd.*, sd.day_of_week AS day_number')
            ->where('sd.school_unit_id', $unitId)
            ->where('sd.is_school_day', 1);
        if ($version) {
            $daysBuilder
                ->distinct()
                ->join('schedule_day_slots sds', 'sds.day_id = sd.id')
                ->where('sds.schedule_version_id', (int) $version['id']);
        }
        $days = $daysBuilder->orderBy('sd.day_of_week', 'ASC')->get()->getResultArray();

        $maxSlotBuilder = $db->table('schedule_day_slots')->selectMax('slot_number', 'max_slot');
        if ($version) {
            $maxSlotBuilder->where('schedule_version_id', (int) $version['id']);
        } else {
            $maxSlotBuilder->where('schedule_version_id', 0);
        }
        $maxSlotRow = $maxSlotBuilder->get()->getRowArray();
        $maxSlot = max(0, (int) ($maxSlotRow['max_slot'] ?? 0));

        $entries = [];
        if ($version) {
            $teacherOwnerIds = [$teacherId];
            $substitutionService = new TeacherScheduleSubstitutionService();
            if ($scope === 'teacher' && $teacherId > 0) {
                $resolvedTeacherId = $substitutionService->resolveResourceTeacherId($teacherId, $periodId);
                if ($resolvedTeacherId === $teacherId) {
                    $teacherOwnerIds = $substitutionService->teacherIdsSharingResource($teacherId, $periodId);
                }
            }
            $builder = $db->table('schedule_entries se')
                ->select(
                    'se.*, s.name AS subject_name, s.code AS subject_code, ' .
                    'c.name AS classroom_name, c.code AS classroom_code, c.grade_level_id, ' .
                    'gl.grade_number, gl.name AS grade_name, r.name AS room_name, ' .
                    'sd.day_name, sd.day_of_week AS day_number, sds.slot_number, sds.start_time, sds.end_time, ' .
                    't.full_name AS teacher_name, t2.full_name AS second_teacher_name'
                )
                ->join('subjects s', 's.id = se.subject_id', 'left')
                ->join('classrooms c', 'c.id = se.classroom_id')
                ->join('grade_levels gl', 'gl.id = c.grade_level_id')
                ->join('teachers t', 't.id = se.teacher_id', 'left')
                ->join('teachers t2', 't2.id = se.second_teacher_id', 'left')
                ->join('rooms r', 'r.id = se.room_id', 'left')
                ->join('schedule_day_slots sds', 'sds.id = se.day_slot_id')
                ->join('schedule_days sd', 'sd.id = sds.day_id')
                ->where('se.schedule_version_id', (int) $version['id']);

            if ($scope === 'teacher') {
                $builder->groupStart()
                    ->whereIn('se.teacher_id', $teacherOwnerIds)
                    ->orWhereIn('se.second_teacher_id', $teacherOwnerIds)
                    ->groupEnd();
            } elseif ($scope === 'classroom' && $classroomId) {
                $builder->where('se.classroom_id', $classroomId);
            } elseif ($scope === 'grade' && $gradeLevelId) {
                $builder->where('c.grade_level_id', $gradeLevelId);
            }

            $entries = $builder
                // day_number is only a SELECT alias. Ordering by the physical
                // column keeps this query valid on MySQL in every portal scope.
                ->orderBy('sd.day_of_week', 'ASC')
                ->orderBy('sds.slot_number', 'ASC')
                ->orderBy('c.name', 'ASC')
                ->get()
                ->getResultArray();

            if ($scope === 'teacher') {
                foreach ($entries as &$entry) {
                    $entry['is_substitution_assignment'] = false;
                    $ownerIds = [(int) ($entry['teacher_id'] ?? 0), (int) ($entry['second_teacher_id'] ?? 0)];
                    foreach (array_filter($ownerIds) as $ownerId) {
                        if ($ownerId !== $teacherId
                            && $substitutionService->resolveResourceTeacherId($ownerId, $periodId) === $teacherId) {
                            $entry['is_substitution_assignment'] = true;
                            $entry['substitution_owner_name'] = $ownerId === (int) ($entry['teacher_id'] ?? 0)
                                ? ($entry['teacher_name'] ?? '')
                                : ($entry['second_teacher_name'] ?? '');
                            break;
                        }
                    }
                }
                unset($entry);
            }
        }

        $scheduleGrid = [];
        foreach ($entries as $entry) {
            $scheduleGrid[(int) $entry['day_number']][(int) $entry['slot_number']][] = $entry;
        }

        $assignments = [];
        $assignmentVersion = null;
        if ($teacherId > 0) {
            if ($version && !empty($version['assignment_version_id'])) {
                $assignmentVersion = $db->table('assignment_versions')
                    ->where('id', (int) $version['assignment_version_id'])
                    ->get()->getRowArray();
            }
            if (!$assignmentVersion) {
                $assignmentVersion = $db->table('assignment_versions av')
                    ->select('av.*')
                    ->join('teaching_assignments ta', 'ta.assignment_version_id = av.id')
                    ->where('av.academic_period_id', $periodId)
                    ->where('av.workflow_status !=', 'ARCHIVED')
                    ->where('ta.teacher_id', $teacherId)
                    ->where('ta.unit_id', $unitId)
                    ->where('ta.status', 'ACTIVE')
                    ->where('ta.deleted_at IS NULL')
                    ->orderBy('av.is_active', 'DESC')
                    ->orderBy('av.id', 'DESC')
                    ->get()->getRowArray();
            }

            $assignmentBuilder = $db->table('teaching_assignments ta')
                ->select('ta.*, ta.assigned_weekly_hours AS weekly_hours, s.name AS subject_name, c.name AS classroom_name')
                ->join('assignment_versions av', 'av.id = ta.assignment_version_id')
                ->join('subjects s', 's.id = ta.subject_id', 'left')
                ->join('classrooms c', 'c.id = ta.classroom_id', 'left')
                ->where('ta.teacher_id', $teacherId)
                ->where('ta.unit_id', $unitId)
                ->where('av.academic_period_id', $periodId)
                ->where('ta.status', 'ACTIVE')
                ->where('ta.deleted_at IS NULL')
                ->orderBy('c.name', 'ASC')
                ->orderBy('s.name', 'ASC');
            if ($assignmentVersion) {
                $assignmentBuilder->where('ta.assignment_version_id', (int) $assignmentVersion['id']);
            }
            $assignments = $assignmentBuilder->get()->getResultArray();
        }

        $assignmentTotalJp = array_sum(array_map(
            static fn (array $assignment): float => (float) ($assignment['assigned_weekly_hours'] ?? 0),
            $assignments
        ));
        $assignmentSubjectCount = count(array_unique(array_filter(array_column($assignments, 'subject_id'))));
        $assignmentClassroomCount = count(array_unique(array_filter(array_column($assignments, 'classroom_id'))));

        return [
            'teacherInfo'          => $teacherInfo,
            'scheduleVersion'      => $version,
            'scheduleIsOfficial'   => $scheduleIsOfficial,
            'assignmentVersion'    => $assignmentVersion,
            'scope'                => $scope,
            'selectedClassroomId'  => $classroomId,
            'selectedGradeLevelId' => $gradeLevelId,
            'classrooms'           => $classrooms,
            'gradeLevels'          => $gradeLevels,
            'scheduleGrid'         => $scheduleGrid,
            'days'                 => $days,
            'maxSlot'              => $maxSlot,
            'totalJp'              => $entries !== [] ? count($entries) : $assignmentTotalJp,
            'subjectCount'         => $entries !== [] ? count(array_unique(array_filter(array_column($entries, 'subject_id')))) : $assignmentSubjectCount,
            'classroomCount'       => $entries !== [] ? count(array_unique(array_filter(array_column($entries, 'classroom_id')))) : $assignmentClassroomCount,
            'assignments'          => $assignments,
        ];
    }
}
