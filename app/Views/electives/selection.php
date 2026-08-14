<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php
$primarySelected = [];
$backupSelected = [];
foreach ($choices as $choice) {
    if ($choice['choice_type'] === 'PRIMARY') $primarySelected[(int) $choice['offering_id']] = (int) $choice['priority_order'];
    if ($choice['choice_type'] === 'BACKUP') $backupSelected[(int) $choice['offering_id']] = (int) $choice['priority_order'];
}

// Restore the wali kelas' posted choices after a validation redirect.
$oldPrimaryPriority = old('primary_priority');
$oldBackupPriority = old('backup_priority');
if (is_array($oldPrimaryPriority)) {
    $primarySelected = [];
    foreach ($oldPrimaryPriority as $offeringId => $priority) {
        if ((int) $priority > 0) $primarySelected[(int) $offeringId] = (int) $priority;
    }
} elseif (is_array(old('primary'))) {
    $primarySelected = [];
    foreach (array_values(old('primary')) as $index => $offeringId) {
        $primarySelected[(int) $offeringId] = $index + 1;
    }
}
if (is_array($oldBackupPriority)) {
    $backupSelected = [];
    foreach ($oldBackupPriority as $offeringId => $priority) {
        if ((int) $priority > 0) $backupSelected[(int) $offeringId] = (int) $priority;
    }
} elseif (is_array(old('backup'))) {
    $backupSelected = [];
    foreach (array_values(old('backup')) as $index => $offeringId) {
        $backupSelected[(int) $offeringId] = $index + 1;
    }
}
$periodAcceptsEntries = in_array((string) ($period['status'] ?? ''), ['PUBLISHED', 'SELECTION_OPEN'], true);
$editable = $periodAcceptsEntries
    && (! $submission || in_array($submission['status'], ['DRAFT', 'NEEDS_REVISION', 'SUBMITTED'], true));
$changeable = $periodAcceptsEntries && $selfService && (int) ($period['target_grade'] ?? 0) !== 12
    && $submission && in_array($submission['status'], ['APPROVED', 'FINALIZED', 'CHANGED'], true)
    && (int) $period['allow_changes'] === 1 && ! empty($period['change_deadline'])
    && date('Y-m-d') <= $period['change_deadline'];
$choiceEditable = $editable || $changeable;
$base = $selfService ? 'my-electives' : 'electives/' . $period['id'] . '/students/' . $student['id'] . '/selection';

$minPrimary = (int) ($period['min_primary_choices'] ?? 4);
$maxPrimary = (int) ($period['max_primary_choices'] ?? 5);
$maxBackup  = (int) ($period['max_backup_choices'] ?? 2);
?>

<?php if ($selfService && ! empty($activePeriods) && count($activePeriods) > 1): ?>
    <ul class="nav nav-pills mb-4 p-1 bg-light rounded-4">
        <?php foreach ($activePeriods as $actP): ?>
            <li class="nav-item">
                <a class="nav-link rounded-3 fw-semibold px-4 <?= (int) $actP['elective_period_id'] === (int) $period['id'] ? 'active' : '' ?>" href="<?= base_url('my-electives?period_id=' . $actP['elective_period_id']) ?>">
                    <i data-lucide="book-open" class="me-2" style="width:16px"></i>
                    <?= esc($actP['period_title']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <?php if (! $selfService): ?>
            <a href="<?= base_url('electives/' . $period['id']) ?>" class="btn btn-sm btn-light border rounded-3 mb-2">
                <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Kembali ke Periode
            </a>
        <?php endif ?>
        <h4 class="fw-bold mb-1">Pilihan Mata Pelajaran Fase F SMA</h4>
        <p class="text-muted mb-0">
            <strong><?= esc($student['full_name']) ?></strong> (<code><?= esc($student['student_number']) ?></code>) · Pilih <strong><?= $minPrimary ?>–<?= $maxPrimary ?> Mapel Utama</strong>. Pilihan cadangan <strong>opsional</strong> (maksimal <?= $maxBackup ?>).
        </p>
    </div>
    <div>
        <span class="badge text-bg-light border fs-7 py-2 px-3 rounded-3 fw-normal">
            <i data-lucide="calendar" class="me-1 text-primary" style="width:14px;height:14px;"></i> <?= esc($period['title']) ?>
        </span>
    </div>
</div>

<?php if (! $periodAcceptsEntries): ?>
    <div class="alert alert-warning border-0 shadow-sm rounded-4 d-flex align-items-start gap-2 mb-4" role="alert">
        <i data-lucide="lock" class="mt-1" style="width:18px;height:18px;"></i>
        <div>
            <strong>Form belum dibuka.</strong>
            Periode masih berstatus <?= esc((string) ($period['status'] ?? 'DRAFT')) ?>.
            Admin harus mempublikasikan periode sebelum wali kelas dapat menyimpan atau mengirim pilihan.
        </div>
    </div>
<?php endif ?>

<?php if ($submission): ?>
    <div class="alert alert-light border shadow-sm rounded-4 d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="info" class="text-primary" style="width:20px;height:20px;"></i>
            <div>
                <strong>Status Pengajuan:</strong>
                <span class="badge text-bg-primary fs-7 ms-1"><?= esc($submission['status']) ?></span>
                <?php if ($submission['submitted_at']): ?> · Dikirim <?= esc(date('d M Y H:i', strtotime($submission['submitted_at']))) ?><?php endif ?>
            </div>
        </div>
        <div>
            <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-1 fs-8">Versi Draf #<?= esc($submission['revision_number'] ?? 1) ?></span>
        </div>
    </div>
<?php endif ?>

<!-- SMART SELECTION PROGRESS WIDGET -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-gradient" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
    <div class="card-body p-4">
        <div class="row g-4 align-items-center">
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-4">
                        <i data-lucide="check-square" style="width:28px;height:28px;"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-semibold fs-8 d-block">PRIORITAS UTAMA</small>
                        <h5 class="fw-bold mb-0 text-slate-800" id="primaryCounterDisplay">0 / <?= $minPrimary ?>-<?= $maxPrimary ?> Mapel</h5>
                        <div class="progress mt-2" style="height: 6px; width: 140px;">
                            <div class="progress-bar bg-primary rounded-pill" id="primaryProgressBar" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 border-start border-light-subtle ps-md-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-4">
                        <i data-lucide="bookmark" style="width:28px;height:28px;"></i>
                    </div>
                    <div>
                        <small class="text-muted fw-semibold fs-8 d-block">MAPEL CADANGAN (OPSIONAL)</small>
                        <h5 class="fw-bold mb-0 text-slate-800" id="backupCounterDisplay">0 / <?= $maxBackup ?> Mapel</h5>
                        <div class="progress mt-2" style="height: 6px; width: 140px;">
                            <div class="progress-bar bg-warning rounded-pill" id="backupProgressBar" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 border-start border-light-subtle ps-md-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted fw-semibold fs-8 d-block">TOTAL BEBAN JAM (JP)</small>
                        <h5 class="fw-bold mb-0 text-primary" id="totalJpDisplay">0 JP / Mingguan</h5>
                    </div>
                    <div>
                        <span class="badge text-bg-secondary rounded-pill px-3 py-2 fs-8" id="statusBadge">
                            <i data-lucide="clock" class="me-1" style="width:12px;height:12px;"></i> Belum Lengkap
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form method="post" class="card border-0 shadow-sm rounded-4 mb-4" id="selectionForm">
    <?= csrf_field() ?>
    <input type="hidden" name="period_id" value="<?= esc($period['id']) ?>">

    <div class="card-body p-4">
        <!-- Rencana Karir & Studi -->
        <h6 class="fw-bold mb-3 text-slate-800 d-flex align-items-center gap-2">
            <i data-lucide="compass" class="text-primary" style="width:18px;height:18px;"></i> Rencana Studi & Profil Siswa
        </h6>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label fw-semibold fs-8">Rencana Setelah Lulus</label>
                <input class="form-control rounded-3 fs-8" name="career_plan" placeholder="Contoh: Kuliah S1 / Kerja / Kedinasan" maxlength="500" value="<?= esc(old('career_plan', $submission['career_plan'] ?? '')) ?>" <?= $editable ? '' : 'disabled' ?>>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold fs-8">Program Studi yang Diminati</label>
                <input class="form-control rounded-3 fs-8" name="intended_major" placeholder="Contoh: Kedokteran / Teknik Informatika" maxlength="200" value="<?= esc(old('intended_major', $submission['intended_major'] ?? '')) ?>" <?= $editable ? '' : 'disabled' ?>>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold fs-8">Alasan Pemilihan</label>
                <input class="form-control rounded-3 fs-8" name="selection_reason" placeholder="Keterangan singkat motivasi memilih mapel ini" maxlength="1000" value="<?= esc(old('selection_reason', $submission['selection_reason'] ?? '')) ?>" <?= $editable ? '' : 'disabled' ?>>
            </div>
        </div>

        <hr class="my-4 border-light-subtle">

        <!-- Header Table Mapel Pilihan -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0 text-slate-800 d-flex align-items-center gap-2">
                <i data-lucide="layers" class="text-primary" style="width:18px;height:18px;"></i> Penawaran Mata Pelajaran Pilihan
            </h6>
            <span class="fs-8 text-muted"><i data-lucide="sparkles" class="text-warning me-1" style="width:14px;height:14px;"></i>Sistem otomatis memblokir pilihan ganda/cadangan yang tidak valid.</span>
        </div>

        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0" id="offeringsTable">
                <thead>
                    <tr class="text-uppercase text-muted fs-8 fw-bold bg-light">
                        <th class="text-center ps-3" style="width: 45px;">No.</th>
                        <th>Mata Pelajaran</th>
                        <th>Pengampu / Guru</th>
                        <th class="text-center" style="width: 100px;">Beban JP</th>
                        <th class="text-center" style="width: 165px;">Prioritas Utama</th>
                        <th class="text-center" style="width: 165px;">Cadangan (Opsional)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($offerings as $idx => $offering): $id=(int)$offering['id']; ?>
                        <?php
                        $jp = (float)($offering['weekly_hours'] ?? 0);
                        $pVal = $primarySelected[$id] ?? 0;
                        $bVal = $backupSelected[$id] ?? 0;
                        $infoText = trim((string)($offering['study_relevance'] ?: $offering['description'] ?? ''));
                        ?>
                        <tr class="offering-row transition-all" id="row-<?= $id ?>" data-jp="<?= $jp ?>">
                            <td class="text-center fw-bold text-secondary fs-8 ps-3"><?= $idx + 1 ?>.</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <strong class="text-slate-800 fs-7 subject-name"><?= esc($offering['subject_name']) ?></strong>
                                            <span class="font-monospace fs-8 text-primary fw-bold bg-primary-subtle px-2 py-0.5 rounded-2"><?= esc($offering['subject_code']) ?></span>
                                            <?php if ($infoText !== ''): ?>
                                                <button type="button" class="btn btn-xs rounded-pill px-2 py-0.5 border-0 bg-info-subtle text-info fw-semibold fs-9 d-inline-flex align-items-center gap-1 shadow-2xs cursor-pointer ms-1" data-bs-toggle="modal" data-bs-target="#infoModal-<?= $id ?>" title="Lihat Deskripsi & Relevansi Studi">
                                                    <i data-lucide="info" style="width:12px;height:12px;"></i> Info Mapel
                                                </button>
                                            <?php endif ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-semibold text-slate-700 fs-8 d-block"><?= esc($offering['teacher_name'] ?: 'Belum Ditentukan') ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge text-bg-primary-subtle text-primary fw-bold px-2.5 py-1 rounded-3 fs-8"><?= esc($offering['weekly_hours']) ?> JP</span>
                            </td>
                            <td>
                                <select class="form-select form-select-sm rounded-3 choice-priority text-center fw-semibold" data-offering-id="<?= $id ?>" data-kind="primary" name="primary_priority[<?= $id ?>]" <?= $choiceEditable ? '' : 'disabled' ?>>
                                    <option value="">— Pilih —</option>
                                    <?php for($i=1;$i<=$maxPrimary;$i++): ?>
                                        <option value="<?= $i ?>" <?= $pVal === $i ? 'selected' : '' ?>>Prioritas <?= $i ?></option>
                                    <?php endfor ?>
                                </select>
                            </td>
                            <td>
                                <select class="form-select form-select-sm rounded-3 choice-priority text-center fw-semibold" data-offering-id="<?= $id ?>" data-kind="backup" name="backup_priority[<?= $id ?>]" <?= $choiceEditable ? '' : 'disabled' ?>>
                                    <option value="">— Pilih —</option>
                                    <?php for($i=1;$i<=$maxBackup;$i++): ?>
                                        <option value="<?= $i ?>" <?= $bVal === $i ? 'selected' : '' ?>>Cadangan <?= $i ?></option>
                                    <?php endfor ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <div id="choiceError" class="alert alert-danger border-0 rounded-4 mt-3 py-2 px-3 fs-8 fw-semibold d-none"></div>
        <div id="choiceWarning" class="alert alert-warning border-0 rounded-4 mt-3 py-2 px-3 fs-8 fw-semibold d-none"></div>
    </div>

    <?php if ($editable): ?>
        <div class="card-footer bg-white border-0 p-4 pt-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <small class="text-muted fs-8"><i data-lucide="shield-check" class="text-success me-1" style="width:14px;height:14px;"></i>Pilihan yang dikirim akan diverifikasi oleh Tim Kurikulum / BK.</small>
            <div class="d-flex gap-2">
                <button type="submit" name="selection_action" value="draft" formaction="<?= base_url($base . '/save') ?>" class="btn btn-outline-primary rounded-3 px-4">
                    <i data-lucide="save" class="me-1" style="width:16px;height:16px;"></i> Simpan Draf
                </button>
                <button type="submit" name="selection_action" value="submit" formaction="<?= base_url($base . '/submit') ?>" class="btn btn-primary rounded-3 px-4 shadow-sm" id="submitBtn">
                    <i data-lucide="send" class="me-1" style="width:16px;height:16px;"></i> Kirim Pilihan
                </button>
            </div>
        </div>
    <?php endif ?>

    <?php if ($changeable): ?>
        <div class="card-footer bg-white border-0 p-4 pt-0 border-top mt-3">
            <label class="form-label fw-semibold fs-8 text-slate-800">Alasan Perubahan Pilihan (Wajib Diisi)</label>
            <textarea class="form-control rounded-3 fs-8 mb-3" name="change_reason" placeholder="Jelaskan alasan pengajuan ulang/perubahan pilihan mata pelajaran..." required rows="3"></textarea>
            <div class="text-end">
                <button type="submit" name="selection_action" value="change" formaction="<?= base_url('my-electives/change-request') ?>" class="btn btn-warning rounded-3 px-4 shadow-sm">
                    <i data-lucide="refresh-cw" class="me-1" style="width:16px;height:16px;"></i> Ajukan Perubahan Pilihan
                </button>
            </div>
        </div>
    <?php endif ?>
</form>

<?php if ($reviews): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 p-4"><h5 class="mb-0 fw-bold fs-6">Riwayat Review</h5></div>
        <div class="card-body pt-0">
            <?php foreach ($reviews as $review): ?>
                <div class="border rounded-3 p-3 mb-2 bg-light">
                    <strong class="fs-7 text-dark"><?= esc($review['review_type']) ?> · <?= esc($review['status']) ?></strong>
                    <small class="d-block text-muted fs-8"><?= esc($review['reviewer_name']) ?> · <?= esc($review['reviewed_at']) ?></small>
                    <?php if ($review['notes']): ?><p class="mb-0 mt-2 fs-8 text-slate-700"><?= esc($review['notes']) ?></p><?php endif ?>
                </div>
            <?php endforeach ?>
        </div>
    </div>
<?php endif ?>

<?php if ($changeRequests): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 p-4"><h5 class="mb-0 fw-bold fs-6">Pengajuan Perubahan</h5></div>
        <div class="card-body pt-0">
            <?php foreach ($changeRequests as $request): ?>
                <div class="border rounded-3 p-3 mb-2 bg-light">
                    <strong class="fs-7 text-dark"><?= esc($request['status']) ?></strong>
                    <small class="d-block text-muted fs-8"><?= esc($request['requested_at']) ?></small>
                    <p class="my-2 fs-8 text-slate-700"><?= esc($request['reason']) ?></p>
                    <?php if (! $selfService && $request['status'] === 'PENDING' && has_permission('electives.change.approve')): ?>
                        <form method="post" action="<?= base_url('electives/' . $period['id'] . '/change-requests/' . $request['id'] . '/review') ?>" class="row g-2">
                            <?= csrf_field() ?>
                            <div class="col-md-3"><select class="form-select form-select-sm rounded-3" name="decision"><option value="APPROVED">Setujui</option><option value="REJECTED">Tolak</option></select></div>
                            <div class="col-md-7"><input class="form-control form-control-sm rounded-3" name="notes" placeholder="Catatan penilaian ulang"></div>
                            <div class="col-md-2"><button class="btn btn-primary btn-sm rounded-3 w-100">Proses</button></div>
                        </form>
                    <?php endif ?>
                </div>
            <?php endforeach ?>
        </div>
    </div>
<?php endif ?>

<?php if (! $selfService && $submission && has_permission('electives.selection.review')): ?>
    <div class="card border-0 shadow-sm rounded-4">
        <form method="post" action="<?= base_url('electives/' . $period['id'] . '/submissions/' . $submission['id'] . '/review') ?>">
            <div class="card-body p-4">
                <?= csrf_field() ?>
                <h5 class="fw-bold mb-3 fs-6">Review Pilihan Siswa oleh Guru / Kurikulum</h5>
                <div class="row g-3">
                    <div class="col-md-3"><select class="form-select form-select-sm rounded-3" name="review_type" required><option value="BK">Guru BK</option><option value="CURRICULUM">Kurikulum</option></select></div>
                    <div class="col-md-3"><select class="form-select form-select-sm rounded-3" name="decision" required><option value="APPROVED">Setujui</option><option value="NEEDS_REVISION">Minta revisi</option><option value="REJECTED">Tolak</option></select></div>
                    <div class="col-md-4"><input class="form-control form-control-sm rounded-3" name="notes" placeholder="Catatan review"></div>
                    <div class="col-md-2"><button class="btn btn-primary btn-sm rounded-3 w-100">Simpan Review</button></div>
                </div>
            </div>
        </form>
    </div>
<?php endif ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const MIN_PRIMARY = <?= $minPrimary ?>;
    const MAX_PRIMARY = <?= $maxPrimary ?>;
    const MAX_BACKUP  = <?= $maxBackup ?>;
    const CHOICE_EDITABLE = <?= $choiceEditable ? 'true' : 'false' ?>;

    const primarySelects = document.querySelectorAll('[data-kind="primary"]');
    const backupSelects  = document.querySelectorAll('[data-kind="backup"]');
    const form           = document.getElementById('selectionForm');
    const errorBox       = document.getElementById('choiceError');

    function syncSmartSelections() {
        const selectedPrimaryValues = new Map(); // value -> offeringId
        const selectedBackupValues  = new Map(); // value -> offeringId
        let primaryCount = 0;
        let backupCount  = 0;
        let totalJp      = 0;

        // 1. Gather current selections
        primarySelects.forEach(sel => {
            const val = sel.value;
            const offId = sel.getAttribute('data-offering-id');
            if (val) {
                selectedPrimaryValues.set(val, offId);
                primaryCount++;
                const row = document.getElementById('row-' + offId);
                if (row) totalJp += parseFloat(row.getAttribute('data-jp') || '0');
            }
        });

        backupSelects.forEach(sel => {
            const val = sel.value;
            const offId = sel.getAttribute('data-offering-id');
            if (val) {
                selectedBackupValues.set(val, offId);
                backupCount++;
            }
        });

        // 2. Process each subject row: Disable backup if primary selected, and disable primary if backup selected!
        document.querySelectorAll('.offering-row').forEach(row => {
            const offId = row.id.replace('row-', '');
            const pSel  = row.querySelector('[data-kind="primary"]');
            const bSel  = row.querySelector('[data-kind="backup"]');

            if (!pSel || !bSel) return;

            const pVal = pSel.value;
            const bVal = bSel.value;

            // MUTUAL EXCLUSION: If Primary selected, disable Backup for this subject!
            if (!CHOICE_EDITABLE) {
                pSel.disabled = true;
                bSel.disabled = true;
            } else {
                if (pVal !== '') {
                    bSel.value = '';
                    bSel.disabled = true;
                    bSel.classList.add('bg-light-subtle');
                } else {
                    bSel.disabled = false;
                    bSel.classList.remove('bg-light-subtle');
                }

                // MUTUAL EXCLUSION: If Backup selected, disable Primary for this subject!
                if (bVal !== '') {
                    pSel.value = '';
                    pSel.disabled = true;
                    pSel.classList.add('bg-light-subtle');
                } else if (bVal === '' && pVal === '') {
                    pSel.disabled = false;
                    pSel.classList.remove('bg-light-subtle');
                }
            }

            // Row Visual Highlights
            row.classList.remove('table-primary', 'table-warning', 'border-primary', 'border-warning');
            if (pVal !== '') {
                row.classList.add('table-primary', 'bg-primary-subtle');
            } else if (bVal !== '') {
                row.classList.add('table-warning', 'bg-warning-subtle');
            } else {
                row.classList.remove('bg-primary-subtle', 'bg-warning-subtle');
            }

            // 3. Disable duplicate option values in dropdowns across subjects
            Array.from(pSel.options).forEach(opt => {
                if (opt.value !== '') {
                    const owner = selectedPrimaryValues.get(opt.value);
                    opt.disabled = (owner && owner !== offId);
                }
            });

            Array.from(bSel.options).forEach(opt => {
                if (opt.value !== '') {
                    const owner = selectedBackupValues.get(opt.value);
                    opt.disabled = (owner && owner !== offId);
                }
            });
        });

        // 4. Update Header Metrics & Widget Bar
        document.getElementById('primaryCounterDisplay').textContent = `${primaryCount} / ${MIN_PRIMARY}-${MAX_PRIMARY} Mapel`;
        document.getElementById('backupCounterDisplay').textContent  = `${backupCount} / ${MAX_BACKUP} Mapel`;
        document.getElementById('totalJpDisplay').textContent       = `${totalJp} JP / Mingguan`;

        const primaryPercent = Math.min(100, Math.round((primaryCount / MAX_PRIMARY) * 100));
        const backupPercent  = MAX_BACKUP > 0 ? Math.min(100, Math.round((backupCount / MAX_BACKUP) * 100)) : 0;

        document.getElementById('primaryProgressBar').style.width = primaryPercent + '%';
        document.getElementById('backupProgressBar').style.width  = backupPercent + '%';

        const statusBadge = document.getElementById('statusBadge');
        if (primaryCount >= MIN_PRIMARY && primaryCount <= MAX_PRIMARY && backupCount <= MAX_BACKUP) {
            statusBadge.className = 'badge text-bg-success rounded-pill px-3 py-2 fs-8 shadow-sm';
            statusBadge.innerHTML = '<i data-lucide="check-circle" class="me-1" style="width:14px;height:14px;"></i> Siap Dikirim';
        } else {
            statusBadge.className = 'badge text-bg-warning text-dark rounded-pill px-3 py-2 fs-8';
            statusBadge.innerHTML = '<i data-lucide="clock" class="me-1" style="width:14px;height:14px;"></i> Belum Lengkap';
        }

        const warningBox = document.getElementById('choiceWarning');
        if (warningBox) {
            if (primaryCount > 0 && (totalJp < 20 || totalJp > 25)) {
                warningBox.classList.remove('d-none');
                warningBox.innerHTML = `<i data-lucide="alert-triangle" class="me-1" style="width:14px;height:14px;"></i> <strong>Peringatan:</strong> Total pilihan utama adalah <strong>${totalJp} JP</strong> per minggu (Standar 20–25 JP). Pilihan tetap dapat disimpan & dikirim.`;
            } else {
                warningBox.classList.add('d-none');
            }
        }

        if (window.lucide) lucide.createIcons();
    }

    // Attach Change Listeners
    primarySelects.forEach(sel => sel.addEventListener('change', syncSmartSelections));
    backupSelects.forEach(sel => sel.addEventListener('change', syncSmartSelections));

    // Initial Sync on Load
    syncSmartSelections();

    // Keep browser-native submission so CSRF, formaction, and every priority
    // field are posted reliably on desktop and mobile browsers.
    let requestedAction = '';
    form?.querySelectorAll('button[type="submit"]').forEach(function (button) {
        button.addEventListener('click', function () {
            requestedAction = button.value || '';
        });
    });

    form?.addEventListener('submit', function (event) {
        const primary = [], backup = [];

        document.querySelectorAll('[name^="primary_priority"]').forEach(function (select) {
            if (select.value) primary.push([select.name.match(/\d+/)[0], Number(select.value)]);
        });
        document.querySelectorAll('[name^="backup_priority"]').forEach(function (select) {
            if (select.value) backup.push([select.name.match(/\d+/)[0], Number(select.value)]);
        });

        const submitter = event.submitter || document.activeElement;
        const action = requestedAction || (submitter && submitter.value) || '';
        const isSubmitBtn = action === 'submit';

        if (isSubmitBtn) {
            if (primary.length < MIN_PRIMARY || primary.length > MAX_PRIMARY) {
                event.preventDefault();
                errorBox.classList.remove('d-none');
                errorBox.textContent = `Mohon pilih minimal ${MIN_PRIMARY} dan maksimal ${MAX_PRIMARY} Mapel Utama. Saat ini baru memilih ${primary.length} Mapel.`;
                errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            if (backup.length > MAX_BACKUP) {
                event.preventDefault();
                errorBox.classList.remove('d-none');
                errorBox.textContent = `Pilihan cadangan bersifat opsional dan maksimal ${MAX_BACKUP} mata pelajaran.`;
                errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
        }

        errorBox.classList.add('d-none');
    });
});
</script>

<!-- INFO MAPEL MODALS -->
<?php foreach ($offerings as $offering): $id=(int)$offering['id']; ?>
    <?php $infoText = trim((string)($offering['study_relevance'] ?: $offering['description'] ?? '')); ?>
    <?php if ($infoText !== ''): ?>
        <div class="modal fade" id="infoModal-<?= $id ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-0 pb-0">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge text-bg-primary fs-7 font-monospace"><?= esc($offering['subject_code']) ?></span>
                            <h5 class="modal-title fw-bold mb-0 text-slate-800"><?= esc($offering['subject_name']) ?></h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-2 mb-3 bg-light p-3 rounded-3">
                            <div class="col-6">
                                <small class="text-muted d-block fs-9">Pengampu / Guru</small>
                                <strong class="fs-8 text-slate-800"><?= esc($offering['teacher_name'] ?: 'Belum Ditentukan') ?></strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block fs-9">Beban Belajar Mingguan</small>
                                <strong class="fs-8 text-primary"><?= esc($offering['weekly_hours']) ?> JP / Mingguan</strong>
                            </div>
                        </div>

                        <div class="p-3 rounded-3 bg-primary-subtle border border-primary-subtle text-dark mb-3">
                            <div class="fw-bold fs-8 text-primary-emphasis mb-1 d-flex align-items-center gap-1">
                                <i data-lucide="book-open" style="width:14px;height:14px;"></i> Yang Akan Dipelajari
                            </div>
                            <p class="mb-0 text-slate-700 fs-8" style="line-height: 1.5;"><?= esc($offering['description'] ?? $infoText) ?></p>
                        </div>
                        <div class="p-3 rounded-3 bg-info-subtle border border-info-subtle text-dark mb-3">
                            <div class="fw-bold fs-8 text-info-emphasis mb-1 d-flex align-items-center gap-1">
                                <i data-lucide="graduation-cap" style="width:14px;height:14px;"></i> Hubungan dengan Kuliah & Dunia Kerja
                            </div>
                            <p class="mb-0 text-slate-700 fs-8" style="line-height: 1.5;"><?= esc($offering['study_relevance'] ?? $infoText) ?></p>
                        </div>
                        <?php if (!empty($offering['prerequisites'])): ?>
                            <div class="small text-muted fs-8"><strong>Siap untuk mapel ini?</strong> <?= esc($offering['prerequisites']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary btn-sm rounded-3 px-3" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>
<?php endforeach ?>
<?= $this->endSection() ?>
