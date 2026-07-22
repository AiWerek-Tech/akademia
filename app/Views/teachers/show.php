<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-1 text-slate-800">
                <?= esc(($teacher['title_prefix'] ? $teacher['title_prefix'] . ' ' : '') . $teacher['full_name'] . ($teacher['degree_suffix'] ? ', ' . $teacher['degree_suffix'] : '')) ?>
            </h4>
            <p class="text-muted fs-7 mb-0">NIP: <?= esc($teacher['nip'] ?? '-') ?> | NIK: <?= esc($teacher['nik'] ?? '-') ?></p>
        </div>
        <div class="d-flex gap-2">
            <?php if (has_permission('teachers.verify') && $teacher['profile_status'] !== 'VERIFIED'): ?>
                <form method="POST" action="<?= base_url('teachers/' . $teacher['uuid'] . '/verify') ?>" data-confirm="Apakah Anda yakin ingin memverifikasi profil guru ini?" data-confirm-title="Verifikasi guru?" data-confirm-button="Verifikasi">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-success btn-sm rounded-3">Verifikasi Profil</button>
                </form>
            <?php endif; ?>
            <?php if (has_permission('teachers.manage')): ?>
                <a href="<?= base_url('teachers/' . $teacher['uuid'] . '/edit') ?>" class="btn btn-outline-primary btn-sm rounded-3">Edit Profil</a>
            <?php endif; ?>
            <a href="<?= base_url('teachers') ?>" class="btn btn-light btn-sm rounded-3">Kembali</a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 text-center p-4">
            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center fw-bold fs-2 mb-3 mx-auto" style="width: 80px; height: 80px;">
                <?= esc(mb_substr($teacher['full_name'], 0, 1)) ?>
            </div>
            <h5 class="fw-bold mb-1"><?= esc($teacher['full_name']) ?></h5>
            <span class="badge bg-secondary bg-opacity-10 text-dark px-3 py-1 rounded-pill mb-3">
                <?= esc($teacher['employment_status']) ?>
            </span>

            <div class="text-start border-top pt-3 mt-2 fs-8">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Kelengkapan:</span>
                    <span class="fw-bold"><?= $completeness['score'] ?>%</span>
                </div>
                <div class="progress mb-2" style="height: 6px;">
                    <div class="progress-bar <?= $completeness['score'] >= 90 ? 'bg-success' : 'bg-warning' ?>" style="width: <?= $completeness['score'] ?>%"></div>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Status Profil:</span>
                    <span class="badge bg-info bg-opacity-10 text-dark"><?= esc($completeness['status']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h6 class="fw-bold mb-0">Informasi Pribadi & Kontak</h6>
            </div>
            <div class="card-body p-4 pt-2 fs-8">
                <div class="row g-3">
                    <div class="col-6"><span class="text-muted d-block">Jenis Kelamin:</span> <strong><?= esc($teacher['gender'] ?? '-') ?></strong></div>
                    <div class="col-6"><span class="text-muted d-block">TTL:</span> <strong><?= esc($teacher['birth_place'] ?? '-') ?>, <?= esc($teacher['birth_date'] ?? '-') ?></strong></div>
                    <div class="col-6"><span class="text-muted d-block">Telepon:</span> <strong><?= esc($teacher['phone'] ?? '-') ?></strong></div>
                    <div class="col-6"><span class="text-muted d-block">Email:</span> <strong><?= esc($teacher['email'] ?? '-') ?></strong></div>
                    <div class="col-12"><span class="text-muted d-block">Alamat:</span> <strong><?= esc($teacher['address'] ?? '-') ?></strong></div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h6 class="fw-bold mb-0">Penugasan Unit Sekolah</h6>
            </div>
            <div class="card-body p-4 pt-2 fs-8">
                <?php if (empty($assignments)): ?>
                    <p class="text-muted">Belum ada penugasan unit registered.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($assignments as $a): ?>
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= esc($a['unit_name']) ?></strong>
                                    <span class="text-muted ms-2">(<?= esc($a['assignment_type']) ?>)</span>
                                </div>
                                <?php if ((int)$a['is_primary'] === 1): ?>
                                    <span class="badge bg-primary px-2 py-1 rounded-pill">Unit Utama</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
