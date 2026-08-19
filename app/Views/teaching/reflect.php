<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-0 px-md-3">
    <!-- Header Page -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1.5">
                <a href="<?= base_url('teaching/session/' . $session['uuid']) ?>" class="btn btn-sm btn-outline-secondary rounded-pill py-0.5 px-2.5 text-xs">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Kembali ke Teaching Mode
                </a>
                <span class="badge bg-success-subtle text-success px-2.5 py-1 rounded-pill text-xs fw-bold">
                    <i data-lucide="check" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Sesi Kelas Tuntas
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mb-1">Jurnal Refleksi Pembelajaran</h1>
            <p class="text-muted text-sm mb-0">Refleksikan proses mengajar untuk menyempurnakan siklus pedagogis dan tindak lanjut siswa.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Reflection Form -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                    <h5 class="fw-bold text-gray-900 mb-0">Formulir Refleksi Guru</h5>
                    <p class="text-xs text-muted mb-0">Catat evaluasi mandiri setelah pembelajaran selesai</p>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="<?= base_url('teaching/session/' . $session['uuid'] . '/reflect') ?>">
                        <?= csrf_field() ?>

                        <!-- 1. What Went Well -->
                        <div class="mb-4">
                            <label class="form-label text-xs fw-bold text-gray-900 text-uppercase tracking-wider">
                                <i data-lucide="thumbs-up" class="w-4 h-4 text-success me-1 d-inline-block"></i>
                                1. Apa yang berjalan sangat baik hari ini?
                            </label>
                            <textarea name="what_went_well" class="form-control text-sm" rows="3" placeholder="Contoh: Siswa sangat aktif saat simulasi kelompok; analogi materi mudah dipahami..."><?= esc($reflection['what_went_well'] ?? '') ?></textarea>
                            <span class="text-2xs text-muted">Identifikasi praktik baik yang perlu dipertahankan untuk pertemuan berikutnya.</span>
                        </div>

                        <!-- 2. Challenges -->
                        <div class="mb-4">
                            <label class="form-label text-xs fw-bold text-gray-900 text-uppercase tracking-wider">
                                <i data-lucide="alert-circle" class="w-4 h-4 text-warning me-1 d-inline-block"></i>
                                2. Tantangan & Kendala di Dalam Kelas
                            </label>
                            <textarea name="challenges" class="form-control text-sm" rows="3" placeholder="Contoh: Beberapa siswa kesulitan pada sintaks dasar; waktu praktik terasa kurang 10 menit..."><?= esc($reflection['challenges'] ?? '') ?></textarea>
                            <span class="text-2xs text-muted">Catat kendala pemahaman, manajemen waktu, atau sarana digital di kelas.</span>
                        </div>

                        <!-- 3. Objective & Engagement Selectors -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-xs fw-bold text-gray-900 text-uppercase tracking-wider">
                                    <i data-lucide="target" class="w-4 h-4 text-primary me-1 d-inline-block"></i>
                                    Ketercapaian TP
                                </label>
                                <select name="objective_achievement" class="form-select text-sm">
                                    <option value="ACHIEVED" <?= (($reflection['objective_achievement'] ?? 'ACHIEVED') === 'ACHIEVED') ? 'selected' : '' ?>>
                                        Tercapai Penuh (Sesuai Target)
                                    </option>
                                    <option value="PARTIAL" <?= (($reflection['objective_achievement'] ?? '') === 'PARTIAL') ? 'selected' : '' ?>>
                                        Tercapai Sebagian (Perlu Penguatan)
                                    </option>
                                    <option value="NOT_ACHIEVED" <?= (($reflection['objective_achievement'] ?? '') === 'NOT_ACHIEVED') ? 'selected' : '' ?>>
                                        Belum Tercapai (Perlu Diulang)
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-xs fw-bold text-gray-900 text-uppercase tracking-wider">
                                    <i data-lucide="users" class="w-4 h-4 text-purple me-1 d-inline-block"></i>
                                    Keterlibatan Siswa
                                </label>
                                <select name="student_engagement" class="form-select text-sm">
                                    <option value="HIGH" <?= (($reflection['student_engagement'] ?? 'HIGH') === 'HIGH') ? 'selected' : '' ?>>
                                        Sangat Tinggi & Antusias
                                    </option>
                                    <option value="MODERATE" <?= (($reflection['student_engagement'] ?? '') === 'MODERATE') ? 'selected' : '' ?>>
                                        Cukup / Rata-rata
                                    </option>
                                    <option value="LOW" <?= (($reflection['student_engagement'] ?? '') === 'LOW') ? 'selected' : '' ?>>
                                        Pasif / Butuh Pemicu
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- 4. Follow up Plan -->
                        <div class="mb-4">
                            <label class="form-label text-xs fw-bold text-gray-900 text-uppercase tracking-wider">
                                <i data-lucide="repeat" class="w-4 h-4 text-info me-1 d-inline-block"></i>
                                3. Rencana Tindak Lanjut & Materi Pertemuan Berikutnya
                            </label>
                            <textarea name="follow_up_plan" class="form-control text-sm" rows="3" placeholder="Contoh: Berikan latihan pengayaan untuk kelompok A; review singkat konsep percabangan di awal pertemuan depan..."><?= esc($reflection['follow_up_plan'] ?? '') ?></textarea>
                        </div>

                        <!-- 4. TP Coverage Notes -->
                        <div class="mb-4">
                            <label class="form-label text-xs fw-bold text-gray-900 text-uppercase tracking-wider">
                                <i data-lucide="git-branch" class="w-4 h-4 text-purple me-1 d-inline-block"></i>
                                4. Catatan Ketercakupan Tujuan Pembelajaran (TP)
                            </label>
                            <textarea name="tp_coverage_notes" class="form-control text-sm" rows="2" placeholder="Contoh: TP 2.1 dan 2.2 tercakup; TP 2.3 ditunda karena waktu..."><?= esc($reflection['tp_coverage_notes'] ?? '') ?></textarea>
                        </div>

                        <!-- 5. Next Session Notes -->
                        <div class="mb-4">
                            <label class="form-label text-xs fw-bold text-gray-900 text-uppercase tracking-wider">
                                <i data-lucide="calendar-plus" class="w-4 h-4 text-info me-1 d-inline-block"></i>
                                5. Catatan Persiapan Pertemuan Berikutnya
                            </label>
                            <textarea name="next_session_notes" class="form-control text-sm" rows="2" placeholder="Contoh: Siapkan media kartu percobaan dan form pengamatan kelompok..."><?= esc($reflection['next_session_notes'] ?? '') ?></textarea>
                        </div>

                        <!-- 6. Self Rating -->
                        <div class="mb-4">
                            <label class="form-label text-xs fw-bold text-gray-900 text-uppercase tracking-wider">
                                <i data-lucide="star" class="w-4 h-4 text-warning me-1 d-inline-block"></i>
                                Penilaian Kepuasan Sesi (1 - 5 Bintang)
                            </label>
                            <div class="d-flex align-items-center gap-3">
                                <?php $currentRating = (int) ($reflection['self_rating'] ?? 5); ?>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="self_rating" id="rating_<?= $i ?>" value="<?= $i ?>" <?= ($currentRating === $i) ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold text-sm" for="rating_<?= $i ?>">
                                            <?= $i ?> <i data-lucide="star" class="w-3.5 h-3.5 text-warning fill-warning d-inline-block"></i>
                                        </label>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                            <a href="<?= base_url('teaching/today') ?>" class="btn btn-light rounded-pill px-4">
                                Batal
                            </a>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                                <i data-lucide="save" class="w-4 h-4 me-1.5 d-inline-block"></i> Simpan & Tuntaskan Sesi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar Summary Recap -->
        <div class="col-lg-4">
            <div class="d-flex flex-column gap-3">
                <!-- Session Snapshot Card -->
                <div class="card border-0 shadow-sm rounded-4 bg-white">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-1">
                        <h6 class="fw-bold text-gray-900 mb-0">Ringkasan Sesi Pembelajaran</h6>
                    </div>
                    <div class="card-body p-4 d-flex flex-column gap-2 text-xs">
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Mata Pelajaran</span>
                            <span class="fw-bold text-gray-900"><?= esc($session['subject_name']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Kelas / Rombel</span>
                            <span class="fw-bold text-gray-900"><?= esc($session['classroom_name']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Pertemuan</span>
                            <span class="fw-bold text-gray-900">#<?= esc($session['meeting_number']) ?> (<?= esc($session['jp_count']) ?> JP)</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Tanggal Pelaksanaan</span>
                            <span class="fw-bold text-gray-900"><?= date('d F Y', strtotime($session['session_date'])) ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Jam Aktual</span>
                            <span class="fw-bold text-gray-900"><?= esc($session['actual_start_time'] ?: '--:--') ?> - <?= esc($session['actual_end_time'] ?: '--:--') ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">Aktivitas Tuntas</span>
                            <span class="badge bg-success-subtle text-success fw-bold">
                                <?= count(array_filter($activities, fn($a) => $a['is_completed'] == 1)) ?> / <?= count($activities) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Observations Log Snapshot -->
                <div class="card border-0 shadow-sm rounded-4 bg-white">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-1 d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold text-gray-900 mb-0">Observasi Formatif Sesi Ini</h6>
                        <span class="badge bg-light text-muted text-2xs"><?= count($observations) ?></span>
                    </div>
                    <div class="card-body p-4">
                        <?php if (empty($observations)): ?>
                            <p class="text-xs text-muted mb-0">Tidak ada observasi khusus yang dicatat saat sesi kelas.</p>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($observations as $obs): ?>
                                    <div class="p-2.5 bg-light rounded-3 text-xs">
                                        <div class="d-flex justify-content-between mb-1">
                                            <strong class="text-gray-900"><?= esc($obs['student_name'] ?: 'Umum') ?></strong>
                                            <span class="badge bg-white text-dark text-2xs border"><?= esc($obs['rating']) ?></span>
                                        </div>
                                        <p class="text-muted mb-0 text-2xs"><?= esc($obs['notes']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
