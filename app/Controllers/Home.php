<?php

namespace App\Controllers;

use Config\Database;
use App\Services\UnitScopeService;
use App\Services\WaliKelasAccessService;
use App\Services\TeacherScheduleReadService;
use App\Services\PortalUnitScopeService;
use App\Services\AcademicCalendarGeneratorService;

class Home extends BaseController
{
    public function index()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        $db = Database::connect();

        $roleCode = (string) session()->get('role_code');
        $roleCodes = (array) (session()->get('all_role_codes') ?? [$roleCode]);
        $dashboardMode = 'general';
        if (array_intersect($roleCodes, ['super_admin', 'superadmin', 'admin_smp', 'admin_sma'])) {
            $dashboardMode = 'administration';
        } elseif (in_array('wakasek_kurikulum', $roleCodes, true)) {
            $dashboardMode = 'academic';
        } elseif (array_intersect($roleCodes, ['kepala_sekolah', 'viewer_yayasan'])) {
            $dashboardMode = 'executive';
        } elseif (array_intersect($roleCodes, ['guru', 'wali_kelas'])) {
            $dashboardMode = 'teacher';
        } elseif (in_array('siswa', $roleCodes, true)) {
            $dashboardMode = 'student';
        } elseif (in_array('tata_usaha', $roleCodes, true)) {
            $dashboardMode = 'operations';
        }
        $buildAdministrativeSummary = in_array($dashboardMode, ['administration', 'academic', 'executive', 'operations'], true);
        
        $teacherId = (int) (get_teacher_id() ?? 0);
        $unitScope = PortalUnitScopeService::resolve(
            (string) $this->request->getGet('unit_scope'),
            $dashboardMode === 'teacher' && $teacherId > 0 ? $teacherId : null
        );
        $unitIds = $unitScope['unitIds'];
        $totalUnits = count($unitIds);
        $totalUsers = 0;
        if ($buildAdministrativeSummary && $unitIds !== []) {
            $totalUsers = $db->table('users u')->distinct()->select('u.id')
                ->join('user_unit_access uua', 'uua.user_id = u.id')
                ->whereIn('uua.unit_id', $unitIds)
                ->where('u.is_active', 1)->where('u.deleted_at IS NULL')
                ->countAllResults();
        }

        $activePeriod = get_active_period();
        $activePeriodStr = $activePeriod 
            ? "T.A " . $activePeriod['year_name'] . " (" . ($activePeriod['semester_number'] == 1 ? 'Ganjil' : 'Genap') . ")" 
            : 'Belum ditentukan';

        $masterCounts = ['teachers' => 0, 'subjects' => 0, 'classrooms' => 0, 'rooms' => 0];
        if ($buildAdministrativeSummary && $unitIds !== []) {
            $masterCounts['teachers'] = $db->table('teachers t')
                ->select('t.id')->distinct()
                ->join('teacher_unit_assignments tua', 'tua.teacher_id = t.id', 'left')
                ->where('t.is_active', 1)->where('t.deleted_at IS NULL')
                ->groupStart()->whereIn('t.primary_unit_id', $unitIds)->orWhereIn('tua.unit_id', $unitIds)->groupEnd()
                ->countAllResults();
            $masterCounts['subjects'] = $db->table('subject_unit_availability sua')
                ->select('sua.subject_id')->distinct()->join('subjects s', 's.id = sua.subject_id')
                ->whereIn('sua.unit_id', $unitIds)->where('sua.is_available', 1)->where('s.is_active', 1)
                ->where('s.deleted_at IS NULL')->countAllResults();
            $masterCounts['classrooms'] = $db->table('classrooms')->whereIn('unit_id', $unitIds)
                ->where('is_active', 1)->where('deleted_at IS NULL')->countAllResults();
            $masterCounts['rooms'] = $db->table('rooms')->groupStart()->whereIn('unit_id', $unitIds)->orWhere('shared_between_units', 1)->groupEnd()
                ->where('is_active', 1)->where('deleted_at IS NULL')->countAllResults();
        }

        $readiness = [
            ['label' => 'Unit sekolah dapat diakses', 'ready' => $totalUnits > 0, 'href' => 'settings/units'],
            ['label' => 'Periode akademik aktif', 'ready' => $activePeriod !== null, 'href' => 'academic-periods'],
            ['label' => 'Guru aktif tersedia', 'ready' => $masterCounts['teachers'] > 0, 'href' => 'teachers'],
            ['label' => 'Mata pelajaran tersedia', 'ready' => $masterCounts['subjects'] > 0, 'href' => 'subjects'],
            ['label' => 'Rombel aktif tersedia', 'ready' => $masterCounts['classrooms'] > 0, 'href' => 'classrooms'],
            ['label' => 'Ruangan aktif tersedia', 'ready' => $masterCounts['rooms'] > 0, 'href' => 'rooms'],
        ];
        $planningCounts = ['curricula' => 0, 'assignments' => 0, 'schedules' => 0];
        if ($buildAdministrativeSummary && $activePeriod && $unitIds !== []) {
            $planningCounts['curricula'] = $db->table('curriculum_versions cv')->distinct()
                ->select('cv.id')->join('curriculum_structures cs', 'cs.curriculum_version_id = cv.id')
                ->where('cv.academic_period_id', $activePeriod['id'])->whereIn('cs.unit_id', $unitIds)
                ->where('cs.deleted_at IS NULL')->countAllResults();
            $planningCounts['assignments'] = $db->table('assignment_versions av')->distinct()
                ->select('av.id')->join('teaching_assignments ta', 'ta.assignment_version_id = av.id')
                ->where('av.academic_period_id', $activePeriod['id'])->whereIn('ta.unit_id', $unitIds)
                ->where('ta.deleted_at IS NULL')->countAllResults();
            if ($db->fieldExists('unit_id', 'schedule_versions')) {
                $planningCounts['schedules'] = $db->table('schedule_versions')
                    ->where('academic_period_id', $activePeriod['id'])->whereIn('unit_id', $unitIds)
                    ->countAllResults();
            }
        }
        $readiness[] = ['label' => 'Struktur kurikulum tersedia', 'ready' => $planningCounts['curricula'] > 0, 'href' => 'curriculum'];
        $readiness[] = ['label' => 'Pembagian tugas tersedia', 'ready' => $planningCounts['assignments'] > 0, 'href' => 'assignments'];
        $readiness[] = ['label' => 'Jadwal pelajaran tersedia', 'ready' => $planningCounts['schedules'] > 0, 'href' => 'schedules'];
        $readinessDone = count(array_filter($readiness, static fn (array $item): bool => $item['ready']));

        $roleDashboard = [
            'code' => $roleCode,
            'label' => active_user_role(),
            'classroom' => null,
            'class_elective_count' => 0,
            'published_schedule_id' => null,
            'classroom_filtered_out' => false,
        ];
        if (is_wali_kelas()) {
            $classroomId = WaliKelasAccessService::classroomId();
            if ($classroomId) {
                $roleDashboard['classroom'] = $db->table('classrooms c')
                    ->select('c.id, c.code, c.name, c.unit_id')->where('c.id', $classroomId)->get()->getRowArray();
                if ($roleDashboard['classroom'] && !in_array((int) $roleDashboard['classroom']['unit_id'], $unitIds, true)) {
                    $roleDashboard['classroom_filtered_out'] = true;
                    $roleDashboard['classroom'] = null;
                }
                if ($activePeriod && $roleDashboard['classroom']) {
                    $roleDashboard['class_elective_count'] = $db->table('elective_students es')
                        ->join('elective_periods ep', 'ep.unit_id = es.unit_id AND ep.academic_year_id = es.academic_year_id AND ep.source_grade = es.current_grade')
                        ->where('es.classroom_id', $classroomId)->where('es.is_active', 1)
                        ->where('ep.status', 'SELECTION_OPEN')->countAllResults();
                    $schedule = $db->table('schedule_versions sv')
                        ->join('schedule_entries se', 'se.schedule_version_id = sv.id')
                        ->where('sv.unit_id', (int) ($roleDashboard['classroom']['unit_id'] ?? 0))
                        ->where('sv.academic_period_id', $activePeriod['id'])
                        ->whereIn('sv.workflow_status', ['APPROVED', 'LOCKED'])
                        ->where('se.classroom_id', $classroomId)->select('sv.id')->distinct()->get()->getRowArray();
                    $roleDashboard['published_schedule_id'] = $schedule ? (int) $schedule['id'] : null;
                }
            }
        }

        $workspace = [
            'mode' => $dashboardMode,
            'teacher' => null,
            'today_entries' => [],
            'calendar_today' => [],
            'calendar_notice' => null,
            'teaching_hours' => 0.0,
            'subject_count' => 0,
            'classroom_count' => 0,
            'duty_days' => 0,
            'elective_offering_count' => 0,
            'elective_student_count' => 0,
            'assignment_document_available' => false,
            'assignment_document_official' => false,
            'homeroom_student_count' => 0,
            'student' => null,
            'executive' => ['approved_assignments' => 0, 'published_schedules' => 0, 'open_conflicts' => 0],
        ];

        if ($teacherId > 0 && $activePeriod && $unitIds !== []) {
            $projection = (new TeacherScheduleReadService())->buildForUnits($teacherId, $unitIds, (int) $activePeriod['id']);
            $workspace['teacher'] = $projection['teacherInfo'];
            $today = (int) date('N');
            foreach (($projection['scheduleGrid'][$today] ?? []) as $slotEntries) {
                foreach ($slotEntries as $entry) {
                    $entryUnitId = (int) ($entry['unit_id'] ?? 0);
                    if ($entryUnitId <= 0 && count($unitIds) === 1) $entryUnitId = (int) $unitIds[0];
                    if (!array_key_exists($entryUnitId, $workspace['calendar_today'])) {
                        $workspace['calendar_today'][$entryUnitId] = (new AcademicCalendarGeneratorService())->getActiveDay((int) $activePeriod['academic_year_id'], $entryUnitId, date('Y-m-d'));
                    }
                    $calendarDay = $workspace['calendar_today'][$entryUnitId];
                    if (!$calendarDay || (int) $calendarDay['is_school_effective'] === 1) {
                        $workspace['today_entries'][] = $entry;
                    } elseif ($workspace['calendar_notice'] === null) {
                        $workspace['calendar_notice'] = $calendarDay['event_title'] ?: $calendarDay['day_type_code'];
                    }
                }
            }

            $personalAssignments = $projection['assignments'];
            $workspace['teaching_hours'] = array_sum(array_map('floatval', array_column($personalAssignments, 'assigned_weekly_hours')));
            $workspace['subject_count'] = count(array_unique(array_column($personalAssignments, 'subject_id')));
            $workspace['classroom_count'] = count(array_unique(array_column($personalAssignments, 'classroom_id')));
            $workspace['duty_days'] = $db->table('teacher_duty_schedules')
                ->where('academic_year_id', $activePeriod['academic_year_id'])->where('teacher_id', $teacherId)
                ->countAllResults();
            $workspace['elective_offering_count'] = $db->table('elective_offerings eo')
                ->join('elective_periods ep', 'ep.id = eo.elective_period_id')
                ->where('eo.teacher_id', $teacherId)
                ->where('ep.academic_year_id', $activePeriod['academic_year_id'])
                ->whereIn('ep.unit_id', $unitIds)
                ->whereIn('ep.status', ['PUBLISHED', 'SELECTION_OPEN', 'CLOSED', 'FINALIZED', 'LOCKED'])
                ->countAllResults();
            $electiveStudentRow = $db->table('student_elective_choices sec')
                ->select('COUNT(DISTINCT ses.student_id) AS total', false)
                ->join('student_elective_submissions ses', 'ses.id = sec.submission_id')
                ->join('elective_offerings eo', 'eo.id = sec.offering_id')
                ->join('elective_periods ep', 'ep.id = eo.elective_period_id AND ep.id = ses.elective_period_id')
                ->where('eo.teacher_id', $teacherId)
                ->where('ep.academic_year_id', $activePeriod['academic_year_id'])
                ->whereIn('ep.unit_id', $unitIds)
                ->whereIn('ep.status', ['PUBLISHED', 'SELECTION_OPEN', 'CLOSED', 'FINALIZED', 'LOCKED'])
                ->whereIn('ses.status', ['SUBMITTED', 'WAITING_CURRICULUM', 'APPROVED', 'FINALIZED', 'CHANGE_REQUESTED', 'CHANGED', 'NEEDS_REVISION'])
                ->get()->getRowArray();
            $workspace['elective_student_count'] = (int) ($electiveStudentRow['total'] ?? 0);
            $workspace['assignment_document_available'] = $db->table('assignment_versions av')
                ->join('teaching_assignments ta', 'ta.assignment_version_id = av.id')
                ->where('av.academic_period_id', $activePeriod['id'])->where('av.workflow_status !=', 'ARCHIVED')
                ->where('ta.teacher_id', $teacherId)->whereIn('ta.unit_id', $unitIds)
                ->where('ta.status', 'ACTIVE')->where('ta.deleted_at IS NULL')->countAllResults() > 0;
            $workspace['assignment_document_official'] = $db->table('assignment_versions av')
                ->join('teaching_assignments ta', 'ta.assignment_version_id = av.id')
                ->where('av.academic_period_id', $activePeriod['id'])->whereIn('av.workflow_status', ['APPROVED', 'LOCKED'])
                ->where('ta.teacher_id', $teacherId)->whereIn('ta.unit_id', $unitIds)
                ->where('ta.status', 'ACTIVE')->where('ta.deleted_at IS NULL')->countAllResults() > 0;
        }

        if (!empty($roleDashboard['classroom'])) {
            $workspace['homeroom_student_count'] = $db->table('elective_students')
                ->where('classroom_id', $roleDashboard['classroom']['id'])->where('is_active', 1)->countAllResults();
        }

        if (in_array('siswa', $roleCodes, true)) {
            $workspace['student'] = $db->table('elective_students es')
                ->select('es.*, c.name AS classroom_name')
                ->join('classrooms c', 'c.id = es.classroom_id', 'left')
                ->where('es.user_id', session()->get('user_id'))->whereIn('es.unit_id', $unitIds ?: [0])->where('es.is_active', 1)
                ->get()->getRowArray();
        }

        if ($buildAdministrativeSummary && $activePeriod && $unitIds !== []) {
            $workspace['executive']['approved_assignments'] = $db->table('assignment_versions av')
                ->join('teaching_assignments ta', 'ta.assignment_version_id = av.id')
                ->where('av.academic_period_id', $activePeriod['id'])->whereIn('ta.unit_id', $unitIds)
                ->whereIn('av.workflow_status', ['APPROVED', 'LOCKED'])->select('av.id')->distinct()->countAllResults();
            $workspace['executive']['published_schedules'] = $db->table('schedule_versions')
                ->where('academic_period_id', $activePeriod['id'])->whereIn('unit_id', $unitIds)
                ->whereIn('workflow_status', ['APPROVED', 'LOCKED'])->countAllResults();
            $workspace['executive']['open_conflicts'] = $db->table('schedule_conflicts sc')
                ->join('schedule_versions sv', 'sv.id = sc.schedule_version_id')
                ->where('sv.academic_period_id', $activePeriod['id'])->whereIn('sv.unit_id', $unitIds)
                ->where('sc.is_resolved', 0)->countAllResults();
        }

        $recentAudits = [];
        if (has_permission('audit.view') && !in_array($dashboardMode, ['teacher', 'student', 'general'], true)) {
            $recentAudits = $db->table('audit_logs al')
                ->select('al.*, u.username')
                ->join('users u', 'u.id = al.user_id', 'left')
                ->orderBy('al.created_at', 'DESC')
                ->limit(8)
                ->get()
                ->getResultArray();
        }

        $showAdministrativeDashboard = in_array($dashboardMode, ['administration', 'academic'], true)
            && (has_permission('units.view')
                || has_permission('teachers.view')
                || has_permission('subjects.view')
                || has_permission('curriculum.view')
                || has_permission('assignments.view')
                || has_permission('schedules.view')
                || has_permission('users.view')
                || has_permission('audit.view'));

        return view('dashboard', [
            'title'             => 'Dashboard Utama',
            'breadcrumb_active' => 'Dashboard',
            'totalUsers'        => $totalUsers,
            'totalUnits'        => $totalUnits,
            'activePeriodStr'   => $activePeriodStr,
            'recentAudits'      => $recentAudits,
            'activePeriod'      => $activePeriod,
            'masterCounts'      => $masterCounts,
            'readiness'         => $readiness,
            'readinessDone'     => $readinessDone,
            'planningCounts'    => $planningCounts,
            'roleDashboard'     => $roleDashboard,
            'showAdministrativeDashboard' => $showAdministrativeDashboard,
            'workspace'         => $workspace,
            'unitScope'         => $unitScope,
        ]);
    }
}
