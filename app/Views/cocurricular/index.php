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
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-purple-subtle text-purple px-2.5 py-1.5 rounded-pill fw-semibold text-xs text-uppercase tracking-wider">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Phase 7 Kokurikuler
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mt-2 mb-1">Program Kokurikuler & Karakter</h1>
            <p class="text-muted mb-0"><?= esc($periodName) ?> — desain, jadwal, eksekusi, asesmen dimensi profil lulusan, dan evaluasi.</p>
        </div>
        <?php if ($canManage): ?>
            <a href="<?= base_url('cocurricular/create') ?>" class="btn btn-primary shadow-sm px-3">
                <i data-lucide="plus" class="w-4 h-4 me-1"></i> Buat Program
            </a>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= base_url('cocurricular') ?>" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Jenis Program</label>
                    <select name="program_type" class="form-select form-select-sm shadow-sm">
                        <option value="">Semua Jenis</option>
                        <?php foreach ($types as $t): ?>
                            <option value="<?= $t ?>" <?= $filterType === $t ? 'selected' : '' ?>><?= esc($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-outline-primary shadow-sm w-100">Filter</button>
                    <a href="<?= base_url('cocurricular') ?>" class="btn btn-sm btn-outline-secondary shadow-sm" title="Reset">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                    <tr>
                        <th class="px-3 py-3">Program</th>
                        <th class="px-3 py-3">Jenis</th>
                        <th class="px-3 py-3">Periode</th>
                        <th class="px-3 py-3">Model</th>
                        <th class="px-3 py-3 text-center">Dimensi</th>
                        <th class="px-3 py-3 text-center">Mapel</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3 text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($programs === []): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i data-lucide="inbox" class="w-8 h-8 text-muted mb-2 d-inline-block"></i><br>
                                Belum ada program kokurikuler. Klik "Buat Program" untuk memulai.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($programs as $p): ?>
                            <?php
                            $dims = $db->table('cocurricular_program_dimensions pd')
                                ->join('graduate_profile_dimensions gpd', 'gpd.id = pd.dimension_id', 'left')
                                ->where('pd.program_id', $p['id'])
                                ->countAllResults();
                            $subj = $db->table('cocurricular_program_subjects')->where('program_id', $p['id'])->countAllResults();
                            ?>
                            <tr>
                                <td class="px-3 py-3">
                                    <div class="fw-semibold"><?= esc($p['title']) ?></div>
                                    <div class="text-xs text-muted"><?= esc($p['code'] ?? '') ?><?= $p['code'] ? ' · ' : '' ?><?= esc($p['theme'] ?? 'Tanpa tema') ?></div>
                                </td>
                                <td class="px-3 py-3"><span class="badge bg-light text-dark border"><?= esc($p['program_type']) ?></span></td>
                                <td class="px-3 py-3"><?= esc($p['period_name'] ?? '-') ?></td>
                                <td class="px-3 py-3"><?= esc($p['delivery_model']) ?></td>
                                <td class="px-3 py-3 text-center"><?= (int) $dims ?></td>
                                <td class="px-3 py-3 text-center"><?= (int) $subj ?></td>
                                <td class="px-3 py-3">
                                    <span class="badge <?= match ($p['status']) {
                                        'DRAFT' => 'bg-warning-subtle text-warning',
                                        'ACTIVE' => 'bg-success-subtle text-success',
                                        'COMPLETED' => 'bg-primary-subtle text-primary',
                                        default => 'bg-secondary-subtle text-secondary',
                                    } ?>"><?= esc($p['status']) ?></span>
                                </td>
                                <td class="px-3 py-3 text-end">
                                    <a href="<?= base_url('cocurricular/' . $p['id']) ?>" class="btn btn-sm btn-outline-primary shadow-sm"><i data-lucide="eye" class="w-3 h-3"></i> Detail</a>
                                    <?php if ($canManage): ?>
                                        <a href="<?= base_url('cocurricular/' . $p['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary shadow-sm"><i data-lucide="edit-3" class="w-3 h-3"></i></a>
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
<?= $this->endSection() ?>