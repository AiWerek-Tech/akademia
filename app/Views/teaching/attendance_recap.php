<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-purple-subtle text-purple px-2.5 py-1.5 rounded-pill fw-semibold text-xs text-uppercase tracking-wider">
                    <i data-lucide="bar-chart-3" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Rekap
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mt-2 mb-1">Rekapan Absensi</h1>
            <p class="text-muted mb-0">Lihat ringkasan kehadiran siswa per kelas dan mata pelajaran. Pilih kelas dan mapel di bawah untuk melihat rekapnya.</p>
        </div>
        <div>
            <a href="<?= base_url('teaching/attendance/history') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <i data-lucide="history" class="w-4 h-4 me-1 d-inline-block"></i> Riwayat
            </a>
        </div>
    </div>

    <!-- Filter -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= base_url('teaching/attendance/recap') ?>" class="row g-2 align-items-end">
                <div class="col-md-4 col-sm-6">
                    <label class="form-label text-xs fw-semibold text-muted">Kelas / Rombel</label>
                    <select name="classroom_id" class="form-select form-select-sm" required>
                        <option value="">— Pilih Kelas —</option>
                        <?php foreach ($myClassrooms as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= (int) $filters['classroom_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                                <?= esc($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 col-sm-6">
                    <label class="form-label text-xs fw-semibold text-muted">Mata Pelajaran</label>
                    <select name="subject_id" class="form-select form-select-sm" required>
                        <option value="">— Pilih Mapel —</option>
                        <?php foreach ($mySubjects as $s): ?>
                            <option value="<?= (int) $s['id'] ?>" <?= (int) $filters['subject_id'] === (int) $s['id'] ? 'selected' : '' ?>>
                                <?= esc($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 col-sm-6 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3">
                        <i data-lucide="eye" class="w-4 h-4 me-1 d-inline-block"></i> Tampilkan Rekap
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($recapData): ?>
        <!-- Recap Header -->
        <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="text-xs fw-semibold text-muted text-uppercase">Kelas</div>
                        <div class="fw-bold text-gray-900 text-sm"><?= esc($recapData['classroom']['name']) ?></div>
                        <div class="text-2xs text-muted"><?= esc($recapData['classroom']['unit_name'] ?? '') ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-xs fw-semibold text-muted text-uppercase">Mata Pelajaran</div>
                        <div class="fw-bold text-gray-900 text-sm"><?= esc($recapData['subject']['name']) ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-xs fw-semibold text-muted text-uppercase">Total Pertemuan</div>
                        <div class="fw-bold text-gray-900 text-sm"><?= $recapData['total_meetings'] ?> Pertemuan</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-xs fw-semibold text-muted text-uppercase">Total Siswa</div>
                        <div class="fw-bold text-gray-900 text-sm"><?= $recapData['total_students'] ?> Siswa</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Matrix -->
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex align-items-center justify-content-between">
                <h5 class="fw-bold text-gray-900 mb-0">Matriks Kehadiran Siswa</h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-success-subtle text-success text-2xs">H = Hadir</span>
                    <span class="badge bg-warning-subtle text-warning text-2xs">T = Terlambat</span>
                    <span class="badge bg-info-subtle text-info text-2xs">I = Izin</span>
                    <span class="badge bg-secondary-subtle text-dark text-2xs">S = Sakit</span>
                    <span class="badge bg-danger-subtle text-danger text-2xs">A = Alpa</span>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm text-center mb-0" style="font-size: 0.75rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start text-xs fw-semibold" style="min-width:150px;">Nama Siswa</th>
                                <?php foreach ($recapData['sessions'] as $sess): ?>
                                    <th class="text-center text-2xs fw-semibold" style="min-width:50px;" title="<?= esc($sess['topic'] ?? $sess['attendance_date']) ?>">
                                        #<?= (int) $sess['meeting_number'] ?>
                                        <br><span class="text-muted"><?= date('d/m', strtotime($sess['attendance_date'])) ?></span>
                                    </th>
                                <?php endforeach; ?>
                                <th class="text-center text-xs fw-semibold bg-light">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recapData['student_matrix'] as $row): ?>
                                <tr>
                                    <td class="text-start fw-semibold text-xs text-gray-900">
                                        <span class="text-muted text-2xs me-1"><?= esc($row['student_number']) ?></span>
                                        <?= esc($row['full_name']) ?>
                                    </td>
                                    <?php foreach ($recapData['sessions'] as $sess): ?>
                                        <?php
                                        $att = $row['meetings'][(int)$sess['id']] ?? null;
                                        $code = $att ? $att['status'] : '-';
                                        $cellClass = match($code) {
                                            'HADIR' => 'bg-success-subtle text-success fw-bold',
                                            'TERLAMBAT' => 'bg-warning-subtle text-warning fw-bold',
                                            'IZIN' => 'bg-info-subtle text-info',
                                            'SAKIT' => 'bg-secondary-subtle text-dark',
                                            'ALPA' => 'bg-danger-subtle text-danger fw-bold',
                                            default => 'text-muted',
                                        };
                                        $label = match($code) {
                                            'HADIR' => 'H',
                                            'TERLAMBAT' => 'T',
                                            'IZIN' => 'I',
                                            'SAKIT' => 'S',
                                            'ALPA' => 'A',
                                            default => '-',
                                        };
                                        ?>
                                        <td class="<?= $cellClass ?> text-center" title="<?= $att ? esc($att['notes'] ?? '') : '' ?>"><?= $label ?></td>
                                    <?php endforeach; ?>
                                    <td class="bg-light fw-bold text-xs <?= $row['rate'] >= 90 ? 'text-success' : ($row['rate'] >= 75 ? 'text-warning' : 'text-danger') ?>">
                                        <?= $row['rate'] ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Summary row -->
                <div class="mt-3 p-3 bg-light rounded-3">
                    <div class="d-flex flex-wrap gap-4 text-xs">
                        <div><span class="fw-bold text-gray-900">Total Pertemuan:</span> <?= $recapData['total_meetings'] ?></div>
                        <div><span class="fw-bold text-success">Rata-rata Kehadiran:</span>
                            <?php
                            $totalRate = 0;
                            $count = count($recapData['student_matrix']);
                            foreach ($recapData['student_matrix'] as $row) { $totalRate += $row['rate']; }
                            echo $count > 0 ? round($totalRate / $count, 1) : 0;
                            ?>%
                        </div>
                        <div><span class="fw-bold text-danger">Siswa Alpa ≥ 3x:</span>
                            <?php
                            $atRisk = 0;
                            foreach ($recapData['student_matrix'] as $row) { if ($row['alpa'] >= 3) $atRisk++; }
                            echo $atRisk;
                            ?> siswa
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($filters['classroom_id'] > 0 && $filters['subject_id'] > 0): ?>
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-body text-center py-5">
                <div class="p-4 bg-light rounded-circle d-inline-block text-muted mb-3">
                    <i data-lucide="search-x" class="w-8 h-8"></i>
                </div>
                <h6 class="fw-bold text-gray-800">Belum Ada Data Presensi</h6>
                <p class="text-muted text-sm mb-0">
                    Belum ada catatan presensi untuk kelas dan mata pelajaran yang dipilih.
                    Mulai dengan menginput presensi dari Teaching Mode atau form input offline.
                </p>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-body text-center py-5">
                <div class="p-4 bg-light rounded-circle d-inline-block text-muted mb-3">
                    <i data-lucide="list-checks" class="w-8 h-8"></i>
                </div>
                <h6 class="fw-bold text-gray-800">Pilih Kelas dan Mata Pelajaran</h6>
                <p class="text-muted text-sm mb-0">
                    Pilih kelas dan mata pelajaran dari filter di atas untuk melihat rekapan kehadiran siswa.
                </p>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
