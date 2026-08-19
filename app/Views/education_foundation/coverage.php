<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php 
$pageTitle = 'Coverage & Analisis Kesenjangan Kurikulum'; 
$pageIcon = 'scan-search'; 
$pageDescription = 'Audit cakupan Tujuan Pembelajaran (TP) efektif dalam ATP, deteksi kesenjangan kompetensi, dan duplikasi.'; 
?>
<?= view('education_foundation/_page_header', compact('pageTitle','pageIcon','pageDescription')) ?>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
            <i data-lucide="filter" class="text-primary" style="width: 18px; height: 18px;"></i>
            Filter Konteks Analisis Kurikulum
        </h6>
        <form method="get" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Versi Kurikulum</label>
                <select class="form-select rounded-3" name="curriculum_version_id" required>
                    <option value="">Pilih versi...</option>
                    <?php foreach ($versions as $v): ?>
                        <option value="<?= $v['id'] ?>" <?= (string)request()->getGet('curriculum_version_id') === (string)$v['id'] ? 'selected' : '' ?>>
                            <?= esc($v['code']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Unit Sekolah</label>
                <select class="form-select rounded-3" name="unit_id" required>
                    <?php foreach ($units as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (string)request()->getGet('unit_id') === (string)$u['id'] ? 'selected' : '' ?>>
                            <?= esc($u['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Mata Pelajaran</label>
                <select class="form-select rounded-3" name="subject_id" required>
                    <option value="">Pilih mapel...</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= (string)request()->getGet('subject_id') === (string)$s['id'] ? 'selected' : '' ?>>
                            <?= esc($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Tingkat</label>
                <select class="form-select rounded-3" name="grade_level_id" required>
                    <option value="">Pilih tingkat...</option>
                    <?php foreach ($gradeLevels as $g): ?>
                        <option value="<?= $g['id'] ?>" <?= (string)request()->getGet('grade_level_id') === (string)$g['id'] ? 'selected' : '' ?>>
                            <?= esc($g['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 rounded-3 py-2">
                    <i data-lucide="play" style="width: 16px; height: 16px;"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (isset($rows['total_objectives'])): ?>
    <?php
    $covPct = (float)($rows['coverage_percent'] ?? 0);
    $covColor = $covPct >= 100 ? 'success' : ($covPct >= 75 ? 'primary' : 'warning');
    ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-4 p-3 text-<?= $covColor ?>" style="background: rgba(var(--bs-<?= $covColor ?>-rgb), 0.1);">
                        <i data-lucide="pie-chart" style="width: 28px; height: 28px;"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold text-<?= $covColor ?>"><?= $rows['coverage_percent'] ?>%</div>
                        <div class="small text-muted">Tingkat Cakupan (Coverage)</div>
                    </div>
                </div>
                <div class="progress mt-3 rounded-pill" style="height: 6px;">
                    <div class="progress-bar bg-<?= $covColor ?>" style="width: <?= $rows['coverage_percent'] ?>%"></div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-4 p-3 bg-success bg-opacity-10 text-success">
                        <i data-lucide="check-circle" style="width: 28px; height: 28px;"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold text-success"><?= $rows['covered_objectives'] ?> / <?= $rows['total_objectives'] ?></div>
                        <div class="small text-muted">TP Terdistribusi ke ATP</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-4 p-3 bg-danger bg-opacity-10 text-danger">
                        <i data-lucide="alert-triangle" style="width: 28px; height: 28px;"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold text-danger"><?= count($rows['missing']) ?></div>
                        <div class="small text-muted">TP Belum Masuk ATP (Gap)</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-4 p-3 bg-warning bg-opacity-10 text-warning">
                        <i data-lucide="copy" style="width: 28px; height: 28px;"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold text-warning"><?= count($rows['duplicates']) ?></div>
                        <div class="small text-muted">Duplikasi Antar-ATP</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 p-4 pb-0 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 bg-danger bg-opacity-10 text-danger p-2">
                            <i data-lucide="alert-circle" style="width: 18px; height: 18px;"></i>
                        </div>
                        <h6 class="fw-bold mb-0">Tujuan Pembelajaran Belum Tercakup (<?= count($rows['missing']) ?>)</h6>
                    </div>
                    <?php if (!empty($rows['missing'])): ?>
                        <a href="<?= base_url('curriculum/sequences') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            <i data-lucide="route" class="me-1" style="width: 14px; height: 14px;"></i> Buka ATP
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body p-4">
                    <div class="list-group list-group-flush">
                        <?php foreach ($rows['missing'] as $tp): ?>
                            <div class="list-group-item px-0 py-3 border-bottom d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <span class="badge bg-danger bg-opacity-10 text-danger font-monospace rounded-pill px-2 py-1 mb-1">
                                        <?= esc($tp['code']) ?>
                                    </span>
                                    <div class="small text-dark fw-medium"><?= esc($tp['statement']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($rows['missing'])): ?>
                            <div class="text-center py-5 text-success">
                                <div class="rounded-circle bg-success bg-opacity-10 text-success p-3 d-inline-flex mb-3">
                                    <i data-lucide="check-check" style="width: 36px; height: 36px;"></i>
                                </div>
                                <h6 class="fw-bold">Semua TP Telah Tercakup Sempurna!</h6>
                                <p class="small text-muted mb-0">Seluruh Tujuan Pembelajaran telah dipetakan ke dalam Alur Tujuan Pembelajaran (ATP).</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 p-4 pb-0 d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-warning bg-opacity-10 text-warning p-2">
                        <i data-lucide="copy-check" style="width: 18px; height: 18px;"></i>
                    </div>
                    <h6 class="fw-bold mb-0">Duplikasi & Integritas (<?= count($rows['duplicates']) ?>)</h6>
                </div>
                <div class="card-body p-4">
                    <div class="list-group list-group-flush">
                        <?php foreach ($rows['duplicates'] as $tp): ?>
                            <div class="list-group-item px-0 py-2 border-bottom">
                                <span class="badge bg-warning bg-opacity-10 text-warning font-monospace rounded-pill px-2 py-1">
                                    <?= esc($tp['code'] ?? $tp['objective_code'] ?? 'TP') ?>
                                </span>
                                <div class="small text-muted mt-1">Terdeteksi ganda pada lebih dari satu urutan ATP dalam tahun ajaran yang sama.</div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($rows['duplicates'])): ?>
                            <div class="text-center py-5 text-success">
                                <div class="rounded-circle bg-success bg-opacity-10 text-success p-3 d-inline-flex mb-3">
                                    <i data-lucide="shield-check" style="width: 36px; height: 36px;"></i>
                                </div>
                                <h6 class="fw-bold">Tidak Ada Duplikasi</h6>
                                <p class="small text-muted mb-0">Setiap TP hanya dialokasikan secara unik ke dalam satu alur ATP.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body text-center text-muted py-5">
            <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-4 d-inline-flex mb-3">
                <i data-lucide="sliders-horizontal" style="width: 40px; height: 40px;"></i>
            </div>
            <h5 class="fw-bold text-dark">Pilih Konteks Analisis</h5>
            <p class="small text-muted mb-0" style="max-width: 450px; margin: 0 auto;">Pilih versi kurikulum, unit sekolah, mata pelajaran, dan tingkat kelas di atas untuk memulai audit cakupan kurikulum.</p>
        </div>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
