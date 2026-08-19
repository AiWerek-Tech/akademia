<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
    <div class="card-body p-4 text-white">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-4 bg-primary bg-opacity-25 text-primary-subtle p-3 d-flex align-items-center justify-content-center shadow-inner" style="width: 56px; height: 56px; backdrop-filter: blur(8px);">
                    <i data-lucide="<?= esc($pageIcon ?? 'network') ?>" style="width: 28px; height: 28px; color: #60a5fa;"></i>
                </div>
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 small">
                            <li class="breadcrumb-item"><a href="<?= base_url('education') ?>" class="text-white-50 text-decoration-none">IALOS Education</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page"><?= esc($pageTitle) ?></li>
                        </ol>
                    </nav>
                    <h3 class="fw-bold text-white mb-1"><?= esc($pageTitle) ?></h3>
                    <p class="text-white text-opacity-75 mb-0 small"><?= esc($pageDescription) ?></p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <?php if (isset($headerActions)): ?>
                    <?= $headerActions ?>
                <?php endif; ?>
                <a href="<?= base_url('education') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3 py-2">
                    <i data-lucide="layout-dashboard" class="me-1" style="width: 16px; height: 16px;"></i> Control Center
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
