<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3" style="max-width:800px">
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="mb-4">
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="<?= base_url('quality/supervisions') ?>">Supervisi</a></li><li class="breadcrumb-item active">Baru</li></ol></nav>
        <h1 class="h3 fw-bold text-gray-900 mb-1">Catatan Supervisi Baru</h1>
    </div>

    <form method="POST" action="<?= base_url('quality/supervision/create') ?>" class="card border-0 shadow-sm rounded-4">
        <?= csrf_field() ?>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Guru yang Diobservasi <span class="text-danger">*</span></label>
                    <select name="teacher_id" class="form-select" required>
                        <option value="">Pilih Guru</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= esc($t['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Tanggal Observasi <span class="text-danger">*</span></label>
                    <input type="date" name="observation_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Tipe Observasi</label>
                    <select name="observation_type" class="form-select">
                        <?php foreach ($observationTypes as $t): ?>
                            <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Rating Keseluruhan</label>
                    <select name="overall_rating" class="form-select">
                        <option value="">— Pilih —</option>
                        <?php foreach ($ratings as $r): ?>
                            <option value="<?= $r ?>"><?= $r ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Mata Pelajaran</label>
                    <select name="subject_id" class="form-select">
                        <option value="">— Pilih —</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= esc($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Kelas</label>
                    <select name="classroom_id" class="form-select">
                        <option value="">— Pilih —</option>
                        <?php foreach ($classrooms as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Kekuatan</label>
                    <textarea name="strengths" class="form-control" rows="3" placeholder="Apa yang dilakukan dengan baik oleh guru?"></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Area Pertumbuhan</label>
                    <textarea name="areas_for_growth" class="form-control" rows="3" placeholder="Aspek yang perlu ditingkatkan?"></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Rekomendasi</label>
                    <textarea name="recommendations" class="form-control" rows="3" placeholder="Saran tindak lanjut untuk guru..."></textarea>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" name="follow_up_needed" value="1" class="form-check-input" id="followUpCheck">
                        <label class="form-check-label fw-semibold" for="followUpCheck">Perlu Follow-up</label>
                    </div>
                </div>
                <div class="col-12" id="followUpNotesDiv" style="display:none">
                    <label class="form-label fw-semibold">Catatan Follow-up</label>
                    <textarea name="follow_up_notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white border-0 p-4 d-flex gap-2 justify-content-end">
            <a href="<?= base_url('quality/supervisions') ?>" class="btn btn-outline-secondary rounded-pill">Batal</a>
            <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan</button>
        </div>
    </form>
</div>
<script>
document.getElementById('followUpCheck').addEventListener('change', function() {
    document.getElementById('followUpNotesDiv').style.display = this.checked ? '' : 'none';
});
</script>
<?= $this->endSection() ?>
