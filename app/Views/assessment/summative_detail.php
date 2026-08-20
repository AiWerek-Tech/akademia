<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb" class="mb-1">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="<?= base_url('summative') ?>" class="text-decoration-none">Sumatif</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= esc($subject['name']) ?></li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-gray-900 mb-1"><?= esc($subject['name']) ?></h1>
            <p class="text-muted mb-0">
                Metode: <span class="badge bg-light text-dark rounded-pill"><?= esc($policy['calculation_method'] ?? 'AVERAGE') ?></span>
                <?php if ($policy): ?>
                    · Kebijakan <span class="badge bg-primary-subtle text-primary rounded-pill">v<?= (int) $policy['version'] ?></span>
                <?php else: ?>
                    · tanpa kebijakan (default AVERAGE)
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <form method="GET" action="<?= base_url('summative/' . $subject['id']) ?>" class="d-flex gap-2">
                <select name="classroom_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($classrooms as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $selectedClassroom === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <form method="POST" action="<?= base_url('summative/process') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">
                <button type="submit" class="btn btn-primary shadow-sm"><i data-lucide="refresh-cw" class="w-4 h-4 me-1"></i> Proses Ulang</button>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                    <tr>
                        <th class="px-3 py-3">Siswa</th>
                        <th class="px-3 py-3">Kelas</th>
                        <th class="px-3 py-3 text-center">Metode</th>
                        <th class="px-3 py-3 text-center">Skor</th>
                        <th class="px-3 py-3 text-center">Predikat</th>
                        <th class="px-3 py-3 text-center">Status</th>
                        <th class="px-3 py-3 text-end">Validasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($results === []): ?>
                        <tr><td colspan="7" class="text-center text-muted py-5">Belum ada hasil. Klik "Proses" untuk mengolah mastery TP menjadi nilai sumatif.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($results as $row): ?>
                        <?php $detail = json_decode($row['detail_json'] ?? '', true); ?>
                        <tr>
                            <td class="px-3 py-3">
                                <div class="fw-semibold text-gray-900"><?= esc($row['full_name'] ?? '-') ?></div>
                                <div class="text-xs text-muted"><?= esc($row['student_number'] ?? '') ?></div>
                            </td>
                            <td class="px-3 py-3 text-muted"><?= esc($row['classroom_name'] ?? '-') ?></td>
                            <td class="px-3 py-3 text-center"><span class="badge bg-light text-dark rounded-pill"><?= esc($row['calculation_method']) ?></span></td>
                            <td class="px-3 py-3 text-center fw-semibold text-gray-900"><?= $row['raw_score'] !== null ? esc($row['raw_score']) : '—' ?></td>
                            <td class="px-3 py-3 text-center">
                                <?php if ($row['grade_label'] !== null): ?>
                                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3"><?= esc($row['grade_label']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <?php if ($row['status'] === 'VALIDATED'): ?>
                                    <span class="badge bg-success-subtle text-success rounded-pill"><i data-lucide="shield-check" class="w-3 h-3 me-1 d-inline-block"></i> Validated</span>
                                    <?php if ($row['validated_at']): ?><div class="text-xs text-muted mt-1"><?= esc($row['validated_at']) ?></div><?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning rounded-pill">DRAFT</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-3 text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <?php if ($row['status'] !== 'VALIDATED'): ?>
                                        <form method="POST" action="<?= base_url('summative/' . $row['id'] . '/validate') ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-success shadow-sm"><i data-lucide="check" class="w-3.5 h-3.5 me-1"></i> Validasi</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="<?= base_url('summative/' . $row['id'] . '/reopen') ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-secondary shadow-sm" onclick="return confirm('Buka kembali hasil ini agar dapat diproses ulang?')"><i data-lucide="undo-2" class="w-3.5 h-3.5 me-1"></i> Buka</button>
                                        </form>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#detailModal-<?= (int) $row['id'] ?>"><i data-lucide="list-tree" class="w-3.5 h-3.5"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php foreach ($results as $row): ?>
        <?php $detail = json_decode($row['detail_json'] ?? '', true); ?>
        <div class="modal fade" id="detailModal-<?= (int) $row['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Rincian <?= esc($row['full_name'] ?? '-') ?> — <?= esc($subject['name']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                                <tr><th>TP</th><th class="text-center">Status</th><th class="text-center">Skor</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($detail['tps'])): ?>
                                    <tr><td colspan="3" class="text-center text-muted py-3">Belum ada data mastery.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($detail['tps'] ?? [] as $tp): ?>
                                    <tr>
                                        <td class="small"><span class="badge bg-light text-dark me-1"><?= esc($tp['tp_code']) ?></span><?= esc($tp['tp']) ?></td>
                                        <td class="text-center small"><?= esc($tp['result'] ?? '—') ?></td>
                                        <td class="text-center small"><?= $tp['score'] !== null ? esc($tp['score']) : '—' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <a href="<?= base_url('summative') ?>" class="btn btn-outline-secondary shadow-sm mt-3"><i data-lucide="arrow-left" class="w-4 h-4 me-1"></i> Kembali</a>
</div>
<?= $this->endSection() ?>