<?php
/**
 * Shared assessment authoring form.
 * Expects: $formAction, $submitLabel, $assessment (array|null), $classrooms,
 * $subjects, $tps, $teachers, $types, $forms, $isManagement.
 */
$existing = $assessment ?? [];
?>
<form method="POST" action="<?= $formAction ?>" class="row g-3">
    <?= csrf_field() ?>

    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 py-3 px-4">
                <h6 class="fw-bold text-gray-900 mb-0"><i data-lucide="info" class="w-4 h-4 me-1 text-primary"></i> Informasi Dasar</h6>
            </div>
            <div class="card-body px-4 pb-4 pt-0">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Judul Assessment <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required value="<?= esc($existing['title'] ?? old('title', '')) ?>" placeholder="cth: Sumatif Lingkaran & Garis Singgung">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="assessment_date" class="form-control" required value="<?= esc($existing['assessment_date'] ?? old('assessment_date', date('Y-m-d'))) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tipe</label>
                        <select name="assessment_type" class="form-select">
                            <?php foreach ($types as $t): ?>
                                <option value="<?= $t ?>" <?= ($existing['assessment_type'] ?? 'FORMATIVE') === $t ? 'selected' : '' ?>><?= esc($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Bentuk</label>
                        <select name="assessment_form" class="form-select">
                            <?php foreach ($forms as $f): ?>
                                <option value="<?= $f ?>" <?= ($existing['assessment_form'] ?? 'ANGKA') === $f ? 'selected' : '' ?>><?= esc($f) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Skor Maksimal</label>
                        <input type="number" step="0.01" min="0" name="max_score" class="form-control" value="<?= esc($existing['max_score'] ?? old('max_score', '')) ?>" placeholder="Opsional (untuk kalkulasi otomatis)">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Kelas / Rombel <span class="text-danger">*</span></label>
                        <select name="classroom_id" id="classroomSelect" class="form-select" required>
                            <option value="">Pilih Kelas...</option>
                            <?php foreach ($classrooms as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= (int) ($existing['classroom_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Mata Pelajaran <span class="text-danger">*</span></label>
                        <select name="subject_id" id="subjectSelect" class="form-select" required>
                            <option value="">Pilih Mapel...</option>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?= $s['id'] ?>" data-name="<?= esc($s['name']) ?>" <?= (int) ($existing['subject_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>><?= esc($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (!empty($isManagement)): ?>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Guru Pengampu</label>
                            <select name="teacher_id" class="form-select">
                                <option value="">Pilih Guru...</option>
                                <?php foreach ($teachers as $t): ?>
                                    <option value="<?= $t['id'] ?>" <?= (int) ($existing['teacher_id'] ?? 0) === (int) $t['id'] ? 'selected' : '' ?>><?= esc($t['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Konteks singkat assessment..."><?= esc($existing['description'] ?? old('description', '')) ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TP Coverage -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-gray-900 mb-0"><i data-lucide="target" class="w-4 h-4 me-1 text-primary"></i> Tujuan Pembelajaran yang Diukur</h6>
                <span class="text-xs text-muted" id="tpCountLabel">0 TP dipilih</span>
            </div>
            <div class="card-body px-4 pb-4 pt-0">
                <?php
                $tpsBySubject = [];
                foreach ($tps as $tp) {
                    $tpsBySubject[(int) $tp['subject_id']][] = $tp;
                }
                $selectedObjectives = array_map('intval', $existing['objectives'] ?? []);
                $selectedObjectives = array_column($existing['objectives'] ?? [], 'learning_objective_id');
                ?>
                <?php foreach ($tpsBySubject as $subjectId => $subjectTps): ?>
                    <div class="tp-group mb-3" data-subject="<?= $subjectId ?>">
                        <div class="fw-semibold text-muted text-xs text-uppercase tracking-wider mb-2"><?= esc($subjectTps[0]['subject_name'] ?? 'Mapel') ?></div>
                        <div class="row g-2">
                            <?php foreach ($subjectTps as $tp): ?>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-check form-check-inline border rounded-3 px-3 py-2 w-100 bg-white shadow-xs">
                                        <input class="form-check-input tp-check" type="checkbox" name="objective_ids[]" value="<?= $tp['id'] ?>" data-subject="<?= $subjectId ?>" <?= in_array((int) $tp['id'], $selectedObjectives, true) ? 'checked' : '' ?>>
                                        <span class="form-check-label small"><strong><?= esc($tp['code']) ?></strong><br><?= esc($tp['name']) ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if ($tps === []): ?>
                    <p class="text-muted small mb-0">Belum ada TP. Lengkapi adaptasi TP di Curriculum &rarr; Adaptasi TP terlebih dahulu.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Criteria -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-gray-900 mb-0"><i data-lucide="list-checks" class="w-4 h-4 me-1 text-primary"></i> Kriteria & Rubrik</h6>
                <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" onclick="addCriterionRow()"><i data-lucide="plus" class="w-3.5 h-3.5"></i> Tambah Kriteria</button>
            </div>
            <div class="card-body px-4 pb-4 pt-0">
                <div id="criteriaContainer" class="d-grid gap-2">
                    <?php foreach ($existing['criteria'] ?? [] as $i => $criterion): ?>
                        <?= $this->include('assessment/_criterion_row', ['criterion' => $criterion, 'index' => $i, 'tps' => $tps]) ?>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($existing['criteria'])): ?>
                    <p class="text-muted small mb-0 mt-2">Tambahkan minimal satu kriteria (terhubung ke TP agar mastery dapat dihitung).</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Items -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-gray-900 mb-0"><i data-lucide="file-text" class="w-4 h-4 me-1 text-primary"></i> Item / Soal</h6>
                <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" onclick="addItemRow()"><i data-lucide="plus" class="w-3.5 h-3.5"></i> Tambah Item</button>
            </div>
            <div class="card-body px-4 pb-4 pt-0">
                <div id="itemsContainer" class="d-grid gap-2">
                    <?php foreach ($existing['items'] ?? [] as $i => $item): ?>
                        <div class="item-row border rounded-3 p-3 bg-white shadow-xs">
                            <div class="row g-2">
                                <div class="col-md-7"><input type="text" name="items[<?= $i ?>][prompt]" class="form-control form-control-sm" value="<?= esc($item['prompt']) ?>" placeholder="Prompt / soal"></div>
                                <div class="col-md-2">
                                    <select name="items[<?= $i ?>][item_type]" class="form-select form-select-sm">
                                        <?php foreach (['ESSAY', 'MULTIPLE_CHOICE', 'SHORT_ANSWER', 'PERFORMANCE', 'PROJECT'] as $it): ?>
                                            <option value="<?= $it ?>" <?= ($item['item_type'] ?? 'ESSAY') === $it ? 'selected' : '' ?>><?= esc($it) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2"><input type="number" step="0.01" min="0" name="items[<?= $i ?>][max_score]" class="form-control form-control-sm" value="<?= esc($item['max_score'] ?? '') ?>" placeholder="Maks"></div>
                                <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="this.closest('.item-row').remove()"><i data-lucide="x" class="w-3.5 h-3.5"></i></button></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4 shadow-sm"><i data-lucide="save" class="w-4 h-4 me-1"></i> <?= $submitLabel ?></button>
        <a href="<?= base_url('assessment') ?>" class="btn btn-outline-secondary shadow-sm">Batal</a>
    </div>
</form>

<script>
let criterionCounter = <?= max(1, count($existing['criteria'] ?? [])) ?>;
let itemCounter = <?= max(1, count($existing['items'] ?? [])) ?>;

function filteredTpOptions(subjectId, selected = '') {
    const groups = document.querySelectorAll('.tp-group');
    let html = '<option value="">— Tanpa TP (tidak hitung mastery) —</option>';
    groups.forEach((g) => {
        if (subjectId && g.dataset.subject !== String(subjectId)) return;
        g.querySelectorAll('input.tp-check').forEach((cb) => {
            html += `<option value="${cb.value}" ${String(cb.value) === selected ? 'selected' : ''}>${cb.closest('label').querySelector('strong').textContent} — ${cb.closest('label').querySelector('span.form-check-label').innerText.split('\n')[1] || ''}</option>`;
        });
    });
    return html;
}

function refreshTpSelects() {
    const subjectId = document.getElementById('subjectSelect').value;
    document.querySelectorAll('select.tp-select').forEach((sel) => {
        const selected = sel.value;
        sel.innerHTML = filteredTpOptions(subjectId, selected);
    });
}

function addCriterionRow() {
    const container = document.getElementById('criteriaContainer');
    const i = criterionCounter++;
    const div = document.createElement('div');
    div.className = 'criterion-row border rounded-3 p-3 bg-white shadow-xs';
    div.innerHTML = `
        <div class="row g-2">
            <div class="col-md-6"><input type="text" name="criteria[${i}][criterion]" class="form-control form-control-sm" placeholder="Kriteria penilaian"></div>
            <div class="col-md-3"><select name="criteria[${i}][learning_objective_id]" class="form-select form-select-sm tp-select">${filteredTpOptions(document.getElementById('subjectSelect').value)}</select></div>
            <div class="col-md-2"><input type="number" step="0.01" min="0" name="criteria[${i}][weight]" class="form-control form-control-sm" value="1" placeholder="Bobot"></div>
            <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="this.closest('.criterion-row').remove()"><i data-lucide="x" class="w-3.5 h-3.5"></i></button></div>
        </div>
        <div class="mt-2">
            <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none rubric-toggle" onclick="toggleRubric(this)"><i data-lucide="list-checks" class="w-3.5 h-3.5 me-1"></i> Atur level rubrik</button>
            <div class="rubric-editor d-none mt-2 border rounded-3 p-2 bg-light-subtle">
                <input type="hidden" name="criteria[${i}][rubric_levels_json]" class="rubric-json" value="">
                <div class="rubric-level-list d-grid gap-1"></div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="addRubricLevel(this)"><i data-lucide="plus" class="w-3 h-3 me-1"></i> Tambah level</button>
            </div>
        </div>`;
    container.appendChild(div);
    window.lucide && lucide.createIcons();
}

function rubricLevelRowHtml(data) {
    data = data || {};
    const div = document.createElement('div');
    div.className = 'rubric-level d-flex gap-1 align-items-center';
    div.innerHTML = `
        <input type="text" class="form-control form-control-sm rubric-label" style="max-width:150px" placeholder="Label level" value="${data.label || ''}">
        <input type="number" step="0.01" class="form-control form-control-sm rubric-score" style="max-width:90px" placeholder="Skor" value="${data.score === null || data.score === undefined ? '' : data.score}">
        <input type="text" class="form-control form-control-sm rubric-desc" placeholder="Deskripsi level" value="${data.description || ''}">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.rubric-level').remove(); syncRubricJson(this.closest('.rubric-editor'))"><i data-lucide="x" class="w-3 h-3"></i></button>`;
    return div;
}

function toggleRubric(btn) {
    const editor = btn.nextElementSibling;
    editor.classList.toggle('d-none');
    if (!editor.dataset.initialized) {
        editor.dataset.initialized = '1';
        initRubricEditor(editor);
    }
    window.lucide && lucide.createIcons();
}

function initRubricEditor(editor) {
    const hidden = editor.querySelector('.rubric-json');
    const list = editor.querySelector('.rubric-level-list');
    list.innerHTML = '';
    let levels = [];
    if (hidden.value) {
        try { levels = JSON.parse(hidden.value); } catch (e) { levels = []; }
    }
    if (!levels.length) levels = [{ label: '', score: null, description: '' }];
    levels.forEach((lvl) => list.appendChild(rubricLevelRowHtml(lvl)));
    bindRubricEvents(editor);
    syncRubricJson(editor);
    window.lucide && lucide.createIcons();
}

function addRubricLevel(btn) {
    const editor = btn.closest('.rubric-editor');
    editor.querySelector('.rubric-level-list').appendChild(rubricLevelRowHtml({}));
    bindRubricEvents(editor);
    syncRubricJson(editor);
    window.lucide && lucide.createIcons();
}

function bindRubricEvents(editor) {
    editor.querySelectorAll('input.rubric-label, input.rubric-score, input.rubric-desc').forEach((el) => {
        el.removeEventListener('input', rubricSyncHandler);
        el.addEventListener('input', rubricSyncHandler);
    });
}

function rubricSyncHandler() {
    syncRubricJson(this.closest('.rubric-editor'));
}

function syncRubricJson(editor) {
    const list = editor.querySelector('.rubric-level-list');
    const hidden = editor.querySelector('.rubric-json');
    const levels = [];
    list.querySelectorAll('.rubric-level').forEach((row, idx) => {
        const label = row.querySelector('.rubric-label').value.trim();
        const score = row.querySelector('.rubric-score').value;
        const desc = row.querySelector('.rubric-desc').value.trim();
        if (!label && !score && !desc) return;
        levels.push({ level_index: idx, label: label || 'Level ' + (idx + 1), score: score === '' ? null : Number(score), description: desc });
    });
    hidden.value = levels.length ? JSON.stringify(levels) : '';
}

function addItemRow() {
    const container = document.getElementById('itemsContainer');
    const i = itemCounter++;
    const div = document.createElement('div');
    div.className = 'item-row border rounded-3 p-3 bg-white shadow-xs';
    div.innerHTML = `
        <div class="row g-2">
            <div class="col-md-7"><input type="text" name="items[${i}][prompt]" class="form-control form-control-sm" placeholder="Prompt / soal"></div>
            <div class="col-md-2"><select name="items[${i}][item_type]" class="form-select form-select-sm">
                <option value="ESSAY">ESSAY</option><option value="MULTIPLE_CHOICE">MULTIPLE_CHOICE</option><option value="SHORT_ANSWER">SHORT_ANSWER</option><option value="PERFORMANCE">PERFORMANCE</option><option value="PROJECT">PROJECT</option>
            </select></div>
            <div class="col-md-2"><input type="number" step="0.01" min="0" name="items[${i}][max_score]" class="form-control form-control-sm" placeholder="Maks"></div>
            <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="this.closest('.item-row').remove()"><i data-lucide="x" class="w-3.5 h-3.5"></i></button></div>
        </div>`;
    container.appendChild(div);
    window.lucide && lucide.createIcons();
}

document.getElementById('subjectSelect').addEventListener('change', function () {
    const subjectId = this.value;
    document.querySelectorAll('.tp-group').forEach((g) => {
        g.style.display = subjectId && g.dataset.subject !== String(subjectId) ? 'none' : '';
    });
    refreshTpSelects();
    updateTpCount();
});

function updateTpCount() {
    const subjectId = document.getElementById('subjectSelect').value;
    const count = document.querySelectorAll('.tp-check:checked').length;
    document.getElementById('tpCountLabel').textContent = count + ' TP dipilih';
}
document.querySelectorAll('.tp-check').forEach((cb) => cb.addEventListener('change', updateTpCount));
window.addEventListener('DOMContentLoaded', function () {
    updateTpCount();
    document.getElementById('subjectSelect').dispatchEvent(new Event('change'));
    document.querySelectorAll('.criterion-row').forEach((row) => {
        const editor = row.querySelector('.rubric-editor');
        const hidden = editor && editor.querySelector('.rubric-json');
        if (editor && hidden && hidden.value) {
            editor.dataset.initialized = '1';
            initRubricEditor(editor);
        }
    });
});