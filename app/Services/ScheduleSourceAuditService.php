<?php

namespace App\Services;

use Config\Database;

/** Audits the curriculum -> assignment -> requirement -> schedule chain. */
class ScheduleSourceAuditService
{
    public function auditVersion(int $versionId): array
    {
        $db = Database::connect();
        $version = $db->table('schedule_versions')->where('id', $versionId)->get()->getRowArray();
        if (! $version) {
            return ['rows' => [], 'issue_count' => 1];
        }

        $assignmentVersionId = (int) ($version['assignment_version_id'] ?? 0);
        if ($assignmentVersionId === 0) {
            $assignmentVersionId = (int) ($db->table('assignment_versions')
                ->where('academic_period_id', (int) $version['academic_period_id'])
                ->where('unit_id', (int) $version['unit_id'])->where('is_active', 1)
                ->orderBy('id', 'DESC')->get()->getRowArray()['id'] ?? 0);
        }
        $curriculumVersionId = (int) ($version['curriculum_version_id'] ?? 0);
        if ($curriculumVersionId === 0 && $assignmentVersionId > 0) {
            $curriculumVersionId = (int) ($db->table('assignment_versions')->where('id', $assignmentVersionId)
                ->get()->getRowArray()['curriculum_version_id'] ?? 0);
        }

        $classrooms = $db->table('classrooms c')->select('c.id,c.name,c.grade_level_id,gl.grade_number')
            ->join('grade_levels gl', 'gl.id=c.grade_level_id')
            ->where('c.academic_period_id', (int) $version['academic_period_id'])
            ->where('c.unit_id', (int) $version['unit_id'])->where('c.is_active', 1)
            ->where('c.deleted_at IS NULL')->orderBy('gl.grade_number')->get()->getResultArray();
        $slotCount = (int) $db->table('schedule_day_slots')->where('schedule_version_id', $versionId)
            ->where('slot_type', 'LESSON')->countAllResults();

        $rows = [];
        $issueCount = 0;
        foreach ($classrooms as $classroom) {
            $classId = (int) $classroom['id'];
            $gradeId = (int) $classroom['grade_level_id'];
            $structures = $db->table('curriculum_structures cs')->select('cs.*,s.name subject_name,s.code subject_code,s.category subject_category')
                ->join('subjects s', 's.id=cs.subject_id')->where('cs.curriculum_version_id', $curriculumVersionId)
                ->where('cs.unit_id', (int) $version['unit_id'])->where('cs.grade_level_id', $gradeId)
                ->groupStart()->where('cs.classroom_id', null)->orWhere('cs.classroom_id', $classId)->groupEnd()
                ->where('cs.status', 'ACTIVE')->where('cs.deleted_at IS NULL')->where('cs.effective_weekly_hours >', 0)
                ->get()->getResultArray();

            $pendingApprovals = [];
            // SMA XI/XII elective structures only become schedulable after approval.
            if ((int) $classroom['grade_number'] >= 11) {
                $approved = $db->table('elective_offerings eo')->select('eo.subject_id')
                    ->join('elective_periods ep', 'ep.id=eo.elective_period_id')
                    ->join('academic_periods ap', 'ap.academic_year_id=ep.academic_year_id')
                    ->where('ap.id', (int) $version['academic_period_id'])
                    ->where('ep.target_grade', (int) $classroom['grade_number'])
                    ->where('eo.is_approved', 1)->get()->getResultArray();
                $approvedIds = array_map('intval', array_column($approved, 'subject_id'));
                foreach ($structures as $structure) {
                    if (strtoupper((string) ($structure['subject_category'] ?? $structure['category'])) === 'PILIHAN'
                        && ! in_array((int) $structure['subject_id'], $approvedIds, true)) {
                        $pendingApprovals[] = ($structure['subject_code'] ?: $structure['subject_name']) . ' (' . (float) $structure['effective_weekly_hours'] . ' JP)';
                    }
                }
                $structures = array_values(array_filter($structures, static fn(array $s): bool =>
                    strtoupper((string) ($s['subject_category'] ?? $s['category'])) !== 'PILIHAN' || in_array((int) $s['subject_id'], $approvedIds, true)));
            }

            $assignments = $db->table('teaching_assignments ta')->select('ta.*,s.name subject_name,s.code subject_code')
                ->join('subjects s', 's.id=ta.subject_id')->where('ta.assignment_version_id', $assignmentVersionId)
                ->where('ta.classroom_id', $classId)->where('ta.status', 'ACTIVE')->where('ta.deleted_at IS NULL')
                ->get()->getResultArray();
            $schedulableSubjectIds = array_map('intval', array_column($structures, 'subject_id'));
            $assignments = array_values(array_filter($assignments, static fn(array $assignment): bool =>
                in_array((int) $assignment['subject_id'], $schedulableSubjectIds, true)));
            $requirements = $db->table('schedule_requirements sr')->select('sr.*,s.name subject_name,s.code subject_code')
                ->join('subjects s', 's.id=sr.subject_id')->where('sr.schedule_version_id', $versionId)
                ->where('sr.classroom_id', $classId)->get()->getResultArray();
            $fixed = (int) $db->table('schedule_fixed_activities')->where('schedule_version_id', $versionId)
                ->where('classroom_id', $classId)->countAllResults();
            $scheduled = (int) $db->table('schedule_entries')->where('schedule_version_id', $versionId)
                ->where('classroom_id', $classId)->where('schedule_requirement_id IS NOT NULL')->countAllResults();

            $assignmentSubjectIds = array_map('intval', array_column($assignments, 'subject_id'));
            $requirementAssignmentIds = array_map('intval', array_column($requirements, 'teaching_assignment_id'));
            $missingAssignments = [];
            foreach ($structures as $structure) {
                if (! in_array((int) $structure['subject_id'], $assignmentSubjectIds, true)) {
                    $missingAssignments[] = ($structure['subject_code'] ?: $structure['subject_name']) . ' (' . (float) $structure['effective_weekly_hours'] . ' JP)';
                }
            }
            $missingRequirements = [];
            foreach ($assignments as $assignment) {
                if (! in_array((int) $assignment['id'], $requirementAssignmentIds, true)) {
                    $missingRequirements[] = ($assignment['subject_code'] ?: $assignment['subject_name']) . ' (' . (float) $assignment['assigned_weekly_hours'] . ' JP)';
                }
            }
            $curriculumHours = array_sum(array_map(static fn(array $s): float => (float) $s['effective_weekly_hours'], $structures));
            $assignmentHours = array_sum(array_map(static fn(array $a): float => (float) $a['assigned_weekly_hours'], $assignments));
            $requirementHours = array_sum(array_map(static fn(array $r): float => (float) $r['required_weekly_hours'], $requirements));
            $capacity = max(0, $slotCount - $fixed);
            $balanced = $missingAssignments === [] && $missingRequirements === []
                && abs($curriculumHours - $assignmentHours) < .01
                && abs($assignmentHours - $requirementHours) < .01
                && abs($requirementHours - $scheduled) < .01
                && abs($curriculumHours - $capacity) < .01;
            if (! $balanced) $issueCount++;
            $rows[] = compact('classId', 'curriculumHours', 'assignmentHours', 'requirementHours', 'scheduled', 'capacity', 'missingAssignments', 'missingRequirements', 'pendingApprovals', 'balanced')
                + ['classroom_name' => $classroom['name']];
        }

        return ['rows' => $rows, 'issue_count' => $issueCount, 'curriculum_version_id' => $curriculumVersionId, 'assignment_version_id' => $assignmentVersionId];
    }
}
