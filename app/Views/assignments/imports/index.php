<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Impor Penugasan Mengajar</h1>
            <p class="text-muted small mb-0">Unggah dan tinjau batch penugasan mengajar guru via template Excel</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('assignments/imports/template') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-download me-1"></i> Unduh Template Excel
            </a>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="bi bi-upload me-1"></i> Unggah File Baru
            </button>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= esc(session()->getFlashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Waktu Unggah</th>
                            <th>Nama File</th>
                            <th>Versi Target</th>
                            <th>Total Baris</th>
                            <th>Valid / Warning / Error</th>
                            <th>Status Batch</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($batches)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Belum ada berkas yang diunggah.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($batches as $b): ?>
                                <tr>
                                    <td><?= esc($b['created_at']) ?></td>
                                    <td class="fw-bold"><?= esc($b['source_filename']) ?></td>
                                    <td><?= esc($b['version_code'] ?? 'Staging') ?></td>
                                    <td><?= esc($b['total_rows']) ?></td>
                                    <td>
                                        <span class="badge bg-success"><?= esc($b['valid_rows']) ?></span>
                                        <span class="badge bg-warning text-dark"><?= esc($b['warning_rows']) ?></span>
                                        <span class="badge bg-danger"><?= esc($b['error_rows']) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $badge = match ($b['status']) {
                                            'PARSED'    => 'bg-secondary',
                                            'VALIDATED' => 'bg-info text-dark',
                                            'APPLIED'   => 'bg-success',
                                            default     => 'bg-light text-muted',
                                        };
                                        ?>
                                        <span class="badge <?= $badge ?>"><?= esc($b['status']) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= base_url('assignments/imports/' . $b['uuid']) ?>" class="btn btn-sm btn-outline-primary">
                                            Tinjau Staging
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
        <form method="post" action="<?= base_url('assignments/imports/upload') ?>" enctype="multipart/form-data" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Unggah Template Penugasan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Versi Penugasan Target</label>
                    <select name="assignment_version_id" class="form-select form-select-sm" required>
                        <option value="">-- Pilih Versi Penugasan --</option>
                        <?php
                        // Fetch active draft/validated/reviewed versions to import into
                        $db = \Config\Database::connect();
                        $vers = $db->table('assignment_versions')->whereIn('workflow_status', ['DRAFT', 'VALIDATED', 'REVIEWED'])->get()->getResultArray();
                        foreach ($vers as $v):
                        ?>
                            <option value="<?= $v['id'] ?>"><?= esc($v['name']) ?> (<?= esc($v['code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Pilih File (.xlsx, .xls, .csv)</label>
                    <input type="file" name="import_file" class="form-control form-control-sm" accept=".xlsx,.xls,.csv" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Proses Validasi</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
