<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-4 py-4">
    <!-- Header Title -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-0 fw-bold text-dark"><i class="bi bi-calculator-fill text-primary me-2"></i>Laporan Beban Jam Mengajar Guru</h1>
            <p class="text-muted small mb-0">Evaluasi pembagian beban mengajar tatap muka dan tugas tambahan sesuai Regulasi Permendikdasmen No. 13 Tahun 2025 & Kebijakan Sekolah</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if (has_permission('workloads.recalculate') && $version): ?>
                <form method="post" action="<?= base_url('workloads/recalculate') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="assignment_version_id" value="<?= $version['id'] ?>">
                    <input type="hidden" name="academic_period_id" value="<?= $selected_period_id ?>">
                    <input type="hidden" name="unit_id" value="<?= $selected_unit_id ?>">
                    <button type="submit" class="btn btn-outline-primary btn-sm fw-semibold">
                        <i class="bi bi-arrow-clockwise me-1"></i> Hitung Ulang Beban Kerja
                    </button>
                </form>
            <?php endif; ?>
            <?php if (has_permission('workloads.export')): ?>
                <a href="<?= base_url('workloads/export?unit_id=' . $selected_unit_id) ?>" class="btn btn-success btn-sm fw-semibold">
                    <i class="bi bi-file-earmark-excel me-1"></i> Ekspor Laporan Excel
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Flash Notifications -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <?= esc(session()->getFlashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body">
                    <div class="text-muted small fw-semibold">Total Guru Aktif</div>
                    <div class="fs-3 fw-bold text-dark mt-1"><?= $report['summary']['total_teachers'] ?></div>
                    <div class="small text-muted">Guru terdaftar dalam versi ini</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body">
                    <div class="text-muted small fw-semibold">Belum Minimum (< 24 JP)</div>
                    <div class="fs-3 fw-bold text-warning mt-1"><?= $report['summary']['underload_count'] ?> <span class="fs-6 text-muted">Guru</span></div>
                    <div class="small text-warning-emphasis">Memerlukan alokasi jam tambahan</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body">
                    <div class="text-muted small fw-semibold">Memenuhi Target (24–40 JP)</div>
                    <div class="fs-3 fw-bold text-success mt-1"><?= $report['summary']['optimal_count'] ?> <span class="fs-6 text-muted">Guru</span></div>
                    <div class="small text-success">Sesuai Permendikbud</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body">
                    <div class="text-muted small fw-semibold">Melebihi Maksimum (> 40 JP)</div>
                    <div class="fs-3 fw-bold text-danger mt-1"><?= $report['summary']['overload_count'] ?> <span class="fs-6 text-muted">Guru</span></div>
                    <div class="small text-danger">Memerlukan penyesuaian beban</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Policy Rules Legend Banner -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-center mb-3">
                <div class="col-md-4">
                    <form method="get" class="d-flex align-items-center gap-2">
                        <label class="form-label small text-muted text-nowrap mb-0 fw-semibold">Periode:</label>
                        <select name="academic_period_id" class="form-select form-select-sm fw-semibold" onchange="this.form.submit()">
                            <?php foreach ($periods as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ($selected_period_id == $p['id']) ? 'selected' : '' ?>><?= esc($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                <div class="col-md-4">
                    <form method="get" class="d-flex align-items-center gap-2">
                        <label class="form-label small text-muted text-nowrap mb-0 fw-semibold">Unit:</label>
                        <select name="unit_id" class="form-select form-select-sm fw-semibold" onchange="this.form.submit()">
                            <option value="">Semua Unit (SMP & SMA)</option>
                            <?php foreach ($units as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= ($selected_unit_id == $u['id']) ? 'selected' : '' ?>><?= esc($u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="teacherSearchInput" class="form-control border-start-0" placeholder="Cari nama guru atau tugas...">
                    </div>
                </div>
            </div>

            <!-- Legend & Policy Reference -->
            <div class="p-3 bg-light rounded border">
                <div class="row g-2 align-items-center">
                    <div class="col-md-7">
                        <div class="small fw-bold text-secondary mb-1"><i class="bi bi-info-circle me-1"></i>Acuan Ketentuan Permendikbud & Tugas Tambahan:</div>
                        <ul class="list-inline mb-0 small text-muted" style="font-size: 0.8rem;">
                            <li class="list-inline-item me-3"><strong>Minimum Mengajar:</strong> 24 JP/minggu</li>
                            <li class="list-inline-item me-3"><strong>Maksimum Mengajar:</strong> 40 JP/minggu</li>
                            <li class="list-inline-item me-3"><strong>Kepala Sekolah:</strong> 24 JP</li>
                            <li class="list-inline-item me-3"><strong>Wakasek / Bendahara / TU:</strong> 12 JP</li>
                            <li class="list-inline-item"><strong>Wali Kelas / Kep. Lab / Pathfinder / Operator:</strong> 2 JP</li>
                        </ul>
                    </div>
                    <div class="col-md-5 text-md-end">
                        <div class="d-flex flex-wrap justify-content-md-end gap-1 small">
                            <span class="badge bg-warning text-dark border"><i class="bi bi-circle-fill text-warning me-1"></i>< 24 JP (Underload)</span>
                            <span class="badge bg-success text-white"><i class="bi bi-check-circle-fill me-1"></i>24-40 JP (Optimal)</span>
                            <span class="badge bg-danger text-white"><i class="bi bi-exclamation-triangle-fill me-1"></i>> 40 JP (Overload)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Spreadsheet-Style Table: JUMLAH BEBAN JAM MENGAJAR PER MINGGU -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold text-dark">
                <i class="bi bi-table text-primary me-2"></i>Matriks Jumlah Beban Jam Mengajar Per Minggu
            </h5>
            <?php if ($version): ?>
                <span class="badge bg-light text-secondary border px-3 py-2">Versi Penugasan: <strong><?= esc($version['name']) ?></strong> (<?= esc($version['code']) ?>)</span>
            <?php endif; ?>
        </div>
        <div class="table-responsive" style="max-height: 75vh;">
            <table class="table table-bordered table-hover align-middle mb-0" id="workloadTable">
                <thead class="table-dark sticky-top text-center align-middle" style="z-index: 5;">
                    <tr>
                        <th rowspan="2" style="width: 40px;">No</th>
                        <th rowspan="2" style="min-width: 200px;">Nama Guru & Tugas Tambahan</th>
                        <th rowspan="2" style="min-width: 160px;">Mata Pelajaran</th>
                        <th colspan="<?= count($report['grades']) ?>" class="bg-primary text-white">Alokasi Jam per Kelas (JP/Minggu)</th>
                        <th rowspan="2" style="width: 100px;">Total JP Mengajar</th>
                        <th rowspan="2" style="width: 100px;">Tugas Tambahan</th>
                        <th rowspan="2" style="width: 100px;" class="bg-dark text-warning">TOTAL BEBAN</th>
                        <th colspan="2" class="bg-secondary text-white">Selisih JP / Minggu</th>
                        <th rowspan="2" style="width: 120px;">Status</th>
                    </tr>
                    <tr>
                        <?php foreach ($report['grades'] as $g): ?>
                            <th style="width: 70px;" class="bg-primary text-white small"><?= esc($g['code']) ?></th>
                        <?php endforeach; ?>
                        <th style="width: 90px;" class="bg-secondary text-white small" title="Selisih dari Minimum 24 JP (Total - 24)">Min (24 JP)</th>
                        <th style="width: 90px;" class="bg-secondary text-white small" title="Selisih dari Maksimum 40 JP (Total - 40)">Max (40 JP)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report['rows'])): ?>
                        <tr>
                            <td colspan="<?= 8 + count($report['grades']) ?>" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block text-muted mb-2"></i>
                                Belum ada data penugasan atau perhitungan beban kerja untuk unit dan periode ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($report['rows'] as $r): ?>
                            <?php
                                $statusClass = match($r['status']) {
                                    'UNDERLOAD' => 'table-warning',
                                    'OVERLOAD'  => 'table-danger',
                                    default     => '',
                                };
                            ?>
                            <tr class="teacher-row <?= $statusClass ?>" data-search="<?= strtolower(esc($r['full_name'] . ' ' . $r['duties_title'] . ' ' . $r['subjects_title'])) ?>">
                                <td class="text-center text-muted small fw-semibold"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= esc($r['full_name']) ?></div>
                                    <?php if (!empty($r['duties_title'])): ?>
                                        <div class="small text-primary fw-semibold"><i class="bi bi-person-badge me-1"></i><?= esc($r['duties_title']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-secondary"><?= esc($r['subjects_title'] ?: '-') ?></div>
                                </td>

                                <!-- Alokasi per Kelas -->
                                <?php foreach ($report['grades'] as $g): ?>
                                    <?php $hrs = $r['grade_allocations'][(int)$g['id']] ?? 0; ?>
                                    <td class="text-center fw-bold <?= $hrs > 0 ? 'text-primary' : 'text-muted opacity-50' ?>">
                                        <?= $hrs > 0 ? number_format($hrs, 0) : '-' ?>
                                    </td>
                                <?php endforeach; ?>

                                <td class="text-center fw-bold text-dark"><?= number_format($r['teaching_hours'], 1, ',', '.') ?> JP</td>
                                <td class="text-center fw-bold text-info-emphasis"><?= number_format($r['duty_hours'], 1, ',', '.') ?> JP</td>
                                <td class="text-center fw-bold text-dark fs-6 bg-light border"><?= number_format($r['total_beban'], 1, ',', '.') ?> JP</td>

                                <!-- Selisih Min 24 JP -->
                                <td class="text-center fw-bold <?= $r['diff_min_24'] < 0 ? 'text-danger bg-warning-subtle' : 'text-success' ?>">
                                    <?= $r['diff_min_24'] > 0 ? '+' : '' ?><?= number_format($r['diff_min_24'], 1, ',', '.') ?>
                                </td>

                                <!-- Selisih Max 40 JP -->
                                <td class="text-center fw-bold <?= $r['diff_max_40'] > 0 ? 'text-danger bg-danger-subtle' : 'text-muted' ?>">
                                    <?= $r['diff_max_40'] > 0 ? '+' : '' ?><?= number_format($r['diff_max_40'], 1, ',', '.') ?>
                                </td>

                                <!-- Status Badge -->
                                <td class="text-center">
                                    <?php if ($r['status'] === 'UNDERLOAD'): ?>
                                        <span class="badge bg-warning text-dark border px-2 py-1"><i class="bi bi-exclamation-circle me-1"></i>< 24 JP</span>
                                    <?php elseif ($r['status'] === 'OVERLOAD'): ?>
                                        <span class="badge bg-danger text-white px-2 py-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>> 40 JP</span>
                                    <?php else: ?>
                                        <span class="badge bg-success text-white px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Optimal</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-light sticky-bottom fw-bold" style="z-index: 4;">
                    <tr class="align-middle">
                        <td colspan="3" class="text-end text-uppercase pe-3 fs-6">Total Beban Sekolah:</td>
                        <?php foreach ($report['grades'] as $g): ?>
                            <td class="text-center text-muted small">-</td>
                        <?php endforeach; ?>
                        <td class="text-center text-primary fs-6"><?= number_format($report['summary']['grand_teaching'], 1, ',', '.') ?> JP</td>
                        <td class="text-center text-info-emphasis fs-6"><?= number_format($report['summary']['grand_duties'], 1, ',', '.') ?> JP</td>
                        <td class="text-center text-dark fs-6 bg-warning-subtle border"><?= number_format($report['summary']['grand_total'], 1, ',', '.') ?> JP</td>
                        <td colspan="3" class="text-muted small">Rata-rata: <?= count($report['rows']) > 0 ? number_format($report['summary']['grand_total'] / count($report['rows']), 1, ',', '.') : 0 ?> JP / Guru</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('teacherSearchInput');
    const teacherRows = document.querySelectorAll('.teacher-row');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = (this.value || '').toLowerCase().trim();
            teacherRows.forEach(row => {
                const searchData = row.getAttribute('data-search') || '';
                if (query === '' || searchData.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
</script>
<?= $this->endSection() ?>
