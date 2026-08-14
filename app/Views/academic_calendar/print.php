<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($calendar['name']) ?> - Cetak</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 0;
            font-size: 10pt;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h3, .header h2, .header h1, .header h4 {
            margin: 2px 0;
            font-weight: bold;
        }
        .header h3 { font-size: 14pt; }
        .header h2 { font-size: 16pt; }
        .header h1 { font-size: 18pt; }

        .title-section {
            text-align: center;
            margin-bottom: 15px;
        }
        .title-section h4 {
            margin: 2px 0;
            font-size: 12pt;
            text-transform: uppercase;
        }

        .calendar-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .calendar-table th, .calendar-table td {
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
            padding: 2px;
            font-size: 8pt;
        }
        .calendar-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .month-col {
            text-align: left !important;
            padding-left: 5px !important;
            font-weight: bold;
            width: 70px;
        }
        .day-cell {
            width: 20px;
            height: 20px;
            font-weight: bold;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .empty-cell {
            background-color: #e0e0e0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .metric-col {
            width: 35px;
            font-weight: bold;
        }

        .info-section {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            font-size: 9pt;
        }
        .info-col {
            width: 32%;
        }
        .info-title {
            font-weight: bold;
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
            padding-bottom: 2px;
        }

        .color-legend {
            margin-bottom: 3px;
            display: flex;
            align-items: center;
        }
        .color-box {
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            margin-right: 5px;
            display: inline-block;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        ul, ol {
            margin: 0;
            padding-left: 15px;
        }
        li {
            margin-bottom: 2px;
        }

        .signature-section {
            float: right;
            text-align: center;
            margin-top: 20px;
            width: 250px;
        }
        .signature-space {
            height: 60px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        /* Ensure backgrounds print */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 10px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 15px; background: #0d6efd; color: #fff; border: none; border-radius: 4px; cursor: pointer;">Print Sekarang</button>
    </div>

    <!-- Kop -->
    <div class="header">
        <h3>YAYASAN PENDIDIKAN ADVENT PAPUA (YPAP)</h3>
        <h2>WAMENA MOUNTAIN VIEW ADVENTIST ACADEMY</h2>
        <h1>SMP-SMA ADVENT SOGOKMO</h1>
    </div>

    <!-- Title -->
    <div class="title-section">
        <h4><?= esc($calendar['name']) ?></h4>
        <div>TAHUN AJARAN <?= esc($year['name']) ?> <?= $unit ? '- ' . esc($unit['name']) : '' ?></div>
    </div>

    <!-- Table -->
    <table class="calendar-table">
        <thead>
            <tr>
                <th rowspan="2" class="month-col">BULAN</th>
                <th colspan="31">TANGGAL</th>
                <th rowspan="2" class="metric-col">HES</th>
                <th rowspan="2" class="metric-col">HEB</th>
            </tr>
            <tr>
                <?php for($i=1; $i<=31; $i++): ?>
                    <th style="width:20px"><?= $i ?></th>
                <?php endfor; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($grid as $ym => $month): ?>
                <tr>
                    <td class="month-col"><?= esc($month['month_name']) ?></td>
                    <?php for($i=1; $i<=31; $i++): ?>
                        <?php if(isset($month['days'][$i])): ?>
                            <?php $day = $month['days'][$i]; ?>
                            <td class="day-cell" style="background-color: <?= esc($day['bg_color']) ?>; color: <?= esc($day['text_color']) ?>;">
                                <?= $i ?>
                            </td>
                        <?php else: ?>
                            <td class="empty-cell"></td>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <td class="metric-col"><?= $month['hes'] ?></td>
                    <td class="metric-col"><?= $month['heb'] ?></td>
                </tr>
            <?php endforeach; ?>

            <!-- Summary Row -->
            <tr style="font-weight: bold; background-color: #f0f0f0;">
                <td colspan="32" style="text-align: right; padding-right: 10px;">TOTAL</td>
                <td><?= (int)$calendar['total_hes_sem1'] + (int)$calendar['total_hes_sem2'] ?></td>
                <td><?= (int)$calendar['total_heb_sem1'] + (int)$calendar['total_heb_sem2'] ?></td>
            </tr>
        </tbody>
    </table>

    <div style="font-weight: bold; font-size: 9pt; margin-bottom: 10px;">
        MINGGU EFEKTIF BELAJAR: SEMESTER 1 = <?= (int)$calendar['total_effective_weeks_sem1'] ?> MINGGU, SEMESTER 2 = <?= (int)$calendar['total_effective_weeks_sem2'] ?> MINGGU
    </div>

    <!-- Info Sections -->
    <div class="info-section">
        <div class="info-col">
            <div class="info-title">Keterangan Warna</div>
            <?php foreach($eventTypes as $type): ?>
                <div class="color-legend">
                    <span class="color-box" style="background-color: <?= esc($type['bg_color']) ?>;"></span>
                    <span><?= esc($type['name']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="info-col">
            <div class="info-title">Hari Libur Umum & Cuti Bersama</div>
            <ol>
                <?php foreach($holidays as $h): ?>
                    <li><strong><?= date('d M Y', strtotime($h['date'])) ?></strong>: <?= esc($h['event_title']) ?></li>
                <?php endforeach; ?>
            </ol>
        </div>

        <div class="info-col">
            <div class="info-title">Program Sekolah Lainnya</div>
            <ul>
                <?php foreach($events as $e): ?>
                    <li>
                        <?= esc($e['title']) ?>
                        (<?= date('d/m', strtotime($e['start_date'])) ?><?= $e['start_date'] !== $e['end_date'] ? ' - ' . date('d/m/Y', strtotime($e['end_date'])) : '/'.date('Y', strtotime($e['start_date'])) ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Signature -->
    <div class="signature-section">
        <div>Ditetapkan di: SOGOKMO</div>
        <?php
            $monthsId = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $now = date('Y-m-d');
            $d = date('d', strtotime($now));
            $m = $monthsId[(int)date('m', strtotime($now))];
            $y = date('Y', strtotime($now));
        ?>
        <div>Tanggal: <?= $d . ' ' . $m . ' ' . $y ?></div>
        <div style="margin-top: 5px;">Direktur SMP-SMA Advent Sogokmo</div>
        <div class="signature-space"></div>
        <div class="signature-name">NOD WINDEWANI, SE</div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
