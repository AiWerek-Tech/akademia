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
                    <i data-lucide="trophy" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Phase 8 Ekstrakurikuler & Karakter
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mt-2 mb-1">Program Ekstrakurikuler</h1>
            <p class="text-muted mb-0">Pengembangan minat, bakat, kepemimpinan, kepanduan (Pathfinder), olahraga, seni, dan prestasi siswa.</p>
        </div>
        <?php if (has_permission('extracurricular.manage')): ?>
            <a href="<?= base_url('extracurricular/create') ?>" class="btn btn-primary shadow-sm px-3">
                <i data-lucide="plus" class="w-4 h-4 me-1"></i> Buat Program Baru
            </a>
        <?php endif; ?>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-4 bg-primary-subtle p-3 text-primary"><i data-lucide="trophy" style="width:24px;height:24px"></i></div>
                        <div>
                            <div class="text-muted text-xs text-uppercase tracking-wider fw-semibold">Total Program</div>
                            <div class="h3 fw-bold text-gray-900 mb-0"><?= $stats['total_programs'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-4 bg-success-subtle p-3 text-success"><i data-lucide="play-circle" style="width:24px;height:24px"></i></div>
                        <div>
                            <div class="text-muted text-xs text-uppercase tracking-wider fw-semibold">Program Berjalan (Aktif)</div>
                            <div class="h3 fw-bold text-success mb-0"><?= $stats['active_programs'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-4 bg-purple-subtle p-3 text-purple"><i data-lucide="users" style="width:24px;height:24px"></i></div>
                        <div>
                            <div class="text-muted text-xs text-uppercase tracking-wider fw-semibold">Total Partisipasi Siswa</div>
                            <div class="h3 fw-bold text-purple mb-0"><?= $stats['total_members'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= base_url('extracurricular') ?>" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Cari Program</label>
                    <input type="text" id="clientSearchProgram" class="form-control form-control-sm shadow-sm" placeholder="Ketik nama ekskul...">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Kategori</label>
                    <select name="category" class="form-select form-select-sm shadow-sm">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat ?>" <?= ($filters['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm shadow-sm">
                        <option value="">Semua Status</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary shadow-sm w-100"><i data-lucide="filter" class="w-3.5 h-3.5 me-1"></i> Filter</button>
                    <a href="<?= base_url('extracurricular') ?>" class="btn btn-sm btn-outline-secondary shadow-sm" title="Reset">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Programs Table -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <?php if (empty($programs)): ?>
                <div class="text-center py-5">
                    <i data-lucide="trophy" class="text-muted mb-3" style="width:48px;height:48px"></i>
                    <p class="text-muted mb-0">Belum ada program ekstrakurikuler terdaftar.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="extracurricularTable">
                        <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                            <tr>
                                <th class="ps-3 py-3">Program & Kode</th>
                                <th class="py-3">Kategori</th>
                                <th class="py-3">Pembina / Pelatih</th>
                                <th class="py-3">Jadwal & Lokasi</th>
                                <th class="text-center py-3">Status</th>
                                <th class="text-end pe-3 py-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($programs as $p): ?>
                                <?php
                                $catBadge = match($p['category']) {
                                    'SPORTS'            => ['class' => 'bg-danger-subtle text-danger border-danger-subtle', 'icon' => 'activity'],
                                    'ARTS'              => ['class' => 'bg-info-subtle text-info border-info-subtle', 'icon' => 'palette'],
                                    'SCOUT'             => ['class' => 'bg-warning-subtle text-warning border-warning-subtle', 'icon' => 'compass'],
                                    'ACADEMIC_OLYMPIAD' => ['class' => 'bg-purple-subtle text-purple border-purple-subtle', 'icon' => 'award'],
                                    'COMMUNITY_SERVICE' => ['class' => 'bg-success-subtle text-success border-success-subtle', 'icon' => 'heart'],
                                    default             => ['class' => 'bg-light text-dark border', 'icon' => 'trophy'],
                                };
                                ?>
                                <tr class="program-table-row">
                                    <td class="ps-3 py-3">
                                        <a href="<?= base_url('extracurricular/' . $p['id']) ?>" class="fw-bold text-gray-900 text-decoration-none program-title">
                                            <?= esc($p['title']) ?>
                                        </a>
                                        <?php if ($p['code']): ?>
                                            <span class="text-muted small ms-1">(<?= esc($p['code']) ?>)</span>
                                        <?php endif; ?>
                                        <?php if (! empty($p['description'])): ?>
                                            <div class="text-xs text-muted mt-0.5"><?= esc(mb_strimwidth($p['description'], 0, 50, '…')) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3">
                                        <span class="badge border <?= $catBadge['class'] ?> rounded-pill px-2 py-1 text-xs">
                                            <i data-lucide="<?= $catBadge['icon'] ?>" class="w-3 h-3 me-1 d-inline-block"></i> <?= esc($p['category']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 small">
                                        <i data-lucide="user" class="w-3.5 h-3.5 me-1 text-secondary d-inline-block"></i>
                                        <?= esc($p['coach_name'] ?? 'Belum ditentukan') ?>
                                    </td>
                                    <td class="py-3 small">
                                        <?php if ($p['meeting_day']): ?>
                                            <div><i data-lucide="calendar" class="w-3.5 h-3.5 me-1 text-secondary d-inline-block"></i><?= esc($p['meeting_day']) ?><?= $p['meeting_time'] ? ' (' . esc($p['meeting_time']) . ')' : '' ?></div>
                                        <?php endif; ?>
                                        <?php if ($p['location']): ?>
                                            <div class="text-xs text-muted mt-0.5"><i data-lucide="map-pin" class="w-3 h-3 me-1 text-secondary d-inline-block"></i><?= esc($p['location']) ?></div>
                                        <?php endif; ?>
                                        <?php if (! $p['meeting_day'] && ! $p['location']): ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center py-3">
                                        <?php
                                        $statusClass = match($p['status']) {
                                            'ACTIVE'    => 'bg-success-subtle text-success',
                                            'DRAFT'     => 'bg-warning-subtle text-warning',
                                            'COMPLETED' => 'bg-primary-subtle text-primary',
                                            'CANCELLED' => 'bg-danger-subtle text-danger',
                                            default     => 'bg-secondary-subtle text-secondary',
                                        };
                                        ?>
                                        <span class="badge <?= $statusClass ?> rounded-pill px-2.5 py-1 text-xs"><?= $p['status'] ?></span>
                                    </td>
                                    <td class="text-end pe-3 py-3">
                                        <a href="<?= base_url('extracurricular/' . $p['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            Detail & Kelola
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const searchInput = document.getElementById('clientSearchProgram');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('.program-table-row').forEach(row => {
                const title = row.querySelector('.program-title')?.textContent.toLowerCase() || '';
                row.style.display = title.includes(q) ? '' : 'none';
            });
        });
    }
});
</script>
<?= $this->endSection() ?>
