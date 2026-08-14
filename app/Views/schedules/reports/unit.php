<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php
    // Format Unit Head Name (Kepala Unit / Kepala Sekolah Unit)
    $unitHeadRaw = rtrim(trim((string)($unit['head_name'] ?? $context['head_name'] ?? '')), ',');
    $unitHeadFormatted = !empty($unitHeadRaw) ? $unitHeadRaw : 'SARAY BARUSA, S.Pd';
    $dirName = !empty($director_name) ? $director_name : 'MARTHEN REFASI, S.Ag';

    // Indonesian Date Format
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $formattedDate = date('d') . ' ' . ($months[(int)date('n')] ?? date('F')) . ' ' . date('Y');
    ?>
    <title>Jadwal Pelajaran <?= esc($unit['name'] ?? 'Jenjang') ?></title>
    <style>
        @page {
            size: 215mm 330mm;
            margin: 3mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            background: #edf1f6;
            color: #0b1220;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 6.8pt;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .toolbar {
            max-width: 207mm;
            margin: 6px auto;
            padding: 6px 12px;
            background: #fff;
            border: 1px solid #d6dde8;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 8.5pt;
        }
        .toolbar button {
            border: 0;
            border-radius: 5px;
            background: #2457d6;
            color: #fff;
            padding: 6px 12px;
            font-weight: 800;
            cursor: pointer;
        }
        .page {
            width: 207mm;
            margin: 0 auto 8px;
            background: #fff;
            padding: 2.5mm 3mm;
            box-shadow: 0 8px 25px #1f293720;
        }
        .official-header {
            height: 18mm;
            position: relative;
            text-align: center;
            border-bottom: .65mm double #111827;
            padding: .3mm 12mm;
        }
        .official-logo {
            position: absolute;
            top: 0.5mm;
            width: 14mm;
            height: 14mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .official-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .logo-left { left: 1mm; }
        .logo-right { right: 1mm; }
        .kop-line {
            text-transform: uppercase;
            font-weight: 800;
            line-height: 1.05;
        }
        .kop-1 { font-size: 5.8pt; }
        .kop-2 { font-size: 7.8pt; margin-top: .2mm; }
        .kop-3 { font-size: 8.8pt; margin-top: .2mm; }
        .kop-4 { font-size: 4.5pt; font-style: italic; font-weight: 500; text-transform: none; margin-top: .4mm; }

        .title {
            text-align: center;
            margin: 1.5mm 0 1.5mm 0;
        }
        .title h1 {
            display: inline-block;
            margin: 0;
            border: .35mm solid #111827;
            border-radius: 0.8mm;
            padding: 0.6mm 5mm;
            font-size: 7.8pt;
            letter-spacing: .2mm;
            text-transform: uppercase;
        }

        .grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: .45mm solid #111827;
            font-size: 4.8pt;
        }
        .grid th, .grid td {
            border: .18mm solid #1c2533;
            height: 3.5mm;
            padding: .1mm .2mm;
            text-align: center;
            vertical-align: middle;
            overflow: hidden;
            white-space: nowrap;
        }
        .grid thead th {
            height: 4mm;
            background: #1e3a8a;
            color: #ffffff;
            text-transform: uppercase;
            font-weight: 900;
            font-size: 5.5pt;
        }
        .grid .day {
            width: 5.5mm;
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            font-size: 5.5pt;
            background: #f1f5f9;
            font-weight: 900;
            color: #0f172a;
        }
        .grid .jp {
            width: 6.5mm;
            font-weight: 900;
            background: #f8fafc;
            font-size: 5pt;
        }
        .grid .time {
            width: 17mm;
            font-family: Consolas, monospace;
            font-size: 4.2pt;
            background: #f8fafc;
            font-weight: 700;
        }
        .grid .dress {
            width: 11mm;
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            font-size: 4pt;
            font-weight: 800;
            background: #f8fafc;
            color: #1e293b;
        }
        .grid .class-head {
            background: #fef3c7 !important;
            color: #78350f !important;
            font-size: 5.8pt !important;
            font-weight: 900 !important;
        }
        .subject {
            font-weight: 900;
            font-size: 4.8pt;
        }
        .routine td {
            height: 2.8mm;
            background: #e2e8f0 !important;
            color: #0f172a !important;
            font-size: 4.5pt;
            font-weight: 800;
            letter-spacing: 0.1mm;
        }
        .day-start > * { border-top: 1.5px solid #334155 !important; }
        .day-end > * { border-bottom: 1.5px solid #334155 !important; }

        /* LEGEND PANELS */
        .legend-wrap {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5mm;
            margin-top: 2mm;
        }
        .legend-panel {
            border: .25mm solid #1e293b;
            border-radius: 0.8mm;
            background: #f8fafc;
            padding: 1mm;
            min-width: 0;
        }
        .legend-title {
            text-align: center;
            font-size: 5.2pt;
            font-weight: 900;
            margin-bottom: 0.6mm;
            padding-bottom: 0.4mm;
            border-bottom: 1px solid #cbd5e1;
            color: #0f172a;
            text-transform: uppercase;
        }
        .legend-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .3mm .8mm;
        }
        .legend-item {
            min-width: 0;
            height: 3.2mm;
            display: flex;
            align-items: center;
            gap: 0.6mm;
            background: #fff;
            border: .12mm solid #cbd5e1;
            border-radius: .5mm;
            padding: .1mm 0.6mm;
        }
        .swatch {
            width: 2.2mm;
            height: 2.2mm;
            border-radius: 50%;
            border: .1mm solid #475569;
            flex: none;
        }
        .num {
            font-size: 4pt;
            color: #64748b;
            min-width: 3mm;
            text-align: right;
            font-weight: 800;
        }
        .code {
            font-size: 4.5pt;
            font-weight: 900;
            min-width: 5.5mm;
            font-family: Consolas, monospace;
            color: #0f172a;
        }
        .name {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 4.5pt;
            font-weight: 800;
            text-transform: uppercase;
            color: #1e293b;
        }

        /* SIGNATURE SECTION */
        .signature-section {
            margin-top: 2.5mm;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 0 4mm;
        }
        .sig-box {
            text-align: center;
            width: 60mm;
            font-size: 6pt;
        }
        .sig-title {
            color: #334155;
            margin-bottom: 0.3mm;
        }
        .sig-role {
            font-weight: 700;
            color: #0f172a;
        }
        .sig-space {
            height: 8mm;
        }
        .sig-name {
            font-weight: 800;
            text-decoration: underline;
            color: #0f172a;
            font-size: 6.5pt;
        }

        .foot {
            display: flex;
            justify-content: space-between;
            margin-top: 2mm;
            font-size: 5pt;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 0.6mm;
        }
        .status {
            font-weight: 900;
            color: #1f3b73;
        }

        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .page { width: auto; margin: 0; box-shadow: none; padding: 0; }
            .grid tr { break-inside: avoid; }
            .legend-wrap, .signature-section { break-inside: avoid; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <strong>Preview Jadwal Per Jenjang / Unit Sekolah</strong>
    <button onclick="window.print()">Cetak F4 Portrait</button>
</div>
<main class="page">
    <?= view('schedules/reports/_print_header', ['context' => $context, 'unit' => $unit]) ?>

    <div class="title">
        <h1>JADWAL PELAJARAN <?= esc(strtoupper($unit['name'] ?? 'JENJANG')) ?> - T.A. <?= esc($context['year_name'] ?? '') ?></h1>
    </div>

    <?php
    $classes = $grid['classrooms'] ?? [];
    $days = $grid['days'] ?? [];
    $slotsByDay = [];
    foreach (($grid['slots'] ?? []) as $slot) {
        $slotsByDay[(int) $slot['day_id']][(int) $slot['slot_number']] = $slot;
    }
    $dress = [
        'SENIN'  => ['PUTIH BIRU / ABU-ABU', 'KEKI'],
        'SELASA' => ['SERAGAM PATHFINDER', 'SERAGAM PATHFINDER'],
        'RABU'   => ['PUTIH BIRU / ABU-ABU', 'PUTIH / HITAM'],
        'KAMIS'  => ['BATIK WMVAA', 'BATIK WMVAA'],
        'JUMAT'  => ['KOSTUM WMVAA', 'KOSTUM WMVAA'],
    ];
    $color = static function (?string $hex): array {
        $hex = preg_match('/^#[0-9a-f]{6}$/i', (string) $hex) ? (string) $hex : '#ffffff';
        $r = hexdec(substr($hex, 1, 2));
        $g = hexdec(substr($hex, 3, 2));
        $b = hexdec(substr($hex, 5, 2));
        return [$hex, (($r * .299 + $g * .587 + $b * .114) > 155 ? '#000' : '#fff')];
    };
    ?>

    <!-- MAIN UNIT MASTER SCHEDULE GRID -->
    <table class="grid">
        <thead>
            <tr>
                <th style="width: 5.5mm;">Hari</th>
                <th style="width: 6.5mm;">JP</th>
                <th style="width: 17mm;">Periode</th>
                <th colspan="<?= count($classes) ?>"><?= esc(strtoupper($unit['name'] ?? 'JENJANG')) ?></th>
                <th colspan="2" style="width: 22mm;">Dress Code</th>
            </tr>
            <tr>
                <th colspan="3"></th>
                <?php foreach ($classes as $class): ?>
                    <th class="class-head"><?= esc($class['name']) ?></th>
                <?php endforeach; ?>
                <th style="background:#1e3a8a; width:11mm;">Siswa</th>
                <th style="background:#1e3a8a; width:11mm;">Guru</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($days as $day): ?>
                <?php
                $dayName = strtoupper((string) $day['day_name']);
                $daySlots = $slotsByDay[(int) $day['id']] ?? [];
                ksort($daySlots);
                $dayDress = $dress[$dayName] ?? ['-', '-'];
                $rowspan = count($daySlots) + 2;
                ?>
                <tr class="routine day-start">
                    <th class="day" rowspan="<?= $rowspan ?>"><?= esc($dayName) ?></th>
                    <td class="jp">#</td>
                    <td class="time">06:30-07:30</td>
                    <td colspan="<?= count($classes) ?>">TEACHERS' WORSHIP - 777 PRAYER - APEL &amp; ABSEN PAGI</td>
                    <td class="dress" rowspan="<?= $rowspan ?>"><?= esc($dayDress[0]) ?></td>
                    <td class="dress" rowspan="<?= $rowspan ?>"><?= esc($dayDress[1]) ?></td>
                </tr>
                <?php foreach ($daySlots as $number => $slot): ?>
                    <tr>
                        <td class="jp"><?= (int) $number ?></td>
                        <td class="time"><?= esc(substr((string) $slot['start_time'], 0, 5) . '-' . substr((string) $slot['end_time'], 0, 5)) ?></td>
                        <?php foreach ($classes as $class): ?>
                            <?php
                            $entry = $grid['matrix'][(int) $class['id']][(int) $slot['id']] ?? null;
                            $fixed = $grid['fixed_matrix'][(int) $class['id']][(int) $slot['id']] ?? null;
                            ?>
                            <?php if ($fixed): ?>
                                <?php
                                $fixedColor = (!empty($fixed['color_label']) && $fixed['color_label'] !== '#242424' && $fixed['color_label'] !== '#000000') ? $fixed['color_label'] : '#1e3a8a';
                                [$bg, $fg] = $color($fixedColor);
                                ?>
                                <td class="subject" style="background:<?= esc($bg) ?>;color:<?= esc($fg) ?>">
                                    <?= esc(strtoupper($fixed['title'])) ?>
                                </td>
                            <?php elseif ($entry): ?>
                                <?php [$bg, $fg] = $color($entry['color_code'] ?? '#fff'); ?>
                                <td class="subject" style="background:<?= esc($bg) ?>;color:<?= esc($fg) ?>">
                                    <?= esc(strtoupper(($entry['subject_code'] ?: $entry['subject_name']) . '/' . ($entry['teacher_initial'] ?: ''))) ?>
                                </td>
                            <?php else: ?>
                                <td style="color:#cbd5e1;">-</td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                <?php $last = end($daySlots); $end = $last ? substr((string) $last['end_time'], 0, 5) : '-'; ?>
                <tr class="routine day-end">
                    <td class="jp">#</td>
                    <td class="time"><?= esc($end) ?>+</td>
                    <td colspan="<?= count($classes) ?>">ABSEN SIANG &amp; PULANG - TEACHERS' AFTERNOON WORSHIP</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- LEGENDS SECTION -->
    <div class="legend-wrap">
        <!-- TEACHER INITIALS LEGEND -->
        <section class="legend-panel">
            <div class="legend-title">INISIAL GURU (<?= count($grid['teachers'] ?? []) ?>)</div>
            <div class="legend-list">
                <?php foreach (($grid['teachers'] ?? []) as $index => $teacher): ?>
                    <?php
                    $rawName = rtrim(trim((string)$teacher['full_name']), ',');
                    $prefix = trim((string)($teacher['title_prefix'] ?? ''));
                    $suffix = trim((string)($teacher['degree_suffix'] ?? ''));
                    if (!empty($suffix) && !str_contains($rawName, $suffix)) {
                        $rawName .= ', ' . $suffix;
                    }
                    if (!empty($prefix) && !str_contains($rawName, $prefix)) {
                        $rawName = $prefix . ' ' . $rawName;
                    }
                    ?>
                    <div class="legend-item">
                        <span class="num"><?= $index + 1 ?>.</span>
                        <span class="swatch" style="background:<?= esc($teacher['color_code'] ?: '#fff') ?>"></span>
                        <span class="code"><?= esc(strtoupper((string) ($teacher['teacher_initial'] ?? ''))) ?></span>
                        <span class="name"><?= esc($rawName) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- SUBJECT INITIALS LEGEND -->
        <section class="legend-panel">
            <div class="legend-title">INISIAL MATA PELAJARAN (<?= count($grid['subjects'] ?? []) ?>)</div>
            <div class="legend-list">
                <?php foreach (($grid['subjects'] ?? []) as $index => $subject): ?>
                    <div class="legend-item">
                        <span class="num"><?= $index + 1 ?>.</span>
                        <span class="code"><?= esc(strtoupper((string) ($subject['code'] ?: $subject['short_name']))) ?></span>
                        <span class="name"><?= esc($subject['name']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <!-- SIGNATURE SECTION - KEPALA UNIT SEKOLAH BERSANGKUTAN -->
    <div class="signature-section">
        <div></div>
        <div class="sig-box">
            <div class="sig-title">Ditetapkan di: Sogokmo</div>
            <div class="sig-title">Pada Tanggal: <?= esc($formattedDate) ?></div>
            <div class="sig-role" style="margin-top:0.4mm;">Menyetujui,<br><strong>Kepala <?= esc($unit['name'] ?? 'Unit Sekolah') ?></strong></div>
            <div class="sig-space" style="height:9mm;"></div>
            <div class="sig-name">( <?= esc($unitHeadFormatted) ?> )</div>
        </div>
    </div>

    <div class="foot">
        <span>Master Jadwal Pelajaran <?= esc($unit['name'] ?? 'Unit Sekolah') ?>. Status dokumen mengikuti versi jadwal <?= esc($context['workflow_status'] ?? 'DRAFT') ?>.</span>
        <span class="status">Status: <?= esc($context['workflow_status'] ?? 'DRAFT') ?> - Dicetak <?= date('d-m-Y H:i') ?></span>
    </div>
</main>
</body>
</html>
