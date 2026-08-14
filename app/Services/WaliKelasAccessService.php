<?php

namespace App\Services;

use CodeIgniter\Database\BaseBuilder;
use Config\Database;
use RuntimeException;

/** Central scope boundary for wali kelas operations. */
class WaliKelasAccessService
{
    /**
     * Wali kelas remains class-scoped unless another role grants global
     * elective administration access.
     */
    public static function isElectiveClassScoped(): bool
    {
        if (function_exists('is_super_admin') && is_super_admin()) {
            return false;
        }

        return function_exists('is_wali_kelas')
            && is_wali_kelas()
            && function_exists('has_permission')
            && has_permission('class_electives.manage')
            && !has_permission('electives.view')
            && !has_permission('electives.manage')
            && !has_permission('electives.participants.manage');
    }

    public static function classroomId(?int $userId = null): ?int
    {
        $userId ??= (int) session()->get('user_id');
        if ($userId <= 0) return null;

        $db = Database::connect();
        $user = $db->table('users')->where('id', $userId)->where('is_active', 1)->get()->getRowArray();
        if (!$user) return null;

        if (!empty($user['classroom_id'])) {
            return (int) $user['classroom_id'];
        }

        // Backward-compatible fallback for accounts linked only to a teacher.
        if (!empty($user['teacher_id'])) {
            $row = $db->table('classrooms')
                ->select('id')
                ->where('homeroom_teacher_id', (int) $user['teacher_id'])
                ->where('is_active', 1)
                ->orderBy('id', 'ASC')->get()->getRowArray();
            return $row ? (int) $row['id'] : null;
        }
        return null;
    }

    /** Resolve the authoritative scope of the assigned homeroom. */
    public static function classroomContext(?int $userId = null): ?array
    {
        $classroomId = self::classroomId($userId);
        if (!$classroomId) {
            return null;
        }

        $row = Database::connect()->table('classrooms c')
            ->select('c.id AS classroom_id, c.unit_id, c.academic_period_id, gl.grade_number, ap.academic_year_id')
            ->join('grade_levels gl', 'gl.id = c.grade_level_id')
            ->join('academic_periods ap', 'ap.id = c.academic_period_id')
            ->where('c.id', $classroomId)
            ->where('c.is_active', 1)
            ->where('c.deleted_at IS NULL')
            ->get()->getRowArray();

        if (!$row) {
            return null;
        }

        return [
            'classroom_id' => (int) $row['classroom_id'],
            'unit_id' => (int) $row['unit_id'],
            'academic_period_id' => (int) $row['academic_period_id'],
            'academic_year_id' => (int) $row['academic_year_id'],
            'grade_number' => (int) $row['grade_number'],
        ];
    }

    /** Apply the homeroom boundary to an elective-period query. */
    public static function scopeElectivePeriods(BaseBuilder $builder, string $periodAlias = 'ep'): BaseBuilder
    {
        if (!self::isElectiveClassScoped()) {
            return $builder;
        }

        $context = self::classroomContext();
        if (!$context) {
            throw new RuntimeException('Akun wali kelas belum ditautkan ke rombel aktif. Hubungi superadmin.');
        }

        return $builder
            ->where($periodAlias . '.unit_id', $context['unit_id'])
            ->where($periodAlias . '.academic_year_id', $context['academic_year_id'])
            ->where($periodAlias . '.source_grade', $context['grade_number']);
    }

    public static function canAccessElectivePeriod(array $period): bool
    {
        if (!self::isElectiveClassScoped()) {
            return true;
        }

        $context = self::classroomContext();
        return $context !== null
            && (int) ($period['unit_id'] ?? 0) === $context['unit_id']
            && (int) ($period['academic_year_id'] ?? 0) === $context['academic_year_id']
            && (int) ($period['source_grade'] ?? 0) === $context['grade_number'];
    }

    public static function assertCanManageStudent(int $studentId, int $periodId): void
    {
        if (function_exists('is_super_admin') && is_super_admin()) return;
        // Global selection reviewers may open any student in the unit. The class
        // permission, however, is deliberately limited to the assigned homeroom.
        if (function_exists('has_permission') && (has_permission('electives.selection.manage') || has_permission('electives.participants.manage'))) return;
        if (!function_exists('is_wali_kelas') || !is_wali_kelas() || !function_exists('has_permission') || !has_permission('class_electives.manage')) {
            throw new RuntimeException('Anda tidak memiliki hak untuk mengelola pilihan siswa.');
        }

        $context = self::classroomContext();
        if (!$context) throw new RuntimeException('Akun wali kelas belum ditautkan ke rombel. Hubungi superadmin.');

        $row = Database::connect()->table('elective_students es')
            ->select('es.id')
            ->join('elective_periods ep', 'ep.unit_id = es.unit_id AND ep.academic_year_id = es.academic_year_id AND ep.source_grade = es.current_grade')
            ->where('es.id', $studentId)->where('es.classroom_id', $context['classroom_id'])
            ->where('ep.unit_id', $context['unit_id'])
            ->where('ep.academic_year_id', $context['academic_year_id'])
            ->where('ep.source_grade', $context['grade_number'])
            ->where('ep.id', $periodId)->where('es.is_active', 1)->get()->getRowArray();
        if (!$row) throw new RuntimeException('Anda hanya dapat mengelola pilihan siswa pada rombel yang ditugaskan.');
    }

    public static function assertClassroomInUnit(int $classroomId, int $unitId): void
    {
        $row = Database::connect()->table('classrooms')
            ->where('id', $classroomId)->where('unit_id', $unitId)->where('is_active', 1)->get()->getRowArray();
        if (!$row) throw new RuntimeException('Rombel tidak termasuk dalam unit/versi jadwal yang dipilih.');
    }
}
