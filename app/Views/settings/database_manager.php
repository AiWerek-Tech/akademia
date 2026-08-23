<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <nav aria-label="breadcrumb" class="mb-3"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= base_url('settings/database') ?>">Pengaturan</a></li><li class="breadcrumb-item active">Database</li></ol></nav>
    <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div><h1 class="h3 fw-bold text-gray-900 mb-1">Manajemen Database</h1><p class="text-muted mb-0">Backup, export, dan pengelolaan data berbasis modul.</p></div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4 text-center"><i data-lucide="database" class="text-primary mb-2" style="width:32px;height:32px"></i><div class="h4 fw-bold mb-0"><?= $summary['total_tables'] ?></div><small class="text-muted">Total Tabel</small></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4 text-center"><i data-lucide="hard-drive" class="text-success mb-2" style="width:32px;height:32px"></i><div class="h4 fw-bold mb-0"><?= $summary['total_size'] ?></div><small class="text-muted">Total Ukuran</small></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4 text-center"><i data-lucide="rows-3" class="text-info mb-2" style="width:32px;height:32px"></i><div class="h4 fw-bold mb-0"><?= number_format($summary['total_rows']) ?></div><small class="text-muted">Total Baris</small></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4 text-center"><i data-lucide="shield-check" class="text-warning mb-2" style="width:32px;height:32px"></i><div class="h4 fw-bold mb-0">HEALTHY</div><small class="text-muted">Status Integritas</small></div></div></div>
    </div>

    <ul class="nav nav-pills mb-4 gap-2" id="dbTabs">
        <li class="nav-item"><button class="nav-link active rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#tab-modules"><i data-lucide="layers" class="w-4 h-4 me-1 d-inline-block"></i> Per Modul</button></li>
        <li class="nav-item"><button class="nav-link rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#tab-all"><i data-lucide="list" class="w-4 h-4 me-1 d-inline-block"></i> Semua Tabel</button></li>
        <li class="nav-item"><button class="nav-link rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#tab-top"><i data-lucide="trophy" class="w-4 h-4 me-1 d-inline-block"></i> Top 5 Terbesar</button></li>
        <li class="nav-item"><button class="nav-link rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#tab-backup"><i data-lucide="hard-drive-download" class="w-4 h-4 me-1 d-inline-block"></i> Backup & Retensi</button></li>
    </ul>

    <div class="tab-content">
        <!-- TAB: Per Module -->
        <div class="tab-pane fade show active" id="tab-modules">
            <?php foreach ($grouped as $module => $tables): ?>
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-header bg-white border-bottom rounded-top-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><?= esc($module) ?></h6>
                    <span class="badge bg-primary-subtle text-primary rounded-pill"><?= count($tables) ?> tabel</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th class="ps-4">Nama Tabel</th><th>Baris</th><th>Ukuran</th><th>Engine</th><th class="text-end pe-4">Aksi</th></tr></thead>
                            <tbody>
                            <?php foreach ($tables as $t): ?>
                                <tr>
                                    <td class="ps-4 fw-medium"><code class="text-primary"><?= esc($t['name']) ?></code></td>
                                    <td><?= number_format($t['rows']) ?></td>
                                    <td><?= $t['size'] ?></td>
                                    <td><span class="badge bg-secondary-subtle text-secondary rounded-pill"><?= $t['engine'] ?></span></td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= base_url('settings/database/' . $t['name'] . '/export') ?>" class="btn btn-outline-success rounded-pill" title="Export SQL"><i data-lucide="download" class="w-3 h-3"></i></a>
                                            <button class="btn btn-outline-info rounded-pill btn-view-table" data-table="<?= esc($t['name']) ?>" title="Lihat Data"><i data-lucide="eye" class="w-3 h-3"></i></button>
                                            <?php if ($t['rows'] > 0): ?>
                                                <button class="btn btn-outline-danger rounded-pill btn-truncate" data-table="<?= esc($t['name']) ?>" data-rows="<?= $t['rows'] ?>" title="Kosongkan Tabel"><i data-lucide="trash-2" class="w-3 h-3"></i></button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- TAB: All Tables -->
        <div class="tab-pane fade" id="tab-all">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th class="ps-4">#</th><th>Nama Tabel</th><th>Baris</th><th>Ukuran</th><th>Engine</th><th class="text-end pe-4">Aksi</th></tr></thead>
                            <tbody>
                            <?php foreach ($tables as $i => $t): ?>
                                <tr>
                                    <td class="ps-4 text-muted"><?= $i + 1 ?></td>
                                    <td class="fw-medium"><code class="text-primary"><?= esc($t['name']) ?></code></td>
                                    <td><?= number_format($t['rows']) ?></td>
                                    <td><?= $t['size'] ?></td>
                                    <td><span class="badge bg-secondary-subtle text-secondary rounded-pill"><?= $t['engine'] ?></span></td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= base_url('settings/database/' . $t['name'] . '/export') ?>" class="btn btn-outline-success rounded-pill" title="Export"><i data-lucide="download" class="w-3 h-3"></i></a>
                                            <button class="btn btn-outline-info rounded-pill btn-view-table" data-table="<?= esc($t['name']) ?>" title="Lihat"><i data-lucide="eye" class="w-3 h-3"></i></button>
                                            <?php if ($t['rows'] > 0): ?>
                                                <button class="btn btn-outline-danger rounded-pill btn-truncate" data-table="<?= esc($t['name']) ?>" data-rows="<?= $t['rows'] ?>" title="Kosongkan"><i data-lucide="trash-2" class="w-3 h-3"></i></button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: Top 5 -->
        <div class="tab-pane fade" id="tab-top">
            <?php foreach ($summary['largest_tables'] as $i => $t): ?>
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-4 d-flex align-items-center gap-4">
                    <div class="text-center" style="min-width:60px"><div class="h3 fw-bold text-primary mb-0">#<?= $i + 1 ?></div></div>
                    <div class="flex-grow-1">
                        <code class="fs-6 fw-bold text-dark"><?= esc($t['name']) ?></code>
                        <div class="text-muted small mt-1"><?= number_format($t['rows']) ?> baris · <?= $t['size'] ?> · <?= $t['engine'] ?></div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= base_url('settings/database/' . $t['name'] . '/export') ?>" class="btn btn-outline-success btn-sm rounded-pill"><i data-lucide="download" class="w-3 h-3 me-1"></i>Export</a>
                        <button class="btn btn-outline-info btn-sm rounded-pill btn-view-table" data-table="<?= esc($t['name']) ?>"><i data-lucide="eye" class="w-3 h-3 me-1"></i>Lihat</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
        <!-- TAB: Backup Config -->
        <div class="tab-pane fade" id="tab-backup">
            <form method="POST" action="<?= base_url('settings/database/save-backup') ?>" class="card border-0 shadow-sm rounded-4">
                <?= csrf_field() ?>
                <div class="card-header bg-white border-bottom rounded-top-4"><h6 class="mb-0 fw-semibold"><i data-lucide="hard-drive-download" class="w-4 h-4 me-1 d-inline-block"></i> Pengaturan Backup Database</h6></div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-4">Konfigurasi backup otomatis dan retensi data untuk menjaga keamanan basis data.</p>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="card border rounded-4 h-100"><div class="card-body text-center p-4">
                                <i data-lucide="calendar-clock" class="text-primary mb-2" style="width:32px;height:32px"></i>
                                <h6 class="fw-bold">Retensi Backup</h6>
                                <input class="form-control rounded-3 text-center fw-bold fs-5 mt-2" name="backup_retention_days" type="number" value="<?= esc($dbConfig['backup_retention_days'] ?? '30') ?>" min="1">
                                <small class="text-muted">hari</small>
                                <div class="form-text">Hapus backup otomatis setelah periode ini</div>
                            </div></div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border rounded-4 h-100"><div class="card-body text-center p-4">
                                <i data-lucide="timer" class="text-success mb-2" style="width:32px;height:32px"></i>
                                <h6 class="fw-bold">Waktu Backup</h6>
                                <input class="form-control rounded-3 text-center fw-bold fs-5 mt-2" name="auto_backup_time" type="time" value="<?= esc($dbConfig['auto_backup_time'] ?? '02:00') ?>">
                                <div class="form-text">Jalankan backup setiap jam ini</div>
                            </div></div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border rounded-4 h-100"><div class="card-body text-center p-4">
                                <i data-lucide="power" class="text-warning mb-2" style="width:32px;height:32px"></i>
                                <h6 class="fw-bold">Status Backup</h6>
                                <div class="form-check form-switch justify-content-center mt-3">
                                    <input class="form-check-input" type="checkbox" name="auto_backup_enabled" value="1" <?= !empty($dbConfig['auto_backup_enabled']) ? 'checked' : '' ?> style="width:3em;height:1.5em">
                                </div>
                                <div class="mt-2"><span class="badge <?= !empty($dbConfig['auto_backup_enabled']) ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?> rounded-pill px-3">
                                    <?= !empty($dbConfig['auto_backup_enabled']) ? '● Aktif' : '○ Nonaktif' ?>
                                </span></div>
                            </div></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white border-top rounded-bottom-4 p-4"><button class="btn btn-primary rounded-pill px-4"><i data-lucide="save" class="w-4 h-4 me-1 d-inline-block"></i> Simpan Pengaturan Backup</button></div>
            </form>
        </div>
    </div>

<!-- Table Detail Modal -->
<div class="modal fade" id="tableDetailModal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content rounded-4">
    <div class="modal-header"><h5 class="modal-title fw-bold" id="modalTableName">Data Tabel</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="modalBody"><div class="text-center py-4"><div class="spinner-border text-primary"></div></div></div>
</div></div></div>

<!-- Confirm Truncate Modal -->
<div class="modal fade" id="truncateModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content rounded-4">
    <div class="modal-header border-0"><h5 class="modal-title fw-bold text-danger">⚠️ Konfirmasi Pengosongan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><p>Anda akan mengosongkan tabel <strong id="truncateTableName"></strong> yang berisi <strong id="truncateTableRows"></strong> baris.</p><p class="text-danger fw-semibold">Tindakan ini tidak dapat dibatalkan!</p></div>
    <div class="modal-footer border-0">
        <button class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
        <form method="POST" id="truncateForm"><input type="hidden" name="csrf_token_name" value="<?= csrf_hash() ?>"><button class="btn btn-danger rounded-pill px-3"><i data-lucide="trash-2" class="w-4 h-4 me-1 d-inline-block"></i> Ya, Kosongkan</button></form>
    </div>
</div></div></div>

<script>
document.addEventListener('click', function(e) {
    // View table
    var btn = e.target.closest('.btn-view-table');
    if (btn) {
        var table = btn.dataset.table;
        document.getElementById('modalTableName').textContent = 'Data: ' + table;
        document.getElementById('modalBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
        new bootstrap.Modal(document.getElementById('tableDetailModal')).show();
        fetch('/settings/database/' + table + '/detail').then(function(r){return r.json()}).then(function(d){
            if (d.status !== 'success') { document.getElementById('modalBody').innerHTML = '<p class="text-danger">Error: ' + d.message + '</p>'; return; }
            var html = '<p class="text-muted mb-3">' + d.count + ' total baris, menampilkan 50 baris pertama.</p>';
            html += '<div class="table-responsive"><table class="table table-sm table-striped"><thead class="table-light"><tr>';
            d.columns.forEach(function(c){ html += '<th><small>' + c.name + '</small></th>'; });
            html += '</tr></thead><tbody>';
            d.rows.forEach(function(r){ html += '<tr>'; d.columns.forEach(function(c){ html += '<td><small>' + (r[c.name] !== null ? r[c.name] : '<em class="text-muted">NULL</em>') + '</small></th>'; }); html += '</tr>'; });
            html += '</tbody></table></div>';
            document.getElementById('modalBody').innerHTML = html;
        });
    }
    // Truncate confirm
    var btnTrunc = e.target.closest('.btn-truncate');
    if (btnTrunc) {
        e.preventDefault();
        document.getElementById('truncateTableName').textContent = btnTrunc.dataset.table;
        document.getElementById('truncateTableRows').textContent = Number(btnTrunc.dataset.rows).toLocaleString();
        document.getElementById('truncateForm').action = '/settings/database/' + btnTrunc.dataset.table + '/truncate';
        new bootstrap.Modal(document.getElementById('truncateModal')).show();
    }
});
</script>
<?= $this->endSection() ?>
