<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php 
$pageTitle = 'Tujuan Pembelajaran (TP)'; 
$pageIcon = 'target'; 
$pageDescription = 'Tujuan Pembelajaran nasional dan adaptasi satuan pendidikan/guru dengan silsilah data yang dapat diaudit.'; 
?>
<?= view('education_foundation/_page_header', compact('pageTitle','pageIcon','pageDescription')) ?>

<?php
$totalTp = count($rows);
$nationalTp = count(array_filter($rows, fn($r) => strtoupper($r['source_level'] ?? '') === 'NATIONAL'));
$schoolTp = count(array_filter($rows, fn($r) => strtoupper($r['source_level'] ?? '') === 'SCHOOL'));
$teacherTp = count(array_filter($rows, fn($r) => strtoupper($r['source_level'] ?? '') === 'TEACHER'));
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3">
                    <i data-lucide="target" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= $totalTp ?></div>
                    <div class="small text-muted">Total TP Terdaftar</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3" style="background: rgba(59, 130, 246, 0.1) !important; color: #2563eb !important;">
                    <i data-lucide="landmark" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-primary"><?= $nationalTp ?></div>
                    <div class="small text-muted">TP Standar Nasional</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-purple bg-opacity-10 p-3" style="background: rgba(147, 51, 234, 0.1); color: #9333ea;">
                    <i data-lucide="school" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold" style="color: #9333ea;"><?= $schoolTp ?></div>
                    <div class="small text-muted">Adaptasi Sekolah</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-emerald bg-opacity-10 p-3" style="background: rgba(16, 185, 129, 0.1); color: #059669;">
                    <i data-lucide="user-check" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-success"><?= $teacherTp ?></div>
                    <div class="small text-muted">Adaptasi Guru</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                <div class="position-relative" style="min-width: 260px; max-width: 380px;">
                    <input type="text" id="tpSearchInput" class="form-control form-control-sm rounded-pill ps-4" placeholder="Cari kode TP atau pernyataan...">
                    <i data-lucide="search" class="position-absolute text-muted" style="top: 8px; left: 12px; width: 14px; height: 14px;"></i>
                </div>
                <div class="btn-group btn-group-sm" id="sourceLevelFilter">
                    <button type="button" class="btn btn-outline-secondary rounded-pill active px-3" data-level="ALL">Semua</button>
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-level="NATIONAL">Nasional</button>
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-level="SCHOOL">Sekolah</button>
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-level="TEACHER">Guru</button>
                </div>
            </div>

            <?php if (has_permission('regulations.manage')): ?>
                <button type="button" class="btn btn-primary rounded-pill px-4 btn-sm" data-bs-toggle="modal" data-bs-target="#createNationalTpModal">
                    <i data-lucide="plus-circle" class="me-1" style="width: 16px; height: 16px;"></i> Tambah TP Nasional
                </button>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tpTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Kode TP</th>
                        <th>Pernyataan Tujuan Pembelajaran</th>
                        <th>Tingkat Sumber (Lineage)</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr class="tp-row" data-level="<?= esc(strtoupper($row['source_level'])) ?>">
                            <td class="ps-4">
                                <span class="badge bg-primary bg-opacity-10 text-primary font-monospace px-3 py-2 rounded-pill">
                                    <?= esc($row['code']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark mb-1" style="max-width: 500px;"><?= esc($row['statement']) ?></div>
                            </td>
                            <td>
                                <?php if (strtoupper($row['source_level']) === 'NATIONAL'): ?>
                                    <span class="badge bg-primary text-white rounded-pill px-3 py-1">
                                        <i data-lucide="landmark" class="me-1" style="width: 12px; height: 12px;"></i>Nasional
                                    </span>
                                <?php elseif (strtoupper($row['source_level']) === 'SCHOOL'): ?>
                                    <span class="badge text-white rounded-pill px-3 py-1" style="background-color: #9333ea;">
                                        <i data-lucide="school" class="me-1" style="width: 12px; height: 12px;"></i>Sekolah
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success text-white rounded-pill px-3 py-1">
                                        <i data-lucide="user" class="me-1" style="width: 12px; height: 12px;"></i>Guru
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= strtoupper($row['status'] ?? '') === 'ACTIVE' ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary' ?> rounded-pill px-2 py-1">
                                    <?= esc($row['status'] ?? 'ACTIVE') ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-1">
                                    <?php if (has_permission('learning_objectives.manage')): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-adapt-tp"
                                                data-bs-toggle="modal" data-bs-target="#adaptTpModal"
                                                data-tp-uuid="<?= esc($row['uuid']) ?>"
                                                data-tp-code="<?= esc($row['code']) ?>"
                                                data-tp-statement="<?= esc($row['statement']) ?>"
                                                data-source-level="<?= esc($row['source_level']) ?>">
                                            <i data-lucide="git-branch" class="me-1" style="width: 14px; height: 14px;"></i> Adaptasi
                                        </button>
                                        <?php if ($row['source_level'] !== 'NATIONAL' || has_permission('regulations.manage')): ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2 btn-add-criteria"
                                                    data-bs-toggle="modal" data-bs-target="#addCriteriaModal"
                                                    data-tp-uuid="<?= esc($row['uuid']) ?>"
                                                    data-tp-code="<?= esc($row['code']) ?>">
                                                <i data-lucide="check-square" style="width: 14px; height: 14px;"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i data-lucide="target" class="d-block mx-auto mb-2 text-muted" style="width: 48px; height: 48px;"></i>
                                Belum ada tujuan pembelajaran terdaftar.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (has_permission('regulations.manage')): ?>
<!-- Modal Create National TP -->
<div class="modal fade" id="createNationalTpModal" tabindex="-1" aria-labelledby="createNationalTpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary text-white p-2">
                        <i data-lucide="plus-circle" style="width: 20px; height: 20px;"></i>
                    </div>
                    <h5 class="modal-title fw-bold" id="createNationalTpModalLabel">Tambah TP Standar Nasional</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="<?= base_url('curriculum/objectives') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="source_level" value="NATIONAL">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Capaian Pembelajaran (CP) Induk</label>
                            <select class="form-select rounded-3" name="learning_outcome_id" required>
                                <option value="">Pilih CP...</option>
                                <?php foreach ($outcomeOptions as $cp): ?>
                                    <option value="<?= $cp['id'] ?>"><?= esc($cp['code']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Kode TP</label>
                            <input class="form-control rounded-3" name="code" placeholder="Contoh: TP-INF-E-01" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Pernyataan Tujuan Pembelajaran</label>
                            <textarea class="form-control rounded-3" rows="3" name="statement" placeholder="Murid mampu memahami..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan TP Nasional</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (has_permission('learning_objectives.manage')): ?>
<!-- Modal Adapt TP -->
<div class="modal fade" id="adaptTpModal" tabindex="-1" aria-labelledby="adaptTpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary text-white p-2">
                        <i data-lucide="git-branch" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold" id="adaptTpModalLabel">Buat Adaptasi Tujuan Pembelajaran</h5>
                        <div class="small text-muted" id="adaptTpSourceCode">Sumber Induk: -</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="adaptTpForm" method="post" action="">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 rounded-3 mb-3 small d-flex align-items-center gap-2">
                        <i data-lucide="info" style="width: 18px; height: 18px;"></i>
                        Adaptasi akan mencatat relasi lineage ke TP induk tanpa mengubah teks standar nasional.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Unit Sekolah Target</label>
                            <select class="form-select rounded-3" name="unit_id" required>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= esc($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tingkat Adaptasi</label>
                            <select class="form-select rounded-3" id="adaptSourceLevelSelect" name="source_level" required>
                                <option value="SCHOOL">Tingkat Satuan Pendidikan (Sekolah)</option>
                                <option value="TEACHER">Tingkat Guru (Kelas / RPP)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Pernyataan TP Hasil Kontekstualisasi</label>
                            <textarea class="form-control rounded-3" rows="3" id="adaptStatementInput" name="statement" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Adaptasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Add Criteria -->
<div class="modal fade" id="addCriteriaModal" tabindex="-1" aria-labelledby="addCriteriaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div>
                    <h5 class="modal-title fw-bold" id="addCriteriaModalLabel">Tambah Kriteria Ketercapaian (IKTP)</h5>
                    <div class="small text-muted" id="criteriaTpCode">TP: -</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addCriteriaForm" method="post" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="sort_order" value="1">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Deskripsi Indikator / Kriteria Keberhasilan</label>
                        <textarea class="form-control rounded-3" rows="3" name="description" placeholder="Contoh: Peserta didik mampu menjelaskan langkah analisis masalah dengan tepat..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Kriteria</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.querySelectorAll('#sourceLevelFilter button').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('#sourceLevelFilter button').forEach(b => {
            b.classList.remove('btn-primary', 'active');
            b.classList.add('btn-outline-secondary');
        });
        this.classList.remove('btn-outline-secondary');
        this.classList.add('btn-primary', 'active');

        const level = this.dataset.level;
        document.querySelectorAll('#tpTable tbody tr.tp-row').forEach(tr => {
            if (level === 'ALL' || tr.dataset.level === level) {
                tr.style.display = '';
            } else {
                tr.style.display = 'none';
            }
        });
    });
});

document.getElementById('tpSearchInput')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tpTable tbody tr.tp-row').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

document.querySelectorAll('.btn-adapt-tp').forEach(btn => {
    btn.addEventListener('click', function() {
        const uuid = this.dataset.tpUuid;
        const code = this.dataset.tpCode;
        const stmt = this.dataset.tpStatement;
        const srcLevel = this.dataset.sourceLevel;

        document.getElementById('adaptTpSourceCode').textContent = 'TP Induk: ' + code;
        document.getElementById('adaptStatementInput').value = stmt;
        document.getElementById('adaptTpForm').action = '<?= base_url('curriculum/objectives/') ?>/' + encodeURIComponent(uuid) + '/adapt';

        const sel = document.getElementById('adaptSourceLevelSelect');
        sel.innerHTML = '';
        if (srcLevel === 'NATIONAL') {
            sel.innerHTML += '<option value="SCHOOL">Tingkat Satuan Pendidikan (Sekolah)</option>';
        }
        sel.innerHTML += '<option value="TEACHER">Tingkat Guru (Kelas / RPP)</option>';
    });
});

document.querySelectorAll('.btn-add-criteria').forEach(btn => {
    btn.addEventListener('click', function() {
        const uuid = this.dataset.tpUuid;
        const code = this.dataset.tpCode;
        document.getElementById('criteriaTpCode').textContent = 'TP: ' + code;
        document.getElementById('addCriteriaForm').action = '<?= base_url('curriculum/objectives/') ?>/' + encodeURIComponent(uuid) + '/criteria';
    });
});
</script>
<?= $this->endSection() ?>
