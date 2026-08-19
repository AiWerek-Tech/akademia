<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php $pageTitle = 'Buat Rencana Pembelajaran'; $pageIcon = 'book-open'; $pageDescription = 'Isi data rencana pembelajaran baru.'; ?>
<?= view('education_foundation/_page_header', compact('pageTitle', 'pageIcon', 'pageDescription')) ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form method="post" action="<?= base_url('lesson-plans') ?>" class="row g-3">
            <?= csrf_field() ?>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Mata Pelajaran</label>
                <select class="form-select" name="subject_id" required>
                    <option value="">Pilih mapel</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"><?= esc($s['name']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Tingkat</label>
                <select class="form-select" name="grade_level_id" required>
                    <option value="">Pilih</option>
                    <?php foreach ($gradeLevels as $g): ?>
                        <option value="<?= (int) $g['id'] ?>"><?= esc($g['name']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Kelas</label>
                <select class="form-select" name="class_id">
                    <option value="">Umum</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"><?= esc($c['name']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Guru</label>
                <select class="form-select" name="teacher_id" required>
                    <option value="">Pilih guru</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= (int) $t['id'] ?>"><?= esc($t['full_name']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Tanggal</label>
                <input type="date" class="form-control" name="date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Pertemuan #</label>
                <input type="number" class="form-control" name="session_number" value="1" min="1" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Label Sesi</label>
                <input class="form-control" name="session_label" placeholder="Pertemuan 1 — Pengenalan Algoritma">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Learning Pack (opsional)</label>
                <select class="form-select" name="learning_pack_id">
                    <option value="">Tanpa pack</option>
                    <?php foreach ($packs as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"><?= esc($p['code'] . ' — ' . $p['name']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Sumber</label>
                <select class="form-select" name="source_type">
                    <option>CUSTOM</option><option>USE_AS_IS</option><option>ADAPT</option><option>CLONE</option>
                </select>
            </div>
            <div class="col-12 text-end mt-3">
                <a href="<?= base_url('lesson-plans') ?>" class="btn btn-light me-2">Batal</a>
                <button class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
