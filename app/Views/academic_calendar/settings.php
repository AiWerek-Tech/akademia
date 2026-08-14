<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php $dayLabels = [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu']; ?>
<div class="container-fluid academic-settings-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><span class="text-primary fw-semibold small">SATU SUMBER KONFIGURASI</span><h3 class="fw-bold mb-1">Operasional Akademik</h3><p class="text-muted mb-0">Hari sekolah yang dipakai kalender, jadwal, jurnal, dan presensi.</p></div>
        <a class="btn btn-outline-primary" href="<?= base_url('academic-calendar') ?>"><i data-lucide="calendar-range" class="icon-xs me-2"></i>Kalender Pendidikan</a>
    </div>
    <?php foreach (['success'=>'success','error'=>'danger'] as $key=>$tone): if (session()->getFlashdata($key)): ?><div class="alert alert-<?= $tone ?> border-0 shadow-sm"><?= esc(session()->getFlashdata($key)) ?></div><?php endif; endforeach; ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-3"><form method="get" class="d-flex align-items-end gap-3"><div class="flex-grow-1"><label class="form-label fw-semibold">Tahun ajaran</label><select class="form-select" name="academic_year_id" onchange="this.form.submit()"><?php foreach ($years as $year): ?><option value="<?= (int)$year['id'] ?>" <?= (int)$year['id']===$yearId?'selected':'' ?>><?= esc($year['name']) ?></option><?php endforeach; ?></select></div><div class="text-muted small pb-2">Perubahan hanya menyinkronkan kalender draft. Kalender aktif ditandai perlu revisi.</div></form></div></div>
    <div class="row g-4">
    <?php foreach ($units as $unit): $policy=$policies[(int)$unit['id']]; $setting=$policy['setting'] ?? null; ?>
        <div class="col-xl-6"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-header bg-white border-0 p-4 pb-2 d-flex justify-content-between"><div><span class="badge bg-primary-subtle text-primary mb-2"><?= esc($unit['code']) ?></span><h5 class="fw-bold mb-1"><?= esc($unit['name']) ?></h5><div class="text-muted small">Sumber aktif: <?= esc($policy['source']) ?></div></div><div class="rounded-3 bg-success-subtle text-success p-3 text-center"><strong><?= count($policy['working_days']) ?></strong><small class="d-block">hari/minggu</small></div></div><div class="card-body p-4 pt-2">
            <form method="post" action="<?= base_url('settings/academic-operations') ?>"><?= csrf_field() ?><input type="hidden" name="academic_year_id" value="<?= $yearId ?>"><input type="hidden" name="unit_id" value="<?= (int)$unit['id'] ?>">
                <div class="mb-3"><label class="form-label fw-semibold">Sumber hari sekolah</label><select class="form-select source-mode" name="source_mode"><option value="CURRICULUM" <?= (!$setting || $setting['source_mode']==='CURRICULUM')?'selected':'' ?>>Ikuti Struktur Kurikulum</option><option value="CUSTOM" <?= ($setting && $setting['source_mode']==='CUSTOM')?'selected':'' ?>>Atur Khusus Global</option></select><div class="form-text">Direkomendasikan mengikuti struktur kurikulum agar seluruh modul selalu konsisten.</div></div>
                <div class="custom-days border rounded-3 p-3 mb-3"><label class="form-label fw-semibold d-block">Hari sekolah khusus</label><div class="d-flex flex-wrap gap-2"><?php foreach($dayLabels as $day=>$label): ?><label class="btn btn-sm btn-outline-primary rounded-pill"><input class="form-check-input me-1" type="checkbox" name="working_days[]" value="<?= $day ?>" <?= in_array($day,$policy['working_days'],true)?'checked':'' ?>><?= $label ?></label><?php endforeach; ?></div></div>
                <div class="row g-3"><div class="col-sm-6"><label class="form-label fw-semibold">Minimal hari/minggu efektif</label><input class="form-control" type="number" name="effective_week_min_days" min="1" max="7" value="<?= (int)$policy['effective_week_min_days'] ?>"></div><div class="col-sm-6 d-flex align-items-end"><label class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="compare_official_targets" value="1" <?= !empty($policy['compare_official_targets'])?'checked':'' ?>><span class="form-check-label">Bandingkan target Dinas</span></label></div></div>
                <div class="mt-3"><label class="form-label fw-semibold">Catatan</label><textarea class="form-control" name="notes" rows="2"><?= esc($setting['notes'] ?? '') ?></textarea></div>
                <button class="btn btn-primary w-100 mt-3" type="submit"><i data-lucide="save" class="icon-xs me-2"></i>Simpan & Sinkronkan</button>
            </form>
        </div></div></div>
    <?php endforeach; ?>
    </div>
</div>
<script>document.querySelectorAll('.source-mode').forEach(s=>{const sync=()=>{s.closest('form').querySelector('.custom-days').style.display=s.value==='CUSTOM'?'block':'none'};s.addEventListener('change',sync);sync()});</script>
<?= $this->endSection() ?>
