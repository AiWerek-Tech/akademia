<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-purple-subtle text-purple px-2.5 py-1.5 rounded-pill fw-semibold text-xs text-uppercase tracking-wider">
                    <i data-lucide="edit-3" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Input Manual
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mt-2 mb-1">Input Absensi Offline</h1>
            <p class="text-muted mb-0">Untuk guru yang mengajar tanpa membuka Teaching Mode. Isi presensi setelah kelas selesai.</p>
        </div>
        <div>
            <a href="<?= base_url('teaching/attendance/history') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <i data-lucide="history" class="w-4 h-4 me-1 d-inline-block"></i> Lihat Riwayat
            </a>
        </div>
    </div>

    <!-- Info Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-primary-subtle border-start border-primary border-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex align-items-start gap-3">
                <div class="p-2 bg-primary text-white rounded-circle flex-shrink-0">
                    <i data-lucide="info" class="w-5 h-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1 text-gray-900">Cara Menggunakan</h6>
                    <p class="text-xs text-muted mb-0">
                        Pilih kelas dan mata pelajaran, tentukan tanggal serta nomor pertemuan, lalu tandai kehadiran setiap siswa.
                        Data ini akan langsung tersimpan dan bisa dilihat di Riwayat Absensi dan Rekapan.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="<?= base_url('teaching/attendance/offline/save') ?>" id="offlineForm">
        <?= csrf_field() ?>

        <!-- Class & Subject Selection -->
        <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                <h5 class="fw-bold text-gray-900 mb-0">1. Pilih Kelas & Mata Pelajaran</h5>
                <p class="text-xs text-muted mb-0">Tentukan kelas mana yang ingin Anda input presensinya</p>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label text-xs fw-semibold text-gray-900">Kelas / Rombel <span class="text-danger">*</span></label>
                        <select name="classroom_id" class="form-select" id="classroomSelect" required>
                            <option value="">— Pilih Kelas —</option>
                            <?php
                            $allClasses = array_merge($homerooms, $teachingAssignments);
                            // Deduplicate by id
                            $seen = [];
                            foreach ($allClasses as $cls) {
                                $id = (int) ($cls['classroom_id'] ?? $cls['id']);
                                if ($id > 0 && !isset($seen[$id])) {
                                    $seen[$id] = true;
                                    $name = $cls['classroom_name'] ?? $cls['name'] ?? 'Kelas';
                                    $unit = $cls['unit_name'] ?? '';
                                    ?>
                                    <option value="<?= $id ?>" <?= $classroomId == $id ? 'selected' : '' ?>>
                                        <?= esc($name) ?><?= $unit ? ' (' . esc($unit) . ')' : '' ?>
                                    </option>
                                    <?php
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-xs fw-semibold text-gray-900">Mata Pelajaran <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-select" id="subjectSelect" required>
                            <option value="">— Pilih Mapel —</option>
                            <?php
                            $seenSubjects = [];
                            foreach ($teachingAssignments as $ta) {
                                $sid = (int) ($ta['subject_id'] ?? 0);
                                $sname = $ta['subject_name'] ?? '';
                                if ($sid > 0 && $sname && !isset($seenSubjects[$sid])) {
                                    $seenSubjects[$sid] = true;
                                    ?>
                                    <option value="<?= $sid ?>"><?= esc($sname) ?></option>
                                    <?php
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-xs fw-semibold text-gray-900">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="attendance_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-xs fw-semibold text-gray-900">Pertemuan #</label>
                        <input type="number" name="meeting_number" class="form-control" value="1" min="1" max="40">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label class="form-label text-xs fw-semibold text-gray-900">Topik / Kegiatan (Opsional)</label>
                        <input type="text" name="topic" class="form-control" placeholder="Contoh: Persamaan Linear Dua Variabel">
                    </div>
                </div>
            </div>
        </div>

        <!-- Roster -->
        <?php if (!empty($roster)): ?>
        <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="fw-bold text-gray-900 mb-0">2. Tandai Kehadiran Siswa</h5>
                    <p class="text-xs text-muted mb-0">Klik tombol status untuk setiap siswa. Default: Hadir (H).</p>
                </div>
                <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill text-xs">
                    <?= count($roster) ?> Siswa
                </span>
            </div>
            <div class="card-body p-4">
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($roster as $st): ?>
                        <div class="d-flex align-items-center justify-content-between p-2.5 bg-light-subtle border border-gray-100 rounded-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-2xs text-muted" style="min-width: 50px;"><?= esc($st['student_number'] ?? '') ?></span>
                                <span class="fw-semibold text-xs text-gray-900"><?= esc($st['full_name']) ?></span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="btn-group btn-group-xs roster-btn-group" data-student-id="<?= (int) $st['id'] ?>">
                                    <button type="button" class="btn btn-xs btn-success roster-btn active" data-status="HADIR">H</button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary roster-btn" data-status="TERLAMBAT">T</button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary roster-btn" data-status="IZIN">I</button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary roster-btn" data-status="SAKIT">S</button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary roster-btn" data-status="ALPA">A</button>
                                </div>
                                <input type="text" class="form-control form-control-xs text-2xs roster-notes" data-student-id="<?= (int) $st['id'] ?>" placeholder="Catatan" style="width: 100px; max-width: 100px;">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Hidden inputs populated by JS -->
                <div id="rosterHiddenInputs"></div>

                <div class="mt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-4 rounded-pill fw-semibold shadow-sm" id="btnSubmit">
                        <i data-lucide="save" class="w-4 h-4 me-1 d-inline-block"></i> Simpan Presensi
                    </button>
                </div>
            </div>
        </div>
        <?php elseif ($classroomId > 0): ?>
        <div class="card border-0 shadow-sm rounded-4 bg-warning-subtle mb-4">
            <div class="card-body text-center py-4">
                <i data-lucide="users" class="w-8 h-8 text-warning mb-2 d-inline-block"></i>
                <h6 class="fw-bold text-gray-800">Tidak Ada Siswa di Kelas Ini</h6>
                <p class="text-xs text-muted mb-0">Pastikan kelas sudah memiliki daftar siswa aktif sebelum menginput presensi.</p>
            </div>
        </div>
        <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
            <div class="card-body text-center py-5">
                <i data-lucide="clipboard-list" class="w-8 h-8 text-muted mb-2 d-inline-block"></i>
                <h6 class="fw-bold text-gray-800">Pilih Kelas Terlebih Dahulu</h6>
                <p class="text-xs text-muted mb-0">Pilih kelas dan mata pelajaran di atas, lalu daftar siswa akan muncul di sini.</p>
            </div>
        </div>
        <?php endif; ?>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Roster button groups
    document.querySelectorAll('.roster-btn-group').forEach(group => {
        group.querySelectorAll('.roster-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                group.querySelectorAll('.roster-btn').forEach(b => {
                    b.className = b.className.replace(/btn-(success|warning|danger|primary)/g, 'btn-outline-secondary');
                    b.classList.remove('active');
                });
                const status = this.getAttribute('data-status');
                if (status === 'HADIR') {
                    this.className = this.className.replace('btn-outline-secondary', 'btn-success');
                } else if (status === 'TERLAMBAT') {
                    this.className = this.className.replace('btn-outline-secondary', 'btn-warning');
                } else if (status === 'ALPA') {
                    this.className = this.className.replace('btn-outline-secondary', 'btn-danger');
                } else {
                    this.className = this.className.replace('btn-outline-secondary', 'btn-primary');
                }
                this.classList.add('active');
            });
        });
    });

    // Build hidden inputs on submit
    document.getElementById('offlineForm')?.addEventListener('submit', function(e) {
        const container = document.getElementById('rosterHiddenInputs');
        container.innerHTML = '';
        document.querySelectorAll('.roster-btn-group').forEach(group => {
            const studentId = group.getAttribute('data-student-id');
            const activeBtn = group.querySelector('.active');
            const status = activeBtn ? activeBtn.getAttribute('data-status') : 'HADIR';
            const notesInput = document.querySelector('.roster-notes[data-student-id="' + studentId + '"]');
            const notes = notesInput ? notesInput.value : '';

            container.innerHTML += '<input type="hidden" name="roster[' + studentId + '][status]" value="' + status + '">';
            container.innerHTML += '<input type="hidden" name="roster[' + studentId + '][notes]" value="' + notes.replace(/"/g, '&quot;') + '">';
        });
    });

    // Auto-reload when classroom changes
    document.getElementById('classroomSelect')?.addEventListener('change', function() {
        if (this.value) {
            window.location.href = '<?= base_url('teaching/attendance/offline') ?>?classroom_id=' + this.value;
        }
    });
});
</script>
<?= $this->endSection() ?>
