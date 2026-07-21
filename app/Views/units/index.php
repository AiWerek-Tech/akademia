<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-1 text-slate-800 d-flex align-items-center gap-2">
                    <i data-lucide="building" class="text-primary" style="width: 24px; height: 24px;"></i>
                    Unit Sekolah
                </h4>
                <p class="text-muted fs-7 mb-0">Manajemen profil unit sekolah terintegrasi</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <?php foreach ($units as $unit): ?>
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3 d-flex align-items-center justify-content-center">
                                <i data-lucide="building-2" style="width: 28px; height: 28px;"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1"><?= esc($unit['name']) ?></h5>
                                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill fs-8 fw-semibold">
                                    <?= esc($unit['code']) ?>
                                </span>
                            </div>
                        </div>
                        <?php if (has_permission('units.manage')): ?>
                            <a href="<?= base_url('settings/units/' . $unit['uuid'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary rounded-3 d-inline-flex align-items-center gap-1">
                                <i data-lucide="pencil" style="width: 14px; height: 14px;"></i> Edit Profil
                            </a>
                        <?php endif; ?>
                    </div>

                    <hr class="my-3 text-slate-200">

                    <div class="flex-grow-1">
                        <div class="row g-3">
                            <div class="col-6">
                                <span class="text-muted fs-8 d-block text-uppercase fw-semibold">NPSN</span>
                                <span class="fw-medium text-slate-700"><?= esc($unit['npsn'] ?? '-') ?></span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted fs-8 d-block text-uppercase fw-semibold">Waktu Lokal</span>
                                <span class="fw-medium text-slate-700"><?= esc($unit['timezone']) ?></span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted fs-8 d-block text-uppercase fw-semibold">Alamat</span>
                                <span class="fw-medium text-slate-700 fs-7"><?= esc($unit['address'] ?? 'Belum diisi') ?></span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted fs-8 d-block text-uppercase fw-semibold">Telepon</span>
                                <span class="fw-medium text-slate-700"><?= esc($unit['phone'] ?? '-') ?></span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted fs-8 d-block text-uppercase fw-semibold">Email</span>
                                <span class="fw-medium text-slate-700 fs-7 text-truncate d-block"><?= esc($unit['email'] ?? '-') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>document.addEventListener('DOMContentLoaded', function(){ if(typeof lucide!=='undefined') lucide.createIcons(); });</script>
<?= $this->endSection() ?>
