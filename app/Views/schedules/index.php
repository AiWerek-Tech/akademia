<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 font-weight-bold text-gray-800">Versi Jadwal Pelajaran</h1>
        <p class="text-muted">Unit: <strong><?= esc($unit['name'] ?? 'SMP & SMA') ?></strong></p>
    </div>
    <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#createScheduleVersionModal">
        <i class="fas fa-plus mr-1"></i> Buat Versi Jadwal Baru
    </button>
</div>

<div class="card border-left-primary shadow h-100 py-2 mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle">
                <thead class="thead-light">
                    <tr>
                        <th>Kode</th>
                        <th>Nama Versi</th>
                        <th>Tahun / Periode</th>
                        <th>Versi Penugasan (M4)</th>
                        <th>Status Workflow</th>
                        <th>Revisi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($versions)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-calendar-alt fa-2x mb-2 d-block text-gray-400"></i>
                                Belum ada versi jadwal pelajaran. Klik tombol di atas untuk membuat versi baru.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($versions as $v): ?>
                            <tr>
                                <td><code><?= esc($v['code']) ?></code></td>
                                <td><strong><?= esc($v['name']) ?></strong></td>
                                <td><?= esc($v['period_name'] ?? '-') ?></td>
                                <td><?= esc($v['assignment_name'] ?? '-') ?></td>
                                <td>
                                    <span class="badge badge-<?= $v['workflow_status'] === 'APPROVED' ? 'success' : ($v['workflow_status'] === 'LOCKED' ? 'secondary' : 'info') ?>">
                                        <?= esc($v['workflow_status']) ?>
                                    </span>
                                </td>
                                <td>v<?= esc($v['revision_number']) ?></td>
                                <td>
                                    <a href="<?= base_url('schedules/' . $v['id'] . '/editor') ?>" class="btn btn-sm btn-info shadow-sm">
                                        <i class="fas fa-th mr-1"></i> Buka Editor
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

<!-- Modal Buat Versi Jadwal -->
<div class="modal fade" id="createScheduleVersionModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">Buat Versi Jadwal Pelajaran</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="createScheduleVersionForm" action="<?= base_url('schedules/create') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Kode Versi</label>
                        <input type="text" name="code" class="form-control" placeholder="Contoh: JADWAL-2026-T1" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Versi</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Jadwal Semester Ganjil 2026/2027" required>
                    </div>
                    <div class="form-group">
                        <label>Periode Akademik ID</label>
                        <input type="number" name="academic_period_id" class="form-control" required value="1">
                    </div>
                    <div class="form-group">
                        <label>Versi Kurikulum (M3) ID</label>
                        <input type="number" name="curriculum_version_id" class="form-control" required value="1">
                    </div>
                    <div class="form-group">
                        <label>Versi Penugasan (M4) ID</label>
                        <input type="number" name="assignment_version_id" class="form-control" required value="1">
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan Versi</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
