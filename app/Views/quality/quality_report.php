<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <nav aria-label="breadcrumb" class="mb-3 no-print">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('quality') ?>">Kualitas</a></li>
            <li class="breadcrumb-item active">Laporan Mutu Akademik</li>
        </ol>
    </nav>

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-gray-900 mb-1">Laporan Mutu Akademik & Supervisi</h1>
            <p class="text-muted mb-0">Rekapitulasi capaian refleksi guru, hasil observasi supervisi kelas, dan pemanfaatan AI Copilot.</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <button class="btn btn-outline-primary shadow-sm rounded-pill px-3" onclick="window.print()">
                <i data-lucide="printer" class="w-4 h-4 me-1 d-inline-block"></i> Cetak Laporan
            </button>
            <a href="<?= base_url('quality') ?>" class="btn btn-outline-secondary shadow-sm rounded-pill px-3">
                <i data-lucide="arrow-left" class="w-4 h-4 me-1 d-inline-block"></i> Kembali
            </a>
        </div>
    </div>

    <!-- KPI Summary Grid -->
    <div class="row g-3 mb-4">
        <!-- Reflection Summary -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 p-4 pb-2">
                    <h5 class="mb-0 fw-bold text-gray-900"><i data-lucide="notebook-pen" class="w-5 h-5 me-2 text-primary d-inline-block"></i>Refleksi Guru</h5>
                </div>
                <div class="card-body p-4 pt-0">
                    <div class="row text-center g-2 mb-3">
                        <div class="col-4">
                            <div class="p-3 bg-light rounded-3">
                                <div class="h3 fw-bold text-primary mb-0"><?= $reflectionStats['total'] ?></div>
                                <small class="text-muted">Total</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 bg-success-subtle rounded-3">
                                <div class="h3 fw-bold text-success mb-0"><?= $reflectionStats['published'] ?></div>
                                <small class="text-muted">Publikasi</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 bg-secondary-subtle rounded-3">
                                <div class="h3 fw-bold text-secondary mb-0"><?= $reflectionStats['draft'] ?></div>
                                <small class="text-muted">Draft</small>
                            </div>
                        </div>
                    </div>
                    <?php if ($reflectionStats['total'] > 0): ?>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Tingkat Publikasi</span>
                            <?php $rate = round(($reflectionStats['published'] / $reflectionStats['total']) * 100); ?>
                            <span class="fw-semibold text-success"><?= $rate ?>%</span>
                        </div>
                        <div class="progress mb-3" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: <?= $rate ?>%"></div>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between align-items-center p-2 bg-light-subtle rounded-3 border">
                        <span class="text-muted small"><i data-lucide="sparkles" class="w-4 h-4 me-1 text-info d-inline-block"></i>Refleksi Berbantuan AI</span>
                        <span class="badge bg-info text-white rounded-pill"><?= $reflectionStats['with_ai'] ?> Refleksi</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Supervision Summary -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 p-4 pb-2">
                    <h5 class="mb-0 fw-bold text-gray-900"><i data-lucide="eye" class="w-5 h-5 me-2 text-success d-inline-block"></i>Supervisi Akademik</h5>
                </div>
                <div class="card-body p-4 pt-0">
                    <div class="row text-center g-2 mb-3">
                        <div class="col-3">
                            <div class="p-3 bg-light rounded-3">
                                <div class="h3 fw-bold text-primary mb-0"><?= $supervisionStats['total'] ?></div>
                                <small class="text-muted">Total</small>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-3 bg-success-subtle rounded-3">
                                <div class="h3 fw-bold text-success mb-0"><?= $supervisionStats['completed'] ?></div>
                                <small class="text-muted">Selesai</small>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-3 bg-warning-subtle rounded-3">
                                <div class="h3 fw-bold text-warning mb-0"><?= $supervisionStats['follow_up'] ?></div>
                                <small class="text-muted">Follow-up</small>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-3 bg-secondary-subtle rounded-3">
                                <div class="h3 fw-bold text-secondary mb-0"><?= $supervisionStats['draft'] ?></div>
                                <small class="text-muted">Draft</small>
                            </div>
                        </div>
                    </div>
                    <?php if ($supervisionStats['total'] > 0): ?>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Tingkat Ketuntasan Supervisi</span>
                            <?php $sRate = round(($supervisionStats['completed'] / $supervisionStats['total']) * 100); ?>
                            <span class="fw-semibold text-primary"><?= $sRate ?>%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-primary" style="width: <?= $sRate ?>%"></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Visual Charts Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 p-4 pb-0">
                    <h6 class="fw-bold text-gray-900 mb-0">Grafik Predikat Hasil Observasi</h6>
                </div>
                <div class="card-body p-4 pt-2">
                    <div id="reportRatingChart" style="min-height: 260px;"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 p-4 pb-0">
                    <h6 class="fw-bold text-gray-900 mb-0">Grafik Distribusi Tipe Refleksi</h6>
                </div>
                <div class="card-body p-4 pt-2">
                    <div id="reportTypeChart" style="min-height: 260px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print { display: none !important; }
    .card { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Rating Chart
    var ratings = [
        <?= (int) ($ratingBreakdown['EXCELLENT'] ?? 0) ?>,
        <?= (int) ($ratingBreakdown['GOOD'] ?? 0) ?>,
        <?= (int) ($ratingBreakdown['SATISFACTORY'] ?? 0) ?>,
        <?= (int) ($ratingBreakdown['NEEDS_IMPROVEMENT'] ?? 0) ?>
    ];

    var ratingOptions = {
        series: [{
            name: 'Jumlah Observasi',
            data: ratings
        }],
        chart: {
            height: 260,
            type: 'bar',
            toolbar: { show: false }
        },
        plotOptions: {
            bar: {
                borderRadius: 6,
                horizontal: true,
                distributed: true,
                dataLabels: { position: 'top' }
            }
        },
        colors: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
        dataLabels: {
            enabled: true,
            offsetX: 20,
            style: { fontSize: '11px', colors: ["#304758"] }
        },
        legend: { show: false },
        xaxis: {
            categories: ['Sangat Baik (Excellent)', 'Baik (Good)', 'Cukup (Satisfactory)', 'Perlu Bimbingan'],
        }
    };
    new ApexCharts(document.querySelector("#reportRatingChart"), ratingOptions).render();

    // 2. Type Chart
    var typeOptions = {
        series: [
            <?= (int) ($typeBreakdown['POST_LESSON'] ?? 0) ?>,
            <?= (int) ($typeBreakdown['PERIODIC'] ?? 0) ?>,
            <?= (int) ($typeBreakdown['ANNUAL'] ?? 0) ?>
        ],
        chart: {
            type: 'pie',
            height: 260
        },
        labels: ['Post-Lesson', 'Periodik', 'Tahunan'],
        colors: ['#6366f1', '#06b6d4', '#ec4899'],
        legend: { position: 'bottom' }
    };
    new ApexCharts(document.querySelector("#reportTypeChart"), typeOptions).render();
});
</script>
<?= $this->endSection() ?>
