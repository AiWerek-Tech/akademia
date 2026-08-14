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
    private TeacherScheduleSubstitutionService $substitutionService;
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
        $this->substitutionService         = new TeacherScheduleSubstitutionService();
        $this->roomAvailabilityService    = new RoomAvailabilityService();
        $this->scoringService             = new ScheduleScoringService();
        $this->db                         = Database::connect();
    }

    public function generate(int $scheduleVersionId, ?int $createdBy = null, int $strategy = 0): array
    {
        $startTime = microtime(true);

        $version = $this->versionModel->find($scheduleVersionId);
        if (!$version) {
            throw new \RuntimeException("Schedule version ID {$scheduleVersionId} not found.");
        }

        $academicPeriodId = (int)$version['academic_period_id'];
        if ($this->db->table('schedule_day_slots')->where('schedule_version_id', $scheduleVersionId)
            ->where('slot_type', 'LESSON')->countAllResults() === 0) {
            throw new \RuntimeException('Slot pelajaran belum tersedia. Siapkan ulang versi jadwal dari profil perencanaan.');
        }
        if ($this->db->table('schedule_requirements')->where('schedule_version_id', $scheduleVersionId)
            ->countAllResults() === 0) {
            throw new \RuntimeException('Kebutuhan jadwal masih kosong. Lengkapi pembagian tugas guru terlebih dahulu.');
        }
        $teamRequirements = $this->db->table('schedule_requirements')
            ->where('schedule_version_id', $scheduleVersionId)
            ->groupStart()
                ->where('is_team_teaching', 1)
                ->orWhere('teaching_assignment_group_id IS NOT NULL')
                ->orWhere('second_teacher_id IS NOT NULL')
            ->groupEnd()->get()->getResultArray();
        $teamPolicy = new TeamTeachingPolicyService();
        foreach ($teamRequirements as $teamRequirement) {
            $teamPolicy->assertRequirementSupported($teamRequirement);
        }

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
            'configuration_json'   => json_encode([
                'strategy_variant' => $strategy,
                'version_revision' => (int) ($version['revision_number'] ?? 1),
            ], JSON_THROW_ON_ERROR),
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
            ->orderBy('sds.slot_number', 'ASC')
            ->orderBy('sd.day_of_week', 'ASC')
            ->get()->getResultArray();

        // Keep slots grouped by day so a 2 JP block is always two adjacent
        // periods on the same day. A flat scan would silently split blocks.
        $slotsByDay = [];
        $slotDayById = [];
        foreach ($daySlots as $slot) {
            $dayNumber = (int) $slot['day_of_week'];
            $slotsByDay[$dayNumber][] = $slot;
            $slotDayById[(int) $slot['id']] = $dayNumber;
        }
        foreach ($slotsByDay as &$daySlotList) {
            usort($daySlotList, static fn(array $a, array $b): int => (int) $a['slot_number'] <=> (int) $b['slot_number']);
        }
        $roomBuilder = $this->db->table('rooms')
            ->select('id')
            ->where('is_active', 1)
            ->where('deleted_at IS NULL')
            ->orderBy('id', 'ASC');
        if (!empty($version['unit_id'])) {
            $roomBuilder->groupStart()
                ->where('unit_id', (int) $version['unit_id'])
                ->orWhere('shared_between_units', 1)
            ->groupEnd();
        }
        $availableRooms = $roomBuilder->get()->getResultArray();
        $availableRoomIds = array_map(static fn(array $room): int => (int) $room['id'], $availableRooms);

        $breakAfterSlot = $this->resolveIntermissionBreakAfterSlot((int) ($version['unit_id'] ?? 0));
        $subjectRows = $this->db->table('subjects')
            ->select('id, code, name, short_name')
            ->where('deleted_at IS NULL')
            ->get()->getResultArray();
        $subjectsById = [];
        foreach ($subjectRows as $subjectRow) {
            $subjectsById[(int) $subjectRow['id']] = $subjectRow;
        }

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
        $lockedPerRequirement = [];
        $lockedDaysByRequirement = [];
        $teacherClassDaySubjects = [];
        $classSubjectDays = [];
        $lockedEntries = $this->entryModel->where('schedule_version_id', $scheduleVersionId)
            ->where('is_locked', 1)->findAll();
        foreach ($lockedEntries as $locked) {
            $slotId = (int) $locked['day_slot_id'];
            $lockedTeacherResourceId = $this->substitutionService->resolveResourceTeacherId(
                (int) $locked['teacher_id'],
                $academicPeriodId
            );
            $teacherOccupied["{$slotId}_{$lockedTeacherResourceId}"] = true;
            if (! empty($locked['second_teacher_id'])) {
                $lockedSecondResourceId = $this->substitutionService->resolveResourceTeacherId(
                    (int) $locked['second_teacher_id'],
                    $academicPeriodId
                );
                $teacherOccupied["{$slotId}_{$lockedSecondResourceId}"] = true;
            }
            $classOccupied["{$slotId}_" . (int) $locked['classroom_id']] = true;
            if (! empty($locked['room_id'])) {
                $roomOccupied["{$slotId}_" . (int) $locked['room_id']] = true;
            }
            $requirementId = (int) $locked['schedule_requirement_id'];
            if ($requirementId > 0) {
                $lockedPerRequirement[$requirementId] = ($lockedPerRequirement[$requirementId] ?? 0) + 1;
                if (isset($slotDayById[$slotId])) {
                    $lockedDaysByRequirement[$requirementId][] = $slotDayById[$slotId];
                }
            }
            $lockedDay = $slotDayById[$slotId] ?? null;
            $lockedSubjectId = (int) ($locked['subject_id'] ?? 0);
            if ($lockedDay !== null && $lockedSubjectId > 0) {
                $classSubjectDays[(int) $locked['classroom_id']][$lockedSubjectId][$lockedDay] = true;
                $lockedTeacherIds = [$lockedTeacherResourceId];
                if (!empty($locked['second_teacher_id'])) $lockedTeacherIds[] = $lockedSecondResourceId;
                foreach ($lockedTeacherIds as $lockedTeacherId) {
                    $teacherClassDaySubjects[$lockedTeacherId][(int) $locked['classroom_id']][$lockedDay][$lockedSubjectId] = true;
                }
            }
        }

        // Load fixed routine activities (ceremonies, breaks, WORKED, chapel, etc.)
        $fixedActivities = $this->db->table('schedule_fixed_activities')
            ->where('schedule_version_id', $scheduleVersionId)
            ->get()->getResultArray();
        foreach ($fixedActivities as $fa) {
            $slotId = (int)$fa['day_slot_id'];
            $cId    = (int)$fa['classroom_id'];
            if ($slotId > 0 && $cId > 0) {
                $classOccupied["{$slotId}_{$cId}"] = true;
            }
        }

        // Hard feasibility gate: do not generate a partial candidate when a
        // classroom has fewer effective lesson slots than its weekly load.
        // This is a data/capacity problem, not something a scheduling heuristic
        // can safely solve by creating overlaps.
        $fixedCountByClassroom = [];
        foreach ($fixedActivities as $fa) {
            $fixedClassId = (int) ($fa['classroom_id'] ?? 0);
            if ($fixedClassId > 0) $fixedCountByClassroom[$fixedClassId] = ($fixedCountByClassroom[$fixedClassId] ?? 0) + 1;
        }
        $baseLessonCapacity = array_sum(array_map('count', $slotsByDay));
        $requiredByClassroom = [];
        $requiredBlocksByClassroom = [];
        foreach ($requirements as $req) {
            $classId = (int) $req['classroom_id'];
            $hours = (int) ceil((float) $req['required_weekly_hours']);
            $requiredByClassroom[$classId] = ($requiredByClassroom[$classId] ?? 0) + $hours;
            $requiredBlocksByClassroom[$classId] = ($requiredBlocksByClassroom[$classId] ?? 0) + (int) floor($hours / 2);
        }
        $capacityIssues = [];
        $classNames = [];
        if ($requiredByClassroom !== []) {
            $classRows = $this->db->table('classrooms')->select('id,name')->whereIn('id', array_keys($requiredByClassroom))->get()->getResultArray();
            foreach ($classRows as $classRow) $classNames[(int) $classRow['id']] = $classRow['name'];
        }
        foreach ($requiredByClassroom as $classId => $requiredHours) {
            $availableHours = $baseLessonCapacity - ($fixedCountByClassroom[$classId] ?? 0);
            if ($requiredHours > $availableHours) {
                $classLabel = $classNames[$classId] ?? ('Kelas #' . $classId);
                $capacityIssues[] = "{$classLabel}: kebutuhan {$requiredHours} JP, slot efektif {$availableHours} JP, kurang " . ($requiredHours - $availableHours) . ' JP';
            }
            $availableBlocks = 0;
            foreach ($slotsByDay as $daySlotsForClass) {
                $freeRun = 0;
                foreach ($daySlotsForClass as $slot) {
                    $slotKey = (int) $slot['id'];
                    $isBlocked = false;
                    foreach ($fixedActivities as $fixed) {
                        if ((int) ($fixed['classroom_id'] ?? 0) === (int) $classId && (int) $fixed['day_slot_id'] === $slotKey) {
                            $isBlocked = true;
                            break;
                        }
                    }
                    if ($isBlocked) {
                        $availableBlocks += intdiv($freeRun, 2);
                        $freeRun = 0;
                    } else {
                        $freeRun++;
                    }
                }
                $availableBlocks += intdiv($freeRun, 2);
            }
            if (($requiredBlocksByClassroom[$classId] ?? 0) > $availableBlocks) {
                $classLabel = $classNames[$classId] ?? ('Kelas #' . $classId);
                $capacityIssues[] = "{$classLabel}: membutuhkan {$requiredBlocksByClassroom[$classId]} blok 2 JP, tetapi hanya tersedia {$availableBlocks} pasangan slot berurutan";
            }
        }
        if ($capacityIssues !== []) {
            $this->runModel->update($runId, [
                'status' => 'FAILED',
                'completed_at' => date('Y-m-d H:i:s'),
                'log_output' => 'Capacity preflight failed: ' . implode('; ', $capacityIssues),
            ]);
            throw new \RuntimeException('Susun otomatis dihentikan karena kapasitas tidak cukup: ' . implode('; ', $capacityIssues) . '. Tambahkan slot LESSON, kurangi beban mapel, atau pindahkan kegiatan rutin terlebih dahulu.');
        }

        // Schedule tight classrooms first. The old ID order filled a class
        // with easy requirements and left the final requirements without any
        // legal slot, even when total capacity was mathematically sufficient.
        $capacityByClassroom = [];
        $teacherLoadCount = [];
        foreach ($requirements as &$req) {
            $classId = (int) $req['classroom_id'];
            $tId = $this->substitutionService->resolveResourceTeacherId((int) $req['teacher_id'], $academicPeriodId);
            $req['_resource_teacher_id'] = $tId;
            $capacityByClassroom[$classId] = $baseLessonCapacity - ($fixedCountByClassroom[$classId] ?? 0);
            $teacherLoadCount[$tId] = ($teacherLoadCount[$tId] ?? 0) + (float) $req['required_weekly_hours'];
        }
        unset($req);
        usort($requirements, static function (array $a, array $b) use ($capacityByClassroom, $requiredByClassroom, $teacherLoadCount, $strategy): int {
            $aClass = (int) $a['classroom_id'];
            $bClass = (int) $b['classroom_id'];
            $aSlack = ($capacityByClassroom[$aClass] ?? 0) - ($requiredByClassroom[$aClass] ?? 0);
            $bSlack = ($capacityByClassroom[$bClass] ?? 0) - ($requiredByClassroom[$bClass] ?? 0);
            if ($aSlack !== $bSlack) return $aSlack <=> $bSlack;

            // Strategy branch to prioritize busy multi-class teachers
            if ($strategy % 3 === 0) {
                $aTLoad = $teacherLoadCount[(int) ($a['_resource_teacher_id'] ?? $a['teacher_id'])] ?? 0;
                $bTLoad = $teacherLoadCount[(int) ($b['_resource_teacher_id'] ?? $b['teacher_id'])] ?? 0;
                if ($aTLoad !== $bTLoad) return $bTLoad <=> $aTLoad;
            }

            if ($aClass !== $bClass) return $strategy % 2 === 0 ? ($bClass <=> $aClass) : ($aClass <=> $bClass);
            $aHours = (float) $a['required_weekly_hours'];
            $bHours = (float) $b['required_weekly_hours'];
            if ($aHours !== $bHours) return $bHours <=> $aHours;
            return $strategy % 4 < 2
                ? ((int) $a['id'] <=> (int) $b['id'])
                : ((int) $b['id'] <=> (int) $a['id']);
        });

        $placedReqCount   = 0;
        $unplacedReqCount = 0;

        foreach ($requirements as $req) {
            $reqId         = (int)$req['id'];
            $classId       = (int)$req['classroom_id'];
            $subjectId     = (int)$req['subject_id'];
            $teacherId     = (int)$req['teacher_id'];
            $secondTeacherId = $req['second_teacher_id'] ? (int)$req['second_teacher_id'] : null;
            $teacherResourceId = $this->substitutionService->resolveResourceTeacherId($teacherId, $academicPeriodId);
            $secondTeacherResourceId = $secondTeacherId
                ? $this->substitutionService->resolveResourceTeacherId($secondTeacherId, $academicPeriodId)
                : null;
            $prefRoomId    = $req['preferred_room_id'] ? (int)$req['preferred_room_id'] : null;
            $totalRequiredHours = max(0, (int) ceil((float) $req['required_weekly_hours']));
            $requiredHours = max(0, $totalRequiredHours - ($lockedPerRequirement[$reqId] ?? 0));
            $isHeavySubject = $this->isMorningPrioritySubject($subjectsById[$subjectId] ?? []);

            $blocks = $this->buildJpBlocks($requiredHours);
            $placedForThisReq = 0;
            $placedPerDay = [];
            $usedDays = array_values(array_unique(array_map('intval', $lockedDaysByRequirement[$reqId] ?? [])));
            $schoolDayCount = max(1, count($slotsByDay));
            $maxPerDay = max(1, max($blocks ?: [1]), (int) ceil(max(1, $totalRequiredHours) / min($schoolDayCount, max(1, $totalRequiredHours))));

            // Use an index loop because a 3-JP preference may be downgraded to
            // the only permitted fallback (2+1) when no legal 3-slot run exists.
            for ($blockIndex = 0; $blockIndex < count($blocks); $blockIndex++) {
                $blockLength = $blocks[$blockIndex];
                $placedBlock = false;
                // A subject must have at least one free day between meetings for
                // the same classroom. If capacity makes that impossible, leave
                // the requirement unmet and surface it for correction instead of
                // producing a schedule that violates the pedagogical rule.
                $allDayNumbers = array_keys($slotsByDay);
                $subjectDays = array_keys($classSubjectDays[$classId][$subjectId] ?? []);
                $spacedDays = array_values(array_filter($allDayNumbers, static function(int $day) use ($usedDays, $subjectDays): bool {
                    if (in_array($day, $usedDays, true)) return false;
                    foreach ($usedDays as $used) {
                        if (abs($day - $used) < 2) return false;
                    }
                    foreach ($subjectDays as $used) {
                        if (abs($day - (int)$used) < 2) return false;
                    }
                    return true;
                }));

                // Prefer a free day between meetings. When the configured
                // school week cannot provide one (for example, a one-day test
                // calendar), retain the documented capacity fallback.
                $dayNumbers = !empty($spacedDays) ? $spacedDays : array_values(array_filter($allDayNumbers, static fn(int $day): bool => !in_array($day, $usedDays, true)));
                if (empty($dayNumbers)) {
                    $dayNumbers = $allDayNumbers;
                }

                usort($dayNumbers, static function (int $a, int $b) use ($placedPerDay, $strategy, $reqId, $blockIndex, $usedDays, $subjectDays): int {
                    $allUsed = array_merge($usedDays, $subjectDays);
                    if ($allUsed !== []) {
                        $aDist = min(array_map(static fn($u): int => abs($a - (int)$u), $allUsed));
                        $bDist = min(array_map(static fn($u): int => abs($b - (int)$u), $allUsed));
                        if ($aDist !== $bDist) return $bDist <=> $aDist;
                    }
                    $loadCompare = ($placedPerDay[$a] ?? 0) <=> ($placedPerDay[$b] ?? 0);
                    if ($loadCompare !== 0) return $loadCompare;
                    $aRank = (($a * 17) + ($strategy * 11) + ($reqId * 3) + $blockIndex) % 97;
                    $bRank = (($b * 17) + ($strategy * 11) + ($reqId * 3) + $blockIndex) % 97;
                    return $aRank <=> $bRank;
                });
                foreach ($dayNumbers as $dayOfWeek) {
                    if (($placedPerDay[$dayOfWeek] ?? 0) + $blockLength > $maxPerDay) continue;
                    $primaryDaySubjects = $teacherClassDaySubjects[$teacherResourceId][$classId][$dayOfWeek] ?? [];
                    if ($primaryDaySubjects !== [] && !isset($primaryDaySubjects[$subjectId])) continue;
                    if ($secondTeacherId) {
                        $secondaryDaySubjects = $teacherClassDaySubjects[$secondTeacherResourceId][$classId][$dayOfWeek] ?? [];
                        if ($secondaryDaySubjects !== [] && !isset($secondaryDaySubjects[$subjectId])) continue;
                    }
                    $dayList = $slotsByDay[$dayOfWeek];
                    $dayCount = count($dayList);
                    $startIndexes = range(0, max(0, $dayCount - $blockLength));
                    $pivot = count($startIndexes) > 0 ? (($reqId * 7 + $blockIndex * 3 + $dayOfWeek + ($strategy * 5)) % count($startIndexes)) : 0;
                    usort($startIndexes, function (int $a, int $b) use ($dayList, $blockLength, $pivot, $isHeavySubject, $breakAfterSlot): int {
                        $aSlots = array_slice($dayList, $a, $blockLength);
                        $bSlots = array_slice($dayList, $b, $blockLength);
                        $aRank = $this->blockTimePreferenceRank($aSlots, $isHeavySubject, $breakAfterSlot);
                        $bRank = $this->blockTimePreferenceRank($bSlots, $isHeavySubject, $breakAfterSlot);
                        if ($aRank !== $bRank) return $aRank <=> $bRank;

                        return abs($a - $pivot) <=> abs($b - $pivot);
                    });
                    foreach ($startIndexes as $start) {
                        $block = array_slice($dayList, $start, $blockLength);
                        if ($this->blockCrossesIntermission($block, $breakAfterSlot)) {
                            continue;
                        }
                        $roomIds = [];
                        $valid = true;
                        foreach ($block as $slot) {
                            $slotId = (int) $slot['id'];
                            $slotNum = (int) $slot['slot_number'];
                            if (isset($classOccupied["{$slotId}_{$classId}"]) || isset($teacherOccupied["{$slotId}_{$teacherResourceId}"])) {
                                $valid = false; break;
                            }
                            if (!$this->teacherAvailabilityService->isTeacherAvailable($teacherId, $academicPeriodId, $dayOfWeek, $slotNum)
                                || $this->teacherAvailabilityService->getCrossUnitTeacherScheduleOccupancy($teacherId, $academicPeriodId, $dayOfWeek, $slotNum, $scheduleVersionId) !== []) {
                                $valid = false; break;
                            }
                            if ($secondTeacherId && (isset($teacherOccupied["{$slotId}_{$secondTeacherResourceId}"])
                                || !$this->teacherAvailabilityService->isTeacherAvailable($secondTeacherId, $academicPeriodId, $dayOfWeek, $slotNum)
                                || $this->teacherAvailabilityService->getCrossUnitTeacherScheduleOccupancy($secondTeacherId, $academicPeriodId, $dayOfWeek, $slotNum, $scheduleVersionId) !== [])) {
                                $valid = false; break;
                            }
                            $roomCandidates = ($prefRoomId && in_array($prefRoomId, $availableRoomIds, true))
                                ? array_values(array_unique(array_merge([$prefRoomId], $availableRoomIds)))
                                : $availableRoomIds;
                            $selectedRoomId = null;
                            foreach ($roomCandidates as $candidateRoomId) {
                                if (!isset($roomOccupied["{$slotId}_{$candidateRoomId}"])
                                    && $this->roomAvailabilityService->isRoomAvailable((int) $candidateRoomId, $academicPeriodId, $dayOfWeek, $slotNum)) {
                                    $selectedRoomId = (int) $candidateRoomId;
                                    break;
                                }
                            }
                            if ($availableRoomIds !== [] && $selectedRoomId === null) {
                                $valid = false; break;
                            }
                            $roomIds[$slotId] = $selectedRoomId;
                        }
                        if (!$valid) continue;

                        foreach ($block as $slot) {
                            $slotId = (int) $slot['id'];
                            $roomId = $roomIds[$slotId] ?? null;
                            $placedEntries[] = [
                                'day_slot_id' => $slotId, 'schedule_requirement_id' => $reqId,
                                'classroom_id' => $classId, 'teacher_id' => $teacherId,
                                'second_teacher_id' => $secondTeacherId, 'subject_id' => $subjectId,
                                'room_id' => $roomId, 'score_contribution' => $roomId ? 10 : 0,
                                'created_at' => date('Y-m-d H:i:s'),
                            ];
                            $classOccupied["{$slotId}_{$classId}"] = true;
                            $teacherOccupied["{$slotId}_{$teacherResourceId}"] = true;
                            if ($secondTeacherId) $teacherOccupied["{$slotId}_{$secondTeacherResourceId}"] = true;
                            if ($roomId) $roomOccupied["{$slotId}_{$roomId}"] = true;
                        }
                        $placedForThisReq += $blockLength;
                        $placedPerDay[$dayOfWeek] = ($placedPerDay[$dayOfWeek] ?? 0) + $blockLength;
                        $usedDays[] = $dayOfWeek;
                        $teacherClassDaySubjects[$teacherResourceId][$classId][$dayOfWeek][$subjectId] = true;
                        if ($secondTeacherId) $teacherClassDaySubjects[$secondTeacherResourceId][$classId][$dayOfWeek][$subjectId] = true;
                        $classSubjectDays[$classId][$subjectId][$dayOfWeek] = true;
                        $placedBlock = true;
                        break 2;
                    }
                }
                if (!$placedBlock && $blockLength === 3) {
                    // A 3-JP subject prefers one uninterrupted session. Only
                    // when that is impossible may it become a 2-JP block plus
                    // one separate JP. The 2-JP part remains indivisible.
                    array_splice($blocks, $blockIndex + 1, 0, [2, 1]);
                }
            }

            if ($placedForThisReq + ($lockedPerRequirement[$reqId] ?? 0) >= $totalRequiredHours) {
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

    /**
     * Split weekly JP into pedagogically useful blocks. Two JP is always an
     * indivisible block. Three JP is attempted as one block and may be changed
     * to 2+1 by the placement loop only when a legal 3-slot run is unavailable.
     * Examples: 1 => [1], 2 => [2], 3 => [3], 4 => [2,2], 5 => [2,2,1].
     */
    private function buildJpBlocks(int $hours): array
    {
        if ($hours === 3) {
            return [3];
        }

        $blocks = [];
        while ($hours >= 2) {
            $blocks[] = 2;
            $hours -= 2;
        }
        if ($hours === 1) $blocks[] = 1;
        return $blocks;
    }

    private function resolveIntermissionBreakAfterSlot(int $unitId): ?int
    {
        $builder = $this->db->table('school_routine_activities')
            ->select('placement_sequence')
            ->where('is_active', 1)
            ->where('deleted_at IS NULL')
            ->where('placement_zone', 'INTERMISSION_BREAK')
            ->where('is_locked_slot', 1)
            ->orderBy('unit_id IS NULL', 'ASC', false)
            ->orderBy('placement_sequence', 'ASC');
        if ($unitId > 0) {
            $builder->groupStart()
                ->where('unit_id', $unitId)
                ->orWhere('unit_id IS NULL')
                ->groupEnd();
        }

        $row = $builder->get()->getRowArray();
        if (!$row || $row['placement_sequence'] === null) {
            return null;
        }

        return max(1, (int) $row['placement_sequence']);
    }

    private function blockCrossesIntermission(array $slots, ?int $breakAfterSlot): bool
    {
        if ($breakAfterSlot === null || count($slots) < 2) {
            return false;
        }

        $slotNumbers = array_map(static fn(array $slot): int => (int) $slot['slot_number'], $slots);
        return min($slotNumbers) <= $breakAfterSlot && max($slotNumbers) > $breakAfterSlot;
    }

    private function isMorningPrioritySubject(array $subject): bool
    {
        $haystack = strtoupper(trim(implode(' ', [
            (string) ($subject['code'] ?? ''),
            (string) ($subject['short_name'] ?? ''),
            (string) ($subject['name'] ?? ''),
        ])));

        foreach (['MTK', 'MATEMATIKA', 'FISIKA', 'KIMIA', 'BIOLOGI', 'BIO', 'IPA', 'EKONOMI', 'EKON'] as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function blockTimePreferenceRank(array $slots, bool $isMorningPrioritySubject, ?int $breakAfterSlot): int
    {
        if ($slots === []) {
            return 999;
        }

        $slotNumbers = array_map(static fn(array $slot): int => (int) $slot['slot_number'], $slots);
        $minSlot = min($slotNumbers);
        $maxSlot = max($slotNumbers);
        if ($this->blockCrossesIntermission($slots, $breakAfterSlot)) {
            return 900 + $minSlot;
        }

        if ($isMorningPrioritySubject) {
            if ($maxSlot <= 5) {
                return $minSlot;
            }
            if ($minSlot >= 6 && $maxSlot <= 7) {
                return 50 + $minSlot;
            }
            return 150 + $minSlot;
        }

        if ($maxSlot <= 7) {
            return 40 + $minSlot;
        }

        return 80 + $minSlot;
    }

    public function applyCandidate(int $candidateId, int $userId, ?int $expectedRevision = null): array
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

        if ((int) ($candidate['is_applied'] ?? 0) === 1) {
            throw new \RuntimeException('Kandidat jadwal ini sudah pernah diterapkan.');
        }
        if ((int) ($candidate['unplaced_count'] ?? 0) > 0 || (int) ($run['unplaced_requirements'] ?? 0) > 0) {
            throw new \RuntimeException('Kandidat belum lengkap dan tidak boleh diterapkan.');
        }

        $entries = $this->candidateEntryModel->where('candidate_id', $candidateId)->findAll();
        if ($entries === []) {
            throw new \RuntimeException('Kandidat tidak memiliki entri jadwal.');
        }

        $runConfiguration = json_decode((string) ($run['configuration_json'] ?? '{}'), true);
        $generatedRevision = (int) ($runConfiguration['version_revision'] ?? 0);

        $this->db->transException(true)->transBegin();

        try {
            $lockedVersion = $this->db->query(
                'SELECT id, unit_id, workflow_status, revision_number FROM schedule_versions WHERE id = ? FOR UPDATE',
                [$scheduleVersionId]
            )->getRowArray();
            if (! $lockedVersion) {
                throw new \RuntimeException('Versi jadwal tidak ditemukan saat kandidat diterapkan.');
            }
            if ((string) $lockedVersion['workflow_status'] !== 'DRAFT') {
                throw new \RuntimeException('Kandidat hanya dapat diterapkan pada jadwal berstatus DRAFT.');
            }
            $actualRevision = (int) $lockedVersion['revision_number'];
            if ($expectedRevision !== null && $actualRevision !== $expectedRevision) {
                throw new \RuntimeException('Jadwal telah berubah di proses lain. Muat ulang halaman sebelum menerapkan kandidat.', 409);
            }
            if ($generatedRevision > 0 && $actualRevision !== $generatedRevision) {
                throw new \RuntimeException('Kandidat sudah kedaluwarsa karena jadwal berubah setelah proses penyusunan. Buat kandidat baru.', 409);
            }

            $invalidEntryBuilder = $this->db->table('schedule_candidate_entries sce')
                ->join('schedule_day_slots sds', 'sds.id = sce.day_slot_id')
                ->join('schedule_requirements sr', 'sr.id = sce.schedule_requirement_id')
                ->join('classrooms c', 'c.id = sce.classroom_id')
                ->where('sce.candidate_id', $candidateId)
                ->groupStart()
                    ->where('sds.schedule_version_id !=', $scheduleVersionId)
                    ->orWhere('sr.schedule_version_id !=', $scheduleVersionId);
            if (! empty($lockedVersion['unit_id'])) {
                $invalidEntryBuilder->orWhere('c.unit_id !=', (int) $lockedVersion['unit_id']);
            }
            $invalidEntryCount = (int) $invalidEntryBuilder->groupEnd()->countAllResults();
            if ($invalidEntryCount > 0) {
                throw new \RuntimeException('Kandidat berisi slot, kebutuhan, atau kelas di luar lingkup versi jadwal.');
            }

            // Find existing unlocked entries to delete safely
            $existingEntries = $this->db->table('schedule_entries')
                ->select('id')
                ->where('schedule_version_id', $scheduleVersionId)
                ->where('is_locked', 0)
                ->get()->getResultArray();

            $existingIds = array_map('intval', array_column($existingEntries, 'id'));
            $now = date('Y-m-d H:i:s');
            if (!empty($existingIds)) {
                // Conflict rows are retained as audit history, but their entry
                // foreign keys must not prevent an explicit candidate replace.
                // The canonical fingerprint and description preserve identity.
                $resolvedHistory = [
                    'is_resolved' => 1,
                    'status'      => 'RESOLVED',
                    'updated_at'  => $now,
                ];
                $this->db->table('schedule_conflicts')
                    ->whereIn('primary_entry_id', $existingIds)
                    ->update($resolvedHistory + ['primary_entry_id' => null]);
                $this->db->table('schedule_conflicts')
                    ->whereIn('conflicting_entry_id', $existingIds)
                    ->update($resolvedHistory + ['conflicting_entry_id' => null]);
                $this->db->table('schedule_entries')
                    ->whereIn('id', $existingIds)
                    ->delete();
            }

            $insertedCount = 0;

            foreach ($entries as $e) {
                $data = [
                    'uuid'                    => UuidService::v4(),
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
                if (! $this->entryModel->insert($data)) {
                    throw new \RuntimeException('Gagal menyimpan salah satu entri kandidat.');
                }
                $insertedCount++;
            }

            // Never publish a candidate that violates the same invariants used by
            // the audit/workflow layer. This also protects against applying an old
            // candidate generated before the latest spacing rules were enabled.
            $audit = (new ScheduleConflictDetectionService())->detectConflicts($scheduleVersionId);
            $blockingConflicts = array_filter($audit['conflicts'] ?? [], static fn(array $conflict): bool =>
                ($conflict['severity'] ?? '') === 'CRITICAL' || ($conflict['conflict_type'] ?? '') === 'UNMET_HOURS'
            );
            if ($blockingConflicts !== []) {
                $blockingCodes = array_values(array_unique(array_map(
                    static fn(array $conflict): string => (string) ($conflict['conflict_type'] ?? 'UNKNOWN'),
                    $blockingConflicts
                )));
                throw new \RuntimeException('Kandidat ditolak karena masih memiliki ' . count($blockingConflicts)
                    . ' konflik/gap jadwal (' . implode(', ', $blockingCodes)
                    . '). Jalankan generate ulang setelah memperbaiki pembagian tugas atau slot.');
            }

            $this->db->table('schedule_generation_candidates')
                ->where('id', $candidateId)
                ->update([
                    'is_applied' => 1,
                    'applied_at' => $now,
                    'applied_by' => $userId,
                ]);

            if (!empty($run['id'])) {
                $this->runModel->update($run['id'], [
                    'placed_requirements'   => $run['total_requirements'],
                    'unplaced_requirements' => 0,
                ]);
            }

            $newRevision = $actualRevision + 1;
            $this->db->table('schedule_versions')->where('id', $scheduleVersionId)->update([
                'revision_number' => $newRevision,
                'change_summary' => 'Kandidat jadwal #' . $candidateId . ' diterapkan',
                'updated_at' => $now,
                'updated_by' => $userId,
            ]);
            $this->db->table('schedule_revision_history')->insert([
                'uuid' => UuidService::v4(),
                'schedule_version_id' => $scheduleVersionId,
                'revision_number' => $newRevision,
                'action' => 'APPLY_GENERATED_CANDIDATE',
                'changes_json' => json_encode([
                    'candidate_id' => $candidateId,
                    'replaced_unlocked_entries' => count($existingIds),
                    'inserted_entries' => $insertedCount,
                ], JSON_THROW_ON_ERROR),
                'performed_by' => $userId,
                'created_at' => $now,
            ]);

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }

        return [
            'status'         => 'success',
            'applied_entries'=> $insertedCount,
            'candidate_id'   => $candidateId,
            'version_id'     => $scheduleVersionId,
            'new_revision'   => $newRevision,
        ];
    }
}
