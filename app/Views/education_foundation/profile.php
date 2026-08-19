<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php 
$pageTitle = 'Delapan Dimensi Profil Lulusan'; 
$pageIcon = 'badge-check'; 
$pageDescription = 'Spine kompetensi dan karakter lulusan yang melandasi intrakurikuler, kokurikuler, ekstrakurikuler, dan asesmen.'; 
?>
<?= view('education_foundation/_page_header', compact('pageTitle','pageIcon','pageDescription')) ?>

<?php
$dimensionMeta = [
    'DIM_01' => ['icon' => 'sparkles', 'color' => '#6366f1', 'bg' => 'rgba(99, 102, 241, 0.1)', 'theme' => 'Spiritual & Karakter'],
    'DIM_02' => ['icon' => 'globe', 'color' => '#0ea5e9', 'bg' => 'rgba(14, 165, 233, 0.1)', 'theme' => 'Kewarganegaraan Global'],
    'DIM_03' => ['icon' => 'brain', 'color' => '#8b5cf6', 'bg' => 'rgba(139, 92, 246, 0.1)', 'theme' => 'Penalaran & Logika'],
    'DIM_04' => ['icon' => 'lightbulb', 'color' => '#f59e0b', 'bg' => 'rgba(245, 158, 11, 0.1)', 'theme' => 'Inovasi & Karya'],
    'DIM_05' => ['icon' => 'users', 'color' => '#10b981', 'bg' => 'rgba(16, 185, 129, 0.1)', 'theme' => 'Gotong Royong'],
    'DIM_06' => ['icon' => 'compass', 'color' => '#ec4899', 'bg' => 'rgba(236, 72, 153, 0.1)', 'theme' => 'Inisiatif & Refleksi'],
    'DIM_07' => ['icon' => 'heart-pulse', 'color' => '#ef4444', 'bg' => 'rgba(239, 68, 68, 0.1)', 'theme' => 'Kebugaran & Resiliensi'],
    'DIM_08' => ['icon' => 'book-open-check', 'color' => '#14b8a6', 'bg' => 'rgba(20, 184, 166, 0.1)', 'theme' => 'Kecakapan Dasar'],
];
?>

<div class="row g-4 mb-4">
    <?php foreach ($rows as $index => $row): ?>
        <?php 
        $meta = $dimensionMeta[$row['code']] ?? [
            'icon' => 'star', 
            'color' => '#3b82f6', 
            'bg' => 'rgba(59, 130, 246, 0.1)', 
            'theme' => 'Dimensi Lulusan'
        ];
        ?>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 position-relative overflow-hidden card-hover transition-all" style="border-top: 4px solid <?= $meta['color'] ?> !important;">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="rounded-4 p-3 d-flex align-items-center justify-content-center" style="background: <?= $meta['bg'] ?>; width: 50px; height: 50px;">
                                <i data-lucide="<?= $meta['icon'] ?>" style="width: 26px; height: 26px; color: <?= $meta['color'] ?>;"></i>
                            </div>
                            <span class="badge rounded-pill font-monospace px-3 py-1 text-dark bg-light border">
                                <?= esc($row['code']) ?>
                            </span>
                        </div>
                        <div class="text-uppercase small fw-bold mb-1" style="color: <?= $meta['color'] ?>; font-size: 0.75rem; letter-spacing: 0.5px;">
                            <?= esc($meta['theme']) ?>
                        </div>
                        <h5 class="fw-bold text-dark mb-2"><?= esc($row['name']) ?></h5>
                        <p class="small text-muted mb-0 lh-base">
                            <?= esc($row['description'] ?? 'Dimensi profil lulusan yang diintegrasikan ke dalam seluruh kegiatan belajar dan pengalaman nyata murid.') ?>
                        </p>
                    </div>
                    <div class="pt-3 mt-3 border-top d-flex justify-content-between align-items-center">
                        <span class="small text-muted">Integrasi IALOS</span>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1 small">
                            <i data-lucide="check" class="me-1" style="width: 12px; height: 12px;"></i>Aktif
                        </span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm rounded-4 bg-light p-4">
    <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3">
            <i data-lucide="info" style="width: 24px; height: 24px;"></i>
        </div>
        <div>
            <h6 class="fw-bold mb-1">Penyelarasan Spine Pembelajaran</h6>
            <p class="small text-muted mb-0">Delapan dimensi profil lulusan ini otomatis terhubung saat guru dan tim kurikulum menyusun Tujuan Pembelajaran (TP), modul proyek kokurikuler, ekstrakurikuler, dan rubrik portofolio murid.</p>
        </div>
    </div>
</div>

<style>
.transition-all { transition: all 0.25s ease-in-out; }
.card-hover:hover { transform: translateY(-4px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important; }
</style>
<?= $this->endSection() ?>
