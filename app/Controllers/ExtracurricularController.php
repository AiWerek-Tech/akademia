<?php

namespace App\Controllers;

use App\Services\ExtracurricularService;
use App\Services\UnitScopeService;
use Config\Database;
use Exception;

class ExtracurricularController extends BaseController
{
    private ExtracurricularService $extService;

    public function __construct()
    {
        $this->extService = new ExtracurricularService();
    }

    // ================================================================
    // PROGRAMS
    // ================================================================

    public function index()
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;

        $filters = [
            'status'   => $this->request->getGet('status') ?: '',
            'category' => $this->request->getGet('category') ?: '',
        ];

        $programs = $this->extService->listPrograms($unitId, $periodId, $filters);
        $stats    = $this->extService->stats($unitId, $periodId);

        return view('extracurricular/index', [
            'title'             => 'Ekstrakurikuler',
            'breadcrumb_active' => 'Ekstrakurikuler',
            'programs'          => $programs,
            'stats'             => $stats,
            'filters'           => $filters,
            'categories'        => ExtracurricularService::ALLOWED_CATEGORIES,
            'statuses'          => ExtracurricularService::ALLOWED_STATUSES,
        ]);
    }

    public function create()
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;

        return view('extracurricular/create', [
            'title'             => 'Buat Program Ekstrakurikuler',
            'breadcrumb_active' => 'Program Baru',
            'teachers'          => $this->teachersForUnit($unitId),
            'categories'        => ExtracurricularService::ALLOWED_CATEGORIES,
            'statuses'          => ExtracurricularService::ALLOWED_STATUSES,
            'days'              => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
        ]);
    }

    public function store()
    {
        $unitId = (int) session()->get('active_unit_id');
        UnitScopeService::assertUnit($unitId);
        $period = get_active_period();
        $userId = (int) session()->get('user_id');

        $title = trim((string) $this->request->getPost('title'));
        if ($title === '') {
            return redirect()->back()->withInput()->with('error', 'Nama program wajib diisi.');
        }

        try {
            $id = $this->extService->createProgram([
                'unit_id'           => $unitId,
                'academic_period_id' => $period ? (int) $period['id'] : 0,
                'code'              => $this->request->getPost('code'),
                'title'             => $this->request->getPost('title'),
                'category'          => $this->request->getPost('category'),
                'rationale'         => $this->request->getPost('rationale'),
                'objective'         => $this->request->getPost('objective'),
                'description'       => $this->request->getPost('description'),
                'coach_teacher_id'  => $this->request->getPost('coach_teacher_id'),
                'management_notes'  => $this->request->getPost('management_notes'),
                'funding_source'    => $this->request->getPost('funding_source'),
                'funding_amount'    => $this->request->getPost('funding_amount'),
                'max_members'       => $this->request->getPost('max_members'),
                'meeting_day'       => $this->request->getPost('meeting_day'),
                'meeting_time'      => $this->request->getPost('meeting_time'),
                'location'          => $this->request->getPost('location'),
                'status'            => $this->request->getPost('status'),
            ], $userId);

            return redirect()->to(base_url('extracurricular/' . $id))->with('success', 'Program ekstrakurikuler berhasil dibuat.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal membuat program: ' . $e->getMessage());
        }
    }

    public function detail(int $id)
    {
        $program = $this->extService->detailProgram($id);
        if (! $program) {
            return redirect()->to(base_url('extracurricular'))->with('error', 'Program tidak ditemukan.');
        }

        UnitScopeService::assertUnit((int) $program['unit_id']);

        return view('extracurricular/detail', [
            'title'             => $program['title'],
            'breadcrumb_active' => 'Detail Program',
            'program'           => $program,
            'statuses'          => ExtracurricularService::ALLOWED_STATUSES,
            'ipooHealth'        => $this->extService->calculateIpooHealth($id),
            'attStats'          => $this->extService->calculateAttendanceStats($id),
        ]);
    }

    public function edit(int $id)
    {
        $program = $this->extService->detailProgram($id);
        if (! $program) {
            return redirect()->to(base_url('extracurricular'))->with('error', 'Program tidak ditemukan.');
        }

        UnitScopeService::assertUnit((int) $program['unit_id']);

        $unitId = (int) session()->get('active_unit_id');

        return view('extracurricular/create', [
            'title'             => 'Edit Program Ekstrakurikuler',
            'breadcrumb_active' => 'Edit Program',
            'program'           => $program,
            'teachers'          => $this->teachersForUnit($unitId),
            'categories'        => ExtracurricularService::ALLOWED_CATEGORIES,
            'statuses'          => ExtracurricularService::ALLOWED_STATUSES,
            'days'              => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
            'isEdit'            => true,
        ]);
    }

    public function update(int $id)
    {
        $userId = (int) session()->get('user_id');
        try {
            $program = $this->extService->detailProgram($id);
            if (! $program) {
                return redirect()->to(base_url('extracurricular'))->with('error', 'Program tidak ditemukan.');
            }
            UnitScopeService::assertUnit((int) $program['unit_id']);

            $title = trim((string) $this->request->getPost('title'));
            if ($title === '') {
                return redirect()->back()->withInput()->with('error', 'Nama program wajib diisi.');
            }

            $this->extService->updateProgram($id, [
                'code'              => $this->request->getPost('code'),
                'title'             => $this->request->getPost('title'),
                'category'          => $this->request->getPost('category'),
                'rationale'         => $this->request->getPost('rationale'),
                'objective'         => $this->request->getPost('objective'),
                'description'       => $this->request->getPost('description'),
                'coach_teacher_id'  => $this->request->getPost('coach_teacher_id'),
                'management_notes'  => $this->request->getPost('management_notes'),
                'funding_source'    => $this->request->getPost('funding_source'),
                'funding_amount'    => $this->request->getPost('funding_amount'),
                'max_members'       => $this->request->getPost('max_members'),
                'meeting_day'       => $this->request->getPost('meeting_day'),
                'meeting_time'      => $this->request->getPost('meeting_time'),
                'location'          => $this->request->getPost('location'),
                'status'            => $this->request->getPost('status'),
            ], $userId);

            return redirect()->to(base_url('extracurricular/' . $id))->with('success', 'Program berhasil diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui: ' . $e->getMessage());
        }
    }

    public function transition(int $id)
    {
        $userId = (int) session()->get('user_id');
        try {
            $program = $this->extService->detailProgram($id);
            if (! $program) {
                return redirect()->to(base_url('extracurricular'))->with('error', 'Program tidak ditemukan.');
            }
            UnitScopeService::assertUnit((int) $program['unit_id']);

            $target = (string) $this->request->getPost('status');
            $this->extService->transitionProgram($id, $target, $userId);

            return redirect()->to(base_url('extracurricular/' . $id))->with('success', 'Status program diperbarui ke ' . $target . '.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengubah status: ' . $e->getMessage());
        }
    }

    // ================================================================
    // MEMBERS
    // ================================================================

    public function members(int $id)
    {
        $program = $this->extService->detailProgram($id);
        if (! $program) {
            return redirect()->to(base_url('extracurricular'))->with('error', 'Program tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $program['unit_id']);

        $unitId   = (int) session()->get('active_unit_id');
        $members  = $this->extService->listMembers($id);
        $available = $this->extService->availableStudents($unitId, $id);

        return view('extracurricular/members', [
            'title'             => 'Anggota — ' . $program['title'],
            'breadcrumb_active' => 'Anggota',
            'program'           => $program,
            'members'           => $members,
            'available'         => $available,
            'roles'             => [ExtracurricularService::ROLE_MEMBER, ExtracurricularService::ROLE_LEADER, ExtracurricularService::ROLE_COACH],
        ]);
    }

    private function assertProgramUnit(int $programId): ?array
    {
        $program = $this->extService->detailProgram($programId);
        if (! $program) {
            return null;
        }
        UnitScopeService::assertUnit((int) $program['unit_id']);
        return $program;
    }

    public function addMember(int $id)
    {
        if (! $this->assertProgramUnit($id)) {
            return redirect()->to(base_url('extracurricular'))->with('error', 'Program tidak ditemukan.');
        }

        if (! $this->extService->hasCapacity($id)) {
            return redirect()->back()->with('error', 'Kapasitas program sudah penuh.');
        }

        $userId = (int) session()->get('user_id');
        try {
            $this->extService->addMember([
                'program_id' => $id,
                'student_id' => $this->request->getPost('student_id'),
                'role'       => $this->request->getPost('role'),
                'join_date'  => $this->request->getPost('join_date'),
                'notes'      => $this->request->getPost('notes'),
            ], $userId);

            return redirect()->back()->with('success', 'Anggota berhasil ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan anggota: ' . $e->getMessage());
        }
    }

    public function removeMember(int $id)
    {
        if (! $this->assertProgramUnit($id)) {
            return redirect()->to(base_url('extracurricular'))->with('error', 'Program tidak ditemukan.');
        }
        $userId = (int) session()->get('user_id');
        try {
            $this->extService->removeMember($id, $userId);
            return redirect()->back()->with('success', 'Anggota berhasil dikeluarkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengeluarkan anggota: ' . $e->getMessage());
        }
    }

    // ================================================================
    // SESSIONS & ATTENDANCE
    // ================================================================

    public function sessions(int $id)
    {
        $program  = $this->extService->detailProgram($id);
        if (! $program) {
            return redirect()->to(base_url('extracurricular'))->with('error', 'Program tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $program['unit_id']);

        $sessions   = $this->extService->listSessions($id);
        $attendance = $this->extService->attendanceSummary($id);
        $attStats   = $this->extService->calculateAttendanceStats($id);

        return view('extracurricular/sessions', [
            'title'             => 'Sesi — ' . $program['title'],
            'breadcrumb_active' => 'Sesi & Kehadiran',
            'program'           => $program,
            'sessions'          => $sessions,
            'attendance'        => $attendance,
            'attStats'          => $attStats,
            'days'              => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
        ]);
    }

    public function createSession(int $id)
    {
        $program = $this->assertProgramUnit($id);
        if (! $program) {
            return redirect()->to(base_url('extracurricular'))->with('error', 'Program tidak ditemukan.');
        }

        // Schedule conflict check
        $unitId = (int) $program['unit_id'];
        $sessionDate = $this->request->getPost('session_date');
        $startTime   = $this->request->getPost('start_time');
        $endTime     = $this->request->getPost('end_time');

        if ($startTime && $endTime) {
            $conflicts = $this->extService->checkScheduleConflict($unitId, $sessionDate, $startTime, $endTime);
            if (! empty($conflicts)) {
                $conflictList = array_map(fn($c) => ($c['subject_name'] ?? '?') . ' (' . $c['slot_start'] . '–' . $c['slot_end'] . ', ' . ($c['classroom_name'] ?? '?') . ')', $conflicts);
                return redirect()->back()->withInput()->with('error', 'Konflik jadwal dengan: ' . implode(', ', $conflictList));
            }
        }

        $userId = (int) session()->get('user_id');
        try {
            $this->extService->createSession([
                'program_id'   => $id,
                'session_date' => $this->request->getPost('session_date'),
                'start_time'   => $this->request->getPost('start_time'),
                'end_time'     => $this->request->getPost('end_time'),
                'location'     => $this->request->getPost('location'),
                'topic'        => $this->request->getPost('topic'),
                'notes'        => $this->request->getPost('notes'),
            ], $userId);

            return redirect()->back()->with('success', 'Sesi berhasil dibuat.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat sesi: ' . $e->getMessage());
        }
    }

    public function attendance(int $sessionId)
    {
        $sessionRow = Database::connect()->table('extracurricular_sessions')->where('id', $sessionId)->get()->getRowArray();
        if (! $sessionRow) {
            return redirect()->to(base_url('extracurricular'))->with('error', 'Sesi tidak ditemukan.');
        }
        $program = $this->extService->detailProgram((int) $sessionRow['program_id']);
        if (! $program) {
            return redirect()->to(base_url('extracurricular'))->with('error', 'Program tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $program['unit_id']);

        $records   = $this->extService->sessionAttendance($sessionId);
        $session   = $sessionRow;
        $members   = $this->extService->listMembers((int) $program['id']);

        return view('extracurricular/attendance', [
            'title'             => 'Kehadiran Sesi',
            'breadcrumb_active' => 'Kehadiran',
            'program'           => $program,
            'session'           => $session,
            'records'           => $records,
            'members'           => $members,
            'statuses'          => ExtracurricularService::ALLOWED_ATTENDANCE,
        ]);
    }

    public function saveAttendance(int $sessionId)
    {
        $sessionRow = Database::connect()->table('extracurricular_sessions')->where('id', $sessionId)->get()->getRowArray();
        if (! $sessionRow) {
            return redirect()->to(base_url('extracurricular'))->with('error', 'Sesi tidak ditemukan.');
        }
        $program = $this->extService->detailProgram((int) $sessionRow['program_id']);
        if (! $program) {
            return redirect()->to(base_url('extracurricular'))->with('error', 'Program tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $program['unit_id']);

        $userId = (int) session()->get('user_id');
        try {
            $rawRecords = $this->request->getPost('attendance') ?: [];
            $count      = $this->extService->saveAttendance($sessionId, $rawRecords, $userId);

            return redirect()->back()->with('success', 'Kehadiran tersimpan: ' . $count . ' catatan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan kehadiran: ' . $e->getMessage());
        }
    }

    // ================================================================
    // COMPETENCIES
    // ================================================================

    public function competencies(int $id)
    {
        $program = $this->extService->detailProgram($id);
        if (! $program) {
            return redirect()->to(base_url('extracurricular'))->with('error', 'Program tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $program['unit_id']);

        $competencies = $this->extService->listCompetencies($id);
        $achievements = $this->extService->listAchievements($id);
        $members      = $this->extService->listMembers($id);

        return view('extracurricular/competencies', [
            'title'             => 'Kompetensi & Pencapaian — ' . $program['title'],
            'breadcrumb_active' => 'Kompetensi',
            'program'           => $program,
            'competencies'      => $competencies,
            'achievements'      => $achievements,
            'members'           => $members,
            'types'             => [ExtracurricularService::COMPETENCY_QUALITATIVE, ExtracurricularService::COMPETENCY_QUANTITATIVE],
            'ratings'           => ExtracurricularService::ALLOWED_RATINGS,
        ]);
    }

    public function addCompetency(int $id)
    {
        if (! $this->assertProgramUnit($id)) {
            return redirect()->to(base_url('extracurricular'))->with('error', 'Program tidak ditemukan.');
        }
        $userId = (int) session()->get('user_id');
        try {
            $this->extService->addCompetency([
                'program_id'      => $id,
                'code'            => $this->request->getPost('code'),
                'name'            => $this->request->getPost('name'),
                'description'     => $this->request->getPost('description'),
                'assessment_type' => $this->request->getPost('assessment_type'),
                'sort_order'      => $this->request->getPost('sort_order'),
            ], $userId);

            return redirect()->back()->with('success', 'Kompetensi berhasil ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan kompetensi: ' . $e->getMessage());
        }
    }

    public function addAchievement(int $id)
    {
        if (! $this->assertProgramUnit($id)) {
            return redirect()->to(base_url('extracurricular'))->with('error', 'Program tidak ditemukan.');
        }
        $userId = (int) session()->get('user_id');
        try {
            $this->extService->addAchievement([
                'member_id'     => $this->request->getPost('member_id'),
                'competency_id' => $this->request->getPost('competency_id'),
                'achieved_date' => $this->request->getPost('achieved_date'),
                'level'         => $this->request->getPost('level'),
                'score'         => $this->request->getPost('score'),
                'remarks'       => $this->request->getPost('remarks'),
                'evidence_url'  => $this->request->getPost('evidence_url'),
                'assessed_by'   => $this->request->getPost('assessed_by'),
            ], $userId);

            return redirect()->back()->with('success', 'Pencapaian berhasil dicatat.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal mencatat pencapaian: ' . $e->getMessage());
        }
    }

    // ================================================================
    // EVALUATIONS
    // ================================================================

    public function evaluations(int $id)
    {
        $program     = $this->extService->detailProgram($id);
        if (! $program) {
            return redirect()->to(base_url('extracurricular'))->with('error', 'Program tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $program['unit_id']);

        $evaluations = $this->extService->listEvaluations($id);
        $ipooHealth  = $this->extService->calculateIpooHealth($id);

        return view('extracurricular/evaluations', [
            'title'             => 'Evaluasi — ' . $program['title'],
            'breadcrumb_active' => 'Evaluasi',
            'program'           => $program,
            'evaluations'       => $evaluations,
            'ipooHealth'        => $ipooHealth,
            'ratings'           => ExtracurricularService::ALLOWED_RATINGS,
        ]);
    }

    public function saveEvaluation(int $id)
    {
        if (! $this->assertProgramUnit($id)) {
            return redirect()->to(base_url('extracurricular'))->with('error', 'Program tidak ditemukan.');
        }
        $userId = (int) session()->get('user_id');
        try {
            $this->extService->saveEvaluation([
                'program_id'        => $id,
                'evaluation_period' => $this->request->getPost('evaluation_period'),
                'input_data'        => $this->request->getPost('input_data'),
                'process_data'      => $this->request->getPost('process_data'),
                'output_data'       => $this->request->getPost('output_data'),
                'outcome_data'      => $this->request->getPost('outcome_data'),
                'findings'          => $this->request->getPost('findings'),
                'recommendations'   => $this->request->getPost('recommendations'),
                'overall_rating'    => $this->request->getPost('overall_rating'),
            ], $userId);

            return redirect()->back()->with('success', 'Evaluasi berhasil disimpan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan evaluasi: ' . $e->getMessage());
        }
    }

    // ================================================================
    // QUALITATIVE REPORTS & NARRATIVE DRAFTER
    // ================================================================

    public function reports(int $id)
    {
        $program = $this->extService->detailProgram($id);
        if (! $program) {
            return redirect()->to(base_url('extracurricular'))->with('error', 'Program tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $program['unit_id']);

        $reports    = $this->extService->listStudentReports($id);
        $attStats   = $this->extService->calculateAttendanceStats($id);
        $ipooHealth = $this->extService->calculateIpooHealth($id);

        return view('extracurricular/reports', [
            'title'             => 'Laporan Kualitatif — ' . $program['title'],
            'breadcrumb_active' => 'Laporan',
            'program'           => $program,
            'reports'           => $reports,
            'attStats'          => $attStats,
            'ipooHealth'        => $ipooHealth,
            'extService'        => $this->extService,
        ]);
    }

    public function studentNarrative(int $id, int $studentId): \CodeIgniter\HTTP\ResponseInterface
    {
        $program = $this->assertProgramUnit($id);
        if (! $program) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Program tidak ditemukan.'])->setStatusCode(404);
        }

        $narrative = $this->extService->generateStudentNarrative($id, $studentId);

        return $this->response->setJSON([
            'status'    => 'success',
            'narrative' => $narrative,
        ]);
    }

    public function studentReport(int $id, int $studentId)
    {
        $program = $this->extService->detailProgram($id);
        if (! $program) {
            return redirect()->to(base_url('extracurricular'))->with('error', 'Program tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $program['unit_id']);

        $report = $this->extService->studentReport($id, $studentId);
        if (empty($report)) {
            return redirect()->back()->with('error', 'Laporan tidak ditemukan untuk siswa ini.');
        }

        return view('extracurricular/student_report', [
            'title'             => 'Laporan — ' . $report['member']['student_name'],
            'breadcrumb_active' => 'Laporan Siswa',
            'report'            => $report,
            'narrative'         => $this->extService->generateStudentNarrative($id, $studentId),
        ]);
    }

    public function certificate(int $id, int $studentId, ?int $achievementId = null)
    {
        $program = $this->assertProgramUnit($id);
        if (! $program) {
            return redirect()->to(base_url('extracurricular'))->with('error', 'Program tidak ditemukan.');
        }

        try {
            $certData = $this->extService->getCertificateData($id, $studentId, $achievementId);
            return view('extracurricular/certificate', [
                'title'    => 'Sertifikat — ' . $certData['student']['full_name'],
                'cert'     => $certData,
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memuat sertifikat: ' . $e->getMessage());
        }
    }

    public function studentSummary(int $studentId): \CodeIgniter\HTTP\ResponseInterface
    {
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;

        $items = $this->extService->getStudentExtracurricularReportCardData($studentId, $periodId);

        return $this->response->setJSON([
            'status'     => 'success',
            'student_id' => $studentId,
            'period_id'  => $periodId,
            'data'       => $items,
        ]);
    }

    // ================================================================
    // HELPERS
    // ================================================================

    public function delete(int $id)
    {
        $userId = (int) session()->get('user_id');
        try {
            $program = $this->extService->detailProgram($id);
            if (! $program) {
                return redirect()->to(base_url('extracurricular'))->with('error', 'Program tidak ditemukan.');
            }
            UnitScopeService::assertUnit((int) $program['unit_id']);

            $this->extService->deleteProgram($id, $userId);

            return redirect()->to(base_url('extracurricular'))->with('success', 'Program "' . $program['title'] . '" berhasil dihapus.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus program: ' . $e->getMessage());
        }
    }

    public function deleteSession(int $sessionId)
    {
        $sessionRow = Database::connect()->table('extracurricular_sessions')->where('id', $sessionId)->get()->getRowArray();
        if (! $sessionRow) {
            return redirect()->to(base_url('extracurricular'))->with('error', 'Sesi tidak ditemukan.');
        }
        $program = $this->extService->detailProgram((int) $sessionRow['program_id']);
        if (! $program) {
            return redirect()->to(base_url('extracurricular'))->with('error', 'Program tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $program['unit_id']);

        $userId = (int) session()->get('user_id');
        try {
            $this->extService->deleteSession($sessionId, $userId);
            return redirect()->back()->with('success', 'Sesi berhasil dihapus.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus sesi: ' . $e->getMessage());
        }
    }

    public function deleteCompetency(int $competencyId)
    {
        $db = Database::connect();
        $comp = $db->table('extracurricular_competencies')->where('id', $competencyId)->get()->getRowArray();
        if (! $comp) {
            return redirect()->back()->with('error', 'Kompetensi tidak ditemukan.');
        }
        $program = $this->extService->detailProgram((int) $comp['program_id']);
        if (! $program) {
            return redirect()->back()->with('error', 'Program tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $program['unit_id']);

        $userId = (int) session()->get('user_id');
        try {
            $this->extService->deleteCompetency($competencyId, $userId);
            return redirect()->back()->with('success', 'Kompetensi berhasil dinonaktifkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menonaktifkan kompetensi: ' . $e->getMessage());
        }
    }

    // ================================================================
    // HELPERS
    // ================================================================

    private function teachersForUnit(int $unitId): array
    {
        $db = Database::connect();

        // Try junction table first
        $teachers = $db->table('teachers t')
            ->select('t.id, t.full_name')
            ->join('teacher_unit_assignments tua', 'tua.teacher_id = t.id', 'inner')
            ->where('tua.unit_id', $unitId)
            ->where('tua.status', 'ACTIVE')
            ->where('t.is_active', 1)
            ->orderBy('t.full_name', 'ASC')
            ->groupBy('t.id')
            ->get()->getResultArray();

        if (empty($teachers)) {
            // Fallback to primary_unit_id
            $teachers = $db->table('teachers')
                ->select('id, full_name')
                ->where('primary_unit_id', $unitId)
                ->where('is_active', 1)
                ->orderBy('full_name', 'ASC')
                ->get()->getResultArray();
        }

        return $teachers;
    }
}
