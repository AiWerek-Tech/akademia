<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php 
$pageTitle = 'Alur Tujuan Pembelajaran (ATP)'; 
$pageIcon = 'route'; 
$pageDescription = 'Alur perancangan urutan TP lintas semester/tahun dengan workflow persetujuan berjenjang.'; 
?>
<?= view('education_foundation/_page_header', compact('pageTitle','pageIcon','pageDescription')) ?>

<?php
$totalSequences = count($rows);
$approvedCount = count(array_filter($rows, fn($r) => in_array(strtoupper($r['workflow_status'] ?? ''), ['APPROVED', 'LOCKED'])));
$draftCount = count(array_filter($rows, fn($r) => strtoupper($r['workflow_status'] ?? '') === 'DRAFT'));
$reviewCount = count(array_filter($rows, fn($r) => in_array(strtoupper($r['workflow_status'] ?? ''), ['VALIDATED', 'REVIEWED'])));
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3">
                    <i data-lucide="route" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= $totalSequences ?></div>
                    <div class="small text-muted">Total Alur (ATP)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-secondary bg-opacity-10 text-secondary p-3">
                    <i data-lucide="edit-3" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= $draftCount ?></div>
                    <div class="small text-muted">Draft Penyusunan</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 text-warning p-3">
                    <i data-lucide="clock" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-warning"><?= $reviewCount ?></div>
                    <div class="small text-muted">Review & Validasi</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 text-success p-3">
                    <i data-lucide="check-circle" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-success"><?= $approvedCount ?></div>
                    <div class="small text-muted">Disetujui / Locked</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div class="position-relative flex-grow-1" style="max-width: 400px;">
                <input type="text" id="atpSearchInput" class="form-control form-control-sm rounded-pill ps-4" placeholder="Cari kode atau nama ATP...">
                <i data-lucide="search" class="position-absolute text-muted" style="top: 8px; left: 12px; width: 14px; height: 14px;"></i>
            </div>
            <?php if (has_permission('learning_sequences.manage')): ?>
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createAtpModal">
                    <i data-lucide="plus-circle" class="me-1" style="width: 16px; height: 16px;"></i> Buat ATP Baru
                </button>
            <?php endif; ?>
        </div>

        <div class="row g-4" id="atpGrid">
            <?php foreach ($rows as $row): ?>
                <div class="col-xl-6 atp-card-item">
                    <article class="card border-0 shadow-sm rounded-4 h-100 p-3 card-hover transition-all" style="background: #fafafa; border-top: 4px solid #3b82f6 !important;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div>
                                        <span class="badge bg-primary bg-opacity-10 text-primary font-monospace px-3 py-1 rounded-pill mb-1">
                                            <?= esc($row['code']) ?>
                                        </span>
                                        <h5 class="fw-bold text-dark mb-1"><?= esc($row['name']) ?></h5>
                                        <div class="small text-muted">
                                            Fase <?= esc($row['phase']) ?> · Revisi <?= (int)$row['revision_number'] ?>
                                        </div>
                                    </div>
                                    <?php
                                    $st = strtoupper($row['workflow_status'] ?? 'DRAFT');
                                    $stBadge = match($st) {
                                        'APPROVED' => 'bg-success text-white',
                                        'LOCKED' => 'bg-dark text-white',
                                        'VALIDATED', 'REVIEWED' => 'bg-warning text-dark',
                                        default => 'bg-secondary bg-opacity-10 text-secondary border'
                                    };
                                    ?>
                                    <span class="badge <?= $stBadge ?> rounded-pill px-3 py-1">
                                        <?= esc($st) ?>
                                    </span>
                                </div>

                                <!-- Workflow stepper mini -->
                                <div class="p-2 bg-white rounded-3 border my-3">
                                    <div class="small fw-semibold text-muted text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                        Tahapan Workflow Persetujuan
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <?php 
                                        $stages = ['DRAFT', 'VALIDATED', 'REVIEWED', 'APPROVED', 'LOCKED'];
                                        $currentIndex = array_search($st, $stages);
                                        if ($currentIndex === false) $currentIndex = 0;
                                        ?>
                                        <?php foreach ($stages as $idx => $stage): ?>
                                            <div class="flex-grow-1 text-center py-1 px-1 rounded-2 small <?= $idx <= $currentIndex ? 'bg-primary text-white fw-bold' : 'bg-light text-muted' ?>" style="font-size: 0.7rem;">
                                                <?= $stage ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-3 border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div class="d-flex gap-1 flex-wrap">
                                    <?php if (has_permission('learning_sequences.manage') && in_array($st, ['DRAFT', 'VALIDATED'], true)): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-add-tp-seq"
                                                data-bs-toggle="modal" data-bs-target="#addTpToSeqModal"
                                                data-seq-uuid="<?= esc($row['uuid']) ?>"
                                                data-seq-name="<?= esc($row['name']) ?>">
                                            <i data-lucide="plus" class="me-1" style="width: 14px; height: 14px;"></i> Tambah TP
                                        </button>
                                    <?php endif; ?>

                                    <?php if (has_permission('learning_sequences.manage')): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 btn-clone-seq"
                                                data-bs-toggle="modal" data-bs-target="#cloneSeqModal"
                                                data-seq-uuid="<?= esc($row['uuid']) ?>"
                                                data-seq-code="<?= esc($row['code']) ?>">
                                            <i data-lucide="copy" class="me-1" style="width: 14px; height: 14px;"></i> Klon Revisi
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <button type="button" class="btn btn-sm btn-success rounded-pill px-3 btn-transition-seq"
                                            data-bs-toggle="modal" data-bs-target="#transitionSeqModal"
                                            data-seq-uuid="<?= esc($row['uuid']) ?>"
                                            data-seq-code="<?= esc($row['code']) ?>"
                                            data-current-status="<?= esc($st) ?>"
                                            data-revision="<?= (int)$row['revision_number'] ?>">
                                        <i data-lucide="arrow-right-circle" class="me-1" style="width: 14px; height: 14px;"></i> Transisi Status
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>

            <?php if (empty($rows)): ?>
                <div class="col-12 text-center text-muted py-5">
                    <i data-lucide="route" class="d-block mx-auto mb-2 text-muted" style="width: 48px; height: 48px;"></i>
                    Belum ada Alur Tujuan Pembelajaran (ATP) pada unit aktif.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (has_permission('learning_sequences.manage')): ?>
<!-- Modal Create ATP -->
<div class="modal fade" id="createAtpModal" tabindex="-1" aria-labelledby="createAtpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary text-white p-2">
                        <i data-lucide="plus-circle" style="width: 20px; height: 20px;"></i>
                    </div>
                    <h5 class="modal-title fw-bold" id="createAtpModalLabel">Buat Alur Tujuan Pembelajaran (ATP)</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="<?= base_url('curriculum/sequences') ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Versi Kurikulum</label>
                            <select class="form-select rounded-3" name="curriculum_version_id" required>
                                <option value="">Pilih versi...</option>
                                <?php foreach ($versions as $v): ?>
                                    <option value="<?= $v['id'] ?>"><?= esc($v['code']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Unit Sekolah</label>
                            <select class="form-select rounded-3" name="unit_id" required>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= esc($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Mata Pelajaran</label>
                            <select class="form-select rounded-3" name="subject_id" required>
                                <option value="">Pilih mapel...</option>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= esc($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tingkat Kelas</label>
                            <select class="form-select rounded-3" name="grade_level_id" required>
                                <option value="">Pilih tingkat...</option>
                                <?php foreach ($gradeLevels as $g): ?>
                                    <option value="<?= $g['id'] ?>"><?= esc($g['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Fase</label>
                            <input class="form-control rounded-3" name="phase" placeholder="Contoh: E, F" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kode ATP</label>
                            <input class="form-control rounded-3" name="code" placeholder="Contoh: ATP-INF-X-2026" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nama Lengkap ATP</label>
                            <input class="form-control rounded-3" name="name" placeholder="Contoh: Alur Tujuan Pembelajaran Informatika Kelas X Semester 1 & 2" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Buat ATP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Add TP to Sequence -->
<div class="modal fade" id="addTpToSeqModal" tabindex="-1" aria-labelledby="addTpToSeqModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div>
                    <h5 class="modal-title fw-bold" id="addTpToSeqModalLabel">Tambah TP ke Urutan ATP</h5>
                    <div class="small text-muted" id="addTpSeqTitle">ATP: -</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addTpSeqForm" method="post" action="">
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
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Tautkan ke ATP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Clone Sequence -->
<div class="modal fade" id="cloneSeqModal" tabindex="-1" aria-labelledby="cloneSeqModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div>
                    <h5 class="modal-title fw-bold" id="cloneSeqModalLabel">Klon / Buat Revisi Baru ATP</h5>
                    <div class="small text-muted" id="cloneSeqSourceLabel">Sumber: -</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="cloneSeqForm" method="post" action="">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kode Revisi Baru</label>
                        <input class="form-control rounded-3" id="cloneSeqCodeInput" name="code" required>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Klon Dokumen</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Transition Sequence Status -->
<div class="modal fade" id="transitionSeqModal" tabindex="-1" aria-labelledby="transitionSeqModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div>
                    <h5 class="modal-title fw-bold" id="transitionSeqModalLabel">Perbarui Status Workflow ATP</h5>
                    <div class="small text-muted" id="transitionSeqTargetLabel">ATP: -</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="transitionSeqForm" method="post" action="">
                <?= csrf_field() ?>
                <input type="hidden" id="transitionRevisionInput" name="revision_number" value="1">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Target Status Baru</label>
                        <select class="form-select rounded-3" name="target_status" required>
                            <option value="">Pilih status berikutnya...</option>
                            <?php if (has_permission('learning_sequences.validate')): ?>
                                <option value="VALIDATED">VALIDATED (Telah Divalidasi Kurikulum)</option>
                            <?php endif; ?>
                            <?php if (has_permission('learning_sequences.review')): ?>
                                <option value="REVIEWED">REVIEWED (Telah Direview)</option>
                            <?php endif; ?>
                            <?php if (has_permission('learning_sequences.approve')): ?>
                                <option value="APPROVED">APPROVED (Disetujui Kepala Sekolah)</option>
                            <?php endif; ?>
                            <?php if (has_permission('learning_sequences.lock')): ?>
                                <option value="LOCKED">LOCKED (Terkunci untuk Produksi)</option>
                                <option value="ARCHIVED">ARCHIVED (Diarsipkan)</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4">Proses Transisi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('atpSearchInput')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.atp-card-item').forEach(card => {
        card.style.display = card.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

document.querySelectorAll('.btn-add-tp-seq').forEach(btn => {
    btn.addEventListener('click', function() {
        const uuid = this.dataset.seqUuid;
        const name = this.dataset.seqName;
        document.getElementById('addTpSeqTitle').textContent = 'ATP: ' + name;
        document.getElementById('addTpSeqForm').action = '<?= base_url('curriculum/sequences/') ?>/' + encodeURIComponent(uuid) + '/items';
    });
});

document.querySelectorAll('.btn-clone-seq').forEach(btn => {
    btn.addEventListener('click', function() {
        const uuid = this.dataset.seqUuid;
        const code = this.dataset.seqCode;
        document.getElementById('cloneSeqSourceLabel').textContent = 'Sumber: ' + code;
        document.getElementById('cloneSeqCodeInput').value = code + '-R2';
        document.getElementById('cloneSeqForm').action = '<?= base_url('curriculum/sequences/') ?>/' + encodeURIComponent(uuid) + '/clone';
    });
});

document.querySelectorAll('.btn-transition-seq').forEach(btn => {
    btn.addEventListener('click', function() {
        const uuid = this.dataset.seqUuid;
        const code = this.dataset.seqCode;
        const rev = this.dataset.revision;
        document.getElementById('transitionSeqTargetLabel').textContent = 'ATP: ' + code + ' (Status saat ini: ' + this.dataset.currentStatus + ')';
        document.getElementById('transitionRevisionInput').value = rev;
        document.getElementById('transitionSeqForm').action = '<?= base_url('curriculum/sequences/') ?>/' + encodeURIComponent(uuid) + '/transition';
    });
});
</script>

<style>
.transition-all { transition: all 0.25s ease-in-out; }
.card-hover:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1) !important; }
</style>
<?= $this->endSection() ?>
