<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<?php
$p = $data['program'];
$summary = $data['summary'];
$rows = $data['rows'];
$dimensions = $data['dimensions'];

// Prepare data for ApexCharts Radar & Donut
$dimensionLabels = [];
$dimensionAverages = [];
$levelDistribution = ['EMERGING' => 0, 'DEVELOPING' => 0, 'PROFICIENT' => 0, 'EXEMPLARY' => 0];

$levelNumericWeight = ['EMERGING' => 1, 'DEVELOPING' => 2, 'PROFICIENT' => 3, 'EXEMPLARY' => 4];

foreach ($summary as $s) {
    $dimensionLabels[] = $s['name'];
    $totalWeight = 0;
    $totalCount = 0;
    $levels = ['EMERGING', 'DEVELOPING', 'PROFICIENT', 'EXEMPLARY'];
    foreach ($levels as $idx => $lvl) {
        $cnt = (int) ($s['distribution'][$lvl] ?? 0);
        $levelDistribution[$lvl] += $cnt;
        $totalWeight += $cnt * $levelNumericWeight[$lvl];
        $totalCount += $cnt;
    }
    $avgScore = $totalCount > 0 ? round(($totalWeight / $totalCount) * 25, 1) : 0; // scale 0-100%
    $dimensionAverages[] = $avgScore;
}
?>
<div class="container-fluid px-0 px-md-3">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-purple-subtle text-purple rounded-pill fw-semibold text-xs text-uppercase tracking-wider">
                    <i data-lucide="sparkles" class="w-3 h-3 me-1 d-inline-block"></i> Phase 7 Kokurikuler & Karakter
                </span>
                <span class="badge bg-light text-dark border"><?= esc($p['program_type']) ?></span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mt-2 mb-1">Laporan Capaian Profil & Karakter</h1>
            <p class="text-muted mb-0"><?= esc($p['title']) ?> · <?= esc($p['period_name'] ?? 'Periode Aktif') ?><?= $p['theme'] ? ' · Tema: ' . esc($p['theme']) : '' ?></p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= base_url('cocurricular/' . (int) $p['id'] . '?tab=results') ?>" class="btn btn-outline-primary shadow-sm">
                <i data-lucide="grid-3x3" class="w-4 h-4 me-1"></i> Edit Hasil
            </a>
            <button type="button" class="btn btn-outline-dark shadow-sm" onclick="window.print()">
                <i data-lucide="printer" class="w-4 h-4 me-1"></i> Cetak Laporan
            </button>
            <a href="<?= base_url('cocurricular/' . (int) $p['id']) ?>" class="btn btn-outline-secondary shadow-sm">
                <i data-lucide="arrow-left" class="w-4 h-4 me-1"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Top KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center h-100">
                <div class="card-body py-3">
                    <div class="text-xs text-muted text-uppercase tracking-wider fw-semibold mb-1">Peserta Didik</div>
                    <div class="text-3xl fw-bold text-primary"><?= count($rows) ?></div>
                    <div class="text-xs text-muted mt-1">Siswa Terdaftar</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center h-100">
                <div class="card-body py-3">
                    <div class="text-xs text-muted text-uppercase tracking-wider fw-semibold mb-1">Dimensi Sasaran</div>
                    <div class="text-3xl fw-bold text-success"><?= count($dimensions) ?></div>
                    <div class="text-xs text-muted mt-1">Dimensi Profil Lulusan</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center h-100">
                <div class="card-body py-3">
                    <div class="text-xs text-muted text-uppercase tracking-wider fw-semibold mb-1">Bukti Portofolio</div>
                    <div class="text-3xl fw-bold text-warning"><?= array_sum(array_column($rows, 'evidences')) ?></div>
                    <div class="text-xs text-muted mt-1">Artefak / Bukti Fisik</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center h-100">
                <div class="card-body py-3">
                    <div class="text-xs text-muted text-uppercase tracking-wider fw-semibold mb-1">Indeks Mutu IPOO</div>
                    <div class="text-3xl fw-bold text-purple"><?= $ipooHealth['overall_percent'] ?? 85 ?>%</div>
                    <div class="text-xs text-muted mt-1"><span class="badge <?= $ipooHealth['status']['class'] ?? 'bg-success' ?> rounded-pill"><?= $ipooHealth['status']['label'] ?? 'SEHAT' ?></span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Visual Analytics: Radar & Donut Charts -->
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h6 class="fw-bold mb-0 text-gray-900"><i data-lucide="compass" class="w-4 h-4 me-1 text-purple"></i> Radar Kekuatan Dimensi Profil</h6>
                            <small class="text-muted">Rata-rata tingkat capaian karakter sekelas (%)</small>
                        </div>
                        <span class="badge bg-purple-subtle text-purple px-2 py-1 rounded-pill text-xs">ApexCharts Radar</span>
                    </div>
                    <?php if ($dimensionLabels !== []): ?>
                        <div id="radarDimensionsChart" style="min-height: 320px;"></div>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">Belum ada data dimensi untuk divisualisasikan.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h6 class="fw-bold mb-0 text-gray-900"><i data-lucide="pie-chart" class="w-4 h-4 me-1 text-purple"></i> Distribusi Tingkat Capaian</h6>
                            <small class="text-muted">Komposisi level profil seluruh siswa</small>
                        </div>
                    </div>
                    <?php if (array_sum($levelDistribution) > 0): ?>
                        <div id="donutDistributionChart" style="min-height: 250px;"></div>
                        <div class="row g-2 mt-2 pt-2 border-top text-center text-xs">
                            <div class="col-3"><span class="badge bg-danger-subtle text-danger d-block py-1">MB: <?= $levelDistribution['EMERGING'] ?></span></div>
                            <div class="col-3"><span class="badge bg-warning-subtle text-warning d-block py-1">SB: <?= $levelDistribution['DEVELOPING'] ?></span></div>
                            <div class="col-3"><span class="badge bg-info-subtle text-info d-block py-1">BSH: <?= $levelDistribution['PROFICIENT'] ?></span></div>
                            <div class="col-3"><span class="badge bg-success-subtle text-success d-block py-1">SAB: <?= $levelDistribution['EXEMPLARY'] ?></span></div>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">Belum ada hasil penilaian siswa.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive P5 Narrative Drafter Section -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                <div>
                    <h6 class="fw-bold mb-1 text-gray-900">
                        <i data-lucide="sparkles" class="w-4 h-4 me-1 text-purple"></i> ✨ Asisten Narasi Rapor Kokurikuler / P5
                    </h6>
                    <p class="text-muted text-xs mb-0">Draf narasi deskriptif kualitatif rapor otomatis yang merangkum kekuatan karakter dan saran tindak lanjut tiap siswa.</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-purple shadow-sm" id="btnCopyAllNarratives">
                        <i data-lucide="copy" class="w-3.5 h-3.5 me-1"></i> Salin Semua Narasi
                    </button>
                </div>
            </div>

            <div class="row g-3" id="narrativesList">
                <?php if ($rows === []): ?>
                    <div class="col-12 text-center text-muted py-4">Belum ada data siswa untuk menyusun narasi.</div>
                <?php else: ?>
                    <?php foreach ($rows as $idx => $row): ?>
                        <?php
                        $st = $row['student'];
                        $service = new \App\Services\CocurricularService();
                        $narrativeText = $service->generateStudentNarrative((int) $p['id'], (int) $st['id']);
                        ?>
                        <div class="col-md-6 narrative-card-item">
                            <div class="card border rounded-3 p-3 bg-light-subtle h-100 shadow-none">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="fw-bold text-gray-900 small"><?= esc($st['full_name']) ?></span>
                                        <span class="text-xs text-muted d-block"><?= esc($st['classroom_name'] ?? '') ?> · NIS: <?= esc($st['student_number'] ?? '-') ?></span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 text-xs btn-copy-single" data-narrative="<?= esc($narrativeText, 'attr') ?>" title="Salin Narasi">
                                        <i data-lucide="copy" class="w-3 h-3 me-1"></i> Salin
                                    </button>
                                </div>
                                <p class="small text-muted mb-0 lh-base narrative-content" style="font-size: 0.82rem;">
                                    <?= nl2br(esc($narrativeText)) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Student Matrix Table -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                <h6 class="fw-bold mb-0 text-gray-900"><i data-lucide="table" class="w-4 h-4 me-1 text-purple"></i> Matriks Nilai & Portofolio Siswa</h6>
                <div class="d-flex gap-2">
                    <input type="text" id="filterStudentInput" class="form-control form-control-sm shadow-sm" placeholder="Cari nama siswa..." style="max-width: 220px;">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="reportMatrixTable">
                    <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                        <tr>
                            <th class="px-3 py-3" style="min-width: 180px;">Siswa</th>
                            <?php foreach ($dimensions as $d): ?>
                                <th class="px-2 py-3 text-center" title="<?= esc($d['name']) ?>"><?= esc($d['code'] ?? substr($d['name'], 0, 18)) ?></th>
                            <?php endforeach; ?>
                            <th class="px-3 py-3 text-center">Bukti</th>
                            <th class="px-3 py-3 text-center">Formatif</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rows === []): ?>
                            <tr><td colspan="<?= count($dimensions) + 3 ?>" class="text-center text-muted py-5">Belum ada siswa.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <tr class="student-matrix-row">
                                    <td class="px-3 py-3">
                                        <div class="fw-semibold small student-name"><?= esc($row['student']['full_name']) ?></div>
                                        <div class="text-xs text-muted"><?= esc($row['student']['classroom_name'] ?? '') ?> · NIS <?= esc($row['student']['student_number'] ?? '') ?></div>
                                    </td>
                                    <?php foreach ($row['dimensions'] as $cell): ?>
                                        <td class="px-2 py-3 text-center">
                                            <span class="badge <?= match ($cell['level']) {
                                                'EMERGING' => 'bg-danger-subtle text-danger',
                                                'DEVELOPING' => 'bg-warning-subtle text-warning',
                                                'PROFICIENT' => 'bg-info-subtle text-info',
                                                default => 'bg-success-subtle text-success',
                                            } ?>"><?= esc($cell['level']) ?></span>
                                            <?php if ($cell['note']): ?>
                                                <div class="text-xs text-muted mt-0.5" title="<?= esc($cell['note']) ?>">
                                                    <i data-lucide="message-square" class="w-2.5 h-2.5 d-inline-block text-secondary"></i>
                                                    <?= esc(mb_strimwidth($cell['note'], 0, 18, '…')) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="px-3 py-3 text-center">
                                        <span class="badge bg-light text-dark border"><?= (int) $row['evidences'] ?></span>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <span class="badge bg-light text-dark border"><?= (int) $row['observations'] ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container for Copy Feedback -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
    <div id="cocurricularToast" class="toast align-items-center text-bg-dark border-0 rounded-4 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 text-success"></i>
                <span id="toastMessage">Teks berhasil disalin ke clipboard!</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- ApexCharts Script Initialization -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // 1. Radar Chart for Dimensions
    const dimensionLabels = <?= json_encode($dimensionLabels) ?>;
    const dimensionAverages = <?= json_encode($dimensionAverages) ?>;

    if (dimensionLabels.length > 0 && document.getElementById('radarDimensionsChart')) {
        const radarOptions = {
            series: [{
                name: 'Kekuatan Capaian (%)',
                data: dimensionAverages
            }],
            chart: {
                height: 320,
                type: 'radar',
                toolbar: { show: false }
            },
            colors: ['#7c3aed'],
            markers: { size: 4, colors: ['#7c3aed'], strokeColor: '#fff', strokeWidth: 2 },
            xaxis: {
                categories: dimensionLabels,
                labels: {
                    style: { fontSize: '11px', fontWeight: 500 }
                }
            },
            yaxis: {
                show: false,
                min: 0,
                max: 100
            },
            fill: {
                opacity: 0.25
            }
        };
        new ApexCharts(document.getElementById('radarDimensionsChart'), radarOptions).render();
    }

    // 2. Donut Chart for Level Distribution
    const levelCounts = [
        <?= (int) $levelDistribution['EMERGING'] ?>,
        <?= (int) $levelDistribution['DEVELOPING'] ?>,
        <?= (int) $levelDistribution['PROFICIENT'] ?>,
        <?= (int) $levelDistribution['EXEMPLARY'] ?>
    ];

    if (document.getElementById('donutDistributionChart') && levelCounts.reduce((a, b) => a + b, 0) > 0) {
        const donutOptions = {
            series: levelCounts,
            chart: {
                type: 'donut',
                height: 250
            },
            labels: ['Mulai Berkembang (MB)', 'Sedang Berkembang (SB)', 'Berkembang Sesuai Harapan (BSH)', 'Sangat Berkembang (SAB)'],
            colors: ['#ef4444', '#f59e0b', '#06b6d4', '#10b981'],
            legend: { position: 'bottom', fontSize: '11px' },
            dataLabels: { enabled: true }
        };
        new ApexCharts(document.getElementById('donutDistributionChart'), donutOptions).render();
    }

    // 3. Search Filter in Student Matrix Table
    const searchInput = document.getElementById('filterStudentInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            document.querySelectorAll('.student-matrix-row').forEach(row => {
                const name = row.querySelector('.student-name')?.textContent.toLowerCase() || '';
                row.style.display = name.includes(query) ? '' : 'none';
            });
        });
    }

    // 4. Clipboard Copy Single Narrative
    const toastEl = document.getElementById('cocurricularToast');
    const toast = toastEl ? new bootstrap.Toast(toastEl, { delay: 2500 }) : null;

    function showToast(msg) {
        if (toastEl && toast) {
            document.getElementById('toastMessage').textContent = msg;
            toast.show();
        }
    }

    document.querySelectorAll('.btn-copy-single').forEach(btn => {
        btn.addEventListener('click', function() {
            const text = this.getAttribute('data-narrative');
            if (text) {
                navigator.clipboard.writeText(text).then(() => {
                    showToast('Draf narasi rapor berhasil disalin!');
                });
            }
        });
    });

    // 5. Copy All Narratives
    const btnCopyAll = document.getElementById('btnCopyAllNarratives');
    if (btnCopyAll) {
        btnCopyAll.addEventListener('click', function() {
            const allItems = [];
            document.querySelectorAll('.narrative-card-item').forEach(card => {
                const name = card.querySelector('.fw-bold')?.textContent.trim() || '';
                const text = card.querySelector('.narrative-content')?.textContent.trim() || '';
                if (name && text) {
                    allItems.push(`[${name}]\n${text}\n`);
                }
            });
            if (allItems.length > 0) {
                navigator.clipboard.writeText(allItems.join('\n---\n\n')).then(() => {
                    showToast('Semua draf narasi kelas berhasil disalin!');
                });
            }
        });
    }
});
</script>
<?= $this->endSection() ?>