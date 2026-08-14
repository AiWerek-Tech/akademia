<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-4 py-4">
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= base_url('assignments') ?>">Penugasan</a></li>
                <li class="breadcrumb-item active">Buat Versi Baru</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0 text-gray-800">Buat Versi Penugasan Baru</h1>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger">
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach (session()->getFlashdata('errors') as $err): ?>
                    <li><?= esc($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="post" action="<?= base_url('assignments') ?>">
                <?= csrf_field() ?>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Periode Akademik <span class="text-danger">*</span></label>
                        <select name="academic_period_id" class="form-select" required>
                            <option value="">-- Pilih Periode Akademik --</option>
                            <?php foreach ($periods as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= old('academic_period_id') == $p['id'] ? 'selected' : '' ?>>
                                    <?= esc($p['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Struktur Kurikulum Target <span class="text-danger">*</span></label>
                        <select name="curriculum_version_id" class="form-select" required>
                            <option value="">-- Pilih Kurikulum Target --</option>
                            <?php foreach ($curriculums as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= old('curriculum_version_id') == $c['id'] ? 'selected' : '' ?>>
                                    <?= esc($c['name']) ?> (<?= esc($c['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Kode Versi Penugasan <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" placeholder="Contoh: TP-26-27-SM1-V1" value="<?= esc(old('code')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Nama Versi Penugasan <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Penugasan Guru Ganjil v1" value="<?= esc(old('name')) ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="3"><?= esc(old('description')) ?></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url('assignments') ?>" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Versi</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
