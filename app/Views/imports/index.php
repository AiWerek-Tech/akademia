<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<?php
$typeColors = [
    'TEACHERS' => 'primary', 'SUBJECTS' => 'success', 'GRADE_LEVELS' => 'info',
    'CLASSROOMS' => 'warning', 'ROOMS' => 'secondary',
];
$selectedType = isset($allowedTypes[$selectedType]) ? $selectedType : (string) array_key_first($allowedTypes);
?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width:42px;height:42px">
                <i data-lucide="file-up" style="width:22px;height:22px"></i>
            </span>
            <div>
                <h4 class="fw-bold text-slate-800 mb-0">Import Master Data</h4>
                <p class="text-muted fs-7 mb-0">Guru, mata pelajaran, tingkat, kelas/rombel, dan ruangan melalui staging yang aman.</p>
            </div>
        </div>
    </div>
    <a href="#riwayat-import" class="btn btn-light btn-sm rounded-3 d-flex align-items-center gap-2">
        <i data-lucide="history" style="width:16px;height:16px"></i> Lihat Riwayat
    </a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success border-0 rounded-3"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger border-0 rounded-3"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>
<?php if ($errors = session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger border-0 rounded-3">
        <strong>Upload belum dapat diproses.</strong>
        <ul class="mb-0 mt-2"><?php foreach ($errors as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
    <div class="card-body p-0">
        <div class="row g-0">
            <div class="col-xl-7 p-4 p-lg-5 border-end">
                <div class="d-flex gap-2 align-items-center mb-4">
                    <?php foreach ([['1', 'Pilih data'], ['2', 'Isi template'], ['3', 'Upload & review'], ['4', 'Terapkan']] as $step): ?>
                        <div class="d-flex align-items-center gap-2 flex-grow-1">
                            <span class="badge rounded-pill bg-primary"><?= $step[0] ?></span>
                            <span class="fs-8 fw-semibold text-slate-700 d-none d-md-inline"><?= esc($step[1]) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form method="POST" action="<?= base_url('imports/master/upload') ?>" enctype="multipart/form-data" id="masterImportForm">
                    <?= csrf_field() ?>
                    <label class="form-label fw-bold">1. Pilih jenis master data</label>
                    <div class="row g-2 mb-4" role="radiogroup" aria-label="Jenis master data">
                        <?php foreach ($allowedTypes as $type => $config): ?>
                            <div class="col-sm-6 col-lg-4">
                                <input class="btn-check import-type-input" type="radio" name="import_type" id="type-<?= strtolower($type) ?>" value="<?= esc($type) ?>" <?= $selectedType === $type ? 'checked' : '' ?> required>
                                <label class="btn btn-outline-<?= $typeColors[$type] ?? 'primary' ?> w-100 h-100 text-start rounded-3 p-3" for="type-<?= strtolower($type) ?>">
                                    <i data-lucide="<?= esc($config['icon']) ?>" class="d-block mb-2" style="width:20px;height:20px"></i>
                                    <span class="fw-bold d-block"><?= esc($config['label']) ?></span>
                                    <small class="opacity-75">Template khusus <?= esc(strtolower($config['label'])) ?></small>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2 gap-2 flex-wrap">
                        <label class="form-label fw-bold mb-0">2. Unggah file yang sudah diisi</label>
                        <a id="activeTemplateLink" href="<?= base_url('imports/master/template/' . strtolower($selectedType)) ?>" class="btn btn-sm btn-outline-success rounded-3">
                            <i data-lucide="download" style="width:14px;height:14px"></i> Unduh template terpilih
                        </a>
                    </div>
                    <label for="masterFile" id="dropZone" class="d-block border border-2 border-dashed rounded-4 p-4 text-center bg-light-subtle" style="cursor:pointer">
                        <i data-lucide="file-spreadsheet" class="text-success mb-2" style="width:38px;height:38px"></i>
                        <span class="fw-bold text-slate-800 d-block" id="fileLabel">Klik atau jatuhkan file Excel di sini</span>
                        <span class="text-muted fs-8 d-block mt-1">.xlsx, .xls, atau .csv • maksimum 10 MB • maksimum 10.000 baris</span>
                        <input id="masterFile" type="file" name="file" class="visually-hidden" accept=".xlsx,.xls,.csv" required>
                    </label>
                    <div class="alert alert-info border-0 rounded-3 fs-8 mt-3 mb-3">
                        <i data-lucide="shield-check" style="width:15px;height:15px" class="me-1"></i>
                        Data belum langsung masuk database. Sistem akan memvalidasi setiap baris dan menampilkan pratinjau terlebih dahulu.
                    </div>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 d-inline-flex align-items-center gap-2" id="uploadButton">
                        <i data-lucide="scan-line" style="width:16px;height:16px"></i> Upload dan Validasi
                    </button>
                </form>
            </div>

            <div class="col-xl-5 p-4 p-lg-5 bg-light-subtle">
                <h5 class="fw-bold text-slate-800 mb-3">Template Excel yang ramah pengguna</h5>
                <ul class="list-unstyled d-grid gap-3 fs-7 mb-4">
                    <li class="d-flex gap-2"><i data-lucide="check-circle-2" class="text-success flex-shrink-0" style="width:18px"></i><span>Header teknis siap impor, dengan komentar penjelas pada setiap kolom.</span></li>
                    <li class="d-flex gap-2"><i data-lucide="check-circle-2" class="text-success flex-shrink-0" style="width:18px"></i><span>Sheet <strong>Contoh</strong>, <strong>Petunjuk Pengisian</strong>, dan <strong>Referensi</strong>.</span></li>
                    <li class="d-flex gap-2"><i data-lucide="check-circle-2" class="text-success flex-shrink-0" style="width:18px"></i><span>Dropdown untuk nilai baku seperti unit, kategori, semester, status, dan jenis ruang.</span></li>
                    <li class="d-flex gap-2"><i data-lucide="check-circle-2" class="text-success flex-shrink-0" style="width:18px"></i><span>Kolom identitas diperlakukan sebagai teks agar nol di depan tidak hilang.</span></li>
                </ul>
                <div class="rounded-3 border bg-white p-3 fs-8 text-muted">
                    <strong class="text-slate-800 d-block mb-1">Urutan impor yang disarankan</strong>
                    1. Tingkat kelas → 2. Guru dan mata pelajaran → 3. Ruangan → 4. Kelas/rombel.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4" id="riwayat-import">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><h5 class="fw-bold text-slate-800 mb-1">Riwayat Import</h5><p class="text-muted fs-8 mb-0">Klik review untuk melihat hasil validasi per baris.</p></div>
            <span class="badge bg-light text-slate-700 rounded-pill"><?= count($batches) ?> pada halaman ini</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr class="text-uppercase text-muted fs-8"><th>Batch</th><th>Jenis & File</th><th>Hasil Validasi</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                <?php if (!$batches): ?>
                    <tr><td colspan="5" class="text-center py-5"><i data-lucide="inbox" class="text-muted mb-2" style="width:32px"></i><div class="text-muted">Belum ada riwayat import.</div></td></tr>
                <?php else: foreach ($batches as $batch): ?>
                    <?php $total = max(1, (int) $batch['total_rows']); ?>
                    <tr>
                        <td><strong>#<?= esc($batch['id']) ?></strong><span class="d-block text-muted fs-8"><?= esc(date('d M Y, H:i', strtotime($batch['created_at']))) ?></span></td>
                        <td><span class="badge bg-<?= $typeColors[$batch['import_type']] ?? 'secondary' ?> bg-opacity-10 text-dark mb-1"><?= esc($allowedTypes[$batch['import_type']]['label'] ?? $batch['import_type']) ?></span><span class="d-block text-truncate fs-8" style="max-width:260px" title="<?= esc($batch['source_filename']) ?>"><?= esc($batch['source_filename']) ?></span></td>
                        <td style="min-width:210px"><div class="progress mb-2" style="height:7px"><div class="progress-bar bg-success" style="width:<?= round(((int)$batch['valid_rows'] / $total) * 100) ?>%"></div><div class="progress-bar bg-warning" style="width:<?= round(((int)$batch['warning_rows'] / $total) * 100) ?>%"></div><div class="progress-bar bg-danger" style="width:<?= round(((int)$batch['error_rows'] / $total) * 100) ?>%"></div></div><span class="fs-9 text-success me-2"><?= esc($batch['valid_rows']) ?> valid</span><span class="fs-9 text-warning me-2"><?= esc($batch['warning_rows']) ?> warning</span><span class="fs-9 text-danger"><?= esc($batch['error_rows']) ?> error</span></td>
                        <td><span class="badge rounded-pill <?= $batch['status'] === 'APPLIED' ? 'bg-success' : ($batch['status'] === 'VALIDATED' ? 'bg-info' : 'bg-secondary') ?>"><?= esc($batch['status']) ?></span><?php if ($batch['status'] === 'APPLIED'): ?><span class="d-block fs-9 text-muted mt-1"><?= esc($batch['applied_rows']) ?> diterapkan</span><?php endif; ?></td>
                        <td class="text-end"><a href="<?= base_url('imports/master/' . $batch['uuid']) ?>" class="btn btn-sm btn-outline-primary rounded-3">Review</a></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3"><?= $pager->links() ?></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('masterFile');
    const fileLabel = document.getElementById('fileLabel');
    const dropZone = document.getElementById('dropZone');
    const templateLink = document.getElementById('activeTemplateLink');
    const templateBase = <?= json_encode(base_url('imports/master/template/'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    document.querySelectorAll('.import-type-input').forEach(input => input.addEventListener('change', () => {
        templateLink.href = templateBase + input.value.toLowerCase();
    }));
    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        fileLabel.textContent = file ? file.name + ' • ' + (file.size / 1024 / 1024).toFixed(2) + ' MB' : 'Klik atau jatuhkan file Excel di sini';
        dropZone.classList.toggle('border-success', Boolean(file));
    });
    ['dragenter', 'dragover'].forEach(eventName => dropZone.addEventListener(eventName, event => { event.preventDefault(); dropZone.classList.add('border-primary'); }));
    ['dragleave', 'drop'].forEach(eventName => dropZone.addEventListener(eventName, event => { event.preventDefault(); dropZone.classList.remove('border-primary'); }));
    dropZone.addEventListener('drop', event => { if (event.dataTransfer.files.length) { fileInput.files = event.dataTransfer.files; fileInput.dispatchEvent(new Event('change')); } });
    document.getElementById('masterImportForm').addEventListener('submit', () => {
        const button = document.getElementById('uploadButton'); button.disabled = true; button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memvalidasi file…';
    });
});
</script>
<?= $this->endSection() ?>
