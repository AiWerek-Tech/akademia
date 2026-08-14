<?php

namespace App\Services;

use App\Models\ScheduleRequirementModel;
use App\Models\ScheduleVersionModel;
use Config\Database;

class ScheduleRequirementSyncService
{
    private ScheduleRequirementModel $requirementModel;
    private ScheduleVersionModel $versionModel;
    private $db;

    public function __construct()
    {
        $this->requirementModel = new ScheduleRequirementModel();
        $this->versionModel     = new ScheduleVersionModel();
        $this->db               = Database::connect();
    }

    public function syncFromAssignments(int $scheduleVersionId): array
    {
        $version = $this->versionModel->find($scheduleVersionId);
        if (!$version) {
            throw new \RuntimeException("Schedule version ID {$scheduleVersionId} not found.");
        }

        $assignmentVersionId = isset($version['assignment_version_id']) && (int)$version['assignment_version_id'] > 0 ? (int)$version['assignment_version_id'] : null;
        $unitId = isset($version['unit_id']) && (int)$version['unit_id'] > 0 ? (int)$version['unit_id'] : null;
        $periodId = (int)$version['academic_period_id'];

        // Fetch teaching assignments from assignment version(s)
        $assignmentBuilder = $this->db->table('teaching_assignments ta')
            ->select('ta.*')
            ->join('assignment_versions av', 'av.id = ta.assignment_version_id')
            ->where('av.academic_period_id', $periodId)
            ->where('ta.status', 'ACTIVE')
            ->where('ta.deleted_at IS NULL');

        if ($assignmentVersionId !== null) {
            $assignmentBuilder->where('ta.assignment_version_id', $assignmentVersionId);
        } else {
            // For Combined Schedule: Pull from ALL active assignment versions in this period
            $assignmentBuilder->where('av.is_active', 1);
        }

        if ($unitId !== null) {
            $assignmentBuilder->where('ta.unit_id', $unitId);
        }
        $assignments = $assignmentBuilder->get()->getResultArray();

        // Validate before any delete/update so team members are never silently
        // flattened into a single-teacher requirement.
        (new TeamTeachingPolicyService())->assertAssignmentsSupported($assignments);

        $syncedCount = 0;
        $createdCount = 0;
        $updatedCount = 0;
        foreach ($assignments as $assignment) {
            $assignmentId = (int)$assignment['id'];
            $teacherId    = (int)$assignment['teacher_id'];
            $classroomId  = (int)$assignment['classroom_id'];
            $subjectId    = (int)$assignment['subject_id'];

            $classroom = $this->db->table('classrooms c')
                ->select('c.unit_id, gl.id as grade_level_id, gl.grade_number, su.level as unit_level')
                ->join('grade_levels gl', 'gl.id = c.grade_level_id')
                ->join('school_units su', 'su.id = c.unit_id')
                ->where('c.id', $classroomId)->get()->getRowArray();

            $gradeNumber = (int)($classroom['grade_number'] ?? 0);
            $gradeLevelId = (int)($classroom['grade_level_id'] ?? 0);
            $unitLevel   = strtoupper((string)($classroom['unit_level'] ?? ''));

            // Find active non-deleted curriculum structure
            $structure = $this->db->table('curriculum_structures')
                ->where('id', $assignment['curriculum_structure_id'])
                ->where('status', 'ACTIVE')
                ->where('deleted_at IS NULL')
                ->get()->getRowArray();

            if (!$structure && $gradeLevelId > 0) {
                $structure = $this->db->table('curriculum_structures')
                    ->where('grade_level_id', $gradeLevelId)
                    ->where('subject_id', $subjectId)
                    ->where('status', 'ACTIVE')
                    ->where('deleted_at IS NULL')
                    ->get()->getRowArray();

                if ($structure) {
                    $this->db->table('teaching_assignments')
                        ->where('id', $assignmentId)
                        ->update(['curriculum_structure_id' => $structure['id']]);
                }
            }

            if (!$structure || (float)($structure['effective_weekly_hours'] ?? 0) <= 0) {
                $existing = $this->requirementModel
                    ->where('schedule_version_id', $scheduleVersionId)
                    ->where('teaching_assignment_id', $assignmentId)
                    ->first();
                if ($existing) {
                    $this->deleteRequirementWithCandidates((int) $existing['id']);
                }
                continue;
            }

            $assignedHours = (float)$structure['effective_weekly_hours'];
            if (abs((float)$assignment['assigned_weekly_hours'] - $assignedHours) > 0.001) {
                $this->db->table('teaching_assignments')
                    ->where('id', $assignmentId)
                    ->update(['assigned_weekly_hours' => $assignedHours]);
            }

            $preferredRoomId = ! empty($structure['preferred_room_id'] ?? null) ? (int) $structure['preferred_room_id'] : null;
            $requiredRoomType = null;
            if (! empty($structure['required_room_type_id'] ?? null)) {
                $roomType = $this->db->table('room_types')->select('code')
                    ->where('id', $structure['required_room_type_id'])->get()->getRowArray();
                $requiredRoomType = $roomType['code'] ?? null;
            }

            $subject = $this->db->table('subjects')->select('category, code, name')
                ->where('id', $subjectId)->get()->getRowArray();

            // Verification check for Elective Subjects (Kategori PILIHAN) in SMA Phase F (Kelas XI & XII / Grade 11 & 12):
            // Elective subjects MUST be verified & approved in "Rancangan Mapel Pilihan" (elective_offerings.is_approved = 1)
            if (($subject['category'] ?? '') === 'PILIHAN' && $gradeNumber >= 11) {
                $approvedOffering = $this->db->table('elective_offerings eo')
                    ->join('elective_periods ep', 'ep.id = eo.elective_period_id')
                    ->join('academic_periods ap', 'ap.academic_year_id = ep.academic_year_id')
                    ->where('ap.id', $periodId)
                    ->where('ep.target_grade', $gradeNumber)
                    ->where('eo.subject_id', $subjectId)
                    ->where('eo.is_approved', 1)
                    ->get()->getRowArray();

                if (! $approvedOffering) {
                    $existing = $this->requirementModel
                        ->where('schedule_version_id', $scheduleVersionId)
                        ->where('teaching_assignment_id', $assignmentId)
                        ->first();
                    if ($existing) {
                        $this->deleteRequirementWithCandidates((int) $existing['id']);
                    }
                    continue;
                }
            }

            $existing = $this->requirementModel
                ->where('schedule_version_id', $scheduleVersionId)
                ->where('teaching_assignment_id', $assignmentId)
                ->first();

            $data = [
                'schedule_version_id'          => $scheduleVersionId,
                'teaching_assignment_group_id' => null,
                'teaching_assignment_id'       => $assignmentId,
                'classroom_id'                 => $classroomId,
                'subject_id'                   => $subjectId,
                'teacher_id'                   => $teacherId,
                'second_teacher_id'            => null,
                'required_weekly_hours'        => $assignedHours,
                'consecutive_slots_required'   => 1,
                'preferred_room_id'            => $preferredRoomId,
                'required_room_type'           => $requiredRoomType,
                'is_team_teaching'             => 0,
            ];

            if ($existing) {
                $this->requirementModel->update($existing['id'], $data);
                $updatedCount++;
            } else {
                $data['uuid'] = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
                $this->requirementModel->insert($data);
                $createdCount++;
            }
            $syncedCount++;
        }

        $activeAssignmentIds = array_map(static fn (array $row): int => (int) $row['id'], $assignments);
        $stale = $this->db->table('schedule_requirements')
            ->select('id, teaching_assignment_id')
            ->where('schedule_version_id', $scheduleVersionId)
            ->where('teaching_assignment_id IS NOT NULL')
            ->get()->getResultArray();
        foreach ($stale as $row) {
            if (! in_array((int) $row['teaching_assignment_id'], $activeAssignmentIds, true)) {
                $this->deleteRequirementWithCandidates((int) $row['id']);
            }
        }

        return [
            'status'        => 'success',
            'synced_count'  => $syncedCount,
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
        ];
    }

    private function deleteRequirementWithCandidates(int $requirementId): void
    {
        if ($requirementId <= 0) return;

        $entriesToDelete = $this->db->table('schedule_entries')
            ->select('id')
            ->where('schedule_requirement_id', $requirementId)
            ->where('is_locked', 0)
            ->get()->getResultArray();

        $entryIds = array_map('intval', array_column($entriesToDelete, 'id'));

        if (!empty($entryIds)) {
            $this->db->table('schedule_entries')
                ->whereIn('id', $entryIds)
                ->delete();
        }

        $this->db->table('schedule_candidate_entries')->where('schedule_requirement_id', $requirementId)->delete();
        $this->db->table('schedule_entries')->where('schedule_requirement_id', $requirementId)->update(['schedule_requirement_id' => null]);
        $this->requirementModel->delete($requirementId);
    }
}
