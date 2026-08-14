<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid py-4">
    <!-- Header Page -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-7">
            <h3 class="fw-bold text-dark mb-1">
                <i data-lucide="activity" class="text-primary me-2"></i>Monitoring Presensi & Jurnal Mengajar
            </h3>
            <p class="text-muted mb-0">Dashboard eksekutif rekapitulasi kehadiran peserta didik dan pengesahan jurnal kelas.</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex flex-wrap justify-content-md-end gap-2">
            <a href="<?= base_url('attendances/export?' . http_build_query($_GET)) ?>" class="btn btn-outline-success rounded-3 fw-semibold">
                <i data-lucide="file-spreadsheet" class="me-1"></i> Ekspor CSV
            </a>
            <a href="<?= base_url('attendances/print-unit-report?' . http_build_query($_GET)) ?>" target="_blank" class="btn btn-outline-secondary rounded-3 fw-semibold">
                <i data-lucide="printer" class="me-1"></i> Cetak Laporan Unit
            </a>
        </div>
    </div>

    <!-- Executive KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-4 border-primary">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase small fw-bold text-muted">Total Sesi Terisi</span>
                        <h2 class="fw-bold text-dark mb-0 mt-1"><?= number_format($total_sessions) ?></h2>
                        <small class="text-primary fw-medium">Sesi Pembelajaran Logged</small>
                    </div>
                    <div class="p-3 bg-primary-subtle text-primary rounded-circle">
                        <i data-lucide="calendar-check-2" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-4 border-success">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase small fw-bold text-muted">Tingkat Kehadiran (Rate)</span>
                        <h2 class="fw-bold text-success mb-0 mt-1"><?= $attendance_rate ?>%</h2>
                        <small class="text-success fw-medium"><?= number_format($counts['HADIR'] ?? 0) ?> Hadir dari <?= number_format($total_logs) ?> Log</small>
                    </div>
                    <div class="p-3 bg-success-subtle text-success rounded-circle">
                        <i data-lucide="trending-up" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-4 border-warning">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase small fw-bold text-muted">Total Izin & Sakit</span>
                        <h2 class="fw-bold text-warning-emphasis mb-0 mt-1"><?= number_format(($counts['IZIN'] ?? 0) + ($counts['SAKIT'] ?? 0)) ?></h2>
                        <small class="text-muted">Izin: <?= esc($counts['IZIN'] ?? 0) ?> | Sakit: <?= esc($counts['SAKIT'] ?? 0) ?></small>
                    </div>
                    <div class="p-3 bg-warning-subtle text-warning-emphasis rounded-circle">
                        <i data-lucide="shield-alert" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-4 border-danger">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase small fw-bold text-muted">Total Alpa (Tanpa Ket.)</span>
                        <h2 class="fw-bold text-danger mb-0 mt-1"><?= number_format($counts['ALPA'] ?? 0) ?></h2>
                        <small class="text-danger fw-medium">Memerlukan Perhatian</small>
                    </div>
                    <div class="p-3 bg-danger-subtle text-danger rounded-circle">
                        <i data-lucide="user-x" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="<?= base_url('attendances') ?>" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Unit Sekolah</label>
                    <select name="unit_id" class="form-select form-select-sm rounded-2">
                        <option value="">-- Semua Unit --</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= (int)$selectedUnit === (int)$u['id'] ? 'selected' : '' ?>><?= esc($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Kelas / Rombel</label>
                    <select name="classroom_id" class="form-select form-select-sm rounded-2">
                        <option value="">-- Semua Kelas --</option>
                        <?php foreach ($classrooms as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (int)$selectedClassroom === (int)$c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Mata Pelajaran</label>
                    <select name="subject_id" class="form-select form-select-sm rounded-2">
                        <option value="">-- Semua Mata Pelajaran --</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= (int)$selectedSubject === (int)$s['id'] ? 'selected' : '' ?>><?= esc($s['name']) ?> (<?= esc($s['code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Jenis Sesi</label>
                    <select name="session_type" class="form-select form-select-sm rounded-2"><option value="">-- Semua Jenis --</option><?php foreach (\App\Services\AttendanceService::SESSION_TYPES as $type): ?><option value="<?= $type ?>" <?= ($selectedSessionType ?? '')===$type?'selected':'' ?>><?= esc(\App\Services\AttendanceService::sessionTypeLabel($type)) ?></option><?php endforeach; ?></select>
                </div>

                <div class="col-md-2"><label class="form-label small fw-bold text-muted">Dari tanggal</label><input type="date" name="start_date" class="form-control form-control-sm" value="<?= esc($startDate) ?>"></div>
                <div class="col-md-2"><label class="form-label small fw-bold text-muted">Sampai tanggal</label><input type="date" name="end_date" class="form-control form-control-sm" value="<?= esc($endDate) ?>"></div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100 rounded-2 fw-bold">
                        <i data-lucide="filter" class="me-1 w-4 h-4"></i> Filter
                    </button>
                    <a href="<?= base_url('attendances') ?>" class="btn btn-outline-secondary btn-sm rounded-2" title="Reset Filter">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Alert / Problematic Students Warning Box -->
    <?php if (!empty($problematic_students)): ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-4 border-danger">
            <div class="card-header bg-danger-subtle text-danger border-0 py-3 d-flex align-items-center">
                <i data-lucide="alert-triangle" class="w-5 h-5 me-2"></i>
                <h6 class="fw-bold mb-0">Peringatan Ketidakhadiran Siswa (Ketidakhadiran Tinggi / Alpa)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="text-muted small">
                                <th class="ps-4">NIS</th>
                                <th>Nama Peserta Didik</th>
                                <th>Kelas</th>
                                <th class="text-center">Total Alpa</th>
                                <th class="text-center">Total Sakit</th>
                                <th class="text-center">Total Izin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($problematic_students as $pst): ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?= esc($pst['student_number']) ?></td>
                                    <td class="fw-semibold text-dark"><?= esc($pst['full_name']) ?></td>
                                    <td><span class="badge bg-slate-100 text-dark"><?= esc($pst['classroom_name']) ?></span></td>
                                    <td class="text-center"><span class="badge bg-danger text-white px-2 py-1"><?= esc($pst['total_alpa']) ?> Hari</span></td>
                                    <td class="text-center"><span class="badge bg-info-subtle text-info px-2 py-1"><?= esc($pst['total_sakit']) ?> Hari</span></td>
                                    <td class="text-center"><span class="badge bg-warning-subtle text-warning-emphasis px-2 py-1"><?= esc($pst['total_izin']) ?> Hari</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Sessions Data Table with Bulk Actions -->
    <div class="card border-0 shadow-sm rounded-3">
        <form action="<?= base_url('attendances/bulk-verify') ?>" method="POST" id="bulkForm">
            <?= csrf_field() ?>
            <div class="card-header bg-white py-3 border-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h5 class="fw-bold mb-0 text-dark"><i data-lucide="list" class="me-2 text-primary"></i>Daftar Sesi Presensi & Jurnal Kelas</h5>
                <?php if (has_permission('attendances.admin')): ?>
                    <button type="submit" class="btn btn-sm btn-success rounded-3 px-3 fw-semibold" onclick="return confirm('Sahkan sesi presensi yang dipilih?');">
                        <i data-lucide="check-check" class="me-1 w-4 h-4"></i> Sahkan Terpilih (Bulk Verify)
                    </button>
                <?php endif; ?>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="text-muted small text-uppercase">
                                <th class="ps-4" style="width: 40px;">
                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                </th>
                                <th>Tanggal</th>
                                <th>Pertemuan</th>
                                <th>Unit / Kelas</th>
                                <th>Kegiatan / Jenis</th>
                                <th>Guru Pengampu</th>
                                <th>Topik / Pokok Bahasan</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sessions)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i data-lucide="inbox" class="w-10 h-10 text-slate-300 d-block mx-auto mb-2"></i>
                                        Tidak ada data sesi presensi yang ditemukan untuk filter ini.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sessions as $sess): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <?php if($sess['status']!=='DRAFT'):?><input type="checkbox" name="session_ids[]" value="<?= $sess['id'] ?>" class="form-check-input session-check"><?php endif;?>
                                        </td>
                                        <td class="fw-bold text-dark">
                                            <?= date('d/m/Y', strtotime($sess['attendance_date'])) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary fw-bold">Ke-<?= esc($sess['meeting_number']) ?></span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-dark"><?= esc($sess['classroom_name']) ?></span>
                                            <small class="text-muted d-block"><?= esc($sess['unit_name']) ?></small>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-primary"><?= esc($sess['subject_name']) ?></span>
                                            <small class="text-muted d-block"><?= esc(\App\Services\AttendanceService::sessionTypeLabel($sess['session_type'] ?? 'SUBJECT')) ?></small>
                                        </td>
                                        <td><span class="fw-medium text-dark"><?= esc($sess['teacher_name']) ?></span></td>
                                        <td style="max-width: 200px;">
                                            <span class="text-truncate d-block fw-medium text-dark"><?= esc($sess['topic'] ?: '-') ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($sess['status'] === 'VERIFIED'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i data-lucide="check-circle-2" class="w-3.5 h-3.5 me-1"></i>VERIFIED</span>
                                            <?php elseif ($sess['status'] === 'DRAFT'): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border px-2 py-1">DRAFT</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i data-lucide="send" class="w-3.5 h-3.5 me-1"></i>SUBMITTED</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= base_url('attendances/' . $sess['id']) ?>" class="btn btn-outline-primary" title="Detail Presensi">
                                                    <i data-lucide="eye"></i>
                                                </a>
                                                <a href="<?= base_url('portal/attendance/session/' . $sess['id'] . '/print') ?>" target="_blank" class="btn btn-outline-secondary" title="Cetak Jurnal">
                                                    <i data-lucide="printer"></i>
                                                </a>
                                                <?php if (has_permission('attendances.admin') && $sess['status'] !== 'VERIFIED'): ?>
                                                    <form method="post" action="<?= base_url('attendances/' . $sess['id'] . '/verify') ?>" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-outline-success" title="Sahkan Sesi" aria-label="Sahkan sesi presensi">
                                                            <i data-lucide="check"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.session-check').forEach(chk => {
        chk.checked = this.checked;
    });
});
</script>
<?= $this->endSection() ?>
