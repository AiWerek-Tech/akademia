<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1 text-slate-800 d-flex align-items-center gap-2">
                    <i data-lucide="shield-alert" class="text-primary" style="width: 24px; height: 24px;"></i>
                    Manajemen Pengguna
                </h4>
                <p class="text-muted fs-7 mb-0">Atur role, unit, profil guru, dan kelas binaan dari satu tempat.</p>
            </div>
            <?php if (has_permission('users.manage')): ?>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <?php if (is_super_admin()): ?>
                <form action="<?= base_url('users/provision-teachers') ?>" method="POST" data-confirm="Buat akun untuk semua guru aktif yang belum memiliki akun? File kredensial sementara akan langsung diunduh dan hanya dapat diperoleh sekali." data-confirm-title="Provisioning akun guru" data-confirm-icon="warning" data-confirm-button="Buat & Unduh" class="d-inline-flex">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-primary rounded-3 btn-sm px-3 d-flex align-items-center gap-2">
                        <i data-lucide="users-round" style="width:16px"></i><span>Buat Akun Semua Guru</span>
                    </button>
                </form>
                <?php endif; ?>
                <a href="<?= base_url('users/create') ?>" class="btn btn-primary rounded-3 btn-sm px-3 d-flex align-items-center gap-2">
                    <i data-lucide="user-plus" style="width: 16px; height: 16px;"></i>
                    <span>Tambah Pengguna</span>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="fs-8 text-muted">
                Total Data: <span class="fw-bold text-dark"><?= count($users) ?></span> pengguna
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="text-uppercase text-muted fs-8 fw-bold bg-light rounded-3">
                        <th class="text-center ps-3" style="width: 55px;">No.</th>
                        <th>Nama Pengguna</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status Akun</th>
                        <th>Tindakan Kata Sandi</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">Belum ada data pengguna.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $idx => $usr): ?>
                            <tr>
                                <td class="text-center fw-semibold text-secondary fs-8 ps-3"><?= $idx + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                                            <i data-lucide="user" style="width: 20px; height: 20px;"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-slate-800 d-block"><?= esc($usr['full_name']) ?></span>
                                            <span class="text-muted fs-9"><?= esc($usr['role_names'] ?: 'Belum ada role') ?></span>
                                            <div class="d-flex align-items-center gap-1 flex-wrap mt-1">
                                                <?php if ((int)$usr['must_change_password'] === 1): ?>
                                                    <span class="badge bg-warning bg-opacity-10 text-warning fs-9 fw-semibold rounded-pill px-2 py-0.5">Wajib Ganti Sandi</span>
                                                <?php endif; ?>
                                                <?php if ((int)($usr['must_change_username'] ?? 0) === 1): ?>
                                                    <span class="badge bg-warning bg-opacity-10 text-warning fs-9 fw-semibold rounded-pill px-2 py-0.5">Wajib Ganti Username</span>
                                                <?php endif; ?>
                                                <?php if (!empty($usr['teacher_name'])): ?>
                                                    <span class="badge bg-info bg-opacity-10 text-info fs-9 fw-semibold rounded-pill px-2 py-0.5">
                                                        <i data-lucide="user-check" style="width: 10px; height: 10px;" class="me-1"></i>Guru: <?= esc($usr['teacher_name']) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($usr['classroom_name'])): ?>
                                                    <span class="badge bg-purple bg-opacity-10 text-purple fs-9 fw-semibold rounded-pill px-2 py-0.5" style="color: #6f42c1; background-color: rgba(111,66,193,0.1);">
                                                        <i data-lucide="door-open" style="width: 10px; height: 10px;" class="me-1"></i>Wali: <?= esc($usr['classroom_name']) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (empty($usr['teacher_name']) && preg_match('/Guru|Wali Kelas/i', (string)($usr['role_names'] ?? ''))): ?>
                                                    <span class="badge bg-danger bg-opacity-10 text-danger fs-9 fw-semibold rounded-pill px-2 py-0.5"><i data-lucide="link-2-off" style="width:10px" class="me-1"></i>Profil guru belum ditautkan</span>
                                                <?php endif; ?>
                                            </div>
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
                                    <?php if (has_permission('users.reset_password') && !empty($usr['can_manage'])): ?>
                                        <form action="<?= base_url('users/' . $usr['uuid'] . '/reset-password') ?>" method="POST" data-confirm="Apakah Anda yakin ingin mereset password pengguna ini secara acak?" data-confirm-title="Reset password?" data-confirm-icon="warning" data-confirm-button="Reset">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-xs btn-outline-warning rounded-pill fs-8 py-1 px-3 d-inline-flex align-items-center gap-1">
                                                <i data-lucide="key-round" style="width: 14px; height: 14px;"></i> Reset Sandi
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted fs-8">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <?php if (has_permission('users.manage') && !empty($usr['can_manage'])): ?>
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
