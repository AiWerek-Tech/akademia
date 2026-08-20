<?= $this->extend('layouts/admin') ?>

<?= $this->section('additional_css') ?>
<style>
/* ========= Mastery Heatmap Styles ========= */
.heatmap-container { overflow-x: auto; }
.heatmap-table { border-collapse: separate; border-spacing: 2px; }
.heatmap-table th { font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; padding: 8px 6px; }
.heatmap-table td { padding: 0; text-align: center; }
.heatmap-cell {
    width: 44px; height: 44px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 8px; font-size: .7rem; font-weight: 700;
    cursor: pointer; transition: all .2s ease;
    position: relative;
}
.heatmap-cell:hover { transform: scale(1.15); z-index: 10; box-shadow: 0 4px 12px rgba(0,0,0,.2); }
.heatmap-cell[data-result="NEEDS_SUPPORT"] { background: #dc3545; color: #fff; }
.heatmap-cell[data-result="DEVELOPING"]    { background: #ffc107; color: #212529; }
.heatmap-cell[data-result="ACHIEVED"]      { background: #198754; color: #fff; }
.heatmap-cell[data-result="ADVANCED"]      { background: #0d6efd; color: #fff; }
.heatmap-cell[data-result=""]              { background: #f1f3f5; color: #adb5bd; }

.heatmap-student-name { font-size: .82rem; font-weight: 600; white-space: nowrap; max-width: 180px; overflow: hidden; text-overflow: ellipsis; }
.heatmap-student-num  { font-size: .68rem; color: #6c757d; }

.heatmap-legend { display: flex; gap: 16px; flex-wrap: wrap; }
.heatmap-legend-item { display: flex; align-items: center; gap: 6px; font-size: .78rem; }
.heatmap-legend-swatch { width: 18px; height: 18px; border-radius: 5px; }

.progress-ring { width: 48px; height: 48px; }
.progress-ring-text { font-size: .7rem; font-weight: 700; fill: currentColor; }

/* Summary stat cards */
.stat-card-smart { border-radius: 16px; padding: 20px; border: none; }
.stat-card-smart .stat-value { font-size: 1.8rem; font-weight: 800; line-height: 1; }
.stat-card-smart .stat-label { font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #6c757d; }

/* Tooltip popover */
.heatmap-tooltip {
    position: absolute; bottom: calc(100% + 8px); left: 50%; transform: translateX(-50%);
    background: #1e293b; color: #fff; padding: 8px 12px; border-radius: 8px;
    font-size: .72rem; white-space: nowrap; pointer-events: none; opacity: 0;
    transition: opacity .2s; z-index: 20;
}
.heatmap-cell:hover .heatmap-tooltip { opacity: 1; }

@media (max-width: 768px) {
    .heatmap-cell { width: 36px; height: 36px; font-size: .6rem; }
    .heatmap-student-name { max-width: 120px; font-size: .75rem; }
}
</style>
<?= $this->endSection() ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-gradient px-3 py-2 rounded-pill fw-semibold text-xs" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff;">
                    <i data-lucide="grid-3x3" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Smart Heatmap
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mt-2 mb-1">Mastery Heatmap TP</h1>
            <p class="text-muted mb-0">Matriks visual penguasaan Tujuan Pembelajaran seluruh siswa. Klik sel untuk detail.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= base_url('smart/mastery-heatmap') ?>" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Kelas / Rombel</label>
                    <select name="classroom_id" class="form-select form-select-sm shadow-sm">
                        <option value="">Pilih Kelas...</option>
                        <?php foreach ($classrooms as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (int) $selectedClassroom === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Mata Pelajaran</label>
                    <select name="subject_id" class="form-select form-select-sm shadow-sm">
                        <option value="">Pilih Mapel...</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= (int) $selectedSubject === (int) $s['id'] ? 'selected' : '' ?>><?= esc($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary shadow-sm px-4"><i data-lucide="search" class="w-3.5 h-3.5 me-1"></i> Tampilkan</button>
                    <a href="<?= base_url('smart/mastery-heatmap') ?>" class="btn btn-sm btn-outline-secondary shadow-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <?php if (!$heatmapData): ?>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body text-center text-muted py-5">
                <i data-lucide="grid-3x3" class="w-12 h-12 text-muted mb-3 d-inline-block" style="width:48px;height:48px;"></i><br>
                <span class="fw-semibold">Pilih kelas dan mata pelajaran</span><br>
                <small>untuk menampilkan heatmap mastery TP interaktif.</small>
            </div>
        </div>
    <?php else: ?>
        <?php
        $summary = $heatmapData['classSummary'];
        $students = $heatmapData['students'];
        $objectives = $heatmapData['objectives'];
        $matrix = $heatmapData['matrix'];
        $studentSummaries = $heatmapData['studentSummaries'];
        $resultColors = $heatmapData['resultColors'];
        ?>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card-smart bg-white shadow-sm">
                    <div class="stat-label">Tingkat Ketercapaian</div>
                    <div class="stat-value text-success"><?= $summary['achieved_rate'] ?>%</div>
                    <div class="text-xs text-muted mt-1">Tercapai + Melampaui</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card-smart bg-white shadow-sm">
                    <div class="stat-label">Perlu Pendampingan</div>
                    <div class="stat-value text-danger"><?= $summary['needs_support_pct'] ?>%</div>
                    <div class="text-xs text-muted mt-1">dari <?= $summary['assessed'] ?> data</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card-smart bg-white shadow-sm">
                    <div class="stat-label">Sedang Berkembang</div>
                    <div class="stat-value text-warning"><?= $summary['developing_pct'] ?>%</div>
                    <div class="text-xs text-muted mt-1">dari <?= $summary['assessed'] ?> data</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card-smart bg-white shadow-sm">
                    <div class="stat-label">Siswa Dinilai</div>
                    <div class="stat-value text-primary"><?= count($students) ?></div>
                    <div class="text-xs text-muted mt-1"><?= count($objectives) ?> TP dipantau</div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body py-2 px-3">
                <div class="heatmap-legend">
                    <?php foreach ($resultColors as $code => $color): ?>
                        <div class="heatmap-legend-item">
                            <div class="heatmap-legend-swatch" style="background: <?= $color['bg'] ?>"></div>
                            <span><?= $color['label'] ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="heatmap-legend-item">
                        <div class="heatmap-legend-swatch" style="background: #f1f3f5"></div>
                        <span>Belum Dinilai</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Heatmap Matrix -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <div class="heatmap-container">
                    <table class="heatmap-table">
                        <thead>
                            <tr>
                                <th class="text-start" style="min-width:180px; position: sticky; left: 0; background: #fff; z-index: 5;">Siswa</th>
                                <?php foreach ($objectives as $obj): ?>
                                    <th class="text-center" title="<?= esc($obj['name'] ?? '') ?>"><?= esc($obj['code'] ?? 'TP-' . $obj['id']) ?></th>
                                <?php endforeach; ?>
                                <th class="text-center" style="min-width:70px">Capaian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <?php $stSummary = $studentSummaries[$student['id']] ?? []; ?>
                                <tr>
                                    <td style="position: sticky; left: 0; background: #fff; z-index: 4;">
                                        <div class="heatmap-student-name"><?= esc($student['full_name']) ?></div>
                                        <div class="heatmap-student-num"><?= esc($student['student_number'] ?? '') ?></div>
                                    </td>
                                    <?php foreach ($objectives as $obj): ?>
                                        <?php
                                        $cell = $matrix[$student['id']][$obj['id']] ?? ['result' => null];
                                        $result = $cell['result'] ?? '';
                                        $scoreInitial = '';
                                        if ($result === 'NEEDS_SUPPORT') $scoreInitial = 'NS';
                                        elseif ($result === 'DEVELOPING') $scoreInitial = 'D';
                                        elseif ($result === 'ACHIEVED') $scoreInitial = 'A';
                                        elseif ($result === 'ADVANCED') $scoreInitial = 'A+';
                                        else $scoreInitial = '—';
                                        $colorInfo = $resultColors[$result] ?? null;
                                        $tooltipLabel = $colorInfo ? $colorInfo['label'] : 'Belum Dinilai';
                                        ?>
                                        <td>
                                            <div class="heatmap-cell" data-result="<?= esc($result) ?>"
                                                 data-student="<?= (int) $student['id'] ?>"
                                                 data-objective="<?= (int) $obj['id'] ?>"
                                                 title="<?= esc($student['full_name']) ?> — <?= esc($obj['code'] ?? '') ?>: <?= $tooltipLabel ?>">
                                                <?= $scoreInitial ?>
                                                <div class="heatmap-tooltip">
                                                    <?= esc($obj['code'] ?? '') ?>: <?= $tooltipLabel ?>
                                                    <?php if (!empty($cell['updated_at'])): ?>
                                                        <br><small><?= date('d M Y', strtotime($cell['updated_at'])) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="text-center">
                                        <div class="fw-bold text-sm <?= ($stSummary['completion_pct'] ?? 0) >= 75 ? 'text-success' : (($stSummary['completion_pct'] ?? 0) >= 50 ? 'text-warning' : 'text-danger') ?>">
                                            <?= $stSummary['completion_pct'] ?? 0 ?>%
                                        </div>
                                        <div class="text-xs text-muted"><?= $stSummary['achieved_count'] ?? 0 ?>/<?= $stSummary['total_objectives'] ?? 0 ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Distribution Chart -->
        <?php if ($chartData): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-transparent border-0 pt-4 pb-0 px-4">
                <h6 class="fw-bold text-gray-800 mb-0"><i data-lucide="bar-chart-3" class="w-4 h-4 me-2 d-inline-block"></i>Distribusi Mastery per TP</h6>
            </div>
            <div class="card-body">
                <div id="distributionChart" style="min-height: 300px;"></div>
            </div>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('additional_js') ?>
<?php if (!empty($chartData)): ?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartData = <?= json_encode($chartData) ?>;
    if (chartData && document.getElementById('distributionChart')) {
        new ApexCharts(document.getElementById('distributionChart'), {
            chart: { type: 'bar', height: 300, stacked: true, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
            plotOptions: { bar: { horizontal: false, columnWidth: '60%', borderRadius: 4 } },
            series: chartData.series,
            xaxis: { categories: chartData.categories, labels: { style: { fontSize: '11px' } } },
            yaxis: { title: { text: 'Jumlah Siswa' } },
            legend: { position: 'bottom', fontSize: '12px' },
            fill: { opacity: 1 },
            tooltip: { y: { formatter: (val) => val + ' siswa' } },
            colors: chartData.series.map(s => s.color),
        }).render();
    }
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>
