<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php
$personaCopy = [
    'platform' => ['Control Center Platform', 'Kelola referensi nasional, governance, keamanan, dan kesiapan seluruh unit.'],
    'executive' => ['Control Center Pimpinan', 'Pantau kesiapan kurikulum dan pekerjaan akademik yang membutuhkan keputusan.'],
    'curriculum' => ['Control Center Kurikulum', 'Kelola adaptasi sekolah, CP/TP/ATP, coverage, dan paket pembelajaran.'],
    'teacher' => ['Control Center Guru', 'Susun tujuan, ATP, dan paket pembelajaran dalam scope penugasan Anda.'],
    'viewer' => ['Control Center Akademik', 'Lihat referensi dan progres pendidikan sesuai hak akses Anda.'],
];
$hero=$personaCopy[$control['persona']] ?? $personaCopy['viewer'];
$areas=[
    ['permission'=>'ksp.view','href'=>'education/ksp','icon'=>'book-open-check','title'=>'Digital KSP','copy'=>'Control penyusunan, review, persetujuan, dan evaluasi KSP.'],
    ['permission'=>'regulations.view','href'=>'references/regulations','icon'=>'landmark','title'=>'Regulasi Pendidikan','copy'=>'Registry regulasi dan versi sumber resmi.'],
    ['permission'=>'curriculum_sources.view','href'=>'references/curriculum-sources','icon'=>'library','title'=>'Sumber Kurikulum','copy'=>'Provenance dan referensi kurikulum resmi.'],
    ['permission'=>'graduate_profile.view','href'=>'references/graduate-profile','icon'=>'badge-check','title'=>'Profil Lulusan','copy'=>'Delapan dimensi lintas pengalaman belajar.'],
    ['permission'=>'learning_outcomes.view','href'=>'curriculum/outcomes','icon'=>'milestone','title'=>'CP & Elemen','copy'=>'Capaian pembelajaran per versi, mapel, dan fase.'],
    ['permission'=>'learning_objectives.view','href'=>'curriculum/objectives','icon'=>'target','title'=>'Tujuan Pembelajaran','copy'=>'TP nasional serta adaptasi sekolah dan guru.'],
    ['permission'=>'learning_sequences.view','href'=>'curriculum/sequences','icon'=>'route','title'=>'ATP','copy'=>'Urutan TP dengan workflow validasi dan approval.'],
    ['permission'=>'learning_sequences.view','href'=>'curriculum/coverage','icon'=>'scan-search','title'=>'Coverage','copy'=>'Deteksi TP belum tercakup, duplikat, dan mismatch.'],
    ['permission'=>'learning_packs.view','href'=>'curriculum/learning-packs','icon'=>'package-open','title'=>'Paket Pembelajaran','copy'=>'Hubungkan paket mapel dengan TP dan ATP.'],
];
?>
<section class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4" style="background:linear-gradient(135deg,#172554,#4338ca);">
    <div class="card-body p-4 p-lg-5 text-white d-flex flex-column flex-lg-row justify-content-between gap-4">
        <div><span class="badge bg-white bg-opacity-10 text-white rounded-pill mb-3">Satu platform akademik terpadu</span><h2 class="fw-bold mb-2"><?= esc($hero[0]) ?></h2><p class="text-white text-opacity-75 mb-0"><?= esc($hero[1]) ?></p></div>
        <div class="bg-white bg-opacity-10 rounded-4 p-3 align-self-lg-center"><div class="small text-white text-opacity-75">Cakupan aktif</div><div class="fs-4 fw-bold"><?= (int)$control['unit_count'] ?> unit sekolah</div></div>
    </div>
</section>
<div class="row g-3 mb-4">
<?php foreach([['Digital KSP','ksp_versions','book-open-check'],['Regulasi','regulations','landmark'],['CP','outcomes','milestone'],['TP efektif','objectives','target'],['ATP','sequences','route'],['Paket','packs','package-open'],['Import tertunda','pending_imports','file-clock']] as $metric): ?>
    <div class="col-6 col-lg"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body"><i data-lucide="<?= $metric[2] ?>" class="text-primary mb-2"></i><div class="fs-3 fw-bold"><?= (int)$control['metrics'][$metric[1]] ?></div><div class="small text-muted"><?= esc($metric[0]) ?></div></div></div></div>
<?php endforeach ?>
</div>
<div class="row g-4">
    <div class="col-xl-8"><h5 class="fw-bold mb-3">Area kerja</h5><div class="row g-3">
    <?php foreach($areas as $area): if(!has_permission($area['permission'])) continue; ?><div class="col-md-6"><a href="<?= base_url($area['href']) ?>" class="card border-0 shadow-sm rounded-4 h-100 text-decoration-none text-body"><div class="card-body d-flex gap-3"><div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3 align-self-start"><i data-lucide="<?= $area['icon'] ?>"></i></div><div><h6 class="fw-bold mb-1"><?= esc($area['title']) ?></h6><p class="small text-muted mb-0"><?= esc($area['copy']) ?></p></div></div></a></div><?php endforeach ?>
    </div></div>
    <div class="col-xl-4"><h5 class="fw-bold mb-3">Antrian tindakan</h5><div class="card border-0 shadow-sm rounded-4"><div class="list-group list-group-flush">
    <?php foreach($control['work_queue'] as $item): ?><a href="<?= base_url($item['href']) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3"><span><?= esc($item['label']) ?></span><span class="badge text-bg-primary rounded-pill"><?= (int)$item['count'] ?></span></a><?php endforeach ?>
    </div></div></div>
</div>
<?= $this->endSection() ?>
