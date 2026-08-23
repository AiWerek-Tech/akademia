<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="<?= base_url('reporting') ?>">Rapor</a></li><li class="breadcrumb-item active">Dashboard Kelas</li></ol></nav>
            <h1 class="h3 fw-bold text-gray-900 mb-1">Dashboard Analitik Kelas</h1>
            <p class="text-muted mb-0">Visualisasi performa akademik per mata pelajaran — rata-rata nilai, penguasaan kompetensi, dan distribusi predikat.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('reporting') ?>" class="btn btn-outline-primary shadow-sm rounded-pill px-3">
                <i data-lucide="arrow-left" class="w-4 h-4 me-1 d-inline-block"></i> Kembali ke Rapor
            </a>
        </div>
    </div>

    <?php if (empty($analytics['summary'])): ?>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="text-center py-5">
                <i data-lucide="bar-chart-3" class="text-muted mb-3" style="width:48px;height:48px"></i>
                <p class="fw-bold text-muted mb-1">Belum ada data analitik tersedia.</p>
                <p class="text-muted small mb-0">Generate rapor terlebih dahulu dari halaman <a href="<?= base_url('reporting') ?>">Rapor & Pelaporan</a>.</p>
            </div>
        </div>
    <?php else: ?>

        <!-- ApexCharts Visualisation Row -->
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white border-0 p-4 pb-0">
                        <h6 class="fw-bold text-gray-900 mb-0"><i data-lucide="bar-chart-3" class="w-5 h-5 me-1 text-primary d-inline-block"></i> Perbandingan Rata-rata Nilai & Penguasaan Kompetensi</h6>
                    </div>
                    <div class="card-body p-4 pt-2">
                        <div id="subjectPerformanceChart" style="min-height: 340px;"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white border-0 p-4 pb-0">
                        <h6 class="fw-bold text-gray-900 mb-0"><i data-lucide="pie-chart" class="w-5 h-5 me-1 text-warning d-inline-block"></i> Distribusi Predikat Keseluruhan</h6>
                    </div>
                    <div class="card-body p-4 pt-2 d-flex align-items-center justify-content-center">
                        <div id="predicateDonutChart" style="min-height: 280px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subject Performance Cards -->
        <h5 class="fw-bold text-gray-900 mb-3"><i data-lucide="layout-grid" class="w-5 h-5 me-1 text-primary d-inline-block"></i> Rincian Per Mata Pelajaran</h5>
        <div class="row g-3 mb-4">
            <?php foreach ($analytics['summary'] as $subjectName => $data): ?>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3 text-gray-900"><?= esc($subjectName) ?></h6>
                            <div class="row g-2 mb-3">
                                <div class="col-4">
                                    <div class="text-center p-2 rounded-3 bg-primary-subtle">
                                        <div class="h5 fw-bold text-primary mb-0"><?= $data['avg_score'] ?? '—' ?></div>
                                        <div class="text-muted" style="font-size: 10px;">Rata-rata</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="text-center p-2 rounded-3 bg-success-subtle">
                                        <div class="h5 fw-bold text-success mb-0"><?= $data['avg_mastery'] !== null ? $data['avg_mastery'] . '%' : '—' ?></div>
                                        <div class="text-muted" style="font-size: 10px;">Mastery</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="text-center p-2 rounded-3 bg-info-subtle">
                                        <div class="h5 fw-bold text-info mb-0"><?= (int) ($data['student_count'] ?? 0) ?></div>
                                        <div class="text-muted" style="font-size: 10px;">Siswa</div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-muted small mb-1 fw-semibold">Distribusi Predikat</div>
                            <div class="d-flex gap-1 flex-wrap">
                                <?php foreach ($data['predicate_dist'] as $pred => $count): ?>
                                    <?php $pc = match($pred) { 'A' => 'success', 'B' => 'primary', 'C' => 'warning', 'D' => 'danger', default => 'secondary' }; ?>
                                    <span class="badge bg-<?= $pc ?>-subtle text-<?= $pc ?> rounded-pill px-2.5 py-1"><?= $pred ?>: <?= $count ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Student List -->
        <?php if (! empty($analytics['students'])): ?>
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 p-4 pb-2 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-gray-900"><i data-lucide="users" class="w-5 h-5 me-1 text-info d-inline-block"></i> Daftar Siswa</h5>
                    <span class="badge bg-info-subtle text-info rounded-pill px-2.5 py-1"><?= count($analytics['students']) ?> Siswa</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                                <tr>
                                    <th class="ps-4 py-3">Peserta Didik</th>
                                    <th class="text-center py-3">Status Rapor</th>
                                    <th class="text-center py-3">Jumlah Mapel</th>
                                    <th class="text-end pe-4 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($analytics['students'] as $s): ?>
                                    <?php
                                    $subjCount = \Config\Database::connect()->table('report_subject_results')
                                        ->where('snapshot_id', $s['id'])
                                        ->countAllResults();
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold py-3"><?= esc($s['student_name'] ?? '—') ?></td>
                                        <td class="text-center py-3">
                                            <?php $sc = match($s['status']) { 'PUBLISHED' => 'success', 'LOCKED' => 'warning', default => 'secondary' }; ?>
                                            <span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?> rounded-pill px-2.5 py-1"><?= $s['status'] ?></span>
                                        </td>
                                        <td class="text-center py-3 fw-bold text-primary"><?= $subjCount ?></td>
                                        <td class="text-end pe-4 py-3">
                                            <div class="d-inline-flex gap-1">
                                                <a href="<?= base_url('reporting/' . $s['id'] . '/print') ?>" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-2.5" title="Cetak Rapor">
                                                    <i data-lucide="printer" class="w-3.5 h-3.5 d-inline-block"></i>
                                                </a>
                                                <a href="<?= base_url('reporting/' . $s['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">Detail</a>
                                            </div>
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

<!-- ApexCharts CDN -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    <?php if (! empty($analytics['summary'])): ?>

    // Prepare data from PHP
    const subjects = <?= json_encode(array_keys($analytics['summary'])) ?>;
    const avgScores = <?= json_encode(array_values(array_map(fn($d) => (float) ($d['avg_score'] ?? 0), $analytics['summary']))) ?>;
    const avgMastery = <?= json_encode(array_values(array_map(fn($d) => (float) ($d['avg_mastery'] ?? 0), $analytics['summary']))) ?>;

    // Aggregate predicate distribution across all subjects
    const allPredicates = {};
    <?php foreach ($analytics['summary'] as $data): ?>
        <?php foreach ($data['predicate_dist'] as $pred => $count): ?>
            allPredicates['<?= $pred ?>'] = (allPredicates['<?= $pred ?>'] || 0) + <?= $count ?>;
        <?php endforeach; ?>
    <?php endforeach; ?>

    // Bar Chart — Subject Performance
    if (document.getElementById('subjectPerformanceChart')) {
        new ApexCharts(document.getElementById('subjectPerformanceChart'), {
            chart: {
                type: 'bar',
                height: 340,
                toolbar: { show: false },
                fontFamily: 'Plus Jakarta Sans, sans-serif',
            },
            series: [
                { name: 'Rata-rata Nilai', data: avgScores },
                { name: 'Penguasaan Kompetensi (%)', data: avgMastery }
            ],
            xaxis: {
                categories: subjects,
                labels: { style: { fontSize: '11px', fontWeight: 600, colors: '#64748b' } }
            },
            yaxis: { max: 100, labels: { style: { fontSize: '11px', colors: '#94a3b8' } } },
            colors: ['#6366f1', '#10b981'],
            plotOptions: {
                bar: { borderRadius: 6, columnWidth: '45%', dataLabels: { position: 'top' } }
            },
            dataLabels: {
                enabled: true,
                offsetY: -20,
                style: { fontSize: '11px', fontWeight: 700, colors: ['#334155'] },
                formatter: v => v > 0 ? v : ''
            },
            grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
            legend: { position: 'top', fontSize: '12px', fontWeight: 600 },
            tooltip: { theme: 'light' }
        }).render();
    }

    // Donut Chart — Predicate Distribution
    if (document.getElementById('predicateDonutChart')) {
        const predLabels = Object.keys(allPredicates);
        const predValues = Object.values(allPredicates);
        const predColors = predLabels.map(p => ({
            'A': '#10b981', 'B': '#6366f1', 'C': '#f59e0b', 'D': '#ef4444'
        }[p] || '#94a3b8'));

        new ApexCharts(document.getElementById('predicateDonutChart'), {
            chart: {
                type: 'donut',
                height: 280,
                fontFamily: 'Plus Jakarta Sans, sans-serif',
            },
            series: predValues,
            labels: predLabels.map(l => 'Predikat ' + l),
            colors: predColors,
            legend: { position: 'bottom', fontSize: '12px', fontWeight: 600, labels: { colors: '#475569' } },
            plotOptions: {
                pie: {
                    donut: {
                        size: '55%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Nilai',
                                fontSize: '12px',
                                fontWeight: 700,
                                color: '#334155'
                            }
                        }
                    }
                }
            },
            dataLabels: { enabled: true, style: { fontSize: '12px', fontWeight: 700 } },
            tooltip: { theme: 'light' }
        }).render();
    }

    <?php endif; ?>
});
</script>
<?= $this->endSection() ?>
