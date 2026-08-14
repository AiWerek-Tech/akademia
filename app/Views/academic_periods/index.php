<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-1 text-slate-800 d-flex align-items-center gap-2">
                    <i data-lucide="calendar" class="text-primary" style="width: 24px; height: 24px;"></i>
                    Kalender Akademik
                </h4>
                <p class="text-muted fs-7 mb-0">Manajemen tahun pelajaran dan periode semester sekolah</p>
            </div>
            <div class="d-flex gap-2">
                <?php if (has_permission('academic_years.manage')): ?>
                    <a href="<?= base_url('academic-years/create') ?>" class="btn btn-outline-primary rounded-3 btn-sm px-3 d-inline-flex align-items-center gap-1">
                        <i data-lucide="calendar-plus" style="width: 16px; height: 16px;"></i> Tambah Tahun
                    </a>
                <?php endif; ?>
                <?php if (has_permission('academic_periods.manage')): ?>
                    <a href="<?= base_url('academic-periods/create') ?>" class="btn btn-primary rounded-3 btn-sm px-3 d-inline-flex align-items-center gap-1">
                        <i data-lucide="plus-circle" style="width: 16px; height: 16px;"></i> Tambah Periode
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (session('success')): ?><div class="alert alert-success rounded-3"><?= esc(session('success')) ?></div><?php endif; ?>
<?php if (session('error')): ?><div class="alert alert-danger rounded-3"><?= esc(session('error')) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 mb-3"><div class="card-body p-3"><form method="GET" action="<?= base_url('academic-periods') ?>" class="row g-2 align-items-end"><div class="col-md-5"><label class="form-label fs-8 fw-semibold">Tahun Pelajaran</label><select name="academic_year_id" class="form-select form-select-sm rounded-3"><option value="">Semua tahun</option><?php foreach($years as $year): ?><option value="<?= esc($year['id']) ?>" <?= (string)($filters['academic_year_id']??'')===(string)$year['id']?'selected':'' ?>><?= esc($year['name']) ?></option><?php endforeach; ?></select></div><div class="col-md-4"><label class="form-label fs-8 fw-semibold">Status Periode</label><select name="state" class="form-select form-select-sm rounded-3"><option value="">Semua status</option><option value="ACTIVE" <?= ($filters['state']??'')==='ACTIVE'?'selected':'' ?>>Aktif</option><option value="INACTIVE" <?= ($filters['state']??'')==='INACTIVE'?'selected':'' ?>>Tidak Aktif</option><option value="ARCHIVED" <?= ($filters['state']??'')==='ARCHIVED'?'selected':'' ?>>Diarsipkan</option></select></div><div class="col-md-3 d-flex gap-2"><button class="btn btn-sm btn-primary flex-grow-1">Terapkan</button><a href="<?= base_url('academic-periods') ?>" class="btn btn-sm btn-light">Reset</a></div></form></div></div>

<!-- Tabs Control -->
<div class="row mb-3">
    <div class="col-12">
        <ul class="nav nav-pills gap-2 p-1 bg-white rounded-3 shadow-xs border d-inline-flex" id="calendarTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-2 px-4 py-2 fw-semibold fs-7 d-inline-flex align-items-center gap-1" id="periods-tab" data-bs-toggle="tab" data-bs-target="#periods" type="button" role="tab">
                    <i data-lucide="hourglass" style="width: 14px; height: 14px;"></i> Periode Semester
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-2 px-4 py-2 fw-semibold fs-7 d-inline-flex align-items-center gap-1" id="years-tab" data-bs-toggle="tab" data-bs-target="#years" type="button" role="tab">
                    <i data-lucide="calendar-range" style="width: 14px; height: 14px;"></i> Tahun Pelajaran
                </button>
            </li>
        </ul>
    </div>
</div>

<div class="tab-content" id="calendarTabContent">
    <!-- Tab 1: Academic Periods -->
    <div class="tab-pane fade show active" id="periods" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="text-uppercase text-muted fs-8 fw-bold bg-light">
                                <th class="text-center ps-3" style="width: 55px;">No.</th>
                                <th>Tahun Pelajaran</th>
                                <th>Semester</th>
                                <th>Tanggal Mulai</th>
                                <th>Tanggal Selesai</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($periods)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">Belum ada data periode akademik.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($periods as $idx => $ap): ?>
                                    <tr>
                                        <td class="text-center fw-semibold text-secondary fs-8 ps-3"><?= $idx + 1 ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="bg-light rounded p-2 d-flex align-items-center justify-content-center">
                                                    <i data-lucide="calendar-days" class="text-primary" style="width: 16px; height: 16px;"></i>
                                                </div>
                                                <span class="fw-bold text-slate-800"><?= esc($ap['year_name']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-slate-700">
                                                Semester <?= (int)$ap['semester_number'] === 1 ? '1 (Ganjil)' : '2 (Genap)' ?>
                                            </span>
                                        </td>
                                        <td><?= date('d-m-Y', strtotime($ap['start_date'])) ?></td>
                                        <td><?= date('d-m-Y', strtotime($ap['end_date'])) ?></td>
                                        <td>
                                            <?php if ((int)$ap['is_active'] === 1): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success px-3 py-1 rounded-pill fw-semibold fs-8 d-inline-flex align-items-center gap-1">
                                                    <i data-lucide="check-circle" style="width: 12px; height: 12px;"></i> Aktif
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted px-3 py-1 rounded-pill fw-semibold fs-8">Non-Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="<?= base_url('academic-periods/' . $ap['uuid']) ?>" class="btn btn-sm btn-light rounded-3 text-primary d-inline-flex align-items-center gap-1">
                                                    <i data-lucide="eye" style="width: 14px; height: 14px;"></i> Detail
                                                </a>
                                                <?php if ((int)$ap['is_active'] === 0 && $ap['workflow_status'] !== 'ARCHIVED' && has_permission('academic_periods.manage')): ?>
                                                    <form action="<?= base_url('academic-periods/' . $ap['uuid'] . '/activate') ?>" method="POST" class="d-inline" data-confirm="Aktifkan Semester <?= (int)$ap['semester_number'] === 1 ? 'Ganjil' : 'Genap' ?> T.A. <?= esc($ap['year_name']) ?> sekarang? Periode aktif sebelumnya akan dinonaktifkan otomatis." data-confirm-title="Aktifkan periode?" data-confirm-button="Aktifkan">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-sm btn-success rounded-3 d-inline-flex align-items-center gap-1">
                                                            <i data-lucide="play-circle" style="width: 14px; height: 14px;"></i> Aktifkan Sekarang
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 2: Academic Years -->
    <div class="tab-pane fade" id="years" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="text-uppercase text-muted fs-8 fw-bold bg-light">
                                <th class="text-center ps-3" style="width: 55px;">No.</th>
                                <th>Tahun Pelajaran</th>
                                <th>Tanggal Mulai</th>
                                <th>Tanggal Selesai</th>
                                <th>Status Aktif</th>
                                <th>Status Lifecycle</th>
                                <th class="text-end pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($years)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">Belum ada data tahun pelajaran.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($years as $idx => $yr): ?>
                                    <tr>
                                        <td class="text-center fw-semibold text-secondary fs-8 ps-3"><?= $idx + 1 ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="bg-light rounded p-2 d-flex align-items-center justify-content-center">
                                                    <i data-lucide="calendar-check" class="text-primary" style="width: 16px; height: 16px;"></i>
                                                </div>
                                                <span class="fw-bold text-slate-800"><?= esc($yr['name']) ?></span>
                                            </div>
                                        </td>
                                        <td><?= date('d-m-Y', strtotime($yr['start_date'])) ?></td>
                                        <td><?= date('d-m-Y', strtotime($yr['end_date'])) ?></td>
                                        <td>
                                            <?php if ((int)$yr['is_active'] === 1): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success px-3 py-1 rounded-pill fw-semibold fs-8 d-inline-flex align-items-center gap-1">
                                                    <i data-lucide="check-circle" style="width: 12px; height: 12px;"></i> Aktif
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted px-3 py-1 rounded-pill fw-semibold fs-8">Non-Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $yearBadgeClass = 'bg-secondary';
                                            if ($yr['status'] === 'ACTIVE') $yearBadgeClass = 'bg-success';
                                            elseif ($yr['status'] === 'ARCHIVED') $yearBadgeClass = 'bg-dark';
                                            ?>
                                            <span class="badge <?= $yearBadgeClass ?> px-3 py-1 rounded-pill fw-semibold fs-8">
                                                <?= $yr['status'] ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="d-flex justify-content-end gap-2">
                                                <?php if (has_permission('academic_years.manage')): ?>
                                                    <a href="<?= base_url('academic-years/' . $yr['uuid'] . '/edit') ?>" class="btn btn-sm btn-light rounded-3 text-secondary d-inline-flex align-items-center gap-1">
                                                        <i data-lucide="pencil" style="width: 14px; height: 14px;"></i> Edit
                                                    </a>
                                                    <?php if ((int)$yr['is_active'] === 0): ?>
                                                        <form action="<?= base_url('academic-years/' . $yr['uuid'] . '/activate') ?>" method="POST" class="d-inline">
                                                            <?= csrf_field() ?>
                                                            <button type="submit" class="btn btn-sm btn-outline-success rounded-3 d-inline-flex align-items-center gap-1">
                                                                <i data-lucide="play-circle" style="width: 14px; height: 14px;"></i> Aktifkan
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>document.addEventListener('DOMContentLoaded', function(){ if(typeof lucide!=='undefined') lucide.createIcons(); });</script>
<?= $this->endSection() ?>
