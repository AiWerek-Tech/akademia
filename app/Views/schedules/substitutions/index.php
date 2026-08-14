<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>

<?php
$today = date('Y-m-d');
$teacherOptions = static function (array $teachers, int $selected = 0): string {
    $html = '<option value="">Pilih guru</option>';
    foreach ($teachers as $teacher) {
        $label = trim((string) ($teacher['teacher_initial'] ?? '')) !== ''
            ? $teacher['full_name'] . ' (' . $teacher['teacher_initial'] . ')'
            : $teacher['full_name'];
        $html .= '<option value="' . (int) $teacher['id'] . '"'
            . ((int) $teacher['id'] === $selected ? ' selected' : '') . '>'
            . esc($label) . '</option>';
    }
    return $html;
};
$periodOptions = static function (array $periods, int $selected = 0): string {
    $html = '<option value="">Pilih periode akademik</option>';
    foreach ($periods as $period) {
        $label = ($period['name'] ?? '') . ' · ' . ($period['year_name'] ?? '');
        $html .= '<option value="' . (int) $period['id'] . '" data-start="' . esc($period['start_date'])
            . '" data-end="' . esc($period['end_date']) . '"'
            . ((int) $period['id'] === $selected ? ' selected' : '') . '>'
            . esc($label) . ((int) ($period['is_active'] ?? 0) === 1 ? ' — Aktif' : '') . '</option>';
    }
    return $html;
};
?>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="text-uppercase text-primary fw-semibold small mb-1">Jadwal Pelajaran</div>
        <h3 class="fw-bold mb-1">Substitusi Guru Sementara</h3>
        <p class="text-muted mb-0">Atur guru pelaksana tanpa mengubah nama, inisial, dan warna guru pemilik pada jadwal cetak.</p>
    </div>
    <?php if ($can_manage): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSubstitutionModal">
            <i data-lucide="user-round-plus" class="me-1"></i> Tambah Substitusi
        </button>
    <?php endif; ?>
</div>

<?php if (session('success')): ?><div class="alert alert-success"><?= esc(session('success')) ?></div><?php endif; ?>
<?php if (session('warning')): ?><div class="alert alert-warning"><?= esc(session('warning')) ?></div><?php endif; ?>
<?php if (session('error')): ?><div class="alert alert-danger"><?= esc(session('error')) ?></div><?php endif; ?>

<div class="alert alert-info border-0 shadow-sm rounded-4 mb-4">
    <div class="d-flex gap-3">
        <i data-lucide="info" class="flex-shrink-0"></i>
        <div>
            <strong>Cara kerja</strong>
            <div class="small mt-1">Guru pemilik tetap tampil di grid dan cetak. Selama tanggal aktif, ketersediaan dan benturan memakai guru pengganti sebagai orang yang benar-benar mengajar. Setelah tanggal berakhir, aturan berhenti otomatis.</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white border-0 px-4 pt-4 pb-3 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Daftar Substitusi</h5>
        <span class="badge bg-light text-dark"><?= count($substitutions) ?> aturan</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr><th class="ps-4">Guru pemilik</th><th>Guru pelaksana</th><th>Periode berlaku</th><th>Status</th><th>Auto Repair</th><th class="text-end pe-4">Aksi</th></tr>
            </thead>
            <tbody>
            <?php if ($substitutions === []): ?>
                <tr><td colspan="6" class="text-center text-muted py-5">Belum ada substitusi guru.</td></tr>
            <?php else: foreach ($substitutions as $row):
                $isActive = (string) $row['status'] === 'ACTIVE';
                $temporal = ! $isActive ? 'Nonaktif' : ($today < $row['effective_from'] ? 'Akan datang' : ($today > $row['effective_to'] ? 'Berakhir' : 'Sedang berlaku'));
                $badge = $temporal === 'Sedang berlaku' ? 'success' : ($temporal === 'Akan datang' ? 'primary' : 'secondary');
            ?>
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-2">
                            <span class="rounded-circle border" style="width:28px;height:28px;background:<?= esc($row['absent_color'] ?: '#fff') ?>"></span>
                            <div><div class="fw-semibold"><?= esc($row['absent_name']) ?></div><small class="text-muted">Cetak: <?= esc($row['absent_initial'] ?: '—') ?></small></div>
                        </div>
                    </td>
                    <td><div class="fw-semibold"><?= esc($row['substitute_name']) ?></div><small class="text-muted">Pelaksana fisik</small></td>
                    <td><div><?= date('d M Y', strtotime($row['effective_from'])) ?> – <?= date('d M Y', strtotime($row['effective_to'])) ?></div><small class="text-muted"><?= esc($row['period_name'] . ' · ' . $row['year_name']) ?></small></td>
                    <td><span class="badge bg-<?= $badge ?>"><?= esc($temporal) ?></span></td>
                    <td style="min-width:240px">
                        <?php $repair = $repair_candidates[(int) $row['id']] ?? null; ?>
                        <?php if (!$repair): ?><span class="text-muted small">Belum dianalisis</span>
                        <?php elseif ($repair['status'] === 'SAFE'): ?><span class="badge bg-success-subtle text-success">Aman · 0 perubahan</span>
                        <?php elseif ($repair['status'] === 'APPLIED'): ?><span class="badge bg-success">Diterapkan · <?= (int) $repair['moved_entry_count'] ?> slot</span>
                        <?php elseif ($repair['status'] === 'READY'): ?><button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#repairPreview<?= (int) $row['id'] ?>">Pratinjau <?= (int) $repair['moved_entry_count'] ?> perubahan</button>
                        <?php else: ?><span class="badge bg-secondary"><?= esc($repair['status']) ?></span><?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                        <?php if ($can_manage): ?>
                            <div class="d-inline-flex gap-1">
                                <?php if ($isActive): ?>
                                <form method="post" action="<?= base_url('schedules/substitutions/' . (int) $row['id'] . '/repair/analyze') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-success" title="Analisis dan cari perbaikan aman"><i data-lucide="wand-sparkles"></i></button></form>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editSubstitution<?= (int) $row['id'] ?>" title="Edit"><i data-lucide="pencil"></i></button>
                                <form method="post" action="<?= base_url('schedules/substitutions/' . (int) $row['id'] . '/toggle') ?>" data-confirm="<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?> aturan substitusi ini?" data-confirm-title="Ubah status substitusi?" data-confirm-button="Ya, lanjutkan">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-<?= $isActive ? 'danger' : 'success' ?>" title="<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?>"><i data-lucide="<?= $isActive ? 'pause' : 'play' ?>"></i></button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($can_manage): ?>
<div class="modal fade" id="createSubstitutionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content border-0 rounded-4">
        <form method="post" action="<?= base_url('schedules/substitutions') ?>" class="substitution-form">
            <?= csrf_field() ?>
            <div class="modal-header"><h5 class="modal-title fw-bold">Tambah Substitusi Guru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4"><?= view('schedules/substitutions/_form', ['periods' => $periods, 'teachers' => $teachers, 'periodOptions' => $periodOptions, 'teacherOptions' => $teacherOptions, 'row' => []]) ?></div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Substitusi</button></div>
        </form>
    </div></div>
</div>

<?php foreach ($substitutions as $row): ?>
<div class="modal fade" id="editSubstitution<?= (int) $row['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content border-0 rounded-4">
        <form method="post" action="<?= base_url('schedules/substitutions/' . (int) $row['id']) ?>" class="substitution-form">
            <?= csrf_field() ?>
            <div class="modal-header"><h5 class="modal-title fw-bold">Edit Substitusi Guru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4"><?= view('schedules/substitutions/_form', ['periods' => $periods, 'teachers' => $teachers, 'periodOptions' => $periodOptions, 'teacherOptions' => $teacherOptions, 'row' => $row]) ?></div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan Perubahan</button></div>
        </form>
    </div></div>
</div>
<?php $repair = $repair_candidates[(int) $row['id']] ?? null; if ($repair && $repair['status'] === 'READY'): ?>
<div class="modal fade" id="repairPreview<?= (int) $row['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content border-0 rounded-4">
        <div class="modal-header"><div><h5 class="modal-title fw-bold">Pratinjau Auto Repair</h5><div class="small text-muted"><?= esc($row['absent_name']) ?> → <?= esc($row['substitute_name']) ?></div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body p-4">
            <div class="alert alert-success">Konflik kritis: <strong><?= (int) $repair['critical_before'] ?></strong> → <strong><?= (int) $repair['critical_after'] ?></strong>. Nama, inisial, warna, guru pemilik, mapel, kelas, dan total JP tidak berubah.</div>
            <div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Kelas</th><th>Mapel / guru tampil</th><th>Dari</th><th>Ke</th></tr></thead><tbody>
            <?php foreach ($repair['changes'] as $change): ?><tr><td><?= esc($change['class_name']) ?></td><td><strong><?= esc($change['subject_name']) ?></strong><br><small class="text-muted"><?= esc($change['teacher_name']) ?></small></td><td><?= esc($change['from_label']) ?></td><td><strong><?= esc($change['to_label']) ?></strong></td></tr><?php endforeach; ?>
            </tbody></table></div>
            <p class="small text-muted mb-0">Penerapan mengunci versi terkait, memeriksa revisi dan entri terkunci, lalu menjalankan audit final. Kegagalan apa pun membatalkan seluruh perubahan.</p>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><form method="post" action="<?= base_url('schedules/substitutions/' . (int) $row['id'] . '/repair/' . (int) $repair['id'] . '/apply') ?>" data-confirm="Terapkan seluruh perubahan Auto Repair?" data-confirm-title="Terapkan kandidat?" data-confirm-button="Ya, terapkan"><?= csrf_field() ?><button class="btn btn-success"><i data-lucide="check-check" class="me-1"></i>Terapkan Atomik</button></form></div>
    </div></div>
</div>
<?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.substitution-form').forEach(function (form) {
        const period = form.querySelector('[name="academic_period_id"]');
        const from = form.querySelector('[name="effective_from"]');
        const to = form.querySelector('[name="effective_to"]');
        function syncBounds() {
            const option = period.options[period.selectedIndex];
            if (!option || !option.dataset.start) return;
            from.min = to.min = option.dataset.start;
            from.max = to.max = option.dataset.end;
            if (!from.value) from.value = option.dataset.start;
            if (!to.value) to.value = option.dataset.end;
        }
        period.addEventListener('change', syncBounds);
        syncBounds();
    });
});
</script>
<?= $this->endSection() ?>
