<?php

namespace App\Controllers;

use App\Services\SummativeProcessingService;
use Config\Database;
use Exception;

class SummativeController extends BaseController
{
    private SummativeProcessingService $summativeService;

    public function __construct()
    {
        $this->summativeService = new SummativeProcessingService();
    }

    /**
     * Per-subject processing status.
     */
    public function index()
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;

        $db      = Database::connect();
        $subjects = $db->table('subjects')->where('is_active', 1)->orderBy('name', 'ASC')->get()->getResultArray();
        $status  = $this->summativeService->subjectStatus($unitId, $periodId);

        $statusBySubject = [];
        foreach ($status as $row) {
            $statusBySubject[(int) $row['subject_id']] = $row;
        }

        return view('assessment/summative', [
            'title'             => 'Pengolahan Nilai Sumatif',
            'breadcrumb_active' => 'Sumatif',
            'subjects'          => $subjects,
            'status'            => $statusBySubject,
            'methods'           => SummativeProcessingService::ALLOWED_METHODS,
        ]);
    }

    public function detail(int $subjectId)
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;
        $classroomId = (int) ($this->request->getGet('classroom_id') ?: 0);

        $db      = Database::connect();
        $subject = $db->table('subjects')->where('id', $subjectId)->get()->getRowArray();
        if (! $subject) {
            return redirect()->to(base_url('summative'))->with('error', 'Mapel tidak ditemukan.');
        }

        $policy = $this->summativeService->activePolicy($unitId, $periodId, $subjectId);

        return view('assessment/summative_detail', [
            'title'             => 'Sumatif — ' . $subject['name'],
            'breadcrumb_active' => 'Sumatif',
            'subject'           => $subject,
            'policy'            => $policy,
            'results'           => $this->summativeService->results($unitId, $periodId, $subjectId, $classroomId),
            'classrooms'        => $this->classrooms($unitId, $periodId),
            'selectedClassroom' => $classroomId,
            'statuses'          => [SummativeProcessingService::STATUS_DRAFT, SummativeProcessingService::STATUS_VALIDATED],
        ]);
    }

    public function process()
    {
        $unitId   = (int) session()->get('active_unit_id');
        $period   = get_active_period();
        $periodId = $period ? (int) $period['id'] : 0;
        $userId   = (int) session()->get('user_id');

        $subjectId = (int) ($this->request->getPost('subject_id') ?: 0);

        try {
            if ($subjectId > 0) {
                $r = $this->summativeService->processSubject($unitId, $periodId, $subjectId, $userId);
                return redirect()->to(base_url('summative/' . $subjectId))->with('success', 'Diproses: ' . $r['students'] . ' siswa dari ' . $r['tps'] . ' TP.');
            }
            $r = $this->summativeService->processAll($unitId, $periodId, $userId);
            return redirect()->to(base_url('summative'))->with('success', 'Semua mapel diproses: ' . $r['students'] . ' hasil dari ' . $r['subjects'] . ' mapel.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses: ' . $e->getMessage());
        }
    }

    public function validateResult(int $id)
    {
        $userId = (int) session()->get('user_id');
        try {
            $this->summativeService->validate($id, $userId);
            return redirect()->back()->with('success', 'Hasil sumatif divalidasi dan dikunci.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function reopen(int $id)
    {
        $userId = (int) session()->get('user_id');
        try {
            $this->summativeService->reopen($id, $userId);
            return redirect()->back()->with('success', 'Hasil sumatif dibuka kembali untuk diproses ulang.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function classrooms(int $unitId, int $periodId): array
    {
        return Database::connect()->table('classrooms')
            ->where('unit_id', $unitId)
            ->where('academic_period_id', $periodId)
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();
    }
}