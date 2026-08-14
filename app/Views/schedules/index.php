<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<?php
$canCreate = has_permission('schedules.manage') && ! empty($periods);
$statusClasses = [
    'DRAFT'     => 'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle',
    'VALIDATED' => 'bg-info-subtle text-info-emphasis border-info-subtle',
    'REVIEWED'  => 'bg-primary-subtle text-primary-emphasis border-primary-subtle',
    'APPROVED'  => 'bg-success-subtle text-success-emphasis border-success-subtle',
    'LOCKED'    => 'bg-dark bg-opacity-10 text-dark border',
    'ARCHIVED'  => 'bg-light text-muted border',
];
?>

<div class="mb-4">
    <!-- Hero Header Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-relative" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff;">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h4 class="fw-bold mb-0 text-white"><i class="bi bi-calendar3-week text-primary me-2"></i>Jadwal Pelajaran Sekolah</h4>
                    </div>
                    <p class="text-white-50 mb-0 small">
                        Unit <span class="text-white fw-semibold"><?= esc($unit['name'] ?? '-') ?></span> · Otomatisasi penyusunan slot waktu mengajar, ketersediaan guru, dan ruang kelas.
                    </p>
                </div>
                <div>
                    <?php if (has_permission('schedules.manage')): ?>
                        <button class="btn btn-primary rounded-3 px-4 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#createScheduleVersionModal" <?= $canCreate ? '' : 'disabled' ?>>
                            <i class="bi bi-calendar-plus me-1.5"></i> Buat Jadwal Otomatis
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (! $canCreate && has_permission('schedules.manage')): ?>
    <div class="alert alert-warning border-0 rounded-4 shadow-sm mb-4 d-flex gap-3 align-items-center bg-warning-subtle text-warning-emphasis p-3">
        <i class="bi bi-exclamation-triangle-fill fs-4 flex-shrink-0 text-warning"></i>
        <div>
            <strong class="d-block">Data Prasyarat Belum Lengkap</strong>
            <span class="small">Silakan aktifkan periode akademik, lengkapi struktur kurikulum, dan selesaikan pembagian tugas mengajar guru untuk unit ini agar tombol pembuatan jadwal aktif.</span>
        </div>
    </div>
<?php endif; ?>

<div class="alert alert-primary border-0 rounded-4 shadow-sm mb-4 d-flex gap-3 align-items-center">
    <i class="bi bi-magic fs-4 flex-shrink-0"></i>
    <div class="small"><strong>Aturan generator cerdas:</strong> beban mapel dipecah menjadi blok 2 JP + 2 JP + sisa 1 JP, diberi jeda minimal satu hari antarpertemuan, posisi JP diputar, dan guru tidak mengajar mapel berbeda di rombel yang sama pada hari yang sama.</div>
</div>

<!-- Quick Stat Widgets -->
<?php
$totalCount = count($versions);
$approvedCount = 0;
$draftCount = 0;
foreach ($versions as $vItem) {
    if (in_array(($vItem['workflow_status'] ?? ''), ['APPROVED', 'LOCKED'], true)) $approvedCount++;
    if (($vItem['workflow_status'] ?? '') === 'DRAFT') $draftCount++;
}
?>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-body d-flex flex-row align-items-center gap-3">
            <div class="rounded-3 bg-primary bg-opacity-10 p-3 text-primary d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                <i class="bi bi-calendar-week fs-4"></i>
            </div>
            <div>
                <div class="text-body-secondary small fw-semibold">Total Versi Jadwal</div>
                <div class="fs-4 fw-bold text-body mt-0"><?= $totalCount ?> <span class="fs-8 fw-normal text-body-secondary">Versi</span></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-body d-flex flex-row align-items-center gap-3">
            <div class="rounded-3 bg-success bg-opacity-10 p-3 text-success d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                <i class="bi bi-check-circle fs-4"></i>
            </div>
            <div>
                <div class="text-body-secondary small fw-semibold">Jadwal Disetujui / Kunci</div>
                <div class="fs-4 fw-bold text-success-emphasis mt-0"><?= $approvedCount ?> <span class="fs-8 fw-normal text-body-secondary">Disetujui</span></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-body d-flex flex-row align-items-center gap-3">
            <div class="rounded-3 bg-warning bg-opacity-10 p-3 text-warning d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                <i class="bi bi-pencil-square fs-4"></i>
            </div>
            <div>
                <div class="text-body-secondary small fw-semibold">Jadwal Draf / Penyusunan</div>
                <div class="fs-4 fw-bold text-warning-emphasis mt-0"><?= $draftCount ?> <span class="fs-8 fw-normal text-body-secondary">Draf</span></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-body border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 text-body"><i class="bi bi-list-columns-reverse text-primary me-2"></i>Daftar Versi Jadwal Pelajaran</h5>
        <span class="badge bg-body-tertiary text-body border rounded-pill px-3 py-1.5"><?= count($versions) ?> Versi Terdaftar</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-body-tertiary border-bottom">
                    <tr class="text-uppercase text-body-secondary fs-8 fw-bold">
                        <th class="text-center ps-4" style="width: 55px;">No.</th>
                        <th>Kode & Nama Jadwal</th>
                        <th>Periode Akademik</th>
                        <th>Sumber Penugasan & Kurikulum</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-4" style="min-width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($versions)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-body-secondary">
                            <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary"></i>
                            <h6 class="fw-bold text-body">Belum ada jadwal pelajaran</h6>
                            <p class="small text-muted mb-0">Setelah prasyarat lengkap, klik tombol "Buat Jadwal Otomatis".</p>
                        </td>
                    </tr>
                <?php else: foreach ($versions as $idx => $version):
                    $isCombined = empty($version['unit_id']);
                ?>
                    <tr>
                        <td class="text-center ps-4 fw-semibold text-body-secondary small"><?= $idx + 1 ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div class="fw-bold text-body mb-0"><?= esc($version['name']) ?></div>
                                <?php if ($isCombined): ?>
                                    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle rounded-pill px-2 py-0.5 fs-9 fw-bold">GABUNGAN</span>
                                <?php endif; ?>
                            </div>
                            <span class="badge bg-light text-primary border font-monospace fs-9"><?= esc($version['code']) ?></span>
                        </td>
                        <td>
                            <div class="small fw-semibold text-body"><?= esc($version['period_name'] ?? '-') ?></div>
                        </td>
                        <td>
                            <?php if ($isCombined): ?>
                                <div class="small fw-semibold text-success"><i class="bi bi-layers me-1"></i>Semua Penugasan Aktif (SMP + SMA)</div>
                                <small class="text-body-secondary"><i class="bi bi-building me-1"></i>Jadwal Gabungan Multi-Unit</small>
                            <?php else: ?>
                                <div class="small fw-semibold text-body"><?= esc($version['assignment_name'] ?? '-') ?></div>
                                <small class="text-body-secondary"><i class="bi bi-journal-bookmark me-1"></i><?= esc($version['curriculum_name'] ?? '-') ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge rounded-pill border px-3 py-1 fw-bold <?= $statusClasses[$version['workflow_status']] ?? 'bg-secondary' ?>">
                                <?= esc($version['workflow_status']) ?>
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-flex align-items-center justify-content-end gap-1">
                                <?php if (has_permission('schedules.manage')): ?>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-secondary rounded-3 px-2.5 py-1.5 fw-semibold edit-schedule-btn"
                                            data-id="<?= $version['id'] ?>"
                                            data-code="<?= esc($version['code']) ?>"
                                            data-name="<?= esc($version['name']) ?>"
                                            data-description="<?= esc($version['description'] ?? '') ?>"
                                            data-period="<?= $version['academic_period_id'] ?>"
                                            data-curriculum="<?= $version['curriculum_version_id'] ?? '' ?>"
                                            data-assignment="<?= $version['assignment_version_id'] ?? '' ?>"
                                            data-is-combined="<?= $isCombined ? '1' : '0' ?>"
                                            title="Edit Pengaturan Versi Jadwal">
                                        <i class="bi bi-pencil me-1"></i> Edit
                                    </button>
                                <?php endif; ?>
                                <a href="<?= base_url('schedules/' . $version['id'] . '/editor') ?>" class="btn btn-sm btn-outline-primary rounded-3 px-3 py-1.5 fw-semibold shadow-sm">
                                    <i class="bi bi-grid-3x3-gap me-1"></i> Buka Editor
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Info Banner -->
    <div class="alert alert-light border rounded-4 p-3.5 mb-4 shadow-sm">
        <div class="d-flex align-items-start gap-3">
            <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
                <i class="bi bi-info-circle-fill fs-5"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-1">Panduan Pengaturan Jadwal Pelajaran</h6>
                <p class="small text-muted mb-0">
                    Gunakan <strong>Jadwal Gabungan (SMP + SMA)</strong> untuk menyusun jadwal gabungan lintas unit yang otomatis mencegah bentrok pengajaran guru.
                    Status draf dapat diubah ke <strong>DIPILIH/AKTIF</strong> setelah tuntas dari editor jadwal.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Create Schedule Version -->
<div class="modal fade" id="createScheduleVersionModal" tabindex="-1" aria-labelledby="createScheduleVersionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= base_url('schedules/create') ?>" class="modal-content border-0 shadow-lg rounded-4">
            <?= csrf_field() ?>
            <div class="modal-header bg-gradient bg-light p-3">
                <h5 class="modal-header-title fw-bold text-dark mb-0" id="createScheduleVersionModalLabel">
                    <i class="bi bi-plus-circle text-primary me-2"></i>Buat Versi Jadwal Pelajaran Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <?php if (session('errors')): ?>
                    <div class="alert alert-danger rounded-3 py-2 px-3 mb-3">
                        <ul class="mb-0 small ps-3">
                            <?php foreach (session('errors') as $error): ?>
                                <li><?= esc($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="row g-3">
                    <!-- Scope Type Selector -->
                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">Lingkup Jadwal <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2" id="scopeSelector">
                            <input type="radio" class="btn-check" name="scope_type" id="scopeCombined" value="COMBINED" autocomplete="off" checked>
                            <label class="btn btn-outline-success rounded-3 px-4 py-2 fw-bold flex-fill text-center" for="scopeCombined">
                                <i class="bi bi-layers me-1"></i> Gabungan SMP + SMA
                                <small class="d-block fw-normal text-muted mt-1">Menarik semua penugasan aktif pada periode terpilih. Anti-bentrok guru lintas unit.</small>
                            </label>
                            <input type="radio" class="btn-check" name="scope_type" id="scopeSingle" value="SINGLE" autocomplete="off">
                            <label class="btn btn-outline-primary rounded-3 px-4 py-2 fw-bold flex-fill text-center" for="scopeSingle">
                                <i class="bi bi-building me-1"></i> Unit Tunggal (<?= esc($unit['name'] ?? 'Unit') ?>)
                                <small class="d-block fw-normal text-muted mt-1">Hanya untuk unit sekolah aktif saat ini.</small>
                            </label>
                        </div>
                    </div>

                    <!-- Combined Info Alert -->
                    <div class="col-12" id="combinedInfoAlert">
                        <div class="alert alert-success border-0 rounded-3 py-2 px-3 mb-0 d-flex align-items-start gap-2 fs-8">
                            <i class="bi bi-info-circle-fill mt-0.5"></i>
                            <div>Jadwal Gabungan akan otomatis menarik <strong>semua Versi Penugasan Mengajar Aktif</strong> (SMP + SMA) pada periode akademik yang dipilih. Kelas SMP dan SMA digabung dalam satu kanvas penjadwalan.</div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">Periode Akademik <span class="text-danger">*</span></label>
                        <select name="academic_period_id" id="schedulePeriod" class="form-select rounded-3" required>
                            <option value="">-- Pilih Periode Akademik --</option>
                            <?php foreach ($periods as $period): ?>
                                <?php
                                $pText = 'T.A. ' . esc($period['year_name'] ?? '') . ' - Semester ' . esc($period['semester_number'] ?? '') . ((int)($period['semester_number'] ?? 0) === 1 ? ' (Ganjil)' : ' (Genap)');
                                if(!empty(trim($period['name'] ?? ''))) { $pText = esc($period['name']) . ' (' . $pText . ')'; }
                                ?>
                                <option value="<?= $period['id'] ?>" <?= old('academic_period_id') == $period['id'] ? 'selected' : '' ?>><?= $pText ?><?= (int)$period['is_active'] === 1 ? ' · Aktif' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12" id="singleUnitFields">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">Struktur Kurikulum Target</label>
                                <select name="curriculum_version_id" id="scheduleCurriculum" class="form-select rounded-3">
                                    <option value="">-- Pilih Struktur Kurikulum --</option>
                                    <?php foreach ($curricula as $curriculum): ?>
                                        <option value="<?= $curriculum['id'] ?>" data-period="<?= $curriculum['academic_period_id'] ?>"><?= esc($curriculum['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">Pembagian Tugas Guru</label>
                                <select name="assignment_version_id" id="scheduleAssignment" class="form-select rounded-3">
                                    <option value="">-- Pilih Versi Penugasan Mengajar --</option>
                                    <?php foreach ($assignments as $assignment): ?>
                                        <option value="<?= $assignment['id'] ?>" data-period="<?= $assignment['academic_period_id'] ?>" data-curriculum="<?= $assignment['curriculum_version_id'] ?>"><?= esc($assignment['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text fs-9 text-muted"><i class="bi bi-info-circle me-1"></i>Hanya versi penugasan yang sesuai dengan periode & kurikulum yang aktif.</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-muted">Kode Jadwal <span class="text-danger">*</span></label>
                        <input name="code" class="form-control rounded-3 font-monospace" maxlength="50" value="<?= esc(old('code')) ?>" placeholder="JDW-2026-GJL" required>
                    </div>

                    <div class="col-md-7">
                        <label class="form-label small fw-bold text-muted">Nama Versi Jadwal <span class="text-danger">*</span></label>
                        <input name="name" class="form-control rounded-3" maxlength="150" value="<?= esc(old('name')) ?>" placeholder="Jadwal Pelajaran Semester Ganjil 2026/2027" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">Catatan Operasional <span class="text-muted fw-normal">(Opsional)</span></label>
                        <textarea name="description" class="form-control rounded-3" rows="2" placeholder="Catatan khusus jadwal..."><?= esc(old('description')) ?></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light p-3">
                <button type="button" class="btn btn-light rounded-3 px-4 fw-semibold" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary rounded-3 px-4 fw-bold shadow-sm">
                    <i class="bi bi-rocket-takeoff me-1"></i> Buat & Siapkan Otomatis
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Schedule Version -->
<div class="modal fade" id="editScheduleVersionModal" tabindex="-1" aria-labelledby="editScheduleVersionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" id="editScheduleForm" action="" class="modal-content border-0 shadow-lg rounded-4">
            <?= csrf_field() ?>
            <div class="modal-header bg-gradient bg-light p-3">
                <h5 class="modal-header-title fw-bold text-dark mb-0" id="editScheduleVersionModalLabel">
                    <i class="bi bi-pencil-square text-primary me-2"></i>Edit Pengaturan Versi Jadwal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <!-- Scope Type Selector -->
                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">Lingkup Jadwal <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2" id="editScopeSelector">
                            <input type="radio" class="btn-check" name="scope_type" id="editScopeCombined" value="COMBINED" autocomplete="off">
                            <label class="btn btn-outline-success rounded-3 px-4 py-2 fw-bold flex-fill text-center" for="editScopeCombined">
                                <i class="bi bi-layers me-1"></i> Gabungan SMP + SMA
                                <small class="d-block fw-normal text-muted mt-1">Menarik semua penugasan aktif (SMP + SMA). Anti-bentrok guru lintas unit.</small>
                            </label>
                            <input type="radio" class="btn-check" name="scope_type" id="editScopeSingle" value="SINGLE" autocomplete="off">
                            <label class="btn btn-outline-primary rounded-3 px-4 py-2 fw-bold flex-fill text-center" for="editScopeSingle">
                                <i class="bi bi-building me-1"></i> Unit Tunggal (<?= esc($unit['name'] ?? 'Unit') ?>)
                                <small class="d-block fw-normal text-muted mt-1">Hanya untuk unit sekolah aktif saat ini.</small>
                            </label>
                        </div>
                    </div>

                    <!-- Combined Info Alert -->
                    <div class="col-12" id="editCombinedInfoAlert">
                        <div class="alert alert-success border-0 rounded-3 py-2 px-3 mb-0 d-flex align-items-start gap-2 fs-8">
                            <i class="bi bi-info-circle-fill mt-0.5"></i>
                            <div>Mengubah ke <strong>Jadwal Gabungan</strong> akan otomatis mengisap dan mengombinasikan <strong>semua Versi Penugasan Mengajar Aktif</strong> (SMP + SMA) pada periode akademik ini.</div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">Periode Akademik <span class="text-danger">*</span></label>
                        <select name="academic_period_id" id="editSchedulePeriod" class="form-select rounded-3" required>
                            <option value="">-- Pilih Periode Akademik --</option>
                            <?php foreach ($periods as $period): ?>
                                <?php
                                $pText = 'T.A. ' . esc($period['year_name'] ?? '') . ' - Semester ' . esc($period['semester_number'] ?? '') . ((int)($period['semester_number'] ?? 0) === 1 ? ' (Ganjil)' : ' (Genap)');
                                if(!empty(trim($period['name'] ?? ''))) { $pText = esc($period['name']) . ' (' . $pText . ')'; }
                                ?>
                                <option value="<?= $period['id'] ?>"><?= $pText ?><?= (int)$period['is_active'] === 1 ? ' · Aktif' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12" id="editSingleUnitFields">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">Struktur Kurikulum Target</label>
                                <select name="curriculum_version_id" id="editScheduleCurriculum" class="form-select rounded-3">
                                    <option value="">-- Pilih Struktur Kurikulum --</option>
                                    <?php foreach ($curricula as $curriculum): ?>
                                        <option value="<?= $curriculum['id'] ?>" data-period="<?= $curriculum['academic_period_id'] ?>"><?= esc($curriculum['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">Pembagian Tugas Guru</label>
                                <select name="assignment_version_id" id="editScheduleAssignment" class="form-select rounded-3">
                                    <option value="">-- Pilih Versi Penugasan Mengajar --</option>
                                    <?php foreach ($assignments as $assignment): ?>
                                        <option value="<?= $assignment['id'] ?>" data-period="<?= $assignment['academic_period_id'] ?>" data-curriculum="<?= $assignment['curriculum_version_id'] ?>"><?= esc($assignment['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-muted">Kode Jadwal <span class="text-danger">*</span></label>
                        <input name="code" id="editScheduleCode" class="form-control rounded-3 font-monospace" maxlength="50" required>
                    </div>

                    <div class="col-md-7">
                        <label class="form-label small fw-bold text-muted">Nama Versi Jadwal <span class="text-danger">*</span></label>
                        <input name="name" id="editScheduleName" class="form-control rounded-3" maxlength="150" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">Catatan Operasional <span class="text-muted fw-normal">(Opsional)</span></label>
                        <textarea name="description" id="editScheduleDescription" class="form-control rounded-3" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light p-3">
                <button type="button" class="btn btn-light rounded-3 px-4 fw-semibold" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary rounded-3 px-4 fw-bold shadow-sm">
                    <i class="bi bi-save me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Create Modal logic ---
    const period = document.getElementById('schedulePeriod');
    const curriculum = document.getElementById('scheduleCurriculum');
    const assignment = document.getElementById('scheduleAssignment');
    const singleUnitFields = document.getElementById('singleUnitFields');
    const combinedInfoAlert = document.getElementById('combinedInfoAlert');
    const scopeRadios = document.querySelectorAll('input[name="scope_type"]');

    function updateScope() {
        const isCombined = document.getElementById('scopeCombined')?.checked;
        if (singleUnitFields) singleUnitFields.style.display = isCombined ? 'none' : '';
        if (combinedInfoAlert) combinedInfoAlert.style.display = isCombined ? '' : 'none';
        if (isCombined) {
            if (curriculum) { curriculum.value = ''; curriculum.removeAttribute('required'); }
            if (assignment) { assignment.value = ''; assignment.removeAttribute('required'); }
        } else {
            filter();
        }
    }

    scopeRadios.forEach(r => r.addEventListener('change', updateScope));
    updateScope();

    const filter = () => {
        const periodId = period?.value || '';
        [...(curriculum?.options || [])].forEach(o => { if (o.value) o.hidden = !!periodId && o.dataset.period !== periodId; });
        if (curriculum?.selectedOptions[0]?.hidden) curriculum.value = '';
        const curriculumId = curriculum?.value || '';
        [...(assignment?.options || [])].forEach(o => { if (o.value) o.hidden = (!!periodId && o.dataset.period !== periodId) || (!!curriculumId && o.dataset.curriculum !== curriculumId); });
        if (assignment?.selectedOptions[0]?.hidden) assignment.value = '';
    };
    period?.addEventListener('change', filter);
    curriculum?.addEventListener('change', filter);
    filter();

    // --- Edit Modal logic ---
    const editForm = document.getElementById('editScheduleForm');
    const editPeriod = document.getElementById('editSchedulePeriod');
    const editCurriculum = document.getElementById('editScheduleCurriculum');
    const editAssignment = document.getElementById('editScheduleAssignment');
    const editSingleUnitFields = document.getElementById('editSingleUnitFields');
    const editCombinedInfoAlert = document.getElementById('editCombinedInfoAlert');
    const editScopeCombined = document.getElementById('editScopeCombined');
    const editScopeSingle = document.getElementById('editScopeSingle');
    const editScopeRadios = editForm ? editForm.querySelectorAll('input[name="scope_type"]') : [];

    function updateEditScope() {
        const isCombined = editScopeCombined?.checked;
        if (editSingleUnitFields) editSingleUnitFields.style.display = isCombined ? 'none' : '';
        if (editCombinedInfoAlert) editCombinedInfoAlert.style.display = isCombined ? '' : 'none';
        if (!isCombined) {
            filterEdit();
        }
    }

    editScopeRadios.forEach(r => r.addEventListener('change', updateEditScope));

    const filterEdit = () => {
        const periodId = editPeriod?.value || '';
        [...(editCurriculum?.options || [])].forEach(o => { if (o.value) o.hidden = !!periodId && o.dataset.period !== periodId; });
        if (editCurriculum?.selectedOptions[0]?.hidden) editCurriculum.value = '';
        const curriculumId = editCurriculum?.value || '';
        [...(editAssignment?.options || [])].forEach(o => { if (o.value) o.hidden = (!!periodId && o.dataset.period !== periodId) || (!!curriculumId && o.dataset.curriculum !== curriculumId); });
        if (editAssignment?.selectedOptions[0]?.hidden) editAssignment.value = '';
    };
    editPeriod?.addEventListener('change', filterEdit);
    editCurriculum?.addEventListener('change', filterEdit);

    const baseUrl = '<?= base_url('schedules') ?>';
    document.querySelectorAll('.edit-schedule-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const code = this.dataset.code;
            const name = this.dataset.name;
            const description = this.dataset.description;
            const periodId = this.dataset.period;
            const curriculumId = this.dataset.curriculum;
            const assignmentId = this.dataset.assignment;
            const isCombined = this.dataset.isCombined === '1';

            editForm.action = `${baseUrl}/${id}/update`;
            document.getElementById('editScheduleCode').value = code;
            document.getElementById('editScheduleName').value = name;
            document.getElementById('editScheduleDescription').value = description;
            editPeriod.value = periodId;

            if (isCombined) {
                if (editScopeCombined) editScopeCombined.checked = true;
            } else {
                if (editScopeSingle) editScopeSingle.checked = true;
            }
            updateEditScope();

            if (!isCombined) {
                filterEdit();
                if (curriculumId && editCurriculum) editCurriculum.value = curriculumId;
                filterEdit();
                if (assignmentId && editAssignment) editAssignment.value = assignmentId;
            }

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editScheduleVersionModal'));
            modal.show();
        });
    });

    <?php if (session('errors')): ?>
        bootstrap.Modal.getOrCreateInstance(document.getElementById('createScheduleVersionModal')).show();
    <?php endif; ?>
});
</script>
<?= $this->endSection() ?>
