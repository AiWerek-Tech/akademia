<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<?php
$trends = $attStats['session_trends'] ?? [];
$trendDates = array_column($trends, 'date');
$trendPercentages = array_column($trends, 'percentage');
?>
<div class="container-fluid px-0 px-md-3" style="max-width:1150px">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= base_url('extracurricular/' . $program['id']) ?>"><?= esc($program['title']) ?></a></li>
                    <li class="breadcrumb-item active">Sesi & Kehadiran</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-gray-900 mb-0">Jadwal Sesi & Rekap Presensi</h1>
            <p class="text-muted mb-0">Kelola jadwal pertemuan, topik materi, dan pemantauan presensi latihan rutin.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary shadow-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addSessionModal">
                <i data-lucide="plus" class="w-4 h-4 me-1 d-inline-block"></i> Tambah Sesi Latihan
            </button>
            <a href="<?= base_url('extracurricular/' . $program['id']) ?>" class="btn btn-outline-secondary shadow-sm rounded-pill px-3">
                <i data-lucide="arrow-left" class="w-4 h-4 me-1"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Attendance Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100">
                <div class="text-xs text-muted text-uppercase tracking-wider fw-semibold mb-1">Total Sesi Selesai</div>
                <div class="h3 fw-bold text-primary mb-0"><?= $attendance['total_sessions'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100">
                <div class="text-xs text-muted text-uppercase tracking-wider fw-semibold mb-1">Anggota Aktif</div>
                <div class="h3 fw-bold text-info mb-0"><?= $attendance['total_members'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100">
                <div class="text-xs text-muted text-uppercase tracking-wider fw-semibold mb-1">Total Kehadiran</div>
                <div class="h3 fw-bold text-success mb-0"><?= $attendance['present'] ?> Presensi</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100">
                <div class="text-xs text-muted text-uppercase tracking-wider fw-semibold mb-1">Tingkat Presensi</div>
                <div class="h3 fw-bold <?= $attendance['attendance_rate'] >= 75 ? 'text-success' : 'text-danger' ?> mb-0"><?= $attendance['attendance_rate'] ?>%</div>
            </div>
        </div>
    </div>

    <!-- ApexCharts Attendance Trend Chart -->
    <?php if (count($trends) > 1): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold mb-0 text-gray-900">
                        <i data-lucide="trending-up" class="w-4 h-4 me-1 text-purple"></i> Tren Kehadiran Latihan (%)
                    </h6>
                    <span class="badge bg-purple-subtle text-purple text-xs">ApexCharts Timeline</span>
                </div>
                <div id="sessionAttendanceTrendChart" style="min-height: 220px;"></div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Sessions List -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 p-4 pb-2">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <h5 class="fw-bold mb-0 text-gray-900">Daftar Sesi Latihan</h5>
                <input type="text" id="filterSessionInput" class="form-control form-control-sm shadow-sm" placeholder="Cari topik / lokasi..." style="max-width: 220px;">
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($sessions)): ?>
                <div class="text-center py-5"><p class="text-muted mb-0">Belum ada sesi latihan terjadwal.</p></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="sessionsTable">
                        <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                            <tr>
                                <th class="ps-3 py-3" style="width: 40px;">#</th>
                                <th class="py-3">Tanggal</th>
                                <th class="py-3">Waktu</th>
                                <th class="py-3">Topik Latihan</th>
                                <th class="py-3">Lokasi</th>
                                <th class="text-center py-3">Status</th>
                                <th class="text-end pe-3 py-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $i => $s): ?>
                                <tr class="session-table-row">
                                    <td class="ps-3 py-3 text-muted small"><?= $i + 1 ?></td>
                                    <td class="py-3 fw-bold text-gray-900 session-date"><?= date('d M Y', strtotime($s['session_date'])) ?></td>
                                    <td class="py-3 small text-muted"><?= esc($s['start_time'] ?? '—') ?><?= $s['end_time'] ? ' — ' . esc($s['end_time']) : '' ?></td>
                                    <td class="py-3 session-topic">
                                        <div class="fw-semibold text-gray-900 small"><?= esc($s['topic'] ?? 'Latihan Rutin') ?></div>
                                        <?php if (! empty($s['notes'])): ?><div class="text-xs text-muted"><?= esc(mb_strimwidth($s['notes'], 0, 30, '…')) ?></div><?php endif; ?>
                                    </td>
                                    <td class="py-3 small session-location">
                                        <i data-lucide="map-pin" class="w-3 h-3 me-1 text-secondary d-inline-block"></i>
                                        <?= esc($s['location'] ?? '—') ?>
                                    </td>
                                    <td class="text-center py-3">
                                        <?php $sc = match($s['status']) { 'COMPLETED' => 'success', 'PLANNED' => 'info', 'CANCELLED' => 'danger', default => 'secondary' }; ?>
                                        <span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?> rounded-pill px-2.5 py-1 text-xs"><?= $s['status'] ?></span>
                                    </td>
                                    <td class="text-end pe-3 py-3">
                                        <div class="d-flex gap-1 justify-content-end">
                                            <a href="<?= base_url('extracurricular/session/' . $s['id'] . '/attendance') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm">
                                                <i data-lucide="check-square" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Presensi
                                            </a>
                                            <form method="POST" action="<?= base_url('extracurricular/session/' . $s['id'] . '/delete') ?>" onsubmit="return confirm('Hapus sesi ini beserta data kehadirannya?')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill shadow-sm" title="Hapus"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Session Modal -->
    <div class="modal fade" id="addSessionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <form method="POST" action="<?= base_url('extracurricular/' . $program['id'] . '/sessions/add') ?>">
                    <?= csrf_field() ?>
                    <div class="modal-header border-bottom px-4 py-3">
                        <h5 class="modal-title fw-bold">Tambah Sesi Latihan Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-xs text-muted fw-semibold mb-1">Tanggal Sesi <span class="text-danger">*</span></label>
                            <input type="date" name="session_date" class="form-control form-control-sm shadow-sm" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label text-xs text-muted fw-semibold mb-1">Jam Mulai</label>
                                <input type="time" name="start_time" class="form-control form-control-sm shadow-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-xs text-muted fw-semibold mb-1">Jam Selesai</label>
                                <input type="time" name="end_time" class="form-control form-control-sm shadow-sm">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-xs text-muted fw-semibold mb-1">Topik / Agenda Latihan</label>
                            <input type="text" name="topic" class="form-control form-control-sm shadow-sm" placeholder="cth. Latihan Teknik Dasar & Taktik Pertandingan">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-xs text-muted fw-semibold mb-1">Lokasi Latihan</label>
                            <input type="text" name="location" class="form-control form-control-sm shadow-sm" value="<?= esc($program['location'] ?? '') ?>">
                        </div>
                        <div>
                            <label class="form-label text-xs text-muted fw-semibold mb-1">Catatan</label>
                            <textarea name="notes" class="form-control form-control-sm shadow-sm" rows="2" placeholder="Catatan instruktur/pembina..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">Buat Sesi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Attendance Trend Chart
    const trendDates = <?= json_encode($trendDates) ?>;
    const trendPercentages = <?= json_encode($trendPercentages) ?>;

    if (trendDates.length > 1 && document.getElementById('sessionAttendanceTrendChart')) {
        const options = {
            series: [{
                name: 'Presensi Hadir (%)',
                data: trendPercentages
            }],
            chart: {
                type: 'area',
                height: 200,
                toolbar: { show: false }
            },
            colors: ['#10b981'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 90, 100] }
            },
            xaxis: {
                categories: trendDates,
                labels: { style: { fontSize: '11px' } }
            },
            yaxis: {
                min: 0,
                max: 100,
                labels: { formatter: (val) => val + '%' }
            }
        };
        new ApexCharts(document.getElementById('sessionAttendanceTrendChart'), options).render();
    }

    // Search filter
    const search = document.getElementById('filterSessionInput');
    if (search) {
        search.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('.session-table-row').forEach(row => {
                const topic = row.querySelector('.session-topic')?.textContent.toLowerCase() || '';
                const loc = row.querySelector('.session-location')?.textContent.toLowerCase() || '';
                const date = row.querySelector('.session-date')?.textContent.toLowerCase() || '';
                row.style.display = (topic.includes(q) || loc.includes(q) || date.includes(q)) ? '' : 'none';
            });
        });
    }
});
</script>
<?= $this->endSection() ?>
