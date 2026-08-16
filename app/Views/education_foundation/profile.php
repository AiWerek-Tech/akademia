<?= $this->extend('layouts/admin') ?><?= $this->section('main_content') ?>
<?php $pageTitle='Delapan Dimensi Profil Lulusan'; $pageIcon='badge-check'; $pageDescription='Spine kompetensi lintas intrakurikuler, kokurikuler, ekstrakurikuler, asesmen, dan portofolio.'; ?>
<?= view('education_foundation/_page_header', compact('pageTitle','pageIcon','pageDescription')) ?>
<div class="row g-3"><?php foreach($rows as $row): ?><div class="col-md-6 col-xl-3"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body"><span class="badge text-bg-primary mb-3"><?= esc($row['code']) ?></span><h6 class="fw-bold"><?= esc($row['name']) ?></h6><p class="small text-muted mb-0"><?= esc($row['description'] ?? 'Dimensi profil lulusan nasional.') ?></p></div></div></div><?php endforeach ?></div>
<?= $this->endSection() ?>
