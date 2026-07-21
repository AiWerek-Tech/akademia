<?php

namespace App\Services;

class TeacherProfileCompletenessService
{
    /**
     * Calculate profile completeness score and status for a teacher record
     */
    public static function evaluate(array $teacher, array $unitAssignments = [], array $qualifications = []): array
    {
        $totalWeight = 100;
        $score = 0;
        $missing = [];

        // 1. Core Mandatory Fields (40%)
        if (!empty(trim($teacher['full_name'] ?? ''))) {
            $score += 15;
        } else {
            $missing[] = 'Nama Lengkap';
        }

        if (!empty($teacher['employment_status'] ?? '')) {
            $score += 15;
        } else {
            $missing[] = 'Status Kepegawaian';
        }

        if (!empty($teacher['primary_unit_id']) || !empty($unitAssignments)) {
            $score += 10;
        } else {
            $missing[] = 'Penugasan Unit Sekolah';
        }

        // 2. Identity & Identifiers (30%)
        if (!empty(trim($teacher['nip'] ?? '')) || !empty(trim($teacher['nik'] ?? '')) || !empty(trim($teacher['employee_number'] ?? ''))) {
            $score += 15;
        } else {
            $missing[] = 'Identitas Pegawai (NIP/NIK/NIPG)';
        }

        if (!empty($teacher['gender'] ?? '')) {
            $score += 8;
        } else {
            $missing[] = 'Jenis Kelamin';
        }

        if (!empty($teacher['birth_date'] ?? '')) {
            $score += 7;
        } else {
            $missing[] = 'Tanggal Lahir';
        }

        // 3. Contact & Address (15%)
        if (!empty(trim($teacher['phone'] ?? '')) || !empty(trim($teacher['email'] ?? ''))) {
            $score += 10;
        } else {
            $missing[] = 'Kontak (Telepon/Email)';
        }

        if (!empty(trim($teacher['address'] ?? ''))) {
            $score += 5;
        } else {
            $missing[] = 'Alamat Lengkap';
        }

        // 4. Qualifications & Documents (15%)
        if (!empty($qualifications)) {
            $score += 15;
        } else {
            $missing[] = 'Kualifikasi Pendidikan';
        }

        // Determine Status
        $status = 'INCOMPLETE';
        if ($score >= 90) {
            $status = 'COMPLETE';
        } elseif ($score >= 60) {
            $status = 'PARTIAL';
        } elseif ($score >= 30) {
            $status = 'INCOMPLETE';
        }

        if (isset($teacher['profile_status']) && $teacher['profile_status'] === 'VERIFIED') {
            $status = 'VERIFIED';
        } elseif (isset($teacher['profile_status']) && $teacher['profile_status'] === 'NEEDS_REVIEW') {
            $status = 'NEEDS_REVIEW';
        }

        return [
            'score'          => min(100, $score),
            'status'         => $status,
            'missing_fields' => $missing,
        ];
    }
}
