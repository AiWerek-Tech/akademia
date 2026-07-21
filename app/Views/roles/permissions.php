<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('roles') ?>" class="btn btn-outline-secondary btn-sm rounded-3 d-inline-flex align-items-center gap-1">
                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i> Kembali
            </a>
            <h4 class="fw-bold mb-0 text-slate-800">Atur Wewenang Peran: <?= esc($role['name']) ?></h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-10 col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <form action="<?= base_url('roles/' . $role['id'] . '/permissions') ?>" method="POST">
                    <?= csrf_field() ?>

                    <?php 
                    // Group permissions by module
                    $grouped = [];
                    foreach ($permissions as $p) {
                        $grouped[$p['module']][] = $p;
                    }
                    ?>

                    <?php foreach ($grouped as $module => $perms): ?>
                        <div class="mb-4">
                            <h6 class="fw-bold text-primary text-uppercase fs-8 letter-spacing-1 border-bottom pb-2 mb-3 d-flex align-items-center gap-2">
                                <i data-lucide="folder" style="width: 14px; height: 14px;"></i>
                                <?= esc(ucfirst($module)) ?>
                            </h6>
                            <div class="row g-3">
                                <?php foreach ($perms as $p): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="form-check form-switch bg-light p-2.5 rounded-3 border d-flex justify-content-between align-items-center" style="min-height: 48px; padding-left: 2.75rem;">
                                            <div>
                                                <label class="form-check-label fw-semibold text-slate-700 fs-7 d-block" for="perm_<?= $p['id'] ?>">
                                                    <?= esc($p['name']) ?>
                                                </label>
                                                <span class="text-muted fs-9 d-block"><?= esc($p['code']) ?></span>
                                            </div>
                                            <input class="form-check-input ms-2 me-1" type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" id="perm_<?= $p['id'] ?>" <?= in_array($p['id'], $activePerms) ? 'checked' : '' ?>>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <hr class="my-4 text-slate-200">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= base_url('roles') ?>" class="btn btn-light rounded-3 px-4">Batal</a>
                        <button type="submit" class="btn btn-primary rounded-3 px-4 d-inline-flex align-items-center gap-2">
                            <i data-lucide="save" style="width: 16px; height: 16px;"></i> Simpan Wewenang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>document.addEventListener('DOMContentLoaded', function(){ if(typeof lucide!=='undefined') lucide.createIcons(); });</script>
<?= $this->endSection() ?>
