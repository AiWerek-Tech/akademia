<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Import Struktur Kurikulum</h1>
            <p class="text-muted small mb-0">Kelola batch impor data kurikulum dari file Excel via staging pipeline</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('curriculum/imports/template') ?>" class="btn btn-outline-success">
                <i class="bi bi-download me-1"></i> Unduh Template Excel
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="bi bi-upload me-1"></i> Upload File Import
            </button>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID Batch</th>
                            <th>Nama File</th>
                            <th>Status</th>
                            <th>Total Baris</th>
                            <th>Valid</th>
                            <th>Warning</th>
                            <th>Error</th>
                            <th>Diterapkan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($batches)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">Belum ada history import kurikulum.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($batches as $b): ?>
                                <tr>
                                    <td class="fw-bold">#<?= $b['id'] ?></td>
                                    <td><?= esc($b['source_filename']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $b['status'] === 'APPLIED' ? 'success' : ($b['status'] === 'VALIDATED' ? 'info text-dark' : 'secondary') ?>">
                                            <?= $b['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= $b['total_rows'] ?></td>
                                    <td><span class="text-success fw-bold"><?= $b['valid_rows'] ?></span></td>
                                    <td><span class="text-warning fw-bold"><?= $b['warning_rows'] ?></span></td>
                                    <td><span class="text-danger fw-bold"><?= $b['error_rows'] ?></span></td>
                                    <td><?= $b['applied_rows'] ?></td>
                                    <td class="text-end">
                                        <a href="<?= base_url('curriculum/imports/' . $b['uuid']) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye me-1"></i> Preview & Action
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= base_url('curriculum/imports/upload') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Upload File Kurikulum</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Versi Kurikulum Target <span class="text-danger">*</span></label>
                        <select name="curriculum_version_id" class="form-select" required>
                            <option value="">-- Pilih Versi Kurikulum --</option>
                            <?php
                            $vm = new \App\Models\CurriculumVersionModel();
                            $versions = $vm->whereNotIn('workflow_status', ['LOCKED', 'ARCHIVED'])->findAll();
                            foreach ($versions as $v):
                            ?>
                                <option value="<?= $v['id'] ?>"><?= esc($v['code']) ?> - <?= esc($v['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih File (.xlsx, .csv) <span class="text-danger">*</span></label>
                        <input type="file" name="import_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i> Unggah & Parse Staging</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
