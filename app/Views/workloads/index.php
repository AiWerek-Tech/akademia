<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="mb-4">
    <!-- Header Title Hero Banner -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff;">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h4 class="fw-bold mb-0 text-white"><i class="bi bi-calculator-fill text-primary me-2"></i>Laporan Beban Jam Mengajar Guru</h4>
                    </div>
                    <p class="text-white-50 mb-0 small">
                        Evaluasi pembagian beban mengajar tatap muka dan tugas tambahan (Permendikbud No. 15/2018 & Kebijakan Sekolah).
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <?php if (has_permission('workloads.recalculate') && $version): ?>
                        <form method="post" action="<?= base_url('workloads/recalculate') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="assignment_version_id" value="0">
                            <input type="hidden" name="academic_period_id" value="<?= $selected_period_id ?>">
                            <input type="hidden" name="unit_id" value="<?= $selected_unit_id ?>">
                            <button type="submit" class="btn btn-sm btn-outline-light rounded-3 px-3 py-2 fw-semibold">
                                <i class="bi bi-arrow-clockwise me-1"></i> Hitung Ulang Beban Kerja
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php if (has_permission('workloads.export')): ?>
                        <a href="<?= base_url('workloads/export?unit_id=' . $selected_unit_id) ?>" class="btn btn-sm btn-success rounded-3 shadow-sm px-3 py-2 fw-semibold">
                            <i class="bi bi-file-earmark-excel me-1"></i> Ekspor Laporan Excel
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Notifications -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div><?= esc(session()->getFlashdata('success')) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div><?= esc(session()->getFlashdata('error')) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 text-primary d-flex align-items-center justify-content-center" style="width:48px; height:48px; background-color: #eff6ff;">
                        <i class="bi bi-people-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-semibold">Total Guru Aktif</div>
                        <div class="fs-4 fw-bold text-dark mt-0"><?= $report['summary']['total_teachers'] ?> <span class="fs-8 fw-normal text-muted">Guru</span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 text-warning d-flex align-items-center justify-content-center" style="width:48px; height:48px; background-color: #fefce8;">
                        <i class="bi bi-exclamation-circle-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-semibold">Kurang Jam (< 24 JP)</div>
                        <div class="fs-4 fw-bold text-dark mt-0"><?= $report['summary']['underload_count'] ?> <span class="fs-8 fw-normal text-muted">Guru</span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 text-success d-flex align-items-center justify-content-center" style="width:48px; height:48px; background-color: #f0fdf4;">
                        <i class="bi bi-check-circle-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-semibold">Memenuhi Target (24–40 JP)</div>
                        <div class="fs-4 fw-bold text-dark mt-0"><?= $report['summary']['optimal_count'] ?> <span class="fs-8 fw-normal text-muted">Guru</span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 text-danger d-flex align-items-center justify-content-center" style="width:48px; height:48px; background-color: #fef2f2;">
                        <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-semibold">Kelebihan Jam (> 40 JP)</div>
                        <div class="fs-4 fw-bold text-dark mt-0"><?= $report['summary']['overload_count'] ?> <span class="fs-8 fw-normal text-muted">Guru</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Policy Rules Legend Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body">
        <div class="card-body p-4">
            <div class="row g-3 align-items-center mb-3">
                <div class="col-md-4">
                    <form method="get" class="d-flex align-items-center gap-2">
                        <label class="form-label small text-muted text-nowrap mb-0 fw-semibold">Periode:</label>
                        <select name="academic_period_id" class="form-select form-select-sm rounded-3 fw-semibold" onchange="this.form.submit()">
                            <?php foreach ($periods as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ($selected_period_id == $p['id']) ? 'selected' : '' ?>><?= esc($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                <div class="col-md-4">
                    <form method="get" class="d-flex align-items-center gap-2">
                        <label class="form-label small text-muted text-nowrap mb-0 fw-semibold">Unit:</label>
                        <select name="unit_id" class="form-select form-select-sm rounded-3 fw-semibold" onchange="this.form.submit()">
                            <option value="">Semua Unit (SMP & SMA)</option>
                            <?php foreach ($units as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= ($selected_unit_id == $u['id']) ? 'selected' : '' ?>><?= esc($u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-secondary border-end-0 rounded-start-3"><i class="bi bi-search"></i></span>
                        <input type="text" id="teacherSearchInput" class="form-control rounded-end-3 border-start-0" placeholder="Cari nama guru atau tugas...">
                    </div>
                </div>
            </div>

            <!-- Legend & Policy Reference -->
            <div class="p-3 rounded-4 border" style="background-color: #f8fafc;">
                <div class="row g-2 align-items-center">
                    <div class="col-md-7">
                        <div class="small fw-bold text-dark mb-1"><i class="bi bi-info-circle text-primary me-1"></i>Acuan Ketentuan Permendikbud & Tugas Tambahan:</div>
                        <ul class="list-inline mb-0 small text-secondary" style="font-size: 0.8rem;">
                            <li class="list-inline-item me-3"><strong>Min Tatap Muka:</strong> 24 JP/minggu</li>
                            <li class="list-inline-item me-3"><strong>Max Tatap Muka:</strong> 40 JP/minggu</li>
                            <li class="list-inline-item me-3"><strong>Kepala Sekolah:</strong> +24 JP</li>
                            <li class="list-inline-item me-3"><strong>Wakasek / TU:</strong> +12 JP</li>
                            <li class="list-inline-item"><strong>Wali Kelas / Lab / Pathfinder:</strong> +2 JP</li>
                        </ul>
                    </div>
                    <div class="col-md-5 text-md-end">
                        <div class="d-flex flex-wrap justify-content-md-end gap-1.5 small">
                            <span class="badge rounded-pill px-2.5 py-1 fw-bold" style="background-color: #fef08a; color: #713f12; border: 1px solid #fde047;"><i class="bi bi-circle-fill text-warning me-1"></i>< 24 JP (Underload)</span>
                            <span class="badge rounded-pill px-2.5 py-1 fw-bold" style="background-color: #dcfce7; color: #14532d; border: 1px solid #86efac;"><i class="bi bi-check-circle-fill me-1"></i>24-40 JP (Optimal)</span>
                            <span class="badge rounded-pill px-2.5 py-1 fw-bold" style="background-color: #fee2e2; color: #7f1d1d; border: 1px solid #fca5a5;"><i class="bi bi-exclamation-triangle-fill me-1"></i>> 40 JP (Overload)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table: JUMLAH BEBAN JAM MENGAJAR PER MINGGU -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-body">
        <div class="card-header bg-body py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
            <h5 class="card-title mb-0 fw-bold text-dark">
                <i class="bi bi-table text-primary me-2"></i>Matriks Jumlah Beban Jam Mengajar Per Minggu
            </h5>
            <?php if (!empty($activeVersions) && count($activeVersions) > 1): ?>
                <div class="d-flex flex-wrap gap-1">
                    <?php foreach ($activeVersions as $av): ?>
                        <span class="badge bg-light text-dark border rounded-pill px-3 py-1.5">
                            <i class="bi bi-check-circle-fill text-success me-1"></i>
                            <?= esc($av['name']) ?> <span class="text-muted">(<?= esc($av['code']) ?>)</span>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($version): ?>
                <span class="badge bg-light text-dark border rounded-pill px-3 py-1.5">Versi Penugasan: <strong><?= esc($version['name']) ?></strong> (<?= esc($version['code']) ?>)</span>
            <?php endif; ?>
        </div>
        <div class="table-responsive" style="max-height: 75vh;">
            <table class="table table-hover align-middle mb-0" id="workloadTable" style="border-collapse: separate; border-spacing: 0;">
                <thead class="sticky-top text-center align-middle" style="z-index: 5; background-color: #f8fafc;">
                    <tr class="text-uppercase text-secondary fs-8 fw-bold border-bottom">
                        <th rowspan="2" class="ps-4 text-dark" style="width: 40px; background-color: #f8fafc; border-bottom: 2px solid #cbd5e1;">NO</th>
                        <th rowspan="2" style="min-width: 220px; background-color: #f8fafc; border-bottom: 2px solid #cbd5e1;" class="text-start text-dark">NAMA GURU & TUGAS TAMBAHAN</th>
                        <th rowspan="2" style="min-width: 180px; background-color: #f8fafc; border-bottom: 2px solid #cbd5e1;" class="text-start text-dark">MATA PELAJARAN</th>
                        <th colspan="<?= count($report['grades']) ?>" class="py-2 text-primary" style="background-color: #eff6ff; border-bottom: 1px solid #bfdbfe; font-weight: 800;">ALOKASI JAM PER KELAS (JP/MINGGU)</th>
                        <th rowspan="2" style="width: 110px; background-color: #f8fafc; border-bottom: 2px solid #cbd5e1;" class="text-dark">JAM MENGAJAR</th>
                        <th rowspan="2" style="width: 110px; background-color: #f8fafc; border-bottom: 2px solid #cbd5e1;" class="text-dark">TUGAS TAMBAHAN</th>
                        <th rowspan="2" style="width: 120px; background-color: #e0e7ff; color: #3730a3; border-bottom: 2px solid #a5b4fc; border-left: 2px solid #c7d2fe; border-right: 2px solid #c7d2fe; font-weight: 800;">TOTAL BEBAN</th>
                        <th colspan="2" class="py-2 text-dark" style="background-color: #f1f5f9; border-bottom: 1px solid #cbd5e1; font-weight: 800;">SELISIH JP / MINGGU</th>
                        <th rowspan="2" class="pe-4 text-dark" style="width: 120px; background-color: #f8fafc; border-bottom: 2px solid #cbd5e1;">STATUS</th>
                    </tr>
                    <tr class="text-uppercase text-secondary fs-8 fw-bold border-bottom">
                        <?php foreach ($report['grades'] as $g): ?>
                            <th style="width: 70px; background-color: #eff6ff; color: #1d4ed8; border-bottom: 2px solid #cbd5e1;" class="py-1.5"><?= esc($g['code']) ?></th>
                        <?php endforeach; ?>
                        <th style="width: 95px; background-color: #f1f5f9; color: #475569; border-bottom: 2px solid #cbd5e1;" class="py-1.5" title="Selisih dari Minimum 24 JP (Total - 24)">MIN (24 JP)</th>
                        <th style="width: 95px; background-color: #f1f5f9; color: #475569; border-bottom: 2px solid #cbd5e1;" class="py-1.5" title="Selisih dari Maksimum 40 JP (Total - 40)">MAX (40 JP)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report['rows'])): ?>
                        <tr>
                            <td colspan="<?= 8 + count($report['grades']) ?>" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                Belum ada data penugasan atau perhitungan beban kerja untuk unit dan periode ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($report['rows'] as $r): ?>
                            <?php
                                $rowBgStyle = match($r['status']) {
                                    'UNDERLOAD' => 'background-color: #fffbeb;',
                                    'OVERLOAD'  => 'background-color: #fef2f2;',
                                    default     => 'background-color: #ffffff;',
                                };
                            ?>
                            <tr class="teacher-row border-bottom" style="<?= $rowBgStyle ?>" data-search="<?= strtolower(esc($r['full_name'] . ' ' . $r['duties_title'] . ' ' . $r['subjects_title'])) ?>">
                                <td class="ps-4 text-center text-muted small fw-semibold"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= esc($r['full_name']) ?></div>
                                    <?php if (!empty($r['duties_title'])): ?>
                                        <div class="small fw-semibold mt-0.5 text-primary"><i class="bi bi-person-badge me-1"></i><?= esc($r['duties_title']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-secondary"><?= esc($r['subjects_title'] ?: '-') ?></div>
                                </td>

                                <!-- Alokasi per Kelas -->
                                <?php foreach ($report['grades'] as $g): ?>
                                    <?php $hrs = $r['grade_allocations'][(int)$g['id']] ?? 0; ?>
                                    <td class="text-center fw-bold" style="color: <?= $hrs > 0 ? '#1d4ed8' : '#94a3b8' ?>;">
                                        <?= $hrs > 0 ? number_format($hrs, 0) : '-' ?>
                                    </td>
                                <?php endforeach; ?>

                                <td class="text-center fw-bold text-dark"><?= number_format($r['teaching_hours'], 1, ',', '.') ?> JP</td>
                                <td class="text-center fw-bold" style="color: #0369a1;"><?= number_format($r['duty_hours'], 1, ',', '.') ?> JP</td>
                                <td class="text-center fw-bold fs-6" style="background-color: #eef2ff; color: #312e81; border-left: 2px solid #c7d2fe; border-right: 2px solid #c7d2fe; font-weight: 800;">
                                    <?= number_format($r['total_beban'], 1, ',', '.') ?> JP
                                </td>

                                <!-- Selisih Min 24 JP -->
                                <td class="text-center fw-bold" style="color: <?= $r['diff_min_24'] < 0 ? '#b45309' : '#15803d' ?>; background-color: <?= $r['diff_min_24'] < 0 ? '#fef3c7' : 'transparent' ?>;">
                                    <?= $r['diff_min_24'] > 0 ? '+' : '' ?><?= number_format($r['diff_min_24'], 1, ',', '.') ?>
                                </td>

                                <!-- Selisih Max 40 JP -->
                                <td class="text-center fw-bold" style="color: <?= $r['diff_max_40'] > 0 ? '#b91c1c' : '#64748b' ?>; background-color: <?= $r['diff_max_40'] > 0 ? '#fee2e2' : 'transparent' ?>;">
                                    <?= $r['diff_max_40'] > 0 ? '+' : '' ?><?= number_format($r['diff_max_40'], 1, ',', '.') ?>
                                </td>

                                <!-- Status Badge -->
                                <td class="text-center pe-4">
                                    <?php if ($r['status'] === 'UNDERLOAD'): ?>
                                        <span class="badge rounded-pill px-2.5 py-1.5 fw-bold" style="background-color: #fef08a; color: #713f12; border: 1px solid #fde047;"><i class="bi bi-exclamation-circle me-1"></i>< 24 JP</span>
                                    <?php elseif ($r['status'] === 'OVERLOAD'): ?>
                                        <span class="badge rounded-pill px-2.5 py-1.5 fw-bold" style="background-color: #fee2e2; color: #7f1d1d; border: 1px solid #fca5a5;"><i class="bi bi-exclamation-triangle-fill me-1"></i>> 40 JP</span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill px-2.5 py-1.5 fw-bold" style="background-color: #dcfce7; color: #14532d; border: 1px solid #86efac;"><i class="bi bi-check-circle-fill me-1"></i>Optimal</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="sticky-bottom fw-bold border-top" style="z-index: 4; background-color: #f8fafc;">
                    <tr class="align-middle border-top">
                        <td colspan="3" class="ps-4 text-end text-uppercase pe-3 fs-8 text-dark fw-bold">TOTAL BEBAN SEKOLAH:</td>
                        <?php foreach ($report['grades'] as $g): ?>
                            <td class="text-center text-muted small">-</td>
                        <?php endforeach; ?>
                        <td class="text-center text-primary fs-6 fw-bold"><?= number_format($report['summary']['grand_teaching'], 1, ',', '.') ?> JP</td>
                        <td class="text-center fs-6 fw-bold" style="color: #0369a1;"><?= number_format($report['summary']['grand_duties'], 1, ',', '.') ?> JP</td>
                        <td class="text-center fs-6 fw-bold" style="background-color: #e0e7ff; color: #312e81; border-left: 2px solid #c7d2fe; border-right: 2px solid #c7d2fe; font-weight: 800;"><?= number_format($report['summary']['grand_total'], 1, ',', '.') ?> JP</td>
                        <td colspan="3" class="pe-4 text-secondary small">Rata-rata: <?= count($report['rows']) > 0 ? number_format($report['summary']['grand_total'] / count($report['rows']), 1, ',', '.') : 0 ?> JP / Guru</td>
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
