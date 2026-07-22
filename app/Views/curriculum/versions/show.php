<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php
$totalHours = array_sum(array_map(static fn ($item) => (float) $item['effective_weekly_hours'], $structures));
$selectedUnit = null;
foreach ($units as $unit) { if ((int) $unit['id'] === (int) $filters['unit_id']) { $selectedUnit = $unit; break; } }
?>
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
        <div>
            <a href="<?= base_url('curriculum') ?>" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i>Daftar kurikulum</a>
            <div class="d-flex flex-wrap align-items-center gap-2 mt-2"><h1 class="h3 mb-0"><?= esc($version['name']) ?></h1><?php if ((int) $version['is_active'] === 1): ?><span class="badge rounded-pill bg-success">Aktif</span><?php endif; ?></div>
            <p class="text-muted mb-0"><?= esc($version['code']) ?> · <?= esc(($version['year_name'] ?? '') . ' · ' . ($version['period_name'] ?? '-')) ?></p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if (has_permission('curriculum.import')): ?><a href="<?= base_url('curriculum/imports') ?>" class="btn btn-outline-success"><i class="bi bi-file-earmark-arrow-up me-1"></i>Import Excel</a><?php endif; ?>
            <?php if (has_permission('curriculum.export')): ?><a href="<?= base_url('curriculum/' . $version['uuid'] . '/export') ?>" class="btn btn-outline-success"><i class="bi bi-download me-1"></i>Ekspor</a><?php endif; ?>
            <?php if ((int) $version['is_active'] !== 1 && (has_permission('curriculum.manage') || has_permission('curriculum.approve'))): ?><form action="<?= base_url('curriculum/' . $version['uuid'] . '/activate') ?>" method="post" onsubmit="return confirm('Aktifkan kurikulum ini? Versi aktif lain pada periode yang sama akan dinonaktifkan.')"><?= csrf_field() ?><button class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Aktifkan kurikulum</button></form><?php endif; ?>
        </div>
    </div>

    <?php foreach (['success' => 'success', 'error' => 'danger'] as $key => $type): ?><?php if ($message = session()->getFlashdata($key)): ?><div class="alert alert-<?= $type ?> alert-dismissible fade show"><?= esc($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?><?php endforeach; ?>

    <?php if ($validation['has_blockers']): ?><div class="alert alert-danger border-0"><i class="bi bi-exclamation-octagon me-2"></i><strong>Belum dapat diaktifkan.</strong> Ada <?= (int) $validation['errors'] + (int) $validation['blockers'] ?> masalah data yang perlu diperbaiki.</div>
    <?php elseif (!(int) $version['is_active']): ?><div class="alert alert-info border-0"><i class="bi bi-info-circle me-2"></i>Struktur siap ditinjau. Setelah lengkap, klik <strong>Aktifkan kurikulum</strong>; tidak ada tahapan persetujuan berlapis.</div><?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Mata pelajaran</div><div class="fs-3 fw-bold"><?= count($structures) ?></div></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Total jam per minggu</div><div class="fs-3 fw-bold text-primary"><?= number_format($totalHours, 1, ',', '.') ?> <span class="fs-6">JP</span></div></div></div></div>
        <div class="col-xl-6"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small mb-1">Unit yang sedang dikelola</div><div class="fw-semibold fs-5"><?= esc(($selectedUnit['code'] ?? '-') . ' · ' . ($selectedUnit['name'] ?? 'Pilih unit')) ?></div><div class="small text-muted">Daftar tingkat, kelas, dan mapel otomatis mengikuti unit ini.</div></div></div></div>
    </div>

    <div class="card border-0 shadow-sm mb-4"><div class="card-body"><form method="get" class="row g-3 align-items-end">
        <div class="col-md-5"><label class="form-label fw-semibold">Unit sekolah</label><select name="unit_id" class="form-select" onchange="this.form.submit()"><?php foreach ($units as $unit): ?><option value="<?= (int) $unit['id'] ?>" <?= (int) $filters['unit_id'] === (int) $unit['id'] ? 'selected' : '' ?>><?= esc($unit['code'] . ' · ' . $unit['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Tingkat</label><select name="grade_level_id" class="form-select"><option value="">Semua tingkat</option><?php foreach ($grades as $grade): ?><option value="<?= (int) $grade['id'] ?>" <?= (string) ($filters['grade_level_id'] ?? '') === (string) $grade['id'] ? 'selected' : '' ?>><?= esc($grade['code'] . ' · ' . $grade['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary flex-grow-1">Tampilkan</button><a href="<?= base_url('curriculum/' . $version['uuid'] . '?unit_id=' . (int) $filters['unit_id']) ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a></div>
    </form></div></div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2"><div><h5 class="mb-0">Daftar mata pelajaran</h5><div class="small text-muted">Jam efektif dihitung otomatis dari sumber jam yang dipilih.</div></div><?php if (has_permission('curriculum.manage') && !empty($grades) && !empty($subjects)): ?><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStructureModal"><i class="bi bi-plus-lg me-1"></i>Tambah mapel</button><?php endif; ?></div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th class="ps-4">Mata pelajaran</th><th>Tingkat / kelas</th><th>Kategori</th><th class="text-end">Jam/minggu</th><th>Sumber</th><th>Rapor</th><th class="text-end pe-4">Aksi</th></tr></thead><tbody>
        <?php if ($structures === []): ?><tr><td colspan="7" class="text-center py-5"><i class="bi bi-grid-3x3-gap fs-1 text-muted"></i><h5 class="mt-3">Struktur masih kosong</h5><p class="text-muted mb-3">Tambahkan mapel satu per satu atau gunakan import Excel.</p><?php if (has_permission('curriculum.import')): ?><a href="<?= base_url('curriculum/imports') ?>" class="btn btn-outline-success">Mulai dari Excel</a><?php endif; ?></td></tr>
        <?php else: foreach ($structures as $structure): ?><tr>
            <td class="ps-4"><div class="fw-semibold"><?= esc($structure['subject_name'] ?? '-') ?></div><div class="small text-muted"><?= esc($structure['subject_code'] ?? '-') ?></div></td>
            <td><div><?= esc($structure['grade_name'] ?? $structure['grade_code'] ?? '-') ?></div><div class="small text-muted"><?= !empty($structure['classroom_name']) ? 'Khusus ' . esc($structure['classroom_name']) : 'Semua kelas pada tingkat ini' ?></div></td>
            <td><span class="badge bg-light text-dark border"><?= esc(str_replace('_', ' ', $structure['category'])) ?></span></td>
            <td class="text-end fw-bold text-primary"><?= number_format((float) $structure['effective_weekly_hours'], 1, ',', '.') ?> JP</td>
            <td><?= esc(['OFFICIAL' => 'Jam resmi', 'CUSTOM' => 'Penyesuaian', 'MANUAL' => 'Manual'][$structure['effective_source']] ?? $structure['effective_source']) ?></td>
            <td><?= (int) $structure['counts_in_report'] === 1 ? '<i class="bi bi-check-circle-fill text-success"></i> Ya' : '<span class="text-muted">Tidak</span>' ?></td>
            <td class="text-end pe-4"><?php if (has_permission('curriculum.manage')): ?><form action="<?= base_url('curriculum/' . $version['uuid'] . '/structures/' . $structure['uuid'] . '/delete') ?>" method="post" class="d-inline" onsubmit="return confirm('Hapus <?= esc($structure['subject_name'] ?? 'mata pelajaran ini') ?> dari struktur?')"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button></form><?php endif; ?></td>
        </tr><?php endforeach; endif; ?></tbody></table></div>
    </div>

    <?php if (!empty($validation['results'])): ?><details class="card border-0 shadow-sm mt-4"><summary class="card-header bg-white py-3 fw-semibold" style="cursor:pointer"><i class="bi bi-shield-check me-2"></i>Catatan pemeriksaan data (<?= (int) $validation['total_results'] ?>)</summary><div class="list-group list-group-flush"><?php foreach ($validation['results'] as $result): ?><div class="list-group-item"><span class="badge bg-<?= in_array($result['severity'], ['ERROR', 'BLOCKER'], true) ? 'danger' : 'warning text-dark' ?> me-2"><?= esc($result['severity']) ?></span><?= esc($result['message']) ?></div><?php endforeach; ?></div></details><?php endif; ?>
</div>

<?php if (has_permission('curriculum.manage') && !empty($grades) && !empty($subjects)): ?>
<div class="modal fade" id="addStructureModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><form action="<?= base_url('curriculum/' . $version['uuid'] . '/structures') ?>" method="post" id="structureForm"><?= csrf_field() ?><input type="hidden" name="unit_id" value="<?= (int) $filters['unit_id'] ?>">
    <div class="modal-header"><div><h5 class="modal-title">Tambah mata pelajaran</h5><div class="small text-muted"><?= esc($selectedUnit['name'] ?? '') ?></div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-semibold">Tingkat <span class="text-danger">*</span></label><select name="grade_level_id" class="form-select" required><option value="">Pilih tingkat</option><?php foreach ($grades as $grade): ?><option value="<?= (int) $grade['id'] ?>"><?= esc($grade['code'] . ' · ' . $grade['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Kelas khusus <span class="text-muted fw-normal">(opsional)</span></label><select name="classroom_id" class="form-select"><option value="">Berlaku untuk semua kelas</option><?php foreach ($classrooms as $classroom): ?><option value="<?= (int) $classroom['id'] ?>"><?= esc($classroom['name']) ?></option><?php endforeach; ?></select><div class="form-text">Kosongkan untuk menerapkan ke seluruh kelas pada tingkat.</div></div>
        <div class="col-12"><label class="form-label fw-semibold">Mata pelajaran <span class="text-danger">*</span></label><select name="subject_id" class="form-select" required><option value="">Pilih mata pelajaran</option><?php foreach ($subjects as $subject): ?><option value="<?= (int) $subject['id'] ?>"><?= esc($subject['code'] . ' · ' . $subject['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Kategori</label><select name="category" class="form-select"><option value="INTRAKURIKULER">Intrakurikuler</option><option value="MUATAN_LOKAL">Muatan lokal</option><option value="KOKURIKULER">Kokurikuler</option><option value="EKSTRAKURIKULER">Ekstrakurikuler</option><option value="KEGIATAN_TETAP">Kegiatan tetap</option><option value="PENGEMBANGAN_DIRI">Pengembangan diri</option><option value="OTHER">Lainnya</option></select></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Sumber jam <span class="text-danger">*</span></label><select name="effective_source" id="effectiveSource" class="form-select" required><option value="OFFICIAL">Jam resmi</option><option value="CUSTOM">Penyesuaian sekolah</option><option value="MANUAL">Input manual</option></select></div>
        <div class="col-md-6"><label class="form-label fw-semibold" id="weeklyHoursLabel">Jam resmi per minggu <span class="text-danger">*</span></label><div class="input-group"><input type="number" step="0.5" min="0.5" max="60" name="weekly_hours" class="form-control" required><span class="input-group-text">JP</span></div></div>
        <div class="col-md-6" id="reasonGroup" hidden><label class="form-label fw-semibold">Alasan penyesuaian <span class="text-danger">*</span></label><input type="text" name="adjustment_reason" id="adjustmentReason" class="form-control" maxlength="255" placeholder="Contoh: tambahan muatan lokal"></div>
        <div class="col-md-6"><div class="form-check form-switch mt-md-4 pt-md-2"><input type="hidden" name="counts_in_report" value="0"><input class="form-check-input" type="checkbox" name="counts_in_report" value="1" id="inReport" checked><label class="form-check-label" for="inReport">Masuk rapor</label></div></div>
        <div class="col-md-6"><div class="form-check form-switch mt-md-4 pt-md-2"><input type="hidden" name="counts_as_teaching_load" value="0"><input class="form-check-input" type="checkbox" name="counts_as_teaching_load" value="1" id="asLoad" checked><label class="form-check-label" for="asLoad">Dihitung sebagai beban mengajar</label></div></div>
    </div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Simpan</button></div>
</form></div></div></div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const source = document.getElementById('effectiveSource');
    const reasonGroup = document.getElementById('reasonGroup');
    const reason = document.getElementById('adjustmentReason');
    const label = document.getElementById('weeklyHoursLabel');
    function syncSource() {
        const adjusted = source.value !== 'OFFICIAL';
        reasonGroup.hidden = !adjusted;
        reason.required = adjusted;
        label.innerHTML = (source.value === 'OFFICIAL' ? 'Jam resmi' : source.value === 'CUSTOM' ? 'Jam penyesuaian' : 'Jam manual') + ' per minggu <span class="text-danger">*</span>';
    }
    source.addEventListener('change', syncSource); syncSource();
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>
