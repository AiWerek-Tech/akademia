<?php

namespace App\Services;

use Config\Database;
use InvalidArgumentException;
use RuntimeException;

/**
 * Phase 7 — Cocurricular & Character Engine.
 *
 * Implements the cocurricular program model, the blueprint workflow
 * (Need/Context Analysis → Design → Schedule → Execute → Formative →
 * Summative → Report → Evaluate → Follow-up), interdisciplinary mapping
 * junctions, profile-dimension assessment, INPUT→PROCESS→OUTPUT→OUTCOME
 * evaluation, and the optional 7KAIH support.
 */
class CocurricularService
{
    public const PROGRAM_TYPES = [
        'COCURRICULAR',
        'EXTRACURRICULAR',
        'FIXED_SCHOOL_ACTIVITY',
        'FORMATION',
        'SERVICE',
    ];

    public const PROGRAM_STATUS = [
        'DRAFT',
        'ACTIVE',
        'COMPLETED',
        'ARCHIVED',
    ];

    public const DELIVERY_MODELS = [
        'PROJECT',
        'BLOCK',
        'WEEKLY',
        'INTEGRATED',
    ];

    public const SESSION_STATUS = [
        'PLAN',
        'EXECUTED',
        'CANCELLED',
    ];

    public const SESSION_MODES = [
        'BLOCK',
        'WEEKLY',
        'PROJECT',
        'MIXED',
    ];

    public const OBSERVATION_TYPES = [
        'OBSERVATION',
        'JOURNAL',
        'PEER_FEEDBACK',
        'SELF_ASSESSMENT',
        'REFLECTION',
    ];

    public const EVIDENCE_TYPES = [
        'PERFORMANCE',
        'PROJECT',
        'ACTION',
        'PRODUCT',
        'PRESENTATION',
        'FINAL_REFLECTION',
    ];

    public const RESULT_LEVELS = [
        'EMERGING',
        'DEVELOPING',
        'PROFICIENT',
        'EXEMPLARY',
    ];

    public const EVALUATION_ASPECTS = [
        'INPUT',
        'PROCESS',
        'OUTPUT',
        'OUTCOME',
    ];

    public const HABIT_STATUS = [
        'DONE',
        'PARTIAL',
        'SKIP',
    ];

    private $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::connect();
    }

    // ----------------------------------------------------------------
    // Programs
    // ----------------------------------------------------------------

    public function programs(int $unitId, ?int $periodId = null, ?string $type = null): array
    {
        $builder = $this->db->table('cocurricular_programs p')
            ->select('p.*, ap.name as period_name, u.name as unit_name')
            ->join('academic_periods ap', 'ap.id = p.academic_period_id', 'left')
            ->join('school_units u', 'u.id = p.unit_id', 'left')
            ->where('p.unit_id', $unitId)
            ->orderBy('p.created_at', 'DESC');

        if ($periodId !== null) {
            $builder->where('p.academic_period_id', $periodId);
        }
        if ($type !== null && $type !== '') {
            $builder->where('p.program_type', $type);
        }

        return $builder->get()->getResultArray();
    }

    public function program(int $id): array
    {
        $program = $this->db->table('cocurricular_programs p')
            ->select('p.*, ap.name as period_name, u.name as unit_name')
            ->join('academic_periods ap', 'ap.id = p.academic_period_id', 'left')
            ->join('school_units u', 'u.id = p.unit_id', 'left')
            ->where('p.id', $id)
            ->get()
            ->getRowArray();

        if (! $program) {
            throw new RuntimeException('Program kokurikuler tidak ditemukan.');
        }

        return $program;
    }

    public function assertProgramInUnit(int $id, int $unitId): array
    {
        $program = $this->db->table('cocurricular_programs')
            ->where('id', $id)
            ->where('unit_id', $unitId)
            ->get()
            ->getRowArray();

        if (! $program) {
            throw new RuntimeException('Program kokurikuler tidak ditemukan pada unit yang dipilih.');
        }

        return $program;
    }

    public function programDetail(int $id): array
    {
        return [
            'program'      => $this->program($id),
            'dimensions'   => $this->programDimensions($id),
            'subjects'     => $this->programSubjects($id),
            'objectives'   => $this->programObjectives($id),
            'teachers'     => $this->programTeachers($id),
            'classes'      => $this->programClasses($id),
            'partners'     => $this->programPartners($id),
            'resources'    => $this->programResources($id),
            'sessions'     => $this->sessions($id),
            'observations' => $this->observations($id),
            'evidences'    => $this->evidences($id),
            'results'      => $this->results($id),
            'evaluations'  => $this->evaluations($id),
        ];
    }

    public function createProgram(int $unitId, int $periodId, array $data, int $userId): int
    {
        $this->assertPeriodInUnit($periodId, $unitId);

        $programType = strtoupper(trim((string) ($data['program_type'] ?? 'COCURRICULAR')));
        $delivery    = strtoupper(trim((string) ($data['delivery_model'] ?? 'PROJECT')));

        if (! in_array($programType, self::PROGRAM_TYPES, true)) {
            throw new InvalidArgumentException('Jenis program kokurikuler tidak valid.');
        }
        if (! in_array($delivery, self::DELIVERY_MODELS, true)) {
            throw new InvalidArgumentException('Model pelaksanaan tidak valid.');
        }
        if (trim((string) ($data['title'] ?? '')) === '') {
            throw new InvalidArgumentException('Nama program wajib diisi.');
        }

        $this->db->table('cocurricular_programs')->insert([
            'uuid'               => UuidService::v4(),
            'unit_id'            => $unitId,
            'academic_period_id' => $periodId,
            'ksp_version_id'     => ! empty($data['ksp_version_id']) ? (int) $data['ksp_version_id'] : null,
            'code'               => trim((string) ($data['code'] ?? '')) ?: null,
            'title'              => trim((string) $data['title']),
            'program_type'       => $programType,
            'theme'              => trim((string) ($data['theme'] ?? '')) ?: null,
            'rationale'          => trim((string) ($data['rationale'] ?? '')) ?: null,
            'objective'          => trim((string) ($data['objective'] ?? '')) ?: null,
            'annual_minutes'     => ! empty($data['annual_minutes']) ? (int) $data['annual_minutes'] : null,
            'delivery_model'     => $delivery,
            'start_date'         => ! empty($data['start_date']) ? $data['start_date'] : null,
            'end_date'           => ! empty($data['end_date']) ? $data['end_date'] : null,
            'status'             => 'DRAFT',
            'description'        => trim((string) ($data['description'] ?? '')) ?: null,
            'created_by'         => $userId,
            'updated_by'         => $userId,
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);
        $programId = (int) $this->db->insertID();

        $this->syncJunctions($programId, $data, $userId);

        AuditService::log('cocurricular', 'create_program', 'CocurricularProgram', $programId,
            null, ['title' => trim((string) $data['title']), 'program_type' => $programType]);

        return $programId;
    }

    public function updateProgram(int $id, int $unitId, array $data, int $userId): void
    {
        $this->assertProgramInUnit($id, $unitId);
        $program = $this->db->table('cocurricular_programs')->where('id', $id)->get()->getRowArray();
        if (strtoupper((string) $program['status']) !== 'DRAFT') {
            throw new RuntimeException('Desain program hanya dapat diubah saat status DRAFT.');
        }

        $programType = strtoupper(trim((string) ($data['program_type'] ?? $program['program_type'])));
        $delivery    = strtoupper(trim((string) ($data['delivery_model'] ?? $program['delivery_model'])));

        if (! in_array($programType, self::PROGRAM_TYPES, true)) {
            throw new InvalidArgumentException('Jenis program kokurikuler tidak valid.');
        }
        if (! in_array($delivery, self::DELIVERY_MODELS, true)) {
            throw new InvalidArgumentException('Model pelaksanaan tidak valid.');
        }
        if (trim((string) ($data['title'] ?? '')) === '') {
            throw new InvalidArgumentException('Nama program wajib diisi.');
        }

        $this->db->table('cocurricular_programs')->where('id', $id)->update([
            'ksp_version_id'   => ! empty($data['ksp_version_id']) ? (int) $data['ksp_version_id'] : null,
            'code'             => trim((string) ($data['code'] ?? '')) ?: null,
            'title'            => trim((string) $data['title']),
            'program_type'     => $programType,
            'theme'            => trim((string) ($data['theme'] ?? '')) ?: null,
            'rationale'        => trim((string) ($data['rationale'] ?? '')) ?: null,
            'objective'        => trim((string) ($data['objective'] ?? '')) ?: null,
            'annual_minutes'   => ! empty($data['annual_minutes']) ? (int) $data['annual_minutes'] : null,
            'delivery_model'   => $delivery,
            'start_date'       => ! empty($data['start_date']) ? $data['start_date'] : null,
            'end_date'         => ! empty($data['end_date']) ? $data['end_date'] : null,
            'description'      => trim((string) ($data['description'] ?? '')) ?: null,
            'updated_by'       => $userId,
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        $this->syncJunctions($id, $data, $userId);

        AuditService::log('cocurricular', 'update_program', 'CocurricularProgram', $id,
            null, ['title' => trim((string) $data['title'])]);
    }

    public function transition(int $id, int $unitId, string $targetStatus, int $userId): void
    {
        $program = $this->assertProgramInUnit($id, $unitId);
        $current = strtoupper((string) $program['status']);
        $target  = strtoupper($targetStatus);

        if (! in_array($target, self::PROGRAM_STATUS, true)) {
            throw new InvalidArgumentException('Status program tidak valid.');
        }

        $allowed = [
            'DRAFT'     => ['ACTIVE', 'ARCHIVED'],
            'ACTIVE'    => ['COMPLETED', 'ARCHIVED'],
            'COMPLETED' => ['ACTIVE', 'ARCHIVED'],
            'ARCHIVED'  => ['DRAFT'],
        ];

        if (! in_array($target, $allowed[$current] ?? [], true)) {
            throw new InvalidArgumentException("Transisi status {$current} → {$target} tidak diizinkan.");
        }

        $this->db->table('cocurricular_programs')->where('id', $id)->update([
            'status'     => $target,
            'updated_by' => $userId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        AuditService::log('cocurricular', 'transition_program', 'CocurricularProgram', $id,
            ['status' => $current], ['status' => $target]);
    }

    public function deleteProgram(int $id, int $unitId, int $userId): void
    {
        $program = $this->assertProgramInUnit($id, $unitId);
        if (strtoupper((string) $program['status']) !== 'DRAFT') {
            throw new RuntimeException('Program yang sudah aktif/berjalan tidak dapat dihapus.');
        }

        $this->db->table('cocurricular_programs')->where('id', $id)->delete();

        AuditService::log('cocurricular', 'delete_program', 'CocurricularProgram', $id,
            ['title' => $program['title']], null);
    }

    private function assertPeriodInUnit(int $periodId, int $unitId): void
    {
        $period = $this->db->table('academic_periods')
            ->where('id', $periodId)
            ->where('is_active', 1)
            ->get()
            ->getRowArray();
        if (! $period) {
            throw new InvalidArgumentException('Periode akademik aktif tidak valid.');
        }
        $year = $this->db->table('academic_years')->where('id', $period['academic_year_id'])->get()->getRowArray();
        if ($year && isset($year['unit_id']) && (int) $year['unit_id'] !== $unitId) {
            throw new InvalidArgumentException('Periode akademik tidak cocok dengan unit yang dipilih.');
        }
    }

    // ----------------------------------------------------------------
    // Junction synchronization & readers
    // ----------------------------------------------------------------

    private function syncJunctions(int $programId, array $data, int $userId): void
    {
        $junctions = [
            'dimension_ids' => 'cocurricular_program_dimensions',
            'subject_ids'   => 'cocurricular_program_subjects',
            'objective_ids' => 'cocurricular_program_objectives',
            'teacher_ids'   => 'cocurricular_program_teachers',
            'classroom_ids' => 'cocurricular_program_classes',
        ];

        foreach ($junctions as $field => $table) {
            $this->db->table($table)->where('program_id', $programId)->delete();
            $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($data[$field] ?? [])))));
            foreach ($ids as $refId) {
                if ($refId <= 0) {
                    continue;
                }
                $this->db->table($table)->insert([
                    'uuid'       => UuidService::v4(),
                    'program_id' => $programId,
                    $this->junctionRefColumn($table) => $refId,
                ]);
            }
        }

        $teacherRoles = (array) ($data['teacher_roles'] ?? []);
        foreach ($teacherRoles as $teacherId => $role) {
            $this->db->table('cocurricular_program_teachers')
                ->where('program_id', $programId)
                ->where('teacher_id', (int) $teacherId)
                ->update(['role' => trim((string) $role) ?: 'FACILITATOR']);
        }

        $this->db->table('cocurricular_program_partners')->where('program_id', $programId)->delete();
        foreach ((array) ($data['partners'] ?? []) as $partner) {
            $name = trim((string) ($partner['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $this->db->table('cocurricular_program_partners')->insert([
                'uuid'         => UuidService::v4(),
                'program_id'   => $programId,
                'name'         => $name,
                'partner_type' => trim((string) ($partner['partner_type'] ?? '')) ?: null,
                'role'         => trim((string) ($partner['role'] ?? '')) ?: null,
                'contact'      => trim((string) ($partner['contact'] ?? '')) ?: null,
            ]);
        }

        $this->db->table('cocurricular_program_resources')->where('program_id', $programId)->delete();
        foreach ((array) ($data['resources'] ?? []) as $resource) {
            $name = trim((string) ($resource['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $this->db->table('cocurricular_program_resources')->insert([
                'uuid'          => UuidService::v4(),
                'program_id'    => $programId,
                'name'          => $name,
                'resource_type' => trim((string) ($resource['resource_type'] ?? '')) ?: null,
                'quantity'      => ! empty($resource['quantity']) ? (int) $resource['quantity'] : null,
                'notes'         => trim((string) ($resource['notes'] ?? '')) ?: null,
            ]);
        }

        $this->db->table('cocurricular_programs')->where('id', $programId)->update([
            'updated_by' => $userId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function junctionRefColumn(string $table): string
    {
        return match ($table) {
            'cocurricular_program_dimensions' => 'dimension_id',
            'cocurricular_program_subjects'   => 'subject_id',
            'cocurricular_program_objectives' => 'learning_objective_id',
            'cocurricular_program_teachers'   => 'teacher_id',
            default                           => 'classroom_id',
        };
    }

    public function programDimensions(int $programId): array
    {
        return $this->db->table('cocurricular_program_dimensions pd')
            ->select('pd.*, gpd.code, gpd.name, gpd.description')
            ->join('graduate_profile_dimensions gpd', 'gpd.id = pd.dimension_id', 'left')
            ->where('pd.program_id', $programId)
            ->orderBy('gpd.sort_order', 'ASC')
            ->get()->getResultArray();
    }

    public function programSubjects(int $programId): array
    {
        return $this->db->table('cocurricular_program_subjects ps')
            ->select('ps.*, s.code, s.name')
            ->join('subjects s', 's.id = ps.subject_id', 'left')
            ->where('ps.program_id', $programId)
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();
    }

    public function programObjectives(int $programId): array
    {
        return $this->db->table('cocurricular_program_objectives po')
            ->select('po.*, lo.code, lo.statement as description')
            ->join('learning_objectives_tp lo', 'lo.id = po.learning_objective_id', 'left')
            ->where('po.program_id', $programId)
            ->orderBy('lo.code', 'ASC')
            ->get()->getResultArray();
    }

    public function programTeachers(int $programId): array
    {
        return $this->db->table('cocurricular_program_teachers pt')
            ->select('pt.*, t.full_name, t.teacher_initial')
            ->join('teachers t', 't.id = pt.teacher_id', 'left')
            ->where('pt.program_id', $programId)
            ->orderBy('t.full_name', 'ASC')
            ->get()->getResultArray();
    }

    public function programClasses(int $programId): array
    {
        return $this->db->table('cocurricular_program_classes pc')
            ->select('pc.*, c.name as classroom_name, c.grade_level_id, gl.name as grade_name')
            ->join('classrooms c', 'c.id = pc.classroom_id', 'left')
            ->join('grade_levels gl', 'gl.id = c.grade_level_id', 'left')
            ->where('pc.program_id', $programId)
            ->orderBy('c.name', 'ASC')
            ->get()->getResultArray();
    }

    public function programPartners(int $programId): array
    {
        return $this->db->table('cocurricular_program_partners')
            ->where('program_id', $programId)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();
    }

    public function programResources(int $programId): array
    {
        return $this->db->table('cocurricular_program_resources')
            ->where('program_id', $programId)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();
    }

    // ----------------------------------------------------------------
    // Sessions (Schedule → Execute)
    // ----------------------------------------------------------------

    public function sessions(int $programId): array
    {
        return $this->db->table('cocurricular_sessions s')
            ->select('s.*, c.name as classroom_name, t.full_name as teacher_name')
            ->join('classrooms c', 'c.id = s.classroom_id', 'left')
            ->join('teachers t', 't.id = s.teacher_id', 'left')
            ->where('s.program_id', $programId)
            ->orderBy('s.session_date', 'ASC')
            ->orderBy('s.start_time', 'ASC')
            ->get()->getResultArray();
    }

    public function addSession(int $programId, int $unitId, array $data, int $userId): int
    {
        $this->assertProgramInUnit($programId, $unitId);
        if (empty($data['session_date'])) {
            throw new InvalidArgumentException('Tanggal sesi wajib diisi.');
        }
        if (trim((string) ($data['title'] ?? '')) === '') {
            throw new InvalidArgumentException('Judul sesi wajib diisi.');
        }
        $mode = strtoupper(trim((string) ($data['mode'] ?? 'WEEKLY')));
        if (! in_array($mode, self::SESSION_MODES, true)) {
            throw new InvalidArgumentException('Moda sesi tidak valid.');
        }

        $this->db->table('cocurricular_sessions')->insert([
            'uuid'        => UuidService::v4(),
            'program_id'  => $programId,
            'classroom_id'=> ! empty($data['classroom_id']) ? (int) $data['classroom_id'] : null,
            'teacher_id'  => ! empty($data['teacher_id']) ? (int) $data['teacher_id'] : null,
            'title'       => trim((string) $data['title']),
            'session_date'=> $data['session_date'],
            'start_time'  => ! empty($data['start_time']) ? $data['start_time'] : null,
            'end_time'    => ! empty($data['end_time']) ? $data['end_time'] : null,
            'mode'        => $mode,
            'status'      => 'PLAN',
            'notes'       => trim((string) ($data['notes'] ?? '')) ?: null,
            'created_by'  => $userId,
            'updated_by'  => $userId,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->insertID();
    }

    public function updateSession(int $sessionId, int $programId, int $unitId, array $data, int $userId): void
    {
        $this->assertProgramInUnit($programId, $unitId);
        $session = $this->db->table('cocurricular_sessions')->where('id', $sessionId)->where('program_id', $programId)->get()->getRowArray();
        if (! $session) {
            throw new RuntimeException('Sesi kokurikuler tidak ditemukan.');
        }
        if (strtoupper((string) $session['status']) === 'EXECUTED') {
            throw new RuntimeException('Sesi yang sudah dieksekusi tidak dapat diubah; buat sesi baru bila perlu.');
        }

        $this->db->table('cocurricular_sessions')->where('id', $sessionId)->update([
            'classroom_id'=> ! empty($data['classroom_id']) ? (int) $data['classroom_id'] : null,
            'teacher_id'  => ! empty($data['teacher_id']) ? (int) $data['teacher_id'] : null,
            'title'       => trim((string) ($data['title'] ?? $session['title'])),
            'session_date'=> ! empty($data['session_date']) ? $data['session_date'] : $session['session_date'],
            'start_time'  => array_key_exists('start_time', $data) && $data['start_time'] !== '' ? $data['start_time'] : $session['start_time'],
            'end_time'    => array_key_exists('end_time', $data) && $data['end_time'] !== '' ? $data['end_time'] : $session['end_time'],
            'notes'       => trim((string) ($data['notes'] ?? '')) ?: null,
            'updated_by'  => $userId,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    public function deleteSession(int $sessionId, int $programId, int $unitId): void
    {
        $this->assertProgramInUnit($programId, $unitId);
        $this->db->table('cocurricular_sessions')->where('id', $sessionId)->where('program_id', $programId)->delete();
    }

    public function executeSession(int $sessionId, int $programId, int $unitId, int $userId): void
    {
        $this->assertProgramInUnit($programId, $unitId);
        $session = $this->db->table('cocurricular_sessions')->where('id', $sessionId)->where('program_id', $programId)->get()->getRowArray();
        if (! $session) {
            throw new RuntimeException('Sesi kokurikuler tidak ditemukan.');
        }
        if (strtoupper((string) $session['status']) !== 'PLAN') {
            throw new RuntimeException('Hanya sesi berstatus PLAN yang dapat dieksekusi.');
        }

        $this->db->table('cocurricular_sessions')->where('id', $sessionId)->update([
            'status'      => 'EXECUTED',
            'executed_at' => date('Y-m-d H:i:s'),
            'updated_by'  => $userId,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        AuditService::log('cocurricular', 'execute_session', 'CocurricularSession', $sessionId,
            ['status' => 'PLAN'], ['status' => 'EXECUTED']);
    }

    public function cancelSession(int $sessionId, int $programId, int $unitId, int $userId): void
    {
        $this->assertProgramInUnit($programId, $unitId);
        $this->db->table('cocurricular_sessions')
            ->where('id', $sessionId)
            ->where('program_id', $programId)
            ->update([
                'status'     => 'CANCELLED',
                'updated_by' => $userId,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    // ----------------------------------------------------------------
    // Formative monitoring (observations / journal / feedback / reflection)
    // ----------------------------------------------------------------

    public function observations(int $programId, ?int $studentId = null): array
    {
        $builder = $this->db->table('cocurricular_observations o')
            ->select('o.*, s.full_name as student_name, s.student_number, t.full_name as teacher_name, gpd.name as dimension_name')
            ->join('elective_students s', 's.id = o.student_id', 'left')
            ->join('teachers t', 't.id = o.teacher_id', 'left')
            ->join('graduate_profile_dimensions gpd', 'gpd.id = o.dimension_id', 'left')
            ->where('o.program_id', $programId)
            ->orderBy('o.observed_on', 'DESC');

        if ($studentId !== null) {
            $builder->where('o.student_id', $studentId);
        }

        return $builder->get()->getResultArray();
    }

    public function addObservation(int $programId, int $unitId, array $data, int $userId): int
    {
        $this->assertProgramInUnit($programId, $unitId);
        if (empty($data['student_id'])) {
            throw new InvalidArgumentException('Siswa wajib dipilih.');
        }
        $type = strtoupper(trim((string) ($data['observation_type'] ?? 'OBSERVATION')));
        if (! in_array($type, self::OBSERVATION_TYPES, true)) {
            throw new InvalidArgumentException('Jenis catatan formatif tidak valid.');
        }
        if (trim((string) ($data['notes'] ?? '')) === '') {
            throw new InvalidArgumentException('Catatan wajib diisi.');
        }
        $rating = isset($data['rating']) && $data['rating'] !== '' ? (int) $data['rating'] : null;
        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            throw new InvalidArgumentException('Rating harus antara 1–5.');
        }

        $this->db->table('cocurricular_observations')->insert([
            'uuid'             => UuidService::v4(),
            'program_id'       => $programId,
            'session_id'       => ! empty($data['session_id']) ? (int) $data['session_id'] : null,
            'student_id'       => (int) $data['student_id'],
            'teacher_id'       => (int) ($data['teacher_id'] ?? $userId),
            'observation_type' => $type,
            'dimension_id'     => ! empty($data['dimension_id']) ? (int) $data['dimension_id'] : null,
            'notes'            => trim((string) $data['notes']),
            'rating'           => $rating,
            'observed_on'      => ! empty($data['observed_on']) ? $data['observed_on'] : date('Y-m-d'),
            'created_by'       => $userId,
            'updated_by'       => $userId,
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->insertID();
    }

    public function updateObservation(int $observationId, int $programId, int $unitId, array $data, int $userId): void
    {
        $this->assertProgramInUnit($programId, $unitId);
        $this->db->table('cocurricular_observations')->where('id', $observationId)->where('program_id', $programId)->update([
            'notes'        => trim((string) ($data['notes'] ?? '')),
            'dimension_id' => ! empty($data['dimension_id']) ? (int) $data['dimension_id'] : null,
            'updated_by'   => $userId,
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    public function deleteObservation(int $observationId, int $programId, int $unitId): void
    {
        $this->assertProgramInUnit($programId, $unitId);
        $this->db->table('cocurricular_observations')->where('id', $observationId)->where('program_id', $programId)->delete();
    }

    // ----------------------------------------------------------------
    // Summative evidence
    // ----------------------------------------------------------------

    public function evidences(int $programId, ?int $studentId = null): array
    {
        $builder = $this->db->table('cocurricular_evidences e')
            ->select('e.*, s.full_name as student_name, s.student_number, gpd.name as dimension_name')
            ->join('elective_students s', 's.id = e.student_id', 'left')
            ->join('graduate_profile_dimensions gpd', 'gpd.id = e.dimension_id', 'left')
            ->where('e.program_id', $programId)
            ->orderBy('e.captured_at', 'DESC');

        if ($studentId !== null) {
            $builder->where('e.student_id', $studentId);
        }

        return $builder->get()->getResultArray();
    }

    public function evidence(int $evidenceId): array
    {
        $evidence = $this->db->table('cocurricular_evidences')
            ->where('id', $evidenceId)
            ->get()
            ->getRowArray();
        if (! $evidence) {
            throw new RuntimeException('Bukti kokurikuler tidak ditemukan.');
        }

        return $evidence;
    }

    public function addEvidence(int $programId, int $unitId, array $data, int $userId, ?array $file = null): int
    {
        $this->assertProgramInUnit($programId, $unitId);
        if (empty($data['student_id'])) {
            throw new InvalidArgumentException('Siswa wajib dipilih.');
        }
        $type = strtoupper(trim((string) ($data['evidence_type'] ?? 'PROJECT')));
        if (! in_array($type, self::EVIDENCE_TYPES, true)) {
            throw new InvalidArgumentException('Jenis bukti sumatif tidak valid.');
        }
        if (trim((string) ($data['title'] ?? '')) === '') {
            throw new InvalidArgumentException('Judul bukti wajib diisi.');
        }

        $this->db->table('cocurricular_evidences')->insert([
            'uuid'          => UuidService::v4(),
            'program_id'    => $programId,
            'student_id'    => (int) $data['student_id'],
            'dimension_id'  => ! empty($data['dimension_id']) ? (int) $data['dimension_id'] : null,
            'title'         => trim((string) $data['title']),
            'evidence_type' => $type,
            'description'   => trim((string) ($data['description'] ?? '')) ?: null,
            'file_path'     => $file ? trim((string) $file['path']) : null,
            'meta_json'     => $file ? json_encode($file['meta']) : null,
            'captured_at'   => ! empty($data['captured_at']) ? $data['captured_at'] : date('Y-m-d H:i:s'),
            'created_by'    => $userId,
            'updated_by'    => $userId,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->insertID();
    }

    public function deleteEvidence(int $evidenceId, int $programId, int $unitId): void
    {
        $this->assertProgramInUnit($programId, $unitId);
        $this->db->table('cocurricular_evidences')->where('id', $evidenceId)->where('program_id', $programId)->delete();
    }

    // ----------------------------------------------------------------
    // Per-student × dimension results
    // ----------------------------------------------------------------

    public function results(int $programId): array
    {
        return $this->db->table('cocurricular_student_results r')
            ->select('r.*, s.full_name as student_name, s.student_number, c.name as classroom_name, gpd.name as dimension_name')
            ->join('elective_students s', 's.id = r.student_id', 'left')
            ->join('classrooms c', 'c.id = s.classroom_id', 'left')
            ->join('graduate_profile_dimensions gpd', 'gpd.id = r.dimension_id', 'left')
            ->where('r.program_id', $programId)
            ->orderBy('s.full_name', 'ASC')
            ->get()->getResultArray();
    }

    public function studentsForResults(int $programId): array
    {
        $program = $this->program($programId);
        $classes = $this->programClasses($programId);

        if ($classes !== []) {
            $classIds = array_values(array_unique(array_map('intval', array_column($classes, 'classroom_id'))));
            return $this->db->table('elective_students s')
                ->select('s.id, s.full_name, s.student_number, s.current_grade, c.name as classroom_name')
                ->join('classrooms c', 'c.id = s.classroom_id', 'left')
                ->whereIn('s.classroom_id', $classIds)
                ->where('s.unit_id', $program['unit_id'])
                ->where('s.is_active', 1)
                ->orderBy('s.full_name', 'ASC')
                ->get()->getResultArray();
        }

        return $this->db->table('elective_students s')
            ->select('s.id, s.full_name, s.student_number, s.current_grade, c.name as classroom_name')
            ->join('classrooms c', 'c.id = s.classroom_id', 'left')
            ->where('s.unit_id', $program['unit_id'])
            ->where('s.is_active', 1)
            ->orderBy('s.full_name', 'ASC')
            ->get()->getResultArray();
    }

    public function saveResults(int $programId, int $unitId, array $payload, int $userId): int
    {
        $this->assertProgramInUnit($programId, $unitId);
        $saved = 0;

        foreach ((array) ($payload['results'] ?? []) as $studentId => $dimensionRows) {
            $studentId = (int) $studentId;
            foreach ((array) $dimensionRows as $dimensionId => $value) {
                $dimensionId = (int) $dimensionId;
                if ($dimensionId <= 0) {
                    continue;
                }
                $level = strtoupper(trim((string) (is_array($value) ? ($value['level'] ?? '') : $value)));
                if (! in_array($level, self::RESULT_LEVELS, true)) {
                    continue;
                }
                $note = is_array($value) ? trim((string) ($value['note'] ?? '')) : '';

                $existing = $this->db->table('cocurricular_student_results')
                    ->where('program_id', $programId)
                    ->where('student_id', $studentId)
                    ->where('dimension_id', $dimensionId)
                    ->get()->getRowArray();

                if ($existing) {
                    $this->db->table('cocurricular_student_results')->where('id', $existing['id'])->update([
                        'level'      => $level,
                        'note'       => $note ?: null,
                        'updated_by' => $userId,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                } else {
                    $this->db->table('cocurricular_student_results')->insert([
                        'uuid'         => UuidService::v4(),
                        'program_id'   => $programId,
                        'student_id'   => $studentId,
                        'dimension_id' => $dimensionId,
                        'level'        => $level,
                        'note'         => $note ?: null,
                        'created_by'   => $userId,
                        'updated_by'   => $userId,
                        'created_at'   => date('Y-m-d H:i:s'),
                        'updated_at'   => date('Y-m-d H:i:s'),
                    ]);
                }
                $saved++;
            }
        }

        return $saved;
    }

    // ----------------------------------------------------------------
    // Evaluation (INPUT → PROCESS → OUTPUT → OUTCOME)
    // ----------------------------------------------------------------

    public function evaluations(int $programId): array
    {
        return $this->db->table('cocurricular_evaluations')
            ->where('program_id', $programId)
            ->orderBy('aspect', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();
    }

    public function addEvaluation(int $programId, int $unitId, array $data, int $userId): int
    {
        $this->assertProgramInUnit($programId, $unitId);
        $aspect = strtoupper(trim((string) ($data['aspect'] ?? 'INPUT')));
        if (! in_array($aspect, self::EVALUATION_ASPECTS, true)) {
            throw new InvalidArgumentException('Aspek evaluasi tidak valid.');
        }
        if (trim((string) ($data['indicator'] ?? '')) === '') {
            throw new InvalidArgumentException('Indikator evaluasi wajib diisi.');
        }

        $this->db->table('cocurricular_evaluations')->insert([
            'uuid'        => UuidService::v4(),
            'program_id'  => $programId,
            'aspect'      => $aspect,
            'indicator'   => trim((string) $data['indicator']),
            'finding'     => trim((string) ($data['finding'] ?? '')) ?: null,
            'rating'      => isset($data['rating']) && $data['rating'] !== '' ? (int) $data['rating'] : null,
            'created_by'  => $userId,
            'updated_by'  => $userId,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->insertID();
    }

    public function updateEvaluation(int $evaluationId, int $programId, int $unitId, array $data, int $userId): void
    {
        $this->assertProgramInUnit($programId, $unitId);
        $this->db->table('cocurricular_evaluations')->where('id', $evaluationId)->where('program_id', $programId)->update([
            'indicator'  => trim((string) ($data['indicator'] ?? '')),
            'finding'    => trim((string) ($data['finding'] ?? '')) ?: null,
            'rating'     => isset($data['rating']) && $data['rating'] !== '' ? (int) $data['rating'] : null,
            'updated_by' => $userId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function deleteEvaluation(int $evaluationId, int $programId, int $unitId): void
    {
        $this->assertProgramInUnit($programId, $unitId);
        $this->db->table('cocurricular_evaluations')->where('id', $evaluationId)->where('program_id', $programId)->delete();
    }

    // ----------------------------------------------------------------
    // Report (profile-dimension achievement, blueprint §7)
    // ----------------------------------------------------------------

    public function report(int $programId): array
    {
        $program = $this->program($programId);
        $dimensions = $this->programDimensions($programId);
        $students   = $this->studentsForResults($programId);
        $results    = $this->results($programId);

        $resultMap = [];
        foreach ($results as $row) {
            $resultMap[$row['student_id'] . ':' . $row['dimension_id']] = $row;
        }

        $evidenceCounts = [];
        $observationCounts = [];
        foreach ($this->db->table('cocurricular_evidences')->select('student_id, COUNT(*) as c')->where('program_id', $programId)->groupBy('student_id')->get()->getResultArray() as $row) {
            $evidenceCounts[(int) $row['student_id']] = (int) $row['c'];
        }
        foreach ($this->db->table('cocurricular_observations')->select('student_id, COUNT(*) as c')->where('program_id', $programId)->groupBy('student_id')->get()->getResultArray() as $row) {
            $observationCounts[(int) $row['student_id']] = (int) $row['c'];
        }

        $rows = [];
        foreach ($students as $student) {
            $sid = (int) $student['id'];
            $dimensionCells = [];
            foreach ($dimensions as $dimension) {
                $cell = $resultMap[$sid . ':' . $dimension['dimension_id']] ?? null;
                $dimensionCells[] = [
                    'dimension_id' => $dimension['dimension_id'],
                    'code'         => $dimension['code'],
                    'name'         => $dimension['name'],
                    'level'        => $cell ? $cell['level'] : 'EMERGING',
                    'note'         => $cell ? $cell['note'] : null,
                ];
            }
            $rows[] = [
                'student'      => $student,
                'dimensions'   => $dimensionCells,
                'evidences'    => $evidenceCounts[$sid] ?? 0,
                'observations' => $observationCounts[$sid] ?? 0,
            ];
        }

        $dimensionSummaries = [];
        foreach ($dimensions as $dimension) {
            $distribution = array_fill_keys(self::RESULT_LEVELS, 0);
            foreach ($rows as $row) {
                foreach ($row['dimensions'] as $cell) {
                    if ((int) $cell['dimension_id'] === (int) $dimension['dimension_id']) {
                        $distribution[$cell['level']]++;
                    }
                }
            }
            $dimensionSummaries[] = [
                'dimension_id' => $dimension['dimension_id'],
                'code'         => $dimension['code'],
                'name'         => $dimension['name'],
                'distribution' => $distribution,
            ];
        }

        return [
            'program'    => $program,
            'dimensions' => $dimensions,
            'rows'       => $rows,
            'summary'    => $dimensionSummaries,
        ];
    }

    // ----------------------------------------------------------------
    // 7KAIH (optional, feature-flagged)
    // ----------------------------------------------------------------

    public function habits(?int $unitId = null, bool $includeDisabled = false): array
    {
        $builder = $this->db->table('cocurricular_habits')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC');
        if ($unitId !== null) {
            $builder->groupStart()->where('unit_id', $unitId)->orWhere('unit_id IS NULL')->groupEnd();
        }
        if (! $includeDisabled) {
            $builder->where('enabled', 1);
        }

        return $builder->get()->getResultArray();
    }

    public function saveHabit(int $unitId, array $data, int $userId): int
    {
        if (trim((string) ($data['name'] ?? '')) === '') {
            throw new InvalidArgumentException('Nama kebiasaan wajib diisi.');
        }
        $code = strtoupper(trim((string) ($data['code'] ?? '')));
        if ($code === '') {
            $code = 'HABIT-' . strtoupper(substr(md5(trim((string) $data['name'])), 0, 8));
        }

        $duplicate = $this->db->table('cocurricular_habits')
            ->where('code', $code)
            ->groupStart()->where('unit_id', $unitId)->orWhere('unit_id IS NULL')->groupEnd()
            ->get()->getRowArray();
        if ($duplicate) {
            throw new InvalidArgumentException('Kode kebiasaan sudah digunakan.');
        }

        $this->db->table('cocurricular_habits')->insert([
            'uuid'            => UuidService::v4(),
            'unit_id'         => $unitId,
            'code'            => $code,
            'name'            => trim((string) $data['name']),
            'description'     => trim((string) ($data['description'] ?? '')) ?: null,
            'icon'            => trim((string) ($data['icon'] ?? '')) ?: null,
            'weekly_challenge'=> trim((string) ($data['weekly_challenge'] ?? '')) ?: null,
            'sort_order'      => (int) ($data['sort_order'] ?? 1),
            'enabled'         => isset($data['enabled']) && (int) $data['enabled'] === 1 ? 1 : 1,
            'created_by'      => $userId,
            'updated_by'      => $userId,
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->insertID();
    }

    public function updateHabit(int $habitId, int $unitId, array $data, int $userId): void
    {
        $this->db->table('cocurricular_habits')->where('id', $habitId)->update([
            'name'             => trim((string) ($data['name'] ?? '')),
            'description'      => trim((string) ($data['description'] ?? '')) ?: null,
            'icon'             => trim((string) ($data['icon'] ?? '')) ?: null,
            'weekly_challenge' => trim((string) ($data['weekly_challenge'] ?? '')) ?: null,
            'sort_order'       => (int) ($data['sort_order'] ?? 1),
            'enabled'          => isset($data['enabled']) ? (int) $data['enabled'] : 1,
            'updated_by'       => $userId,
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);
    }

    public function deleteHabit(int $habitId, int $unitId): void
    {
        $this->db->table('cocurricular_habits')->where('id', $habitId)->where('unit_id', $unitId)->delete();
    }

    public function checkins(int $unitId, string $week, ?int $classroomId = null, ?int $habitId = null): array
    {
        $habits = $this->habits($unitId);
        if ($habitId !== null) {
            $habits = array_values(array_filter($habits, static fn ($h) => (int) $h['id'] === $habitId));
        }

        $students = $this->db->table('elective_students s')
            ->select('s.id, s.full_name, s.student_number, c.name as classroom_name')
            ->join('classrooms c', 'c.id = s.classroom_id', 'left')
            ->where('s.unit_id', $unitId)
            ->where('s.is_active', 1);
        if ($classroomId !== null) {
            $students->where('s.classroom_id', $classroomId);
        }
        $students = $students->orderBy('s.full_name', 'ASC')->get()->getResultArray();

        $existing = [];
        if ($habits !== []) {
            $existing = $this->db->table('cocurricular_habit_checkins')
                ->where('checkin_week', $week)
                ->whereIn('habit_id', array_column($habits, 'id'))
                ->get()->getResultArray();
        }
        $checkinMap = [];
        foreach ($existing as $row) {
            $checkinMap[$row['habit_id'] . ':' . $row['student_id']] = $row;
        }

        return [
            'habits'   => $habits,
            'students' => $students,
            'checkins' => $checkinMap,
        ];
    }

    public function saveCheckins(int $unitId, string $week, array $payload, ?int $teacherId, int $userId): int
    {
        $saved = 0;
        foreach ((array) ($payload['checkins'] ?? []) as $studentId => $habitRows) {
            $studentId = (int) $studentId;
            foreach ((array) $habitRows as $habitId => $value) {
                $habitId = (int) $habitId;
                if ($habitId <= 0) {
                    continue;
                }
                $status = strtoupper(trim((string) (is_array($value) ? ($value['status'] ?? '') : $value)));
                if ($status === '' || $status === 'UNSET') {
                    continue;
                }
                if (! in_array($status, self::HABIT_STATUS, true)) {
                    throw new InvalidArgumentException("Status kebiasaan tidak valid: {$status}");
                }
                $note       = is_array($value) ? trim((string) ($value['note'] ?? '')) : '';
                $parentNote = is_array($value) ? trim((string) ($value['parent_note'] ?? '')) : '';

                $existing = $this->db->table('cocurricular_habit_checkins')
                    ->where('habit_id', $habitId)
                    ->where('student_id', $studentId)
                    ->where('checkin_week', $week)
                    ->get()->getRowArray();

                if ($existing) {
                    $this->db->table('cocurricular_habit_checkins')->where('id', $existing['id'])->update([
                        'status'      => $status,
                        'note'        => $note ?: null,
                        'parent_note' => $parentNote ?: null,
                        'teacher_id'  => $teacherId,
                        'updated_by'  => $userId,
                        'updated_at'  => date('Y-m-d H:i:s'),
                    ]);
                } else {
                    $this->db->table('cocurricular_habit_checkins')->insert([
                        'uuid'        => UuidService::v4(),
                        'habit_id'    => $habitId,
                        'student_id'  => $studentId,
                        'checkin_week'=> $week,
                        'teacher_id'  => $teacherId,
                        'status'      => $status,
                        'note'        => $note ?: null,
                        'parent_note' => $parentNote ?: null,
                        'created_by'  => $userId,
                        'updated_by'  => $userId,
                        'created_at'  => date('Y-m-d H:i:s'),
                        'updated_at'  => date('Y-m-d H:i:s'),
                    ]);
                }
                $saved++;
            }
        }

        return $saved;
    }

    // ----------------------------------------------------------------
    // Reference data for dropdowns
    // ----------------------------------------------------------------

    public function dimensions(): array
    {
        return $this->db->table('graduate_profile_dimensions')
            ->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();
    }

    public function subjectsForUnit(int $unitId): array
    {
        return $this->db->table('subjects s')
            ->select('s.id, s.code, s.name')
            ->join('subject_unit_availability sua', 'sua.subject_id = s.id AND sua.is_available = 1', 'inner')
            ->where('sua.unit_id', $unitId)
            ->where('s.is_active', 1)
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();
    }

    public function objectivesForUnit(int $unitId): array
    {
        return $this->db->table('learning_objectives_tp lo')
            ->select('lo.id, lo.code, lo.description')
            ->join('subject_unit_availability sua', 'sua.subject_id = lo.subject_id AND sua.is_available = 1', 'inner')
            ->where('sua.unit_id', $unitId)
            ->orderBy('lo.code', 'ASC')
            ->get()->getResultArray();
    }

    public function teachersForUnit(int $unitId): array
    {
        return $this->db->table('teachers t')
            ->select('t.id, t.full_name, t.teacher_initial')
            ->join('teacher_unit_assignments tua', 'tua.teacher_id = t.id')
            ->where('tua.unit_id', $unitId)
            ->where('tua.status', 'ACTIVE')
            ->where('t.is_active', 1)
            ->groupBy('t.id')
            ->orderBy('t.full_name', 'ASC')
            ->get()->getResultArray();
    }

    public function classroomsForUnit(int $unitId): array
    {
        return $this->db->table('classrooms')
            ->where('unit_id', $unitId)
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();
    }

    public function studentsForUnit(int $unitId, ?int $classroomId = null): array
    {
        $builder = $this->db->table('elective_students s')
            ->select('s.id, s.full_name, s.student_number, c.name as classroom_name')
            ->join('classrooms c', 'c.id = s.classroom_id', 'left')
            ->where('s.unit_id', $unitId)
            ->where('s.is_active', 1)
            ->orderBy('s.full_name', 'ASC');
        if ($classroomId !== null) {
            $builder->where('s.classroom_id', $classroomId);
        }

        return $builder->get()->getResultArray();
    }

    public function kspVersions(): array
    {
        return $this->db->table('ksp_versions')
            ->orderBy('created_at', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Standard behavioral descriptors for Graduate Profile Dimensions across 4 levels.
     */
    public function dimensionDescriptorRubric(): array
    {
        return [
            'BERIMAN_BERTAKWA' => [
                'name'        => 'Beriman, Bertakwa kepada Tuhan YME, & Berakhlak Mulia',
                'EMERGING'    => 'Mulai memahami nilai moral dan spiritual dasar dalam kegiatan bersama dengan bimbingan guru.',
                'DEVELOPING'  => 'Mampu menerapkan etika, integritas, dan rasa syukur secara konsisten dalam kelompok.',
                'PROFICIENT'  => 'Mampu menginternalisasi nilai moral, menghargai perbedaan, dan menjadi teladan etika dalam aksi nyata.',
                'EXEMPLARY'   => 'Menjadi penggerak integritas, menginspirasi lingkungan sekitar, dan memimpin inisiatif kebajikan secara mandiri.',
            ],
            'KEBHINEKAAN_GLOBAL' => [
                'name'        => 'Berkebhinekaan Global',
                'EMERGING'    => 'Mengenal keragaman budaya dan identitas diri namun masih membutuhkan arahan dalam berinteraksi.',
                'DEVELOPING'  => 'Menghargai keragaman budaya dan mampu berkomunikasi dengan teman dari latar belakang berbeda.',
                'PROFICIENT'  => 'Mampu beradaptasi, mempromosikan toleransi, dan berkolaborasi dalam konteks multikultural secara aktif.',
                'EXEMPLARY'   => 'Memimpin dialog lintas budaya, merespons isu global secara empati, dan merawat persatuan dalam keberagaman.',
            ],
            'GOTONG_ROYONG' => [
                'name'        => 'Gotong Royong / Kolaborasi',
                'EMERGING'    => 'Bersedia terlibat dalam kerja kelompok saat diminta, masih memerlukan dorongan untuk berbagi tugas.',
                'DEVELOPING'  => 'Aktif berpartisipasi, membantu rekan sekelompok, dan membagi beban kerja secara adil.',
                'PROFICIENT'  => 'Membangun sinergi tim yang harmonis, tanggap terhadap kebutuhan kelompok, dan menyelesaikan target bersama.',
                'EXEMPLARY'   => 'Mengorkestrasi kerja sama tim, memediasi perbedaan, dan menggerakkan komunitas mencapai tujuan bersama.',
            ],
            'MANDIRI' => [
                'name'        => 'Kemandirian',
                'EMERGING'    => 'Mulai mengenali kekuatan dan kelemahan diri dengan bantuan arahan dan instruksi terstruktur.',
                'DEVELOPING'  => 'Mampu mengatur waktu dan menyelesaikan tugas individu secara bertanggung jawab tanpa pengawasan ketat.',
                'PROFICIENT'  => 'Memiliki regulasi diri yang kuat, proaktif mencari solusi tantangan belajar, dan mengevaluasi kemajuan sendiri.',
                'EXEMPLARY'   => 'Menunjukkan determinasi tinggi, berani mengambil risiko terukur, dan mampu memimpin pembelajaran mandiri.',
            ],
            'BERNALAR_KRITIS' => [
                'name'        => 'Bernalar Kritis',
                'EMERGING'    => 'Mampu mengidentifikasi informasi dasar dan mengajukan pertanyaan sederhana terkait masalah.',
                'DEVELOPING'  => 'Mampu menganalisis data, membedakan fakta dan opini, serta menyusun argumen berdasarkan bukti.',
                'PROFICIENT'  => 'Mengevaluasi berbagai perspektif, mengidentifikasi bias/miskonsepsi, dan menyimpulkan solusi logis teruji.',
                'EXEMPLARY'   => 'Mengembangkan kerangka berpikir kritis yang komprehensif, menguji asumsi kompleks, dan menghasilkan sintesis solusi inovatif.',
            ],
            'KREATIF' => [
                'name'        => 'Kreativitas',
                'EMERGING'    => 'Meniru atau memodifikasi gagasan yang sudah ada dengan panduan guru.',
                'DEVELOPING'  => 'Menghasilkan ide-ide orisinal dan mengeksplorasi variasi pendekatan dalam menyelesaikan proyek.',
                'PROFICIENT'  => 'Menciptakan karya/solusi orisinal yang bernilai guna, estetis, dan adaptif terhadap kebutuhan konteks.',
                'EXEMPLARY'   => 'Menghasilkan inovasi bernilai tinggi yang berdampak luas dan mendobrak batasan konvensional secara konstruktif.',
            ],
        ];
    }

    /**
     * Generate structured narrative report text for a student in a cocurricular program.
     */
    public function generateStudentNarrative(int $programId, int $studentId): string
    {
        $program = $this->db->table('cocurricular_programs')->where('id', $programId)->get()->getRowArray();
        if (! $program) {
            return '';
        }

        $student = $this->db->table('elective_students')->where('id', $studentId)->get()->getRowArray();
        $studentName = $student['full_name'] ?? 'Peserta didik';

        $results = $this->db->table('cocurricular_student_results r')
            ->select('r.*, gpd.name as dimension_name, gpd.code as dimension_code')
            ->join('graduate_profile_dimensions gpd', 'gpd.id = r.dimension_id', 'left')
            ->where('r.program_id', $programId)
            ->where('r.student_id', $studentId)
            ->get()->getResultArray();

        $observations = $this->db->table('cocurricular_observations')
            ->where('program_id', $programId)
            ->where('student_id', $studentId)
            ->get()->getResultArray();

        $evidenceCount = $this->db->table('cocurricular_evidences')
            ->where('program_id', $programId)
            ->where('student_id', $studentId)
            ->countAllResults();

        $exemplary = [];
        $proficient = [];
        $developing = [];
        $emerging   = [];

        foreach ($results as $r) {
            $dim = $r['dimension_name'] ?? 'Karakter';
            $level = strtoupper($r['level'] ?? 'DEVELOPING');
            if ($level === 'EXEMPLARY') {
                $exemplary[] = $dim;
            } elseif ($level === 'PROFICIENT') {
                $proficient[] = $dim;
            } elseif ($level === 'DEVELOPING') {
                $developing[] = $dim;
            } else {
                $emerging[] = $dim;
            }
        }

        $title = $program['title'] ?? 'Program Kokurikuler';
        $theme = $program['theme'] ? ' (Tema: ' . $program['theme'] . ')' : '';

        $sentences = [];
        $sentences[] = "Dalam pelaksanaan program {$title}{$theme}, Ananda {$studentName} berpartisipasi aktif dalam rangkaian kegiatan kolaboratif dan kontekstual.";

        $strengths = array_merge($exemplary, $proficient);
        if ($strengths !== []) {
            $strengthList = implode(', ', array_slice($strengths, 0, 3));
            if ($exemplary !== []) {
                $sentences[] = "Ananda menunjukkan pencapaian Sangat Berkembang (SAB) terutama pada dimensi {$strengthList}, di mana ia memperlihatkan inisiatif tinggi, konsistensi tindakan, dan kontribusi nyata dalam kelompok.";
            } else {
                $sentences[] = "Ananda telah Berkembang Sesuai Harapan (BSH) pada dimensi {$strengthList}, mampu menerapkan keterampilan profil pelajar secara mandiri dan bertanggung jawab.";
            }
        }

        $growth = array_merge($developing, $emerging);
        if ($growth !== []) {
            $growthList = implode(', ', array_slice($growth, 0, 2));
            $sentences[] = "Untuk pengembangan ke depan, Ananda disarankan untuk terus didampingi dalam menguatkan dimensi {$growthList}, khususnya dalam hal keterlibatan diskusi mendalam dan refleksi hasil karya.";
        }

        if ($evidenceCount > 0) {
            $sentences[] = "Capaian ini didukung oleh portofolio {$evidenceCount} bukti karya dan catatan observasi formatif berkelanjutan.";
        }

        return implode(' ', $sentences);
    }

    /**
     * Calculate comprehensive IPOO (Input -> Process -> Output -> Outcome) quality scores.
     */
    public function calculateIpooHealth(int $programId): array
    {
        $evaluations = $this->db->table('cocurricular_evaluations')
            ->where('program_id', $programId)
            ->get()->getResultArray();

        $aspects = ['INPUT', 'PROCESS', 'OUTPUT', 'OUTCOME'];
        $grouped = [];

        foreach ($aspects as $asp) {
            $grouped[$asp] = [
                'aspect'     => $asp,
                'items'      => [],
                'avg_rating' => 0.0,
                'percent'    => 0,
                'count'      => 0,
            ];
        }

        foreach ($evaluations as $ev) {
            $asp = strtoupper($ev['aspect'] ?? 'PROCESS');
            if (isset($grouped[$asp])) {
                $grouped[$asp]['items'][] = $ev;
            }
        }

        $totalAvg = 0.0;
        $activeAspects = 0;

        foreach ($aspects as $asp) {
            $count = count($grouped[$asp]['items']);
            $grouped[$asp]['count'] = $count;
            if ($count > 0) {
                $ratings = array_filter(array_column($grouped[$asp]['items'], 'rating'));
                $avg = $ratings !== [] ? array_sum($ratings) / count($ratings) : 3.5;
                $grouped[$asp]['avg_rating'] = round($avg, 2);
                $grouped[$asp]['percent'] = (int) round(($avg / 5.0) * 100);
                $totalAvg += $avg;
                $activeAspects++;
            } else {
                // Baseline default
                $grouped[$asp]['avg_rating'] = 3.5;
                $grouped[$asp]['percent'] = 70;
            }
        }

        $overallScore = $activeAspects > 0 ? round($totalAvg / $activeAspects, 2) : 3.5;
        $overallPercent = (int) round(($overallScore / 5.0) * 100);

        $status = match (true) {
            $overallPercent >= 85 => ['label' => 'SANGAT SEHAT', 'class' => 'bg-success text-white'],
            $overallPercent >= 70 => ['label' => 'BAIK & SESUAI TARGET', 'class' => 'bg-primary text-white'],
            $overallPercent >= 55 => ['label' => 'CUKUP (PERLU PENYEMPURNAAN)', 'class' => 'bg-warning text-dark'],
            default               => ['label' => 'PERLU INTERVENSI', 'class' => 'bg-danger text-white'],
        };

        return [
            'aspects'         => $grouped,
            'overall_score'   => $overallScore,
            'overall_percent' => $overallPercent,
            'status'          => $status,
            'total_evaluations' => count($evaluations),
        ];
    }
}