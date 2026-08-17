<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?= view('ksp/_header', compact('version')) ?>

<?php if($canManage): ?>
<div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body">
    <h5 class="fw-bold">Catat evaluasi periodik</h5>
    <form method="post" action="<?= base_url('education/ksp/'.$version['uuid'].'/evaluation') ?>" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-3"><input class="form-control" name="evaluation_period" placeholder="Semester / bulan" required></div>
        <div class="col-md-6"><input class="form-control" name="objective" placeholder="Objek evaluasi" required></div>
        <div class="col-md-3"><input class="form-control" name="target_value" placeholder="Target"></div>
        <div class="col-md-6"><textarea class="form-control" name="finding" placeholder="Temuan berbasis bukti"></textarea></div>
        <div class="col-md-6"><textarea class="form-control" name="root_cause" placeholder="Akar masalah"></textarea></div>
        <div class="col-12"><button class="btn btn-primary">Simpan evaluasi</button></div>
    </form>
</div></div>
<?php endif ?>

<div class="card border-0 shadow-sm rounded-4 mb-4"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th class="ps-4">Periode</th><th>Objek</th><th>Target</th><th>Temuan</th><th>Akar masalah</th><th>Status</th></tr></thead>
    <tbody><?php foreach($rows as $row): ?><tr><td class="ps-4"><?= esc($row['evaluation_period']) ?></td><td><?= esc($row['objective']) ?></td><td><?= esc($row['target_value'] ?: '-') ?></td><td><?= esc($row['finding'] ?: '-') ?></td><td><?= esc($row['root_cause'] ?: '-') ?></td><td><?= esc($row['status']) ?></td></tr><?php endforeach ?><?php if(empty($rows)): ?><tr><td colspan="6" class="text-center text-muted p-5">Belum ada evaluasi KSP.</td></tr><?php endif ?></tbody>
</table></div></div>

<?php if($canManage && !empty($rows)): ?>
<div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body">
    <h5 class="fw-bold">Tambah tindak lanjut perbaikan</h5>
    <form method="post" id="actionForm" class="row g-3" action="<?= base_url('education/ksp/'.$version['uuid'].'/evaluation/'.$rows[0]['uuid'].'/actions') ?>">
        <?= csrf_field() ?>
        <div class="col-md-4"><label class="form-label">Evaluasi</label><select class="form-select" id="evaluationSelector"><?php foreach($rows as $row): ?><option value="<?= esc($row['uuid']) ?>"><?= esc($row['objective']) ?></option><?php endforeach ?></select></div>
        <div class="col-md-8"><label class="form-label">Action</label><input class="form-control" name="title" required></div>
        <div class="col-md-6"><label class="form-label">Deskripsi</label><textarea class="form-control" name="description"></textarea></div>
        <div class="col-md-3"><label class="form-label">Owner role</label><input class="form-control" name="owner_role_code" value="wakasek_kurikulum"></div>
        <div class="col-md-3"><label class="form-label">Due date</label><input class="form-control" type="date" name="due_date" required></div>
        <div class="col-md-8"><label class="form-label">Success indicator</label><input class="form-control" name="success_indicator" required></div>
        <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status"><option>OPEN</option><option>IN_PROGRESS</option><option>COMPLETED</option><option>CANCELLED</option></select></div>
        <div class="col-12"><button class="btn btn-primary">Simpan tindak lanjut</button></div>
    </form>
</div></div>
<script>document.getElementById('evaluationSelector')?.addEventListener('change',function(){const form=document.getElementById('actionForm');form.action=<?= json_encode(base_url('education/ksp/'.$version['uuid'].'/evaluation/')) ?>+encodeURIComponent(this.value)+'/actions';});</script>
<?php endif ?>

<div class="card border-0 shadow-sm rounded-4"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th class="ps-4">Action</th><th>Owner</th><th>Due</th><th>Success indicator</th><th>Status</th><th>Update</th></tr></thead>
    <tbody><?php foreach($actions as $action): ?><tr><td class="ps-4"><div class="fw-semibold"><?= esc($action['title']) ?></div><small class="text-muted"><?= esc($action['evaluation_objective']) ?></small></td><td><?= esc($action['owner_name'] ?: ucwords(str_replace('_',' ',$action['owner_role_code'] ?: '-'))) ?></td><td><?= esc($action['due_date'] ?: '-') ?></td><td><?= esc($action['success_indicator'] ?: '-') ?></td><td><span class="badge text-bg-light"><?= esc($action['status']) ?></span></td><td><?php if($canManage): ?><form method="post" action="<?= base_url('education/ksp/'.$version['uuid'].'/improvement-actions/'.$action['uuid']) ?>" class="d-flex gap-2"><?= csrf_field() ?><input type="hidden" name="revision_number" value="<?= $action['revision_number'] ?>"><select class="form-select form-select-sm" name="status"><?php foreach(['OPEN','IN_PROGRESS','COMPLETED','CANCELLED'] as $status): ?><option <?= $action['status']===$status?'selected':'' ?>><?= $status ?></option><?php endforeach ?></select><button class="btn btn-sm btn-outline-primary">Simpan</button></form><?php else: ?>—<?php endif ?></td></tr><?php endforeach ?><?php if(empty($actions)): ?><tr><td colspan="6" class="text-center text-muted p-5">Belum ada tindak lanjut perbaikan.</td></tr><?php endif ?></tbody>
</table></div></div>
<?= $this->endSection() ?>
