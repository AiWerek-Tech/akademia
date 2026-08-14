<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi Presensi Pelajaran - <?= esc($classroom['name']) ?> (<?= esc($subject['code']) ?>)</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 10pt; color: #000; margin: 0; padding: 15px; }
        .header-table { width: 100%; border-bottom: 3px double #000; margin-bottom: 10px; padding-bottom: 5px; }
        .school-title { font-size: 13pt; font-weight: bold; text-transform: uppercase; text-align: center; }
        .doc-title { font-size: 12pt; font-weight: bold; text-align: center; text-transform: uppercase; margin: 10px 0; }
        .meta-table { width: 100%; margin-bottom: 10px; font-size: 9.5pt; }
        .meta-table td { padding: 2px 4px; }
        .matrix-table { width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-bottom: 15px; }
        .matrix-table th, .matrix-table td { border: 1px solid #000; padding: 4px; text-align: center; }
        .matrix-table th { background-color: #f2f2f2; font-weight: bold; }
        .text-left { text-align: left !important; }
        .text-bold { font-weight: bold; }
        .signature-table { width: 100%; margin-top: 20px; font-size: 10pt; }
        .signature-table td { text-align: center; vertical-align: top; width: 50%; }
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
            🖨️ Cetak Rekapitulasi Presensi (Landscape)
        </button>
    </div>

    <!-- Kop Sekolah -->
    <table class="header-table">
        <tr>
            <td style="text-align: center;">
                <div class="school-title">YAYASAN PENDIDIKAN ADVENT SOGOKMO - <?= esc($classroom['unit_name']) ?></div>
                <div class="doc-title" style="margin: 3px 0;">REKAPITULASI PRESENSI MATEMATIKA / PELAJARAN SISWA PER-SEMESTER</div>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td style="width: 12%; font-weight: bold;">Mata Pelajaran</td>
            <td style="width: 1%;">:</td>
            <td style="width: 37%;"><?= esc($subject['name']) ?> (<?= esc($subject['code']) ?>)</td>
            <td style="width: 12%; font-weight: bold;">Kelas / Rombel</td>
            <td style="width: 1%;">:</td>
            <td style="width: 37%;"><?= esc($classroom['name']) ?> (<?= esc($classroom['unit_name']) ?>)</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Tahun / Periode</td>
            <td>:</td>
            <td><?= esc($activePeriod['name']) ?></td>
            <td style="font-weight: bold;">Total Pertemuan</td>
            <td>:</td>
            <td><?= esc($total_meetings) ?> Pertemuan Pembelajaran</td>
        </tr>
    </table>

    <table class="matrix-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 3%;">No</th>
                <th rowspan="2" style="width: 8%;">NIS / NISN</th>
                <th rowspan="2" style="width: 25%;">Nama Peserta Didik</th>
                <?php if ($total_meetings > 0): ?>
                    <th colspan="<?= $total_meetings ?>">Pertemuan Pembelajaran (Tanggal & Ke-N)</th>
                <?php else: ?>
                    <th>Pertemuan</th>
                <?php endif; ?>
                <th colspan="4">Total</th>
                <th rowspan="2" style="width: 5%;">% Hadir</th>
            </tr>
            <tr>
                <?php foreach ($sessions as $s): ?>
                    <th style="font-size: 7.5pt; font-weight: normal;">
                        <?= date('d/m', strtotime($s['attendance_date'])) ?><br>P<?= $s['meeting_number'] ?>
                    </th>
                <?php endforeach; ?>
                <?php if (empty($sessions)): ?>
                    <th>-</th>
                <?php endif; ?>
                <th style="width: 3%; background: #dcfce7;">H</th>
                <th style="width: 3%; background: #fef9c3;">I</th>
                <th style="width: 3%; background: #e0f2fe;">S</th>
                <th style="width: 3%; background: #fee2e2;">A</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($student_matrix)): ?>
                <tr>
                    <td colspan="<?= 9 + max(1, $total_meetings) ?>">Belum ada data peserta didik pada kelas ini.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; foreach ($student_matrix as $st): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= esc($st['student_number']) ?></td>
                        <td class="text-left text-bold"><?= esc($st['full_name']) ?></td>
                        <?php foreach ($sessions as $s):
                            $m = $st['meetings'][$s['id']] ?? ['status' => '-'];
                            $code = $m['status'];
                            $color = ['HADIR'=>'green','TERLAMBAT'=>'#b45309','IZIN'=>'#d97706','SAKIT'=>'#0284c7','ALPA'=>'red','DISPENSASI'=>'#64748b'][$code] ?? '#94a3b8';
                            $short = ['HADIR'=>'H','TERLAMBAT'=>'T','IZIN'=>'I','SAKIT'=>'S','ALPA'=>'A','DISPENSASI'=>'D'][$code] ?? '-';
                        ?>
                            <td style="color: <?= $color ?>; font-weight: bold;"><?= $short ?></td>
                        <?php endforeach; ?>
                        <?php if (empty($sessions)): ?>
                            <td>-</td>
                        <?php endif; ?>
                        <td class="text-bold" style="background: #f0fdf4; color: green;"><?= $st['hadir'] ?></td>
                        <td class="text-bold" style="background: #fefce8; color: #d97706;"><?= $st['izin'] ?></td>
                        <td class="text-bold" style="background: #f0f9ff; color: #0284c7;"><?= $st['sakit'] ?></td>
                        <td class="text-bold" style="background: #fef2f2; color: red;"><?= $st['alpa'] ?></td>
                        <td class="text-bold"><?= $st['rate'] ?>%</td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>
                Kepala Sekolah<br><br><br><br><br>
                _______________________________
            </td>
            <td>
                Wamena, <?= date('d F Y') ?><br>
                Guru Pengampu Mata Pelajaran<br><br><br><br><br>
                _______________________________
            </td>
        </tr>
    </table>
</body>
</html>
