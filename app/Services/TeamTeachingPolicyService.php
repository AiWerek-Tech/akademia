<?php

namespace App\Services;

use Config\Scheduling;

class TeamTeachingPolicyService
{
    public function isEnabled(): bool
    {
        return config(Scheduling::class)->teamTeachingEnabled;
    }

    public function assertAssignmentsSupported(array $assignments): void
    {
        if ($this->isEnabled()) return;

        foreach ($assignments as $assignment) {
            $isTeam = ! empty($assignment['team_group_uuid'])
                || ! empty($assignment['teaching_assignment_group_id'])
                || (int) ($assignment['is_primary_teacher'] ?? 1) !== 1;
            if ($isTeam) {
                throw new \RuntimeException('Penjadwalan team teaching sedang OFF. Data tim ditolak agar anggota guru tidak hilang saat sinkronisasi.');
            }
        }
    }

    public function assertRequirementSupported(array $requirement): void
    {
        if ($this->isEnabled()) return;

        if (! empty($requirement['is_team_teaching'])
            || ! empty($requirement['teaching_assignment_group_id'])
            || ! empty($requirement['second_teacher_id'])) {
            throw new \RuntimeException('Requirement team teaching ditolak karena fitur penjadwalan team teaching sedang OFF.');
        }
    }

    public function assertSecondTeacherSupported(?int $secondTeacherId): void
    {
        if (! $this->isEnabled() && $secondTeacherId !== null && $secondTeacherId > 0) {
            throw new \RuntimeException('Guru kedua tidak dapat disimpan saat fitur penjadwalan team teaching OFF.');
        }
    }
}
