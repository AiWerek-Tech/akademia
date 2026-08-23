<?php

namespace App\Controllers;

use App\Services\CocurricularService;
use App\Services\FeatureFlagService;
use App\Services\UnitScopeService;
use Config\Database;
use Exception;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Phase 7 — Cocurricular & Character.
 *
 * Program kokurikuler (COCURRICULAR / EXTRACURRICULAR / FIXED_SCHOOL_ACTIVITY /
 * FORMATION / SERVICE) dengan alur kerja Need Analysis → Design → Jadwal →
 * Eksekusi → Formatif → Sumatif → Laporan → Evaluasi → Tindak lanjut, plus
 * dukungan opsional Gerakan 7 Kebiasaan Anak Indonesia Hebat (7KAIH).
 */
class CocurricularController extends BaseController
{
    private CocurricularService $service;

    public function __construct()
    {
        $this->service = new CocurricularService();
    }

    private function ctx(): array
    {
        $unitId = (int) session()->get('active_unit_id');
        UnitScopeService::assertUnit($unitId);

        return [
            'unitId'   => $unitId,
            'period'   => get_active_period(),
            'userId'   => (int) session()->get('user_id'),
            'db'       => Database::connect(),
        ];
    }

    private function require7Kaih(): ?ResponseInterface
    {
        if (! FeatureFlagService::isEnabled('ialos_7kahi')) {
            return $this->response->setStatusCode(403)->setBody(
                view('errors/html/error_403', ['message' => 'Fitur Gerakan 7 Kebiasaan (7KAIH) belum diaktifkan.'])
            );
        }

        return null;
    }

    public function index()
    {
        $ctx = $this->ctx();
        $periodId = $ctx['period'] ? (int) $ctx['period']['id'] : null;

        return view('cocurricular/index', [
            'title'             => 'Kokurikuler & Karakter',
            'breadcrumb_active' => 'Kokurikuler & Karakter',
            'programs'          => $this->service->programs($ctx['unitId'], $periodId, $this->request->getGet('program_type') ?: null),
            'unitId'            => $ctx['unitId'],
            'periodId'          => $periodId,
            'types'             => CocurricularService::PROGRAM_TYPES,
            'statuses'          => CocurricularService::PROGRAM_STATUS,
            'filterType'        => $this->request->getGet('program_type') ?: '',
            'db'                => $ctx['db'],
            'canManage'         => has_permission('cocurricular.manage'),
            'periodName'        => $ctx['period']['name'] ?? 'Semua periode',
        ]);
    }

    public function create()
    {
        $ctx = $this->ctx();

        return view('cocurricular/create', [
            'title'             => 'Buat Program Kokurikuler',
            'breadcrumb_active' => 'Buat Program Kokurikuler',
            'unitId'            => $ctx['unitId'],
            'periodId'          => $ctx['period'] ? (int) $ctx['period']['id'] : 0,
            'types'             => CocurricularService::PROGRAM_TYPES,
            'deliveryModels'    => CocurricularService::DELIVERY_MODELS,
            'dimensions'        => $this->service->dimensions(),
            'subjects'          => $this->service->subjectsForUnit($ctx['unitId']),
            'objectives'        => $this->service->objectivesForUnit($ctx['unitId']),
            'teachers'          => $this->service->teachersForUnit($ctx['unitId']),
            'classrooms'        => $this->service->classroomsForUnit($ctx['unitId']),
            'kspVersions'       => $this->service->kspVersions(),
        ]);
    }

    public function store()
    {
        $ctx = $this->ctx();
        $periodId = $ctx['period'] ? (int) $ctx['period']['id'] : 0;

        try {
            $id = $this->service->createProgram($ctx['unitId'], $periodId, [
                'ksp_version_id'  => $this->request->getPost('ksp_version_id'),
                'code'            => $this->request->getPost('code'),
                'title'           => $this->request->getPost('title'),
                'program_type'    => $this->request->getPost('program_type'),
                'theme'           => $this->request->getPost('theme'),
                'rationale'       => $this->request->getPost('rationale'),
                'objective'       => $this->request->getPost('objective'),
                'annual_minutes'  => $this->request->getPost('annual_minutes'),
                'delivery_model'  => $this->request->getPost('delivery_model'),
                'start_date'      => $this->request->getPost('start_date'),
                'end_date'        => $this->request->getPost('end_date'),
                'description'     => $this->request->getPost('description'),
                'dimension_ids'   => $this->request->getPost('dimension_ids') ?: [],
                'subject_ids'     => $this->request->getPost('subject_ids') ?: [],
                'objective_ids'   => $this->request->getPost('objective_ids') ?: [],
                'teacher_ids'     => $this->request->getPost('teacher_ids') ?: [],
                'classroom_ids'   => $this->request->getPost('classroom_ids') ?: [],
                'partners'        => $this->request->getPost('partners') ?: [],
                'resources'       => $this->request->getPost('resources') ?: [],
            ], $ctx['userId']);

            return redirect()->to(base_url('cocurricular/' . $id))->with('success', 'Program kokurikuler berhasil dibuat.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal membuat program: ' . $e->getMessage());
        }
    }

    public function detail(int $id)
    {
        $ctx = $this->ctx();

        try {
            $this->service->assertProgramInUnit($id, $ctx['unitId']);
        } catch (Exception $e) {
            return redirect()->to(base_url('cocurricular'))->with('error', $e->getMessage());
        }

        return view('cocurricular/detail', [
            'title'             => 'Detail Program Kokurikuler',
            'breadcrumb_active' => 'Kokurikuler & Karakter',
            'data'              => $this->service->programDetail($id),
            'unitId'            => $ctx['unitId'],
            'periodId'          => $ctx['period'] ? (int) $ctx['period']['id'] : 0,
            'canManage'         => has_permission('cocurricular.manage'),
            'sessionModes'      => CocurricularService::SESSION_MODES,
            'observationTypes'  => CocurricularService::OBSERVATION_TYPES,
            'evidenceTypes'     => CocurricularService::EVIDENCE_TYPES,
            'resultLevels'      => CocurricularService::RESULT_LEVELS,
            'evaluationAspects' => CocurricularService::EVALUATION_ASPECTS,
            'sessions'          => $this->service->sessions($id),
            'students'          => $this->service->studentsForResults($id),
            'teachers'          => $this->service->teachersForUnit($ctx['unitId']),
            'ipooHealth'        => $this->service->calculateIpooHealth($id),
            'rubricDescriptors' => $this->service->dimensionDescriptorRubric(),
            'db'                => $ctx['db'],
        ]);
    }

    public function edit(int $id)
    {
        $ctx = $this->ctx();
        $program = $this->service->assertProgramInUnit($id, $ctx['unitId']);

        return view('cocurricular/edit', [
            'title'             => 'Ubah Program Kokurikuler',
            'breadcrumb_active' => 'Kokurikuler & Karakter',
            'program'           => $program,
            'data'              => $this->service->programDetail($id),
            'types'             => CocurricularService::PROGRAM_TYPES,
            'deliveryModels'    => CocurricularService::DELIVERY_MODELS,
            'dimensions'        => $this->service->dimensions(),
            'subjects'          => $this->service->subjectsForUnit($ctx['unitId']),
            'objectives'        => $this->service->objectivesForUnit($ctx['unitId']),
            'teachers'          => $this->service->teachersForUnit($ctx['unitId']),
            'classrooms'        => $this->service->classroomsForUnit($ctx['unitId']),
            'kspVersions'       => $this->service->kspVersions(),
        ]);
    }

    public function update(int $id)
    {
        $ctx = $this->ctx();

        try {
            $this->service->updateProgram($id, $ctx['unitId'], [
                'ksp_version_id'  => $this->request->getPost('ksp_version_id'),
                'code'            => $this->request->getPost('code'),
                'title'           => $this->request->getPost('title'),
                'program_type'    => $this->request->getPost('program_type'),
                'theme'           => $this->request->getPost('theme'),
                'rationale'       => $this->request->getPost('rationale'),
                'objective'       => $this->request->getPost('objective'),
                'annual_minutes'  => $this->request->getPost('annual_minutes'),
                'delivery_model'  => $this->request->getPost('delivery_model'),
                'start_date'      => $this->request->getPost('start_date'),
                'end_date'        => $this->request->getPost('end_date'),
                'description'     => $this->request->getPost('description'),
                'dimension_ids'   => $this->request->getPost('dimension_ids') ?: [],
                'subject_ids'     => $this->request->getPost('subject_ids') ?: [],
                'objective_ids'   => $this->request->getPost('objective_ids') ?: [],
                'teacher_ids'     => $this->request->getPost('teacher_ids') ?: [],
                'classroom_ids'   => $this->request->getPost('classroom_ids') ?: [],
                'partners'        => $this->request->getPost('partners') ?: [],
                'resources'       => $this->request->getPost('resources') ?: [],
            ], $ctx['userId']);

            return redirect()->to(base_url('cocurricular/' . $id))->with('success', 'Program kokurikuler berhasil diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui program: ' . $e->getMessage());
        }
    }

    public function transition(int $id)
    {
        $ctx = $this->ctx();

        try {
            $this->service->transition($id, $ctx['unitId'], (string) $this->request->getPost('status'), $ctx['userId']);
            return redirect()->back()->with('success', 'Status program diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function destroy(int $id)
    {
        $ctx = $this->ctx();

        try {
            $this->service->deleteProgram($id, $ctx['unitId'], $ctx['userId']);
            return redirect()->to(base_url('cocurricular'))->with('success', 'Program kokurikuler dihapus.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ----------------------------------------------------------------
    // Schedule & Execute
    // ----------------------------------------------------------------

    public function storeSession(int $id)
    {
        $ctx = $this->ctx();

        try {
            $this->service->addSession($id, $ctx['unitId'], [
                'classroom_id' => $this->request->getPost('classroom_id'),
                'teacher_id'   => $this->request->getPost('teacher_id'),
                'title'        => $this->request->getPost('title'),
                'session_date' => $this->request->getPost('session_date'),
                'start_time'   => $this->request->getPost('start_time'),
                'end_time'     => $this->request->getPost('end_time'),
                'mode'         => $this->request->getPost('mode'),
                'notes'        => $this->request->getPost('notes'),
            ], $ctx['userId']);
            return redirect()->back()->with('success', 'Sesi kokurikuler ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambah sesi: ' . $e->getMessage());
        }
    }

    public function updateSession(int $id, int $sessionId)
    {
        $ctx = $this->ctx();

        try {
            $this->service->updateSession($sessionId, $id, $ctx['unitId'], [
                'classroom_id' => $this->request->getPost('classroom_id'),
                'teacher_id'   => $this->request->getPost('teacher_id'),
                'title'        => $this->request->getPost('title'),
                'session_date' => $this->request->getPost('session_date'),
                'start_time'   => $this->request->getPost('start_time'),
                'end_time'     => $this->request->getPost('end_time'),
                'notes'        => $this->request->getPost('notes'),
            ], $ctx['userId']);
            return redirect()->back()->with('success', 'Sesi kokurikuler diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui sesi: ' . $e->getMessage());
        }
    }

    public function deleteSession(int $id, int $sessionId)
    {
        $ctx = $this->ctx();
        $this->service->deleteSession($sessionId, $id, $ctx['unitId']);
        return redirect()->back()->with('success', 'Sesi kokurikuler dihapus.');
    }

    public function executeSession(int $id, int $sessionId)
    {
        $ctx = $this->ctx();

        try {
            $this->service->executeSession($sessionId, $id, $ctx['unitId'], $ctx['userId']);
            return redirect()->back()->with('success', 'Sesi ditandai telah dieksekusi.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function cancelSession(int $id, int $sessionId)
    {
        $ctx = $this->ctx();
        $this->service->cancelSession($sessionId, $id, $ctx['unitId'], $ctx['userId']);
        return redirect()->back()->with('success', 'Sesi dibatalkan.');
    }

    // ----------------------------------------------------------------
    // Formative monitoring
    // ----------------------------------------------------------------

    public function storeObservation(int $id)
    {
        $ctx = $this->ctx();

        try {
            $this->service->addObservation($id, $ctx['unitId'], [
                'session_id'       => $this->request->getPost('session_id'),
                'student_id'       => $this->request->getPost('student_id'),
                'teacher_id'       => $this->request->getPost('teacher_id'),
                'observation_type' => $this->request->getPost('observation_type'),
                'dimension_id'     => $this->request->getPost('dimension_id'),
                'notes'            => $this->request->getPost('notes'),
                'rating'           => $this->request->getPost('rating'),
                'observed_on'      => $this->request->getPost('observed_on'),
            ], $ctx['userId']);
            return redirect()->back()->with('success', 'Catatan formatif ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambah catatan: ' . $e->getMessage());
        }
    }

    public function updateObservation(int $id, int $observationId)
    {
        $ctx = $this->ctx();

        try {
            $this->service->updateObservation($observationId, $id, $ctx['unitId'], [
                'notes'        => $this->request->getPost('notes'),
                'dimension_id' => $this->request->getPost('dimension_id'),
            ], $ctx['userId']);
            return redirect()->back()->with('success', 'Catatan formatif diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui catatan: ' . $e->getMessage());
        }
    }

    public function deleteObservation(int $id, int $observationId)
    {
        $ctx = $this->ctx();
        $this->service->deleteObservation($observationId, $id, $ctx['unitId']);
        return redirect()->back()->with('success', 'Catatan formatif dihapus.');
    }

    // ----------------------------------------------------------------
    // Summative evidence
    // ----------------------------------------------------------------

    public function storeEvidence(int $id)
    {
        $ctx = $this->ctx();

        try {
            $file = $this->request->getFile('file');
            $fileData = null;
            if ($file && $file->isValid() && ! $file->hasMoved()) {
                $allowed = ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
                $ext = strtolower($file->getExtension());
                if (! in_array($ext, $allowed, true)) {
                    throw new Exception('Jenis berkas tidak diizinkan.');
                }
                if ($file->getSize() > 10 * 1024 * 1024) {
                    throw new Exception('Ukuran berkas maksimal 10 MB.');
                }
                $path = 'uploads/cocurricular/' . $id;
                $file->move($path, $file->getRandomName(), true);
                $fileData = ['path' => $path . '/' . $file->getName(), 'meta' => ['name' => $file->getClientName(), 'size' => $file->getSize(), 'type' => $file->getClientMimeType()]];
            }

            $this->service->addEvidence($id, $ctx['unitId'], [
                'student_id'    => $this->request->getPost('student_id'),
                'dimension_id'  => $this->request->getPost('dimension_id'),
                'title'         => $this->request->getPost('title'),
                'evidence_type' => $this->request->getPost('evidence_type'),
                'description'   => $this->request->getPost('description'),
                'captured_at'   => $this->request->getPost('captured_at'),
            ], $ctx['userId'], $fileData);
            return redirect()->back()->with('success', 'Bukti sumatif ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambah bukti: ' . $e->getMessage());
        }
    }

    public function evidenceFile(int $evidenceId)
    {
        $ctx = $this->ctx();
        $evidence = $this->service->evidence($evidenceId);
        $this->service->assertProgramInUnit((int) $evidence['program_id'], $ctx['unitId']);

        if (empty($evidence['file_path'])) {
            return redirect()->back()->with('error', 'Bukti ini tidak memiliki berkas.');
        }

        $full = WRITEPATH . $evidence['file_path'];
        if (! is_file($full)) {
            return redirect()->back()->with('error', 'Berkas bukti tidak ditemukan.');
        }

        return $this->response->setHeader('Content-Type', mime_content_type($full))
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($full) . '"')
            ->setBody(file_get_contents($full));
    }

    public function deleteEvidence(int $id, int $evidenceId)
    {
        $ctx = $this->ctx();
        $this->service->deleteEvidence($evidenceId, $id, $ctx['unitId']);
        return redirect()->back()->with('success', 'Bukti sumatif dihapus.');
    }

    // ----------------------------------------------------------------
    // Summative results (per student × dimension matrix)
    // ----------------------------------------------------------------

    public function saveResults(int $id)
    {
        $ctx = $this->ctx();

        try {
            $saved = $this->service->saveResults($id, $ctx['unitId'], ['results' => $this->request->getPost('results') ?: []], $ctx['userId']);
            return redirect()->back()->with('success', "Hasil sumatif disimpan ({$saved} sel).");
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan hasil: ' . $e->getMessage());
        }
    }

    // ----------------------------------------------------------------
    // Evaluation
    // ----------------------------------------------------------------

    public function storeEvaluation(int $id)
    {
        $ctx = $this->ctx();

        try {
            $this->service->addEvaluation($id, $ctx['unitId'], [
                'aspect'    => $this->request->getPost('aspect'),
                'indicator' => $this->request->getPost('indicator'),
                'finding'   => $this->request->getPost('finding'),
                'rating'    => $this->request->getPost('rating'),
            ], $ctx['userId']);
            return redirect()->back()->with('success', 'Indikator evaluasi ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambah evaluasi: ' . $e->getMessage());
        }
    }

    public function updateEvaluation(int $id, int $evaluationId)
    {
        $ctx = $this->ctx();

        try {
            $this->service->updateEvaluation($evaluationId, $id, $ctx['unitId'], [
                'indicator' => $this->request->getPost('indicator'),
                'finding'   => $this->request->getPost('finding'),
                'rating'    => $this->request->getPost('rating'),
            ], $ctx['userId']);
            return redirect()->back()->with('success', 'Evaluasi diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui evaluasi: ' . $e->getMessage());
        }
    }

    public function deleteEvaluation(int $id, int $evaluationId)
    {
        $ctx = $this->ctx();
        $this->service->deleteEvaluation($evaluationId, $id, $ctx['unitId']);
        return redirect()->back()->with('success', 'Indikator evaluasi dihapus.');
    }

    // ----------------------------------------------------------------
    // Report
    // ----------------------------------------------------------------

    public function report(int $id)
    {
        $ctx = $this->ctx();

        try {
            $this->service->assertProgramInUnit($id, $ctx['unitId']);
        } catch (Exception $e) {
            return redirect()->to(base_url('cocurricular'))->with('error', $e->getMessage());
        }

        return view('cocurricular/report', [
            'title'             => 'Laporan Program Kokurikuler',
            'breadcrumb_active' => 'Kokurikuler & Karakter',
            'data'              => $this->service->report($id),
            'ipooHealth'        => $this->service->calculateIpooHealth($id),
            'unitId'            => $ctx['unitId'],
            'db'                => $ctx['db'],
        ]);
    }

    public function studentNarrative(int $id, int $studentId): ResponseInterface
    {
        $ctx = $this->ctx();
        $this->service->assertProgramInUnit($id, $ctx['unitId']);
        $narrative = $this->service->generateStudentNarrative($id, $studentId);

        return $this->response->setJSON([
            'status'    => 'success',
            'narrative' => $narrative,
        ]);
    }

    public function rubricDescriptors(): ResponseInterface
    {
        return $this->response->setJSON([
            'status'      => 'success',
            'descriptors' => $this->service->dimensionDescriptorRubric(),
        ]);
    }

    // ----------------------------------------------------------------
    // 7KAIH (optional)
    // ----------------------------------------------------------------

    public function habits()
    {
        if ($denied = $this->require7Kaih()) {
            return $denied;
        }
        $ctx = $this->ctx();

        return view('cocurricular/habits', [
            'title'             => 'Gerakan 7 Kebiasaan (7KAIH)',
            'breadcrumb_active' => 'Kokurikuler & Karakter',
            'habits'            => $this->service->habits($ctx['unitId']),
            'unitId'            => $ctx['unitId'],
        ]);
    }

    public function storeHabit()
    {
        if ($denied = $this->require7Kaih()) {
            return $denied;
        }
        $ctx = $this->ctx();

        try {
            $this->service->saveHabit($ctx['unitId'], [
                'code'             => $this->request->getPost('code'),
                'name'             => $this->request->getPost('name'),
                'description'      => $this->request->getPost('description'),
                'icon'             => $this->request->getPost('icon'),
                'weekly_challenge' => $this->request->getPost('weekly_challenge'),
                'sort_order'       => $this->request->getPost('sort_order'),
                'enabled'          => $this->request->getPost('enabled'),
            ], $ctx['userId']);
            return redirect()->back()->with('success', 'Kebiasaan ditambahkan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambah kebiasaan: ' . $e->getMessage());
        }
    }

    public function updateHabit(int $habitId)
    {
        if ($denied = $this->require7Kaih()) {
            return $denied;
        }
        $ctx = $this->ctx();

        try {
            $this->service->updateHabit($habitId, $ctx['unitId'], [
                'name'             => $this->request->getPost('name'),
                'description'      => $this->request->getPost('description'),
                'icon'             => $this->request->getPost('icon'),
                'weekly_challenge' => $this->request->getPost('weekly_challenge'),
                'sort_order'       => $this->request->getPost('sort_order'),
                'enabled'          => $this->request->getPost('enabled'),
            ], $ctx['userId']);
            return redirect()->back()->with('success', 'Kebiasaan diperbarui.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui kebiasaan: ' . $e->getMessage());
        }
    }

    public function deleteHabit(int $habitId)
    {
        if ($denied = $this->require7Kaih()) {
            return $denied;
        }
        $ctx = $this->ctx();
        $this->service->deleteHabit($habitId, $ctx['unitId']);
        return redirect()->back()->with('success', 'Kebiasaan dihapus.');
    }

    public function checkins()
    {
        if ($denied = $this->require7Kaih()) {
            return $denied;
        }
        $ctx = $this->ctx();

        $week = $this->request->getGet('week') ?: date('Y-m-d', strtotime('monday this week'));

        return view('cocurricular/checkins', [
            'title'             => 'Check-in Kebiasaan',
            'breadcrumb_active' => 'Kokurikuler & Karakter',
            'data'              => $this->service->checkins($ctx['unitId'], $week,
                $this->request->getGet('classroom_id') ? (int) $this->request->getGet('classroom_id') : null,
                $this->request->getGet('habit_id') ? (int) $this->request->getGet('habit_id') : null),
            'week'              => $week,
            'unitId'            => $ctx['unitId'],
            'classrooms'        => $this->service->classroomsForUnit($ctx['unitId']),
            'habits'            => $this->service->habits($ctx['unitId']),
            'statuses'          => CocurricularService::HABIT_STATUS,
            'teacherId'         => $this->resolveOwnTeacherId(),
        ]);
    }

    public function saveCheckins()
    {
        if ($denied = $this->require7Kaih()) {
            return $denied;
        }
        $ctx = $this->ctx();

        try {
            $saved = $this->service->saveCheckins($ctx['unitId'], (string) $this->request->getPost('week'), [
                'checkins' => $this->request->getPost('checkins') ?: [],
            ], $this->resolveOwnTeacherId(), $ctx['userId']);
            return redirect()->back()->with('success', "Check-in disimpan ({$saved} entri).");
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan check-in: ' . $e->getMessage());
        }
    }

    private function resolveOwnTeacherId(): int
    {
        $userId = (int) session()->get('user_id');
        if ($userId <= 0) {
            return 0;
        }

        $teacherId = (int) (session()->get('teacher_id') ?? 0);
        if ($teacherId > 0) {
            return $teacherId;
        }

        $user = Database::connect()->table('users')
            ->select('teacher_id')
            ->where('id', $userId)
            ->where('is_active', 1)
            ->get()->getRowArray();

        $teacherId = $user ? (int) $user['teacher_id'] : 0;
        if ($teacherId > 0) {
            session()->set('teacher_id', $teacherId);
        }

        return $teacherId;
    }
}