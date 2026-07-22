<?php

namespace App\Controllers;

use App\Services\TeacherWorkloadCalculationService;
use App\Models\WorkloadPolicyModel;
use App\Models\TeacherWorkloadSnapshotModel;
use App\Models\TeacherModel;
use App\Models\AcademicPeriodModel;
use App\Models\SchoolUnitModel;
use App\Models\AssignmentVersionModel;
use App\Services\UnitScopeService;
use App\Services\UuidService;

class WorkloadsController extends BaseController
{
    public function index()
    {
        if (!has_permission('workloads.view')) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        $snapshotModel = new TeacherWorkloadSnapshotModel();
        $periodModel = new AcademicPeriodModel();
        $unitModel = new SchoolUnitModel();
        $versionModel = new AssignmentVersionModel();

        try {
            $unitId = UnitScopeService::resolveUnit($this->request->getGet('unit_id'));
        } catch (\Throwable $e) {
            return redirect()->to('/dashboard')->with('error', $e->getMessage());
        }

        $periodId = (int)($this->request->getGet('academic_period_id') ?: session()->get('active_period_id'));

        // Find active assignment version for this period
        $version = $versionModel->where('academic_period_id', $periodId)
                                ->where('is_active', 1)
                                ->first();

        // Fallback to latest version if no active locked version
        if (!$version) {
            $version = $versionModel->where('academic_period_id', $periodId)
                                    ->orderBy('id', 'DESC')
                                    ->first();
        }

        $snapshots = [];
        $reportData = [
            'grades'  => [],
            'rows'    => [],
            'summary' => ['total_teachers' => 0, 'underload_count' => 0, 'optimal_count' => 0, 'overload_count' => 0, 'grand_teaching' => 0, 'grand_duties' => 0, 'grand_total' => 0],
        ];

        if ($version) {
            $builder = $snapshotModel->select('teacher_workload_snapshots.*, teachers.full_name, teachers.employment_status, teachers.employment_type')
                                     ->join('teachers', 'teachers.id = teacher_workload_snapshots.teacher_id')
                                     ->where('teacher_workload_snapshots.assignment_version_id', $version['id']);

            if ($unitId) {
                $builder->where('teacher_workload_snapshots.unit_id', $unitId);
            }

            $snapshots = $builder->findAll();
            $reportData = TeacherWorkloadCalculationService::getDetailedWorkloadReport($version['id'], $periodId, $unitId);
        }

        $periods = $periodModel->orderBy('id', 'DESC')->findAll();
        $units = UnitScopeService::accessibleUnits();

        return view('workloads/index', [
            'snapshots'          => $snapshots,
            'report'             => $reportData,
            'version'            => $version,
            'periods'            => $periods,
            'units'              => $units,
            'selected_unit_id'   => $unitId,
            'selected_period_id' => $periodId
        ]);
    }

    public function policies()
    {
        if (!has_permission('workloads.view')) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        $policyModel = new WorkloadPolicyModel();
        $allowedUnitIds = UnitScopeService::accessibleUnitIds();
        $policyModel->select('workload_policies.*, academic_periods.name as period_name, school_units.name as unit_name')
                                ->join('academic_periods', 'academic_periods.id = workload_policies.academic_period_id')
                                ->join('school_units', 'school_units.id = workload_policies.unit_id', 'left')
                                ->groupStart()->where('workload_policies.unit_id IS NULL');
        if ($allowedUnitIds !== []) {
            $policyModel->orWhereIn('workload_policies.unit_id', $allowedUnitIds);
        }
        $policies = $policyModel->groupEnd()->orderBy('workload_policies.priority', 'DESC')->findAll();

        return view('workloads/policies/index', [
            'policies' => $policies
        ]);
    }

    public function createPolicy()
    {
        if (!has_permission('workloads.manage')) {
            return redirect()->to('/workloads/policies')->with('error', 'Akses ditolak.');
        }

        $periodModel = new AcademicPeriodModel();
        $unitModel = new SchoolUnitModel();

        $periods = $periodModel->where('status', 'ACTIVE')->orderBy('id', 'DESC')->findAll();
        $units = UnitScopeService::accessibleUnits();

        return view('workloads/policies/create', [
            'periods' => $periods,
            'units'   => $units
        ]);
    }

    public function storePolicy()
    {
        if (!has_permission('workloads.manage')) {
            return redirect()->to('/workloads/policies')->with('error', 'Akses ditolak.');
        }

        $rules = [
            'academic_period_id'     => 'required|numeric',
            'minimum_teaching_hours' => 'required|numeric|greater_than_equal_to[0]',
            'maximum_total_hours'    => 'required|numeric|greater_than_equal_to[0]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $policyModel = new WorkloadPolicyModel();

        $uuid = UuidService::v4();
        $data = [
            'uuid'                        => $uuid,
            'academic_period_id'          => $this->request->getPost('academic_period_id'),
            'unit_id'                     => $this->request->getPost('unit_id') ?: null,
            'employment_status'           => $this->request->getPost('employment_status') ?: null,
            'employment_type'             => $this->request->getPost('employment_type') ?: null,
            'minimum_teaching_hours'      => $this->request->getPost('minimum_teaching_hours'),
            'maximum_teaching_hours'      => $this->request->getPost('maximum_teaching_hours') ?: null,
            'target_total_hours'          => $this->request->getPost('target_total_hours') ?: null,
            'maximum_total_hours'         => $this->request->getPost('maximum_total_hours'),
            'overload_warning_threshold'  => $this->request->getPost('overload_warning_threshold') ?: null,
            'underload_warning_threshold' => $this->request->getPost('underload_warning_threshold') ?: null,
            'priority'                    => (int)($this->request->getPost('priority') ?: 0),
            'is_active'                   => 1,
            'created_by'                  => session()->get('user_id'),
        ];

        try {
            $requestedUnitId = $this->request->getPost('unit_id');
            if ($requestedUnitId) {
                $data['unit_id'] = UnitScopeService::resolveUnit($requestedUnitId);
            } elseif (!in_array(session()->get('role_code'), ['superadmin', 'super_admin'], true)) {
                $data['unit_id'] = UnitScopeService::resolveUnit();
            }
            $policyModel->insert($data);
            return redirect()->to('/workloads/policies')->with('success', 'Kebijakan beban kerja berhasil disimpan.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function recalculate()
    {
        if (!has_permission('workloads.recalculate')) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $versionId = (int)$this->request->getPost('assignment_version_id');
        $periodId = (int)$this->request->getPost('academic_period_id');
        $unitId = $this->request->getPost('unit_id') ? (int)$this->request->getPost('unit_id') : null;

        try {
            $version = (new AssignmentVersionModel())->find($versionId);
            if (!$version || (int) $version['academic_period_id'] !== $periodId) {
                throw new \RuntimeException('Versi penugasan dan periode akademik tidak cocok.');
            }
            if ($unitId !== null) {
                $unitId = UnitScopeService::resolveUnit($unitId);
            } elseif (!in_array(session()->get('role_code'), ['superadmin', 'super_admin'], true)) {
                $unitId = UnitScopeService::resolveUnit();
            }
            TeacherWorkloadCalculationService::recalculateAll($versionId, $periodId, $unitId, (int)session()->get('user_id'));
            return redirect()->back()->with('success', 'Perhitungan ulang beban kerja berhasil diselesaikan.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function export()
    {
        if (!has_permission('workloads.export')) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        try {
            $unitId = UnitScopeService::resolveUnit($this->request->getGet('unit_id'));
        } catch (\Throwable $e) {
            return redirect()->to('/workloads')->with('error', $e->getMessage());
        }

        $snapshotModel = new TeacherWorkloadSnapshotModel();
        $snapshots = $snapshotModel->select('teacher_workload_snapshots.*, teachers.full_name')
                                   ->join('teachers', 'teachers.id = teacher_workload_snapshots.teacher_id')
                                   ->where('teacher_workload_snapshots.unit_id', $unitId)
                                   ->findAll();

        $filename = 'Laporan_Beban_Kerja_' . date('Ymd_His') . '.csv';

        $output = fopen('php://temp', 'w+');
        fputcsv($output, ['Nama Guru', 'Jam Mengajar Real', 'Tugas Tambahan', 'Total Beban Kerja', 'Status']);

        foreach ($snapshots as $row) {
            fputcsv($output, array_map([self::class, 'csvCell'], [
                $row['full_name'],
                $row['teaching_workload_hours'],
                $row['additional_duty_hours'],
                $row['total_workload_hours'],
                $row['status']
            ]));
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csv);
    }

    private static function csvCell($value): string
    {
        $value = (string) $value;
        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'" . $value : $value;
    }
}
