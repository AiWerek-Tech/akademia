<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div><div class="text-success fw-semibold small text-uppercase mb-1">Perencanaan</div><h1 class="h3 mb-1">Import Kurikulum</h1><p class="text-muted mb-0">Isi template Excel, unggah, tinjau hasil pemeriksaan, lalu terapkan.</p></div>
        <div class="d-flex gap-2"><a href="<?= base_url('curriculum') ?>" class="btn btn-outline-secondary"><i class="bi bi-grid me-1"></i>Struktur</a><a href="<?= base_url('curriculum/imports/template') ?>" class="btn btn-success"><i class="bi bi-download me-1"></i>Unduh template Excel</a></div>
    </div>

    <?php foreach (['success' => 'success', 'error' => 'danger'] as $key => $type): ?><?php if ($message = session()->getFlashdata($key)): ?><div class="alert alert-<?= $type ?> alert-dismissible fade show"><?= esc($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?><?php endforeach; ?>
    <?php if ($errors = session()->getFlashdata('errors')): ?><div class="alert alert-danger"><strong>File belum dapat diproses.</strong><ul class="mb-0 mt-2"><?php foreach ($errors as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body d-flex gap-3"><span class="badge bg-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px">1</span><div><h6 class="mb-1">Unduh template</h6><p class="small text-muted mb-0">Template berisi panduan dan referensi kode master terbaru.</p></div></div></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body d-flex gap-3"><span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px">2</span><div><h6 class="mb-1">Isi dan unggah</h6><p class="small text-muted mb-0">Jangan mengubah header. File .xlsx paling direkomendasikan.</p></div></div></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body d-flex gap-3"><span class="badge bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px">3</span><div><h6 class="mb-1">Tinjau dan terapkan</h6><p class="small text-muted mb-0">Data belum masuk sebelum Anda menekan tombol Terapkan.</p></div></div></div></div>
    </div>

    <div class="card border-0 shadow-sm mb-4"><div class="card-header bg-white py-3"><h5 class="mb-0"><i class="bi bi-cloud-arrow-up me-2"></i>Unggah file</h5></div><div class="card-body">
        <?php if ($versions === []): ?><div class="alert alert-warning mb-0">Belum ada kurikulum tujuan. <a href="<?= base_url('curriculum/create') ?>" class="alert-link">Buat kurikulum terlebih dahulu</a>.</div>
        <?php else: ?><form action="<?= base_url('curriculum/imports/upload') ?>" method="post" enctype="multipart/form-data" class="row g-3 align-items-end"><?= csrf_field() ?>
            <div class="col-lg-5"><label class="form-label fw-semibold">Kurikulum tujuan <span class="text-danger">*</span></label><select name="curriculum_version_id" class="form-select" required><option value="">Pilih kurikulum</option><?php foreach ($versions as $version): ?><option value="<?= (int) $version['id'] ?>" <?= (string) old('curriculum_version_id') === (string) $version['id'] ? 'selected' : '' ?>><?= (int) $version['is_active'] === 1 ? '★ ' : '' ?><?= esc($version['code'] . ' · ' . $version['name'] . ' · ' . ($version['period_name'] ?? '')) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-5"><label class="form-label fw-semibold">File Excel <span class="text-danger">*</span></label><input type="file" name="import_file" class="form-control" accept=".xlsx,.xls,.csv" required><div class="form-text">Maksimal 10 MB dan 10.000 baris.</div></div>
            <div class="col-lg-2"><button class="btn btn-primary w-100"><i class="bi bi-shield-check me-1"></i>Periksa file</button></div>
        </form><?php endif; ?>
    </div></div>

    <div class="card border-0 shadow-sm"><div class="card-header bg-white py-3"><h5 class="mb-0">Riwayat import</h5></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th class="ps-4">File</th><th>Status</th><th class="text-center">Total</th><th class="text-center">Siap</th><th class="text-center">Perlu perhatian</th><th class="text-center">Bermasalah</th><th class="text-end pe-4">Aksi</th></tr></thead><tbody>
    <?php if ($batches === []): ?><tr><td colspan="7" class="text-center py-5"><i class="bi bi-file-earmark-spreadsheet fs-1 text-muted"></i><h5 class="mt-3">Belum ada riwayat import</h5><p class="text-muted mb-0">Unggah file pertama Anda melalui formulir di atas.</p></td></tr>
    <?php else: foreach ($batches as $batch): ?><tr><td class="ps-4"><div class="fw-semibold"><?= esc($batch['source_filename']) ?></div><div class="small text-muted"><?= !empty($batch['created_at']) ? date('d M Y, H:i', strtotime($batch['created_at'])) : '-' ?></div></td><td><?php $status = $batch['status'] === 'APPLIED' ? ['success', 'Sudah diterapkan'] : ['info text-dark', 'Siap ditinjau']; ?><span class="badge bg-<?= $status[0] ?>"><?= $status[1] ?></span></td><td class="text-center fw-semibold"><?= (int) $batch['total_rows'] ?></td><td class="text-center text-success fw-semibold"><?= (int) $batch['valid_rows'] ?></td><td class="text-center text-warning fw-semibold"><?= (int) $batch['warning_rows'] ?></td><td class="text-center text-danger fw-semibold"><?= (int) $batch['error_rows'] ?></td><td class="text-end pe-4"><a href="<?= base_url('curriculum/imports/' . $batch['uuid']) ?>" class="btn btn-sm btn-outline-primary">Lihat hasil</a></td></tr><?php endforeach; endif; ?>
    </tbody></table></div></div>
</div>
<?= $this->endSection() ?>
