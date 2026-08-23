<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3" style="max-width:900px">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="<?= base_url('quality/supervisions') ?>">Supervisi</a></li><li class="breadcrumb-item active">Detail</li></ol></nav>
            <h1 class="h3 fw-bold text-gray-900 mb-1">Catatan Supervisi</h1>
            <p class="text-muted mb-0">
                <?= esc($record['teacher_name'] ?? '') ?> · <?= date('d M Y', strtotime($record['observation_date'])) ?> · <?= $record['observation_type'] ?>
                <?php if ($record['overall_rating']): ?>
                    · <span class="badge bg-primary-subtle text-primary rounded-pill"><?= $record['overall_rating'] ?></span>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('quality/supervision/' . $record['id'] . '/print') ?>" target="_blank" class="btn btn-outline-primary shadow-sm rounded-pill px-3">
                <i data-lucide="printer" class="w-4 h-4 me-1 d-inline-block"></i> Cetak Laporan
            </a>
            <a href="<?= base_url('quality/supervisions') ?>" class="btn btn-outline-secondary shadow-sm rounded-pill px-3">
                <i data-lucide="arrow-left" class="w-4 h-4 me-1 d-inline-block"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-success mb-2">Kekuatan</h6>
                    <div class="p-3 rounded-3 bg-success-subtle mb-3"><p class="mb-0"><?= nl2br(esc($record['strengths'] ?? '—')) ?></p></div>
                    <h6 class="fw-bold text-warning mb-2">Area Pertumbuhan</h6>
                    <div class="p-3 rounded-3 bg-warning-subtle mb-3"><p class="mb-0"><?= nl2br(esc($record['areas_for_growth'] ?? '—')) ?></p></div>
                    <h6 class="fw-bold text-info mb-2">Rekomendasi</h6>
                    <div class="p-3 rounded-3 bg-info-subtle"><p class="mb-0"><?= nl2br(esc($record['recommendations'] ?? '—')) ?></p></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">Info</h6>
                    <table class="table table-borderless table-sm mb-0">
                        <tr><td class="text-muted fw-semibold" style="width:100px">Supervisor</td><td><?= esc($record['supervisor_name'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted fw-semibold">Mapel</td><td><?= esc($record['subject_name'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted fw-semibold">Kelas</td><td><?= esc($record['classroom_name'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted fw-semibold">Status</td><td><span class="badge bg-<?= $record['status'] === 'COMPLETED' ? 'success' : 'secondary' ?>-subtle text-<?= $record['status'] === 'COMPLETED' ? 'success' : 'secondary' ?> rounded-pill"><?= $record['status'] ?></span></td></tr>
                        <tr><td class="text-muted fw-semibold">Follow-up</td><td><?= $record['follow_up_needed'] ? '⚠️ Ya' : 'Tidak' ?></td></tr>
                    </table>
                </div>
            </div>
            <?php if ($record['follow_up_needed'] && $record['follow_up_notes']): ?>
                <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-warning">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-2">Catatan Follow-up</h6>
                        <p class="mb-0 small"><?= nl2br(esc($record['follow_up_notes'])) ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Update Form: Status & Follow-up -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom rounded-top-4">
            <h6 class="mb-0 fw-semibold"><i data-lucide="edit-3" class="w-4 h-4 me-1 d-inline-block"></i> Perbarui Catatan</h6>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="<?= base_url('quality/supervision/' . $record['id'] . '/edit') ?>" id="updateForm">
                <input type="hidden" name="csrf_token_name" value="<?= csrf_hash() ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select rounded-3">
                            <option value="DRAFT" <?= $record['status'] === 'DRAFT' ? 'selected' : '' ?>>DRAFT</option>
                            <option value="COMPLETED" <?= $record['status'] === 'COMPLETED' ? 'selected' : '' ?>>COMPLETED</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Follow-up</label>
                        <select name="follow_up_needed" class="form-select rounded-3">
                            <option value="0" <?= !$record['follow_up_needed'] ? 'selected' : '' ?>>Tidak Perlu</option>
                            <option value="1" <?= $record['follow_up_needed'] ? 'selected' : '' ?>>Perlu Follow-up</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary rounded-pill px-4 w-100">
                            <i data-lucide="save" class="w-4 h-4 me-1 d-inline-block"></i> Simpan Perubahan
                        </button>
                    </div>
                    <div class="col-12" id="followUpNotesGroup">
                        <label class="form-label fw-semibold">Catatan Follow-up</label>
                        <textarea name="follow_up_notes" class="form-control rounded-3" rows="3" placeholder="Isi catatan tindak lanjut..."><?= esc($record['follow_up_notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function(){
    var sel = document.querySelector('[name="follow_up_needed"]');
    var notes = document.getElementById('followUpNotesGroup');
    function toggleNotes(){ notes.style.display = sel.value === '1' ? '' : 'none'; }
    sel.addEventListener('change', toggleNotes);
    toggleNotes();
})();
</script>
<?= $this->endSection() ?>
