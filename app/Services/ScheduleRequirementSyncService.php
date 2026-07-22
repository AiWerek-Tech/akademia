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

        $assignmentVersionId = (int)$version['assignment_version_id'];

        // Fetch teaching assignments from assignment version
        $assignments = $this->db->table('teaching_assignments')
            ->where('assignment_version_id', $assignmentVersionId)
            ->where('status', 'ACTIVE')
            ->get()->getResultArray();

        $syncedCount = 0;
        $createdCount = 0;
        $updatedCount = 0;

        foreach ($assignments as $assignment) {
            $assignmentId = (int)$assignment['id'];
            $structureId  = (int)$assignment['curriculum_structure_id'];
            $teacherId    = (int)$assignment['teacher_id'];
            $assignedHours = (float)$assignment['assigned_weekly_hours'];

            // Fetch curriculum structure details
            $structure = $this->db->table('curriculum_structures')
                ->where('id', $structureId)
                ->get()->getRowArray();

            if (!$structure) {
                continue;
            }

            $classroomId = (int)($structure['classroom_id'] ?? 0);
            $subjectId   = (int)($structure['subject_id'] ?? 0);
            $preferredRoomId = !empty($structure['preferred_room_id']) ? (int)$structure['preferred_room_id'] : null;
            $requiredRoomType = $structure['required_room_type'] ?? null;

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

        return [
            'status'        => 'success',
            'synced_count'  => $syncedCount,
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
        ];
    }
}
