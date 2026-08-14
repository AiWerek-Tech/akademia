<?php

namespace App\Services;

use Config\Database;
use App\Services\UnitScopeService;

class ScheduleExportService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getGridForClassroom(int $scheduleVersionId, int $classroomId): array
    {
        $days = $this->db->table('schedule_days sd')
            ->select('sd.*')
            ->join('schedule_day_slots sds', 'sds.day_id = sd.id')
            ->where('sds.schedule_version_id', $scheduleVersionId)
            ->where('sd.is_school_day', 1)
            ->groupBy('sd.id')
            ->orderBy('sd.day_of_week', 'ASC')
            ->get()->getResultArray();

        $slots = $this->db->table('schedule_day_slots')
            ->where('schedule_version_id', $scheduleVersionId)
            ->orderBy('slot_number', 'ASC')
            ->get()->getResultArray();

        $entries = $this->db->table('schedule_entries se')
            ->select('se.*, t.full_name as teacher_name, t.teacher_initial, t.color_code, s.name as subject_name, s.code as subject_code, s.color_label, r.name as room_name')
            ->join('teachers t', 't.id = se.teacher_id', 'left')
            ->join('subjects s', 's.id = se.subject_id', 'left')
            ->join('rooms r', 'r.id = se.room_id', 'left')
            ->where('se.schedule_version_id', $scheduleVersionId)
            ->where('se.classroom_id', $classroomId)
            ->get()->getResultArray();

        $entryMap = [];
        foreach ($entries as $e) {
            $entryMap[(int)$e['day_slot_id']] = $e;
        }

        $fixedList = $this->db->table('schedule_fixed_activities sfa')
            ->select('sfa.*, sra.color_label, sra.duration_mode, sra.duration_minutes, sra.placement_zone')
            ->join('school_routine_activities sra', 'sra.code = SUBSTRING_INDEX(sfa.description, " | ", 1)', 'left')
            ->where('sfa.schedule_version_id', $scheduleVersionId)
            ->where('sfa.classroom_id', $classroomId)
            ->get()->getResultArray();
        $fixedMap = [];
        foreach ($fixedList as $f) {
            $fixedMap[(int)$f['day_slot_id']] = $f;
        }

        $version = $this->db->table('schedule_versions')->where('id', $scheduleVersionId)->get()->getRowArray();
        $unitId = (int)($version['unit_id'] ?? 0);
        $routineModel = new \App\Models\RoutineActivityModel();
        $routines = $routineModel->where('is_active', 1)
            ->groupStart()
                ->where('unit_id', $unitId)
                ->orWhere('unit_id IS NULL')
            ->groupEnd()
            ->findAll();

        return [
            'schedule_version_id' => $scheduleVersionId,
            'classroom_id'        => $classroomId,
            'days'                => $days,
            'slots'               => $slots,
            'entry_map'           => $entryMap,
            'fixed_map'           => $fixedMap,
            'routine_activities'  => $routines,
            'teachers'            => $this->getTeacherLegend([$scheduleVersionId]),
            'subjects'            => $this->getSubjectLegend([$scheduleVersionId], [$classroomId]),
            'curriculum_subjects' => $this->getCurriculumSubjectsForClassroom($scheduleVersionId, $classroomId),
        ];
    }

    public function getCurriculumSubjectsForClassroom(int $scheduleVersionId, int $classroomId): array
    {
        $entries = $this->db->table('schedule_entries se')
            ->select('s.id as subject_id, s.code as subject_code, s.name as subject_name, s.category, t.id as teacher_id, t.full_name as teacher_name, t.title_prefix, t.degree_suffix, t.teacher_initial, COUNT(se.id) as total_jp')
            ->join('subjects s', 's.id = se.subject_id', 'inner')
            ->join('teachers t', 't.id = se.teacher_id', 'left')
            ->where('se.schedule_version_id', $scheduleVersionId)
            ->where('se.classroom_id', $classroomId)
            ->where('s.deleted_at IS NULL')
            ->groupBy('s.id, t.id')
            ->orderBy('s.category', 'ASC')
            ->orderBy('s.sort_order', 'ASC')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        $categorized = [
            'WAJIB'        => [],
            'MUATAN_LOKAL' => [],
            'PILIHAN'      => [],
            'LAINNYA'      => [],
        ];

        foreach ($entries as $item) {
            $rawName = rtrim(trim((string)$item['teacher_name']), ',');
            $prefix = trim((string)($item['title_prefix'] ?? ''));
            $suffix = trim((string)($item['degree_suffix'] ?? ''));
            if (!empty($suffix) && !str_contains($rawName, $suffix)) {
                $rawName .= ', ' . $suffix;
            }
            if (!empty($prefix) && !str_contains($rawName, $prefix)) {
                $rawName = $prefix . ' ' . $rawName;
            }
            $item['formatted_teacher_name'] = $rawName;

            if (empty($item['teacher_initial'])) {
                $parts = preg_split('/\s+/', trim((string)$item['teacher_name']));
                $item['teacher_initial'] = strtoupper(substr($parts[0] ?? '', 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
            }

            $cat = strtoupper(trim((string)($item['category'] ?? 'WAJIB')));
            if (isset($categorized[$cat])) {
                $categorized[$cat][] = $item;
            } else {
                $categorized['LAINNYA'][] = $item;
            }
        }

        return $categorized;
    }

    public function getGridForTeacher(int $scheduleVersionId, int $teacherId): array
    {
        $version = $this->db->table('schedule_versions')->where('id', $scheduleVersionId)->get()->getRowArray();
        if (!$version) throw new \RuntimeException('Versi jadwal tidak ditemukan.');
        $versionRows = $this->db->table('schedule_versions')->select('id,unit_id,is_active,revision_number')
            ->where('academic_period_id', (int)$version['academic_period_id'])->where('workflow_status !=', 'ARCHIVED')
            ->orderBy('is_active','DESC')->orderBy('revision_number','DESC')->orderBy('id','DESC')->get()->getResultArray();
        $latestByUnit=[];
        foreach($versionRows as $row){$unitId=(int)$row['unit_id'];if(!isset($latestByUnit[$unitId]))$latestByUnit[$unitId]=(int)$row['id'];}
        $versionIds=array_values($latestByUnit) ?: [$scheduleVersionId];
        $entries = $this->db->table('schedule_entries se')
            ->select('se.*, c.name as class_name, s.name as subject_name, s.code as subject_code, s.color_label, r.name as room_name, sds.slot_number, sds.start_time, sds.end_time, sd.day_name, sd.day_of_week')
            ->join('schedule_day_slots sds', 'sds.id = se.day_slot_id')
            ->join('schedule_days sd', 'sd.id = sds.day_id')
            ->join('classrooms c', 'c.id = se.classroom_id')
            ->join('subjects s', 's.id = se.subject_id', 'left')
            ->join('rooms r', 'r.id = se.room_id', 'left')
            ->whereIn('se.schedule_version_id', $versionIds)
            ->groupStart()
                ->where('se.teacher_id', $teacherId)
                ->orWhere('se.second_teacher_id', $teacherId)
            ->groupEnd()->orderBy('sd.day_of_week','ASC')->orderBy('sds.slot_number','ASC')
            ->get()->getResultArray();

        return [
            'schedule_version_id' => $scheduleVersionId,
            'schedule_version_ids'=> $versionIds,
            'teacher_id'          => $teacherId,
            'entries'             => $entries,
            'subjects'            => $this->getSubjectLegend($versionIds, null, $teacherId),
        ];
    }

    public function getGridForUnit(int $scheduleVersionId, int $unitId): array
    {
        $version = $this->db->table('schedule_versions')->where('id', $scheduleVersionId)->get()->getRowArray();
        $classrooms = $this->db->table('classrooms')
            ->where('unit_id', $unitId)
            ->where('academic_period_id', $version['academic_period_id'])
            ->where('is_active', 1)
            ->orderBy('grade_level_id', 'ASC')
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        $days = $this->db->table('schedule_days sd')
            ->select('sd.*')
            ->join('schedule_day_slots sds', 'sds.day_id = sd.id')
            ->where('sds.schedule_version_id', $scheduleVersionId)
            ->where('sd.is_school_day', 1)
            ->groupBy('sd.id')
            ->orderBy('sd.day_of_week', 'ASC')
            ->get()->getResultArray();

        $slots = $this->db->table('schedule_day_slots')
            ->where('schedule_version_id', $scheduleVersionId)
            ->orderBy('slot_number', 'ASC')
            ->get()->getResultArray();

        $entries = $this->db->table('schedule_entries se')
            ->select('se.*, t.full_name as teacher_name, t.teacher_initial, t.color_code, t.employee_number, s.name as subject_name, s.code as subject_code, r.name as room_name')
            ->join('teachers t', 't.id = se.teacher_id', 'left')
            ->join('subjects s', 's.id = se.subject_id', 'left')
            ->join('rooms r', 'r.id = se.room_id', 'left')
            ->where('se.schedule_version_id', $scheduleVersionId)
            ->get()->getResultArray();

        $matrix = [];
        foreach ($entries as $e) {
            $matrix[(int)$e['classroom_id']][(int)$e['day_slot_id']] = $e;
        }

        $fixedList = $this->db->table('schedule_fixed_activities sfa')
            ->select('sfa.*, sra.color_label, sra.duration_mode, sra.duration_minutes, sra.placement_zone')
            ->join('school_routine_activities sra', 'sra.code = SUBSTRING_INDEX(sfa.description, " | ", 1)', 'left')
            ->where('sfa.schedule_version_id', $scheduleVersionId)
            ->get()->getResultArray();
        $fixedMatrix = [];
        foreach ($fixedList as $f) {
            $fixedMatrix[(int)$f['classroom_id']][(int)$f['day_slot_id']] = $f;
        }

        $routineModel = new \App\Models\RoutineActivityModel();
        $routines = $routineModel->where('is_active', 1)
            ->groupStart()
                ->where('unit_id', $unitId)
                ->orWhere('unit_id IS NULL')
            ->groupEnd()
            ->findAll();

        return [
            'schedule_version_id' => $scheduleVersionId,
            'unit_id'             => $unitId,
            'classrooms'          => $classrooms,
            'days'                => $days,
            'slots'               => $slots,
            'matrix'              => $matrix,
            'fixed_matrix'        => $fixedMatrix,
            'routine_activities'  => $routines,
            'teachers'            => $this->getTeacherLegend([$scheduleVersionId]),
            'subjects'            => $this->getSubjectLegend([$scheduleVersionId]),
        ];
    }

    public function getGridForMultiUnit(int $scheduleVersionId): array
    {
        $version = $this->db->table('schedule_versions')->where('id', $scheduleVersionId)->get()->getRowArray();
        if (!$version) {
            throw new \RuntimeException('Versi jadwal tidak ditemukan.');
        }
        $periodId = (int)$version['academic_period_id'];
        $accessibleUnitIds = UnitScopeService::accessibleUnitIds();

        $classroomsQuery = $this->db->table('classrooms c')
            ->select('c.*, su.name as unit_name, su.code as unit_code')
            ->join('school_units su', 'su.id = c.unit_id', 'left')
            ->where('c.academic_period_id', $periodId)
            ->where('c.is_active', 1);
        if ($accessibleUnitIds !== []) {
            $classroomsQuery->whereIn('c.unit_id', $accessibleUnitIds);
        } elseif (!function_exists('is_super_admin') || !is_super_admin()) {
            $classroomsQuery->where('c.id', 0);
        }
        $classrooms = $classroomsQuery
            ->orderBy('su.id', 'ASC')->orderBy('c.grade_level_id', 'ASC')->orderBy('c.name', 'ASC')
            ->get()->getResultArray();

        $versionQuery = $this->db->table('schedule_versions')
            ->select('id, unit_id, is_active, revision_number')
            ->where('academic_period_id', $periodId)
            ->where('workflow_status !=', 'ARCHIVED')
            ->orderBy('is_active', 'DESC')->orderBy('revision_number', 'DESC')->orderBy('id', 'DESC');
        if ($accessibleUnitIds !== []) {
            $versionQuery->whereIn('unit_id', $accessibleUnitIds);
        } elseif (!function_exists('is_super_admin') || !is_super_admin()) {
            $versionQuery->where('id', 0);
        }
        $versionRows = $versionQuery->get()->getResultArray();
        $selectedVersionsByUnit = [];
        foreach ($versionRows as $versionRow) {
            $unitKey = (int) $versionRow['unit_id'];
            if (!isset($selectedVersionsByUnit[$unitKey])) {
                $selectedVersionsByUnit[$unitKey] = $versionRow;
            }
        }
        $vIds = array_values(array_map('intval', array_column($selectedVersionsByUnit, 'id')));
        if (empty($vIds)) {
            $vIds = [$scheduleVersionId];
        }

        $days = $this->db->table('schedule_days sd')
            ->select('sd.*')
            ->join('schedule_day_slots sds', 'sds.day_id = sd.id')
            ->whereIn('sds.schedule_version_id', $vIds)
            ->where('sd.is_school_day', 1)
            ->groupBy('sd.id')
            ->orderBy('sd.day_of_week', 'ASC')
            ->get()->getResultArray();

        $slots = $this->db->table('schedule_day_slots')
            ->whereIn('schedule_version_id', $vIds)
            ->orderBy('slot_number', 'ASC')
            ->get()->getResultArray();

        $entries = $this->db->table('schedule_entries se')
            ->select('se.*, t.full_name as teacher_name, t.teacher_initial, t.color_code, s.name as subject_name, s.code as subject_code, r.name as room_name')
            ->join('teachers t', 't.id = se.teacher_id', 'left')
            ->join('subjects s', 's.id = se.subject_id', 'left')
            ->join('rooms r', 'r.id = se.room_id', 'left')
            ->whereIn('se.schedule_version_id', $vIds)
            ->get()->getResultArray();

        $matrix = [];
        foreach ($entries as $e) {
            $matrix[(int)$e['classroom_id']][(int)$e['day_slot_id']] = $e;
        }

        $fixedList = $this->db->table('schedule_fixed_activities sfa')
            ->select('sfa.*, sra.color_label, sra.duration_mode, sra.duration_minutes, sra.placement_zone')
            ->join('school_routine_activities sra', 'sra.code = SUBSTRING_INDEX(sfa.description, " | ", 1)', 'left')
            ->whereIn('sfa.schedule_version_id', $vIds)
            ->get()->getResultArray();
        $fixedMatrix = [];
        foreach ($fixedList as $f) {
            $fixedMatrix[(int)$f['classroom_id']][(int)$f['day_slot_id']] = $f;
        }

        $routineModel = new \App\Models\RoutineActivityModel();
        $routineQuery = $routineModel->where('is_active', 1);
        if ($accessibleUnitIds !== []) {
            $routineQuery->groupStart()->whereIn('unit_id', $accessibleUnitIds)->orWhere('unit_id IS NULL')->groupEnd();
        } elseif (!function_exists('is_super_admin') || !is_super_admin()) {
            $routineQuery->where('id', 0);
        }
        $routines = $routineQuery->findAll();

        return [
            'period_id'          => $periodId,
            'version_ids_by_unit'=> array_map('intval', array_column($selectedVersionsByUnit, 'id', 'unit_id')),
            'classrooms'         => $classrooms,
            'days'               => $days,
            'slots'              => $slots,
            'matrix'             => $matrix,
            'fixed_matrix'       => $fixedMatrix,
            'entries'            => $entries,
            'fixed_entries'      => $fixedList,
            'routine_activities' => $routines,
            'teachers'           => $this->getTeacherLegend($vIds),
            'subjects'           => $this->getSubjectLegend($vIds),
        ];
    }

    private function getTeacherLegend(array $scheduleVersionIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $scheduleVersionIds)));
        if ($ids === []) {
            return [];
        }

        return $this->db->table('teachers t')
            ->select('t.*, MIN(CASE
                WHEN adt.code = "HEADMASTER" THEN 1
                WHEN adt.code IN ("CHAPLAIN", "COUNSELING_COORDINATOR") THEN 2
                WHEN adt.code = "VICE_PRINCIPAL" THEN 3
                WHEN adt.code = "TREASURER" THEN 4
                WHEN adt.code = "HOMEROOM_TEACHER" THEN 5
                WHEN adt.code IN ("LAB_HEAD", "LIBRARY_HEAD") THEN 6
                WHEN adt.sort_order IS NOT NULL THEN 20 + adt.sort_order
                ELSE 999
            END) AS duty_rank, GROUP_CONCAT(DISTINCT COALESCE(tad.title_override, adt.name) ORDER BY adt.sort_order SEPARATOR ", ") AS duty_titles', false)
            ->join('teacher_additional_duties tad', 'tad.teacher_id = t.id AND tad.deleted_at IS NULL AND tad.status = "ACTIVE"', 'left')
            ->join('additional_duty_types adt', 'adt.id = tad.duty_type_id', 'left')
            ->join('schedule_entries se', 'se.teacher_id = t.id OR se.second_teacher_id = t.id', 'inner')
            ->whereIn('se.schedule_version_id', $ids)
            ->where('t.is_active', 1)
            ->where('t.deleted_at IS NULL')
            ->groupBy('t.id')
            ->orderBy('duty_rank', 'ASC')
            ->orderBy('t.primary_unit_id', 'ASC')
            ->orderBy('t.id', 'ASC')
            ->get()->getResultArray();
    }

    private function getSubjectLegend(array $scheduleVersionIds, ?array $classroomIds = null, ?int $teacherId = null): array
    {
        $ids = array_values(array_unique(array_map('intval', $scheduleVersionIds)));
        if ($ids === []) {
            return [];
        }

        $builder = $this->db->table('subjects s')
            ->distinct()
            ->select('s.id, s.code, s.short_name, s.name, s.sort_order')
            ->join('schedule_entries se', 'se.subject_id = s.id', 'inner')
            ->whereIn('se.schedule_version_id', $ids)
            ->where('s.deleted_at IS NULL');

        if ($classroomIds !== null) {
            $classIds = array_values(array_unique(array_map('intval', $classroomIds)));
            if ($classIds === []) {
                return [];
            }
            $builder->whereIn('se.classroom_id', $classIds);
        }

        if ($teacherId !== null) {
            $builder->groupStart()
                ->where('se.teacher_id', $teacherId)
                ->orWhere('se.second_teacher_id', $teacherId)
                ->groupEnd();
        }

        return $builder
            ->orderBy('s.sort_order', 'ASC')
            ->orderBy('s.code', 'ASC')
            ->get()->getResultArray();
    }
}
