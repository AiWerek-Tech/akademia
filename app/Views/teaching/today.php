<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <!-- Header Page -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-purple-subtle text-purple px-2.5 py-1.5 rounded-pill fw-semibold text-xs text-uppercase tracking-wider">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Ruang Mengajar
                </span>
                <span class="badge bg-light text-muted px-2 py-1 rounded-pill text-xs">
                    <?= esc(get_active_unit()['name'] ?? 'Unit Sekolah') ?>
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mt-2 mb-1">
                <?php
                $hour = (int) date('G');
                if ($hour < 11) $greeting = 'Selamat Pagi';
                elseif ($hour < 15) $greeting = 'Selamat Siang';
                else $greeting = 'Selamat Sore';
                ?>
                <?= $greeting ?>, <?= esc(explode(' ', $teacher['full_name'] ?? session()->get('username') ?? 'Guru')[0]) ?>
            </h1>
            <p class="text-muted mb-0">
                <?php if (!empty($workspace['lessons'])): ?>
                    Hari ini ada <strong><?= count($workspace['lessons']) ?> jadwal mengajar</strong>. Pilih kelas yang ingin dimulai atau lanjutkan sesi yang sedang berjalan.
                <?php else: ?>
                    Hari ini tidak ada jadwal mengajar. Anda bisa memeriksa jadwal lain atau menyiapkan RPP.
                <?php endif; ?>
            </p>
        </div>

        <!-- Date & Teacher Filter -->
        <div class="d-flex flex-wrap align-items-center gap-2">
            <form method="GET" action="<?= base_url('teaching/today') ?>" class="d-flex align-items-center gap-2">
                <?php if (!empty($teachersList)): ?>
                    <select name="teacher_id" class="form-select form-select-sm shadow-sm" onchange="this.form.submit()">
                        <?php foreach ($teachersList as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= ((int) $currentTeacherId === (int) $t['id']) ? 'selected' : '' ?>>
                                <?= esc($t['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <div class="input-group input-group-sm shadow-sm">
                    <span class="input-group-text bg-white"><i data-lucide="calendar" class="w-4 h-4 text-muted"></i></span>
                    <input type="date" name="date" class="form-control" value="<?= esc($date) ?>" onchange="this.form.submit()">
                </div>
                <a href="<?= base_url('teaching/today') ?>" class="btn btn-sm btn-outline-secondary shadow-sm" title="Kembali ke hari ini">
                    Hari Ini
                </a>
            </form>
        </div>
    </div>

    <!-- Alert / Attention Center — only show when there is something actionable -->
    <?php if ($workspace['stats']['pending_reflections'] > 0 || $workspace['stats']['follow_up_needed'] > 0): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden border-start border-warning border-4 bg-warning-subtle text-dark">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="p-2.5 bg-warning text-dark rounded-circle flex-shrink-0">
                        <i data-lucide="bell-ring" class="w-5 h-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="fw-bold mb-1 text-gray-900">Perlu Perhatian Anda</h6>
                        <div class="d-flex flex-wrap gap-3 mt-2 text-sm">
                            <?php if ($workspace['stats']['pending_reflections'] > 0): ?>
                                <span class="badge bg-white text-dark border shadow-xs py-1.5 px-2.5 rounded-pill">
                                    <i data-lucide="file-edit" class="w-3.5 h-3.5 text-warning me-1 d-inline-block"></i>
                                    <strong><?= $workspace['stats']['pending_reflections'] ?></strong> kelas sudah selesai tapi belum diisi refleksinya
                                </span>
                            <?php endif; ?>
                            <?php if ($workspace['stats']['follow_up_needed'] > 0): ?>
                                <span class="badge bg-white text-dark border shadow-xs py-1.5 px-2.5 rounded-pill">
                                    <i data-lucide="user-x" class="w-3.5 h-3.5 text-danger me-1 d-inline-block"></i>
                                    <strong><?= $workspace['stats']['follow_up_needed'] ?></strong> catatan siswa perlu tindak lanjut
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-muted mt-2 mb-0">
                            <i data-lucide="info" class="w-3 h-3 me-1 d-inline-block"></i>
                            Isi refleksi setelah kelas selesai agar catatan pengajaran Anda tercatat dengan lengkap.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Summary Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-xs fw-semibold text-muted text-uppercase tracking-wider">Jadwal Hari Ini</div>
                        <div class="h3 fw-bold text-gray-900 mb-0 mt-1"><?= $workspace['stats']['total_lessons'] ?> Kelas</div>
                    </div>
                    <div class="p-3 bg-purple-subtle text-purple rounded-3">
                        <i data-lucide="calendar-days" class="w-5 h-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-xs fw-semibold text-muted text-uppercase tracking-wider">Sedang Berlangsung</div>
                        <div class="h3 fw-bold text-primary mb-0 mt-1"><?= $workspace['stats']['in_progress_count'] ?> Sesi</div>
                    </div>
                    <div class="p-3 bg-primary-subtle text-primary rounded-3">
                        <i data-lucide="play-circle" class="w-5 h-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-xs fw-semibold text-muted text-uppercase tracking-wider">Selesai Hari Ini</div>
                        <div class="h3 fw-bold text-success mb-0 mt-1"><?= $workspace['stats']['completed_count'] ?> Sesi</div>
                    </div>
                    <div class="p-3 bg-success-subtle text-success rounded-3">
                        <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-xs fw-semibold text-muted text-uppercase tracking-wider">Perlu Refleksi</div>
                        <div class="h3 fw-bold text-warning mb-0 mt-1"><?= $workspace['stats']['pending_reflections'] ?> Sesi</div>
                    </div>
                    <div class="p-3 bg-warning-subtle text-warning rounded-3">
                        <i data-lucide="pencil-line" class="w-5 h-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content: Schedule Timeline -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-bold text-gray-900 mb-0">Jadwal Mengajar Hari Ini</h5>
                        <p class="text-xs text-muted mb-0"><?= date('l, d F Y', strtotime($date)) ?></p>
                    </div>
                    <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill text-xs">
                        <?= count($workspace['lessons']) ?> Jadwal
                    </span>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($workspace['lessons'])): ?>
                        <div class="text-center py-5">
                            <div class="p-4 bg-light rounded-circle d-inline-block text-muted mb-3">
                                <i data-lucide="calendar-x-2" class="w-8 h-8"></i>
                            </div>
                            <h6 class="fw-bold text-gray-800">Tidak Ada Jadwal Hari Ini</h6>
                            <p class="text-muted text-sm mb-0">
                                <?php if (!empty($teacher['full_name'])): ?>
                                    <strong><?= esc($teacher['full_name']) ?></strong> tidak memiliki kelas pada hari ini. Coba pilih tanggal lain atau periksa jadwal mengajar.
                                <?php else: ?>
                                    Silakan pilih nama guru dari dropdown di atas untuk melihat jadwal mengajar.
                                <?php endif; ?>
                            </p>
                            <a href="<?= base_url('portal/schedule') ?>" class="btn btn-sm btn-outline-primary rounded-pill mt-3">
                                <i data-lucide="calendar" class="w-4 h-4 me-1 d-inline-block"></i> Lihat Jadwal Lengkap
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="timeline-list d-flex flex-column gap-3">
                            <?php foreach ($workspace['lessons'] as $idx => $lesson): ?>
                                <?php
                                $session = $lesson['learning_session'] ?? null;
                                $status = $session ? $session['status'] : 'PLANNED';
                                $isCompleted = in_array($status, ['COMPLETED', 'REFLECTED']);
                                $isInProgress = $status === 'IN_PROGRESS';
                                $isReflected = $status === 'REFLECTED';
                                ?>
                                <div class="card border <?= $isInProgress ? 'border-primary shadow-md ring-2 ring-primary-subtle' : ($isCompleted ? 'border-success-subtle bg-light-subtle' : 'border-gray-200') ?> rounded-4 overflow-hidden transition-all">
                                    <div class="card-body p-3 p-md-4">
                                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                                            <div class="d-flex align-items-start gap-3">
                                                <!-- Time Badge -->
                                                <div class="text-center p-2.5 <?= $isInProgress ? 'bg-primary text-white' : ($isCompleted ? 'bg-success-subtle text-success' : 'bg-light text-muted') ?> rounded-3 flex-shrink-0" style="min-width: 80px;">
                                                    <div class="fw-bold text-sm">
                                                        <?= $lesson['start_time'] ? substr($lesson['start_time'], 0, 5) : '--:--' ?>
                                                    </div>
                                                    <div class="text-xs opacity-75">
                                                        <?= $lesson['end_time'] ? substr($lesson['end_time'], 0, 5) : '--:--' ?>
                                                    </div>
                                                    <span class="badge <?= $isInProgress ? 'bg-white text-primary' : 'bg-secondary-subtle text-dark' ?> text-2xs mt-1 px-1.5 py-0.5">
                                                        <?= esc($lesson['jp_count'] ?? 2) ?> JP
                                                    </span>
                                                </div>

                                                <!-- Subject & Class Info -->
                                                <div>
                                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                        <span class="badge bg-purple-subtle text-purple px-2 py-0.5 rounded text-xs fw-bold">
                                                            <?= esc($lesson['classroom_name']) ?>
                                                        </span>
                                                        <?php if ($isReflected): ?>
                                                            <span class="badge bg-success text-white px-2 py-0.5 rounded-pill text-xs fw-semibold">
                                                                <i data-lucide="check-check" class="w-3 h-3 me-1 d-inline-block"></i> Selesai & Terrefleksi
                                                            </span>
                                                        <?php elseif ($isInProgress): ?>
                                                            <span class="badge bg-primary text-white px-2 py-0.5 rounded-pill text-xs fw-semibold animate-pulse">
                                                                <i data-lucide="play" class="w-3 h-3 me-1 d-inline-block"></i> Sedang Berlangsung
                                                            </span>
                                                        <?php elseif ($status === 'COMPLETED'): ?>
                                                            <span class="badge bg-warning text-dark px-2 py-0.5 rounded-pill text-xs fw-semibold">
                                                                <i data-lucide="check" class="w-3 h-3 me-1 d-inline-block"></i> Perlu Refleksi
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-muted border px-2 py-0.5 rounded-pill text-xs">
                                                                Belum Dimulai
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <h6 class="fw-bold text-gray-900 mb-1">
                                                        <?= esc($lesson['subject_name']) ?>
                                                    </h6>

                                                    <!-- Lesson Plan Link or Selector -->
                                                    <?php
                                                    $subjectPlans = $availablePlans[(int) $lesson['subject_id']] ?? [];
                                                    $linkedPlanUuid = $session['lesson_plan_uuid'] ?? null;
                                                    ?>
                                                    <?php if ($linkedPlanUuid): ?>
                                                        <div class="d-flex align-items-center gap-1.5 mt-1">
                                                            <i data-lucide="book-open" class="w-3.5 h-3.5 text-purple"></i>
                                                            <span class="text-xs text-muted">RPP: <strong><?= esc($session['topic'] ?: 'Rencana Terhubung') ?></strong></span>
                                                            <a href="<?= base_url('lesson-plans/' . $linkedPlanUuid) ?>" target="_blank" class="text-xs text-purple ms-1" title="Lihat RPP"><i data-lucide="external-link" class="w-3 h-3"></i></a>
                                                        </div>
                                                    <?php elseif ($subjectPlans !== []): ?>
                                                        <div class="d-flex align-items-center gap-1.5 mt-1 text-xs text-muted">
                                                            <i data-lucide="book-open-check" class="w-3.5 h-3.5 text-purple"></i>
                                                            <span>RPP belum terhubung. Pilih RPP saat menyiapkan kelas di bawah.</span>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="text-xs text-muted d-flex align-items-center gap-1.5 mt-1">
                                                            <i data-lucide="info" class="w-3.5 h-3.5 text-secondary"></i>
                                                            <span>Tanpa RPP — gunakan alur 3D Deep Learning bawaan</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Actions -->
                                            <div class="d-flex flex-wrap align-items-center gap-2 align-self-end align-self-md-center">
                                                <?php if ($session): ?>
                                                    <?php if ($status === 'PLANNED'): ?>
                                                        <form method="POST" action="<?= base_url('teaching/session/' . $session['uuid'] . '/start') ?>">
                                                            <?= csrf_field() ?>
                                                            <button type="submit" class="btn btn-sm btn-primary px-3 shadow-sm rounded-pill fw-semibold">
                                                                <i data-lucide="play" class="w-4 h-4 me-1 d-inline-block"></i> Mulai Kelas
                                                            </button>
                                                        </form>
                                                    <?php elseif ($isInProgress): ?>
                                                        <a href="<?= base_url('teaching/session/' . $session['uuid']) ?>" class="btn btn-sm btn-primary px-3 shadow-sm rounded-pill fw-semibold">
                                                            <i data-lucide="monitor" class="w-4 h-4 me-1 d-inline-block"></i> Masuk Kelas
                                                        </a>
                                                    <?php elseif ($status === 'COMPLETED'): ?>
                                                        <a href="<?= base_url('teaching/session/' . $session['uuid'] . '/reflect') ?>" class="btn btn-sm btn-warning text-dark px-3 shadow-sm rounded-pill fw-semibold">
                                                            <i data-lucide="file-edit" class="w-4 h-4 me-1 d-inline-block"></i> Isi Refleksi
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="<?= base_url('teaching/session/' . $session['uuid']) ?>" class="btn btn-sm btn-outline-secondary px-3 rounded-pill">
                                                            <i data-lucide="eye" class="w-4 h-4 me-1 d-inline-block"></i> Lihat Detail
                                                        </a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <!-- Init session button -->
                                                    <form method="POST" action="<?= base_url('teaching/session/init') ?>">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="teacher_id" value="<?= esc($currentTeacherId) ?>">
                                                        <input type="hidden" name="classroom_id" value="<?= esc($lesson['classroom_id']) ?>">
                                                        <input type="hidden" name="subject_id" value="<?= esc($lesson['subject_id']) ?>">
                                                        <input type="hidden" name="schedule_entry_id" value="<?= esc($lesson['schedule_entry_id'] ?? '') ?>">
                                                        <input type="hidden" name="session_date" value="<?= esc($date) ?>">
                                                        <input type="hidden" name="start_time" value="<?= esc($lesson['start_time'] ?? '') ?>">
                                                        <input type="hidden" name="end_time" value="<?= esc($lesson['end_time'] ?? '') ?>">
                                                        <input type="hidden" name="jp_count" value="<?= esc($lesson['jp_count'] ?? 2) ?>">
                                                        <?php if (!empty($subjectPlans)): ?>
                                                        <div class="d-flex align-items-center gap-1.5 mb-2">
                                                            <i data-lucide="book-open-check" class="w-3.5 h-3.5 text-purple"></i>
                                                            <select name="lesson_plan_id" class="form-select form-select-xs border-purple text-purple rounded-pill" style="max-width: 200px; font-size: 0.7rem; padding: 0.15rem 0.5rem;">
                                                                <option value="">Tanpa RPP</option>
                                                                <?php foreach ($subjectPlans as $sp): ?>
                                                                    <option value="<?= (int) $sp['id'] ?>"><?= esc(($sp['session_number'] ? $sp['session_number'] . '. ' : '') . ($sp['title'] ?: $sp['topic'] ?: 'Pertemuan ' . ($sp['session_number'] ?? ''))) ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <?php endif; ?>
                                                        <button type="submit" class="btn btn-sm btn-outline-primary px-3 shadow-xs rounded-pill fw-semibold">
                                                            <i data-lucide="sparkles" class="w-4 h-4 me-1 d-inline-block"></i> Siapkan Kelas
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar Widgets -->
        <div class="col-lg-4">
            <!-- Quick Guide -->
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                    <h6 class="fw-bold text-gray-900 mb-0">Alur Kerja Hari Ini</h6>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-2 bg-success-subtle text-success rounded-circle flex-shrink-0" style="width:2rem; height:2rem; display:flex; align-items:center; justify-content:center;">
                                <span class="fw-bold text-xs">1</span>
                            </div>
                            <div>
                                <div class="fw-semibold text-xs text-gray-900">Siapkan Kelas</div>
                                <div class="text-2xs text-muted">Klik "Siapkan Kelas" pada jadwal untuk membuat sesi pembelajaran</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-2 bg-primary-subtle text-primary rounded-circle flex-shrink-0" style="width:2rem; height:2rem; display:flex; align-items:center; justify-content:center;">
                                <span class="fw-bold text-xs">2</span>
                            </div>
                            <div>
                                <div class="fw-semibold text-xs text-gray-900">Mulai Mengajar</div>
                                <div class="text-2xs text-muted">Gunakan Teaching Mode untuk presensi, checklist aktivitas, dan catatan observasi</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-2 bg-warning-subtle text-warning rounded-circle flex-shrink-0" style="width:2rem; height:2rem; display:flex; align-items:center; justify-content:center;">
                                <span class="fw-bold text-xs">3</span>
                            </div>
                            <div>
                                <div class="fw-semibold text-xs text-gray-900">Selesaikan & Refleksi</div>
                                <div class="text-2xs text-muted">Setelah kelas selesai, isi jurnal refleksi untuk melengkapi catatan hari itu</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Routines & Assembly Cards -->
            <?php if (!empty($workspace['morning']) || !empty($workspace['afternoon'])): ?>
                <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                        <h6 class="fw-bold text-gray-900 mb-0">Presensi Wajib</h6>
                        <span class="text-2xs text-muted">Apel pagi, siang, atau kelas binaan</span>
                    </div>
                    <div class="card-body p-4 d-flex flex-column gap-2">
                        <?php foreach (array_merge($workspace['morning'], $workspace['classroom'], $workspace['afternoon']) as $routine): ?>
                            <div class="d-flex align-items-center justify-content-between p-2.5 bg-light rounded-3">
                                <div>
                                    <div class="fw-semibold text-xs text-gray-900"><?= esc($routine['subject_name']) ?></div>
                                    <div class="text-2xs text-muted"><?= esc($routine['classroom_name']) ?></div>
                                </div>
                                <a href="<?= base_url('teaching/today?date=' . urlencode($date)) ?>" class="btn btn-xs btn-outline-secondary rounded-pill">
                                    Isi
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Recent Misconceptions Radar -->
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold text-gray-900 mb-0">Miskonsepsi Siswa</h6>
                    <span class="badge bg-warning-subtle text-warning px-2 py-0.5 rounded-pill text-2xs">Pedagogi</span>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($workspace['recent_misconceptions'])): ?>
                        <div class="text-center py-4 text-muted">
                            <i data-lucide="sparkles" class="w-6 h-6 text-muted mb-2 d-inline-block"></i>
                            <p class="text-xs mb-0">Belum ada miskonsepsi tercatat. Data ini terisi otomatis saat Anda mencatat observasi di Teaching Mode.</p>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2.5">
                            <?php foreach ($workspace['recent_misconceptions'] as $m): ?>
                                <div class="p-3 border rounded-3 bg-light-subtle">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="fw-bold text-xs text-gray-900"><?= esc($m['student_name'] ?: 'Siswa') ?></span>
                                        <span class="text-2xs text-muted"><?= date('d M', strtotime($m['session_date'])) ?></span>
                                    </div>
                                    <p class="text-xs text-danger mb-0">
                                        <i data-lucide="alert-triangle" class="w-3 h-3 me-1 d-inline-block"></i>
                                        <?= esc($m['misconception_detail'] ?: $m['notes']) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
