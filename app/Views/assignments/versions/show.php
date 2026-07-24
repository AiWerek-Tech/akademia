<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4 py-4">
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= base_url('assignments') ?>">Penugasan</a></li>
                <li class="breadcrumb-item active"><?= esc($version['code']) ?></li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0 text-gray-800"><?= esc($version['name']) ?></h1>
                <p class="text-muted small mb-0">Kode: <?= esc($version['code']) ?> | Status: <strong><?= esc($version['workflow_status']) ?></strong></p>
            </div>
            
            <div class="d-flex gap-2">
                <!-- Workflow Actions -->
                <?php if ($version['workflow_status'] === 'DRAFT' && has_permission('assignments.validate')): ?>
                    <form method="post" action="<?= base_url('assignments/' . $version['uuid'] . '/workflow/validate') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-info btn-sm">Validasi</button>
                    </form>
                <?php endif; ?>

                <?php if ($version['workflow_status'] === 'VALIDATED' && has_permission('assignments.review')): ?>
                    <form method="post" action="<?= base_url('assignments/' . $version['uuid'] . '/workflow/review') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-primary btn-sm">Setujui Review</button>
                    </form>
                <?php endif; ?>

                <?php if ($version['workflow_status'] === 'REVIEWED' && has_permission('assignments.approve')): ?>
                    <form method="post" action="<?= base_url('assignments/' . $version['uuid'] . '/workflow/approve') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-success btn-sm">Approve</button>
                    </form>
                <?php endif; ?>

                <?php if ($version['workflow_status'] === 'APPROVED' && has_permission('assignments.lock')): ?>
                    <form method="post" action="<?= base_url('assignments/' . $version['uuid'] . '/workflow/lock') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-dark btn-sm"><i class="bi bi-lock-fill"></i> Kunci & Aktifkan</button>
                    </form>
                <?php endif; ?>

                <!-- Revision / Clone -->
                <?php if (($version['workflow_status'] === 'LOCKED' || $version['workflow_status'] === 'APPROVED') && has_permission('assignments.revise')): ?>
                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#revisionModal">
                        <i class="bi bi-pencil-square"></i> Buat Revisi Baru
                    </button>
                <?php endif; ?>
            </div>
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

    <!-- Scope Switcher -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <form method="get" class="d-flex align-items-center gap-2">
                        <label class="small fw-bold text-muted text-nowrap mb-0">Tampilkan Unit:</label>
                        <select name="unit_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                            <?php foreach ($units as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= ($unit_id == $u['id']) ? 'selected' : '' ?>><?= esc($u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Matrix View -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 small fw-bold text-uppercase text-secondary">Matriks Kebutuhan & Alokasi Mengajar</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kelas/Rombel</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Kebutuhan JP</th>
                                    <th>Alokasi Jam</th>
                                    <th>Sisa Jam</th>
                                    <th>Guru Pengampu</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($matrix)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">Belum ada struktur kurikulum tersedia pada unit/fase ini.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($matrix as $row): ?>
                                        <tr>
                                            <td class="fw-bold"><?= esc($row['classroom_name']) ?></td>
                                            <td><?= esc($row['subject_name']) ?> <span class="badge bg-light text-secondary small"><?= esc($row['subject_code']) ?></span></td>
                                            <td><?= esc($row['effective_weekly_hours']) ?> JP</td>
                                            <td><?= esc($row['assigned_weekly_hours']) ?> JP</td>
                                            <td>
                                                <?php if ($row['remaining_weekly_hours'] > 0): ?>
                                                    <span class="text-danger fw-bold"><?= esc($row['remaining_weekly_hours']) ?> JP</span>
                                                <?php elseif ($row['remaining_weekly_hours'] < 0): ?>
                                                    <span class="text-warning fw-bold"><?= esc($row['remaining_weekly_hours']) ?> JP (Berlebih)</span>
                                                <?php else: ?>
                                                    <span class="text-success fw-bold">0 JP</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (empty($row['teachers'])): ?>
                                                    <span class="text-muted small"><em>Belum ditugaskan</em></span>
                                                <?php else: ?>
                                                    <ul class="list-unstyled mb-0 small">
                                                        <?php foreach ($row['teachers'] as $t): ?>
                                                            <li>
                                                                <strong><?= esc($t['full_name']) ?></strong> 
                                                                (<?= esc($t['assigned_weekly_hours']) ?> JP - <?= esc($t['assignment_role']) ?>)
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $sBadge = match ($row['validation_status']) {
                                                    'MATCHED'        => 'bg-success',
                                                    'UNASSIGNED'     => 'bg-danger',
                                                    'OVER_ALLOCATED' => 'bg-warning text-dark',
                                                    default          => 'bg-secondary',
                                                };
                                                ?>
                                                <span class="badge <?= $sBadge ?>"><?= esc($row['validation_status']) ?></span>
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

        <!-- Assign Teacher Panel -->
        <div class="col-lg-4">
            <?php if (!in_array($version['workflow_status'], ['APPROVED', 'LOCKED', 'ARCHIVED']) && has_permission('assignments.manage')): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 small fw-bold text-uppercase text-secondary">Alokasikan Guru Baru</h5>
                    </div>
                    <div class="card-body">
                        <form id="assignTeacherForm" method="post" action="<?= base_url('assignments/' . $version['uuid'] . '/store-assignment') ?>">
                            <?= csrf_field() ?>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Rombel Kelas <span class="text-danger">*</span></label>
                                <select name="classroom_id" class="form-select form-select-sm" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php foreach ($classrooms as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Mata Pelajaran <span class="text-danger">*</span></label>
                                <select name="subject_id" class="form-select form-select-sm" required>
                                    <option value="">-- Pilih Mapel --</option>
                                    <?php foreach ($subjects as $s): ?>
                                        <option value="<?= $s['id'] ?>"><?= esc($s['name']) ?> (<?= esc($s['code']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Guru Pengampu <span class="text-danger">*</span></label>
                                <select name="teacher_id" class="form-select form-select-sm" required>
                                    <option value="">-- Pilih Guru --</option>
                                    <?php foreach ($teachers as $t): ?>
                                        <option value="<?= $t['id'] ?>"><?= esc($t['full_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Peran Mengajar <span class="text-danger">*</span></label>
                                <select name="assignment_role" class="form-select form-select-sm" required>
                                    <option value="PRIMARY">PRIMARY (Guru Utama)</option>
                                    <option value="CO_TEACHER">CO_TEACHER</option>
                                    <option value="ASSISTANT">ASSISTANT</option>
                                    <option value="SUBSTITUTE">SUBSTITUTE</option>
                                    <option value="OTHER">OTHER</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Jumlah Jam Mingguan (JP) <span class="text-danger">*</span></label>
                                <input type="number" step="0.5" name="assigned_weekly_hours" class="form-control form-control-sm" placeholder="Contoh: 4" required>
                            </div>

                            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-save me-1"></i> Simpan Penugasan</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-light border small text-muted">
                    <i class="bi bi-info-circle me-1"></i> Mode read-only aktif untuk versi ini karena berstatus <?= esc($version['workflow_status']) ?>.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Revision Modal -->
<div class="modal fade" id="revisionModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="<?= base_url('assignments/' . $version['uuid'] . '/clone') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Buat Revisi Penugasan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Kode Versi Baru</label>
                    <input type="text" name="code" class="form-control form-control-sm" placeholder="Contoh: TP-26-27-SM1-V1-REV1" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Versi Baru</label>
                    <input type="text" name="name" class="form-control form-control-sm" placeholder="Contoh: Penugasan Guru Ganjil v1 Revisi 1" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Alasan Perubahan / Revisi</label>
                    <textarea name="change_reason" class="form-control form-control-sm" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger btn-sm">Buat Revisi</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('assignTeacherForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const url = form.action;
    const formData = new FormData(form);
    const submitButton = form.querySelector('[type="submit"]');
    const originalButtonContent = submitButton?.innerHTML;
    const showMessage = (options) => Swal.fire(SpTheme.mergeSwalOptions({
        confirmButtonText: 'Mengerti',
        buttonsStyling: false,
        customClass: {
            popup: 'rounded-4',
            confirmButton: 'btn btn-primary rounded-3 px-4'
        },
        ...options
    }));

    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
    }

    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            return showMessage({
                icon: 'success',
                title: 'Penugasan tersimpan',
                text: data.message,
                timer: 1600,
                timerProgressBar: true,
                showConfirmButton: false
            }).then(() => window.location.reload());
        } else {
            const details = data.errors ? Object.values(data.errors).join('\n') : '';
            return showMessage({
                icon: 'error',
                title: 'Gagal menyimpan',
                text: data.message + (details ? '\n' + details : '')
            });
        }
    })
    .catch(err => {
        console.error(err);
        return showMessage({
            icon: 'error',
            title: 'Koneksi bermasalah',
            text: 'Terjadi kesalahan jaringan. Periksa koneksi Anda lalu coba kembali.'
        });
    })
    .finally(() => {
        if (submitButton && document.body.contains(submitButton)) {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonContent;
        }
    });
});
</script>
<?= $this->endSection() ?>
