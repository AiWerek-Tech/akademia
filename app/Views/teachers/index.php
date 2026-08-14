<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1 text-slate-800 d-flex align-items-center gap-2">
                    <i data-lucide="users" class="text-primary" style="width: 24px; height: 24px;"></i>
                    Master Guru Global
                </h4>
                <p class="text-muted fs-7 mb-0">Pengelolaan master data guru terpadu unit SMP dan SMA</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if (has_permission('teachers.import')): ?>
                    <a href="<?= base_url('imports/master/template/teachers') ?>" class="btn btn-outline-secondary rounded-3 btn-sm px-3 d-flex align-items-center gap-2" title="Unduh template resmi yang sama dengan halaman Import Master">
                        <i data-lucide="file-spreadsheet" style="width:16px;height:16px"></i><span>Template</span>
                    </a>
                    <a href="<?= base_url('imports/master?type=TEACHERS') ?>" class="btn btn-outline-primary rounded-3 btn-sm px-3 d-flex align-items-center gap-2"><i data-lucide="upload" style="width:16px;height:16px"></i><span>Import Excel</span></a>
                <?php endif; ?>
                <?php if (has_permission('teachers.export')): ?>
                    <a href="<?= base_url('teachers/export?unit_id=' . ($filters['unit_id'] ?? '')) ?>" class="btn btn-outline-success rounded-3 btn-sm px-3 d-flex align-items-center gap-2">
                        <i data-lucide="download" style="width: 16px; height: 16px;"></i>
                        <span>Export Data</span>
                    </a>
                <?php endif; ?>
                <?php if (has_permission('teachers.manage')): ?>
                    <a href="<?= base_url('teachers/create') ?>" class="btn btn-primary rounded-3 btn-sm px-3 d-flex align-items-center gap-2">
                        <i data-lucide="user-plus" style="width: 16px; height: 16px;"></i>
                        <span>Tambah Guru</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= base_url('teachers') ?>" class="row g-3 align-items-center">
            <?php if (!empty($filters['per_page'])): ?>
                <input type="hidden" name="per_page" value="<?= esc($filters['per_page']) ?>">
            <?php endif; ?>
            <div class="col-md-3">
                <select name="unit_id" class="form-select form-select-sm rounded-3">
                    <option value="">-- Semua Unit --</option>
                    <?php foreach ($units as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (string)($filters['unit_id'] ?? '') === (string)$u['id'] ? 'selected' : '' ?>>
                            <?= esc($u['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="profile_status" class="form-select form-select-sm rounded-3">
                    <option value="">-- Semua Profil Status --</option>
                    <option value="INCOMPLETE" <?= ($filters['profile_status'] ?? '') === 'INCOMPLETE' ? 'selected' : '' ?>>Belum Lengkap</option>
                    <option value="PARTIAL" <?= ($filters['profile_status'] ?? '') === 'PARTIAL' ? 'selected' : '' ?>>Sebagian</option>
                    <option value="COMPLETE" <?= ($filters['profile_status'] ?? '') === 'COMPLETE' ? 'selected' : '' ?>>Lengkap</option>
                    <option value="VERIFIED" <?= ($filters['profile_status'] ?? '') === 'VERIFIED' ? 'selected' : '' ?>>Terverifikasi</option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm rounded-3" placeholder="Cari NIP, NIK, NIPG, atau Nama Guru..." value="<?= esc($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm rounded-3 w-100">Filter</button>
                <a href="<?= base_url('teachers') ?>" class="btn btn-light btn-sm rounded-3">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table Card -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <?php
        $currentPage = (int)($pager->getCurrentPage() ?? 1);
        $perPageVal = $perPage ?? '10';
        $itemsPerPage = $perPageVal === 'all' ? (count($teachers) ?: 1) : (int)$perPageVal;
        $totalRecords = $pager->getTotal() ?? count($teachers);
        $startNo = empty($teachers) ? 0 : (($currentPage - 1) * $itemsPerPage) + 1;
        $endNo = empty($teachers) ? 0 : min($startNo + count($teachers) - 1, $totalRecords);
        ?>
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="fs-8 text-muted">
                Menampilkan <span class="fw-bold text-dark"><?= $startNo ?></span> – <span class="fw-bold text-dark"><?= $endNo ?></span> dari <span class="fw-bold text-dark"><?= $totalRecords ?></span> data
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="fs-8 text-muted mb-0">Tampilkan:</label>
                <select class="form-select form-select-sm rounded-3 py-1 ps-2 pe-4 fs-8 border-light-subtle" onchange="location = this.value;" style="width: auto;">
                    <?php
                    $queryParams = $filters;
                    unset($queryParams['per_page']);
                    $buildUrl = function($pp) use ($queryParams) {
                        $p = array_merge($queryParams, ['per_page' => $pp]);
                        return base_url('teachers') . '?' . http_build_query(array_filter($p, fn($v) => $v !== null && $v !== ''));
                    };
                    ?>
                    <option value="<?= $buildUrl(10) ?>" <?= $perPageVal === '10' ? 'selected' : '' ?>>10 baris</option>
                    <option value="<?= $buildUrl(20) ?>" <?= $perPageVal === '20' ? 'selected' : '' ?>>20 baris</option>
                    <option value="<?= $buildUrl(50) ?>" <?= $perPageVal === '50' ? 'selected' : '' ?>>50 baris</option>
                    <option value="<?= $buildUrl('all') ?>" <?= $perPageVal === 'all' ? 'selected' : '' ?>>Semua</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="text-uppercase text-muted fs-8 fw-bold bg-light rounded-3">
                        <th class="text-center ps-3" style="width: 55px;">No.</th>
                        <th>Guru</th>
                        <th>NIP / NIK</th>
                        <th>Status Pegawai</th>
                        <th>Unit Penugasan</th>
                        <th>Kelengkapan Profil</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($teachers)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">Belum ada data guru terdaftar.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($teachers as $idx => $t): ?>
                            <tr>
                                <td class="text-center fw-semibold text-secondary fs-8 ps-3"><?= $startNo + $idx ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px; min-width: 40px;">
                                            <?= esc(mb_substr($t['full_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <a href="<?= base_url('teachers/' . $t['uuid']) ?>" class="fw-bold text-slate-800 text-decoration-none d-block">
                                                <?= esc(($t['title_prefix'] ? $t['title_prefix'] . ' ' : '') . $t['full_name'] . ($t['degree_suffix'] ? ', ' . $t['degree_suffix'] : '')) ?>
                                            </a>
                                            <span class="fs-8 text-muted"><?= esc($t['email'] ?? ($t['phone'] ?? '-')) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="d-block font-monospace fs-8"><?= esc($t['nip'] ?: ($t['nik'] ?: ($t['employee_number'] ?: '-'))) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-dark px-2.5 py-1 rounded-pill fs-8">
                                        <?= esc($t['employment_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($t['assignments'])): ?>
                                        <?php foreach ($t['assignments'] as $a): ?>
                                            <span class="badge bg-info bg-opacity-10 text-info px-2 py-0.5 rounded-pill fs-9 me-1">
                                                <?= esc($a['unit_code'] ?? $a['unit_name'] ?? ('Unit #' . $a['unit_id'])) ?> (<?= esc($a['assignment_type']) ?>)
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted fs-8">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php $c = $t['completeness'] ?? ['score' => 0, 'status' => 'INCOMPLETE']; ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar <?= $c['score'] >= 90 ? 'bg-success' : ($c['score'] >= 60 ? 'bg-warning' : 'bg-danger') ?>" style="width: <?= $c['score'] ?>%"></div>
                                        </div>
                                        <span class="fs-8 fw-semibold"><?= $c['score'] ?>%</span>
                                    </div>
                                    <span class="badge <?= $c['status'] === 'VERIFIED' ? 'bg-success' : ($c['status'] === 'COMPLETE' ? 'bg-info' : 'bg-warning') ?> bg-opacity-10 text-dark fs-9 mt-1">
                                        <?= esc($c['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border-0 rounded-circle" type="button" data-bs-toggle="dropdown">
                                            <i data-lucide="more-vertical" style="width: 16px; height: 16px;"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 fs-8">
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2" href="<?= base_url('teachers/' . $t['uuid']) ?>">
                                                    <i data-lucide="eye" style="width: 14px; height: 14px;"></i> Lihat Profil
                                                </a>
                                            </li>
                                            <?php if (has_permission('teachers.manage')): ?>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-2" href="<?= base_url('teachers/' . $t['uuid'] . '/edit') ?>">
                                                        <i data-lucide="edit" style="width: 14px; height: 14px;"></i> Edit
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            <?php
                                                $hasUserAccount = in_array((int)$t['id'], $userLinkedTeacherIds ?? [], true);
                                            ?>
                                            <?php if ($hasUserAccount): ?>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <span class="dropdown-item d-flex align-items-center gap-2 text-success disabled">
                                                        <i data-lucide="check-circle" style="width: 14px; height: 14px;"></i> Akun User Ada
                                                    </span>
                                                </li>
                                            <?php elseif (has_permission('users.manage')): ?>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-2 text-primary fw-bold" href="<?= base_url('users/create?teacher_id=' . $t['id']) ?>">
                                                        <i data-lucide="user-plus" style="width: 14px; height: 14px;"></i> Buat Akun Login
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($perPageVal !== 'all' && !empty($teachers)): ?>
            <div class="d-flex justify-content-between align-items-center mt-4 pt-2 border-top flex-wrap gap-2">
                <div class="fs-8 text-muted">
                    Halaman <span class="fw-bold text-dark"><?= $currentPage ?></span> dari <span class="fw-bold text-dark"><?= max(1, ceil($totalRecords / $itemsPerPage)) ?></span>
                </div>
                <div class="pagination-container fs-8">
                    <?= $pager->links() ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
