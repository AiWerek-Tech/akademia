<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3" style="max-width:1100px">
    <nav aria-label="breadcrumb" class="mb-3"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= base_url('settings/school-profile') ?>">Pengaturan</a></li><li class="breadcrumb-item active">Profil Sekolah</li></ol></nav>
    <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div><h1 class="h3 fw-bold text-gray-900 mb-1">Profil Sekolah</h1><p class="text-muted mb-0">Pengaturan informasi sekolah, unit, dan data global.</p></div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-pills mb-4 gap-2" id="profileTabs">
        <li class="nav-item"><button class="nav-link active rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#tab-unit"><i data-lucide="building" class="w-4 h-4 me-1 d-inline-block"></i> Profil Unit</button></li>
        <li class="nav-item"><button class="nav-link rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#tab-global"><i data-lucide="globe" class="w-4 h-4 me-1 d-inline-block"></i> Pengaturan Global</button></li>
        <li class="nav-item"><button class="nav-link rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#tab-header"><i data-lucide="file-text" class="w-4 h-4 me-1 d-inline-block"></i> Kop Surat & Header</button></li>
    </ul>

    <div class="tab-content">
        <!-- TAB 1: Unit Profile -->
        <div class="tab-pane fade show active" id="tab-unit">
            <form method="POST" action="<?= base_url('settings/school-profile') ?>" class="card border-0 shadow-sm rounded-4">
                <?= csrf_field() ?>
                <input type="hidden" name="unit_id" value="<?= $unitId ?>">
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-semibold">Nama Unit Sekolah <span class="text-danger">*</span></label><input class="form-control rounded-3" name="name" value="<?= esc($unit['name'] ?? '') ?>" required></div>
                        <div class="col-md-3"><label class="form-label fw-semibold">Nama Pendek</label><input class="form-control rounded-3" name="short_name" value="<?= esc($unit['short_name'] ?? '') ?>"></div>
                        <div class="col-md-3"><label class="form-label fw-semibold">NPSN</label><input class="form-control rounded-3" name="npsn" value="<?= esc($unit['npsn'] ?? '') ?>"></div>
                        <div class="col-12"><label class="form-label fw-semibold">Alamat</label><textarea class="form-control rounded-3" name="address" rows="2"><?= esc($unit['address'] ?? '') ?></textarea></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Telepon</label><input class="form-control rounded-3" name="phone" value="<?= esc($unit['phone'] ?? '') ?>"></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Email</label><input class="form-control rounded-3" name="email" type="email" value="<?= esc($unit['email'] ?? '') ?>"></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Zona Waktu</label><select class="form-select rounded-3" name="timezone">
                            <?php foreach (['Asia/Jayapura','Asia/Makassar','Asia/Jakarta','Asia/Pontianak'] as $tz): ?>
                                <option value="<?= $tz ?>" <?= ($unit['timezone'] ?? '') === $tz ? 'selected' : '' ?>><?= $tz ?></option>
                            <?php endforeach; ?>
                        </select></div>
                        <div class="col-12"><hr></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Kepala Sekolah</label><input class="form-control rounded-3" name="head_name" value="<?= esc($unit['head_name'] ?? '') ?>"></div>
                        <div class="col-md-3"><label class="form-label fw-semibold">Identitas Kepsek</label><input class="form-control rounded-3" name="head_identifier" value="<?= esc($unit['head_identifier'] ?? '') ?>"></div>
                        <div class="col-md-3"><label class="form-label fw-semibold">Kota Dokumen</label><input class="form-control rounded-3" name="document_city" value="<?= esc($unit['document_city'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Prefix Surat Keputusan</label><input class="form-control rounded-3" name="decree_prefix" value="<?= esc($unit['decree_prefix'] ?? '') ?>"></div>
                    </div>
                </div>
                <div class="card-footer bg-white border-top rounded-bottom-4 p-4"><button class="btn btn-primary rounded-pill px-4"><i data-lucide="save" class="w-4 h-4 me-1 d-inline-block"></i> Simpan Profil Unit</button></div>
            </form>
        </div>

        <!-- TAB 2: Global Settings -->
        <div class="tab-pane fade" id="tab-global">
            <form method="POST" action="<?= base_url('settings/school-profile') ?>" class="card border-0 shadow-sm rounded-4">
                <?= csrf_field() ?>
                <input type="hidden" name="unit_id" value="<?= $unitId ?>">
                <div class="card-header bg-white border-bottom rounded-top-4"><h6 class="mb-0 fw-semibold">Pengaturan Global Sekolah</h6></div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label fw-semibold">Motto Sekolah</label><input class="form-control rounded-3" name="motto" value="<?= esc($global['motto'] ?? '') ?>" placeholder="Contoh: Terdepan dalam Pendidikan"></div>
                        <div class="col-12"><label class="form-label fw-semibold">Visi</label><textarea class="form-control rounded-3" name="vision" rows="3"><?= esc($global['vision'] ?? '') ?></textarea></div>
                        <div class="col-12"><label class="form-label fw-semibold">Misi</label><textarea class="form-control rounded-3" name="mission" rows="3"><?= esc($global['mission'] ?? '') ?></textarea></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Akreditasi</label><select class="form-select rounded-3" name="accreditation">
                            <option value="">— Pilih —</option>
                            <?php foreach (['A','B','C','D','Belum Akreditasi'] as $acc): ?>
                                <option value="<?= $acc ?>" <?= ($global['accreditation'] ?? '') === $acc ? 'selected' : '' ?>><?= $acc ?></option>
                            <?php endforeach; ?>
                        </select></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">NPWP</label><input class="form-control rounded-3" name="npwp" value="<?= esc($global['npwp'] ?? '') ?>"></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Rekening Bank</label><input class="form-control rounded-3" name="bank_account" value="<?= esc($global['bank_account'] ?? '') ?>"></div>
                    </div>
                </div>
                <div class="card-footer bg-white border-top rounded-bottom-4 p-4"><button class="btn btn-primary rounded-pill px-4"><i data-lucide="save" class="w-4 h-4 me-1 d-inline-block"></i> Simpan Pengaturan Global</button></div>
            </form>
        </div>

        <!-- TAB 3: Letterhead & Header -->
        <div class="tab-pane fade" id="tab-header">
            <form method="POST" action="<?= base_url('settings/school-profile') ?>" class="card border-0 shadow-sm rounded-4">
                <?= csrf_field() ?>
                <input type="hidden" name="unit_id" value="<?= $unitId ?>">
                <div class="card-header bg-white border-bottom rounded-top-4"><h6 class="mb-0 fw-semibold">Kop Surat & Header Dokumen</h6></div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-4">Baris header ini ditampilkan di bagian atas rapor, surat resmi, dan dokumen lainnya.</p>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-semibold">Header Baris 1</label><input class="form-control rounded-3" name="header_line_1" value="<?= esc($unit['header_line_1'] ?? '') ?>" placeholder="Nama Sekolah"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Header Baris 2</label><input class="form-control rounded-3" name="header_line_2" value="<?= esc($unit['header_line_2'] ?? '') ?>" placeholder="Alamat Sekolah"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Header Baris 3</label><input class="form-control rounded-3" name="header_line_3" value="<?= esc($unit['header_line_3'] ?? '') ?>" placeholder="Telepon / Email"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Header Baris 4</label><input class="form-control rounded-3" name="header_line_4" value="<?= esc($unit['header_line_4'] ?? '') ?>" placeholder="Website / NPSN"></div>
                    </div>
                    <!-- Preview -->
                    <div class="mt-4 p-4 bg-light rounded-3 text-center border">
                        <div class="fw-bold fs-5"><?= esc($unit['header_line_1'] ?? 'NAMA SEKOLAH') ?></div>
                        <div class="small"><?= esc($unit['header_line_2'] ?? 'Alamat Sekolah') ?></div>
                        <div class="small"><?= esc($unit['header_line_3'] ?? 'Telepon / Email') ?></div>
                        <div class="small text-muted"><?= esc($unit['header_line_4'] ?? 'Website / NPSN') ?></div>
                    </div>
                </div>
                <div class="card-footer bg-white border-top rounded-bottom-4 p-4"><button class="btn btn-primary rounded-pill px-4"><i data-lucide="save" class="w-4 h-4 me-1 d-inline-block"></i> Simpan Header</button></div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
