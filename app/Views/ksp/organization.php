<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?= view('ksp/_header', compact('version')) ?>

<?php
$intras = array_filter($rows, fn($r) => strtoupper($r['category']) === 'INTRACURRICULAR');
$cokus = array_filter($rows, fn($r) => strtoupper($r['category']) === 'COCURRICULAR');
$ekstras = array_filter($rows, fn($r) => strtoupper($r['category']) === 'EXTRACURRICULAR');

$totalMinutes = array_sum(array_column($rows, 'annual_minutes'));
$totalHours = round($totalMinutes / 60, 1);
$totalJp = round($totalMinutes / 45, 1); // estimasi 45 menit/JP
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3">
                    <i data-lucide="book-open" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= count($intras) ?></div>
                    <div class="small text-muted">Intrakurikuler</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 text-warning p-3">
                    <i data-lucide="sparkles" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-warning"><?= count($cokus) ?></div>
                    <div class="small text-muted">Kokurikuler (P5)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 text-success p-3">
                    <i data-lucide="trophy" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-success"><?= count($ekstras) ?></div>
                    <div class="small text-muted">Ekstrakurikuler</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-info bg-opacity-10 text-info p-3">
                    <i data-lucide="clock" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-info"><?= number_format($totalMinutes) ?> <span class="fs-6 fw-normal text-muted">menit</span></div>
                    <div class="small text-muted">Total Beban Belajar (~<?= $totalJp ?> JP)</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h5 class="fw-bold mb-1">Pengorganisasian Pembelajaran Sekolah</h5>
                <p class="small text-muted mb-0">Alokasi waktu, struktur intrakurikuler, projek kokurikuler, dan layanan ekstrakurikuler.</p>
            </div>
            <?php if ($canManage): ?>
                <button type="button" class="btn btn-primary rounded-pill px-4 btn-sm" data-bs-toggle="modal" data-bs-target="#createOrgModal">
                    <i data-lucide="plus-circle" class="me-1" style="width: 16px; height: 16px;"></i> Tambah Program / Struktur
                </button>
            <?php endif; ?>
        </div>

        <!-- 3-Pillar Tab Navigation -->
        <ul class="nav nav-pills mb-4 gap-2" id="orgTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-pill px-4" id="intra-tab" data-bs-toggle="pill" data-bs-target="#intra-pane" type="button" role="tab">
                    <i data-lucide="book-open" class="me-1" style="width: 16px; height: 16px;"></i> Intrakurikuler (<?= count($intras) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-4" id="coku-tab" data-bs-toggle="pill" data-bs-target="#coku-pane" type="button" role="tab">
                    <i data-lucide="sparkles" class="me-1" style="width: 16px; height: 16px;"></i> Kokurikuler P5 (<?= count($cokus) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-4" id="ekstra-tab" data-bs-toggle="pill" data-bs-target="#ekstra-pane" type="button" role="tab">
                    <i data-lucide="trophy" class="me-1" style="width: 16px; height: 16px;"></i> Ekstrakurikuler (<?= count($ekstras) ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="orgTabContent">
            <!-- INTRA -->
            <div class="tab-pane fade show active" id="intra-pane" role="tabpanel">
                <div class="row g-3">
                    <?php foreach ($intras as $item): ?>
                        <div class="col-md-6">
                            <div class="card border rounded-4 h-100 p-3 card-hover transition-all bg-white">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill px-3 py-1 font-monospace small">
                                        <?= esc($item['delivery_model']) ?>
                                    </span>
                                    <?php if ($item['annual_minutes']): ?>
                                        <span class="badge bg-light text-dark border rounded-pill px-2 py-1 small">
                                            <i data-lucide="clock" class="me-1 text-muted" style="width: 12px; height: 12px;"></i><?= number_format($item['annual_minutes']) ?> menit/thn
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h5 class="fw-bold text-dark mb-1"><?= esc($item['title']) ?></h5>
                                <p class="small text-muted mb-0 lh-base"><?= nl2br(esc($item['description'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($intras)): ?>
                        <div class="col-12 text-center text-muted py-5">
                            <i data-lucide="book-open" class="d-block mx-auto mb-2 text-muted" style="width: 36px; height: 36px;"></i>
                            Struktur intrakurikuler belum didaftarkan.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- COKU -->
            <div class="tab-pane fade" id="coku-pane" role="tabpanel">
                <div class="row g-3">
                    <?php foreach ($cokus as $item): ?>
                        <div class="col-md-6">
                            <div class="card border rounded-4 h-100 p-3 card-hover transition-all bg-white" style="border-left: 4px solid #f59e0b !important;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle rounded-pill px-3 py-1 font-monospace small">
                                        <?= esc($item['delivery_model']) ?>
                                    </span>
                                    <?php if ($item['annual_minutes']): ?>
                                        <span class="badge bg-light text-dark border rounded-pill px-2 py-1 small">
                                            <i data-lucide="clock" class="me-1 text-muted" style="width: 12px; height: 12px;"></i><?= number_format($item['annual_minutes']) ?> menit/thn
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h5 class="fw-bold text-dark mb-1"><?= esc($item['title']) ?></h5>
                                <p class="small text-muted mb-0 lh-base"><?= nl2br(esc($item['description'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($cokus)): ?>
                        <div class="col-12 text-center text-muted py-5">
                            <i data-lucide="sparkles" class="d-block mx-auto mb-2 text-muted" style="width: 36px; height: 36px;"></i>
                            Program kokurikuler P5 belum didaftarkan.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- EKSTRA -->
            <div class="tab-pane fade" id="ekstra-pane" role="tabpanel">
                <div class="row g-3">
                    <?php foreach ($ekstras as $item): ?>
                        <div class="col-md-6">
                            <div class="card border rounded-4 h-100 p-3 card-hover transition-all bg-white" style="border-left: 4px solid #10b981 !important;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-3 py-1 font-monospace small">
                                        <?= esc($item['delivery_model']) ?>
                                    </span>
                                    <?php if ($item['annual_minutes']): ?>
                                        <span class="badge bg-light text-dark border rounded-pill px-2 py-1 small">
                                            <i data-lucide="clock" class="me-1 text-muted" style="width: 12px; height: 12px;"></i><?= number_format($item['annual_minutes']) ?> menit/thn
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h5 class="fw-bold text-dark mb-1"><?= esc($item['title']) ?></h5>
                                <p class="small text-muted mb-0 lh-base"><?= nl2br(esc($item['description'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($ekstras)): ?>
                        <div class="col-12 text-center text-muted py-5">
                            <i data-lucide="trophy" class="d-block mx-auto mb-2 text-muted" style="width: 36px; height: 36px;"></i>
                            Layanan ekstrakurikuler belum didaftarkan.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($canManage): ?>
<!-- Modal Create Organization Program -->
<div class="modal fade" id="createOrgModal" tabindex="-1" aria-labelledby="createOrgModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary text-white p-2">
                        <i data-lucide="plus-circle" style="width: 20px; height: 20px;"></i>
                    </div>
                    <h5 class="modal-title fw-bold" id="createOrgModalLabel">Tambah Program Pengorganisasian Belajar</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="<?= base_url('education/ksp/'.$version['uuid'].'/organization') ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Pilar Kategori</label>
                            <select class="form-select rounded-3" name="category" required>
                                <option value="INTRACURRICULAR">INTRACURRICULAR (Mata Pelajaran)</option>
                                <option value="COCURRICULAR">COCURRICULAR (Projek Penguatan P5)</option>
                                <option value="EXTRACURRICULAR">EXTRACURRICULAR (Pengembangan Bakat/Karakter)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Model Pelaksanaan</label>
                            <select class="form-select rounded-3" name="delivery_model" required>
                                <option value="SUBJECT">SUBJECT (Reguler Mata Pelajaran)</option>
                                <option value="PROJECT">PROJECT (Projek Kolaboratif)</option>
                                <option value="BLOCK">BLOCK (Sistem Blok Waktu)</option>
                                <option value="INTEGRATED">INTEGRATED (Terintegrasi Tematik)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Alokasi Waktu (Menit/Tahun)</label>
                            <input type="number" class="form-control rounded-3" name="annual_minutes" placeholder="Contoh: 4320">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nama Program / Mata Pelajaran</label>
                            <input class="form-control rounded-3" name="title" placeholder="Contoh: Informatika & Literasi Digital Papua Pegunungan" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Deskripsi Mekanisme Pelaksanaan</label>
                            <textarea class="form-control rounded-3" rows="4" name="description" placeholder="Uraikan strategi pelaksanaan pembelajaran, integrasi nilai, dan asesmen..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Program</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.transition-all { transition: all 0.25s ease-in-out; }
.card-hover:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1) !important; }
</style>
<?= $this->endSection() ?>
