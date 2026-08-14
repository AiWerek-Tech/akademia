<?= $this->extend('layouts/admin') ?>

<?= $this->section('additional_css') ?>
<style>
    .elective-hero{background:linear-gradient(135deg,#312e81 0%,#4f46e5 55%,#2563eb 100%)}
    .elective-stat-icon{width:46px;height:46px;display:grid;place-items:center}
    .offering-card{transition:transform .18s ease,box-shadow .18s ease;border:1px solid rgba(99,102,241,.12)!important}
    .offering-card:hover{transform:translateY(-2px);box-shadow:0 .75rem 1.8rem rgba(30,41,59,.1)!important}
    .student-avatar{width:38px;height:38px;display:grid;place-items:center;background:linear-gradient(135deg,#eef2ff,#dbeafe);color:#4338ca;flex:none}
    .filter-label{font-size:.72rem;font-weight:700;letter-spacing:.035em;text-transform:uppercase;color:#64748b}
    .table-electives thead th{font-size:.7rem;letter-spacing:.04em;text-transform:uppercase;color:#64748b;white-space:nowrap}
    .table-electives tbody td{vertical-align:middle}
    @media(max-width:767.98px){.table-electives{min-width:940px}.elective-hero .card-body{padding:1.5rem!important}}
</style>
<?= $this->endSection() ?>

<?= $this->section('main_content') ?>
<?php
use App\Services\TeacherElectivePortalService;

$statusClass = static function (string $status): string {
    return match (strtoupper($status)) {
        'FINALIZED', 'ALLOCATED', 'APPROVED' => 'success',
        'NEEDS_REVISION', 'REJECTED' => 'danger',
        'CHANGE_REQUESTED', 'WAITING_CURRICULUM' => 'warning',
        default => 'primary',
    };
};
$query = static function (array $changes = []) use ($filters, $unitScope): string {
    $params = array_merge($filters, ['unit_scope' => $unitScope['selected'] ?? 'all'], $changes);
    foreach ($params as $key => $value) {
        if ($value === '' || $value === null || $value === 'ALL' || ($value === 0 && !in_array($key, ['page'], true))) {
            unset($params[$key]);
        }
    }
    return http_build_query($params);
};
?>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 elective-hero text-white">
    <div class="card-body p-4 p-lg-5 d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-4">
        <div>
            <span class="badge rounded-pill bg-white bg-opacity-10 text-white mb-3">Portal Guru · Data Read-only</span>
            <h3 class="fw-bold mb-2 d-flex align-items-center gap-2"><i data-lucide="users-round"></i> Mapel Pilihan Saya</h3>
            <p class="mb-0 text-white text-opacity-75">Daftar siswa yang memilih mata pelajaran yang diampu oleh <?= esc($teacher['full_name'] ?? active_user_name()) ?>.</p>
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2 align-items-stretch">
            <?php if (!empty($periods)): ?>
                <form method="get" action="<?= base_url('portal/electives') ?>" class="d-flex gap-2">
                    <input type="hidden" name="unit_scope" value="<?= esc($unitScope['selected'] ?? 'all') ?>">
                    <label class="visually-hidden" for="heroPeriod">Periode pemilihan</label>
                    <select class="form-select border-0 rounded-3" id="heroPeriod" name="period_id" onchange="this.form.submit()">
                        <?php if (!empty($unitScope['isAll'])): ?><option value="all" <?= (string)($selectedPeriod['id'] ?? '') === 'all' ? 'selected' : '' ?>>Semua periode · gabungan unit</option><?php endif; ?>
                        <?php foreach ($periods as $period): ?>
                            <option value="<?= (int) $period['id'] ?>" <?= (string)($selectedPeriod['id'] ?? '') === (string)$period['id'] ? 'selected' : '' ?>><?= esc(($period['unit_code'] ?? '') . ' · ' . ($period['title'] ?? 'Periode')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>
            <a href="<?= base_url('portal/electives/export?' . $query(['page' => null])) ?>" class="btn btn-light rounded-3 px-3 d-inline-flex align-items-center justify-content-center gap-2 <?= empty($students) ? 'disabled' : '' ?>" aria-disabled="<?= empty($students) ? 'true' : 'false' ?>">
                <i data-lucide="download" style="width:17px"></i> Ekspor CSV
            </a>
        </div>
    </div>
</div>

<?php if (empty($periods)): ?>
    <div class="card border-0 shadow-sm rounded-4"><div class="card-body text-center p-5">
        <span class="elective-stat-icon rounded-4 bg-primary bg-opacity-10 text-primary mx-auto mb-3"><i data-lucide="book-open-check"></i></span>
        <h5 class="fw-bold">Belum ada mapel pilihan yang ditugaskan</h5>
        <p class="text-muted mb-0">Data akan muncul otomatis setelah kurikulum menetapkan Anda sebagai pengampu pada periode pemilihan yang diterbitkan.</p>
    </div></div>
<?php else: ?>
    <div class="row g-3 mb-4">
        <?php foreach ([
            ['Mapel Diampu', $stats['offerings'], 'book-open', 'primary'],
            ['Siswa Unik', $stats['unique_students'], 'users', 'info'],
            ['Pilihan Utama', $stats['primary'], 'badge-check', 'success'],
            ['Pilihan Cadangan', $stats['backup'], 'bookmark', 'warning'],
            ['Sudah Final', $stats['finalized'], 'circle-check-big', 'primary'],
        ] as [$label, $value, $icon, $color]): ?>
            <div class="col-6 col-lg"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-3 d-flex align-items-center gap-3">
                <span class="elective-stat-icon rounded-3 bg-<?= $color ?> bg-opacity-10 text-<?= $color ?>"><i data-lucide="<?= $icon ?>" style="width:21px"></i></span>
                <div><div class="filter-label"><?= esc($label) ?></div><div class="fs-4 fw-bold text-slate-800"><?= number_format((int)$value) ?></div></div>
            </div></div></div>
        <?php endforeach; ?>
    </div>

    <div class="d-flex justify-content-between align-items-end gap-3 mb-3">
        <div><h5 class="fw-bold mb-1">Ringkasan Mata Pelajaran</h5><p class="text-muted fs-8 mb-0"><?= esc($selectedPeriod['title'] ?? '-') ?> · <?= esc($selectedPeriod['academic_year_name'] ?? '-') ?></p></div>
        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2"><?= esc($unitScope['label'] ?? 'Semua Unit') ?></span>
    </div>
    <div class="row g-3 mb-4">
        <?php foreach ($offerings as $offering): ?>
            <?php $capacity = max(1, (int)($offering['maximum_students'] ?? 0)); $primary = (int)$offering['primary_count']; $fill = min(100, (int)round(($primary / $capacity) * 100)); ?>
            <div class="col-md-6 col-xl-4"><a href="<?= base_url('portal/electives?' . $query(['offering_id' => (int)$offering['id'], 'page' => 1])) ?>" class="card offering-card border-0 shadow-sm rounded-4 h-100 text-decoration-none text-reset">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between gap-3 mb-3"><div><span class="badge bg-primary-subtle text-primary rounded-pill mb-2"><?= esc($offering['subject_code'] ?? 'MAPEL') ?></span><h5 class="fw-bold mb-0"><?= esc($offering['subject_name']) ?></h5></div><span class="text-primary fw-bold text-nowrap"><?= number_format((float)$offering['weekly_hours'], fmod((float)$offering['weekly_hours'], 1.0) ? 1 : 0) ?> JP</span></div>
                    <div class="d-flex gap-3 fs-8 mb-3"><span><strong class="text-success"><?= $primary ?></strong> utama</span><span><strong class="text-warning"><?= (int)$offering['backup_count'] ?></strong> cadangan</span><span><strong class="text-primary"><?= (int)$offering['finalized_count'] ?></strong> final</span></div>
                    <div class="progress" style="height:7px"><div class="progress-bar" style="width:<?= $fill ?>%"></div></div>
                    <div class="d-flex justify-content-between mt-2 fs-9 text-muted"><span>Kapasitas utama</span><span><?= $primary ?>/<?= $capacity ?></span></div>
                    <?php if (empty($offering['is_approved'])): ?><span class="badge bg-warning-subtle text-warning-emphasis rounded-pill mt-3">Belum disetujui kurikulum</span><?php endif; ?>
                </div>
            </a></div>
        <?php endforeach; ?>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3"><div><h5 class="fw-bold mb-1">Daftar Siswa Pemilih</h5><p class="text-muted fs-8 mb-0">Hanya pengajuan yang sudah dikirim; data draf siswa tidak ditampilkan.</p></div><span class="badge bg-light text-slate-700 border rounded-pill"><?= number_format((int)$pagination['total']) ?> baris</span></div>
            <form method="get" action="<?= base_url('portal/electives') ?>" class="row g-3 align-items-end">
                <input type="hidden" name="unit_scope" value="<?= esc($unitScope['selected'] ?? 'all') ?>"><input type="hidden" name="period_id" value="<?= esc((string)($selectedPeriod['id'] ?? '')) ?>">
                <div class="col-md-6 col-xl-3"><label class="filter-label mb-1" for="electiveSearch">Cari siswa</label><div class="input-group"><span class="input-group-text bg-light"><i data-lucide="search" style="width:16px"></i></span><input class="form-control" id="electiveSearch" name="q" value="<?= esc($filters['q']) ?>" placeholder="Nama, NIS, atau kelas"></div></div>
                <div class="col-md-6 col-xl-3"><label class="filter-label mb-1" for="offeringFilter">Mata pelajaran</label><select class="form-select" id="offeringFilter" name="offering_id"><option value="0">Semua mapel saya</option><?php foreach ($offerings as $offering): ?><option value="<?= (int)$offering['id'] ?>" <?= (int)$filters['offering_id'] === (int)$offering['id'] ? 'selected' : '' ?>><?= esc($offering['subject_name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6 col-xl-2"><label class="filter-label mb-1" for="classroomFilter">Kelas</label><select class="form-select" id="classroomFilter" name="classroom_id"><option value="0">Semua kelas</option><?php foreach ($classrooms as $classroom): ?><option value="<?= (int)$classroom['id'] ?>" <?= (int)$filters['classroom_id'] === (int)$classroom['id'] ? 'selected' : '' ?>><?= esc($classroom['name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6 col-xl-2"><label class="filter-label mb-1" for="choiceFilter">Jenis pilihan</label><select class="form-select" id="choiceFilter" name="choice_type"><option value="ALL">Semua</option><option value="PRIMARY" <?= $filters['choice_type']==='PRIMARY'?'selected':'' ?>>Utama</option><option value="BACKUP" <?= $filters['choice_type']==='BACKUP'?'selected':'' ?>>Cadangan</option></select></div>
                <div class="col-6 col-xl-1"><label class="filter-label mb-1" for="perPage">Baris</label><select class="form-select" id="perPage" name="per_page"><?php foreach ([10,25,50,100] as $size): ?><option value="<?= $size ?>" <?= (int)$filters['per_page']===$size?'selected':'' ?>><?= $size ?></option><?php endforeach; ?></select></div>
                <div class="col-6 col-xl-1"><button class="btn btn-primary w-100" type="submit" title="Terapkan filter"><i data-lucide="sliders-horizontal" style="width:17px"></i><span class="visually-hidden">Terapkan</span></button></div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <select class="form-select form-select-sm w-auto" name="submission_status" aria-label="Status pengajuan"><option value="ALL">Semua status pengajuan</option><?php foreach ($submissionStatuses as $status): ?><option value="<?= esc($status) ?>" <?= $filters['submission_status']===$status?'selected':'' ?>><?= esc(TeacherElectivePortalService::statusLabel($status)) ?></option><?php endforeach; ?></select>
                    <select class="form-select form-select-sm w-auto" name="allocation_status" aria-label="Status alokasi"><option value="ALL">Semua status alokasi</option><?php foreach ($allocationStatuses as $status): ?><option value="<?= esc($status) ?>" <?= $filters['allocation_status']===$status?'selected':'' ?>><?= esc(TeacherElectivePortalService::statusLabel($status)) ?></option><?php endforeach; ?></select>
                    <select class="form-select form-select-sm w-auto" name="sort" aria-label="Urutkan"><option value="name" <?= $filters['sort']==='name'?'selected':'' ?>>Urut nama</option><option value="classroom" <?= $filters['sort']==='classroom'?'selected':'' ?>>Urut kelas</option><option value="subject" <?= $filters['sort']==='subject'?'selected':'' ?>>Urut mapel</option><option value="priority" <?= $filters['sort']==='priority'?'selected':'' ?>>Urut prioritas</option><option value="status" <?= $filters['sort']==='status'?'selected':'' ?>>Urut status</option></select>
                    <input type="hidden" name="direction" value="ASC"><a href="<?= base_url('portal/electives?period_id='.urlencode((string)($selectedPeriod['id'] ?? '')).'&unit_scope='.urlencode((string)($unitScope['selected'] ?? 'all'))) ?>" class="btn btn-sm btn-light border">Reset filter</a>
                </div>
            </form>
        </div>
        <div class="table-responsive border-top">
            <table class="table table-hover table-electives mb-0">
                <thead class="bg-light"><tr><th class="ps-4">Siswa</th><th>Kelas / Unit</th><th>Mata Pelajaran</th><th>Pilihan</th><th>Status Pengajuan</th><th>Status Alokasi</th><th class="pe-4">Diajukan</th></tr></thead>
                <tbody>
                <?php if (empty($students)): ?>
                    <tr><td colspan="7" class="text-center p-5"><i data-lucide="search-x" class="text-muted opacity-50 mb-2" style="width:38px;height:38px"></i><h6 class="fw-bold mb-1">Tidak ada siswa ditemukan</h6><p class="text-muted fs-8 mb-0">Coba ubah filter, atau tunggu siswa mengirim pilihan mereka.</p></td></tr>
                <?php else: foreach ($students as $student): ?>
                    <?php $initials = implode('', array_map(static fn($part) => mb_strtoupper(mb_substr($part,0,1)), array_slice(preg_split('/\s+/', trim((string)$student['full_name'])) ?: [],0,2))); ?>
                    <tr><td class="ps-4"><div class="d-flex align-items-center gap-3"><span class="student-avatar rounded-circle fw-bold fs-8"><?= esc($initials ?: 'S') ?></span><div><div class="fw-bold text-slate-800"><?= esc($student['full_name']) ?></div><div class="text-muted fs-9"><?= esc($student['student_number'] ?: 'NIS belum tersedia') ?></div></div></div></td>
                    <td><div class="fw-semibold"><?= esc($student['classroom_name'] ?? '-') ?></div><span class="badge bg-info-subtle text-info-emphasis rounded-pill"><?= esc($student['unit_code'] ?? '-') ?></span></td>
                    <td><div class="fw-semibold"><?= esc($student['subject_name']) ?></div><span class="text-muted fs-9"><?= esc($student['subject_code'] ?? '') ?></span></td>
                    <td><span class="badge <?= $student['choice_type']==='PRIMARY'?'bg-success-subtle text-success-emphasis':'bg-warning-subtle text-warning-emphasis' ?> rounded-pill"><?= $student['choice_type']==='PRIMARY'?'Utama':'Cadangan' ?> #<?= (int)$student['priority_order'] ?></span></td>
                    <td><span class="badge bg-<?= $statusClass((string)$student['submission_status']) ?>-subtle text-<?= $statusClass((string)$student['submission_status']) ?>-emphasis rounded-pill"><?= esc(TeacherElectivePortalService::statusLabel((string)$student['submission_status'])) ?></span></td>
                    <td><span class="badge bg-<?= $statusClass((string)$student['allocation_status']) ?>-subtle text-<?= $statusClass((string)$student['allocation_status']) ?>-emphasis rounded-pill"><?= esc(TeacherElectivePortalService::statusLabel((string)$student['allocation_status'])) ?></span></td>
                    <td class="pe-4 text-nowrap text-muted fs-8"><?= !empty($student['submitted_at']) ? date('d M Y, H:i', strtotime($student['submitted_at'])) : '-' ?></td></tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ((int)$pagination['pages'] > 1): ?>
            <div class="card-footer bg-white border-0 p-4 d-flex justify-content-between align-items-center gap-3"><span class="text-muted fs-8">Halaman <?= (int)$pagination['page'] ?> dari <?= (int)$pagination['pages'] ?></span><nav aria-label="Navigasi daftar siswa"><ul class="pagination pagination-sm mb-0"><li class="page-item <?= (int)$pagination['page']<=1?'disabled':'' ?>"><a class="page-link" href="<?= base_url('portal/electives?'.$query(['page'=>max(1,(int)$pagination['page']-1)])) ?>">Sebelumnya</a></li><li class="page-item <?= (int)$pagination['page']>=(int)$pagination['pages']?'disabled':'' ?>"><a class="page-link" href="<?= base_url('portal/electives?'.$query(['page'=>min((int)$pagination['pages'],(int)$pagination['page']+1)])) ?>">Berikutnya</a></li></ul></nav></div>
        <?php endif; ?>
    </div>
<?php endif; ?>
<script>document.addEventListener('DOMContentLoaded',function(){if(typeof lucide!=='undefined')lucide.createIcons();});</script>
<?= $this->endSection() ?>
