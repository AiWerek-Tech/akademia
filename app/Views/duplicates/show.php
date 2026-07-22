<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-1 text-slate-800">Review Side-by-Side Duplikasi</h4>
            <p class="text-muted fs-7 mb-0">Confidence Score: <strong class="text-danger"><?= esc($group['confidence_score']) ?>%</strong></p>
        </div>
        <a href="<?= base_url('duplicates') ?>" class="btn btn-light btn-sm rounded-3">Kembali</a>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($teacherEntities as $idx => $t): ?>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">Kandidat Guru #<?= $idx + 1 ?> (ID: <?= $t['id'] ?>)</h6>
                    <span class="badge bg-secondary bg-opacity-10 text-dark"><?= esc($t['employment_status']) ?></span>
                </div>
                <div class="card-body p-4 pt-2 fs-8">
                    <div class="mb-3">
                        <span class="text-muted d-block">Nama Lengkap:</span>
                        <strong class="fs-6"><?= esc(($t['title_prefix'] ? $t['title_prefix'] . ' ' : '') . $t['full_name'] . ($t['degree_suffix'] ? ', ' . $t['degree_suffix'] : '')) ?></strong>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><span class="text-muted d-block">NIP:</span> <strong><?= esc($t['nip'] ?? '-') ?></strong></div>
                        <div class="col-6"><span class="text-muted d-block">NIK:</span> <strong><?= esc($t['nik'] ?? '-') ?></strong></div>
                        <div class="col-6"><span class="text-muted d-block">NIPG:</span> <strong><?= esc($t['employee_number'] ?? '-') ?></strong></div>
                        <div class="col-6"><span class="text-muted d-block">TTL:</span> <strong><?= esc($t['birth_place'] ?? '-') ?>, <?= esc($t['birth_date'] ?? '-') ?></strong></div>
                        <div class="col-6"><span class="text-muted d-block">Telepon:</span> <strong><?= esc($t['phone'] ?? '-') ?></strong></div>
                        <div class="col-6"><span class="text-muted d-block">Email:</span> <strong><?= esc($t['email'] ?? '-') ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($group['status'] === 'OPEN' && count($teacherEntities) >= 2): ?>
    <div class="card border-0 shadow-sm rounded-4 mt-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4">
            <h6 class="fw-bold mb-0">Tindakan Resolusi Admin</h6>
        </div>
        <div class="card-body p-4 pt-2">
            <form method="POST" action="<?= base_url('duplicates/' . $group['uuid'] . '/resolve') ?>" class="row g-3">
                <?= csrf_field() ?>
                
                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Pilih Record Guru Utama (Canonical)</label>
                    <select name="canonical_teacher_id" class="form-select rounded-3" required>
                        <?php foreach ($teacherEntities as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= esc($t['full_name']) ?> (ID #<?= $t['id'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Pilih Record Guru Duplikat yang Akan Di-Merge</label>
                    <select name="duplicate_teacher_id" class="form-select rounded-3" required>
                        <?php foreach ($teacherEntities as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= esc($t['full_name']) ?> (ID #<?= $t['id'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label fs-8 fw-bold">Alasan Keputusan</label>
                    <textarea name="reason" class="form-control rounded-3" rows="2" placeholder="Catatan pertimbangan admin..." required></textarea>
                </div>

                <div class="col-12 d-flex gap-2 justify-content-end">
                    <button type="submit" name="decision" value="KEEP_SEPARATE" class="btn btn-outline-secondary rounded-3">Tetapkan Data Terpisah (Keep Separate)</button>
                    <button type="submit" name="decision" value="MERGE" class="btn btn-danger rounded-3" data-confirm="Apakah Anda yakin ingin menggabungkan (merge) dua data guru ini?" data-confirm-title="Gabungkan data guru?" data-confirm-icon="warning" data-confirm-button="Gabungkan">Gabungkan Data Guru (Merge)</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
