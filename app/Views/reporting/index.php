<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-purple-subtle text-purple px-2.5 py-1.5 rounded-pill fw-semibold text-xs text-uppercase tracking-wider">
                    <i data-lucide="file-bar-chart" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Phase 9 Master Reporting
                </span>
                <span class="badge bg-success-subtle text-success px-2.5 py-1.5 rounded-pill text-xs fw-semibold">
                    <i data-lucide="sparkles" class="w-3 h-3 me-1 d-inline-block"></i> Terpadu (Fase 6, 7 & 8)
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mt-2 mb-1">Rapor & Pelaporan Terpadu</h1>
            <p class="text-muted mb-0">Pengolahan capaian belajar intrakurikuler, kokurikuler P5, ekstrakurikuler, dan penerbitan buku rapor resmi.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-primary shadow-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#bulkGenerateModal">
                <i data-lucide="zap" class="w-4 h-4 me-1"></i> Generate Rapor Sekelas
            </button>
            <a href="<?= base_url('reporting/class-dashboard') ?>" class="btn btn-outline-primary shadow-sm rounded-pill px-3">
                <i data-lucide="bar-chart-3" class="w-4 h-4 me-1"></i> Dashboard Kelas
            </a>
            <a href="<?= base_url('reporting/promotion') ?>" class="btn btn-outline-secondary shadow-sm rounded-pill px-3">
                <i data-lucide="trending-up" class="w-4 h-4 me-1"></i> Kenaikan Kelas
            </a>
        </div>
    </div>

    <!-- Top KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-2">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100">
                <div class="text-xs text-muted text-uppercase fw-semibold mb-1">Total Siswa</div>
                <div class="h3 fw-bold text-gray-900 mb-0"><?= $stats['total_students'] ?? 0 ?></div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100">
                <div class="text-xs text-muted text-uppercase fw-semibold mb-1">Draf Rapor</div>
                <div class="h3 fw-bold text-secondary mb-0"><?= $stats['draft_count'] ?? 0 ?></div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100">
                <div class="text-xs text-muted text-uppercase fw-semibold mb-1">Terkunci (Review)</div>
                <div class="h3 fw-bold text-warning mb-0"><?= $stats['locked_count'] ?? 0 ?></div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100">
                <div class="text-xs text-muted text-uppercase fw-semibold mb-1">Diterbitkan</div>
                <div class="h3 fw-bold text-success mb-0"><?= $stats['published_count'] ?? 0 ?></div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100">
                <div class="text-xs text-muted text-uppercase fw-semibold mb-1">Rata-rata Nilai</div>
                <div class="h3 fw-bold text-primary mb-0"><?= $stats['overall_avg'] ?? 0 ?></div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100">
                <div class="text-xs text-muted text-uppercase fw-semibold mb-1">Kesiapan Rapor</div>
                <div class="h3 fw-bold text-info mb-0"><?= $stats['readiness_pct'] ?? 0 ?>%</div>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= base_url('reporting') ?>" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Rombongan Belajar</label>
                    <select name="classroom_id" class="form-select form-select-sm shadow-sm" onchange="this.form.submit()">
                        <option value="0">Semua Rombel</option>
                        <?php foreach ($classrooms as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= (int) ($selectedClassroom ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Status Rapor</label>
                    <select name="status" class="form-select form-select-sm shadow-sm" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Cari Siswa</label>
                    <input type="text" id="reportSearchInput" class="form-control form-control-sm shadow-sm" placeholder="Ketik nama siswa...">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <a href="<?= base_url('reporting') ?>" class="btn btn-sm btn-outline-secondary shadow-sm w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Snapshots Table -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <?php if (empty($snapshots)): ?>
                <div class="text-center py-5">
                    <i data-lucide="file-bar-chart" class="text-muted mb-3" style="width:48px;height:48px"></i>
                    <p class="text-muted mb-1 fw-bold">Belum ada rapor yang di-generate untuk filter ini.</p>
                    <p class="text-muted small">Klik tombol <strong>"Generate Rapor Sekelas"</strong> di atas untuk memproses rapor seluruh siswa sekaligus.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="reportSnapshotTable">
                        <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                            <tr>
                                <th class="ps-3 py-3">Nama Peserta Didik</th>
                                <th class="py-3">Rombel</th>
                                <th class="text-center py-3">Tipe</th>
                                <th class="text-center py-3">Status</th>
                                <th class="text-center py-3">Mapel Dinilai</th>
                                <th class="text-end pe-3 py-3">Aksi Terpadu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($snapshots as $s): ?>
                                <?php
                                $subjectCount = \Config\Database::connect()->table('report_subject_results')
                                    ->where('snapshot_id', $s['id'])
                                    ->countAllResults();
                                ?>
                                <tr class="report-snapshot-row">
                                    <td class="ps-3 py-3">
                                        <a href="<?= base_url('reporting/' . $s['id']) ?>" class="fw-bold text-gray-900 text-decoration-none snapshot-student-name">
                                            <?= esc($s['student_name'] ?? '—') ?>
                                        </a>
                                        <div class="text-xs text-muted">ID: <?= $s['student_id'] ?></div>
                                    </td>
                                    <td class="py-3 small fw-semibold"><?= esc($s['classroom_name'] ?? '—') ?></td>
                                    <td class="text-center py-3"><span class="badge bg-light text-dark border px-2 py-1 text-xs"><?= $s['snapshot_type'] ?></span></td>
                                    <td class="text-center py-3">
                                        <?php $sc = match($s['status']) { 'PUBLISHED' => 'success', 'LOCKED' => 'warning', default => 'secondary' }; ?>
                                        <span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?> rounded-pill px-2.5 py-1 text-xs"><?= $s['status'] ?></span>
                                    </td>
                                    <td class="text-center py-3 fw-bold text-primary"><?= $subjectCount ?> Mapel</td>
                                    <td class="text-end pe-3 py-3">
                                        <div class="d-inline-flex gap-1">
                                            <a href="<?= base_url('reporting/' . $s['id'] . '/print') ?>" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-2.5" title="Cetak Dokumen Rapor">
                                                <i data-lucide="printer" class="w-3.5 h-3.5 d-inline-block"></i> Cetak
                                            </a>
                                            <a href="<?= base_url('reporting/' . $s['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                Detail
                                            </a>
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
</div>

<!-- Bulk Generate Modal -->
<div class="modal fade" id="bulkGenerateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" action="<?= base_url('reporting/bulk-generate') ?>">
                <?= csrf_field() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-gray-900"><i data-lucide="zap" class="w-5 h-5 me-1 text-primary"></i> Generate Rapor Sekelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Fitur ini akan secara otomatis menarik data asesmen intrakurikuler, kokurikuler P5, ekstrakurikuler, dan presensi untuk seluruh peserta didik dalam rombel yang dipilih.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih Rombongan Belajar <span class="text-danger">*</span></label>
                        <select name="classroom_id" class="form-select" required>
                            <option value="">— Pilih Rombel —</option>
                            <?php foreach ($classrooms as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"><?= esc($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Generate Rapor Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const searchInput = document.getElementById('reportSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.report-snapshot-row');
            rows.forEach(row => {
                const name = row.querySelector('.snapshot-student-name')?.textContent.toLowerCase() || '';
                row.style.display = name.includes(query) ? '' : 'none';
            });
        });
    }
});
</script>
<?= $this->endSection() ?>
