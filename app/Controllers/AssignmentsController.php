<?php

namespace App\Controllers;

use App\Services\AssignmentWorkflowService;
use App\Services\AssignmentValidationService;
use App\Services\AssignmentMatrixService;
use App\Services\UnitScopeService;
use App\Models\AssignmentVersionModel;
use App\Models\TeachingAssignmentModel;
use App\Models\CurriculumVersionModel;
use App\Models\AcademicPeriodModel;
use App\Models\ClassroomModel;
use App\Models\SubjectModel;
use App\Models\TeacherModel;
use App\Models\SchoolUnitModel;
use App\Services\UuidService;

class AssignmentsController extends BaseController
{
    public function index()
    {
        if (!has_permission('assignments.view')) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        $versionModel = new AssignmentVersionModel();
        $periodModel = new AcademicPeriodModel();

        $filters = [
            'academic_period_id' => $this->request->getGet('academic_period_id'),
            'workflow_status'    => $this->request->getGet('workflow_status'),
            'search'             => $this->request->getGet('search'),
        ];

        $builder = $versionModel->select('assignment_versions.*, academic_periods.name as period_name, curriculum_versions.name as curriculum_name')
                                ->join('academic_periods', 'academic_periods.id = assignment_versions.academic_period_id')
                                ->join('curriculum_versions', 'curriculum_versions.id = assignment_versions.curriculum_version_id')
                                ->orderBy('assignment_versions.id', 'DESC');

        if (!empty($filters['academic_period_id'])) {
            $builder->where('assignment_versions.academic_period_id', $filters['academic_period_id']);
        }
        if (!empty($filters['workflow_status'])) {
            $builder->where('assignment_versions.workflow_status', $filters['workflow_status']);
        }
        if (!empty($filters['search'])) {
            $builder->like('assignment_versions.name', $filters['search'])
                    ->orLike('assignment_versions.code', $filters['search']);
        }

        $versions = $builder->findAll();
        $periods = $periodModel->orderBy('id', 'DESC')->findAll();

        return view('assignments/versions/index', [
            'versions' => $versions,
            'periods'  => $periods,
            'filters'  => $filters
        ]);
    }

    public function create()
    {
        if (!has_permission('assignments.manage')) {
            return redirect()->to('/assignments')->with('error', 'Akses ditolak.');
        }

        $periodModel = new AcademicPeriodModel();
        $curriculumModel = new CurriculumVersionModel();

        $periods = $periodModel->where('status', 'ACTIVE')->orderBy('id', 'DESC')->findAll();
        $curriculums = $curriculumModel->where('workflow_status', 'APPROVED')
                                        ->orWhere('workflow_status', 'LOCKED')
                                        ->findAll();

        return view('assignments/versions/create', [
            'periods'     => $periods,
            'curriculums' => $curriculums
        ]);
    }

    public function store()
    {
        if (!has_permission('assignments.manage')) {
            return redirect()->to('/assignments')->with('error', 'Akses ditolak.');
        }

        $rules = [
            'academic_period_id'    => 'required|numeric',
            'curriculum_version_id' => 'required|numeric',
            'code'                  => 'required|min_length[3]|max_length[50]',
            'name'                  => 'required|min_length[3]|max_length[150]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $versionModel = new AssignmentVersionModel();

        $uuid = UuidService::v4();
        $data = [
            'uuid'                  => $uuid,
            'academic_period_id'    => $this->request->getPost('academic_period_id'),
            'curriculum_version_id' => $this->request->getPost('curriculum_version_id'),
            'code'                  => $this->request->getPost('code'),
            'name'                  => $this->request->getPost('name'),
            'description'           => $this->request->getPost('description'),
            'workflow_status'       => 'DRAFT',
            'revision_number'       => 1,
            'is_active'             => 0,
            'created_by'            => session()->get('user_id'),
        ];

        try {
            $versionModel->insert($data);
            return redirect()->to('/assignments/' . $uuid)->with('success', 'Versi penugasan berhasil dibuat.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(string $uuid)
    {
        if (!has_permission('assignments.view')) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        $versionModel = new AssignmentVersionModel();
        $version = $versionModel->where('uuid', $uuid)->first();
        if (!$version) {
            return redirect()->to('/assignments')->with('error', 'Versi penugasan tidak ditemukan.');
        }

        try {
            $unitId = UnitScopeService::resolveUnit($this->request->getGet('unit_id'));
        } catch (\Throwable $e) {
            return redirect()->to('/assignments')->with('error', $e->getMessage());
        }

        // Fetch classrooms & subjects for assignment inputs
        $classroomModel = new ClassroomModel();
        $subjectModel = new SubjectModel();
        $teacherModel = new TeacherModel();
        $unitModel = new SchoolUnitModel();

        $classrooms = $classroomModel->where('academic_period_id', $version['academic_period_id'])
                                     ->where('unit_id', $unitId)
                                     ->where('is_active', 1)
                                     ->findAll();

        $subjects = $subjectModel->select('subjects.*')
            ->join('subject_unit_availability sua', 'sua.subject_id = subjects.id')
            ->where('sua.unit_id', $unitId)->where('sua.is_available', 1)
            ->where('subjects.is_active', 1)->findAll();
        
        // Fetch teachers having assignment inside this unit
        $db = \Config\Database::connect();
        $teachers = $db->table('teachers t')
                       ->select('t.*')
                       ->join('teacher_unit_assignments tua', 'tua.teacher_id = t.id')
                       ->where('tua.unit_id', $unitId)
                       ->where('tua.status', 'ACTIVE')
                       ->groupStart()
                           ->where('tua.academic_period_id IS NULL')
                           ->orWhere('tua.academic_period_id', $version['academic_period_id'])
                       ->groupEnd()
                       ->where('t.is_active', 1)
                       ->groupBy('t.id')
                       ->get()
                       ->getResultArray();

        $assignments = (new TeachingAssignmentModel())->where('assignment_version_id', $version['id'])
                                                      ->where('unit_id', $unitId)
                                                      ->findAll();

        $matrix = AssignmentMatrixService::getMatrix((int)$version['id'], $unitId);

        $units = UnitScopeService::accessibleUnits();

        return view('assignments/versions/show', [
            'version'     => $version,
            'classrooms'  => $classrooms,
            'subjects'    => $subjects,
            'teachers'    => $teachers,
            'assignments' => $assignments,
            'matrix'      => $matrix,
            'unit_id'     => $unitId,
            'units'       => $units
        ]);
    }

    public function storeAssignment(string $uuid)
    {
        if (!has_permission('assignments.manage')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Hak akses ditolak.']);
        }

        $versionModel = new AssignmentVersionModel();
        $version = $versionModel->where('uuid', $uuid)->first();
        if (!$version) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Versi penugasan tidak ditemukan.']);
        }

        if (in_array($version['workflow_status'], ['APPROVED', 'LOCKED', 'ARCHIVED'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Versi terkunci tidak dapat diedit.']);
        }

        $rules = [
            'classroom_id'          => 'required|numeric',
            'subject_id'            => 'required|numeric',
            'teacher_id'            => 'required|numeric',
            'assigned_weekly_hours' => 'required|numeric|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Validasi gagal.', 'errors' => $this->validator->getErrors()]);
        }

        $classroomId = (int)$this->request->getPost('classroom_id');
        $subjectId = (int)$this->request->getPost('subject_id');
        $teacherId = (int)$this->request->getPost('teacher_id');
        $hours = (float)$this->request->getPost('assigned_weekly_hours');
        $role = $this->request->getPost('assignment_role') ?: 'PRIMARY';

        try {
            UnitScopeService::assertClassroom($classroomId);
            $classroom = (new ClassroomModel())->find($classroomId);
            $unitId = (int)$classroom['unit_id'];
            UnitScopeService::assertTeacherInUnit($teacherId, $unitId, (int) $version['academic_period_id']);
            UnitScopeService::assertSubjectInUnit($subjectId, $unitId);

            // Find structure match
            $db = \Config\Database::connect();
            $structure = $db->table('curriculum_structures')
                            ->where('curriculum_version_id', $version['curriculum_version_id'])
                            ->where('classroom_id', $classroomId)
                            ->where('subject_id', $subjectId)
                            ->where('status', 'ACTIVE')
                            ->get()
                            ->getRowArray();

            if (!$structure) {
                // Fallback to grade default
                $structure = $db->table('curriculum_structures')
                                ->where('curriculum_version_id', $version['curriculum_version_id'])
                                ->where('grade_level_id', $classroom['grade_level_id'])
                                ->where('classroom_id', null)
                                ->where('subject_id', $subjectId)
                                ->where('status', 'ACTIVE')
                                ->get()
                                ->getRowArray();
            }

            if (!$structure) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Struktur kurikulum untuk kelas dan mapel ini tidak ditemukan.']);
            }

            $assignmentModel = new TeachingAssignmentModel();

            // Look if exact teacher already assigned here
            $existing = $assignmentModel->where('assignment_version_id', $version['id'])
                                        ->where('classroom_id', $classroomId)
                                        ->where('subject_id', $subjectId)
                                        ->where('teacher_id', $teacherId)
                                        ->first();

            $aUuid = $existing ? $existing['uuid'] : UuidService::v4();
            $data = [
                'uuid'                    => $aUuid,
                'assignment_version_id'   => $version['id'],
                'curriculum_structure_id' => $structure['id'],
                'academic_period_id'      => $version['academic_period_id'],
                'unit_id'                 => $unitId,
                'grade_level_id'          => $classroom['grade_level_id'],
                'classroom_id'            => $classroomId,
                'subject_id'              => $subjectId,
                'teacher_id'              => $teacherId,
                'assignment_role'         => $role,
                'assigned_weekly_hours'   => $hours,
                'workload_weekly_hours'   => $hours,
                'source_weekly_hours'     => $structure['effective_weekly_hours'],
                'is_primary_teacher'      => $role === 'PRIMARY' ? 1 : 0,
                'status'                  => 'ACTIVE',
            ];

            if ($existing) {
                $assignmentModel->update($existing['id'], $data);
            } else {
                $assignmentModel->insert($data);
            }

            return $this->response->setJSON(['status' => 'success', 'message' => 'Penugasan berhasil disimpan.']);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function matrix(string $uuid)
    {
        if (!has_permission('assignments.view')) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Hak akses ditolak.',
            ]);
        }

        $version = (new AssignmentVersionModel())->where('uuid', $uuid)->first();
        if (!$version) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'Versi penugasan tidak ditemukan.',
            ]);
        }

        try {
            $unitId = UnitScopeService::resolveUnit($this->request->getGet('unit_id'));
            return $this->response->setJSON([
                'status' => 'success',
                'data' => AssignmentMatrixService::getMatrix((int) $version['id'], $unitId),
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function export(string $uuid)
    {
        if (!has_permission('assignments.export')) {
            return redirect()->to('/assignments')->with('error', 'Hak akses ditolak.');
        }

        $version = (new AssignmentVersionModel())->where('uuid', $uuid)->first();
        if (!$version) {
            return redirect()->to('/assignments')->with('error', 'Versi penugasan tidak ditemukan.');
        }

        try {
            $unitId = UnitScopeService::resolveUnit($this->request->getGet('unit_id'));
        } catch (\Throwable $e) {
            return redirect()->to('/assignments')->with('error', $e->getMessage());
        }

        $rows = \Config\Database::connect()->table('teaching_assignments ta')
            ->select('su.code AS unit_code, gl.code AS grade_code, c.code AS classroom_code, s.code AS subject_code, s.name AS subject_name, t.full_name AS teacher_name, ta.assignment_role, ta.assigned_weekly_hours, ta.workload_weekly_hours, ta.status')
            ->join('school_units su', 'su.id = ta.unit_id')
            ->join('grade_levels gl', 'gl.id = ta.grade_level_id')
            ->join('classrooms c', 'c.id = ta.classroom_id')
            ->join('subjects s', 's.id = ta.subject_id')
            ->join('teachers t', 't.id = ta.teacher_id')
            ->where('ta.assignment_version_id', $version['id'])
            ->where('ta.unit_id', $unitId)
            ->where('ta.deleted_at IS NULL')
            ->orderBy('c.code', 'ASC')
            ->orderBy('s.code', 'ASC')
            ->get()->getResultArray();

        $output = fopen('php://temp', 'w+');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Unit', 'Tingkat', 'Kelas', 'Kode Mapel', 'Mata Pelajaran', 'Guru', 'Peran', 'Jam Dialokasikan', 'Jam Beban Kerja', 'Status']);
        foreach ($rows as $row) {
            fputcsv($output, array_map([self::class, 'csvCell'], array_values($row)));
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        $safeCode = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $version['code']);
        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="Penugasan_' . $safeCode . '.csv"')
            ->setBody($csv);
    }

    public function workflowAction(string $uuid, string $action)
    {
        $versionModel = new AssignmentVersionModel();
        $version = $versionModel->where('uuid', $uuid)->first();
        if (!$version) {
            return redirect()->back()->with('error', 'Versi penugasan tidak ditemukan.');
        }

        // Map actions to statuses
        $actionMap = [
            'validate' => ['status' => 'VALIDATED', 'perm' => 'assignments.validate'],
            'review'   => ['status' => 'REVIEWED', 'perm' => 'assignments.review'],
            'approve'  => ['status' => 'APPROVED', 'perm' => 'assignments.approve'],
            'lock'     => ['status' => 'LOCKED', 'perm' => 'assignments.lock'],
            'reject'   => ['status' => 'REJECTED', 'perm' => 'assignments.review'],
            'archive'  => ['status' => 'ARCHIVED', 'perm' => 'assignments.manage']
        ];

        if (!isset($actionMap[$action])) {
            return redirect()->back()->with('error', 'Aksi workflow tidak dikenali.');
        }

        $config = $actionMap[$action];
        if (!has_permission($config['perm'])) {
            return redirect()->back()->with('error', 'Hak akses ditolak.');
        }

        try {
            $this->assertVersionScope((int) $version['id']);
            AssignmentWorkflowService::transition(
                (int)$version['id'],
                $config['status'],
                (int)$version['revision_number'],
                (int)session()->get('user_id'),
                $this->request->getPost('change_reason')
            );
            return redirect()->back()->with('success', "Status versi penugasan berhasil diperbarui ke {$config['status']}.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function cloneVersion(string $uuid)
    {
        if (!has_permission('assignments.revise')) {
            return redirect()->back()->with('error', 'Hak akses ditolak.');
        }

        $versionModel = new AssignmentVersionModel();
        $version = $versionModel->where('uuid', $uuid)->first();
        if (!$version) {
            return redirect()->back()->with('error', 'Versi penugasan tidak ditemukan.');
        }

        $rules = [
            'code'          => 'required|min_length[3]|max_length[50]',
            'name'          => 'required|min_length[3]|max_length[150]',
            'change_reason' => 'required|min_length[5]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $this->assertVersionScope((int) $version['id']);
            $newId = AssignmentWorkflowService::cloneVersion(
                (int)$version['id'],
                $this->request->getPost('code'),
                $this->request->getPost('name'),
                (int)session()->get('user_id'),
                $this->request->getPost('change_reason')
            );
            $newVer = $versionModel->find($newId);
            return redirect()->to('/assignments/' . $newVer['uuid'])->with('success', 'Revisi penugasan berhasil dibuat.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function assertVersionScope(int $versionId): void
    {
        $rows = \Config\Database::connect()->table('teaching_assignments')
            ->select('unit_id')->where('assignment_version_id', $versionId)
            ->where('deleted_at IS NULL')->groupBy('unit_id')->get()->getResultArray();
        $unitIds = array_map('intval', array_column($rows, 'unit_id'));
        if ($unitIds !== []) {
            UnitScopeService::assertUnits($unitIds);
        }
    }

    private static function csvCell($value): string
    {
        $value = (string) $value;
        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'" . $value : $value;
    }
}
