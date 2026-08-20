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
        <?php if ($heatmapData): ?>
        <div>
            <button type="button" class="btn btn-sm btn-outline-success shadow-sm rounded-pill px-3" onclick="exportHeatmapToCsv()">
                <i data-lucide="download" class="w-3.5 h-3.5 me-1"></i> Ekspor CSV
            </button>
        </div>
        <?php endif; ?>
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
            <div class="card-header bg-transparent border-0 pt-3 pb-0 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="btn-group btn-group-sm rounded-pill p-1 bg-light border shadow-none" role="group">
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 py-1 fw-semibold filter-btn" data-filter="ALL" onclick="filterHeatmap('ALL', this)">Semua Siswa (<?= count($students) ?>)</button>
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-3 py-1 text-danger fw-semibold filter-btn" data-filter="LOW" onclick="filterHeatmap('LOW', this)">&lt; 50% Tercapai</button>
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-3 py-1 text-success fw-semibold filter-btn" data-filter="HIGH" onclick="filterHeatmap('HIGH', this)">&ge; 75% Tercapai</button>
                </div>
                <div class="text-xs text-muted">
                    <i data-lucide="mouse-pointer-click" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Klik sel untuk membuka menu aksi cepat siswa & TP
                </div>
            </div>
            <div class="card-body p-3">
                <div class="heatmap-container">
                    <table class="heatmap-table" id="heatmapMainTable">
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
                                <?php 
                                $stSummary = $studentSummaries[$student['id']] ?? []; 
                                $completionPct = (int)($stSummary['completion_pct'] ?? 0);
                                ?>
                                <tr class="student-row" data-completion="<?= $completionPct ?>">
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
                                                 data-student-name="<?= esc($student['full_name']) ?>"
                                                 data-objective="<?= (int) $obj['id'] ?>"
                                                 data-objective-code="<?= esc($obj['code'] ?? 'TP-' . $obj['id']) ?>"
                                                 data-objective-name="<?= esc($obj['name'] ?? '') ?>"
                                                 data-result-label="<?= esc($tooltipLabel) ?>"
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
                                        <div class="fw-bold text-sm <?= $completionPct >= 75 ? 'text-success' : ($completionPct >= 50 ? 'text-warning' : 'text-danger') ?>">
                                            <?= $completionPct ?>%
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

        <!-- Cell Drilldown Modal -->
        <div class="modal fade" id="heatmapCellModal" tabindex="-1" aria-labelledby="heatmapCellModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-0 bg-light rounded-top-4 p-4 pb-2">
                        <div>
                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1 fw-bold text-xs" id="drillModalObjCode">TP-01</span>
                            <h5 class="modal-title fw-bold text-dark mt-1 mb-0" id="drillModalStudentName">Nama Siswa</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 pt-3">
                        <div class="p-3 rounded-3 bg-light border mb-3">
                            <div class="text-xs text-muted fw-semibold mb-1">Tujuan Pembelajaran:</div>
                            <div class="small text-dark fw-medium" id="drillModalObjName">-</div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="text-xs text-muted fw-semibold">Status Ketercapaian:</span>
                            <span class="badge px-3 py-1.5 rounded-pill fw-bold" id="drillModalStatusBadge">Status</span>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="#" id="drillModalRemedialBtn" class="btn btn-warning rounded-pill py-2 d-flex align-items-center justify-content-center gap-2 text-dark fw-semibold shadow-sm">
                                <i data-lucide="heart-handshake" style="width: 16px; height: 16px;"></i>
                                <span>Buat Paket Remedial Siswa Ini</span>
                            </a>
                            <a href="#" id="drillModalNarrativeBtn" class="btn btn-outline-primary rounded-pill py-2 d-flex align-items-center justify-content-center gap-2">
                                <i data-lucide="file-text" style="width: 16px; height: 16px;"></i>
                                <span>Susun Draf Narasi Rapor</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('additional_js') ?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($chartData)): ?>
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
    <?php endif; ?>

    // Cell Click Drill-Down Modal
    const cellModalEl = document.getElementById('heatmapCellModal');
    let cellModal = null;
    if (cellModalEl) {
        cellModal = new bootstrap.Modal(cellModalEl);
    }

    document.querySelectorAll('.heatmap-cell').forEach(cell => {
        cell.addEventListener('click', function() {
            const studentId = this.dataset.student;
            const studentName = this.dataset.studentName;
            const objId = this.dataset.objective;
            const objCode = this.dataset.objectiveCode;
            const objName = this.dataset.objectiveName;
            const result = this.dataset.result;
            const resultLabel = this.dataset.resultLabel;

            if (!studentId || !objId || !cellModal) return;

            document.getElementById('drillModalObjCode').textContent = objCode || 'TP';
            document.getElementById('drillModalStudentName').textContent = studentName || 'Siswa';
            document.getElementById('drillModalObjName').textContent = objName || 'Tujuan Pembelajaran';

            const badge = document.getElementById('drillModalStatusBadge');
            badge.textContent = resultLabel;
            const colorMap = {
                'NEEDS_SUPPORT': 'bg-danger text-white',
                'DEVELOPING': 'bg-warning text-dark',
                'ACHIEVED': 'bg-success text-white',
                'ADVANCED': 'bg-primary text-white'
            };
            badge.className = `badge px-3 py-1.5 rounded-pill fw-bold ${colorMap[result] || 'bg-secondary text-white'}`;

            document.getElementById('drillModalRemedialBtn').href = `<?= base_url('smart/remedial-package') ?>?student_id=${studentId}&objective_id=${objId}`;
            document.getElementById('drillModalNarrativeBtn').href = `<?= base_url('smart/narrative-drafter') ?>?classroom_id=<?= $selectedClassroom ?>&subject_id=<?= $selectedSubject ?>&student_id=${studentId}`;

            cellModal.show();
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    });
});

// Quick Filtering
function filterHeatmap(type, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => {
        b.classList.remove('btn-primary', 'text-white');
        b.classList.add('btn-light');
    });
    btn.classList.remove('btn-light');
    btn.classList.add('btn-primary', 'text-white');

    const rows = document.querySelectorAll('.student-row');
    rows.forEach(row => {
        const pct = parseInt(row.dataset.completion, 10) || 0;
        if (type === 'ALL') {
            row.style.display = '';
        } else if (type === 'LOW') {
            row.style.display = pct < 50 ? '' : 'none';
        } else if (type === 'HIGH') {
            row.style.display = pct >= 75 ? '' : 'none';
        }
    });
}

// Export CSV
function exportHeatmapToCsv() {
    const table = document.getElementById('heatmapMainTable');
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    rows.forEach(row => {
        if (row.style.display === 'none') return;
        const cols = row.querySelectorAll('th, td');
        let rowData = [];
        cols.forEach(col => {
            // Get clean text without tooltip
            let text = col.innerText.replace(/[\n\r]+/g, ' ').replace(/\s+/g, ' ').trim();
            // Escape double quotes
            text = '"' + text.replace(/"/g, '""') + '"';
            rowData.push(text);
        });
        csv.push(rowData.join(','));
    });

    const csvFile = new Blob(["\uFEFF" + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const downloadLink = document.createElement('a');
    downloadLink.download = `mastery_heatmap_kelas_<?= $selectedClassroom ?>_mapel_<?= $selectedSubject ?>_${new Date().toISOString().slice(0,10)}.csv`;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>
<?= $this->endSection() ?>
