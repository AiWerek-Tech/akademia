<?php
$selected = $selected ?? [];
$action = $action ?? '';
$program = $program ?? null;
$isEdit = $program !== null;
?>
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label text-xs text-muted fw-semibold mb-1">Kode Program</label>
                <input type="text" name="code" class="form-control form-control-sm shadow-sm" value="<?= esc($selected['code'] ?? '') ?>" placeholder="cth. P5-A" maxlength="50">
            </div>
            <div class="col-md-6">
                <label class="form-label text-xs text-muted fw-semibold mb-1">Nama Program <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control form-control-sm shadow-sm" value="<?= esc($selected['title'] ?? '') ?>" placeholder="cth. Pameran Karya Projek IPAS & Bahasa" required>
            </div>
            <div class="col-md-3">
                <label class="form-label text-xs text-muted fw-semibold mb-1">Jenis Program <span class="text-danger">*</span></label>
                <select name="program_type" class="form-select form-select-sm shadow-sm" required>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= $t ?>" <?= strtoupper($selected['program_type'] ?? 'COCURRICULAR') === $t ? 'selected' : '' ?>><?= esc($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label text-xs text-muted fw-semibold mb-1">Tema</label>
                <input type="text" name="theme" class="form-control form-control-sm shadow-sm" value="<?= esc($selected['theme'] ?? '') ?>" placeholder="Tema kegiatan (jika ada)">
            </div>
            <div class="col-md-3">
                <label class="form-label text-xs text-muted fw-semibold mb-1">Model Pelaksanaan</label>
                <select name="delivery_model" class="form-select form-select-sm shadow-sm">
                    <?php foreach ($deliveryModels as $dm): ?>
                        <option value="<?= $dm ?>" <?= strtoupper($selected['delivery_model'] ?? 'PROJECT') === $dm ? 'selected' : '' ?>><?= esc($dm) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label text-xs text-muted fw-semibold mb-1">Versi KSP</label>
                <select name="ksp_version_id" class="form-select form-select-sm shadow-sm">
                    <option value="">—</option>
                    <?php foreach ($kspVersions as $kv): ?>
                        <option value="<?= $kv['id'] ?>" <?= (int) ($selected['ksp_version_id'] ?? 0) === (int) $kv['id'] ? 'selected' : '' ?>><?= esc($kv['name'] ?? $kv['code'] ?? ('Versi ' . $kv['id'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label text-xs text-muted fw-semibold mb-1">Alokasi (menit/tahun)</label>
                <input type="number" name="annual_minutes" class="form-control form-control-sm shadow-sm" value="<?= esc($selected['annual_minutes'] ?? '') ?>" min="0">
            </div>
            <div class="col-md-3">
                <label class="form-label text-xs text-muted fw-semibold mb-1">Tanggal Mulai</label>
                <input type="date" name="start_date" class="form-control form-control-sm shadow-sm" value="<?= esc($selected['start_date'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label text-xs text-muted fw-semibold mb-1">Tanggal Selesai</label>
                <input type="date" name="end_date" class="form-control form-control-sm shadow-sm" value="<?= esc($selected['end_date'] ?? '') ?>">
            </div>
            <div class="col-12">
                <label class="form-label text-xs text-muted fw-semibold mb-1">Alasan / Kebutuhan (Need Analysis)</label>
                <textarea name="rationale" rows="2" class="form-control form-control-sm shadow-sm" placeholder="Mengapa program ini perlu dilaksanakan?"><?= esc($selected['rationale'] ?? '') ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label text-xs text-muted fw-semibold mb-1">Tujuan Program</label>
                <textarea name="objective" rows="2" class="form-control form-control-sm shadow-sm" placeholder="Tujuan yang ingin dicapai"><?= esc($selected['objective'] ?? '') ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label text-xs text-muted fw-semibold mb-1">Deskripsi</label>
                <textarea name="description" rows="2" class="form-control form-control-sm shadow-sm" placeholder="Deskripsi singkat program"><?= esc($selected['description'] ?? '') ?></textarea>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i data-lucide="badge-check" class="w-4 h-4 me-1 text-purple"></i> Dimensi Profil Lulusan</h6>
                <?php foreach ($dimensions as $d): ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="dimension_ids[]" value="<?= $d['id'] ?>" id="dim-<?= $d['id'] ?>" <?= in_array((int) $d['id'], array_map('intval', $selected['dimension_ids'] ?? []), true) ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="dim-<?= $d['id'] ?>">
                            <strong><?= esc($d['code'] ?? '') ?></strong> — <?= esc($d['name']) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
                <?php if ($dimensions === []): ?>
                    <p class="text-muted small mb-0">Belum ada dimensi profil lulusan.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i data-lucide="book-open" class="w-4 h-4 me-1 text-purple"></i> Mapel Terkait (Lintas Disiplin)</h6>
                <?php foreach ($subjects as $s): ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="subject_ids[]" value="<?= $s['id'] ?>" id="subj-<?= $s['id'] ?>" <?= in_array((int) $s['id'], array_map('intval', $selected['subject_ids'] ?? []), true) ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="subj-<?= $s['id'] ?>"><?= esc($s['code'] ?? '') ?> — <?= esc($s['name']) ?></label>
                    </div>
                <?php endforeach; ?>
                <?php if ($subjects === []): ?>
                    <p class="text-muted small mb-0">Belum ada mapel tersedia untuk unit ini.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i data-lucide="target" class="w-4 h-4 me-1 text-purple"></i> Tujuan Pembelajaran (TP)</h6>
                <div class="form-check-list" style="max-height:260px;overflow:auto">
                    <?php foreach ($objectives as $o): ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="objective_ids[]" value="<?= $o['id'] ?>" id="obj-<?= $o['id'] ?>" <?= in_array((int) $o['id'], array_map('intval', $selected['objective_ids'] ?? []), true) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="obj-<?= $o['id'] ?>"><strong><?= esc($o['code'] ?? '') ?></strong> — <?= esc($o['description'] ?? '') ?></label>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($objectives === []): ?>
                        <p class="text-muted small mb-0">Belum ada TP untuk unit ini.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i data-lucide="users" class="w-4 h-4 me-1 text-purple"></i> Guru Fasilitator & Kelas</h6>
                <label class="form-label text-xs text-muted fw-semibold mb-1">Guru</label>
                <div style="max-height:180px;overflow:auto" class="mb-3">
                    <?php foreach ($teachers as $t): ?>
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="teacher_ids[]" value="<?= $t['id'] ?>" id="t-<?= $t['id'] ?>" <?= in_array((int) $t['id'], array_map('intval', $selected['teacher_ids'] ?? []), true) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="t-<?= $t['id'] ?>"><?= esc($t['full_name']) ?></label>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($teachers === []): ?>
                        <p class="text-muted small mb-0">Belum ada guru aktif.</p>
                    <?php endif; ?>
                </div>
                <label class="form-label text-xs text-muted fw-semibold mb-1">Kelas / Rombel</label>
                <div style="max-height:180px;overflow:auto">
                    <?php foreach ($classrooms as $c): ?>
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="classroom_ids[]" value="<?= $c['id'] ?>" id="cl-<?= $c['id'] ?>" <?= in_array((int) $c['id'], array_map('intval', $selected['classroom_ids'] ?? []), true) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="cl-<?= $c['id'] ?>"><?= esc($c['name']) ?></label>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($classrooms === []): ?>
                        <p class="text-muted small mb-0">Belum ada kelas.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i data-lucide="handshake" class="w-4 h-4 me-1 text-purple"></i> Mitra Program</h6>
                <div id="partnersWrap"></div>
                <button type="button" class="btn btn-sm btn-outline-secondary shadow-sm mt-2" onclick="addPartnerRow()"><i data-lucide="plus" class="w-3 h-3 me-1"></i> Tambah Mitra</button>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i data-lucide="box" class="w-4 h-4 me-1 text-purple"></i> Sumber Daya</h6>
                <div id="resourcesWrap"></div>
                <button type="button" class="btn btn-sm btn-outline-secondary shadow-sm mt-2" onclick="addResourceRow()"><i data-lucide="plus" class="w-3 h-3 me-1"></i> Tambah Sumber Daya</button>
            </div>
        </div>
    </div>
</div>

<script>
window._partners = <?= json_encode($selected['partners'] ?? []) ?>;
window._resources = <?= json_encode($selected['resources'] ?? []) ?>;
function addPartnerRow(p) {
    p = p || {};
    const wrap = document.getElementById('partnersWrap');
    const div = document.createElement('div');
    div.className = 'partner-row row g-2 mb-2 align-items-end';
    div.innerHTML =
        '<div class="col-4"><input name="partners[' + (wrap.children.length) + '][name]" class="form-control form-control-sm shadow-sm" placeholder="Nama mitra" value="' + (p.name || '') + '"></div>' +
        '<div class="col-4"><input name="partners[' + (wrap.children.length) + '][partner_type]" class="form-control form-control-sm shadow-sm" placeholder="Jenis (mis. universitas)" value="' + (p.partner_type || '') + '"></div>' +
        '<div class="col-3"><input name="partners[' + (wrap.children.length) + '][role]" class="form-control form-control-sm shadow-sm" placeholder="Peran" value="' + (p.role || '') + '"></div>' +
        '<div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.parentElement.parentElement.remove()"><i data-lucide="trash-2" class="w-3 h-3"></i></button></div>';
    wrap.appendChild(div);
    lucide.createIcons();
}
function addResourceRow(r) {
    r = r || {};
    const wrap = document.getElementById('resourcesWrap');
    const div = document.createElement('div');
    div.className = 'resource-row row g-2 mb-2 align-items-end';
    div.innerHTML =
        '<div class="col-4"><input name="resources[' + (wrap.children.length) + '][name]" class="form-control form-control-sm shadow-sm" placeholder="Nama sumber daya" value="' + (r.name || '') + '"></div>' +
        '<div class="col-3"><input name="resources[' + (wrap.children.length) + '][resource_type]" class="form-control form-control-sm shadow-sm" placeholder="Jenis (mis. alat)" value="' + (r.resource_type || '') + '"></div>' +
        '<div class="col-2"><input name="resources[' + (wrap.children.length) + '][quantity]" type="number" min="0" class="form-control form-control-sm shadow-sm" placeholder="Jumlah" value="' + (r.quantity || '') + '"></div>' +
        '<div class="col-2"><input name="resources[' + (wrap.children.length) + '][notes]" class="form-control form-control-sm shadow-sm" placeholder="Catatan" value="' + (r.notes || '') + '"></div>' +
        '<div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.parentElement.parentElement.remove()"><i data-lucide="trash-2" class="w-3 h-3"></i></button></div>';
    wrap.appendChild(div);
    lucide.createIcons();
}
(function initDynamicRows() {
    (window._partners || []).forEach(addPartnerRow);
    (window._resources || []).forEach(addResourceRow);
})();
</script>