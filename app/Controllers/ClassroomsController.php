<?php

namespace App\Controllers;

use App\Services\ClassroomService;
use App\Services\GradeLevelService;
use App\Services\RoomService;
use App\Services\TeacherService;
use App\Services\MasterExportService;
use App\Services\UnitScopeService;
use App\Models\SchoolUnitModel;
use App\Models\AcademicPeriodModel;
use App\Models\ClassroomModel;

class ClassroomsController extends BaseController
{
    public function index()
    {
        if (!has_permission('classrooms.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $activePeriodId = session()->get('active_period_id');

        try {
            $query = $this->request->getGet();
            $unitId = array_key_exists('unit_id', $query) && $query['unit_id'] === ''
                ? null
                : UnitScopeService::resolveUnit($query['unit_id'] ?? null);
        } catch (\Throwable $e) {
            return redirect()->to('/dashboard')->with('error', $e->getMessage());
        }

        $filters = [
            'unit_id'            => $unitId,
            'unit_ids'           => UnitScopeService::accessibleUnitIds(),
            'academic_period_id' => $this->request->getGet('academic_period_id') ?? $activePeriodId,
            'grade_level_id'     => $this->request->getGet('grade_level_id'),
            'is_active'          => $this->request->getGet('is_active'),
            'search'             => $this->request->getGet('search'),
        ];

        $perPageRaw = (string)$this->request->getGet('per_page');
        $perPage = in_array($perPageRaw, ['10', '20', '50', 'all'], true) ? $perPageRaw : '10';
        $limit = $perPage === 'all' ? 1000 : (int)$perPage;

        $filters['per_page'] = $perPage;
        $result = ClassroomService::getClassrooms($filters, $limit);

        $units = UnitScopeService::accessibleUnits();

        $db = \Config\Database::connect();
        $periods = $db->table('academic_periods ap')
            ->select('ap.*, ay.name as year_name')
            ->join('academic_years ay', 'ay.id = ap.academic_year_id', 'left')
            ->orderBy('ay.name', 'DESC')
            ->orderBy('ap.semester_number', 'DESC')
            ->get()
            ->getResultArray();

        $gradeLevels = GradeLevelService::getGradeLevels($filters['unit_id'] ? (int)$filters['unit_id'] : null, $filters['unit_ids']);

        return view('classrooms/index', [
            'title'             => 'Master Kelas / Rombel',
            'breadcrumb_active' => 'Kelas & Rombel',
            'classrooms'        => $result['data'],
            'pager'             => $result['pager'],
            'units'             => $units,
            'periods'           => $periods,
            'gradeLevels'       => $gradeLevels,
            'filters'           => $filters,
            'perPage'           => $perPage,
        ]);
    }

    public function create()
    {
        if (!has_permission('classrooms.manage')) {
            return redirect()->to('/classrooms')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $activeUnitId   = UnitScopeService::resolveUnit();
        $activePeriodId = session()->get('active_period_id');

        $units = UnitScopeService::accessibleUnits();

        $db = \Config\Database::connect();
        $periods = $db->table('academic_periods ap')
            ->select('ap.*, ay.name as year_name')
            ->join('academic_years ay', 'ay.id = ap.academic_year_id', 'left')
            ->orderBy('ay.name', 'DESC')
            ->orderBy('ap.semester_number', 'DESC')
            ->get()
            ->getResultArray();

        $gradeLevels = GradeLevelService::getGradeLevels($activeUnitId ? (int)$activeUnitId : null);
        $rooms       = RoomService::getRooms(['unit_id' => $activeUnitId], 1000)['data'];
        $teachers    = TeacherService::getTeachers(['unit_id' => $activeUnitId, 'is_active' => 1], 1000)['data'];

        return view('classrooms/create', [
            'title'             => 'Tambah Kelas / Rombel Baru',
            'breadcrumb_active' => 'Tambah Rombel',
            'units'             => $units,
            'periods'           => $periods,
            'gradeLevels'       => $gradeLevels,
            'rooms'             => $rooms,
            'teachers'          => $teachers,
            'activeUnitId'      => $activeUnitId,
            'activePeriodId'    => $activePeriodId,
        ]);
    }

    public function store()
    {
        if (!has_permission('classrooms.manage')) {
            return redirect()->to('/classrooms')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $rules = [
            'academic_period_id' => 'required|numeric',
            'unit_id'            => 'required|numeric',
            'grade_level_id'     => 'required|numeric',
            'code'               => 'required|min_length[1]|max_length[30]',
            'name'               => 'required|min_length[3]|max_length[100]',
            'capacity'           => 'permit_empty|integer|greater_than_equal_to[0]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $data = $this->request->getPost();
            $data['unit_id'] = UnitScopeService::resolveUnit($data['unit_id'] ?? null);
            ClassroomService::createClassroom($data);
            return redirect()->to('/classrooms')->with('success', 'Kelas/Rombel berhasil dibuat.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(string $uuid)
    {
        if (!has_permission('classrooms.manage')) {
            return redirect()->to('/classrooms')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $classroom = ClassroomService::getClassroomByUuid($uuid);

        if (!$classroom) {
            return redirect()->to('/classrooms')->with('error', 'Kelas/Rombel tidak ditemukan.');
        }
        try {
            UnitScopeService::assertClassroom((int) $classroom['id']);
        } catch (\Throwable $e) {
            return redirect()->to('/classrooms')->with('error', $e->getMessage());
        }

        $units = UnitScopeService::accessibleUnits();

        $periodModel = new AcademicPeriodModel();
        $periods = $periodModel->orderBy('id', 'DESC')->findAll();

        $gradeLevels = GradeLevelService::getGradeLevels((int)$classroom['unit_id']);
        $rooms       = RoomService::getRooms(['unit_id' => $classroom['unit_id']], 1000)['data'];
        $teachers    = TeacherService::getTeachers(['unit_id' => $classroom['unit_id'], 'is_active' => 1], 1000)['data'];

        return view('classrooms/edit', [
            'title'             => 'Edit Kelas / Rombel - ' . $classroom['name'],
            'breadcrumb_active' => 'Edit Rombel',
            'classroom'         => $classroom,
            'units'             => $units,
            'periods'           => $periods,
            'gradeLevels'       => $gradeLevels,
            'rooms'             => $rooms,
            'teachers'          => $teachers,
        ]);
    }

    public function update(string $uuid)
    {
        if (!has_permission('classrooms.manage')) {
            return redirect()->to('/classrooms')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $rules = [
            'code'            => 'required|min_length[1]|max_length[30]',
            'name'            => 'required|min_length[3]|max_length[100]',
            'capacity'        => 'permit_empty|integer|greater_than_equal_to[0]',
            'revision_number' => 'required|numeric',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $classroom = (new ClassroomModel())->where('uuid', $uuid)->where('deleted_at IS NULL')->first();
            if (!$classroom) {
                throw new \RuntimeException('Kelas/Rombel tidak ditemukan.');
            }
            UnitScopeService::assertClassroom((int) $classroom['id']);
            $data = $this->request->getPost();
            if (isset($data['unit_id'])) {
                $data['unit_id'] = UnitScopeService::resolveUnit($data['unit_id']);
            }
            ClassroomService::updateClassroom($uuid, $data);
            return redirect()->to('/classrooms')->with('success', 'Kelas/Rombel berhasil diperbarui.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function copyPeriodView()
    {
        if (!has_permission('classrooms.manage')) {
            return redirect()->to('/classrooms')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $activeUnitId   = UnitScopeService::resolveUnit();
        $activePeriodId = session()->get('active_period_id');

        $units = UnitScopeService::accessibleUnits();

        $periodModel = new AcademicPeriodModel();
        $periods = $periodModel->orderBy('id', 'DESC')->findAll();

        $sourcePeriodId = (int)($this->request->getGet('source_period_id') ?? 0);
        $targetPeriodId = (int)($this->request->getGet('target_period_id') ?? $activePeriodId);
        try {
            $unitId = UnitScopeService::resolveUnit($this->request->getGet('unit_id') ?? $activeUnitId);
        } catch (\Throwable $e) {
            return redirect()->to('/classrooms')->with('error', $e->getMessage());
        }

        $preview = [];
        if ($sourcePeriodId > 0 && $targetPeriodId > 0 && $unitId > 0) {
            $preview = ClassroomService::previewCopyPeriod($sourcePeriodId, $targetPeriodId, $unitId);
        }

        return view('classrooms/copy_period', [
            'title'             => 'Copy Rombel Antar Periode',
            'breadcrumb_active' => 'Copy Period Rombel',
            'units'             => $units,
            'periods'           => $periods,
            'sourcePeriodId'    => $sourcePeriodId,
            'targetPeriodId'    => $targetPeriodId,
            'unitId'            => $unitId,
            'preview'           => $preview,
        ]);
    }

    public function applyCopyPeriod()
    {
        if (!has_permission('classrooms.manage')) {
            return redirect()->to('/classrooms')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $sourcePeriodId = (int)$this->request->getPost('source_period_id');
        $targetPeriodId = (int)$this->request->getPost('target_period_id');
        try {
            $unitId = UnitScopeService::resolveUnit($this->request->getPost('unit_id'));
        } catch (\Throwable $e) {
            return redirect()->to('/classrooms/copy-period')->with('error', $e->getMessage());
        }
        $copyHomeroom   = (bool)$this->request->getPost('copy_homeroom');

        if ($sourcePeriodId === $targetPeriodId) {
            return redirect()->back()->with('error', 'Periode sumber dan periode target tidak boleh sama.');
        }

        try {
            $res = ClassroomService::applyCopyPeriod($sourcePeriodId, $targetPeriodId, $unitId, $copyHomeroom);
            return redirect()->to('/classrooms?academic_period_id=' . $targetPeriodId . '&unit_id=' . $unitId)
                ->with('success', 'Copy rombel berhasil: ' . $res['copied_count'] . ' kelas dibuat, ' . $res['skipped_count'] . ' kelas dilewati (sudah ada).');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function export()
    {
        if (!has_permission('classrooms.export')) {
            return redirect()->to('/classrooms')->with('error', 'Anda tidak memiliki hak akses export.');
        }

        try {
            $unitId = UnitScopeService::resolveUnit($this->request->getGet('unit_id'));
            $filePath = MasterExportService::exportExcel('CLASSROOMS', [
                'unit_id'            => $unitId,
                'academic_period_id' => $this->request->getGet('academic_period_id') ?? session()->get('active_period_id'),
            ]);

            return $this->response->download($filePath, null);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function students(string $uuid)
    {
        if (!has_permission('classrooms.view')) {
            return redirect()->to('/classrooms')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $classroom = ClassroomService::getClassroomByUuid($uuid);
        if (!$classroom) {
            return redirect()->to('/classrooms')->with('error', 'Kelas/Rombel tidak ditemukan.');
        }

        $db = \Config\Database::connect();

        $gradeLevel = $db->table('grade_levels')->where('id', $classroom['grade_level_id'])->get()->getRowArray();
        $gradeNumber = $gradeLevel ? (int) $gradeLevel['grade_number'] : 10;

        $assignedStudents = $db->table('elective_students es')
            ->select('es.*, u.username AS user_name')
            ->join('users u', 'u.id = es.user_id', 'left')
            ->where('es.classroom_id', $classroom['id'])
            ->where('es.is_active', 1)
            ->orderBy('es.full_name')
            ->get()->getResultArray();

        $unassignedStudents = $db->table('elective_students es')
            ->select('es.*, c.code AS current_classroom_code')
            ->join('classrooms c', 'c.id = es.classroom_id', 'left')
            ->where('es.unit_id', $classroom['unit_id'])
            ->where('es.current_grade', $gradeNumber)
            ->where('es.is_active', 1)
            ->groupStart()
                ->where('es.classroom_id IS NULL')
                ->orWhere('es.classroom_id !=', $classroom['id'])
            ->groupEnd()
            ->orderBy('es.full_name')
            ->get()->getResultArray();

        return view('classrooms/students', [
            'title'              => 'Pengaturan Siswa Rombel - ' . $classroom['name'],
            'breadcrumb_active'  => 'Roster Siswa Rombel',
            'classroom'          => $classroom,
            'assignedStudents'   => $assignedStudents,
            'unassignedStudents' => $unassignedStudents,
            'gradeNumber'        => $gradeNumber,
        ]);
    }

    public function assignStudent(string $uuid)
    {
        if (!has_permission('classrooms.manage')) {
            return redirect()->to('/classrooms')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $classroom = ClassroomService::getClassroomByUuid($uuid);
        if (!$classroom) {
            return redirect()->to('/classrooms')->with('error', 'Kelas/Rombel tidak ditemukan.');
        }

        $studentIds = (array) $this->request->getPost('student_ids');
        if ($studentIds === []) {
            $singleId = (int) $this->request->getPost('student_id');
            if ($singleId > 0) $studentIds = [$singleId];
        }

        if ($studentIds === []) {
            return redirect()->back()->with('error', 'Pilih minimal satu siswa untuk dimasukkan ke Rombel ini.');
        }

        $db = \Config\Database::connect();
        $count = 0;
        foreach ($studentIds as $sId) {
            $sId = (int) $sId;
            if ($sId > 0) {
                $db->table('elective_students')->where('id', $sId)->update([
                    'classroom_id' => $classroom['id'],
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);
                $count++;
            }
        }

        return redirect()->to('/classrooms/' . $uuid . '/students')
            ->with('success', "Berhasil menambahkan {$count} siswa ke dalam Rombel " . $classroom['name'] . '.');
    }

    public function removeStudent(string $uuid, int $studentId)
    {
        if (!has_permission('classrooms.manage')) {
            return redirect()->to('/classrooms')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $classroom = ClassroomService::getClassroomByUuid($uuid);
        if (!$classroom) {
            return redirect()->to('/classrooms')->with('error', 'Kelas/Rombel tidak ditemukan.');
        }

        $db = \Config\Database::connect();
        $db->table('elective_students')->where('id', $studentId)->where('classroom_id', $classroom['id'])->update([
            'classroom_id' => null,
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/classrooms/' . $uuid . '/students')
            ->with('success', 'Siswa berhasil dikeluarkan dari Rombel ' . $classroom['name'] . '.');
    }

    public function promoteView()
    {
        if (!has_permission('classrooms.manage')) {
            return redirect()->to('/classrooms')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $db = \Config\Database::connect();
        $academicYears = $db->table('academic_years')->orderBy('start_date', 'DESC')->get()->getResultArray();
        $units = UnitScopeService::accessibleUnits();

        $sourceYearId = (int) ($this->request->getGet('source_year_id') ?? 0);
        $targetYearId = (int) ($this->request->getGet('target_year_id') ?? 0);
        $unitId = (int) ($this->request->getGet('unit_id') ?? ($units !== [] ? $units[0]['id'] : 0));

        $previewData = [];
        if ($sourceYearId > 0 && $targetYearId > 0 && $unitId > 0) {
            $studentGrades = $db->table('elective_students')
                ->select('current_grade, COUNT(id) as total')
                ->where('unit_id', $unitId)
                ->where('academic_year_id', $sourceYearId)
                ->where('is_active', 1)
                ->groupBy('current_grade')
                ->get()->getResultArray();

            foreach ($studentGrades as $sg) {
                $g = (int) $sg['current_grade'];
                $nextG = $g + 1;
                $isGraduate = ($g === 9 || $g === 12);
                $previewData[] = [
                    'current_grade' => $g,
                    'next_grade'    => $isGraduate ? 'LULUS (Alumni)' : 'Tingkat ' . $nextG,
                    'total_students'=> $sg['total'],
                    'is_graduate'   => $isGraduate,
                ];
            }
        }

        return view('classrooms/promote', [
            'title'             => 'Kenaikan Kelas & Perpindahan Rombel Otomatis',
            'breadcrumb_active' => 'Kenaikan Kelas',
            'academicYears'     => $academicYears,
            'units'             => $units,
            'sourceYearId'      => $sourceYearId,
            'targetYearId'      => $targetYearId,
            'unitId'            => $unitId,
            'previewData'       => $previewData,
        ]);
    }

    public function applyPromote()
    {
        if (!has_permission('classrooms.manage')) {
            return redirect()->to('/classrooms')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $sourceYearId = (int) $this->request->getPost('source_year_id');
        $targetYearId = (int) $this->request->getPost('target_year_id');
        $unitId       = (int) $this->request->getPost('unit_id');

        if ($sourceYearId <= 0 || $targetYearId <= 0 || $sourceYearId === $targetYearId) {
            return redirect()->back()->with('error', 'Tahun Pelajaran sumber dan target harus berbeda dan valid.');
        }

        $db = \Config\Database::connect();

        $students = $db->table('elective_students')
            ->where('unit_id', $unitId)
            ->where('academic_year_id', $sourceYearId)
            ->where('is_active', 1)
            ->get()->getResultArray();

        if ($students === []) {
            return redirect()->back()->with('error', 'Tidak ada data siswa aktif pada Tahun Pelajaran sumber yang dipilih.');
        }

        $targetClassrooms = $db->table('classrooms c')
            ->select('c.id, c.code, gl.grade_number')
            ->join('grade_levels gl', 'gl.id = c.grade_level_id')
            ->join('academic_periods ap', 'ap.id = c.academic_period_id')
            ->where('c.unit_id', $unitId)
            ->where('ap.academic_year_id', $targetYearId)
            ->get()->getResultArray();

        $classroomsByGrade = [];
        foreach ($targetClassrooms as $tc) {
            $gn = (int) $tc['grade_number'];
            $classroomsByGrade[$gn][] = $tc;
        }

        $promotedCount = 0;
        $graduatedCount = 0;

        foreach ($students as $st) {
            $curG = (int) $st['current_grade'];

            if ($curG === 9 || $curG === 12) {
                $graduatedCount++;
                continue;
            }

            $nextG = $curG + 1;

            $targetClassId = null;
            if (isset($classroomsByGrade[$nextG]) && $classroomsByGrade[$nextG] !== []) {
                $clsList = $classroomsByGrade[$nextG];
                $targetClassId = $clsList[0]['id'];
            }

            $existing = $db->table('elective_students')
                ->where('unit_id', $unitId)
                ->where('academic_year_id', $targetYearId)
                ->where('student_number', $st['student_number'])
                ->get()->getRowArray();

            if ($existing) {
                $db->table('elective_students')->where('id', $existing['id'])->update([
                    'full_name'     => $st['full_name'],
                    'current_grade' => $nextG,
                    'classroom_id'  => $targetClassId ?: $existing['classroom_id'],
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
            } else {
                $db->table('elective_students')->insert([
                    'uuid'             => \App\Services\UuidService::v4(),
                    'user_id'          => $st['user_id'],
                    'unit_id'          => $unitId,
                    'academic_year_id' => $targetYearId,
                    'student_number'   => $st['student_number'],
                    'full_name'        => $st['full_name'],
                    'current_grade'    => $nextG,
                    'classroom_id'     => $targetClassId,
                    'is_active'        => 1,
                    'created_by'       => session()->get('user_id'),
                    'created_at'       => date('Y-m-d H:i:s'),
                    'updated_at'       => date('Y-m-d H:i:s'),
                ]);
            }
            $promotedCount++;
        }

        return redirect()->to('/students?academic_year_id=' . $targetYearId . '&unit_id=' . $unitId)
            ->with('success', "Proses Kenaikan Kelas Otomatis Berhasil! {$promotedCount} siswa berhasil naik kelas ke Tahun Pelajaran baru, {$graduatedCount} siswa diproses kelulusannya.");
    }
}
