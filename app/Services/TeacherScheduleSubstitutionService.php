<?php

namespace App\Services;

use Config\Database;

/**
 * Resolves the physical teaching resource without changing the teacher shown
 * on schedules and printouts. This lets a temporary substitute consume the
 * absent teacher's slots while presentation remains attached to the owner.
 */
class TeacherScheduleSubstitutionService
{
    private $db;
    private array $resourceCache = [];
    private array $sharingCache = [];

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function resolveResourceTeacherId(int $teacherId, int $academicPeriodId, ?string $onDate = null): int
    {
        if ($teacherId <= 0 || ! $this->db->tableExists('teacher_schedule_substitutions')) {
            return $teacherId;
        }

        $date = $onDate ?: date('Y-m-d');
        $key = "{$academicPeriodId}:{$teacherId}:{$date}";
        if (isset($this->resourceCache[$key])) {
            return $this->resourceCache[$key];
        }

        $visited = [];
        $resourceId = $teacherId;
        while ($resourceId > 0 && ! isset($visited[$resourceId])) {
            $visited[$resourceId] = true;
            $row = $this->activeBuilder($academicPeriodId, $date)
                ->where('absent_teacher_id', $resourceId)
                ->orderBy('effective_from', 'DESC')
                ->orderBy('id', 'DESC')
                ->get()->getRowArray();
            if (! $row) {
                break;
            }
            $resourceId = (int) $row['substitute_teacher_id'];
        }

        return $this->resourceCache[$key] = $resourceId;
    }

    /** @return int[] */
    public function teacherIdsSharingResource(int $teacherId, int $academicPeriodId, ?string $onDate = null): array
    {
        $date = $onDate ?: date('Y-m-d');
        $resourceId = $this->resolveResourceTeacherId($teacherId, $academicPeriodId, $date);
        $key = "{$academicPeriodId}:{$resourceId}:{$date}";
        if (isset($this->sharingCache[$key])) {
            return $this->sharingCache[$key];
        }

        $ids = [$resourceId => true];
        if ($this->db->tableExists('teacher_schedule_substitutions')) {
            foreach ($this->activeBuilder($academicPeriodId, $date)->get()->getResultArray() as $row) {
                $absentId = (int) $row['absent_teacher_id'];
                if ($this->resolveResourceTeacherId($absentId, $academicPeriodId, $date) === $resourceId) {
                    $ids[$absentId] = true;
                }
            }
        }

        $result = array_map('intval', array_keys($ids));
        sort($result);
        return $this->sharingCache[$key] = $result;
    }

    private function activeBuilder(int $academicPeriodId, string $date)
    {
        return $this->db->table('teacher_schedule_substitutions')
            ->where('academic_period_id', $academicPeriodId)
            ->where('status', 'ACTIVE')
            ->where('effective_from <=', $date)
            ->where('effective_to >=', $date);
    }
}
