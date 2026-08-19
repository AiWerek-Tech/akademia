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
                    <li class="breadcrumb-item"><a href="<?= base_url('assessment') ?>" class="text-decoration-none">Assessment</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Detail</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-gray-900 mb-1"><?= esc($assessment['title']) ?></h1>
            <p class="text-muted mb-0"><?= esc($assessment['assessment_type']) ?> · <?= esc($assessment['assessment_form']) ?> · <?= esc($assessment['assessment_date']) ?></p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if (has_permission('assessment.manage')): ?>
                <a href="<?= base_url('assessment/' . $assessment['id'] . '/gradebook') ?>" class="btn btn-primary shadow-sm"><i data-lucide="pen-line" class="w-4 h-4 me-1"></i> Gradebook</a>
                <a href="<?= base_url('assessment/' . $assessment['id'] . '/edit') ?>" class="btn btn-outline-secondary shadow-sm"><i data-lucide="pencil" class="w-4 h-4 me-1"></i> Edit</a>
                <?php if ($assessment['status'] === 'DRAFT'): ?>
                    <form method="POST" action="<?= base_url('assessment/' . $assessment['id'] . '/transition') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="status" value="PUBLISHED">
                        <button type="submit" class="btn btn-success shadow-sm"><i data-lucide="send" class="w-4 h-4 me-1"></i> Terbitkan</button>
                    </form>
                <?php elseif ($assessment['status'] === 'PUBLISHED'): ?>
                    <form method="POST" action="<?= base_url('assessment/' . $assessment['id'] . '/transition') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="status" value="CLOSED">
                        <button type="submit" class="btn btn-outline-danger shadow-sm"><i data-lucide="lock" class="w-4 h-4 me-1"></i> Tutup</button>
                    </form>
                <?php endif; ?>
                <?php if ($assessment['status'] === 'DRAFT'): ?>
                    <form method="POST" action="<?= base_url('assessment/' . $assessment['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Hapus assessment draft ini?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-danger shadow-sm"><i data-lucide="trash-2" class="w-4 h-4 me-1"></i> Hapus</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Status & Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="text-xs fw-semibold text-muted text-uppercase tracking-wider">Status</div>
                <div class="mt-1">
                    <?php $badgeMap = ['DRAFT' => 'bg-secondary-subtle text-secondary', 'PUBLISHED' => 'bg-success-subtle text-success', 'CLOSED' => 'bg-dark-subtle text-dark']; ?>
                    <span class="badge <?= $badgeMap[$assessment['status']] ?? 'bg-light' ?> rounded-pill px-3 py-2"><?= esc($assessment['status']) ?></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="text-xs fw-semibold text-muted text-uppercase tracking-wider">Siswa Terlibat</div>
                <div class="h3 fw-bold text-gray-900 mb-0 mt-1"><?= $student_count ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="text-xs fw-semibold text-muted text-uppercase tracking-wider">Sudah Dinilai</div>
                <div class="h3 fw-bold text-primary mb-0 mt-1"><?= $attempt_count ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="text-xs fw-semibold text-muted text-uppercase tracking-wider">Bukti & Umpan Balik</div>
                <div class="h3 fw-bold text-success mb-0 mt-1"><?= $evidence_count + $feedback_count ?></div>
            </div>
        </div>
    </div>

    <!-- Objectives / TP covered -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h6 class="fw-bold text-gray-900 mb-0"><i data-lucide="target" class="w-4 h-4 me-1 text-primary"></i> Tujuan Pembelajaran (TP)</h6>
        </div>
        <div class="card-body pt-0 px-4 pb-4">
            <?php if ($assessment['objectives'] === []): ?>
                <p class="text-muted mb-0 small">Belum ada TP yang diukur.</p>
            <?php endif; ?>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($assessment['objectives'] as $obj): ?>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 text-start">
                        <strong><?= esc($obj['tp_code']) ?></strong> — <?= esc($obj['tp_name'] ?? '-') ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Criteria -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h6 class="fw-bold text-gray-900 mb-0"><i data-lucide="list-checks" class="w-4 h-4 me-1 text-primary"></i> Kriteria & Rubrik</h6>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Kriteria</th>
                        <th class="px-4 py-3">TP</th>
                        <th class="px-4 py-3 text-center">Bobot</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($assessment['criteria'] === []): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada kriteria penilaian.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($assessment['criteria'] as $i => $criterion): ?>
                        <?php
                        $rubricLevels = [];
                        if (! empty($criterion['rubric_levels_json'])) {
                            $decoded = json_decode($criterion['rubric_levels_json'], true);
                            if (is_array($decoded)) {
                                $rubricLevels = $decoded;
                            }
                        }
                        ?>
                        <tr>
                            <td class="px-4 py-3 text-muted"><?= $i + 1 ?></td>
                            <td class="px-4 py-3">
                                <?= esc($criterion['criterion']) ?>
                                <?php if ($rubricLevels !== []): ?>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        <?php foreach ($rubricLevels as $lvl): ?>
                                            <span class="badge bg-light-subtle text-dark-subtle rounded-pill" style="font-size:10px">
                                                <?= esc($lvl['label'] ?? 'L' . ($lvl['level_index'] ?? '')) ?>
                                                <?= isset($lvl['score']) ? '=' . esc($lvl['score']) : '' ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-muted"><?= esc($criterion['tp_code'] ?? '-') ?></td>
                            <td class="px-4 py-3 text-center"><span class="badge bg-light text-dark"><?= esc($criterion['weight']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Items -->
    <?php if ($assessment['items'] !== []): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 py-3 px-4">
                <h6 class="fw-bold text-gray-900 mb-0"><i data-lucide="file-text" class="w-4 h-4 me-1 text-primary"></i> Item / Soal</h6>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($assessment['items'] as $i => $item): ?>
                    <div class="list-group-item px-4 py-3">
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold text-gray-900"><?= $i + 1 ?>. <?= esc($item['prompt']) ?></span>
                            <span class="badge bg-light text-muted rounded-pill"><?= esc($item['item_type']) ?><?= $item['max_score'] !== null ? ' · maks ' . esc($item['max_score']) : '' ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <a href="<?= base_url('assessment') ?>" class="btn btn-outline-secondary shadow-sm"><i data-lucide="arrow-left" class="w-4 h-4 me-1"></i> Kembali</a>
</div>
<?= $this->endSection() ?>