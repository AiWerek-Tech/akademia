<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <!-- Header & Quick Actions -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-purple-subtle text-purple px-2.5 py-1 rounded-pill fw-semibold text-xs text-uppercase tracking-wider">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Phase 10 Quality Engine
                </span>
                <span class="badge bg-info-subtle text-info px-2.5 py-1 rounded-pill fw-semibold text-xs">
                    <i data-lucide="bot" class="w-3.5 h-3.5 me-1 d-inline-block"></i> AI Copilot Governed
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mb-1">Pusat Penjaminan Mutu & AI Copilot</h1>
            <p class="text-muted mb-0">Pemantauan refleksi pedagogik, observasi supervisi guru, evaluasi KSP, dan asisten AI akademik.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= base_url('quality/report') ?>" class="btn btn-outline-primary shadow-sm rounded-pill px-3">
                <i data-lucide="file-text" class="w-4 h-4 me-1 d-inline-block"></i> Laporan Mutu
            </a>
            <?php if (has_permission('supervision.manage')): ?>
                <a href="<?= base_url('quality/supervision/create') ?>" class="btn btn-outline-success shadow-sm rounded-pill px-3">
                    <i data-lucide="eye" class="w-4 h-4 me-1 d-inline-block"></i> Catat Supervisi
                </a>
            <?php endif; ?>
            <?php if (has_permission('teacher_reflection.manage')): ?>
                <a href="<?= base_url('quality/reflection/create') ?>" class="btn btn-primary shadow-sm rounded-pill px-3">
                    <i data-lucide="plus" class="w-4 h-4 me-1 d-inline-block"></i> Buat Refleksi
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top KPI Cards -->
    <div class="row g-3 mb-4">
        <!-- Reflections -->
        <div class="col-md-4">
            <a href="<?= base_url('quality/reflections') ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 bg-primary-subtle p-3 text-primary"><i data-lucide="brain" style="width:24px;height:24px"></i></div>
                                <div>
                                    <div class="h3 fw-bold mb-0 text-gray-900"><?= $reflectionStats['total'] ?></div>
                                    <div class="text-muted small">Total Refleksi Guru</div>
                                </div>
                            </div>
                            <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1 text-xs">Refleksi</span>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1"><i data-lucide="check" class="w-3 h-3 me-1 d-inline-block"></i><?= $reflectionStats['published'] ?> Publik</span>
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1"><?= $reflectionStats['draft'] ?> Draft</span>
                            <span class="badge bg-info-subtle text-info rounded-pill px-2.5 py-1"><i data-lucide="sparkles" class="w-3 h-3 me-1 d-inline-block"></i><?= $reflectionStats['with_ai'] ?> AI Assist</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Supervision -->
        <div class="col-md-4">
            <a href="<?= base_url('quality/supervisions') ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 bg-success-subtle p-3 text-success"><i data-lucide="eye" style="width:24px;height:24px"></i></div>
                                <div>
                                    <div class="h3 fw-bold mb-0 text-gray-900"><?= $supervisionStats['total'] ?></div>
                                    <div class="text-muted small">Total Supervisi Kelas</div>
                                </div>
                            </div>
                            <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1 text-xs">Supervisi</span>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1"><i data-lucide="check-circle" class="w-3 h-3 me-1 d-inline-block"></i><?= $supervisionStats['completed'] ?> Selesai</span>
                            <span class="badge bg-warning-subtle text-warning rounded-pill px-2.5 py-1"><i data-lucide="alert-circle" class="w-3 h-3 me-1 d-inline-block"></i><?= $supervisionStats['follow_up'] ?> Follow-up</span>
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1"><?= $supervisionStats['draft'] ?> Draft</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- AI Copilot -->
        <div class="col-md-4">
            <a href="<?= base_url('quality/copilot') ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 bg-info-subtle p-3 text-info"><i data-lucide="bot" style="width:24px;height:24px"></i></div>
                                <div>
                                    <div class="h3 fw-bold mb-0 text-gray-900"><?= $aiStats['total'] ?></div>
                                    <div class="text-muted small">Generasi AI Copilot</div>
                                </div>
                            </div>
                            <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1 text-xs">AI Copilot</span>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1"><i data-lucide="thumbs-up" class="w-3 h-3 me-1 d-inline-block"></i><?= $aiStats['accepted'] ?> Diterima</span>
                            <span class="badge bg-danger-subtle text-danger rounded-pill px-2.5 py-1"><?= $aiStats['rejected'] ?> Ditolak</span>
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1"><?= $aiStats['pending'] ?> Pending</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Visual Analytics: ApexCharts Section -->
    <div class="row g-4 mb-4">
        <!-- Chart 1: Supervision Ratings Radar / Bar -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 p-4 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-gray-900 mb-1"><i data-lucide="award" class="w-4 h-4 me-1 text-primary d-inline-block"></i> Distribusi Predikat Supervisi Guru</h6>
                        <p class="text-muted small mb-0">Capaian mutu observasi pedagogik pada periode aktif.</p>
                    </div>
                </div>
                <div class="card-body p-4 pt-2">
                    <div id="supervisionRadarChart" style="min-height: 280px;"></div>
                </div>
            </div>
        </div>

        <!-- Chart 2: Reflection & AI Governance Breakdown -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 p-4 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-gray-900 mb-1"><i data-lucide="pie-chart" class="w-4 h-4 me-1 text-info d-inline-block"></i> Komposisi Tipe Refleksi & Adopsi AI</h6>
                        <p class="text-muted small mb-0">Rincian jenis refleksi dan tingkat akseptasi asisten AI.</p>
                    </div>
                </div>
                <div class="card-body p-4 pt-2">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <div id="reflectionDonutChart" style="min-height: 250px;"></div>
                        </div>
                        <div class="col-md-5">
                            <div class="p-3 bg-light-subtle rounded-3 border">
                                <div class="text-xs text-muted fw-semibold text-uppercase tracking-wider mb-2">Tingkat Akseptasi AI</div>
                                <div class="d-flex align-items-baseline gap-2 mb-2">
                                    <div class="h4 fw-bold text-success mb-0"><?= $aiAdoption['acceptance_rate'] ?? 0 ?>%</div>
                                    <small class="text-muted">Approval Rate</small>
                                </div>
                                <div class="progress mb-3" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: <?= $aiAdoption['acceptance_rate'] ?? 0 ?>%"></div>
                                </div>
                                <div class="text-xs text-muted">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Feedback Berguna:</span>
                                        <strong class="text-success"><?= $aiAdoption['feedback_useful'] ?? 0 ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Perlu Koreksi:</span>
                                        <strong class="text-warning"><?= $aiAdoption['feedback_correct'] ?? 0 ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Navigation Features Grid -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 p-4 pb-2">
            <h5 class="fw-bold text-gray-900 mb-1">Modul Penjaminan Mutu & Kolaborasi</h5>
            <p class="text-muted small mb-0">Akses cepat seluruh instrumen pengembangan akademik sekolah.</p>
        </div>
        <div class="card-body p-4 pt-0">
            <div class="row g-3">
                <div class="col-md-3">
                    <a href="<?= base_url('quality/reflections') ?>" class="card card-body border rounded-4 text-decoration-none h-100 p-3 hover-shadow">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2.5 rounded-3 bg-primary-subtle text-primary"><i data-lucide="notebook-pen" class="w-5 h-5"></i></div>
                            <div>
                                <h6 class="fw-bold text-gray-900 mb-0">Refleksi Guru</h6>
                                <small class="text-muted">Pencatatan evaluasi diri</small>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= base_url('quality/supervisions') ?>" class="card card-body border rounded-4 text-decoration-none h-100 p-3 hover-shadow">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2.5 rounded-3 bg-success-subtle text-success"><i data-lucide="eye" class="w-5 h-5"></i></div>
                            <div>
                                <h6 class="fw-bold text-gray-900 mb-0">Supervisi Akademik</h6>
                                <small class="text-muted">Observasi & pendampingan</small>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= base_url('quality/ksp') ?>" class="card card-body border rounded-4 text-decoration-none h-100 p-3 hover-shadow">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2.5 rounded-3 bg-warning-subtle text-warning"><i data-lucide="clipboard-check" class="w-5 h-5"></i></div>
                            <div>
                                <h6 class="fw-bold text-gray-900 mb-0">Evaluasi KSP</h6>
                                <small class="text-muted">Kurikulum Satuan Pendidikan</small>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= base_url('quality/copilot') ?>" class="card card-body border rounded-4 text-decoration-none h-100 p-3 hover-shadow">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2.5 rounded-3 bg-info-subtle text-info"><i data-lucide="bot" class="w-5 h-5"></i></div>
                            <div>
                                <h6 class="fw-bold text-gray-900 mb-0">AI Academic Copilot</h6>
                                <small class="text-muted">Asisten telaah & draf narasi</small>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ApexCharts CDN -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Supervision Radar/Bar Chart
    var radarData = [
        <?= (int) ($ratingBreakdown['EXCELLENT'] ?? 0) ?>,
        <?= (int) ($ratingBreakdown['GOOD'] ?? 0) ?>,
        <?= (int) ($ratingBreakdown['SATISFACTORY'] ?? 0) ?>,
        <?= (int) ($ratingBreakdown['NEEDS_IMPROVEMENT'] ?? 0) ?>
    ];

    var optionsRadar = {
        series: [{
            name: 'Jumlah Guru',
            data: radarData
        }],
        chart: {
            height: 280,
            type: 'bar',
            toolbar: { show: false }
        },
        plotOptions: {
            bar: {
                borderRadius: 8,
                columnWidth: '45%',
                distributed: true,
                dataLabels: { position: 'top' }
            }
        },
        colors: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
        dataLabels: {
            enabled: true,
            offsetY: -20,
            style: { fontSize: '12px', colors: ["#304758"] }
        },
        legend: { show: false },
        xaxis: {
            categories: ['Sangat Baik', 'Baik', 'Cukup', 'Perlu Bimbingan'],
            labels: { style: { fontSize: '11px', fontWeight: 600 } }
        },
        yaxis: {
            title: { text: 'Jumlah Supervisi' },
            labels: { formatter: function(v) { return Math.floor(v); } }
        },
        grid: { strokeDashArray: 4 }
    };

    var chartRadar = new ApexCharts(document.querySelector("#supervisionRadarChart"), optionsRadar);
    chartRadar.render();

    // 2. Reflection Types Donut Chart
    var typePostLesson = <?= (int) ($typeBreakdown['POST_LESSON'] ?? 0) ?>;
    var typePeriodic   = <?= (int) ($typeBreakdown['PERIODIC'] ?? 0) ?>;
    var typeAnnual     = <?= (int) ($typeBreakdown['ANNUAL'] ?? 0) ?>;

    var seriesDonut = [typePostLesson, typePeriodic, typeAnnual];
    var totalReflections = typePostLesson + typePeriodic + typeAnnual;

    if (totalReflections === 0) {
        seriesDonut = [1, 0, 0];
    }

    var optionsDonut = {
        series: seriesDonut,
        chart: {
            type: 'donut',
            height: 250
        },
        labels: totalReflections === 0 ? ['Belum ada data'] : ['Post-Lesson', 'Periodik', 'Tahunan'],
        colors: totalReflections === 0 ? ['#cbd5e1'] : ['#6366f1', '#38bdf8', '#a855f7'],
        legend: {
            position: 'bottom',
            fontSize: '11px'
        },
        dataLabels: {
            enabled: totalReflections > 0
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            formatter: function() { return totalReflections; }
                        }
                    }
                }
            }
        }
    };

    var chartDonut = new ApexCharts(document.querySelector("#reflectionDonutChart"), optionsDonut);
    chartDonut.render();
});
</script>
<?= $this->endSection() ?>
