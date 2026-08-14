<?php

namespace App\Services;

use Config\Database;

class AssignmentDocumentService
{
    public static function build(string $versionUuid, int $unitId, ?int $teacherId = null): array
    {
        $db = Database::connect();
        $version = $db->table('assignment_versions av')
            ->select('av.*, cv.name AS curriculum_name, cv.code AS curriculum_code, ap.name AS period_name, ap.semester_number, ay.name AS year_name, ap.start_date, ap.end_date')
            ->join('curriculum_versions cv', 'cv.id = av.curriculum_version_id')
            ->join('academic_periods ap', 'ap.id = av.academic_period_id')
            ->join('academic_years ay', 'ay.id = ap.academic_year_id')
            ->where('av.uuid', $versionUuid)->get()->getRowArray();
        if (! $version) {
            throw new \RuntimeException('Versi pembagian tugas tidak ditemukan.');
        }

        UnitScopeService::assertUnit($unitId);
        $unit = $db->table('school_units')->where('id', $unitId)->where('deleted_at IS NULL')->get()->getRowArray();
        if (! $unit) {
            throw new \RuntimeException('Unit sekolah tidak ditemukan.');
        }

        $assignmentBuilder = $db->table('teaching_assignments ta')
            ->select('ta.teacher_id, ta.assigned_weekly_hours, ta.workload_weekly_hours, t.full_name, t.nip, t.employee_number, c.name AS classroom_name, s.name AS subject_name, s.code AS subject_code')
            ->join('teachers t', 't.id = ta.teacher_id')
            ->join('classrooms c', 'c.id = ta.classroom_id')
            ->join('subjects s', 's.id = ta.subject_id')
            ->where('ta.assignment_version_id', $version['id'])->where('ta.unit_id', $unitId)
            ->where('ta.status', 'ACTIVE')->where('ta.deleted_at IS NULL')
            ->orderBy('t.full_name')->orderBy('s.name')->orderBy('c.name');
        if ($teacherId !== null) {
            $assignmentBuilder->where('ta.teacher_id', $teacherId);
        }
        $assignments = $assignmentBuilder->get()->getResultArray();

        $dutyBuilder = $db->table('teacher_additional_duties tad')
            ->select('tad.teacher_id, COALESCE(tad.title_override, adt.name) AS duty_name, tad.workload_hours')
            ->join('additional_duty_types adt', 'adt.id = tad.duty_type_id')
            ->where('tad.assignment_version_id', $version['id'])->where('tad.status', 'ACTIVE')
            ->where('tad.deleted_at IS NULL')
            ->groupStart()->where('tad.unit_id', $unitId)->orWhere('tad.unit_id IS NULL')->groupEnd();
        if ($teacherId !== null) {
            $dutyBuilder->where('tad.teacher_id', $teacherId);
        }
        $duties = $dutyBuilder->get()->getResultArray();

        $teachers = [];
        foreach ($assignments as $assignment) {
            $id = (int) $assignment['teacher_id'];
            if (! isset($teachers[$id])) {
                $teachers[$id] = [
                    'id' => $id,
                    'full_name' => $assignment['full_name'],
                    'identifier' => $assignment['nip'] ?: ($assignment['employee_number'] ?: '-'),
                    'assignments' => [],
                    'duties' => [],
                    'assigned_hours' => 0.0,
                    'teaching_hours' => 0.0,
                    'duty_hours' => 0.0,
                    'total_hours' => 0.0,
                ];
            }
            $teachers[$id]['assignments'][] = $assignment;
            $teachers[$id]['assigned_hours'] += (float) $assignment['assigned_weekly_hours'];
            $teachers[$id]['teaching_hours'] += (float) $assignment['workload_weekly_hours'];
        }
        foreach ($duties as $duty) {
            $id = (int) $duty['teacher_id'];
            if (! isset($teachers[$id])) {
                $teacher = $db->table('teachers')->where('id', $id)->get()->getRowArray();
                if (! $teacher) {
                    continue;
                }
                $teachers[$id] = [
                    'id' => $id, 'full_name' => $teacher['full_name'],
                    'identifier' => $teacher['nip'] ?: ($teacher['employee_number'] ?: '-'),
                    'assignments' => [], 'duties' => [], 'assigned_hours' => 0.0, 'teaching_hours' => 0.0,
                    'duty_hours' => 0.0, 'total_hours' => 0.0,
                ];
            }
            $teachers[$id]['duties'][] = $duty;
            $teachers[$id]['duty_hours'] += (float) ($duty['workload_hours'] ?? 0);
        }
        foreach ($teachers as &$teacher) {
            $teacher['total_hours'] = $teacher['teaching_hours'] + $teacher['duty_hours'];
        }
        unset($teacher);
        uasort($teachers, static fn (array $a, array $b): int => strcasecmp($a['full_name'], $b['full_name']));

        $prefix = trim((string) ($unit['decree_prefix'] ?? ''));
        $documentNumber = ($prefix !== '' ? $prefix : 'SK-PEMBAGIAN-TUGAS') . '/' . $version['year_name'] . '/' . $version['code'];

        return [
            'version' => $version,
            'unit' => $unit,
            'teachers' => array_values($teachers),
            'document_number' => $documentNumber,
            'issued_date' => date('Y-m-d'),
        ];
    }
}
