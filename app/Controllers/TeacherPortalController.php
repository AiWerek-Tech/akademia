<?php

namespace App\Controllers;

use App\Services\AssignmentDocumentService;
use Config\Database;
use App\Services\TeacherScheduleReadService;
use App\Services\TeacherWorkloadCalculationService;
use App\Services\UnitScopeService;
use App\Services\WaliKelasAccessService;
use App\Services\PortalUnitScopeService;

class TeacherPortalController extends BaseController
{
    public function schedule()
    {
        if (!has_permission('teacher_schedule.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses ke Portal Guru.');
        }

        $ctx = $this->resolveTeacherContext();
        $teacherId = $ctx['teacherId'];
        $teacherInfo = $ctx['teacherInfo'];

        $activePeriod = get_active_period();
        $unitScope = PortalUnitScopeService::resolve((string) $this->request->getGet('unit_scope'), $teacherId ?: null);
        $scope = strtolower(trim((string) $this->request->getGet('scope'))) ?: 'teacher';
        $classroomId = ($requestedClassroomId = (int) $this->request->getGet('classroom_id')) > 0 ? $requestedClassroomId : null;
        $gradeLevelId = ($requestedGradeId = (int) $this->request->getGet('grade_level_id')) > 0 ? $requestedGradeId : null;

        // Personal permissions never imply access to every teacher/class in a unit.
        if (!has_permission('schedules.view')) {
            if ($scope === 'classroom' && has_permission('class_schedule.view') && is_wali_kelas()) {
                $classroomId = WaliKelasAccessService::classroomId();
                if (!$classroomId) {
                    $scope = 'teacher';
                }
            } else {
                $scope = 'teacher';
                $classroomId = null;
                $gradeLevelId = null;
            }
        }

        $projection = (new TeacherScheduleReadService())->buildForUnits(
            (int) $teacherId,
            $unitScope['unitIds'],
            (int) ($activePeriod['id'] ?? 0),
            $scope,
            $classroomId,
            $gradeLevelId
        );

        return view('teacher_portal/schedule', array_merge($projection, [
            'title'             => $ctx['isManagement'] ? 'Jadwal Mengajar Guru' : 'Jadwal Mengajar Saya',
            'breadcrumb_active' => 'Portal Guru',
            'activePeriod'      => $activePeriod,
            'unitScope'         => $unitScope,
            'teacherInfo'       => $teacherInfo,
            'isManagement'      => $ctx['isManagement'],
            'teachersList'      => $ctx['teachersList'],
            'currentTeacherId'  => $teacherId,
            'availableScopes'   => has_permission('schedules.view')
                ? ['teacher', 'classroom', 'grade', 'all']
                : (has_permission('class_schedule.view') && is_wali_kelas() ? ['teacher', 'classroom'] : ['teacher']),
        ]));
    }

    public function workload()
    {
        if (!has_permission('teacher_workload.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses ke Portal Guru.');
        }

        $db = Database::connect();
        $ctx = $this->resolveTeacherContext();
        $teacherId = $ctx['teacherId'];
        $teacherInfo = $ctx['teacherInfo'];
        $activePeriod = get_active_period();
        $unitScope = PortalUnitScopeService::resolve((string) $this->request->getGet('unit_scope'), $teacherId ?: null);

        $workloadSnapshot = null;
        $assignments = [];
        $duties = [];
        $assignmentVersion = null;
        $assignmentVersions = [];

        if ($teacherId > 0 && $activePeriod) {
            $workloadSnapshot = [
                'teaching_assigned_hours' => 0.0,
                'teaching_workload_hours' => 0.0,
                'additional_duty_hours' => 0.0,
                'total_workload_hours' => 0.0,
                'status' => 'NO_POLICY',
            ];
            foreach ($unitScope['selectedUnits'] as $selectedUnit) {
                $activeUnitId = (int) $selectedUnit['id'];
                $currentVersion = $this->findVisibleAssignmentVersion($teacherId, $activeUnitId, (int) $activePeriod['id']);
                if (!$currentVersion) {
                    continue;
                }
                $currentVersion['unit_id'] = $activeUnitId;
                $currentVersion['unit_code'] = $selectedUnit['code'];
                $currentVersion['unit_name'] = $selectedUnit['name'];
                $assignmentVersions[] = $currentVersion;
                $assignmentVersion ??= $currentVersion;
                // Calculate from current rows so the personal portal never shows
                // an obsolete snapshot after an assignment is edited.
                $metrics = TeacherWorkloadCalculationService::calculate(
                    $teacherId,
                    (int) $currentVersion['id'],
                    (int) $activePeriod['id'],
                    $activeUnitId
                );
                foreach (['teaching_assigned_hours', 'teaching_workload_hours', 'additional_duty_hours', 'total_workload_hours'] as $metric) {
                    $workloadSnapshot[$metric] += (float) ($metrics[$metric] ?? 0);
                }
                if (in_array((string) ($metrics['status'] ?? ''), ['UNDERLOAD', 'OVERLOAD'], true)) {
                    $workloadSnapshot['status'] = 'PERLU_PERHATIAN';
                }

                $unitAssignments = $db->table('teaching_assignments ta')
                ->select('ta.*, ta.assigned_weekly_hours AS weekly_hours, s.name as subject_name, c.name as classroom_name, av.uuid AS assignment_version_uuid, av.code AS assignment_version_code, av.workflow_status AS assignment_status, su.code AS unit_code, su.name AS unit_name')
                ->join('assignment_versions av', 'av.id = ta.assignment_version_id')
                ->join('subjects s', 's.id = ta.subject_id', 'left')
                ->join('classrooms c', 'c.id = ta.classroom_id', 'left')
                ->join('school_units su', 'su.id = ta.unit_id')
                ->where('ta.teacher_id', $teacherId)
                ->where('ta.unit_id', $activeUnitId)
                ->where('ta.assignment_version_id', (int) $currentVersion['id'])
                ->where('ta.status', 'ACTIVE')
                ->where('ta.deleted_at IS NULL')
                ->get()
                ->getResultArray();
                array_push($assignments, ...$unitAssignments);

                $unitDuties = $db->table('teacher_additional_duties tad')
                ->select('tad.*, COALESCE(tad.title_override, adt.name) AS duty_name, tad.workload_hours AS equivalent_hours, su.code AS unit_code, su.name AS unit_name')
                ->join('additional_duty_types adt', 'adt.id = tad.duty_type_id', 'left')
                ->join('school_units su', 'su.id = tad.unit_id', 'left')
                ->where('tad.teacher_id', $teacherId)
                ->where('tad.assignment_version_id', (int) $currentVersion['id'])
                ->where('tad.status', 'ACTIVE')
                ->groupStart()
                    ->where('tad.unit_id', $activeUnitId)
                    ->orWhere('tad.unit_id IS NULL')
                ->groupEnd()
                ->where('tad.deleted_at IS NULL')
                ->get()
                ->getResultArray();
                foreach ($unitDuties as &$unitDuty) {
                    $unitDuty['unit_code'] ??= $selectedUnit['code'];
                    $unitDuty['unit_name'] ??= $selectedUnit['name'];
                }
                unset($unitDuty);
                array_push($duties, ...$unitDuties);
            }
        }

        return view('teacher_portal/workload', [
            'title'             => $ctx['isManagement'] ? 'Beban Kerja Guru' : 'Beban Kerja Saya',
            'breadcrumb_active' => 'Portal Guru',
            'teacherInfo'       => $teacherInfo,
            'workloadSnapshot'  => $workloadSnapshot,
            'assignments'       => $assignments,
            'duties'            => $duties,
            'activePeriod'      => $activePeriod,
            'assignmentVersion' => $assignmentVersion,
            'assignmentVersions' => $assignmentVersions,
            'unitScope'         => $unitScope,
            'isManagement'      => $ctx['isManagement'],
            'teachersList'      => $ctx['teachersList'],
            'currentTeacherId'  => $teacherId,
            'assignmentDocumentAvailable' => $assignmentVersions !== [],
        ]);
    }

    public function assignmentDocument()
    {
        if (!has_permission('teacher_assignment_document.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses ke SK pembagian tugas pribadi.');
        }

        $ctx = $this->resolveTeacherContext();
        $teacherId = $ctx['teacherId'];
        $period = get_active_period();
        if (!$teacherId || !$period) {
            return redirect()->to('/portal/workload')->with('error', 'Profil guru atau periode aktif belum tersedia.');
        }

        $unitScope = PortalUnitScopeService::resolve((string) $this->request->getGet('unit_scope'), $teacherId ?: null);
        $documents = [];
        foreach ($unitScope['selectedUnits'] as $unit) {
            $unitId = (int) $unit['id'];
            $version = $this->findVisibleAssignmentVersion($teacherId, $unitId, (int) $period['id']);
            if (!$version) {
                continue;
            }
            try {
                UnitScopeService::assertTeacherInUnit($teacherId, $unitId);
                $document = AssignmentDocumentService::build((string) $version['uuid'], $unitId, $teacherId);
                if ($document['teachers'] !== []) {
                    $document['is_official'] = in_array(strtoupper((string) $version['workflow_status']), ['APPROVED', 'LOCKED'], true);
                    $documents[] = $document;
                }
            } catch (\Throwable $e) {
                return redirect()->to('/portal/workload')->with('error', $e->getMessage());
            }
        }
        if ($documents === []) {
            return redirect()->to('/portal/workload')->with('error', 'Data pembagian tugas belum tersedia untuk periode aktif.');
        }
        return view('assignments/documents/teacher', [
            'document' => $documents[0],
            'documents' => $documents,
            'personalPortal' => true,
            'isOfficial' => count(array_filter($documents, static fn (array $doc): bool => !$doc['is_official'])) === 0,
            'isManagement' => $ctx['isManagement'],
            'teachersList' => $ctx['teachersList'],
            'currentTeacherId' => $teacherId,
        ]);
    }

    public function dutySchedule()
    {
        if (!has_permission('teacher_duty_schedule.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses ke jadwal piket pribadi.');
        }

        $db = Database::connect();
        $ctx = $this->resolveTeacherContext();
        $teacherId = $ctx['teacherId'];
        $teacherInfo = $ctx['teacherInfo'];
        $period = get_active_period();
        $unitScope = PortalUnitScopeService::resolve((string) $this->request->getGet('unit_scope'), $teacherId ?: null);

        $duties = [];
        $academicYear = null;
        if ($teacherInfo && $period) {
            $academicYear = $db->table('academic_years')->where('id', $period['academic_year_id'])->get()->getRowArray();
            $duties = $db->table('teacher_duty_schedules')
                ->where('academic_year_id', $period['academic_year_id'])
                ->where('teacher_id', $teacherId)
                ->orderBy('day_of_week', 'ASC')
                ->get()->getResultArray();
        }

        return view('teacher_portal/duty_schedule', [
            'title'             => $ctx['isManagement'] ? 'Jadwal Piket Guru' : 'Jadwal Piket Saya',
            'breadcrumb_active' => 'Portal Guru',
            'teacherInfo'       => $teacherInfo,
            'duties'            => $duties,
            'academicYear'      => $academicYear,
            'activePeriod'      => $period,
            'unitScope'         => $unitScope,
            'isManagement'      => $ctx['isManagement'],
            'teachersList'      => $ctx['teachersList'],
            'currentTeacherId'  => $teacherId,
        ]);
    }

    private function resolveTeacherContext(): array
    {
        $db = Database::connect();
        $activeUnitId = (int) session()->get('active_unit_id');

        $isManagement = has_role('super_admin', 'superadmin', 'wakasek_kurikulum', 'admin_smp', 'admin_sma')
            || has_permission('teachers.view')
            || has_permission('schedules.view')
            || has_permission('assignments.view');

        $teachersList = [];
        $teacherId = 0;

        if ($isManagement) {
            $tQuery = $db->table('teachers')->where('is_active', 1)->where('deleted_at IS NULL');
            if ($activeUnitId > 0) {
                $tQuery->groupStart()
                    ->where('primary_unit_id', $activeUnitId)
                    ->orWhere('primary_unit_id IS NULL')
                    ->groupEnd();
            }
            $teachersList = $tQuery->orderBy('full_name', 'ASC')->get()->getResultArray();
            if (empty($teachersList)) {
                $teachersList = $db->table('teachers')->where('is_active', 1)->where('deleted_at IS NULL')->orderBy('full_name', 'ASC')->get()->getResultArray();
            }

            $requestedTeacherId = (int) $this->request->getGet('teacher_id');
            if ($requestedTeacherId > 0) {
                $teacherId = $requestedTeacherId;
            } else {
                $ownTeacherId = $this->resolveTeacherId();
                if ($ownTeacherId > 0) {
                    $teacherId = $ownTeacherId;
                } elseif (!empty($teachersList)) {
                    $teacherId = (int) $teachersList[0]['id'];
                }
            }
        } else {
            $teacherId = $this->resolveTeacherId();
        }

        $teacherInfo = $teacherId > 0
            ? $db->table('teachers')->where('id', $teacherId)->where('deleted_at IS NULL')->get()->getRowArray()
            : null;

        return [
            'teacherId'    => $teacherId,
            'teacherInfo'  => $teacherInfo,
            'isManagement' => $isManagement,
            'teachersList' => $teachersList,
        ];
    }

    private function resolveTeacherId(): int
    {
        $teacherId = (int) (get_teacher_id() ?? 0);
        $userId = (int) session()->get('user_id');
        if ($teacherId > 0 || $userId <= 0) {
            return $teacherId;
        }

        $db = Database::connect();
        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();
        if (!$user || empty($user['email'])) {
            return 0;
        }
        $teacher = $db->table('teachers')->where('email', $user['email'])->where('deleted_at IS NULL')->get()->getRowArray();
        if (!$teacher) {
            return 0;
        }

        $teacherId = (int) $teacher['id'];
        $db->table('users')->where('id', $userId)->update(['teacher_id' => $teacherId]);
        session()->set('teacher_id', $teacherId);
        return $teacherId;
    }

    private function findVisibleAssignmentVersion(int $teacherId, int $unitId, int $periodId): ?array
    {
        if ($teacherId <= 0 || $unitId <= 0 || $periodId <= 0) {
            return null;
        }

        return Database::connect()->table('assignment_versions av')
            ->select('av.id, av.uuid, av.code, av.name, av.workflow_status')
            ->join('teaching_assignments ta', 'ta.assignment_version_id = av.id')
            ->where('av.academic_period_id', $periodId)
            ->where('av.workflow_status !=', 'ARCHIVED')
            ->where('ta.teacher_id', $teacherId)
            ->where('ta.unit_id', $unitId)
            ->where('ta.status', 'ACTIVE')
            ->where('ta.deleted_at IS NULL')
            ->orderBy('av.is_active', 'DESC')
            ->orderBy('av.id', 'DESC')
            ->get()->getRowArray() ?: null;
    }
}
