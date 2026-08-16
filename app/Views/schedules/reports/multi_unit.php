<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jadwal Master SMP-SMA T.A. <?= esc($context['year_name'] ?? '2026/2027') ?></title>
    <style>
        @page { size: 215mm 330mm; margin: 1.5mm; }
        * { box-sizing: border-box; }
        :root { --ink: #050505; --grid: #151515; --paper: #fff; --muted: #eef1f4; --class: #fff2c8; }
        html, body { margin: 0; padding: 0; background: #e9edf2; color: var(--ink); font-family: Arial, Helvetica, sans-serif; }
        body { padding: 12px; }
        .toolbar { max-width: 215mm; margin: 0 auto 10px; display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 9px 12px; background: #fff; border: 1px solid #d7dde5; border-radius: 8px; box-shadow: 0 3px 12px rgba(15, 23, 42, .08); }
        .toolbar strong { font-size: 13px; color: #172033; }
        .toolbar span { display: block; margin-top: 2px; font-size: 11px; color: #64748b; }
        .toolbar button { border: 0; border-radius: 6px; background: #1d4ed8; color: #fff; padding: 8px 14px; font-size: 12px; font-weight: 800; cursor: pointer; }
        .sheet { width: 215mm; min-height: 330mm; margin: 0 auto; padding: 1.5mm; background: var(--paper); box-shadow: 0 8px 28px rgba(15, 23, 42, .18); overflow: hidden; }
        .header-kop { position: relative; text-align: center; height: 14.5mm; border-bottom: .55mm solid var(--ink); padding: .3mm 15mm 0; }
        .header-logo { position: absolute; top: .5mm; width: 12mm; height: 12mm; display: flex; align-items: center; justify-content: center; }
        .header-logo.left { left: 1.2mm; }
        .header-logo.right { right: 1.2mm; }
        .header-logo img { display: block; max-width: 100%; max-height: 100%; object-fit: contain; }
        .header-kop .line-1 { margin: 0; font-size: 6.2pt; line-height: 1.05; font-weight: 800; letter-spacing: .15mm; text-transform: uppercase; }
        .header-kop .line-2 { margin: .45mm 0 0; font-size: 8.1pt; line-height: 1.02; font-weight: 900; letter-spacing: .1mm; text-transform: uppercase; }
        .header-kop .line-3 { margin: .35mm 0 0; font-size: 9pt; line-height: 1.02; font-weight: 900; text-transform: uppercase; }
        .header-kop .line-4 { margin: .55mm 0 0; font-size: 4.4pt; line-height: 1; font-style: italic; }
        .title-wrap { height: 6mm; display: flex; align-items: center; justify-content: center; }
        .title-box { min-width: 72mm; padding: .75mm 4mm; border: .35mm solid var(--ink); font-size: 6.5pt; line-height: 1; font-weight: 900; text-align: center; text-transform: uppercase; letter-spacing: .12mm; }
        table { border-collapse: collapse; }
        .master-grid { width: 100%; table-layout: fixed; border: .55mm solid var(--ink); font-size: 4.8pt; line-height: .98; }
        .master-grid th, .master-grid td { height: 3.75mm; border: .22mm solid var(--grid); padding: .14mm .2mm; text-align: center; vertical-align: middle; overflow: hidden; white-space: nowrap; text-overflow: clip; }
        .master-grid thead th { height: 3.75mm; background: #d8d8d8; font-size: 4.75pt; font-weight: 900; text-transform: uppercase; }
        .master-grid .unit-head { background: #c9c9c9; font-size: 5.15pt; }
        .master-grid .class-head { background: var(--class); font-size: 4.6pt; }
        .master-grid .unit-start { border-left-width: .65mm !important; }
        .master-grid .dress-start { border-left-width: .65mm !important; }
        .master-grid .day-start > * { border-top-width: .85mm !important; }
        .master-grid .day-end > * { border-bottom-width: .85mm !important; }
        .day-cell { background: #f2f3f5; font-size: 5.5pt !important; font-weight: 900; writing-mode: vertical-rl; transform: rotate(180deg); letter-spacing: .2mm; }
        .jp-cell { background: #fafafa; font-weight: 900; }
        .period-cell { font-size: 4.15pt; font-weight: 700; }
        .routine-row td { height: 3mm; padding: .1mm .15mm; background: #fff; font-size: 4.2pt; font-weight: 800; letter-spacing: .02mm; }
        .routine-row.major td { background: #242424; color: #fff; font-style: italic; }
        .routine-row.break td { background: #fff; color: #000; font-weight: 900; }
        .routine-row.end td { font-size: 4.05pt; font-weight: 900; }
        .subject-cell { font-size: 4.55pt; font-weight: 900; letter-spacing: -.02mm; }
        .empty-cell { color: #cbd0d6; background: #fff; }
        .dress-cell { background: #fff; font-size: 4.5pt; font-weight: 800; writing-mode: vertical-rl; transform: rotate(180deg); letter-spacing: .08mm; }
        .legend-title { margin: 1.05mm 0 .5mm; font-size: 5pt; line-height: 1; font-weight: 900; text-align: center; text-transform: uppercase; letter-spacing: .15mm; }
        .legend-wrap { display: grid; grid-template-columns: 1.06fr .94fr; gap: 1mm; margin-top: 1mm; }
        .legend-panel { min-width: 0; border: .35mm solid var(--ink); background: #f6f7f9; padding: .45mm; }
        .legend-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .28mm .45mm; }
        .teacher-item, .subject-item { min-width: 0; height: 2.9mm; display: flex; align-items: center; gap: .45mm; padding: .22mm .45mm .22mm .3mm; border: .14mm solid #b8bec7; border-radius: .6mm; background: #fff; }
        .teacher-swatch { flex: 0 0 2.1mm; width: 2.1mm; height: 2.1mm; border: .15mm solid rgba(0,0,0,.35); border-radius: 50%; }
        .legend-number { font-size: 3.5pt; color: #555; min-width: 3.1mm; text-align: right; font-weight: 800; }
        .teacher-code, .subject-code { flex: 0 0 auto; min-width: 5mm; font-size: 3.9pt; line-height: 1; font-weight: 900; }
        .teacher-name, .subject-name { min-width: 0; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; font-size: 3.85pt; line-height: 1; font-weight: 800; text-transform: uppercase; }
        .print-note { margin-top: .55mm; font-size: 3.55pt; text-align: right; color: #444; }
        @media print {
            html, body { width: 215mm; height: 330mm; background: #fff !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            body { padding: 0; }
            .toolbar { display: none !important; }
            .sheet { width: 212mm; min-height: 0; height: auto; margin: 0; padding: 0; box-shadow: none; overflow: visible; }
        }
    </style>
</head>
<body>
<?php
$classrooms = $grid['classrooms'] ?? [];
$days = $grid['days'] ?? [];
$slots = $grid['slots'] ?? [];
$matrix = $grid['matrix'] ?? [];
$fixedMatrix = $grid['fixed_matrix'] ?? [];
$teachers = $grid['teachers'] ?? [];
$subjects = $grid['subjects'] ?? [];
$routineActivities = $grid['routine_activities'] ?? [];
$versionIdsByUnit = $grid['version_ids_by_unit'] ?? [];

$smpClasses = [];
$smaClasses = [];
foreach ($classrooms as $classroom) {
    $unitCode = strtoupper((string) ($classroom['unit_code'] ?? ''));
    $unitName = strtoupper((string) ($classroom['unit_name'] ?? ''));
    if (str_contains($unitCode, 'SMP') || str_contains($unitName, 'SMP')) {
        $smpClasses[] = $classroom;
    } else {
        $smaClasses[] = $classroom;
    }
}
$allClasses = array_merge($smpClasses, $smaClasses);
$dressCodes = [
    'SENIN' => ['student' => 'PUTIH BIRU / ABU-ABU', 'teacher' => 'KEKI'],
    'SELASA' => ['student' => 'SERAGAM PATHFINDER', 'teacher' => 'SERAGAM PATHFINDER'],
    'RABU' => ['student' => 'PUTIH BIRU / ABU-ABU', 'teacher' => 'PUTIH / HITAM'],
    'KAMIS' => ['student' => 'BATIK WMVAA', 'teacher' => 'BATIK WMVAA'],
    'JUMAT' => ['student' => 'KOSTUM WMVAA', 'teacher' => 'KOSTUM WMVAA'],
];
$dayById = [];
$canonicalDays = [];
foreach ($days as $day) {
    $dayId = (int) $day['id'];
    $dayOrder = (int) $day['day_of_week'];
    $dayById[$dayId] = $day;
    if (!isset($canonicalDays[$dayOrder])) {
        $canonicalDays[$dayOrder] = $day;
    }
}
ksort($canonicalDays);

$slotsByVersionAndDay = [];
$displaySlots = [];
$maxSlot = 0;
foreach ($slots as $slot) {
    $day = $dayById[(int) $slot['day_id']] ?? null;
    if (!$day) {
        continue;
    }
    $dayOrder = (int) $day['day_of_week'];
    $slotNumber = (int) $slot['slot_number'];
    $versionId = (int) $slot['schedule_version_id'];
    $slotsByVersionAndDay[$versionId][$dayOrder][$slotNumber] = $slot;
    $displaySlots[$dayOrder][$slotNumber] ??= $slot;
    $maxSlot = max($maxSlot, $slotNumber);
}
$breakRoutines = array_values(array_filter($routineActivities, static fn(array $routine): bool =>
    ($routine['placement_zone'] ?? '') === 'INTERMISSION_BREAK' && (int) ($routine['is_locked_slot'] ?? 0) === 1
));
$hasBreak = $breakRoutines !== [];
$breakAfterSlot = $hasBreak ? max(1, (int) ($breakRoutines[0]['placement_sequence'] ?? 5)) : $maxSlot;
$breakLabel = $hasBreak ? implode(' / ', array_column($breakRoutines, 'name')) : 'ISTIRAHAT';
$postAcademicRoutines = array_values(array_filter($routineActivities, static fn(array $routine): bool =>
    ($routine['placement_zone'] ?? '') === 'POST_ACADEMIC' && (int) ($routine['is_locked_slot'] ?? 0) === 1
));
usort($postAcademicRoutines, static fn(array $left, array $right): int =>
    ((int) ($left['placement_sequence'] ?? 0)) <=> ((int) ($right['placement_sequence'] ?? 0))
);
if ($postAcademicRoutines === []) {
    $postAcademicRoutines = [
        ['name' => 'ABSEN SIANG & PULANG', 'duration_minutes' => 10],
        ['name' => "TEACHERS' AFTERNOON WORSHIP", 'duration_minutes' => 15],
    ];
}

$compactClassName = static function (string $name): string {
    $value = strtoupper(trim($name));
    $value = preg_replace('/^KELAS\s+/u', 'KLS ', $value) ?: $value;
    return $value;
};
$cellStyle = static function (?string $color): array {
    $background = $color && preg_match('/^#[0-9a-f]{6}$/i', $color) ? $color : '#ffffff';
    $hex = ltrim($background, '#');
    $red = hexdec(substr($hex, 0, 2));
    $green = hexdec(substr($hex, 2, 2));
    $blue = hexdec(substr($hex, 4, 2));
    $foreground = (($red * .299 + $green * .587 + $blue * .114) > 150) ? '#000000' : '#ffffff';
    return [$background, $foreground];
};
$periodAfter = static function (string $startTime, int $minutes): array {
    $start = \DateTimeImmutable::createFromFormat('H:i:s', strlen($startTime) === 5 ? $startTime . ':00' : $startTime);
    if (! $start) {
        $start = new \DateTimeImmutable('13:45:00');
    }
    $end = $start->modify('+' . max(1, $minutes) . ' minutes');
    return [$start->format('H.i'), $end->format('H.i'), $end->format('H:i:s')];
};
$configuredLogoPath = trim((string) ($context['logo_path'] ?? ''));
$headerLogoUrl = base_url($configuredLogoPath !== '' ? ltrim($configuredLogoPath, '/') : 'assets/img/brand-mark.svg');
$configuredRightLogoPath = trim((string) ($context['logo_right_path'] ?? ''));
$headerRightLogoUrl = base_url($configuredRightLogoPath !== '' ? ltrim($configuredRightLogoPath, '/') : 'assets/img/brand-mark.svg');
?>

<div class="toolbar">
    <div><strong>Preview Jadwal Master SMP-SMA</strong><span>F4 portrait · satu lembar · margin 1,5 mm</span></div>
    <button type="button" onclick="window.print()">Cetak Master F4</button>
</div>

<main class="sheet">
    <header class="header-kop">
        <div class="header-logo left"><img src="<?= esc($headerLogoUrl) ?>" alt="Logo kiri"></div>
        <div class="header-logo right"><img src="<?= esc($headerRightLogoUrl) ?>" alt="Logo kanan"></div>
        <div class="line-1"><?= esc($context['header_line_1'] ?? 'YAYASAN PENDIDIKAN ADVENT PAPUA') ?></div>
        <div class="line-2"><?= esc($context['header_line_2'] ?? 'WAMENA MOUNTAIN VIEW ADVENTIST ACADEMY') ?></div>
        <div class="line-3"><?= esc($context['header_line_3'] ?? 'SMP-SMA ADVENT SOGOKMO') ?></div>
        <div class="line-4"><?= esc($context['header_line_4'] ?? 'Jalan Wamena - Kurima, Desa Sogokmo, Distrik Asotipo, Kabupaten Jayawijaya - Papua') ?></div>
    </header>
    <div class="title-wrap"><div class="title-box">JADWAL PELAJARAN T.A. <?= esc($context['year_name'] ?? '2026/2027') ?></div></div>

    <table class="master-grid">
        <colgroup>
            <col style="width:4mm"><col style="width:5mm"><col style="width:14mm">
            <?php foreach ($allClasses as $_): ?><col><?php endforeach; ?>
            <col style="width:12mm"><col style="width:11mm">
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2">HARI</th><th rowspan="2">JP</th><th rowspan="2">PERIODE</th>
                <?php if ($smpClasses !== []): ?><th colspan="<?= count($smpClasses) ?>" class="unit-head">SMP</th><?php endif; ?>
                <?php if ($smaClasses !== []): ?><th colspan="<?= count($smaClasses) ?>" class="unit-head unit-start">SMA</th><?php endif; ?>
                <th colspan="2" class="dress-start">DRESS CODE</th>
            </tr>
            <tr>
                <?php foreach ($allClasses as $index => $classroom): ?>
                    <th class="class-head <?= $index === count($smpClasses) ? 'unit-start' : '' ?>"><?= esc($compactClassName((string) $classroom['name'])) ?></th>
                <?php endforeach; ?>
                <th class="dress-start">STUDENT</th><th>TEACHER</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($canonicalDays as $dayOrder => $day): ?>
            <?php
            $dayName = strtoupper((string) $day['day_name']);
            $dress = $dressCodes[$dayName] ?? ['student' => '-', 'teacher' => '-'];
            $dayMaxSlot = $dayName === 'JUMAT' ? min(7, $maxSlot) : $maxSlot;
            $lastAcademicSlot = $displaySlots[$dayOrder][$dayMaxSlot] ?? null;
            $postAcademicStart = (string) ($lastAcademicSlot['end_time'] ?? ($dayName === 'JUMAT' ? '12:25:00' : '13:45:00'));
            $postAcademicPeriods = [];
            foreach ($postAcademicRoutines as $postRoutine) {
                [$periodStart, $periodEnd, $nextStart] = $periodAfter($postAcademicStart, (int) ($postRoutine['duration_minutes'] ?? 10));
                $postAcademicPeriods[] = [
                    'label' => strtoupper((string) ($postRoutine['name'] ?? 'KEGIATAN PENUTUP')),
                    'period' => $periodStart . ' - ' . $periodEnd,
                ];
                $postAcademicStart = $nextStart;
            }
            $rowspan = 3 + $dayMaxSlot + ($hasBreak && $breakAfterSlot <= $dayMaxSlot ? 1 : 0) + count($postAcademicPeriods);
            ?>
            <tr class="routine-row day-start">
                <th class="day-cell" rowspan="<?= $rowspan ?>"><?= esc($dayName) ?></th>
                <td class="jp-cell">#</td><td class="period-cell">06.30 - 07.00</td>
                <td colspan="<?= count($smpClasses) ?>">TEACHERS' MORNING WORSHIP</td>
                <td colspan="<?= count($smaClasses) ?>" class="unit-start">TEACHERS' MORNING WORSHIP</td>
                <td rowspan="<?= $rowspan ?>" class="dress-cell dress-start"><?= esc($dress['student']) ?></td>
                <td rowspan="<?= $rowspan ?>" class="dress-cell"><?= esc($dress['teacher']) ?></td>
            </tr>
            <tr class="routine-row"><td class="jp-cell">#</td><td class="period-cell">07.00 - 07.20</td><td colspan="<?= count($smpClasses) ?>">777 PRAYER &amp; FOLLOW THE BIBLE</td><td colspan="<?= count($smaClasses) ?>" class="unit-start">777 PRAYER &amp; FOLLOW THE BIBLE</td></tr>
            <tr class="routine-row"><td class="jp-cell">#</td><td class="period-cell">07.20 - 07.30</td><td colspan="<?= count($smpClasses) ?>">APEL &amp; ABSEN PAGI</td><td colspan="<?= count($smaClasses) ?>" class="unit-start">APEL &amp; ABSEN PAGI</td></tr>

            <?php for ($slotNumber = 1; $slotNumber <= $dayMaxSlot; $slotNumber++): ?>
                <?php $displaySlot = $displaySlots[$dayOrder][$slotNumber] ?? null; ?>
                <tr>
                    <td class="jp-cell"><?= $slotNumber ?></td>
                    <td class="period-cell"><?= $displaySlot ? esc(substr($displaySlot['start_time'], 0, 5) . ' - ' . substr($displaySlot['end_time'], 0, 5)) : '-' ?></td>
                    <?php foreach ($allClasses as $index => $classroom): ?>
                        <?php
                        $classId = (int) $classroom['id'];
                        $versionId = (int) ($versionIdsByUnit[(int) $classroom['unit_id']] ?? 0);
                        $classSlot = $slotsByVersionAndDay[$versionId][$dayOrder][$slotNumber] ?? null;
                        $slotId = $classSlot ? (int) $classSlot['id'] : 0;
                        $fixed = $fixedMatrix[$classId][$slotId] ?? null;
                        $entry = $matrix[$classId][$slotId] ?? null;
                        $unitClass = $index === count($smpClasses) ? ' unit-start' : '';
                        ?>
                        <?php if ($fixed): ?>
                            <?php [$background, $foreground] = $cellStyle($fixed['color_label'] ?? '#262626'); ?>
                            <td class="subject-cell<?= $unitClass ?>" style="background:<?= esc($background) ?>;color:<?= esc($foreground) ?>"><?= esc(strtoupper((string) ($fixed['title'] ?? 'RUTIN'))) ?></td>
                        <?php elseif ($entry): ?>
                            <?php
                            [$background, $foreground] = $cellStyle($entry['color_code'] ?? '#ffffff');
                            $subject = strtoupper((string) ($entry['subject_code'] ?: $entry['subject_name'] ?: 'RUTIN'));
                            $initial = strtoupper((string) ($entry['teacher_initial'] ?: ($entry['teacher_name'] ? substr($entry['teacher_name'], 0, 2) : '')));
                            ?>
                            <td class="subject-cell<?= $unitClass ?>" style="background:<?= esc($background) ?>;color:<?= esc($foreground) ?>"><?= esc($subject . ($initial !== '' ? '/' . $initial : '')) ?></td>
                        <?php else: ?>
                            <td class="empty-cell<?= $unitClass ?>">-</td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
                <?php if ($hasBreak && $slotNumber === $breakAfterSlot): ?>
                    <tr class="routine-row break"><td class="jp-cell">#</td><td class="period-cell">10.50 - 11.05</td><td colspan="<?= count($smpClasses) ?>"><?= esc(strtoupper($breakLabel)) ?></td><td colspan="<?= count($smaClasses) ?>" class="unit-start"><?= esc(strtoupper($breakLabel)) ?></td></tr>
                <?php endif; ?>
            <?php endfor; ?>

            <?php foreach ($postAcademicPeriods as $postIndex => $postPeriod): ?>
                <tr class="routine-row end <?= $postIndex === array_key_last($postAcademicPeriods) ? 'day-end' : '' ?>"><td class="jp-cell">#</td><td class="period-cell"><?= esc($postPeriod['period']) ?></td><td colspan="<?= count($smpClasses) ?>"><?= esc($postPeriod['label']) ?></td><td colspan="<?= count($smaClasses) ?>" class="unit-start"><?= esc($postPeriod['label']) ?></td></tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="legend-wrap">
        <section class="legend-panel">
            <div class="legend-title">INISIAL GURU (<?= count($teachers) ?>)</div>
            <div class="legend-list">
                <?php foreach ($teachers as $index => $teacher): ?>
                    <?php
                    [$background] = $cellStyle($teacher['color_code'] ?? '#ffffff');
                    $name = trim((string) $teacher['full_name'] . (!empty($teacher['degree_suffix']) ? ', ' . $teacher['degree_suffix'] : ''));
                    ?>
                    <div class="teacher-item"><span class="legend-number"><?= $index + 1 ?>.</span><span class="teacher-swatch" style="background:<?= esc($background) ?>"></span><span class="teacher-code"><?= esc(strtoupper((string) ($teacher['teacher_initial'] ?? ''))) ?></span><span class="teacher-name"><?= esc($name) ?></span></div>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="legend-panel">
            <div class="legend-title">INISIAL MATA PELAJARAN (<?= count($subjects) ?>)</div>
            <div class="legend-list">
                <?php foreach ($subjects as $index => $subject): ?>
                    <div class="subject-item"><span class="legend-number"><?= $index + 1 ?>.</span><span class="subject-code"><?= esc(strtoupper((string) ($subject['code'] ?: $subject['short_name']))) ?></span><span class="subject-name"><?= esc($subject['name']) ?></span></div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    <div class="print-note">Dicetak dari IALOS Education · <?= esc(date('d-m-Y H:i')) ?></div>
</main>
</body>
</html>
