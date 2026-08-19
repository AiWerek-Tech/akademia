<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-purple-subtle text-purple px-2.5 py-1.5 rounded-pill fw-semibold text-xs text-uppercase tracking-wider">
                    <i data-lucide="settings-2" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Kebijakan Pelaporan
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mt-2 mb-1">Kebijakan Pelaporan</h1>
            <p class="text-muted mb-0">Atur metode perhitungan nilai rapor per periode &amp; mapel. Versi baru akan menonaktifkan versi lama.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Create form -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 py-3 px-4">
                    <h6 class="fw-bold text-gray-900 mb-0"><i data-lucide="file-plus" class="w-4 h-4 me-1 text-primary"></i> Kebijakan Baru</h6>
                </div>
                <div class="card-body px-4 pb-4 pt-0">
                    <form method="POST" action="<?= base_url('reporting-policies') ?>" class="row g-3">
                        <?= csrf_field() ?>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nama Kebijakan</label>
                            <input type="text" name="policy_name" class="form-control" required placeholder="cth: Rapor Sumatif 2026/2027">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mapel</label>
                            <select name="subject_id" class="form-select">
                                <option value="">Semua Mapel</option>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= esc($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Metode Perhitungan</label>
                            <select name="calculation_method" class="form-select">
                                <?php foreach ($methods as $m): ?>
                                    <option value="<?= $m ?>"><?= esc($m) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Konfigurasi (JSON, opsional)</label>
                            <textarea name="config_json" class="form-control" rows="4" placeholder='{"thresholds":{"advanced":0.9,"achieved":0.7}}'></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary shadow-sm w-100"><i data-lucide="save" class="w-4 h-4 me-1"></i> Simpan Versi Baru</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- List -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 py-3 px-4">
                    <h6 class="fw-bold text-gray-900 mb-0"><i data-lucide="history" class="w-4 h-4 me-1 text-primary"></i> Riwayat Kebijakan (Periode Aktif)</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                            <tr>
                                <th class="px-3 py-3">Nama</th>
                                <th class="px-3 py-3">Mapel</th>
                                <th class="px-3 py-3">Metode</th>
                                <th class="px-3 py-3 text-center">Versi</th>
                                <th class="px-3 py-3 text-center">Aktif</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($policies === []): ?>
                                <tr><td colspan="5" class="text-center text-muted py-5">Belum ada kebijakan untuk periode ini.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($policies as $policy): ?>
                                <tr>
                                    <td class="px-3 py-3">
                                        <div class="fw-semibold text-gray-900"><?= esc($policy['policy_name']) ?></div>
                                        <div class="text-xs text-muted"><?= esc($policy['calculation_method']) ?></div>
                                    </td>
                                    <td class="px-3 py-3 text-muted"><?= esc($policy['subject_name'] ?? 'Semua Mapel') ?></td>
                                    <td class="px-3 py-3"><span class="badge bg-light text-dark rounded-pill"><?= esc($policy['calculation_method']) ?></span></td>
                                    <td class="px-3 py-3 text-center"><span class="badge bg-primary-subtle text-primary rounded-pill">v<?= (int) $policy['version'] ?></span></td>
                                    <td class="px-3 py-3 text-center">
                                        <?php if ((int) $policy['is_active'] === 1): ?>
                                            <span class="badge bg-success-subtle text-success rounded-pill"><i data-lucide="check" class="w-3 h-3 me-1 d-inline-block"></i>Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>