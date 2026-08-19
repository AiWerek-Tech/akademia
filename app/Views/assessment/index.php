<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-purple-subtle text-purple px-2.5 py-1.5 rounded-pill fw-semibold text-xs text-uppercase tracking-wider">
                    <i data-lucide="clipboard-check" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Phase 6 Assessment
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mt-2 mb-1">Assessment & Penilaian</h1>
            <p class="text-muted mb-0">Kelola assessment, nilai, bukti belajar, mastery TP, dan intervensi.</p>
        </div>
        <?php if (has_permission('assessment.manage')): ?>
            <a href="<?= base_url('assessment/create') ?>" class="btn btn-primary shadow-sm px-3">
                <i data-lucide="plus" class="w-4 h-4 me-1"></i> Buat Assessment
            </a>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= base_url('assessment') ?>" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Kelas / Rombel</label>
                    <select name="classroom_id" class="form-select form-select-sm shadow-sm">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($classrooms as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (int) $filters['classroom_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Mata Pelajaran</label>
                    <select name="subject_id" class="form-select form-select-sm shadow-sm">
                        <option value="">Semua Mapel</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= (int) $filters['subject_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= esc($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Tipe</label>
                    <select name="assessment_type" class="form-select form-select-sm shadow-sm">
                        <option value="">Semua Tipe</option>
                        <?php foreach ($types as $t): ?>
                            <option value="<?= $t ?>" <?= $filters['assessment_type'] === $t ? 'selected' : '' ?>><?= esc($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm shadow-sm">
                        <option value="">Semua Status</option>
                        <?php foreach ($statuses as $st): ?>
                            <option value="<?= $st ?>" <?= $filters['status'] === $st ? 'selected' : '' ?>><?= esc($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-outline-primary shadow-sm w-100">Filter</button>
                    <a href="<?= base_url('assessment') ?>" class="btn btn-sm btn-outline-secondary shadow-sm" title="Reset">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- List -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                    <tr>
                        <th class="px-3 py-3">Judul</th>
                        <th class="px-3 py-3">Kelas</th>
                        <th class="px-3 py-3">Mapel</th>
                        <th class="px-3 py-3">Tanggal</th>
                        <th class="px-3 py-3">Tipe</th>
                        <th class="px-3 py-3 text-center">TP</th>
                        <th class="px-3 py-3 text-center">Nilai</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3 text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($assessments === []): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i data-lucide="inbox" class="w-8 h-8 text-muted mb-2 d-inline-block"></i><br>
                                Belum ada assessment. Klik "Buat Assessment" untuk memulai.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($assessments as $a): ?>
                        <tr>
                            <td class="px-3 py-3">
                                <a href="<?= base_url('assessment/' . $a['id']) ?>" class="text-decoration-none fw-semibold text-gray-900"><?= esc($a['title']) ?></a>
                                <div class="text-xs text-muted"><?= esc($a['assessment_form']) ?></div>
                            </td>
                            <td class="px-3 py-3 text-muted"><?= esc($a['classroom_name'] ?? '-') ?></td>
                            <td class="px-3 py-3 text-muted"><?= esc($a['subject_name'] ?? '-') ?></td>
                            <td class="px-3 py-3 text-muted"><?= esc($a['assessment_date']) ?></td>
                            <td class="px-3 py-3">
                                <span class="badge <?= $a['assessment_type'] === 'SUMMATIVE' ? 'bg-danger-subtle text-danger' : ($a['assessment_type'] === 'DIAGNOSTIC' ? 'bg-info-subtle text-info' : 'bg-primary-subtle text-primary') ?> rounded-pill">
                                    <?= esc($a['assessment_type']) ?>
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center"><span class="badge bg-light text-dark rounded-pill"><?= (int) $a['tp_count'] ?></span></td>
                            <td class="px-3 py-3 text-center">
                                <span class="badge bg-light text-dark rounded-pill"><?= (int) $a['graded_count'] ?></span>
                            </td>
                            <td class="px-3 py-3">
                                <?php
                                $badgeMap = [
                                    'DRAFT'     => ['bg-secondary-subtle text-secondary', 'Draft'],
                                    'PUBLISHED' => ['bg-success-subtle text-success', 'Terbit'],
                                    'CLOSED'    => ['bg-dark-subtle text-dark', 'Ditutup'],
                                ];
                                [$badgeClass, $badgeLabel] = $badgeMap[$a['status']] ?? ['bg-light text-dark', $a['status']];
                                ?>
                                <span class="badge <?= $badgeClass ?> rounded-pill"><?= $badgeLabel ?></span>
                            </td>
                            <td class="px-3 py-3 text-end">
                                <a href="<?= base_url('assessment/' . $a['id']) ?>" class="btn btn-sm btn-outline-secondary shadow-sm" title="Detail"><i data-lucide="eye" class="w-3.5 h-3.5"></i></a>
                                <?php if (has_permission('assessment.manage')): ?>
                                    <a href="<?= base_url('assessment/' . $a['id'] . '/gradebook') ?>" class="btn btn-sm btn-outline-primary shadow-sm" title="Gradebook"><i data-lucide="pen-line" class="w-3.5 h-3.5"></i></a>
                                    <a href="<?= base_url('assessment/' . $a['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary shadow-sm" title="Edit"><i data-lucide="pencil" class="w-3.5 h-3.5"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>