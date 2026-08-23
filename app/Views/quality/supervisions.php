<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="<?= base_url('quality') ?>">Kualitas</a></li><li class="breadcrumb-item active">Supervisi</li></ol></nav>
            <h1 class="h3 fw-bold text-gray-900 mb-0">Catatan Supervisi</h1>
        </div>
        <?php if (has_permission('supervision.manage')): ?>
            <a href="<?= base_url('quality/supervision/create') ?>" class="btn btn-primary shadow-sm rounded-pill px-3"><i data-lucide="plus" class="w-4 h-4 me-1"></i> Catatan Baru</a>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= base_url('quality/supervisions') ?>" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Guru</label>
                    <select name="teacher_id" class="form-select form-select-sm shadow-sm">
                        <option value="">Semua Guru</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= ($filters['teacher_id'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= esc($t['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm shadow-sm">
                        <option value="">Semua</option>
                        <option value="DRAFT" <?= ($filters['status'] ?? '') === 'DRAFT' ? 'selected' : '' ?>>DRAFT</option>
                        <option value="COMPLETED" <?= ($filters['status'] ?? '') === 'COMPLETED' ? 'selected' : '' ?>>COMPLETED</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Follow-up</label>
                    <select name="follow_up" class="form-select form-select-sm shadow-sm">
                        <option value="">Semua</option>
                        <option value="1" <?= ($filters['follow_up'] ?? '') === '1' ? 'selected' : '' ?>>Perlu Follow-up</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-outline-primary shadow-sm w-100">Filter</button>
                    <a href="<?= base_url('quality/supervisions') ?>" class="btn btn-sm btn-outline-secondary shadow-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <?php if (empty($records)): ?>
                <div class="text-center py-5">
                    <i data-lucide="eye" class="text-muted mb-3" style="width:48px;height:48px"></i>
                    <p class="text-muted mb-0">Belum ada catatan supervisi.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Guru</th>
                                <th>Tanggal</th>
                                <th>Tipe</th>
                                <th>Supervisor</th>
                                <th class="text-center">Rating</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $r): ?>
                                <tr>
                                    <td class="ps-3 fw-semibold"><?= esc($r['teacher_name'] ?? '—') ?></td>
                                    <td><?= date('d M Y', strtotime($r['observation_date'])) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= $r['observation_type'] ?></span></td>
                                    <td><?= esc($r['supervisor_name'] ?? '—') ?></td>
                                    <td class="text-center">
                                        <?php if ($r['overall_rating']): ?>
                                            <?php $rc = match($r['overall_rating']) { 'EXCELLENT' => 'success', 'GOOD' => 'primary', 'SATISFACTORY' => 'warning', default => 'danger' }; ?>
                                            <span class="badge bg-<?= $rc ?>-subtle text-<?= $rc ?> rounded-pill"><?= $r['overall_rating'] ?></span>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php $sc = $r['status'] === 'COMPLETED' ? 'success' : 'secondary'; ?>
                                        <span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?> rounded-pill"><?= $r['status'] ?></span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <?php if ($r['follow_up_needed']): ?>
                                            <span class="badge bg-warning-subtle text-warning rounded-pill me-1" style="font-size:.6rem">Follow-up</span>
                                        <?php endif; ?>
                                        <a href="<?= base_url('quality/supervision/' . $r['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">Detail</a>
                                        <a href="<?= base_url('quality/supervision/' . $r['id'] . '/print') ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-2 ms-1" title="Cetak Lembar Observasi"><i data-lucide="printer" style="width:14px;height:14px"></i></a>
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
<?= $this->endSection() ?>
