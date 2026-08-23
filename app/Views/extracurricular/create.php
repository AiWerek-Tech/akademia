<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3" style="max-width:900px">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="mb-4">
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="<?= base_url('extracurricular') ?>">Ekstrakurikuler</a></li><li class="breadcrumb-item active"><?= ($isEdit ?? false) ? 'Edit Program' : 'Program Baru' ?></li></ol></nav>
        <h1 class="h3 fw-bold text-gray-900 mb-1"><?= ($isEdit ?? false) ? 'Edit Program Ekstrakurikuler' : 'Buat Program Ekstrakurikuler' ?></h1>
        <p class="text-muted mb-0">Isi detail program, jadwal, pembina, dan dana operasional.</p>
    </div>

    <form method="POST" action="<?= base_url($isEdit ?? false ? 'extracurricular/' . $program['id'] : 'extracurricular') ?>" class="card border-0 shadow-sm rounded-4">
        <?= csrf_field() ?>
        <div class="card-body p-4">
            <div class="row g-3">
                <!-- Code -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Kode Program</label>
                    <input type="text" name="code" class="form-control" value="<?= esc($program['code'] ?? '') ?>" placeholder="Contoh: PRG-001">
                </div>

                <!-- Title -->
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Nama Program <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?= esc($program['title'] ?? '') ?>" required>
                </div>

                <!-- Category -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Kategori</label>
                    <select name="category" class="form-select">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat ?>" <?= ($program['category'] ?? 'CLUB') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Coach -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Pembina / Coach</label>
                    <select name="coach_teacher_id" class="form-select">
                        <option value="">— Pilih Guru —</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= (int) ($program['coach_teacher_id'] ?? 0) === (int) $t['id'] ? 'selected' : '' ?>><?= esc($t['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= ($program['status'] ?? 'DRAFT') === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Rationale -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Rationale / Latar Belakang</label>
                    <textarea name="rationale" class="form-control" rows="3" placeholder="Mengapa program ini penting..."><?= esc($program['rationale'] ?? '') ?></textarea>
                </div>

                <!-- Objective -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Tujuan Program</label>
                    <textarea name="objective" class="form-control" rows="3" placeholder="Tujuan yang ingin dicapai..."><?= esc($program['objective'] ?? '') ?></textarea>
                </div>

                <!-- Description -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Deskripsi detail program..."><?= esc($program['description'] ?? '') ?></textarea>
                </div>

                <div class="col-12"><hr></div>

                <!-- Schedule -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Hari Pertemuan</label>
                    <select name="meeting_day" class="form-select">
                        <option value="">— Pilih Hari —</option>
                        <?php foreach ($days as $d): ?>
                            <option value="<?= $d ?>" <?= ($program['meeting_day'] ?? '') === $d ? 'selected' : '' ?>><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Jam</label>
                    <input type="text" name="meeting_time" class="form-control" value="<?= esc($program['meeting_time'] ?? '') ?>" placeholder="14:00 - 16:00">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Lokasi</label>
                    <input type="text" name="location" class="form-control" value="<?= esc($program['location'] ?? '') ?>" placeholder="Lapangan / Aula">
                </div>

                <div class="col-12"><hr></div>

                <!-- Membership & Funding -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Kapasitas Maks</label>
                    <input type="number" name="max_members" class="form-control" value="<?= esc($program['max_members'] ?? '') ?>" min="1">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Sumber Dana</label>
                    <input type="text" name="funding_source" class="form-control" value="<?= esc($program['funding_source'] ?? '') ?>" placeholder="SPP / Donasi / dll">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Anggaran (Rp)</label>
                    <input type="number" name="funding_amount" class="form-control" value="<?= esc($program['funding_amount'] ?? '') ?>" min="0" step="1000">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Catatan Manajemen</label>
                    <input type="text" name="management_notes" class="form-control" value="<?= esc($program['management_notes'] ?? '') ?>">
                </div>
            </div>
        </div>
        <div class="card-footer bg-white border-top d-flex justify-content-between p-4">
            <a href="<?= base_url('extracurricular') ?>" class="btn btn-outline-secondary rounded-pill px-4">Batal</a>
            <button type="submit" class="btn btn-primary shadow-sm rounded-pill px-4">
                <i data-lucide="save" class="w-4 h-4 me-1 d-inline-block"></i> <?= $isEdit ?? false ? 'Simpan Perubahan' : 'Buat Program' ?>
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
