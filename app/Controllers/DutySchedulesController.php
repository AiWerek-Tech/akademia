<?php

namespace App\Controllers;

use App\Services\TeacherDutyScheduleService;
use App\Models\AcademicYearModel;
use App\Models\TeacherModel;
use Config\Database;
use RuntimeException;

class DutySchedulesController extends BaseController
{
    /** @var TeacherDutyScheduleService */
    private $dutyService;

    public function __construct()
    {
        $this->dutyService = new TeacherDutyScheduleService();
    }

    public function index()
    {
        if (!is_super_admin() && !has_permission('duty_schedules.view') && !has_permission('schedules.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses ke Jadwal Piket Guru.');
        }

        $db = Database::connect();
        $years = $db->table('academic_years')->orderBy('name', 'DESC')->get()->getResultArray();

        if (empty($years)) {
            return redirect()->to('/dashboard')->with('error', 'Belum ada data tahun pelajaran di sistem.');
        }

        $activeYear = array_values(array_filter($years, fn($y) => (int)($y['is_active'] ?? 0) === 1))[0] ?? $years[0];
        $yearId = (int) ($this->request->getGet('academic_year_id') ?: $activeYear['id']);

        $selectedYear = array_values(array_filter($years, fn($y) => (int)$y['id'] === $yearId))[0] ?? $activeYear;

        $matrix = $this->dutyService->getDutyMatrix($yearId);

        // Fetch all active teachers for manual assignment dropdown
        $rawTeachers = $db->table('teachers')
            ->select('id, full_name, nip, employee_number AS code, title_prefix, degree_suffix')
            ->where('is_active', 1)
            ->where('deleted_at IS NULL')
            ->orderBy('full_name', 'ASC')
            ->get()->getResultArray();

        $teachers = array_map(static function($t) {
            $rawName = rtrim(trim((string)$t['full_name']), ',');
            $prefix = trim((string)($t['title_prefix'] ?? ''));
            $suffix = trim((string)($t['degree_suffix'] ?? ''));

            if (!empty($suffix) && !str_contains($rawName, $suffix)) {
                $rawName .= ', ' . $suffix;
            }
            if (!empty($prefix) && !str_contains($rawName, $prefix)) {
                $rawName = $prefix . ' ' . $rawName;
            }
            $t['full_name'] = $rawName;
            return $t;
        }, $rawTeachers);

        // Calculate total duties assigned, unassigned teachers, & multiple duty teachers
        $assignedTeacherIds = [];
        $teacherDutyDays = [];
        $totalDutyAssignments = 0;

        foreach ($matrix as $dayData) {
            $dayName = $dayData['day_name'];
            $totalDutyAssignments += count($dayData['duties']);

            foreach ($dayData['duties'] as $duty) {
                $tId = (int) $duty['teacher_id'];
                $assignedTeacherIds[$tId] = true;

                if (!isset($teacherDutyDays[$tId])) {
                    $teacherDutyDays[$tId] = [
                        'teacher_name' => $duty['teacher_name'],
                        'teacher_code' => $duty['teacher_code'] ?? $duty['nip'] ?? '',
                        'days'         => [],
                        'count'        => 0,
                    ];
                }
                $teacherDutyDays[$tId]['days'][] = $dayName;
                $teacherDutyDays[$tId]['count']++;
            }
        }

        // Unassigned teachers = active teachers not yet assigned to any day
        $unassignedTeachers = array_values(array_filter($teachers, function($t) use ($assignedTeacherIds) {
            return !isset($assignedTeacherIds[(int)$t['id']]);
        }));

        // Multiple duty teachers = teachers assigned more than 1 time in the week
        $multipleDutyTeachers = array_values(array_filter($teacherDutyDays, fn($item) => $item['count'] > 1));

        return view('duty_schedules/index', [
            'title'                => 'Jadwal Piket Guru',
            'breadcrumb_active'    => 'Jadwal Piket',
            'years'                => $years,
            'selectedYear'         => $selectedYear,
            'matrix'               => $matrix,
            'teachers'             => $teachers,
            'unassignedTeachers'   => $unassignedTeachers,
            'multipleDutyTeachers' => $multipleDutyTeachers,
            'totalDutyAssignments' => $totalDutyAssignments,
            'daysMap'              => TeacherDutyScheduleService::DAYS_MAP,
        ]);
    }

    public function generate()
    {
        if (!is_super_admin() && !has_permission('duty_schedules.manage') && !has_permission('schedules.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak menyusun jadwal piket guru.');
        }

        $yearId = (int) $this->request->getPost('academic_year_id');
        $quotaMode = trim((string) $this->request->getPost('quota_mode')) ?: 'auto';
        $quota = (int) ($this->request->getPost('quota_per_day') ?: 3);

        $includedPositions = $this->request->getPost('included_positions');
        $allPositions = ['HEADMASTER', 'VICE_PRINCIPAL', 'CHAPLAIN', 'COUNSELING_COORDINATOR', 'HOMEROOM_TEACHER'];

        if ($includedPositions !== null) {
            $includedArr = (array) $includedPositions;
            $excludedPositions = array_values(array_diff($allPositions, $includedArr));
        } else {
            $excludedPositions = (array) ($this->request->getPost('excluded_positions') ?: []);
        }

        try {
            $result = $this->dutyService->generate($yearId, $quotaMode, $quota, $excludedPositions, (int) session()->get('user_id'));
            $modeText = $result['quota_mode'] === 'auto' ? 'Otomatis Proporsional' : 'Manual';
            $excludedText = !empty($excludedPositions) ? ' [Pengecualian Jabatan: ' . count($excludedPositions) . ' Posisi]' : '';
            $msg = 'Berhasil menyusun ' . $result['total_generated'] . ' penugasan piket guru secara otomatis [Mode ' . $modeText . ': ' . $result['target_quota_per_day'] . ' guru/hari]' . $excludedText . '.';
            return redirect()->to('/duty-schedules?academic_year_id=' . $yearId)->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function store()
    {
        if (!is_super_admin() && !has_permission('duty_schedules.manage') && !has_permission('schedules.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak menambah penugasan piket guru.');
        }

        $rules = [
            'academic_year_id' => 'required|integer',
            'teacher_id'       => 'required|integer',
            'day_of_week'      => 'required|integer|in_list[1,2,3,4,5]',
            'duty_role'        => 'required|string|max_length[100]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $yearId = (int) $this->request->getPost('academic_year_id');
        $teacherId = (int) $this->request->getPost('teacher_id');
        $dayOfWeek = (int) $this->request->getPost('day_of_week');
        $dutyRole = trim((string) $this->request->getPost('duty_role')) ?: 'GURU_PIKET';
        $notes = trim((string) $this->request->getPost('notes')) ?: null;

        try {
            $this->dutyService->addDuty($yearId, $teacherId, $dayOfWeek, $dutyRole, $notes, (int) session()->get('user_id'));
            return redirect()->to('/duty-schedules?academic_year_id=' . $yearId)->with('success', 'Penugasan piket guru berhasil ditambahkan.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function delete(int $id)
    {
        if (!is_super_admin() && !has_permission('duty_schedules.manage') && !has_permission('schedules.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak menghapus penugasan piket guru.');
        }

        $yearId = (int) $this->request->getGet('academic_year_id');

        try {
            $this->dutyService->deleteDuty($id);
            return redirect()->to('/duty-schedules' . ($yearId ? '?academic_year_id=' . $yearId : ''))->with('success', 'Penugasan piket guru berhasil dihapus.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function clear()
    {
        if (!is_super_admin() && !has_permission('duty_schedules.manage') && !has_permission('schedules.manage')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak mengosongkan jadwal piket guru.');
        }

        $yearId = (int) $this->request->getPost('academic_year_id');

        try {
            $this->dutyService->clearSchedule($yearId);
            return redirect()->to('/duty-schedules?academic_year_id=' . $yearId)->with('success', 'Jadwal piket guru berhasil dikosongkan.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function print()
    {
        if (!is_super_admin() && !has_permission('duty_schedules.view') && !has_permission('schedules.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses mencetak jadwal piket.');
        }

        $db = Database::connect();
        $years = $db->table('academic_years')->orderBy('name', 'DESC')->get()->getResultArray();
        $yearId = (int) ($this->request->getGet('academic_year_id') ?: ($years[0]['id'] ?? 0));
        $selectedYear = array_values(array_filter($years, fn($y) => (int)$y['id'] === $yearId))[0] ?? ($years[0] ?? []);

        $matrix = $this->dutyService->getDutyMatrix($yearId);

        // Fetch School Unit for KOP (Prefer SMA / Sekolah Satu Atap unit)
        $unit = $db->table('school_units')->where('code', 'SMA')->get()->getRowArray()
             ?: $db->table('school_units')->where('is_active', 1)->get()->getRowArray()
             ?: [];

        // Fetch Headmaster (Direktur Kompleks / Kepala Sekolah SMA)
        $headmaster = $db->table('teacher_additional_duties tad')
            ->select('t.full_name, t.nip, t.employee_number, t.title_prefix, t.degree_suffix')
            ->join('teachers t', 't.id = tad.teacher_id')
            ->join('additional_duty_types adt', 'adt.id = tad.duty_type_id')
            ->where('adt.code', 'HEADMASTER')
            ->where('tad.status', 'ACTIVE')
            ->where('tad.deleted_at IS NULL')
            ->get()->getRowArray();

        if ($headmaster) {
            $rawName = rtrim(trim($headmaster['full_name']), ',');
            $prefix = trim((string)($headmaster['title_prefix'] ?? ''));
            $suffix = trim((string)($headmaster['degree_suffix'] ?? ''));

            if (!empty($suffix) && !str_contains($rawName, $suffix)) {
                $rawName .= ', ' . $suffix;
            }
            if (!empty($prefix) && !str_contains($rawName, $prefix)) {
                $rawName = $prefix . ' ' . $rawName;
            }
            $headmaster['full_name'] = $rawName;
        } else if (!empty($unit['head_name'])) {
            $headmaster = ['full_name' => $unit['head_name'], 'nip' => ''];
        }

        return view('duty_schedules/print', [
            'selectedYear' => $selectedYear,
            'matrix'       => $matrix,
            'unit'         => $unit,
            'headmaster'   => $headmaster,
        ]);
    }
}
