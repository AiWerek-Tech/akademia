<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold mb-1 text-slate-800">Tambah Mata Pelajaran Global</h4>
        <p class="text-muted fs-7 mb-0">Master mata pelajaran dapat tersedia di unit SMP dan/atau SMA</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form method="POST" action="<?= base_url('subjects') ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Kode Mapel <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control rounded-3" placeholder="misal: MAT, IND, IPA" value="<?= esc(old('code')) ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label fs-8 fw-bold">Nama Lengkap Mapel <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control rounded-3" placeholder="misal: Matematika Umum" value="<?= esc(old('name')) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-8 fw-bold">Nama Singkat <span class="text-danger">*</span></label>
                    <input type="text" name="short_name" class="form-control rounded-3" placeholder="misal: MTK" value="<?= esc(old('short_name')) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Kategori Mapel <span class="text-danger">*</span></label>
                    <select name="category" class="form-select rounded-3" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat ?>" <?= old('category') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Ketersediaan Unit Sekolah</label>
                    <div class="d-flex gap-3 mt-2">
                        <?php foreach ($units as $u): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="unit_ids[]" value="<?= $u['id'] ?>" id="unit_<?= $u['id'] ?>" checked>
                                <label class="form-check-label fs-8" for="unit_<?= $u['id'] ?>"><?= esc($u['name']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label fs-8 fw-bold">Alias Nama (pisahkan dengan koma)</label>
                    <input type="text" name="aliases" class="form-control rounded-3" placeholder="misal: Math, Matematika Wajib, MTK" value="<?= esc(old('aliases')) ?>">
                </div>

                <div class="col-md-6">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="counts_in_report" value="1" id="counts_in_report" checked>
                        <label class="form-check-label fs-8 fw-semibold" for="counts_in_report">Tampil dan Dihitung pada Rapor Hasil Belajar</label>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="counts_as_teaching_load" value="1" id="counts_as_teaching_load" checked>
                        <label class="form-check-label fs-8 fw-semibold" for="counts_as_teaching_load">Dihitung dalam Beban Mengajar Guru</label>
                    </div>
                </div>

                <div class="col-12 mt-4 d-flex justify-content-end gap-2">
                    <a href="<?= base_url('subjects') ?>" class="btn btn-light rounded-3 px-4">Batal</a>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Simpan Mapel</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
