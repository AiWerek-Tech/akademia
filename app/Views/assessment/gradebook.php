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
                    <li class="breadcrumb-item"><a href="<?= base_url('assessment/' . $assessment['id']) ?>" class="text-decoration-none"><?= esc($assessment['title']) ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Gradebook</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-gray-900 mb-1">Gradebook — <?= esc($assessment['title']) ?></h1>
            <p class="text-muted mb-0">
                Tentukan status per kriteria. Status kosong dihitung otomatis dari skor (jika ada). Menyimpan nilai akan
                memperbarui Mastery TP dan membuka rekomendasi intervensi.
            </p>
        </div>
        <span class="badge <?= $assessment['status'] === 'CLOSED' ? 'bg-dark-subtle text-dark' : 'bg-success-subtle text-success' ?> rounded-pill px-3 py-2">
            <?= esc($assessment['status']) ?>
        </span>
    </div>

    <?php if ($assessment['criteria'] === []): ?>
        <div class="alert alert-warning border-0 rounded-4">
            Assessment ini belum memiliki kriteria. Tambahkan kriteria (terhubung ke TP) melalui halaman edit agar mastery dapat dihitung.
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= base_url('assessment/' . $assessment['id'] . '/gradebook') ?>">
        <?= csrf_field() ?>
        <?php
        $rubricMap = [];
        foreach ($assessment['criteria'] as $criterion) {
            $levels = [];
            if (! empty($criterion['rubric_levels_json'])) {
                $decoded = json_decode($criterion['rubric_levels_json'], true);
                if (is_array($decoded)) {
                    foreach ($decoded as $lvl) {
                        $levels[] = $lvl;
                    }
                }
            }
            $rubricMap[(int) $criterion['id']] = $levels;
        }
        ?>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0 gradebook-table">
                    <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                        <tr>
                            <th class="px-3 py-3 sticky-col-name" style="min-width:200px">Siswa</th>
                            <?php foreach ($assessment['criteria'] as $i => $criterion): ?>
                                <th class="px-3 py-3 text-center" style="min-width:170px">
                                    Kriteria <?= $i + 1 ?>
                                    <div class="fw-normal text-uppercase" style="font-size:10px">
                                        <?= esc($criterion['tp_code'] ?? 'tanpa TP') ?> · bobot <?= esc($criterion['weight']) ?>
                                    </div>
                                    <?php if (! empty($rubricMap[(int) $criterion['id']])): ?>
                                        <div class="fw-normal text-muted" style="font-size:9px;line-height:1.3">
                                            <?php foreach ($rubricMap[(int) $criterion['id']] as $lvl): ?>
                                                <span class="badge bg-light-subtle text-dark-subtle me-1">
                                                    <?= esc(($lvl['label'] ?? 'L' . ($lvl['level_index'] ?? '')) . (isset($lvl['score']) ? '=' . $lvl['score'] : '')) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                            <th class="px-3 py-3 text-center" style="min-width:110px">Skor</th>
                            <th class="px-3 py-3 text-center" style="min-width:110px">Lengkap</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rows === []): ?>
                            <tr><td colspan="<?= count($assessment['criteria']) + 3 ?>" class="text-center text-muted py-5">Belum ada siswa aktif di kelas ini.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($rows as $row): ?>
                            <?php $studentId = (int) $row['student']['id']; ?>
                            <?php $attemptId = $row['attempt'] ? (int) $row['attempt']['id'] : 0; ?>
                            <tr>
                                <td class="px-3 py-2 sticky-col-name">
                                    <div class="fw-semibold text-gray-900"><?= esc($row['student']['full_name']) ?></div>
                                    <div class="text-xs text-muted"><?= esc($row['student']['student_number'] ?? '') ?></div>
                                </td>
                                <?php foreach ($row['criteria'] as $cell): ?>
                                    <?php $criterionId = (int) $cell['criterion']['id']; ?>
                                    <td class="px-2 py-2 text-center align-middle">
                                        <select name="students[<?= $studentId ?>][criteria][<?= $criterionId ?>][status]" class="form-select form-select-sm criterion-status">
                                            <option value="">— Otomatis —</option>
                                            <?php foreach (['NEEDS_SUPPORT', 'DEVELOPING', 'ACHIEVED', 'ADVANCED'] as $st): ?>
                                                <option value="<?= $st ?>" <?= ($cell['result']['status'] ?? '') === $st ? 'selected' : '' ?>><?= esc($st) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" step="0.01" min="0" name="students[<?= $studentId ?>][criteria][<?= $criterionId ?>][score]" class="form-control form-control-sm mt-1 criterion-score" placeholder="Skor" value="<?= esc($cell['result']['score'] ?? '') ?>">
                                        <input type="text" name="students[<?= $studentId ?>][criteria][<?= $criterionId ?>][notes]" class="form-control form-control-sm mt-1" placeholder="Catatan" value="<?= esc($cell['result']['notes'] ?? '') ?>">
                                    </td>
                                <?php endforeach; ?>
                                <td class="px-2 py-2 text-center align-middle">
                                    <input type="number" step="0.01" min="0" name="students[<?= $studentId ?>][score]" class="form-control form-control-sm text-center" placeholder="Skor" value="<?= esc($row['attempt']['score'] ?? '') ?>">
                                </td>
                                <td class="px-2 py-2 text-center align-middle">
                                    <div class="form-check form-switch justify-content-center">
                                        <input type="hidden" name="students[<?= $studentId ?>][is_complete]" value="0">
                                        <input class="form-check-input" type="checkbox" name="students[<?= $studentId ?>][is_complete]" value="1" <?= ! empty($row['attempt']['is_complete']) ? 'checked' : '' ?>>
                                    </div>
                                </td>
                            </tr>
                            <tr class="gradebook-details-row">
                                <td colspan="<?= count($assessment['criteria']) + 3 ?>" class="px-3 py-2 bg-light-subtle">
                                    <details>
                                        <summary class="text-xs fw-semibold text-muted cursor-pointer">Bukti Belajar & Umpan Balik — <?= esc($row['student']['full_name']) ?></summary>
                                        <div class="row g-2 mt-2">
                                            <div class="col-md-6">
                                                <form method="POST" action="<?= base_url('assessment/' . $assessment['id'] . '/evidence') ?>" class="d-flex flex-column gap-1">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="student_id" value="<?= $studentId ?>">
                                                    <input type="hidden" name="attempt_id" value="<?= $attemptId ?>">
                                                    <input type="text" name="title" class="form-control form-control-sm" placeholder="Judul bukti (mis. foto karya)">
                                                    <select name="learning_objective_id" class="form-select form-select-sm">
                                                        <option value="">Tanpa TP</option>
                                                        <?php foreach ($assessment['objectives'] as $obj): ?>
                                                            <option value="<?= $obj['learning_objective_id'] ?>"><?= esc($obj['tp_code']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div class="d-flex gap-1">
                                                        <select name="criterion_id" class="form-select form-select-sm flex-grow-1">
                                                            <option value="">Tanpa Kriteria</option>
                                                            <?php foreach ($assessment['criteria'] as $c): ?>
                                                                <option value="<?= $c['id'] ?>">K<?= $c['sequence_order'] ?? '' ?> <?= esc($c['tp_code'] ?? '') ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <select name="profile_dimension_id" class="form-select form-select-sm flex-grow-1">
                                                            <option value="">Tanpa Dimensi</option>
                                                            <?php foreach ($dimensions as $d): ?>
                                                                <option value="<?= $d['id'] ?>"><?= esc($d['name']) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <button type="submit" class="btn btn-sm btn-outline-primary shadow-sm align-self-start">Tambah Bukti</button>
                                                </form>
                                            </div>
                                            <div class="col-md-5">
                                                <form method="POST" action="<?= base_url('assessment/' . $assessment['id'] . '/feedback') ?>" class="d-flex gap-2">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="attempt_id" value="<?= $attemptId ?>">
                                                    <input type="hidden" name="student_id" value="<?= $studentId ?>">
                                                    <input type="text" name="content" class="form-control form-control-sm" placeholder="Umpan balik untuk siswa...">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary shadow-sm">Kirim</button>
                                                </form>
                                            </div>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-0 py-3 px-4 d-flex gap-2 justify-content-end">
                <a href="<?= base_url('assessment/' . $assessment['id']) ?>" class="btn btn-outline-secondary shadow-sm">Batal</a>
                <?php if ($assessment['status'] !== 'CLOSED'): ?>
                    <button type="submit" class="btn btn-primary shadow-sm"><i data-lucide="save" class="w-4 h-4 me-1"></i> Simpan Nilai</button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<style>
    .gradebook-table { min-width: <?= 200 + (count($assessment['criteria']) * 170) + 220 ?>px; }
    .gradebook-details-row td { border-bottom: 0; }
    .sticky-col-name { position: sticky; left: 0; background: #fff; z-index: 2; }
    .gradebook-table thead th.sticky-col-name { background: #f8f9fa; }
</style>
<?= $this->endSection() ?>