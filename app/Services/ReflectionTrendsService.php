<?php

namespace App\Services;

use Config\Database;

/**
 * ReflectionTrendsService — Aggregates teaching reflection data over time
 * to surface pedagogical patterns, recurring challenges, and growth indicators.
 *
 * Provides:
 *   - Reflection frequency trends per teacher
 *   - Theme/tag clustering (top challenges, strategies used)
 *   - Session quality indicators (completion rate, reflection depth)
 *   - Comparative metrics (this month vs last month)
 */
class ReflectionTrendsService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Build reflection trends for a teacher over a given time period.
     *
     * @param int      $teacherId  The teacher's ID
     * @param int      $periodId   Academic period
     * @param int|null $subjectId  Optional filter by subject
     * @return array Trend data including session stats, reflections, and insights
     */
    public function buildTrends(int $teacherId, int $periodId, ?int $subjectId = null): array
    {
        // Guard: if learning_sessions table doesn't exist, return empty data
        if (!$this->db->tableExists('learning_sessions')) {
            return $this->emptyTrends();
        }

        // 1. Get all learning sessions for this teacher in the period
        $sessionQuery = $this->db->table('learning_sessions ls')
            ->select('ls.id, ls.schedule_entry_id, ls.status, ls.started_at, ls.completed_at, ls.created_at,
                      ls.subject_id, ls.classroom_id');

        // Left-join subject and classroom names if their tables exist
        $hasSubjects = $this->db->tableExists('subjects');
        $hasClassrooms = $this->db->tableExists('classrooms');

        if ($hasSubjects) {
            $sessionQuery->join('subjects s', 's.id = ls.subject_id', 'left');
        }
        if ($hasClassrooms) {
            $sessionQuery->join('classrooms c', 'c.id = ls.classroom_id', 'left');
        }

        // Add name columns after joins
        if ($hasSubjects) {
            $sessionQuery->select('s.name as subject_name');
        }
        if ($hasClassrooms) {
            $sessionQuery->select('c.name as classroom_name');
        }

        $sessionQuery
            ->where('ls.teacher_id', $teacherId)
            ->where('ls.academic_period_id', $periodId);

        if ($subjectId) {
            $sessionQuery->where('ls.subject_id', $subjectId);
        }

        $result = $sessionQuery->orderBy('ls.created_at', 'ASC')->get();
        $sessions = $result ? $result->getResultArray() : [];
        $sessionIds = array_column($sessions, 'id');

        // 2. Get reflections
        $reflections = [];
        if (!empty($sessionIds)) {
            $reflections = $this->db->table('learning_session_reflections')
                ->select('*')
                ->whereIn('session_id', $sessionIds)
                ->orderBy('created_at', 'ASC')
                ->get()->getResultArray();
        }

        // 3. Get observations
        $observations = [];
        if (!empty($sessionIds)) {
            $observations = $this->db->table('learning_session_observations')
                ->select('*')
                ->whereIn('session_id', $sessionIds)
                ->orderBy('created_at', 'ASC')
                ->get()->getResultArray();
        }

        // 4. Build session stats
        $totalSessions     = count($sessions);
        $completedSessions = count(array_filter($sessions, fn($s) => $s['status'] === 'completed'));
        $reflectedSessions = count(array_filter($sessions, fn($s) => $s['status'] === 'reflected'));
        $totalReflected    = $completedSessions + $reflectedSessions;

        // 5. Build weekly frequency chart data
        $weeklyData = $this->buildWeeklyFrequency($sessions);

        // 6. Build subject distribution
        $subjectDistribution = [];
        foreach ($sessions as $session) {
            $subName = $session['subject_name'] ?? 'Tidak Diketahui';
            if (!isset($subjectDistribution[$subName])) {
                $subjectDistribution[$subName] = 0;
            }
            $subjectDistribution[$subName]++;
        }

        // 7. Observation type distribution
        $obsTypes = [];
        foreach ($observations as $obs) {
            $type = $obs['observation_type'] ?? 'note';
            if (!isset($obsTypes[$type])) {
                $obsTypes[$type] = 0;
            }
            $obsTypes[$type]++;
        }

        // 8. Reflection insights — extract common themes
        $reflectionInsights = $this->extractReflectionInsights($reflections);

        // 9. Growth indicators
        $growth = $this->calculateGrowth($sessions, $reflections);

        return [
            'sessionStats' => [
                'total'           => $totalSessions,
                'completed'       => $completedSessions,
                'reflected'       => $reflectedSessions,
                'total_reflected' => $totalReflected,
                'completion_rate' => $totalSessions > 0 ? round($totalReflected / $totalSessions * 100, 1) : 0,
                'reflection_rate' => $totalReflected > 0 ? round($reflectedSessions / $totalReflected * 100, 1) : 0,
            ],
            'weeklyFrequency'     => $weeklyData,
            'subjectDistribution' => $subjectDistribution,
            'observationTypes'    => $obsTypes,
            'reflectionInsights'  => $reflectionInsights,
            'growth'              => $growth,
            'recentReflections'   => array_slice(array_reverse($reflections), 0, 10),
            'observations'        => $observations,
        ];
    }

    /**
     * Build weekly frequency data for chart.
     */
    private function buildWeeklyFrequency(array $sessions): array
    {
        $weeks = [];
        foreach ($sessions as $session) {
            $date = $session['created_at'] ?? null;
            if (!$date) continue;

            $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($date)));
            if (!isset($weeks[$weekStart])) {
                $weeks[$weekStart] = ['sessions' => 0, 'completed' => 0];
            }
            $weeks[$weekStart]['sessions']++;
            if (in_array($session['status'], ['completed', 'reflected'])) {
                $weeks[$weekStart]['completed']++;
            }
        }

        $categories = [];
        $sessionSeries = [];
        $completedSeries = [];

        foreach ($weeks as $weekStart => $data) {
            $categories[] = date('d M', strtotime($weekStart));
            $sessionSeries[] = $data['sessions'];
            $completedSeries[] = $data['completed'];
        }

        return [
            'categories' => $categories,
            'series' => [
                ['name' => 'Sesi Terjadwal', 'data' => $sessionSeries, 'color' => '#6366f1'],
                ['name' => 'Sesi Selesai',   'data' => $completedSeries, 'color' => '#22c55e'],
            ],
        ];
    }

    /**
     * Extract common themes from reflections.
     */
    private function extractReflectionInsights(array $reflections): array
    {
        $achievements = [];
        $challenges = [];
        $followUps = [];

        foreach ($reflections as $ref) {
            if (!empty($ref['what_went_well'])) {
                $achievements[] = $ref['what_went_well'];
            }
            if (!empty($ref['what_to_improve'])) {
                $challenges[] = $ref['what_to_improve'];
            }
            if (!empty($ref['follow_up_plan'])) {
                $followUps[] = $ref['follow_up_plan'];
            }
        }

        return [
            'total_reflections' => count($reflections),
            'achievements'      => count($achievements),
            'challenges'        => count($challenges),
            'follow_ups'        => count($followUps),
            'recent_achievements' => array_slice(array_reverse($achievements), 0, 5),
            'recent_challenges'   => array_slice(array_reverse($challenges), 0, 5),
            'recent_follow_ups'   => array_slice(array_reverse($followUps), 0, 5),
        ];
    }

    /**
     * Calculate growth indicators by comparing first half vs second half.
     */
    private function calculateGrowth(array $sessions, array $reflections): array
    {
        if (count($sessions) < 4) {
            return [
                'has_enough_data' => false,
                'message' => 'Diperlukan minimal 4 sesi untuk analisis pertumbuhan.',
            ];
        }

        $midPoint = intdiv(count($sessions), 2);
        $firstHalf  = array_slice($sessions, 0, $midPoint);
        $secondHalf = array_slice($sessions, $midPoint);

        $firstCompleted  = count(array_filter($firstHalf, fn($s) => in_array($s['status'], ['completed', 'reflected'])));
        $secondCompleted = count(array_filter($secondHalf, fn($s) => in_array($s['status'], ['completed', 'reflected'])));

        $firstRate  = count($firstHalf) > 0 ? $firstCompleted / count($firstHalf) : 0;
        $secondRate = count($secondHalf) > 0 ? $secondCompleted / count($secondHalf) : 0;

        $delta = $secondRate - $firstRate;

        return [
            'has_enough_data' => true,
            'first_half'  => [
                'sessions'        => count($firstHalf),
                'completion_rate' => round($firstRate * 100, 1),
            ],
            'second_half' => [
                'sessions'        => count($secondHalf),
                'completion_rate' => round($secondRate * 100, 1),
            ],
            'delta_pct'  => round($delta * 100, 1),
            'trend'      => $delta > 0.05 ? 'improving' : ($delta < -0.05 ? 'declining' : 'stable'),
            'trend_label' => $delta > 0.05 ? 'Meningkat' : ($delta < -0.05 ? 'Menurun' : 'Stabil'),
            'trend_color' => $delta > 0.05 ? 'success' : ($delta < -0.05 ? 'warning' : 'secondary'),
        ];
    }

    /**
     * Return empty trend data when tables don't exist.
     */
    private function emptyTrends(): array
    {
        return [
            'sessionStats' => [
                'total'           => 0,
                'completed'       => 0,
                'reflected'       => 0,
                'total_reflected' => 0,
                'completion_rate' => 0,
                'reflection_rate' => 0,
            ],
            'weeklyFrequency'     => ['categories' => [], 'series' => []],
            'subjectDistribution' => [],
            'observationTypes'    => [],
            'reflectionInsights'  => [
                'total_reflections'   => 0,
                'achievements'        => 0,
                'challenges'          => 0,
                'follow_ups'          => 0,
                'recent_achievements' => [],
                'recent_challenges'   => [],
                'recent_follow_ups'   => [],
            ],
            'growth'              => [
                'has_enough_data' => false,
                'message'         => 'Belum ada data sesi pembelajaran.',
            ],
            'recentReflections'   => [],
            'observations'        => [],
        ];
    }
}
