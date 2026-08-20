<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-purple-subtle text-purple px-2.5 py-1.5 rounded-pill fw-semibold text-xs text-uppercase tracking-wider">
                    <i data-lucide="edit-3" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Edit Absensi
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mt-2 mb-1">Perbarui Data Absensi</h1>
            <p class="text-muted mb-0">
                Edit presensi siswa untuk kelas <strong><?= esc($session['classroom_name']) ?></strong> —
                <?= esc($session['subject_name']) ?>, pertemuan #<?= (int) $session['meeting_number'] ?>,
                tanggal <?= date('d/m/Y', strtotime($session['attendance_date'])) ?>.
            </p>
        </div>
        <div>
            <a href="<?= base_url('teaching/attendance/history') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <i data-lucide="arrow-left" class="w-4 h-4 me-1 d-inline-block"></i> Kembali ke Riwayat
            </a>
        </div>
    </div>

    <!-- Info -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-primary-subtle border-start border-primary border-4">
        <div class="card-body p-3">
            <div class="d-flex align-items-start gap-3">
                <div class="p-2 bg-primary text-white rounded-circle flex-shrink-0">
                    <i data-lucide="info" class="w-5 h-5"></i>
                </div>
                <div class="flex-grow-1">
                    <p class="text-xs text-muted mb-0">
                        Ubah status kehadiran siswa sesuai kebutuhan. Perubahan akan langsung tersimpan dan tercatat di rekapan.
                        Anda bebas mengedit kapan saja selama data belum diverifikasi oleh sekolah.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="<?= base_url('teaching/attendance/session/' . $session['id'] . '/update') ?>" id="editForm">
        <?= csrf_field() ?>
        <input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>">

        <!-- Session Info -->
        <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                <h5 class="fw-bold text-gray-900 mb-0">Informasi Sesi</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label text-xs fw-semibold text-gray-900">Tanggal</label>
                        <input type="date" name="attendance_date" class="form-control" value="<?= esc($session['attendance_date']) ?>" <?= strtoupper($session['status'] ?? '') === 'VERIFIED' ? 'disabled' : '' ?>>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-xs fw-semibold text-gray-900">Pertemuan #</label>
                        <input type="number" name="meeting_number" class="form-control" value="<?= (int) $session['meeting_number'] ?>" min="1" max="40" <?= strtoupper($session['status'] ?? '') === 'VERIFIED' ? 'disabled' : '' ?>>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-xs fw-semibold text-gray-900">Topik / Kegiatan</label>
                        <input type="text" name="topic" class="form-control" value="<?= esc($session['topic'] ?? '') ?>" placeholder="Topik pembelajaran">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-xs fw-semibold text-gray-900">Status Sesi</label>
                        <input type="text" class="form-control" value="<?= esc(strtoupper($session['status'] ?? 'DRAFT')) ?>" disabled>
                    </div>
                </div>
            </div>
        </div>

        <!-- Roster -->
        <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="fw-bold text-gray-900 mb-0">Kehadiran Siswa</h5>
                    <p class="text-xs text-muted mb-0">Ubah status kehadiran sesuai kebutuhan. Tombol: H=Hadir, T=Terlambat, I=Izin, S=Sakit, A=Alpa</p>
                </div>
                <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill text-xs">
                    <?= count($roster) ?> Siswa
                </span>
            </div>
            <div class="card-body p-4">
                <?php if (empty($roster)): ?>
                    <div class="text-center py-4 text-muted">
                        <i data-lucide="users" class="w-8 h-8 text-muted mb-2 d-inline-block"></i>
                        <p class="text-xs mb-0">Tidak ada data siswa untuk sesi ini.</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($roster as $st): ?>
                            <?php
                            $currentStatus = strtoupper($st['status'] ?? 'HADIR');
                            ?>
                            <div class="d-flex align-items-center justify-content-between p-2.5 bg-light-subtle border border-gray-100 rounded-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-2xs text-muted" style="min-width: 50px;"><?= esc($st['student_number'] ?? '') ?></span>
                                    <span class="fw-semibold text-xs text-gray-900"><?= esc($st['full_name']) ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="btn-group btn-group-xs roster-btn-group" data-student-id="<?= (int) $st['student_id'] ?>">
                                        <?php foreach (['HADIR' => 'H', 'TERLAMBAT' => 'T', 'IZIN' => 'I', 'SAKIT' => 'S', 'ALPA' => 'A'] as $code => $label): ?>
                                            <button type="button" class="btn btn-xs <?= $currentStatus === $code ? ($code === 'HADIR' ? 'btn-success' : ($code === 'ALPA' ? 'btn-danger' : 'btn-warning')) : 'btn-outline-secondary' ?> roster-btn <?= $currentStatus === $code ? 'active' : '' ?>" data-status="<?= $code ?>">
                                                <?= $label ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="text" class="form-control form-control-xs text-2xs roster-notes" data-student-id="<?= (int) $st['student_id'] ?>" value="<?= esc($st['notes'] ?? '') ?>" placeholder="Catatan" style="width: 100px; max-width: 100px;">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div id="rosterHiddenInputs"></div>

                <div class="mt-4 d-flex justify-content-between">
                    <a href="<?= base_url('teaching/attendance/history') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i data-lucide="x" class="w-4 h-4 me-1 d-inline-block"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill fw-semibold shadow-sm" id="btnSubmit">
                        <i data-lucide="save" class="w-4 h-4 me-1 d-inline-block"></i> Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
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
    document.getElementById('editForm')?.addEventListener('submit', function(e) {
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
});
</script>
<?= $this->endSection() ?>
