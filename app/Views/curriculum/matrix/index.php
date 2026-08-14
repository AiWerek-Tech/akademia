<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php
$selectedUnit = null;
foreach ($units as $u) {
    if ((int)$u['id'] === (int)$selected_unit_id) {
        $selectedUnit = $u;
        break;
    }
}
$csrfTokenName = csrf_token();
$csrfHash = csrf_hash();
$trimPreview = $trim_preview ?? ['plans' => []];
$hasOverCapacity = false;
foreach (($trimPreview['plans'] ?? []) as $plan) {
    if (($plan['status'] ?? '') === 'OVER') {
        $hasOverCapacity = true;
        break;
    }
}
?>

<div class="container-fluid px-4 py-4">
    <!-- Header Page -->
    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
        <div>
            <a href="<?= base_url('curriculum') ?>" class="text-decoration-none small text-secondary">
                <i class="bi bi-arrow-left me-1"></i>Daftar kurikulum
            </a>
            <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                <h1 class="h3 mb-0 fw-bold"><?= esc($version['name']) ?></h1>
                <?php if ((int)$version['is_active'] === 1): ?>
                    <span class="badge rounded-pill bg-success px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i>Aktif</span>
                <?php else: ?>
                    <span class="badge rounded-pill bg-warning text-dark px-3 py-2"><i class="bi bi-pencil-square me-1"></i>Draft Matrix</span>
                <?php endif; ?>
            </div>
            <p class="text-muted mb-0"><?= esc($version['code']) ?> · <?= esc(($version['year_name'] ?? '') . ' · ' . ($version['period_name'] ?? '-')) ?></p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <!-- View Mode Switcher -->
            <div class="btn-group shadow-sm" role="group">
                <a href="<?= base_url('curriculum/' . $version['uuid'] . '?unit_id=' . $selected_unit_id) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-list-ul me-1"></i>Tampilan List
                </a>
                <a href="<?= base_url('curriculum/' . $version['uuid'] . '/matrix?unit_id=' . $selected_unit_id) ?>" class="btn btn-primary active fw-bold">
                    <i class="bi bi-grid-3x3-gap-fill me-1"></i>Editor Matriks
                </a>
            </div>

            <?php if (has_permission('curriculum.export')): ?>
                <a href="<?= base_url('curriculum/' . $version['uuid'] . '/export') ?>" class="btn btn-outline-success">
                    <i class="bi bi-download me-1"></i>Ekspor Excel
                </a>
            <?php endif; ?>

            <?php if ((int)$version['is_active'] !== 1 && (has_permission('curriculum.manage') || has_permission('curriculum.approve'))): ?>
                <form action="<?= base_url('curriculum/' . $version['uuid'] . '/activate') ?>" method="post" data-confirm="Aktifkan kurikulum ini?" class="d-inline">
                    <?= csrf_field() ?>
                    <button class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Aktifkan</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Flash Notifications -->
    <?php foreach (['success' => 'success', 'error' => 'danger'] as $key => $type): ?>
        <?php if ($message = session()->getFlashdata($key)): ?>
            <div class="alert alert-<?= $type ?> alert-dismissible fade show shadow-sm border-0 mb-4">
                <?= esc($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body">
                    <div class="text-muted small fw-semibold">Unit Sekolah</div>
                    <div class="fs-4 fw-bold text-dark mt-1"><?= esc($selectedUnit['code'] ?? '-') ?></div>
                    <div class="small text-muted"><?= esc($selectedUnit['name'] ?? '-') ?></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body">
                    <div class="text-muted small fw-semibold">Total Mata Pelajaran</div>
                    <div class="fs-3 fw-bold text-dark mt-1"><?= count($matrix['subjects']) ?></div>
                    <div class="small text-muted">Tersedia untuk unit ini</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body">
                    <div class="text-muted small fw-semibold">Struktur Terisi</div>
                    <div class="fs-3 fw-bold text-success mt-1" id="kpiStructureCount"><?= $matrix['raw_count'] ?></div>
                    <div class="small text-muted">Kombinasi mapel & tingkat</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body">
                    <div class="text-muted small fw-semibold">Grand Total Jam (JP)</div>
                    <div class="fs-3 fw-bold text-primary mt-1" id="kpiGrandTotal"><?= number_format($matrix['grand_total'], 1, ',', '.') ?> <span class="fs-6 text-muted">JP</span></div>
                    <div class="small text-muted">Total jam mingguan seluruh tingkat</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Toolbar & Live Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-center">
                <!-- Unit Switcher -->
                <div class="col-md-3">
                    <form method="get" class="d-flex align-items-center gap-2">
                        <label class="fw-semibold text-nowrap small text-muted">Unit:</label>
                        <select name="unit_id" class="form-select form-select-sm fw-semibold" onchange="this.form.submit()">
                            <?php foreach ($units as $u): ?>
                                <option value="<?= (int)$u['id'] ?>" <?= (int)$selected_unit_id === (int)$u['id'] ? 'selected' : '' ?>>
                                    <?= esc($u['code'] . ' · ' . $u['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <!-- Instant Matrix Search & Category Filter -->
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="matrixSearchInput" class="form-control border-start-0" placeholder="Cari nama atau kode mata pelajaran...">
                        <select id="matrixCategoryFilter" class="form-select border-start-0" style="max-width: 160px;">
                            <option value="">Semua Kategori</option>
                            <option value="INTRAKURIKULER">Intrakurikuler</option>
                            <option value="MUATAN_LOKAL">Muatan Lokal</option>
                            <option value="KOKURIKULER">Kokurikuler</option>
                            <option value="EKSTRAKURIKULER">Ekstrakurikuler</option>
                            <option value="OTHER">Lainnya</option>
                        </select>
                        <select id="matrixSourceMode" class="form-select border-start-0 fw-semibold" style="max-width: 190px;" title="Pilih jenis jam yang akan disimpan">
                            <option value="OFFICIAL">Input: Jam resmi</option>
                            <option value="CUSTOM">Input: JP custom sekolah</option>
                        </select>
                    </div>
                </div>

                <!-- Matrix Quick Actions -->
                <div class="col-md-4 text-md-end">
                    <?php if (has_permission('curriculum.manage')): ?>
                        <div class="d-flex flex-wrap justify-content-md-end gap-2">
                            <button type="button" class="btn btn-sm btn-warning fw-bold text-dark" data-bs-toggle="modal" data-bs-target="#reconciliationAssistantModal" <?= $hasOverCapacity ? '' : 'disabled' ?> title="<?= $hasOverCapacity ? 'Buka rekomendasi pemotongan jam' : 'Semua tingkat sudah dalam batas kapasitas' ?>">
                                <i class="bi bi-lightning-charge-fill me-1"></i>Asisten Rekonsiliasi
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#bulkStoreModal">
                                <i class="bi bi-plus-square-fill me-1"></i>Tambah Massal
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#cloneModal">
                                <i class="bi bi-copy me-1"></i>Salin Versi Lalu
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#presetModal">
                                <i class="bi bi-magic me-1"></i>Preset Unit
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive Matrix Grid Table -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap text-primary me-2"></i>Matriks Struktur & Alokasi Jam</h5>
                <div class="small text-muted">Ketik angka jam (JP) langsung pada sel. Tekan <strong>Enter</strong> atau ubah fokus untuk menyimpan instan via AJAX. (Ketik 0 untuk hapus).</div>
            </div>
            <div id="saveStatusIndicator" class="badge bg-light text-muted border px-3 py-2 opacity-0 transition-all">
                <i class="bi bi-cloud-check me-1"></i>Tersimpan
            </div>
        </div>

        <div class="table-responsive" style="max-height: 70vh;">
            <table class="table table-bordered table-hover align-middle mb-0" id="matrixTable">
                <thead class="table-dark sticky-top" style="z-index: 5;">
                    <tr class="text-center align-middle">
                        <th style="width: 40px;">#</th>
                        <th style="width: 100px;">Kode</th>
                        <th>Mata Pelajaran</th>
                        <th style="width: 140px;">Kategori</th>
                        <th style="width: 50px;" title="Copy JP ke seluruh tingkat">⚡</th>
                        <?php foreach ($matrix['grades'] as $g): ?>
                            <th style="width: 130px;" class="bg-primary text-white">
                                <div class="fw-bold"><?= esc($g['code']) ?></div>
                                <div class="small text-white-50" style="font-size: 0.72rem;"><?= esc($g['name']) ?></div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($matrix['subjects'])): ?>
                        <tr>
                            <td colspan="<?= 5 + count($matrix['grades']) ?>" class="text-center py-5">
                                <i class="bi bi-info-circle fs-1 text-muted"></i>
                                <h5 class="mt-3">Mata pelajaran belum tersedia</h5>
                                <p class="text-muted mb-0">Pastikan ketersediaan mapel untuk unit ini sudah diaktifkan di Master Mapel.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($matrix['subjects'] as $sub): ?>
                            <tr class="subject-row" data-search="<?= strtolower(esc($sub['code'] . ' ' . $sub['name'])) ?>" data-category="<?= esc($sub['category']) ?>">
                                <td class="text-center text-muted small fw-semibold"><?= $no++ ?></td>
                                <td class="fw-bold text-secondary font-monospace small"><?= esc($sub['code']) ?></td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= esc($sub['name']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border small">
                                        <?= esc(str_replace('_', ' ', $sub['category'])) ?>
                                    </span>
                                </td>
                                <td class="text-center p-1">
                                    <?php if (has_permission('curriculum.manage')): ?>
                                        <button type="button"
                                                class="btn btn-sm btn-light border text-primary px-1 py-0 copy-row-btn"
                                                title="Isi nilai sel pertama ke semua tingkat untuk mapel ini"
                                                data-subject-id="<?= (int)$sub['id'] ?>">
                                            <i class="bi bi-lightning-fill"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <?php foreach ($matrix['grades'] as $g): ?>
                                    <?php
                                        $subId = (int)$sub['id'];
                                        $grdId = (int)$g['id'];
                                        $cellData = $matrix['matrix'][$subId][$grdId] ?? null;
                                        $hoursVal = $cellData ? (float)$cellData['effective_weekly_hours'] : '';
                                        $structUuid = $cellData ? $cellData['uuid'] : '';
                                        $isUnapprovedElective = $cellData && isset($cellData['is_approved_elective']) && (int)$cellData['is_approved_elective'] === 0;
                                    ?>
                                    <td class="p-1 text-center position-relative cell-container <?= $isUnapprovedElective ? 'bg-light bg-opacity-75' : '' ?>">
                                        <div class="input-group input-group-sm">
                                            <input type="number"
                                                   step="0.5"
                                                   min="0"
                                                   max="40"
                                                   class="form-control text-center fw-bold matrix-input <?= $isUnapprovedElective ? 'bg-secondary-subtle text-muted text-decoration-line-through border-secondary' : ($hoursVal !== '' && (float)$hoursVal > 0 ? 'bg-primary-subtle text-primary border-primary' : '') ?>"
                                                   value="<?= $hoursVal ?>"
                                                   placeholder="0"
                                                   data-subject-id="<?= $subId ?>"
                                                   data-grade-id="<?= $grdId ?>"
                                                   data-structure-uuid="<?= $structUuid ?>"
                                                   data-approved="<?= $isUnapprovedElective ? '0' : '1' ?>"
                                                   data-original-val="<?= $hoursVal ?>"
                                                   data-source="<?= esc($cellData['effective_source'] ?? 'OFFICIAL') ?>"
                                                   data-original-source="<?= esc($cellData['effective_source'] ?? 'OFFICIAL') ?>"
                                                   <?= !has_permission('curriculum.manage') ? 'disabled' : '' ?>>
                                            <span class="input-group-text px-1 <?= $isUnapprovedElective ? 'bg-secondary text-white' : (($cellData['effective_source'] ?? 'OFFICIAL') === 'CUSTOM' ? 'bg-warning-subtle text-warning-emphasis' : 'text-muted') ?>" style="font-size: 0.7rem;" title="<?= $isUnapprovedElective ? 'Mapel Pilihan Belum Disetujui di Rancangan Mapel (JP tidak dihitung)' : (($cellData['effective_source'] ?? 'OFFICIAL') === 'CUSTOM' ? 'JP custom sekolah' : 'Jam resmi') ?>"><?= $isUnapprovedElective ? '×' : (($cellData['effective_source'] ?? 'OFFICIAL') === 'CUSTOM' ? 'C' : 'JP') ?></span>
                                        </div>
                                        <?php if ($isUnapprovedElective): ?>
                                            <div class="badge text-bg-secondary opacity-75 border rounded-pill shadow-xs mt-0.5" style="font-size: 0.65rem;" title="Mapel pilihan belum disetujui untuk dikirim ke jadwal, JP tidak dihitung">
                                                Belum Disetujui (0 JP)
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-light sticky-bottom fw-bold" style="z-index: 4;">
                    <!-- Row 1: Jam Resmi -->
                    <tr class="bg-light border-top">
                        <td colspan="5" class="text-end text-muted small pe-3">Jam Resmi (Pemerintah):</td>
                        <?php foreach ($matrix['grades'] as $g): ?>
                            <td class="text-center text-secondary small" id="totalOfficial_<?= (int)$g['id'] ?>">
                                <?= number_format($matrix['official_totals'][(int)$g['id']] ?? 0, 1, ',', '.') ?> JP
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <!-- Row 2: Custom / Kegiatan Tetap -->
                    <tr class="bg-light">
                        <td colspan="5" class="text-end text-muted small pe-3">Kegiatan Tetap & Custom Sekolah:</td>
                        <?php foreach ($matrix['grades'] as $g): ?>
                            <td class="text-center text-warning-emphasis small" id="totalCustom_<?= (int)$g['id'] ?>">
                                <?= number_format($matrix['custom_totals'][(int)$g['id']] ?? 0, 1, ',', '.') ?> JP
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <!-- Row 3: Total Jam Efektif -->
                    <tr class="table-primary border-top border-bottom">
                        <td colspan="5" class="text-end text-uppercase pe-3 fs-7 text-dark">Total Jam Efektif (JP):</td>
                        <?php foreach ($matrix['grades'] as $g): ?>
                            <td class="text-center text-primary fs-7 fw-bold" id="totalGrade_<?= (int)$g['id'] ?>">
                                <?= number_format($matrix['grade_totals'][(int)$g['id']] ?? 0, 1, ',', '.') ?> JP
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <!-- Row 4: Status Kapasitas Akademik Sekolah -->
                    <tr class="bg-white">
                        <td colspan="5" class="text-end pe-3 fs-8 text-muted">
                            Status Kapasitas Sekolah (Max <?= number_format($matrix['max_capacity'], 1, ',', '.') ?> JP · <?= (int)$matrix['minutes_per_jp'] ?>m/JP):
                        </td>
                        <?php foreach ($matrix['grades'] as $g): ?>
                            <?php
                                $gId = (int)$g['id'];
                                $bd = $matrix['breakdown_by_grade'][$gId] ?? null;
                                $status = $bd['status'] ?? 'BALANCED';
                                $diff = $bd['diff'] ?? 0.0;
                                $eff = $bd['effective_total'] ?? 0.0;
                                $maxCap = $bd['max_capacity'] ?? 45.0;
                            ?>
                            <td class="text-center p-2" id="statusContainer_<?= $gId ?>">
                                <?php if ($status === 'BALANCED'): ?>
                                    <span class="badge bg-success text-white px-2 py-1 w-100" id="statusBadge_<?= $gId ?>" title="Kapasitas pas 100%">
                                        <i class="bi bi-check-circle-fill me-1"></i>Pas (<?= number_format($eff, 1, ',', '.') ?>/<?= number_format($maxCap, 1, ',', '.') ?> JP)
                                    </span>
                                <?php elseif ($status === 'OVER'): ?>
                                    <span class="badge bg-danger text-white px-2 py-1 w-100" id="statusBadge_<?= $gId ?>" title="Melebihi kapasitas maksimal <?= number_format($maxCap, 1) ?> JP">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Melebihi +<?= number_format($diff, 1, ',', '.') ?> JP
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info px-2 py-1 w-100" id="statusBadge_<?= $gId ?>" title="Sisa kapasitas <?= number_format(abs($diff), 1) ?> JP">
                                        <i class="bi bi-info-circle-fill me-1"></i>Sisa <?= number_format(abs($diff), 1, ',', '.') ?> JP
                                    </span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Live Validation Audit Accordion -->
    <?php if (!empty($validation['results'])): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-shield-check me-2 text-primary"></i>Pemeriksaan Stabilitas Data (<?= (int)$validation['total_results'] ?> Catatan)
                </h6>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($validation['results'] as $res): ?>
                    <div class="list-group-item d-flex align-items-center gap-2">
                        <span class="badge bg-<?= in_array($res['severity'], ['ERROR', 'BLOCKER'], true) ? 'danger' : 'warning text-dark' ?>">
                            <?= esc($res['severity']) ?>
                        </span>
                        <span class="small text-dark"><?= esc($res['message']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ==================================================================== -->
<!-- MODAL 1: Tambah Mapel Massal -->
<!-- ==================================================================== -->
<?php if (has_permission('curriculum.manage')): ?>
<div class="modal fade" id="bulkStoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="<?= base_url('curriculum/' . $version['uuid'] . '/matrix/bulk-store') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="unit_id" value="<?= (int)$selected_unit_id ?>">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-square-fill me-2"></i>Tambah Mapel Massal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Pilih satu/beberapa mata pelajaran dan tingkat kelas untuk mengalokasikan jam secara serentak.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Pilih Mata Pelajaran <span class="text-danger">*</span></label>
                            <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                                <?php foreach ($matrix['subjects'] as $s): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="subject_ids[]" value="<?= (int)$s['id'] ?>" id="sub_chk_<?= (int)$s['id'] ?>">
                                        <label class="form-check-label small" for="sub_chk_<?= (int)$s['id'] ?>">
                                            <strong><?= esc($s['code']) ?></strong> · <?= esc($s['name']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Pilih Tingkat Kelas <span class="text-danger">*</span></label>
                            <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                                <?php foreach ($matrix['grades'] as $g): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="grade_level_ids[]" value="<?= (int)$g['id'] ?>" id="grd_chk_<?= (int)$g['id'] ?>" checked>
                                        <label class="form-check-label small" for="grd_chk_<?= (int)$g['id'] ?>">
                                            <strong><?= esc($g['code']) ?></strong> · <?= esc($g['name']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Jam Pelajaran per Minggu (JP) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.5" min="0.5" max="40" name="weekly_hours" class="form-control" value="2.0" required>
                                <span class="input-group-text">JP</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Terapkan Massal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================================================================== -->
<!-- MODAL 2: Salin dari Kurikulum Sebelumnya -->
<!-- ==================================================================== -->
<div class="modal fade" id="cloneModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="<?= base_url('curriculum/' . $version['uuid'] . '/matrix/clone-previous') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="unit_id" value="<?= (int)$selected_unit_id ?>">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-copy me-2"></i>Salin dari Kurikulum Lalu</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Menyalin seluruh alokasi jam dari versi kurikulum sebelumnya untuk unit <strong><?= esc($selectedUnit['name'] ?? '') ?></strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih Versi Kurikulum Sumber <span class="text-danger">*</span></label>
                        <select name="source_version_id" class="form-select" required>
                            <option value="">-- Pilih Versi Sumber --</option>
                            <?php foreach ($previous_versions as $pv): ?>
                                <option value="<?= (int)$pv['id'] ?>">
                                    <?= esc($pv['code'] . ' · ' . $pv['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary"><i class="bi bi-copy me-1"></i>Salin Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================================================================== -->
<!-- MODAL 3: Preset Standar Unit -->
<!-- ==================================================================== -->
<div class="modal fade" id="presetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="<?= base_url('curriculum/' . $version['uuid'] . '/matrix/apply-preset') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="unit_id" value="<?= (int)$selected_unit_id ?>">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-magic me-2"></i>Preset Standar Unit</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Secara otomatis mengisikan seluruh Mata Pelajaran aktif ke dalam semua tingkat kelas unit <strong><?= esc($selectedUnit['name'] ?? '') ?></strong> dengan nilai awal default.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nilai Default Jam (JP)</label>
                        <div class="input-group">
                            <input type="number" step="0.5" min="0.5" max="40" name="default_hours" class="form-control" value="2.0" required>
                            <span class="input-group-text">JP</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-success"><i class="bi bi-magic me-1"></i>Terapkan Preset</button>
                </div>
            </form>
        </div>
<!-- ==================================================================== -->
<!-- MODAL 4: Asisten Rekonsiliasi Jam & Rekomendasi Pemotongan -->
<!-- ==================================================================== -->
<div class="modal fade" id="reconciliationAssistantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form class="modal-content border-0 shadow rounded-4" id="assistantTrimForm" method="post" action="<?= base_url('curriculum/' . $version['uuid'] . '/matrix/auto-trim') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="unit_id" value="<?= (int)$selected_unit_id ?>">
            <input type="hidden" name="grade_level_id" id="assistantGradeHidden" value="">
            <div class="modal-header bg-warning text-dark px-4 pt-4">
                <div>
                    <h5 class="modal-title fw-bold"><i class="bi bi-lightning-charge-fill me-2"></i>Asisten Rekonsiliasi Jam & Pemotongan Cerdas</h5>
                    <div class="small text-dark opacity-75">Solusi instan untuk menyeimbangkan kelebihan jam resmi vs kegiatan custom sekolah.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4">
                <div class="alert alert-info border-0 rounded-3 mb-4">
                    <i class="bi bi-info-circle me-2"></i>Anda dapat melakukan pemotongan jam otomatis melalui asisten ini <strong>atau secara manual langsung pada tabel matriks</strong> hingga indikator status berwarna hijau 🟢.
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pilih Tingkat Kelas</label>
                        <select id="assistantGradeSelect" class="form-select rounded-3">
                            <?php foreach ($matrix['grades'] as $g): ?>
                                <?php
                                    $gId = (int)$g['id'];
                                    $bd = $matrix['breakdown_by_grade'][$gId] ?? null;
                                    $statusText = ($bd['status'] ?? '') === 'OVER' ? (' (Kelebihan +' . number_format($bd['diff'], 1) . ' JP)') : ' (Aman)';
                                ?>
                                <option value="<?= $gId ?>"><?= esc($g['code'] . ' · ' . $g['name']) ?><?= $statusText ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Target Kapasitas Maksimal</label>
                        <div class="input-group">
                            <input type="text" class="form-control rounded-3" value="<?= number_format($matrix['max_capacity'], 1) ?> JP" readonly>
                            <span class="input-group-text"><?= (int)$matrix['minutes_per_jp'] ?> mnt/JP</span>
                        </div>
                    </div>
                </div>

                <div id="assistantBreakdownContainer">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>
            <div class="modal-footer bg-light px-4 pb-4">
                <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Tutup</button>
                <button type="submit" class="btn btn-warning fw-bold text-dark rounded-3 px-4" id="assistantApplyBtn" disabled>
                    <i class="bi bi-magic me-1"></i>Terapkan Rekomendasi
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Inline Interactive Matrix JS Engine -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const updateUrl = '<?= base_url('curriculum/' . $version['uuid'] . '/matrix/update-cell') ?>';
    let currentCsrfToken = '<?= $csrfHash ?>';
    const unitId = <?= (int)$selected_unit_id ?>;
    const saveIndicator = document.getElementById('saveStatusIndicator');

    const matrixInputs = document.querySelectorAll('.matrix-input');
    const searchInput = document.getElementById('matrixSearchInput');
    const categoryFilter = document.getElementById('matrixCategoryFilter');
    const sourceMode = document.getElementById('matrixSourceMode');
    const subjectRows = document.querySelectorAll('.subject-row');
    const copyRowBtns = document.querySelectorAll('.copy-row-btn');
    const trimPreview = <?= json_encode($trimPreview, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const assistantGradeSelect = document.getElementById('assistantGradeSelect');
    const assistantGradeHidden = document.getElementById('assistantGradeHidden');
    const assistantBreakdownContainer = document.getElementById('assistantBreakdownContainer');
    const assistantApplyBtn = document.getElementById('assistantApplyBtn');

    function formatJp(value) {
        return (parseFloat(value) || 0).toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
    }

    function renderAssistantPlan() {
        if (!assistantGradeSelect || !assistantBreakdownContainer) return;

        const gradeId = assistantGradeSelect.value;
        const plan = trimPreview?.plans?.[gradeId];
        if (assistantGradeHidden) assistantGradeHidden.value = gradeId;

        if (!plan) {
            assistantBreakdownContainer.innerHTML = '<div class="alert alert-secondary border-0 rounded-3">Data rekonsiliasi tingkat ini belum tersedia.</div>';
            if (assistantApplyBtn) assistantApplyBtn.disabled = true;
            return;
        }

        const afterTotal = (parseFloat(plan.effective_total) || 0) - (parseFloat(plan.overage) || 0);
        const recs = plan.recommendations || [];
        const hiddenInputs = recs.map(rec => `<input type="hidden" name="adjustments[${rec.subject_id}]" value="${rec.new_hours}">`).join('');
        const rows = recs.map(rec => `
            <tr>
                <td>
                    <div class="fw-semibold">${escapeHtml(rec.subject_name || '-')}</div>
                    <div class="small text-muted">${escapeHtml(rec.subject_code || '')} · ${escapeHtml(rec.category || 'OFFICIAL')}</div>
                </td>
                <td class="text-end">${formatJp(rec.current_hours)} JP</td>
                <td class="text-end text-danger">-${formatJp(rec.trim_hours)} JP</td>
                <td class="text-end fw-bold text-success">${formatJp(rec.new_hours)} JP</td>
            </tr>
        `).join('');

        assistantBreakdownContainer.innerHTML = `
            ${hiddenInputs}
            <div class="row g-3 mb-3">
                <div class="col-md-3"><div class="p-3 bg-light rounded-3 h-100"><div class="small text-muted">Jam resmi</div><div class="fs-5 fw-bold">${formatJp(plan.official_total)} JP</div></div></div>
                <div class="col-md-3"><div class="p-3 bg-light rounded-3 h-100"><div class="small text-muted">Custom sekolah</div><div class="fs-5 fw-bold text-warning-emphasis">${formatJp(plan.custom_total)} JP</div></div></div>
                <div class="col-md-3"><div class="p-3 bg-light rounded-3 h-100"><div class="small text-muted">Total sekarang</div><div class="fs-5 fw-bold ${plan.status === 'OVER' ? 'text-danger' : 'text-success'}">${formatJp(plan.effective_total)} JP</div></div></div>
                <div class="col-md-3"><div class="p-3 bg-light rounded-3 h-100"><div class="small text-muted">Target sesudah</div><div class="fs-5 fw-bold text-primary">${formatJp(afterTotal)} / ${formatJp(plan.max_capacity)} JP</div></div></div>
            </div>
            ${plan.status !== 'OVER'
                ? `<div class="alert alert-success border-0 rounded-3 mb-0"><i class="bi bi-check-circle me-1"></i>${escapeHtml(plan.message || 'Kapasitas sudah aman.')}</div>`
                : recs.length === 0
                    ? `<div class="alert alert-warning border-0 rounded-3 mb-0"><i class="bi bi-exclamation-triangle me-1"></i>${escapeHtml(plan.message || 'Belum ada rekomendasi pemotongan.')}</div>`
                    : `<div class="alert alert-danger border-0 rounded-3"><strong>Kelebihan ${formatJp(plan.overage)} JP.</strong> Rekomendasi berikut akan memotong jam resmi agar total sesuai kapasitas sekolah.</div>
                       <div class="table-responsive border rounded-3">
                           <table class="table table-sm align-middle mb-0">
                               <thead class="table-light"><tr><th>Mata pelajaran resmi</th><th class="text-end">Saat ini</th><th class="text-end">Potong</th><th class="text-end">Sesudah</th></tr></thead>
                               <tbody>${rows}</tbody>
                           </table>
                       </div>`
            }
        `;

        if (assistantApplyBtn) {
            assistantApplyBtn.disabled = !(plan.status === 'OVER' && plan.can_apply && recs.length > 0);
        }
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    if (assistantGradeSelect) {
        const firstOver = Object.values(trimPreview?.plans || {}).find(plan => plan.status === 'OVER');
        if (firstOver) assistantGradeSelect.value = String(firstOver.grade_id);
        assistantGradeSelect.addEventListener('change', renderAssistantPlan);
        renderAssistantPlan();
    }

    // 1. Live Matrix Inputs Handling
    matrixInputs.forEach(input => {
        input.addEventListener('change', function () {
            saveCell(this);
        });

        // Keydown navigation & Enter handling
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.blur();
            }
        });
    });

    // 2. Quick Copy Row to All Grades
    copyRowBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const subId = this.getAttribute('data-subject-id');
            const rowInputs = document.querySelectorAll(`.matrix-input[data-subject-id="${subId}"]`);
            if (rowInputs.length === 0) return;

            const firstVal = parseFloat(rowInputs[0].value) || 2.0; // default 2 if empty
            rowInputs.forEach(inp => {
                inp.value = firstVal;
                saveCell(inp);
            });
        });
    });

    // 3. Live Client-side Filter & Search
    function filterMatrixRows() {
        const query = (searchInput.value || '').toLowerCase().trim();
        const cat = (categoryFilter.value || '').toUpperCase();

        subjectRows.forEach(row => {
            const searchData = row.getAttribute('data-search') || '';
            const rowCat = row.getAttribute('data-category') || '';

            const matchesQuery = query === '' || searchData.includes(query);
            const matchesCat = cat === '' || rowCat === cat;

            if (matchesQuery && matchesCat) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterMatrixRows);
    if (categoryFilter) categoryFilter.addEventListener('change', filterMatrixRows);

    // 4. AJAX Save Function
    function saveCell(inputEl) {
        const subjectId = inputEl.getAttribute('data-subject-id');
        const gradeId = inputEl.getAttribute('data-grade-id');
        let structureUuid = inputEl.getAttribute('data-structure-uuid');
        const originalVal = inputEl.getAttribute('data-original-val');
        const originalSource = inputEl.getAttribute('data-original-source') || 'OFFICIAL';
        const selectedSource = sourceMode?.value || 'OFFICIAL';
        const newHours = parseFloat(inputEl.value) || 0;

        if (parseFloat(originalVal) === newHours && originalSource === selectedSource) return;

        showSaveIndicator('loading');

        fetch(updateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': currentCsrfToken
            },
            body: JSON.stringify({
                unit_id: unitId,
                subject_id: subjectId,
                grade_level_id: gradeId,
                weekly_hours: newHours,
                effective_source: selectedSource,
                structure_uuid: structureUuid
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.csrf_hash) {
                currentCsrfToken = data.csrf_hash;
            }
            if (data.status === 'success') {
                showSaveIndicator('success', data.message);
                inputEl.setAttribute('data-original-val', newHours);
                inputEl.setAttribute('data-source', data.effective_source || selectedSource);
                inputEl.setAttribute('data-original-source', data.effective_source || selectedSource);

                if (data.action === 'created' && data.structure) {
                    inputEl.setAttribute('data-structure-uuid', data.structure.uuid);
                } else if (data.action === 'deleted') {
                    inputEl.setAttribute('data-structure-uuid', '');
                    inputEl.value = '';
                }

                // Styling updates
                if (newHours > 0) {
                    inputEl.classList.remove('bg-primary-subtle', 'text-primary', 'border-primary', 'bg-warning-subtle', 'text-warning-emphasis', 'border-warning');
                    inputEl.classList.add(...(selectedSource === 'CUSTOM'
                        ? ['bg-warning-subtle', 'text-warning-emphasis', 'border-warning']
                        : ['bg-primary-subtle', 'text-primary', 'border-primary']));
                    const sourceBadge = inputEl.nextElementSibling;
                    if (sourceBadge) {
                        sourceBadge.textContent = selectedSource === 'CUSTOM' ? 'C' : 'JP';
                        sourceBadge.title = selectedSource === 'CUSTOM' ? 'JP custom sekolah' : 'Jam resmi';
                        sourceBadge.classList.toggle('bg-warning-subtle', selectedSource === 'CUSTOM');
                        sourceBadge.classList.toggle('text-warning-emphasis', selectedSource === 'CUSTOM');
                    }
                } else {
                    inputEl.classList.remove('bg-primary-subtle', 'text-primary', 'border-primary', 'bg-warning-subtle', 'text-warning-emphasis', 'border-warning');
                }

                recalculateTotals(data.matrix_totals || null);
            } else {
                showSaveIndicator('error', data.message || 'Gagal menyimpan.');
                inputEl.value = originalVal;
            }
        })
        .catch(err => {
            showSaveIndicator('error', 'Terjadi kesalahan koneksi.');
            inputEl.value = originalVal;
        });
    }

    function showSaveIndicator(state, msg = '') {
        if (!saveIndicator) return;
        saveIndicator.classList.remove('opacity-0', 'bg-light', 'bg-success', 'bg-danger', 'text-muted', 'text-white');

        if (state === 'loading') {
            saveIndicator.className = 'badge bg-warning text-dark border px-3 py-2';
            saveIndicator.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
        } else if (state === 'success') {
            saveIndicator.className = 'badge bg-success text-white px-3 py-2';
            saveIndicator.innerHTML = '<i class="bi bi-check-circle me-1"></i> Tersimpan!';
            setTimeout(() => { saveIndicator.classList.add('opacity-0'); }, 2500);
        } else {
            saveIndicator.className = 'badge bg-danger text-white px-3 py-2';
            saveIndicator.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i> Error: ' + msg;
            setTimeout(() => { saveIndicator.classList.add('opacity-0'); }, 4000);
        }
    }

    const maxCapacity = <?= (float)($matrix['max_capacity'] ?? 45.0) ?>;
    const minutesPerJp = <?= (int)($matrix['minutes_per_jp'] ?? 40) ?>;

    function recalculateTotals(serverTotals = null) {
        const grades = <?= json_encode(array_column($matrix['grades'], 'id')) ?>;
        let grandTotal = 0;
        let filledCount = 0;

        grades.forEach(gId => {
            let effSum = 0;
            let offSum = 0;
            let cusSum = 0;
            let status = 'BALANCED';
            let diff = 0;

            if (serverTotals && serverTotals.grade_totals && serverTotals.grade_totals[gId] !== undefined) {
                effSum = parseFloat(serverTotals.grade_totals[gId]) || 0;
                offSum = parseFloat(serverTotals.official_totals[gId]) || 0;
                cusSum = parseFloat(serverTotals.custom_totals[gId]) || 0;
                if (serverTotals.breakdown_by_grade && serverTotals.breakdown_by_grade[gId]) {
                    diff = parseFloat(serverTotals.breakdown_by_grade[gId].diff) || 0;
                } else {
                    diff = effSum - maxCapacity;
                }
            } else {
                const inputs = document.querySelectorAll(`.matrix-input[data-grade-id="${gId}"]`);
                inputs.forEach(inp => {
                    const val = parseFloat(inp.value) || 0;
                    const src = inp.getAttribute('data-source') || 'OFFICIAL';
                    const isApproved = inp.getAttribute('data-approved') !== '0';

                    if (val > 0 && isApproved) {
                        filledCount++;
                        effSum += val;
                        if (src === 'CUSTOM') {
                            cusSum += val;
                        } else {
                            offSum += val;
                        }
                    }
                });
                diff = effSum - maxCapacity;
            }

            // Update footer totals
            const elOff = document.getElementById(`totalOfficial_${gId}`);
            if (elOff) elOff.innerText = offSum.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' JP';

            const elCus = document.getElementById(`totalCustom_${gId}`);
            if (elCus) elCus.innerText = cusSum.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' JP';

            const elGrade = document.getElementById(`totalGrade_${gId}`);
            if (elGrade) elGrade.innerText = effSum.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' JP';

            // Update status badge
            const statusContainer = document.getElementById(`statusContainer_${gId}`);
            if (statusContainer) {
                if (Math.abs(diff) < 0.01) {
                    statusContainer.innerHTML = `<span class="badge bg-success text-white px-2 py-1 w-100" id="statusBadge_${gId}" title="Kapasitas pas 100%"><i class="bi bi-check-circle-fill me-1"></i>Pas (${effSum.toFixed(1)}/${maxCapacity.toFixed(1)} JP)</span>`;
                } else if (diff > 0) {
                    statusContainer.innerHTML = `<span class="badge bg-danger text-white px-2 py-1 w-100" id="statusBadge_${gId}" title="Melebihi kapasitas maksimal ${maxCapacity.toFixed(1)} JP"><i class="bi bi-exclamation-triangle-fill me-1"></i>Melebihi +${diff.toFixed(1)} JP</span>`;
                } else {
                    statusContainer.innerHTML = `<span class="badge bg-info-subtle text-info-emphasis border border-info px-2 py-1 w-100" id="statusBadge_${gId}" title="Sisa kapasitas ${Math.abs(diff).toFixed(1)} JP"><i class="bi bi-info-circle-fill me-1"></i>Sisa ${Math.abs(diff).toFixed(1)} JP</span>`;
                }
            }

            grandTotal += effSum;
        });

        if (serverTotals && serverTotals.grand_total !== undefined) {
            grandTotal = parseFloat(serverTotals.grand_total) || grandTotal;
        }

        const kpiGrandTotal = document.getElementById('kpiGrandTotal');
        if (kpiGrandTotal) {
            kpiGrandTotal.innerHTML = grandTotal.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' <span class="fs-6 text-muted">JP</span>';
        }

        const allInputs = document.querySelectorAll('.matrix-input');
        let totalActiveCount = 0;
        allInputs.forEach(inp => {
            if ((parseFloat(inp.value) || 0) > 0 && inp.getAttribute('data-approved') !== '0') {
                totalActiveCount++;
            }
        });

        const kpiStructureCount = document.getElementById('kpiStructureCount');
        if (kpiStructureCount) {
            kpiStructureCount.innerText = totalActiveCount;
        }
    }
});
</script>
<?= $this->endSection() ?>
