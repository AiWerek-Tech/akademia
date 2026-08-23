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
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="<?= base_url('quality') ?>">Kualitas</a></li><li class="breadcrumb-item active">Refleksi</li></ol></nav>
            <h1 class="h3 fw-bold text-gray-900 mb-0">Refleksi Guru</h1>
        </div>
        <?php if (has_permission('teacher_reflection.manage')): ?>
            <a href="<?= base_url('quality/reflection/create') ?>" class="btn btn-primary shadow-sm rounded-pill px-3"><i data-lucide="plus" class="w-4 h-4 me-1"></i> Buat Refleksi</a>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= base_url('quality/reflections') ?>" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Guru</label>
                    <select name="teacher_id" class="form-select form-select-sm shadow-sm">
                        <option value="">Semua Guru</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= ($filters['teacher_id'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= esc($t['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm shadow-sm">
                        <option value="">Semua</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-outline-primary shadow-sm w-100">Filter</button>
                    <a href="<?= base_url('quality/reflections') ?>" class="btn btn-sm btn-outline-secondary shadow-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <?php if (empty($reflections)): ?>
                <div class="text-center py-5">
                    <i data-lucide="brain" class="text-muted mb-3" style="width:48px;height:48px"></i>
                    <p class="text-muted mb-0">Belum ada refleksi.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Guru</th>
                                <th>Mata Pelajaran</th>
                                <th>Tipe</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">AI</th>
                                <th class="text-end pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reflections as $r): ?>
                                <tr>
                                    <td class="ps-3 fw-semibold"><?= esc($r['teacher_name'] ?? '—') ?></td>
                                    <td><?= esc($r['subject_name'] ?? '—') ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= $r['reflection_type'] ?></span></td>
                                    <td class="text-center">
                                        <?php $sc = $r['status'] === 'PUBLISHED' ? 'success' : 'secondary'; ?>
                                        <span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?> rounded-pill"><?= $r['status'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($r['ai_status'] !== 'NONE'): ?>
                                            <i data-lucide="bot" class="text-info" style="width:16px;height:16px"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="<?= base_url('quality/reflections/' . $r['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">Detail</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
