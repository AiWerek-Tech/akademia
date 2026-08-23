<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3" style="max-width:1000px">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="<?= base_url('reporting') ?>">Rapor</a></li><li class="breadcrumb-item active">Portofolio</li></ol></nav>
            <h1 class="h3 fw-bold text-gray-900 mb-0">Portofolio — <?= esc($student['full_name'] ?? '') ?></h1>
            <p class="text-muted mb-0">Kumpulan bukti karya terbaik, pertumbuhan, dan pencapaian siswa.</p>
        </div>
        <?php if (has_permission('reporting.manage')): ?>
            <button class="btn btn-primary shadow-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addPortfolioModal">
                <i data-lucide="plus" class="w-4 h-4 me-1"></i> Tambah Item
            </button>
        <?php endif; ?>
    </div>

    <?php if (empty($items)): ?>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="text-center py-5">
                <i data-lucide="briefcase" class="text-muted mb-3" style="width:48px;height:48px"></i>
                <p class="text-muted mb-0">Belum ada item portofolio.</p>
            </div>
        </div>
    <?php else: ?>
        <?php
        $grouped = [];
        foreach ($items as $item) {
            $grouped[$item['category']][] = $item;
        }
        $categoryLabels = [
            'BEST_WORK' => '🏆 Karya Terbaik',
            'GROWTH' => '📈 Bukti Pertumbuhan',
            'PROJECT' => '🔧 Proyek',
            'COCURRICULAR' => '🎭 Kokurikuler',
            'EXTRACURRICULAR' => '🏆 Ekstrakurikuler',
            'REFLECTION' => '📝 Refleksi',
        ];
        ?>
        <?php foreach ($grouped as $cat => $catItems): ?>
            <div class="mb-4">
                <h5 class="fw-bold mb-3"><?= $categoryLabels[$cat] ?? $cat ?></h5>
                <div class="row g-3">
                    <?php foreach ($catItems as $item): ?>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm rounded-4 h-100 <?= $item['is_highlighted'] ? 'border border-warning' : '' ?>">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-bold mb-0"><?= esc($item['title']) ?></h6>
                                        <?php if ($item['is_highlighted']): ?>
                                            <i data-lucide="star" class="text-warning" style="width:16px;height:16px"></i>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($item['description']): ?>
                                        <p class="text-muted small mb-2"><?= esc($item['description']) ?></p>
                                    <?php endif; ?>
                                    <div class="d-flex gap-2 mt-2">
                                        <?php if ($item['file_url']): ?>
                                            <a href="<?= esc($item['file_url'], 'attr') ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">Lihat File</a>
                                        <?php endif; ?>
                                        <form method="POST" action="<?= base_url('reporting/portfolio/' . $item['id'] . '/highlight') ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-<?= $item['is_highlighted'] ? 'warning' : 'secondary' ?> rounded-pill" title="Highlight">
                                                <i data-lucide="star" style="width:14px;height:14px"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="<?= base_url('reporting/portfolio/' . $item['id'] . '/delete') ?>" onsubmit="return confirm('Hapus item ini?')" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill" title="Hapus">
                                                <i data-lucide="trash-2" style="width:14px;height:14px"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Import from evidence (auto-pull, blueprint §11) -->
    <?php if (has_permission('reporting.manage') && ! empty($suggestions)): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 p-4 pb-2 d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">Rekomendasi Bukti Belajar</h5>
                <span class="text-muted small">Diambil otomatis dari bukti penilaian, kokurikuler, dan ekstrakurikuler</span>
            </div>
            <div class="card-body p-4 pt-3">
                <form method="POST" action="<?= base_url('reporting/portfolio/' . $student['id'] . '/import') ?>" id="importSuggestionForm">
                    <?= csrf_field() ?>
                    <div class="row g-3 mb-3">
                        <?php foreach ($suggestions as $s): ?>
                            <div class="col-md-6">
                                <div class="p-3 rounded-3 bg-light border">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="suggestions[]" value="<?= esc($s['key'], 'attr') ?>" id="sg-<?= md5($s['key']) ?>">
                                        <label class="form-check-label w-100" for="sg-<?= md5($s['key']) ?>">
                                            <span class="badge bg-light text-dark border" style="font-size:.6rem"><?= esc($s['category']) ?></span>
                                            <h6 class="fw-bold mb-1 mt-1"><?= esc($s['title']) ?></h6>
                                            <?php if ($s['description']): ?>
                                                <p class="text-muted small mb-0"><?= esc($s['description']) ?></p>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn btn-primary rounded-pill px-3">
                        <i data-lucide="download" class="w-4 h-4 me-1"></i> Tambahkan yang Dipilih
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Add Portfolio Modal -->
    <div class="modal fade" id="addPortfolioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content rounded-4">
                <form method="POST" action="<?= base_url('reporting/portfolio/' . $student['id'] . '/add') ?>">
                    <?= csrf_field() ?>
                    <div class="modal-header border-0">
                        <h5 class="modal-title fw-bold">Tambah Item Portofolio</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat ?>"><?= $cat ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Judul <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required placeholder="Judul item portofolio">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Deskripsi singkat..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">URL File (opsional)</label>
                            <input type="url" name="file_url" class="form-control" placeholder="https://...">
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_highlighted" value="1" class="form-check-input" id="highlightCheck">
                            <label class="form-check-label fw-semibold" for="highlightCheck">Tandai sebagai highlight</label>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Tambah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
