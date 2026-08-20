<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<?php
$ipoo = $ipooHealth ?? [
    'overall_score'   => 3.5,
    'overall_percent' => 70,
    'status'          => ['label' => 'BAIK', 'class' => 'bg-primary text-white'],
];
$attendanceStats = $attStats ?? [
    'overall_attendance_pct' => 85,
    'total_sessions'         => 0,
    'active_members'         => count($reports),
];

// Calculate grade distribution for Donut Chart
$gradeCounts = ['Sangat Baik (A)' => 0, 'Baik (B)' => 0, 'Cukup (C)' => 0, 'Perlu Bimbingan (D)' => 0];
foreach ($reports as $r) {
    $rate = (float) $r['attendance_rate'];
    if ($rate >= 85) {
        $gradeCounts['Sangat Baik (A)']++;
    } elseif ($rate >= 70) {
        $gradeCounts['Baik (B)']++;
    } elseif ($rate >= 50) {
        $gradeCounts['Cukup (C)']++;
    } else {
        $gradeCounts['Perlu Bimbingan (D)']++;
    }
}
?>
<div class="container-fluid px-0 px-md-3">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= base_url('extracurricular/' . $program['id']) ?>"><?= esc($program['title']) ?></a></li>
                    <li class="breadcrumb-item active">Laporan Kualitatif</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-gray-900 mb-0">Laporan & Evaluasi Kualitatif Siswa</h1>
            <p class="text-muted mb-0">Rekapitulasi kehadiran, rekam jejak kompetensi, draf deskripsi rapor ekstrakurikuler, dan evaluasi capaian minat bakat.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-dark shadow-sm" onclick="window.print()">
                <i data-lucide="printer" class="w-4 h-4 me-1"></i> Cetak Laporan
            </button>
            <a href="<?= base_url('extracurricular/' . $program['id']) ?>" class="btn btn-outline-secondary shadow-sm">
                <i data-lucide="arrow-left" class="w-4 h-4 me-1"></i> Kembali ke Program
            </a>
        </div>
    </div>

    <!-- Top KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center h-100">
                <div class="card-body py-3">
                    <div class="text-xs text-muted text-uppercase tracking-wider fw-semibold mb-1">Anggota Aktif</div>
                    <div class="text-3xl fw-bold text-primary"><?= count($reports) ?></div>
                    <div class="text-xs text-muted mt-1">Siswa Terdaftar</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center h-100">
                <div class="card-body py-3">
                    <div class="text-xs text-muted text-uppercase tracking-wider fw-semibold mb-1">Rata-rata Kehadiran</div>
                    <div class="text-3xl fw-bold text-success"><?= $attendanceStats['overall_attendance_pct'] ?>%</div>
                    <div class="text-xs text-muted mt-1">Tingkat Presensi Latihan</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center h-100">
                <div class="card-body py-3">
                    <div class="text-xs text-muted text-uppercase tracking-wider fw-semibold mb-1">Total Prestasi</div>
                    <div class="text-3xl fw-bold text-warning">
                        <?= array_sum(array_map(fn($r) => count($r['achievements']), $reports)) ?>
                    </div>
                    <div class="text-xs text-muted mt-1">Lencana & Penghargaan</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center h-100">
                <div class="card-body py-3">
                    <div class="text-xs text-muted text-uppercase tracking-wider fw-semibold mb-1">Indeks Mutu IPOO</div>
                    <div class="text-3xl fw-bold text-purple"><?= $ipoo['overall_percent'] ?>%</div>
                    <div class="text-xs text-muted mt-1"><span class="badge <?= $ipoo['status']['class'] ?> rounded-pill"><?= $ipoo['status']['label'] ?></span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Visual Analytics: Donut Chart Distribution -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h6 class="fw-bold mb-0 text-gray-900"><i data-lucide="pie-chart" class="w-4 h-4 me-1 text-purple"></i> Distribusi Predikat Capaian</h6>
                            <small class="text-muted">Berdasarkan kedisiplinan dan partisipasi latihan</small>
                        </div>
                    </div>
                    <?php if (array_sum($gradeCounts) > 0): ?>
                        <div id="extracurricularDonutChart" style="min-height: 250px;"></div>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">Belum ada data anggota untuk divisualisasikan.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3 text-gray-900"><i data-lucide="award" class="w-4 h-4 me-1 text-purple"></i> Pedoman Predikat Rapor Ekstrakurikuler</h6>
                    <div class="list-group list-group-flush small">
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-bottom">
                            <div><span class="badge bg-success-subtle text-success me-2">Sangat Baik (A)</span> Presensi ≥ 85% & berprestasi/menguasai kompetensi unggul</div>
                            <span class="fw-bold"><?= $gradeCounts['Sangat Baik (A)'] ?> Siswa</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-bottom">
                            <div><span class="badge bg-primary-subtle text-primary me-2">Baik (B)</span> Presensi 70% – 84% & konsisten mengikuti latihan</div>
                            <span class="fw-bold"><?= $gradeCounts['Baik (B)'] ?> Siswa</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-bottom">
                            <div><span class="badge bg-warning-subtle text-warning me-2">Cukup (C)</span> Presensi 50% – 69% & berpartisipasi cukup baik</div>
                            <span class="fw-bold"><?= $gradeCounts['Cukup (C)'] ?> Siswa</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <div><span class="badge bg-danger-subtle text-danger me-2">Perlu Bimbingan (D)</span> Presensi < 50% & perlu pendampingan motivasi</div>
                            <span class="fw-bold"><?= $gradeCounts['Perlu Bimbingan (D)'] ?> Siswa</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ✨ Asisten Draf Narasi Rapor Ekstrakurikuler -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                <div>
                    <h6 class="fw-bold mb-1 text-gray-900">
                        <i data-lucide="sparkles" class="w-4 h-4 me-1 text-purple"></i> ✨ Asisten Narasi Rapor Ekstrakurikuler (Kualitatif)
                    </h6>
                    <p class="text-muted text-xs mb-0">Draf narasi rapor resmi siap pakai yang merangkum keaktifan, kepemimpinan, dan pencapaian tiap peserta didik.</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-purple shadow-sm" id="btnCopyAllExtraNarratives">
                        <i data-lucide="copy" class="w-3.5 h-3.5 me-1"></i> Salin Semua Narasi
                    </button>
                </div>
            </div>

            <div class="row g-3" id="extraNarrativesList">
                <?php if (empty($reports)): ?>
                    <div class="col-12 text-center text-muted py-4">Belum ada anggota terdaftar untuk menyusun narasi.</div>
                <?php else: ?>
                    <?php foreach ($reports as $r): ?>
                        <?php
                        $st = $r['member'];
                        $service = new \App\Services\ExtracurricularService();
                        $narrativeText = $service->generateStudentNarrative((int) $program['id'], (int) $st['student_id']);
                        ?>
                        <div class="col-md-6 extra-narrative-card">
                            <div class="card border rounded-3 p-3 bg-light-subtle h-100 shadow-none">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="fw-bold text-gray-900 small"><?= esc($st['student_name']) ?></span>
                                        <span class="text-xs text-muted d-block"><?= esc($st['classroom_name'] ?? '') ?> · Peran: <?= esc($st['role'] ?? 'MEMBER') ?></span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 text-xs btn-copy-single-extra" data-narrative="<?= esc($narrativeText, 'attr') ?>" title="Salin Narasi">
                                        <i data-lucide="copy" class="w-3 h-3 me-1"></i> Salin
                                    </button>
                                </div>
                                <p class="small text-muted mb-0 lh-base extra-narrative-content" style="font-size: 0.82rem;">
                                    <?= nl2br(esc($narrativeText)) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Student Reports Table -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                <h6 class="fw-bold mb-0 text-gray-900"><i data-lucide="table" class="w-4 h-4 me-1 text-purple"></i> Matriks Capaian & Rekapitulasi Anggota</h6>
                <div class="d-flex gap-2">
                    <input type="text" id="filterExtraReportStudent" class="form-control form-control-sm shadow-sm" placeholder="Cari nama siswa..." style="max-width: 220px;">
                    <button type="button" class="btn btn-sm btn-outline-secondary shadow-sm" id="btnExportExtraCsv">
                        <i data-lucide="download" class="w-3.5 h-3.5 me-1"></i> Ekspor CSV
                    </button>
                </div>
            </div>

            <?php if (empty($reports)): ?>
                <div class="text-center py-5">
                    <i data-lucide="file-text" class="text-muted mb-3" style="width:48px;height:48px"></i>
                    <p class="text-muted mb-0">Belum ada data laporan. Pastikan program memiliki anggota aktif.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="extraReportsTable">
                        <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                            <tr>
                                <th class="ps-3 py-3">Siswa & Peran</th>
                                <th class="py-3">Kelas</th>
                                <th class="text-center py-3">Kehadiran</th>
                                <th class="text-center py-3">Predikat</th>
                                <th class="text-center py-3">Prestasi</th>
                                <th class="text-center py-3">Kompetensi</th>
                                <th class="text-end pe-3 py-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $r): ?>
                                <?php
                                $rate = (float) $r['attendance_rate'];
                                $predikatBadge = match(true) {
                                    $rate >= 85 => ['label' => 'Sangat Baik (A)', 'class' => 'bg-success-subtle text-success'],
                                    $rate >= 70 => ['label' => 'Baik (B)', 'class' => 'bg-primary-subtle text-primary'],
                                    $rate >= 50 => ['label' => 'Cukup (C)', 'class' => 'bg-warning-subtle text-warning'],
                                    default     => ['label' => 'Kurang (D)', 'class' => 'bg-danger-subtle text-danger'],
                                };
                                ?>
                                <tr class="extra-report-row">
                                    <td class="ps-3 py-3">
                                        <div class="fw-bold text-gray-900 report-student-name"><?= esc($r['member']['student_name'] ?? '—') ?></div>
                                        <div class="text-xs text-muted"><?= esc($r['member']['role'] ?? 'MEMBER') ?></div>
                                    </td>
                                    <td class="py-3 small"><?= esc($r['member']['classroom_name'] ?? '—') ?></td>
                                    <td class="text-center py-3">
                                        <span class="fw-bold <?= $r['attendance_rate'] >= 75 ? 'text-success' : ($r['attendance_rate'] >= 50 ? 'text-warning' : 'text-danger') ?>"><?= $r['attendance_rate'] ?>%</span>
                                        <div class="text-muted" style="font-size:.7rem"><?= $r['present_count'] ?>H <?= $r['late_count'] ?>T <?= $r['absent_count'] ?>A <?= $r['excused_count'] ?>I</div>
                                    </td>
                                    <td class="text-center py-3">
                                        <span class="badge <?= $predikatBadge['class'] ?> rounded-pill px-2.5 py-1 text-xs"><?= $predikatBadge['label'] ?></span>
                                    </td>
                                    <td class="text-center py-3">
                                        <span class="fw-bold text-primary"><?= count($r['achievements']) ?></span>
                                        <div class="text-muted" style="font-size:.7rem">pencapaian</div>
                                    </td>
                                    <td class="text-center py-3">
                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                            <div class="progress flex-grow-1" style="height:6px;max-width:60px">
                                                <div class="progress-bar bg-primary" style="width:<?= $r['competency_coverage'] ?>%"></div>
                                            </div>
                                            <span class="text-muted small"><?= $r['achieved_competencies'] ?>/<?= $r['total_competencies'] ?></span>
                                        </div>
                                    </td>
                                    <td class="text-end pe-3 py-3">
                                        <a href="<?= base_url('extracurricular/' . $program['id'] . '/report/' . $r['member']['student_id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            Profil Rapor
                                        </a>
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

<!-- Toast Container for Clipboard Notification -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
    <div id="extraToast" class="toast align-items-center text-bg-dark border-0 rounded-4 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 text-success"></i>
                <span id="extraToastMessage">Teks berhasil disalin ke clipboard!</span>
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

    // 1. Donut Chart for Grade Distribution
    const gradeData = [
        <?= (int) $gradeCounts['Sangat Baik (A)'] ?>,
        <?= (int) $gradeCounts['Baik (B)'] ?>,
        <?= (int) $gradeCounts['Cukup (C)'] ?>,
        <?= (int) $gradeCounts['Perlu Bimbingan (D)'] ?>
    ];

    if (document.getElementById('extracurricularDonutChart') && gradeData.reduce((a, b) => a + b, 0) > 0) {
        const donutOptions = {
            series: gradeData,
            chart: {
                type: 'donut',
                height: 250
            },
            labels: ['Sangat Baik (A)', 'Baik (B)', 'Cukup (C)', 'Perlu Bimbingan (D)'],
            colors: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
            legend: { position: 'bottom', fontSize: '11px' },
            dataLabels: { enabled: true }
        };
        new ApexCharts(document.getElementById('extracurricularDonutChart'), donutOptions).render();
    }

    // 2. Student Filter
    const searchInput = document.getElementById('filterExtraReportStudent');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            document.querySelectorAll('.extra-report-row').forEach(row => {
                const name = row.querySelector('.report-student-name')?.textContent.toLowerCase() || '';
                row.style.display = name.includes(query) ? '' : 'none';
            });
        });
    }

    // 3. Toast and Clipboard single copy
    const toastEl = document.getElementById('extraToast');
    const toast = toastEl ? new bootstrap.Toast(toastEl, { delay: 2500 }) : null;

    function showToast(msg) {
        if (toastEl && toast) {
            document.getElementById('extraToastMessage').textContent = msg;
            toast.show();
        }
    }

    document.querySelectorAll('.btn-copy-single-extra').forEach(btn => {
        btn.addEventListener('click', function() {
            const text = this.getAttribute('data-narrative');
            if (text) {
                navigator.clipboard.writeText(text).then(() => {
                    showToast('Draf narasi rapor ekstrakurikuler berhasil disalin!');
                });
            }
        });
    });

    // 4. Copy All Narratives
    const btnCopyAll = document.getElementById('btnCopyAllExtraNarratives');
    if (btnCopyAll) {
        btnCopyAll.addEventListener('click', function() {
            const allItems = [];
            document.querySelectorAll('.extra-narrative-card').forEach(card => {
                const name = card.querySelector('.fw-bold')?.textContent.trim() || '';
                const text = card.querySelector('.extra-narrative-content')?.textContent.trim() || '';
                if (name && text) {
                    allItems.push(`[${name}]\n${text}\n`);
                }
            });
            if (allItems.length > 0) {
                navigator.clipboard.writeText(allItems.join('\n---\n\n')).then(() => {
                    showToast('Semua draf narasi ekstrakurikuler berhasil disalin!');
                });
            }
        });
    }

    // 5. CSV Exporter for Extra Reports Table
    const btnCsv = document.getElementById('btnExportExtraCsv');
    if (btnCsv) {
        btnCsv.addEventListener('click', function() {
            const table = document.getElementById('extraReportsTable');
            if (!table) return;

            let csvContent = '\uFEFF'; // UTF-8 BOM
            const rows = table.querySelectorAll('tr');

            rows.forEach((row, rIdx) => {
                if (row.style.display === 'none') return;
                const cols = [];
                if (rIdx === 0) {
                    row.querySelectorAll('th').forEach(th => {
                        cols.push('"' + th.textContent.trim().replace(/"/g, '""') + '"');
                    });
                } else {
                    row.querySelectorAll('td').forEach(td => {
                        cols.push('"' + td.textContent.trim().replace(/\s+/g, ' ').replace(/"/g, '""') + '"');
                    });
                }
                csvContent += cols.join(',') + '\r\n';
            });

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.setAttribute('download', 'Laporan_Ekstrakurikuler_<?= url_title($program['title'], '_', true) ?>.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    }
});
</script>
<?= $this->endSection() ?>
