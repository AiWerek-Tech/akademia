<?php

namespace App\Services;

use App\Models\AttendanceSessionModel;
use App\Models\StudentAttendanceModel;
use Config\Database;

class AttendanceService
{
    public const SESSION_TYPES = ['SUBJECT', 'CLASSROOM', 'MORNING_ASSEMBLY', 'AFTERNOON_ASSEMBLY'];
    public const STUDENT_STATUSES = ['HADIR', 'TERLAMBAT', 'IZIN', 'SAKIT', 'ALPA', 'DISPENSASI'];

    private $db;
    private AttendanceSessionModel $sessionModel;
    private StudentAttendanceModel $studentAttendanceModel;

    public function __construct()
    {
        $this->db                     = Database::connect();
        $this->sessionModel           = new AttendanceSessionModel();
        $this->studentAttendanceModel = new StudentAttendanceModel();
    }

    public static function sessionTypeLabel(string $type): string
    {
        return [
            'SUBJECT' => 'Absensi Mata Pelajaran',
            'CLASSROOM' => 'Absensi Kelas',
            'MORNING_ASSEMBLY' => 'Apel & Absensi Pagi',
            'AFTERNOON_ASSEMBLY' => 'Apel & Absensi Siang',
        ][strtoupper($type)] ?? 'Presensi Siswa';
    }

    public function getOperatingSettings(int $periodId, int $unitId): array
    {
        $period = $this->db->table('academic_periods')->select('academic_year_id')->where('id', $periodId)->get()->getRowArray();
        $setting = $period ? $this->db->table('attendance_operating_settings')->where('academic_year_id', (int) $period['academic_year_id'])->where('unit_id', $unitId)->get()->getRowArray() : null;
        return $setting ?: [
            'morning_label'=>'Apel & Absensi Pagi','morning_start_time'=>'06:45:00','morning_end_time'=>'07:30:00',
            'afternoon_label'=>'Apel & Absensi Siang','afternoon_start_time'=>'14:00:00','afternoon_end_time'=>'15:30:00',
            'carry_forward_absence'=>1,'allow_off_schedule_subject'=>0,
        ];
    }

    public function getSessionWindow(int $periodId, int $unitId, string $date, string $type): array
    {
        $settings = $this->getOperatingSettings($periodId, $unitId);
        $dayNumber = (int) (new \DateTimeImmutable($date))->format('N');
        $bounds = $this->db->table('schedule_day_slots sds')
            ->select('MIN(sds.start_time) first_lesson,MAX(sds.end_time) last_lesson')
            ->join('schedule_versions sv', 'sv.id=sds.schedule_version_id')
            ->join('schedule_days sd', 'sd.id=sds.day_id')
            ->where('sv.academic_period_id', $periodId)->where('sv.unit_id', $unitId)
            ->whereIn('sv.workflow_status', ['APPROVED','LOCKED'])->where('sd.day_of_week', $dayNumber)
            ->where('sds.slot_type', 'LESSON')->get()->getRowArray();
        if ($type === 'MORNING_ASSEMBLY') {
            return ['start_time'=>$settings['morning_start_time'],'end_time'=>$bounds['first_lesson'] ?: $settings['morning_end_time']];
        }
        if ($type === 'AFTERNOON_ASSEMBLY') {
            return ['start_time'=>$bounds['last_lesson'] ?: $settings['afternoon_start_time'],'end_time'=>$settings['afternoon_end_time']];
        }
        return ['start_time'=>null,'end_time'=>null];
    }

    /**
     * Builds the teacher's date-aware operational timeline from the locked or
     * approved schedule. Subject JP blocks are collapsed into one attendance
     * card, while homeroom teachers receive morning/class/afternoon cards.
     */
    public function getDailyWorkspace(int $teacherId, int $periodId, string $date, ?int $unitId = null): array
    {
        $dayNumber = (int) (new \DateTimeImmutable($date))->format('N');
        $ownerIds = (new TeacherScheduleSubstitutionService())->teacherIdsSharingResource($teacherId, $periodId, $date);
        $entries = [];
        if ($teacherId > 0) {
            $builder = $this->db->table('schedule_entries se')
                ->select('se.id schedule_entry_id, se.classroom_id, se.subject_id, se.teacher_id, se.second_teacher_id, sv.unit_id, c.name classroom_name, s.code subject_code, s.name subject_name, su.code unit_code, su.name unit_name, sds.slot_number, sds.start_time, sds.end_time')
                ->join('schedule_versions sv', 'sv.id=se.schedule_version_id')
                ->join('schedule_day_slots sds', 'sds.id=se.day_slot_id')
                ->join('schedule_days sd', 'sd.id=sds.day_id')
                ->join('classrooms c', 'c.id=se.classroom_id')
                ->join('subjects s', 's.id=se.subject_id')
                ->join('school_units su', 'su.id=sv.unit_id')
                ->where('sv.academic_period_id', $periodId)
                ->whereIn('sv.workflow_status', ['APPROVED', 'LOCKED'])
                ->where('sd.day_of_week', $dayNumber)
                ->groupStart()->whereIn('se.teacher_id', $ownerIds)->orWhereIn('se.second_teacher_id', $ownerIds)->groupEnd()
                ->orderBy('sds.start_time', 'ASC');
            if ($unitId) {
                $builder->where('sv.unit_id', $unitId);
            }
            $entries = $builder->get()->getResultArray();
        }

        $blocks = [];
        foreach ($entries as $entry) {
            $key = $entry['unit_id'] . ':' . $entry['classroom_id'] . ':' . $entry['subject_id'];
            $lastIndex = isset($blocks[$key]) ? count($blocks[$key]) - 1 : -1;
            $last = $lastIndex >= 0 ? $blocks[$key][$lastIndex] : null;
            if ($last && (int) $entry['slot_number'] === (int) $last['last_slot'] + 1) {
                $blocks[$key][$lastIndex]['last_slot'] = (int) $entry['slot_number'];
                $blocks[$key][$lastIndex]['end_time'] = $entry['end_time'];
                $blocks[$key][$lastIndex]['jp_count']++;
                continue;
            }
            $sourceKey = $this->buildSourceKey('SUBJECT', $periodId, (int) $entry['classroom_id'], $date, (int) $entry['subject_id'], (int) $entry['schedule_entry_id'], 1);
            $blocks[$key][] = $entry + [
                'session_type' => 'SUBJECT', 'source_key' => $sourceKey,
                'jp_count' => 1, 'last_slot' => (int) $entry['slot_number'],
            ];
        }
        $subjectBlocks = [];
        foreach ($blocks as $group) {
            foreach ($group as $block) {
                $subjectBlocks[] = $block;
            }
        }
        usort($subjectBlocks, static fn(array $a, array $b): int => strcmp((string) $a['start_time'], (string) $b['start_time']));

        $homeroomBuilder = $this->db->table('classrooms c')
            ->select('c.id classroom_id,c.name classroom_name,c.unit_id,su.code unit_code,su.name unit_name')
            ->join('school_units su', 'su.id=c.unit_id')
            ->where('c.academic_period_id', $periodId)->where('c.homeroom_teacher_id', $teacherId)
            ->where('c.is_active', 1)->where('c.deleted_at IS NULL');
        if ($unitId) {
            $homeroomBuilder->where('c.unit_id', $unitId);
        }
        $homerooms = $teacherId > 0 ? $homeroomBuilder->get()->getResultArray() : [];

        $existingRows = $this->db->table('attendance_sessions')
            ->select('id,source_key,status,session_type,classroom_id,subject_id')
            ->where('academic_period_id', $periodId)->where('attendance_date', $date)
            ->where('deleted_at IS NULL')->get()->getResultArray();
        $existing = [];
        foreach ($existingRows as $row) {
            $existing[(string) $row['source_key']] = $row;
        }

        $attach = static function (array $card) use ($existing): array {
            $card['session'] = $existing[(string) $card['source_key']] ?? null;
            return $card;
        };
        $subjectBlocks = array_map($attach, $subjectBlocks);
        $homeroomCards = [];
        foreach ($homerooms as $classroom) {
            $operating = $this->getOperatingSettings($periodId, (int) $classroom['unit_id']);
            foreach (['MORNING_ASSEMBLY', 'CLASSROOM', 'AFTERNOON_ASSEMBLY'] as $type) {
                $window = $this->getSessionWindow($periodId, (int) $classroom['unit_id'], $date, $type);
                $sourceKey = $this->buildSourceKey($type, $periodId, (int) $classroom['classroom_id'], $date, null, null, 1);
                $homeroomCards[$type][] = $attach($classroom + [
                    'session_type' => $type, 'source_key' => $sourceKey,
                    'subject_name' => $type === 'MORNING_ASSEMBLY' ? $operating['morning_label'] : ($type === 'AFTERNOON_ASSEMBLY' ? $operating['afternoon_label'] : 'Absensi Kelas'),
                    'start_time' => $window['start_time'], 'end_time' => $window['end_time'],
                ]);
            }
        }

        $calendarState = null;
        if ($unitId) {
            $period = $this->db->table('academic_periods')->select('academic_year_id')->where('id', $periodId)->get()->getRowArray();
            if ($period) {
                $calendarState = (new AcademicCalendarGeneratorService())->getActiveDay((int) $period['academic_year_id'], $unitId, $date);
            }
        }
        return [
            'date' => $date, 'calendar_day' => $calendarState,
            'morning' => $homeroomCards['MORNING_ASSEMBLY'] ?? [],
            'classroom' => $homeroomCards['CLASSROOM'] ?? [],
            'lessons' => $subjectBlocks,
            'afternoon' => $homeroomCards['AFTERNOON_ASSEMBLY'] ?? [],
        ];
    }

    public function buildSourceKey(string $type, int $periodId, int $classroomId, string $date, ?int $subjectId, ?int $scheduleEntryId, int $meeting): string
    {
        $type = strtoupper($type);
        $resource = $type === 'SUBJECT'
            ? ($scheduleEntryId ? 'SE' . $scheduleEntryId : 'S' . (int) $subjectId . 'M' . $meeting)
            : $type;
        return $type . ':' . $periodId . ':' . $classroomId . ':' . $date . ':' . $resource;
    }

    public function teacherCanRecordType(int $teacherId, int $periodId, int $classroomId, string $type, ?int $subjectId, string $date, bool $isAdmin): bool
    {
        if ($isAdmin) {
            return true;
        }
        $type = strtoupper($type);
        if ($type === 'SUBJECT') {
            return $subjectId !== null && $this->teacherHasActiveAssignment($teacherId, $periodId, $classroomId, $subjectId, $date);
        }
        return $this->db->table('classrooms')->where('id', $classroomId)
            ->where('academic_period_id', $periodId)->where('homeroom_teacher_id', $teacherId)
            ->where('is_active', 1)->where('deleted_at IS NULL')->countAllResults() > 0;
    }

    public function getSuggestedRoster(int $classroomId, string $date, string $sessionType): array
    {
        $students = $this->getClassroomStudents($classroomId);
        $suggestions = [];
        if ($students === []) {
            return [];
        }
        $classroom = $this->db->table('classrooms')->select('academic_period_id,unit_id')->where('id', $classroomId)->get()->getRowArray();
        if ($classroom && empty($this->getOperatingSettings((int) $classroom['academic_period_id'], (int) $classroom['unit_id'])['carry_forward_absence'])) {
            foreach ($students as &$student) {
                $student['suggested_status']='HADIR';$student['suggested_notes']='';$student['source_session_id']=null;
            }
            unset($student);
            return $students;
        }
        $latest = $this->db->table('attendance_sessions')
            ->select('id,session_type')->where('classroom_id', $classroomId)->where('attendance_date', $date)
            ->where('deleted_at IS NULL')->where('status !=', 'DRAFT');
        if ($sessionType === 'SUBJECT') {
            $latest->whereIn('session_type', ['MORNING_ASSEMBLY', 'CLASSROOM', 'SUBJECT']);
        } elseif ($sessionType === 'AFTERNOON_ASSEMBLY') {
            $latest->whereIn('session_type', ['MORNING_ASSEMBLY', 'CLASSROOM', 'SUBJECT']);
        } else {
            return $students;
        }
        $source = $latest->orderBy('id', 'DESC')->get()->getRowArray();
        if ($source) {
            $rows = $this->db->table('student_attendances')->where('session_id', (int) $source['id'])->get()->getResultArray();
            foreach ($rows as $row) {
                if (in_array($row['status'], ['IZIN', 'SAKIT', 'ALPA', 'DISPENSASI'], true)) {
                    $suggestions[(int) $row['student_id']] = $row;
                }
            }
        }
        foreach ($students as &$student) {
            $source = $suggestions[(int) $student['id']] ?? null;
            $student['suggested_status'] = $source['status'] ?? 'HADIR';
            $student['suggested_notes'] = $source ? trim('Dibawa dari sesi sebelumnya. ' . ($source['notes'] ?? '')) : '';
            $student['source_session_id'] = $source['session_id'] ?? null;
        }
        unset($student);
        return $students;
    }

    /**
     * Verifies ownership of a class/subject attendance product. Temporary
     * substitutes inherit the absent teacher's assignment only while the
     * substitution is active on the attendance date.
     */
    public function teacherHasActiveAssignment(
        int $teacherId,
        int $periodId,
        int $classroomId,
        int $subjectId,
        ?string $onDate = null
    ): bool {
        if ($teacherId <= 0 || $periodId <= 0 || $classroomId <= 0 || $subjectId <= 0) {
            return false;
        }

        $ownerIds = (new TeacherScheduleSubstitutionService())
            ->teacherIdsSharingResource($teacherId, $periodId, $onDate);

        return $this->db->table('teaching_assignments ta')
            ->join('assignment_versions av', 'av.id = ta.assignment_version_id')
            ->where('ta.academic_period_id', $periodId)
            ->where('ta.classroom_id', $classroomId)
            ->where('ta.subject_id', $subjectId)
            ->whereIn('ta.teacher_id', $ownerIds)
            ->where('ta.status', 'ACTIVE')
            ->where('ta.deleted_at IS NULL')
            ->where('av.workflow_status !=', 'ARCHIVED')
            ->countAllResults() > 0;
    }

    /**
     * Fetch active classroom + subject teaching assignments for a teacher
     */
    public function getTeacherAssignments(int $teacherId, int $periodId, ?int $unitId = null): array
    {
        if ($teacherId <= 0 || $periodId <= 0) {
            return ['teaching_assignments' => [], 'schedule_entries' => []];
        }

        $teacherOwnerIds = (new TeacherScheduleSubstitutionService())
            ->teacherIdsSharingResource($teacherId, $periodId);

        $builder = $this->db->table('teaching_assignments ta')
            ->select('ta.id as assignment_id, ta.unit_id, ta.classroom_id, ta.subject_id, c.name as classroom_name, s.code as subject_code, s.name as subject_name, su.name as unit_name, gl.grade_number')
            ->join('assignment_versions av', 'av.id = ta.assignment_version_id')
            ->join('classrooms c', 'c.id = ta.classroom_id')
            ->join('grade_levels gl', 'gl.id = c.grade_level_id')
            ->join('subjects s', 's.id = ta.subject_id')
            ->join('school_units su', 'su.id = ta.unit_id')
            ->where('av.academic_period_id', $periodId)
            ->where('av.workflow_status !=', 'ARCHIVED')
            ->whereIn('ta.teacher_id', $teacherOwnerIds)
            ->where('ta.status', 'ACTIVE')
            ->where('ta.deleted_at IS NULL')
            ->where('c.deleted_at IS NULL')
            ->orderBy('c.name', 'ASC')
            ->orderBy('s.name', 'ASC');

        if ($unitId !== null && $unitId > 0) {
            $builder->where('ta.unit_id', $unitId);
        }

        $assignments = $builder->get()->getResultArray();

        // Also check if teacher has schedule entries in schedule_entries for active schedule versions
        $scheduleBuilder = $this->db->table('schedule_entries se')
            ->select('se.id as schedule_entry_id, se.classroom_id, se.subject_id, c.name as classroom_name, s.code as subject_code, s.name as subject_name, su.name as unit_name, sd.day_name, sds.slot_number, sds.start_time, sds.end_time')
            ->join('schedule_versions sv', 'sv.id = se.schedule_version_id')
            ->join('classrooms c', 'c.id = se.classroom_id')
            ->join('subjects s', 's.id = se.subject_id')
            ->join('school_units su', 'su.id = sv.unit_id')
            ->join('schedule_day_slots sds', 'sds.id = se.day_slot_id')
            ->join('schedule_days sd', 'sd.id = sds.day_id')
            ->where('sv.academic_period_id', $periodId)
            ->whereIn('sv.workflow_status', ['APPROVED', 'LOCKED'])
            ->groupStart()
                ->whereIn('se.teacher_id', $teacherOwnerIds)
                ->orWhereIn('se.second_teacher_id', $teacherOwnerIds)
            ->groupEnd();

        if ($unitId !== null && $unitId > 0) {
            $scheduleBuilder->where('sv.unit_id', $unitId);
        }

        $scheduleEntries = $scheduleBuilder->get()->getResultArray();

        return [
            'teaching_assignments' => $assignments,
            'schedule_entries'     => $scheduleEntries,
        ];
    }

    /**
     * Get active students in a classroom
     */
    public function getClassroomStudents(int $classroomId): array
    {
        if ($classroomId <= 0) {
            return [];
        }

        return $this->db->table('elective_students es')
            ->select('es.id, es.student_number, es.full_name, es.current_grade, es.classroom_id, es.user_id')
            ->where('es.classroom_id', $classroomId)
            ->where('es.is_active', 1)
            ->orderBy('es.full_name', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Get details of an attendance session and student rosters
     */
    public function getSessionDetails(int $sessionId): ?array
    {
        if ($sessionId <= 0) {
            return null;
        }

        $session = $this->db->table('attendance_sessions as')
            ->select("as.*, c.name as classroom_name, COALESCE(s.code, as.routine_code, '-') as subject_code, COALESCE(s.name, as.topic, 'Kegiatan Kelas') as subject_name, COALESCE(t.full_name, 'Petugas Presensi') as teacher_name, COALESCE(t.teacher_initial, t.employee_number, '-') as teacher_code, su.name as unit_name, ap.name as period_name")
            ->join('classrooms c', 'c.id = as.classroom_id')
            ->join('subjects s', 's.id = as.subject_id', 'left')
            ->join('teachers t', 't.id = as.teacher_id', 'left')
            ->join('school_units su', 'su.id = as.unit_id')
            ->join('academic_periods ap', 'ap.id = as.academic_period_id')
            ->where('as.id', $sessionId)
            ->where('as.deleted_at IS NULL')
            ->get()->getRowArray();

        if (!$session) {
            return null;
        }

        // Fetch recorded student attendances
        $records = $this->db->table('student_attendances sa')
            ->select('sa.*, es.student_number, es.full_name')
            ->join('elective_students es', 'es.id = sa.student_id')
            ->where('sa.session_id', $sessionId)
            ->orderBy('es.full_name', 'ASC')
            ->get()->getResultArray();

        $recordMap = [];
        foreach ($records as $r) {
            $recordMap[(int)$r['student_id']] = $r;
        }

        // Fetch all current classroom students to ensure every active student is present in roster
        $students = $this->getClassroomStudents((int)$session['classroom_id']);
        $roster = [];
        $counts = array_fill_keys(self::STUDENT_STATUSES, 0);

        foreach ($students as $st) {
            $stId = (int)$st['id'];
            $att = $recordMap[$stId] ?? [
                'id'         => null,
                'session_id' => $sessionId,
                'student_id' => $stId,
                'status'     => 'HADIR',
                'notes'      => '',
            ];
            $status = strtoupper((string)($att['status'] ?? 'HADIR'));
            if (!isset($counts[$status])) {
                $counts[$status] = 0;
            }
            $counts[$status]++;

            $roster[] = [
                'student_id'     => $stId,
                'student_number' => $st['student_number'],
                'full_name'      => $st['full_name'],
                'status'         => $status,
                'notes'          => $att['notes'] ?? '',
                'arrival_time'   => $att['arrival_time'] ?? null,
                'late_minutes'   => (int) ($att['late_minutes'] ?? 0),
                'source_session_id' => $att['source_session_id'] ?? null,
                'record_id'      => $att['id'] ?? null,
            ];
        }

        return [
            'session' => $session,
            'roster'  => $roster,
            'counts'  => $counts,
            'total'   => count($roster),
        ];
    }

    /**
     * Save/Update attendance session & bulk record student attendances
     */
    public function saveSession(array $sessionData, array $studentAttendanceData, int $userId): array
    {
        $classroomId = (int) ($sessionData['classroom_id'] ?? 0);
        if ($classroomId <= 0) {
            throw new \InvalidArgumentException('Kelas presensi tidak valid.');
        }

        $sessionType = strtoupper((string) ($sessionData['session_type'] ?? 'SUBJECT'));
        if (!in_array($sessionType, self::SESSION_TYPES, true)) {
            throw new \InvalidArgumentException('Jenis sesi presensi tidak valid.');
        }
        $sessionData['session_type'] = $sessionType;
        if ($sessionType === 'SUBJECT' && (int) ($sessionData['subject_id'] ?? 0) <= 0) {
            throw new \InvalidArgumentException('Mata pelajaran wajib untuk sesi absensi mapel.');
        }
        if ($sessionType !== 'SUBJECT') {
            $sessionData['subject_id'] = null;
            $sessionData['schedule_entry_id'] = null;
        }

        $attendanceDate = (string) ($sessionData['attendance_date'] ?? '');
        $periodId = (int) ($sessionData['academic_period_id'] ?? 0);
        $unitId = (int) ($sessionData['unit_id'] ?? 0);
        if ($attendanceDate !== '' && $periodId > 0 && $unitId > 0) {
            $period = $this->db->table('academic_periods')->select('academic_year_id, start_date, end_date')->where('id', $periodId)->get()->getRowArray();
            if (!$period || $attendanceDate < $period['start_date'] || $attendanceDate > $period['end_date']) {
                throw new \InvalidArgumentException('Tanggal presensi berada di luar periode akademik.');
            }
            $calendarDay = (new AcademicCalendarGeneratorService())->getActiveDay((int) $period['academic_year_id'], $unitId, $attendanceDate);
            if ($calendarDay && (int) $calendarDay['is_school_effective'] !== 1) {
                $reason = trim((string) ($calendarDay['event_title'] ?? $calendarDay['day_type_code']));
                throw new \InvalidArgumentException("Presensi tidak dapat dibuat pada hari non-efektif sekolah: {$reason}.");
            }
        }

        $allowedStudentIds = array_map(
            static fn (array $student): int => (int) $student['id'],
            $this->getClassroomStudents($classroomId)
        );
        sort($allowedStudentIds);

        $submittedStudentIds = [];
        foreach ($studentAttendanceData as $studentData) {
            $studentId = (int) ($studentData['student_id'] ?? 0);
            if ($studentId <= 0 || in_array($studentId, $submittedStudentIds, true)) {
                throw new \InvalidArgumentException('Daftar peserta didik presensi tidak valid atau duplikat.');
            }
            if (mb_strlen((string) ($studentData['notes'] ?? '')) > 255) {
                throw new \InvalidArgumentException('Catatan presensi maksimal 255 karakter.');
            }
            $status = strtoupper((string) ($studentData['status'] ?? 'HADIR'));
            if (!in_array($status, self::STUDENT_STATUSES, true)) {
                throw new \InvalidArgumentException('Status kehadiran siswa tidak valid.');
            }
            $lateMinutes = (int) ($studentData['late_minutes'] ?? 0);
            if ($lateMinutes < 0 || $lateMinutes > 600) {
                throw new \InvalidArgumentException('Durasi keterlambatan tidak valid.');
            }
            $submittedStudentIds[] = $studentId;
        }
        sort($submittedStudentIds);

        if ($allowedStudentIds !== $submittedStudentIds) {
            throw new \InvalidArgumentException('Daftar presensi harus sama dengan peserta didik aktif pada kelas tersebut.');
        }

        foreach (['teaching_summary', 'learning_objectives', 'learning_activity', 'assessment_summary', 'follow_up'] as $journalField) {
            if (mb_strlen((string) ($sessionData[$journalField] ?? '')) > 10000) {
                throw new \InvalidArgumentException('Isi jurnal terlalu panjang. Maksimal 10.000 karakter per bagian.');
            }
        }
        $workflowStatus = strtoupper((string) ($sessionData['status'] ?? 'DRAFT'));
        if (!in_array($workflowStatus, ['DRAFT', 'SUBMITTED'], true)) {
            throw new \InvalidArgumentException('Status workflow presensi tidak valid.');
        }
        $sessionData['status'] = $workflowStatus;
        if ($workflowStatus === 'SUBMITTED') {
            $sessionData['submitted_at'] = date('Y-m-d H:i:s');
            $sessionData['submitted_by'] = $userId;
        }

        $this->db->transException(true)->transStart();

        $sessionId = isset($sessionData['id']) && (int)$sessionData['id'] > 0 ? (int)$sessionData['id'] : 0;
        $now = date('Y-m-d H:i:s');

        if ($sessionId > 0) {
            $existing = $this->sessionModel->find($sessionId);
            if (!$existing) {
                throw new \RuntimeException('Sesi absensi tidak ditemukan.');
            }
            if (strtoupper((string) ($existing['status'] ?? '')) === 'VERIFIED' || !empty($existing['locked_at'])) {
                throw new \RuntimeException('Sesi telah diverifikasi dan dikunci. Buka kembali melalui pengelola presensi bila perlu koreksi.');
            }
            $postedRevision = (int) ($sessionData['revision_number'] ?? 0);
            if ($postedRevision > 0 && $postedRevision !== (int) ($existing['revision_number'] ?? 1)) {
                throw new \RuntimeException('Data sesi telah diperbarui pengguna lain. Muat ulang halaman sebelum menyimpan.');
            }
            $sessionData['revision_number'] = (int) ($existing['revision_number'] ?? 1) + 1;
            $sessionData['updated_by'] = $userId;
            $sessionData['updated_at'] = $now;
            $this->sessionModel->update($sessionId, $sessionData);
        } else {
            $sourceKey = trim((string) ($sessionData['source_key'] ?? ''));
            if ($sourceKey !== '' && $this->db->table('attendance_sessions')->where('source_key', $sourceKey)->where('deleted_at IS NULL')->countAllResults() > 0) {
                throw new \RuntimeException('Sesi presensi untuk kegiatan dan waktu tersebut sudah tersedia.');
            }
            $sessionData['uuid']       = $this->sessionModel->generateUuid();
            $sessionData['created_by'] = $userId;
            $sessionData['created_at'] = $now;
            $sessionData['updated_at'] = $now;
            $this->sessionModel->insert($sessionData);
            $sessionId = (int) $this->sessionModel->insertID();
        }

        // Process student roster attendances
        foreach ($studentAttendanceData as $stData) {
            $studentId = (int) ($stData['student_id'] ?? 0);
            if ($studentId <= 0) {
                continue;
            }
            $status = strtoupper(trim((string)($stData['status'] ?? 'HADIR')));
            $notes = trim((string)($stData['notes'] ?? ''));
            $arrivalTime = trim((string) ($stData['arrival_time'] ?? '')) ?: null;
            $lateMinutes = $status === 'TERLAMBAT' ? max(0, (int) ($stData['late_minutes'] ?? 0)) : 0;
            $sourceSessionId = !empty($stData['source_session_id']) ? (int) $stData['source_session_id'] : null;

            $existing = $this->db->table('student_attendances')
                ->where('session_id', $sessionId)
                ->where('student_id', $studentId)
                ->get()->getRowArray();

            if ($existing) {
                $this->db->table('student_attendances')
                    ->where('id', $existing['id'])
                    ->update([
                        'status'     => $status,
                        'arrival_time' => $arrivalTime,
                        'late_minutes' => $lateMinutes,
                        'notes'      => $notes,
                        'source_session_id' => $sourceSessionId,
                        'updated_at' => $now,
                    ]);
            } else {
                $this->db->table('student_attendances')->insert([
                    'session_id' => $sessionId,
                    'student_id' => $studentId,
                    'status'     => $status,
                    'arrival_time' => $arrivalTime,
                    'late_minutes' => $lateMinutes,
                    'notes'      => $notes,
                    'source_session_id' => $sourceSessionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new \RuntimeException('Gagal menyimpan data absensi siswa.');
        }

        return $this->getSessionDetails($sessionId);
    }

    /**
     * Calculate matrix recap of subject sessions for a classroom over a semester or period
     */
    public function calculateClassroomRecap(int $classroomId, int $subjectId, int $periodId): array
    {
        $classroom = $this->db->table('classrooms c')
            ->select('c.*, su.name as unit_name, gl.name as grade_level_name')
            ->join('school_units su', 'su.id = c.unit_id')
            ->join('grade_levels gl', 'gl.id = c.grade_level_id')
            ->where('c.id', $classroomId)->get()->getRowArray();

        $subject = $this->db->table('subjects')->where('id', $subjectId)->get()->getRowArray();

        if (!$classroom || !$subject) {
            throw new \RuntimeException('Data kelas atau mata pelajaran tidak ditemukan.');
        }

        // Fetch all active attendance sessions for this class and subject
        $sessions = $this->db->table('attendance_sessions as')
            ->select('as.*, t.full_name as teacher_name')
            ->join('teachers t', 't.id = as.teacher_id')
            ->where('as.classroom_id', $classroomId)
            ->where('as.subject_id', $subjectId)
            ->where('as.session_type', 'SUBJECT')
            ->where('as.academic_period_id', $periodId)
            ->where('as.deleted_at IS NULL')
            ->orderBy('as.attendance_date', 'ASC')
            ->orderBy('as.meeting_number', 'ASC')
            ->get()->getResultArray();

        $students = $this->getClassroomStudents($classroomId);
        $sessionIds = array_map(static fn($s) => (int)$s['id'], $sessions);

        $attendanceMap = [];
        if (!empty($sessionIds)) {
            $records = $this->db->table('student_attendances')
                ->whereIn('session_id', $sessionIds)
                ->get()->getResultArray();
            foreach ($records as $r) {
                $attendanceMap[(int)$r['session_id']][(int)$r['student_id']] = $r;
            }
        }

        $studentMatrix = [];
        foreach ($students as $st) {
            $stId = (int)$st['id'];
            $row = [
                'student_id'     => $stId,
                'student_number' => $st['student_number'],
                'full_name'      => $st['full_name'],
                'meetings'       => [],
                'hadir'          => 0,
                'izin'           => 0,
                'sakit'          => 0,
                'alpa'           => 0,
                'terlambat'      => 0,
                'dispensasi'     => 0,
                'rate'           => 0.0,
            ];

            foreach ($sessions as $sess) {
                $sessId = (int)$sess['id'];
                $att = $attendanceMap[$sessId][$stId] ?? null;
                $stCode = $att ? strtoupper((string)$att['status']) : '-';
                if ($stCode === 'HADIR') $row['hadir']++;
                elseif ($stCode === 'IZIN') $row['izin']++;
                elseif ($stCode === 'SAKIT') $row['sakit']++;
                elseif ($stCode === 'ALPA') $row['alpa']++;
                elseif ($stCode === 'TERLAMBAT') $row['terlambat']++;
                elseif ($stCode === 'DISPENSASI') $row['dispensasi']++;

                $row['meetings'][$sessId] = [
                    'status' => $stCode,
                    'notes'  => $att['notes'] ?? '',
                ];
            }

            $totalRecorded = $row['hadir'] + $row['terlambat'] + $row['izin'] + $row['sakit'] + $row['alpa'] + $row['dispensasi'];
            $row['rate'] = $totalRecorded > 0 ? round((($row['hadir'] + $row['terlambat']) / $totalRecorded) * 100, 1) : 100.0;
            $studentMatrix[] = $row;
        }

        return [
            'classroom'      => $classroom,
            'subject'        => $subject,
            'sessions'       => $sessions,
            'student_matrix' => $studentMatrix,
            'total_meetings' => count($sessions),
            'total_students' => count($students),
        ];
    }

    /**
     * Advanced Superadmin Executive Analytics Dashboard Data
     */
    public function getExecutiveAnalytics(
        int $periodId,
        int|array|null $unitId = null,
        ?int $classroomId = null,
        ?int $subjectId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $sessionType = null
    ): array {
        $builder = $this->db->table('attendance_sessions as')
            ->select("as.*, c.name as classroom_name, COALESCE(s.name, as.topic, 'Kegiatan Kelas') as subject_name, COALESCE(t.full_name, 'Petugas Presensi') as teacher_name, su.name as unit_name")
            ->join('classrooms c', 'c.id = as.classroom_id')
            ->join('subjects s', 's.id = as.subject_id', 'left')
            ->join('teachers t', 't.id = as.teacher_id', 'left')
            ->join('school_units su', 'su.id = as.unit_id')
            ->where('as.academic_period_id', $periodId)
            ->where('as.deleted_at IS NULL');

        if (is_array($unitId) && $unitId !== []) {
            $builder->whereIn('as.unit_id', array_values(array_unique(array_map('intval', $unitId))));
        } elseif (is_int($unitId) && $unitId > 0) {
            $builder->where('as.unit_id', $unitId);
        }
        if ($classroomId !== null && $classroomId > 0) {
            $builder->where('as.classroom_id', $classroomId);
        }
        if ($subjectId !== null && $subjectId > 0) {
            $builder->where('as.subject_id', $subjectId);
        }
        if (!empty($startDate)) {
            $builder->where('as.attendance_date >=', $startDate);
        }
        if (!empty($endDate)) {
            $builder->where('as.attendance_date <=', $endDate);
        }
        if ($sessionType !== null && in_array(strtoupper($sessionType), self::SESSION_TYPES, true)) {
            $builder->where('as.session_type', strtoupper($sessionType));
        }

        $sessions = $builder->orderBy('as.attendance_date', 'DESC')->orderBy('as.id', 'DESC')->get()->getResultArray();
        $sessionIds = array_map(static fn($s) => (int)$s['id'], $sessions);

        $counts = array_fill_keys(self::STUDENT_STATUSES, 0);
        $totalAttendanceLogs = 0;

        if (!empty($sessionIds)) {
            $summary = $this->db->table('student_attendances')
                ->select('status, COUNT(*) as cnt')
                ->whereIn('session_id', $sessionIds)
                ->groupBy('status')
                ->get()->getResultArray();

            foreach ($summary as $row) {
                $st = strtoupper((string)$row['status']);
                if (isset($counts[$st])) {
                    $counts[$st] = (int)$row['cnt'];
                }
                $totalAttendanceLogs += (int)$row['cnt'];
            }
        }

        $attendanceRate = $totalAttendanceLogs > 0
            ? round((($counts['HADIR'] + $counts['TERLAMBAT']) / $totalAttendanceLogs) * 100, 1)
            : 100.0;

        // Daily trend data (last 14 active days or date range)
        $dailyTrends = [];
        if (!empty($sessionIds)) {
            $trends = $this->db->table('attendance_sessions as')
                ->select('as.attendance_date, sa.status, COUNT(sa.id) as cnt')
                ->join('student_attendances sa', 'sa.session_id = as.id')
                ->whereIn('as.id', $sessionIds)
                ->groupBy('as.attendance_date, sa.status')
                ->orderBy('as.attendance_date', 'ASC')
                ->get()->getResultArray();

            foreach ($trends as $tr) {
                $d = $tr['attendance_date'];
                if (!isset($dailyTrends[$d])) {
                    $dailyTrends[$d] = array_fill_keys(self::STUDENT_STATUSES, 0) + ['date' => $d, 'total' => 0];
                }
                $st = strtoupper((string)$tr['status']);
                if (isset($dailyTrends[$d][$st])) {
                    $dailyTrends[$d][$st] += (int)$tr['cnt'];
                }
                $dailyTrends[$d]['total'] += (int)$tr['cnt'];
            }
        }

        // Students with high absenteeism (> 2 Alpa or Sakit/Izin)
        $problematicStudents = [];
        if (!empty($sessionIds)) {
            $problematic = $this->db->table('student_attendances sa')
                ->select('sa.student_id, es.student_number, es.full_name, c.name as classroom_name,
                          SUM(CASE WHEN sa.status = "ALPA" THEN 1 ELSE 0 END) as total_alpa,
                          SUM(CASE WHEN sa.status = "SAKIT" THEN 1 ELSE 0 END) as total_sakit,
                          SUM(CASE WHEN sa.status = "IZIN" THEN 1 ELSE 0 END) as total_izin,
                          COUNT(sa.id) as total_recorded')
                ->join('elective_students es', 'es.id = sa.student_id')
                ->join('classrooms c', 'c.id = es.classroom_id')
                ->whereIn('sa.session_id', $sessionIds)
                ->groupBy('sa.student_id')
                ->having('total_alpa > 0 OR total_sakit >= 3 OR total_izin >= 3')
                ->orderBy('total_alpa', 'DESC')
                ->get()->getResultArray();

            $problematicStudents = $problematic;
        }

        return [
            'sessions'             => $sessions,
            'total_sessions'       => count($sessions),
            'total_logs'           => $totalAttendanceLogs,
            'counts'               => $counts,
            'attendance_rate'      => $attendanceRate,
            'daily_trends'         => array_values($dailyTrends),
            'problematic_students' => $problematicStudents,
        ];
    }
}
