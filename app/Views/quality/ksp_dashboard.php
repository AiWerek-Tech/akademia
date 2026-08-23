<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('quality') ?>">Kualitas</a></li>
            <li class="breadcrumb-item active">Evaluasi KSP</li>
        </ol>
    </nav>

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-gray-900 mb-1">Evaluasi KSP</h1>
            <p class="text-muted mb-0">Evaluasi dan tindak lanjut perbaikan mutu berdasarkan Komite Sekolah Pendidikan.</p>
        </div>
    </div>

    <!-- Evaluations -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom rounded-top-4">
            <h5 class="mb-0 fw-semibold">Daftar Evaluasi KSP</h5>
        </div>
        <div class="card-body">
            <?php if (empty($evaluations)): ?>
                <div class="text-center py-5">
                    <i data-lucide="clipboard-check" class="text-muted mb-3" style="width:48px;height:48px"></i>
                    <p class="text-muted">Belum ada evaluasi KSP.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Objektif</th>
                                <th>Target</th>
                                <th>Capaian Aktual</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($evaluations as $ev): ?>
                                <tr>
                                    <td class="fw-medium"><?= esc($ev['objective'] ?? '-') ?></td>
                                    <td><?= esc($ev['target_value'] ?? '-') ?></td>
                                    <td><?= esc($ev['actual_value'] ?? '-') ?></td>
                                    <td>
                                        <?php
                                        $statusClass = match($ev['status'] ?? '') {
                                            'COMPLETED' => 'success',
                                            'IN_PROGRESS' => 'warning',
                                            'ACTIVE' => 'primary',
                                            'DRAFT' => 'secondary',
                                            'CANCELLED' => 'danger',
                                            default => 'secondary',
                                        };
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?>-subtle text-<?= $statusClass ?> rounded-pill">
                                            <?= esc($ev['status'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small"><?= esc($ev['created_at'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Improvement Actions -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom rounded-top-4">
            <h5 class="mb-0 fw-semibold">Tindak Lanjut Perbaikan</h5>
        </div>
        <div class="card-body">
            <?php if (empty($actions)): ?>
                <div class="text-center py-5">
                    <i data-lucide="target" class="text-muted mb-3" style="width:48px;height:48px"></i>
                    <p class="text-muted">Belum ada tindak lanjut perbaikan.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Judul</th>
                                <th>Deskripsi</th>
                                <th>PIC</th>
                                <th>Deadline</th>
                                <th>Hasil / Outcome</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($actions as $act): ?>
                                <tr>
                                    <td class="fw-medium"><?= esc($act['title'] ?? '-') ?></td>
                                    <td class="text-muted small"><?= esc($act['description'] ?? '-') ?></td>
                                    <td><?= esc($act['owner_name'] ?? 'User #' . ($act['owner_user_id'] ?? '-')) ?></td>
                                    <td><?= esc($act['due_date'] ?? '-') ?></td>
                                    <td class="text-muted small"><?= esc($act['outcome'] ?? '-') ?></td>
                                    <td>
                                        <?php
                                        $statusClass = match($act['status'] ?? '') {
                                            'COMPLETED' => 'success',
                                            'IN_PROGRESS' => 'warning',
                                            'OPEN' => 'info',
                                            'CANCELLED' => 'danger',
                                            default => 'secondary',
                                        };
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?>-subtle text-<?= $statusClass ?> rounded-pill">
                                            <?= esc($act['status'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $this->endSection() ?>
