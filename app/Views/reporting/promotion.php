<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3" style="max-width:1200px">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="<?= base_url('reporting') ?>">Rapor</a></li><li class="breadcrumb-item active">Kesiapan Kenaikan</li></ol></nav>
            <h1 class="h3 fw-bold text-gray-900 mb-0">Kesiapan Kenaikan Kelas</h1>
            <p class="text-muted mb-0">Bantuan keputusan kenaikan/kelulusan berdasarkan kelengkapan nilai, kehadiran, penguasaan kompetensi, dan tindak lanjut intervensi.</p>
        </div>
        <form method="GET" action="<?= base_url('reporting/promotion') ?>" class="d-flex gap-2">
            <select name="classroom_id" class="form-select rounded-pill" style="width:auto" onchange="this.form.submit()">
                <option value="0">Semua Kelas</option>
                <?php foreach ($classrooms as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === $selectedClassroom ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if (! empty($summary)): ?>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 d-flex align-items-center gap-3">
                        <div class="rounded-3 p-3 bg-success-subtle text-success"><i data-lucide="badge-check" class="d-block" style="width:24px;height:24px"></i></div>
                        <div>
                            <div class="h4 fw-bold mb-0"><?= (int) ($summary['READY'] ?? 0) ?></div>
                            <div class="text-muted small">Siap Naik Kelas</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 d-flex align-items-center gap-3">
                        <div class="rounded-3 p-3 bg-warning-subtle text-warning"><i data-lucide="list-checks" class="d-block" style="width:24px;height:24px"></i></div>
                        <div>
                            <div class="h4 fw-bold mb-0"><?= (int) ($summary['REVIEW'] ?? 0) ?></div>
                            <div class="text-muted small">Perlu Review</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 d-flex align-items-center gap-3">
                        <div class="rounded-3 p-3 bg-danger-subtle text-danger"><i data-lucide="alert-triangle" class="d-block" style="width:24px;height:24px"></i></div>
                        <div>
                            <div class="h4 fw-bold mb-0"><?= (int) ($summary['ATTENTION'] ?? 0) ?></div>
                            <div class="text-muted small">Perlu Perhatian Khusus</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($rows)): ?>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="text-center py-5">
                <p class="text-muted mb-0">Belum ada siswa untuk periode ini.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Siswa</th>
                                <th class="text-center">Kelengkapan Nilai</th>
                                <th class="text-center">Rata-rata</th>
                                <th class="text-center">Penguasaan</th>
                                <th class="text-center">Kehadiran</th>
                                <th>Catatan</th>
                                <th class="text-center">Status</th>
                                <th class="pe-4 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <?php $sc = match($r['status']) { 'READY' => 'success', 'REVIEW' => 'warning', default => 'danger' }; ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-semibold"><?= esc($r['student']['full_name'] ?? '') ?></div>
                                        <div class="text-muted small"><?= esc($r['student']['student_number'] ?? '') ?> · <?= esc($r['student']['classroom_name'] ?? '') ?></div>
                                    </td>
                                    <td class="text-center"><?= $r['completion_pct'] ?>%<div class="text-muted small"><?= $r['graded_subjects'] ?>/<?= $r['total_subjects'] ?></div></td>
                                    <td class="text-center"><?= $r['avg_score'] !== null ? $r['avg_score'] : '—' ?></td>
                                    <td class="text-center"><?= $r['avg_mastery'] !== null ? $r['avg_mastery'] . '%' : '—' ?></td>
                                    <td class="text-center <?= $r['avg_attendance'] !== null && $r['avg_attendance'] < 75 ? 'text-danger fw-bold' : '' ?>"><?= $r['avg_attendance'] !== null ? $r['avg_attendance'] . '%' : '—' ?></td>
                                    <td>
                                        <?php if ($r['alerts'] === []): ?>
                                            <span class="text-muted small">—</span>
                                        <?php else: ?>
                                            <?php foreach ($r['alerts'] as $alert): ?>
                                                <span class="badge bg-danger-subtle text-danger rounded-pill mb-1" style="font-size:.65rem"><?= esc($alert) ?></span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?> rounded-pill"><?= $r['status'] ?></span></td>
                                    <td class="pe-4 text-end">
                                        <a href="<?= base_url('reporting/portfolio/' . $r['student']['id']) ?>" class="btn btn-sm btn-outline-secondary rounded-pill">Portofolio</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>