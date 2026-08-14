<?php

namespace App\Controllers;

use App\Services\AuditService;
use App\Services\UnitScopeService;
use App\Services\UuidService;
use Config\Database;

class StudentsController extends BaseController
{
    public function index()
    {
        if (!has_permission('students.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki akses ke master peserta didik.');
        }

        $db = Database::connect();
        $unitId = (int) $this->request->getGet('unit_id');
        if ($unitId > 0) {
            UnitScopeService::assertUnit($unitId);
        }
        $allowedUnitIds = UnitScopeService::accessibleUnitIds();
        $search = trim((string) $this->request->getGet('q'));
        $academicYearId = (int) $this->request->getGet('academic_year_id');
        $gradeFilter = (int) $this->request->getGet('grade');
        $classroomId = (int) $this->request->getGet('classroom_id');

        // Master lists for filters
        $units = UnitScopeService::accessibleUnits();
        $academicYears = $db->table('academic_years')->orderBy('start_date', 'DESC')->get()->getResultArray();
        $currentYear = $academicYears !== [] ? $academicYears[0] : null;
        $activeYearId = $academicYearId > 0 ? $academicYearId : ($currentYear ? (int)$currentYear['id'] : 0);

        $classroomBuilder = $db->table('classrooms c')
            ->select('c.id, c.code, c.name, gl.grade_number')
            ->join('grade_levels gl', 'gl.id = c.grade_level_id');
        if ($unitId > 0) {
            $classroomBuilder->where('c.unit_id', $unitId);
        } else {
            $classroomBuilder->whereIn('c.unit_id', $allowedUnitIds ?: [0]);
        }
        $classrooms = $classroomBuilder->orderBy('gl.grade_number')->orderBy('c.code')->get()->getResultArray();

        // Main Student Query
        $builder = $db->table('elective_students es')
            ->select('es.*, su.name AS unit_name, su.code AS unit_code, ay.name AS academic_year_name, c.code AS classroom_code, c.name AS classroom_name, u.username AS user_name')
            ->join('school_units su', 'su.id = es.unit_id')
            ->join('academic_years ay', 'ay.id = es.academic_year_id')
            ->join('classrooms c', 'c.id = es.classroom_id', 'left')
            ->join('users u', 'u.id = es.user_id', 'left');

        if ($unitId > 0) {
            $builder->where('es.unit_id', $unitId);
        } else {
            $builder->whereIn('es.unit_id', $allowedUnitIds ?: [0]);
        }
        if ($activeYearId > 0) {
            $builder->where('es.academic_year_id', $activeYearId);
        }
        if ($gradeFilter > 0) {
            $builder->where('es.current_grade', $gradeFilter);
        }
        if ($classroomId > 0) {
            $builder->where('es.classroom_id', $classroomId);
        }
        if ($search !== '') {
            $builder->groupStart()
                ->like('es.full_name', $search)
                ->orLike('es.student_number', $search)
                ->orLike('c.code', $search)
            ->groupEnd();
        }

        $students = $builder->orderBy('su.code')->orderBy('es.current_grade')->orderBy('es.full_name')->get()->getResultArray();

        // Stats metrics
        $totalStudents = count($students);
        $smaCount = 0;
        $smpCount = 0;
        $assignedClassCount = 0;

        foreach ($students as $st) {
            if (strtoupper((string) $st['unit_code']) === 'SMA') $smaCount++;
            if (strtoupper((string) $st['unit_code']) === 'SMP') $smpCount++;
            if ($st['classroom_id']) $assignedClassCount++;
        }

        return view('students/index', [
            'title'              => 'Master Peserta Didik',
            'breadcrumb_active'  => 'Peserta Didik',
            'students'           => $students,
            'totalStudents'      => $totalStudents,
            'smaCount'           => $smaCount,
            'smpCount'           => $smpCount,
            'assignedClassCount' => $assignedClassCount,
            'units'              => $units,
            'academicYears'      => $academicYears,
            'classrooms'         => $classrooms,
            'selectedUnitId'     => $unitId,
            'selectedYearId'     => $activeYearId,
            'selectedGrade'      => $gradeFilter,
            'selectedClassroomId'=> $classroomId,
            'searchQuery'        => $search,
            'canManageStudents'  => $this->canManageStudents(),
        ]);
    }

    public function store()
    {
        if (!$this->canManageStudents()) {
            return redirect()->to('/students')->with('error', 'Anda tidak memiliki hak untuk mengubah peserta didik.');
        }

        $rules = [
            'unit_id'          => 'required|integer',
            'academic_year_id' => 'required|integer',
            'student_number'   => 'required|max_length[40]',
            'full_name'        => 'required|max_length[150]',
            'current_grade'    => 'required|integer|greater_than[0]',
            'classroom_id'     => 'permit_empty|integer',
            'user_id'          => 'permit_empty|integer',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $db = Database::connect();
        $unitId         = (int) $this->request->getPost('unit_id');
        $academicYearId = (int) $this->request->getPost('academic_year_id');
        $studentNumber  = trim((string) $this->request->getPost('student_number'));
        $fullName       = trim((string) $this->request->getPost('full_name'));
        $currentGrade   = (int) $this->request->getPost('current_grade');
        $classroomId    = (int) $this->request->getPost('classroom_id');
        $userId         = (int) $this->request->getPost('user_id');

        try {
            $this->assertStudentReferences($unitId, $academicYearId, $classroomId, $userId);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        if ($userId > 0 && ! $db->table('users')->where('id', $userId)->where('is_active', 1)->get()->getRowArray()) {
            return redirect()->back()->withInput()->with('error', 'Akun pengguna siswa tidak valid.');
        }

        try {
            $id = $db->table('elective_students')->insert([
                'uuid'             => UuidService::v4(),
                'user_id'          => $userId ?: null,
                'unit_id'          => $unitId,
                'academic_year_id' => $academicYearId,
                'student_number'   => $studentNumber,
                'full_name'        => $fullName,
                'current_grade'    => $currentGrade,
                'classroom_id'     => $classroomId ?: null,
                'is_active'        => 1,
                'created_by'       => session()->get('user_id'),
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s'),
            ], true);

            AuditService::log('students', 'CREATE', 'ElectiveStudent', $id, null, $this->request->getPost(), 'Menambahkan peserta didik baru');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Nomor induk (' . $studentNumber . ') sudah terdaftar untuk tahun pelajaran ini.');
        }

        return redirect()->to('/students')->with('success', 'Peserta didik berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        if (!$this->canManageStudents()) {
            return redirect()->to('/students')->with('error', 'Anda tidak memiliki hak untuk mengubah peserta didik.');
        }

        $db = Database::connect();
        $student = $db->table('elective_students')->where('id', $id)->get()->getRowArray();
        if (! $student) {
            return redirect()->back()->with('error', 'Data peserta didik tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $student['unit_id']);

        $rules = [
            'unit_id'          => 'required|integer',
            'academic_year_id' => 'required|integer',
            'student_number'   => 'required|max_length[40]',
            'full_name'        => 'required|max_length[150]',
            'current_grade'    => 'required|integer|greater_than[0]',
            'classroom_id'     => 'permit_empty|integer',
            'user_id'          => 'permit_empty|integer',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $unitId         = (int) $this->request->getPost('unit_id');
        $academicYearId = (int) $this->request->getPost('academic_year_id');
        $studentNumber  = trim((string) $this->request->getPost('student_number'));
        $fullName       = trim((string) $this->request->getPost('full_name'));
        $currentGrade   = (int) $this->request->getPost('current_grade');
        $classroomId    = (int) $this->request->getPost('classroom_id');
        $userId         = (int) $this->request->getPost('user_id');

        try {
            $this->assertStudentReferences($unitId, $academicYearId, $classroomId, $userId);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        $old = $student;
        $db->table('elective_students')->where('id', $id)->update([
            'unit_id'          => $unitId,
            'academic_year_id' => $academicYearId,
            'student_number'   => $studentNumber,
            'full_name'        => $fullName,
            'current_grade'    => $currentGrade,
            'classroom_id'     => $classroomId ?: null,
            'user_id'          => $userId ?: null,
            'is_active'        => $this->request->getPost('is_active') ? 1 : 0,
            'updated_by'       => session()->get('user_id'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        AuditService::log('students', 'UPDATE', 'ElectiveStudent', $id, $old, $this->request->getPost(), 'Memperbarui data peserta didik');
        return redirect()->to('/students')->with('success', 'Data peserta didik berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        if (!$this->canManageStudents()) {
            return redirect()->to('/students')->with('error', 'Anda tidak memiliki hak untuk menghapus peserta didik.');
        }

        $db = Database::connect();
        $student = $db->table('elective_students')->where('id', $id)->get()->getRowArray();
        if (! $student) {
            return redirect()->back()->with('error', 'Data peserta didik tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $student['unit_id']);

        $db->table('elective_students')->where('id', $id)->delete();
        AuditService::log('students', 'DELETE', 'ElectiveStudent', $id, $student, null, 'Menghapus data peserta didik');
        return redirect()->to('/students')->with('success', 'Peserta didik berhasil dihapus.');
    }

    private function canManageStudents(): bool
    {
        return is_super_admin() || has_permission('students.manage');
    }

    private function assertStudentReferences(int $unitId, int $academicYearId, int $classroomId, int $userId): void
    {
        UnitScopeService::assertUnit($unitId);
        $db = Database::connect();
        if (!$db->table('academic_years')->where('id', $academicYearId)->get()->getRowArray()) {
            throw new \RuntimeException('Tahun ajaran tidak valid.');
        }
        if ($classroomId > 0) {
            $classroom = $db->table('classrooms')->select('id, unit_id')->where('id', $classroomId)
                ->where('is_active', 1)->where('deleted_at IS NULL')->get()->getRowArray();
            if (!$classroom || (int) $classroom['unit_id'] !== $unitId) {
                throw new \RuntimeException('Rombel harus aktif dan berasal dari unit peserta didik yang sama.');
            }
            UnitScopeService::assertClassroom($classroomId);
        }
        if ($userId > 0) {
            UnitScopeService::assertUser($userId);
            if (!$db->table('users')->where('id', $userId)->where('is_active', 1)
                ->where('deleted_at IS NULL')->get()->getRowArray()) {
                throw new \RuntimeException('Akun pengguna siswa tidak valid atau tidak aktif.');
            }
        }
    }
}
