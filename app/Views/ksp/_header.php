<?php
$currentUri = service('request')->getUri()->getPath();
$activeModule = 'overview';
if (str_contains($currentUri, '/context')) $activeModule = 'context';
elseif (str_contains($currentUri, '/vision-goals')) $activeModule = 'vision-goals';
elseif (str_contains($currentUri, '/organization')) $activeModule = 'organization';
elseif (str_contains($currentUri, '/evaluation')) $activeModule = 'evaluation';
elseif (str_contains($currentUri, '/evidence')) $activeModule = 'evidence';
elseif (str_contains($currentUri, '/compliance')) $activeModule = 'compliance';
elseif (str_contains($currentUri, '/documents')) $activeModule = 'documents';

$status = strtoupper($version['status'] ?? 'DRAFT');
$statusBadge = match($status) {
    'APPROVED' => 'bg-success text-white',
    'LOCKED' => 'bg-dark text-white',
    'REVIEW' => 'bg-warning text-dark',
    'SUPERSEDED' => 'bg-secondary text-white',
    default => 'bg-primary bg-opacity-10 text-primary border border-primary-subtle'
};
?>

<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);">
    <div class="card-body p-4 text-white">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-4 p-3 d-flex align-items-center justify-content-center shadow-inner" style="width: 56px; height: 56px; background: rgba(99, 102, 241, 0.2); backdrop-filter: blur(8px);">
                    <i data-lucide="book-open-check" style="width: 28px; height: 28px; color: #818cf8;"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <span class="text-uppercase small fw-bold text-indigo-300" style="color: #a5b4fc; letter-spacing: 0.5px;">IALOS Digital KSP</span>
                        <span class="text-white-50">·</span>
                        <span class="badge bg-white bg-opacity-10 text-white rounded-pill font-monospace px-2 py-1"><?= esc($version['code']) ?></span>
                        <span class="badge <?= $statusBadge ?> rounded-pill px-3 py-1"><?= esc($status) ?></span>
                    </div>
                    <h3 class="fw-bold text-white mb-1"><?= esc($version['title']) ?></h3>
                    <div class="small text-white-50">Revisi ke-<?= (int)$version['revision_number'] ?> · Kurikulum Satuan Pendidikan Hidup</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a class="btn btn-sm btn-outline-light rounded-pill px-3 py-2" href="<?= base_url('education/ksp') ?>">
                    <i data-lucide="arrow-left" class="me-1" style="width: 15px; height: 15px;"></i> Semua KSP
                </a>
                <a class="btn btn-sm <?= $activeModule === 'overview' ? 'btn-primary text-white' : 'btn-outline-light' ?> rounded-pill px-4 py-2" href="<?= base_url('education/ksp/'.$version['uuid']) ?>">
                    <i data-lucide="layout-dashboard" class="me-1" style="width: 15px; height: 15px;"></i> Dashboard KSP
                </a>
            </div>
        </div>
    </div>
</div>

<?php if (session('error')): ?>
    <div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex align-items-center gap-3 mb-4 p-3" role="alert">
        <div class="rounded-circle bg-danger bg-opacity-25 p-2 d-flex align-items-center justify-content-center text-danger">
            <i data-lucide="alert-circle" style="width: 20px; height: 20px;"></i>
        </div>
        <div class="flex-grow-1">
            <div class="fw-semibold">Terjadi Kendala</div>
            <div class="small"><?= esc(session('error')) ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif ?>

<?php if (session('success')): ?>
    <div class="alert alert-success border-0 shadow-sm rounded-4 d-flex align-items-center gap-3 mb-4 p-3" role="status">
        <div class="rounded-circle bg-success bg-opacity-25 p-2 d-flex align-items-center justify-content-center text-success">
            <i data-lucide="check-circle-2" style="width: 20px; height: 20px;"></i>
        </div>
        <div class="flex-grow-1">
            <div class="fw-semibold">Berhasil</div>
            <div class="small"><?= esc(session('success')) ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif ?>

<!-- Segmented Modern Navigation Pills -->
<div class="card border-0 shadow-sm rounded-4 mb-4 p-2 bg-light">
    <div class="d-flex flex-wrap gap-2">
        <?php
        $navLinks = [
            ['context', 'Konteks Sekolah', 'building-2'],
            ['vision-goals', 'Visi, Misi & Tujuan', 'telescope'],
            ['organization', 'Organisasi Belajar', 'layers-3'],
            ['evaluation', 'Evaluasi & Tindak Lanjut', 'chart-no-axes-combined'],
            ['evidence', 'Evidence Vault', 'paperclip'],
            ['compliance', 'Kepatuhan Regulasi', 'shield-check'],
            ['documents', 'Dokumen KSP (PDF/DOCX)', 'file-output'],
        ];
        ?>
        <?php foreach ($navLinks as [$path, $label, $icon]): ?>
            <?php $isActive = ($activeModule === $path); ?>
            <a class="btn btn-sm <?= $isActive ? 'btn-primary text-white shadow-sm' : 'btn-outline-secondary border-0 text-dark bg-white' ?> rounded-pill px-3 py-2 text-decoration-none d-flex align-items-center gap-2 transition-all flex-grow-1 flex-md-grow-0 justify-content-center" 
               href="<?= base_url('education/ksp/'.$version['uuid'].'/'.$path) ?>">
                <i data-lucide="<?= $icon ?>" style="width: 16px; height: 16px;"></i>
                <span class="fw-medium small"><?= $label ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
