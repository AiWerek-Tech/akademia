<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php 
$pageTitle = 'Capaian Pembelajaran & Elemen'; 
$pageIcon = 'milestone'; 
$pageDescription = 'Capaian Pembelajaran (CP) resmi per fase, mata pelajaran, dan rincian elemen konten/kompetensi.'; 
?>
<?= view('education_foundation/_page_header', compact('pageTitle','pageIcon','pageDescription')) ?>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div class="d-flex flex-wrap gap-2" id="phaseFilterGroup">
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 active" data-phase="ALL">Semua Fase</button>
                <?php foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $ph): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-phase="<?= $ph ?>">Fase <?= $ph ?></button>
                <?php endforeach; ?>
            </div>
            <div class="d-flex align-items-center gap-2 flex-grow-1 justify-content-lg-end">
                <div class="position-relative" style="min-width: 250px; max-width: 350px;">
                    <input type="text" id="cpSearchInput" class="form-control form-control-sm rounded-pill ps-4" placeholder="Cari CP, mapel, atau elemen...">
                    <i data-lucide="search" class="position-absolute text-muted" style="top: 8px; left: 12px; width: 14px; height: 14px;"></i>
                </div>
                <?php if (has_permission('learning_outcomes.manage')): ?>
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 text-nowrap" data-bs-toggle="modal" data-bs-target="#createCpModal">
                        <i data-lucide="plus-circle" class="me-1" style="width: 15px; height: 15px;"></i> Tambah CP
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-3" id="cpGrid">
            <?php foreach ($rows as $row): ?>
                <div class="col-xl-6 cp-card-item" data-phase="<?= esc(strtoupper($row['phase'])) ?>">
                    <article class="card border-0 shadow-sm rounded-4 h-100 p-3 card-hover transition-all" style="background: #fafafa;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary px-3 py-1 rounded-pill font-monospace">
                                            <?= esc($row['code']) ?>
                                        </span>
                                        <span class="badge bg-dark bg-opacity-10 text-dark rounded-pill px-2 py-1">
                                            Fase <?= esc($row['phase']) ?>
                                        </span>
                                    </div>
                                    <span class="badge <?= strtoupper($row['status'] ?? '') === 'ACTIVE' ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary' ?> rounded-pill px-2 py-1">
                                        <?= esc($row['status'] ?? 'ACTIVE') ?>
                                    </span>
                                </div>
                                
                                <h5 class="fw-bold text-dark mb-2"><?= esc($row['subject_name'] ?? 'Mata Pelajaran') ?></h5>
                                <p class="text-muted small mb-3 lh-base"><?= nl2br(esc($row['statement'])) ?></p>

                                <?php if (!empty($row['elements'])): ?>
                                    <div class="p-3 bg-white rounded-3 border mb-3">
                                        <div class="small fw-semibold text-muted text-uppercase mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                            <i data-lucide="layers" class="me-1 text-primary" style="width: 14px; height: 14px;"></i>Elemen Capaian Pembelajaran (<?= count($row['elements']) ?>)
                                        </div>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($row['elements'] as $elem): ?>
                                                <span class="badge bg-light text-dark border px-2 py-1 rounded-3 small">
                                                    <span class="text-primary fw-bold me-1"><?= esc($elem['code']) ?>:</span><?= esc($elem['name']) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (has_permission('learning_outcomes.manage')): ?>
                                <div class="pt-2 border-top d-flex justify-content-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-add-element" 
                                            data-bs-toggle="modal" data-bs-target="#addElementModal" 
                                            data-cp-uuid="<?= esc($row['uuid']) ?>" 
                                            data-cp-title="<?= esc($row['code'].' - '.$row['subject_name']) ?>">
                                        <i data-lucide="plus" class="me-1" style="width: 14px; height: 14px;"></i> Tambah Elemen
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($rows)): ?>
                <div class="col-12 text-center text-muted py-5">
                    <i data-lucide="inbox" class="d-block mx-auto mb-2 text-muted" style="width: 48px; height: 48px;"></i>
                    Belum ada data Capaian Pembelajaran.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (has_permission('learning_outcomes.manage')): ?>
<!-- Modal Create CP -->
<div class="modal fade" id="createCpModal" tabindex="-1" aria-labelledby="createCpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary text-white p-2">
                        <i data-lucide="plus-circle" style="width: 20px; height: 20px;"></i>
                    </div>
                    <h5 class="modal-title fw-bold" id="createCpModalLabel">Tambah Capaian Pembelajaran (CP)</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="<?= base_url('curriculum/outcomes') ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Versi Kurikulum</label>
                            <select class="form-select rounded-3" name="curriculum_version_id" required>
                                <option value="">Pilih versi...</option>
                                <?php foreach ($versions as $v): ?>
                                    <option value="<?= $v['id'] ?>"><?= esc($v['code'].' — '.$v['name']) ?></option>
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
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Kode CP</label>
                            <input class="form-control rounded-3" name="code" placeholder="Contoh: CP-INF-FASE-E" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Fase</label>
                            <select class="form-select rounded-3" name="phase" required>
                                <option value="A">Fase A (Kelas 1-2 SD)</option>
                                <option value="B">Fase B (Kelas 3-4 SD)</option>
                                <option value="C">Fase C (Kelas 5-6 SD)</option>
                                <option value="D">Fase D (Kelas 7-9 SMP)</option>
                                <option value="E" selected>Fase E (Kelas 10 SMA)</option>
                                <option value="F">Fase F (Kelas 11-12 SMA)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Pernyataan Capaian Pembelajaran (CP Statement)</label>
                            <textarea class="form-control rounded-3" rows="4" name="statement" placeholder="Teks utuh capaian pembelajaran..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan CP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Add Element -->
<div class="modal fade" id="addElementModal" tabindex="-1" aria-labelledby="addElementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div>
                    <h5 class="modal-title fw-bold" id="addElementModalLabel">Tambah Elemen CP</h5>
                    <div class="small text-muted" id="modalCpTargetLabel">Target CP</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addElementForm" method="post" action="">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Kode Elemen</label>
                            <input class="form-control rounded-3" name="code" placeholder="Contoh: BK, AD, JKI" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Urutan</label>
                            <input type="number" class="form-control rounded-3" name="sort_order" value="1" min="1" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nama Elemen</label>
                            <input class="form-control rounded-3" name="name" placeholder="Contoh: Berpikir Komputasional" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Elemen</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.querySelectorAll('#phaseFilterGroup button').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('#phaseFilterGroup button').forEach(b => {
            b.classList.remove('btn-primary', 'active');
            b.classList.add('btn-outline-secondary');
        });
        this.classList.remove('btn-outline-secondary');
        this.classList.add('btn-primary', 'active');

        const phase = this.dataset.phase;
        document.querySelectorAll('.cp-card-item').forEach(card => {
            if (phase === 'ALL' || card.dataset.phase === phase) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
});

document.getElementById('cpSearchInput')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.cp-card-item').forEach(card => {
        card.style.display = card.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

document.querySelectorAll('.btn-add-element').forEach(btn => {
    btn.addEventListener('click', function() {
        const uuid = this.dataset.cpUuid;
        const title = this.dataset.cpTitle;
        document.getElementById('modalCpTargetLabel').textContent = title;
        document.getElementById('addElementForm').action = '<?= base_url('curriculum/outcomes/') ?>/' + encodeURIComponent(uuid) + '/elements';
    });
});
</script>

<style>
.transition-all { transition: all 0.2s ease; }
.card-hover:hover { transform: translateY(-3px); box-shadow: 0 8px 20px -4px rgba(0,0,0,0.1) !important; }
</style>
<?= $this->endSection() ?>
