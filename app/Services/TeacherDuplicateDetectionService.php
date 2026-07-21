<?php

namespace App\Services;

use App\Models\TeacherModel;
use App\Models\DuplicateReviewGroupModel;
use App\Models\DuplicateReviewMemberModel;
use Config\Database;

class TeacherDuplicateDetectionService
{
    /**
     * Normalize teacher full name for comparison
     */
    public static function normalizeName(string $name): string
    {
        // Trim, collapse whitespace, lowercase
        $normalized = mb_strtolower(trim($name), 'UTF-8');
        // Replace multiple spaces with a single space
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        return $normalized;
    }

    /**
     * Check if a candidate teacher matches existing teachers and create a review group if duplicate suspected
     */
    public static function scanForDuplicates(array $teacherData, ?int $excludeTeacherId = null): array
    {
        $teacherModel = new TeacherModel();
        $normalizedName = self::normalizeName($teacherData['full_name'] ?? '');

        $query = $teacherModel->where('deleted_at IS NULL');
        if ($excludeTeacherId !== null) {
            $query->where('id !=', $excludeTeacherId);
        }

        $allTeachers = $query->findAll();
        $matches = [];

        foreach ($allTeachers as $candidate) {
            $score = 0;
            $reasons = [];

            // Tier 1: Exact Identifiers
            if (!empty($teacherData['nip']) && !empty($candidate['nip']) && trim($teacherData['nip']) === trim($candidate['nip'])) {
                $score += 95;
                $reasons[] = 'NIP sama persis (' . $candidate['nip'] . ')';
            }

            if (!empty($teacherData['nik']) && !empty($candidate['nik']) && trim($teacherData['nik']) === trim($candidate['nik'])) {
                $score += 95;
                $reasons[] = 'NIK sama persis (' . $candidate['nik'] . ')';
            }

            if (!empty($teacherData['employee_number']) && !empty($candidate['employee_number']) && trim($teacherData['employee_number']) === trim($candidate['employee_number'])) {
                $score += 90;
                $reasons[] = 'Nomor Pegawai sama persis (' . $candidate['employee_number'] . ')';
            }

            if (!empty($teacherData['email']) && !empty($candidate['email']) && mb_strtolower(trim($teacherData['email'])) === mb_strtolower(trim($candidate['email']))) {
                $score += 80;
                $reasons[] = 'Email sama persis (' . $candidate['email'] . ')';
            }

            // Tier 2: Name & Birth Date / Unit Fuzzy Match
            $candidateNormName = self::normalizeName($candidate['full_name'] ?? '');
            if ($normalizedName !== '' && $candidateNormName !== '' && $normalizedName === $candidateNormName) {
                $score += 50;
                $reasons[] = 'Nama lengkap (normalisasi) sama persis';

                if (!empty($teacherData['birth_date']) && !empty($candidate['birth_date']) && $teacherData['birth_date'] === $candidate['birth_date']) {
                    $score += 35;
                    $reasons[] = 'Tanggal lahir sama (' . $candidate['birth_date'] . ')';
                }

                if (!empty($teacherData['primary_unit_id']) && !empty($candidate['primary_unit_id']) && (int)$teacherData['primary_unit_id'] === (int)$candidate['primary_unit_id']) {
                    $score += 10;
                    $reasons[] = 'Unit sekolah utama sama';
                }
            }

            // Phone number signal check (extra signal, never alone)
            if (!empty($teacherData['phone']) && !empty($candidate['phone']) && trim($teacherData['phone']) === trim($candidate['phone'])) {
                $score += 15;
                $reasons[] = 'Nomor telepon sama (' . $candidate['phone'] . ')';
            }

            if ($score >= 60) {
                $matches[] = [
                    'teacher' => $candidate,
                    'score'   => min(100, $score),
                    'reasons' => $reasons,
                ];
            }
        }

        return $matches;
    }

    /**
     * Create a duplicate review group in DB if matches found
     */
    public static function recordDuplicateGroup(array $newTeacherSnapshot, array $matches): ?string
    {
        if (empty($matches)) {
            return null;
        }

        $db = Database::connect();
        $db->transBegin();

        try {
            $highestScore = max(array_column($matches, 'score'));
            $groupModel = new DuplicateReviewGroupModel();
            $memberModel = new DuplicateReviewMemberModel();

            $allReasons = [];
            foreach ($matches as $m) {
                $allReasons = array_merge($allReasons, $m['reasons']);
            }
            $allReasons = array_values(array_unique($allReasons));

            $groupId = $groupModel->insert([
                'entity_type'        => 'TEACHER',
                'status'             => 'OPEN',
                'confidence_score'   => $highestScore,
                'match_reasons_json' => json_encode($allReasons),
                'decision'           => 'NONE',
            ]);

            $group = $groupModel->find($groupId);

            // Record snapshot member for the incoming candidate
            $memberModel->insert([
                'group_id'         => $groupId,
                'source_type'      => 'NEW_INPUT',
                'source_reference' => $newTeacherSnapshot['full_name'] ?? 'Candidate',
                'entity_id'        => $newTeacherSnapshot['id'] ?? null,
                'snapshot_json'    => json_encode($newTeacherSnapshot),
                'created_at'       => date('Y-m-d H:i:s'),
            ]);

            // Record existing candidate matches
            foreach ($matches as $m) {
                $memberModel->insert([
                    'group_id'         => $groupId,
                    'source_type'      => 'EXISTING_TEACHER',
                    'source_reference' => $m['teacher']['full_name'] . ' (ID: ' . $m['teacher']['id'] . ')',
                    'entity_id'        => $m['teacher']['id'],
                    'snapshot_json'    => json_encode($m['teacher']),
                    'created_at'       => date('Y-m-d H:i:s'),
                ]);
            }

            $db->transCommit();
            return $group['uuid'];
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Failed to record duplicate review group: ' . $e->getMessage());
            return null;
        }
    }
}
