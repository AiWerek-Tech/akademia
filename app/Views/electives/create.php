<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<div class="mb-4">
    <a href="<?= base_url('electives') ?>" class="text-decoration-none">← Kembali</a>
    <h4 class="fw-bold mt-2 mb-1">Buat Periode Pemilihan Mapel Pilihan SMA</h4>
    <p class="text-muted mb-0"><?= esc($unit['name']) ?> (Jenjang SMA) · Konfigurasi regulasi Mata Pelajaran Pilihan Fase F Permendikdasmen No. 13 Tahun 2025.</p>
</div>
<?php if (session('errors')): ?><div class="alert alert-danger"><?= implode('<br>', array_map('esc', session('errors'))) ?></div><?php endif ?>
<?php if ($curricula === []): ?><div class="alert alert-warning rounded-4"><strong>Prasyarat belum terpenuhi.</strong> Belum ada versi kurikulum berstatus disetujui/dikunci yang memiliki struktur pada unit ini. Selesaikan dan sahkan struktur kurikulum sebelum membuat periode pemilihan.</div><?php endif ?>

<form method="post" action="<?= base_url('electives') ?>" class="card border-0 shadow-sm rounded-4">
    <?= csrf_field() ?>
    <input type="hidden" name="selection_type" value="FASE_F_ELECTIVE">
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Nama periode <span class="text-danger">*</span></label>
                <input class="form-control" name="title" required maxlength="150" value="<?= old('title', 'Pemilihan Mata Pelajaran Pilihan Fase F') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Tahun pelajaran <span class="text-danger">*</span></label>
                <select class="form-select" name="academic_year_id" required>
                    <option value="">Pilih tahun pelajaran…</option>
                    <?php foreach ($years as $year): ?>
                        <option value="<?= $year['id'] ?>" <?= old('academic_year_id') == $year['id'] ? 'selected' : '' ?>><?= esc($year['name']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Versi kurikulum <span class="text-danger">*</span></label>
                <select class="form-select" name="curriculum_version_id" required>
                    <option value="">Pilih kurikulum yang telah disetujui/dikunci…</option>
                    <?php foreach ($curricula as $curriculum): ?>
                        <option value="<?= $curriculum['id'] ?>" <?= old('curriculum_version_id') == $curriculum['id'] ? 'selected' : '' ?>><?= esc($curriculum['code'] . ' · ' . $curriculum['name']) ?></option>
                    <?php endforeach ?>
                </select>
                <div class="form-text">Penawaran mapel pilihan mengacu pada struktur kurikulum versi ini.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Kelas asal <span class="text-danger">*</span></label>
                <select class="form-select" name="source_grade" id="sourceGradeSelect" required>
                    <option value="10" <?= old('source_grade', '10') == '10' ? 'selected' : '' ?>>Kelas X (Siswa X saat ini)</option>
                    <option value="11" <?= old('source_grade') == '11' ? 'selected' : '' ?>>Kelas XI (Siswa XI saat ini)</option>
                    <option value="12" <?= old('source_grade') == '12' ? 'selected' : '' ?>>Kelas XII (Siswa XII saat ini)</option>
                </select>
                <div class="form-text">Tingkat kelas siswa saat memilih.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Kelas tujuan <span class="text-danger">*</span></label>
                <select class="form-select" name="target_grade" id="targetGradeSelect" required>
                    <option value="11" <?= old('target_grade', '11') == '11' ? 'selected' : '' ?>>Kelas XI (Pelaksanaan Pelajaran)</option>
                    <option value="12" <?= old('target_grade') == '12' ? 'selected' : '' ?>>Kelas XII (Pelaksanaan Pelajaran)</option>
                    <option value="10" <?= old('target_grade') == '10' ? 'selected' : '' ?>>Kelas X (Pelaksanaan Pelajaran)</option>
                </select>
                <div class="form-text">Tingkat kelas saat mapel dipelajari.</div>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Mulai memilih <span class="text-danger">*</span></label>
                <input type="datetime-local" class="form-control" name="selection_start_at" required value="<?= old('selection_start_at') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Tutup pemilihan <span class="text-danger">*</span></label>
                <input type="datetime-local" class="form-control" name="selection_end_at" required value="<?= old('selection_end_at') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Cadangan maks.</label>
                <input type="number" min="0" max="5" class="form-control" name="max_backup_choices" value="<?= old('max_backup_choices', 2) ?>">
                <div class="form-text">Batas pilihan cadangan (0 - 5 mapel).</div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Batas perubahan</label>
                <input type="date" class="form-control" name="change_deadline" value="<?= old('change_deadline') ?>">
            </div>
            <div class="col-md-12">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="allow_changes" value="1" id="allowChanges" <?= old('allow_changes', '1') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="allowChanges">Izinkan pengajuan perubahan pilihan dengan penilaian ulang sekolah (opsional)</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Catatan kebijakan</label>
                <textarea class="form-control" name="notes" rows="3" placeholder="Catatan internal sekolah..."><?= old('notes') ?></textarea>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white border-0 p-4 pt-0 text-end">
        <button class="btn btn-primary px-4" <?= $curricula === [] ? 'disabled' : '' ?>>Simpan draf periode</button>
    </div>
</form>

<script>
document.getElementById('sourceGradeSelect')?.addEventListener('change', function() {
    const val = parseInt(this.value);
    const targetSelect = document.getElementById('targetGradeSelect');
    if (val === 10) {
        targetSelect.value = 11;
    } else if (val === 11) {
        targetSelect.value = 11;
    } else if (val === 12) {
        targetSelect.value = 12;
    }
});
</script>
<?= $this->endSection() ?>
