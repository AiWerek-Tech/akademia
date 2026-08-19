<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php
$pageTitle = 'Subject Learning Pack';
$pageIcon = 'package-open';
$pageDescription = $pack['subject_name'].' · '.$pack['grade_name'].' · '.$pack['curriculum_code'];
$tabs = [
    'overview' => ['layout-dashboard', 'Overview'],
    'units' => ['layers-3', 'Units'],
    'concepts' => ['brain', 'Concepts'],
    'activities' => ['list-checks', 'Activities'],
    'resources' => ['folder-open', 'Resources'],
    'assessment' => ['clipboard-check', 'Assessment Guidance'],
    'followup' => ['refresh-cw', 'Follow-up'],
    'coverage' => ['gauge', 'Coverage'],
    'lineage' => ['git-branch', 'Source & Lineage'],
    'history' => ['history', 'History'],
];
?>
<?= view('education_foundation/_page_header', compact('pageTitle', 'pageIcon', 'pageDescription')) ?>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div>
                <span class="badge text-bg-light mb-2"><?= esc($pack['code']) ?></span>
                <h4 class="fw-bold mb-1"><?= esc($pack['name']) ?></h4>
                <div class="text-muted small">
                    <?= esc($pack['unit_name']) ?> · Phase <?= esc($pack['phase'] ?: '-') ?> · Source <?= esc($pack['source_type'] ?: 'CUSTOM') ?> · Revision <?= (int) $pack['revision_number'] ?>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-start">
                <span class="badge text-bg-primary px-3 py-2"><?= esc($pack['status']) ?></span>
                <?php $next = ['DRAFT'=>'VALIDATED','VALIDATED'=>'REVIEWED','REVIEWED'=>'APPROVED','APPROVED'=>'LOCKED'][$pack['status']] ?? null; ?>
                <?php $perm = ['VALIDATED'=>'learning_packs.validate','REVIEWED'=>'learning_packs.review','APPROVED'=>'learning_packs.approve','LOCKED'=>'learning_packs.lock'][$next] ?? null; ?>
                <?php if($next && $perm && has_permission($perm)): ?>
                    <form method="post" action="<?= base_url('curriculum/learning-packs/'.$pack['uuid'].'/transition') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="target_status" value="<?= esc($next) ?>">
                        <input type="hidden" name="revision_number" value="<?= (int) $pack['revision_number'] ?>">
                        <button class="btn btn-primary btn-sm">Kirim ke <?= esc($next) ?></button>
                    </form>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<nav class="d-flex gap-2 flex-wrap mb-4" aria-label="Subject learning pack sections">
    <?php foreach($tabs as $key => [$icon, $label]): ?>
        <a class="btn <?= $section === $key ? 'btn-primary' : 'btn-light border' ?>" href="<?= base_url('curriculum/learning-packs/'.$pack['uuid'].'/'.$key) ?>">
            <i data-lucide="<?= esc($icon) ?>" class="me-1" style="width:16px;height:16px"></i><?= esc($label) ?>
        </a>
    <?php endforeach ?>
</nav>

<?php if($section === 'overview'): ?>
    <div class="row g-3">
        <?php foreach([['TP Total',$coverage['tp_total'],'target'],['TP dengan Unit',$coverage['tp_with_unit'],'layers-3'],['Aktivitas',$coverage['activity_count'],'list-checks'],['Assessment Ref',$coverage['assessment_reference_count'],'clipboard-check']] as [$label,$value,$icon]): ?>
            <div class="col-md-3"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4"><i data-lucide="<?= $icon ?>" class="text-primary mb-3"></i><div class="text-muted small"><?= esc($label) ?></div><div class="fs-3 fw-bold"><?= (int) $value ?></div></div></div></div>
        <?php endforeach ?>
    </div>
    <?php if($coverage['warnings']): ?><div class="alert alert-warning mt-4 mb-0"><?= esc(implode(' ', $coverage['warnings'])) ?></div><?php endif ?>
<?php elseif($section === 'units'): ?>
    <?php if($canManage): ?><div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4"><h5 class="fw-bold">Tambah Unit/Bab</h5><form method="post" action="<?= base_url('curriculum/learning-packs/'.$pack['uuid'].'/units') ?>" class="row g-3"><?= csrf_field() ?><div class="col-md-2"><input class="form-control" name="code" placeholder="Kode" required></div><div class="col-md-4"><input class="form-control" name="title" placeholder="Judul unit" required></div><div class="col-md-2"><select class="form-select" name="unit_type"><option>CHAPTER</option><option>UNIT</option><option>TOPIC</option><option>SUBTOPIC</option><option>MODULE</option><option>PROJECT</option><option>OTHER</option></select></div><div class="col-md-2"><input class="form-control" type="number" name="sequence_order" value="1" min="1"></div><div class="col-md-2"><input class="form-control" type="number" step="0.5" name="estimated_hours" placeholder="JP"></div><div class="col-md-6"><input class="form-control" name="source_locator" placeholder="Source locator"></div><div class="col-md-6"><input class="form-control" name="description" placeholder="Deskripsi singkat"></div><div class="col-12"><button class="btn btn-primary">Simpan unit</button></div></form></div></div><?php endif ?>
    <?= view('learning_packs/_simple_table', ['headers'=>['Kode','Judul','Tipe','JP','Status'], 'rows'=>array_map(fn($u)=>[$u['code'],$u['title'],$u['unit_type'],$u['estimated_hours'] ?: '-',$u['status']], $units)]) ?>
<?php elseif($section === 'concepts'): ?>
    <?php if($canManage): ?><div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4"><h5 class="fw-bold">Tambah Konsep</h5><form method="post" action="<?= base_url('curriculum/learning-packs/'.$pack['uuid'].'/concepts') ?>" class="row g-3"><?= csrf_field() ?><div class="col-md-3"><select class="form-select" name="learning_unit_uuid"><option value="">Tanpa unit khusus</option><?php foreach($units as $u): ?><option value="<?= esc($u['uuid']) ?>"><?= esc($u['code'].' · '.$u['title']) ?></option><?php endforeach ?></select></div><div class="col-md-2"><input class="form-control" name="code" placeholder="Kode" required></div><div class="col-md-3"><input class="form-control" name="title" placeholder="Konsep" required></div><div class="col-md-2"><select class="form-select" name="concept_type"><option>CORE</option><option>SUPPORTING</option><option>EXTENSION</option><option>CROSS_DISCIPLINARY</option></select></div><div class="col-md-2"><button class="btn btn-primary w-100">Simpan</button></div></form></div></div><?php endif ?>
    <?= view('learning_packs/_simple_table', ['headers'=>['Kode','Konsep','Tipe','Source'], 'rows'=>array_map(fn($c)=>[$c['code'],$c['title'],$c['concept_type'],$c['source_locator'] ?: '-'], $concepts)]) ?>
<?php elseif($section === 'activities'): ?>
    <?php if($canManage && $units): ?><div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4"><h5 class="fw-bold">Tambah Aktivitas</h5><form method="post" action="<?= base_url('curriculum/learning-packs/'.$pack['uuid'].'/activities') ?>" class="row g-3"><?= csrf_field() ?><div class="col-md-3"><select class="form-select" name="learning_unit_uuid" required><?php foreach($units as $u): ?><option value="<?= esc($u['uuid']) ?>"><?= esc($u['code'].' · '.$u['title']) ?></option><?php endforeach ?></select></div><div class="col-md-2"><input class="form-control" name="code" placeholder="Kode" required></div><div class="col-md-3"><input class="form-control" name="title" placeholder="Aktivitas" required></div><div class="col-md-2"><select class="form-select" name="delivery_mode"><option>DISCUSSION</option><option>PLUGGED</option><option>UNPLUGGED</option><option>HYBRID</option><option>PRACTICE</option><option>PROJECT</option><option>LAB</option><option>OTHER</option></select></div><div class="col-md-2"><input class="form-control" type="number" name="estimated_minutes" value="45" min="1"></div><div class="col-md-3"><select class="form-select" name="grouping_mode"><option>FLEXIBLE</option><option>INDIVIDUAL</option><option>PAIR</option><option>SMALL_GROUP</option><option>LARGE_GROUP</option><option>WHOLE_CLASS</option></select></div><div class="col-md-4"><input class="form-control" name="device_requirement" placeholder="Kebutuhan perangkat opsional"></div><div class="col-md-5"><input class="form-control" name="teacher_guidance" placeholder="Panduan guru singkat"></div><div class="col-12"><button class="btn btn-primary">Simpan aktivitas</button></div></form></div></div><?php endif ?>
    <?= view('learning_packs/_simple_table', ['headers'=>['Kode','Aktivitas','Mode','Kelompok','Menit','Status'], 'rows'=>array_map(fn($a)=>[$a['code'],$a['title'],$a['delivery_mode'],$a['grouping_mode'],$a['estimated_minutes'],$a['status']], $activities)]) ?>
<?php elseif($section === 'resources'): ?>
    <?php if($canManage): ?><div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4"><h5 class="fw-bold">Tambah Resource</h5><form method="post" action="<?= base_url('curriculum/learning-packs/'.$pack['uuid'].'/resources') ?>" class="row g-3"><?= csrf_field() ?><div class="col-md-3"><select class="form-select" name="resource_type"><option>DOCUMENT</option><option>BOOK</option><option>VIDEO</option><option>WEBSITE</option><option>DEVICE</option><option>APPLICATION</option><option>MATERIAL</option><option>ROOM</option><option>OTHER</option></select></div><div class="col-md-4"><input class="form-control" name="title" placeholder="Nama resource" required></div><div class="col-md-3"><input class="form-control" name="url" placeholder="URL/referensi"></div><div class="col-md-2"><button class="btn btn-primary w-100">Simpan</button></div></form></div></div><?php endif ?>
    <?= view('learning_packs/_simple_table', ['headers'=>['Tipe','Resource','Internet','Lisensi'], 'rows'=>array_map(fn($r)=>[$r['resource_type'],$r['title'],((int)$r['internet_required'] ? 'Ya' : 'Tidak'),$r['license_notes'] ?: '-'], $resources)]) ?>
<?php elseif($section === 'assessment'): ?>
    <?php if($canManage && $units): ?><div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4"><h5 class="fw-bold">Tambah Assessment Guidance</h5><form method="post" action="<?= base_url('curriculum/learning-packs/'.$pack['uuid'].'/assessment') ?>" class="row g-3"><?= csrf_field() ?><div class="col-md-4"><select class="form-select" name="learning_unit_uuid" required><?php foreach($units as $u): ?><option value="<?= esc($u['uuid']) ?>"><?= esc($u['code'].' · '.$u['title']) ?></option><?php endforeach ?></select></div><div class="col-md-2"><select class="form-select" name="assessment_purpose"><option>INITIAL</option><option>FORMATIVE</option><option>SUMMATIVE</option></select></div><div class="col-md-4"><input class="form-control" name="recommended_method" placeholder="Metode rekomendasi" required></div><div class="col-md-2"><button class="btn btn-primary w-100">Simpan</button></div></form></div></div><?php endif ?>
    <?= view('learning_packs/_simple_table', ['headers'=>['Purpose','Metode','Catatan'], 'rows'=>array_map(fn($a)=>[$a['assessment_purpose'],$a['recommended_method'],$a['notes'] ?: '-'], $assessments)]) ?>
<?php elseif($section === 'followup'): ?>
    <?php if($canManage && $units): ?><div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4"><h5 class="fw-bold">Tambah Follow-up Guidance</h5><form method="post" action="<?= base_url('curriculum/learning-packs/'.$pack['uuid'].'/followup') ?>" class="row g-3"><?= csrf_field() ?><div class="col-md-3"><select class="form-select" name="learning_unit_uuid" required><?php foreach($units as $u): ?><option value="<?= esc($u['uuid']) ?>"><?= esc($u['code'].' · '.$u['title']) ?></option><?php endforeach ?></select></div><div class="col-md-2"><select class="form-select" name="guidance_type"><option>REMEDIAL</option><option>REINFORCEMENT</option><option>ENRICHMENT</option></select></div><div class="col-md-3"><input class="form-control" name="trigger_description" placeholder="Pemicu tindak lanjut" required></div><div class="col-md-4"><input class="form-control" name="guidance" placeholder="Arahan guru" required></div><div class="col-12"><button class="btn btn-primary">Simpan follow-up</button></div></form></div></div><?php endif ?>
    <?= view('learning_packs/_simple_table', ['headers'=>['Tipe','Pemicu','Arahan'], 'rows'=>array_map(fn($f)=>[$f['guidance_type'],$f['trigger_description'],$f['guidance']], $followups)]) ?>
<?php elseif($section === 'coverage'): ?>
    <div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><h5 class="fw-bold">Coverage Summary</h5><pre class="bg-light border rounded-3 p-3 mb-0"><?= esc(json_encode($coverage, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre></div></div>
<?php elseif($section === 'lineage'): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4"><h5 class="fw-bold">Source & Lineage</h5><p class="mb-1"><strong>Source:</strong> <?= esc($pack['source_type'] ?: 'CUSTOM') ?> <?= esc($pack['source_locator'] ?: '') ?></p><p class="text-muted mb-0">Reference content stores metadata and locator only. Full copyrighted source content is not dumped into the production database.</p></div></div>
    <?php if($canManage): ?><div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4"><h5 class="fw-bold">Stage Import Phase 3</h5><form method="post" action="<?= base_url('curriculum/learning-packs/'.$pack['uuid'].'/lineage/imports') ?>" class="row g-3"><?= csrf_field() ?><div class="col-md-4"><input class="form-control" name="source_filename" value="learning-pack.json" required></div><div class="col-12"><textarea class="form-control font-monospace" rows="6" name="rows_json" placeholder='[{"entity_type":"LEARNING_UNIT","payload":{"code":"U1","title":"Unit 1"}}]' required></textarea></div><div class="col-12"><button class="btn btn-primary">Validasi ke staging</button></div></form></div></div><?php endif ?>
    <?= view('learning_packs/_simple_table', ['headers'=>['File','Status','Valid','Error','Applied'], 'rows'=>array_map(fn($i)=>[$i['source_filename'],$i['status'],$i['valid_rows'],$i['error_rows'],$i['applied_rows']], $imports)]) ?>
<?php else: ?>
    <?= view('learning_packs/_simple_table', ['headers'=>['Waktu','Aksi','Entity','Ref'], 'rows'=>array_map(fn($a)=>[$a['created_at'],$a['action'],$a['entity_type'],$a['entity_uuid'] ?? '-'], $audits)]) ?>
<?php endif ?>

<?= $this->endSection() ?>
