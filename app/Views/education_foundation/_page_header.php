<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-4">
    <div>
        <div class="text-uppercase small fw-semibold text-primary mb-1">IALOS Education</div>
        <h3 class="fw-bold mb-2"><i data-lucide="<?= esc($pageIcon ?? 'network') ?>" class="text-primary me-2"></i><?= esc($pageTitle) ?></h3>
        <p class="text-muted mb-0"><?= esc($pageDescription) ?></p>
    </div>
    <a href="<?= base_url('education') ?>" class="btn btn-outline-primary"><i data-lucide="layout-dashboard" class="me-1"></i> Control Center</a>
</div>
<?php if (session('error')): ?><div class="alert alert-danger" role="alert"><?= esc(session('error')) ?></div><?php endif ?>
<?php if (session('success')): ?><div class="alert alert-success" role="status"><?= esc(session('success')) ?></div><?php endif ?>
