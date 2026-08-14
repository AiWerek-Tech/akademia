<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="<?= base_url('classrooms') ?>" class="btn btn-sm btn-light border rounded-3"><i data-lucide="arrow-left" style="width:16px;height:16px;"></i></a>
            <h4 class="fw-bold mb-0">Roster Siswa: <?= esc($classroom['name']) ?> (<?= esc($classroom['code']) ?>)</h4>
        </div>
        <p class="text-muted fs-7 mb-0">Pengaturan siswa terdaftar dan pembagian rombel pada unit <?= esc($classroom['unit_code'] ?? 'Sekolah') ?>.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= base_url('classrooms/' . $classroom['uuid'] . '/edit') ?>" class="btn btn-outline-secondary rounded-3 btn-sm px-3">
            <i data-lucide="pencil" class="me-1" style="width:14px;height:14px;"></i> Edit Rombel
        </a>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif ?>

<!-- Classroom Summary Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-sm-6 col-md-3">
                <small class="text-muted d-block fs-8">Kode Rombel</small>
                <span class="fw-bold font-monospace text-primary fs-6"><?= esc($classroom['code']) ?></span>
            </div>
            <div class="col-sm-6 col-md-3">
                <small class="text-muted d-block fs-8">Tingkat Kelas</small>
                <span class="fw-bold text-slate-800 fs-6">Tingkat <?= esc($gradeNumber) ?></span>
            </div>
            <div class="col-sm-6 col-md-3">
                <small class="text-muted d-block fs-8">Wali Kelas</small>
                <span class="fw-bold text-slate-800 fs-6"><?= esc($classroom['homeroom_teacher_name'] ?: 'Belum Ditentukan') ?></span>
            </div>
            <div class="col-sm-6 col-md-3">
                <small class="text-muted d-block fs-8">Jumlah Siswa / Kapasitas</small>
                <span class="badge text-bg-primary-subtle text-primary border border-primary-subtle fs-7 px-3 py-1 mt-1">
                    <?= count($assignedStudents) ?> / <?= esc($classroom['capacity'] ?: '∞') ?> Siswa
                </span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Assigned Students Roster -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-1"><i data-lucide="users" class="me-2 text-primary" style="width:20px;height:20px;"></i>Siswa Terdaftar (<?= count($assignedStudents) ?>)</h5>
                    <small class="text-muted">Daftar siswa aktif yang dimasukkan ke dalam kelas ini.</small>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr class="text-uppercase text-muted fs-8 fw-bold bg-light">
                            <th class="text-center ps-3" style="width: 45px;">No.</th>
                            <th>NIS / NISN</th>
                            <th>Nama Siswa</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($assignedStudents === []): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">
                                    <i data-lucide="user-x" class="d-block mx-auto mb-2 text-secondary" style="width:32px;height:32px;"></i>
                                    Belum ada siswa di dalam Rombel ini.<br>
                                    <small>Pilih siswa dari panel sebelah kanan untuk dimasukkan.</small>
                                </td>
                            </tr>
                        <?php endif ?>
                        <?php foreach ($assignedStudents as $idx => $st): ?>
                            <tr>
                                <td class="text-center fw-semibold text-secondary fs-8 ps-3"><?= $idx + 1 ?></td>
                                <td><code><?= esc($st['student_number']) ?></code></td>
                                <td>
                                    <strong><?= esc($st['full_name']) ?></strong>
                                    <?php if ($st['user_id']): ?>
                                        <span class="badge text-bg-light border text-muted ms-1 fs-9">User #<?= esc($st['user_id']) ?></span>
                                    <?php endif ?>
                                </td>
                                <td class="text-end pe-4">
                                    <form method="post" action="<?= base_url('classrooms/' . $classroom['uuid'] . '/students/' . $st['id'] . '/remove') ?>" onsubmit="return confirm('Keluarkan siswa ini dari Rombel?')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-3" title="Keluarkan dari Rombel"><i data-lucide="user-minus" style="width:14px;height:14px;"></i> Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Unassigned/Available Students Selection -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 p-4">
                <h5 class="fw-bold mb-1"><i data-lucide="user-plus" class="me-2 text-success" style="width:20px;height:20px;"></i>Tambah Siswa ke Rombel</h5>
                <small class="text-muted">Pilih siswa Tingkat <?= esc($gradeNumber) ?> yang belum memiliki/ingin dipindahkan ke kelas ini.</small>
            </div>
            <div class="card-body p-4 pt-0">
                <?php if ($unassignedStudents === []): ?>
                    <div class="text-center py-5 text-muted bg-light rounded-4">
                        <i data-lucide="check-circle" class="d-block mx-auto mb-2 text-success" style="width:32px;height:32px;"></i>
                        Seluruh siswa Tingkat <?= esc($gradeNumber) ?> sudah ter-assign ke Rombel!
                    </div>
                <?php else: ?>
                    <form action="<?= base_url('classrooms/' . $classroom['uuid'] . '/students/assign') ?>" method="post">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <input type="text" id="unassignedSearch" class="form-control form-control-sm rounded-3" placeholder="Cari nama atau NIS siswa...">
                        </div>

                        <div class="border rounded-4 p-3 bg-light mb-3" style="max-height: 350px; overflow-y: auto;">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="selectAllUnassigned">
                                    <label class="form-check-label fw-bold fs-8" for="selectAllUnassigned">Pilih Semua Siswa</label>
                                </div>
                                <span class="badge text-bg-white border text-dark fs-9"><?= count($unassignedStudents) ?> Siswa Tersedia</span>
                            </div>

                            <div id="unassignedList">
                                <?php foreach ($unassignedStudents as $st): ?>
                                    <div class="form-check unassigned-item py-1" data-search="<?= esc(strtolower($st['full_name'] . ' ' . $st['student_number'])) ?>">
                                        <input class="form-check-input unassigned-checkbox" type="checkbox" name="student_ids[]" value="<?= $st['id'] ?>" id="unSt<?= $st['id'] ?>">
                                        <label class="form-check-label d-block cursor-pointer fs-8" for="unSt<?= $st['id'] ?>">
                                            <strong><?= esc($st['full_name']) ?></strong>
                                            <span class="text-muted"> (<?= esc($st['student_number']) ?>)</span>
                                            <?php if ($st['current_classroom_code']): ?>
                                                <span class="badge text-bg-warning text-dark border ms-1 fs-9" title="Rombel Saat Ini">Dari: <?= esc($st['current_classroom_code']) ?></span>
                                            <?php endif ?>
                                        </label>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 rounded-3 shadow-sm"><i data-lucide="plus-circle" class="me-1" style="width:16px;height:16px;"></i> Masukkan Siswa Terpilih</button>
                    </form>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('unassignedSearch');
    const selectAll = document.getElementById('selectAllUnassigned');
    const checkboxes = document.querySelectorAll('.unassigned-checkbox');
    const items = document.querySelectorAll('.unassigned-item');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            items.forEach(item => {
                const search = item.getAttribute('data-search') || '';
                item.style.display = (!q || search.includes(q)) ? '' : 'none';
            });
        });
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                if (cb.closest('.unassigned-item').style.display !== 'none') {
                    cb.checked = selectAll.checked;
                }
            });
        });
    }
});
</script>
<?= $this->endSection() ?>
