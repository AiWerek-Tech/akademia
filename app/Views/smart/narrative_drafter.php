<?= $this->extend('layouts/admin') ?>

<?= $this->section('additional_css') ?>
<style>
.narrative-box {
    border-radius: 16px; border: 1px solid #e2e8f0;
    background: #fff; padding: 20px; transition: all .2s ease;
}
.narrative-box:focus-within { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, .15); }
.badge-strength { background: #dcfce7; color: #166534; font-weight: 600; }
.badge-growth   { background: #fef3c7; color: #92400e; font-weight: 600; }
</style>
<?= $this->endSection() ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-gradient px-3 py-2 rounded-pill fw-semibold text-xs" style="background: linear-gradient(135deg, #0ea5e9, #6366f1); color: #fff;">
                    <i data-lucide="file-text" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Human-in-the-Loop Drafter
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mt-2 mb-1">Penyusun Draf Narasi Rapor</h1>
            <p class="text-muted mb-0">Hasilkan draf deskripsi capaian kompetensi siswa berbasis bukti ketercapaian TP secara otomatis untuk buku rapor.</p>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= base_url('smart/narrative-drafter') ?>" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Kelas / Rombel</label>
                    <select name="classroom_id" class="form-select form-select-sm shadow-sm" onchange="this.form.submit()">
                        <option value="">Pilih Kelas...</option>
                        <?php foreach ($classrooms as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (int) $selectedClassroom === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Mata Pelajaran</label>
                    <select name="subject_id" class="form-select form-select-sm shadow-sm" onchange="this.form.submit()">
                        <option value="">Pilih Mapel...</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= (int) $selectedSubject === (int) $s['id'] ? 'selected' : '' ?>><?= esc($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Pilih Siswa</label>
                    <select name="student_id" class="form-select form-select-sm shadow-sm">
                        <option value="">Pilih Siswa...</option>
                        <?php foreach ($students as $st): ?>
                            <option value="<?= $st['id'] ?>" <?= (int) $selectedStudent === (int) $st['id'] ? 'selected' : '' ?>><?= esc($st['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary shadow-sm w-100"><i data-lucide="sparkles" class="w-3.5 h-3.5 me-1"></i> Susun Draf</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!$narrativeData): ?>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body text-center text-muted py-5">
                <i data-lucide="edit-3" class="w-12 h-12 text-muted mb-3 d-inline-block" style="width:48px;height:48px;"></i><br>
                <span class="fw-semibold">Pilih kelas, mata pelajaran, dan siswa</span><br>
                <small>untuk menyusun draf deskripsi capaian rapor secara otomatis.</small>
            </div>
        </div>
    <?php else: ?>
        <?php
        $student = $narrativeData['student'];
        $subject = $narrativeData['subject'];
        $strengths = $narrativeData['strengths'];
        $growth = $narrativeData['growth_areas'];
        ?>

        <!-- Student Context -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                    <div>
                        <h4 class="fw-bold text-gray-900 mb-0"><?= esc($student['full_name']) ?></h4>
                        <div class="text-sm text-muted">Mata Pelajaran: <strong><?= esc($subject['name']) ?></strong></div>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge badge-strength px-3 py-2 rounded-pill"><?= $narrativeData['achieved_count'] ?> TP Tercapai/Mahir</span>
                        <span class="badge badge-growth px-3 py-2 rounded-pill"><?= $narrativeData['needs_growth_count'] ?> TP Perlu Pendampingan</span>
                    </div>
                </div>

                <!-- Competency Breakdown -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-light border h-100">
                            <div class="text-xs fw-bold text-success text-uppercase tracking-wider mb-2">
                                <i data-lucide="check-circle-2" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Capaian Tertinggi (Strengths)
                            </div>
                            <?php if (empty($strengths)): ?>
                                <div class="text-xs text-muted">Belum ada TP dengan predikat Tercapai / Mahir.</div>
                            <?php else: ?>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach (array_slice($strengths, 0, 3) as $st): ?>
                                        <li class="text-sm text-gray-800 mb-1">
                                            <span class="badge bg-success text-white me-1" style="font-size:.65rem;"><?= esc($st['code']) ?></span>
                                            <?= esc($st['name']) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-light border h-100">
                            <div class="text-xs fw-bold text-warning text-uppercase tracking-wider mb-2">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Area Pengembangan (Growth Areas)
                            </div>
                            <?php if (empty($growth)): ?>
                                <div class="text-xs text-success">Seluruh TP telah tercapai dengan baik.</div>
                            <?php else: ?>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach (array_slice($growth, 0, 3) as $gr): ?>
                                        <li class="text-sm text-gray-800 mb-1">
                                            <span class="badge bg-warning text-dark me-1" style="font-size:.65rem;"><?= esc($gr['code']) ?></span>
                                            <?= esc($gr['name']) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Final Editable Narrative Draft -->
                <div class="narrative-box mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <label class="form-label fw-bold text-gray-900 mb-0">
                                <i data-lucide="sparkles" class="w-4 h-4 text-indigo me-1 d-inline-block"></i> Draf Deskripsi Rapor (Dapat Diedit)
                            </label>
                            <span id="draftSaveStatus" class="badge <?= !empty($savedDraft) ? 'bg-success-subtle text-success border border-success' : 'bg-light text-muted border' ?> rounded-pill text-xs px-2.5 py-1">
                                <i data-lucide="<?= !empty($savedDraft) ? 'check-circle' : 'clock' ?>" class="w-3 h-3 me-1 d-inline-block"></i>
                                <?= !empty($savedDraft) ? 'Tersimpan di Database' : 'Belum Disimpan' ?>
                            </span>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="copyNarrative()">
                                <i data-lucide="copy" class="w-3.5 h-3.5 me-1"></i> Salin Teks
                            </button>
                            <button type="button" id="btnSaveDraft" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" onclick="saveDraftToDatabase()">
                                <i data-lucide="save" class="w-3.5 h-3.5 me-1"></i> Simpan Draf
                            </button>
                        </div>
                    </div>
                    <textarea id="narrativeText" class="form-control border-0 p-0 text-sm text-gray-800" rows="4" style="resize:vertical; background:transparent; font-size:.92rem; line-height:1.6;"><?= esc($narrativeData['composite_narrative']) ?></textarea>
                    
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-2">
                        <span id="charCountLabel" class="text-xs text-muted">0 / 300 karakter</span>
                        <span class="text-xs text-muted">Maksimal disarankan: 300 karakter</span>
                    </div>
                </div>

                <div class="text-xs text-muted">
                    <i data-lucide="info" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Guru dapat menyunting dan menyesuaikan kalimat draf di atas lalu klik <strong>Simpan Draf</strong> agar tersimpan secara permanen untuk buku rapor siswa ini.
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>
<?= $this->endSection() ?>

<?= $this->section('additional_js') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const textEl = document.getElementById('narrativeText');
    const countEl = document.getElementById('charCountLabel');
    if (textEl && countEl) {
        function updateCharCount() {
            const len = textEl.value.length;
            countEl.textContent = `${len} / 300 karakter`;
            if (len > 300) {
                countEl.className = 'text-xs text-danger fw-bold';
            } else {
                countEl.className = 'text-xs text-muted';
            }
        }
        textEl.addEventListener('input', updateCharCount);
        updateCharCount();
    }
});

function copyNarrative() {
    const text = document.getElementById('narrativeText');
    if (text) {
        text.select();
        navigator.clipboard.writeText(text.value).then(() => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Draf narasi berhasil disalin ke clipboard',
                    showConfirmButton: false,
                    timer: 2000
                });
            } else {
                alert('Draf narasi berhasil disalin.');
            }
        });
    }
}

async function saveDraftToDatabase() {
    const textEl = document.getElementById('narrativeText');
    const btn = document.getElementById('btnSaveDraft');
    const statusEl = document.getElementById('draftSaveStatus');

    if (!textEl || !textEl.value.trim()) {
        alert('Teks narasi tidak boleh kosong.');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

    try {
        const formData = new FormData();
        formData.append('student_id', '<?= (int) $selectedStudent ?>');
        formData.append('subject_id', '<?= (int) $selectedSubject ?>');
        formData.append('classroom_id', '<?= (int) $selectedClassroom ?>');
        formData.append('narrative_text', textEl.value.trim());
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        const res = await fetch('<?= base_url('smart/narrative-drafter/save') ?>', {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        const json = await res.json();

        if (json.status === 'success') {
            statusEl.className = 'badge bg-success-subtle text-success border border-success rounded-pill text-xs px-2.5 py-1';
            statusEl.innerHTML = '<i data-lucide="check-circle" class="w-3 h-3 me-1 d-inline-block"></i> Tersimpan di Database';
            if (typeof lucide !== 'undefined') lucide.createIcons();

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Draf narasi berhasil disimpan ke database!',
                    showConfirmButton: false,
                    timer: 2500
                });
            } else {
                alert('Draf narasi berhasil disimpan.');
            }
        } else {
            alert(json.message || 'Gagal menyimpan draf narasi.');
        }
    } catch (e) {
        alert('Terjadi kesalahan jaringan.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="save" class="w-3.5 h-3.5 me-1"></i> Simpan Draf';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
}
</script>
<?= $this->endSection() ?>
