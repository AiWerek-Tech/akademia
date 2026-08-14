<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('settings/units') ?>" class="btn btn-outline-secondary btn-sm rounded-3 d-inline-flex align-items-center gap-1">
                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i> Kembali
            </a>
            <h4 class="fw-bold mb-0 text-slate-800">Edit Profil Unit: <?= esc($unit['code']) ?></h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <form action="<?= base_url('settings/units/' . $unit['uuid']) ?>" method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <?php if (session('error')): ?><div class="alert alert-danger rounded-3"><?= esc(session('error')) ?></div><?php endif; ?>
                    <?php if (session('errors')): ?><div class="alert alert-danger rounded-3"><ul class="mb-0"><?php foreach (session('errors') as $message): ?><li><?= esc($message) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="name" class="form-label fw-semibold">Nama Unit Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="name" name="name" value="<?= esc(old('name', $unit['name'])) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="short_name" class="form-label fw-semibold">Singkatan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="short_name" name="short_name" value="<?= esc(old('short_name', $unit['short_name'])) ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="npsn" class="form-label fw-semibold">NPSN</label>
                            <input type="text" inputmode="numeric" maxlength="12" class="form-control rounded-3" id="npsn" name="npsn" value="<?= esc(old('npsn', $unit['npsn'])) ?>"><div class="form-text">8–12 digit, tanpa spasi atau tanda baca.</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="timezone" class="form-label fw-semibold">Zona Waktu <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" id="timezone" name="timezone" required>
                                <?php foreach ($timezones as $tz): ?>
                                    <option value="<?= $tz ?>" <?= $tz === old('timezone', $unit['timezone']) ? 'selected' : '' ?>>
                                        <?= $tz ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="address" class="form-label fw-semibold">Alamat</label>
                            <textarea class="form-control rounded-3" id="address" name="address" rows="3"><?= esc(old('address', $unit['address'])) ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label fw-semibold">Telepon Kantor</label>
                            <input type="tel" class="form-control rounded-3" id="phone" name="phone" placeholder="Contoh: 0812 3456 7890" value="<?= esc(old('phone', $unit['phone'])) ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold">Email Resmi</label>
                            <input type="email" class="form-control rounded-3" id="email" name="email" value="<?= esc(old('email', $unit['email'])) ?>">
                        </div>

                        <div class="col-12"><hr class="my-2"><h6 class="fw-bold mb-0">Pengaturan KOP Resmi Dokumen / SK Sekolah</h6><p class="text-muted small mb-0">Pengaturan ini akan digunakan secara otomatis pada cetakan jadwal pelajaran master, SK pembagian tugas, dan dokumen laporan sekolah.</p></div>

                        <div class="col-md-6">
                            <label for="logo" class="form-label fw-semibold">Logo KOP Kiri</label>
                            <div class="border rounded-4 p-3 bg-light-subtle">
                                <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3">
                                    <div class="bg-white border rounded-3 d-flex align-items-center justify-content-center" style="width:76px;height:76px;overflow:hidden;">
                                        <img id="logoPreview" src="<?= base_url(!empty($unit['logo_path']) ? ltrim((string) $unit['logo_path'], '/') : 'assets/img/brand-mark.svg') ?>" alt="Pratinjau logo kop" style="max-width:66px;max-height:66px;object-fit:contain;">
                                    </div>
                                    <div class="flex-grow-1">
                                        <input type="file" class="form-control rounded-3" id="logo" name="logo" accept="image/png,image/jpeg,image/webp">
                                        <div class="form-text">PNG, JPG, atau WEBP; maksimum 2 MB.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="logo_right" class="form-label fw-semibold">Logo KOP Kanan</label>
                            <div class="border rounded-4 p-3 bg-light-subtle"><div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3">
                                <div class="bg-white border rounded-3 d-flex align-items-center justify-content-center" style="width:76px;height:76px;overflow:hidden;">
                                    <img id="logoRightPreview" src="<?= base_url(!empty($unit['logo_right_path']) ? ltrim((string) $unit['logo_right_path'], '/') : 'assets/img/brand-mark.svg') ?>" alt="Pratinjau logo kanan" style="max-width:66px;max-height:66px;object-fit:contain;">
                                </div>
                                <div class="flex-grow-1"><input type="file" class="form-control rounded-3" id="logo_right" name="logo_right" accept="image/png,image/jpeg,image/webp"><div class="form-text">Logo mitra/yayasan pada sisi kanan kop.</div></div>
                            </div></div>
                        </div>

                        <div class="col-12">
                            <label for="header_line_1" class="form-label fw-semibold">KOP Baris 1 (Yayasan / Lembaga Induk)</label>
                            <input type="text" class="form-control rounded-3" id="header_line_1" name="header_line_1" value="<?= esc(old('header_line_1', $unit['header_line_1'] ?? 'YAYASAN PENDIDIKAN ADVENT PAPUA')) ?>" placeholder="Contoh: YAYASAN PENDIDIKAN ADVENT PAPUA">
                        </div>

                        <div class="col-12">
                            <label for="header_line_2" class="form-label fw-semibold">KOP Baris 2 (Nama Perguruan / Akademi)</label>
                            <input type="text" class="form-control rounded-3" id="header_line_2" name="header_line_2" value="<?= esc(old('header_line_2', $unit['header_line_2'] ?? 'WAMENA MOUNTAIN VIEW ADVENTIST ACADEMY')) ?>" placeholder="Contoh: WAMENA MOUNTAIN VIEW ADVENTIST ACADEMY">
                        </div>

                        <div class="col-12">
                            <label for="header_line_3" class="form-label fw-semibold">KOP Baris 3 (Nama Unit / Jenjang Sekolah)</label>
                            <input type="text" class="form-control rounded-3" id="header_line_3" name="header_line_3" value="<?= esc(old('header_line_3', $unit['header_line_3'] ?? 'SMP-SMA ADVENT SOGOKMO')) ?>" placeholder="Contoh: SMP-SMA ADVENT SOGOKMO">
                        </div>

                        <div class="col-12">
                            <label for="header_line_4" class="form-label fw-semibold">KOP Baris 4 (Alamat Lengkap & Kabupaten/Kota)</label>
                            <input type="text" class="form-control rounded-3" id="header_line_4" name="header_line_4" value="<?= esc(old('header_line_4', $unit['header_line_4'] ?? 'Jalan Wamena - Kurima, Desa Sogokmo, Distrik Asotipo, Kabupaten Jayawijaya - Papua')) ?>" placeholder="Contoh: Jalan Wamena - Kurima, Desa Sogokmo, Distrik Asotipo, Kabupaten Jayawijaya - Papua">
                        </div>

                        <div class="col-12"><hr class="my-2"><h6 class="fw-bold mb-0">Penandatangan & Dokumen Resmi</h6><p class="text-muted small mb-0">Dipakai otomatis pada SK pembagian tugas dan surat tugas guru.</p></div>
                        <div class="col-md-7">
                            <label for="head_name" class="form-label fw-semibold">Nama Kepala Sekolah</label>
                            <input type="text" class="form-control rounded-3" id="head_name" name="head_name" value="<?= esc(old('head_name', $unit['head_name'] ?? '')) ?>" placeholder="Nama lengkap beserta gelar">
                        </div>
                        <div class="col-md-5">
                            <label for="head_identifier" class="form-label fw-semibold">NIP / Identitas</label>
                            <input type="text" class="form-control rounded-3" id="head_identifier" name="head_identifier" value="<?= esc(old('head_identifier', $unit['head_identifier'] ?? '')) ?>" placeholder="Opsional">
                        </div>
                        <div class="col-md-6">
                            <label for="document_city" class="form-label fw-semibold">Kota Penetapan</label>
                            <input type="text" class="form-control rounded-3" id="document_city" name="document_city" value="<?= esc(old('document_city', $unit['document_city'] ?? '')) ?>" placeholder="Contoh: Sogokmo">
                        </div>
                        <div class="col-md-6">
                            <label for="decree_prefix" class="form-label fw-semibold">Prefix Nomor SK</label>
                            <input type="text" class="form-control rounded-3" id="decree_prefix" name="decree_prefix" value="<?= esc(old('decree_prefix', $unit['decree_prefix'] ?? '')) ?>" placeholder="Contoh: 421.3/SK-SMP">
                        </div>
                    </div>

                    <hr class="my-4 text-slate-200">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= base_url('settings/units') ?>" class="btn btn-light rounded-3 px-4">Batal</a>
                        <button type="submit" class="btn btn-primary rounded-3 px-4 d-inline-flex align-items-center gap-2">
                            <i data-lucide="save" style="width: 16px; height: 16px;"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>document.addEventListener('DOMContentLoaded', function(){
    if(typeof lucide!=='undefined') lucide.createIcons();
    [['logo','logoPreview'],['logo_right','logoRightPreview']].forEach(function(ids){
        const input=document.getElementById(ids[0]), preview=document.getElementById(ids[1]);
        if(input&&preview) input.addEventListener('change',function(){
            const file=this.files&&this.files[0];
            if(file) preview.src=URL.createObjectURL(file);
        });
    });
});</script>
<?= $this->endSection() ?>
