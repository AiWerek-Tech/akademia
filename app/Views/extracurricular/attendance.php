<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3" style="max-width:1100px">
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
                    <li class="breadcrumb-item"><a href="<?= base_url('extracurricular/' . $program['id'] . '/sessions') ?>">Sesi</a></li>
                    <li class="breadcrumb-item active">Kehadiran</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-gray-900 mb-0">Presensi Sesi Latihan</h1>
            <p class="text-muted mb-0">
                <i data-lucide="calendar" class="w-3.5 h-3.5 me-1 d-inline-block"></i><?= date('d M Y', strtotime($session['session_date'])) ?>
                <?php if ($session['start_time']): ?> · <?= $session['start_time'] ?><?= $session['end_time'] ? ' — ' . $session['end_time'] : '' ?><?php endif; ?>
                <?php if ($session['topic']): ?> · <strong><?= esc($session['topic']) ?></strong><?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('extracurricular/' . $program['id'] . '/sessions') ?>" class="btn btn-outline-secondary shadow-sm rounded-pill px-3">
                <i data-lucide="arrow-left" class="w-4 h-4 me-1"></i> Kembali ke Sesi
            </a>
        </div>
    </div>

    <form method="POST" action="<?= base_url('extracurricular/session/' . $session['id'] . '/attendance') ?>" id="attendanceForm">
        <?= csrf_field() ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <!-- Toolbar with Quick Set Buttons & Live Meter -->
            <div class="card-header bg-white border-0 p-4 pb-3">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <input type="text" id="filterStudentAttendance" class="form-control form-control-sm shadow-sm" placeholder="Cari nama anggota..." style="max-width: 220px;">
                    </div>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div class="d-flex align-items-center gap-2 text-xs text-muted">
                            <span>Tingkat Kehadiran Sesi:</span>
                            <div class="progress" style="width: 100px; height: 8px;">
                                <div id="liveAttendanceBar" class="progress-bar bg-success" style="width: 0%;"></div>
                            </div>
                            <span id="liveAttendanceText" class="fw-bold text-gray-900">0%</span>
                        </div>
                        <div class="d-flex gap-1.5">
                            <button type="button" class="btn btn-xs btn-outline-success shadow-sm py-1 px-2.5 text-xs rounded-pill" onclick="quickSetAttendance('PRESENT')">
                                <i data-lucide="check" class="w-3 h-3 me-1"></i> Setel Semua Hadir
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-info shadow-sm py-1 px-2.5 text-xs rounded-pill" onclick="quickSetAttendance('EXCUSED')">
                                Setel Semua Izin
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <?php if (empty($members)): ?>
                    <div class="text-center py-5"><p class="text-muted mb-0">Tidak ada anggota terdaftar dalam program ini.</p></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="attendanceTable">
                            <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                                <tr>
                                    <th class="ps-3 py-3" style="width:40px">#</th>
                                    <th class="py-3" style="min-width: 180px;">Nama Anggota</th>
                                    <th class="py-3">Kelas</th>
                                    <th class="text-center py-3" style="min-width:280px">Status Kehadiran</th>
                                    <th class="py-3" style="min-width: 200px;">Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $i => $m):
                                    // Find existing attendance record
                                    $existingStatus = 'PRESENT';
                                    $existingNotes = '';
                                    foreach ($records as $r) {
                                        if ((int) ($r['member_id'] ?? 0) === (int) $m['id']) {
                                            $existingStatus = $r['status'];
                                            $existingNotes = $r['notes'] ?? '';
                                            break;
                                        }
                                    }
                                ?>
                                    <tr class="attendance-row">
                                        <td class="ps-3 py-3 text-muted small"><?= $i + 1 ?></td>
                                        <td class="py-3">
                                            <div class="fw-bold text-gray-900 student-name"><?= esc($m['student_name'] ?? '—') ?></div>
                                            <div class="text-xs text-muted"><?= esc($m['role'] ?? 'MEMBER') ?></div>
                                        </td>
                                        <td class="py-3 small"><?= esc($m['classroom_name'] ?? '—') ?></td>
                                        <td class="text-center py-3">
                                            <input type="hidden" name="attendance[<?= $i ?>][member_id]" value="<?= $m['id'] ?>">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <?php foreach ($statuses as $st): ?>
                                                    <?php
                                                    $btnColor = match($st) {
                                                        'PRESENT' => 'btn-outline-success',
                                                        'LATE'    => 'btn-outline-warning',
                                                        'EXCUSED' => 'btn-outline-info',
                                                        default   => 'btn-outline-danger',
                                                    };
                                                    ?>
                                                    <input type="radio" class="btn-check att-radio" name="attendance[<?= $i ?>][status]" id="att_<?= $i ?>_<?= $st ?>" value="<?= $st ?>" <?= $existingStatus === $st ? 'checked' : '' ?> onchange="calculateLiveAttendance()">
                                                    <label class="btn <?= $btnColor ?> text-xs px-2.5 py-1" for="att_<?= $i ?>_<?= $st ?>">
                                                        <?= $st ?>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td class="py-3 pe-3">
                                            <input type="text" name="attendance[<?= $i ?>][notes]" class="form-control form-control-sm shadow-sm" value="<?= esc($existingNotes) ?>" placeholder="Catatan khusus...">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white border-top d-flex justify-content-end p-3">
                <button type="submit" class="btn btn-primary shadow-sm rounded-pill px-4">
                    <i data-lucide="save" class="w-4 h-4 me-1 d-inline-block"></i> Simpan Presensi Kehadiran
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function quickSetAttendance(targetStatus) {
    document.querySelectorAll('.attendance-row').forEach(row => {
        if (row.style.display !== 'none') {
            const radio = row.querySelector(`input[value="${targetStatus}"]`);
            if (radio) radio.checked = true;
        }
    });
    calculateLiveAttendance();
}

function calculateLiveAttendance() {
    const rows = document.querySelectorAll('.attendance-row');
    if (rows.length === 0) return;

    let present = 0;
    let total = 0;

    rows.forEach(row => {
        const checked = row.querySelector('.att-radio:checked');
        if (checked) {
            total++;
            if (checked.value === 'PRESENT' || checked.value === 'LATE') {
                present++;
            }
        }
    });

    const pct = total > 0 ? Math.round((present / total) * 100) : 0;
    const bar = document.getElementById('liveAttendanceBar');
    const txt = document.getElementById('liveAttendanceText');
    if (bar) bar.style.width = pct + '%';
    if (txt) txt.textContent = pct + '%';
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    calculateLiveAttendance();

    const search = document.getElementById('filterStudentAttendance');
    if (search) {
        search.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('.attendance-row').forEach(row => {
                const name = row.querySelector('.student-name')?.textContent.toLowerCase() || '';
                row.style.display = name.includes(q) ? '' : 'none';
            });
        });
    }
});
</script>
<?= $this->endSection() ?>
