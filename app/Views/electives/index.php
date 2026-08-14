<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pemilihan Mata Pelajaran Pilihan Fase F SMA</h4>
        <p class="text-muted mb-0">
            <?= esc($unit['name']) ?> ·
            <?php if (\App\Services\WaliKelasAccessService::isElectiveClassScoped()): ?>
                Periode pemilihan sesuai jenjang kelas binaan Anda
            <?php else: ?>
                Perencanaan penawaran mata pelajaran pilihan Fase F SMA (Kelas X ke XI, XI, dan XII)
            <?php endif ?>
        </p>
    </div>
    <?php if (has_permission('electives.manage')): ?>
        <a class="btn btn-primary rounded-3" href="<?= base_url('electives/create') ?>"><i data-lucide="plus" class="me-1"></i> Periode baru</a>
    <?php endif ?>
</div>

<div class="alert alert-primary border-0 rounded-4">
    <strong>Pengaman regulasi aktif (Permendikdasmen No. 13/2025).</strong> Publikasi memeriksa kepatuhan penawaran mapel pilihan Fase F SMA: minimal 7 mapel pilihan dibuka sekolah dan siswa memilih 4–5 mapel utama (20–25 JP per minggu).
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr class="text-uppercase text-muted fs-8 fw-bold bg-light"><th class="text-center ps-3" style="width: 55px;">No.</th><th>Periode</th><th>Tahun Pelajaran</th><th>Sasaran Tingkat</th><th>Penawaran Mapel</th><th>Status</th><th class="text-end pe-4">Aksi</th></tr></thead>
                <tbody>
                <?php if ($periods === []): ?>
                    <tr><td colspan="7" class="text-center text-muted py-5">
                        <?= \App\Services\WaliKelasAccessService::isElectiveClassScoped()
                            ? 'Belum ada periode pemilihan untuk jenjang kelas binaan Anda pada tahun pelajaran ini.'
                            : 'Belum ada periode pemilihan mata pelajaran pilihan pada unit ini.' ?>
                    </td></tr>
                <?php endif ?>
                <?php foreach ($periods as $idx => $period): ?>
                    <?php $ready = (int) $period['open_offerings'] >= 7; ?>
                    <tr>
                        <td class="text-center fw-semibold text-secondary fs-8 ps-3"><?= $idx + 1 ?></td>
                        <td><strong><?= esc($period['title']) ?></strong><small class="d-block text-muted"><?= esc(date('d M Y H:i', strtotime($period['selection_start_at']))) ?> – <?= esc(date('d M Y H:i', strtotime($period['selection_end_at']))) ?></small></td>
                        <td><?= esc($period['academic_year_name']) ?></td>
                        <td>Kelas <?= esc($period['source_grade']) ?> → <?= esc($period['target_grade']) ?></td>
                        <td><span class="badge <?= $ready ? 'text-bg-success' : 'text-bg-warning' ?>"><?= esc($period['open_offerings']) ?>/7 aktif</span></td>
                        <td><span class="badge text-bg-light border"><?= esc($period['status']) ?></span></td>
                        <td class="text-end pe-4">
                            <div class="d-flex justify-content-end gap-1">
                                <?php if (has_permission('electives.manage')): ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPeriodModal<?= $period['id'] ?>" title="Edit periode"><i data-lucide="pencil" style="width:14px;height:14px;"></i></button>
                                <?php endif ?>
                                <a class="btn btn-sm btn-outline-primary" href="<?= base_url('electives/' . $period['id']) ?>">Kelola</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php foreach ($periods as $period): ?>
<!-- Edit Period Modal -->
<div class="modal fade" id="editPeriodModal<?= $period['id'] ?>" tabindex="-1" aria-labelledby="editPeriodLabel<?= $period['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="<?= base_url('electives/' . $period['id'] . '/update') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="editPeriodLabel<?= $period['id'] ?>"><i data-lucide="pencil" class="me-2" style="width:18px;height:18px;"></i>Edit Periode Pemilihan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Periode <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" name="title" value="<?= esc($period['title']) ?>" required maxlength="150" placeholder="Contoh: Pemilihan Mata Pelajaran Fase F">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Waktu Pembukaan <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control rounded-3" name="selection_start_at" value="<?= date('Y-m-d\TH:i', strtotime($period['selection_start_at'])) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Waktu Penutupan <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control rounded-3" name="selection_end_at" value="<?= date('Y-m-d\TH:i', strtotime($period['selection_end_at'])) ?>" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Batas Perubahan</label>
                            <input type="date" class="form-control rounded-3" name="change_deadline" value="<?= esc($period['change_deadline'] ?? '') ?>">
                            <small class="text-muted">Kosongkan jika tidak ada batas perubahan.</small>
                        </div>
                        <div class="col-md-6 d-flex align-items-end pb-4">
                            <?php if ((int) $period['target_grade'] !== 12): ?>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="allow_changes" value="1" id="allowChanges<?= $period['id'] ?>" <?= !empty($period['allow_changes']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="allowChanges<?= $period['id'] ?>">Izinkan perubahan pilihan</label>
                                </div>
                            <?php else: ?>
                                <span class="text-muted fst-italic fs-8">Perubahan pilihan Kelas XII dikunci regulasi.</span>
                            <?php endif ?>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Catatan</label>
                        <textarea class="form-control rounded-3" name="notes" rows="2" maxlength="500" placeholder="Catatan opsional untuk periode ini..."><?= esc($period['notes'] ?? '') ?></textarea>
                    </div>
                    <div class="p-3 bg-light rounded-3 mt-3">
                        <div class="row text-muted fs-8">
                            <div class="col-sm-4"><strong>Tahun Pelajaran:</strong> <?= esc($period['academic_year_name']) ?></div>
                            <div class="col-sm-4"><strong>Kelas Asal:</strong> <?= esc($period['source_grade']) ?></div>
                            <div class="col-sm-4"><strong>Kelas Tujuan:</strong> <?= esc($period['target_grade']) ?></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3"><i data-lucide="save" class="me-1" style="width:16px;height:16px;"></i>Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach ?>
<?= $this->endSection() ?>
