<?php

namespace App\Controllers;

use App\Exceptions\AuthorizationException;
use App\Services\PortalUnitScopeService;
use App\Services\TeacherElectivePortalService;
use Config\Database;

class TeacherElectivesController extends BaseController
{
    public function index()
    {
        if (!has_permission('teacher_electives.view')) {
            return service('response')->setStatusCode(403)->setBody(
                view('errors/html/error_403', ['message' => 'Anda tidak memiliki izin melihat daftar pemilih mapel guru.'])
            );
        }

        try {
            $teacherId = $this->resolveTeacherId();
            $unitScope = PortalUnitScopeService::resolve(
                (string) $this->request->getGet('unit_scope'),
                $teacherId
            );
            $projection = (new TeacherElectivePortalService())->build(
                $teacherId,
                $unitScope['unitIds'],
                (array) $this->request->getGet()
            );
        } catch (AuthorizationException $e) {
            return service('response')->setStatusCode(403)->setBody(
                view('errors/html/error_403', ['message' => $e->getMessage()])
            );
        }

        return view('teacher_portal/electives', array_merge($projection, [
            'title' => 'Mapel Pilihan Saya',
            'breadcrumb_active' => 'Mapel Pilihan Saya',
            'unitScope' => $unitScope,
        ]));
    }

    public function export()
    {
        if (!has_permission('teacher_electives.view')) {
            return service('response')->setStatusCode(403);
        }

        try {
            $teacherId = $this->resolveTeacherId();
            $unitScope = PortalUnitScopeService::resolve(
                (string) $this->request->getGet('unit_scope'),
                $teacherId
            );
            $projection = (new TeacherElectivePortalService())->build(
                $teacherId,
                $unitScope['unitIds'],
                (array) $this->request->getGet(),
                false
            );
        } catch (AuthorizationException $e) {
            return service('response')->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        $stream = fopen('php://temp', 'w+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, [
            'No', 'NIS/NISN', 'Nama Siswa', 'Kelas', 'Unit', 'Mata Pelajaran',
            'Jenis Pilihan', 'Prioritas', 'Status Pengajuan', 'Status Alokasi', 'Tanggal Diajukan',
        ], ';');
        foreach ($projection['students'] as $index => $student) {
            fputcsv($stream, [
                $index + 1,
                $this->safeCsv((string) ($student['student_number'] ?? '')),
                $this->safeCsv((string) ($student['full_name'] ?? '')),
                $this->safeCsv((string) ($student['classroom_name'] ?? '-')),
                $this->safeCsv((string) ($student['unit_name'] ?? '-')),
                $this->safeCsv((string) ($student['subject_name'] ?? '-')),
                ($student['choice_type'] ?? '') === 'PRIMARY' ? 'Utama' : 'Cadangan',
                (int) ($student['priority_order'] ?? 0),
                TeacherElectivePortalService::statusLabel((string) ($student['submission_status'] ?? '')),
                TeacherElectivePortalService::statusLabel((string) ($student['allocation_status'] ?? '')),
                (string) ($student['submitted_at'] ?? ''),
            ], ';');
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        $periodCode = preg_replace('/[^a-z0-9_-]+/i', '-', (string) ($projection['selectedPeriod']['title'] ?? 'periode'));
        $teacherCode = preg_replace('/[^a-z0-9_-]+/i', '-', (string) ($projection['teacher']['full_name'] ?? 'guru'));
        $filename = 'pemilih-mapel-' . trim($teacherCode, '-') . '-' . trim($periodCode, '-') . '.csv';

        return $this->response->download($filename, $csv)->setContentType('text/csv; charset=UTF-8');
    }

    private function resolveTeacherId(): int
    {
        $teacherId = (int) (get_teacher_id() ?? 0);
        $userId = (int) session()->get('user_id');
        if ($teacherId > 0) {
            return $teacherId;
        }
        if ($userId <= 0) {
            throw new AuthorizationException('Sesi pengguna tidak valid.');
        }

        $db = Database::connect();
        $user = $db->table('users')->select('id, teacher_id, email')->where('id', $userId)->get()->getRowArray();
        if (!empty($user['teacher_id'])) {
            $teacherId = (int) $user['teacher_id'];
            session()->set('teacher_id', $teacherId);
            return $teacherId;
        }
        if (empty($user['email'])) {
            throw new AuthorizationException('Akun belum ditautkan ke profil guru.');
        }

        $matches = $db->table('teachers')->select('id')->where('email', $user['email'])
            ->where('is_active', 1)->where('deleted_at IS NULL')->limit(2)->get()->getResultArray();
        if (count($matches) !== 1) {
            throw new AuthorizationException('Akun belum ditautkan secara unik ke profil guru.');
        }
        $teacherId = (int) $matches[0]['id'];
        $db->table('users')->where('id', $userId)->update(['teacher_id' => $teacherId]);
        session()->set('teacher_id', $teacherId);

        return $teacherId;
    }

    private function safeCsv(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
    }
}
