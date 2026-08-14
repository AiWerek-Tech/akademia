<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Tugas Tambahan Guru</h1>
            <p class="text-muted small mb-0">Kelola penugasan struktural dan fungsional guru seperti Wali Kelas, Wakasek, Kepala Lab, dll</p>
        </div>
        <?php if (has_permission('duties.manage')): ?>
            <a href="<?= base_url('duties/create') ?>" class="btn btn-primary btn-sm rounded-3">
                <i class="bi bi-plus-lg me-1"></i> Berikan Tugas Baru
            </a>
        <?php endif; ?>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            <?= esc(session()->getFlashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div class="fs-8 text-muted">
                    Total Data: <span class="fw-bold text-dark"><?= count($duties) ?></span> tugas tambahan
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="text-uppercase text-muted fs-8 fw-bold bg-light">
                            <th class="text-center ps-3" style="width: 55px;">No.</th>
                            <th>Guru</th>
                            <th>Tugas Tambahan</th>
                            <th>Judul Override</th>
                            <th>Beban Jam (JP)</th>
                            <th>Unit Scope</th>
                            <th>Versi Penugasan</th>
                            <th>Nomor SK / Ref</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($duties)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">Belum ada tugas tambahan yang diberikan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($duties as $idx => $d): ?>
                                <tr>
                                    <td class="text-center fw-semibold text-secondary fs-8 ps-3"><?= $idx + 1 ?></td>
                                    <td class="fw-bold text-slate-800"><?= esc($d['full_name']) ?></td>
                                    <td><?= esc($d['duty_name']) ?></td>
                                    <td><?= esc($d['title_override'] ?? '-') ?></td>
                                    <td><span class="badge bg-primary bg-opacity-10 text-primary px-2.5 py-1 rounded-pill fs-8"><?= esc($d['workload_hours']) ?> JP</span></td>
                                    <td><?= esc($d['unit_name'] ?? 'Global') ?></td>
                                    <td><span class="badge bg-secondary bg-opacity-10 text-dark px-2.5 py-1 rounded-pill fs-8"><?= esc($d['version_code']) ?></span></td>
                                    <td><?= esc($d['reference_number'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge bg-success bg-opacity-10 text-success px-2.5 py-1 rounded-pill fs-8"><?= esc($d['status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
