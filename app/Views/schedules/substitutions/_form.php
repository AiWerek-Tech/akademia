<?php $row = $row ?? []; ?>
<div class="row g-3">
    <div class="col-12">
        <label class="form-label fw-semibold">Periode akademik</label>
        <select class="form-select" name="academic_period_id" required><?= $periodOptions($periods, (int) ($row['academic_period_id'] ?? 0)) ?></select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Guru cuti/pemilik jadwal</label>
        <select class="form-select" name="absent_teacher_id" required><?= $teacherOptions($teachers, (int) ($row['absent_teacher_id'] ?? 0)) ?></select>
        <div class="form-text">Nama, inisial, dan warna guru ini tetap muncul pada cetak.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Guru pengganti/pelaksana</label>
        <select class="form-select" name="substitute_teacher_id" required><?= $teacherOptions($teachers, (int) ($row['substitute_teacher_id'] ?? 0)) ?></select>
        <div class="form-text">Ketersediaan dan benturan dihitung memakai guru ini.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Tanggal mulai</label>
        <input type="date" class="form-control" name="effective_from" value="<?= esc($row['effective_from'] ?? '') ?>" required>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Tanggal selesai</label>
        <input type="date" class="form-control" name="effective_to" value="<?= esc($row['effective_to'] ?? '') ?>" required>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Status</label>
        <select class="form-select" name="status"><option value="ACTIVE" <?= ($row['status'] ?? 'ACTIVE') === 'ACTIVE' ? 'selected' : '' ?>>Aktif</option><option value="INACTIVE" <?= ($row['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>Nonaktif</option></select>
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Catatan</label>
        <textarea class="form-control" name="notes" rows="3" maxlength="2000" placeholder="Contoh: Cuti selama dua bulan; pembelajaran IPS SMP dilaksanakan guru pengganti."><?= esc($row['notes'] ?? '') ?></textarea>
    </div>
</div>
