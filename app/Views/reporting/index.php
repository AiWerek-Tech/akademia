<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-purple-subtle text-purple px-2.5 py-1.5 rounded-pill fw-semibold text-xs text-uppercase tracking-wider">
                    <i data-lucide="file-bar-chart" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Phase 9 Reporting
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mt-2 mb-1">Rapor & Pelaporan</h1>
            <p class="text-muted mb-0">Generate laporan semester, kelola narasi, dan pantau analitik kelas.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('reporting/class-dashboard') ?>" class="btn btn-outline-primary shadow-sm rounded-pill px-3">
                <i data-lucide="bar-chart-3" class="w-4 h-4 me-1"></i> Dashboard Kelas
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= base_url('reporting') ?>" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm shadow-sm">
                        <option value="">Semua Status</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-outline-primary shadow-sm w-100">Filter</button>
                    <a href="<?= base_url('reporting') ?>" class="btn btn-sm btn-outline-secondary shadow-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Snapshots Table -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <?php if (empty($snapshots)): ?>
                <div class="text-center py-5">
                    <i data-lucide="file-bar-chart" class="text-muted mb-3" style="width:48px;height:48px"></i>
                    <p class="text-muted mb-0">Belum ada laporan yang di-generate.</p>
                    <p class="text-muted small">Generate laporan dari halaman detail siswa atau menggunakan tombol aksi bulk.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Siswa</th>
                                <th>Rombel</th>
                                <th class="text-center">Tipe</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Mata Pelajaran</th>
                                <th class="text-end pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($snapshots as $s): ?><?php
$subjectCount = \Config\Database::connect()->table('report_subject_results')
    ->where('snapshot_id', $s['id'])
    ->countAllResults();
?>
                                <tr>
                                    <td class="ps-3">
                                        <a href="<?= base_url('reporting/' . $s['id']) ?>" class="fw-semibold text-decoration-none"><?= esc($s['student_name'] ?? '—') ?></a>
                                    </td>
                                    <td><?= esc($s['classroom_name'] ?? '—') ?></td>
                                    <td class="text-center"><span class="badge bg-light text-dark border"><?= $s['snapshot_type'] ?></span></td>
                                    <td class="text-center">
                                        <?php $sc = match($s['status']) { 'PUBLISHED' => 'success', 'LOCKED' => 'warning', default => 'secondary' }; ?>
                                        <span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?> rounded-pill"><?= $s['status'] ?></span>
                                    </td>
                                    <td class="text-center"><?= $subjectCount ?></td>
                                    <td class="text-end pe-3">
                                        <a href="<?= base_url('reporting/' . $s['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">Detail</a>
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
