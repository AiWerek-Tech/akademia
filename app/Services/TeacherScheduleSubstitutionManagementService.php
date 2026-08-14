<?php

namespace App\Services;

use App\Models\TeacherScheduleSubstitutionModel;
use Config\Database;

class TeacherScheduleSubstitutionManagementService
{
    private $db;
    private TeacherScheduleSubstitutionModel $model;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->model = new TeacherScheduleSubstitutionModel();
    }

    public function save(?int $id, array $input, int $userId): array
    {
        $periodId = (int) ($input['academic_period_id'] ?? 0);
        $absentId = (int) ($input['absent_teacher_id'] ?? 0);
        $substituteId = (int) ($input['substitute_teacher_id'] ?? 0);
        $from = trim((string) ($input['effective_from'] ?? ''));
        $to = trim((string) ($input['effective_to'] ?? ''));
        $status = strtoupper(trim((string) ($input['status'] ?? 'ACTIVE')));
        $notes = trim((string) ($input['notes'] ?? ''));

        if ($periodId <= 0 || $absentId <= 0 || $substituteId <= 0) {
            throw new \InvalidArgumentException('Periode, guru cuti, dan guru pengganti wajib dipilih.');
        }
        if ($absentId === $substituteId) {
            throw new \InvalidArgumentException('Guru pengganti harus berbeda dari guru yang digantikan.');
        }
        if (! in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            throw new \InvalidArgumentException('Status substitusi tidak valid.');
        }
        if (! $this->validDate($from) || ! $this->validDate($to) || $from > $to) {
            throw new \InvalidArgumentException('Rentang tanggal substitusi tidak valid.');
        }
        if (mb_strlen($notes) > 2000) {
            throw new \InvalidArgumentException('Catatan maksimal 2.000 karakter.');
        }

        $period = $this->db->table('academic_periods')->where('id', $periodId)->get()->getRowArray();
        if (! $period) {
            throw new \InvalidArgumentException('Periode akademik tidak ditemukan.');
        }
        if ($from < (string) $period['start_date'] || $to > (string) $period['end_date']) {
            throw new \InvalidArgumentException('Tanggal substitusi harus berada di dalam periode akademik yang dipilih.');
        }
        foreach ([$absentId, $substituteId] as $teacherId) {
            $teacher = $this->db->table('teachers')->where('id', $teacherId)
                ->where('is_active', 1)->where('deleted_at IS NULL')->get()->getRowArray();
            if (! $teacher) {
                throw new \InvalidArgumentException('Guru tidak ditemukan atau tidak aktif.');
            }
        }

        if ($status === 'ACTIVE') {
            $overlap = $this->db->table('teacher_schedule_substitutions')
                ->where('academic_period_id', $periodId)
                ->where('absent_teacher_id', $absentId)
                ->where('status', 'ACTIVE')
                ->where('effective_from <=', $to)
                ->where('effective_to >=', $from);
            if ($id) {
                $overlap->where('id !=', $id);
            }
            if ($overlap->countAllResults() > 0) {
                throw new \InvalidArgumentException('Guru tersebut sudah memiliki substitusi aktif pada rentang tanggal yang beririsan.');
            }
            $this->assertNoCycle($id, $periodId, $absentId, $substituteId, $from, $to);
        }

        $data = [
            'academic_period_id' => $periodId,
            'absent_teacher_id' => $absentId,
            'substitute_teacher_id' => $substituteId,
            'effective_from' => $from,
            'effective_to' => $to,
            'status' => $status,
            'notes' => $notes !== '' ? $notes : null,
            'updated_by' => $userId ?: null,
        ];
        if ($id) {
            $existing = $this->model->find($id);
            if (! $existing) {
                throw new \InvalidArgumentException('Data substitusi tidak ditemukan.');
            }
            if (! $this->model->update($id, $data)) {
                throw new \RuntimeException('Substitusi guru gagal diperbarui.');
            }
            return $this->model->find($id);
        }

        $data['uuid'] = UuidService::v4();
        $data['created_by'] = $userId ?: null;
        if (! $this->model->insert($data)) {
            throw new \RuntimeException('Substitusi guru gagal disimpan.');
        }
        return $this->model->find((int) $this->model->insertID());
    }

    private function assertNoCycle(?int $ignoreId, int $periodId, int $absentId, int $substituteId, string $from, string $to): void
    {
        $builder = $this->db->table('teacher_schedule_substitutions')
            ->where('academic_period_id', $periodId)->where('status', 'ACTIVE')
            ->where('effective_from <=', $to)->where('effective_to >=', $from);
        if ($ignoreId) {
            $builder->where('id !=', $ignoreId);
        }
        $edges = [];
        foreach ($builder->get()->getResultArray() as $row) {
            $edges[(int) $row['absent_teacher_id']] = (int) $row['substitute_teacher_id'];
        }
        $edges[$absentId] = $substituteId;
        $visited = [];
        $cursor = $absentId;
        while (isset($edges[$cursor])) {
            if (isset($visited[$cursor])) {
                throw new \InvalidArgumentException('Rantai substitusi membentuk siklus dan tidak dapat disimpan.');
            }
            $visited[$cursor] = true;
            $cursor = $edges[$cursor];
        }
    }

    private function validDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
