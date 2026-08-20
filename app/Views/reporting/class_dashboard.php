<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <div class="mb-4">
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="<?= base_url('reporting') ?>">Rapor</a></li><li class="breadcrumb-item active">Dashboard Kelas</li></ol></nav>
        <h1 class="h3 fw-bold text-gray-900 mb-1">Dashboard Kelas</h1>
        <p class="text-muted mb-0">Analitik performa kelas berdasarkan laporan yang sudah di-generate.</p>
    </div>

    <?php if (empty($analytics['summary'])): ?>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="text-center py-5">
                <i data-lucide="bar-chart-3" class="text-muted mb-3" style="width:48px;height:48px"></i>
                <p class="text-muted mb-0">Belum ada data analitik. Generate laporan terlebih dahulu.</p>
            </div>
        </div>
    <?php else: ?>
        <!-- Subject Performance Cards -->
        <div class="row g-3 mb-4">
            <?php foreach ($analytics['summary'] as $subjectName => $data): ?>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3"><?= esc($subjectName) ?></h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="text-muted small">Rata-rata Nilai</div>
                                    <div class="h5 fw-bold text-primary mb-0"><?= $data['avg_score'] ?? '—' ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Rata-rata Mastery</div>
                                    <div class="h5 fw-bold text-success mb-0"><?= $data['avg_mastery'] ?? '—' ?>%</div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <div class="text-muted small mb-1">Distribusi Predikat</div>
                                <div class="d-flex gap-1 flex-wrap">
                                    <?php foreach ($data['predicate_dist'] as $pred => $count): ?>
                                        <?php $pc = match($pred) { 'A' => 'success', 'B' => 'primary', 'C' => 'warning', 'D' => 'danger', default => 'secondary' }; ?>
                                        <span class="badge bg-<?= $pc ?>-subtle text-<?= $pc ?> rounded-pill"><?= $pred ?>: <?= $count ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Student List -->
        <?php if (! empty($analytics['students'])): ?>
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 p-4 pb-2"><h5 class="fw-bold mb-0">Daftar Siswa dengan Laporan</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Siswa</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Mata Pelajaran</th>
                                    <th class="text-end pe-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($analytics['students'] as $s): ?><?php
$subjCount = \Config\Database::connect()->table('report_subject_results')
    ->where('snapshot_id', $s['id'])
    ->countAllResults();
?>
                                    <tr>
                                        <td class="ps-3 fw-semibold"><?= esc($s['student_name'] ?? '—') ?></td>
                                        <td class="text-center">
                                            <?php $sc = match($s['status']) { 'PUBLISHED' => 'success', 'LOCKED' => 'warning', default => 'secondary' }; ?>
                                            <span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?> rounded-pill"><?= $s['status'] ?></span>
                                        </td>
                                        <td class="text-center"><?= $subjCount ?></td>
                                        <td class="text-end pe-3">
                                            <a href="<?= base_url('reporting/' . $s['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">Detail</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
