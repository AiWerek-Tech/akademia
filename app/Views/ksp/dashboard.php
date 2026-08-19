<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?= view('ksp/_header', compact('version')) ?>

<?php
$overall = (int)($readiness['overall'] ?? 0);
$readinessColor = $overall >= 100 ? 'success' : ($overall >= 75 ? 'primary' : ($overall >= 50 ? 'warning' : 'danger'));

$sectionMeta = [
    'CHARACTERISTICS' => ['name' => 'Karakteristik Satuan Pendidikan', 'icon' => 'building-2', 'url' => 'context', 'desc' => 'Analisis internal, eksternal, SWOT, dan profil peserta didik'],
    'VISION_MISSION_GOALS' => ['name' => 'Visi, Misi & Tujuan Sekolah', 'icon' => 'telescope', 'url' => 'vision-goals', 'desc' => 'Pernyataan resmi visi, misi, dan target terukur profil lulusan'],
    'ORGANIZATION' => ['name' => 'Pengorganisasian Pembelajaran', 'icon' => 'layers-3', 'url' => 'organization', 'desc' => 'Beban intrakurikuler, alokasi JP, dan model pembelajaran'],
    'INTRACURRICULAR' => ['name' => 'Struktur Intrakurikuler', 'icon' => 'book-open', 'url' => 'organization', 'desc' => 'Mata pelajaran wajib, pilihan, muatan lokal, dan Koding/AI'],
    'COCURRICULAR' => ['name' => 'Kokurikuler & Proyek P5', 'icon' => 'sparkles', 'url' => 'organization', 'desc' => 'Tema tahunan proyek penguatan profil lulusan dan alokasi waktu'],
    'EXTRACURRICULAR' => ['name' => 'Ekstrakurikuler & Karakter', 'icon' => 'trophy', 'url' => 'organization', 'desc' => 'Layanan kepanduan, minat bakat, kebugaran, dan pembentukan karakter'],
    'LEARNING_PLAN' => ['name' => 'Rencana Pembelajaran Sekolah', 'icon' => 'calendar-check', 'url' => 'organization', 'desc' => 'ATP rujukan, kalender akademik, dan strategi asesmen umum'],
    'EVALUATION' => ['name' => 'Evaluasi & Tindak Lanjut', 'icon' => 'chart-no-axes-combined', 'url' => 'evaluation', 'desc' => 'Evaluasi periodik, temuan akar masalah, dan perbaikan terukur'],
    'APPENDICES' => ['name' => 'Lampiran & Evidence Dokumen', 'icon' => 'paperclip', 'url' => 'evidence', 'desc' => 'SK tim pengembang, SK kurikulum, kalender pendidikan, dan regulasi'],
];
?>

<div class="row g-4 mb-4">
    <!-- Overall Readiness Card -->
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <div class="text-uppercase small fw-bold text-muted mb-1" style="letter-spacing: 0.5px;">Indikator Kesiapan Dokumen</div>
                    <h4 class="fw-bold mb-0">Kelengkapan 9 Bagian Kurikulum Satuan Pendidikan</h4>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-<?= $readinessColor ?> bg-opacity-10 text-<?= $readinessColor ?> border border-<?= $readinessColor ?>-subtle rounded-pill px-3 py-2 fs-6">
                        <?= $overall >= 75 ? '<i data-lucide="check" class="me-1" style="width: 16px; height: 16px;"></i>Siap Diajukan' : '<i data-lucide="clock" class="me-1" style="width: 16px; height: 16px;"></i>Penyusunan' ?>
                    </span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-4 mb-4 p-3 bg-light rounded-4">
                <div class="fs-1 fw-bold text-<?= $readinessColor ?> font-monospace"><?= $overall ?>%</div>
                <div class="flex-grow-1">
                    <div class="progress rounded-pill shadow-inner" style="height: 12px;">
                        <div class="progress-bar bg-<?= $readinessColor ?> progress-bar-striped progress-bar-animated" style="width: <?= $overall ?>%"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mt-2">
                        <span>Draft Awal</span>
                        <span>Ambang Review: 75%</span>
                        <span>Lengkap: 100%</span>
                    </div>
                </div>
            </div>

            <!-- 9 Sections Grid -->
            <div class="row g-3">
                <?php foreach ($sections as $section): ?>
                    <?php 
                    $code = $section['section_code'];
                    $meta = $sectionMeta[$code] ?? [
                        'name' => str_replace('_', ' ', ucwords(strtolower($code))),
                        'icon' => 'file-text',
                        'url' => 'dashboard',
                        'desc' => 'Bagian kurikulum satuan pendidikan'
                    ];
                    $pct = (int)$section['completion_percent'];
                    $secColor = $pct >= 100 ? 'success' : ($pct >= 75 ? 'primary' : ($pct > 0 ? 'warning' : 'secondary'));
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border rounded-4 h-100 p-3 card-hover transition-all position-relative" style="background: #ffffff;">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="rounded-3 p-2 bg-<?= $secColor ?> bg-opacity-10 text-<?= $secColor ?>">
                                    <i data-lucide="<?= $meta['icon'] ?>" style="width: 18px; height: 18px;"></i>
                                </div>
                                <span class="badge bg-<?= $secColor ?> bg-opacity-10 text-<?= $secColor ?> rounded-pill font-monospace small px-2 py-1">
                                    <?= $pct ?>%
                                </span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1" style="font-size: 0.9rem;"><?= esc($meta['name']) ?></h6>
                            <p class="small text-muted mb-3" style="font-size: 0.75rem; min-height: 32px;"><?= esc($meta['desc']) ?></p>
                            
                            <div class="progress rounded-pill mb-3" style="height: 4px;">
                                <div class="progress-bar bg-<?= $secColor ?>" style="width: <?= $pct ?>%"></div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <a href="<?= base_url('education/ksp/'.$version['uuid'].'/'.$meta['url']) ?>" class="small text-primary text-decoration-none fw-semibold">
                                    Buka Modul <i data-lucide="arrow-right" class="ms-1" style="width: 12px; height: 12px;"></i>
                                </a>
                                <?php if ($canManage): ?>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-muted btn-edit-section"
                                            data-bs-toggle="modal" data-bs-target="#editSectionModal"
                                            data-section-code="<?= esc($section['section_code']) ?>"
                                            data-section-name="<?= esc($meta['name']) ?>"
                                            data-percent="<?= $pct ?>"
                                            data-notes="<?= esc($section['notes'] ?? '') ?>"
                                            data-revision="<?= (int)$section['revision_number'] ?>">
                                        <i data-lucide="edit-2" style="width: 14px; height: 14px;"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Workflow Approval Panel -->
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="rounded-3 bg-primary text-white p-2">
                    <i data-lucide="git-pull-request" style="width: 20px; height: 20px;"></i>
                </div>
                <h5 class="fw-bold mb-0">Workflow Persetujuan</h5>
            </div>
            
            <p class="small text-muted mb-4">
                KSP mengikuti lifecycle resmi IALOS. Dokumen yang telah di-Approved/Locked dibekukan dan menjadi dasar acuan hukum operasional sekolah.
            </p>

            <!-- Stepper Timeline -->
            <div class="p-3 bg-light rounded-4 mb-4">
                <?php
                $steps = [
                    'DRAFT' => ['label' => 'Draft Penyusunan', 'role' => 'Tim Pengembang Kurikulum', 'icon' => 'edit-3'],
                    'REVIEW' => ['label' => 'Review & Verifikasi', 'role' => 'Wakasek Kurikulum', 'icon' => 'check-square'],
                    'APPROVED' => ['label' => 'Disetujui', 'role' => 'Kepala Satuan Pendidikan', 'icon' => 'award'],
                    'LOCKED' => ['label' => 'Terkunci (Produksi)', 'role' => 'Yayasan / Pengawas', 'icon' => 'lock'],
                ];
                $currentStatus = strtoupper($version['status'] ?? 'DRAFT');
                $stepKeys = array_keys($steps);
                $curIdx = array_search($currentStatus, $stepKeys);
                if ($curIdx === false) $curIdx = 0;
                ?>
                
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($steps as $key => $step): ?>
                        <?php 
                        $isPast = array_search($key, $stepKeys) < $curIdx;
                        $isCurrent = ($key === $currentStatus);
                        $stepClass = $isCurrent ? 'bg-primary text-white shadow-sm' : ($isPast ? 'bg-success bg-opacity-10 text-success' : 'bg-white text-muted border');
                        ?>
                        <div class="d-flex align-items-center gap-3 p-3 rounded-3 <?= $stepClass ?>">
                            <div class="rounded-circle p-2 d-flex align-items-center justify-content-center <?= $isCurrent ? 'bg-white text-primary' : ($isPast ? 'bg-success text-white' : 'bg-light text-muted') ?>" style="width: 32px; height: 32px;">
                                <i data-lucide="<?= $isPast ? 'check' : $step['icon'] ?>" style="width: 16px; height: 16px;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold small"><?= esc($step['label']) ?></div>
                                <div class="small <?= $isCurrent ? 'text-white-50' : 'text-muted' ?>" style="font-size: 0.75rem;"><?= esc($step['role']) ?></div>
                            </div>
                            <?php if ($isCurrent): ?>
                                <span class="badge bg-white text-primary rounded-pill px-2 py-1 small">Aktif</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Transition Actions -->
            <?php
            $targets = [
                'DRAFT' => ['REVIEW', 'Ajukan untuk Direview (Review)', 'ksp.review', 'btn-primary'],
                'REVIEW' => ['APPROVED', 'Setujui KSP (Approve)', 'ksp.approve', 'btn-success'],
                'APPROVED' => ['LOCKED', 'Kunci KSP (Lock)', 'ksp.lock', 'btn-dark'],
                'LOCKED' => ['SUPERSEDED', 'Gantikan Versi (Supersede)', 'ksp.lock', 'btn-secondary'],
            ];
            ?>

            <?php if (isset($targets[$currentStatus])): ?>
                <?php [$target, $label, $perm, $btnStyle] = $targets[$currentStatus]; ?>
                <?php if (has_permission($perm)): ?>
                    <form method="post" action="<?= base_url('education/ksp/'.$version['uuid'].'/transition') ?>" class="mb-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="target_status" value="<?= $target ?>">
                        <input type="hidden" name="revision_number" value="<?= (int)$version['revision_number'] ?>">
                        <button type="submit" class="btn <?= $btnStyle ?> w-100 rounded-pill py-2 fw-semibold">
                            <i data-lucide="arrow-right-circle" class="me-1" style="width: 18px; height: 18px;"></i> <?= $label ?>
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($currentStatus === 'REVIEW' && has_permission('ksp.review')): ?>
                <form method="post" action="<?= base_url('education/ksp/'.$version['uuid'].'/transition') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="target_status" value="DRAFT">
                    <input type="hidden" name="revision_number" value="<?= (int)$version['revision_number'] ?>">
                    <button type="submit" class="btn btn-outline-secondary w-100 rounded-pill py-2">
                        <i data-lucide="rotate-ccw" class="me-1" style="width: 16px; height: 16px;"></i> Kembalikan ke Draft
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($canManage): ?>
<!-- Modal Edit Section Completion -->
<div class="modal fade" id="editSectionModal" tabindex="-1" aria-labelledby="editSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div>
                    <h5 class="modal-title fw-bold" id="editSectionModalLabel">Perbarui Kesiapan Bagian KSP</h5>
                    <div class="small text-muted" id="editSectionTitleLabel">Bagian: -</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSectionForm" method="post" action="">
                <?= csrf_field() ?>
                <input type="hidden" id="editSectionRevision" name="revision_number" value="1">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Persentase Kelengkapan (%)</label>
                        <input type="number" min="0" max="100" class="form-control rounded-3" id="editSectionPercent" name="completion_percent" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Catatan Kelengkapan / Referensi Bukti</label>
                        <textarea class="form-control rounded-3" rows="3" id="editSectionNotes" name="notes" placeholder="Catatan kemajuan penyusunan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.btn-edit-section').forEach(btn => {
    btn.addEventListener('click', function() {
        const code = this.dataset.sectionCode;
        const name = this.dataset.sectionName;
        const pct = this.dataset.percent;
        const notes = this.dataset.notes;
        const rev = this.dataset.revision;

        document.getElementById('editSectionTitleLabel').textContent = name + ' (' + code + ')';
        document.getElementById('editSectionPercent').value = pct;
        document.getElementById('editSectionNotes').value = notes;
        document.getElementById('editSectionRevision').value = rev;
        document.getElementById('editSectionForm').action = '<?= base_url('education/ksp/'.$version['uuid'].'/sections/') ?>/' + encodeURIComponent(code);
    });
});
</script>
<?php endif; ?>

<style>
.transition-all { transition: all 0.25s ease-in-out; }
.card-hover:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1) !important; }
</style>
<?= $this->endSection() ?>
