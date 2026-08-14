<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php
    $rawTName = rtrim(trim((string)($teacher['full_name'] ?? '')), ',');
    $tPrefix = trim((string)($teacher['title_prefix'] ?? ''));
    $tSuffix = trim((string)($teacher['degree_suffix'] ?? ''));
    if (!empty($tSuffix) && !str_contains($rawTName, $tSuffix)) {
        $rawTName .= ', ' . $tSuffix;
    }
    if (!empty($tPrefix) && !str_contains($rawTName, $tPrefix)) {
        $rawTName = $tPrefix . ' ' . $rawTName;
    }
    $teacherNameFormatted = $rawTName;
    $dirName = !empty($director_name) ? $director_name : 'MARTHEN REFASI, S.Ag';

    // Indonesian Date Format
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $formattedDate = date('d') . ' ' . ($months[(int)date('n')] ?? date('F')) . ' ' . date('Y');

    // Calculate Summary Data
    $entries = $grid['entries'] ?? [];
    $totalJP = count($entries);
    $uniqueDays = [];
    $uniqueClasses = [];
    $bySubject = [];

    foreach ($entries as $e) {
        $uniqueDays[$e['day_name']] = true;
        $uniqueClasses[$e['class_name']] = true;

        $sCode = $e['subject_code'] ?: ($e['subject_name'] ?: 'KGT');
        if (!isset($bySubject[$sCode])) {
            $bySubject[$sCode] = [
                'subject_name' => $e['subject_name'] ?: 'Kegiatan Rutin',
                'subject_code' => $e['subject_code'] ?: '-',
                'color_label'  => $e['color_label'] ?? '#1e3a8a',
                'classes'      => [],
                'total_jp'     => 0,
            ];
        }
        $bySubject[$sCode]['total_jp']++;
        if (!empty($e['class_name']) && !in_array($e['class_name'], $bySubject[$sCode]['classes'], true)) {
            $bySubject[$sCode]['classes'][] = $e['class_name'];
        }
    }

    $romanVal = static function($className) {
        if (preg_match('/VII\b/i', $className)) return 7;
        if (preg_match('/VIII\b/i', $className)) return 8;
        if (preg_match('/IX\b/i', $className)) return 9;
        if (preg_match('/XII\b/i', $className)) return 12;
        if (preg_match('/XI\b/i', $className)) return 11;
        if (preg_match('/X\b/i', $className)) return 10;
        return 99;
    };

    foreach ($bySubject as $sCode => &$sItem) {
        usort($sItem['classes'], static function($a, $b) use ($romanVal) {
            return $romanVal($a) <=> $romanVal($b);
        });
    }
    unset($sItem);
    $totalDaysCount = count($uniqueDays);
    $totalClassesCount = count($uniqueClasses);

    // Day Color Palettes for Vibrant UI
    $dayConfig = [
        'SENIN'  => ['row' => '#f8fafc', 'header' => '#e2e8f0', 'border' => '#94a3b8'],
        'SELASA' => ['row' => '#f0fdf4', 'header' => '#dcfce7', 'border' => '#86efac'],
        'RABU'   => ['row' => '#eff6ff', 'header' => '#dbeafe', 'border' => '#93c5fd'],
        'KAMIS'  => ['row' => '#faf5ff', 'header' => '#f3e8ff', 'border' => '#d8b4fe'],
        'JUMAT'  => ['row' => '#f0fdfa', 'header' => '#ccfbf1', 'border' => '#99f6e4'],
    ];
    ?>
    <title>Jadwal Mengajar Guru - <?= esc($teacherNameFormatted) ?></title>
    <style>
        @page {
            size: 215mm 330mm;
            margin: 4mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            background: #edf1f6;
            color: #101827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7.5pt;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .toolbar {
            max-width: 205mm;
            margin: 8px auto;
            padding: 8px 14px;
            background: #fff;
            border: 1px solid #d6dde8;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .toolbar button {
            border: 0;
            border-radius: 6px;
            background: #2457d6;
            color: #fff;
            padding: 7px 14px;
            font-weight: 800;
            cursor: pointer;
        }
        .page {
            width: 205mm;
            margin: 0 auto 10px;
            background: #fff;
            padding: 3.5mm 4mm;
            box-shadow: 0 10px 30px #1f293720;
        }
        .official-header {
            height: 20mm;
            position: relative;
            text-align: center;
            border-bottom: .8mm double #111827;
            padding: .5mm 15mm;
        }
        .official-logo {
            position: absolute;
            top: 0.5mm;
            width: 16mm;
            height: 16mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .official-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .logo-left { left: 1.5mm; }
        .logo-right { right: 1.5mm; }
        .kop-line {
            text-transform: uppercase;
            font-weight: 800;
            line-height: 1.08;
        }
        .kop-1 { font-size: 6.5pt; }
        .kop-2 { font-size: 8.8pt; margin-top: .3mm; }
        .kop-3 { font-size: 9.8pt; margin-top: .3mm; }
        .kop-4 { font-size: 5pt; font-style: italic; font-weight: 500; text-transform: none; margin-top: .5mm; }

        .document-title {
            text-align: center;
            margin: 2mm 0 2mm;
        }
        .document-title h1 {
            display: inline-block;
            margin: 0;
            padding: 0.8mm 6mm;
            border: .4mm solid #111827;
            border-radius: 1mm;
            font-size: 8.8pt;
            letter-spacing: .3mm;
        }

        .teacher-card {
            display: grid;
            grid-template-columns: 1.8fr .8fr 1fr;
            gap: 2mm;
            margin-bottom: 2.5mm;
        }
        .stat {
            padding: 1.5mm 2.5mm;
            border: 1px solid #cbd5e1;
            border-radius: 1.2mm;
            background: #f8fafc;
        }
        .stat small {
            display: block;
            color: #64748b;
            font-size: 5.8pt;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: .15mm;
        }
        .stat strong {
            display: block;
            margin-top: .4mm;
            font-size: 8.5pt;
            color: #0f172a;
        }
        .initial {
            display: inline-flex;
            padding: .3mm 2.5mm;
            border-radius: 4mm;
            background: #0f172a;
            color: #fff;
            font-size: 8pt;
        }

        .schedule {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .schedule th, .schedule td {
            border: .28mm solid #334155;
            padding: 0.8mm 1mm;
            vertical-align: middle;
            font-size: 6.8pt;
        }
        .schedule thead th {
            background: #1e293b;
            color: #ffffff;
            font-size: 6.5pt;
            text-transform: uppercase;
            height: 6mm;
            text-align: center;
            letter-spacing: 0.2mm;
        }
        .schedule .day {
            text-align: center;
            font-weight: 900;
            font-size: 7.2pt;
            color: #0f172a;
        }
        .schedule .jp {
            text-align: center;
            font-weight: 900;
            font-size: 6.8pt;
            color: #0f172a;
        }
        .schedule .period {
            text-align: center;
            font-family: Consolas, monospace;
            font-size: 6.2pt;
            font-weight: 700;
            color: #334155;
        }
        .schedule .class-cell {
            text-align: center;
        }
        .class-badge {
            display: inline-block;
            padding: 0.6mm 2mm;
            border-radius: 0.8mm;
            background: #e0e7ff;
            color: #3730a3;
            font-weight: 800;
            font-size: 6.8pt;
            border: 1px solid #c7d2fe;
        }
        .subject-cell {
            padding: 0.8mm 1.5mm !important;
        }
        .subject-title {
            font-weight: 900;
            color: #1e3a8a;
            font-size: 7.2pt;
        }
        .code-pill {
            display: inline-block;
            margin-left: 1.5mm;
            padding: .1mm 1mm;
            border-radius: 0.5mm;
            background: #e2e8f0;
            font-size: 5.8pt;
            font-family: Consolas, monospace;
            font-weight: 800;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .room-text {
            color: #64748b;
            font-size: 6.2pt;
            text-align: center;
        }
        .empty {
            padding: 10mm !important;
            text-align: center;
            color: #64748b;
            font-style: italic;
        }
        .day-start td {
            border-top: 2.2px solid #0f172a !important;
        }

        /* TEACHING LOAD SUMMARY SECTION */
        .summary-card {
            margin-top: 2.5mm;
            border: .3mm solid #1e293b;
            border-radius: 1mm;
            padding: 1.8mm;
            background: #f8fafc;
        }
        .summary-title {
            font-size: 6.8pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            margin-bottom: 1mm;
            padding-bottom: 0.5mm;
            border-bottom: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6pt;
        }
        .summary-table th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 800;
            text-align: left;
            padding: 0.5mm 0.8mm;
            border-bottom: 1px solid #cbd5e1;
            font-size: 5.8pt;
            text-transform: uppercase;
        }
        .summary-table td {
            padding: 0.5mm 0.8mm;
            border-bottom: 1px dashed #e2e8f0;
            vertical-align: middle;
            color: #0f172a;
        }
        .summary-table tr:last-child td {
            border-bottom: none;
        }

        /* SIGNATURE SECTION */
        .signature-section {
            margin-top: 3.5mm;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 0 4mm;
        }
        .sig-box {
            text-align: center;
            width: 65mm;
            font-size: 6.8pt;
        }
        .sig-title {
            color: #334155;
            margin-bottom: 0.4mm;
        }
        .sig-role {
            font-weight: 700;
            color: #0f172a;
        }
        .sig-space {
            height: 10mm;
        }
        .sig-name {
            font-weight: 800;
            text-decoration: underline;
            color: #0f172a;
            font-size: 7.2pt;
        }

        .foot {
            display: flex;
            justify-content: space-between;
            margin-top: 2.5mm;
            font-size: 5.8pt;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 0.8mm;
        }
        .status {
            font-weight: 900;
            color: #1f3b73;
        }

        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .page { width: auto; margin: 0; box-shadow: none; padding: 0; }
            .schedule tr { break-inside: avoid; }
            .summary-card, .signature-section { break-inside: avoid; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <strong>Preview Jadwal Mengajar Guru</strong>
    <button onclick="window.print()">Cetak F4 Portrait</button>
</div>
<main class="page">
    <?= view('schedules/reports/_print_header', ['context' => $context]) ?>

    <div class="document-title">
        <h1>JADWAL MENGAJAR GURU - T.A. <?= esc($context['year_name'] ?? '') ?></h1>
    </div>

    <!-- TEACHER PROFILE & STAT CARD -->
    <section class="teacher-card">
        <div class="stat">
            <small>Guru Pengampu</small>
            <strong><?= esc($teacherNameFormatted) ?></strong>
        </div>
        <div class="stat">
            <small>Inisial</small>
            <strong><span class="initial"><?= esc($teacher['teacher_initial'] ?: '-') ?></span></strong>
        </div>
        <div class="stat">
            <small>Beban Mengajar</small>
            <strong><?= $totalJP ?> JP (<?= $totalClassesCount ?> Rombel / <?= $totalDaysCount ?> Hari)</strong>
        </div>
    </section>

    <!-- SCHEDULE TIMETABLE GRID WITH ENHANCED COLUMN WIDTHS & COLORS -->
    <table class="schedule">
        <thead>
            <tr>
                <th style="width: 14%;">Hari</th>
                <th style="width: 7%;">JP</th>
                <th style="width: 13%;">Waktu</th>
                <th style="width: 11%;">Kelas</th>
                <th style="width: 44%;">Mata Pelajaran</th>
                <th style="width: 11%;">Ruang</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$entries): ?>
                <tr>
                    <td colspan="6" class="empty">Belum ada jadwal mengajar pada periode ini.</td>
                </tr>
            <?php else: ?>
                <?php
                $lastDay = null;
                foreach ($entries as $entry):
                    $newDay = $lastDay !== $entry['day_name'];
                    $dNameUpper = strtoupper($entry['day_name']);
                    $dayTheme = $dayConfig[$dNameUpper] ?? ['row' => '#ffffff', 'header' => '#f1f5f9'];
                ?>
                    <tr class="<?= $newDay ? 'day-start' : '' ?>" style="background-color: <?= $dayTheme['row'] ?>;">
                        <?php if ($newDay): ?>
                            <?php $same = array_filter($entries, fn($e) => $e['day_name'] === $entry['day_name']); ?>
                            <td class="day" rowspan="<?= count($same) ?>" style="background-color: <?= $dayTheme['header'] ?> !important;">
                                <strong><?= esc($dNameUpper) ?></strong>
                            </td>
                        <?php endif; ?>
                        <td class="jp">JP <?= (int)$entry['slot_number'] ?></td>
                        <td class="period"><?= esc(substr((string)$entry['start_time'], 0, 5) . ' - ' . substr((string)$entry['end_time'], 0, 5)) ?></td>
                        <td class="class-cell">
                            <span class="class-badge"><?= esc($entry['class_name']) ?></span>
                        </td>
                        <td class="subject-cell">
                            <span class="subject-title"><?= esc($entry['subject_name'] ?: 'Kegiatan Rutin') ?></span>
                            <?php if (!empty($entry['subject_code'])): ?>
                                <span class="code-pill"><?= esc($entry['subject_code']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="room-text"><?= esc($entry['room_name'] ?: 'Ruang kelas') ?></td>
                    </tr>
                    <?php $lastDay = $entry['day_name']; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- TEACHING LOAD SUMMARY CARD -->
    <?php if (!empty($bySubject)): ?>
        <div class="summary-card">
            <div class="summary-title">
                <span>RINGKASAN DISTRIBUSI MENGAJAR KURIKULUM</span>
                <span>Total: <?= $totalJP ?> JP (<?= count($bySubject) ?> Mapel)</span>
            </div>
            <table class="summary-table">
                <thead>
                    <tr>
                        <th style="width: 6%;">No</th>
                        <th style="width: 14%;">Kode</th>
                        <th style="width: 36%;">Mata Pelajaran</th>
                        <th style="width: 36%;">Kelas / Rombel Yang Diajar</th>
                        <th style="width: 8%; text-align:center;">JP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $sNo = 1; foreach ($bySubject as $sCode => $sItem): ?>
                        <tr>
                            <td style="font-weight:700; text-align:center;"><?= $sNo++ ?></td>
                            <td><span class="code-pill"><?= esc($sItem['subject_code']) ?></span></td>
                            <td style="font-weight:700; color:#0f172a;"><?= esc($sItem['subject_name']) ?></td>
                            <td><?= esc(implode(', ', $sItem['classes'])) ?></td>
                            <td style="text-align:center; font-weight:800;"><?= esc($sItem['total_jp']) ?> JP</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- SIGNATURE SECTION - DIREKTUR & GURU PENGAMPU ONLY -->
    <div class="signature-section">
        <div class="sig-box">
            <div class="sig-title">Guru Pengampu,</div>
            <div class="sig-space"></div>
            <div class="sig-name">( <?= esc($teacherNameFormatted) ?> )</div>
        </div>

        <div class="sig-box">
            <div class="sig-title">Ditetapkan di: Sogokmo</div>
            <div class="sig-title">Pada Tanggal: <?= esc($formattedDate) ?></div>
            <div class="sig-role" style="margin-top:0.5mm;">Menyetujui,<br><strong>Direktur</strong></div>
            <div class="sig-space" style="height:9mm;"></div>
            <div class="sig-name">( <?= esc($dirName) ?> )</div>
        </div>
    </div>

    <div class="foot">
        <span>Mengajar pada <?= $totalDaysCount ?> hari dan <?= $totalClassesCount ?> rombel. Status dokumen mengikuti versi jadwal <?= esc($context['workflow_status'] ?? 'DRAFT') ?>.</span>
        <span class="status">Status: <?= esc($context['workflow_status'] ?? 'DRAFT') ?></span>
    </div>
</main>
</body>
</html>
