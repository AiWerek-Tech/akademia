<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<?php $checkins = $data; ?>
<div class="container-fluid px-0 px-md-3">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-purple-subtle text-purple rounded-pill fw-semibold text-xs text-uppercase tracking-wider">
                    <i data-lucide="sparkles" class="w-3 h-3 me-1 d-inline-block"></i> Phase 7 · Gerakan 7KAIH
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mt-2 mb-1">Check-in Kebiasaan Mingguan (7KAIH)</h1>
            <p class="text-muted mb-0">Pemantauan pembiasaan karakter harian siswa: Terlaksana (DONE), Sebagian (PARTIAL), Lewat (SKIP).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('cocurricular/habits') ?>" class="btn btn-outline-secondary shadow-sm">
                <i data-lucide="settings" class="w-4 h-4 me-1"></i> Kelola Kebiasaan
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= base_url('cocurricular/checkins') ?>" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Pekan (Senin)</label>
                    <input type="date" name="week" value="<?= esc($week) ?>" class="form-control form-control-sm shadow-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Kelas</label>
                    <select name="classroom_id" class="form-select form-select-sm shadow-sm">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($classrooms as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (int) (request()->getGet('classroom_id') ?: 0) === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Kebiasaan</label>
                    <select name="habit_id" class="form-select form-select-sm shadow-sm">
                        <option value="">Semua Kebiasaan</option>
                        <?php foreach ($habits as $h): ?>
                            <option value="<?= $h['id'] ?>" <?= (int) (request()->getGet('habit_id') ?: 0) === (int) $h['id'] ? 'selected' : '' ?>><?= esc($h['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary shadow-sm w-100"><i data-lucide="filter" class="w-3.5 h-3.5 me-1"></i> Tampilkan</button>
                    <a href="<?= base_url('cocurricular/checkins') ?>" class="btn btn-sm btn-outline-secondary shadow-sm" title="Reset">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <form method="POST" action="<?= base_url('cocurricular/checkins/save') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="week" value="<?= esc($week) ?>">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <!-- Toolbar with Quick Actions & Search -->
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <input type="text" id="filterStudentCheckin" class="form-control form-control-sm shadow-sm" placeholder="Cari nama siswa..." style="max-width: 220px;">
                    </div>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <span class="text-xs text-muted fw-semibold">Quick-Checkin:</span>
                        <button type="button" class="btn btn-xs btn-outline-success shadow-sm py-1 px-2 text-xs" onclick="quickFillCheckin('DONE')">
                            <i data-lucide="check" class="w-3 h-3 me-1"></i> Setel Semua DONE
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-warning shadow-sm py-1 px-2 text-xs" onclick="quickFillCheckin('PARTIAL')">
                            Setel Semua PARTIAL
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="checkinsTable">
                        <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                            <tr>
                                <th class="px-3 py-3" style="min-width:180px">Siswa</th>
                                <?php foreach ($checkins['habits'] as $h): ?>
                                    <th class="px-2 py-3 text-center" style="min-width:140px" title="<?= esc($h['name']) ?>">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <i data-lucide="<?= esc($h['icon'] ?? 'smile') ?>" class="w-3.5 h-3.5 text-purple"></i>
                                            <span class="fw-bold text-gray-900"><?= esc(mb_strimwidth($h['name'], 0, 16, '…')) ?></span>
                                        </div>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($checkins['habits'] === []): ?>
                                <tr><td colspan="2" class="text-center text-muted py-5">Belum ada kebiasaan aktif. <a href="<?= base_url('cocurricular/habits') ?>">Kelola kebiasaan</a>.</td></tr>
                            <?php elseif ($checkins['students'] === []): ?>
                                <tr><td colspan="<?= count($checkins['habits']) + 1 ?>" class="text-center text-muted py-5">Tidak ada siswa pada filter ini.</td></tr>
                            <?php else: ?>
                                <?php foreach ($checkins['students'] as $st): ?>
                                    <tr class="student-checkin-row">
                                        <td class="px-3 py-3">
                                            <div class="fw-semibold small checkin-student-name"><?= esc($st['full_name']) ?></div>
                                            <div class="text-xs text-muted"><?= esc($st['classroom_name'] ?? '') ?> · NIS <?= esc($st['student_number'] ?? '') ?></div>
                                        </td>
                                        <?php foreach ($checkins['habits'] as $h): ?>
                                            <?php $row = $checkins['checkins'][$h['id'] . ':' . $st['id']] ?? null; ?>
                                            <td class="px-2 py-3 text-center">
                                                <select name="checkins[<?= (int) $st['id'] ?>][<?= (int) $h['id'] ?>][status]" class="form-select form-select-sm shadow-sm text-center checkin-select">
                                                    <option value="UNSET" <?= $row === null ? 'selected' : '' ?>>—</option>
                                                    <?php foreach ($statuses as $stt): ?>
                                                        <option value="<?= $stt ?>" <?= $row && $row['status'] === $stt ? 'selected' : '' ?>><?= esc($stt) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="text" name="checkins[<?= (int) $st['id'] ?>][<?= (int) $h['id'] ?>][note]" class="form-control form-control-sm shadow-sm mt-1" placeholder="Catatan" value="<?= esc($row['note'] ?? '') ?>">
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php if ($checkins['students'] !== [] && $checkins['habits'] !== []): ?>
        <div class="d-flex justify-content-end mt-3 mb-5">
            <button type="submit" class="btn btn-primary shadow-sm px-4"><i data-lucide="save" class="w-4 h-4 me-1"></i> Simpan Check-in Kebiasaan</button>
        </div>
        <?php endif; ?>
    </form>
</div>

<script>
function quickFillCheckin(status) {
    if (!confirm('Terapkan status ' + status + ' untuk semua siswa di halaman ini?')) return;
    document.querySelectorAll('.checkin-select').forEach(sel => {
        sel.value = status;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const searchInput = document.getElementById('filterStudentCheckin');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('.student-checkin-row').forEach(row => {
                const name = row.querySelector('.checkin-student-name')?.textContent.toLowerCase() || '';
                row.style.display = name.includes(q) ? '' : 'none';
            });
        });
    }
});
</script>
<?= $this->endSection() ?>