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
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr class="text-uppercase text-muted fs-8 fw-bold">
                                <th>Tahun Pelajaran</th>
                                <th>Semester</th>
                                <th>Tanggal Mulai</th>
                                <th>Tanggal Selesai</th>
                                <th>Status Aktif</th>
                                <th>Status Alur Kerja</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($periods)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Belum ada data periode akademik.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($periods as $ap): ?>
                                    <tr>
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
                                        <td>
                                            <?php 
                                            $badgeClass = 'bg-secondary';
                                            if ($ap['workflow_status'] === 'APPROVED') $badgeClass = 'bg-success';
                                            elseif ($ap['workflow_status'] === 'LOCKED') $badgeClass = 'bg-dark';
                                            elseif ($ap['workflow_status'] === 'VALIDATED') $badgeClass = 'bg-info text-white';
                                            elseif ($ap['workflow_status'] === 'REVIEWED') $badgeClass = 'bg-warning text-dark';
                                            ?>
                                            <span class="badge <?= $badgeClass ?> px-3 py-1 rounded-pill fw-semibold fs-8">
                                                <?= $ap['workflow_status'] ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="<?= base_url('academic-periods/' . $ap['uuid']) ?>" class="btn btn-sm btn-light rounded-3 text-primary d-inline-flex align-items-center gap-1">
                                                    <i data-lucide="eye" style="width: 14px; height: 14px;"></i> Detail
                                                </a>
                                                <?php if ((int)$ap['is_active'] === 0 && in_array($ap['workflow_status'], ['APPROVED', 'LOCKED'], true) && has_permission('academic_periods.manage')): ?>
                                                    <form action="<?= base_url('academic-periods/' . $ap['uuid'] . '/activate') ?>" method="POST" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-sm btn-outline-success rounded-3 d-inline-flex align-items-center gap-1">
                                                            <i data-lucide="play-circle" style="width: 14px; height: 14px;"></i> Aktifkan
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
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr class="text-uppercase text-muted fs-8 fw-bold">
                                <th>Tahun Pelajaran</th>
                                <th>Tanggal Mulai</th>
                                <th>Tanggal Selesai</th>
                                <th>Status Aktif</th>
                                <th>Status Lifecycle</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($years)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Belum ada data tahun pelajaran.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($years as $yr): ?>
                                    <tr>
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
                                        <td class="text-end">
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
