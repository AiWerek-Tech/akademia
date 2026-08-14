<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Eksekutif Presensi Siswa - <?= esc($unit['name'] ?? 'Seluruh Unit') ?></title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 10.5pt; color: #000; margin: 0; padding: 20px; }
        .header-table { width: 100%; border-bottom: 3px double #000; margin-bottom: 15px; padding-bottom: 10px; }
        .school-title { font-size: 14pt; font-weight: bold; text-transform: uppercase; text-align: center; }
        .doc-title { font-size: 12.5pt; font-weight: bold; text-align: center; text-transform: uppercase; margin: 15px 0; }
        .kpi-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .kpi-table td { border: 1px solid #000; padding: 8px; text-align: center; width: 25%; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 9.5pt; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 5px 8px; }
        .data-table th { background-color: #f2f2f2; text-align: center; font-weight: bold; }
        .text-center { text-align: center; }
        .signature-table { width: 100%; margin-top: 30px; font-size: 10.5pt; }
        .signature-table td { text-align: center; vertical-align: top; width: 50%; }
        @media print {
            @page { size: A4; margin: 1.5cm; }
            body { padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 15px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 16px; font-size: 11pt; background: #0284c7; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
            🖨️ Cetak Laporan Eksekutif (Print PDF)
        </button>
    </div>

    <!-- Kop Sekolah -->
    <table class="header-table">
        <tr>
            <td style="text-align: center;">
                <div class="school-title">YAYASAN PENDIDIKAN ADVENT SOGOKMO</div>
                <div class="school-title" style="font-size: 12pt;"><?= esc($unit['name'] ?? 'UNIT SMP & SMA ADVENT SOGOKMO') ?></div>
                <div style="font-style: italic; font-size: 10pt;">Alamat: Jl. Raya Sogokmo, Wamena, Jayawijaya, Papua Pegunungan</div>
            </td>
        </tr>
    </table>

    <div class="doc-title">LAPORAN REKAPITULASI EKSEKUTIF PRESENSI & JURNAL MENGAJAR</div>

    <div style="margin-bottom: 15px; font-size: 10pt;">
        Periode Akademik: <strong><?= esc($activePeriod['name']) ?></strong> |
        Unit Sekolah: <strong><?= esc($unit['name'] ?? 'Semua Unit') ?></strong> |
        Dicetak Pada: <strong><?= date('d F Y H:i') ?></strong>
    </div>

    <!-- KPI Table -->
    <table class="kpi-table">
        <tr>
            <td>
                <span style="font-size: 9pt; text-transform: uppercase;">Total Sesi Terisi</span><br>
                <strong style="font-size: 14pt;"><?= number_format($total_sessions) ?></strong> Sesi
            </td>
            <td>
                <span style="font-size: 9pt; text-transform: uppercase;">Tingkat Kehadiran</span><br>
                <strong style="font-size: 14pt; color: green;"><?= $attendance_rate ?>%</strong>
            </td>
            <td>
                <span style="font-size: 9pt; text-transform: uppercase;">Izin & Sakit</span><br>
                <strong style="font-size: 14pt; color: #d97706;"><?= number_format(($counts['IZIN'] ?? 0) + ($counts['SAKIT'] ?? 0)) ?></strong> Log
            </td>
            <td>
                <span style="font-size: 9pt; text-transform: uppercase;">Total Alpa</span><br>
                <strong style="font-size: 14pt; color: red;"><?= number_format($counts['ALPA'] ?? 0) ?></strong> Log
            </td>
        </tr>
    </table>

    <!-- Sessions Summary Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 12%;">Tanggal</th>
                <th style="width: 10%;">Pertemuan</th>
                <th style="width: 18%;">Kelas</th>
                <th style="width: 25%;">Mata Pelajaran</th>
                <th style="width: 20%;">Guru Pengampu</th>
                <th style="width: 10%;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($sessions)): ?>
                <tr><td colspan="7" class="text-center">Tidak ada data sesi presensi.</td></tr>
            <?php else: ?>
                <?php $no = 1; foreach ($sessions as $s): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="text-center"><?= date('d/m/Y', strtotime($s['attendance_date'])) ?></td>
                        <td class="text-center">Ke-<?= esc($s['meeting_number']) ?></td>
                        <td><?= esc($s['classroom_name']) ?></td>
                        <td><?= esc($s['subject_name']) ?></td>
                        <td><?= esc($s['teacher_name']) ?></td>
                        <td class="text-center"><?= esc($s['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>
                Kepala Sekolah / Wakasek Kurikulum<br><br><br><br><br>
                _______________________________
            </td>
            <td>
                Wamena, <?= date('d F Y') ?><br>
                Administrator Sistem Akademik<br><br><br><br><br>
                _______________________________
            </td>
        </tr>
    </table>
</body>
</html>
