<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php
$dayNames = [1 => 'SENIN', 2 => 'SELASA', 3 => 'RABU', 4 => 'KAMIS', 5 => 'JUMAT', 6 => 'SABTU', 7 => 'MINGGU'];
$classroomCount = count($classrooms ?? []);
?>
<style>
.master-wrap { overflow-x:auto; border:1px solid #dbe3ef; border-radius:14px; background:#fff; }
.master-grid { min-width:1450px; border-collapse:separate; border-spacing:0; }
.master-grid th,.master-grid td { border-right:1px solid #dbe3ef; border-bottom:1px solid #dbe3ef; padding:5px; vertical-align:middle; }
.master-grid thead th { position:sticky; top:0; z-index:4; background:#f1f5f9; text-align:center; font-size:11px; }
.master-grid .unit-head { background:#dbeafe; color:#1e3a8a; font-size:12px; }
.master-grid .time-col { min-width:115px; width:115px; background:#f8fafc; position:sticky; left:0; z-index:3; }
.master-grid .class-head { min-width:118px; width:118px; }
.master-grid .day-label { min-width:58px; width:58px; background:#e2e8f0; font-weight:800; writing-mode:vertical-rl; transform:rotate(180deg); text-align:center; position:sticky; left:0; z-index:2; }
.master-grid .period-label { min-width:78px; width:78px; background:#f8fafc; font-size:11px; text-align:center; position:sticky; left:58px; z-index:2; }
.master-cell { min-height:42px; height:42px; text-align:center; background:#fff; }
.master-cell.drop-hover { outline:3px dashed #2563eb; outline-offset:-3px; background:#eff6ff; }
.master-card { display:block; border-radius:7px; padding:5px 3px; min-height:31px; font-size:10px; line-height:1.15; cursor:grab; color:#0f172a; font-weight:700; }
.master-card small { display:block; font-size:9px; font-weight:600; opacity:.85; margin-top:2px; }
.routine-cell { background:#f8fafc; color:#475569; font-size:10px; font-weight:700; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
    <div>
        <a href="<?= base_url('schedules/' . (int)$version['id'] . '/editor') ?>" class="btn btn-sm btn-light border me-2"><i class="bi bi-arrow-left"></i> Per Kelas</a>
        <span class="fw-bold fs-5">Master Jadwal Multi-Jenjang</span>
        <div class="text-muted small">Satu sumbu waktu untuk memantau guru yang mengajar lintas kelas SMP–SMA.</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= base_url('schedules/' . (int)$version['id'] . '/reports/multi-unit') ?>"><i class="bi bi-printer me-1"></i> Cetak Multi-Unit</a>
        <span class="badge bg-light text-dark border align-self-center px-3 py-2"><?= $classroomCount ?> kelas</span>
    </div>
</div>

<div class="alert alert-info border-0 shadow-sm small">
    <i class="bi bi-info-circle me-1"></i> Geser kartu pelajaran secara vertikal di kolom kelas yang sama. Sistem memeriksa slot kegiatan rutin, guru, ruang, dan status jadwal sebelum menyimpan perubahan.
</div>

<?php if (!$classrooms): ?>
    <div class="alert alert-warning">Tidak ada kelas yang dapat ditampilkan pada periode ini atau akses unit belum tersedia.</div>
<?php else: ?>
<div class="master-wrap shadow-sm">
<table class="master-grid">
    <thead>
        <tr>
            <th colspan="2" class="time-col">WAKTU</th>
            <?php $lastUnit = null; $unitStart = 0; foreach ($classrooms as $i => $c): if ($lastUnit !== null && $lastUnit !== ($c['unit_id'] ?? null)): ?>
                <th colspan="<?= $i - $unitStart ?>" class="unit-head"><?= esc($classrooms[$unitStart]['unit_code'] ?: $classrooms[$unitStart]['unit_name']) ?></th>
            <?php $unitStart = $i; endif; $lastUnit = $c['unit_id'] ?? null; endforeach; ?>
            <?php if ($lastUnit !== null): ?><th colspan="<?= $classroomCount - $unitStart ?>" class="unit-head"><?= esc($classrooms[$unitStart]['unit_code'] ?: $classrooms[$unitStart]['unit_name']) ?></th><?php endif; ?>
        </tr>
        <tr>
            <th class="day-label">HARI</th><th class="period-label">PERIODE</th>
            <?php foreach ($classrooms as $c): ?><th class="class-head"><?= esc($c['name']) ?><small class="d-block text-muted"><?= esc($c['unit_code'] ?? '') ?></small></th><?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($days as $day): $dayNo = (int)$day['day_of_week']; ?>
        <?php for ($slotNo = 1; $slotNo <= $max_slot; $slotNo++): ?>
        <tr>
            <?php if ($slotNo === 1): ?><td rowspan="<?= $max_slot ?>" class="day-label"><?= esc($dayNames[$dayNo] ?? ($day['day_name'] ?? 'HARI')) ?></td><?php endif; ?>
            <?php $shownTime = ''; foreach ($classrooms as $c) { $sv = (int)($c['schedule_version_id'] ?? 0); if (!empty($slots_by_version[$sv][$dayNo][$slotNo])) { $shownTime = $slots_by_version[$sv][$dayNo][$slotNo]; break; } } ?>
            <td class="period-label"><strong>JP <?= $slotNo ?></strong><?php if ($shownTime): ?><small class="d-block text-muted"><?= esc(substr((string)$shownTime['start_time'],0,5)) ?>–<?= esc(substr((string)$shownTime['end_time'],0,5)) ?></small><?php endif; ?></td>
            <?php foreach ($classrooms as $c):
                $classId = (int)$c['id']; $sv = (int)($c['schedule_version_id'] ?? 0);
                $slot = $slots_by_version[$sv][$dayNo][$slotNo] ?? null;
                $fixed = $fixed_cells[$classId][$dayNo][$slotNo] ?? null;
                $entry = $entry_cells[$classId][$dayNo][$slotNo] ?? null;
                $editable = isset($editable_versions[$sv]);
                $bg = !empty($entry['color_code']) ? $entry['color_code'] : (!empty($entry['color_label']) ? $entry['color_label'] : '#e2e8f0');
                $text = $entry ? (($entry['subject_code'] ?: $entry['subject_name'] ?: 'Mapel')) : '';
                $teacher = $entry ? (($entry['teacher_initial'] ?? '') ?: (isset($entry['teacher_name']) ? mb_substr((string)$entry['teacher_name'],0,3) : '')) : '';
            ?>
            <td class="master-cell" data-target-version-id="<?= $sv ?>" data-target-classroom-id="<?= $classId ?>" data-target-day-slot-id="<?= (int)($slot['id'] ?? 0) ?>">
                <?php if ($fixed): ?><div class="routine-cell" title="Kegiatan rutin"><?= esc($fixed['description'] ?? 'Kegiatan rutin') ?></div>
                <?php elseif ($entry): ?><div class="master-card" draggable="<?= $editable ? 'true' : 'false' ?>" style="background:<?= esc($bg) ?>" data-entry-id="<?= (int)$entry['id'] ?>" data-source-version-id="<?= (int)$entry['schedule_version_id'] ?>" data-source-classroom-id="<?= $classId ?>" data-source-day-slot-id="<?= (int)($slot['id'] ?? 0) ?>" data-subject-id="<?= (int)$entry['subject_id'] ?>" data-teacher-id="<?= (int)$entry['teacher_id'] ?>" data-second-teacher-id="<?= (int)($entry['second_teacher_id'] ?? 0) ?>" data-requirement-id="<?= (int)($entry['schedule_requirement_id'] ?? 0) ?>" data-room-id="<?= (int)($entry['room_id'] ?? 0) ?>"><span><?= esc($text) ?></span><small><?= esc($teacher) ?></small></div>
                <?php endif; ?>
            </td>
            <?php endforeach; ?>
        </tr>
        <?php endfor; ?>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<script>
(() => {
    const csrfName = '<?= csrf_token() ?>';
    const saveUrl = '<?= base_url('schedules') ?>';
    const token = () => (document.querySelector('input[name="' + csrfName + '"]') || {}).value || '<?= csrf_hash() ?>';
    let dragged = null;
    document.querySelectorAll('.master-card[draggable="true"]').forEach(card => {
        card.addEventListener('dragstart', () => { dragged = card; card.classList.add('opacity-50'); });
        card.addEventListener('dragend', () => { card.classList.remove('opacity-50'); dragged = null; });
    });
    document.querySelectorAll('.master-cell').forEach(cell => {
        cell.addEventListener('dragover', e => { if (dragged) { e.preventDefault(); cell.classList.add('drop-hover'); } });
        cell.addEventListener('dragleave', () => cell.classList.remove('drop-hover'));
        cell.addEventListener('drop', async e => {
            e.preventDefault(); cell.classList.remove('drop-hover');
            if (!dragged) return;
            if (cell.dataset.targetClassroomId !== dragged.dataset.sourceClassroomId || cell.dataset.targetVersionId !== dragged.dataset.sourceVersionId) {
                alert('Pindah lintas kelas/unit dinonaktifkan agar penugasan guru dan rombel tetap konsisten. Geser di kolom kelas yang sama.'); return;
            }
            if (!cell.dataset.targetDaySlotId || cell.dataset.targetDaySlotId === '0') { alert('Slot tujuan tidak tersedia.'); return; }
            const body = new FormData();
            body.set(csrfName, token()); body.set('day_slot_id', cell.dataset.targetDaySlotId); body.set('classroom_id', dragged.dataset.sourceClassroomId);
            body.set('source_day_slot_id', dragged.dataset.sourceDaySlotId || ''); body.set('source_entry_id', dragged.dataset.entryId || '');
            body.set('subject_id', dragged.dataset.subjectId); body.set('teacher_id', dragged.dataset.teacherId); body.set('second_teacher_id', dragged.dataset.secondTeacherId || '');
            body.set('schedule_requirement_id', dragged.dataset.requirementId || ''); body.set('room_id', dragged.dataset.roomId || '');
            try {
                const response = await fetch(saveUrl + '/' + dragged.dataset.sourceVersionId + '/save-entry', {method:'POST', body, headers:{'X-Requested-With':'XMLHttpRequest'}});
                const data = await response.json();
                if (!response.ok || data.status === 'error') throw new Error(data.message || 'Gagal menyimpan perubahan.');
                window.location.reload();
            } catch (err) { alert(err.message); }
        });
    });
})();
</script>
<?= $this->endSection() ?>
