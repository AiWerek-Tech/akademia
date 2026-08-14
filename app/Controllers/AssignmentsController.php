<?php

namespace App\Controllers;

use App\Services\AssignmentWorkflowService;
use App\Services\AssignmentValidationService;
use App\Services\AssignmentMatrixService;
use App\Services\ScheduleCapacityService;
use App\Services\TeacherWorkloadCalculationService;
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
use Config\Database;

class AssignmentsController extends BaseController
{
    public function index()
    {
        if (!has_permission('assignments.view')) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        $db = Database::connect();
        $periodModel = new AcademicPeriodModel();

        $periods = $db->table('academic_periods ap')
            ->select('ap.*, ay.name AS year_name')
            ->join('academic_years ay', 'ay.id = ap.academic_year_id', 'left')
            ->orderBy('ap.id', 'DESC')
            ->get()->getResultArray();

        $filters = [
            'period_id' => $this->request->getGet('period_id'),
            'status'    => $this->request->getGet('status'),
            'search'    => $this->request->getGet('search')
        ];

        $query = $db->table('assignment_versions av')
            ->select('av.*, ap.name AS raw_period_name, ap.semester_number, ay.name AS year_name, cv.name AS curriculum_name, cv.code AS curriculum_code')
            ->join('academic_periods ap', 'ap.id = av.academic_period_id', 'left')
            ->join('academic_years ay', 'ay.id = ap.academic_year_id', 'left')
            ->join('curriculum_versions cv', 'cv.id = av.curriculum_version_id', 'left');

        if (!empty($filters['period_id'])) {
            $query->where('av.academic_period_id', $filters['period_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('av.workflow_status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $query->groupStart()
                ->like('av.code', $filters['search'])
                ->orLike('av.name', $filters['search'])
            ->groupEnd();
        }

        $versions = $query->orderBy('av.id', 'DESC')->get()->getResultArray();

        foreach ($versions as &$v) {
            $pName = trim((string)($v['raw_period_name'] ?? ''));
            if ($pName === '') {
                $semLabel = ((int)($v['semester_number'] ?? 1) === 1) ? 'Semester 1 (Ganjil)' : 'Semester 2 (Genap)';
                $v['period_name'] = 'T.A ' . ($v['year_name'] ?? '') . ' · ' . $semLabel;
            } else {
                $v['period_name'] = $pName;
            }
        }
        unset($v);

        $curriculumModel = new CurriculumVersionModel();
        $curriculums = $curriculumModel->findAll();

        return view('assignments/versions/index', [
            'versions'    => $versions,
            'periods'     => $periods,
            'curriculums' => $curriculums,
            'filters'     => $filters
        ]);
    }

    public function create()
    {
        if (!has_permission('assignments.manage')) {
            return redirect()->to('/assignments')->with('error', 'Akses ditolak.');
        }

        $db = Database::connect();
        $periods = $db->table('academic_periods ap')
            ->select('ap.*, ay.name AS year_name')
            ->join('academic_years ay', 'ay.id = ap.academic_year_id', 'left')
            ->orderBy('ap.id', 'DESC')
            ->get()->getResultArray();

        foreach ($periods as &$p) {
            $name = trim((string) ($p['name'] ?? ''));
            if ($name === '') {
                $semLabel = ((int) ($p['semester_number'] ?? 1) === 1) ? 'Semester 1 (Ganjil)' : 'Semester 2 (Genap)';
                $p['name'] = 'T.A ' . ($p['year_name'] ?? '') . ' · ' . $semLabel;
            }
        }
        unset($p);

        $curriculumModel = new CurriculumVersionModel();
        $curriculums = $curriculumModel->where('workflow_status', 'APPROVED')
                                        ->orWhere('workflow_status', 'LOCKED')
                                        ->findAll();

        return view('assignments/versions/create', [
            'periods'     => $periods,
            'curriculums' => $curriculums
        ]);
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

        $unitId = UnitScopeService::resolveUnit();
        $classrooms = (new ClassroomModel())->where('unit_id', $unitId)->findAll();
        $subjects = (new SubjectModel())->findAll();
        $teachers = (new TeacherModel())->where('is_active', 1)->findAll();

        $assignments = (new TeachingAssignmentModel())->where('assignment_version_id', $version['id'])
                                                      ->where('unit_id', $unitId)
                                                      ->findAll();

        $matrix = AssignmentMatrixService::getMatrix((int)$version['id'], $unitId);
        $scheduleCapacity = ScheduleCapacityService::forAssignmentVersion((int) $version['id'], $unitId);
        foreach ($scheduleCapacity['classrooms'] as $classId => &$capacity) {
            $capacity['required_jp'] = 0.0;
            $capacity['assigned_jp'] = 0.0;
            foreach ($matrix as $matrixRow) {
                if ((int) $matrixRow['classroom_id'] !== (int) $classId) continue;
                $capacity['required_jp'] += (float) $matrixRow['effective_weekly_hours'];
                $capacity['assigned_jp'] += (float) $matrixRow['assigned_weekly_hours'];
            }
            $capacity['capacity_gap'] = $capacity['required_jp'] - $capacity['available_slots'];
            $capacity['block_gap'] = (int) ceil($capacity['required_jp'] / 2) - $capacity['available_2jp_blocks'];
        }
        unset($capacity);
        $units = UnitScopeService::accessibleUnits();

        $db = Database::connect();
        $dutyTypes = $db->table('additional_duty_types')->orderBy('name', 'ASC')->get()->getResultArray();
        $duties = $db->table('teacher_additional_duties tad')
            ->select('tad.*, t.full_name AS teacher_name, adt.name AS duty_type_name, adt.code AS duty_type_code')
            ->join('teachers t', 't.id = tad.teacher_id', 'left')
            ->join('additional_duty_types adt', 'adt.id = tad.duty_type_id', 'left')
            ->where('tad.assignment_version_id', $version['id'])
            ->where('tad.deleted_at IS NULL')
            ->get()->getResultArray();

        $teacherLoads = [];
        foreach ($teachers as $t) {
            $tId = (int)$t['id'];
            $calc = TeacherWorkloadCalculationService::calculate($tId, (int)$version['id'], (int)$version['academic_period_id'], $unitId);
            $teacherLoads[$tId] = $calc;
        }

        return view('assignments/versions/show', [
            'version'      => $version,
            'classrooms'   => $classrooms,
            'subjects'     => $subjects,
            'teachers'     => $teachers,
            'assignments'  => $assignments,
            'matrix'       => $matrix,
            'scheduleCapacity' => $scheduleCapacity,
            'unit_id'      => $unitId,
            'units'        => $units,
            'dutyTypes'    => $dutyTypes,
            'duties'       => $duties,
            'teacherLoads' => $teacherLoads
        ]);
    }

    public function storeDuty(string $uuid)
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

        $teacherId = (int)$this->request->getPost('teacher_id');
        $dutyTypeId = (int)$this->request->getPost('duty_type_id');
        $hours = (float)$this->request->getPost('workload_hours');

        if ($teacherId <= 0 || $dutyTypeId <= 0 || $hours <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Pilih guru, jenis tugas tambahan, dan jumlah jam ekuivalensi.']);
        }

        $uuidDuty = UuidService::v4();
        $db = Database::connect();
        $db->table('teacher_additional_duties')->insert([
            'uuid' => $uuidDuty,
            'assignment_version_id' => $version['id'],
            'academic_period_id' => $version['academic_period_id'],
            'unit_id' => $this->request->getPost('unit_id') ?: null,
            'teacher_id' => $teacherId,
            'duty_type_id' => $dutyTypeId,
            'title_override' => trim((string)$this->request->getPost('title_override')) ?: null,
            'workload_hours' => $hours,
            'status' => 'ACTIVE',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'created_by' => session()->get('user_id'),
        ]);

        return $this->response->setJSON(['status' => 'success', 'message' => 'Tugas tambahan berhasil dialokasikan.']);
    }

    public function updateDuty(string $uuid, int $dutyId)
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

        $teacherId = (int)$this->request->getPost('teacher_id');
        $dutyTypeId = (int)$this->request->getPost('duty_type_id');
        $hours = (float)$this->request->getPost('workload_hours');

        if ($teacherId <= 0 || $dutyTypeId <= 0 || $hours <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Pilih guru, jenis tugas tambahan, dan jumlah jam ekuivalensi.']);
        }

        $db = Database::connect();
        $duty = $db->table('teacher_additional_duties')
                   ->where('id', $dutyId)
                   ->where('assignment_version_id', $version['id'])
                   ->get()
                   ->getRowArray();

        if (!$duty) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tugas tambahan tidak ditemukan.']);
        }

        $db->table('teacher_additional_duties')->where('id', $dutyId)->update([
            'teacher_id' => $teacherId,
            'duty_type_id' => $dutyTypeId,
            'title_override' => trim((string)$this->request->getPost('title_override')) ?: null,
            'workload_hours' => $hours,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON(['status' => 'success', 'message' => 'Tugas tambahan berhasil diperbarui.']);
    }

    public function deleteDuty(string $uuid, int $dutyId)
    {
        if (!has_permission('assignments.manage')) {
            return redirect()->back()->with('error', 'Hak akses ditolak.');
        }

        $db = Database::connect();
        $db->table('teacher_additional_duties')->where('id', $dutyId)->delete();

        return redirect()->back()->with('success', 'Tugas tambahan berhasil dihapus.');
    }

    public function autoAssign(string $uuid)
    {
        if (!has_permission('assignments.manage')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Hak akses ditolak.']);
        }

        $versionModel = new AssignmentVersionModel();
        $version = $versionModel->where('uuid', $uuid)->first();
        if (!$version) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Versi penugasan tidak ditemukan.']);
        }

        $unitId = UnitScopeService::resolveUnit();
        $matrix = AssignmentMatrixService::getMatrix((int)$version['id'], $unitId);
        $teachers = (new TeacherModel())->where('is_active', 1)->findAll();

        $assignedCount = 0;
        $db = Database::connect();

        foreach ($matrix as $slot) {
            if ($slot['validation_status'] === 'UNASSIGNED' && $slot['remaining_weekly_hours'] > 0) {
                $subId = (int)$slot['subject_id'];
                $classId = (int)$slot['classroom_id'];
                $hoursNeeded = (float)$slot['remaining_weekly_hours'];

                $bestTeacher = null;
                $lowestWorkload = 999;

                foreach ($teachers as $t) {
                    $tId = (int)$t['id'];
                    $calc = TeacherWorkloadCalculationService::calculate($tId, (int)$version['id'], (int)$version['academic_period_id'], $unitId);
                    $tot = $calc['total_workload_hours'];
                    if ($tot < 24 && $tot < $lowestWorkload) {
                        $lowestWorkload = $tot;
                        $bestTeacher = $t;
                    }
                }

                if ($bestTeacher) {
                    $db->table('teaching_assignments')->insert([
                        'uuid' => UuidService::v4(),
                        'assignment_version_id' => $version['id'],
                        'academic_period_id' => $version['academic_period_id'],
                        'unit_id' => $unitId,
                        'classroom_id' => $classId,
                        'subject_id' => $subId,
                        'teacher_id' => $bestTeacher['id'],
                        'assignment_role' => 'PRIMARY',
                        'assigned_weekly_hours' => $hoursNeeded,
                        'workload_weekly_hours' => $hoursNeeded,
                        'status' => 'ACTIVE',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'created_by' => session()->get('user_id'),
                    ]);
                    $assignedCount++;
                }
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => "Asisten Cerdas berhasil mengalokasikan {$assignedCount} slot mengajar otomatis."
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

        $teacherId = (int)$this->request->getPost('teacher_id');
        if ($teacherId <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Silakan pilih Guru Pengampu.']);
        }

        // Support both single values and array inputs for flexible bulk assignment
        $classroomIds = $this->request->getPost('classroom_ids') ?: [$this->request->getPost('classroom_id')];
        $subjectIds = $this->request->getPost('subject_ids') ?: [$this->request->getPost('subject_id')];

        $classroomIds = array_values(array_filter(array_map('intval', (array)$classroomIds)));
        $subjectIds = array_values(array_filter(array_map('intval', (array)$subjectIds)));

        if (empty($classroomIds) || empty($subjectIds)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Pilih minimal satu Rombel Kelas dan satu Mata Pelajaran.']);
        }

        $role = $this->request->getPost('assignment_role') ?: 'PRIMARY';
        $manualHours = $this->request->getPost('assigned_weekly_hours');
        $useCurriculumJp = $this->request->getPost('use_curriculum_jp') !== null ? (int)$this->request->getPost('use_curriculum_jp') : 1;

        $db = \Config\Database::connect();
        $classroomModel = new ClassroomModel();
        $assignmentModel = new TeachingAssignmentModel();

        $savedCount = 0;
        $db->transBegin();

        try {
            foreach ($classroomIds as $classroomId) {
                UnitScopeService::assertClassroom($classroomId);
                $classroom = $classroomModel->find($classroomId);
                if (!$classroom) continue;
                $unitId = (int)$classroom['unit_id'];

                UnitScopeService::assertTeacherInUnit($teacherId, $unitId, (int)$version['academic_period_id']);

                foreach ($subjectIds as $subjectId) {
                    UnitScopeService::assertSubjectInUnit($subjectId, $unitId);

                    // Find curriculum structure match for this classroom & subject
                    $structure = $db->table('curriculum_structures')
                        ->where('curriculum_version_id', $version['curriculum_version_id'])
                        ->where('classroom_id', $classroomId)
                        ->where('subject_id', $subjectId)
                        ->where('status', 'ACTIVE')
                        ->where('deleted_at IS NULL')
                        ->get()
                        ->getRowArray();

                    if (!$structure) {
                        // Fallback to grade level default
                        $structure = $db->table('curriculum_structures')
                            ->where('curriculum_version_id', $version['curriculum_version_id'])
                            ->where('grade_level_id', $classroom['grade_level_id'])
                            ->where('classroom_id', null)
                            ->where('subject_id', $subjectId)
                            ->where('status', 'ACTIVE')
                            ->where('deleted_at IS NULL')
                            ->get()
                            ->getRowArray();
                    }

                    // Determine weekly JP hours
                    if ($manualHours !== null && $manualHours !== '' && (float)$manualHours > 0 && !$useCurriculumJp) {
                        $hours = (float)$manualHours;
                    } elseif ($structure && (float)$structure['effective_weekly_hours'] > 0) {
                        $hours = (float)$structure['effective_weekly_hours'];
                    } else {
                        $hours = 2.0;
                    }

                    // A PRIMARY allocation is singular per class + subject.
                    // Selecting another teacher means replacement, not an
                    // accidental second allocation with duplicated JP.
                    $existing = $assignmentModel->where('assignment_version_id', $version['id'])
                        ->where('classroom_id', $classroomId)
                        ->where('subject_id', $subjectId)
                        ->where('status', 'ACTIVE')
                        ->orderBy('is_primary_teacher', 'DESC')
                        ->orderBy('id', 'ASC')
                        ->first();

                    $aUuid = $existing ? $existing['uuid'] : UuidService::v4();
                    $data = [
                        'uuid'                    => $aUuid,
                        'assignment_version_id'   => $version['id'],
                        'curriculum_structure_id' => $structure ? $structure['id'] : null,
                        'academic_period_id'      => $version['academic_period_id'],
                        'unit_id'                 => $unitId,
                        'grade_level_id'          => $classroom['grade_level_id'],
                        'classroom_id'            => $classroomId,
                        'subject_id'              => $subjectId,
                        'teacher_id'              => $teacherId,
                        'assignment_role'         => $role,
                        'assigned_weekly_hours'   => $hours,
                        'workload_weekly_hours'   => $hours,
                        'source_weekly_hours'     => $structure ? $structure['effective_weekly_hours'] : $hours,
                        'is_primary_teacher'      => $role === 'PRIMARY' ? 1 : 0,
                        'status'                  => 'ACTIVE',
                        'revision_number'         => $existing ? ((int)($existing['revision_number'] ?? 1) + 1) : 1,
                        'updated_by'              => session()->get('user_id'),
                        'updated_at'              => date('Y-m-d H:i:s'),
                    ];

                    if ($existing) {
                        if ((int)$existing['teacher_id'] !== $teacherId) {
                            $this->assertTeacherCanTakeScheduledEntries((int)$existing['id'], $teacherId, (int)$version['academic_period_id']);
                        }
                        $assignmentModel->update($existing['id'], $data);
                        $requirementRows=$db->table('schedule_requirements')->select('id')->where('teaching_assignment_id',(int)$existing['id'])->get()->getResultArray();
                        $requirementIds=array_map('intval',array_column($requirementRows,'id'));
                        $db->table('schedule_requirements')->where('teaching_assignment_id',(int)$existing['id'])->update(['teacher_id'=>$teacherId,'required_weekly_hours'=>$hours,'updated_at'=>date('Y-m-d H:i:s')]);
                        if($requirementIds!==[]) $db->table('schedule_entries')->whereIn('schedule_requirement_id',$requirementIds)->update(['teacher_id'=>$teacherId,'updated_by'=>session()->get('user_id'),'updated_at'=>date('Y-m-d H:i:s')]);
                    } else {
                        $data['created_at'] = date('Y-m-d H:i:s');
                        $data['created_by'] = session()->get('user_id');
                        $assignmentModel->insert($data);
                    }
                    $savedCount++;
                }
            }

            $db->transCommit();

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => "Berhasil mengalokasikan penugasan guru untuk {$savedCount} kombinasi kelas & mata pelajaran."
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function updateAssignment(string $uuid, int $assignmentId)
    {
        if (!has_permission('assignments.manage')) {
            return $this->response->setStatusCode(403)->setJSON(['status'=>'error','message'=>'Hak akses ditolak.']);
        }
        $version=(new AssignmentVersionModel())->where('uuid',$uuid)->first();
        if(!$version) return $this->response->setStatusCode(404)->setJSON(['status'=>'error','message'=>'Versi penugasan tidak ditemukan.']);
        if(in_array($version['workflow_status'],['APPROVED','LOCKED','ARCHIVED'],true)) return $this->response->setStatusCode(409)->setJSON(['status'=>'error','message'=>'Versi terkunci tidak dapat diedit.']);
        $model=new TeachingAssignmentModel(); $assignment=$model->find($assignmentId);
        if(!$assignment || (int)$assignment['assignment_version_id']!==(int)$version['id']) return $this->response->setStatusCode(404)->setJSON(['status'=>'error','message'=>'Penugasan guru tidak ditemukan.']);
        UnitScopeService::assertUnit((int)$assignment['unit_id']);
        $expected=(int)$this->request->getPost('revision_number');
        if($expected<1 || $expected!==(int)($assignment['revision_number']??1)) return $this->response->setStatusCode(409)->setJSON(['status'=>'error','message'=>'Data telah berubah. Muat ulang halaman sebelum menyimpan.']);
        $teacherId=(int)$this->request->getPost('teacher_id');
        if($teacherId<1) return $this->response->setStatusCode(422)->setJSON(['status'=>'error','message'=>'Guru pengampu wajib dipilih.']);
        UnitScopeService::assertTeacherInUnit($teacherId,(int)$assignment['unit_id'],(int)$version['academic_period_id']);
        $duplicate=$model->where('assignment_version_id',$version['id'])->where('classroom_id',$assignment['classroom_id'])->where('subject_id',$assignment['subject_id'])->where('teacher_id',$teacherId)->where('status','ACTIVE')->where('id !=',$assignmentId)->first();
        if($duplicate) return $this->response->setStatusCode(409)->setJSON(['status'=>'error','message'=>'Guru tersebut sudah dialokasikan pada mata pelajaran dan kelas yang sama.']);
        $hours=(float)$this->request->getPost('assigned_weekly_hours');
        if($hours<=0) $hours=(float)$assignment['assigned_weekly_hours'];
        $role=(string)($this->request->getPost('assignment_role')?:'PRIMARY');
        if(!in_array($role,['PRIMARY','CO_TEACHER','ASSISTANT','SUBSTITUTE','OTHER'],true) || $hours<=0 || $hours>40) return $this->response->setStatusCode(422)->setJSON(['status'=>'error','message'=>'Peran atau jumlah JP tidak valid.']);
        $db=Database::connect(); $db->transBegin();
        try {
            $this->assertTeacherCanTakeScheduledEntries($assignmentId,$teacherId,(int)$version['academic_period_id']);
            $model->update($assignmentId,['teacher_id'=>$teacherId,'assignment_role'=>$role,'assigned_weekly_hours'=>$hours,'workload_weekly_hours'=>$hours,'is_primary_teacher'=>$role==='PRIMARY'?1:0,'revision_number'=>$expected+1,'updated_by'=>session()->get('user_id')]);
            $requirements=$db->table('schedule_requirements')->select('id')->where('teaching_assignment_id',$assignmentId)->get()->getResultArray();
            $requirementIds=array_map('intval',array_column($requirements,'id'));
            $db->table('schedule_requirements')->where('teaching_assignment_id',$assignmentId)->update(['teacher_id'=>$teacherId,'required_weekly_hours'=>$hours,'updated_at'=>date('Y-m-d H:i:s')]);
            if($requirementIds!==[]) $db->table('schedule_entries')->whereIn('schedule_requirement_id',$requirementIds)->update(['teacher_id'=>$teacherId,'updated_by'=>session()->get('user_id'),'updated_at'=>date('Y-m-d H:i:s')]);
            $db->transCommit();
        } catch(\Throwable $e) {
            $db->transRollback();
            return $this->response->setStatusCode(409)->setJSON(['status'=>'error','message'=>$e->getMessage()]);
        }
        return $this->response->setJSON(['status'=>'success','message'=>'Guru pengampu berhasil diperbarui tanpa membuat alokasi ganda.']);
    }

    public function deleteAssignment(string $uuid, int $assignmentId)
    {
        if(!has_permission('assignments.manage')) return redirect()->back()->with('error','Hak akses ditolak.');
        $version=(new AssignmentVersionModel())->where('uuid',$uuid)->first();
        $model=new TeachingAssignmentModel(); $assignment=$model->find($assignmentId);
        if(!$version || !$assignment || (int)$assignment['assignment_version_id']!==(int)$version['id']) return redirect()->back()->with('error','Penugasan tidak ditemukan.');
        if(in_array($version['workflow_status'],['APPROVED','LOCKED','ARCHIVED'],true)) return redirect()->back()->with('error','Versi terkunci tidak dapat diedit.');
        UnitScopeService::assertUnit((int)$assignment['unit_id']);
        $expected=(int)$this->request->getPost('revision_number');
        if($expected<1 || $expected!==(int)($assignment['revision_number']??1)) return redirect()->back()->with('error','Data telah berubah. Muat ulang halaman sebelum menghapus.');
        $db=Database::connect();
        $requirements=$db->table('schedule_requirements')->select('id')->where('teaching_assignment_id',$assignmentId)->get()->getResultArray();
        $reqIds=array_map('intval',array_column($requirements,'id'));
        if($reqIds!==[] && $db->table('schedule_entries')->whereIn('schedule_requirement_id',$reqIds)->countAllResults()>0) {
            return redirect()->back()->with('error','Penugasan sudah dipakai pada jadwal. Ganti guru melalui tombol Edit agar jadwal tetap utuh.');
        }
        $db->transException(true)->transStart();
        if($reqIds!==[]) {
            $db->table('schedule_candidate_entries')->whereIn('schedule_requirement_id',$reqIds)->delete();
            $db->table('schedule_requirements')->whereIn('id',$reqIds)->delete();
        }
        $model->update($assignmentId,['status'=>'INACTIVE','revision_number'=>(int)($assignment['revision_number']??1)+1,'updated_by'=>session()->get('user_id')]);
        $model->delete($assignmentId);
        $db->transComplete();
        return redirect()->back()->with('success','Alokasi guru berhasil dihapus.');
    }

    private function assertTeacherCanTakeScheduledEntries(int $assignmentId,int $teacherId,int $academicPeriodId): void
    {
        $db=Database::connect();
        $entries=$db->table('schedule_entries se')->select('se.id,se.schedule_version_id,sds.slot_number,sds.start_time,sds.end_time,sd.day_of_week')
            ->join('schedule_requirements sr','sr.id=se.schedule_requirement_id')->join('schedule_day_slots sds','sds.id=se.day_slot_id')->join('schedule_days sd','sd.id=sds.day_id')
            ->where('sr.teaching_assignment_id',$assignmentId)->get()->getResultArray();
        $availability=new \App\Services\TeacherAvailabilityService();
        foreach($entries as $entry){
            if(!$availability->isTeacherAvailable($teacherId,$academicPeriodId,(int)$entry['day_of_week'],(int)$entry['slot_number'])) throw new \RuntimeException('Guru pengganti tidak tersedia pada salah satu slot jadwal yang sudah terpasang.');
            $conflict=$db->table('schedule_entries se')->select('se.id,c.name class_name,s.name subject_name')
                ->join('schedule_versions sv','sv.id=se.schedule_version_id')->join('schedule_day_slots sds','sds.id=se.day_slot_id')->join('schedule_days sd','sd.id=sds.day_id')->join('classrooms c','c.id=se.classroom_id')->join('subjects s','s.id=se.subject_id','left')
                ->where('sv.academic_period_id',$academicPeriodId)->where('sd.day_of_week',(int)$entry['day_of_week'])->where('sds.start_time <',$entry['end_time'])->where('sds.end_time >',$entry['start_time'])->where('se.id !=',(int)$entry['id'])
                ->groupStart()->where('se.teacher_id',$teacherId)->orWhere('se.second_teacher_id',$teacherId)->groupEnd()->get()->getRowArray();
            if($conflict) throw new \RuntimeException('Guru pengganti bentrok dengan '.$conflict['subject_name'].' di '.$conflict['class_name'].'.');
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

    public function updateVersion(string $uuid)
    {
        if (!has_permission('assignments.manage')) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $versionModel = new AssignmentVersionModel();
        $version = $versionModel->where('uuid', $uuid)->first();
        if (!$version) {
            return redirect()->to('/assignments')->with('error', 'Versi penugasan tidak ditemukan.');
        }

        $rules = [
            'name' => 'required|min_length[3]|max_length[150]',
            'code' => 'required|min_length[3]|max_length[50]',
            'academic_period_id' => 'required|numeric',
            'curriculum_version_id' => 'required|numeric',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $isActive = (int)$this->request->getPost('is_active');
        $periodId = (int)$this->request->getPost('academic_period_id');
        $curriculumVersionId = (int)$this->request->getPost('curriculum_version_id');

        $db = \Config\Database::connect();

        // When activating, deactivate other versions for same period AND same curriculum target only
        // This allows SMP and SMA to each have one active version simultaneously
        if ($isActive === 1) {
            $db->table('assignment_versions')
                ->where('academic_period_id', $periodId)
                ->where('curriculum_version_id', $curriculumVersionId)
                ->where('id !=', $version['id'])
                ->update(['is_active' => 0]);
        }

        $versionModel->update($version['id'], [
            'name' => $this->request->getPost('name'),
            'code' => $this->request->getPost('code'),
            'academic_period_id' => $periodId,
            'curriculum_version_id' => $curriculumVersionId,
            'workflow_status' => $this->request->getPost('workflow_status') ?: $version['workflow_status'],
            'description' => $this->request->getPost('description'),
            'is_active' => $isActive,
            'updated_by' => session()->get('user_id'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/assignments')->with('success', 'Versi penugasan berhasil diperbarui.');
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
