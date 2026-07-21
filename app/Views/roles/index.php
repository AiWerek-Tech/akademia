<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div>
            <h4 class="fw-bold mb-1 text-slate-800 d-flex align-items-center gap-2">
                <i data-lucide="shield-check" class="text-primary" style="width: 24px; height: 24px;"></i>
                Manajemen Peran & Hak Akses
            </h4>
            <p class="text-muted fs-7 mb-0">Daftar peran sistem dan pengaturan wewenang hak akses (RBAC)</p>
        </div>
    </div>
</div>

<div class="row">
    <?php foreach ($roles as $role): ?>
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 d-flex align-items-center justify-content-center">
                                <i data-lucide="shield" style="width: 24px; height: 24px;"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0"><?= esc($role['name']) ?></h5>
                                <span class="badge bg-light text-muted fs-9 rounded-pill"><?= esc($role['code']) ?></span>
                            </div>
                        </div>
                        <p class="text-muted fs-7 mb-4">
                            <?= esc($role['description'] ?: 'Tidak ada deskripsi peran.') ?>
                        </p>
                    </div>

                    <div>
                        <hr class="my-3 text-slate-200">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fs-8 text-muted">Jumlah Wewenang:</span>
                            <span class="badge bg-secondary px-2 py-1 rounded-pill fw-semibold fs-8">
                                <?= (int)$role['permission_count'] ?> Hak Akses
                            </span>
                        </div>
                        
                        <div class="d-grid">
                            <?php if ($role['code'] === 'superadmin'): ?>
                                <button class="btn btn-sm btn-light rounded-3 opacity-75 d-inline-flex align-items-center justify-content-center gap-2" disabled>
                                    <i data-lucide="shield-off" style="width: 16px; height: 16px;"></i> Hak Akses Mutlak
                                </button>
                            <?php else: ?>
                                <a href="<?= base_url('roles/' . $role['id'] . '/permissions') ?>" class="btn btn-sm btn-outline-primary rounded-3 d-inline-flex align-items-center justify-content-center gap-2">
                                    <i data-lucide="settings" style="width: 16px; height: 16px;"></i> Kelola Wewenang
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>document.addEventListener('DOMContentLoaded', function(){ if(typeof lucide!=='undefined') lucide.createIcons(); });</script>
<?= $this->endSection() ?>
