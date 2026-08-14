<?php

namespace App\Controllers;

use App\Models\ElectiveOfferingModel;
use App\Models\ElectivePeriodModel;
use App\Services\AuditService;
use App\Services\ElectiveComplianceService;
use App\Services\ElectiveConflictMatrixService;
use App\Services\ElectivePromotionCatalog;
use App\Services\UnitScopeService;
use App\Services\WaliKelasAccessService;
use Config\Database;
use RuntimeException;

class ElectivesController extends BaseController
{
    public function index()
    {
        if (!is_super_admin() && !has_permission('electives.view') && !has_permission('class_electives.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses ke pemilihan mapel.');
        }
        $unitId = UnitScopeService::resolveUnit($this->request->getGet('unit_id'));
        $db = Database::connect();
        $periodBuilder = $db->table('elective_periods ep')
            ->select('ep.*, ay.name AS academic_year_name, COUNT(CASE WHEN eo.is_open = 1 THEN 1 END) AS open_offerings')
            ->join('academic_years ay', 'ay.id = ep.academic_year_id')
            ->join('elective_offerings eo', 'eo.elective_period_id = ep.id', 'left')
            ->where('ep.unit_id', $unitId);
        try {
            WaliKelasAccessService::scopeElectivePeriods($periodBuilder);
        } catch (RuntimeException $e) {
            return redirect()->to('/dashboard')->with('error', $e->getMessage());
        }
        $periods = $periodBuilder
            ->groupBy('ep.id')
            ->orderBy('ay.name', 'DESC')
            ->orderBy('ep.target_grade', 'ASC')
            ->get()->getResultArray();

        return view('electives/index', [
            'title' => 'Pemilihan Mata Pelajaran',
            'breadcrumb_active' => 'Pemilihan Mapel',
            'periods' => $periods,
            'unit' => Database::connect()->table('school_units')->where('id', $unitId)->get()->getRowArray(),
        ]);
    }

    public function create()
    {
        if (!is_super_admin() && !has_permission('electives.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak membuat periode pemilihan.');
        }
        $unit = UnitScopeService::getCurrentUnit();
        $unitLevel = strtoupper((string) ($unit['level'] ?? ''));

        return view('electives/create', [
            'title' => 'Buat Periode Pemilihan',
            'breadcrumb_active' => 'Periode Baru',
            'unit' => $unit,
            'unitLevel' => $unitLevel,
            'years' => Database::connect()->table('academic_years')->orderBy('name', 'DESC')->get()->getResultArray(),
            'curricula' => Database::connect()->table('curriculum_versions cv')
                ->distinct()->select('cv.id, cv.name, cv.code, ap.academic_year_id')
                ->join('academic_periods ap', 'ap.id = cv.academic_period_id')
                ->join('curriculum_structures cs', 'cs.curriculum_version_id = cv.id')
                ->where('cs.unit_id', $unit['id'])->where('cs.deleted_at IS NULL')
                ->whereIn('cv.workflow_status', ['APPROVED', 'LOCKED'])
                ->orderBy('cv.name')->get()->getResultArray(),
        ]);
    }

    public function store()
    {
        if (!is_super_admin() && !has_permission('electives.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak membuat periode pemilihan.');
        }
        $unitId = UnitScopeService::resolveUnit();
        $unit = UnitScopeService::getCurrentUnit();
        $unitLevel = strtoupper((string) ($unit['level'] ?? ''));

        if ($unitLevel !== 'SMA') {
            return redirect()->back()->withInput()->with('error', 'Pemilihan mata pelajaran pilihan hanya tersedia untuk unit SMA.');
        }

        $postData = $this->request->getPost();
        $selectionType = 'FASE_F_ELECTIVE';

        $rules = [
            'academic_year_id' => 'required|is_not_unique[academic_years.id]',
            'curriculum_version_id' => 'required|is_not_unique[curriculum_versions.id]',
            'title' => 'required|max_length[150]',
            'source_grade' => 'required|integer',
            'target_grade' => 'required|integer',
            'selection_start_at' => 'required',
            'selection_end_at' => 'required',
            'change_deadline' => 'permit_empty|valid_date',
        ];
        if (! $this->validateData($postData, $rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $start = (string) ($postData['selection_start_at'] ?? '');
        $end = (string) ($postData['selection_end_at'] ?? '');
        if (strtotime($end) <= strtotime($start)) {
            return redirect()->back()->withInput()->with('error', 'Waktu penutupan harus sesudah waktu pembukaan.');
        }
        $yearId = (int) ($postData['academic_year_id'] ?? 0);
        $curriculumId = (int) ($postData['curriculum_version_id'] ?? 0);
        $sourceGrade = (int) ($postData['source_grade'] ?? 0);
        $targetGrade = (int) ($postData['target_grade'] ?? 0);

        $validTransitions = [
            [10, 11], [11, 12], // Lintas tingkat (pra-tahun ajaran)
            [10, 10], [11, 11], [12, 12] // Tingkat sama
        ];
        $isValid = false;
        foreach ($validTransitions as $t) {
            if ($sourceGrade === $t[0] && $targetGrade === $t[1]) {
                $isValid = true;
                break;
            }
        }
        if (! $isValid) {
            return redirect()->back()->withInput()->with('error', 'Kombinasi kelas asal dan kelas tujuan Fase F SMA tidak valid.');
        }

        $db = Database::connect();
        $year = $db->table('academic_years')->where('id', $yearId)->get()->getRowArray();
        $curriculum = $db->table('curriculum_versions cv')
            ->select('cv.id')
            ->join('academic_periods ap', 'ap.id = cv.academic_period_id')
            ->join('curriculum_structures cs', 'cs.curriculum_version_id = cv.id')
            ->where('cv.id', $curriculumId)->where('ap.academic_year_id', $yearId)
            ->where('cs.unit_id', $unitId)->where('cs.deleted_at IS NULL')
            ->whereIn('cv.workflow_status', ['APPROVED', 'LOCKED'])->get()->getRowArray();
        if (! $year || ! $curriculum) {
            return redirect()->back()->withInput()->with('error', 'Versi kurikulum tidak sah untuk unit dan tahun pelajaran ini.');
        }

        $startDate = date('Y-m-d', strtotime($start));
        $endDate = date('Y-m-d', strtotime($end));
        $changeDeadline = $this->request->getPost('change_deadline') ?: null;
        if ($startDate < $year['start_date'] || $endDate > $year['end_date']
            || ($changeDeadline && ($changeDeadline < $year['start_date'] || $changeDeadline > $year['end_date']))) {
            return redirect()->back()->withInput()->with('error', 'Periode pemilihan dan batas perubahan harus berada dalam tahun pelajaran.');
        }
        // For Kelas XII, change requests are disabled by regulation (fixed selection)
        $allowChanges = ($targetGrade === 12) ? 0 : ($this->request->getPost('allow_changes') ? 1 : 0);

        $minPrimary = 4;
        $maxPrimary = 5;
        $minOffered = 7;
        $maxBackup = max(0, min(5, (int) $this->request->getPost('max_backup_choices')));

        try {
            $id = (new ElectivePeriodModel())->insert([
                'unit_id' => $unitId,
                'academic_year_id' => $yearId,
                'curriculum_version_id' => $curriculumId,
                'title' => trim((string) $this->request->getPost('title')),
                'selection_type' => $selectionType,
                'source_grade' => $sourceGrade,
                'target_grade' => $targetGrade,
                'selection_start_at' => date('Y-m-d H:i:s', strtotime($start)),
                'selection_end_at' => date('Y-m-d H:i:s', strtotime($end)),
                'min_primary_choices' => $minPrimary,
                'max_primary_choices' => $maxPrimary,
                'max_backup_choices' => $maxBackup,
                'minimum_subjects_offered' => $minOffered,
                'allow_changes' => $allowChanges,
                'change_deadline' => $changeDeadline,
                'status' => 'DRAFT',
                'notes' => trim((string) $this->request->getPost('notes')) ?: null,
                'created_by' => session()->get('user_id'),
            ], true);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        AuditService::log('electives', 'create_period', 'ElectivePeriod', $id, null, $postData, 'Membuat periode pemilihan mapel pilihan SMA');
        return redirect()->to('/electives/' . $id)->with('success', 'Periode pemilihan mapel pilihan berhasil dibuat.');
    }

    public function show(int $id)
    {
        if (!is_super_admin() && !has_permission('electives.view') && !has_permission('class_electives.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses ke pemilihan mapel.');
        }
        [$period, $offerings] = $this->periodContext($id);
        $db = Database::connect();
        $subjectBuilder = $db->table('subjects s')
            ->select('s.id, s.code, s.name')
            ->join('subject_unit_availability sua', 'sua.subject_id = s.id')
            ->join('curriculum_structures cs', 'cs.subject_id = s.id AND cs.unit_id = sua.unit_id')
            ->join('grade_levels gl', 'gl.id = cs.grade_level_id')
            ->where('sua.unit_id', $period['unit_id'])
            ->where('sua.is_available', 1)->where('s.is_active', 1)
            ->where('cs.curriculum_version_id', $period['curriculum_version_id'] ?: 0)
            ->where('gl.grade_number', $period['target_grade'])
            ->where('cs.deleted_at IS NULL')->where('cs.status', 'ACTIVE')
            ->where('s.category', 'PILIHAN');

        if ($offerings !== []) {
            $subjectBuilder->whereNotIn('s.id', array_column($offerings, 'subject_id'));
        }

        $teachers = $db->table('teachers t')
            ->select('t.id, t.full_name')
            ->join('teacher_unit_assignments tua', 'tua.teacher_id = t.id')
            ->where('tua.unit_id', $period['unit_id'])->where('tua.status', 'ACTIVE')
            ->where('t.is_active', 1)->groupBy('t.id')->orderBy('t.full_name')->get()->getResultArray();
        $classContext = WaliKelasAccessService::isElectiveClassScoped()
            ? WaliKelasAccessService::classroomContext()
            : null;
        if (WaliKelasAccessService::isElectiveClassScoped() && !$classContext) {
            throw new RuntimeException('Akun wali kelas belum ditautkan ke rombel. Hubungi superadmin.');
        }

        $choiceBuilder = $db->table('student_elective_choices sec')
            ->select('sec.submission_id, sec.offering_id, sec.choice_type')
            ->join('student_elective_submissions ses', 'ses.id = sec.submission_id')
            ->where('ses.elective_period_id', $period['id'])
            ->whereIn('ses.status', ['SUBMITTED', 'WAITING_CURRICULUM', 'APPROVED', 'FINALIZED', 'CHANGE_REQUESTED', 'CHANGED']);
        if ($classContext) {
            $choiceBuilder
                ->join('elective_students choice_student', 'choice_student.id = ses.student_id')
                ->where('choice_student.classroom_id', $classContext['classroom_id']);
        }
        $choiceRows = $choiceBuilder->get()->getResultArray();

        $studentBuilder = $db->table('elective_students es')
                ->select('es.*, ses.id AS submission_id, ses.status AS submission_status, c.code AS classroom_code, c.name AS classroom_name')
                ->join('student_elective_submissions ses', 'ses.student_id = es.id AND ses.elective_period_id = ' . (int) $period['id'], 'left')
                ->join('classrooms c', 'c.id = es.classroom_id', 'left')
                ->where('es.unit_id', $period['unit_id'])
                ->where('es.academic_year_id', $period['academic_year_id'])
                ->where('es.current_grade', $period['source_grade'])->where('es.is_active', 1);
        if ($classContext) {
            $studentBuilder->where('es.classroom_id', $classContext['classroom_id']);
        }

        $classroomBuilder = $db->table('classrooms c')
            ->join('grade_levels gl', 'gl.id = c.grade_level_id')
            ->where('c.unit_id', $period['unit_id'])
            ->where('gl.grade_number', $period['source_grade'])
            ->where('c.is_active', 1)
            ->where('c.deleted_at IS NULL');
        if (isset($classContext)) {
            $classroomBuilder->where('c.id', $classContext['classroom_id']);
        }

        $offeringStudentsBuilder = $db->table('student_elective_choices sec')
            ->select('sec.offering_id, sec.choice_type, sec.priority_order, es.student_number, es.full_name, c.code AS classroom_code, c.name AS classroom_name, ses.status AS submission_status')
            ->join('student_elective_submissions ses', 'ses.id = sec.submission_id')
            ->join('elective_students es', 'es.id = ses.student_id')
            ->join('classrooms c', 'c.id = es.classroom_id', 'left')
            ->where('ses.elective_period_id', $period['id'])
            ->whereIn('ses.status', ['SUBMITTED', 'WAITING_CURRICULUM', 'APPROVED', 'FINALIZED', 'CHANGE_REQUESTED', 'CHANGED'])
            ->orderBy('c.code', 'ASC')
            ->orderBy('es.full_name', 'ASC');

        if ($classContext) {
            $offeringStudentsBuilder->where('es.classroom_id', $classContext['classroom_id']);
        }
        $offeringStudentRows = $offeringStudentsBuilder->get()->getResultArray();
        $offeringStudents = [];
        foreach ($offeringStudentRows as $r) {
            $offeringStudents[(int)$r['offering_id']][$r['choice_type']][] = $r;
        }

        return view('electives/show', [
            'title' => 'Rancangan Pemilihan Mapel',
            'breadcrumb_active' => 'Rancangan Mapel Pilihan',
            'period' => $period,
            'offerings' => $offerings,
            'assessment' => (new ElectiveComplianceService())->assessPeriod($period, $offerings),
            'subjects' => $subjectBuilder->orderBy('s.name')->get()->getResultArray(),
            'teachers' => $teachers,
            'conflictMatrix' => (new ElectiveConflictMatrixService())->build(array_column($offerings, 'id'), $choiceRows),
            'classrooms' => $classroomBuilder
                ->select('c.id, c.code, c.name')->orderBy('c.code')->get()->getResultArray(),
            'students' => $studentBuilder->orderBy('es.full_name')->get()->getResultArray(),
            'offeringStudents' => $offeringStudents,
        ]);
    }

    public function addOffering(int $id)
    {
        if (!is_super_admin() && !has_permission('electives.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak mengelola penawaran mapel.');
        }
        [$period] = $this->periodContext($id);
        if ($period['status'] !== 'DRAFT') {
            return redirect()->back()->with('error', 'Daftar mapel hanya dapat diubah ketika periode berstatus draf.');
        }
        $rules = [
            'subject_id' => 'required|integer',
            'teacher_id' => 'permit_empty|integer',
            'minimum_students' => 'required|integer|greater_than[0]',
            'maximum_students' => 'required|integer|greater_than[0]',
            'weekly_hours' => 'required|decimal|greater_than[0]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        try {
            UnitScopeService::assertSubjectInUnit((int) $this->request->getPost('subject_id'), (int) $period['unit_id']);
            $subjectId = (int) $this->request->getPost('subject_id');
            $subject = Database::connect()->table('subjects')
                ->where('id', $subjectId)->where('is_active', 1)->where('category', 'PILIHAN')->get()->getRowArray();
            $inCurriculum = Database::connect()->table('curriculum_structures cs')
                ->join('grade_levels gl', 'gl.id = cs.grade_level_id')
                ->where('cs.curriculum_version_id', $period['curriculum_version_id'])
                ->where('cs.unit_id', $period['unit_id'])->where('cs.subject_id', $subjectId)
                ->where('gl.grade_number', $period['target_grade'])->where('cs.deleted_at IS NULL')
                ->where('cs.status', 'ACTIVE')->get()->getRowArray();
            if (! $subject || ! $inCurriculum) {
                throw new RuntimeException('Mapel harus berkategori PILIHAN dan tercantum pada struktur kurikulum tingkat tujuan.');
            }
            $teacherId = (int) $this->request->getPost('teacher_id');
            if ($teacherId > 0) {
                UnitScopeService::assertTeacherInUnit($teacherId, (int) $period['unit_id']);
            }
            $minimum = (int) $this->request->getPost('minimum_students');
            $maximum = (int) $this->request->getPost('maximum_students');
            if ($maximum < $minimum) {
                throw new RuntimeException('Kapasitas maksimum tidak boleh lebih kecil dari minimum peminat.');
            }
            (new ElectiveOfferingModel())->insert([
                'elective_period_id' => $period['id'],
                'subject_id' => $subjectId,
                'teacher_id' => $teacherId ?: null,
                'minimum_students' => $minimum,
                'maximum_students' => $maximum,
                'weekly_hours' => (float) $this->request->getPost('weekly_hours'),
                'description' => trim((string) $this->request->getPost('description')) ?: null,
                'study_relevance' => trim((string) $this->request->getPost('study_relevance')) ?: null,
                'prerequisites' => trim((string) $this->request->getPost('prerequisites')) ?: null,
                'is_open' => 1,
                'created_by' => session()->get('user_id'),
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
        return redirect()->to('/electives/' . $id)->with('success', 'Mata pelajaran ditambahkan ke penawaran.');
    }

    public function removeOffering(int $periodId, int $offeringId)
    {
        if (!is_super_admin() && !has_permission('electives.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak mengelola penawaran mapel.');
        }
        [$period] = $this->periodContext($periodId);
        if ($period['status'] !== 'DRAFT') {
            return redirect()->back()->with('error', 'Penawaran hanya dapat dihapus ketika periode berstatus draf.');
        }
        $offering = Database::connect()->table('elective_offerings')
            ->where('id', $offeringId)->where('elective_period_id', $periodId)->get()->getRowArray();
        if (! $offering) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        Database::connect()->table('elective_offerings')->where('id', $offeringId)->delete();
        AuditService::log('electives', 'remove_offering', 'ElectiveOffering', $offeringId, $offering, null, 'Penawaran mapel dihapus dari draf');
        return redirect()->to('/electives/' . $periodId)->with('success', 'Penawaran mata pelajaran dihapus.');
    }

    public function updateOffering(int $periodId, int $offeringId)
    {
        if (!is_super_admin() && !has_permission('electives.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak mengelola penawaran mapel.');
        }
        [$period] = $this->periodContext($periodId);
        if ($period['status'] !== 'DRAFT') {
            return redirect()->back()->with('error', 'Penawaran hanya dapat diubah ketika periode berstatus draf.');
        }
        $db = Database::connect();
        $offering = $db->table('elective_offerings')
            ->where('id', $offeringId)->where('elective_period_id', $periodId)->get()->getRowArray();
        if (! $offering) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $rules = [
            'teacher_id'       => 'permit_empty|integer',
            'minimum_students' => 'required|integer|greater_than[0]',
            'maximum_students' => 'required|integer|greater_than[0]',
            'weekly_hours'     => 'required|decimal|greater_than[0]',
            'description'      => 'permit_empty|max_length[1000]',
            'study_relevance'  => 'permit_empty|max_length[1000]',
            'prerequisites'    => 'permit_empty|max_length[500]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $teacherId = (int) $this->request->getPost('teacher_id');
        if ($teacherId > 0) {
            UnitScopeService::assertTeacherInUnit($teacherId, (int) $period['unit_id']);
        }
        $minimum = (int) $this->request->getPost('minimum_students');
        $maximum = (int) $this->request->getPost('maximum_students');
        if ($maximum < $minimum) {
            return redirect()->back()->with('error', 'Kapasitas maksimum tidak boleh lebih kecil dari minimum peminat.');
        }

        $curriculumStructure = Database::connect()->table('curriculum_structures cs')
            ->select('cs.effective_weekly_hours')
            ->join('grade_levels gl', 'gl.id = cs.grade_level_id')
            ->where('cs.curriculum_version_id', $period['curriculum_version_id'])
            ->where('cs.unit_id', $period['unit_id'])->where('cs.subject_id', $offering['subject_id'])
            ->where('gl.grade_number', $period['target_grade'])->where('cs.deleted_at IS NULL')
            ->where('cs.status', 'ACTIVE')->get()->getRowArray();
        $effectiveHours = (float)($curriculumStructure['effective_weekly_hours'] ?? $this->request->getPost('weekly_hours'));

        $old = $offering;
        $db->table('elective_offerings')->where('id', $offeringId)->update([
            'teacher_id'       => $teacherId ?: null,
            'minimum_students' => $minimum,
            'maximum_students' => $maximum,
            'weekly_hours'     => $effectiveHours,
            'description'      => trim((string) $this->request->getPost('description')) ?: null,
            'study_relevance'  => trim((string) $this->request->getPost('study_relevance')) ?: null,
            'prerequisites'    => trim((string) $this->request->getPost('prerequisites')) ?: null,
            'is_open'          => $this->request->getPost('is_open') ? 1 : 0,
            'updated_by'       => session()->get('user_id'),
        ]);
        AuditService::log('electives', 'update_offering', 'ElectiveOffering', $offeringId, $old, $this->request->getPost(), 'Penawaran mapel diperbarui');
        return redirect()->to('/electives/' . $periodId)->with('success', 'Penawaran mapel berhasil diperbarui.');
    }

    public function toggleOfferingApproval(int $periodId, int $offeringId)
    {
        if (!is_super_admin() && !has_permission('electives.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak mengelola persetujuan penawaran mapel.');
        }
        [$period] = $this->periodContext($periodId);
        $db = Database::connect();
        $offering = $db->table('elective_offerings')
            ->where('id', $offeringId)->where('elective_period_id', $periodId)->get()->getRowArray();
        if (!$offering) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $newApproved = (int)($offering['is_approved'] ?? 1) === 1 ? 0 : 1;
        $db->table('elective_offerings')->where('id', $offeringId)->update([
            'is_approved' => $newApproved,
            'approved_at' => $newApproved ? date('Y-m-d H:i:s') : null,
            'approved_by' => $newApproved ? session()->get('user_id') : null,
            'updated_by'  => session()->get('user_id'),
        ]);

        $statusMsg = $newApproved ? 'disetujui untuk dikirim ke jadwal' : 'ditahan (batal disetujui untuk jadwal)';
        AuditService::log('electives', 'toggle_offering_approval', 'ElectiveOffering', $offeringId, $offering, ['is_approved' => $newApproved], "Penawaran mapel {$statusMsg}");
        return redirect()->to('/electives/' . $periodId)->with('success', "Mata pelajaran pilihan berhasil {$statusMsg}.");
    }

    public function approveAllEligibleOfferings(int $periodId)
    {
        if (!is_super_admin() && !has_permission('electives.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak mengelola persetujuan penawaran mapel.');
        }
        [$period] = $this->periodContext($periodId);
        $db = Database::connect();
        $db->table('elective_offerings')
            ->where('elective_period_id', $periodId)
            ->where('is_open', 1)
            ->update([
                'is_approved' => 1,
                'approved_at' => date('Y-m-d H:i:s'),
                'approved_by' => session()->get('user_id'),
                'updated_by'  => session()->get('user_id'),
            ]);

        AuditService::log('electives', 'approve_all_offerings', 'ElectivePeriod', $periodId, null, null, 'Semua penawaran mapel disetujui untuk jadwal');
        return redirect()->to('/electives/' . $periodId)->with('success', 'Seluruh mata pelajaran pilihan yang ditawarkan berhasil disetujui untuk dikirim ke jadwal.');
    }

    public function publish(int $id)
    {
        if (!is_super_admin() && !has_permission('electives.publish')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak mempublikasikan periode pemilihan.');
        }
        [$period, $offerings] = $this->periodContext($id);
        // Kelas XII is a continuation/fixed selection. Normalize legacy rows
        // during the explicit publish action so stale change settings cannot
        // block publication or accidentally allow post-approval changes.
        if ((int) $period['target_grade'] === 12) {
            $period['allow_changes'] = 0;
            $period['change_deadline'] = null;
        }
        $assessment = (new ElectiveComplianceService())->assessPeriod($period, $offerings);
        if (! $assessment['compliant']) {
            return redirect()->back()->with('error', 'Belum dapat dipublikasikan: ' . implode(' ', $assessment['errors']));
        }
        $db = Database::connect();
        $publishData = [
            'status' => 'PUBLISHED', 'updated_by' => session()->get('user_id'),
            'updated_at' => date('Y-m-d H:i:s'), 'revision_number' => (int) $period['revision_number'] + 1,
        ];
        if ((int) $period['target_grade'] === 12) {
            $publishData['allow_changes'] = 0;
            $publishData['change_deadline'] = null;
        }
        $db->table('elective_periods')->where('id', $id)->where('status', 'DRAFT')->update($publishData);
        if ($db->affectedRows() !== 1) {
            return redirect()->back()->with('error', 'Status periode telah berubah. Muat ulang halaman sebelum mempublikasikan.');
        }
        AuditService::log('electives', 'publish', 'ElectivePeriod', $id, ['status' => 'DRAFT'], ['status' => 'PUBLISHED'], 'Periode mapel pilihan dipublikasikan');
        return redirect()->to('/electives/' . $id)->with('success', 'Periode dipublikasikan setelah lulus pemeriksaan kepatuhan.');
    }

    public function update(int $id)
    {
        if (!is_super_admin() && !has_permission('electives.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak mengubah periode pemilihan.');
        }
        [$period] = $this->periodContext($id);
        $postData = $this->request->getPost();
        $rules = [
            'title'              => 'required|max_length[150]',
            'selection_start_at' => 'required',
            'selection_end_at'   => 'required',
            'change_deadline'    => 'permit_empty|valid_date',
            'notes'              => 'permit_empty|max_length[500]',
        ];
        if (! $this->validateData($postData, $rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $start = (string) ($postData['selection_start_at'] ?? '');
        $end   = (string) ($postData['selection_end_at'] ?? '');
        if (strtotime($end) <= strtotime($start)) {
            return redirect()->back()->with('error', 'Waktu penutupan harus sesudah waktu pembukaan.');
        }

        $db   = Database::connect();
        $year = $db->table('academic_years')->where('id', $period['academic_year_id'])->get()->getRowArray();
        $startDate      = date('Y-m-d', strtotime($start));
        $endDate         = date('Y-m-d', strtotime($end));
        $changeDeadline = trim((string) ($postData['change_deadline'] ?? '')) ?: null;
        if ($year && ($startDate < $year['start_date'] || $endDate > $year['end_date']
            || ($changeDeadline && ($changeDeadline < $year['start_date'] || $changeDeadline > $year['end_date'])))) {
            return redirect()->back()->with('error', 'Periode pemilihan dan batas perubahan harus berada dalam tahun pelajaran.');
        }

        $old = $period;
        $allowChanges = ((int) $period['target_grade'] === 12) ? 0 : ($this->request->getPost('allow_changes') ? 1 : 0);

        $db->table('elective_periods')->where('id', $id)->update([
            'title'              => trim((string) $postData['title']),
            'selection_start_at' => date('Y-m-d H:i:s', strtotime($start)),
            'selection_end_at'   => date('Y-m-d H:i:s', strtotime($end)),
            'change_deadline'    => $changeDeadline,
            'allow_changes'      => $allowChanges,
            'notes'              => trim((string) ($postData['notes'] ?? '')) ?: null,
            'updated_by'         => session()->get('user_id'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);
        AuditService::log('electives', 'update_period', 'ElectivePeriod', $id, $old, $postData, 'Periode pemilihan mapel diperbarui');
        return redirect()->to('/electives/' . $id)->with('success', 'Periode pemilihan berhasil diperbarui.');
    }

    /**
     * Stable CSV hand-off for grouping and schedule preparation.
     * One row represents one submitted choice, retaining priority and class.
     */
    /**
     * Export rekapitulasi pilihan mata pelajaran pilihan (Fase F SMA)
     * ke format Excel (.xlsx) interaktif & terstruktur sempurna,
     * dengan opsi parameter ?format=csv untuk ekspor CSV mentah.
     */
    public function exportSelections(int $id)
    {
        if (!is_super_admin() && !has_permission('electives.view') && !has_permission('class_electives.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak melihat rekap pilihan.');
        }
        [$period] = $this->periodContext($id);

        $format = strtolower((string) $this->request->getGet('format'));
        if ($format === 'csv') {
            return $this->exportSelectionsCsv($period, $id);
        }

        $scopedClassroom = null;
        if (WaliKelasAccessService::isElectiveClassScoped()) {
            $scopedClassroom = WaliKelasAccessService::classroomContext();
            if (!$scopedClassroom) {
                return redirect()->back()->with('error', 'Akun wali kelas belum ditautkan ke rombel.');
            }
        }

        try {
            $exportService = new \App\Services\ElectiveExportService();
            $spreadsheet = $exportService->generateSpreadsheet($id, $scopedClassroom);

            $filename = 'Rekap_Pemilihan_Mapel_Pilihan_SMA_Periode_' . $id . '.xlsx';
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            ob_start();
            $writer->save('php://output');
            $content = ob_get_clean();

            return $this->response
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->setHeader('Cache-Control', 'max-age=0')
                ->setBody($content);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal memproses ekspor Excel rekap pilihan periode {periodId}: {message}', [
                'periodId' => $id,
                'message'  => $e->getMessage(),
            ]);
            return redirect()->back()->with('error', 'Gagal menghasilkan berkas Excel: ' . $e->getMessage());
        }
    }

    private function exportSelectionsCsv(array $period, int $id)
    {
        $db = Database::connect();
        $builder = $db->table('student_elective_choices sec')
            ->select('es.student_number, es.full_name, c.code AS classroom_code, c.name AS classroom_name, ses.status AS submission_status, s.code AS subject_code, s.name AS subject_name, sec.choice_type, sec.priority_order, sec.allocation_status')
            ->join('student_elective_submissions ses', 'ses.id = sec.submission_id')
            ->join('elective_students es', 'es.id = ses.student_id')
            ->join('elective_offerings eo', 'eo.id = sec.offering_id')
            ->join('subjects s', 's.id = eo.subject_id')
            ->join('classrooms c', 'c.id = es.classroom_id', 'left')
            ->where('ses.elective_period_id', $id)
            ->whereIn('ses.status', ['SUBMITTED', 'WAITING_CURRICULUM', 'APPROVED', 'FINALIZED', 'CHANGE_REQUESTED', 'CHANGED'])
            ->orderBy('c.code')->orderBy('es.full_name')->orderBy('sec.choice_type')->orderBy('sec.priority_order');

        if (WaliKelasAccessService::isElectiveClassScoped()) {
            $classContext = WaliKelasAccessService::classroomContext();
            if (!$classContext) {
                return redirect()->back()->with('error', 'Akun wali kelas belum ditautkan ke rombel.');
            }
            $builder->where('es.classroom_id', $classContext['classroom_id']);
        }

        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['periode', 'tingkat_asal', 'tingkat_tujuan', 'nomor_induk', 'nama_siswa', 'kode_rombel', 'nama_rombel', 'status_pengajuan', 'jenis_pilihan', 'prioritas', 'kode_mapel', 'nama_mapel', 'status_alokasi']);
        foreach ($builder->get()->getResultArray() as $row) {
            fputcsv($handle, [
                $period['title'], $period['source_grade'], $period['target_grade'], $row['student_number'],
                $row['full_name'], $row['classroom_code'] ?? '', $row['classroom_name'] ?? '', $row['submission_status'],
                $row['choice_type'], $row['priority_order'], $row['subject_code'], $row['subject_name'], $row['allocation_status'],
            ]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $this->response->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="rekap-pilihan-' . $id . '.csv"')
            ->setBody("\xEF\xBB\xBF" . $csv);
    }

    private function periodContext(int $id): array
    {
        $period = Database::connect()->table('elective_periods ep')
            ->select('ep.*, ay.name AS academic_year_name, su.name AS unit_name, cv.workflow_status AS curriculum_status')
            ->join('academic_years ay', 'ay.id = ep.academic_year_id')
            ->join('school_units su', 'su.id = ep.unit_id')
            ->join('curriculum_versions cv', 'cv.id = ep.curriculum_version_id', 'left')
            ->where('ep.id', $id)->get()->getRowArray();
        if (! $period) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        UnitScopeService::assertUnit((int) $period['unit_id']);
        if (!WaliKelasAccessService::canAccessElectivePeriod($period)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Periode pemilihan tidak tersedia untuk jenjang kelas binaan Anda.'
            );
        }
        $offerings = Database::connect()->table('elective_offerings eo')
            ->select('eo.*, s.code AS subject_code, s.name AS subject_name, s.is_active AS subject_is_active, s.category AS subject_category, t.full_name AS teacher_name')
            ->join('subjects s', 's.id = eo.subject_id')
            ->join('teachers t', 't.id = eo.teacher_id', 'left')
            ->where('eo.elective_period_id', $id)->orderBy('s.name')->get()->getResultArray();
        $offerings = array_map([ElectivePromotionCatalog::class, 'enrich'], $offerings);
        $curriculumStructures = Database::connect()->table('curriculum_structures cs')
            ->select('cs.subject_id, cs.effective_weekly_hours')
            ->join('grade_levels gl', 'gl.id = cs.grade_level_id')
            ->where('cs.curriculum_version_id', $period['curriculum_version_id'] ?: 0)
            ->where('cs.unit_id', $period['unit_id'])
            ->where('gl.grade_number', $period['target_grade'])
            ->where('cs.deleted_at IS NULL')
            ->where('cs.status', 'ACTIVE')
            ->get()->getResultArray();
        $curriculumHoursMap = [];
        foreach ($curriculumStructures as $csRow) {
            $curriculumHoursMap[(int)$csRow['subject_id']] = (float)$csRow['effective_weekly_hours'];
        }
        $curriculumSubjectIds = array_keys($curriculumHoursMap);
        $availableTeacherIds = Database::connect()->table('teachers t')->distinct()->select('t.id')
            ->join('teacher_unit_assignments tua', 'tua.teacher_id = t.id')
            ->where('tua.unit_id', $period['unit_id'])->where('tua.status', 'ACTIVE')
            ->where('t.is_active', 1)->where('t.deleted_at IS NULL')->get()->getResultArray();
        $availableTeacherIds = array_map('intval', array_column($availableTeacherIds, 'id'));
        $demandBuilder = Database::connect()->table('student_elective_choices sec')
            ->select('sec.offering_id, sec.choice_type, COUNT(*) AS total')
            ->join('student_elective_submissions ses', 'ses.id = sec.submission_id')
            ->where('ses.elective_period_id', $period['id'])
            ->whereIn('ses.status', ['SUBMITTED', 'WAITING_CURRICULUM', 'APPROVED', 'FINALIZED', 'CHANGE_REQUESTED', 'CHANGED'])
            ->groupBy(['sec.offering_id', 'sec.choice_type']);
        if (WaliKelasAccessService::isElectiveClassScoped()) {
            $classContext = WaliKelasAccessService::classroomContext();
            $demandBuilder
                ->join('elective_students demand_student', 'demand_student.id = ses.student_id')
                ->where('demand_student.classroom_id', $classContext['classroom_id']);
        }
        $demandRows = $demandBuilder->get()->getResultArray();
        $demand = [];
        foreach ($demandRows as $row) {
            $demand[(int) $row['offering_id']][$row['choice_type']] = (int) $row['total'];
        }
        foreach ($offerings as &$offering) {
            $subId = (int) $offering['subject_id'];
            $offering['in_curriculum'] = in_array($subId, $curriculumSubjectIds, true) ? 1 : 0;
            if (isset($curriculumHoursMap[$subId])) {
                $offering['weekly_hours'] = $curriculumHoursMap[$subId];
            }
            $offering['teacher_available'] = $offering['teacher_id']
                && in_array((int) $offering['teacher_id'], $availableTeacherIds, true) ? 1 : 0;
            $offering['primary_interest'] = $demand[(int) $offering['id']]['PRIMARY'] ?? 0;
            $offering['backup_interest'] = $demand[(int) $offering['id']]['BACKUP'] ?? 0;
            $offering['demand_status'] = $offering['primary_interest'] > (int) $offering['maximum_students']
                ? 'KUOTA TERLAMPAUI'
                : ($offering['primary_interest'] >= (int) $offering['minimum_students'] ? 'LAYAK DIBUKA' : 'BELUM MINIMUM');
        }
        unset($offering);
        return [$period, $offerings];
    }
}
