<?php
/** Criterion row partial. Expects $criterion, $index, $tps. */
$selectedTp = (int) ($criterion['learning_objective_id'] ?? 0);
?>
<div class="criterion-row border rounded-3 p-3 bg-white shadow-xs">
    <div class="row g-2">
        <div class="col-md-6">
            <input type="text" name="criteria[<?= $index ?>][criterion]" class="form-control form-control-sm" value="<?= esc($criterion['criterion'] ?? '') ?>" placeholder="Kriteria penilaian">
        </div>
        <div class="col-md-3">
            <select name="criteria[<?= $index ?>][learning_objective_id]" class="form-select form-select-sm tp-select">
                <option value="">— Tanpa TP (tidak hitung mastery) —</option>
                <?php foreach ($tps as $tp): ?>
                    <option value="<?= $tp['id'] ?>" data-subject="<?= $tp['subject_id'] ?>" <?= $selectedTp === (int) $tp['id'] ? 'selected' : '' ?>>
                        <?= esc($tp['code']) ?> — <?= esc($tp['name']) ?> (<?= esc($tp['subject_name'] ?? '') ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" step="0.01" min="0" name="criteria[<?= $index ?>][weight]" class="form-control form-control-sm" value="<?= esc($criterion['weight'] ?? '1') ?>" placeholder="Bobot">
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="this.closest('.criterion-row').remove()"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
        </div>
    </div>
    <div class="mt-2">
        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none rubric-toggle" onclick="toggleRubric(this)"><i data-lucide="list-checks" class="w-3.5 h-3.5 me-1"></i> Atur level rubrik</button>
        <div class="rubric-editor d-none mt-2 border rounded-3 p-2 bg-light-subtle">
            <input type="hidden" name="criteria[<?= $index ?>][rubric_levels_json]" class="rubric-json" value="<?= esc($criterion['rubric_levels_json'] ?? '') ?>">
            <div class="rubric-level-list d-grid gap-1"></div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="addRubricLevel(this)"><i data-lucide="plus" class="w-3 h-3 me-1"></i> Tambah level</button>
        </div>
    </div>
</div>