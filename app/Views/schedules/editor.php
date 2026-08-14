<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<?php $editable = ! in_array($version['workflow_status'], ['LOCKED', 'ARCHIVED'], true); ?>

<style>
.schedule-grid td.drop-target-active {
    background-color: #dbeafe !important;
    border: 2px dashed #2563eb !important;
    transition: all 0.15s ease-in-out;
}
.drag-card {
    cursor: grab;
    user-select: none;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.drag-card:active {
    cursor: grabbing;
    transform: scale(0.98);
}
.drag-card.dragging {
    opacity: 0.4;
}
</style>

<div class="mb-4">
    <!-- Navigation back line -->
    <div class="d-flex align-items-center gap-2 mb-2">
        <a href="<?= base_url('schedules') ?>" class="btn btn-sm btn-light rounded-circle shadow-sm p-1 d-inline-flex align-items-center justify-content-center" style="width:32px; height:32px;" title="Kembali ke Daftar Jadwal">
            <i class="bi bi-arrow-left fs-6"></i>
        </a>
        <span class="text-muted small">Jadwal Pelajaran</span>
        <span class="text-muted small">/</span>
        <span class="fw-semibold small text-primary"><?= esc($version['code']) ?></span>
    </div>

    <!-- Hero Header Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-relative" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff;">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h4 class="fw-bold mb-0 text-white"><?= esc($version['name']) ?></h4>
                        <?php
                        $st = $version['workflow_status'];
                        $statusBadgeClass = match ($st) {
                            'DRAFT'      => 'bg-secondary bg-opacity-25 text-light border-secondary',
                            'VALIDATED'  => 'bg-info bg-opacity-25 text-info border-info',
                            'REVIEWED'   => 'bg-primary bg-opacity-25 text-primary-subtle border-primary',
                            'APPROVED'   => 'bg-success bg-opacity-25 text-success-subtle border-success',
                            'LOCKED'     => 'bg-warning bg-opacity-25 text-warning-subtle border-warning',
                            default      => 'bg-secondary bg-opacity-25 text-light border-secondary'
                        };
                        ?>
                        <span class="badge border rounded-pill px-3 py-1 fs-8 fw-semibold <?= $statusBadgeClass ?>">
                            <i class="bi bi-calendar-check me-1"></i><?= esc($st) ?>
                        </span>
                        <?php if (!empty($conflicts)): ?>
                            <span class="badge bg-danger rounded-pill px-3 py-1 fs-8 fw-bold animate__animated animate__pulse animate__infinite">
                                🚨 <?= count($conflicts) ?> Benturan Konflik
                            </span>
                        <?php else: ?>
                            <span class="badge bg-success bg-opacity-25 text-success-subtle border border-success rounded-pill px-3 py-1 fs-8 fw-semibold">
                                🟢 0 Konflik Lintas Unit
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="text-white-50 mb-0 small">
                        Kode: <span class="text-white font-monospace fw-semibold me-3"><?= esc($version['code']) ?></span>
                        Revisi: <span class="text-white fw-semibold me-3">v<?= (int)$version['revision_number'] ?></span>
                    </p>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <?php if (has_permission('schedules.export')): ?>
                        <button type="button" class="btn btn-sm btn-light rounded-3 shadow-sm border-0 fw-bold text-dark px-3 py-2" data-bs-toggle="modal" data-bs-target="#printOptionsModal">
                            <i class="bi bi-printer me-1.5 text-primary"></i> 🖨️ Cetak Dokumen Jadwal
                        </button>
                    <?php endif; ?>
                    <?php if ($editable && has_permission('schedules.generate')): ?>
                        <button type="button" class="btn btn-sm btn-warning text-dark fw-bold rounded-3 shadow-sm px-3 py-2" id="btnRunGenerator" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #fff;">
                            <i class="bi bi-lightning-charge-fill me-1"></i> ⚡ Susun Otomatis
                        </button>
                    <?php endif; ?>
                    <?php if (has_permission('schedules.validate')): ?>
                        <button type="button" class="btn btn-sm btn-info text-white rounded-3 shadow-sm px-3 py-2 fw-semibold" id="btnAuditConflicts">
                            <i class="bi bi-shield-check me-1"></i> Periksa Konflik (<?= count($conflicts ?? []) ?>)
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($last_candidate && ! (int)$last_candidate['is_applied']): ?>
    <?php $candidateReadyToApply = (int)($last_candidate['unplaced_count'] ?? 0) === 0; ?>
    <div class="alert alert-info border-0 rounded-4 shadow-sm mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 bg-info-subtle text-info-emphasis p-3.5">
        <div>
            <strong class="d-block"><i class="bi bi-stars me-1 text-info"></i>Kandidat Jadwal Otomatis Siap Ditinjau</strong>
            <span class="small"><?= (int)$last_candidate['placed_count'] ?> slot terisi · <?= (int)$last_candidate['unplaced_count'] ?> kebutuhan belum tuntas · diproses dalam <?= (int)$last_candidate['execution_time_ms'] ?> ms.</span>
        </div>
        <?php if ($editable && has_permission('schedules.generate')): ?>
            <button class="btn btn-sm <?= $candidateReadyToApply ? 'btn-info text-white' : 'btn-outline-secondary' ?> rounded-3 px-3 py-2 fw-semibold shadow-sm" id="btnApplyCandidate" data-id="<?= (int)$last_candidate['id'] ?>" <?= $candidateReadyToApply ? '' : 'disabled title="Perbaiki kebutuhan yang belum tuntas dan konflik terlebih dahulu"' ?>>
                <i class="bi <?= $candidateReadyToApply ? 'bi-check2-all' : 'bi-lock' ?> me-1"></i> <?= $candidateReadyToApply ? 'Terapkan Kandidat' : 'Belum Dapat Diterapkan' ?>
            </button>
            <?php if (!$candidateReadyToApply): ?><button type="button" class="btn btn-sm btn-outline-danger rounded-3 px-3 py-2 fw-semibold btnAuditConflictsInline"><i class="bi bi-list-check me-1"></i> Lihat Rincian</button><?php endif; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (!empty($advisories)): ?>
    <div class="alert alert-warning border-0 rounded-4 shadow-sm mb-4 bg-warning bg-opacity-10 text-warning-emphasis border-start border-warning border-4 p-3.5">
        <div class="d-flex align-items-start gap-3"><i class="bi bi-lightbulb-fill fs-3 flex-shrink-0"></i><div>
            <strong class="fs-6 d-block mb-1"><?= count($advisories) ?> Catatan Pedagogis (Bukan Bentrok Jadwal)</strong>
            <div class="small mb-2">Tidak ada overlap guru, kelas, atau ruang. Catatan berikut menunjukkan jarak pertemuan yang rapat karena kapasitas/ketersediaan guru.</div>
            <ul class="mb-0 small ps-3"><?php foreach(array_slice($advisories,0,3) as $item):?><li><?= esc($item['description']) ?></li><?php endforeach;?></ul>
        </div></div>
    </div>
<?php endif; ?>

<!-- Conflict Notification Banner -->
<?php if (!empty($conflicts)): ?>
    <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4 bg-danger bg-opacity-10 text-danger-emphasis border-start border-danger border-4 p-3.5">
        <div class="d-flex align-items-start gap-3">
            <i class="bi bi-exclamation-triangle-fill fs-3 text-danger flex-shrink-0"></i>
            <div>
                <strong class="fs-6 d-block mb-1 text-danger">Terdeteksi <?= count($conflicts) ?> Benturan Konflik Jadwal (Termasuk Lintas Unit SMP/SMA)</strong>
                <ul class="mb-0 small ps-3">
                    <?php foreach (array_slice($conflicts, 0, 3) as $cItem): ?>
                        <li><?= esc($cItem['description']) ?></li>
                    <?php endforeach; ?>
                    <?php if (count($conflicts) > 3): ?>
                        <li class="fw-bold mt-1">...dan <?= count($conflicts) - 3 ?> konflik lainnya. <button type="button" class="btn btn-link btn-sm p-0 align-baseline fw-bold btnAuditConflictsInline">Buka rincian konflik</button></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php $auditRows = $source_audit['rows'] ?? []; ?>
<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
    <div class="card-header bg-body py-3 px-4 d-flex justify-content-between align-items-center">
        <div><h6 class="fw-bold mb-1"><i class="bi bi-diagram-3 text-primary me-2"></i>Audit Sumber JP</h6><small class="text-body-secondary">Kurikulum → pembagian tugas → requirement → jadwal aktif.</small></div>
        <span class="badge rounded-pill <?= empty($source_audit['issue_count']) ? 'text-bg-success' : 'text-bg-warning' ?>"><?= empty($source_audit['issue_count']) ? 'Semua sinkron' : (int)$source_audit['issue_count'] . ' kelas perlu perhatian' ?></span>
    </div>
    <div class="table-responsive"><table class="table table-sm align-middle mb-0">
        <thead class="table-light"><tr><th class="ps-4">Kelas</th><th>Kapasitas</th><th>Kurikulum</th><th>Penugasan</th><th>Requirement</th><th>Terjadwal</th><th>Status / Kekurangan</th></tr></thead>
        <tbody><?php foreach ($auditRows as $row): ?><tr>
            <td class="ps-4 fw-bold"><?= esc($row['classroom_name']) ?></td><td><?= (float)$row['capacity'] ?> JP</td><td><?= (float)$row['curriculumHours'] ?> JP</td><td><?= (float)$row['assignmentHours'] ?> JP</td><td><?= (float)$row['requirementHours'] ?> JP</td><td><?= (int)$row['scheduled'] ?> JP</td>
            <td><?php if ($row['balanced']): ?><span class="badge text-bg-success">Sinkron</span><?php else: ?><span class="badge text-bg-warning mb-1">Belum sinkron</span><div class="small text-danger"><?= esc(implode('; ', array_merge($row['missingAssignments'], $row['missingRequirements']))) ?: 'Jumlah JP antar-tahap belum sama.' ?></div><?php endif; ?>
                <?php if (!empty($row['pendingApprovals'])): ?><div class="small text-warning-emphasis mt-1"><i class="bi bi-hourglass-split me-1"></i>Belum dijadwalkan—menunggu persetujuan penawaran: <?= esc(implode(', ', $row['pendingApprovals'])) ?></div><?php endif; ?>
            </td>
        </tr><?php endforeach; ?></tbody>
    </table></div>
</div>

<!-- Classroom Filter Selector Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-body">
    <div class="card-body p-4">
        <form method="get" class="row g-3 align-items-center">
            <div class="col-md-5">
                <label class="form-label small fw-bold text-muted mb-1">Pilih Rombel / Kelas Pelajaran</label>
                <select name="classroom_id" class="form-select rounded-3 shadow-none fw-semibold" onchange="this.form.submit()">
                    <?php foreach ($classrooms as $classroom): ?>
                        <option value="<?= $classroom['id'] ?>" <?= (int)$classroom['id'] === $selected_classroom_id ? 'selected' : '' ?>><?= esc($classroom['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-7">
                <div class="p-3 rounded-4 border bg-body-tertiary small text-body-secondary">
                    <i class="bi bi-hand-index-thumb text-primary me-1"></i>
                    <strong>Fitur Interaktif Drag & Drop:</strong> Anda dapat menggeser (drag) kartu pelajaran dan melepaskannya (drop) pada slot jam pelajaran yang diinginkan. Sistem akan secara otomatis memeriksa benturan jadwal guru lintas unit (SMP & SMA).
                </div>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <a href="<?= base_url('schedules/' . (int)$version['id'] . '/editor?mode=multi') ?>" class="btn btn-sm btn-outline-primary rounded-3 fw-semibold">
                    <i class="bi bi-layout-three-columns me-1"></i> Buka Master Multi-Jenjang SMP-SMA
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Main Schedule Grid Card -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-body">
    <div class="card-header bg-body border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0 text-body"><i class="bi bi-grid-3x3-gap text-primary me-2"></i>Kisi Mingguan Pelajaran (Drag & Drop Active)</h5>
            <small class="text-body-secondary">Geser kartu pelajaran ke slot yang diinginkan untuk penyesuaian manual.</small>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered align-middle text-center mb-0 schedule-grid" style="border-collapse: separate; border-spacing: 0;">
                <thead class="sticky-top border-bottom" style="z-index: 5; background-color: #f8fafc;">
                    <tr class="text-uppercase text-secondary fs-8 fw-bold">
                        <th class="text-start ps-4 text-dark" style="min-width:130px; background-color: #f8fafc; border-bottom: 2px solid #cbd5e1;">WAKTU / JP</th>
                        <?php foreach ($days as $day): ?>
                            <th style="min-width:190px; background-color: #eff6ff; color: #1d4ed8; border-bottom: 2px solid #cbd5e1;" class="py-2.5"><?= esc($day['day_name']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if ($max_slot === 0): ?>
                    <tr>
                        <td colspan="<?= count($days) + 1 ?>" class="py-5 text-muted">
                            <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary"></i>
                            Slot jam pelajaran belum tersedia untuk unit ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <!-- PRA-AKADEMIK ROW (# Sebelum Jam ke-1) -->
                    <?php
                    $preRoutines = array_filter($routine_activities ?? [], fn($r) => ($r['placement_zone'] ?? '') === 'PRE_ACADEMIC' && (int)($r['is_locked_slot'] ?? 0) === 1);
                    ?>
                    <?php if (!empty($preRoutines)): ?>
                        <tr class="bg-light-subtle border-bottom" style="background-color: #f8fafc;">
                            <th class="text-start ps-4 align-middle" style="background-color: #f1f5f9; border-right: 2px solid #cbd5e1;">
                                <div class="fw-bold text-dark fs-8 text-uppercase"><i class="bi bi-sun me-1 text-warning"></i> Pra-Akademik</div>
                                <span class="fw-normal text-muted fs-9">Sebelum JP 1</span>
                            </th>
                            <?php foreach ($days as $day):
                                $dayCode = match((int)$day['day_of_week']) { 1 => 'MONDAY', 2 => 'TUESDAY', 3 => 'WEDNESDAY', 4 => 'THURSDAY', 5 => 'FRIDAY', default => '' };
                                $dayPre = array_filter($preRoutines, fn($r) => ($r['default_day'] ?? 'ALL_DAYS') === 'ALL_DAYS' || strtoupper((string)$r['default_day']) === $dayCode);
                                $dayJp1 = $slot_map[(int)$day['id']][1] ?? null;
                                $dayJp1Start = $dayJp1 ? substr($dayJp1['start_time'], 0, 5) : '07:30';
                            ?>
                                <td class="p-2 align-top bg-light-subtle" style="background-color: #fafafa;">
                                    <?php if (!empty($dayPre)): ?>
                                        <div class="d-flex flex-column gap-1.5">
                                            <?php foreach ($dayPre as $r):
                                                $dur = (int)($r['duration_minutes'] ?: 15);
                                                $parts = explode(':', $dayJp1Start);
                                                $jp1Min = ((int)$parts[0] * 60) + (int)($parts[1] ?? 0);
                                                $sMin = max(0, $jp1Min - $dur);
                                                $timeStr = sprintf('%02d.%02d–%02d.%02d', intdiv($sMin, 60), $sMin % 60, intdiv($jp1Min, 60), $jp1Min % 60);
                                            ?>
                                                <div class="rounded-3 p-2 text-start border shadow-2xs" style="background-color: <?= esc($r['color_label'] ?: '#e0e7ff') ?>; border-color: #cbd5e1 !important;">
                                                    <div class="fw-bold fs-8 text-dark"><i class="bi bi-clock me-1"></i><?= esc($r['name']) ?></div>
                                                    <div class="fs-9 text-muted font-monospace fw-semibold"><i class="bi bi-alarm me-1"></i><?= esc($timeStr) ?> (<?= $dur ?>m)</div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted fs-9 opacity-50">-</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endif; ?>

                    <?php for ($slotNumber = 1; $slotNumber <= $max_slot; $slotNumber++): ?>
                    <tr class="border-bottom">
                        <?php $firstSlot = $days ? ($slot_map[(int)$days[0]['id']][$slotNumber] ?? null) : null; ?>
                        <th class="text-start ps-4 align-middle" style="background-color: #f8fafc; border-right: 2px solid #e2e8f0;">
                            <div class="fw-bold text-dark fs-7">JP <?= $slotNumber ?></div>
                            <span class="fw-normal text-muted small"><?= $firstSlot ? substr($firstSlot['start_time'],0,5).'–'.substr($firstSlot['end_time'],0,5) : '-' ?></span>
                        </th>
                        <?php foreach ($days as $day):
                            $daySlot   = $slot_map[(int)$day['id']][$slotNumber] ?? null;
                            $entry     = $daySlot ? ($entry_map[(int)$daySlot['id']] ?? null) : null;
                            $fixed     = $daySlot ? ($fixed_map[(int)$daySlot['id']] ?? null) : null;
                            $daySlotId = $daySlot ? (int)$daySlot['id'] : 0;
                        ?>
                            <td class="p-2 align-top drop-cell"
                                data-day-slot-id="<?= $daySlotId ?>"
                                data-day-id="<?= (int)$day['id'] ?>"
                                data-slot-number="<?= $slotNumber ?>"
                                ondragover="handleDragOver(event)"
                                ondragleave="handleDragLeave(event)"
                                ondrop="handleDrop(event, <?= $daySlotId ?>)"
                                style="background-color: #ffffff; min-height: 85px;">
                                <?php if ($fixed): ?>
                                    <?php $bgColor = !empty($fixed['color_label']) ? $fixed['color_label'] : '#f1f5f9'; ?>
                                    <div class="rounded-3 p-2.5 text-start shadow-2xs border"
                                         style="background-color: <?= esc($bgColor) ?>; border-color: #cbd5e1 !important; opacity: 0.95;">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <div class="fw-bold fs-7 text-dark">
                                                <i class="bi bi-lock-fill text-danger me-1"></i><?= esc($fixed['title']) ?>
                                            </div>
                                            <?php if (!empty($fixed['teacher_name'])): ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary fs-9 me-1"><?= esc($fixed['teacher_name']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small text-muted" style="font-size: 0.78rem;">🔒 Dipasang otomatis dari Kegiatan Rutin</div>
                                    </div>
                                <?php elseif ($entry): ?>
                                    <?php
                                    $eId = (int)$entry['id'];
                                    $hasConflict = false;
                                    $conflictDesc = '';
                                    foreach ($conflicts as $cnf) {
                                        if ((int)$cnf['primary_entry_id'] === $eId || (int)$cnf['conflicting_entry_id'] === $eId) {
                                            $hasConflict = true;
                                            $conflictDesc = $cnf['description'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <div class="rounded-3 p-2.5 text-start shadow-sm border drag-card <?= $hasConflict ? 'bg-danger bg-opacity-10 border-danger' : '' ?>"
                                         <?= $editable ? 'draggable="true"' : '' ?>
                                         data-entry-id="<?= $entry['id'] ?>"
                                         data-day-slot-id="<?= $daySlotId ?>"
                                         data-subject-id="<?= $entry['subject_id'] ?>"
                                         data-teacher-id="<?= $entry['teacher_id'] ?>"
                                         data-second-teacher-id="<?= $entry['second_teacher_id'] ?? '' ?>"
                                         data-requirement-id="<?= $entry['schedule_requirement_id'] ?? 0 ?>"
                                         data-room-id="<?= $entry['room_id'] ?? '' ?>"
                                         ondragstart="handleDragStart(event)"
                                         ondragend="handleDragEnd(event)"
                                         style="background-color: <?= $hasConflict ? '#fef2f2' : '#eff6ff' ?>; border-color: <?= $hasConflict ? '#ef4444' : '#bfdbfe' ?> !important;">

                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <div class="fw-bold fs-7" style="color: <?= $hasConflict ? '#b91c1c' : '#1d4ed8' ?>;">
                                                <i class="bi bi-grip-vertical text-muted me-0.5 drag-handle"></i><?= esc($entry['subject_name'] ?: 'Kegiatan Rutin') ?>
                                            </div>
                                            <?php if ($hasConflict): ?>
                                                <span class="badge bg-danger rounded-pill fs-9" title="<?= esc($conflictDesc) ?>"><i class="bi bi-exclamation-triangle-fill"></i> Benturan</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small fw-semibold text-dark mb-0.5"><i class="bi bi-person-fill text-muted me-1"></i><?= esc($entry['teacher_name'] ?: 'Pengampu') ?></div>
                                        <div class="small text-muted" style="font-size: 0.78rem;"><i class="bi bi-geo-alt-fill text-secondary me-1"></i><?= esc($entry['room_name'] ?? 'Tanpa ruang khusus') ?></div>
                                    </div>
                                <?php else: ?>
                                    <div class="rounded-3 p-3 text-muted small border border-dashed text-center" style="background-color: #fafafa; border-style: dashed !important;">
                                        <span class="text-muted opacity-75">Kosong</span>
                                    </div>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- INTERMISSION BREAK ROW (# Setelah Jam 5) -->
                    <?php $breakRoutinesForDay = array_filter($routine_activities ?? [], fn($r) => ($r['placement_zone'] ?? '') === 'INTERMISSION_BREAK' && (int)($r['is_locked_slot'] ?? 0) === 1); ?>
                    <?php $breakAfterSlots = array_values(array_unique(array_map(fn($r) => (int)($r['placement_sequence'] ?? 5), $breakRoutinesForDay ?: [['placement_sequence' => 5]]))); ?>
                    <?php if (in_array($slotNumber, $breakAfterSlots, true)): ?>
                        <?php
                        $breakRoutines = $breakRoutinesForDay;
                        ?>
                        <?php if (!empty($breakRoutines)): ?>
                            <tr class="bg-warning-subtle border-bottom" style="background-color: #fffbe6;">
                                <th class="text-start ps-4 align-middle" style="background-color: #fef08a; border-right: 2px solid #cbd5e1;">
                                    <div class="fw-bold text-dark fs-8 text-uppercase"><i class="bi bi-cup-hot me-1 text-warning-emphasis"></i> Istirahat</div>
                                    <span class="fw-normal text-muted fs-9">Sela JP 5 & 6</span>
                                </th>
                                <?php foreach ($days as $day):
                                    $dayCode = match((int)$day['day_of_week']) { 1 => 'MONDAY', 2 => 'TUESDAY', 3 => 'WEDNESDAY', 4 => 'THURSDAY', 5 => 'FRIDAY', default => '' };
                                    $dayBreak = array_filter($breakRoutines, fn($r) => ($r['default_day'] ?? 'ALL_DAYS') === 'ALL_DAYS' || strtoupper((string)$r['default_day']) === $dayCode);
                                    $dayJp5 = $slot_map[(int)$day['id']][5] ?? null;
                                    $dayJp5End = $dayJp5 ? substr($dayJp5['end_time'], 0, 5) : '10:50';
                                ?>
                                    <td class="p-2 align-top bg-warning-subtle" style="background-color: #fffdf0;">
                                        <?php if (!empty($dayBreak)): ?>
                                            <?php foreach ($dayBreak as $r):
                                                $dur = (int)($r['duration_minutes'] ?: 15);
                                                $parts = explode(':', $dayJp5End);
                                                $jp5Min = ((int)$parts[0] * 60) + (int)($parts[1] ?? 0);
                                                $eMin = $jp5Min + $dur;
                                                $timeStr = sprintf('%02d.%02d–%02d.%02d', intdiv($jp5Min, 60), $jp5Min % 60, intdiv($eMin, 60), $eMin % 60);
                                            ?>
                                                <div class="rounded-3 p-2 text-start border shadow-2xs" style="background-color: <?= esc($r['color_label'] ?: '#ffedd5') ?>; border-color: #fde047 !important;">
                                                    <div class="fw-bold fs-8 text-dark"><i class="bi bi-cup-hot-fill me-1 text-warning"></i><?= esc($r['name']) ?></div>
                                                    <div class="fs-9 text-muted font-monospace fw-semibold"><i class="bi bi-clock me-1"></i><?= esc($timeStr) ?> (<?= $dur ?>m)</div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted fs-9 opacity-50">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php endfor; ?>

                    <!-- PASCA-AKADEMIK ROW (# Setelah Jam Terakhir) -->
                    <?php
                    $postRoutines = array_filter($routine_activities ?? [], fn($r) => ($r['placement_zone'] ?? '') === 'POST_ACADEMIC' && (int)($r['is_locked_slot'] ?? 0) === 1);
                    ?>
                    <?php if (!empty($postRoutines)): ?>
                        <tr class="bg-light-subtle border-top" style="background-color: #f8fafc;">
                            <th class="text-start ps-4 align-middle" style="background-color: #f1f5f9; border-right: 2px solid #cbd5e1;">
                                <div class="fw-bold text-dark fs-8 text-uppercase"><i class="bi bi-moon-stars me-1 text-primary"></i> Pasca-Akademik</div>
                                <span class="fw-normal text-muted fs-9">Setelah JP Akhir</span>
                            </th>
                            <?php foreach ($days as $day):
                                $dayCode = match((int)$day['day_of_week']) { 1 => 'MONDAY', 2 => 'TUESDAY', 3 => 'WEDNESDAY', 4 => 'THURSDAY', 5 => 'FRIDAY', default => '' };
                                $dayPost = array_filter($postRoutines, fn($r) => ($r['default_day'] ?? 'ALL_DAYS') === 'ALL_DAYS' || strtoupper((string)$r['default_day']) === $dayCode);

                                $daySlots = $slot_map[(int)$day['id']] ?? [];
                                $maxDaySlotNum = !empty($daySlots) ? max(array_keys($daySlots)) : 9;
                                $lastSlot = $daySlots[$maxDaySlotNum] ?? null;
                                $lastJpEnd = $lastSlot ? substr($lastSlot['end_time'], 0, 5) : '13:45';
                            ?>
                                <td class="p-2 align-top bg-light-subtle" style="background-color: #fafafa;">
                                    <?php if (!empty($dayPost)): ?>
                                        <div class="d-flex flex-column gap-1.5">
                                            <?php foreach ($dayPost as $r):
                                                $dur = (int)($r['duration_minutes'] ?: 15);
                                                $parts = explode(':', $lastJpEnd);
                                                $lastMin = ((int)$parts[0] * 60) + (int)($parts[1] ?? 0);
                                                $endMin = $lastMin + $dur;
                                                $timeStr = sprintf('%02d.%02d–%02d.%02d', intdiv($lastMin, 60), $lastMin % 60, intdiv($endMin, 60), $endMin % 60);
                                            ?>
                                                <div class="rounded-3 p-2 text-start border shadow-2xs" style="background-color: <?= esc($r['color_label'] ?: '#e0f2fe') ?>; border-color: #cbd5e1 !important;">
                                                    <div class="fw-bold fs-8 text-dark"><i class="bi bi-box-arrow-right me-1"></i><?= esc($r['name']) ?></div>
                                                    <div class="fs-9 text-muted font-monospace fw-semibold"><i class="bi bi-clock me-1"></i><?= esc($timeStr) ?> (<?= $dur ?>m)</div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted fs-9 opacity-50">-</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endif; ?>

                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Opsi Cetak Dokumen Jadwal Serbaguna & Multi-Jenjang -->
<div class="modal fade" id="printOptionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow overflow-hidden">
            <div class="modal-header bg-primary text-white p-3.5">
                <div>
                    <h5 class="modal-title fw-bold text-white fs-6"><i class="bi bi-printer me-2"></i>Opsi Cetak & Dokumen Resmi Jadwal</h5>
                    <p class="text-white-50 small mb-0">Pilih format dokumen cetak per Kelas, per Guru, per Jenjang, atau Multi-Jenjang (SMP & SMA 1 Lembar Dokumen/SK).</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <!-- Option 1: Multi-Jenjang (SMP & SMA Gabungan 1 Lembar) -->
                    <div class="col-12">
                        <div class="card border border-primary border-opacity-50 rounded-4 p-3.5 bg-primary bg-opacity-10">
                            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                                <div>
                                    <span class="badge bg-primary text-white mb-1"><i class="bi bi-star-fill me-1"></i>REKOMENDASI SK PERGURUAN</span>
                                    <h6 class="fw-bold text-dark mb-1"><i class="bi bi-layers-fill text-primary me-1.5"></i>Cetak Multi-Jenjang (SMP & SMA Gabungan 1 Lembar)</h6>
                                    <p class="small text-muted mb-0">Menghasilkan Matriks Jadwal Master Seluruh Sekolah (SMP + SMA) dalam 1 Lembar Dokumen / SK Resmi untuk papan pengumuman & ruang guru (Rekomendasi Kertas A3/A4 Landscape).</p>
                                </div>
                                <button type="button" onclick="printMultiUnitReport()" class="btn btn-primary rounded-3 px-3.5 py-2.5 fw-bold text-nowrap shadow-sm">
                                    <i class="bi bi-printer me-1"></i> Cetak Multi-Jenjang
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Option 2: Per Jenjang / Unit (SMP saja atau SMA saja) -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-light h-100">
                            <h6 class="fw-bold text-dark mb-1"><i class="bi bi-building me-1.5 text-info"></i>Cetak Per Jenjang / Unit Sekolah</h6>
                            <p class="small text-muted mb-3">Cetak Master Matriks Jadwal Pelajaran khusus 1 unit sekolah (SMP saja atau SMA saja).</p>

                            <div class="input-group input-group-sm mb-2">
                                <select id="selectUnitReport" class="form-select rounded-start-3 fw-semibold">
                                    <?php foreach ($units as $u): ?>
                                        <option value="<?= $u['id'] ?>" <?= ($u['id'] == $version['unit_id']) ? 'selected' : '' ?>><?= esc($u['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-info text-white fw-bold rounded-end-3" onclick="printUnitReport()">
                                    <i class="bi bi-printer me-1"></i> Cetak
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Option 3: Per Kelas -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-light h-100">
                            <h6 class="fw-bold text-dark mb-1"><i class="bi bi-door-open me-1.5 text-success"></i>Cetak Per Kelas / Rombel</h6>
                            <p class="small text-muted mb-3">Cetak jadwal pelajaran individu untuk ditempel di ruang kelas murid.</p>

                            <div class="input-group input-group-sm mb-2">
                                <select id="selectClassroomReport" class="form-select rounded-start-3 fw-semibold">
                                    <?php foreach ($classrooms as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= ($c['id'] == $selected_classroom_id) ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-success fw-bold rounded-end-3" onclick="printClassroomReport()">
                                    <i class="bi bi-printer me-1"></i> Cetak
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Option 4: Per Guru -->
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-light">
                            <h6 class="fw-bold text-dark mb-1"><i class="bi bi-person-badge me-1.5 text-warning"></i>Cetak Per Guru Pengampu</h6>
                            <p class="small text-muted mb-3">Cetak jadwal mengajar individu untuk pegangan pribadi masing-masing guru.</p>

                            <div class="input-group input-group-sm">
                                <select id="selectTeacherReport" class="form-select rounded-start-3 fw-semibold">
                                    <option value="">-- Pilih Guru Pengampu --</option>
                                    <?php foreach ($teachers as $t): ?>
                                        <option value="<?= $t['id'] ?>"><?= esc($t['full_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-warning text-dark fw-bold rounded-end-3" onclick="printTeacherReport()">
                                    <i class="bi bi-printer me-1"></i> Cetak Jadwal Guru
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light p-3">
                <button type="button" class="btn btn-light rounded-3 px-4 fw-semibold" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
let dragPayload = null;
const csrfTokenName = '<?= csrf_token() ?>';

function getLiveCsrfToken() {
    const name = '<?= config('Security')->cookieName ?>' + '=';
    const decodedCookie = decodeURIComponent(document.cookie);
    const ca = decodedCookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i].trim();
        if (c.indexOf(name) === 0) return c.substring(name.length, c.length);
    }
    const hiddenInput = document.querySelector('input[name="' + csrfTokenName + '"]');
    return hiddenInput ? hiddenInput.value : '<?= csrf_hash() ?>';
}

function syncCsrfTokenInDOM(newToken) {
    if (!newToken) return;
    document.querySelectorAll('input[name="' + csrfTokenName + '"]').forEach(inp => { inp.value = newToken; });
}

function handleDragStart(e) {
    const card = e.currentTarget;
    card.classList.add('dragging');
    dragPayload = {
        entryId: card.getAttribute('data-entry-id'),
        daySlotId: card.getAttribute('data-day-slot-id'),
        subjectId: card.getAttribute('data-subject-id'),
        teacherId: card.getAttribute('data-teacher-id'),
        secondTeacherId: card.getAttribute('data-second-teacher-id'),
        requirementId: card.getAttribute('data-requirement-id'),
        roomId: card.getAttribute('data-room-id'),
        classroomId: '<?= $selected_classroom_id ?>'
    };
    e.dataTransfer.setData('text/plain', JSON.stringify(dragPayload));
}

function handleDragEnd(e) {
    e.currentTarget.classList.remove('dragging');
}

function handleDragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add('drop-target-active');
}

function handleDragLeave(e) {
    e.currentTarget.classList.remove('drop-target-active');
}

function handleDrop(e, targetDaySlotId) {
    e.preventDefault();
    const cell = e.currentTarget;
    cell.classList.remove('drop-target-active');

    if (!targetDaySlotId || targetDaySlotId <= 0) {
        alert('Slot waktu tidak valid.');
        return;
    }

    if (!dragPayload) {
        try {
            dragPayload = JSON.parse(e.dataTransfer.getData('text/plain'));
        } catch(err) {
            console.error(err);
            return;
        }
    }

    // Perform AJAX request to save/move entry to new slot
    saveSlotEntry(targetDaySlotId, dragPayload);
}

function saveSlotEntry(targetDaySlotId, payload) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Memindahkan Slot Jadwal...',
            text: 'Memeriksa benturan jadwal guru lintas unit SMP & SMA.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
    }

    const versionId = <?= (int)$version['id'] ?>;
    const body = new FormData();
    const token = getLiveCsrfToken();
    body.set(csrfTokenName, token);
    body.append('day_slot_id', targetDaySlotId);
    body.append('classroom_id', payload.classroomId);
    body.append('subject_id', payload.subjectId);
    body.append('teacher_id', payload.teacherId);
    body.append('source_entry_id', payload.entryId || 0);
    body.append('source_day_slot_id', payload.daySlotId || 0);
    body.append('current_revision', <?= (int) $version['revision_number'] ?>);
    body.append('schedule_requirement_id', payload.requirementId || 0);
    if (payload.secondTeacherId) body.append('second_teacher_id', payload.secondTeacherId);
    if (payload.roomId) body.append('room_id', payload.roomId);

    fetch(`<?= base_url('schedules') ?>/${versionId}/save-entry`, {
        method: 'POST',
        body: body,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_header() ?>': token
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.csrf_hash) syncCsrfTokenInDOM(data.csrf_hash);

        if (data.status === 'success') {
            const conflicts = data.conflict_report ? (data.conflict_report.conflicts || data.conflict_report) : [];
            const hasCrossUnit = Array.isArray(conflicts) && conflicts.some(c => c.conflict_type === 'CROSS_UNIT_TEACHER_DOUBLE_BOOKING' || c.conflict_type === 'TEACHER_DOUBLE_BOOKING');

            if (typeof Swal !== 'undefined') {
                if (hasCrossUnit) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Slot Terpindah dengan Catatan Benturan!',
                        text: '🚨 Peringatan: Terdeteksi benturan mengajar guru lintas unit SMP/SMA pada slot waktu ini!',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: 'Slot Berhasil Dipindahkan',
                        text: 'Jadwal terbebas dari benturan lintas unit.',
                        timer: 1200,
                        showConfirmButton: false
                    }).then(() => window.location.reload());
                }
            } else {
                window.location.reload();
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Gagal Memindahkan Slot', text: data.message });
            } else {
                alert(data.message);
            }
        }
    })
    .catch(err => {
        console.error(err);
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Koneksi Bermasalah', text: 'Terjadi kesalahan saat menyimpan slot jadwal.' });
        }
    });
}

function printReportDirectly(url) {
    let iframe = document.getElementById('printReportFrame');
    if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = 'printReportFrame';
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        iframe.style.visibility = 'hidden';
        document.body.appendChild(iframe);
    }

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Menyiapkan Dokumen SK Resmi...',
            text: 'Membuka dialog cetak...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
    }

    iframe.onload = function() {
        if (typeof Swal !== 'undefined') {
            Swal.close();
        }
        setTimeout(function() {
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            } catch(e) {
                console.error(e);
            }
        }, 400);
    };

    iframe.src = url;
}

function printMultiUnitReport() {
    printReportDirectly('<?= base_url("schedules/{$version['id']}/reports/multi-unit") ?>');
}

function printUnitReport() {
    const unitId = document.getElementById('selectUnitReport').value;
    if (unitId) {
        printReportDirectly('<?= base_url("schedules/{$version['id']}/reports/unit/") ?>' + unitId);
    }
}

function printClassroomReport() {
    const classId = document.getElementById('selectClassroomReport').value;
    if (classId) {
        printReportDirectly('<?= base_url("schedules/{$version['id']}/reports/classroom/") ?>' + classId);
    }
}

function printTeacherReport() {
    const teacherId = document.getElementById('selectTeacherReport').value;
    if (teacherId) {
        printReportDirectly('<?= base_url("schedules/{$version['id']}/reports/teacher/") ?>' + teacherId);
    } else {
        alert('Pilih guru terlebih dahulu.');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const versionId = <?= (int)$version['id'] ?>;
    const post = async (url, values = {}) => {
        const body = new FormData();
        const token = getLiveCsrfToken();
        body.set(csrfTokenName, token);
        Object.entries(values).forEach(([key,value]) => body.append(key,value));

        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            '<?= csrf_header() ?>': token
        };

        const response = await fetch(url, { method: 'POST', body: body, headers: headers });
        const data = await response.json();
        if (data.csrf_hash) syncCsrfTokenInDOM(data.csrf_hash);

        if (!response.ok || data.status !== 'success') throw new Error(data.message || 'Permintaan gagal diproses.');
        return data;
    };
    document.getElementById('btnRunGenerator')?.addEventListener('click', async function () {
        if (typeof Swal === 'undefined') return;
        const result = await Swal.fire({
            icon: 'question',
            title: 'Susun Jadwal Pelajaran Otomatis?',
            text: 'Sistem akan membuat kandidat jadwal untuk ditinjau. Jadwal aktif tidak akan berubah sampai Anda menekan Terapkan Kandidat.',
            showCancelButton: true,
            confirmButtonText: 'Ya, Susun Otomatis',
            cancelButtonText: 'Batal',
            customClass: { popup: 'rounded-4', confirmButton: 'btn btn-primary rounded-3 px-4 me-2', cancelButton: 'btn btn-light rounded-3 px-4' },
            buttonsStyling: false
        });
        if (!result.isConfirmed) return;
        Swal.fire({
            title: 'Menyusun Jadwal Pelajaran...',
            text: 'Memeriksa ketersediaan guru, kelas, dan ruang belajar.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        try {
            const response = await post(`<?= base_url('schedules') ?>/${versionId}/generate`);
            const outcome = response.data || {};
            const complete = Number(outcome.unplaced_requirements || 0) === 0;
            await Swal.fire({
                icon: complete ? 'success' : 'warning',
                title: complete ? 'Kandidat Siap Ditinjau' : 'Kandidat Belum Lengkap',
                text: outcome.apply_message || (complete ? 'Tinjau hasil sebelum diterapkan.' : 'Perbaiki gap jadwal lalu susun ulang.'),
                confirmButtonText: 'Buka Kandidat',
                buttonsStyling: false,
                customClass: { popup: 'rounded-4', confirmButton: 'btn btn-primary rounded-3 px-4' }
            });
            location.reload();
        } catch (error) {
            const isCapacityError = String(error.message || '').toLowerCase().includes('kapasitas');
            Swal.fire({
                icon: 'error', title: isCapacityError ? 'Kapasitas Jadwal Tidak Cukup' : 'Penyusunan Gagal',
                text: error.message,
                showCancelButton: isCapacityError, confirmButtonText: isCapacityError ? 'Buka Kegiatan Rutin' : 'Tutup', cancelButtonText: 'Tutup',
                buttonsStyling: false,
                customClass: { popup: 'rounded-4', confirmButton: 'btn btn-primary rounded-3 px-4 me-2', cancelButton: 'btn btn-light rounded-3 px-4' }
            }).then(result => { if (isCapacityError && result.isConfirmed) window.location.href = '<?= base_url('routine-activities') ?>'; });
        }
    });
    document.getElementById('btnApplyCandidate')?.addEventListener('click', async function () {
        if (typeof Swal === 'undefined') return;
        const result = await Swal.fire({
            icon: 'warning',
            title: 'Terapkan Kandidat Jadwal Ini?',
            text: 'Entri slot jadwal yang belum terkunci akan diperbarui dengan hasil susunan kandidat ini.',
            showCancelButton: true,
            confirmButtonText: 'Terapkan Sekarang',
            cancelButtonText: 'Batal',
            customClass: { popup: 'rounded-4', confirmButton: 'btn btn-primary rounded-3 px-4 me-2', cancelButton: 'btn btn-light rounded-3 px-4' },
            buttonsStyling: false
        });
        if (!result.isConfirmed) return;
        try {
            await post(`<?= base_url('schedules/candidates') ?>/${this.dataset.id}/apply`, {
                current_revision: <?= (int) $version['revision_number'] ?>
            });
            location.reload();
        } catch (error) {
            Swal.fire({
                icon: 'error', title: 'Gagal Menerapkan Kandidat', text: error.message,
                showCancelButton: true, confirmButtonText: 'Lihat Rincian Konflik', cancelButtonText: 'Tutup',
                buttonsStyling: false,
                customClass: { popup: 'rounded-4', confirmButton: 'btn btn-primary rounded-3 px-4 me-2', cancelButton: 'btn btn-light rounded-3 px-4' }
            }).then(result => { if (result.isConfirmed) openConflictDetails(); });
        }
    });
    async function openConflictDetails() {
        if (typeof Swal === 'undefined') return;
        try {
            const response = await fetch(`<?= base_url('schedules') ?>/${versionId}/audit`, {headers:{'X-Requested-With':'XMLHttpRequest'}});
            const data = await response.json();
            if (!response.ok || data.status === 'error') throw new Error(data.message || 'Audit konflik gagal.');
            const report = data.data || data;
            const conflicts = Array.isArray(report.blocking_conflicts) ? report.blocking_conflicts : (Array.isArray(report.conflicts) ? report.conflicts : []);
            const advisories = Array.isArray(report.advisories) ? report.advisories : [];
            const count = Number(report.total_blocking_conflicts ?? conflicts.length ?? 0);
            const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','\"':'&quot;'}[char]));
            const severityClass = severity => severity === 'CRITICAL' ? 'danger' : (severity === 'HIGH' ? 'warning' : 'secondary');
            const detailHtml = `<div class="text-start" style="max-height:420px;overflow:auto">${conflicts.length ? `<div class="small text-muted mb-2">Perbaiki konflik keras terlebih dahulu.</div>${conflicts.map((conflict,index)=>`<div class="border rounded-3 p-2 mb-2 bg-light"><strong class="small">${index+1}. ${escapeHtml(conflict.conflict_type||'KONFLIK')}</strong><div class="small mt-1">${escapeHtml(conflict.description||'')}</div></div>`).join('')}` : '<div class="text-success fw-semibold mb-2">Tidak ditemukan bentrok guru, kelas, ruang, atau kekurangan JP.</div>'}${advisories.length?`<div class="small fw-bold text-warning-emphasis mt-3 mb-1">Catatan pedagogis</div>${advisories.map(a=>`<div class="small border-start border-warning border-3 ps-2 mb-2">${escapeHtml(a.description||'')}</div>`).join('')}`:''}</div>`;
            Swal.fire({
                icon: count ? 'warning' : 'success',
                title: count ? `${count} Benturan Konflik Ditemukan` : 'Tidak Ada Konflik Terdeteksi',
                html: detailHtml,
                width: 760,
                showCancelButton: true,
                confirmButtonText: count ? 'Buka Pembagian Tugas' : 'Mengerti',
                cancelButtonText: count ? 'Tutup' : undefined,
                buttonsStyling: false,
                customClass: { popup: 'rounded-4', confirmButton: 'btn btn-primary rounded-3 px-4 me-2', cancelButton: 'btn btn-light rounded-3 px-4' }
            }).then(result => {
                if (result.isConfirmed && count) window.location.href = '<?= base_url('assignments') ?>';
            });
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Pemeriksaan Gagal', text: error.message || 'Tidak dapat memeriksa konflik saat ini.' });
        }
    }
    document.querySelectorAll('#btnAuditConflicts, .btnAuditConflictsInline').forEach(button => {
        button.addEventListener('click', openConflictDetails);
    });
});
</script>
<?= $this->endSection() ?>
