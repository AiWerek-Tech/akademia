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
                    </div>
                </div>

                <!-- Matrix Quick Actions -->
                <div class="col-md-4 text-md-end">
                    <?php if (has_permission('curriculum.manage')): ?>
                        <div class="d-flex flex-wrap justify-content-md-end gap-2">
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
                                    ?>
                                    <td class="p-1 text-center position-relative cell-container">
                                        <div class="input-group input-group-sm">
                                            <input type="number" 
                                                   step="0.5" 
                                                   min="0" 
                                                   max="40" 
                                                   class="form-control text-center fw-bold matrix-input <?= $hoursVal !== '' && (float)$hoursVal > 0 ? 'bg-primary-subtle text-primary border-primary' : '' ?>" 
                                                   value="<?= $hoursVal ?>" 
                                                   placeholder="0"
                                                   data-subject-id="<?= $subId ?>"
                                                   data-grade-id="<?= $grdId ?>"
                                                   data-structure-uuid="<?= $structUuid ?>"
                                                   data-original-val="<?= $hoursVal ?>"
                                                   <?= !has_permission('curriculum.manage') ? 'disabled' : '' ?>>
                                            <span class="input-group-text px-1 text-muted" style="font-size: 0.7rem;">JP</span>
                                        </div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-light sticky-bottom fw-bold" style="z-index: 4;">
                    <tr>
                        <td colspan="5" class="text-end text-uppercase pe-3 fs-6">Total Jam per Minggu:</td>
                        <?php foreach ($matrix['grades'] as $g): ?>
                            <td class="text-center text-primary fs-6" id="totalGrade_<?= (int)$g['id'] ?>">
                                <?= number_format($matrix['grade_totals'][(int)$g['id']] ?? 0, 1, ',', '.') ?> JP
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
    </div>
</div>
<?php endif; ?>

<!-- Inline Interactive Matrix JS Engine -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const updateUrl = '<?= base_url('curriculum/' . $version['uuid'] . '/matrix/update-cell') ?>';
    const csrfToken = '<?= $csrfHash ?>';
    const unitId = <?= (int)$selected_unit_id ?>;
    const saveIndicator = document.getElementById('saveStatusIndicator');

    const matrixInputs = document.querySelectorAll('.matrix-input');
    const searchInput = document.getElementById('matrixSearchInput');
    const categoryFilter = document.getElementById('matrixCategoryFilter');
    const subjectRows = document.querySelectorAll('.subject-row');
    const copyRowBtns = document.querySelectorAll('.copy-row-btn');

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
        const newHours = parseFloat(inputEl.value) || 0;

        if (parseFloat(originalVal) === newHours) return;

        showSaveIndicator('loading');

        fetch(updateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                unit_id: unitId,
                subject_id: subjectId,
                grade_level_id: gradeId,
                weekly_hours: newHours,
                structure_uuid: structureUuid
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showSaveIndicator('success', data.message);
                inputEl.setAttribute('data-original-val', newHours);

                if (data.action === 'created' && data.structure) {
                    inputEl.setAttribute('data-structure-uuid', data.structure.uuid);
                } else if (data.action === 'deleted') {
                    inputEl.setAttribute('data-structure-uuid', '');
                    inputEl.value = '';
                }

                // Styling updates
                if (newHours > 0) {
                    inputEl.classList.add('bg-primary-subtle', 'text-primary', 'border-primary');
                } else {
                    inputEl.classList.remove('bg-primary-subtle', 'text-primary', 'border-primary');
                }

                recalculateTotals();
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

    function recalculateTotals() {
        const grades = <?= json_encode(array_column($matrix['grades'], 'id')) ?>;
        let grandTotal = 0;
        let filledCount = 0;

        grades.forEach(gId => {
            const inputs = document.querySelectorAll(`.matrix-input[data-grade-id="${gId}"]`);
            let sum = 0;
            inputs.forEach(inp => {
                const val = parseFloat(inp.value) || 0;
                sum += val;
                if (val > 0) filledCount++;
            });
            const colTotalEl = document.getElementById(`totalGrade_${gId}`);
            if (colTotalEl) {
                colTotalEl.innerText = sum.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' JP';
            }
            grandTotal += sum;
        });

        const kpiGrandTotal = document.getElementById('kpiGrandTotal');
        if (kpiGrandTotal) {
            kpiGrandTotal.innerHTML = grandTotal.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' <span class="fs-6 text-muted">JP</span>';
        }

        const kpiStructureCount = document.getElementById('kpiStructureCount');
        if (kpiStructureCount) {
            kpiStructureCount.innerText = filledCount;
        }
    }
});
</script>
<?= $this->endSection() ?>
