<?php
$hour = (int) date('G');
$greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
$dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
$monthNames = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
$todayLabel = $dayNames[(int)date('w')] . ', ' . date('j') . ' ' . $monthNames[(int)date('n')];
$displayName = active_user_name();
$firstName = explode(' ', trim($displayName))[0] ?: $displayName;
$initials = '';
foreach (array_slice(preg_split('/\s+/', trim($displayName)) ?: [], 0, 2) as $part) $initials .= mb_strtoupper(mb_substr($part, 0, 1));

$focus = ['eyebrow'=>'Ruang kerja Anda','title'=>'Semua pekerjaan akademik dalam satu aplikasi','copy'=>'Buka fitur yang paling sering Anda gunakan.','href'=>'#mobileQuickActions','action'=>'Lihat fitur','icon'=>'sparkles'];
$stats = [];
$actions = [];
$addAction = static function (array &$items, string $permission, string $href, string $icon, string $label, string $tone): void {
    if ($permission === '' || has_permission($permission)) $items[] = compact('href','icon','label','tone');
};

if ($mode === 'teacher') {
    $next = $workspace['today_entries'][0] ?? null;
    $focus = $next
        ? ['eyebrow'=>'Kelas berikutnya','title'=>$next['subject_name'],'copy'=>($next['classroom_name'] ?? '-') . ' · JP ' . (int)$next['slot_number'],'href'=>'portal/schedule','action'=>'Buka jadwal','icon'=>'calendar-clock']
        : ['eyebrow'=>'Agenda hari ini','title'=>'Jadwal Anda terkendali','copy'=>'Tidak ada kelas lain yang perlu ditampilkan saat ini.','href'=>'portal/schedule','action'=>'Lihat jadwal','icon'=>'circle-check-big'];
    $stats = [
        ['label'=>'JP/minggu','value'=>number_format((float)$workspace['teaching_hours'], fmod((float)$workspace['teaching_hours'],1.0)===0.0?0:1),'icon'=>'clock','tone'=>'purple'],
        ['label'=>'Mapel','value'=>(int)$workspace['subject_count'],'icon'=>'book-open','tone'=>'mint'],
        ['label'=>'Rombel','value'=>(int)$workspace['classroom_count'],'icon'=>'school','tone'=>'blue'],
        ['label'=>'Hari piket','value'=>(int)$workspace['duty_days'],'icon'=>'shield-check','tone'=>'amber'],
    ];
    if (is_wali_kelas()) {
        $addAction($actions,'class_students.view','portal/classroom','users-round','Kelas Saya','mint');
        $addAction($actions,'class_electives.manage','electives','list-checks','Pilihan Kelas','coral');
    }
    $addAction($actions,'teacher_schedule.view','portal/schedule','calendar-days','Jadwal','purple');
    $addAction($actions,'teacher_attendance.view','portal/attendance','clipboard-check','Absensi','blue');
    $addAction($actions,'teacher_electives.view','portal/electives','users-round','Mapel Pilihan','coral');
    $addAction($actions,'teacher_workload.view','portal/workload','bar-chart-3','Beban Saya','amber');
} elseif (in_array($mode, ['administration','academic'], true)) {
    $focus = ['eyebrow'=>'Kesiapan akademik','title'=>$readinessDone . ' dari ' . count($readiness) . ' fondasi siap','copy'=>'Lanjutkan data yang belum lengkap sebelum jadwal diterbitkan.','href'=>'#mobileReadiness','action'=>'Periksa kesiapan','icon'=>'gauge'];
    $stats = [
        ['label'=>'Unit','value'=>$totalUnits,'icon'=>'building-2','tone'=>'purple'],
        ['label'=>'Pengguna','value'=>$totalUsers,'icon'=>'users','tone'=>'blue'],
        ['label'=>'Guru','value'=>$masterCounts['teachers'],'icon'=>'user-check','tone'=>'mint'],
        ['label'=>'Rombel','value'=>$masterCounts['classrooms'],'icon'=>'school','tone'=>'amber'],
    ];
    $addAction($actions,'teachers.view','teachers','users','Data Guru','purple');
    $addAction($actions,'curriculum.view','curriculum','table-2','Kurikulum','mint');
    $addAction($actions,'assignments.view','assignments','briefcase','Pembagian Tugas','coral');
    $addAction($actions,'schedules.view','schedules','calendar-days','Jadwal','blue');
    $addAction($actions,'users.view','users','user-cog','Akun User','amber');
} elseif ($mode === 'executive') {
    $focus = ['eyebrow'=>'Monitoring sekolah','title'=>'Ringkasan akademik terbaru','copy'=>'Pantau dokumen resmi dan kesiapan jadwal dalam satu tampilan.','href'=>'schedules','action'=>'Buka monitoring','icon'=>'line-chart'];
    $stats = [
        ['label'=>'SK resmi','value'=>$workspace['executive']['approved_assignments'],'icon'=>'file-check-2','tone'=>'mint'],
        ['label'=>'Jadwal','value'=>$workspace['executive']['published_schedules'],'icon'=>'calendar-check','tone'=>'purple'],
        ['label'=>'Konflik','value'=>$workspace['executive']['open_conflicts'],'icon'=>'triangle-alert','tone'=>'coral'],
    ];
    $addAction($actions,'assignments.view','assignments','file-check-2','SK Tugas','purple');
    $addAction($actions,'workloads.view','workloads','bar-chart-3','Beban Guru','amber');
    $addAction($actions,'schedules.view','schedules','calendar-days','Jadwal Resmi','blue');
    $addAction($actions,'teachers.view','teachers','users','Daftar Guru','mint');
} elseif ($mode === 'operations') {
    $focus = ['eyebrow'=>'Layanan sekolah','title'=>'Administrasi lebih ringkas','copy'=>'Kelola siswa, rombel, guru, dan akun sesuai akses Anda.','href'=>'students','action'=>'Buka layanan','icon'=>'folders'];
    $stats = [
        ['label'=>'Unit','value'=>$totalUnits,'icon'=>'building-2','tone'=>'purple'],
        ['label'=>'Pengguna','value'=>$totalUsers,'icon'=>'users','tone'=>'blue'],
        ['label'=>'Guru','value'=>$masterCounts['teachers'],'icon'=>'contact','tone'=>'mint'],
        ['label'=>'Rombel','value'=>$masterCounts['classrooms'],'icon'=>'school','tone'=>'amber'],
    ];
    $addAction($actions,'students.view','students','graduation-cap','Peserta Didik','purple');
    $addAction($actions,'classrooms.view','classrooms','school','Kelas & Rombel','blue');
    $addAction($actions,'teachers.view','teachers','contact','Data Guru','mint');
    $addAction($actions,'users.view','users','user-cog','Akun Pengguna','amber');
} elseif ($mode === 'student') {
    $focus = ['eyebrow'=>'Portal peserta didik','title'=>'Pilihan belajar Anda','copy'=>($workspace['student']['classroom_name'] ?? 'Rombel belum ditautkan') . ' · ' . $activePeriodStr,'href'=>'my-electives','action'=>'Buka pilihan','icon'=>'graduation-cap'];
    $addAction($actions,'electives.selection.submit','my-electives','list-checks','Pilihan Mapel','purple');
}
if (has_permission('regulations.view') || has_permission('learning_outcomes.view') || has_permission('learning_objectives.view') || has_permission('learning_sequences.view') || has_permission('learning_packs.view') || has_permission('ksp.view')) {
    array_unshift($actions, ['href'=>'education','icon'=>'network','label'=>'IALOS Education','tone'=>'purple']);
}
$actions = array_slice($actions, 0, 6);
?>
<section class="native-mobile-dashboard" aria-labelledby="mobile-dashboard-title">
    <header class="native-home-header">
        <div>
            <span class="native-home-date"><?= esc($todayLabel) ?></span>
            <p><?= esc($greeting) ?>,</p>
            <h1 id="mobile-dashboard-title"><?= esc($firstName) ?>!</h1>
        </div>
        <button type="button" class="native-profile-button" data-mobile-menu aria-label="Buka profil dan menu">
            <span><?= esc($initials ?: 'WA') ?></span><i data-lucide="menu"></i>
        </button>
    </header>

    <button type="button" class="native-search-trigger" data-mobile-menu>
        <i data-lucide="search"></i><span>Cari menu atau fitur...</span><i data-lucide="sliders-horizontal"></i>
    </button>

    <article class="native-focus-card">
        <div class="native-focus-orb"><i data-lucide="<?= esc($focus['icon'], 'attr') ?>"></i></div>
        <span><?= esc($focus['eyebrow']) ?></span>
        <h2><?= esc($focus['title']) ?></h2>
        <p><?= esc($focus['copy']) ?></p>
        <a href="<?= str_starts_with($focus['href'], '#') ? esc($focus['href']) : base_url($focus['href']) ?>"><?= esc($focus['action']) ?><i data-lucide="arrow-up-right"></i></a>
    </article>

    <?php if ($stats): ?>
        <div class="native-section-heading"><div><span>Ringkasan</span><h2>Data Anda</h2></div><span class="native-unit-pill"><?= esc($unitScope['label'] ?? 'Unit aktif') ?></span></div>
        <div class="native-stat-strip">
            <?php foreach ($stats as $stat): ?><article class="native-stat-tile tone-<?= esc($stat['tone']) ?>"><i data-lucide="<?= esc($stat['icon'], 'attr') ?>"></i><strong><?= esc($stat['value']) ?></strong><span><?= esc($stat['label']) ?></span></article><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="native-section-heading" id="mobileQuickActions"><div><span>Akses cepat</span><h2>Fitur utama</h2></div><button type="button" data-mobile-menu>Semua</button></div>
    <div class="native-action-grid">
        <?php foreach ($actions as $action): ?><a class="native-action-card tone-<?= esc($action['tone']) ?>" href="<?= base_url($action['href']) ?>"><span><i data-lucide="<?= esc($action['icon'], 'attr') ?>"></i></span><strong><?= esc($action['label']) ?></strong><i data-lucide="arrow-up-right"></i></a><?php endforeach; ?>
    </div>

    <?php if ($mode === 'teacher'): ?>
        <div class="native-section-heading"><div><span>Hari ini</span><h2>Agenda mengajar</h2></div><a href="<?= base_url('portal/schedule') ?>">Lihat semua</a></div>
        <div class="native-list-card">
            <?php if (empty($workspace['today_entries'])): ?><div class="native-empty"><i data-lucide="calendar-check"></i><div><strong><?= !empty($workspace['calendar_notice']) ? 'Hari non-efektif' : 'Tidak ada kelas hari ini' ?></strong><span><?= !empty($workspace['calendar_notice']) ? esc($workspace['calendar_notice']) : 'Gunakan waktu untuk persiapan mengajar.' ?></span></div></div><?php else: ?>
                <?php foreach (array_slice($workspace['today_entries'],0,4) as $entry): ?><a href="<?= base_url('portal/schedule') ?>" class="native-list-row"><span class="native-list-icon tone-purple"><i data-lucide="book-open"></i></span><div><strong><?= esc($entry['subject_name']) ?></strong><span><?= esc($entry['classroom_name']) ?><?= !empty($entry['room_name']) ? ' · '.esc($entry['room_name']) : '' ?></span></div><b>JP <?= (int)$entry['slot_number'] ?></b><i data-lucide="chevron-right"></i></a><?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="native-section-heading"><div><span>Dokumen</span><h2>Tugas saya</h2></div></div>
        <div class="native-list-card">
            <?php if (has_permission('teacher_assignment_document.view')): ?><a href="<?= base_url('portal/assignment-document') ?>" class="native-list-row"><span class="native-list-icon tone-coral"><i data-lucide="file-signature"></i></span><div><strong>SK Pembagian Tugas</strong><span><?= $workspace['assignment_document_available'] ? ($workspace['assignment_document_official']?'Dokumen resmi tersedia':'Pratinjau tersedia') : 'Belum tersedia' ?></span></div><i data-lucide="chevron-right"></i></a><?php endif; ?>
            <?php if (has_permission('teacher_electives.view')): ?><a href="<?= base_url('portal/electives') ?>" class="native-list-row"><span class="native-list-icon tone-mint"><i data-lucide="users-round"></i></span><div><strong>Mapel Pilihan</strong><span><?= (int)$workspace['elective_student_count'] ?> siswa memilih mapel Anda</span></div><i data-lucide="chevron-right"></i></a><?php endif; ?>
            <?php if (has_permission('teacher_duty_schedule.view')): ?><a href="<?= base_url('portal/duty-schedule') ?>" class="native-list-row"><span class="native-list-icon tone-amber"><i data-lucide="shield-check"></i></span><div><strong>Jadwal Piket</strong><span><?= (int)$workspace['duty_days'] ?> hari piket</span></div><i data-lucide="chevron-right"></i></a><?php endif; ?>
        </div>
    <?php elseif (in_array($mode,['administration','academic'],true)): ?>
        <div class="native-section-heading" id="mobileReadiness"><div><span>Perencanaan</span><h2>Kesiapan sistem</h2></div><span class="native-unit-pill"><?= $readinessDone ?>/<?= count($readiness) ?></span></div>
        <div class="native-list-card"><?php foreach (array_slice($readiness,0,6) as $item): ?><a href="<?= base_url($item['href']) ?>" class="native-list-row"><span class="native-list-icon <?= $item['ready']?'tone-mint':'tone-amber' ?>"><i data-lucide="<?= $item['ready']?'check':'clock-3' ?>"></i></span><div><strong><?= esc($item['label']) ?></strong><span><?= $item['ready']?'Siap digunakan':'Perlu dilengkapi' ?></span></div><i data-lucide="chevron-right"></i></a><?php endforeach; ?></div>
    <?php endif; ?>
</section>
