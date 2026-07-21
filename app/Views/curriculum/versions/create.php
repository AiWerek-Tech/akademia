<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4 py-4">
    <div class="mb-4">
        <a href="<?= base_url('curriculum') ?>" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Kurikulum</a>
        <h1 class="h3 mb-0 text-gray-800 mt-2">Buat Versi Kurikulum Baru</h1>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 col-lg-8">
        <div class="card-body">
            <form action="<?= base_url('curriculum') ?>" method="post">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label fw-bold">Periode Akademik <span class="text-danger">*</span></label>
                    <select name="academic_period_id" class="form-select" required>
                        <option value="">-- Pilih Periode Akademik --</option>
                        <?php foreach ($periods as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= old('academic_period_id') == $p['id'] ? 'selected' : '' ?>>
                                <?= esc($p['name']) ?> (<?= esc($p['year_name'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Kode Versi Kurikulum <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" placeholder="Misal: KUR-KMER-2026-SMP" value="<?= old('code') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Nama Versi Kurikulum <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="Misal: Kurikulum Merdeka SMP TP 2026/2027" value="<?= old('name') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Catatan atau deskripsi tambahan..."><?= old('description') ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Referensi Legal / Sumber</label>
                    <input type="text" name="source_reference" class="form-control" placeholder="Misal: Permendikbudristek No. 12 Tahun 2024" value="<?= old('source_reference') ?>">
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="<?= base_url('curriculum') ?>" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Versi Kurikulum</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
