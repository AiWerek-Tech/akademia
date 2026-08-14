<?php

namespace App\Services;

use App\Exceptions\AuthorizationException;
use Config\Database;
use RuntimeException;

/**
 * Central authorization boundary for data that belongs to a school unit.
 *
 * Permissions answer what a user may do; this service answers on which unit
 * the operation may be performed. Request values must never be trusted as a
 * unit scope without passing through this service.
 */
class UnitScopeService
{
    public static function accessibleUnitIds(?int $userId = null): array
    {
        $userId ??= (int) session()->get('user_id');
        if ($userId <= 0) {
            return [];
        }

        if (function_exists('is_super_admin') && is_super_admin($userId)) {
            $rows = Database::connect()->table('school_units')
                ->select('id as unit_id')
                ->where('is_active', 1)
                ->where('deleted_at IS NULL')
                ->get()
                ->getResultArray();

            return array_values(array_unique(array_map('intval', array_column($rows, 'unit_id'))));
        }

        $rows = Database::connect()->table('user_unit_access uua')
            ->select('uua.unit_id')
            ->join('school_units su', 'su.id = uua.unit_id')
            ->where('uua.user_id', $userId)
            ->where('su.is_active', 1)
            ->where('su.deleted_at IS NULL')
            ->get()
            ->getResultArray();

        return array_values(array_unique(array_map('intval', array_column($rows, 'unit_id'))));
    }

    public static function accessibleUnits(?int $userId = null): array
    {
        $ids = self::accessibleUnitIds($userId);
        if ($ids === []) {
            return [];
        }

        return Database::connect()->table('school_units')
            ->whereIn('id', $ids)
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Return the active unit as a complete row after enforcing user scope.
     *
     * Controllers that need unit metadata should use this instead of trusting
     * an ID stored in the request or session.
     */
    public static function getCurrentUnit(): array
    {
        $unitId = self::resolveUnit();
        $unit = Database::connect()->table('school_units')
            ->where('id', $unitId)
            ->where('is_active', 1)
            ->where('deleted_at IS NULL')
            ->get()
            ->getRowArray();

        if (! $unit) {
            throw new RuntimeException('Unit sekolah aktif tidak ditemukan.');
        }

        return $unit;
    }

    public static function resolveUnit($requestedUnitId = null): int
    {
        $unitId = (int) ($requestedUnitId ?: session()->get('active_unit_id'));
        self::assertUnit($unitId);

        return $unitId;
    }

    public static function assertUnit(int $unitId): void
    {
        if ($unitId <= 0 || !in_array($unitId, self::accessibleUnitIds(), true)) {
            throw new AuthorizationException('Anda tidak memiliki akses ke unit sekolah yang diminta.');
        }
    }

    public static function assertUnits(array $unitIds): array
    {
        $normalized = array_values(array_unique(array_filter(array_map('intval', $unitIds))));
        if ($normalized === []) {
            throw new AuthorizationException('Minimal satu unit sekolah yang dapat diakses wajib dipilih.');
        }

        $allowed = self::accessibleUnitIds();
        foreach ($normalized as $unitId) {
            if (!in_array($unitId, $allowed, true)) {
                throw new AuthorizationException('Anda tidak memiliki akses ke salah satu unit sekolah yang dipilih.');
            }
        }

        return $normalized;
    }

    public static function assertTeacher(int $teacherId): void
    {
        $ids = self::accessibleUnitIds();
        if ($ids === []) {
            throw new AuthorizationException('Anda tidak memiliki akses ke data guru tersebut.');
        }

        $db = Database::connect();
        $allowed = $db->table('teachers t')
            ->select('t.id')
            ->join('teacher_unit_assignments tua', 'tua.teacher_id = t.id', 'left')
            ->where('t.id', $teacherId)
            ->groupStart()
                ->whereIn('t.primary_unit_id', $ids)
                ->orWhereIn('tua.unit_id', $ids)
            ->groupEnd()
            ->get()
            ->getRowArray();

        if (!$allowed) {
            throw new AuthorizationException('Anda tidak memiliki akses ke data guru tersebut.');
        }
    }

    public static function assertTeacherInUnit(int $teacherId, int $unitId, ?int $academicPeriodId = null): void
    {
        self::assertUnit($unitId);

        $builder = Database::connect()->table('teachers t')
            ->select('t.id')
            ->join('teacher_unit_assignments tua', 'tua.teacher_id = t.id')
            ->where('t.id', $teacherId)
            ->where('tua.unit_id', $unitId)
            ->where('tua.status', 'ACTIVE')
            ->where('t.is_active', 1)
            ->limit(1);

        if ($academicPeriodId !== null) {
            $builder->groupStart()
                ->where('tua.academic_period_id IS NULL')
                ->orWhere('tua.academic_period_id', $academicPeriodId)
                ->groupEnd();
        }

        $allowed = $builder->get()->getRowArray();

        if (!$allowed) {
            throw new AuthorizationException('Guru tidak aktif atau tidak ditugaskan pada unit sekolah yang dipilih.');
        }
    }

    public static function assertTeacherManage(int $teacherId): void
    {
        $ids = self::accessibleUnitIds();
        $allowed = $ids === [] ? null : Database::connect()->table('teachers')
            ->where('id', $teacherId)
            ->whereIn('primary_unit_id', $ids)
            ->get()
            ->getRowArray();

        if (!$allowed) {
            throw new AuthorizationException('Data guru lintas unit hanya dapat dilihat; perubahan harus dilakukan oleh unit utamanya.');
        }
    }

    public static function assertSubject(int $subjectId): void
    {
        $ids = self::accessibleUnitIds();
        $allowed = $ids === [] ? null : Database::connect()->table('subject_unit_availability')
            ->where('subject_id', $subjectId)
            ->whereIn('unit_id', $ids)
            ->where('is_available', 1)
            ->get()
            ->getRowArray();

        if (!$allowed) {
            throw new AuthorizationException('Anda tidak memiliki akses ke mata pelajaran tersebut.');
        }
    }

    public static function assertSubjectInUnit(int $subjectId, int $unitId): void
    {
        self::assertUnit($unitId);

        $allowed = Database::connect()->table('subjects s')
            ->select('s.id')
            ->join('subject_unit_availability sua', 'sua.subject_id = s.id')
            ->where('s.id', $subjectId)
            ->where('sua.unit_id', $unitId)
            ->where('sua.is_available', 1)
            ->where('s.is_active', 1)
            ->get()
            ->getRowArray();

        if (!$allowed) {
            throw new AuthorizationException('Mata pelajaran tidak aktif atau tidak tersedia pada unit sekolah yang dipilih.');
        }
    }

    public static function assertRoom(int $roomId): void
    {
        $ids = self::accessibleUnitIds();
        $builder = Database::connect()->table('rooms')->where('id', $roomId);
        if ($ids !== []) {
            $builder->groupStart()->where('shared_between_units', 1)->orWhereIn('unit_id', $ids)->groupEnd();
        } else {
            $builder->where('id', 0);
        }

        if (!$builder->get()->getRowArray()) {
            throw new AuthorizationException('Anda tidak memiliki akses ke ruang tersebut.');
        }
    }

    public static function assertClassroom(int $classroomId): void
    {
        $ids = self::accessibleUnitIds();
        $allowed = $ids === [] ? null : Database::connect()->table('classrooms')
            ->where('id', $classroomId)
            ->whereIn('unit_id', $ids)
            ->get()
            ->getRowArray();

        if (!$allowed) {
            throw new AuthorizationException('Anda tidak memiliki akses ke kelas tersebut.');
        }
    }

    public static function assertUser(int $targetUserId): void
    {
        $targetRows = Database::connect()->table('user_unit_access')
            ->select('unit_id')
            ->where('user_id', $targetUserId)
            ->get()
            ->getResultArray();
        $targetIds = array_values(array_unique(array_map('intval', array_column($targetRows, 'unit_id'))));
        $outsideScope = array_diff($targetIds, self::accessibleUnitIds());

        if ($targetIds === [] || $outsideScope !== []) {
            throw new AuthorizationException('Anda tidak memiliki cakupan unit yang cukup untuk mengelola pengguna tersebut.');
        }
    }
}
