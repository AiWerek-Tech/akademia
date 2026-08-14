<?php

namespace App\Controllers;

use App\Services\ElectiveSelectionService;
use App\Services\ElectivePromotionCatalog;
use App\Services\UnitScopeService;
use App\Services\WaliKelasAccessService;
use App\Services\UuidService;
use Config\Database;
use RuntimeException;

class ElectiveSelectionsController extends BaseController
{
    public function enroll(int $periodId)
    {
        if (!is_super_admin() && !has_permission('electives.participants.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak mengelola peserta pemilihan.');
        }
        $period = $this->period($periodId);
        $rules = [
            'student_number' => 'required|max_length[40]',
            'full_name'      => 'required|max_length[150]',
            'user_id'        => 'permit_empty|integer',
            'classroom_id'   => 'permit_empty|integer',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $userId = (int) $this->request->getPost('user_id');
        try {
            if ($userId > 0) {
                $this->assertStudentUser($userId, (int) $period['unit_id']);
            }
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
        $classroomId = (int) $this->request->getPost('classroom_id');
        if ($classroomId > 0) {
            $classroom = Database::connect()->table('classrooms c')
                ->select('c.id')
                ->join('grade_levels gl', 'gl.id = c.grade_level_id')
                ->where('c.id', $classroomId)->where('c.unit_id', $period['unit_id'])
                ->where('gl.grade_number', $period['source_grade'])->where('c.is_active', 1)
                ->get()->getRowArray();
            if (!$classroom) {
                return redirect()->back()->withInput()->with('error', 'Rombel tidak sesuai unit dan tingkat periode pemilihan.');
            }
        }
        try {
            Database::connect()->table('elective_students')->insert([
                'uuid'             => UuidService::v4(),
                'user_id'          => $userId ?: null,
                'unit_id'          => $period['unit_id'],
                'academic_year_id' => $period['academic_year_id'],
                'student_number'   => trim((string) $this->request->getPost('student_number')),
                'full_name'        => trim((string) $this->request->getPost('full_name')),
                'current_grade'    => $period['source_grade'],
                'classroom_id'     => $classroomId ?: null,
                'is_active'        => 1,
                'created_by'       => session()->get('user_id'),
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Nomor induk atau akun siswa sudah terdaftar untuk tahun pelajaran ini.');
        }
        return redirect()->to('/electives/' . $periodId)->with('success', 'Peserta pemilihan berhasil didaftarkan.');
    }

    public function syncRombel(int $periodId)
    {
        if (!is_super_admin() && !has_permission('electives.participants.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak mengelola peserta pemilihan.');
        }
        $period = $this->period($periodId);
        $db = Database::connect();

        $classrooms = $db->table('classrooms c')
            ->join('grade_levels gl', 'gl.id = c.grade_level_id')
            ->where('c.unit_id', $period['unit_id'])
            ->where('gl.grade_number', $period['source_grade'])
            ->select('c.id, c.code, c.name')
            ->get()->getResultArray();

        if ($classrooms === []) {
            return redirect()->back()->with('error', 'Belum ada Rombel/Kelas untuk Tingkat ' . $period['source_grade'] . ' di Master Data.');
        }

        $usersSiswa = $db->table('users u')->distinct()
            ->join('user_roles ur', 'ur.user_id = u.id')
            ->join('roles r', 'r.id = ur.role_id')
            ->join('user_unit_access uua', 'uua.user_id = u.id')
            ->where('r.code', 'siswa')
            ->where('uua.unit_id', $period['unit_id'])
            ->where('u.is_active', 1)
            ->where('u.deleted_at IS NULL')
            ->select('u.id, u.username, u.full_name, u.classroom_id')
            ->get()->getResultArray();

        $enrolledCount = 0;
        $classCount = count($classrooms);

        if ($usersSiswa !== []) {
            foreach ($usersSiswa as $idx => $user) {
                $exists = $db->table('elective_students')
                    ->where('unit_id', $period['unit_id'])
                    ->where('academic_year_id', $period['academic_year_id'])
                    ->where('user_id', $user['id'])
                    ->get()->getRowArray();

                if (! $exists) {
                    $assignedClass = null;
                    foreach ($classrooms as $candidate) {
                        if ((int) $candidate['id'] === (int) ($user['classroom_id'] ?? 0)) {
                            $assignedClass = $candidate;
                            break;
                        }
                    }
                    // Never guess a rombel. An administrator must link the account
                    // explicitly when the master data has no classroom relation.
                    if (!$assignedClass) continue;
                    $db->table('elective_students')->insert([
                        'uuid'             => UuidService::v4(),
                        'user_id'          => $user['id'],
                        'unit_id'          => $period['unit_id'],
                        'academic_year_id' => $period['academic_year_id'],
                        'student_number'   => $user['username'],
                        'full_name'        => $user['full_name'],
                        'current_grade'    => $period['source_grade'],
                        'classroom_id'     => $assignedClass['id'],
                        'is_active'        => 1,
                        'created_by'       => session()->get('user_id'),
                        'created_at'       => date('Y-m-d H:i:s'),
                        'updated_at'       => date('Y-m-d H:i:s'),
                    ]);
                    $enrolledCount++;
                }
            }
        }

        if ($enrolledCount === 0) {
            return redirect()->to('/electives/' . $periodId)->with('info', 'Tidak ada peserta baru dengan tautan rombel yang valid. Tidak ada data yang ditebak atau dipindahkan.');
        }

        return redirect()->to('/electives/' . $periodId)->with('success', 'Berhasil mengaitkan ' . $enrolledCount . ' peserta dengan Rombel/Kelas Tingkat ' . $period['source_grade'] . '.');
    }

    public function importStudents(int $periodId)
    {
        if (!is_super_admin() && !has_permission('electives.participants.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak mengelola peserta pemilihan.');
        }
        $period = $this->period($periodId);
        $file = $this->request->getFile('student_file');
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return redirect()->back()->with('error', 'File CSV yang diunggah tidak valid.');
        }

        $db = Database::connect();
        $handle = fopen($file->getTempName(), 'r');
        if (! $handle) {
            return redirect()->back()->with('error', 'Gagal membaca berkas CSV.');
        }

        $header = fgetcsv($handle, 1000, ',');
        $importedCount = 0;
        $updatedCount = 0;
        $db->transBegin();

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if (count($row) < 2) continue;
            $studentNumber = trim((string) ($row[0] ?? ''));
            $fullName      = trim((string) ($row[1] ?? ''));
            $classCode     = trim((string) ($row[2] ?? ''));
            $userId        = (int) ($row[3] ?? 0);

            if ($studentNumber === '' || $fullName === '') continue;

            if ($userId > 0) {
                try {
                    $this->assertStudentUser($userId, (int) $period['unit_id']);
                } catch (\Throwable $e) {
                    fclose($handle);
                    $db->transRollback();
                    return redirect()->back()->with('error',
                        'Import dihentikan pada nomor induk ' . $studentNumber . ': ' . $e->getMessage()
                    );
                }
            }

            $classroomId = null;
            if ($classCode !== '') {
                $cls = $db->table('classrooms c')
                    ->join('grade_levels gl', 'gl.id = c.grade_level_id')
                    ->where('c.unit_id', $period['unit_id'])
                    ->where('gl.grade_number', $period['source_grade'])
                    ->groupStart()
                        ->where('c.code', $classCode)
                        ->orWhere('c.name', $classCode)
                    ->groupEnd()
                    ->select('c.id')->get()->getRowArray();
                if ($cls) {
                    $classroomId = (int) $cls['id'];
                }
            }

            $existing = $db->table('elective_students')
                ->where('unit_id', $period['unit_id'])
                ->where('academic_year_id', $period['academic_year_id'])
                ->where('student_number', $studentNumber)
                ->get()->getRowArray();

            if ($existing) {
                $db->table('elective_students')->where('id', $existing['id'])->update([
                    'full_name'    => $fullName,
                    'user_id'      => $userId > 0 ? $userId : $existing['user_id'],
                    'classroom_id' => $classroomId ?: $existing['classroom_id'],
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);
                $updatedCount++;
            } else {
                $db->table('elective_students')->insert([
                    'uuid'             => UuidService::v4(),
                    'user_id'          => $userId > 0 ? $userId : null,
                    'unit_id'          => $period['unit_id'],
                    'academic_year_id' => $period['academic_year_id'],
                    'student_number'   => $studentNumber,
                    'full_name'        => $fullName,
                    'current_grade'    => $period['source_grade'],
                    'classroom_id'     => $classroomId,
                    'is_active'        => 1,
                    'created_by'       => session()->get('user_id'),
                    'created_at'       => date('Y-m-d H:i:s'),
                    'updated_at'       => date('Y-m-d H:i:s'),
                ]);
                $importedCount++;
            }
        }
        fclose($handle);

        if ($db->transStatus() === false) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Import dibatalkan karena ada data yang tidak valid atau duplikat.');
        }
        $db->transCommit();

        return redirect()->to('/electives/' . $periodId)->with('success', "Proses import selesai. {$importedCount} peserta baru ditambahkan, {$updatedCount} peserta diperbarui.");
    }

    public function downloadStudentsTemplate(int $periodId)
    {
        $period = $this->period($periodId);
        $db = Database::connect();
        $classrooms = $db->table('classrooms c')
            ->join('grade_levels gl', 'gl.id = c.grade_level_id')
            ->where('c.unit_id', $period['unit_id'])
            ->where('gl.grade_number', $period['source_grade'])
            ->select('c.code, c.name')->get()->getResultArray();
        $sampleClass = $classrooms !== [] ? $classrooms[0]['code'] : 'X';

        $csvData = "nomor_induk,nama_siswa,kelas_rombel,user_id\n";
        $csvData .= "20261001,Ahmad Dahlan," . $sampleClass . ",\n";
        $csvData .= "20261002,Beti Maria," . $sampleClass . ",\n";
        $csvData .= "20261003,Chandra Gunawan," . $sampleClass . ",\n";

        return $this->response->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="template_peserta_kelas_' . $period['source_grade'] . '.csv"')
            ->setBody($csvData);
    }

    public function deleteStudent(int $periodId, int $studentId)
    {
        if (!is_super_admin() && !has_permission('electives.participants.manage') && !has_permission('class_electives.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak mengelola peserta pemilihan.');
        }
        $period = $this->period($periodId);
        WaliKelasAccessService::assertCanManageStudent($studentId, $periodId);
        $db = Database::connect();

        $submission = $db->table('student_elective_submissions')
            ->where('elective_period_id', $periodId)->where('student_id', $studentId)
            ->get()->getRowArray();
        if ($submission && in_array($submission['status'], ['SUBMITTED', 'APPROVED', 'FINALIZED'], true)) {
            return redirect()->back()->with('error', 'Peserta tidak dapat dihapus karena sudah mengirimkan pilihan.');
        }

        if ($submission) {
            $db->table('student_elective_choices')->where('submission_id', $submission['id'])->delete();
            $db->table('student_elective_submissions')->where('id', $submission['id'])->delete();
        }
        $db->table('elective_students')->where('id', $studentId)->delete();

        return redirect()->to('/electives/' . $periodId)->with('success', 'Peserta berhasil dihapus dari periode ini.');
    }

    public function form(int $periodId, int $studentId)
    {
        if (!$this->canManageSelection()) return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak untuk mengelola pilihan siswa.');
        $period = $this->period($periodId);
        WaliKelasAccessService::assertCanManageStudent($studentId, $periodId);
        return $this->selectionView($period, $this->student($studentId, $periodId));
    }

    public function save(int $periodId, int $studentId)
    {
        if (!$this->canManageSelection()) return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak untuk mengelola pilihan siswa.');
        $period = $this->period($periodId);
        WaliKelasAccessService::assertCanManageStudent($studentId, $periodId);
        $student = $this->student($studentId, $periodId);
        return $this->persist($period, $student, false);
    }

    public function submit(int $periodId, int $studentId)
    {
        if (!$this->canManageSelection()) return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak untuk mengelola pilihan siswa.');
        $period = $this->period($periodId);
        WaliKelasAccessService::assertCanManageStudent($studentId, $periodId);
        $student = $this->student($studentId, $periodId);
        return $this->persist($period, $student, true);
    }

    public function mySelection()
    {
        $requestedPeriodId = (int) $this->request->getGet('period_id');
        $db = Database::connect();

        $activePeriods = $db->table('elective_students es')
            ->select('es.*, ep.id AS elective_period_id, ep.title AS period_title, ep.selection_type')
            ->join('elective_periods ep', 'ep.unit_id = es.unit_id AND ep.academic_year_id = es.academic_year_id AND ep.source_grade = es.current_grade')
            ->where('es.user_id', session()->get('user_id'))->where('es.is_active', 1)
            ->whereIn('ep.status', ['PUBLISHED', 'SELECTION_OPEN'])
            ->orderBy('ep.selection_start_at', 'DESC')->get()->getResultArray();

        if ($activePeriods === []) {
            return redirect()->to('/dashboard')->with('error', 'Akun Anda belum terdaftar pada periode pemilihan aktif.');
        }

        $selectedStudent = $activePeriods[0];
        if ($requestedPeriodId > 0) {
            foreach ($activePeriods as $row) {
                if ((int) $row['elective_period_id'] === $requestedPeriodId) {
                    $selectedStudent = $row;
                    break;
                }
            }
        }

        return $this->selectionView(
            $this->period((int) $selectedStudent['elective_period_id']),
            $selectedStudent,
            true,
            $activePeriods
        );
    }

    public function saveMine()
    {
        [$period, $student] = $this->myContext();
        return $this->persist($period, $student, false, true);
    }

    public function submitMine()
    {
        [$period, $student] = $this->myContext();
        return $this->persist($period, $student, true, true);
    }

    public function review(int $periodId, int $submissionId)
    {
        if (!is_super_admin() && !has_permission('electives.selection.review')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak meninjau pilihan siswa.');
        }
        $period = $this->period($periodId);
        $submission = Database::connect()->table('student_elective_submissions ses')
            ->join('elective_students es', 'es.id = ses.student_id')
            ->where('ses.id', $submissionId)->where('ses.elective_period_id', $periodId)
            ->where('es.unit_id', $period['unit_id'])->get()->getRowArray();
        if (! $submission) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        try {
            (new ElectiveSelectionService())->review(
                $submissionId,
                (string) $this->request->getPost('review_type'),
                (string) $this->request->getPost('decision'),
                $this->request->getPost('notes'),
                (int) session()->get('user_id')
            );
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
        return redirect()->to('/electives/' . $periodId . '/students/' . $submission['student_id'] . '/selection')
            ->with('success', 'Review pilihan berhasil disimpan.');
    }

    public function requestChangeMine()
    {
        [$period, $student] = $this->myContext();
        $submission = Database::connect()->table('student_elective_submissions')
            ->where('elective_period_id', $period['id'])->where('student_id', $student['id'])->get()->getRowArray();
        if (! $submission) {
            return redirect()->back()->with('error', 'Pilihan yang akan diubah tidak ditemukan.');
        }
        try {
            $primary = (array) $this->request->getPost('primary');
            $backup = (array) $this->request->getPost('backup');
            if ($primary === []) {
                $primary = $this->orderedChoiceIds((array) $this->request->getPost('primary_priority'));
            }
            if ($backup === []) {
                $backup = $this->orderedChoiceIds((array) $this->request->getPost('backup_priority'));
            }
            (new ElectiveSelectionService())->requestChange(
                (int) $submission['id'],
                $primary,
                $backup,
                (string) $this->request->getPost('change_reason'),
                (int) session()->get('user_id')
            );
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
        return redirect()->to('/my-electives?period_id=' . $period['id'])->with('success', 'Pengajuan perubahan berhasil dikirim untuk penilaian ulang.');
    }

    public function reviewChange(int $periodId, int $requestId)
    {
        if (!is_super_admin() && !has_permission('electives.change.approve')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak memproses perubahan pilihan.');
        }
        $period = $this->period($periodId);
        $request = Database::connect()->table('student_elective_change_requests cr')
            ->join('student_elective_submissions ses', 'ses.id = cr.submission_id')
            ->where('cr.id', $requestId)->where('ses.elective_period_id', $period['id'])->get()->getRowArray();
        if (! $request) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        try {
            (new ElectiveSelectionService())->reviewChange(
                $requestId,
                (string) $this->request->getPost('decision'),
                $this->request->getPost('notes'),
                (int) session()->get('user_id')
            );
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
        return redirect()->back()->with('success', 'Pengajuan perubahan telah diproses.');
    }

    private function persist(array $period, array $student, bool $submit, bool $selfService = false)
    {
        $primary = (array) $this->request->getPost('primary');
        $backup = (array) $this->request->getPost('backup');
        try {
            if ($primary === []) {
                $primary = $this->orderedChoiceIds((array) $this->request->getPost('primary_priority'));
            }
            if ($backup === []) {
                $backup = $this->orderedChoiceIds((array) $this->request->getPost('backup_priority'));
            }
            (new ElectiveSelectionService())->save(
                (int) $period['id'],
                (int) $student['id'],
                $primary,
                $backup,
                [
                    'career_plan' => $this->request->getPost('career_plan'),
                    'intended_major' => $this->request->getPost('intended_major'),
                    'selection_reason' => $this->request->getPost('selection_reason'),
                ],
                (int) session()->get('user_id'),
                $submit
            );
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'Gagal menyimpan pilihan mapel siswa {studentId} pada periode {periodId}: {message}', [
                'studentId' => (int) $student['id'],
                'periodId' => (int) $period['id'],
                'message' => $e->getMessage(),
            ]);
            return redirect()->back()->withInput()->with('error', 'Pilihan belum tersimpan karena terjadi gangguan sistem. Silakan coba kembali.');
        }
        $target = $selfService ? '/my-electives?period_id=' . $period['id'] : '/electives/' . $period['id'] . '/students/' . $student['id'] . '/selection';
        return redirect()->to($target)->with('success', $submit ? 'Pilihan berhasil dikirim.' : 'Draf pilihan berhasil disimpan.');
    }

    private function selectionView(array $period, array $student, bool $selfService = false, array $activePeriods = [])
    {
        $db = Database::connect();
        $submission = $db->table('student_elective_submissions')
            ->where('elective_period_id', $period['id'])->where('student_id', $student['id'])->get()->getRowArray();
        $choices = $submission ? $db->table('student_elective_choices')->where('submission_id', $submission['id'])
            ->orderBy('choice_type')->orderBy('priority_order')->get()->getResultArray() : [];
        $reviews = $submission ? $db->table('student_elective_reviews r')
            ->select('r.*, u.full_name AS reviewer_name')->join('users u', 'u.id = r.reviewed_by')
            ->where('r.submission_id', $submission['id'])->get()->getResultArray() : [];
        $changeRequests = $submission ? $db->table('student_elective_change_requests cr')
            ->select('cr.*, u.full_name AS reviewer_name')->join('users u', 'u.id = cr.reviewed_by', 'left')
            ->where('cr.submission_id', $submission['id'])->orderBy('cr.requested_at', 'DESC')->get()->getResultArray() : [];
        $offerings = $db->table('elective_offerings eo')
            ->select('eo.*, s.code AS subject_code, s.name AS subject_name, t.full_name AS teacher_name')
            ->join('subjects s', 's.id = eo.subject_id')->join('teachers t', 't.id = eo.teacher_id', 'left')
            ->where('eo.elective_period_id', $period['id'])->where('eo.is_open', 1)->orderBy('s.name')->get()->getResultArray();
        $offerings = array_map([ElectivePromotionCatalog::class, 'enrich'], $offerings);
        return view('electives/selection', [
            'title' => 'Pilihan Mata Pelajaran',
            'breadcrumb_active' => 'Pilihan Siswa',
            'period' => $period,
            'student' => $student,
            'submission' => $submission,
            'choices' => $choices,
            'reviews' => $reviews,
            'changeRequests' => $changeRequests,
            'offerings' => $offerings,
            'selfService' => $selfService,
            'activePeriods' => $activePeriods,
        ]);
    }

    private function period(int $periodId): array
    {
        $period = Database::connect()->table('elective_periods')->where('id', $periodId)->get()->getRowArray();
        if (! $period) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        UnitScopeService::assertUnit((int) $period['unit_id']);
        if (!WaliKelasAccessService::canAccessElectivePeriod($period)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Periode pemilihan tidak tersedia untuk jenjang kelas binaan Anda.'
            );
        }
        return $period;
    }

    private function student(int $studentId, int $periodId): array
    {
        $period = $this->period($periodId);
        $student = Database::connect()->table('elective_students')->where('id', $studentId)
            ->where('unit_id', $period['unit_id'])->where('academic_year_id', $period['academic_year_id'])
            ->where('current_grade', $period['source_grade'])->where('is_active', 1)->get()->getRowArray();
        if (! $student) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        WaliKelasAccessService::assertCanManageStudent($studentId, $periodId);
        return $student;
    }

    private function myContext(): array
    {
        $periodId = (int) ($this->request->getPost('period_id') ?: $this->request->getGet('period_id'));
        $db = Database::connect();

        $query = $db->table('elective_students es')
            ->select('es.*, ep.id AS elective_period_id')
            ->join('elective_periods ep', 'ep.unit_id = es.unit_id AND ep.academic_year_id = es.academic_year_id AND ep.source_grade = es.current_grade')
            ->where('es.user_id', session()->get('user_id'))->where('es.is_active', 1)
            ->whereIn('ep.status', ['PUBLISHED', 'SELECTION_OPEN']);

        if ($periodId > 0) {
            $query->where('ep.id', $periodId);
        }

        $student = $query->orderBy('ep.selection_start_at', 'DESC')->get()->getRowArray();
        if (! $student) {
            throw new RuntimeException('Akun siswa tidak terhubung ke periode pemilihan aktif.');
        }
        UnitScopeService::assertUnit((int) $student['unit_id']);
        return [$this->period((int) $student['elective_period_id']), $student];
    }

    private function orderedChoiceIds(array $priorityMap): array
    {
        $selected = [];
        foreach ($priorityMap as $offeringId => $priority) {
            if ((int) $priority > 0) {
                $selected[(int) $offeringId] = (int) $priority;
            }
        }
        asort($selected, SORT_NUMERIC);
        if (count($selected) !== count(array_unique($selected))) {
            throw new RuntimeException('Setiap urutan prioritas harus unik.');
        }
        return array_map('intval', array_keys($selected));
    }

    private function canManageSelection(): bool
    {
        return is_super_admin() || has_permission('electives.selection.manage') || has_permission('class_electives.manage');
    }

    private function assertStudentUser(int $userId, int $unitId): void
    {
        UnitScopeService::assertUser($userId);
        $valid = Database::connect()->table('users u')
            ->join('user_roles ur', 'ur.user_id = u.id')
            ->join('roles r', 'r.id = ur.role_id')
            ->join('user_unit_access uua', 'uua.user_id = u.id')
            ->where('u.id', $userId)
            ->where('u.is_active', 1)
            ->where('u.deleted_at IS NULL')
            ->where('r.code', 'siswa')
            ->where('uua.unit_id', $unitId)
            ->countAllResults() > 0;
        if (!$valid) {
            throw new RuntimeException('Akun harus aktif, berperan sebagai siswa, dan memiliki akses ke unit yang sama.');
        }
    }
}
