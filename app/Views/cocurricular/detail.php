<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<?php
$p = $data['program'];
$tab = (string) (request()->getGet('tab') ?: 'overview');
$canManage = $canManage ?? false;
$activeTab = static fn (string $key) => $tab === $key ? 'active' : '';
$ipoo = $ipooHealth ?? [
    'overall_score' => 3.5,
    'overall_percent' => 70,
    'status' => ['label' => 'BAIK', 'class' => 'bg-primary text-white'],
    'aspects' => []
];
?>
<div class="container-fluid px-0 px-md-3">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-purple-subtle text-purple rounded-pill fw-semibold text-xs text-uppercase tracking-wider">
                    <i data-lucide="sparkles" class="w-3 h-3 me-1 d-inline-block"></i> Phase 7 Kokurikuler & Karakter
                </span>
                <span class="badge bg-light text-dark border"><?= esc($p['program_type']) ?></span>
                <span class="badge <?= match ($p['status']) {
                    'DRAFT' => 'bg-warning-subtle text-warning',
                    'ACTIVE' => 'bg-success-subtle text-success',
                    'COMPLETED' => 'bg-primary-subtle text-primary',
                    default => 'bg-secondary-subtle text-secondary',
                } ?>"><?= esc($p['status']) ?></span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mt-2 mb-1"><?= esc($p['title']) ?></h1>
            <p class="text-muted mb-0"><?= esc($p['period_name'] ?? '') ?><?= $p['code'] ? ' · ' . esc($p['code']) : '' ?><?= $p['theme'] ? ' · ' . esc($p['theme']) : '' ?></p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-purple shadow-sm" data-bs-toggle="modal" data-bs-target="#rubricDescriptorModal">
                <i data-lucide="help-circle" class="w-4 h-4 me-1"></i> ✨ Pedoman Dimensi
            </button>
            <?php if ($canManage): ?>
                <?php if ($p['status'] === 'DRAFT'): ?>
                    <form method="POST" action="<?= base_url('cocurricular/' . (int) $p['id'] . '/transition') ?>"><?= csrf_field() ?><input type="hidden" name="status" value="ACTIVE"><button class="btn btn-success shadow-sm"><i data-lucide="play" class="w-4 h-4 me-1"></i> Aktifkan</button></form>
                    <a href="<?= base_url('cocurricular/' . (int) $p['id'] . '/edit') ?>" class="btn btn-outline-primary shadow-sm"><i data-lucide="edit-3" class="w-4 h-4 me-1"></i> Ubah Desain</a>
                    <form method="POST" action="<?= base_url('cocurricular/' . (int) $p['id'] . '/delete') ?>" onsubmit="return confirm('Hapus program ini?')"><?= csrf_field() ?><button class="btn btn-outline-danger shadow-sm"><i data-lucide="trash-2" class="w-4 h-4 me-1"></i> Hapus</button></form>
                <?php elseif ($p['status'] === 'ACTIVE'): ?>
                    <form method="POST" action="<?= base_url('cocurricular/' . (int) $p['id'] . '/transition') ?>"><?= csrf_field() ?><input type="hidden" name="status" value="COMPLETED"><button class="btn btn-primary shadow-sm"><i data-lucide="check-circle" class="w-4 h-4 me-1"></i> Selesaikan Program</button></form>
                <?php endif; ?>
            <?php endif; ?>
            <a href="<?= base_url('cocurricular/' . (int) $p['id'] . '/report') ?>" class="btn btn-outline-dark shadow-sm"><i data-lucide="file-bar-chart" class="w-4 h-4 me-1"></i> Laporan</a>
            <a href="<?= base_url('cocurricular') ?>" class="btn btn-outline-secondary shadow-sm"><i data-lucide="arrow-left" class="w-4 h-4 me-1"></i> Kembali</a>
        </div>
    </div>

    <ul class="nav nav-pills nav-pills-academia mb-4 gap-1" role="tablist">
        <?php $tabs = [
            'overview' => ['eye', 'Desain'],
            'schedule' => ['calendar-days', 'Jadwal & Eksekusi'],
            'monitoring' => ['activity', 'Monitoring Formatif'],
            'evidence' => ['folder-check', 'Bukti Sumatif'],
            'results' => ['grid-3x3', 'Hasil Dimensi'],
            'evaluation' => ['clipboard-list', 'Evaluasi (IPOO)'],
        ]; ?>
        <?php foreach ($tabs as $key => [$icon, $label]): ?>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $activeTab($key) ?>" href="<?= base_url('cocurricular/' . (int) $p['id'] . '?tab=' . $key) ?>"><i data-lucide="<?= $icon ?>" class="w-4 h-4 me-1"></i><?= $label ?></a>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- TAB 1: OVERVIEW -->
    <?php if ($tab === 'overview'): ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3 text-gray-900"><i data-lucide="info" class="w-4 h-4 me-1 text-purple"></i> Rasional & Tujuan</h6>
                        <p class="mb-3"><strong>Alasan / Kebutuhan:</strong><br><?= nl2br(esc($p['rationale'] ?? '-')) ?></p>
                        <p class="mb-3"><strong>Tujuan:</strong><br><?= nl2br(esc($p['objective'] ?? '-')) ?></p>
                        <p class="mb-0"><strong>Deskripsi:</strong><br><?= nl2br(esc($p['description'] ?? '-')) ?></p>
                    </div>
                </div>
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3 text-gray-900"><i data-lucide="table-2" class="w-4 h-4 me-1 text-purple"></i> Pemetaan Lintas Disiplin</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-xs text-muted fw-semibold">Dimensi Profil Lulusan</label>
                                <ul class="list-unstyled small mb-0">
                                    <?php foreach ($data['dimensions'] as $d): ?>
                                        <li class="mb-1"><i data-lucide="badge-check" class="w-3.5 h-3.5 me-1 text-success"></i><strong><?= esc($d['code'] ?? '') ?></strong> — <?= esc($d['name']) ?></li>
                                    <?php endforeach; ?>
                                    <?php if ($data['dimensions'] === []): ?><li class="text-muted">Belum ada dimensi.</li><?php endif; ?>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-xs text-muted fw-semibold">Mata Pelajaran & TP</label>
                                <ul class="list-unstyled small mb-0">
                                    <?php foreach ($data['subjects'] as $s): ?>
                                        <li class="mb-1"><i data-lucide="book-open" class="w-3.5 h-3.5 me-1 text-primary"></i><?= esc($s['code'] ?? '') ?> — <?= esc($s['name']) ?></li>
                                    <?php endforeach; ?>
                                    <?php foreach ($data['objectives'] as $o): ?>
                                        <li class="mb-1 text-muted"><i data-lucide="target" class="w-3.5 h-3.5 me-1 text-warning"></i><?= esc($o['code'] ?? '') ?> — <?= esc($o['description']) ?></li>
                                    <?php endforeach; ?>
                                    <?php if ($data['subjects'] === [] && $data['objectives'] === []): ?><li class="text-muted">Belum ada mapel/TP.</li><?php endif; ?>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-xs text-muted fw-semibold">Guru Fasilitator</label>
                                <ul class="list-unstyled small mb-0">
                                    <?php foreach ($data['teachers'] as $t): ?>
                                        <li class="mb-1"><i data-lucide="user" class="w-3.5 h-3.5 me-1 text-secondary"></i><?= esc($t['full_name']) ?><?= $t['role'] ? ' <span class="badge bg-light border">' . esc($t['role']) . '</span>' : '' ?></li>
                                    <?php endforeach; ?>
                                    <?php if ($data['teachers'] === []): ?><li class="text-muted">Belum ada guru.</li><?php endif; ?>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-xs text-muted fw-semibold">Kelas / Rombel</label>
                                <ul class="list-unstyled small mb-0">
                                    <?php foreach ($data['classes'] as $c): ?>
                                        <li class="mb-1"><i data-lucide="school" class="w-3.5 h-3.5 me-1 text-secondary"></i><?= esc($c['classroom_name']) ?></li>
                                    <?php endforeach; ?>
                                    <?php if ($data['classes'] === []): ?><li class="text-muted">Belum ada kelas.</li><?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3 text-gray-900"><i data-lucide="settings-2" class="w-4 h-4 me-1 text-purple"></i> Ringkasan Eksekusi</h6>
                        <dl class="row small mb-0">
                            <dt class="col-5 text-muted fw-normal">Model</dt><dd class="col-7 fw-semibold"><?= esc($p['delivery_model']) ?></dd>
                            <dt class="col-5 text-muted fw-normal">Alokasi</dt><dd class="col-7"><?= $p['annual_minutes'] ? (int) $p['annual_minutes'] . ' menit/tahun' : '-' ?></dd>
                            <dt class="col-5 text-muted fw-normal">Mulai</dt><dd class="col-7"><?= esc($p['start_date'] ?? '-') ?></dd>
                            <dt class="col-5 text-muted fw-normal">Selesai</dt><dd class="col-7"><?= esc($p['end_date'] ?? '-') ?></dd>
                            <dt class="col-5 text-muted fw-normal">Total Sesi</dt><dd class="col-7"><?= count($sessions) ?></dd>
                            <dt class="col-5 text-muted fw-normal">Observasi</dt><dd class="col-7"><?= count($data['observations']) ?></dd>
                            <dt class="col-5 text-muted fw-normal">Bukti</dt><dd class="col-7"><?= count($data['evidences']) ?></dd>
                        </dl>
                    </div>
                </div>
                <?php if ($data['partners'] !== [] || $data['resources'] !== []): ?>
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3 text-gray-900"><i data-lucide="handshake" class="w-4 h-4 me-1 text-purple"></i> Mitra & Sumber Daya</h6>
                            <?php foreach ($data['partners'] as $partner): ?>
                                <div class="small mb-2"><i data-lucide="users" class="w-3.5 h-3.5 me-1 text-secondary"></i><strong><?= esc($partner['name']) ?></strong><?= $partner['role'] ? ' — ' . esc($partner['role']) : '' ?><?= $partner['partner_type'] ? ' <span class="badge bg-light border">' . esc($partner['partner_type']) . '</span>' : '' ?></div>
                            <?php endforeach; ?>
                            <?php foreach ($data['resources'] as $r): ?>
                                <div class="small mb-2"><i data-lucide="box" class="w-3.5 h-3.5 me-1 text-secondary"></i><?= esc($r['name']) ?><?= $r['quantity'] ? ' × ' . (int) $r['quantity'] : '' ?><?= $r['resource_type'] ? ' <span class="badge bg-light border">' . esc($r['resource_type']) . '</span>' : '' ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <!-- TAB 2: SCHEDULE -->
    <?php elseif ($tab === 'schedule'): ?>
        <div class="row g-4">
            <?php if ($canManage): ?>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3 text-gray-900"><i data-lucide="calendar-plus" class="w-4 h-4 me-1 text-purple"></i> Tambah Sesi</h6>
                        <form method="POST" action="<?= base_url('cocurricular/' . (int) $p['id'] . '/sessions') ?>">
                            <?= csrf_field() ?>
                            <div class="mb-2"><label class="form-label text-xs text-muted fw-semibold mb-1">Judul Sesi</label><input name="title" class="form-control form-control-sm shadow-sm" required placeholder="cth. Kick-off & pembagian kelompok"></div>
                            <div class="mb-2"><label class="form-label text-xs text-muted fw-semibold mb-1">Tanggal</label><input type="date" name="session_date" class="form-control form-control-sm shadow-sm" required></div>
                            <div class="row g-2 mb-2">
                                <div class="col-6"><label class="form-label text-xs text-muted fw-semibold mb-1">Mulai</label><input type="time" name="start_time" class="form-control form-control-sm shadow-sm"></div>
                                <div class="col-6"><label class="form-label text-xs text-muted fw-semibold mb-1">Selesai</label><input type="time" name="end_time" class="form-control form-control-sm shadow-sm"></div>
                            </div>
                            <div class="mb-2"><label class="form-label text-xs text-muted fw-semibold mb-1">Moda</label><select name="mode" class="form-select form-select-sm shadow-sm"><?php foreach ($sessionModes as $sm): ?><option value="<?= $sm ?>"><?= esc($sm) ?></option><?php endforeach; ?></select></div>
                            <div class="mb-2"><label class="form-label text-xs text-muted fw-semibold mb-1">Kelas</label><select name="classroom_id" class="form-select form-select-sm shadow-sm"><option value="">—</option><?php foreach ($data['classes'] as $c): ?><option value="<?= $c['classroom_id'] ?>"><?= esc($c['classroom_name']) ?></option><?php endforeach; ?></select></div>
                            <div class="mb-2"><label class="form-label text-xs text-muted fw-semibold mb-1">Guru</label><select name="teacher_id" class="form-select form-select-sm shadow-sm"><option value="">—</option><?php foreach ($teachers as $t): ?><option value="<?= $t['id'] ?>"><?= esc($t['full_name']) ?></option><?php endforeach; ?></select></div>
                            <div class="mb-3"><label class="form-label text-xs text-muted fw-semibold mb-1">Catatan</label><textarea name="notes" rows="2" class="form-control form-control-sm shadow-sm"></textarea></div>
                            <button class="btn btn-primary btn-sm shadow-sm w-100"><i data-lucide="plus" class="w-3 h-3 me-1"></i> Tambah Sesi</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="<?= $canManage ? 'col-lg-8' : 'col-12' ?>">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                                <tr>
                                    <th class="px-3 py-3">Sesi</th>
                                    <th class="px-3 py-3">Tanggal</th>
                                    <th class="px-3 py-3">Waktu</th>
                                    <th class="px-3 py-3">Kelas</th>
                                    <th class="px-3 py-3">Guru</th>
                                    <th class="px-3 py-3">Status</th>
                                    <th class="px-3 py-3 text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($sessions === []): ?>
                                    <tr><td colspan="7" class="text-center text-muted py-5">Belum ada sesi terjadwal.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($sessions as $s): ?>
                                        <tr>
                                            <td class="px-3 py-3"><div class="fw-semibold small"><?= esc($s['title']) ?></div><div class="text-xs text-muted"><?= esc($s['mode']) ?></div></td>
                                            <td class="px-3 py-3"><?= esc($s['session_date']) ?></td>
                                            <td class="px-3 py-3 small"><?= esc($s['start_time'] ?? '-') ?><?= $s['end_time'] ? ' – ' . esc($s['end_time']) : '' ?></td>
                                            <td class="px-3 py-3"><?= esc($s['classroom_name'] ?? '-') ?></td>
                                            <td class="px-3 py-3"><?= esc($s['teacher_name'] ?? '-') ?></td>
                                            <td class="px-3 py-3"><span class="badge <?= $s['status'] === 'EXECUTED' ? 'bg-success-subtle text-success' : ($s['status'] === 'CANCELLED' ? 'bg-secondary-subtle text-secondary' : 'bg-warning-subtle text-warning') ?>"><?= esc($s['status']) ?></span></td>
                                            <td class="px-3 py-3 text-end">
                                                <?php if ($canManage): ?>
                                                    <?php if ($s['status'] === 'PLAN'): ?>
                                                        <form class="d-inline" method="POST" action="<?= base_url('cocurricular/' . (int) $p['id'] . '/sessions/' . (int) $s['id'] . '/execute') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-success shadow-sm" title="Eksekusi"><i data-lucide="check" class="w-3 h-3"></i></button></form>
                                                        <form class="d-inline" method="POST" action="<?= base_url('cocurricular/' . (int) $p['id'] . '/sessions/' . (int) $s['id'] . '/cancel') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary shadow-sm" title="Batalkan"><i data-lucide="x" class="w-3 h-3"></i></button></form>
                                                    <?php endif; ?>
                                                    <form class="d-inline" method="POST" action="<?= base_url('cocurricular/' . (int) $p['id'] . '/sessions/' . (int) $s['id'] . '/delete') ?>" onsubmit="return confirm('Hapus sesi ini?')"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger shadow-sm" title="Hapus"><i data-lucide="trash-2" class="w-3 h-3"></i></button></form>
                                                <?php endif; ?>
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

    <!-- TAB 3: MONITORING FORMATIF -->
    <?php elseif ($tab === 'monitoring'): ?>
        <div class="row g-4">
            <?php if ($canManage): ?>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3 text-gray-900"><i data-lucide="edit-3" class="w-4 h-4 me-1 text-purple"></i> Catatan Formatif</h6>
                        <form method="POST" action="<?= base_url('cocurricular/' . (int) $p['id'] . '/observations') ?>">
                            <?= csrf_field() ?>
                            <div class="mb-2"><label class="form-label text-xs text-muted fw-semibold mb-1">Jenis</label><select name="observation_type" class="form-select form-select-sm shadow-sm"><?php foreach ($observationTypes as $ot): ?><option value="<?= $ot ?>"><?= esc($ot) ?></option><?php endforeach; ?></select></div>
                            <div class="mb-2"><label class="form-label text-xs text-muted fw-semibold mb-1">Siswa</label><select name="student_id" class="form-select form-select-sm shadow-sm" required><option value="">Pilih siswa</option><?php foreach ($students as $st): ?><option value="<?= $st['id'] ?>"><?= esc($st['full_name']) ?></option><?php endforeach; ?></select></div>
                            <div class="mb-2"><label class="form-label text-xs text-muted fw-semibold mb-1">Dimensi</label><select name="dimension_id" class="form-select form-select-sm shadow-sm"><option value="">—</option><?php foreach ($data['dimensions'] as $d): ?><option value="<?= $d['dimension_id'] ?>"><?= esc($d['code'] ?? $d['name']) ?></option><?php endforeach; ?></select></div>
                            <div class="mb-2"><label class="form-label text-xs text-muted fw-semibold mb-1">Sesi</label><select name="session_id" class="form-select form-select-sm shadow-sm"><option value="">—</option><?php foreach ($sessions as $s): ?><option value="<?= $s['id'] ?>"><?= esc($s['title']) ?> (<?= esc($s['session_date']) ?>)</option><?php endforeach; ?></select></div>
                            <div class="mb-2"><label class="form-label text-xs text-muted fw-semibold mb-1">Rating (1–5)</label><input type="number" name="rating" min="1" max="5" class="form-control form-control-sm shadow-sm"></div>
                            <div class="mb-2"><label class="form-label text-xs text-muted fw-semibold mb-1">Tanggal</label><input type="date" name="observed_on" class="form-control form-control-sm shadow-sm"></div>
                            <div class="mb-3"><label class="form-label text-xs text-muted fw-semibold mb-1">Catatan <span class="text-danger">*</span></label><textarea name="notes" rows="3" class="form-control form-control-sm shadow-sm" required></textarea></div>
                            <button class="btn btn-primary btn-sm shadow-sm w-100"><i data-lucide="plus" class="w-3 h-3 me-1"></i> Simpan Catatan</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="<?= $canManage ? 'col-lg-8' : 'col-12' ?>">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                                <tr>
                                    <th class="px-3 py-3">Tanggal</th>
                                    <th class="px-3 py-3">Siswa</th>
                                    <th class="px-3 py-3">Jenis</th>
                                    <th class="px-3 py-3">Dimensi</th>
                                    <th class="px-3 py-3 text-center">Rating</th>
                                    <th class="px-3 py-3">Catatan</th>
                                    <th class="px-3 py-3 text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($data['observations'] === []): ?>
                                    <tr><td colspan="7" class="text-center text-muted py-5">Belum ada catatan formatif.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($data['observations'] as $o): ?>
                                        <tr>
                                            <td class="px-3 py-3"><?= esc($o['observed_on']) ?></td>
                                            <td class="px-3 py-3"><?= esc($o['student_name'] ?? '-') ?></td>
                                            <td class="px-3 py-3"><span class="badge bg-light text-dark border"><?= esc($o['observation_type']) ?></span></td>
                                            <td class="px-3 py-3"><?= esc($o['dimension_name'] ?? '-') ?></td>
                                            <td class="px-3 py-3 text-center"><?= $o['rating'] ? '<span class="badge bg-primary-subtle text-primary">' . (int) $o['rating'] . '/5</span>' : '-' ?></td>
                                            <td class="px-3 py-3 small text-muted"><?= esc($o['notes']) ?></td>
                                            <td class="px-3 py-3 text-end">
                                                <?php if ($canManage): ?>
                                                    <form class="d-inline" method="POST" action="<?= base_url('cocurricular/' . (int) $p['id'] . '/observations/' . (int) $o['id'] . '/delete') ?>" onsubmit="return confirm('Hapus catatan?')"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger shadow-sm"><i data-lucide="trash-2" class="w-3 h-3"></i></button></form>
                                                <?php endif; ?>
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

    <!-- TAB 4: EVIDENCE / BUKTI SUMATIF -->
    <?php elseif ($tab === 'evidence'): ?>
        <div class="row g-4">
            <?php if ($canManage): ?>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3 text-gray-900"><i data-lucide="upload" class="w-4 h-4 me-1 text-purple"></i> Unggah Bukti Portofolio</h6>
                        <form method="POST" action="<?= base_url('cocurricular/' . (int) $p['id'] . '/evidences') ?>" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <div class="mb-2"><label class="form-label text-xs text-muted fw-semibold mb-1">Siswa</label><select name="student_id" class="form-select form-select-sm shadow-sm" required><option value="">Pilih siswa</option><?php foreach ($students as $st): ?><option value="<?= $st['id'] ?>"><?= esc($st['full_name']) ?></option><?php endforeach; ?></select></div>
                            <div class="mb-2"><label class="form-label text-xs text-muted fw-semibold mb-1">Jenis Bukti</label><select name="evidence_type" class="form-select form-select-sm shadow-sm"><?php foreach ($evidenceTypes as $et): ?><option value="<?= $et ?>"><?= esc($et) ?></option><?php endforeach; ?></select></div>
                            <div class="mb-2"><label class="form-label text-xs text-muted fw-semibold mb-1">Dimensi</label><select name="dimension_id" class="form-select form-select-sm shadow-sm"><option value="">—</option><?php foreach ($data['dimensions'] as $d): ?><option value="<?= $d['dimension_id'] ?>"><?= esc($d['code'] ?? $d['name']) ?></option><?php endforeach; ?></select></div>
                            <div class="mb-2"><label class="form-label text-xs text-muted fw-semibold mb-1">Judul <span class="text-danger">*</span></label><input name="title" class="form-control form-control-sm shadow-sm" required placeholder="cth. Poster Solusi Sampah"></div>
                            <div class="mb-2"><label class="form-label text-xs text-muted fw-semibold mb-1">Deskripsi</label><textarea name="description" rows="2" class="form-control form-control-sm shadow-sm"></textarea></div>
                            <div class="mb-2"><label class="form-label text-xs text-muted fw-semibold mb-1">Berkas (maks 10 MB)</label><input type="file" name="file" class="form-control form-control-sm shadow-sm"></div>
                            <div class="mb-3"><label class="form-label text-xs text-muted fw-semibold mb-1">Tanggal Capture</label><input type="datetime-local" name="captured_at" class="form-control form-control-sm shadow-sm"></div>
                            <button class="btn btn-primary btn-sm shadow-sm w-100"><i data-lucide="upload" class="w-3 h-3 me-1"></i> Simpan Bukti</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="<?= $canManage ? 'col-lg-8' : 'col-12' ?>">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-gray-900"><i data-lucide="folder-check" class="w-4 h-4 me-1 text-purple"></i> Galeri & Rekapitulasi Bukti Karya</h6>
                            <span class="badge bg-purple-subtle text-purple"><?= count($data['evidences']) ?> Bukti Tersimpan</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                                    <tr>
                                        <th class="px-3 py-3">Bukti</th>
                                        <th class="px-3 py-3">Siswa</th>
                                        <th class="px-3 py-3">Jenis</th>
                                        <th class="px-3 py-3">Dimensi</th>
                                        <th class="px-3 py-3">Tanggal</th>
                                        <th class="px-3 py-3 text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($data['evidences'] === []): ?>
                                        <tr><td colspan="6" class="text-center text-muted py-5">Belum ada bukti sumatif.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($data['evidences'] as $e): ?>
                                            <tr>
                                                <td class="px-3 py-3">
                                                    <div class="fw-semibold small"><?= esc($e['title']) ?></div>
                                                    <div class="text-xs text-muted"><?= esc($e['description'] ?? '') ?></div>
                                                </td>
                                                <td class="px-3 py-3"><?= esc($e['student_name'] ?? '-') ?></td>
                                                <td class="px-3 py-3"><span class="badge bg-light text-dark border"><?= esc($e['evidence_type']) ?></span></td>
                                                <td class="px-3 py-3"><?= esc($e['dimension_name'] ?? '-') ?></td>
                                                <td class="px-3 py-3 small"><?= esc($e['captured_at'] ?? '-') ?></td>
                                                <td class="px-3 py-3 text-end">
                                                    <?php if ($e['file_path']): ?><a href="<?= base_url('cocurricular/evidence-file/' . (int) $e['id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary shadow-sm" title="Unduh / Buka Berkas"><i data-lucide="download" class="w-3 h-3"></i></a><?php endif; ?>
                                                    <?php if ($canManage): ?>
                                                        <form class="d-inline" method="POST" action="<?= base_url('cocurricular/' . (int) $p['id'] . '/evidences/' . (int) $e['id'] . '/delete') ?>" onsubmit="return confirm('Hapus bukti ini?')"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger shadow-sm"><i data-lucide="trash-2" class="w-3 h-3"></i></button></form>
                                                    <?php endif; ?>
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
        </div>

    <!-- TAB 5: RESULTS (HASIL CAPAIAN DIMENSI) -->
    <?php elseif ($tab === 'results'): ?>
        <?php if ($canManage): ?>
        <form method="POST" action="<?= base_url('cocurricular/' . (int) $p['id'] . '/results') ?>" id="resultsForm">
            <?= csrf_field() ?>
        <?php endif; ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <!-- Toolbar with Quick Fill, Search & Progress -->
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                    <div>
                        <h6 class="fw-bold mb-1 text-gray-900"><i data-lucide="grid-3x3" class="w-4 h-4 me-1 text-purple"></i> Matriks Capaian Dimensi Profil Lulusan</h6>
                        <small class="text-muted">Nilai perkembangan karakter siswa pada 4 level kompetensi.</small>
                    </div>
                    <?php if ($canManage): ?>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <span class="text-xs text-muted fw-semibold">Quick-Fill:</span>
                        <button type="button" class="btn btn-xs btn-outline-info shadow-sm py-1 px-2 text-xs" onclick="quickFillLevel('PROFICIENT')">
                            Setel Semua BSH
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-warning shadow-sm py-1 px-2 text-xs" onclick="quickFillLevel('DEVELOPING')">
                            Setel Semua SB
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-success shadow-sm py-1 px-2 text-xs" onclick="quickFillLevel('EXEMPLARY')">
                            Setel Semua SAB
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <input type="text" id="matrixSearchInput" class="form-control form-control-sm shadow-sm" placeholder="Cari nama siswa...">
                    </div>
                    <div class="col-md-8 d-flex justify-content-md-end align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2 text-xs text-muted">
                            <span>Kelengkapan:</span>
                            <div class="progress" style="width: 120px; height: 8px;">
                                <div id="liveProgressBar" class="progress-bar bg-success" style="width: 0%;"></div>
                            </div>
                            <span id="liveProgressText" class="fw-bold text-gray-900">0%</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary shadow-sm" id="btnExportMatrixCsv">
                            <i data-lucide="download" class="w-3.5 h-3.5 me-1"></i> Ekspor CSV
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="matrixDimensionsTable">
                        <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                            <tr>
                                <th class="px-3 py-3" style="min-width:180px">Siswa</th>
                                <?php foreach ($data['dimensions'] as $d): ?>
                                    <th class="px-2 py-3 text-center" style="min-width:140px" title="<?= esc($d['name']) ?>">
                                        <div class="fw-bold text-gray-900"><?= esc($d['code'] ?? substr($d['name'], 0, 18)) ?></div>
                                        <div class="text-xs text-muted fw-normal" style="font-size: 10px;"><?= esc(mb_strimwidth($d['name'], 0, 20, '…')) ?></div>
                                    </th>
                                <?php endforeach; ?>
                                <th class="px-3 py-3 text-end" style="min-width: 100px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($students === []): ?>
                                <tr><td colspan="<?= count($data['dimensions']) + 2 ?>" class="text-center text-muted py-5">Belum ada siswa.</td></tr>
                            <?php else: ?>
                                <?php
                                $resultMap = [];
                                foreach ($data['results'] as $r) {
                                    $resultMap[$r['student_id'] . ':' . $r['dimension_id']] = $r;
                                }
                                ?>
                                <?php foreach ($students as $st): ?>
                                    <tr class="dimension-matrix-row">
                                        <td class="px-3 py-3">
                                            <div class="fw-semibold small student-cell-name"><?= esc($st['full_name']) ?></div>
                                            <div class="text-xs text-muted"><?= esc($st['classroom_name'] ?? '') ?> · NIS <?= esc($st['student_number'] ?? '') ?></div>
                                        </td>
                                        <?php foreach ($data['dimensions'] as $d): ?>
                                            <?php $existing = $resultMap[$st['id'] . ':' . $d['dimension_id']] ?? null; ?>
                                            <td class="px-2 py-3 text-center">
                                                <select name="results[<?= (int) $st['id'] ?>][<?= (int) $d['dimension_id'] ?>][level]" class="form-select form-select-sm shadow-sm text-center level-select <?= $canManage ? '' : 'form-select-plain' ?>" <?= $canManage ? '' : 'disabled' ?>>
                                                    <?php foreach ($resultLevels as $rl): ?>
                                                        <option value="<?= $rl ?>" <?= strtoupper($existing['level'] ?? 'DEVELOPING') === $rl ? 'selected' : '' ?>><?= esc($rl) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="text" name="results[<?= (int) $st['id'] ?>][<?= (int) $d['dimension_id'] ?>][note]" class="form-control form-control-sm shadow-sm mt-1 <?= $canManage ? '' : 'd-none' ?>" placeholder="Catatan" value="<?= esc($existing['note'] ?? '') ?>">
                                            </td>
                                        <?php endforeach; ?>
                                        <td class="px-3 py-3 text-end">
                                            <span class="badge bg-light border row-filled-badge"><?= count(array_filter($data['results'], static fn ($r) => (int) $r['student_id'] === (int) $st['id'])) ?>/<?= count($data['dimensions']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php if ($canManage): ?>
        <div class="d-flex justify-content-end mt-3 mb-5">
            <button type="submit" class="btn btn-primary shadow-sm px-4"><i data-lucide="save" class="w-4 h-4 me-1"></i> Simpan Hasil Dimensi</button>
        </div>
        </form>
        <?php endif; ?>

    <!-- TAB 6: EVALUATION (IPOO) -->
    <?php elseif ($tab === 'evaluation'): ?>
        <!-- IPOO Health Quality Scorecard -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                    <div>
                        <h6 class="fw-bold mb-1 text-gray-900"><i data-lucide="activity" class="w-4 h-4 me-1 text-purple"></i> Indeks Mutu & Evaluasi IPOO</h6>
                        <small class="text-muted">Kerangka evaluasi kualitas 4 pilar: Input, Process, Output, Outcome.</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-xs text-muted">Skor Keseluruhan:</span>
                        <span class="badge <?= $ipoo['status']['class'] ?? 'bg-primary' ?> px-3 py-2 rounded-pill fw-bold">
                            <?= $ipoo['overall_score'] ?? 3.5 ?> / 5.0 (<?= $ipoo['overall_percent'] ?? 70 ?>%) · <?= $ipoo['status']['label'] ?? 'BAIK' ?>
                        </span>
                    </div>
                </div>

                <div class="row g-3">
                    <?php
                    $aspectIcons = [
                        'INPUT'   => 'box',
                        'PROCESS' => 'cog',
                        'OUTPUT'  => 'package-check',
                        'OUTCOME' => 'award',
                    ];
                    foreach (['INPUT', 'PROCESS', 'OUTPUT', 'OUTCOME'] as $asp):
                        $aspData = $ipoo['aspects'][$asp] ?? ['avg_rating' => 3.5, 'percent' => 70, 'count' => 0];
                    ?>
                        <div class="col-md-3">
                            <div class="border rounded-4 p-3 bg-light-subtle h-100">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-xs fw-bold text-uppercase tracking-wider text-muted">
                                        <i data-lucide="<?= $aspectIcons[$asp] ?? 'circle' ?>" class="w-3.5 h-3.5 me-1 text-purple"></i> <?= $asp ?>
                                    </span>
                                    <span class="fw-bold small text-gray-900"><?= $aspData['avg_rating'] ?>/5</span>
                                </div>
                                <div class="progress mb-2" style="height: 6px;">
                                    <div class="progress-bar <?= $aspData['percent'] >= 80 ? 'bg-success' : ($aspData['percent'] >= 60 ? 'bg-primary' : 'bg-warning') ?>" style="width: <?= $aspData['percent'] ?>%;"></div>
                                </div>
                                <div class="d-flex justify-content-between text-xs text-muted">
                                    <span><?= $aspData['percent'] ?>% Tercapai</span>
                                    <span><?= $aspData['count'] ?> Indikator</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <?php if ($canManage): ?>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3 text-gray-900"><i data-lucide="clipboard-plus" class="w-4 h-4 me-1 text-purple"></i> Tambah Evaluasi Aspek</h6>
                        <form method="POST" action="<?= base_url('cocurricular/' . (int) $p['id'] . '/evaluations') ?>">
                            <?= csrf_field() ?>
                            <div class="mb-2"><label class="form-label text-xs text-muted fw-semibold mb-1">Aspek</label><select name="aspect" class="form-select form-select-sm shadow-sm"><?php foreach ($evaluationAspects as $ea): ?><option value="<?= $ea ?>"><?= esc($ea) ?></option><?php endforeach; ?></select></div>
                            <div class="mb-2"><label class="form-label text-xs text-muted fw-semibold mb-1">Indikator <span class="text-danger">*</span></label><input name="indicator" class="form-control form-control-sm shadow-sm" required placeholder="cth. Ketersediaan alat dan bahan"></div>
                            <div class="mb-2"><label class="form-label text-xs text-muted fw-semibold mb-1">Temuan / Catatan</label><textarea name="finding" rows="3" class="form-control form-control-sm shadow-sm"></textarea></div>
                            <div class="mb-3"><label class="form-label text-xs text-muted fw-semibold mb-1">Rating (1–5)</label><input type="number" name="rating" min="1" max="5" value="4" class="form-control form-control-sm shadow-sm"></div>
                            <button class="btn btn-primary btn-sm shadow-sm w-100"><i data-lucide="plus" class="w-3 h-3 me-1"></i> Simpan Evaluasi</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="<?= $canManage ? 'col-lg-8' : 'col-12' ?>">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                                <tr>
                                    <th class="px-3 py-3">Aspek</th>
                                    <th class="px-3 py-3">Indikator</th>
                                    <th class="px-3 py-3">Temuan</th>
                                    <th class="px-3 py-3 text-center">Rating</th>
                                    <th class="px-3 py-3 text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($data['evaluations'] === []): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-5">Belum ada evaluasi.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($data['evaluations'] as $ev): ?>
                                        <tr>
                                            <td class="px-3 py-3"><span class="badge bg-light text-dark border"><?= esc($ev['aspect']) ?></span></td>
                                            <td class="px-3 py-3 fw-semibold small"><?= esc($ev['indicator']) ?></td>
                                            <td class="px-3 py-3 small text-muted"><?= esc($ev['finding'] ?? '-') ?></td>
                                            <td class="px-3 py-3 text-center"><?= $ev['rating'] ? '<span class="badge bg-primary-subtle text-primary">' . (int) $ev['rating'] . '/5</span>' : '-' ?></td>
                                            <td class="px-3 py-3 text-end">
                                                <?php if ($canManage): ?>
                                                    <form class="d-inline" method="POST" action="<?= base_url('cocurricular/' . (int) $p['id'] . '/evaluations/' . (int) $ev['id'] . '/delete') ?>" onsubmit="return confirm('Hapus indikator evaluasi ini?')"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger shadow-sm"><i data-lucide="trash-2" class="w-3 h-3"></i></button></form>
                                                <?php endif; ?>
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
    <?php endif; ?>
</div>

<!-- Modal: Pedoman Deskriptor Dimensi Profil Lulusan -->
<div class="modal fade" id="rubricDescriptorModal" tabindex="-1" aria-labelledby="rubricDescriptorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom px-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="sparkles" class="w-5 h-5 text-purple"></i>
                    <h5 class="modal-title fw-bold" id="rubricDescriptorModalLabel">Pedoman Level Dimensi Profil Pelajar (4 Tingkat)</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-xs text-muted mb-3">Gunakan acuan deskriptor perilaku ini saat menilai perkembangan dimensi profil lulusan peserta didik.</p>
                <?php
                $rubric = $rubricDescriptors ?? [];
                foreach ($rubric as $code => $info):
                ?>
                    <div class="card border rounded-3 p-3 mb-3 bg-light-subtle shadow-none">
                        <h6 class="fw-bold text-gray-900 mb-2"><?= esc($info['name']) ?></h6>
                        <div class="row g-2 text-xs">
                            <div class="col-md-3">
                                <div class="p-2 border rounded bg-white">
                                    <span class="badge bg-danger-subtle text-danger mb-1 d-block">MB (Mulai Berkembang)</span>
                                    <span class="text-muted"><?= esc($info['EMERGING'] ?? '-') ?></span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded bg-white">
                                    <span class="badge bg-warning-subtle text-warning mb-1 d-block">SB (Sedang Berkembang)</span>
                                    <span class="text-muted"><?= esc($info['DEVELOPING'] ?? '-') ?></span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded bg-white">
                                    <span class="badge bg-info-subtle text-info mb-1 d-block">BSH (Sesuai Harapan)</span>
                                    <span class="text-muted"><?= esc($info['PROFICIENT'] ?? '-') ?></span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded bg-white">
                                    <span class="badge bg-success-subtle text-success mb-1 d-block">SAB (Sangat Berkembang)</span>
                                    <span class="text-muted"><?= esc($info['EXEMPLARY'] ?? '-') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="modal-footer border-top px-4 py-2">
                <button type="button" class="btn btn-sm btn-secondary shadow-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function quickFillLevel(targetLevel) {
    if (!confirm('Terapkan level ' + targetLevel + ' untuk semua isian yang belum diubah?')) return;
    document.querySelectorAll('.level-select').forEach(sel => {
        sel.value = targetLevel;
    });
    updateMatrixProgress();
}

function updateMatrixProgress() {
    const selects = document.querySelectorAll('.level-select');
    if (selects.length === 0) return;
    let filled = 0;
    selects.forEach(s => {
        if (s.value && s.value.trim() !== '') filled++;
    });
    const percent = Math.round((filled / selects.length) * 100);
    const pBar = document.getElementById('liveProgressBar');
    const pText = document.getElementById('liveProgressText');
    if (pBar) pBar.style.width = percent + '%';
    if (pText) pText.textContent = percent + '%';
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Update progress bar
    updateMatrixProgress();
    document.querySelectorAll('.level-select').forEach(s => {
        s.addEventListener('change', updateMatrixProgress);
    });

    // Matrix search filter
    const search = document.getElementById('matrixSearchInput');
    if (search) {
        search.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('.dimension-matrix-row').forEach(row => {
                const name = row.querySelector('.student-cell-name')?.textContent.toLowerCase() || '';
                row.style.display = name.includes(q) ? '' : 'none';
            });
        });
    }

    // CSV Exporter for Results Matrix
    const btnCsv = document.getElementById('btnExportMatrixCsv');
    if (btnCsv) {
        btnCsv.addEventListener('click', function() {
            const table = document.getElementById('matrixDimensionsTable');
            if (!table) return;

            let csvContent = '\uFEFF'; // UTF-8 BOM
            const rows = table.querySelectorAll('tr');

            rows.forEach((row, rIdx) => {
                if (row.style.display === 'none') return;
                const cols = [];
                if (rIdx === 0) {
                    row.querySelectorAll('th').forEach(th => {
                        const txt = th.querySelector('.fw-bold')?.textContent || th.textContent;
                        cols.push('"' + txt.trim().replace(/"/g, '""') + '"');
                    });
                } else {
                    const nameCell = row.querySelector('.student-cell-name');
                    cols.push('"' + (nameCell ? nameCell.textContent.trim().replace(/"/g, '""') : '') + '"');

                    row.querySelectorAll('.level-select').forEach(sel => {
                        cols.push('"' + (sel.value || '').replace(/"/g, '""') + '"');
                    });
                }
                csvContent += cols.join(',') + '\r\n';
            });

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.setAttribute('download', 'Matriks_Dimensi_Kokurikuler.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    }
});
</script>
<?= $this->endSection() ?>