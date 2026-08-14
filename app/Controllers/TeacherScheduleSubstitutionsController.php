<?php

namespace App\Controllers;

use App\Models\TeacherScheduleSubstitutionModel;
use App\Services\AuditService;
use App\Services\ScheduleConflictDetectionService;
use App\Services\TeacherScheduleSubstitutionManagementService;
use App\Services\TeacherSubstitutionScheduleRepairService;
use App\Services\UnitScopeService;
use Config\Database;

class TeacherScheduleSubstitutionsController extends BaseController
{
    private $db;
    private TeacherScheduleSubstitutionModel $model;
    private TeacherScheduleSubstitutionManagementService $management;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->model = new TeacherScheduleSubstitutionModel();
        $this->management = new TeacherScheduleSubstitutionManagementService();
    }

    public function index()
    {
        $teacherIds = $this->accessibleTeacherIds();
        $substitutions = [];
        if ($teacherIds !== []) {
            $substitutions = $this->db->table('teacher_schedule_substitutions tss')
                ->select('tss.*, absent.full_name AS absent_name, absent.teacher_initial AS absent_initial, absent.color_code AS absent_color, substitute.full_name AS substitute_name, substitute.teacher_initial AS substitute_initial, ap.name AS period_name, ay.name AS year_name')
                ->join('teachers absent', 'absent.id = tss.absent_teacher_id')
                ->join('teachers substitute', 'substitute.id = tss.substitute_teacher_id')
                ->join('academic_periods ap', 'ap.id = tss.academic_period_id')
                ->join('academic_years ay', 'ay.id = ap.academic_year_id')
                ->whereIn('tss.absent_teacher_id', $teacherIds)
                ->whereIn('tss.substitute_teacher_id', $teacherIds)
                ->orderBy('tss.status', 'ASC')->orderBy('tss.effective_from', 'DESC')
                ->get()->getResultArray();
        }

        $teachers = $teacherIds === [] ? [] : $this->db->table('teachers')
            ->select('id, full_name, teacher_initial, color_code, primary_unit_id')
            ->whereIn('id', $teacherIds)->where('is_active', 1)->where('deleted_at IS NULL')
            ->orderBy('full_name', 'ASC')->get()->getResultArray();
        $periods = $this->db->table('academic_periods ap')
            ->select('ap.*, ay.name AS year_name')
            ->join('academic_years ay', 'ay.id = ap.academic_year_id')
            ->orderBy('ap.is_active', 'DESC')->orderBy('ap.start_date', 'DESC')
            ->get()->getResultArray();

        $repairCandidates = [];
        if ($substitutions !== [] && $this->db->tableExists('teacher_substitution_repair_candidates')) {
            $candidateRows = $this->db->table('teacher_substitution_repair_candidates')
                ->whereIn('substitution_id', array_map('intval', array_column($substitutions, 'id')))
                ->orderBy('id', 'DESC')->get()->getResultArray();
            foreach ($candidateRows as $candidate) {
                $substitutionId = (int) $candidate['substitution_id'];
                if (!isset($repairCandidates[$substitutionId])) {
                    $candidate['changes'] = json_decode((string) $candidate['changes_json'], true) ?: [];
                    $candidate['diagnostics'] = json_decode((string) ($candidate['diagnostics_json'] ?? '{}'), true) ?: [];
                    $repairCandidates[$substitutionId] = $candidate;
                }
            }
        }

        return view('schedules/substitutions/index', [
            'title' => 'Substitusi Guru Jadwal',
            'substitutions' => $substitutions,
            'teachers' => $teachers,
            'periods' => $periods,
            'can_manage' => has_permission('schedules.manage'),
            'repair_candidates' => $repairCandidates,
        ]);
    }

    public function store()
    {
        $this->assertManagePermission();
        try {
            $input = $this->payload();
            UnitScopeService::assertTeacher((int) $input['absent_teacher_id']);
            UnitScopeService::assertTeacher((int) $input['substitute_teacher_id']);
            $saved = $this->management->save(null, $input, (int) session()->get('user_id'));
            AuditService::log('schedules', 'CREATE_SUBSTITUTION', 'TeacherScheduleSubstitution', (int) $saved['id'], null, $saved, 'Membuat substitusi guru sementara');
            return $this->redirectAfterAudit('Substitusi guru berhasil ditambahkan.', (int) $saved['academic_period_id']);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function update(int $id)
    {
        $this->assertManagePermission();
        try {
            $old = $this->findAccessible($id);
            $input = $this->payload();
            UnitScopeService::assertTeacher((int) $input['absent_teacher_id']);
            UnitScopeService::assertTeacher((int) $input['substitute_teacher_id']);
            $saved = $this->management->save($id, $input, (int) session()->get('user_id'));
            AuditService::log('schedules', 'UPDATE_SUBSTITUTION', 'TeacherScheduleSubstitution', $id, $old, $saved, 'Memperbarui substitusi guru sementara');
            return $this->redirectAfterAudit('Substitusi guru berhasil diperbarui.', (int) $saved['academic_period_id']);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function toggle(int $id)
    {
        $this->assertManagePermission();
        try {
            $old = $this->findAccessible($id);
            $input = $old;
            $input['status'] = (string) $old['status'] === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
            $saved = $this->management->save($id, $input, (int) session()->get('user_id'));
            AuditService::log('schedules', 'TOGGLE_SUBSTITUTION', 'TeacherScheduleSubstitution', $id, $old, $saved, 'Mengubah status substitusi guru');
            $label = $saved['status'] === 'ACTIVE' ? 'diaktifkan' : 'dinonaktifkan';
            return $this->redirectAfterAudit("Substitusi guru berhasil {$label}.", (int) $saved['academic_period_id']);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function analyzeRepair(int $id)
    {
        $this->assertManagePermission();
        try {
            $this->findAccessible($id);
            $result = (new TeacherSubstitutionScheduleRepairService())->generate($id, (int) session()->get('user_id'));
            AuditService::log('schedules', 'ANALYZE_SUBSTITUTION_REPAIR', 'TeacherScheduleSubstitution', $id, null, ['candidate_id' => $result['candidate']['id'], 'moves' => count($result['changes'])], 'Menganalisis Auto Repair substitusi guru');
            $message = $result['changes'] === [] ? 'Analisis selesai: jadwal guru pelaksana sudah aman, tidak ada slot yang perlu dipindahkan.' : 'Kandidat Auto Repair siap. Periksa pratinjau perubahan sebelum menerapkan.';
            return redirect()->to(base_url('schedules/substitutions'))->with('success', $message);
        } catch (\Throwable $e) {
            return redirect()->to(base_url('schedules/substitutions'))->with('error', $e->getMessage());
        }
    }

    public function applyRepair(int $id, int $candidateId)
    {
        $this->assertManagePermission();
        try {
            $this->findAccessible($id);
            $result = (new TeacherSubstitutionScheduleRepairService())->apply($candidateId, $id, (int) session()->get('user_id'));
            AuditService::log('schedules', 'APPLY_SUBSTITUTION_REPAIR', 'TeacherScheduleSubstitution', $id, null, ['candidate_id' => $candidateId] + $result, 'Menerapkan Auto Repair substitusi guru');
            return redirect()->to(base_url('schedules/substitutions'))->with('success', $result['moved_entries'] . ' slot berhasil disesuaikan secara atomik. Audit final memastikan tidak ada konflik kritis.');
        } catch (\Throwable $e) {
            return redirect()->to(base_url('schedules/substitutions'))->with('error', $e->getMessage());
        }
    }

    private function payload(): array
    {
        return [
            'academic_period_id' => (int) $this->request->getPost('academic_period_id'),
            'absent_teacher_id' => (int) $this->request->getPost('absent_teacher_id'),
            'substitute_teacher_id' => (int) $this->request->getPost('substitute_teacher_id'),
            'effective_from' => (string) $this->request->getPost('effective_from'),
            'effective_to' => (string) $this->request->getPost('effective_to'),
            'status' => (string) ($this->request->getPost('status') ?: 'ACTIVE'),
            'notes' => trim((string) $this->request->getPost('notes')),
        ];
    }

    private function findAccessible(int $id): array
    {
        $row = $this->model->find($id);
        if (! $row) {
            throw new \InvalidArgumentException('Data substitusi tidak ditemukan.');
        }
        UnitScopeService::assertTeacher((int) $row['absent_teacher_id']);
        UnitScopeService::assertTeacher((int) $row['substitute_teacher_id']);
        return $row;
    }

    private function accessibleTeacherIds(): array
    {
        $unitIds = UnitScopeService::accessibleUnitIds();
        if ($unitIds === []) {
            return [];
        }
        $rows = $this->db->table('teachers t')->distinct()->select('t.id')
            ->join('teacher_unit_assignments tua', 'tua.teacher_id = t.id AND tua.status = "ACTIVE"', 'left')
            ->where('t.is_active', 1)->where('t.deleted_at IS NULL')
            ->groupStart()->whereIn('t.primary_unit_id', $unitIds)->orWhereIn('tua.unit_id', $unitIds)->groupEnd()
            ->get()->getResultArray();
        return array_values(array_unique(array_map('intval', array_column($rows, 'id'))));
    }

    private function assertManagePermission(): void
    {
        if (! has_permission('schedules.manage')) {
            throw new \App\Exceptions\AuthorizationException('Anda tidak memiliki izin mengelola substitusi guru.');
        }
    }

    private function redirectAfterAudit(string $message, int $academicPeriodId)
    {
        $critical = 0;
        $selectedUnits = [];
        $versions = $this->db->table('schedule_versions')
            ->select('id, unit_id, is_active, revision_number')
            ->where('academic_period_id', $academicPeriodId)
            ->where('workflow_status !=', 'ARCHIVED')
            ->orderBy('is_active', 'DESC')->orderBy('revision_number', 'DESC')->orderBy('id', 'DESC')
            ->get()->getResultArray();
        foreach ($versions as $version) {
            $unitId = (int) $version['unit_id'];
            if (isset($selectedUnits[$unitId])) {
                continue;
            }
            $selectedUnits[$unitId] = true;
            try {
                $audit = (new ScheduleConflictDetectionService())->detectConflicts((int) $version['id']);
                $critical += (int) ($audit['critical_conflicts'] ?? 0);
            } catch (\Throwable $e) {
                log_message('error', 'Audit after teacher substitution update failed: ' . $e->getMessage());
            }
        }

        $redirect = redirect()->to(base_url('schedules/substitutions'))->with('success', $message . ' Audit jadwal terkait telah diperbarui.');
        if ($critical > 0) {
            $redirect->with('warning', "Ditemukan {$critical} konflik kritis setelah perubahan. Buka Jadwal Pelajaran, periksa konflik, lalu generate atau sesuaikan jadwal sebelum digunakan.");
        }
        return $redirect;
    }
}
