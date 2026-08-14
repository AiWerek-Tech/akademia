<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-slate-800">Kelas Binaan Saya</h4>
        <p class="text-muted fs-7 mb-0">Data peserta didik khusus rombel yang ditugaskan kepada Anda.</p>
    </div>
    <?php if ($classroom): ?>
        <a href="<?= base_url('portal/schedule?scope=classroom&classroom_id=' . (int) $classroom['id']) ?>" class="btn btn-outline-primary rounded-3">
            <i data-lucide="calendar-days" style="width:16px"></i> Lihat Jadwal Kelas
        </a>
    <?php endif; ?>
</div>

<?php if (!$classroom): ?>
    <div class="alert alert-warning border-0 rounded-4 shadow-sm p-4">
        <h5 class="fw-bold"><?= !empty($classroomFilteredOut) ? 'Tidak ada kelas binaan pada unit ini' : 'Rombel belum ditautkan' ?></h5>
        <p class="mb-0"><?= !empty($classroomFilteredOut) ? 'Kelas binaan Anda berada di unit lain. Pilih Semua Unit atau unit kelas binaan melalui filter di navbar.' : 'Akun wali kelas belum ditautkan ke rombel aktif. Hubungi administrator.' ?></p>
    </div>
<?php else: ?>
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <span class="text-muted fs-8 text-uppercase fw-semibold">Rombel</span>
                    <h3 class="fw-bold mb-1"><?= esc($classroom['name']) ?></h3>
                    <p class="text-muted mb-0"><?= esc($classroom['unit_name']) ?> · <?= esc($classroom['grade_name']) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-primary bg-opacity-10">
                <div class="card-body p-4">
                    <span class="text-muted fs-8 text-uppercase fw-semibold">Peserta Didik Aktif</span>
                    <h2 class="fw-bold text-primary mb-0 mt-1"><?= count($students) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-transparent border-0 p-4 pb-0">
            <h5 class="fw-bold mb-0">Daftar Peserta Didik</h5>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:70px">No.</th>
                            <th>Nomor Induk</th>
                            <th>Nama Lengkap</th>
                            <th class="text-center">Tingkat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($students === []): ?>
                            <tr><td colspan="4" class="text-center text-muted py-5">Belum ada peserta didik pada rombel ini.</td></tr>
                        <?php else: ?>
                            <?php foreach ($students as $index => $student): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= esc($student['student_number']) ?></td>
                                    <td class="fw-semibold"><?= esc($student['full_name']) ?></td>
                                    <td class="text-center"><?= (int) $student['current_grade'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
