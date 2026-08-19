<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php 
$pageTitle = 'Buat Rencana Pembelajaran'; 
$pageIcon = 'plus-circle'; 
$pageDescription = 'Susun Rencana Pelaksanaan Pembelajaran (RPP / Modul Ajar) baru atau isi otomatis dari Paket Pembelajaran.'; 
?>
<?= view('education_foundation/_page_header', compact('pageTitle', 'pageIcon', 'pageDescription')) ?>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <form method="post" action="<?= base_url('lesson-plans') ?>" class="row g-3">
            <?= csrf_field() ?>
            <div class="col-12 mb-2">
                <div class="alert alert-info border-0 rounded-4 p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-info bg-opacity-25 p-2 text-info">
                        <i data-lucide="sparkles" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="small">
                        <strong>Tips Efisiensi:</strong> Memilih <em>Learning Pack</em> di bawah ini akan mengisi seluruh Tujuan Pembelajaran, Tahapan (Memahami, Mengaplikasi, Merefleksi), Aktivitas, dan Asesmen secara otomatis dari desain kurikulum yang telah tervalidasi.
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-semibold">Mata Pelajaran</label>
                <select class="form-select rounded-3" name="subject_id" required>
                    <option value="">Pilih mapel...</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"><?= esc($s['name']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Tingkat Kelas</label>
                <select class="form-select rounded-3" name="grade_level_id" required>
                    <option value="">Pilih tingkat...</option>
                    <?php foreach ($gradeLevels as $g): ?>
                        <option value="<?= (int) $g['id'] ?>"><?= esc($g['name']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Rombongan Belajar (Kelas)</label>
                <select class="form-select rounded-3" name="class_id">
                    <option value="">Umum / Semua Rombel</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"><?= esc($c['name']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Guru Pengampu</label>
                <select class="form-select rounded-3" name="teacher_id" required>
                    <option value="">Pilih guru...</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= (int) $t['id'] ?>"><?= esc($t['full_name']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Tanggal Pelaksanaan</label>
                <input type="date" class="form-control rounded-3" name="date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Pertemuan Ke- #</label>
                <input type="number" class="form-control rounded-3" name="session_number" value="1" min="1" required>
            </div>
            <div class="col-md-12">
                <label class="form-label small fw-semibold">Label / Topik Sesi Pertemuan</label>
                <input class="form-control rounded-3" name="session_label" placeholder="Contoh: Pertemuan 1 — Berpikir Komputasional & Algoritma Dasar" required>
            </div>

            <!-- Auto Populate from Learning Pack -->
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Rujukan Learning Pack (Phase 3)</label>
                <select class="form-select rounded-3" name="learning_pack_id" id="packSelect">
                    <option value="">Tanpa paket (Buat rancangan custom dari awal)</option>
                    <?php foreach ($packs as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" data-pack-id="<?= (int) $p['id'] ?>">
                            <?= esc($p['code'] . ' — ' . $p['name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-6" id="unitWrapper" style="display:none;">
                <label class="form-label small fw-semibold">Pilih Unit / Bab Tertentu (Opsional)</label>
                <select class="form-select rounded-3" name="learning_unit_id" id="unitSelect">
                    <option value="">Semua unit dalam paket</option>
                </select>
            </div>

            <div class="col-md-12">
                <label class="form-label small fw-semibold">Modalitas / Sumber Rencana</label>
                <select class="form-select rounded-3" name="source_type">
                    <option value="CUSTOM">CUSTOM (Rancangan Mandiri)</option>
                    <option value="USE_AS_IS">USE_AS_IS (Mengikuti Standar Paket Utuh)</option>
                    <option value="ADAPT">ADAPT (Adaptasi Kontekstual)</option>
                    <option value="CLONE">CLONE (Duplikasi dari Rencana Lain)</option>
                </select>
            </div>

            <div class="col-12 text-end mt-4 pt-3 border-top">
                <a href="<?= base_url('lesson-plans') ?>" class="btn btn-light rounded-pill px-4 me-2">Batal</a>
                <button type="submit" class="btn btn-primary rounded-pill px-5">Simpan & Buka Rencana</button>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
    const packUnits = <?= json_encode($packUnits ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const packSel = document.getElementById('packSelect');
    const unitWrap = document.getElementById('unitWrapper');
    const unitSel  = document.getElementById('unitSelect');
    
    packSel?.addEventListener('change', function(){
        const pid = this.value;
        if (!pid || !packUnits[pid]) { 
            unitWrap.style.display='none'; 
            unitSel.innerHTML='<option value="">Semua unit dalam paket</option>'; 
            return; 
        }
        unitWrap.style.display='';
        let html = '<option value="">Semua unit dalam paket</option>';
        packUnits[pid].forEach(function(u){ 
            html += '<option value="'+u.id+'">'+(u.code||'')+' — '+u.title+'</option>'; 
        });
        unitSel.innerHTML = html;
    });
    
    if (packSel && packSel.value && packUnits[packSel.value]) { 
        packSel.dispatchEvent(new Event('change')); 
    }
})();
</script>
<?= $this->endSection() ?>
