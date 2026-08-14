<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php
$totalHours = array_sum(array_map(static fn ($item) => (float) $item['effective_weekly_hours'], $structures));
$selectedUnit = null;
foreach ($units as $unit) { if ((int) $unit['id'] === (int) $filters['unit_id']) { $selectedUnit = $unit; break; } }
$planSummary = $planning['summary'];
$planSettings = $planning['settings'];
$dayLabels = ['MON' => 'Senin', 'TUE' => 'Selasa', 'WED' => 'Rabu', 'THU' => 'Kamis', 'FRI' => 'Jumat', 'SAT' => 'Sabtu', 'SUN' => 'Minggu'];
$activeDayCodes = $planSettings['selected_day_codes'] ?? ['MON', 'TUE', 'WED', 'THU', 'FRI'];
$dailyCapacities = $planSettings['daily_jp_capacities'] ?? [];
$capacitySummary = implode(' + ', array_map(static fn ($code) => ($dayLabels[$code] ?? $code) . ' ' . number_format((float) ($dailyCapacities[$code] ?? $planSettings['daily_jp_capacity'] ?? 0), 1, ',', '.') . ' JP', $activeDayCodes));
?>
<style>
.planning-step{position:relative;padding-left:2.7rem}.planning-step::before{content:attr(data-step);position:absolute;left:0;top:.05rem;width:2rem;height:2rem;border-radius:.7rem;display:grid;place-items:center;background:#eef2ff;color:#4f46e5;font-weight:800}
.planning-metric{background:linear-gradient(145deg,#fff,#f8fafc);border:1px solid #e8edf5;border-radius:1rem}
.capacity-bar{height:.55rem;background:#e9eef5;border-radius:999px;overflow:hidden}.capacity-bar>span{display:block;height:100%;border-radius:inherit}
.planning-table thead th{white-space:nowrap}.planning-table tbody td{vertical-align:middle}
</style>
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
        <div>
            <a href="<?= base_url('curriculum') ?>" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i>Daftar kurikulum</a>
            <div class="d-flex flex-wrap align-items-center gap-2 mt-2"><h1 class="h3 mb-0"><?= esc($version['name']) ?></h1><?php if ((int) $version['is_active'] === 1): ?><span class="badge rounded-pill bg-success">Aktif</span><?php endif; ?></div>
            <p class="text-muted mb-0"><?= esc($version['code']) ?> · <?= esc(($version['year_name'] ?? '') . ' · ' . ($version['period_name'] ?? '-')) ?></p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <div class="btn-group" role="group">
                <a href="<?= base_url('curriculum/' . $version['uuid'] . '?unit_id=' . $filters['unit_id']) ?>" class="btn btn-primary active"><i class="bi bi-list-ul me-1"></i>Tampilan List</a>
                <a href="<?= base_url('curriculum/' . $version['uuid'] . '/matrix?unit_id=' . $filters['unit_id']) ?>" class="btn btn-outline-primary"><i class="bi bi-grid-3x3-gap-fill me-1"></i>Editor Matriks</a>
            </div>
            <?php if (has_permission('curriculum.import')): ?><a href="<?= base_url('curriculum/imports') ?>" class="btn btn-outline-success"><i class="bi bi-file-earmark-arrow-up me-1"></i>Import Excel</a><?php endif; ?>
            <?php if (has_permission('curriculum.export')): ?><a href="<?= base_url('curriculum/' . $version['uuid'] . '/export') ?>" class="btn btn-outline-success"><i class="bi bi-download me-1"></i>Ekspor</a><?php endif; ?>
            <?php if ((int) $version['is_active'] !== 1 && (has_permission('curriculum.manage') || has_permission('curriculum.approve'))): ?><form action="<?= base_url('curriculum/' . $version['uuid'] . '/activate') ?>" method="post" data-confirm="Aktifkan kurikulum ini? Versi aktif lain pada periode yang sama akan dinonaktifkan." data-confirm-title="Aktifkan kurikulum?" data-confirm-button="Aktifkan"><?= csrf_field() ?><button class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Aktifkan kurikulum</button></form><?php endif; ?>
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

    <div class="card border-0 shadow-sm mb-4 overflow-hidden">
        <div class="card-header bg-white border-0 p-4 pb-2 d-flex flex-column flex-xl-row justify-content-between gap-3">
            <div>
                <div class="text-primary fw-bold small text-uppercase mb-1">Pusat Perencanaan Terpadu</div>
                <h4 class="mb-1">Dari struktur kurikulum sampai jadwal otomatis</h4>
                <p class="text-muted mb-0">Sistem menghitung kebutuhan jam per kelas, kebutuhan guru, dan kesiapan jadwal dari satu sumber data.</p>
            </div>
            <button class="btn btn-outline-primary align-self-xl-start" data-bs-toggle="modal" data-bs-target="#planningSettingsModal">
                <i class="bi bi-sliders me-1"></i> Atur sistem 5 hari
            </button>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3"><div class="planning-metric p-3 h-100"><div class="text-muted small">Kapasitas jadwal</div><div class="fs-4 fw-bold"><?= number_format((float) $planning['weekly_capacity'], 1, ',', '.') ?> JP</div><small class="text-muted"><?= (int) $planSettings['teaching_days_per_week'] ?> hari × <?= number_format((float) $planSettings['daily_jp_capacity'], 1, ',', '.') ?> JP/hari</small></div></div>
                <div class="col-sm-6 col-xl-3"><div class="planning-metric p-3 h-100"><div class="text-muted small">Kebutuhan guru</div><div class="fs-4 fw-bold text-primary"><?= number_format((float) $planSummary['teacher_demand_hours'], 1, ',', '.') ?> JP</div><small class="text-muted">Sudah memperhitungkan <?= (int) $planSummary['classroom_count'] ?> rombel</small></div></div>
                <div class="col-sm-6 col-xl-3"><div class="planning-metric p-3 h-100"><div class="text-muted small">Penyesuaian sekolah</div><div class="fs-4 fw-bold text-warning-emphasis"><?= (int) $planSummary['custom_count'] ?></div><small class="text-muted">Mapel memakai jam custom/manual</small></div></div>
                <div class="col-sm-6 col-xl-3"><div class="planning-metric p-3 h-100"><div class="d-flex justify-content-between"><div class="text-muted small">Jam telah dibagi</div><strong><?= number_format((float) $planSummary['allocation_percent'], 0) ?>%</strong></div><div class="fs-4 fw-bold text-success"><?= number_format((float) $planSummary['allocated_hours'], 1, ',', '.') ?> JP</div><div class="capacity-bar mt-1"><span class="bg-success" style="width:<?= min(100, (float) $planSummary['allocation_percent']) ?>%"></span></div></div></div>
            </div>

            <div class="row g-4">
                <div class="col-xl-8">
                    <div class="d-flex justify-content-between align-items-center mb-2"><h6 class="fw-bold mb-0">Kapasitas per tingkat</h6><span class="small text-muted">Batas <?= number_format((float) $planning['weekly_capacity'], 1, ',', '.') ?> JP/minggu</span></div>
                    <div class="table-responsive border rounded-3">
                        <table class="table planning-table mb-0">
                            <thead class="table-light"><tr><th class="ps-3">Tingkat</th><th class="text-center">Rombel</th><th class="text-end">JP resmi</th><th class="text-end">JP berlaku</th><th class="text-end">Kebutuhan guru</th><th style="min-width:180px">Kapasitas</th></tr></thead>
                            <tbody>
                            <?php foreach ($planning['grades'] as $gradePlan): ?>
                                <?php $usedPercent = $gradePlan['capacity'] > 0 ? min(100, ($gradePlan['effective_hours'] / $gradePlan['capacity']) * 100) : 0; ?>
                                <tr>
                                    <td class="ps-3"><strong><?= esc($gradePlan['code']) ?></strong><div class="small text-muted"><?= esc($gradePlan['name']) ?> · <?= (int) $gradePlan['subjects'] ?> mapel</div></td>
                                    <td class="text-center"><?= (int) $gradePlan['classrooms'] ?></td>
                                    <td class="text-end"><?= number_format((float) $gradePlan['official_hours'], 1, ',', '.') ?></td>
                                    <td class="text-end fw-bold"><?= number_format((float) $gradePlan['effective_hours'], 1, ',', '.') ?></td>
                                    <td class="text-end text-primary fw-semibold"><?= number_format((float) $gradePlan['teacher_demand_hours'], 1, ',', '.') ?></td>
                                    <td><div class="d-flex justify-content-between small mb-1"><span><?= number_format((float) $gradePlan['effective_hours'], 1, ',', '.') ?> JP</span><span class="<?= $gradePlan['remaining_capacity'] < 0 ? 'text-danger' : 'text-muted' ?>"><?= $gradePlan['remaining_capacity'] >= 0 ? 'Sisa ' : 'Lebih ' ?><?= number_format(abs((float) $gradePlan['remaining_capacity']), 1, ',', '.') ?></span></div><div class="capacity-bar"><span class="<?= $gradePlan['remaining_capacity'] < 0 ? 'bg-danger' : ($usedPercent > 85 ? 'bg-warning' : 'bg-primary') ?>" style="width:<?= $usedPercent ?>%"></span></div></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-xl-4">
                    <h6 class="fw-bold mb-3">Langkah berikutnya</h6>
                    <div class="planning-step mb-4" data-step="1"><strong>Lengkapi struktur</strong><div class="small text-muted"><?= (int) $planSummary['structure_count'] ?> struktur aktif; <?= (int) $planSummary['missing_block_pattern_count'] ?> belum memiliki pola blok jadwal.</div><a class="small fw-semibold" href="<?= base_url('curriculum/' . $version['uuid'] . '/matrix?unit_id=' . $filters['unit_id']) ?>">Buka editor matriks</a></div>
                    <div class="planning-step mb-4" data-step="2"><strong>Bagi kepada guru</strong><div class="small text-muted"><?= number_format((float) $planSummary['unallocated_hours'], 1, ',', '.') ?> JP kebutuhan belum dialokasikan.</div><a class="small fw-semibold" href="<?= base_url('assignments') ?>">Kelola pembagian mengajar</a></div>
                    <div class="planning-step" data-step="3"><strong>Buat jadwal otomatis</strong><div class="small text-muted">Jadwal menggunakan alokasi guru, pola blok, ruang, dan kapasitas 5 hari.</div><a class="small fw-semibold" href="<?= base_url('schedules') ?>">Buka penyusun jadwal</a></div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const capacityHint = document.querySelector('.planning-metric small.text-muted');
        if (capacityHint) capacityHint.textContent = <?= json_encode($capacitySummary, JSON_UNESCAPED_UNICODE) ?>;
    });
    </script>

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
            <td class="text-end pe-4"><?php if (has_permission('curriculum.manage')): ?><form action="<?= base_url('curriculum/' . $version['uuid'] . '/structures/' . $structure['uuid'] . '/delete') ?>" method="post" class="d-inline" data-confirm="Hapus <?= esc($structure['subject_name'] ?? 'mata pelajaran ini') ?> dari struktur?" data-confirm-title="Hapus struktur?" data-confirm-icon="warning" data-confirm-button="Hapus"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button></form><?php endif; ?></td>
        </tr><?php endforeach; endif; ?></tbody></table></div>
    </div>

    <?php if (!empty($validation['results'])): ?><details class="card border-0 shadow-sm mt-4"><summary class="card-header bg-white py-3 fw-semibold" style="cursor:pointer"><i class="bi bi-shield-check me-2"></i>Catatan pemeriksaan data (<?= (int) $validation['total_results'] ?>)</summary><div class="list-group list-group-flush"><?php foreach ($validation['results'] as $result): ?><div class="list-group-item"><span class="badge bg-<?= in_array($result['severity'], ['ERROR', 'BLOCKER'], true) ? 'danger' : 'warning text-dark' ?> me-2"><?= esc($result['severity']) ?></span><?= esc($result['message']) ?></div><?php endforeach; ?></div></details><?php endif; ?>
</div>

<div class="modal fade" id="planningSettingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content border-0 rounded-4" method="post" action="<?= base_url('curriculum/' . $version['uuid'] . '/planning-settings') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="unit_id" value="<?= (int) $filters['unit_id'] ?>">
            <div class="modal-header border-0 px-4 pt-4"><div><h5 class="modal-title fw-bold">Parameter Perencanaan Sekolah</h5><div class="text-muted small"><?= esc($selectedUnit['name'] ?? '') ?> · berlaku untuk kurikulum ini</div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body px-4"><div class="alert alert-primary border-0"><i class="bi bi-info-circle me-2"></i>Aturan ini menjadi dasar peringatan kapasitas, pembagian beban guru, dan generator jadwal.</div><div class="row g-3">
                <div class="col-md-3"><label class="form-label fw-semibold">Hari belajar per minggu</label><div class="input-group"><input type="number" class="form-control" name="teaching_days_per_week" min="1" max="7" required value="<?= (int) $planSettings['teaching_days_per_week'] ?>"><span class="input-group-text">hari</span></div><div class="form-text">Gunakan 5 hari.</div></div>
                <div class="col-md-3"><label class="form-label fw-semibold">Default JP per hari</label><div class="input-group"><input type="number" class="form-control" name="daily_jp_capacity" min="1" max="20" step="0.5" required value="<?= esc($planSettings['daily_jp_capacity']) ?>"><span class="input-group-text">JP</span></div><div class="form-text">Nilai awal per hari.</div></div>
                <div class="col-md-3"><label class="form-label fw-semibold">Durasi 1 JP (menit)</label><div class="input-group"><input type="number" class="form-control" name="minutes_per_jp" min="15" max="120" required value="<?= (int)($planSettings['minutes_per_jp'] ?? 40) ?>"><span class="input-group-text">menit</span></div><div class="form-text">SMP: 40 mnt, SMA: 45 mnt.</div></div>
                <div class="col-md-3"><label class="form-label fw-semibold">Waktu Mulai JP 1</label><input type="time" class="form-control" name="start_time_jp1" required value="<?= esc($planSettings['start_time_jp1'] ?? '07:30') ?>"><div class="form-text">Jam mulai JP 1 (e.g. 07.30).</div></div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Kapasitas JP per hari aktif</label>
                    <div class="row g-2">
                        <?php
                        $dayOrder = ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'];
                        foreach ($dayLabels as $dayCode => $dayName):
                        ?>
                            <?php
                            $isActiveDay = in_array($dayCode, $activeDayCodes, true);
                            $val = $isActiveDay ? esc($dailyCapacities[$dayCode] ?? $planSettings['daily_jp_capacity'] ?? 9) : '0';
                            ?>
                            <div class="col-6 col-md-3 col-xl">
                                <label class="form-label small text-muted mb-1"><?= esc($dayName) ?></label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control day-capacity-input" data-day-code="<?= esc($dayCode) ?>" name="daily_jp_capacities[<?= esc($dayCode) ?>]" min="0" max="20" step="0.5" value="<?= $val ?>" <?= $isActiveDay ? '' : 'disabled' ?>>
                                    <span class="input-group-text">JP</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="form-text">Untuk sistem 5 hari default: Senin-Kamis 9 JP, Jumat 7 JP. Sabtu & Minggu bernilai 0 JP (tidak dihitung).</div>
                </div>
                <div class="col-md-6"><label class="form-label fw-semibold">Beban minimum guru</label><div class="input-group"><input type="number" class="form-control" name="teacher_minimum_hours" min="0" step="0.5" required value="<?= esc($planSettings['teacher_minimum_hours'] ?? 24.0) ?>"><span class="input-group-text">JP</span></div></div>
                <div class="col-md-6"><label class="form-label fw-semibold">Beban maksimum guru</label><div class="input-group"><input type="number" class="form-control" name="teacher_maximum_hours" min="1" step="0.5" required value="<?= esc($planSettings['teacher_maximum_hours'] ?? 40.0) ?>"><span class="input-group-text">JP</span></div></div>
                <div class="col-12"><div class="form-check form-switch p-3 ps-5 border rounded-3"><input class="form-check-input" type="checkbox" name="allow_custom_hours" value="1" id="allowCustomHours" <?= !empty($planSettings['allow_custom_hours']) ? 'checked' : '' ?>><label class="form-check-label fw-semibold" for="allowCustomHours">Izinkan JP custom sekolah</label><div class="small text-muted">Dipakai untuk tambahan intrakurikuler, muatan lokal, Pathfinder, Kesehatan, SID, Chapel, dan kegiatan tetap lainnya.</div></div></div>
                <div class="col-12"><label class="form-label fw-semibold">Catatan kebijakan</label><textarea class="form-control" name="notes" rows="3" placeholder="Contoh: sekolah swasta, pembelajaran Senin-Jumat, kegiatan tetap masuk jadwal..."><?= esc($planSettings['notes'] ?? '') ?></textarea></div>
            </div></div>
            <div class="modal-footer border-0 px-4 pb-4"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary px-4"><i class="bi bi-check2-circle me-1"></i>Simpan & hitung ulang</button></div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const daysInput = document.querySelector('input[name="teaching_days_per_week"]');
    const dayOrder = ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'];
    const defaultDailyCap = document.querySelector('input[name="daily_jp_capacity"]');

    if (daysInput) {
        daysInput.addEventListener('input', function() {
            const count = parseInt(this.value) || 5;
            const defVal = parseFloat(defaultDailyCap?.value) || 9;

            dayOrder.forEach((code, idx) => {
                const inp = document.querySelector(`.day-capacity-input[data-day-code="${code}"]`);
                if (inp) {
                    if (idx < count) {
                        inp.disabled = false;
                        if (parseFloat(inp.value) === 0 || inp.value === '0') {
                            inp.value = (code === 'FRI' && defVal >= 8) ? 7 : defVal;
                        }
                    } else {
                        inp.disabled = true;
                        inp.value = '0';
                    }
                }
            });
        });
    }
});
</script>

<?php if (has_permission('curriculum.manage') && !empty($grades) && !empty($subjects)): ?>
<div class="modal fade" id="addStructureModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><form action="<?= base_url('curriculum/' . $version['uuid'] . '/structures') ?>" method="post" id="structureForm"><?= csrf_field() ?><input type="hidden" name="unit_id" value="<?= (int) $filters['unit_id'] ?>">
    <div class="modal-header"><div><h5 class="modal-title">Tambah mata pelajaran</h5><div class="small text-muted"><?= esc($selectedUnit['name'] ?? '') ?></div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-semibold">Tingkat <span class="text-danger">*</span></label><select name="grade_level_id" class="form-select" required><option value="">Pilih tingkat</option><?php foreach ($grades as $grade): ?><option value="<?= (int) $grade['id'] ?>"><?= esc($grade['code'] . ' · ' . $grade['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Kelas khusus <span class="text-muted fw-normal">(opsional)</span></label><select name="classroom_id" class="form-select"><option value="">Berlaku untuk semua kelas</option><?php foreach ($classrooms as $classroom): ?><option value="<?= (int) $classroom['id'] ?>"><?= esc($classroom['name']) ?></option><?php endforeach; ?></select><div class="form-text">Kosongkan untuk menerapkan ke seluruh kelas pada tingkat.</div></div>
        <div class="col-12"><label class="form-label fw-semibold">Mata pelajaran <span class="text-danger">*</span></label><select name="subject_id" class="form-select" required><option value="">Pilih mata pelajaran</option><?php foreach ($subjects as $subject): ?><option value="<?= (int) $subject['id'] ?>"><?= esc($subject['code'] . ' · ' . $subject['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Kategori</label><select name="category" class="form-select"><option value="INTRAKURIKULER">Intrakurikuler</option><option value="MUATAN_LOKAL">Muatan lokal</option><option value="KOKURIKULER">Kokurikuler</option><option value="EKSTRAKURIKULER">Ekstrakurikuler</option><option value="PENGEMBANGAN_DIRI">Pengembangan diri</option><option value="OTHER">Lainnya</option></select></div>
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
