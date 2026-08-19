<div class="card border-0 shadow-sm rounded-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><?php foreach($headers as $header): ?><th><?= esc($header) ?></th><?php endforeach ?></tr></thead>
            <tbody>
            <?php foreach($rows as $row): ?>
                <tr><?php foreach($row as $cell): ?><td><?= esc((string) $cell) ?></td><?php endforeach ?></tr>
            <?php endforeach ?>
            <?php if(empty($rows)): ?><tr><td colspan="<?= count($headers) ?>" class="text-center text-muted p-5">Belum ada data.</td></tr><?php endif ?>
            </tbody>
        </table>
    </div>
</div>
