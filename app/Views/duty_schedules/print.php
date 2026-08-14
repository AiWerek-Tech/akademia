<?php
$headerUnit = $unit ?? [];
$leftPath = trim((string)($headerUnit['logo_path'] ?? ''));
$rightPath = trim((string)($headerUnit['logo_right_path'] ?? ''));
$leftLogo = base_url($leftPath !== '' ? ltrim($leftPath, '/') : 'assets/img/brand-mark.svg');
$rightLogo = base_url($rightPath !== '' ? ltrim($rightPath, '/') : 'assets/img/brand-mark.svg');

$kopLine1 = $headerUnit['header_line_1'] ?? 'YAYASAN PENDIDIKAN ADVENT PAPUA';
$kopLine2 = $headerUnit['header_line_2'] ?? 'WAMENA MOUNTAIN VIEW ADVENTIST ACADEMY';
$kopLine3 = $headerUnit['header_line_3'] ?? 'SEKOLAH SATU ATAP (SMP & SMA ADVENT SOGOKMO)';
$kopLine4 = $headerUnit['header_line_4'] ?? 'Jalan Wamena - Kurima, Desa Sogokmo, Distrik Asotipo, Kabupaten Jayawijaya - Papua';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jadwal Piket Guru - T.A. <?= esc($selectedYear['name'] ?? '') ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            background: #f1f5f9;
            color: #0f172a;
            font-family: Arial, Helvetica, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .toolbar {
            width: 200mm;
            margin: 12px auto;
            padding: 10px 18px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .toolbar button {
            border: 0;
            border-radius: 6px;
            background: #2563eb;
            color: #ffffff;
            padding: 8px 18px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .toolbar button:hover {
            background: #1d4ed8;
        }
        .toolbar a {
            color: #64748b;
            text-decoration: none;
            font-weight: 600;
        }
        .toolbar a:hover {
            color: #0f172a;
        }
        .page {
            width: 200mm;
            min-height: 285mm;
            margin: 0 auto 20px;
            background: #ffffff;
            padding: 12mm 12mm;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            position: relative;
        }

        /* OFFICIAL KOP HEADER FROM SETTINGS */
        .official-header {
            position: relative;
            text-align: center;
            border-bottom: 3px double #0f172a;
            padding-bottom: 4mm;
            margin-bottom: 5mm;
        }
        .official-logo {
            position: absolute;
            top: 0;
            width: 20mm;
            height: 20mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .official-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .logo-left { left: 0; }
        .logo-right { right: 0; }
        .kop-line {
            text-transform: uppercase;
            font-weight: 800;
            line-height: 1.25;
            color: #0f172a;
        }
        .kop-1 { font-size: 9.5pt; letter-spacing: 0.5px; }
        .kop-2 { font-size: 11pt; letter-spacing: 0.8px; margin-top: 1px; color: #1e293b; }
        .kop-3 { font-size: 12pt; letter-spacing: 0.5px; margin-top: 2px; color: #0284c7; }
        .kop-4 { font-size: 8pt; font-style: italic; font-weight: 500; text-transform: none; margin-top: 3px; color: #475569; }

        /* DOCUMENT TITLE BOX */
        .doc-title-box {
            text-align: center;
            margin-bottom: 6mm;
        }
        .doc-title-box h1 {
            margin: 0;
            font-size: 13pt;
            font-weight: 900;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #0f172a;
            display: inline-block;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 2px;
        }
        .doc-title-box p {
            margin: 4px 0 0 0;
            font-size: 9.5pt;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
        }

        /* DUTY TABLE DESIGN */
        .duty-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            margin-bottom: 6mm;
            border: 2px solid #0f172a;
        }
        .duty-table th {
            background: #0f172a;
            color: #ffffff;
            font-weight: 800;
            padding: 9px 10px;
            border: 1px solid #0f172a;
            text-transform: uppercase;
            font-size: 9pt;
            letter-spacing: 0.5px;
        }
        .duty-table td {
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }
        .day-cell {
            font-weight: 900;
            color: #0f172a;
            font-size: 11pt;
            text-align: center;
            letter-spacing: 0.5px;
            border-right: 2.5px solid #0f172a !important;
        }

        /* BADGES FOR PRINT */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 8.5pt;
            font-weight: 700;
            line-height: 1.2;
        }
        .badge-zero {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .badge-load {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        .badge-role {
            background: #ffffff;
            color: #1e293b;
            border: 1px solid #cbd5e1;
            font-weight: 600;
        }

        /* SIGNATURES SECTION - DIREKTUR ONLY */
        .signatures-wrapper {
            display: flex;
            justify-content: flex-end;
            page-break-inside: avoid;
            margin-top: 10mm;
        }
        .sig-box {
            width: 48%;
            text-align: center;
        }
        .sig-space {
            height: 60px;
        }
        .sig-name {
            font-weight: 900;
            text-decoration: underline;
            margin-bottom: 2px;
            color: #0f172a;
            font-size: 10pt;
        }

        @media print {
            body { background: #ffffff; }
            .toolbar { display: none !important; }
            .page {
                width: auto;
                min-height: 0;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <div>
        <a href="<?= base_url('duty-schedules') ?>">&larr; Kembali ke Jadwal Piket</a>
        <span class="mx-2 text-muted">|</span>
        <strong>Dokumen Resmi Jadwal Piket Guru</strong> · Sekolah Satu Atap (SMP & SMA)
    </div>
    <button onclick="window.print()">Cetak Dokumen (A4)</button>
</div>

<main class="page">
    <!-- OFFICIAL SCHOOL KOP HEADER FROM SETTINGS -->
    <header class="official-header">
        <div class="official-logo logo-left">
            <img src="<?= esc($leftLogo) ?>" alt="Logo Kiri" onerror="this.style.display='none'">
        </div>
        <div class="official-logo logo-right">
            <img src="<?= esc($rightLogo) ?>" alt="Logo Kanan" onerror="this.style.display='none'">
        </div>
        <div class="kop-line kop-1"><?= esc($kopLine1) ?></div>
        <div class="kop-line kop-2"><?= esc($kopLine2) ?></div>
        <div class="kop-line kop-3"><?= esc($kopLine3) ?></div>
        <div class="kop-line kop-4"><?= esc($kopLine4) ?></div>
    </header>

    <!-- DOCUMENT TITLE -->
    <div class="doc-title-box">
        <h1>Jadwal Piket Guru Harian</h1>
        <p>Sekolah Satu Atap (SMP & SMA) · Tahun Pelajaran <?= esc($selectedYear['name'] ?? '-') ?></p>
    </div>

    <!-- DUTY ROSTER TABLE -->
    <table class="duty-table">
        <thead>
            <tr>
                <th style="width: 16%;">Hari</th>
                <th style="width: 8%;">No</th>
                <th style="width: 52%;">Nama Guru Piket</th>
                <th style="width: 24%;">Peran / Tugas</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $dayBgColors = [
                1 => '#f8fafc', // Senin
                2 => '#f0fdf4', // Selasa
                3 => '#eff6ff', // Rabu
                4 => '#faf5ff', // Kamis
                5 => '#f0fdfa', // Jumat
            ];
            $dayHeaderBgColors = [
                1 => '#e2e8f0', // Senin
                2 => '#dcfce7', // Selasa
                3 => '#dbeafe', // Rabu
                4 => '#f3e8ff', // Kamis
                5 => '#ccfbf1', // Jumat
            ];
            ?>
            <?php foreach ($matrix as $dayNum => $dayData): ?>
                <?php
                $duties = $dayData['duties'];
                $count = count($duties);
                $bgRow = $dayBgColors[$dayNum] ?? '#ffffff';
                $bgHeader = $dayHeaderBgColors[$dayNum] ?? '#e2e8f0';
                $dayTitleUpper = strtoupper($dayData['day_name']);
                ?>
                <?php if ($count === 0): ?>
                    <tr style="border-bottom: 2.5px solid #0f172a;">
                        <td class="day-cell" style="background-color: <?= $bgHeader ?> !important;">
                            <strong><?= esc($dayTitleUpper) ?></strong>
                        </td>
                        <td colspan="3" style="text-align: center; color: #94a3b8; font-style: italic; background-color: <?= $bgRow ?>;">
                            Belum ada penugasan piket
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($duties as $idx => $duty): ?>
                        <?php
                        $isLastRowOfDay = ($idx === $count - 1);
                        $roleClean = ucwords(strtolower(str_replace('_', ' ', $duty['duty_role'])));
                        ?>
                        <tr style="background-color: <?= $bgRow ?>; <?= $isLastRowOfDay ? 'border-bottom: 2.5px solid #0f172a !important;' : 'border-bottom: 1px solid #cbd5e1;' ?>">
                            <?php if ($idx === 0): ?>
                                <td rowspan="<?= $count ?>" class="day-cell" style="background-color: <?= $bgHeader ?> !important;">
                                    <strong><?= esc($dayTitleUpper) ?></strong>
                                </td>
                            <?php endif; ?>
                            <td style="text-align: center; font-weight: 700;"><?= $idx + 1 ?></td>
                            <td>
                                <strong style="font-size: 9.5pt; color: #0f172a;"><?= esc($duty['teacher_name']) ?></strong>
                                <?php if (!empty($duty['teacher_code']) || !empty($duty['nip'])): ?>
                                    <br><small style="color: #64748b; font-size: 8pt; font-family: monospace;"><?= esc($duty['teacher_code'] ?: $duty['nip']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-role"><?= esc($roleClean) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- SIGNATURES SECTION - DIREKTUR ONLY -->
    <?php
    $monthsIndo = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $dayStr = date('d');
    $monthStr = $monthsIndo[(int)date('m')] ?? date('F');
    $yearStr = date('Y');
    $dateIndo = $dayStr . ' ' . $monthStr . ' ' . $yearStr;
    ?>

    <div class="signatures-wrapper">
        <div class="sig-box">
            <div style="text-align: center; font-size: 9.5pt; color: #1e293b; margin-bottom: 8px;">
                Ditetapkan di: <strong>Sogokmo</strong><br>
                Pada Tanggal: <strong><?= $dateIndo ?></strong>
            </div>
            <p style="margin: 0; font-size: 10pt;">Menyetujui,<br><strong>Direktur</strong></p>
            <div class="sig-space"></div>
            <p class="sig-name">( <?= !empty($headmaster['full_name']) ? esc($headmaster['full_name']) : '....................................................' ?> )</p>
            <p style="margin: 0; font-size: 8.5pt; color: #64748b;">NIP. <?= !empty($headmaster['nip']) ? esc($headmaster['nip']) : '-' ?></p>
        </div>
    </div>
</main>

</body>
</html>
