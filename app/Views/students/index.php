<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i data-lucide="user-check" class="me-2 text-primary" style="width:24px;height:24px;"></i>Master Peserta Didik</h4>
        <p class="text-muted mb-0">Pengelolaan data siswa, NIS/NISN, dan penempatan Kelas / Rombel secara terpusat.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($canManageStudents): ?>
        <a href="<?= base_url('classrooms/promote') ?>" class="btn btn-success rounded-3 btn-sm px-3 shadow-sm">
            <i data-lucide="trending-up" class="me-1" style="width:14px;height:14px;"></i> Kenaikan Kelas Otomatis
        </a>
        <a href="<?= base_url('imports/master?type=STUDENTS') ?>" class="btn btn-outline-primary rounded-3 btn-sm px-3">
            <i data-lucide="file-up" class="me-1" style="width:14px;height:14px;"></i> Import Staging Master
        </a>
        <?php endif ?>
        <a href="<?= base_url('exports/master/STUDENTS') ?>" class="btn btn-outline-secondary rounded-3 btn-sm px-3">
            <i data-lucide="download" class="me-1" style="width:14px;height:14px;"></i> Ekspor Excel
        </a>
        <?php if ($canManageStudents): ?>
        <button type="button" class="btn btn-primary rounded-3 btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addStudentMasterModal">
            <i data-lucide="user-plus" class="me-1" style="width:14px;height:14px;"></i> Tambah Peserta Didik
        </button>
        <?php endif ?>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif ?>

<!-- Stats Metrics -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-4">
                    <i data-lucide="users" style="width:24px;height:24px;"></i>
                </div>
                <div>
                    <small class="text-muted d-block fs-8">Total Peserta Didik</small>
                    <span class="h4 fw-bold mb-0"><?= esc($totalStudents) ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="p-3 bg-success bg-opacity-10 text-success rounded-4">
                    <i data-lucide="graduation-cap" style="width:24px;height:24px;"></i>
                </div>
                <div>
                    <small class="text-muted d-block fs-8">Siswa SMA</small>
                    <span class="h4 fw-bold mb-0"><?= esc($smaCount) ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="p-3 bg-info bg-opacity-10 text-info rounded-4">
                    <i data-lucide="book-open" style="width:24px;height:24px;"></i>
                </div>
                <div>
                    <small class="text-muted d-block fs-8">Siswa SMP</small>
                    <span class="h4 fw-bold mb-0"><?= esc($smpCount) ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-4">
                    <i data-lucide="door-closed" style="width:24px;height:24px;"></i>
                </div>
                <div>
                    <small class="text-muted d-block fs-8">Ter-assign Rombel</small>
                    <span class="h4 fw-bold mb-0"><?= esc($assignedClassCount) ?> <small class="fs-8 text-muted">/ <?= esc($totalStudents) ?></small></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="get" action="<?= base_url('students') ?>" class="row g-2 align-items-center">
            <div class="col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i data-lucide="search" style="width:14px;height:14px;"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Cari nama atau NIS..." value="<?= esc($searchQuery) ?>">
                </div>
            </div>
            <div class="col-md-2">
                <select name="unit_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Unit</option>
                    <?php foreach ($units as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (int)$selectedUnitId === (int)$u['id'] ? 'selected' : '' ?>><?= esc($u['code'] . ' - ' . $u['name']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="academic_year_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Thn Pelajaran</option>
                    <?php foreach ($academicYears as $ay): ?>
                        <option value="<?= $ay['id'] ?>" <?= (int)$selectedYearId === (int)$ay['id'] ? 'selected' : '' ?>><?= esc($ay['name']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="grade" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Tingkat</option>
                    <?php foreach ([7, 8, 9, 10, 11, 12] as $g): ?>
                        <option value="<?= $g ?>" <?= (int)$selectedGrade === $g ? 'selected' : '' ?>>Tingkat <?= $g ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="classroom_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Rombel</option>
                    <?php foreach ($classrooms as $cls): ?>
                        <option value="<?= $cls['id'] ?>" <?= (int)$selectedClassroomId === (int)$cls['id'] ? 'selected' : '' ?>><?= esc($cls['code'] . ' - ' . $cls['name']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-1 text-end">
                <a href="<?= base_url('students') ?>" class="btn btn-sm btn-light border w-100" title="Reset filter"><i data-lucide="rotate-ccw" style="width:14px;height:14px;"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table Card -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="text-uppercase text-muted fs-8 fw-bold bg-light">
                    <th class="text-center ps-3" style="width: 55px;">No.</th>
                    <th>Nomor Induk (NIS/NISN)</th>
                    <th>Nama Lengkap Siswa</th>
                    <th>Unit</th>
                    <th>Tahun Pelajaran</th>
                    <th>Tingkat</th>
                    <th>Kelas / Rombel</th>
                    <th>Status</th>
                    <?php if ($canManageStudents): ?><th class="text-end pe-4">Aksi</th><?php endif ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($students === []): ?>
                    <tr>
                        <td colspan="<?= $canManageStudents ? 9 : 8 ?>" class="text-center text-muted py-5">
                            <i data-lucide="user-x" class="d-block mx-auto mb-2 text-secondary" style="width:32px;height:32px;"></i>
                            Tidak ada data peserta didik yang sesuai filter.<br>
                            <small class="text-muted">Gunakan tombol <strong>"Tambah Peserta Didik"</strong> atau <strong>"Import Staging Master"</strong> untuk mengisi data.</small>
                        </td>
                    </tr>
                <?php endif ?>
                <?php foreach ($students as $idx => $st): ?>
                    <tr>
                        <td class="text-center fw-semibold text-secondary fs-8 ps-3"><?= $idx + 1 ?></td>
                        <td><code><?= esc($st['student_number']) ?></code></td>
                        <td>
                            <strong><?= esc($st['full_name']) ?></strong>
                            <?php if ($st['user_id']): ?>
                                <span class="badge text-bg-light border text-muted ms-1 fs-9" title="Terhubung akun user">User #<?= esc($st['user_id']) ?></span>
                            <?php endif ?>
                        </td>
                        <td><span class="badge text-bg-light border"><?= esc($st['unit_code']) ?></span></td>
                        <td><?= esc($st['academic_year_name']) ?></td>
                        <td><span class="badge text-bg-secondary-subtle text-secondary border fs-8">Kelas <?= esc($st['current_grade']) ?></span></td>
                        <td>
                            <?php if ($st['classroom_code']): ?>
                                <span class="badge text-bg-primary-subtle text-primary border border-primary-subtle rounded-2">
                                    <i data-lucide="door-closed" class="me-1" style="width:12px;height:12px;"></i><?= esc($st['classroom_code']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge text-bg-light text-muted border">Belum di-assign</span>
                            <?php endif ?>
                        </td>
                        <td>
                            <span class="badge <?= $st['is_active'] ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                <?= $st['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </td>
                        <?php if ($canManageStudents): ?><td class="text-end pe-4">
                            <div class="d-flex justify-content-end gap-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editStudentModal<?= $st['id'] ?>" title="Edit Siswa">
                                    <i data-lucide="pencil" style="width:14px;height:14px;"></i>
                                </button>
                                <form method="post" action="<?= base_url('students/' . $st['id'] . '/delete') ?>" onsubmit="return confirm('Hapus data peserta didik ini?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>
                                </form>
                            </div>
                        </td><?php endif ?>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($canManageStudents): ?>
<!-- Modal 1: Tambah Peserta Didik Master -->
<div class="modal fade" id="addStudentMasterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="<?= base_url('students') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i data-lucide="user-plus" class="me-2" style="width:18px;height:18px;"></i>Tambah Peserta Didik Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Unit Sekolah <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" name="unit_id" required>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= (int)$selectedUnitId === (int)$u['id'] ? 'selected' : '' ?>><?= esc($u['code'] . ' - ' . $u['name']) ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tahun Pelajaran <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" name="academic_year_id" required>
                                <?php foreach ($academicYears as $ay): ?>
                                    <option value="<?= $ay['id'] ?>" <?= (int)$selectedYearId === (int)$ay['id'] ? 'selected' : '' ?>><?= esc($ay['name']) ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nomor Induk (NIS / NISN) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" name="student_number" required maxlength="40" placeholder="Contoh: 20261001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Lengkap Siswa <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" name="full_name" required maxlength="150" placeholder="Contoh: Ahmad Dahlan">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tingkat Kelas <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" name="current_grade" required>
                                <?php foreach ([7, 8, 9, 10, 11, 12] as $g): ?>
                                    <option value="<?= $g ?>">Tingkat <?= $g ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kelas / Rombel</label>
                            <select class="form-select rounded-3" name="classroom_id">
                                <option value="">Pilih Rombel (Opsional)...</option>
                                <?php foreach ($classrooms as $cls): ?>
                                    <option value="<?= $cls['id'] ?>"><?= esc($cls['code'] . ' - ' . $cls['name']) ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">User ID (Opsional)</label>
                            <input type="number" class="form-control rounded-3" name="user_id" min="1" placeholder="ID User akun login">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3"><i data-lucide="check" class="me-1" style="width:16px;height:16px;"></i> Simpan Siswa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Edit Peserta Didik (Loop per student) -->
<?php foreach ($students as $st): ?>
<div class="modal fade" id="editStudentModal<?= $st['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="<?= base_url('students/' . $st['id'] . '/update') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i data-lucide="pencil" class="me-2" style="width:18px;height:18px;"></i>Edit Peserta Didik: <?= esc($st['full_name']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Unit Sekolah <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" name="unit_id" required>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= (int)$st['unit_id'] === (int)$u['id'] ? 'selected' : '' ?>><?= esc($u['code'] . ' - ' . $u['name']) ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tahun Pelajaran <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" name="academic_year_id" required>
                                <?php foreach ($academicYears as $ay): ?>
                                    <option value="<?= $ay['id'] ?>" <?= (int)$st['academic_year_id'] === (int)$ay['id'] ? 'selected' : '' ?>><?= esc($ay['name']) ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nomor Induk (NIS / NISN) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" name="student_number" value="<?= esc($st['student_number']) ?>" required maxlength="40">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Lengkap Siswa <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" name="full_name" value="<?= esc($st['full_name']) ?>" required maxlength="150">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tingkat Kelas <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" name="current_grade" required>
                                <?php foreach ([7, 8, 9, 10, 11, 12] as $g): ?>
                                    <option value="<?= $g ?>" <?= (int)$st['current_grade'] === $g ? 'selected' : '' ?>>Tingkat <?= $g ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kelas / Rombel</label>
                            <select class="form-select rounded-3" name="classroom_id">
                                <option value="">Pilih Rombel (Opsional)...</option>
                                <?php foreach ($classrooms as $cls): ?>
                                    <option value="<?= $cls['id'] ?>" <?= (int)$st['classroom_id'] === (int)$cls['id'] ? 'selected' : '' ?>><?= esc($cls['code'] . ' - ' . $cls['name']) ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">User ID (Opsional)</label>
                            <input type="number" class="form-control rounded-3" name="user_id" value="<?= esc($st['user_id'] ?? '') ?>" min="1">
                        </div>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_active" value="1" id="stActive<?= $st['id'] ?>" <?= $st['is_active'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="stActive<?= $st['id'] ?>">Status Aktif</label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3"><i data-lucide="save" class="me-1" style="width:16px;height:16px;"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach ?>
<?php endif ?>

<?= $this->endSection() ?>
