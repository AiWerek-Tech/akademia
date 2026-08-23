<?php

namespace App\Controllers;

use App\Services\ReportingService;
use App\Services\UnitScopeService;
use Config\Database;
use Exception;

class ReportingController extends BaseController
{
    private ReportingService $reportService;

    public function __construct()
    {
        $this->reportService = new ReportingService();
    }

    // ================================================================
    // DASHBOARD
    // ================================================================

    public function index()
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;
        $classroomId = (int) ($this->request->getGet('classroom_id') ?: 0);

        $filters = [
            'status'       => $this->request->getGet('status') ?: '',
            'classroom_id' => $classroomId,
        ];

        $snapshots = $this->reportService->listSnapshots($unitId, $periodId, $filters);
        $policies  = $this->reportService->listPolicies($unitId, $periodId);
        $stats     = $this->reportService->reportingStats($unitId, $periodId, $classroomId ?: null);

        $classrooms = Database::connect()->table('classrooms')
            ->where('unit_id', $unitId)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        return view('reporting/index', [
            'title'             => 'Rapor & Pelaporan Terpadu',
            'breadcrumb_active' => 'Rapor',
            'snapshots'         => $snapshots,
            'policies'          => $policies,
            'stats'             => $stats,
            'classrooms'        => $classrooms,
            'selectedClassroom' => $classroomId,
            'filters'           => $filters,
            'statuses'          => ReportingService::ALLOWED_STATUSES,
        ]);
    }

    // ================================================================
    // SNAPSHOT MANAGEMENT
    // ================================================================

    public function generate(int $studentId)
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;
        $userId   = (int) session()->get('user_id');

        try {
            $snapshotId = $this->reportService->generateSnapshot($unitId, $periodId, $studentId, $userId);
            return redirect()->to(base_url('reporting/' . $snapshotId))->with('success', 'Laporan berhasil digenerate.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal generate laporan: ' . $e->getMessage());
        }
    }

    public function bulkGenerate()
    {
        $unitId      = (int) session()->get('active_unit_id');
        $period      = get_active_period();
        $periodId    = $period ? (int) $period['id'] : 0;
        $userId      = (int) session()->get('user_id');
        $classroomId = (int) $this->request->getPost('classroom_id');

        if ($classroomId <= 0) {
            return redirect()->back()->with('error', 'Silakan pilih rombongan belajar terlebih dahulu.');
        }

        try {
            $count = $this->reportService->generateClassSnapshots($unitId, $periodId, $classroomId, $userId);
            return redirect()->back()->with('success', "Berhasil men-generate {$count} rapor untuk rombel ini.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal generate massal: ' . $e->getMessage());
        }
    }

    public function bulkPublish()
    {
        $unitId      = (int) session()->get('active_unit_id');
        $period      = get_active_period();
        $periodId    = $period ? (int) $period['id'] : 0;
        $userId      = (int) session()->get('user_id');
        $classroomId = (int) $this->request->getPost('classroom_id');

        if ($classroomId <= 0) {
            return redirect()->back()->with('error', 'Silakan pilih rombongan belajar terlebih dahulu.');
        }

        try {
            $count = $this->reportService->publishClassSnapshots($unitId, $periodId, $classroomId, $userId);
            return redirect()->back()->with('success', "Berhasil menerbitkan {$count} rapor rombel.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menerbitkan massal: ' . $e->getMessage());
        }
    }

    public function print(int $id)
    {
        $snapshot = $this->reportService->snapshotDetail($id);
        if (! $snapshot) {
            return redirect()->to(base_url('reporting'))->with('error', 'Laporan tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $snapshot['unit_id']);

        return view('reporting/print', [
            'title'    => 'Buku Rapor — ' . ($snapshot['student_name'] ?? 'Siswa'),
            'snapshot' => $snapshot,
        ]);
    }

    public function studentReportCardApi(int $studentId): \CodeIgniter\HTTP\ResponseInterface
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = (int) (session()->get('active_period_id') ?: ($period ? (int) $period['id'] : 0));

        $snapshot = Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null)->table('report_snapshots')
            ->where('student_id', $studentId)
            ->where('academic_period_id', $periodId)
            ->where('unit_id', $unitId)
            ->get()->getRowArray();

        if (! $snapshot) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Laporan rapor belum tersedia untuk periode ini.',
            ])->setStatusCode(404);
        }

        $detail = $this->reportService->snapshotDetail((int) $snapshot['id']);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $detail,
        ]);
    }

    public function detail(int $id)
    {
        $snapshot = $this->reportService->snapshotDetail($id);
        if (! $snapshot) {
            return redirect()->to(base_url('reporting'))->with('error', 'Laporan tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $snapshot['unit_id']);

        return view('reporting/detail', [
            'title'             => 'Detail Rapor — ' . ($snapshot['student_name'] ?? ''),
            'breadcrumb_active' => 'Detail Rapor',
            'snapshot'          => $snapshot,
            'statuses'          => ReportingService::ALLOWED_STATUSES,
            'narrativeTypes'    => [
                ReportingService::NARRATIVE_SUBJECT  => 'Mata Pelajaran',
                ReportingService::NARRATIVE_GENERAL  => 'Umum',
                ReportingService::NARRATIVE_COCURRIC => 'Kokurikuler',
                ReportingService::NARRATIVE_EXTRACUR => 'Ekstrakurikuler',
            ],
        ]);
    }

    public function lock(int $id)
    {
        $snapshot = $this->reportService->snapshotDetail($id);
        if (! $snapshot) {
            return redirect()->to(base_url('reporting'))->with('error', 'Laporan tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $snapshot['unit_id']);

        $userId = (int) session()->get('user_id');
        try {
            $this->reportService->lockSnapshot($id, $userId);
            return redirect()->back()->with('success', 'Laporan berhasil dikunci.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengunci: ' . $e->getMessage());
        }
    }

    public function publish(int $id)
    {
        $snapshot = $this->reportService->snapshotDetail($id);
        if (! $snapshot) {
            return redirect()->to(base_url('reporting'))->with('error', 'Laporan tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $snapshot['unit_id']);

        $userId = (int) session()->get('user_id');
        try {
            $this->reportService->publishSnapshot($id, $userId);
            return redirect()->back()->with('success', 'Laporan berhasil diterbitkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menerbitkan: ' . $e->getMessage());
        }
    }

    // ================================================================
    // NARRATIVES
    // ================================================================

    public function saveNarrative(int $subjectResultId)
    {
        $userId = (int) session()->get('user_id');
        try {
            $content = trim((string) $this->request->getPost('content'));
            $type    = $this->request->getPost('narrative_type') ?? ReportingService::NARRATIVE_SUBJECT;
            $source  = $this->request->getPost('source') ?? ReportingService::SOURCE_TEACHER;

            if ($content === '') {
                return redirect()->back()->with('error', 'Narasi tidak boleh kosong.');
            }

            $this->reportService->saveNarrative($subjectResultId, $type, $content, $source, $userId);
            return redirect()->back()->with('success', 'Narasi berhasil disimpan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan narasi: ' . $e->getMessage());
        }
    }

    public function generateNarrative(int $subjectResultId)
    {
        $db = Database::connect();
        $result = $db->table('report_subject_results rsr')
            ->select('rsr.*, s.name as subject_name')
            ->join('subjects s', 's.id = rsr.subject_id', 'left')
            ->where('rsr.id', $subjectResultId)
            ->get()->getRowArray();

        if (! $result) {
            return redirect()->back()->with('error', 'Data subject result tidak ditemukan.');
        }

        $draft = $this->reportService->generateDraftNarrative($result);

        return redirect()->back()->with('draft_narrative', $draft)->with('draft_subject_result_id', $subjectResultId);
    }

    public function approveNarrative(int $narrativeId)
    {
        $userId = (int) session()->get('user_id');
        try {
            $this->reportService->approveNarrative($narrativeId, $userId);
            return redirect()->back()->with('success', 'Narasi disetujui.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyetujui narasi: ' . $e->getMessage());
        }
    }

    // ================================================================
    // PORTFOLIO
    // ================================================================

    public function portfolio(int $studentId)
    {
        $period = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;

        $items = $this->reportService->listPortfolio($studentId, $periodId);

        $student = Database::connect()->table('elective_students')
            ->where('id', $studentId)
            ->get()->getRowArray();

        if (! $student) {
            return redirect()->back()->with('error', 'Siswa tidak ditemukan.');
        }

        UnitScopeService::assertUnit((int) $student['unit_id']);

        $suggestions = [];
        if (has_permission('reporting.manage')) {
            $suggestions = $this->reportService->suggestPortfolioItems((int) $studentId, (int) $student['unit_id'], $periodId);
        }

        return view('reporting/portfolio', [
            'title'             => 'Portofolio — ' . ($student['full_name'] ?? ''),
            'breadcrumb_active' => 'Portofolio',
            'student'           => $student,
            'items'             => $items,
            'suggestions'       => $suggestions,
            'categories'        => ReportingService::PORTFOLIO_CATEGORIES,
        ]);
    }

    public function importPortfolio(int $studentId)
    {
        $period = get_active_period();
        $userId = (int) session()->get('user_id');

        $student = Database::connect()->table('elective_students')
            ->where('id', $studentId)
            ->get()->getRowArray();
        if (! $student) {
            return redirect()->back()->with('error', 'Siswa tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $student['unit_id']);

        $keys = array_values(array_filter((array) $this->request->getPost('suggestions'), static fn ($k) => is_string($k) && $k !== ''));

        try {
            $imported = $this->reportService->importPortfolioItems(
                (int) $studentId,
                (int) $student['unit_id'],
                $period ? (int) $period['id'] : 0,
                $userId,
                $keys
            );
            return redirect()->back()->with('success', $imported . ' bukti berhasil ditambahkan ke portofolio.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengimpor portofolio: ' . $e->getMessage());
        }
    }

    public function addPortfolioItem(int $studentId)
    {
        $period = get_active_period();
        $userId = (int) session()->get('user_id');

        $student = Database::connect()->table('elective_students')
            ->where('id', $studentId)
            ->get()->getRowArray();

        if (! $student) {
            return redirect()->back()->with('error', 'Siswa tidak ditemukan.');
        }

        try {
            $this->reportService->addPortfolioItem([
                'student_id'        => $studentId,
                'unit_id'           => (int) $student['unit_id'],
                'academic_period_id' => $period ? (int) $period['id'] : 0,
                'category'          => $this->request->getPost('category'),
                'title'             => $this->request->getPost('title'),
                'description'       => $this->request->getPost('description'),
                'file_url'          => $this->request->getPost('file_url'),
                'is_highlighted'    => (int) $this->request->getPost('is_highlighted'),
            ], $userId);

            return redirect()->back()->with('success', 'Item portofolio berhasil ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan: ' . $e->getMessage());
        }
    }

    public function deletePortfolioItem(int $itemId)
    {
        $userId = (int) session()->get('user_id');
        try {
            $this->reportService->deletePortfolioItem($itemId, $userId);
            return redirect()->back()->with('success', 'Item portofolio berhasil dihapus.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }

    public function toggleHighlight(int $itemId)
    {
        $userId = (int) session()->get('user_id');
        $this->reportService->toggleHighlight($itemId, $userId);
        return redirect()->back();
    }

    // ================================================================
    // CLASS DASHBOARD
    // ================================================================

    public function classDashboard()
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;

        $analytics = $this->reportService->classAnalytics($unitId, $periodId);

        return view('reporting/class_dashboard', [
            'title'             => 'Dashboard Kelas',
            'breadcrumb_active' => 'Dashboard Kelas',
            'analytics'         => $analytics,
        ]);
    }

    // ================================================================
    // PROMOTION / GRADUATION DECISION SUPPORT
    // ================================================================

    public function promotion()
    {
        $unitId     = (int) session()->get('active_unit_id');
        $period     = get_active_period();
        $periodId   = $period ? (int) $period['id'] : 0;
        $classroomId = (int) ($this->request->getGet('classroom_id') ?: 0);

        $data = $this->reportService->promotionReadiness($unitId, $periodId, $classroomId ?: null);

        $classrooms = Database::connect()->table('classrooms')
            ->where('unit_id', $unitId)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        return view('reporting/promotion', [
            'title'             => 'Kesiapan Kenaikan Kelas',
            'breadcrumb_active' => 'Kesiapan Kenaikan',
            'rows'              => $data['rows'],
            'summary'           => $data['summary'],
            'classrooms'        => $classrooms,
            'selectedClassroom' => $classroomId,
        ]);
    }
}
