<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4 align-items-center">
        <div class="col-12 col-md-auto d-flex align-items-center">
            <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3 me-3">
                <i data-lucide="calendar-days" class="fs-4"></i>
            </div>
            <div>
                <h4 class="mb-1 text-dark fw-bold"><?= esc($title ?? 'Kalender Pendidikan') ?></h4>
                <p class="text-muted mb-0 fs-7">Kelola kalender pendidikan sekolah dan hari efektif belajar</p>
            </div>
        </div>
        <div class="col-12 col-md mt-3 mt-md-0 text-md-end">
            <?php if (has_permission('academic_calendar.manage')) : ?>
                <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalGenerate">
                    <i data-lucide="plus" class="me-2 icon-sm"></i> Generate Kalender Baru
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i data-lucide="check-circle" class="me-2 icon-sm"></i> <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i data-lucide="alert-circle" class="me-2 icon-sm"></i> <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Calendar List -->
    <div class="row">
        <?php if (empty($calendars)) : ?>
            <div class="col-12 text-center py-5">
                <div class="text-muted mb-3">
                    <i data-lucide="calendar-x" style="width: 64px; height: 64px; opacity: 0.5;"></i>
                </div>
                <h5>Belum ada Kalender Pendidikan</h5>
                <p class="text-muted">Mulai dengan meng-generate kalender baru untuk tahun ajaran tertentu.</p>
                <?php if (has_permission('academic_calendar.manage')) : ?>
                    <button type="button" class="btn btn-primary shadow-sm mt-2" data-bs-toggle="modal" data-bs-target="#modalGenerate">
                        Generate Sekarang
                    </button>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <?php foreach ($calendars as $cal) : ?>
                <div class="col-md-6 col-xl-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 fw-bold text-dark"><?= esc($cal['name']) ?></h6>
                                <div class="text-muted fs-8 mt-1">
                                    <i data-lucide="calendar" class="icon-xs me-1"></i> <?= esc($cal['year_name']) ?>
                                    <?php if ($cal['unit_code']) : ?>
                                        &bull; <span class="badge bg-light text-dark border"><?= esc($cal['unit_code']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php
                                $badgeClass = 'bg-secondary';
                                if ($cal['status'] === 'DRAFT') $badgeClass = 'bg-warning text-dark';
                                if ($cal['status'] === 'ACTIVE') $badgeClass = 'bg-success';
                            ?>
                            <span class="badge <?= $badgeClass ?> rounded-pill px-3 py-2 fw-medium fs-8">
                                <?= esc($cal['status']) ?>
                            </span>
                        </div>
                        <div class="card-body p-4 bg-light bg-opacity-50">
                            <div class="row g-3 text-center mb-0">
                                <div class="col-6">
                                    <div class="p-3 bg-white rounded-3 shadow-sm border border-light">
                                        <div class="fs-4 fw-bolder text-primary mb-1"><?= (int)$cal['total_hes_sem1'] + (int)$cal['total_hes_sem2'] ?></div>
                                        <div class="fs-8 text-muted fw-medium text-uppercase tracking-wider">Total HES</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 bg-white rounded-3 shadow-sm border border-light">
                                        <div class="fs-4 fw-bolder text-info mb-1"><?= (int)$cal['total_heb_sem1'] + (int)$cal['total_heb_sem2'] ?></div>
                                        <div class="fs-8 text-muted fw-medium text-uppercase tracking-wider">Total HEB</div>
                                    </div>
                                </div>
                                <div class="col-12 mt-3">
                                    <div class="d-flex justify-content-between align-items-center p-3 bg-white rounded-3 shadow-sm border border-light">
                                        <div class="text-start">
                                            <div class="fs-8 text-muted fw-medium mb-1">Minggu Efektif</div>
                                            <div class="fs-6 fw-bold text-dark">
                                                Sem 1: <?= (int)$cal['total_effective_weeks_sem1'] ?> <span class="text-muted mx-1">|</span> Sem 2: <?= (int)$cal['total_effective_weeks_sem2'] ?>
                                            </div>
                                        </div>
                                        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-circle">
                                            <i data-lucide="bar-chart-2" class="icon-sm"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <?php $validationClass = ($cal['validation_status'] ?? '') === 'VALID' ? 'success' : ((($cal['validation_status'] ?? '') === 'ERROR') ? 'danger' : 'warning'); ?>
                                    <span class="badge bg-<?= $validationClass ?>-subtle text-<?= $validationClass ?> border border-<?= $validationClass ?>-subtle">
                                        Validasi: <?= esc($cal['validation_status'] ?? 'PENDING') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
                            <div class="d-flex gap-2">
                                <a href="<?= base_url('academic-calendar/' . $cal['id'] . '/editor') ?>" class="btn btn-sm btn-outline-primary shadow-sm rounded-3 px-3">
                                    <i data-lucide="edit" class="icon-xs me-1"></i> Editor
                                </a>
                                <a href="<?= base_url('academic-calendar/' . $cal['id'] . '/print') ?>" target="_blank" class="btn btn-sm btn-outline-secondary shadow-sm rounded-3 px-3">
                                    <i data-lucide="printer" class="icon-xs me-1"></i> Cetak
                                </a>
                            </div>

                            <?php if ($cal['status'] !== 'ACTIVE' && has_permission('academic_calendar.manage')) : ?>
                                <form action="<?= base_url('academic-calendar/' . $cal['id'] . '/activate') ?>" method="post" class="d-inline form-activate">
                                    <?= csrf_field() ?>
                                    <button type="button" class="btn btn-sm btn-success shadow-sm rounded-3 px-3 btn-activate">
                                        <i data-lucide="check-circle" class="icon-xs me-1"></i> Aktifkan
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php if (has_permission('academic_calendar.manage')) : ?>
<!-- Modal Generate -->
<div class="modal fade" id="modalGenerate" tabindex="-1" aria-labelledby="modalGenerateLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom px-4 py-3">
                <h5 class="modal-title fw-bold" id="modalGenerateLabel">Generate Kalender Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('academic-calendar/generate') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Nama Kalender <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required placeholder="Contoh: Kalender Pendidikan 2024/2025">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Tahun Ajaran <span class="text-danger">*</span></label>
                        <select class="form-select" name="academic_year_id" id="calendarYear" required>
                            <option value="">Pilih Tahun Ajaran</option>
                            <?php foreach ($academicYears as $ay) : ?>
                                <option value="<?= esc($ay['id']) ?>"><?= esc($ay['name']) ?> (<?= date('Y', strtotime($ay['start_date'])) ?> - <?= date('Y', strtotime($ay['end_date'])) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Unit Sekolah (Opsional)</label>
                        <select class="form-select" name="unit_id" id="calendarUnit">
                            <option value="">Semua Unit</option>
                            <?php foreach ($units as $u) : ?>
                                <option value="<?= esc($u['id']) ?>"><?= esc($u['name']) ?> (<?= esc($u['code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Biarkan kosong jika kalender berlaku untuk semua unit.</div>
                    </div>

                    <div class="alert alert-primary border-0 small">
                        <i data-lucide="link-2" class="icon-xs me-2"></i>Hari sekolah otomatis mengikuti <a href="<?= base_url('settings/academic-operations') ?>" class="alert-link">Pengaturan Operasional Akademik</a> dan struktur kurikulum.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">No. Referensi Dinas (Opsional)</label>
                        <input type="text" class="form-control" name="dinas_reference_number" placeholder="No. SK Dinas">
                    </div>

                    <div class="border rounded-3 p-3 bg-light mb-3">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div>
                                <div class="fw-semibold fs-7">Pratinjau cerdas</div>
                                <div class="text-muted fs-8">Hitung HES/HEB dan cek perbedaan profil sebelum menyimpan.</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnPreviewCalendar">Hitung</button>
                        </div>
                        <div id="calendarPreviewResult" class="mt-2 fs-8"></div>
                    </div>

                    <details class="mb-3">
                        <summary class="fw-semibold fs-7">Target referensi opsional</summary>
                        <div class="row g-2 mt-1">
                            <div class="col-6"><label class="form-label fs-8">HES Semester 1</label><input type="number" class="form-control form-control-sm" name="target_hes_sem1" min="0"></div>
                            <div class="col-6"><label class="form-label fs-8">HES Semester 2</label><input type="number" class="form-control form-control-sm" name="target_hes_sem2" min="0"></div>
                            <div class="col-6"><label class="form-label fs-8">HEB Semester 1</label><input type="number" class="form-control form-control-sm" name="target_heb_sem1" min="0"></div>
                            <div class="col-6"><label class="form-label fs-8">HEB Semester 2</label><input type="number" class="form-control form-control-sm" name="target_heb_sem2" min="0"></div>
                        </div>
                    </details>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Tanggal Referensi Dinas (Opsional)</label>
                        <input type="date" class="form-control" name="dinas_reference_date">
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm">
                        <i data-lucide="cog" class="icon-xs me-2"></i> Generate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- Script for SweetAlert Confirmation -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const previewButton = document.getElementById('btnPreviewCalendar');
    if (previewButton) {
        previewButton.addEventListener('click', async function () {
            const result = document.getElementById('calendarPreviewResult');
            const year = document.getElementById('calendarYear').value;
            if (!year) { result.innerHTML = '<span class="text-danger">Pilih tahun ajaran terlebih dahulu.</span>'; return; }
            const body = new FormData();
            body.append('academic_year_id', year);
            body.append('unit_id', document.getElementById('calendarUnit').value);
            body.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            this.disabled = true;
            result.textContent = 'Menghitung…';
            try {
                const response = await fetch('<?= base_url('academic-calendar/preview') ?>', {method: 'POST', body, headers: {'X-Requested-With': 'XMLHttpRequest'}});
                const data = await response.json();
                if (!data.success) throw new Error(data.message || 'Pratinjau gagal.');
                const m = data.metrics;
                result.style.whiteSpace = 'pre-line';
                result.textContent = `Sumber: ${data.working_day_source} · ${data.working_day_codes.join(', ')}\nHES S1/S2: ${m.hes_sem1}/${m.hes_sem2} · HEB S1/S2: ${m.heb_sem1}/${m.heb_sem2} · Minggu efektif: ${m.effective_weeks_sem1}/${m.effective_weeks_sem2}\n${(data.warnings || []).join('\n')}`;
            } catch (error) {
                result.classList.add('text-danger');
                result.textContent = error.message;
            } finally { this.disabled = false; }
        });
    }
    const btnActivates = document.querySelectorAll('.btn-activate');
    btnActivates.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('.form-activate');

            Swal.fire({
                title: 'Aktifkan Kalender?',
                text: "Kalender ini akan menjadi aktif dan menjadi referensi utama.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Aktifkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});
</script>

<?= $this->endSection() ?>
