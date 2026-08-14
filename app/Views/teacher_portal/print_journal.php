<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Mengajar & Presensi Kelas - <?= esc($session['classroom_name']) ?> (<?= esc($session['subject_code']) ?>)</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; color: #000; margin: 0; padding: 20px; }
        .header-table { width: 100%; border-bottom: 3px double #000; margin-bottom: 15px; padding-bottom: 10px; }
        .header-table td { vertical-align: middle; }
        .school-title { font-size: 14pt; font-weight: bold; text-transform: uppercase; text-align: center; }
        .school-sub { font-size: 11pt; text-align: center; font-style: italic; }
        .doc-title { font-size: 13pt; font-weight: bold; text-align: center; text-transform: uppercase; margin: 15px 0 10px 0; }
        .meta-table { width: 100%; margin-bottom: 15px; font-size: 10.5pt; }
        .meta-table td { padding: 3px 5px; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 10pt; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 5px 8px; }
        .data-table th { background-color: #f2f2f2; text-align: center; font-weight: bold; text-transform: uppercase; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .journal-box { border: 1px solid #000; padding: 10px; margin-bottom: 20px; font-size: 10.5pt; background: #fafafa; }
        .signature-table { width: 100%; margin-top: 30px; font-size: 11pt; }
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
            🖨️ Cetak Dokumen / Print PDF
        </button>
    </div>

    <!-- Kop Sekolah -->
    <table class="header-table">
        <tr>
            <td style="width: 15%; text-align: center;">
                <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo" style="height: 65px; width: auto;" onerror="this.style.display='none';">
            </td>
            <td style="width: 85%;">
                <div class="school-title">YAYASAN PENDIDIKAN ADVENT SOGOKMO</div>
                <div class="school-title" style="font-size: 12pt;"><?= esc($session['unit_name']) ?></div>
                <div class="school-sub">Alamat: Jl. Raya Sogokmo, Wamena, Jayawijaya, Papua Pegunungan</div>
            </td>
        </tr>
    </table>

    <div class="doc-title">LEMBAR PRESENSI SISWA & JURNAL MENGAJAR KELAS</div>

    <!-- Meta Information -->
    <table class="meta-table">
        <tr>
            <td style="width: 18%; font-weight: bold;">Kegiatan / Mapel</td>
            <td style="width: 2%;">:</td>
            <td style="width: 30%;"><?= esc($session['subject_name']) ?> (<?= esc($session['subject_code']) ?>)</td>
            <td style="width: 18%; font-weight: bold;">Hari / Tanggal</td>
            <td style="width: 2%;">:</td>
            <td style="width: 30%;"><?= date('l, d F Y', strtotime($session['attendance_date'])) ?></td>
        </tr>
        <tr><td style="font-weight:bold;">Jenis Sesi</td><td>:</td><td><?= esc(\App\Services\AttendanceService::sessionTypeLabel($session['session_type'] ?? 'SUBJECT')) ?></td><td></td><td></td><td></td></tr>
        <tr>
            <td style="font-weight: bold;">Kelas / Rombel</td>
            <td>:</td>
            <td><?= esc($session['classroom_name']) ?> (<?= esc($session['unit_name']) ?>)</td>
            <td style="font-weight: bold;">Pertemuan Ke-</td>
            <td>:</td>
            <td>Ke-<?= esc($session['meeting_number']) ?></td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Guru Pengampu</td>
            <td>:</td>
            <td><?= esc($session['teacher_name']) ?></td>
            <td style="font-weight: bold;">Tahun / Periode</td>
            <td>:</td>
            <td><?= esc($session['period_name']) ?></td>
        </tr>
    </table>

    <!-- Pokok Bahasan Jurnal Box -->
    <div class="journal-box">
        <strong>TOPIK / POKOK BAHASAN PEMBELAJARAN:</strong><br>
        <span style="font-size: 11pt; font-weight: bold; color: #1e293b; display: inline-block; margin-top: 4px;"><?= esc($session['topic'] ?: '-') ?></span>
        <?php foreach(['learning_objectives'=>'Tujuan Pembelajaran','learning_activity'=>'Aktivitas Pembelajaran','assessment_summary'=>'Asesmen & Capaian','follow_up'=>'Tindak Lanjut','teaching_summary'=>'Catatan/Kendala'] as $field=>$label):if(!empty($session[$field])):?><br><br><strong><?= $label ?>:</strong><br><?= nl2br(esc($session[$field])) ?><?php endif;endforeach;?>
    </div>

    <!-- Student Attendance Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">NIS / NISN</th>
                <th style="width: 45%;">Nama Peserta Didik</th>
                <th style="width: 15%;">Status</th>
                <th style="width: 20%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($roster as $st): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td class="text-center"><?= esc($st['student_number']) ?></td>
                    <td><?= esc($st['full_name']) ?></td>
                    <td class="text-center text-bold">
                        <?php
                        $stCode = strtoupper((string)($st['status'] ?? 'HADIR'));
                        if ($stCode === 'HADIR') echo '<span style="color: green;">HADIR</span>';
                        elseif ($stCode === 'IZIN') echo '<span style="color: #d97706;">IZIN</span>';
                        elseif ($stCode === 'SAKIT') echo '<span style="color: #0284c7;">SAKIT</span>';
                        elseif ($stCode === 'ALPA') echo '<span style="color: red;">ALPA</span>';
                        elseif ($stCode === 'TERLAMBAT') echo '<span style="color: #b45309;">TERLAMBAT</span>';
                        elseif ($stCode === 'DISPENSASI') echo '<span style="color: #64748b;">DISPENSASI</span>';
                        ?>
                    </td>
                    <td><?= esc($st['notes'] ?: '-') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Attendance Summary -->
    <div style="font-size: 10.5pt; margin-bottom: 25px;">
        <strong>Rekapitulasi Kehadiran:</strong>
        Hadir: <strong><?= esc($counts['HADIR'] ?? 0) ?></strong> orang |
        Izin: <strong><?= esc($counts['IZIN'] ?? 0) ?></strong> orang |
        Sakit: <strong><?= esc($counts['SAKIT'] ?? 0) ?></strong> orang |
        Alpa: <strong><?= esc($counts['ALPA'] ?? 0) ?></strong> orang |
        Terlambat: <strong><?= esc($counts['TERLAMBAT'] ?? 0) ?></strong> orang |
        Dispensasi: <strong><?= esc($counts['DISPENSASI'] ?? 0) ?></strong> orang |
        Total Peserta Didik: <strong><?= esc($total) ?></strong> orang
    </div>

    <!-- Signature Table -->
    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>
                Kepala Sekolah / Wakasek Kurikulum<br><br><br><br><br>
                _______________________________<br>
                NIP. -
            </td>
            <td>
                Wamena, <?= date('d F Y', strtotime($session['attendance_date'])) ?><br>
                Guru Pengampu Mata Pelajaran<br><br><br><br><br>
                <strong><u><?= esc($session['teacher_name']) ?></u></strong><br>
                NIP/NPU. <?= esc($session['teacher_code'] ?: '-') ?>
            </td>
        </tr>
    </table>
</body>
</html>
