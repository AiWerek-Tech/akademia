<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('academic-periods') ?>" class="btn btn-outline-secondary btn-sm rounded-3 d-inline-flex align-items-center gap-1">
                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i> Kembali
            </a>
            <h4 class="fw-bold mb-0 text-slate-800">Edit Tahun Pelajaran: <?= esc($year['name']) ?></h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-md-8 col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <form action="<?= base_url('academic-years/' . $year['uuid']) ?>" method="POST">
                    <?= csrf_field() ?>
                    <?php if (session('error')): ?><div class="alert alert-danger rounded-3"><?= esc(session('error')) ?></div><?php endif; ?>
                    <?php if (session('errors')): ?><div class="alert alert-danger rounded-3"><ul class="mb-0"><?php foreach (session('errors') as $message): ?><li><?= esc($message) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Nama Tahun Pelajaran <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="name" name="name" placeholder="contoh: 2026/2027" required value="<?= esc(old('name', $year['name'])) ?>">
                        <div class="form-text fs-8">Format harus YYYY/YYYY (contoh: 2026/2027)</div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label for="start_date" class="form-label fw-semibold">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control rounded-3" id="start_date" name="start_date" value="<?= esc(old('start_date', $year['start_date'])) ?>" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label for="end_date" class="form-label fw-semibold">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control rounded-3" id="end_date" name="end_date" value="<?= esc(old('end_date', $year['end_date'])) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label fw-semibold">Status Lifecycle <span class="text-danger">*</span></label>
                        <select class="form-select rounded-3" id="status" name="status" required>
                            <?php if ((int)$year['is_active'] === 1): ?><option value="ACTIVE" selected>ACTIVE — aktif melalui tombol aktivasi</option><?php else: ?><option value="DRAFT" <?= old('status', $year['status']) === 'DRAFT' ? 'selected' : '' ?>>DRAFT — masih disiapkan</option><option value="ARCHIVED" <?= old('status', $year['status']) === 'ARCHIVED' ? 'selected' : '' ?>>ARCHIVED — tidak digunakan lagi</option><?php endif; ?>
                        </select>
                    </div>

                    <hr class="my-4 text-slate-200">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= base_url('academic-years') ?>" class="btn btn-light rounded-3 px-4">Batal</a>
                        <button type="submit" class="btn btn-primary rounded-3 px-4 d-inline-flex align-items-center gap-2">
                            <i data-lucide="save" style="width: 16px; height: 16px;"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>document.addEventListener('DOMContentLoaded', function(){ if(typeof lucide!=='undefined') lucide.createIcons(); });</script>
<?= $this->endSection() ?>
