<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php 
$pageTitle = 'Paket Pembelajaran Mata Pelajaran'; 
$pageIcon = 'package-open'; 
$pageDescription = 'Paket desain pembelajaran generik yang mengagregasikan TP, ATP, unit, materi esensial, dan aktivitas.'; 
?>
<?= view('education_foundation/_page_header', compact('pageTitle','pageIcon','pageDescription')) ?>

<?php
$totalPacks = count($rows);
$activePacks = count(array_filter($rows, fn($r) => in_array(strtoupper($r['status'] ?? ''), ['ACTIVE', 'APPROVED', 'LOCKED'])));
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3">
                    <i data-lucide="package-open" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= $totalPacks ?></div>
                    <div class="small text-muted">Total Paket Belajar</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 text-success p-3">
                    <i data-lucide="check-circle-2" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-success"><?= $activePacks ?></div>
                    <div class="small text-muted">Paket Aktif / Disetujui</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-info bg-opacity-10 text-info p-3">
                    <i data-lucide="book-copy" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= count(array_unique(array_filter(array_column($rows, 'subject_name')))) ?></div>
                    <div class="small text-muted">Mata Pelajaran Tercakup</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div class="position-relative flex-grow-1" style="max-width: 400px;">
                <input type="text" id="packSearchInput" class="form-control form-control-sm rounded-pill ps-4" placeholder="Cari kode atau nama paket...">
                <i data-lucide="search" class="position-absolute text-muted" style="top: 8px; left: 12px; width: 14px; height: 14px;"></i>
            </div>
            <?php if (has_permission('learning_packs.manage')): ?>
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createPackModal">
                    <i data-lucide="plus-circle" class="me-1" style="width: 16px; height: 16px;"></i> Buat Paket Baru
                </button>
            <?php endif; ?>
        </div>

        <div class="row g-4" id="packGrid">
            <?php foreach ($rows as $row): ?>
                <div class="col-xl-6 pack-card-item">
                    <article class="card border-0 shadow-sm rounded-4 h-100 p-3 card-hover transition-all" style="background: #fafafa; border-top: 4px solid #6366f1 !important;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="badge bg-indigo text-white font-monospace px-3 py-1 rounded-pill" style="background-color: #6366f1;">
                                                <?= esc($row['code']) ?>
                                            </span>
                                            <span class="badge bg-light text-dark border px-2 py-1 rounded-pill small">
                                                Rev <?= (int)$row['revision_number'] ?>
                                            </span>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1"><?= esc($row['name']) ?></h5>
                                        <div class="small text-muted">
                                            <?= esc($row['subject_name']) ?> · <?= esc($row['grade_name']) ?>
                                        </div>
                                    </div>
                                    <span class="badge <?= in_array(strtoupper($row['status'] ?? ''), ['ACTIVE', 'APPROVED', 'LOCKED']) ? 'bg-success text-white' : 'bg-secondary bg-opacity-10 text-secondary border' ?> rounded-pill px-3 py-1">
                                        <?= esc($row['status'] ?? 'DRAFT') ?>
                                    </span>
                                </div>
                            </div>

                            <div class="pt-3 border-top d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                                <a class="btn btn-sm btn-primary rounded-pill px-3" href="<?= base_url('curriculum/learning-packs/'.$row['uuid']) ?>">
                                    <i data-lucide="layout-dashboard" class="me-1" style="width: 14px; height: 14px;"></i> Buka Detail & Unit Belajar
                                </a>

                                <?php if (has_permission('learning_packs.manage')): ?>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-link-tp"
                                                data-bs-toggle="modal" data-bs-target="#linkTpModal"
                                                data-pack-uuid="<?= esc($row['uuid']) ?>"
                                                data-pack-name="<?= esc($row['name']) ?>">
                                            <i data-lucide="target" class="me-1" style="width: 14px; height: 14px;"></i> Taut TP
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 btn-link-atp"
                                                data-bs-toggle="modal" data-bs-target="#linkAtpModal"
                                                data-pack-uuid="<?= esc($row['uuid']) ?>"
                                                data-pack-name="<?= esc($row['name']) ?>">
                                            <i data-lucide="route" class="me-1" style="width: 14px; height: 14px;"></i> Taut ATP
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>

            <?php if (empty($rows)): ?>
                <div class="col-12 text-center text-muted py-5">
                    <i data-lucide="package-open" class="d-block mx-auto mb-2 text-muted" style="width: 48px; height: 48px;"></i>
                    Belum ada paket pembelajaran pada unit aktif.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (has_permission('learning_packs.manage')): ?>
<!-- Modal Create Learning Pack -->
<div class="modal fade" id="createPackModal" tabindex="-1" aria-labelledby="createPackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary text-white p-2">
                        <i data-lucide="plus-circle" style="width: 20px; height: 20px;"></i>
                    </div>
                    <h5 class="modal-title fw-bold" id="createPackModalLabel">Buat Paket Pembelajaran Baru</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="<?= base_url('curriculum/learning-packs') ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Versi Kurikulum</label>
                            <select class="form-select rounded-3" name="curriculum_version_id" required>
                                <option value="">Pilih versi...</option>
                                <?php foreach ($versions as $v): ?>
                                    <option value="<?= $v['id'] ?>"><?= esc($v['code']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Unit Sekolah</label>
                            <select class="form-select rounded-3" name="unit_id" required>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= esc($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Mata Pelajaran</label>
                            <select class="form-select rounded-3" name="subject_id" required>
                                <option value="">Pilih mapel...</option>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= esc($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tingkat Kelas</label>
                            <select class="form-select rounded-3" name="grade_level_id" required>
                                <option value="">Pilih tingkat...</option>
                                <?php foreach ($gradeLevels as $g): ?>
                                    <option value="<?= $g['id'] ?>"><?= esc($g['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kode Paket</label>
                            <input class="form-control rounded-3" name="code" placeholder="Contoh: PACK-INF-X" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Nama Paket Pembelajaran</label>
                            <input class="form-control rounded-3" name="name" placeholder="Contoh: Paket Belajar Informatika Kelas X" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Paket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Link TP -->
<div class="modal fade" id="linkTpModal" tabindex="-1" aria-labelledby="linkTpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div>
                    <h5 class="modal-title fw-bold" id="linkTpModalLabel">Tautkan Tujuan Pembelajaran</h5>
                    <div class="small text-muted" id="linkTpPackName">Paket: -</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="linkTpForm" method="post" action="">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Pilih Tujuan Pembelajaran</label>
                        <select class="form-select rounded-3" name="objective_uuid" required>
                            <option value="">Pilih TP...</option>
                            <?php foreach ($objectiveOptions as $tp): ?>
                                <option value="<?= esc($tp['uuid']) ?>"><?= esc($tp['code'].' - '.$tp['statement']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Tautkan TP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Link ATP -->
<div class="modal fade" id="linkAtpModal" tabindex="-1" aria-labelledby="linkAtpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div>
                    <h5 class="modal-title fw-bold" id="linkAtpModalLabel">Tautkan Alur Tujuan Pembelajaran</h5>
                    <div class="small text-muted" id="linkAtpPackName">Paket: -</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="linkAtpForm" method="post" action="">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Pilih Alur (ATP)</label>
                        <select class="form-select rounded-3" name="sequence_uuid" required>
                            <option value="">Pilih ATP...</option>
                            <?php foreach ($sequenceOptions as $atp): ?>
                                <option value="<?= esc($atp['uuid']) ?>"><?= esc($atp['code'].' - '.$atp['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Tautkan ATP</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.getElementById('packSearchInput')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.pack-card-item').forEach(card => {
        card.style.display = card.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

document.querySelectorAll('.btn-link-tp').forEach(btn => {
    btn.addEventListener('click', function() {
        const uuid = this.dataset.packUuid;
        const name = this.dataset.packName;
        document.getElementById('linkTpPackName').textContent = 'Paket: ' + name;
        document.getElementById('linkTpForm').action = '<?= base_url('curriculum/learning-packs/') ?>/' + encodeURIComponent(uuid) + '/objectives';
    });
});

document.querySelectorAll('.btn-link-atp').forEach(btn => {
    btn.addEventListener('click', function() {
        const uuid = this.dataset.packUuid;
        const name = this.dataset.packName;
        document.getElementById('linkAtpPackName').textContent = 'Paket: ' + name;
        document.getElementById('linkAtpForm').action = '<?= base_url('curriculum/learning-packs/') ?>/' + encodeURIComponent(uuid) + '/sequences';
    });
});
</script>

<style>
.transition-all { transition: all 0.25s ease-in-out; }
.card-hover:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1) !important; }
</style>
<?= $this->endSection() ?>
