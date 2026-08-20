<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3" style="max-width:1200px">
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
                    <li class="breadcrumb-item active">Kompetensi & Prestasi</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-gray-900 mb-0">Standar Kompetensi & Pencapaian Lencana</h1>
            <p class="text-muted mb-0">Kelola kurikulum keterampilan, lencana kepanduan (Pathfinder), tanda kecakapan, dan rekam jejak penghargaan siswa.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-outline-primary shadow-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addCompetencyModal">
                <i data-lucide="plus" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Tambah Kompetensi
            </button>
            <button class="btn btn-primary shadow-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addAchievementModal">
                <i data-lucide="award" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Catat Prestasi / Lencana
            </button>
            <a href="<?= base_url('extracurricular/' . $program['id']) ?>" class="btn btn-outline-secondary shadow-sm rounded-pill px-3">
                <i data-lucide="arrow-left" class="w-4 h-4 me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Competencies List -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 p-4 pb-2 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-gray-900"><i data-lucide="check-circle" class="w-4 h-4 me-1 text-purple"></i> Daftar Standar Keterampilan</h5>
                    <span class="badge bg-purple-subtle text-purple"><?= count($competencies) ?> Keterampilan</span>
                </div>
                <div class="card-body p-4 pt-2">
                    <?php if (empty($competencies)): ?>
                        <p class="text-muted mb-0 py-3"><em>Belum ada kompetensi terdaftar. Silakan tambah standar keterampilan baru.</em></p>
                    <?php else: ?>
                        <?php foreach ($competencies as $c): ?>
                            <div class="d-flex align-items-start gap-2 mb-3 p-3 rounded-3 border bg-light-subtle">
                                <span class="badge bg-purple-subtle text-purple border fw-bold" style="min-width:38px"><?= esc($c['code']) ?></span>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-gray-900 small"><?= esc($c['name']) ?></div>
                                    <?php if ($c['description']): ?><div class="text-muted text-xs mt-0.5"><?= esc($c['description']) ?></div><?php endif; ?>
                                </div>
                                <span class="badge bg-light text-dark border text-xs" style="font-size:.68rem"><?= $c['assessment_type'] ?></span>
                                <form method="POST" action="<?= base_url('extracurricular/competency/' . $c['id'] . '/delete') ?>" onsubmit="return confirm('Nonaktifkan kompetensi ini?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0 p-1" title="Nonaktifkan"><i data-lucide="trash-2" style="width:14px;height:14px"></i></button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Achievements List -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 p-4 pb-2 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-gray-900"><i data-lucide="award" class="w-4 h-4 me-1 text-purple"></i> Rekam Jejak Prestasi & Lencana Siswa</h5>
                    <span class="badge bg-success-subtle text-success"><?= count($achievements) ?> Penghargaan</span>
                </div>
                <div class="card-body p-4 pt-2">
                    <?php if (empty($achievements)): ?>
                        <p class="text-muted mb-0 py-3"><em>Belum ada prestasi atau lencana kecakapan tercatat.</em></p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle mb-0">
                                <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                                    <tr>
                                        <th class="py-2.5">Siswa</th>
                                        <th class="py-2.5">Kompetensi / Lencana</th>
                                        <th class="py-2.5">Tanggal</th>
                                        <th class="py-2.5 text-center">Level</th>
                                        <th class="py-2.5">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($achievements as $a): ?>
                                        <tr>
                                            <td class="fw-bold text-gray-900 py-2.5 small"><?= esc($a['student_name'] ?? '—') ?></td>
                                            <td class="py-2.5 small"><?= esc($a['competency_name'] ?? 'Prestasi Khusus') ?></td>
                                            <td class="py-2.5 text-muted small"><?= $a['achieved_date'] ? date('d M Y', strtotime($a['achieved_date'])) : '—' ?></td>
                                            <td class="py-2.5 text-center">
                                                <?php if ($a['level']): ?>
                                                    <span class="badge bg-success-subtle text-success rounded-pill px-2 py-0.5 text-xs"><?= esc($a['level']) ?></span>
                                                <?php else: ?>—<?php endif; ?>
                                            </td>
                                            <td class="text-muted small py-2.5"><?= esc($a['remarks'] ?? '—') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Competency Modal -->
    <div class="modal fade" id="addCompetencyModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <form method="POST" action="<?= base_url('extracurricular/' . $program['id'] . '/competencies/add') ?>">
                    <?= csrf_field() ?>
                    <div class="modal-header border-bottom px-4 py-3">
                        <h5 class="modal-title fw-bold">Tambah Standar Keterampilan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <label class="form-label text-xs text-muted fw-semibold mb-1">Kode <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control form-control-sm shadow-sm" required placeholder="K01 / P01">
                            </div>
                            <div class="col-8">
                                <label class="form-label text-xs text-muted fw-semibold mb-1">Nama Keterampilan <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm shadow-sm" required placeholder="cth. Teknik Dribbling & Passing">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-xs text-muted fw-semibold mb-1">Deskripsi Kriteria Penguasaan</label>
                            <textarea name="description" class="form-control form-control-sm shadow-sm" rows="2" placeholder="Indikator pencapaian..."></textarea>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label text-xs text-muted fw-semibold mb-1">Tipe Penilaian</label>
                                <select name="assessment_type" class="form-select form-select-sm shadow-sm">
                                    <?php foreach ($types as $t): ?>
                                        <option value="<?= $t ?>"><?= $t ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-xs text-muted fw-semibold mb-1">Urutan</label>
                                <input type="number" name="sort_order" class="form-control form-control-sm shadow-sm" value="0" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">Simpan Kompetensi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Achievement Modal -->
    <div class="modal fade" id="addAchievementModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <form method="POST" action="<?= base_url('extracurricular/' . $program['id'] . '/achievements/add') ?>">
                    <?= csrf_field() ?>
                    <div class="modal-header border-bottom px-4 py-3">
                        <h5 class="modal-title fw-bold">Catat Prestasi & Penghargaan Siswa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-xs text-muted fw-semibold mb-1">Pilih Anggota Siswa <span class="text-danger">*</span></label>
                            <select name="member_id" class="form-select form-select-sm shadow-sm" required>
                                <option value="">— Pilih Anggota —</option>
                                <?php foreach ($members as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= esc($m['student_name'] ?? '—') ?> (<?= esc($m['classroom_name'] ?? 'Tanpa Kelas') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-xs text-muted fw-semibold mb-1">Standar Kompetensi Terkait</label>
                            <select name="competency_id" class="form-select form-select-sm shadow-sm">
                                <option value="">— Penghargaan Bebas / Non-Kompetensi —</option>
                                <?php foreach ($competencies as $c): ?>
                                    <option value="<?= $c['id'] ?>">[<?= esc($c['code']) ?>] <?= esc($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label text-xs text-muted fw-semibold mb-1">Tanggal Raihan</label>
                                <input type="date" name="achieved_date" class="form-control form-control-sm shadow-sm" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-xs text-muted fw-semibold mb-1">Tingkat / Level</label>
                                <select name="level" class="form-select form-select-sm shadow-sm">
                                    <option value="">— Pilih Level —</option>
                                    <?php foreach ($ratings as $r): ?>
                                        <option value="<?= $r ?>"><?= $r ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="form-label text-xs text-muted fw-semibold mb-1">Catatan Prestasi / Nama Lencana</label>
                            <textarea name="remarks" class="form-control form-control-sm shadow-sm" rows="2" placeholder="cth. Juara 1 Turnamen Futsal Antar Kelas / Lencana Bintang Pionering..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">Simpan Penghargaan</button>
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
});
</script>
<?= $this->endSection() ?>
