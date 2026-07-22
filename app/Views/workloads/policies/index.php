<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Kebijakan Beban Kerja</h1>
            <p class="text-muted small mb-0">Atur kebijakan jam mengajar minimum, target, dan maksimum berdasarkan profil kepegawaian guru</p>
        </div>
        <?php if (has_permission('workloads.manage')): ?>
            <a href="<?= base_url('workloads/policies/create') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Buat Kebijakan Baru
            </a>
        <?php endif; ?>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= esc(session()->getFlashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Periode</th>
                            <th>Unit Scope</th>
                            <th>Status Kepegawaian</th>
                            <th>Tipe Pekerjaan</th>
                            <th>Min Mengajar</th>
                            <th>Target Total</th>
                            <th>Max Total</th>
                            <th>Prioritas</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($policies)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">Belum ada kebijakan beban kerja yang terdaftar.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($policies as $p): ?>
                                <tr>
                                    <td><?= esc($p['period_name']) ?></td>
                                    <td><?= esc($p['unit_name'] ?? 'Global (Semua Unit)') ?></td>
                                    <td><?= esc($p['employment_status'] ?? 'Semua') ?></td>
                                    <td><?= esc($p['employment_type'] ?? 'Semua') ?></td>
                                    <td><?= esc($p['minimum_teaching_hours']) ?> JP</td>
                                    <td><?= esc($p['target_total_hours'] ?? '-') ?> JP</td>
                                    <td><?= esc($p['maximum_total_hours']) ?> JP</td>
                                    <td><span class="badge bg-secondary"><?= esc($p['priority']) ?></span></td>
                                    <td>
                                        <?php if ((int)$p['is_active'] === 1): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted">Non-aktif</span>
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
</div>
<?= $this->endSection() ?>
