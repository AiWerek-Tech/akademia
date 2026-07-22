<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 font-weight-bold text-gray-800">Editor Jadwal: <?= esc($version['name']) ?></h1>
        <p class="text-muted mb-0">Kode: <code><?= esc($version['code']) ?></code> | Status: <span class="badge badge-info"><?= esc($version['workflow_status']) ?></span> | Revisi: <strong>v<?= esc($version['revision_number']) ?></strong></p>
    </div>
    <div class="btn-group">
        <button class="btn btn-primary btn-sm shadow-sm" id="btnRunGenerator" data-version-id="<?= $version['id'] ?>">
            <i class="fas fa-magic mr-1"></i> Generate Otomatis
        </button>
        <button class="btn btn-warning btn-sm shadow-sm" id="btnAuditConflicts" data-version-id="<?= $version['id'] ?>">
            <i class="fas fa-shield-alt mr-1"></i> Audit Konflik
        </button>
    </div>
</div>

<div id="conflictAlert" class="alert alert-danger d-none shadow-sm mb-4" role="alert">
    <i class="fas fa-exclamation-triangle mr-2"></i> <span id="conflictMessage">Terdeteksi konflik pada jadwal.</span>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Kisi Penjadwalan Pelajaran (Timetable Grid)</h6>
        <div class="form-inline">
            <label class="mr-2 text-sm font-weight-bold">Tampilan:</label>
            <select class="form-control form-control-sm" id="viewFilter">
                <option value="class">Per Rombel/Kelas</option>
                <option value="teacher">Per Guru</option>
                <option value="room">Per Ruangan</option>
            </select>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered text-center align-middle" id="timetableGrid">
                <thead class="thead-dark">
                    <tr>
                        <th style="width: 100px;">Jam / Slot</th>
                        <th>Senin</th>
                        <th>Selasa</th>
                        <th>Rabu</th>
                        <th>Kamis</th>
                        <th>Jumat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($slot = 1; $slot <= 8; $slot++): ?>
                        <tr>
                            <th class="table-secondary font-weight-bold align-middle">Slot <?= $slot ?></th>
                            <?php for ($day = 1; $day <= 5; $day++): ?>
                                <td class="slot-dropzone p-2 align-middle" data-day="<?= $day ?>" data-slot="<?= $slot ?>" style="min-height: 70px; background-color: #fcfcfc;">
                                    <span class="text-muted small d-block">Kosong</span>
                                </td>
                            <?php endfor; ?>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
