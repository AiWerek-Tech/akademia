<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php
$readyPercent = (int) round(($readinessDone / max(1, count($readiness))) * 100);
$mode = $workspace['mode'] ?? 'general';
$heroCopy = [
    'administration' => ['Kontrol Akademik', 'Kelola data, akses pengguna, kurikulum, penugasan, dan jadwal dari satu pusat kendali.'],
    'academic' => ['Ruang Kerja Kurikulum', 'Pantau kesiapan kurikulum, pembagian tugas, beban guru, dan jadwal akademik.'],
    'executive' => ['Ringkasan Eksekutif', 'Lihat kondisi operasional sekolah dan dokumen resmi sesuai wewenang Anda.'],
    'teacher' => ['Ruang Kerja Personal', 'Jadwal, beban mengajar, SK pembagian tugas, dan piket Anda tersedia di satu tempat.'],
    'student' => ['Portal Peserta Didik', 'Akses pilihan mata pelajaran dan informasi akademik yang tersedia untuk Anda.'],
    'operations' => ['Ruang Kerja Operasional', 'Kelola layanan administrasi sekolah sesuai hak akses yang diberikan.'],
    'general' => ['Dashboard Akademik', 'Akses fitur sistem sesuai peran dan wewenang Anda.'],
][$mode] ?? ['Dashboard Akademik', 'Akses fitur sistem sesuai peran dan wewenang Anda.'];
?>
<div class="d-lg-none"><?= view('dashboard_mobile', compact('mode','workspace','roleDashboard','unitScope','activePeriodStr','activePeriod','totalUnits','totalUsers','masterCounts','readiness','readinessDone','planningCounts')) ?></div>
<section class="native-dashboard d-none d-lg-block" aria-labelledby="dashboard-title">
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden dashboard-hero" style="background:linear-gradient(135deg,#17324d,#2563eb);">
        <div class="card-body p-4 p-lg-5 text-white d-flex flex-column flex-lg-row justify-content-between gap-4">
            <div><span class="badge rounded-pill mb-3" style="background:rgba(255,255,255,.16);color:#fff"><span class="visually-hidden">Ringkasan Akademik · </span><?= esc($heroCopy[0]) ?></span><h2 id="dashboard-title" class="fw-bold mb-2">Halo, <?= esc(active_user_name()) ?></h2><p class="mb-0 text-white text-opacity-75"><?= esc($heroCopy[1]) ?></p><div class="mt-3 d-flex gap-2 flex-wrap"><span class="badge bg-white bg-opacity-10 text-white rounded-pill"><?= esc(active_user_role()) ?></span><?php foreach (array_slice((array)(session()->get('all_role_codes') ?? []), 1) as $extraRole): ?><span class="badge bg-white bg-opacity-10 text-white rounded-pill"><?= esc(str_replace('_', ' ', ucwords($extraRole, '_'))) ?></span><?php endforeach; ?></div></div>
            <div class="dashboard-context-panel bg-white bg-opacity-10 rounded-4 p-3 align-self-lg-center" style="min-width:260px"><div class="small text-white text-opacity-75">Cakupan data</div><div class="fw-bold mt-1"><?= esc($unitScope['label'] ?? 'Unit aktif') ?></div><div class="small text-white text-opacity-75 mt-2">Periode aktif</div><div class="fw-bold mt-1"><?= esc($activePeriodStr) ?></div><?php if (!$activePeriod): ?><a href="<?= base_url('academic-periods') ?>" class="btn btn-sm btn-light mt-2">Atur periode</a><?php endif; ?></div>
        </div>
    </div>

    <?php if (has_permission('regulations.view') || has_permission('learning_outcomes.view') || has_permission('learning_objectives.view') || has_permission('learning_sequences.view') || has_permission('learning_packs.view') || has_permission('ksp.view')): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-indigo">
            <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div><span class="badge bg-primary-subtle text-primary rounded-pill mb-2">IALOS Education</span><h5 class="fw-bold mb-1">Control Center <?= $mode === 'teacher' ? 'Guru' : ($mode === 'executive' ? 'Pimpinan' : 'Akademik') ?></h5><p class="text-muted mb-0">Buka workspace pendidikan terpadu sesuai peran dan cakupan unit Anda.</p></div>
                <a href="<?= base_url('education') ?>" class="btn btn-primary rounded-3"><i data-lucide="layout-dashboard" class="me-1"></i>Buka Control Center</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (($roleDashboard['code'] ?? '') === 'wali_kelas' || is_wali_kelas()): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-primary">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center">
                    <div>
                        <span class="badge bg-primary-subtle text-primary rounded-pill mb-2">Ruang Kerja Wali Kelas</span>
                        <h5 class="fw-bold mb-1"><?= esc($roleDashboard['classroom']['name'] ?? 'Rombel belum ditautkan') ?></h5>
                        <p class="text-muted mb-0">Kelola pilihan mapel siswa di rombel Anda dan buka jadwal kelas yang sudah diterbitkan.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if (!empty($roleDashboard['classroom'])): ?>
                            <a href="<?= base_url('electives') ?>" class="btn btn-primary rounded-3"><i data-lucide="list-checks" class="me-1" style="width:16px"></i>Pilih Mapel Siswa</a>
                            <?php if (!empty($roleDashboard['published_schedule_id'])): ?>
                                <a href="<?= base_url('portal/schedule?scope=classroom&classroom_id=' . (int) $roleDashboard['classroom']['id']) ?>" class="btn btn-outline-primary rounded-3"><i data-lucide="calendar-days" class="me-1" style="width:16px"></i>Jadwal Kelas</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-<?= !empty($roleDashboard['classroom_filtered_out']) ? 'muted' : 'danger' ?> small align-self-center"><?= !empty($roleDashboard['classroom_filtered_out']) ? 'Kelas binaan berada di unit lain. Pilih Semua Unit atau unit yang sesuai.' : 'Hubungi superadmin untuk menautkan akun ke rombel.' ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($workspace['teacher']) || in_array($mode, ['teacher'], true)): ?>
        <div class="row g-3 mb-4">
            <?php foreach ([
                ['JP Mengajar', $workspace['teaching_hours'], 'clock-3', 'primary'],
                ['Mata Pelajaran', $workspace['subject_count'], 'book-open', 'success'],
                ['Rombel', $workspace['classroom_count'], 'school', 'info'],
                ['Hari Piket', $workspace['duty_days'], 'shield-check', 'warning'],
            ] as [$label, $value, $icon, $color]): ?>
                <div class="col-6 col-xl-3"><div class="card native-kpi-card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-3 p-md-4 d-flex align-items-center gap-3"><span class="native-kpi-icon bg-<?= $color ?> bg-opacity-10 text-<?= $color ?> rounded-3 p-3"><i data-lucide="<?= $icon ?>" style="width:22px;height:22px"></i></span><div><div class="text-muted fs-8 text-uppercase fw-semibold"><?= esc($label) ?></div><div class="fs-4 fw-bold text-slate-800"><?= number_format((float)$value, fmod((float)$value, 1.0) === 0.0 ? 0 : 1) ?></div></div></div></div></div>
            <?php endforeach; ?>
        </div>
        <div class="row g-4 mb-4">
            <div class="col-lg-7"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-start mb-3"><div><h5 class="fw-bold mb-1">Agenda Mengajar Hari Ini</h5><p class="text-muted fs-8 mb-0">Jadwal pada <?= esc($unitScope['label'] ?? 'unit terpilih') ?> dan periode aktif.</p></div><a href="<?= base_url('portal/schedule') ?>" class="btn btn-sm btn-light border rounded-3">Jadwal lengkap</a></div><?php if (empty($workspace['today_entries'])): ?><div class="text-center py-4 text-muted"><i data-lucide="calendar-check" style="width:36px;height:36px" class="mb-2 opacity-50"></i><p class="mb-0"><?= !empty($workspace['calendar_notice']) ? 'Kalender: '.esc($workspace['calendar_notice']) : 'Tidak ada jam mengajar hari ini.' ?></p></div><?php else: ?><div class="d-grid gap-2"><?php foreach ($workspace['today_entries'] as $entry): ?><div class="border rounded-3 p-3 d-flex justify-content-between align-items-center gap-3"><div><strong><?= esc($entry['subject_name']) ?></strong><?php if (!empty($unitScope['isAll'])): ?><span class="badge bg-info-subtle text-info-emphasis rounded-pill ms-1"><?= esc($entry['unit_code'] ?? '-') ?></span><?php endif; ?><div class="text-muted small"><?= esc($entry['classroom_name']) ?><?= !empty($entry['room_name']) ? ' · '.esc($entry['room_name']) : '' ?></div><?php if (!empty($entry['is_substitution_assignment'])): ?><span class="badge bg-warning-subtle text-warning-emphasis rounded-pill mt-1">Menggantikan <?= esc($entry['substitution_owner_name'] ?? 'guru') ?></span><?php endif; ?></div><span class="badge bg-primary-subtle text-primary rounded-pill">JP <?= (int)$entry['slot_number'] ?></span></div><?php endforeach; ?></div><?php endif; ?></div></div></div>
            <div class="col-lg-5"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4"><h5 class="fw-bold mb-1">Dokumen & Tugas Saya</h5><p class="text-muted fs-8 mb-3">Akses cepat produk personal pada <?= esc($unitScope['label'] ?? 'unit terpilih') ?>.</p><div class="d-grid gap-2"><?php if (has_permission('teacher_electives.view')): ?><a href="<?= base_url('portal/electives') ?>" class="btn btn-outline-primary text-start rounded-3 d-flex align-items-center"><i data-lucide="users-round" class="me-2" style="width:17px"></i><span>Mapel Pilihan Saya</span><span class="badge bg-primary rounded-pill ms-auto"><?= (int)$workspace['elective_student_count'] ?> siswa</span></a><?php endif; ?><?php if (has_permission('teacher_workload.view')): ?><a href="<?= base_url('portal/workload') ?>" class="btn btn-outline-primary text-start rounded-3"><i data-lucide="bar-chart-2" class="me-2" style="width:17px"></i>Beban Mengajar Saya</a><?php endif; ?><?php if (has_permission('teacher_assignment_document.view')): ?><a href="<?= base_url('portal/assignment-document') ?>" class="btn <?= $workspace['assignment_document_available'] ? 'btn-primary' : 'btn-light border text-muted' ?> text-start rounded-3"><i data-lucide="file-signature" class="me-2" style="width:17px"></i><?= $workspace['assignment_document_available'] ? ($workspace['assignment_document_official'] ? 'Cetak SK Pembagian Tugas' : 'Pratinjau SK Pembagian Tugas') : 'Data SK belum tersedia' ?></a><?php endif; ?><?php if (has_permission('teacher_duty_schedule.view')): ?><a href="<?= base_url('portal/duty-schedule') ?>" class="btn btn-outline-primary text-start rounded-3"><i data-lucide="shield-check" class="me-2" style="width:17px"></i>Jadwal Piket Saya</a><?php endif; ?></div></div></div></div>
        </div>
    <?php endif; ?>

    <?php if (in_array($mode, ['executive', 'academic'], true)): ?>
        <div class="row g-3 mb-4">
            <?php foreach ([['Versi tugas resmi',$workspace['executive']['approved_assignments'],'file-check-2','success'],['Jadwal diterbitkan',$workspace['executive']['published_schedules'],'calendar-check','primary'],['Konflik terbuka',$workspace['executive']['open_conflicts'],'triangle-alert',$workspace['executive']['open_conflicts'] ? 'danger' : 'success']] as [$label,$value,$icon,$color]): ?><div class="col-md-4"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4 d-flex align-items-center gap-3"><span class="bg-<?= $color ?> bg-opacity-10 text-<?= $color ?> rounded-3 p-3"><i data-lucide="<?= $icon ?>"></i></span><div><div class="text-muted small"><?= esc($label) ?></div><div class="fs-3 fw-bold"><?= number_format((int)$value) ?></div></div></div></div></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($mode === 'executive'): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4"><h5 class="fw-bold mb-1">Laporan & Dokumen Eksekutif</h5><p class="text-muted fs-8 mb-3">Akses baca dan cetak sesuai wewenang pimpinan.</p><div class="row g-2"><?php if (has_permission('assignments.view')): ?><div class="col-md-4"><a href="<?= base_url('assignments') ?>" class="btn btn-outline-primary w-100 text-start rounded-3"><i data-lucide="file-check-2" class="me-2" style="width:17px"></i>SK Pembagian Tugas</a></div><?php endif; ?><?php if (has_permission('workloads.view')): ?><div class="col-md-4"><a href="<?= base_url('workloads') ?>" class="btn btn-outline-primary w-100 text-start rounded-3"><i data-lucide="bar-chart-3" class="me-2" style="width:17px"></i>Beban Kerja Guru</a></div><?php endif; ?><?php if (has_permission('schedules.view')): ?><div class="col-md-4"><a href="<?= base_url('schedules') ?>" class="btn btn-outline-primary w-100 text-start rounded-3"><i data-lucide="calendar-days" class="me-2" style="width:17px"></i>Jadwal Resmi</a></div><?php endif; ?></div></div></div>
    <?php endif; ?>

    <?php if ($mode === 'operations'): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4"><h5 class="fw-bold mb-1">Layanan Administrasi</h5><p class="text-muted fs-8 mb-3">Fitur operasional sesuai wewenang Tata Usaha.</p><div class="row g-2"><?php foreach ([['students.view','students','users-round','Peserta Didik'],['classrooms.view','classrooms','school','Kelas / Rombel'],['teachers.view','teachers','contact','Data Guru'],['users.view','users','user-cog','Akun Pengguna']] as [$permission,$href,$icon,$label]): ?><?php if (has_permission($permission)): ?><div class="col-md-6 col-xl-3"><a href="<?= base_url($href) ?>" class="btn btn-outline-primary w-100 text-start rounded-3"><i data-lucide="<?= $icon ?>" class="me-2" style="width:17px"></i><?= esc($label) ?></a></div><?php endif; ?><?php endforeach; ?></div></div></div>
    <?php endif; ?>

    <?php if ($mode === 'student'): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4 p-lg-5 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3"><div><span class="badge bg-primary-subtle text-primary rounded-pill mb-2">Data Siswa</span><h4 class="fw-bold mb-1"><?= esc($workspace['student']['full_name'] ?? active_user_name()) ?></h4><p class="text-muted mb-0"><?= esc($workspace['student']['classroom_name'] ?? 'Rombel belum ditautkan') ?></p></div><?php if (has_permission('electives.selection.submit')): ?><a href="<?= base_url('my-electives') ?>" class="btn btn-primary rounded-3 px-4"><i data-lucide="list-checks" class="me-1"></i>Buka Pilihan Mata Pelajaran</a><?php endif; ?></div></div>
    <?php endif; ?>

    <?php if (session('success')): ?><div class="alert alert-success rounded-3"><?= esc(session('success')) ?></div><?php endif; ?>
    <?php if (session('error')): ?><div class="alert alert-danger rounded-3"><?= esc(session('error')) ?></div><?php endif; ?>

    <?php if (!$showAdministrativeDashboard): ?>
        <?php if (empty($workspace['teacher']) && $mode === 'general'): ?>
        <div class="row g-3 mb-4">
            <?php if (has_permission('teacher_schedule.view')): ?>
                <div class="col-md-6">
                    <a href="<?= base_url('portal/schedule') ?>" class="card border-0 shadow-sm rounded-4 h-100 text-decoration-none">
                        <div class="card-body p-4 d-flex align-items-center gap-3">
                            <span class="bg-primary bg-opacity-10 text-primary rounded-3 p-3"><i data-lucide="calendar-days"></i></span>
                            <div><h5 class="fw-bold text-slate-800 mb-1">Jadwal Read-only</h5><p class="text-muted mb-0 fs-8">Prioritas jadwal Anda, dengan opsi kelas dan jenjang.</p></div>
                        </div>
                    </a>
                </div>
            <?php endif; ?>
            <?php if (has_permission('teacher_workload.view')): ?>
                <div class="col-md-6">
                    <a href="<?= base_url('portal/workload') ?>" class="card border-0 shadow-sm rounded-4 h-100 text-decoration-none">
                        <div class="card-body p-4 d-flex align-items-center gap-3">
                            <span class="bg-success bg-opacity-10 text-success rounded-3 p-3"><i data-lucide="bar-chart-2"></i></span>
                            <div><h5 class="fw-bold text-slate-800 mb-1">Penugasan & Beban Saya</h5><p class="text-muted mb-0 fs-8">Hanya penugasan dan rekap beban milik Anda.</p></div>
                        </div>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php else: ?>
    <div class="row g-3 mb-4">
        <?php foreach ([['Unit aktif',$totalUnits,'building-2','primary'],['Pengguna aktif',$totalUsers,'users','info'],['Guru aktif',$masterCounts['teachers'],'user-check','success'],['Rombel aktif',$masterCounts['classrooms'],'graduation-cap','warning']] as [$label,$value,$icon,$color]): ?>
            <div class="col-6 col-xl-3"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-3 p-md-4 d-flex align-items-center gap-3"><span class="bg-<?= $color ?> bg-opacity-10 text-<?= $color ?> rounded-3 p-3"><i data-lucide="<?= $icon ?>" style="width:22px;height:22px"></i></span><div><div class="text-muted fs-8 text-uppercase fw-semibold"><?= esc($label) ?></div><div class="fs-4 fw-bold text-slate-800"><?= number_format((int)$value) ?></div></div></div></div></div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-start mb-3"><div><h5 class="fw-bold mb-1">Kesiapan Data Master</h5><p class="text-muted fs-8 mb-0">Lengkapi fondasi sebelum menyusun penugasan dan jadwal.</p></div><span class="badge bg-primary bg-opacity-10 text-primary rounded-pill"><?= $readinessDone ?>/<?= count($readiness) ?> siap</span></div><div class="progress mb-4" style="height:8px"><div class="progress-bar" style="width:<?= $readyPercent ?>%"></div></div><div class="row g-2"><?php foreach ($readiness as $item): ?><div class="col-md-6"><a href="<?= base_url($item['href']) ?>" class="d-flex align-items-center justify-content-between border rounded-3 p-3 text-decoration-none text-slate-700"><span class="d-flex align-items-center gap-2"><i data-lucide="<?= $item['ready'] ? 'check-circle-2' : 'alert-circle' ?>" class="text-<?= $item['ready'] ? 'success' : 'warning' ?>" style="width:18px;height:18px"></i><?= esc($item['label']) ?></span><i data-lucide="chevron-right" style="width:16px;height:16px"></i></a></div><?php endforeach; ?></div></div></div></div>
        <div class="col-lg-5"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4"><h5 class="fw-bold mb-1">Akses Cepat</h5><p class="text-muted fs-8 mb-3">Tindakan yang sering digunakan.</p><div class="d-grid gap-2">
            <?php if (has_permission('academic_periods.view')): ?><a href="<?= base_url('academic-periods') ?>" class="btn btn-outline-primary text-start rounded-3"><i data-lucide="calendar-range" class="me-2" style="width:17px"></i>Tahun Pelajaran</a><?php endif; ?>
            <?php if (has_permission('settings.view')): ?><a href="<?= base_url('settings/school-profile') ?>" class="btn btn-outline-primary text-start rounded-3"><i data-lucide="building" class="me-2" style="width:17px"></i>Profil Sekolah</a><?php endif; ?>
            <?php if (has_permission('teachers.view')): ?><a href="<?= base_url('teachers') ?>" class="btn btn-outline-primary text-start rounded-3"><i data-lucide="users" class="me-2" style="width:17px"></i>Kelola Guru</a><?php endif; ?>
            <?php if (has_permission('teachers.import') || has_permission('subjects.import')): ?><a href="<?= base_url('imports/master') ?>" class="btn btn-primary text-start rounded-3"><i data-lucide="file-up" class="me-2" style="width:17px"></i>Import Master Data</a><?php endif; ?>
            <?php if (has_permission('curriculum.view')): ?><a href="<?= base_url('curriculum') ?>" class="btn btn-outline-primary text-start rounded-3"><i data-lucide="table-2" class="me-2" style="width:17px"></i>Susun Struktur Kurikulum</a><?php endif; ?>
            <?php if (has_permission('assignments.view')): ?><a href="<?= base_url('assignments') ?>" class="btn btn-outline-primary text-start rounded-3"><i data-lucide="briefcase" class="me-2" style="width:17px"></i>Bagi Beban Mengajar & Cetak SK</a><?php endif; ?>
            <?php if (has_permission('schedules.view')): ?><a href="<?= base_url('schedules') ?>" class="btn btn-outline-primary text-start rounded-3"><i data-lucide="calendar-days" class="me-2" style="width:17px"></i>Susun Jadwal Otomatis</a><?php endif; ?>
        </div></div></div></div>
    </div>

    <div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-start mb-3"><div><h5 class="fw-bold mb-1">Aktivitas Terbaru</h5><p class="text-muted fs-8 mb-0">Jejak perubahan terbaru pada sistem.</p></div></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Pengguna</th><th>Modul</th><th>Aksi</th><th>Catatan</th><th>Waktu</th></tr></thead><tbody><?php if (!$recentAudits): ?><tr><td colspan="5" class="text-center py-5 text-muted">Belum ada aktivitas tercatat.</td></tr><?php else: foreach ($recentAudits as $log): ?><tr><td class="fw-semibold"><?= esc($log['username'] ?? 'Sistem') ?></td><td><span class="badge bg-light text-slate-600"><?= esc($log['module']) ?></span></td><td><?= esc(strtoupper($log['action'])) ?></td><td class="text-muted"><?= esc($log['reason'] ?: '-') ?></td><td class="text-nowrap text-muted fs-8"><?= date('d-m-Y H:i', strtotime($log['created_at'])) ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div></div>
    <?php endif; ?>
</section>
<script>document.addEventListener('DOMContentLoaded',function(){if(typeof lucide!=='undefined')lucide.createIcons();});</script>
<?= $this->endSection() ?>
