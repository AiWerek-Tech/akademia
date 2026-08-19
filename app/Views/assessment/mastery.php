<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
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
                <span class="badge bg-purple-subtle text-purple px-2.5 py-1.5 rounded-pill fw-semibold text-xs text-uppercase tracking-wider">
                    <i data-lucide="bar-chart-3" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Mastery TP
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mt-2 mb-1">Mastery TP (Tujuan Pembelajaran)</h1>
            <p class="text-muted mb-0">Pantau ketercapaian TP berbasis bukti per siswa. Baris kuning menandakan intervensi terbuka.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= base_url('mastery') ?>" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Kelas / Rombel</label>
                    <select name="classroom_id" class="form-select form-select-sm shadow-sm">
                        <option value="">Pilih Kelas...</option>
                        <?php foreach ($classrooms as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (int) $selectedClassroom === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Mata Pelajaran</label>
                    <select name="subject_id" class="form-select form-select-sm shadow-sm">
                        <option value="">Pilih Mapel...</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= (int) $selectedSubject === (int) $s['id'] ? 'selected' : '' ?>><?= esc($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-outline-primary shadow-sm w-100">Tampilkan</button>
                    <a href="<?= base_url('mastery') ?>" class="btn btn-sm btn-outline-secondary shadow-sm">Reset</a>
                </div>
                <div class="col-md-2 text-end">
                    <form method="POST" action="<?= base_url('mastery/recommend') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="classroom_id" value="<?= $selectedClassroom ?>">
                        <button type="submit" class="btn btn-sm btn-outline-warning shadow-sm"><i data-lucide="wand-sparkles" class="w-3.5 h-3.5 me-1"></i> Rekomendasi Intervensi</button>
                    </form>
                </div>
            </form>
        </div>
    </div>

    <?php if (!$selectedClassroom || !$selectedSubject): ?>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body text-center text-muted py-5">
                <i data-lucide="filter" class="w-8 h-8 text-muted mb-2 d-inline-block"></i><br>
                Pilih kelas dan mapel untuk menampilkan papan mastery TP.
            </div>
        </div>
    <?php else: ?>
        <!-- Summary -->
        <div class="row g-3 mb-4">
            <?php
            $resultColors = [
                'NEEDS_SUPPORT' => ['bg-danger-subtle text-danger', 'Perlu Pendampingan'],
                'DEVELOPING'    => ['bg-warning-subtle text-warning', 'Sedang Berkembang'],
                'ACHIEVED'      => ['bg-success-subtle text-success', 'Tercapai'],
                'ADVANCED'      => ['bg-primary-subtle text-primary', 'Melampaui'],
            ];
            foreach ($resultColors as $code => [$cls, $label]): ?>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                        <div class="text-xs fw-semibold text-muted text-uppercase tracking-wider"><?= $label ?></div>
                        <div class="h3 fw-bold mb-0 mt-1 <?= str_replace('bg-', 'text-', $cls) ?>"><?= (int) ($summary[$code] ?? 0) ?></div>
                        <div class="text-xs text-muted">dari <?= (int) ($summary['TOTAL'] ?? 0) ?> data mastery</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Matrix -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                        <tr>
                            <th class="px-3 py-3" style="min-width:200px">Siswa</th>
                            <?php foreach ($board['objectives'] as $obj): ?>
                                <th class="px-3 py-3 text-center" style="min-width:130px" title="<?= esc($obj['name']) ?>"><?= esc($obj['code']) ?></th>
                            <?php endforeach; ?>
                            <th class="px-3 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($board['matrix'] === []): ?>
                            <tr><td colspan="<?= count($board['objectives']) + 2 ?>" class="text-center text-muted py-5">Belum ada siswa aktif.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($board['matrix'] as $studentRow): ?>
                            <tr>
                                <td class="px-3 py-2">
                                    <div class="fw-semibold text-gray-900"><?= esc($studentRow['student']['full_name']) ?></div>
                                    <div class="text-xs text-muted"><?= esc($studentRow['student']['student_number'] ?? '') ?></div>
                                </td>
                                <?php foreach ($studentRow['objectives'] as $cell): ?>
                                    <?php
                                    $mastery = $cell['mastery'];
                                    $intervention = $cell['intervention'];
                                    $result = $mastery['result'] ?? null;
                                    [$badgeClass, $badgeLabel] = $result ? ($resultColors[$result] ?? ['bg-light text-dark', $result]) : ['bg-light text-muted', '—'];
                                    ?>
                                    <td class="px-3 py-2 text-center">
                                        <span class="badge <?= $badgeClass ?> rounded-pill px-3 py-2"><?= esc($badgeLabel) ?></span>
                                        <?php if ($intervention): ?>
                                            <span class="d-block text-xs text-warning mt-1" title="Intervensi <?= esc($intervention['intervention_type']) ?> terbuka">
                                                <i data-lucide="heart-pulse" class="w-3 h-3"></i> <?= esc($intervention['intervention_type']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="px-3 py-2 text-center">
                                    <form method="POST" action="<?= base_url('mastery/set') ?>" class="d-flex gap-1 justify-content-center" onsubmit="return confirm('Set mastery TP untuk siswa ini?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="student_id" value="<?= (int) $studentRow['student']['id'] ?>">
                                        <select name="objective_id" class="form-select form-select-sm" style="max-width:110px">
                                            <?php foreach ($board['objectives'] as $obj): ?>
                                                <option value="<?= $obj['id'] ?>" <?= (int) $selectedObjective === (int) $obj['id'] ? 'selected' : '' ?>><?= esc($obj['code']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <select name="result" class="form-select form-select-sm" style="max-width:130px">
                                            <?php foreach ($results as $r): ?>
                                                <option value="<?= $r ?>"><?= esc($r) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-secondary shadow-sm" title="Set manual"><i data-lucide="check" class="w-3.5 h-3.5"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>