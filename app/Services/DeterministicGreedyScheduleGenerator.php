<?php

namespace App\Services;

use App\Models\ScheduleCandidateEntryModel;
use App\Models\ScheduleEntryModel;
use App\Models\ScheduleGenerationCandidateModel;
use App\Models\ScheduleGenerationRunModel;
use App\Models\ScheduleRequirementModel;
use App\Models\ScheduleVersionModel;
use Config\Database;

class DeterministicGreedyScheduleGenerator
{
    private ScheduleGenerationRunModel $runModel;
    private ScheduleGenerationCandidateModel $candidateModel;
    private ScheduleCandidateEntryModel $candidateEntryModel;
    private ScheduleRequirementModel $requirementModel;
    private ScheduleEntryModel $entryModel;
    private ScheduleVersionModel $versionModel;
    private TeacherAvailabilityService $teacherAvailabilityService;
    private RoomAvailabilityService $roomAvailabilityService;
    private ScheduleScoringService $scoringService;
    private $db;

    public function __construct()
    {
        $this->runModel                   = new ScheduleGenerationRunModel();
        $this->candidateModel             = new ScheduleGenerationCandidateModel();
        $this->candidateEntryModel        = new ScheduleCandidateEntryModel();
        $this->requirementModel           = new ScheduleRequirementModel();
        $this->entryModel                 = new ScheduleEntryModel();
        $this->versionModel               = new ScheduleVersionModel();
        $this->teacherAvailabilityService = new TeacherAvailabilityService();
        $this->roomAvailabilityService    = new RoomAvailabilityService();
        $this->scoringService             = new ScheduleScoringService();
        $this->db                         = Database::connect();
    }

    public function generate(int $scheduleVersionId, ?int $createdBy = null): array
    {
        $startTime = microtime(true);

        $version = $this->versionModel->find($scheduleVersionId);
        if (!$version) {
            throw new \RuntimeException("Schedule version ID {$scheduleVersionId} not found.");
        }

        $academicPeriodId = (int)$version['academic_period_id'];

        // Create generation run record
        $runData = [
            'uuid'                 => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
            'schedule_version_id'  => $scheduleVersionId,
            'generator_strategy'   => 'DETERMINISTIC_GREEDY',
            'status'               => 'RUNNING',
            'started_at'           => date('Y-m-d H:i:s'),
            'total_requirements'   => 0,
            'placed_requirements'  => 0,
            'unplaced_requirements'=> 0,
            'created_by'           => $createdBy,
        ];
        $this->runModel->insert($runData);
        $runId = (int)$this->runModel->insertID();

        // Load day slots for version
        $daySlots = $this->db->table('schedule_day_slots sds')
            ->select('sds.*, sd.day_of_week, sd.day_name')
            ->join('schedule_days sd', 'sd.id = sds.day_id')
            ->where('sds.schedule_version_id', $scheduleVersionId)
            ->where('sds.slot_type', 'LESSON')
            ->orderBy('sd.day_of_week', 'ASC')
            ->orderBy('sds.slot_number', 'ASC')
            ->get()->getResultArray();

        // Load requirements
        $requirements = $this->requirementModel
            ->where('schedule_version_id', $scheduleVersionId)
            ->orderBy('is_team_teaching', 'DESC')
            ->orderBy('required_weekly_hours', 'DESC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $totalRequirements = count($requirements);

        // Tracking placed state for candidate
        $placedEntries  = [];
        $teacherOccupied = []; // key: "slotId_teacherId"
        $classOccupied   = []; // key: "slotId_classId"
        $roomOccupied    = []; // key: "slotId_roomId"

        $placedReqCount   = 0;
        $unplacedReqCount = 0;

        foreach ($requirements as $req) {
            $reqId         = (int)$req['id'];
            $classId       = (int)$req['classroom_id'];
            $subjectId     = (int)$req['subject_id'];
            $teacherId     = (int)$req['teacher_id'];
            $secondTeacherId = $req['second_teacher_id'] ? (int)$req['second_teacher_id'] : null;
            $prefRoomId    = $req['preferred_room_id'] ? (int)$req['preferred_room_id'] : null;
            $requiredHours = (int)ceil((float)$req['required_weekly_hours']);

            $placedForThisReq = 0;

            foreach ($daySlots as $slot) {
                if ($placedForThisReq >= $requiredHours) {
                    break;
                }

                $slotId    = (int)$slot['id'];
                $dayOfWeek = (int)$slot['day_of_week'];
                $slotNum   = (int)$slot['slot_number'];

                // 1. Check classroom occupation
                if (isset($classOccupied["{$slotId}_{$classId}"])) {
                    continue;
                }

                // 2. Check teacher occupation & availability
                if (isset($teacherOccupied["{$slotId}_{$teacherId}"])) {
                    continue;
                }
                if (!$this->teacherAvailabilityService->isTeacherAvailable($teacherId, $academicPeriodId, $dayOfWeek, $slotNum)) {
                    continue;
                }

                // Check cross-unit teacher occupancy
                $crossUnit = $this->teacherAvailabilityService->getCrossUnitTeacherScheduleOccupancy($teacherId, $academicPeriodId, $dayOfWeek, $slotNum, $scheduleVersionId);
                if ($crossUnit !== []) {
                    continue;
                }

                // 3. Check second teacher if team teaching
                if ($secondTeacherId) {
                    if (isset($teacherOccupied["{$slotId}_{$secondTeacherId}"])) {
                        continue;
                    }
                    if (!$this->teacherAvailabilityService->isTeacherAvailable($secondTeacherId, $academicPeriodId, $dayOfWeek, $slotNum)) {
                        continue;
                    }
                    $crossUnit2 = $this->teacherAvailabilityService->getCrossUnitTeacherScheduleOccupancy($secondTeacherId, $academicPeriodId, $dayOfWeek, $slotNum, $scheduleVersionId);
                    if ($crossUnit2 !== []) {
                        continue;
                    }
                }

                // 4. Determine Room
                $roomId = $prefRoomId;
                if ($roomId && isset($roomOccupied["{$slotId}_{$roomId}"])) {
                    $roomId = null; // Fallback if preferred room occupied
                }

                // Place entry candidate
                $placedEntries[] = [
                    'day_slot_id'             => $slotId,
                    'schedule_requirement_id' => $reqId,
                    'classroom_id'            => $classId,
                    'teacher_id'              => $teacherId,
                    'second_teacher_id'       => $secondTeacherId,
                    'subject_id'              => $subjectId,
                    'room_id'                 => $roomId,
                    'score_contribution'      => $roomId ? 10 : 0,
                    'created_at'              => date('Y-m-d H:i:s'),
                ];

                $classOccupied["{$slotId}_{$classId}"]   = true;
                $teacherOccupied["{$slotId}_{$teacherId}"] = true;
                if ($secondTeacherId) {
                    $teacherOccupied["{$slotId}_{$secondTeacherId}"] = true;
                }
                if ($roomId) {
                    $roomOccupied["{$slotId}_{$roomId}"] = true;
                }

                $placedForThisReq++;
            }

            if ($placedForThisReq >= $requiredHours) {
                $placedReqCount++;
            } else {
                $unplacedReqCount++;
            }
        }

        // Save Candidate 1
        $candidateData = [
            'uuid'              => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
            'generation_run_id' => $runId,
            'candidate_number'  => 1,
            'score'             => (float)count($placedEntries) * 10,
            'hard_score'        => 0,
            'soft_score'        => count($placedEntries) * 10,
            'placed_count'      => count($placedEntries),
            'unplaced_count'    => $unplacedReqCount,
            'is_applied'        => 0,
        ];
        $this->candidateModel->insert($candidateData);
        $candidateId = (int)$this->candidateModel->insertID();

        foreach ($placedEntries as $entry) {
            $entry['candidate_id'] = $candidateId;
            $this->candidateEntryModel->insert($entry);
        }

        $endTime       = microtime(true);
        $executionMs   = (int)round(($endTime - $startTime) * 1000);

        // Update Run Status
        $this->runModel->update($runId, [
            'status'               => 'COMPLETED',
            'completed_at'         => date('Y-m-d H:i:s'),
            'execution_time_ms'    => $executionMs,
            'score'                => (float)count($placedEntries) * 10,
            'total_requirements'   => $totalRequirements,
            'placed_requirements'  => $placedReqCount,
            'unplaced_requirements'=> $unplacedReqCount,
            'log_output'           => "Deterministic greedy generation finished in {$executionMs} ms. Placed " . count($placedEntries) . " slots.",
        ]);

        return [
            'status'               => 'success',
            'run_id'               => $runId,
            'candidate_id'         => $candidateId,
            'execution_time_ms'    => $executionMs,
            'total_requirements'   => $totalRequirements,
            'placed_requirements'  => $placedReqCount,
            'unplaced_requirements'=> $unplacedReqCount,
            'total_placed_slots'   => count($placedEntries),
        ];
    }

    public function applyCandidate(int $candidateId, int $userId): array
    {
        $candidate = $this->candidateModel->find($candidateId);
        if (!$candidate) {
            throw new \RuntimeException("Candidate ID {$candidateId} not found.");
        }

        $run = $this->runModel->find($candidate['generation_run_id']);
        if (!$run) {
            throw new \RuntimeException("Generation run ID {$candidate['generation_run_id']} not found.");
        }

        $scheduleVersionId = (int)$run['schedule_version_id'];

        $entries = $this->candidateEntryModel->where('candidate_id', $candidateId)->findAll();

        $this->db->transStart();

        // Remove existing unlocked entries for this schedule version
        $this->entryModel->where('schedule_version_id', $scheduleVersionId)->where('is_locked', 0)->delete();

        $now = date('Y-m-d H:i:s');
        $insertedCount = 0;

        foreach ($entries as $e) {
            $data = [
                'uuid'                    => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
                'schedule_version_id'     => $scheduleVersionId,
                'day_slot_id'             => $e['day_slot_id'],
                'schedule_requirement_id' => $e['schedule_requirement_id'],
                'classroom_id'            => $e['classroom_id'],
                'teacher_id'              => $e['teacher_id'],
                'second_teacher_id'       => $e['second_teacher_id'],
                'subject_id'              => $e['subject_id'],
                'room_id'                 => $e['room_id'],
                'is_locked'               => 0,
                'created_at'              => $now,
                'updated_at'              => $now,
                'created_by'              => $userId,
                'updated_by'              => $userId,
            ];
            $this->entryModel->insert($data);
            $insertedCount++;
        }

        $this->candidateModel->update($candidateId, [
            'is_applied' => 1,
            'applied_at' => $now,
            'applied_by' => $userId,
        ]);

        $this->db->transComplete();

        return [
            'status'         => 'success',
            'applied_entries'=> $insertedCount,
            'candidate_id'   => $candidateId,
            'version_id'     => $scheduleVersionId,
        ];
    }
}
