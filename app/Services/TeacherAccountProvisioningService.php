<?php

namespace App\Services;

use Config\Database;

/** Creates and reconciles login accounts for every active teacher. */
class TeacherAccountProvisioningService
{
    public static function provisionAll(?int $actorId = null): array
    {
        $db = Database::connect();
        $guruRole = $db->table('roles')->where('code', 'guru')->get()->getRowArray();
        $waliRole = $db->table('roles')->where('code', 'wali_kelas')->get()->getRowArray();
        if (!$guruRole) {
            throw new \RuntimeException('Role Guru belum tersedia. Jalankan seeder role terlebih dahulu.');
        }

        $teachers = $db->table('teachers')
            ->where('is_active', 1)->where('deleted_at IS NULL')
            ->orderBy('full_name', 'ASC')->get()->getResultArray();
        $reservedUsernames = array_map(
            'strtolower',
            array_column($db->table('users')->select('username')->get()->getResultArray(), 'username')
        );
        $credentials = [];
        $reconciled = 0;

        foreach ($teachers as $teacher) {
            $teacherId = (int) $teacher['id'];
            $db->transBegin();
            try {
                $user = $db->table('users')
                    ->where('teacher_id', $teacherId)->where('deleted_at IS NULL')
                    ->orderBy('id', 'ASC')->get()->getRowArray();

                if (!$user && !empty($teacher['email'])) {
                    $user = $db->table('users')
                        ->where('email', trim((string) $teacher['email']))
                        ->where('teacher_id IS NULL')->where('deleted_at IS NULL')
                        ->orderBy('id', 'ASC')->get()->getRowArray();
                    if ($user) {
                        $db->table('users')->where('id', (int) $user['id'])->update(['teacher_id' => $teacherId]);
                        $user['teacher_id'] = $teacherId;
                    }
                }

                $created = false;
                $temporaryPassword = null;
                if (!$user) {
                    $username = self::uniqueUsername((string) $teacher['full_name'], $reservedUsernames);
                    $temporaryPassword = self::temporaryPassword();
                    $email = trim((string) ($teacher['email'] ?? ''));
                    if ($email !== '' && $db->table('users')->where('email', $email)->countAllResults() > 0) {
                        $email = '';
                    }
                    $db->table('users')->insert([
                        'uuid' => UuidService::v4(),
                        'username' => $username,
                        'email' => $email !== '' ? $email : null,
                        'full_name' => $teacher['full_name'],
                        'password_hash' => password_hash($temporaryPassword, PASSWORD_BCRYPT, ['cost' => 12]),
                        'is_active' => 1,
                        'must_change_password' => 1,
                        'must_change_username' => 1,
                        'teacher_id' => $teacherId,
                        'created_by' => $actorId,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    $user = $db->table('users')->where('id', (int) $db->insertID())->get()->getRowArray();
                    $reservedUsernames[] = strtolower($username);
                    $created = true;
                }

                // Accounts that are still on an untouched temporary password must
                // complete both parts of first-login onboarding. Never reset an
                // existing password or force this on an account that has logged in.
                if (!$created
                    && (int)($user['must_change_password'] ?? 0) === 1
                    && (int)($user['must_change_username'] ?? 0) === 0
                    && empty($user['last_login_at'])
                ) {
                    $db->table('users')->where('id', (int) $user['id'])->update(['must_change_username' => 1]);
                    $user['must_change_username'] = 1;
                }

                $unitIds = self::teacherUnitIds($teacher);
                self::syncUnitAccess((int) $user['id'], $unitIds, $actorId);
                self::ensureRole((int) $user['id'], (int) $guruRole['id'], $actorId);

                $homeroom = $db->table('classrooms')
                    ->select('id, unit_id')->where('homeroom_teacher_id', $teacherId)
                    ->where('is_active', 1)->where('deleted_at IS NULL')
                    ->orderBy('id', 'ASC')->get()->getRowArray();
                if ($homeroom) {
                    if ($waliRole) {
                        self::ensureRole((int) $user['id'], (int) $waliRole['id'], $actorId);
                    }
                    $db->table('users')->where('id', (int) $user['id'])->update(['classroom_id' => (int) $homeroom['id']]);
                }

                if ($created) {
                    $unitCodes = $unitIds === [] ? [] : array_column(
                        $db->table('school_units')->select('code')->whereIn('id', $unitIds)->orderBy('code')->get()->getResultArray(),
                        'code'
                    );
                    $credentials[] = [
                        'teacher_id' => $teacherId,
                        'full_name' => $teacher['full_name'],
                        'username' => $user['username'],
                        'temporary_password' => $temporaryPassword,
                        'units' => implode(', ', $unitCodes),
                        'roles' => $homeroom ? 'Guru, Wali Kelas' : 'Guru',
                    ];
                } else {
                    $reconciled++;
                }
                $db->transCommit();
            } catch (\Throwable $e) {
                $db->transRollback();
                throw new \RuntimeException('Gagal memproses guru ' . $teacher['full_name'] . ': ' . $e->getMessage(), 0, $e);
            }
        }

        return ['credentials' => $credentials, 'created' => count($credentials), 'reconciled' => $reconciled];
    }

    public static function uniqueUsername(string $fullName, array $reservedUsernames): string
    {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', mb_strtolower(trim($fullName))) ?: mb_strtolower(trim($fullName));
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? '';
        $parts = array_values(array_filter(explode(' ', trim($normalized))));
        $base = count($parts) > 1 ? $parts[0] . '.' . end($parts) : ($parts[0] ?? 'guru');
        $base = substr($base, 0, 44);
        if (strlen($base) < 4) {
            $base = 'guru.' . $base;
        }
        $candidate = $base;
        $suffix = 2;
        $reserved = array_fill_keys(array_map('strtolower', $reservedUsernames), true);
        while (isset($reserved[strtolower($candidate)])) {
            $candidate = substr($base, 0, 44) . '.' . $suffix++;
        }
        return $candidate;
    }

    /**
     * Numeric-only temporary password: easy to type and intentionally valid
     * only until the mandatory first-login credential change is completed.
     */
    public static function temporaryPassword(int $length = 8): string
    {
        $length = max(6, min(10, $length));
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= (string) random_int($i === 0 ? 1 : 0, 9);
        }
        return $password;
    }

    /**
     * Rotates only untouched onboarding accounts. Accounts that have already
     * logged in or completed onboarding are never changed by this operation.
     */
    public static function refreshPendingCredentials(?int $actorId = null): array
    {
        $db = Database::connect();
        $rows = $db->table('users u')
            ->select('u.id AS user_id, u.username, t.id AS teacher_id, t.full_name')
            ->join('teachers t', 't.id = u.teacher_id')
            ->where('u.is_active', 1)
            ->where('u.must_change_password', 1)
            ->where('u.must_change_username', 1)
            ->where('u.last_login_at IS NULL')
            ->where('u.deleted_at IS NULL')
            ->where('t.is_active', 1)
            ->where('t.deleted_at IS NULL')
            ->orderBy('t.full_name', 'ASC')
            ->get()->getResultArray();

        $credentials = [];
        foreach ($rows as $row) {
            $temporaryPassword = self::temporaryPassword();
            $db->table('users')->where('id', (int) $row['user_id'])->update([
                'password_hash' => password_hash($temporaryPassword, PASSWORD_BCRYPT, ['cost' => 12]),
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $actorId,
            ]);
            $unitCodes = array_column(
                $db->table('user_unit_access ua')->select('su.code')
                    ->join('school_units su', 'su.id = ua.unit_id')
                    ->where('ua.user_id', (int) $row['user_id'])->orderBy('su.code')->get()->getResultArray(),
                'code'
            );
            $roleNames = array_column(
                $db->table('user_roles ur')->select('r.name')
                    ->join('roles r', 'r.id = ur.role_id')
                    ->where('ur.user_id', (int) $row['user_id'])->orderBy('r.name')->get()->getResultArray(),
                'name'
            );
            $credentials[] = [
                'teacher_id' => (int) $row['teacher_id'],
                'full_name' => $row['full_name'],
                'username' => $row['username'],
                'temporary_password' => $temporaryPassword,
                'units' => implode(', ', array_unique($unitCodes)),
                'roles' => implode(', ', array_unique($roleNames)),
            ];
        }

        return $credentials;
    }

    private static function teacherUnitIds(array $teacher): array
    {
        $rows = Database::connect()->table('teacher_unit_assignments')
            ->select('unit_id')->where('teacher_id', (int) $teacher['id'])
            ->where('status', 'ACTIVE')->get()->getResultArray();
        $ids = array_values(array_unique(array_map('intval', array_column($rows, 'unit_id'))));
        if ($ids === [] && !empty($teacher['primary_unit_id'])) {
            $ids[] = (int) $teacher['primary_unit_id'];
        }
        return $ids;
    }

    private static function syncUnitAccess(int $userId, array $unitIds, ?int $actorId): void
    {
        $db = Database::connect();
        $existing = array_map('intval', array_column(
            $db->table('user_unit_access')->select('unit_id')->where('user_id', $userId)->get()->getResultArray(),
            'unit_id'
        ));
        foreach ($unitIds as $index => $unitId) {
            if (!in_array($unitId, $existing, true)) {
                $db->table('user_unit_access')->insert([
                    'user_id' => $userId, 'unit_id' => $unitId, 'access_level' => 'MEMBER',
                    'is_default' => $existing === [] && $index === 0 ? 1 : 0,
                    'created_by' => $actorId, 'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        if ($unitIds !== [] && $db->table('user_unit_access')->where('user_id', $userId)->where('is_default', 1)->countAllResults() === 0) {
            $db->table('user_unit_access')
                ->where('user_id', $userId)
                ->where('unit_id', $unitIds[0])
                ->update(['is_default' => 1]);
        }
    }

    private static function ensureRole(int $userId, int $roleId, ?int $actorId): void
    {
        $db = Database::connect();
        if ($db->table('user_roles')->where('user_id', $userId)->where('role_id', $roleId)->countAllResults() === 0) {
            $db->table('user_roles')->insert([
                'user_id' => $userId, 'role_id' => $roleId, 'unit_id' => null,
                'assigned_by' => $actorId, 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
