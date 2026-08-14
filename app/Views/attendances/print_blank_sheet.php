<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lembar Presensi Kosong - <?= esc($classroom['name']) ?></title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 10pt; color: #000; margin: 0; padding: 15px; }
        .header-table { width: 100%; border-bottom: 3px double #000; margin-bottom: 10px; padding-bottom: 5px; }
        .school-title { font-size: 13pt; font-weight: bold; text-transform: uppercase; text-align: center; }
        .doc-title { font-size: 11.5pt; font-weight: bold; text-align: center; text-transform: uppercase; margin: 8px 0; }
        .meta-table { width: 100%; margin-bottom: 10px; font-size: 9pt; }
        .blank-table { width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-bottom: 15px; }
        .blank-table th, .blank-table td { border: 1px solid #000; padding: 5px; text-align: center; }
        .blank-table th { background-color: #f2f2f2; font-weight: bold; }
        .text-left { text-align: left !important; }
        @media print {
            @page { size: A4 landscape; margin: 1cm; }
            body { padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 10px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 16px; font-size: 11pt; background: #0284c7; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
            🖨️ Cetak Lembar Presensi Kosong (Landscape)
        </button>
    </div>

    <!-- Kop Sekolah -->
    <table class="header-table">
        <tr>
            <td style="text-align: center;">
                <div class="school-title">YAYASAN PENDIDIKAN ADVENT SOGOKMO - <?= esc($classroom['unit_name']) ?></div>
                <div class="doc-title">LEMBAR PRESENSI PESERTA DIDIK (OFFLINE / MANUAL)</div>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td style="width: 15%; font-weight: bold;">Kelas / Rombel</td>
            <td style="width: 1%;">:</td>
            <td style="width: 34%;"><?= esc($classroom['name']) ?> (<?= esc($classroom['unit_name']) ?>)</td>
            <td style="width: 15%; font-weight: bold;">Mata Pelajaran</td>
            <td style="width: 1%;">:</td>
            <td style="width: 34%;">____________________________________</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Tahun / Periode</td>
            <td>:</td>
            <td><?= esc($activePeriod['name']) ?></td>
            <td style="font-weight: bold;">Guru Pengampu</td>
            <td>:</td>
            <td>____________________________________</td>
        </tr>
    </table>

    <table class="blank-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 3%;">No</th>
                <th rowspan="2" style="width: 10%;">NIS / NISN</th>
                <th rowspan="2" style="width: 25%;">Nama Peserta Didik</th>
                <th colspan="10">Tanggal & Pertemuan Pembelajaran</th>
                <th colspan="4">Keterangan Total</th>
            </tr>
            <tr>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <th style="width: 4%;">P<?= $i ?></th>
                <?php endfor; ?>
                <th style="width: 3%;">H</th>
                <th style="width: 3%;">I</th>
                <th style="width: 3%;">S</th>
                <th style="width: 3%;">A</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($students as $st): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= esc($st['student_number']) ?></td>
                    <td class="text-left" style="font-weight: bold;"><?= esc($st['full_name']) ?></td>
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <td></td>
                    <?php endfor; ?>
                    <td></td><td></td><td></td><td></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
