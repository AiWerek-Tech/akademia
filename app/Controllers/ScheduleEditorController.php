<?php

namespace App\Controllers;

use App\Models\ScheduleEntryModel;
use App\Models\ScheduleVersionModel;
use App\Services\ScheduleConflictDetectionService;
use App\Services\ScheduleExportService;
use App\Services\RoomAvailabilityService;
use App\Services\TeacherAvailabilityService;
use App\Services\TeacherScheduleSubstitutionService;
use App\Services\ScheduleSourceAuditService;
use App\Services\ScheduleConflictPresentationService;
use App\Services\UnitScopeService;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

class ScheduleEditorController extends BaseController
{
    private ScheduleVersionModel $versionModel;
    private ScheduleEntryModel $entryModel;
    private ScheduleConflictDetectionService $conflictService;

    public function __construct()
    {
        $this->versionModel    = new ScheduleVersionModel();
        $this->entryModel      = new ScheduleEntryModel();
        $this->conflictService = new ScheduleConflictDetectionService();
    }

    public function view(int $versionId)
    {
        $version = $this->versionModel->find($versionId);
        if (!$version) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Versi jadwal #{$versionId} tidak ditemukan.");
        }

        $isCombined = empty($version['unit_id']);

        if (!$isCombined) {
            UnitScopeService::assertUnit((int) $version['unit_id']);
        }

        // Open multi-unit grid mode only when requested via ?mode=multi
        if ((string) $this->request->getGet('mode') === 'multi') {
            return $this->multiView($version, Database::connect());
        }

        // Auto-sync routine activities to ensure grid reflects latest routine settings
        (new \App\Services\RoutineActivityScheduleSyncService())->syncForVersion($versionId);

        $db = Database::connect();
        $classroomBuilder = $db->table('classrooms')
            ->where('academic_period_id', $version['academic_period_id'])
            ->where('is_active', 1)->where('deleted_at IS NULL')->orderBy('name');
        if (!empty($version['unit_id'])) {
            $classroomBuilder->where('unit_id', $version['unit_id']);
        }
        $classrooms = $classroomBuilder->get()->getResultArray();
        $selectedClassroomId = (int) ($this->request->getGet('classroom_id') ?: ($classrooms[0]['id'] ?? 0));
        if ($selectedClassroomId > 0) {
            UnitScopeService::assertClassroom($selectedClassroomId);
        }

        $days = $db->table('schedule_days sd')->select('sd.*')->distinct()
            ->join('schedule_day_slots sds', 'sds.day_id = sd.id')
            ->where('sds.schedule_version_id', $versionId)->where('sd.is_school_day', 1)
            ->orderBy('sd.day_of_week')->get()->getResultArray();
        $daySlots = $db->table('schedule_day_slots')->where('schedule_version_id', $versionId)
            ->orderBy('slot_number')->get()->getResultArray();
        $slotMap = [];
        $maxSlot = 0;
        foreach ($daySlots as $slot) {
            $slotMap[(int) $slot['day_id']][(int) $slot['slot_number']] = $slot;
            $maxSlot = max($maxSlot, (int) $slot['slot_number']);
        }

        $entries = [];
        if ($selectedClassroomId > 0) {
            $entries = $db->table('schedule_entries se')
                ->select('se.*, s.name AS subject_name, s.code AS subject_code, t.full_name AS teacher_name, r.name AS room_name')
                ->join('subjects s', 's.id = se.subject_id', 'left')
                ->join('teachers t', 't.id = se.teacher_id', 'left')
                ->join('rooms r', 'r.id = se.room_id', 'left')
                ->where('se.schedule_version_id', $versionId)->where('se.classroom_id', $selectedClassroomId)
                ->get()->getResultArray();
        }
        $entryMap = [];
        foreach ($entries as $entry) {
            $entryMap[(int) $entry['day_slot_id']] = $entry;
        }

        // Fetch fixed routine activities for this classroom & version
        $fixedActivities = [];
        if ($selectedClassroomId > 0) {
            $fixedActivities = $db->table('schedule_fixed_activities sfa')
                ->select('sfa.*, sra.color_label, sra.duration_mode, sra.duration_minutes, sra.placement_zone, sra.placement_sequence')
                ->join('school_routine_activities sra', 'sra.code = SUBSTRING_INDEX(sfa.description, " | ", 1)', 'left')
                ->where('sfa.schedule_version_id', $versionId)
                ->where('sfa.classroom_id', $selectedClassroomId)
                ->get()->getResultArray();
        }
        $fixedMap = [];
        foreach ($fixedActivities as $fa) {
            $fixedMap[(int)$fa['day_slot_id']] = $fa;
        }

        // Fetch active routine activities for unit
        $routineModel = new \App\Models\RoutineActivityModel();
        $routineBuilder = $routineModel->where('is_active', 1);
        if (!empty($version['unit_id'])) {
            $routineBuilder->groupStart()
                ->where('unit_id', $version['unit_id'])
                ->orWhere('unit_id IS NULL')
            ->groupEnd();
        }
        $routineActivities = $routineBuilder->findAll();

        $lastCandidate = $db->table('schedule_generation_candidates sgc')
            ->select('sgc.*, sgr.total_requirements, sgr.placed_requirements, sgr.unplaced_requirements, sgr.execution_time_ms')
            ->join('schedule_generation_runs sgr', 'sgr.id = sgc.generation_run_id')
            ->where('sgr.schedule_version_id', $versionId)->orderBy('sgc.id', 'DESC')->get()->getRowArray();

        $teachers = $db->table('teachers')->where('is_active', 1)->where('deleted_at IS NULL')->orderBy('full_name', 'ASC')->get()->getResultArray();
        $units = $db->table('school_units')->where('is_active', 1)->orderBy('id', 'ASC')->get()->getResultArray();

        $conflictReport = $this->conflictService->detectConflicts($versionId);
        $conflicts = $db->table('schedule_conflicts')->where('schedule_version_id', $versionId)
            ->where('is_resolved', 0)->get()->getResultArray();
        $presentedConflicts=ScheduleConflictPresentationService::split($conflicts);

        return view('schedules/editor', [
            'title'                 => 'Editor Jadwal Pelajaran - ' . $version['name'],
            'version'               => $version,
            'classrooms'            => $classrooms,
            'selected_classroom_id' => $selectedClassroomId,
            'days'                  => $days,
            'slot_map'              => $slotMap,
            'max_slot'              => $maxSlot,
            'entry_map'             => $entryMap,
            'fixed_map'             => $fixedMap,
            'routine_activities'    => $routineActivities,
            'last_candidate'        => $lastCandidate,
            'teachers'              => $teachers,
            'units'                 => $units,
            'conflicts'             => $presentedConflicts['blocking'],
            'advisories'            => $presentedConflicts['advisories'],
            'source_audit'          => (new ScheduleSourceAuditService())->auditVersion($versionId),
        ]);
    }

    /**
     * Master editor: one time-axis with every accessible classroom as a column.
     * Entries can be moved vertically within their own classroom/version while
     * preserving the existing server-side collision and workflow safeguards.
     */
    private function multiView(array $version, $db)
    {
        (new \App\Services\RoutineActivityScheduleSyncService())->syncForVersion((int) $version['id']);
        $grid = (new ScheduleExportService())->getGridForMultiUnit((int) $version['id']);

        $accessibleUnitIds = UnitScopeService::accessibleUnitIds();
        $versionsQuery = $db->table('schedule_versions')
            ->where('academic_period_id', (int) $version['academic_period_id'])
            ->where('workflow_status !=', 'ARCHIVED')
            ->orderBy('is_active', 'DESC')->orderBy('revision_number', 'DESC')->orderBy('id', 'DESC');
        if ($accessibleUnitIds !== []) {
            $versionsQuery->whereIn('unit_id', $accessibleUnitIds);
        }
        $versions = $versionsQuery->get()->getResultArray();
        $versionByUnit = [];
        $versionById = [];
        foreach ($versions as $row) {
            $versionById[(int) $row['id']] = $row;
            if (!isset($versionByUnit[(int) $row['unit_id']])) {
                $versionByUnit[(int) $row['unit_id']] = $row;
            }
        }

        $dayById = [];
        $daysByNumber = [];
        foreach ($grid['days'] as $day) {
            $dayById[(int) $day['id']] = $day;
            $daysByNumber[(int) $day['day_of_week']] = $day;
        }
        ksort($daysByNumber);

        $slotMetaById = [];
        $slotsByVersion = [];
        $maxSlot = 0;
        foreach ($grid['slots'] as $slot) {
            $day = $dayById[(int) $slot['day_id']] ?? null;
            if (!$day) continue;
            $meta = [
                'id' => (int) $slot['id'],
                'version_id' => (int) $slot['schedule_version_id'],
                'day_of_week' => (int) $day['day_of_week'],
                'slot_number' => (int) $slot['slot_number'],
                'start_time' => $slot['start_time'] ?? '',
                'end_time' => $slot['end_time'] ?? '',
                'label' => $slot['label'] ?? '',
            ];
            $slotMetaById[(int) $slot['id']] = $meta;
            $slotsByVersion[(int) $slot['schedule_version_id']][(int) $day['day_of_week']][(int) $slot['slot_number']] = $meta;
            $maxSlot = max($maxSlot, (int) $slot['slot_number']);
        }

        $entryCells = [];
        foreach (($grid['entries'] ?? []) as $entry) {
            $meta = $slotMetaById[(int) $entry['day_slot_id']] ?? null;
            if (!$meta) continue;
            $entryCells[(int) $entry['classroom_id']][$meta['day_of_week']][$meta['slot_number']] = $entry;
        }
        $fixedCells = [];
        foreach (($grid['fixed_entries'] ?? []) as $fixed) {
            $meta = $slotMetaById[(int) $fixed['day_slot_id']] ?? null;
            if (!$meta) continue;
            $fixedCells[(int) $fixed['classroom_id']][$meta['day_of_week']][$meta['slot_number']] = $fixed;
        }

        $classrooms = [];
        $editableVersions = [];
        foreach ($grid['classrooms'] as $classroom) {
            $unitVersion = $versionByUnit[(int) $classroom['unit_id']] ?? null;
            $classroom['schedule_version_id'] = (int) ($unitVersion['id'] ?? 0);
            $classrooms[] = $classroom;
            if ($unitVersion && !in_array($unitVersion['workflow_status'], ['LOCKED', 'ARCHIVED'], true)) {
                $editableVersions[(int) $unitVersion['id']] = true;
            }
        }

        $teacherIds = [];
        foreach (($grid['entries'] ?? []) as $entry) {
            if (!empty($entry['teacher_id'])) $teacherIds[(int) $entry['teacher_id']] = true;
            if (!empty($entry['second_teacher_id'])) $teacherIds[(int) $entry['second_teacher_id']] = true;
        }
        $teacherLegend = [];
        foreach ($grid['teachers'] as $teacher) {
            if (isset($teacherIds[(int) $teacher['id']])) $teacherLegend[] = $teacher;
        }

        return view('schedules/editor_multi', [
            'title' => 'Editor Master Jadwal SMP-SMA',
            'version' => $version,
            'classrooms' => $classrooms,
            'days' => array_values($daysByNumber),
            'max_slot' => $maxSlot,
            'slots_by_version' => $slotsByVersion,
            'entry_cells' => $entryCells,
            'fixed_cells' => $fixedCells,
            'teacher_legend' => $teacherLegend,
            'editable_versions' => $editableVersions,
            'version_by_id' => $versionById,
        ]);
    }

    public function saveEntry(int $versionId): ResponseInterface
    {
        $version = $this->versionModel->find($versionId);
        if (!$version) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Versi jadwal tidak ditemukan'])->setStatusCode(404);
        }
        if (!empty($version['unit_id'])) {
            UnitScopeService::assertUnit((int) $version['unit_id']);
        }

        if (in_array((string)$version['workflow_status'], ['LOCKED', 'ARCHIVED'], true)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Jadwal terkunci/diarsipkan dan tidak dapat diubah'])->setStatusCode(400);
        }

        $daySlotId             = (int)$this->request->getPost('day_slot_id');
        $sourceDaySlotId       = (int)$this->request->getPost('source_day_slot_id');
        $sourceEntryId         = (int)$this->request->getPost('source_entry_id');
        $scheduleRequirementId = (int)$this->request->getPost('schedule_requirement_id');
        $classroomId           = (int)$this->request->getPost('classroom_id');
        $teacherId             = (int)$this->request->getPost('teacher_id');
        $secondTeacherId       = $this->request->getPost('second_teacher_id') ? (int)$this->request->getPost('second_teacher_id') : null;
        $subjectId             = (int)$this->request->getPost('subject_id');
        $roomId                = $this->request->getPost('room_id') ? (int)$this->request->getPost('room_id') : null;
        $currentRevision       = filter_var($this->request->getPost('current_revision'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $userId                = (int)session()->get('user_id');

        try {
            (new \App\Services\TeamTeachingPolicyService())->assertSecondTeacherSupported($secondTeacherId);
        } catch (\RuntimeException $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()])->setStatusCode(422);
        }

        if ($currentRevision === false || $currentRevision === null
            || (int) $version['revision_number'] !== (int) $currentRevision) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Jadwal telah berubah. Muat ulang halaman sebelum memindahkan entri.',
            ])->setStatusCode(409);
        }

        $db = Database::connect();
        $slot = $db->table('schedule_day_slots')->where('id', $daySlotId)
            ->where('schedule_version_id', $versionId)->get()->getRowArray();
        $classroomBuilder = $db->table('classrooms')->where('id', $classroomId)
            ->where('academic_period_id', (int) $version['academic_period_id'])
            ->where('is_active', 1)->where('deleted_at IS NULL');
        if (!empty($version['unit_id'])) {
            $classroomBuilder->where('unit_id', (int) $version['unit_id']);
        }
        $classroom = $classroomBuilder->get()->getRowArray();
        if (!$slot || !$classroom || $teacherId <= 0 || $subjectId <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Slot, rombel, guru, atau mata pelajaran tidak valid untuk versi jadwal ini.'])->setStatusCode(422);
        }

        try {
            if (!empty($version['unit_id'])) {
                UnitScopeService::assertTeacherInUnit($teacherId, (int) $version['unit_id'], (int) $version['academic_period_id']);
                UnitScopeService::assertSubjectInUnit($subjectId, (int) $version['unit_id']);
                if ($secondTeacherId) {
                    UnitScopeService::assertTeacherInUnit($secondTeacherId, (int) $version['unit_id'], (int) $version['academic_period_id']);
                }
            } else {
                UnitScopeService::assertTeacher($teacherId);
                UnitScopeService::assertSubject($subjectId);
                if ($secondTeacherId) UnitScopeService::assertTeacher($secondTeacherId);
            }
            if ($roomId) UnitScopeService::assertRoom($roomId);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()])->setStatusCode(403);
        }

        if ($scheduleRequirementId > 0) {
            $validRequirement = $db->table('schedule_requirements')
                ->where('id', $scheduleRequirementId)
                ->where('schedule_version_id', $versionId)
                ->where('classroom_id', $classroomId)
                ->where('subject_id', $subjectId)
                ->groupStart()
                    ->where('teacher_id', $teacherId)
                    ->orWhere('second_teacher_id', $teacherId)
                ->groupEnd()
                ->get()->getRowArray();
            if (! $validRequirement) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Kebutuhan jadwal tidak sesuai dengan kelas, guru, atau mata pelajaran.'])->setStatusCode(422);
            }
        }

        $existing = $this->entryModel
            ->where('schedule_version_id', $versionId)
            ->where('day_slot_id', $daySlotId)
            ->where('classroom_id', $classroomId)->first();

        $targetDay = $db->table('schedule_day_slots sds')
            ->select('sd.day_of_week, sds.slot_number')
            ->join('schedule_days sd', 'sd.id = sds.day_id')
            ->where('sds.id', $daySlotId)->get()->getRowArray();
        $targetDayNumber = (int) ($targetDay['day_of_week'] ?? 0);
        $targetSlotNumber = (int) ($targetDay['slot_number'] ?? 0);

        $teacherAvailability = new TeacherAvailabilityService();
        $teacherSubstitutions = new TeacherScheduleSubstitutionService();
        foreach (array_filter([$teacherId, $secondTeacherId]) as $candidateTeacherId) {
            if (! $teacherAvailability->isTeacherAvailable((int) $candidateTeacherId, (int) $version['academic_period_id'], $targetDayNumber, $targetSlotNumber)) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Guru tidak tersedia pada waktu yang dipilih.'])->setStatusCode(409);
            }
            if ($teacherAvailability->getCrossUnitTeacherScheduleOccupancy(
                (int) $candidateTeacherId,
                (int) $version['academic_period_id'],
                $targetDayNumber,
                $targetSlotNumber,
                $versionId
            ) !== []) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Guru sudah mengajar pada jadwal aktif unit lain di waktu yang sama.'])->setStatusCode(409);
            }
        }
        if ($roomId && ! (new RoomAvailabilityService())->isRoomAvailable(
            $roomId,
            (int) $version['academic_period_id'],
            $targetDayNumber,
            $targetSlotNumber
        )) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Ruang tidak tersedia pada waktu yang dipilih.'])->setStatusCode(409);
        }
        $sameClassEntries = $db->table('schedule_entries se')
            ->select('se.id, se.subject_id, se.teacher_id, se.second_teacher_id, sd.day_of_week')
            ->join('schedule_day_slots sds', 'sds.id = se.day_slot_id')
            ->join('schedule_days sd', 'sd.id = sds.day_id')
            ->where('se.schedule_version_id', $versionId)->where('se.classroom_id', $classroomId)
            ->get()->getResultArray();
        foreach ($sameClassEntries as $other) {
            if ((int) $other['id'] === $sourceEntryId || ($existing && (int) $other['id'] === (int) $existing['id'])) continue;
            $otherTeachers = [$teacherSubstitutions->resolveResourceTeacherId(
                (int) $other['teacher_id'],
                (int) $version['academic_period_id']
            )];
            if (!empty($other['second_teacher_id'])) {
                $otherTeachers[] = $teacherSubstitutions->resolveResourceTeacherId(
                    (int) $other['second_teacher_id'],
                    (int) $version['academic_period_id']
                );
            }
            $newTeachers = [$teacherSubstitutions->resolveResourceTeacherId(
                $teacherId,
                (int) $version['academic_period_id']
            )];
            if ($secondTeacherId) {
                $newTeachers[] = $teacherSubstitutions->resolveResourceTeacherId(
                    $secondTeacherId,
                    (int) $version['academic_period_id']
                );
            }
            if ((int) $other['day_of_week'] === $targetDayNumber
                && (int) $other['subject_id'] !== $subjectId
                && array_intersect($otherTeachers, $newTeachers) !== []) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Guru tersebut sudah mengajar mata pelajaran lain di kelas yang sama pada hari tersebut.'])->setStatusCode(409);
            }
            if ((int) $other['subject_id'] === $subjectId
                && abs((int) $other['day_of_week'] - $targetDayNumber) < 2
                && (int) $other['day_of_week'] !== $targetDayNumber) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Mata pelajaran yang sama harus diberi jeda minimal satu hari.'])->setStatusCode(409);
            }
        }

        $sourceEntry = null;
        if ($sourceEntryId > 0 && $sourceDaySlotId > 0 && $sourceDaySlotId !== $daySlotId) {
            $sourceEntry = $this->entryModel
                ->where('id', $sourceEntryId)
                ->where('schedule_version_id', $versionId)
                ->where('day_slot_id', $sourceDaySlotId)
                ->where('classroom_id', $classroomId)->first();
            if (!$sourceEntry) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Kartu sumber sudah berubah. Muat ulang editor lalu coba lagi.'])->setStatusCode(409);
            }
            if ((int) ($sourceEntry['is_locked'] ?? 0) === 1) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Entri sumber terkunci dan tidak dapat dipindahkan.'])->setStatusCode(409);
            }
            if ($existing) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Slot tujuan sudah terisi. Pilih slot kosong agar tidak menimpa jadwal lain.'])->setStatusCode(409);
            }
        }
        $fixed = $db->table('schedule_fixed_activities')
            ->where('schedule_version_id', $versionId)->where('day_slot_id', $daySlotId)
            ->where('classroom_id', $classroomId)->get()->getRowArray();
        if ($existing && (int) $existing['is_locked'] === 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Entri kegiatan rutin terkunci dan tidak dapat ditimpa.'])->setStatusCode(409);
        }
        if ($fixed) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Slot tersebut digunakan kegiatan rutin dan tidak dapat ditimpa.'])->setStatusCode(409);
        }

        $slotEntries = $db->table('schedule_entries')
            ->where('schedule_version_id', $versionId)->where('day_slot_id', $daySlotId)
            ->get()->getResultArray();
        foreach ($slotEntries as $other) {
            if ($existing && (int) $other['id'] === (int) $existing['id']) continue;
            $otherResourceIds = [$teacherSubstitutions->resolveResourceTeacherId(
                (int) $other['teacher_id'],
                (int) $version['academic_period_id']
            )];
            if (!empty($other['second_teacher_id'])) {
                $otherResourceIds[] = $teacherSubstitutions->resolveResourceTeacherId(
                    (int) $other['second_teacher_id'],
                    (int) $version['academic_period_id']
                );
            }
            $newResourceIds = [$teacherSubstitutions->resolveResourceTeacherId(
                $teacherId,
                (int) $version['academic_period_id']
            )];
            if ($secondTeacherId) {
                $newResourceIds[] = $teacherSubstitutions->resolveResourceTeacherId(
                    $secondTeacherId,
                    (int) $version['academic_period_id']
                );
            }
            $teachersOverlap = array_intersect($otherResourceIds, $newResourceIds) !== [];
            if ($teachersOverlap) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Guru sudah mengajar di kelas lain pada slot tersebut.'])->setStatusCode(409);
            }
            if ($roomId && (int) $other['room_id'] === $roomId) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Ruang sudah digunakan kelas lain pada slot tersebut.'])->setStatusCode(409);
            }
        }

        $now = date('Y-m-d H:i:s');
        $data = [
            'uuid'                    => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
            'schedule_version_id'     => $versionId,
            'day_slot_id'             => $daySlotId,
            'schedule_requirement_id' => $scheduleRequirementId,
            'classroom_id'            => $classroomId,
            'teacher_id'              => $teacherId,
            'second_teacher_id'       => $secondTeacherId,
            'subject_id'              => $subjectId,
            'room_id'                 => $roomId,
            'is_locked'               => 0,
            'created_at'              => $now,
            'updated_at'              => $now,
            'created_by'              => $userId,
            'updated_by'              => $userId,
        ];

        $db->transException(true)->transBegin();
        try {
            $db->table('schedule_versions')
                ->where('id', $versionId)
                ->where('revision_number', (int) $currentRevision)
                ->update([
                    'revision_number' => (int) $currentRevision + 1,
                    'change_summary' => 'Entri jadwal diperbarui melalui editor',
                    'updated_at' => $now,
                    'updated_by' => $userId,
                ]);
            if ($db->affectedRows() !== 1) {
                throw new \RuntimeException('Jadwal berubah saat penyimpanan. Muat ulang halaman lalu ulangi pemindahan.', 409);
            }

            // Unique constraint check for classroom at same slot
            if ($existing) {
                if (! $this->entryModel->update($existing['id'], $data)) {
                    throw new \RuntimeException('Entri tujuan gagal diperbarui.');
                }
                $entryId = (int)$existing['id'];
            } else {
                if (! $this->entryModel->insert($data)) {
                    throw new \RuntimeException('Entri tujuan gagal disimpan.');
                }
                $entryId = (int)$this->entryModel->insertID();
            }
            if ($sourceEntry && $sourceEntryId !== $entryId && ! $this->entryModel->delete($sourceEntryId)) {
                throw new \RuntimeException('Entri sumber gagal dipindahkan.');
            }

            // Re-run conflict detection in the same transaction.
            $conflictReport = $this->conflictService->detectConflicts($versionId);
            $blocking = array_filter($conflictReport['conflicts'] ?? [], static fn (array $conflict): bool =>
                ($conflict['severity'] ?? '') === 'CRITICAL'
            );
            if ($blocking !== []) {
                throw new \RuntimeException('Perubahan dibatalkan karena menghasilkan konflik kritis.', 409);
            }

            $db->table('schedule_revision_history')->insert([
                'uuid' => \App\Services\UuidService::v4(),
                'schedule_version_id' => $versionId,
                'revision_number' => (int) $currentRevision + 1,
                'action' => $sourceEntry ? 'MOVE_ENTRY' : ($existing ? 'UPDATE_ENTRY' : 'CREATE_ENTRY'),
                'changes_json' => json_encode([
                    'entry_id' => $entryId,
                    'source_entry_id' => $sourceEntryId ?: null,
                    'target_day_slot_id' => $daySlotId,
                ], JSON_THROW_ON_ERROR),
                'performed_by' => $userId,
                'created_at' => $now,
            ]);
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()])
                ->setStatusCode($e->getCode() === 409 ? 409 : 500);
        }

        return $this->response->setJSON([
            'status'          => 'success',
            'data'            => ['entry_id' => $entryId],
            'new_revision'    => (int) $currentRevision + 1,
            'conflict_report' => $conflictReport,
        ]);
    }

    public function deleteEntry(int $entryId): ResponseInterface
    {
        $entry = $this->entryModel->find($entryId);
        if (!$entry) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Entri jadwal tidak ditemukan'])->setStatusCode(404);
        }
        $version = $this->versionModel->find((int) $entry['schedule_version_id']);
        if (! $version) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Versi jadwal tidak ditemukan'])->setStatusCode(404);
        }
        if (!empty($version['unit_id'])) {
            UnitScopeService::assertUnit((int) $version['unit_id']);
        }

        if (in_array((string) $version['workflow_status'], ['LOCKED', 'ARCHIVED'], true)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Jadwal terkunci/diarsipkan dan tidak dapat diubah'])->setStatusCode(409);
        }

        if ((int)$entry['is_locked'] === 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Entri terkunci dan tidak dapat dihapus'])->setStatusCode(400);
        }

        $currentRevision = filter_var($this->request->getPost('current_revision'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($currentRevision === false || $currentRevision === null) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Revisi jadwal wajib disertakan.'])->setStatusCode(422);
        }

        $versionId = (int)$entry['schedule_version_id'];
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        $db->transException(true)->transBegin();
        try {
            $db->table('schedule_versions')->where('id', $versionId)
                ->where('revision_number', (int) $currentRevision)
                ->update([
                    'revision_number' => (int) $currentRevision + 1,
                    'change_summary' => 'Entri jadwal #' . $entryId . ' dihapus',
                    'updated_at' => $now,
                    'updated_by' => (int) session()->get('user_id'),
                ]);
            if ($db->affectedRows() !== 1) {
                throw new \RuntimeException('Jadwal telah berubah. Muat ulang halaman sebelum menghapus entri.', 409);
            }
            if (! $this->entryModel->delete($entryId)) {
                throw new \RuntimeException('Entri jadwal gagal dihapus.');
            }
            $conflictReport = $this->conflictService->detectConflicts($versionId);
            $db->table('schedule_revision_history')->insert([
                'uuid' => \App\Services\UuidService::v4(),
                'schedule_version_id' => $versionId,
                'revision_number' => (int) $currentRevision + 1,
                'action' => 'DELETE_ENTRY',
                'changes_json' => json_encode(['entry_id' => $entryId], JSON_THROW_ON_ERROR),
                'performed_by' => (int) session()->get('user_id'),
                'created_at' => $now,
            ]);
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()])
                ->setStatusCode($e->getCode() === 409 ? 409 : 500);
        }

        return $this->response->setJSON([
            'status'          => 'success',
            'new_revision'    => (int) $currentRevision + 1,
            'conflict_report' => $conflictReport,
        ]);
    }
}
