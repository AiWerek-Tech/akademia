<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-1 text-slate-800 d-flex align-items-center gap-2">
                    <i data-lucide="shield-alert" class="text-primary" style="width: 24px; height: 24px;"></i>
                    Manajemen Pengguna
                </h4>
                <p class="text-muted fs-7 mb-0">Manajemen akun pengguna sistem dan penugasan peran</p>
            </div>
            <?php if (has_permission('users.manage')): ?>
                <a href="<?= base_url('users/create') ?>" class="btn btn-primary rounded-3 btn-sm px-3 d-flex align-items-center gap-2">
                    <i data-lucide="user-plus" style="width: 16px; height: 16px;"></i>
                    <span>Tambah Pengguna</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="text-uppercase text-muted fs-8 fw-bold">
                        <th>Nama Pengguna</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status Akun</th>
                        <th>Tindakan Kata Sandi</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Belum ada data pengguna.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $usr): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                                            <i data-lucide="user" style="width: 20px; height: 20px;"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-slate-800 d-block"><?= esc($usr['full_name']) ?></span>
                                            <?php if ((int)$usr['must_change_password'] === 1): ?>
                                                <span class="badge bg-warning bg-opacity-10 text-warning fs-9 fw-semibold rounded-pill px-2 py-0.5">Wajib Ganti Sandi</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="fw-semibold text-slate-700"><?= esc($usr['username']) ?></span></td>
                                <td><?= esc($usr['email']) ?></td>
                                <td>
                                    <?php if ((int)$usr['is_active'] === 1): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-1 rounded-pill fw-semibold fs-8">
                                            Aktif
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-1 rounded-pill fw-semibold fs-8">
                                            Non-Aktif
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (has_permission('users.manage') && $usr['username'] !== 'superadmin'): ?>
                                        <form action="<?= base_url('users/' . $usr['uuid'] . '/reset-password') ?>" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mereset password pengguna ini secara acak?')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-xs btn-outline-warning rounded-pill fs-8 py-1 px-3 d-inline-flex align-items-center gap-1">
                                                <i data-lucide="key-round" style="width: 14px; height: 14px;"></i> Reset Sandi
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted fs-8">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if (has_permission('users.manage') && $usr['username'] !== 'superadmin'): ?>
                                        <a href="<?= base_url('users/' . $usr['uuid'] . '/edit') ?>" class="btn btn-sm btn-light rounded-3 text-secondary d-inline-flex align-items-center gap-1">
                                            <i data-lucide="pencil" style="width: 14px; height: 14px;"></i> Edit
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-light rounded-3 opacity-50 d-inline-flex align-items-center gap-1" disabled>
                                            <i data-lucide="lock" style="width: 14px; height: 14px;"></i> Locked
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>document.addEventListener('DOMContentLoaded', function(){ if(typeof lucide!=='undefined') lucide.createIcons(); });</script>
<?= $this->endSection() ?>
