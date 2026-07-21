<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold mb-1 text-slate-800">Edit Mata Pelajaran</h4>
        <p class="text-muted fs-7 mb-0"><?= esc($subject['name']) ?></p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form method="POST" action="<?= base_url('subjects/' . $subject['uuid']) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="revision_number" value="<?= esc($subject['revision_number']) ?>">

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Kode Mapel <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control rounded-3" value="<?= esc($subject['code']) ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label fs-8 fw-bold">Nama Lengkap Mapel <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control rounded-3" value="<?= esc($subject['name']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-8 fw-bold">Nama Singkat <span class="text-danger">*</span></label>
                    <input type="text" name="short_name" class="form-control rounded-3" value="<?= esc($subject['short_name']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Kategori Mapel <span class="text-danger">*</span></label>
                    <select name="category" class="form-select rounded-3" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat ?>" <?= $subject['category'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Ketersediaan Unit Sekolah</label>
                    <div class="d-flex gap-3 mt-2">
                        <?php foreach ($units as $u): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="unit_ids[]" value="<?= $u['id'] ?>" id="unit_<?= $u['id'] ?>" <?= in_array($u['id'], $assignedUnitIds, true) ? 'checked' : '' ?>>
                                <label class="form-check-label fs-8" for="unit_<?= $u['id'] ?>"><?= esc($u['name']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label fs-8 fw-bold">Alias Nama (pisahkan dengan koma)</label>
                    <input type="text" name="aliases" class="form-control rounded-3" value="<?= esc($aliasesStr) ?>">
                </div>

                <div class="col-md-6">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="counts_in_report" value="1" id="counts_in_report" <?= (int)$subject['counts_in_report'] === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label fs-8 fw-semibold" for="counts_in_report">Tampil dan Dihitung pada Rapor Hasil Belajar</label>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="counts_as_teaching_load" value="1" id="counts_as_teaching_load" <?= (int)$subject['counts_as_teaching_load'] === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label fs-8 fw-semibold" for="counts_as_teaching_load">Dihitung dalam Beban Mengajar Guru</label>
                    </div>
                </div>

                <div class="col-12 mt-4 d-flex justify-content-end gap-2">
                    <a href="<?= base_url('subjects') ?>" class="btn btn-light rounded-3 px-4">Batal</a>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Perbarui Mapel</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
