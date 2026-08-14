<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php
$previousDate = date('Y-m-d', strtotime($selectedDate . ' -1 day'));
$nextDate = date('Y-m-d', strtotime($selectedDate . ' +1 day'));
$scope = (string) ($unitScope['selected'] ?? 'all');
$allCards = array_merge($workspace['morning'] ?? [], $workspace['classroom'] ?? [], $workspace['lessons'] ?? [], $workspace['afternoon'] ?? []);
$completed = count(array_filter($allCards, static fn(array $card): bool => !empty($card['session']) && in_array($card['session']['status'], ['SUBMITTED', 'VERIFIED'], true)));
$cardUrl = static function (array $card) use ($selectedDate): string {
    if (!empty($card['session']['id'])) return base_url('portal/attendance/session/' . $card['session']['id']);
    return base_url('portal/attendance/record?' . http_build_query([
        'type' => $card['session_type'], 'date' => $selectedDate,
        'classroom_id' => $card['classroom_id'], 'subject_id' => $card['subject_id'] ?? null,
        'schedule_entry_id' => $card['schedule_entry_id'] ?? null,
    ]));
};
$statusBadge = static function (?array $session): string {
    if (!$session) return '<span class="badge rounded-pill bg-light text-secondary border">Belum dicatat</span>';
    $status = strtoupper((string) $session['status']);
    $tone = $status === 'VERIFIED' ? 'success' : ($status === 'DRAFT' ? 'warning' : 'primary');
    $label = $status === 'VERIFIED' ? 'Terverifikasi' : ($status === 'DRAFT' ? 'Draft' : 'Terkirim');
    return '<span class="badge rounded-pill bg-' . $tone . '-subtle text-' . $tone . '">' . $label . '</span>';
};
?>
<style>
.attendance-app{max-width:1180px;margin:auto}.attendance-hero{background:linear-gradient(135deg,#352477,#6941e8 62%,#8b5cf6);color:#fff;border-radius:1.5rem;overflow:hidden;position:relative}.attendance-hero:after{content:"";position:absolute;width:220px;height:220px;border-radius:50%;background:rgba(255,255,255,.1);right:-65px;top:-100px}.date-control{background:#fff;border:1px solid #e7eaf1;border-radius:1rem;padding:.45rem;display:flex;align-items:center;gap:.5rem}.date-control a{text-decoration:none}.timeline-card{border:1px solid #edf0f5;border-radius:1.1rem;background:#fff;transition:.2s}.timeline-card:hover{transform:translateY(-2px);box-shadow:0 .7rem 1.5rem rgba(36,28,75,.08)}.timeline-time{width:74px;color:#6b7280;font-size:.82rem}.timeline-icon{width:42px;height:42px;border-radius:14px;display:grid;place-items:center;flex:0 0 auto}.section-label{font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:#8b82a8;font-weight:800}.recent-mobile{display:none}@media(max-width:767.98px){.attendance-app{padding:0 .25rem 5.5rem}.attendance-hero{border-radius:0 0 1.5rem 1.5rem;margin:-1rem -.75rem 1rem}.timeline-time{width:auto}.timeline-card{border-radius:1rem}.recent-desktop{display:none}.recent-mobile{display:block}.date-control{position:sticky;top:.5rem;z-index:8;box-shadow:0 .5rem 1.2rem rgba(30,22,60,.08)}}
</style>
<div class="container-fluid py-3 attendance-app">
    <?php foreach (['success'=>'success','error'=>'danger'] as $key=>$tone): if (session()->getFlashdata($key)): ?><div class="alert alert-<?= $tone ?> border-0 rounded-4 shadow-sm"><?= esc(session()->getFlashdata($key)) ?></div><?php endif; endforeach; ?>
    <section class="attendance-hero p-4 p-md-5 mb-3">
        <div class="position-relative" style="z-index:1">
            <span class="badge bg-white bg-opacity-20 text-white mb-2">ABSENSI TERPADU</span>
            <h2 class="fw-bold text-white mb-1">Kegiatan hari ini</h2>
            <p class="mb-3 text-white text-opacity-75">Apel, kelas, pembelajaran, dan jurnal dalam satu alur berdasarkan jadwal resmi.</p>
            <div class="d-flex flex-wrap gap-2"><span class="badge bg-white text-primary px-3 py-2"><?= esc($activePeriod['name'] ?? '-') ?></span><span class="badge bg-white bg-opacity-20 text-white px-3 py-2"><?= esc($unitScope['label'] ?? 'Semua Unit') ?></span></div>
        </div>
    </section>

    <div class="date-control mb-3">
        <a class="btn btn-light rounded-3" aria-label="Hari sebelumnya" href="?date=<?= $previousDate ?>&unit_scope=<?= urlencode($scope) ?>"><i data-lucide="chevron-left" class="icon-sm"></i></a>
        <label class="flex-grow-1 mb-0"><span class="visually-hidden">Pilih tanggal</span><input id="attendanceDate" type="date" class="form-control border-0 text-center fw-bold" value="<?= esc($selectedDate) ?>" min="<?= esc($activePeriod['start_date']) ?>" max="<?= esc($activePeriod['end_date']) ?>"></label>
        <a class="btn btn-light rounded-3" aria-label="Hari berikutnya" href="?date=<?= $nextDate ?>&unit_scope=<?= urlencode($scope) ?>"><i data-lucide="chevron-right" class="icon-sm"></i></a>
        <?php if ($selectedDate !== date('Y-m-d')): ?><a class="btn btn-primary rounded-3" href="?date=<?= date('Y-m-d') ?>&unit_scope=<?= urlencode($scope) ?>">Hari ini</a><?php endif; ?>
    </div>

    <?php if (!empty($workspace['calendar_day']) && (int) $workspace['calendar_day']['is_school_effective'] !== 1): ?>
        <div class="alert alert-warning border-0 rounded-4"><i data-lucide="calendar-off" class="icon-sm me-2"></i><strong>Hari non-efektif.</strong> <?= esc($workspace['calendar_day']['event_title'] ?: $workspace['calendar_day']['day_type_code']) ?>. Input sesi baru dinonaktifkan oleh kalender pendidikan.</div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-4"><div class="p-3 bg-primary-subtle rounded-4 h-100"><span class="section-label">Agenda</span><strong class="d-block fs-3 text-primary"><?= count($allCards) ?></strong></div></div>
        <div class="col-4"><div class="p-3 bg-success-subtle rounded-4 h-100"><span class="section-label">Selesai</span><strong class="d-block fs-3 text-success"><?= $completed ?></strong></div></div>
        <div class="col-4"><div class="p-3 bg-warning-subtle rounded-4 h-100"><span class="section-label">Tertunda</span><strong class="d-block fs-3 text-warning-emphasis"><?= max(0,count($allCards)-$completed) ?></strong></div></div>
    </div>

    <?php if ($allCards === []): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body text-center py-5"><div class="timeline-icon bg-light mx-auto mb-3"><i data-lucide="calendar-check"></i></div><h5 class="fw-bold">Tidak ada agenda presensi</h5><p class="text-muted mb-0">Tidak ada jadwal mengajar atau kelas binaan untuk tanggal ini.</p></div></div>
    <?php else: ?>
        <div class="section-label mb-2">Alur harian</div>
        <div class="d-grid gap-2 mb-4">
            <?php foreach ([['morning','sunrise','Pagi','warning'],['classroom','users','Kelas','info'],['lessons','book-open','Pembelajaran','primary'],['afternoon','sunset','Siang','success']] as [$group,$icon,$label,$tone]): foreach ($workspace[$group] ?? [] as $card): ?>
                <a href="<?= $cardUrl($card) ?>" class="timeline-card text-decoration-none text-dark p-3 d-flex align-items-center gap-3">
                    <div class="timeline-time"><?= !empty($card['start_time']) ? substr($card['start_time'],0,5) : $label ?></div>
                    <div class="timeline-icon bg-<?= $tone ?>-subtle text-<?= $tone ?>"><i data-lucide="<?= $icon ?>" class="icon-sm"></i></div>
                    <div class="flex-grow-1 min-w-0"><div class="fw-bold text-truncate"><?= esc($card['subject_name'] ?? \App\Services\AttendanceService::sessionTypeLabel($card['session_type'])) ?></div><small class="text-muted"><?= esc($card['classroom_name']) ?> · <?= esc($card['unit_code'] ?? $card['unit_name']) ?><?php if (!empty($card['jp_count'])): ?> · <?= (int)$card['jp_count'] ?> JP<?php endif; ?></small></div>
                    <div class="d-none d-sm-block"><?= $statusBadge($card['session'] ?? null) ?></div><i data-lucide="chevron-right" class="icon-sm text-muted"></i>
                </a>
            <?php endforeach; endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-end mb-2"><div><div class="section-label">Riwayat</div><h5 class="fw-bold mb-0">Sesi terbaru</h5></div><?php if (has_permission('attendances.view') && !is_guru()): ?><a href="<?= base_url('attendances') ?>" class="btn btn-sm btn-light">Monitoring</a><?php endif; ?></div>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden recent-desktop"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Jenis</th><th>Kelas</th><th>Kegiatan</th><th>Status</th><th></th></tr></thead><tbody><?php if (!$recentSessions):?><tr><td colspan="6" class="text-center py-5 text-muted">Belum ada sesi.</td></tr><?php endif;foreach($recentSessions as $row):?><tr><td><?= date('d/m/Y',strtotime($row['attendance_date'])) ?></td><td><span class="badge bg-light text-dark border"><?= esc(\App\Services\AttendanceService::sessionTypeLabel($row['session_type'] ?? 'SUBJECT')) ?></span></td><td><?= esc($row['classroom_name']) ?></td><td><?= esc($row['subject_name']) ?></td><td><?= $statusBadge($row) ?></td><td class="text-end"><a class="btn btn-sm btn-light" href="<?= base_url('portal/attendance/session/'.$row['id']) ?>"><i data-lucide="chevron-right" class="icon-xs"></i></a></td></tr><?php endforeach;?></tbody></table></div></div>
    <div class="recent-mobile d-grid gap-2"><?php foreach($recentSessions as $row):?><a class="timeline-card p-3 text-decoration-none text-dark d-flex gap-3 align-items-center" href="<?= base_url('portal/attendance/session/'.$row['id']) ?>"><div class="timeline-icon bg-light"><i data-lucide="clipboard-check" class="icon-sm"></i></div><div class="flex-grow-1 min-w-0"><strong class="d-block text-truncate"><?= esc($row['subject_name']) ?></strong><small class="text-muted"><?= date('d M',strtotime($row['attendance_date'])) ?> · <?= esc($row['classroom_name']) ?></small></div><?= $statusBadge($row) ?></a><?php endforeach;?></div>
</div>
<script>document.getElementById('attendanceDate')?.addEventListener('change',e=>location.href='?date='+encodeURIComponent(e.target.value)+'&unit_scope=<?= esc($scope,'js') ?>');</script>
<?= $this->endSection() ?>
