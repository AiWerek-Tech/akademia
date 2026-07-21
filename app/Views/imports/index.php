<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold mb-1 text-slate-800 d-flex align-items-center gap-2">
            <i data-lucide="upload-cloud" class="text-primary" style="width: 24px; height: 24px;"></i>
            Import Master Data
        </h4>
        <p class="text-muted fs-7 mb-0">Impor data guru, mata pelajaran, tingkat, kelas, atau ruang melalui file Excel (.xlsx)</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <h5 class="fw-bold text-slate-800 mb-3">Upload File Baru</h5>
                    <form method="POST" action="<?= base_url('imports/master/upload') ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        
                        <div class="mb-3">
                            <label class="form-label fs-8 fw-bold">Jenis Master Data <span class="text-danger">*</span></label>
                            <select name="import_type" class="form-select rounded-3" required>
                                <option value="TEACHERS">1. Master Guru Global</option>
                                <option value="SUBJECTS">2. Master Mata Pelajaran Global</option>
                                <option value="CLASSROOMS">3. Kelas / Rombongan Belajar</option>
                                <option value="ROOMS">4. Ruangan Sekolah</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-8 fw-bold">File Excel (.xlsx) <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control rounded-3" accept=".xlsx,.xls,.csv" required>
                            <div class="form-text fs-9 text-muted mt-1">Gunakan format template yang sesuai. Ukuran max 10MB.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 rounded-3 d-flex align-items-center justify-content-center gap-2">
                            <i data-lucide="upload" style="width: 16px; height: 16px;"></i>
                            <span>Upload & Validasi</span>
                        </button>
                    </form>
                </div>

                <div class="mt-4 border-top pt-3">
                    <h6 class="fw-bold fs-8 text-muted mb-2">Unduh Template Contoh:</h6>
                    <div class="d-grid gap-2">
                        <a href="<?= base_url('imports/master/template/teachers') ?>" class="btn btn-sm btn-outline-secondary rounded-3 text-start d-flex align-items-center gap-2">
                            <i data-lucide="file-spreadsheet" style="width: 14px; height: 14px;"></i> Template Guru (.xlsx)
                        </a>
                        <a href="<?= base_url('imports/master/template/subjects') ?>" class="btn btn-sm btn-outline-secondary rounded-3 text-start d-flex align-items-center gap-2">
                            <i data-lucide="file-spreadsheet" style="width: 14px; height: 14px;"></i> Template Mapel (.xlsx)
                        </a>
                        <a href="<?= base_url('imports/master/template/classrooms') ?>" class="btn btn-sm btn-outline-secondary rounded-3 text-start d-flex align-items-center gap-2">
                            <i data-lucide="file-spreadsheet" style="width: 14px; height: 14px;"></i> Template Rombel (.xlsx)
                        </a>
                        <a href="<?= base_url('imports/master/template/rooms') ?>" class="btn btn-sm btn-outline-secondary rounded-3 text-start d-flex align-items-center gap-2">
                            <i data-lucide="file-spreadsheet" style="width: 14px; height: 14px;"></i> Template Ruang (.xlsx)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h5 class="fw-bold text-slate-800 mb-3">Riwayat Import & Staging</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr class="text-uppercase text-muted fs-8 fw-bold">
                                <th>Tanggal / Batch</th>
                                <th>Jenis Master</th>
                                <th>File Name</th>
                                <th>Total Baris</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($batches)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Belum ada riwayat import.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($batches as $b): ?>
                                    <tr>
                                        <td>
                                            <span class="fw-semibold text-slate-800 d-block"><?= esc(date('d M Y H:i', strtotime($b['created_at']))) ?></span>
                                            <span class="font-monospace fs-9 text-muted">Batch ID: <?= esc($b['id']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary bg-opacity-10 text-dark px-2.5 py-1 rounded-pill fs-8 text-capitalize"><?= esc($b['import_type']) ?></span>
                                        </td>
                                        <td class="fs-7 text-truncate" style="max-width: 150px;"><?= esc($b['source_filename']) ?></td>
                                        <td><?= esc($b['total_rows']) ?> Baris</td>
                                        <td>
                                            <?php if ($b['status'] === 'APPLIED'): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success px-2.5 py-1 rounded-pill fs-8">Applied</span>
                                            <?php elseif ($b['status'] === 'CANCELLED'): ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger px-2.5 py-1 rounded-pill fs-8">Cancelled</span>
                                            <?php elseif ($b['status'] === 'VALIDATED'): ?>
                                                <span class="badge bg-info bg-opacity-10 text-info px-2.5 py-1 rounded-pill fs-8">Validated</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning bg-opacity-10 text-warning px-2.5 py-1 rounded-pill fs-8"><?= esc($b['status']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= base_url('imports/master/' . $b['uuid']) ?>" class="btn btn-sm btn-outline-primary rounded-3">Review</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3"><?= $pager->links() ?></div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
