<?php

namespace App\Controllers;

use App\Exceptions\AuthorizationException;
use App\Services\ScheduleExportService;
use App\Services\UnitScopeService;
use App\Services\WaliKelasAccessService;
use Config\Database;
use CodeIgniter\HTTP\ResponseInterface;

class ScheduleReportsController extends BaseController
{
    private ScheduleExportService $exportService;

    public function __construct()
    {
        $this->exportService = new ScheduleExportService();
    }

    public function classroomReport(int $versionId, int $classroomId): ResponseInterface
    {
        $context = $this->reportContext($versionId);
        UnitScopeService::assertClassroom($classroomId);
        if (! empty($context['unit_id'])) {
            WaliKelasAccessService::assertClassroomInUnit($classroomId, (int) $context['unit_id']);
        }
        if (is_wali_kelas() && !is_super_admin() && WaliKelasAccessService::classroomId() !== $classroomId) {
            throw new \RuntimeException('Anda hanya dapat mencetak jadwal rombel yang ditugaskan.');
        }
        $data = $this->exportService->getGridForClassroom($versionId, $classroomId);
        if ($this->request->getGet('format') !== 'json') {
            $db = Database::connect();
            $classroom = $db->table('classrooms c')
                ->select('c.*, t.full_name as homeroom_teacher_name, t.title_prefix as hr_prefix, t.degree_suffix as hr_suffix')
                ->join('teachers t', 't.id = c.homeroom_teacher_id', 'left')
                ->where('c.id', $classroomId)
                ->get()->getRowArray();

            $smaUnit = $db->table('school_units')->where('code', 'SMA')->get()->getRowArray();
            $directorName = $smaUnit['head_name'] ?? 'MARTHEN REFASI, S.Ag';

            return $this->response->setBody(view('schedules/reports/classroom', [
                'context'       => $context,
                'classroom'     => $classroom,
                'grid'          => $data,
                'director_name' => $directorName,
            ]));
        }
        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    public function teacherReport(int $versionId, int $teacherId): ResponseInterface
    {
        $context = $this->reportContext($versionId);
        UnitScopeService::assertTeacher($teacherId);
        $teacher = Database::connect()->table('teachers')->where('id', $teacherId)->get()->getRowArray();
        if (! $teacher) {
            return $this->response->setStatusCode(404)->setBody('Guru tidak ditemukan.');
        }
        $data = $this->exportService->getGridForTeacher($versionId, $teacherId);
        if ($this->request->getGet('format') !== 'json') {
            $db = Database::connect();
            $smaUnit = $db->table('school_units')->where('code', 'SMA')->get()->getRowArray();
            $directorName = $smaUnit['head_name'] ?? 'MARTHEN REFASI, S.Ag';

            return $this->response->setBody(view('schedules/reports/teacher', [
                'context'       => $context,
                'teacher'       => $teacher,
                'grid'          => $data,
                'director_name' => $directorName,
            ]));
        }
        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    public function unitReport(int $versionId, int $unitId): ResponseInterface
    {
        $context = $this->reportContext($versionId);
        UnitScopeService::assertUnit($unitId);
        $resolvedVersionId = $versionId;
        if ((int)($context['unit_id'] ?? 0) !== $unitId) {
            $target = Database::connect()->table('schedule_versions')
                ->where('academic_period_id', (int)$context['academic_period_id'])
                ->where('unit_id', $unitId)->where('workflow_status !=', 'ARCHIVED')
                ->orderBy('is_active', 'DESC')->orderBy('revision_number', 'DESC')->orderBy('id', 'DESC')
                ->get()->getRowArray();
            if (!$target) {
                return $this->response->setStatusCode(404)->setBody('Versi jadwal untuk jenjang tersebut belum tersedia.');
            }
            $resolvedVersionId = (int)$target['id'];
            $context = $this->reportContext($resolvedVersionId);
        }
        $data = $this->exportService->getGridForUnit($resolvedVersionId, $unitId);
        $db = Database::connect();
        $unit = $db->table('school_units')->where('id', $unitId)->get()->getRowArray();
        $smaUnit = $db->table('school_units')->where('code', 'SMA')->get()->getRowArray();
        $directorName = $smaUnit['head_name'] ?? 'MARTHEN REFASI, S.Ag';

        return $this->response->setBody(view('schedules/reports/unit', [
            'context'       => $context,
            'unit'          => $unit,
            'grid'          => $data,
            'director_name' => $directorName,
        ]));
    }

    public function multiUnitReport(int $versionId): ResponseInterface
    {
        $context = $this->reportContext($versionId);
        $data = $this->exportService->getGridForMultiUnit($versionId);
        return $this->response->setBody(view('schedules/reports/multi_unit', [
            'context' => $context,
            'grid'    => $data,
        ]));
    }

    private function reportContext(int $versionId): array
    {
        $row = Database::connect()->table('schedule_versions sv')
            ->select('sv.*, su.name AS unit_name, su.address AS unit_address, su.logo_path, su.logo_right_path, su.header_line_1, su.header_line_2, su.header_line_3, su.header_line_4, su.document_city, su.head_name, su.head_identifier, ap.name AS period_name, ay.name AS year_name')
            ->join('school_units su', 'su.id = sv.unit_id', 'left')
            ->join('academic_periods ap', 'ap.id = sv.academic_period_id')
            ->join('academic_years ay', 'ay.id = ap.academic_year_id')
            ->where('sv.id', $versionId)->get()->getRowArray();
        if (! $row) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Versi jadwal tidak ditemukan.');
        }
        if (!empty($row['unit_id'])) {
            UnitScopeService::assertUnit((int) $row['unit_id']);
        }
        return $row;
    }
}
