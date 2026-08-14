<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="<?= base_url('attendances') ?>" class="text-decoration-none text-muted small mb-1 d-inline-block">
                <i data-lucide="arrow-left" class="me-1 w-4 h-4"></i> Kembali ke Monitoring Presensi
            </a>
            <h3 class="fw-bold text-dark mb-0">Detail Sesi Presensi & Jurnal Kelas</h3>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('portal/attendance/session/' . $session['id'] . '/print') ?>" target="_blank" class="btn btn-outline-secondary rounded-3">
                <i data-lucide="printer" class="me-1"></i> Cetak Jurnal Kelas
            </a>
            <?php if (has_permission('attendances.admin') && $session['status'] !== 'VERIFIED'): ?>
                <form method="post" action="<?= base_url('attendances/' . $session['id'] . '/verify') ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-success rounded-3 fw-bold">
                        <i data-lucide="check-circle" class="me-1"></i> Sahkan Sesi Ini (Verify)
                    </button>
                </form>
            <?php elseif (has_permission('attendances.admin') && $session['status'] === 'VERIFIED'): ?>
                <form method="post" action="<?= base_url('attendances/' . $session['id'] . '/reopen') ?>" class="d-inline" onsubmit="return confirm('Buka kembali sesi ini untuk koreksi?')">
                    <?= csrf_field() ?><button class="btn btn-outline-warning rounded-3 fw-bold"><i data-lucide="lock-open" class="me-1"></i>Buka Koreksi</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Header Session Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-slate-900 text-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-white"><i data-lucide="info" class="me-2 text-info"></i>Informasi Sesi Pembelajaran</h5>
            <?php if ($session['status'] === 'VERIFIED'): ?>
                <span class="badge bg-success px-3 py-2">TERVERIFIKASI / DISAHKAN</span>
            <?php else: ?>
                <span class="badge bg-primary px-3 py-2">TERSIMPAN (SUBMITTED)</span>
            <?php endif; ?>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <span class="text-muted small d-block">Jenis Sesi</span>
                    <strong class="text-dark fs-6"><?= esc(\App\Services\AttendanceService::sessionTypeLabel($session['session_type'] ?? 'SUBJECT')) ?></strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">Unit / Sekolah</span>
                    <strong class="text-dark fs-6"><?= esc($session['unit_name']) ?></strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">Kelas / Rombel</span>
                    <strong class="text-dark fs-6"><?= esc($session['classroom_name']) ?></strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">Mata Pelajaran</span>
                    <strong class="text-primary fs-6"><?= esc($session['subject_name']) ?> (<?= esc($session['subject_code']) ?>)</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">Guru Pengampu</span>
                    <strong class="text-dark fs-6"><?= esc($session['teacher_name']) ?></strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">Hari / Tanggal</span>
                    <strong class="text-dark"><?= date('l, d F Y', strtotime($session['attendance_date'])) ?></strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">Pertemuan Ke-</span>
                    <strong class="text-dark">Ke-<?= esc($session['meeting_number']) ?></strong>
                </div>
                <div class="col-md-6">
                    <span class="text-muted small d-block">Topik / Pokok Bahasan</span>
                    <strong class="text-dark"><?= esc($session['topic'] ?: '-') ?></strong>
                </div>
                <?php foreach (['learning_objectives'=>'Tujuan Pembelajaran','learning_activity'=>'Aktivitas Pembelajaran','assessment_summary'=>'Asesmen & Capaian','follow_up'=>'Tindak Lanjut','teaching_summary'=>'Catatan/Kendala'] as $field=>$label): if (!empty($session[$field])): ?>
                    <div class="col-12 border-top pt-2 mt-2">
                        <span class="text-muted small d-block"><?= $label ?></span>
                        <p class="text-dark mb-0"><?= nl2br(esc($session[$field])) ?></p>
                    </div>
                <?php endif; endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Roster Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="fw-bold mb-0 text-dark"><i data-lucide="users" class="me-2 text-primary"></i>Daftar Kehadiran Siswa (Total: <?= count($roster) ?> Siswa)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-muted small text-uppercase">
                            <th class="ps-4">No</th>
                            <th>NIS / NISN</th>
                            <th>Nama Peserta Didik</th>
                            <th class="text-center">Status Kehadiran</th>
                            <th class="pe-4">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($roster as $st): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-muted"><?= $no++ ?></td>
                                <td><span class="badge bg-slate-100 text-dark"><?= esc($st['student_number']) ?></span></td>
                                <td class="fw-bold text-dark"><?= esc($st['full_name']) ?></td>
                                <td class="text-center">
                                    <?php
                                    $stCode = strtoupper((string)$st['status']);
                                    if ($stCode === 'HADIR') echo '<span class="badge bg-success-subtle text-success px-3 py-1.5 fw-bold">HADIR</span>';
                                    elseif ($stCode === 'IZIN') echo '<span class="badge bg-warning-subtle text-warning-emphasis px-3 py-1.5 fw-bold">IZIN</span>';
                                    elseif ($stCode === 'SAKIT') echo '<span class="badge bg-info-subtle text-info px-3 py-1.5 fw-bold">SAKIT</span>';
                                    elseif ($stCode === 'ALPA') echo '<span class="badge bg-danger-subtle text-danger px-3 py-1.5 fw-bold">ALPA</span>';
                                    elseif ($stCode === 'TERLAMBAT') echo '<span class="badge bg-warning-subtle text-warning-emphasis px-3 py-1.5 fw-bold">TERLAMBAT</span>';
                                    elseif ($stCode === 'DISPENSASI') echo '<span class="badge bg-secondary-subtle text-secondary px-3 py-1.5 fw-bold">DISPENSASI</span>';
                                    ?>
                                </td>
                                <td class="pe-4"><?= esc($st['notes'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
