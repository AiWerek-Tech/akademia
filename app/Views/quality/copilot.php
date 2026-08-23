<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3" style="max-width:900px">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="mb-4">
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="<?= base_url('quality') ?>">Kualitas</a></li><li class="breadcrumb-item active">AI Copilot</li></ol></nav>
        <h1 class="h3 fw-bold text-gray-900 mb-1">AI Copilot</h1>
        <p class="text-muted mb-0">Bantuan AI untuk merangkum, membuat draft, dan memberi rekomendasi. Output selalu memerlukan review guru.</p>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3">
                <div class="h4 fw-bold text-primary mb-0"><?= $stats['total'] ?></div>
                <div class="text-muted small">Total Generate</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3">
                <div class="h4 fw-bold text-success mb-0"><?= $stats['accepted'] ?></div>
                <div class="text-muted small">Diterima</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3">
                <div class="h4 fw-bold text-danger mb-0"><?= $stats['rejected'] ?></div>
                <div class="text-muted small">Ditolak</div>
            </div>
        </div>
    </div>

    <!-- Generator -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 p-4 pb-2"><h5 class="fw-bold mb-0">Generate Draft</h5></div>
        <div class="card-body p-4 pt-0">
            <form method="POST" action="<?= base_url('quality/copilot/generate') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tipe Prompt <span class="text-danger">*</span></label>
                        <select name="prompt_type" class="form-select" required id="promptType">
                            <?php foreach ($promptTypes as $key => $label): ?>
                                <option value="<?= $key ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 dynamic-field" data-prompt="reflection_draft,supervision_summary">
                        <label class="form-label fw-semibold">Mata Pelajaran</label>
                        <input type="text" name="subject" class="form-control" placeholder="Contoh: Informatika">
                    </div>
                    <div class="col-md-6 dynamic-field" data-prompt="reflection_draft">
                        <label class="form-label fw-semibold">Penguasaan Kompetensi (%)</label>
                        <input type="number" name="mastery_pct" class="form-control" placeholder="75" min="0" max="100">
                    </div>
                    <div class="col-md-6 dynamic-field" data-prompt="reflection_draft">
                        <label class="form-label fw-semibold">Kehadiran (%)</label>
                        <input type="number" name="attendance_pct" class="form-control" placeholder="90" min="0" max="100">
                    </div>
                    <div class="col-md-6 dynamic-field" data-prompt="supervision_summary">
                        <label class="form-label fw-semibold">Nama Guru</label>
                        <input type="text" name="teacher_name" class="form-control" placeholder="Nama guru">
                    </div>
                    <div class="col-md-6 dynamic-field" data-prompt="supervision_summary">
                        <label class="form-label fw-semibold">Rating</label>
                        <select name="rating" class="form-select">
                            <option value="EXCELLENT">EXCELLENT</option>
                            <option value="GOOD">GOOD</option>
                            <option value="SATISFACTORY">SATISFACTORY</option>
                            <option value="NEEDS_IMPROVEMENT">NEEDS_IMPROVEMENT</option>
                        </select>
                    </div>
                    <div class="col-md-6 dynamic-field" data-prompt="improvement_idea">
                        <label class="form-label fw-semibold">Area Perbaikan</label>
                        <input type="text" name="area" class="form-control" placeholder="Contoh: assessment formatif">
                    </div>
                    <div class="col-md-6 dynamic-field" data-prompt="class_summary">
                        <label class="form-label fw-semibold">Nama Kelas</label>
                        <input type="text" name="class_name" class="form-control" placeholder="Contoh: XII IPA 1">
                    </div>
                    <div class="col-md-6 dynamic-field" data-prompt="class_summary">
                        <label class="form-label fw-semibold">Rata-rata Nilai</label>
                        <input type="number" name="avg_score" class="form-control" placeholder="82" min="0" max="100">
                    </div>
                    <div class="col-md-6 dynamic-field" data-prompt="class_summary">
                        <label class="form-label fw-semibold">Jumlah Siswa</label>
                        <input type="number" name="total_students" class="form-control" placeholder="32" min="0">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary rounded-pill px-4"><i data-lucide="sparkles" class="w-4 h-4 me-1 d-inline-block"></i> Generate Draft</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Result -->
    <?php if (session()->getFlashdata('ai_result')): ?>
        <?php $aiResult = session()->getFlashdata('ai_result'); $aiId = session()->getFlashdata('ai_id'); ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-info">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-2"><i data-lucide="bot" class="text-info me-1" style="width:18px;height:18px"></i> Hasil Generate</h6>
                <div class="p-3 rounded-3 bg-light mb-3">
                    <p class="mb-0"><?= nl2br(esc($aiResult)) ?></p>
                </div>
                <div class="d-flex gap-2 mb-3">
                    <form method="POST" action="<?= base_url('quality/copilot/' . $aiId . '/accept') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-success rounded-pill px-3">Terima</button>
                    </form>
                    <form method="POST" action="<?= base_url('quality/copilot/' . $aiId . '/reject') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="reason" value="Tidak sesuai kebutuhan">
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">Tolak</button>
                    </form>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">Feedback:</span>
                    <form method="POST" action="<?= base_url('quality/copilot/' . $aiId . '/feedback') ?>" class="d-inline-flex gap-1">
                        <?= csrf_field() ?>
                        <input type="hidden" name="feedback_rating" value="USEFUL">
                        <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill" style="font-size:.7rem">👍 Berguna</button>
                    </form>
                    <form method="POST" action="<?= base_url('quality/copilot/' . $aiId . '/feedback') ?>" class="d-inline-flex gap-1">
                        <?= csrf_field() ?>
                        <input type="hidden" name="feedback_rating" value="NEEDS_CORRECTION">
                        <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill" style="font-size:.7rem">🔧 Perlu Koreksi</button>
                    </form>
                    <form method="POST" action="<?= base_url('quality/copilot/' . $aiId . '/feedback') ?>" class="d-inline-flex gap-1">
                        <?= csrf_field() ?>
                        <input type="hidden" name="feedback_rating" value="INAPPROPRIATE">
                        <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill" style="font-size:.7rem">⚠️ Tidak Pantas</button>
                    </form>
                    <form method="POST" action="<?= base_url('quality/copilot/' . $aiId . '/feedback') ?>" class="d-inline-flex gap-1">
                        <?= csrf_field() ?>
                        <input type="hidden" name="feedback_rating" value="WRONG_ALIGNMENT">
                        <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill" style="font-size:.7rem">🎯 Tidak Sesuai Kurikulum</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('promptType').addEventListener('change', function() {
    var val = this.value;
    document.querySelectorAll('.dynamic-field').forEach(function(el) {
        var prompts = el.dataset.prompt || '';
        el.style.display = prompts.includes(val) ? '' : 'none';
    });
});
document.getElementById('promptType').dispatchEvent(new Event('change'));
</script>
<?= $this->endSection() ?>
