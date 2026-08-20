<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-purple-subtle text-purple px-2.5 py-1.5 rounded-pill fw-semibold text-xs text-uppercase tracking-wider">
                    <i data-lucide="sigma" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Pengolahan Nilai Sumatif
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mt-2 mb-1">Pengolahan Nilai Sumatif</h1>
            <p class="text-muted mb-0">
                Ubah mastery TP menjadi nilai akhir per mapel sesuai kebijakan pelaporan. Hasil berstatus
                <span class="badge bg-warning-subtle text-warning rounded-pill">DRAFT</span> dan perlu
                <span class="badge bg-success-subtle text-success rounded-pill">VALIDATED</span> oleh guru/operator sebelum dianggap final.
            </p>
        </div>
        <form method="POST" action="<?= base_url('summative/process') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary shadow-sm" onclick="return confirm('Proses semua mapel pada periode aktif? Hasil yang sudah divalidasi tidak akan ditimpa.')">
                <i data-lucide="play" class="w-4 h-4 me-1"></i> Proses Semua Mapel
            </button>
        </form>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                    <tr>
                        <th class="px-3 py-3">Mata Pelajaran</th>
                        <th class="px-3 py-3 text-center">Siswa Diproses</th>
                        <th class="px-3 py-3 text-center">Tervalidasi</th>
                        <th class="px-3 py-3">Terakhir Diproses</th>
                        <th class="px-3 py-3 text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($subjects === []): ?>
                        <tr><td colspan="5" class="text-center text-muted py-5">Belum ada mata pelajaran aktif.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($subjects as $subject): ?>
                        <?php $st = $status[(int) $subject['id']] ?? null; ?>
                        <tr>
                            <td class="px-3 py-3">
                                <div class="fw-semibold text-gray-900"><?= esc($subject['name']) ?></div>
                                <div class="text-xs text-muted"><?= esc($subject['code'] ?? '') ?></div>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="badge <?= $st ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' ?> rounded-pill"><?= $st ? (int) $st['total'] : 'Belum diproses' ?></span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <?php if ($st && (int) $st['validated'] > 0): ?>
                                    <span class="badge bg-success-subtle text-success rounded-pill"><?= (int) $st['validated'] ?> tervalidasi</span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-3 text-muted"><?= $st ? esc($st['processed_at']) : '—' ?></td>
                            <td class="px-3 py-3 text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="<?= base_url('summative/' . $subject['id']) ?>" class="btn btn-sm btn-outline-primary shadow-sm"><i data-lucide="eye" class="w-3.5 h-3.5 me-1"></i> Lihat</a>
                                    <form method="POST" action="<?= base_url('summative/process') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-primary shadow-sm"><i data-lucide="refresh-cw" class="w-3.5 h-3.5 me-1"></i> Proses</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>