<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<?php
$ipoo = $ipooHealth ?? [
    'overall_score'   => 3.5,
    'overall_percent' => 70,
    'status'          => ['label' => 'BAIK', 'class' => 'bg-primary text-white'],
    'aspects'         => [],
];
?>
<div class="container-fluid px-0 px-md-3" style="max-width:1050px">
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
                    <li class="breadcrumb-item active">Evaluasi Program</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-gray-900 mb-0">Evaluasi Mutu & Kinerja Program</h1>
            <p class="text-muted mb-0">Kerangka evaluasi kualitas 4 pilar: INPUT → PROCESS → OUTPUT → OUTCOME.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('extracurricular/' . $program['id']) ?>" class="btn btn-outline-secondary shadow-sm rounded-pill px-3">
                <i data-lucide="arrow-left" class="w-4 h-4 me-1"></i> Kembali ke Program
            </a>
        </div>
    </div>

    <!-- IPOO Health Quality Scorecard -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                <div>
                    <h6 class="fw-bold mb-1 text-gray-900"><i data-lucide="activity" class="w-4 h-4 me-1 text-purple"></i> Indeks Kebugaran Mutu (IPOO Scorecard)</h6>
                    <small class="text-muted">Skor ketercapaian sumber daya, kehadiran sesi, prestasi anggota, dan dampak karakter.</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-xs text-muted">Indeks Mutu:</span>
                    <span class="badge <?= $ipoo['status']['class'] ?> px-3 py-2 rounded-pill fw-bold">
                        <?= $ipoo['overall_score'] ?> / 5.0 (<?= $ipoo['overall_percent'] ?>%) · <?= $ipoo['status']['label'] ?>
                    </span>
                </div>
            </div>

            <div class="row g-3">
                <?php
                $aspectIcons = [
                    'INPUT'   => 'box',
                    'PROCESS' => 'cog',
                    'OUTPUT'  => 'package-check',
                    'OUTCOME' => 'award',
                ];
                foreach (['INPUT', 'PROCESS', 'OUTPUT', 'OUTCOME'] as $asp):
                    $aspData = $ipoo['aspects'][$asp] ?? ['rating' => 3.5, 'percent' => 70, 'label' => $asp];
                ?>
                    <div class="col-md-3">
                        <div class="border rounded-4 p-3 bg-light-subtle h-100">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-xs fw-bold text-uppercase tracking-wider text-muted">
                                    <i data-lucide="<?= $aspectIcons[$asp] ?? 'circle' ?>" class="w-3.5 h-3.5 me-1 text-purple"></i> <?= $asp ?>
                                </span>
                                <span class="fw-bold small text-gray-900"><?= $aspData['rating'] ?>/5</span>
                            </div>
                            <div class="progress mb-2" style="height: 6px;">
                                <div class="progress-bar <?= $aspData['percent'] >= 80 ? 'bg-success' : ($aspData['percent'] >= 60 ? 'bg-primary' : 'bg-warning') ?>" style="width: <?= $aspData['percent'] ?>%;"></div>
                            </div>
                            <div class="text-xs text-muted" style="font-size: 11px;">
                                <?= esc($aspData['label']) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Existing Evaluations -->
    <?php if (! empty($evaluations)): ?>
        <div class="row g-3 mb-4">
            <?php foreach ($evaluations as $ev): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white border-0 p-4 pb-2 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-0 text-gray-900">Periode Evaluasi: <?= esc($ev['evaluation_period']) ?></h6>
                                <small class="text-muted">Tanggal: <?= date('d M Y', strtotime($ev['created_at'])) ?></small>
                            </div>
                            <?php if ($ev['overall_rating']): ?>
                                <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1.5"><?= esc($ev['overall_rating']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-4 pt-0">
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <div class="rounded-3 border p-3 bg-white">
                                        <div class="text-xs fw-bold text-primary text-uppercase mb-1"><i data-lucide="box" class="w-3 h-3 me-1"></i> INPUT — Sumber Daya & Pelatih</div>
                                        <div class="small text-muted"><?= nl2br(esc($ev['input_data'] ?? '—')) ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="rounded-3 border p-3 bg-white">
                                        <div class="text-xs fw-bold text-warning text-uppercase mb-1"><i data-lucide="cog" class="w-3 h-3 me-1"></i> PROCESS — Pelaksanaan & Presensi</div>
                                        <div class="small text-muted"><?= nl2br(esc($ev['process_data'] ?? '—')) ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="rounded-3 border p-3 bg-white">
                                        <div class="text-xs fw-bold text-success text-uppercase mb-1"><i data-lucide="package-check" class="w-3 h-3 me-1"></i> OUTPUT — Capaian & Prestasi</div>
                                        <div class="small text-muted"><?= nl2br(esc($ev['output_data'] ?? '—')) ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="rounded-3 border p-3 bg-white">
                                        <div class="text-xs fw-bold text-info text-uppercase mb-1"><i data-lucide="award" class="w-3 h-3 me-1"></i> OUTCOME — Dampak Karakter & Minat</div>
                                        <div class="small text-muted"><?= nl2br(esc($ev['outcome_data'] ?? '—')) ?></div>
                                    </div>
                                </div>
                                <?php if ($ev['findings']): ?>
                                    <div class="col-12"><div class="small"><strong>Temuan & Refleksi:</strong> <?= nl2br(esc($ev['findings'])) ?></div></div>
                                <?php endif; ?>
                                <?php if ($ev['recommendations']): ?>
                                    <div class="col-12"><div class="small"><strong>Rekomendasi Tindak Lanjut:</strong> <?= nl2br(esc($ev['recommendations'])) ?></div></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- New / Edit Evaluation Form -->
    <div class="card border-0 shadow-sm rounded-4 mb-5">
        <div class="card-header bg-white border-0 p-4 pb-2">
            <h5 class="fw-bold mb-0 text-gray-900"><i data-lucide="plus-circle" class="w-4 h-4 me-1 text-purple"></i> Tambah / Perbarui Evaluasi Tahunan</h5>
        </div>
        <form method="POST" action="<?= base_url('extracurricular/' . $program['id'] . '/evaluations/save') ?>">
            <?= csrf_field() ?>
            <div class="card-body p-4 pt-2">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-xs text-muted fw-semibold mb-1">Periode Evaluasi <span class="text-danger">*</span></label>
                        <input type="text" name="evaluation_period" class="form-control form-control-sm shadow-sm" required placeholder="Contoh: 2025/2026 Semester Ganjil">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-xs text-muted fw-semibold mb-1">Rating Keseluruhan</label>
                        <select name="overall_rating" class="form-select form-select-sm shadow-sm">
                            <option value="">— Pilih Rating —</option>
                            <?php foreach ($ratings as $r): ?>
                                <option value="<?= $r ?>"><?= $r ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-xs text-muted fw-semibold mb-1">INPUT — Sumber Daya & Pelatih</label>
                        <textarea name="input_data" class="form-control form-control-sm shadow-sm" rows="3" placeholder="Ketersediaan guru pembina, sarana alat, fasilitas tempat latihan, pendanaan..."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-xs text-muted fw-semibold mb-1">PROCESS — Pelaksanaan & Presensi</label>
                        <textarea name="process_data" class="form-control form-control-sm shadow-sm" rows="3" placeholder="Kedisiplinan jadwal, tingkat kehadiran rata-rata siswa, dinamika latihan..."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-xs text-muted fw-semibold mb-1">OUTPUT — Capaian & Prestasi</label>
                        <textarea name="output_data" class="form-control form-control-sm shadow-sm" rows="3" placeholder="Jumlah sesi terlaksana, karya/produk, partisipasi lomba, sertifikat..."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-xs text-muted fw-semibold mb-1">OUTCOME — Dampak Karakter</label>
                        <textarea name="outcome_data" class="form-control form-control-sm shadow-sm" rows="3" placeholder="Peningkatan kepercayaan diri, sportivitas, jiwa kepemimpinan, kerja sama..."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-xs text-muted fw-semibold mb-1">Temuan Utama</label>
                        <textarea name="findings" class="form-control form-control-sm shadow-sm" rows="2" placeholder="Hambatan atau hal positif yang ditemukan..."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-xs text-muted fw-semibold mb-1">Rekomendasi Tahun Depan</label>
                        <textarea name="recommendations" class="form-control form-control-sm shadow-sm" rows="2" placeholder="Saran perbaikan untuk periode ajaran berikutnya..."></textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white border-top d-flex justify-content-end p-3">
                <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i data-lucide="save" class="w-4 h-4 me-1 d-inline-block"></i> Simpan Evaluasi Mutu
                </button>
            </div>
        </form>
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
