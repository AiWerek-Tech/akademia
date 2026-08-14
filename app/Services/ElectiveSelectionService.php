<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use RuntimeException;

class ElectiveSelectionService
{
    /** @var BaseConnection|null */
    private $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?: Database::connect();
    }

    public function save(
        int $periodId,
        int $studentId,
        array $primaryIds,
        array $backupIds,
        array $profile,
        int $actorId,
        bool $submit
    ): int {
        $period = $this->db->table('elective_periods ep')
            ->select('ep.*, su.timezone AS unit_timezone')
            ->join('school_units su', 'su.id = ep.unit_id')
            ->where('ep.id', $periodId)->get()->getRowArray();
        $student = $this->db->table('elective_students')->where('id', $studentId)->where('is_active', 1)->get()->getRowArray();
        if (! $period || ! $student || (int) $period['unit_id'] !== (int) $student['unit_id']
            || (int) $period['academic_year_id'] !== (int) $student['academic_year_id']) {
            throw new RuntimeException('Peserta tidak termasuk dalam cakupan periode pemilihan.');
        }
        if ((int) $student['current_grade'] !== (int) $period['source_grade']) {
            throw new RuntimeException('Tingkat kelas peserta tidak sesuai dengan sasaran periode.');
        }
        if (! in_array($period['status'], ['PUBLISHED', 'SELECTION_OPEN'], true)) {
            throw new RuntimeException('Periode pemilihan belum dibuka.');
        }
        $now = $this->localNow($period);
        $isManager = (int) $actorId !== (int) ($student['user_id'] ?? 0)
            || (function_exists('is_super_admin') && is_super_admin())
            || (function_exists('has_permission') && has_permission('electives.selection.manage'))
            || (function_exists('has_permission') && has_permission('class_electives.manage'));

        if (! $isManager && $submit && ! empty($period['selection_start_at']) && ! empty($period['selection_end_at'])) {
            if ($now < $period['selection_start_at'] || $now > $period['selection_end_at']) {
                throw new RuntimeException('Pengiriman pilihan berada di luar periode yang ditetapkan.');
            }
        }

        $availableRows = $this->db->table('elective_offerings')
            ->select('id, weekly_hours')->where('elective_period_id', $periodId)->where('is_open', 1)->get()->getResultArray();
        $availableIds = array_map('intval', array_column($availableRows, 'id'));
        $weeklyHours = array_column($availableRows, 'weekly_hours', 'id');
        $errors = (new ElectiveComplianceService())->validateSelection(
            $primaryIds,
            $backupIds,
            $period,
            $availableIds,
            $submit,
            $weeklyHours
        );
        if ($errors !== []) {
            throw new RuntimeException(implode(' ', $errors));
        }

        $existing = $this->db->table('student_elective_submissions')
            ->where('elective_period_id', $periodId)->where('student_id', $studentId)->get()->getRowArray();
        if ($existing && ! in_array($existing['status'], ['DRAFT', 'NEEDS_REVISION', 'SUBMITTED'], true)) {
            throw new RuntimeException('Pilihan yang telah dikirim tidak dapat diubah tanpa alur perubahan resmi.');
        }

        $this->db->transException(true)->transStart();
        $submissionData = [
            'career_plan' => trim((string) ($profile['career_plan'] ?? '')) ?: null,
            'intended_major' => trim((string) ($profile['intended_major'] ?? '')) ?: null,
            'selection_reason' => trim((string) ($profile['selection_reason'] ?? '')) ?: null,
            'status' => $submit ? 'SUBMITTED' : ($existing ? $existing['status'] : 'DRAFT'),
            'submitted_at' => $submit ? $now : ($existing['submitted_at'] ?? null),
            'updated_by' => $actorId,
            'updated_at' => $now,
        ];
        if ($existing) {
            $submissionId = (int) $existing['id'];
            $submissionData['revision_number'] = (int) $existing['revision_number'] + 1;
            $this->db->table('student_elective_submissions')->where('id', $submissionId)->update($submissionData);
            $this->db->table('student_elective_choices')->where('submission_id', $submissionId)->delete();
        } else {
            $submissionData += [
                'uuid' => UuidService::v4(),
                'elective_period_id' => $periodId,
                'student_id' => $studentId,
                'revision_number' => 1,
                'created_by' => $actorId,
                'created_at' => $now,
            ];
            $this->db->table('student_elective_submissions')->insert($submissionData);
            $submissionId = (int) $this->db->insertID();
        }

        $this->insertChoices($submissionId, 'PRIMARY', $primaryIds, $now);
        $this->insertChoices($submissionId, 'BACKUP', $backupIds, $now);
        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            throw new RuntimeException('Pilihan gagal disimpan secara lengkap. Silakan coba kembali.');
        }

        AuditService::log(
            'electives',
            $submit ? 'submit_selection' : 'save_selection_draft',
            'StudentElectiveSubmission',
            $submissionId,
            $existing ?: null,
            ['student_id' => $studentId, 'primary' => array_values($primaryIds), 'backup' => array_values($backupIds)],
            $submit ? 'Pilihan siswa dikirim' : 'Draf pilihan siswa disimpan'
        );
        return $submissionId;
    }

    public function review(int $submissionId, string $reviewType, string $decision, ?string $notes, int $actorId): void
    {
        $reviewType = strtoupper($reviewType);
        $decision = strtoupper($decision);
        if (! in_array($reviewType, ['BK', 'CURRICULUM'], true)
            || ! in_array($decision, ['APPROVED', 'NEEDS_REVISION', 'REJECTED'], true)) {
            throw new RuntimeException('Keputusan review tidak valid.');
        }
        $submission = $this->db->table('student_elective_submissions')->where('id', $submissionId)->get()->getRowArray();
        if (! $submission || ! in_array($submission['status'], ['SUBMITTED', 'WAITING_CURRICULUM', 'NEEDS_REVISION'], true)) {
            throw new RuntimeException('Submission tidak berada pada tahap review yang valid.');
        }
        if ($reviewType === 'CURRICULUM') {
            $bk = $this->db->table('student_elective_reviews')
                ->where('submission_id', $submissionId)->where('review_type', 'BK')
                ->where('status', 'APPROVED')->get()->getRowArray();
            if (! $bk) {
                throw new RuntimeException('Review kurikulum hanya dapat dilakukan setelah persetujuan BK.');
            }
        }

        $periodForReview = $this->db->table('elective_periods ep')
            ->select('ep.*, su.timezone AS unit_timezone')
            ->join('school_units su', 'su.id = ep.unit_id')
            ->where('ep.id', $submission['elective_period_id'])->get()->getRowArray();
        $now = $this->localNow($periodForReview ?: []);
        $existing = $this->db->table('student_elective_reviews')
            ->where('submission_id', $submissionId)->where('review_type', $reviewType)->get()->getRowArray();
        $review = [
            'status' => $decision, 'notes' => trim((string) $notes) ?: null,
            'reviewed_by' => $actorId, 'reviewed_at' => $now, 'updated_at' => $now,
        ];
        if ($existing) {
            $this->db->table('student_elective_reviews')->where('id', $existing['id'])->update($review);
        } else {
            $this->db->table('student_elective_reviews')->insert($review + [
                'submission_id' => $submissionId, 'review_type' => $reviewType, 'created_at' => $now,
            ]);
        }

        $newStatus = $decision === 'APPROVED'
            ? ($reviewType === 'BK' ? 'WAITING_CURRICULUM' : 'APPROVED')
            : $decision;
        $updates = [
            'status' => $newStatus, 'updated_by' => $actorId, 'updated_at' => $now,
            'revision_number' => (int) $submission['revision_number'] + 1,
        ];
        if ($reviewType === 'BK') {
            $updates['bk_reviewed_at'] = $now;
        } elseif ($decision === 'APPROVED') {
            $updates['curriculum_approved_at'] = $now;
        }
        $this->db->table('student_elective_submissions')->where('id', $submissionId)->update($updates);
        AuditService::log('electives', 'review_' . strtolower($reviewType), 'StudentElectiveSubmission', $submissionId, null, [
            'review_type' => $reviewType, 'decision' => $decision,
        ], 'Review pilihan siswa');
    }

    public function requestChange(int $submissionId, array $primaryIds, array $backupIds, string $reason, int $actorId): int
    {
        $submission = $this->db->table('student_elective_submissions ses')
            ->select('ses.*, ep.allow_changes, ep.change_deadline, ep.target_grade, ep.id AS period_id')
            ->join('elective_periods ep', 'ep.id = ses.elective_period_id')
            ->where('ses.id', $submissionId)->get()->getRowArray();
        if (! $submission || ! in_array($submission['status'], ['APPROVED', 'FINALIZED', 'CHANGED'], true)) {
            throw new RuntimeException('Perubahan hanya dapat diajukan untuk pilihan yang telah disetujui.');
        }
        if ((int) $submission['target_grade'] === 12) {
            throw new RuntimeException('Pilihan kelas XII bersifat tetap dan tidak dapat diajukan perubahan.');
        }
        if (! (int) $submission['allow_changes'] || empty($submission['change_deadline']) || $this->localDate((int) $submission['period_id']) > $submission['change_deadline']) {
            throw new RuntimeException('Batas pengajuan perubahan telah berakhir.');
        }
        if (trim($reason) === '') {
            throw new RuntimeException('Alasan perubahan wajib dijelaskan untuk penilaian ulang sekolah.');
        }
        $period = $this->db->table('elective_periods ep')
            ->select('ep.*, su.timezone AS unit_timezone')
            ->join('school_units su', 'su.id = ep.unit_id')
            ->where('ep.id', $submission['period_id'])->get()->getRowArray();
        $available = $this->db->table('elective_offerings')->select('id, weekly_hours')
            ->where('elective_period_id', $submission['period_id'])->where('is_open', 1)->get()->getResultArray();
        $errors = (new ElectiveComplianceService())->validateSelection(
            $primaryIds,
            $backupIds,
            $period,
            array_column($available, 'id'),
            true,
            array_column($available, 'weekly_hours', 'id')
        );
        if ($errors !== []) {
            throw new RuntimeException(implode(' ', $errors));
        }
        $pending = $this->db->table('student_elective_change_requests')
            ->where('submission_id', $submissionId)->where('status', 'PENDING')->countAllResults();
        if ($pending > 0) {
            throw new RuntimeException('Masih ada pengajuan perubahan yang menunggu review.');
        }
        $now = $this->localNow($period);
        $this->db->table('student_elective_change_requests')->insert([
            'submission_id' => $submissionId,
            'reason' => trim($reason),
            'proposed_choices_json' => json_encode([
                'primary' => array_values(array_map('intval', $primaryIds)),
                'backup' => array_values(array_map('intval', $backupIds)),
            ], JSON_THROW_ON_ERROR),
            'status' => 'PENDING',
            'requested_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $id = (int) $this->db->insertID();
        $this->db->table('student_elective_submissions')->where('id', $submissionId)->update([
            'status' => 'CHANGE_REQUESTED', 'updated_by' => $actorId, 'updated_at' => $now,
        ]);
        return $id;
    }

    public function reviewChange(int $requestId, string $decision, ?string $notes, int $actorId): void
    {
        $decision = strtoupper($decision);
        if (! in_array($decision, ['APPROVED', 'REJECTED'], true)) {
            throw new RuntimeException('Keputusan perubahan tidak valid.');
        }
        $request = $this->db->table('student_elective_change_requests cr')
            ->select('cr.*, ses.elective_period_id, ses.revision_number')
            ->join('student_elective_submissions ses', 'ses.id = cr.submission_id')
            ->where('cr.id', $requestId)->where('cr.status', 'PENDING')->get()->getRowArray();
        if (! $request) {
            throw new RuntimeException('Pengajuan perubahan tidak ditemukan atau sudah diproses.');
        }
        $period = $this->db->table('elective_periods ep')
            ->select('ep.*, su.timezone AS unit_timezone')
            ->join('school_units su', 'su.id = ep.unit_id')
            ->where('ep.id', $request['elective_period_id'])->get()->getRowArray() ?: [];
        $now = $this->localNow($period);
        $this->db->transException(true)->transStart();
        if ($decision === 'APPROVED') {
            $choices = json_decode($request['proposed_choices_json'], true, 512, JSON_THROW_ON_ERROR);
            $available = $this->db->table('elective_offerings')->select('id, weekly_hours')
                ->where('elective_period_id', $request['elective_period_id'])->where('is_open', 1)->get()->getResultArray();
            $errors = (new ElectiveComplianceService())->validateSelection(
                $choices['primary'] ?? [],
                $choices['backup'] ?? [],
                $period,
                array_column($available, 'id'),
                true,
                array_column($available, 'weekly_hours', 'id')
            );
            if ($errors !== []) {
                throw new RuntimeException(implode(' ', $errors));
            }
            $this->db->table('student_elective_choices')->where('submission_id', $request['submission_id'])->delete();
            $this->insertChoices((int) $request['submission_id'], 'PRIMARY', $choices['primary'], $now);
            $this->insertChoices((int) $request['submission_id'], 'BACKUP', $choices['backup'] ?? [], $now);
        }
        $this->db->table('student_elective_change_requests')->where('id', $requestId)->update([
            'status' => $decision, 'reviewed_by' => $actorId,
            'review_notes' => trim((string) $notes) ?: null, 'reviewed_at' => $now, 'updated_at' => $now,
        ]);
        $this->db->table('student_elective_submissions')->where('id', $request['submission_id'])->update([
            'status' => $decision === 'APPROVED' ? 'CHANGED' : 'APPROVED',
            'updated_by' => $actorId, 'updated_at' => $now,
            'revision_number' => (int) $request['revision_number'] + 1,
        ]);
        $this->db->transComplete();
        AuditService::log('electives', 'review_change', 'StudentElectiveChangeRequest', $requestId, null, [
            'decision' => $decision, 'submission_id' => $request['submission_id'],
        ], 'Penilaian ulang perubahan pilihan');
    }

    private function insertChoices(int $submissionId, string $type, array $ids, string $now): void
    {
        foreach (array_values($ids) as $index => $offeringId) {
            $this->db->table('student_elective_choices')->insert([
                'submission_id' => $submissionId,
                'offering_id' => (int) $offeringId,
                'choice_type' => $type,
                'priority_order' => $index + 1,
                'allocation_status' => 'PENDING',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function localNow(array $period): string
    {
        $timezone = (string) ($period['unit_timezone'] ?? config('App')->appTimezone ?? 'UTC');
        try {
            return (new \DateTimeImmutable('now', new \DateTimeZone($timezone)))->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return date('Y-m-d H:i:s');
        }
    }

    private function localDate(int $periodId): string
    {
        $period = $this->db->table('elective_periods ep')
            ->select('su.timezone AS unit_timezone')
            ->join('school_units su', 'su.id = ep.unit_id')
            ->where('ep.id', $periodId)->get()->getRowArray();
        return substr($this->localNow($period ?: []), 0, 10);
    }
}
