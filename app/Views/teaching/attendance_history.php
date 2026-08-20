<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-purple-subtle text-purple px-2.5 py-1.5 rounded-pill fw-semibold text-xs text-uppercase tracking-wider">
                    <i data-lucide="history" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Riwayat
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mt-2 mb-1">Riwayat Absensi</h1>
            <p class="text-muted mb-0">Lihat semua catatan presensi yang sudah dibuat. Gunakan filter untuk mencari berdasarkan tanggal atau kelas.</p>
        </div>
        <div>
            <a href="<?= base_url('teaching/attendance/offline') ?>" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold shadow-sm">
                <i data-lucide="plus-circle" class="w-4 h-4 me-1 d-inline-block"></i> Input Absensi Baru
            </a>
            <a href="<?= base_url('teaching/attendance/recap') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <i data-lucide="bar-chart-3" class="w-4 h-4 me-1 d-inline-block"></i> Rekapan
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= base_url('teaching/attendance/history') ?>" class="row g-2 align-items-end">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label text-xs fw-semibold text-muted">Dari Tanggal</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="<?= esc($filters['from']) ?>">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label text-xs fw-semibold text-muted">Sampai Tanggal</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="<?= esc($filters['to']) ?>">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label text-xs fw-semibold text-muted">Kelas</label>
                    <select name="classroom_id" class="form-select form-select-sm">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($myClassrooms as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= (int) $filters['classroom_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                                <?= esc($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3">
                        <i data-lucide="search" class="w-4 h-4 me-1 d-inline-block"></i> Cari
                    </button>
                    <a href="<?= base_url('teaching/attendance/history') ?>" class="btn btn-sm btn-outline-secondary rounded-pill">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Sessions List -->
    <div class="card border-0 shadow-sm rounded-4 bg-white">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold text-gray-900 mb-0">Catatan Presensi</h5>
            <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill text-xs">
                <?= count($sessions) ?> catatan
            </span>
        </div>
        <div class="card-body p-4">
            <?php if (empty($sessions)): ?>
                <div class="text-center py-5">
                    <div class="p-4 bg-light rounded-circle d-inline-block text-muted mb-3">
                        <i data-lucide="clipboard-list" class="w-8 h-8"></i>
                    </div>
                    <h6 class="fw-bold text-gray-800">Belum Ada Catatan Presensi</h6>
                    <p class="text-muted text-sm mb-3">
                        Belum ada data presensi yang cocok dengan filter yang dipilih.
                        Coba ubah rentang tanggal atau pilih kelas lain.
                    </p>
                    <a href="<?= base_url('teaching/attendance/offline') ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                        <i data-lucide="plus-circle" class="w-4 h-4 me-1 d-inline-block"></i> Input Absensi Sekarang
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-xs fw-semibold text-muted">Tanggal</th>
                                <th class="text-xs fw-semibold text-muted">Kelas</th>
                                <th class="text-xs fw-semibold text-muted">Mata Pelajaran</th>
                                <th class="text-xs fw-semibold text-muted">Guru</th>
                                <th class="text-xs fw-semibold text-muted">Pertemuan</th>
                                <th class="text-xs fw-semibold text-muted">Status</th>
                                <th class="text-xs fw-semibold text-muted text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $s): ?>
                                <?php
                                $statusBadge = match(strtoupper($s['status'] ?? '')) {
                                    'VERIFIED' => 'bg-success-subtle text-success',
                                    'SUBMITTED' => 'bg-primary-subtle text-primary',
                                    'DRAFT' => 'bg-warning-subtle text-warning',
                                    default => 'bg-light text-muted',
                                };
                                $typeLabels = [
                                    'SUBJECT' => 'Mapel',
                                    'MORNING_ASSEMBLY' => 'Apel Pagi',
                                    'AFTERNOON_ASSEMBLY' => 'Apel Siang',
                                    'CLASSROOM' => 'Kelas',
                                ];
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-xs text-gray-900"><?= date('d M Y', strtotime($s['attendance_date'])) ?></div>
                                        <div class="text-2xs text-muted"><?= date('l', strtotime($s['attendance_date'])) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-purple-subtle text-purple text-xs fw-semibold">
                                            <?= esc($s['classroom_name']) ?>
                                        </span>
                                    </td>
                                    <td class="text-xs text-gray-900"><?= esc($s['subject_name']) ?></td>
                                    <td class="text-xs text-muted"><?= esc($s['teacher_name'] ?: '-') ?></td>
                                    <td class="text-xs text-gray-900">#<?= (int) $s['meeting_number'] ?></td>
                                    <td>
                                        <span class="badge <?= $statusBadge ?> text-2xs rounded-pill">
                                            <?= strtoupper($s['status'] ?? 'DRAFT') ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <?php if (strtoupper($s['status'] ?? '') !== 'VERIFIED'): ?>
                                        <a href="<?= base_url('teaching/attendance/session/' . $s['id'] . '/edit') ?>" class="btn btn-xs btn-outline-primary rounded-pill me-1" title="Edit Absensi">
                                            <i data-lucide="edit-3" class="w-3 h-3"></i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="<?= base_url('portal/attendance/session/' . $s['id'] . '/print') ?>" target="_blank" class="btn btn-xs btn-outline-secondary rounded-pill" title="Cetak Jurnal">
                                            <i data-lucide="printer" class="w-3 h-3"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
