<?php

namespace App\Services;

use App\Exceptions\AuthorizationException;
use Config\Database;

/**
 * Read-only projection of elective selections owned by one teacher.
 *
 * Ownership is enforced in every query through elective_offerings.teacher_id;
 * request IDs are filters only and can never widen the teacher's scope.
 */
class TeacherElectivePortalService
{
    private const VISIBLE_PERIOD_STATUSES = [
        'PUBLISHED', 'SELECTION_OPEN', 'CLOSED', 'FINALIZED', 'LOCKED',
    ];

    private const VISIBLE_SUBMISSION_STATUSES = [
        'SUBMITTED', 'WAITING_CURRICULUM', 'APPROVED', 'FINALIZED',
        'CHANGE_REQUESTED', 'CHANGED', 'NEEDS_REVISION',
    ];

    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * @param list<int> $unitIds
     * @param array<string, mixed> $filters
     */
    public function build(int $teacherId, array $unitIds, array $filters = [], bool $paginate = true): array
    {
        if ($teacherId <= 0) {
            throw new AuthorizationException('Akun belum ditautkan ke profil guru.');
        }
        $unitIds = array_values(array_unique(array_filter(array_map('intval', $unitIds))));
        if ($unitIds === []) {
            throw new AuthorizationException('Tidak ada unit sekolah yang dapat diakses.');
        }

        $teacher = $this->db->table('teachers')
            ->where('id', $teacherId)->where('is_active', 1)->where('deleted_at IS NULL')
            ->get()->getRowArray();
        if (!$teacher) {
            throw new AuthorizationException('Profil guru aktif tidak ditemukan.');
        }

        $periods = $this->db->table('elective_periods ep')
            ->distinct()->select('ep.*, ay.name AS academic_year_name, su.code AS unit_code, su.name AS unit_name')
            ->join('elective_offerings eo', 'eo.elective_period_id = ep.id')
            ->join('academic_years ay', 'ay.id = ep.academic_year_id')
            ->join('school_units su', 'su.id = ep.unit_id')
            ->where('eo.teacher_id', $teacherId)
            ->whereIn('ep.unit_id', $unitIds)
            ->whereIn('ep.status', self::VISIBLE_PERIOD_STATUSES)
            ->orderBy('ay.start_date', 'DESC')->orderBy('ep.target_grade', 'ASC')->orderBy('ep.id', 'DESC')
            ->get()->getResultArray();

        $periodRequest = strtolower(trim((string) ($filters['period_id'] ?? '')));
        $aggregatePeriods = $periodRequest === 'all' || ($periodRequest === '' && count($unitIds) > 1);
        $requestedPeriodId = $aggregatePeriods ? 0 : max(0, (int) $periodRequest);
        $periodIds = array_map('intval', array_column($periods, 'id'));
        if ($requestedPeriodId > 0 && !in_array($requestedPeriodId, $periodIds, true)) {
            throw new AuthorizationException('Periode pemilihan tersebut bukan bagian dari penugasan Anda.');
        }
        $selectedPeriodId = $requestedPeriodId ?: (int) ($periods[0]['id'] ?? 0);
        $selectedPeriodIds = [];
        $selectedPeriod = null;
        if ($aggregatePeriods && $periods !== []) {
            // Combine the newest academic year only, preventing historical
            // selections from inflating current SMP/SMA totals.
            $latestAcademicYearId = (int) $periods[0]['academic_year_id'];
            $selectedPeriodIds = array_map(
                'intval',
                array_column(array_values(array_filter(
                    $periods,
                    static fn (array $period): bool => (int) $period['academic_year_id'] === $latestAcademicYearId
                )), 'id')
            );
            $selectedPeriod = [
                'id' => 'all',
                'title' => 'Gabungan Semua Periode',
                'academic_year_id' => $latestAcademicYearId,
                'academic_year_name' => (string) ($periods[0]['academic_year_name'] ?? '-'),
                'unit_code' => 'ALL',
                'unit_name' => 'Semua Unit',
            ];
        } else {
            foreach ($periods as $period) {
                if ((int) $period['id'] === $selectedPeriodId) {
                    $selectedPeriod = $period;
                    $selectedPeriodIds = [$selectedPeriodId];
                    break;
                }
            }
        }

        $empty = [
            'teacher' => $teacher,
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
            'offerings' => [],
            'students' => [],
            'classrooms' => [],
            'submissionStatuses' => [],
            'allocationStatuses' => [],
            'filters' => $this->normalizeFilters($filters, [], []),
            'stats' => ['offerings' => 0, 'unique_students' => 0, 'primary' => 0, 'backup' => 0, 'finalized' => 0],
            'pagination' => ['page' => 1, 'perPage' => 25, 'total' => 0, 'pages' => 1],
        ];
        if ($selectedPeriodIds === []) {
            return $empty;
        }

        $visibleSql = "'" . implode("','", self::VISIBLE_SUBMISSION_STATUSES) . "'";
        $offerings = $this->db->table('elective_offerings eo')
            ->select("eo.*, s.code AS subject_code, s.name AS subject_name,
                COUNT(DISTINCT CASE WHEN sec.choice_type = 'PRIMARY' AND ses.status IN ({$visibleSql}) THEN sec.id END) AS primary_count,
                COUNT(DISTINCT CASE WHEN sec.choice_type = 'BACKUP' AND ses.status IN ({$visibleSql}) THEN sec.id END) AS backup_count,
                COUNT(DISTINCT CASE WHEN ses.status = 'FINALIZED' THEN sec.id END) AS finalized_count", false)
            ->join('subjects s', 's.id = eo.subject_id')
            ->join('student_elective_choices sec', 'sec.offering_id = eo.id', 'left')
            ->join('student_elective_submissions ses', 'ses.id = sec.submission_id', 'left')
            ->whereIn('eo.elective_period_id', $selectedPeriodIds)
            ->where('eo.teacher_id', $teacherId)
            ->groupBy('eo.id')->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        $offeringIds = array_map('intval', array_column($offerings, 'id'));
        $requestedOfferingId = max(0, (int) ($filters['offering_id'] ?? 0));
        if ($requestedOfferingId > 0 && !in_array($requestedOfferingId, $offeringIds, true)) {
            throw new AuthorizationException('Mata pelajaran tersebut tidak diampu oleh akun guru ini.');
        }

        $optionRows = $offeringIds === [] ? [] : $this->db->table('student_elective_choices sec')
            ->distinct()->select('ses.status AS submission_status, sec.allocation_status')
            ->join('student_elective_submissions ses', 'ses.id = sec.submission_id')
            ->whereIn('sec.offering_id', $offeringIds)
            ->whereIn('ses.status', self::VISIBLE_SUBMISSION_STATUSES)
            ->get()->getResultArray();
        $submissionStatuses = array_values(array_unique(array_filter(array_column($optionRows, 'submission_status'))));
        $allocationStatuses = array_values(array_unique(array_filter(array_column($optionRows, 'allocation_status'))));
        sort($submissionStatuses);
        sort($allocationStatuses);
        $normalized = $this->normalizeFilters($filters, $submissionStatuses, $allocationStatuses);
        $normalized['period_id'] = $aggregatePeriods ? 'all' : $selectedPeriodId;
        $normalized['offering_id'] = $requestedOfferingId;

        $classroomBuilder = $this->ownedStudentBuilder($teacherId, $unitIds, $selectedPeriodIds);
        $classrooms = $classroomBuilder
            ->distinct()->select('c.id, c.code, c.name')
            ->where('c.id IS NOT NULL')
            ->orderBy('c.name', 'ASC')->get()->getResultArray();

        $builder = $this->ownedStudentBuilder($teacherId, $unitIds, $selectedPeriodIds)
            ->select('sec.id AS choice_id, sec.choice_type, sec.priority_order, sec.allocation_status,
                ses.id AS submission_id, ses.status AS submission_status, ses.submitted_at, ses.finalized_at,
                es.id AS student_id, es.student_number, es.full_name, es.current_grade,
                c.id AS classroom_id, c.code AS classroom_code, c.name AS classroom_name,
                eo.id AS offering_id, eo.weekly_hours, eo.is_approved,
                s.code AS subject_code, s.name AS subject_name,
                ep.id AS elective_period_id, ep.title AS period_title,
                su.code AS unit_code, su.name AS unit_name');

        $this->applyFilters($builder, $normalized);
        $total = (int) (clone $builder)->countAllResults();
        $page = max(1, (int) $normalized['page']);
        $perPage = (int) $normalized['per_page'];
        $pages = max(1, (int) ceil($total / max(1, $perPage)));
        $page = min($page, $pages);

        $sortMap = [
            'name' => 'es.full_name',
            'classroom' => 'c.name',
            'subject' => 's.name',
            'choice' => 'sec.choice_type',
            'status' => 'ses.status',
            'priority' => 'sec.priority_order',
        ];
        $builder->orderBy($sortMap[$normalized['sort']] ?? 'es.full_name', $normalized['direction'])
            ->orderBy('es.full_name', 'ASC')->orderBy('s.name', 'ASC');
        if ($paginate) {
            $builder->limit($perPage, ($page - 1) * $perPage);
        }
        $students = $builder->get()->getResultArray();

        $uniqueStudentBuilder = $this->ownedStudentBuilder($teacherId, $unitIds, $selectedPeriodIds)
            ->select('COUNT(DISTINCT es.id) AS total', false);
        $uniqueStudents = (int) ($uniqueStudentBuilder->get()->getRowArray()['total'] ?? 0);
        $stats = [
            'offerings' => count($offerings),
            'unique_students' => $uniqueStudents,
            'primary' => array_sum(array_map('intval', array_column($offerings, 'primary_count'))),
            'backup' => array_sum(array_map('intval', array_column($offerings, 'backup_count'))),
            'finalized' => array_sum(array_map('intval', array_column($offerings, 'finalized_count'))),
        ];

        return array_replace($empty, [
            'offerings' => $offerings,
            'students' => $students,
            'classrooms' => $classrooms,
            'submissionStatuses' => $submissionStatuses,
            'allocationStatuses' => $allocationStatuses,
            'filters' => $normalized,
            'stats' => $stats,
            'pagination' => ['page' => $page, 'perPage' => $perPage, 'total' => $total, 'pages' => $pages],
        ]);
    }

    private function ownedStudentBuilder(int $teacherId, array $unitIds, array $periodIds)
    {
        return $this->db->table('student_elective_choices sec')
            ->join('student_elective_submissions ses', 'ses.id = sec.submission_id')
            ->join('elective_students es', 'es.id = ses.student_id')
            ->join('elective_offerings eo', 'eo.id = sec.offering_id')
            ->join('elective_periods ep', 'ep.id = eo.elective_period_id AND ep.id = ses.elective_period_id')
            ->join('subjects s', 's.id = eo.subject_id')
            ->join('school_units su', 'su.id = ep.unit_id')
            ->join('classrooms c', 'c.id = es.classroom_id', 'left')
            ->where('eo.teacher_id', $teacherId)
            ->whereIn('ep.id', $periodIds)
            ->whereIn('ep.unit_id', $unitIds)
            ->whereIn('ep.status', self::VISIBLE_PERIOD_STATUSES)
            ->whereIn('ses.status', self::VISIBLE_SUBMISSION_STATUSES)
            ->where('es.is_active', 1);
    }

    private function applyFilters($builder, array $filters): void
    {
        if ((int) $filters['offering_id'] > 0) {
            $builder->where('eo.id', (int) $filters['offering_id']);
        }
        if ((int) $filters['classroom_id'] > 0) {
            $builder->where('es.classroom_id', (int) $filters['classroom_id']);
        }
        if ($filters['choice_type'] !== 'ALL') {
            $builder->where('sec.choice_type', $filters['choice_type']);
        }
        if ($filters['submission_status'] !== 'ALL') {
            $builder->where('ses.status', $filters['submission_status']);
        }
        if ($filters['allocation_status'] !== 'ALL') {
            $builder->where('sec.allocation_status', $filters['allocation_status']);
        }
        if ($filters['q'] !== '') {
            $builder->groupStart()
                ->like('es.full_name', $filters['q'])
                ->orLike('es.student_number', $filters['q'])
                ->orLike('c.name', $filters['q'])
                ->groupEnd();
        }
    }

    private function normalizeFilters(array $filters, array $submissionStatuses, array $allocationStatuses): array
    {
        $choiceType = strtoupper(trim((string) ($filters['choice_type'] ?? 'ALL')));
        $submissionStatus = strtoupper(trim((string) ($filters['submission_status'] ?? 'ALL')));
        $allocationStatus = strtoupper(trim((string) ($filters['allocation_status'] ?? 'ALL')));
        $sort = strtolower(trim((string) ($filters['sort'] ?? 'name')));
        $direction = strtolower(trim((string) ($filters['direction'] ?? 'asc'))) === 'desc' ? 'DESC' : 'ASC';
        $perPage = (int) ($filters['per_page'] ?? 25);

        $periodValue = strtolower(trim((string) ($filters['period_id'] ?? '')));

        return [
            'period_id' => $periodValue === 'all' ? 'all' : max(0, (int) $periodValue),
            'offering_id' => max(0, (int) ($filters['offering_id'] ?? 0)),
            'classroom_id' => max(0, (int) ($filters['classroom_id'] ?? 0)),
            'choice_type' => in_array($choiceType, ['ALL', 'PRIMARY', 'BACKUP'], true) ? $choiceType : 'ALL',
            'submission_status' => $submissionStatus === 'ALL' || in_array($submissionStatus, $submissionStatuses, true) ? $submissionStatus : 'ALL',
            'allocation_status' => $allocationStatus === 'ALL' || in_array($allocationStatus, $allocationStatuses, true) ? $allocationStatus : 'ALL',
            'q' => mb_substr(trim((string) ($filters['q'] ?? '')), 0, 100),
            'sort' => in_array($sort, ['name', 'classroom', 'subject', 'choice', 'status', 'priority'], true) ? $sort : 'name',
            'direction' => $direction,
            'page' => max(1, (int) ($filters['page'] ?? 1)),
            'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25,
        ];
    }

    public static function statusLabel(string $status): string
    {
        return [
            'SUBMITTED' => 'Diajukan',
            'WAITING_CURRICULUM' => 'Menunggu Kurikulum',
            'APPROVED' => 'Disetujui',
            'FINALIZED' => 'Final',
            'CHANGE_REQUESTED' => 'Mengajukan Perubahan',
            'CHANGED' => 'Sudah Diubah',
            'NEEDS_REVISION' => 'Perlu Perbaikan',
            'PENDING' => 'Menunggu',
            'ALLOCATED' => 'Dialokasikan',
            'REJECTED' => 'Tidak Dialokasikan',
        ][$status] ?? ucwords(strtolower(str_replace('_', ' ', $status)));
    }
}
