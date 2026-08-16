<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-4">
    <div><div class="text-uppercase small fw-semibold text-primary mb-1">IALOS Education · Digital KSP</div><h3 class="fw-bold mb-1"><?= esc($version['title']) ?></h3><div class="text-muted"><?= esc($version['code']) ?> · Status <span class="badge text-bg-primary"><?= esc($version['status']) ?></span></div></div>
    <div class="d-flex gap-2"><a class="btn btn-outline-primary" href="<?= base_url('education/ksp') ?>">Semua KSP</a><a class="btn btn-primary" href="<?= base_url('education/ksp/'.$version['uuid']) ?>">Dashboard KSP</a></div>
</div>
<?php if (session('error')): ?><div class="alert alert-danger"><?= esc(session('error')) ?></div><?php endif ?>
<?php if (session('success')): ?><div class="alert alert-success"><?= esc(session('success')) ?></div><?php endif ?>
<div class="row g-2 mb-4">
<?php foreach ([['context','Konteks Sekolah','building-2'],['vision-goals','Visi, Misi & Tujuan','telescope'],['organization','Organisasi Pembelajaran','layers-3'],['evaluation','Evaluasi & Perbaikan','chart-no-axes-combined']] as [$path,$label,$icon]): ?>
<div class="col-md-3"><a class="btn btn-outline-secondary w-100 text-start py-3" href="<?= base_url('education/ksp/'.$version['uuid'].'/'.$path) ?>"><i data-lucide="<?= $icon ?>" class="me-1"></i> <?= $label ?></a></div>
<?php endforeach ?>
</div>
