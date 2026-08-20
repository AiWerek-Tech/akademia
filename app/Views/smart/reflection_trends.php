<?= $this->extend('layouts/admin') ?>

<?= $this->section('additional_css') ?>
<style>
.insight-card { border-radius: 14px; border: none; overflow: hidden; }
.insight-card .insight-header { padding: 14px 18px; font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
.insight-item { padding: 10px 18px; border-bottom: 1px solid #f1f3f5; font-size: .82rem; line-height: 1.5; }
.insight-item:last-child { border-bottom: none; }
.trend-badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 12px; border-radius: 20px; font-size: .75rem; font-weight: 700; }
</style>
<?= $this->endSection() ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-gradient px-3 py-2 rounded-pill fw-semibold text-xs" style="background: linear-gradient(135deg, #22c55e, #14b8a6); color: #fff;">
                    <i data-lucide="trending-up" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Smart Trends
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mt-2 mb-1">Tren Refleksi Mengajar</h1>
            <p class="text-muted mb-0">Analisis pola refleksi pedagogis, frekuensi sesi, dan pertumbuhan kualitas mengajar dari waktu ke waktu.</p>
        </div>
    </div>

    <!-- Teacher Selector (for management roles) -->
    <?php if (!empty($teachers)): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= base_url('smart/reflection-trends') ?>" class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Pilih Guru</label>
                    <select name="teacher_id" class="form-select form-select-sm shadow-sm" onchange="this.form.submit()">
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= (int) $selectedTeacher === (int) $t['id'] ? 'selected' : '' ?>><?= esc($t['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-sm btn-primary shadow-sm w-100"><i data-lucide="search" class="w-3.5 h-3.5 me-1"></i> Tampilkan</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$trends): ?>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body text-center text-muted py-5">
                <i data-lucide="trending-up" class="w-12 h-12 text-muted mb-3 d-inline-block" style="width:48px;height:48px;"></i><br>
                <span class="fw-semibold">Belum ada data refleksi</span><br>
                <small>Mulai gunakan ruang mengajar untuk merekam sesi dan refleksi.</small>
            </div>
        </div>
    <?php else: ?>
        <?php
        $stats = $trends['sessionStats'];
        $growth = $trends['growth'];
        $insights = $trends['reflectionInsights'];
        ?>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card-smart bg-white shadow-sm" style="border-radius:16px; padding:18px;">
                    <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;font-weight:600;">Total Sesi</div>
                    <div class="h3 fw-bold mb-0 text-primary"><?= $stats['total'] ?></div>
                    <div class="text-xs text-muted"><?= $stats['total_reflected'] ?> selesai</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card-smart bg-white shadow-sm" style="border-radius:16px; padding:18px;">
                    <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;font-weight:600;">Tingkat Penyelesaian</div>
                    <div class="h3 fw-bold mb-0 text-success"><?= $stats['completion_rate'] ?>%</div>
                    <div class="text-xs text-muted">sesi yang diselesaikan</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card-smart bg-white shadow-sm" style="border-radius:16px; padding:18px;">
                    <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;font-weight:600;">Refleksi Ditulis</div>
                    <div class="h3 fw-bold mb-0 text-info"><?= $insights['total_reflections'] ?></div>
                    <div class="text-xs text-muted"><?= $insights['challenges'] ?> tantangan dicatat</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card-smart bg-white shadow-sm d-flex flex-column justify-content-center" style="border-radius:16px; padding:18px;">
                    <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;font-weight:600;">Tren Pertumbuhan</div>
                    <?php if ($growth['has_enough_data'] ?? false): ?>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="h3 fw-bold mb-0 text-<?= $growth['trend_color'] ?>">
                                <?= $growth['delta_pct'] > 0 ? '+' : '' ?><?= $growth['delta_pct'] ?>%
                            </span>
                            <span class="trend-badge bg-<?= $growth['trend_color'] ?>-subtle text-<?= $growth['trend_color'] ?>">
                                <i data-lucide="<?= $growth['trend'] === 'improving' ? 'trending-up' : ($growth['trend'] === 'declining' ? 'trending-down' : 'minus') ?>" class="w-3 h-3"></i>
                                <?= $growth['trend_label'] ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="text-xs text-muted mt-1"><?= esc($growth['message'] ?? '') ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Weekly Frequency Chart -->
        <?php if (!empty($trends['weeklyFrequency']['categories'])): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-transparent border-0 pt-4 pb-0 px-4">
                <h6 class="fw-bold text-gray-800 mb-0"><i data-lucide="bar-chart" class="w-4 h-4 me-2 d-inline-block"></i>Frekuensi Sesi Mingguan</h6>
            </div>
            <div class="card-body">
                <div id="weeklyChart" style="min-height: 280px;"></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Insights Grid -->
        <div class="row g-3 mb-4">
            <!-- Achievements -->
            <div class="col-md-4">
                <div class="insight-card shadow-sm bg-white h-100">
                    <div class="insight-header bg-success-subtle text-success">
                        <i data-lucide="thumbs-up" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Hal yang Berjalan Baik
                    </div>
                    <?php if (empty($insights['recent_achievements'])): ?>
                        <div class="insight-item text-muted">Belum ada catatan pencapaian.</div>
                    <?php else: ?>
                        <?php foreach ($insights['recent_achievements'] as $text): ?>
                            <div class="insight-item"><?= esc(mb_strimwidth($text, 0, 120, '…')) ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Challenges -->
            <div class="col-md-4">
                <div class="insight-card shadow-sm bg-white h-100">
                    <div class="insight-header bg-warning-subtle text-warning">
                        <i data-lucide="alert-circle" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Tantangan / Perbaikan
                    </div>
                    <?php if (empty($insights['recent_challenges'])): ?>
                        <div class="insight-item text-muted">Belum ada catatan tantangan.</div>
                    <?php else: ?>
                        <?php foreach ($insights['recent_challenges'] as $text): ?>
                            <div class="insight-item"><?= esc(mb_strimwidth($text, 0, 120, '…')) ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Follow-ups -->
            <div class="col-md-4">
                <div class="insight-card shadow-sm bg-white h-100">
                    <div class="insight-header bg-info-subtle text-info">
                        <i data-lucide="arrow-right-circle" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Rencana Tindak Lanjut
                    </div>
                    <?php if (empty($insights['recent_follow_ups'])): ?>
                        <div class="insight-item text-muted">Belum ada rencana tindak lanjut.</div>
                    <?php else: ?>
                        <?php foreach ($insights['recent_follow_ups'] as $text): ?>
                            <div class="insight-item"><?= esc(mb_strimwidth($text, 0, 120, '…')) ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Subject Distribution -->
        <?php if (!empty($trends['subjectDistribution'])): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-transparent border-0 pt-4 pb-0 px-4">
                <h6 class="fw-bold text-gray-800 mb-0"><i data-lucide="pie-chart" class="w-4 h-4 me-2 d-inline-block"></i>Distribusi Sesi per Mapel</h6>
            </div>
            <div class="card-body">
                <div id="subjectChart" style="min-height: 260px;"></div>
            </div>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('additional_js') ?>
<?php if ($trends): ?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Weekly frequency chart
    const weeklyData = <?= json_encode($trends['weeklyFrequency'] ?? ['categories'=>[], 'series'=>[]]) ?>;
    if (weeklyData.categories.length > 0 && document.getElementById('weeklyChart')) {
        new ApexCharts(document.getElementById('weeklyChart'), {
            chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
            series: weeklyData.series,
            xaxis: { categories: weeklyData.categories, labels: { style: { fontSize: '11px' } } },
            yaxis: { title: { text: 'Jumlah Sesi' }, min: 0, forceNiceScale: true },
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 100] } },
            colors: weeklyData.series.map(s => s.color),
            legend: { position: 'bottom' },
            tooltip: { y: { formatter: val => val + ' sesi' } },
        }).render();
    }

    // Subject distribution donut
    const subjectData = <?= json_encode($trends['subjectDistribution'] ?? []) ?>;
    const subjectLabels = Object.keys(subjectData);
    const subjectValues = Object.values(subjectData);
    if (subjectLabels.length > 0 && document.getElementById('subjectChart')) {
        new ApexCharts(document.getElementById('subjectChart'), {
            chart: { type: 'donut', height: 260, fontFamily: 'Inter, sans-serif' },
            series: subjectValues,
            labels: subjectLabels,
            colors: ['#6366f1', '#22c55e', '#f59e0b', '#ef4444', '#06b6d4', '#8b5cf6', '#ec4899'],
            legend: { position: 'bottom', fontSize: '12px' },
            plotOptions: { pie: { donut: { size: '55%', labels: { show: true, total: { show: true, label: 'Total Sesi', fontSize: '12px' } } } } },
        }).render();
    }
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>
