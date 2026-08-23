<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3" style="max-width:1100px">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= base_url('extracurricular/' . $program['id']) ?>"><?= esc($program['title']) ?></a></li>
                    <li class="breadcrumb-item active">Anggota</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-gray-900 mb-0">Daftar Anggota Program</h1>
            <p class="text-muted mb-0">Kelola pendaftaran siswa, peran kepemimpinan, dan keaktifan anggota ekskul.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary shadow-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                <i data-lucide="user-plus" class="w-4 h-4 me-1 d-inline-block"></i> Tambah Anggota
            </button>
            <a href="<?= base_url('extracurricular/' . $program['id']) ?>" class="btn btn-outline-secondary shadow-sm rounded-pill px-3">
                <i data-lucide="arrow-left" class="w-4 h-4 me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 p-4 pb-2">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-purple-subtle text-purple"><?= count($members) ?> Anggota Terdaftar</span>
                    <?php if (! empty($program['max_members'])): ?>
                        <span class="text-xs text-muted">Kapasitas: <?= count($members) ?> / <?= (int) $program['max_members'] ?></span>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2">
                    <input type="text" id="filterMemberInput" class="form-control form-control-sm shadow-sm" placeholder="Cari nama anggota..." style="max-width: 220px;">
                    <button type="button" class="btn btn-sm btn-outline-secondary shadow-sm" id="btnExportMembersCsv">
                        <i data-lucide="download" class="w-3.5 h-3.5 me-1"></i> Ekspor CSV
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <?php if (empty($members)): ?>
                <div class="text-center py-5"><p class="text-muted mb-0">Belum ada anggota terdaftar dalam program ini.</p></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="membersTable">
                        <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                            <tr>
                                <th class="ps-3 py-3" style="width: 40px;">#</th>
                                <th class="py-3">Nama Anggota</th>
                                <th class="py-3">Kelas</th>
                                <th class="py-3">Peran</th>
                                <th class="py-3">Tanggal Bergabung</th>
                                <th class="text-center py-3">Status</th>
                                <th class="text-end pe-3 py-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $i => $m): ?>
                                <tr class="member-table-row">
                                    <td class="ps-3 py-3 text-muted small"><?= $i + 1 ?></td>
                                    <td class="py-3">
                                        <div class="fw-bold text-gray-900 member-name"><?= esc($m['student_name'] ?? '—') ?></div>
                                        <?php if (! empty($m['notes'])): ?><div class="text-xs text-muted"><?= esc($m['notes']) ?></div><?php endif; ?>
                                    </td>
                                    <td class="py-3 small"><?= esc($m['classroom_name'] ?? '—') ?></td>
                                    <td class="py-3">
                                        <?php
                                        $roleBadge = match($m['role']) {
                                            'LEADER' => 'bg-warning-subtle text-warning border-warning-subtle',
                                            'COACH'  => 'bg-purple-subtle text-purple border-purple-subtle',
                                            default  => 'bg-light text-dark border',
                                        };
                                        ?>
                                        <span class="badge border <?= $roleBadge ?> rounded-pill px-2.5 py-1 text-xs">
                                            <?= esc($m['role']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 small text-muted"><?= $m['join_date'] ? date('d M Y', strtotime($m['join_date'])) : '—' ?></td>
                                    <td class="text-center py-3">
                                        <span class="badge bg-<?= $m['status'] === 'ACTIVE' ? 'success' : 'secondary' ?>-subtle text-<?= $m['status'] === 'ACTIVE' ? 'success' : 'secondary' ?> rounded-pill px-2.5 py-1 text-xs">
                                            <?= $m['status'] ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-3 py-3">
                                        <?php if ($m['status'] === 'ACTIVE'): ?>
                                            <form method="POST" action="<?= base_url('extracurricular/member/' . $m['id'] . '/remove') ?>" class="d-inline" onsubmit="return confirm('Yakin ingin mengeluarkan anggota ini?')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill shadow-sm px-2.5 py-1 text-xs">
                                                    Keluarkan
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Member Modal -->
    <div class="modal fade" id="addMemberModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <form method="POST" action="<?= base_url('extracurricular/' . $program['id'] . '/members/add') ?>">
                    <?= csrf_field() ?>
                    <div class="modal-header border-bottom px-4 py-3">
                        <h5 class="modal-title fw-bold">Tambah Anggota Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-xs text-muted fw-semibold mb-1">Pilih Siswa <span class="text-danger">*</span></label>
                            <select name="student_id" class="form-select form-select-sm shadow-sm" required>
                                <option value="">— Pilih Siswa —</option>
                                <?php foreach ($available as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= esc($s['full_name']) ?> (<?= esc($s['classroom_name'] ?? 'Tanpa Kelas') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-xs text-muted fw-semibold mb-1">Peran dalam Ekskul</label>
                                <select name="role" class="form-select form-select-sm shadow-sm">
                                    <?php foreach ($roles as $r): ?>
                                        <option value="<?= $r ?>"><?= $r ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-xs text-muted fw-semibold mb-1">Tanggal Bergabung</label>
                                <input type="date" name="join_date" class="form-control form-control-sm shadow-sm" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div>
                            <label class="form-label text-xs text-muted fw-semibold mb-1">Catatan Tambahan</label>
                            <textarea name="notes" class="form-control form-control-sm shadow-sm" rows="2" placeholder="cth. Minat khusus, nomor punggung/regu..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">Tambah Anggota</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const searchInput = document.getElementById('filterMemberInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('.member-table-row').forEach(row => {
                const name = row.querySelector('.member-name')?.textContent.toLowerCase() || '';
                row.style.display = name.includes(q) ? '' : 'none';
            });
        });
    }

    // CSV Exporter
    const btnCsv = document.getElementById('btnExportMembersCsv');
    if (btnCsv) {
        btnCsv.addEventListener('click', function() {
            const table = document.getElementById('membersTable');
            if (!table) return;

            let csvContent = '\uFEFF';
            const rows = table.querySelectorAll('tr');

            rows.forEach((row, rIdx) => {
                if (row.style.display === 'none') return;
                const cols = [];
                if (rIdx === 0) {
                    row.querySelectorAll('th').forEach(th => {
                        cols.push('"' + th.textContent.trim().replace(/"/g, '""') + '"');
                    });
                } else {
                    row.querySelectorAll('td').forEach(td => {
                        cols.push('"' + td.textContent.trim().replace(/\s+/g, ' ').replace(/"/g, '""') + '"');
                    });
                }
                csvContent += cols.join(',') + '\r\n';
            });

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.setAttribute('download', 'Anggota_Ekstrakurikuler_<?= url_title($program['title'], '_', true) ?>.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    }
});
</script>
<?= $this->endSection() ?>
