<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php $readyPercent = (int) round(($readinessDone / max(1, count($readiness))) * 100); ?>
<section aria-labelledby="dashboard-title">
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background:linear-gradient(135deg,#17324d,#2563eb);">
        <div class="card-body p-4 p-lg-5 text-white d-flex flex-column flex-lg-row justify-content-between gap-4">
            <div><span class="badge bg-white bg-opacity-15 text-white rounded-pill mb-3">Ringkasan Akademik</span><h2 id="dashboard-title" class="fw-bold mb-2">Halo, <?= esc(active_user_name()) ?></h2><p class="mb-0 text-white text-opacity-75">Pantau kesiapan data dan lanjutkan pekerjaan yang paling penting dari satu tempat.</p></div>
            <div class="bg-white bg-opacity-10 rounded-4 p-3 align-self-lg-center" style="min-width:260px"><div class="small text-white text-opacity-75">Periode aktif</div><div class="fw-bold mt-1"><?= esc($activePeriodStr) ?></div><?php if (!$activePeriod): ?><a href="<?= base_url('academic-periods') ?>" class="btn btn-sm btn-light mt-2">Atur periode</a><?php endif; ?></div>
        </div>
    </div>

    <?php if (session('success')): ?><div class="alert alert-success rounded-3"><?= esc(session('success')) ?></div><?php endif; ?>
    <?php if (session('error')): ?><div class="alert alert-danger rounded-3"><?= esc(session('error')) ?></div><?php endif; ?>

    <div class="row g-3 mb-4">
        <?php foreach ([['Unit aktif',$totalUnits,'building-2','primary'],['Pengguna aktif',$totalUsers,'users','info'],['Guru aktif',$masterCounts['teachers'],'user-round-check','success'],['Rombel aktif',$masterCounts['classrooms'],'graduation-cap','warning']] as [$label,$value,$icon,$color]): ?>
            <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-3 p-md-4 d-flex align-items-center gap-3"><span class="bg-<?= $color ?> bg-opacity-10 text-<?= $color ?> rounded-3 p-3"><i data-lucide="<?= $icon ?>" style="width:22px;height:22px"></i></span><div><div class="text-muted fs-8 text-uppercase fw-semibold"><?= esc($label) ?></div><div class="fs-4 fw-bold text-slate-800"><?= number_format((int)$value) ?></div></div></div></div></div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-start mb-3"><div><h5 class="fw-bold mb-1">Kesiapan Data Master</h5><p class="text-muted fs-8 mb-0">Lengkapi fondasi sebelum menyusun penugasan dan jadwal.</p></div><span class="badge bg-primary bg-opacity-10 text-primary rounded-pill"><?= $readinessDone ?>/<?= count($readiness) ?> siap</span></div><div class="progress mb-4" style="height:8px"><div class="progress-bar" style="width:<?= $readyPercent ?>%"></div></div><div class="row g-2"><?php foreach ($readiness as $item): ?><div class="col-md-6"><a href="<?= base_url($item['href']) ?>" class="d-flex align-items-center justify-content-between border rounded-3 p-3 text-decoration-none text-slate-700"><span class="d-flex align-items-center gap-2"><i data-lucide="<?= $item['ready'] ? 'check-circle-2' : 'circle-alert' ?>" class="text-<?= $item['ready'] ? 'success' : 'warning' ?>" style="width:18px;height:18px"></i><?= esc($item['label']) ?></span><i data-lucide="chevron-right" style="width:16px;height:16px"></i></a></div><?php endforeach; ?></div></div></div></div>
        <div class="col-lg-5"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4"><h5 class="fw-bold mb-1">Akses Cepat</h5><p class="text-muted fs-8 mb-3">Tindakan yang sering digunakan.</p><div class="d-grid gap-2">
            <?php if (has_permission('academic_periods.view')): ?><a href="<?= base_url('academic-periods') ?>" class="btn btn-outline-primary text-start rounded-3"><i data-lucide="calendar-range" class="me-2" style="width:17px"></i>Tahun & Periode Akademik</a><?php endif; ?>
            <?php if (has_permission('units.view')): ?><a href="<?= base_url('settings/units') ?>" class="btn btn-outline-primary text-start rounded-3"><i data-lucide="building" class="me-2" style="width:17px"></i>Profil Unit Sekolah</a><?php endif; ?>
            <?php if (has_permission('teachers.view')): ?><a href="<?= base_url('teachers') ?>" class="btn btn-outline-primary text-start rounded-3"><i data-lucide="users" class="me-2" style="width:17px"></i>Kelola Guru</a><?php endif; ?>
            <?php if (has_permission('teachers.import') || has_permission('subjects.import')): ?><a href="<?= base_url('imports/master') ?>" class="btn btn-primary text-start rounded-3"><i data-lucide="file-up" class="me-2" style="width:17px"></i>Import Master Data</a><?php endif; ?>
        </div></div></div></div>
    </div>

    <div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-start mb-3"><div><h5 class="fw-bold mb-1">Aktivitas Terbaru</h5><p class="text-muted fs-8 mb-0">Jejak perubahan terbaru pada sistem.</p></div></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Pengguna</th><th>Modul</th><th>Aksi</th><th>Catatan</th><th>Waktu</th></tr></thead><tbody><?php if (!$recentAudits): ?><tr><td colspan="5" class="text-center py-5 text-muted">Belum ada aktivitas tercatat.</td></tr><?php else: foreach ($recentAudits as $log): ?><tr><td class="fw-semibold"><?= esc($log['username'] ?? 'Sistem') ?></td><td><span class="badge bg-light text-slate-600"><?= esc($log['module']) ?></span></td><td><?= esc(strtoupper($log['action'])) ?></td><td class="text-muted"><?= esc($log['reason'] ?: '-') ?></td><td class="text-nowrap text-muted fs-8"><?= date('d-m-Y H:i', strtotime($log['created_at'])) ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div></div>
</section>
<script>document.addEventListener('DOMContentLoaded',function(){if(typeof lucide!=='undefined')lucide.createIcons();});</script>
<?= $this->endSection() ?>
