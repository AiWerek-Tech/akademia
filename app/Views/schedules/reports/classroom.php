<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php
    $rawClassName = (string)($classroom['name'] ?? '');
    $classNameClean = preg_replace('/^kelas\s+/i', '', trim($rawClassName));
    $fullClassTitle = 'KELAS ' . strtoupper($classNameClean);

    // Format Homeroom Teacher Name
    $hrRaw = rtrim(trim((string)($classroom['homeroom_teacher_name'] ?? '')), ',');
    $hrPrefix = trim((string)($classroom['hr_prefix'] ?? ''));
    $hrSuffix = trim((string)($classroom['hr_suffix'] ?? ''));
    if (!empty($hrSuffix) && !str_contains($hrRaw, $hrSuffix)) {
        $hrRaw .= ', ' . $hrSuffix;
    }
    if (!empty($hrPrefix) && !str_contains($hrRaw, $hrPrefix)) {
        $hrRaw = $hrPrefix . ' ' . $hrRaw;
    }
    $homeroomTeacherFormatted = !empty($hrRaw) ? $hrRaw : 'MARIA SINAGA, S.Pd';
    $dirName = !empty($director_name) ? $director_name : 'MARTHEN REFASI, S.Ag';

    // Indonesian Date Format
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $formattedDate = date('d') . ' ' . ($months[(int)date('n')] ?? date('F')) . ' ' . date('Y');
    ?>
    <title>Jadwal Pelajaran <?= esc($fullClassTitle) ?></title>
    <style>
        @page {
            size: 330mm 215mm;
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
            max-width: 320mm;
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
            width: 320mm;
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
            padding: .5mm 20mm;
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
        .logo-left { left: 2mm; }
        .logo-right { right: 2mm; }
        .kop-line {
            text-transform: uppercase;
            font-weight: 800;
            line-height: 1.08;
        }
        .kop-1 { font-size: 6.5pt; }
        .kop-2 { font-size: 8.8pt; margin-top: .3mm; }
        .kop-3 { font-size: 9.8pt; margin-top: .3mm; }
        .kop-4 { font-size: 5pt; font-style: italic; font-weight: 500; text-transform: none; margin-top: .5mm; }

        .title {
            text-align: center;
            margin: 2mm 0 2mm 0;
        }
        .title h1 {
            display: inline-block;
            margin: 0;
            border: .4mm solid #111827;
            border-radius: 1mm;
            padding: 0.8mm 6mm;
            font-size: 8.8pt;
            letter-spacing: .25mm;
        }

        .grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .grid th, .grid td {
            border: .28mm solid #202b3d;
            padding: 1mm;
            text-align: center;
            vertical-align: middle;
            height: 9.8mm;
        }
        .grid thead th {
            height: 7mm;
            background: #dce5f4;
            text-transform: uppercase;
            font-size: 7pt;
        }
        .grid .jp {
            width: 12mm;
            background: #f3f6fa;
            font-weight: 900;
            font-size: 6.8pt;
        }
        .grid .time {
            width: 25mm;
            background: #f8fafc;
            font-family: Consolas, monospace;
            font-size: 6.2pt;
            font-weight: 600;
        }
        .grid .day {
            background: #1f3b73;
            color: #fff;
            font-size: 7.8pt;
        }
        .lesson {
            border-radius: 1mm;
            padding: 0.8mm .5mm;
            min-height: 7mm;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .subject {
            font-size: 7.5pt;
            font-weight: 900;
            line-height: 1.05;
        }
        .teacher {
            font-size: 6.2pt;
            font-weight: 700;
            margin-top: .4mm;
        }
        .room {
            font-size: 5.5pt;
            opacity: .78;
        }
        .fixed {
            background: #272f3e;
            color: #fff;
            font-weight: 900;
            border-radius: 1mm;
            padding: 1.2mm;
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.2mm;
        }
        .routine-banner {
            background: #0f172a;
            color: #ffffff;
            font-weight: 900;
            font-size: 7.2pt;
            letter-spacing: 0.3mm;
            padding: 1mm;
            border-radius: 0.8mm;
            text-transform: uppercase;
        }
        .routine-banner.break {
            background: #047857;
        }
        .routine-banner.post {
            background: #1e1b4b;
        }
        .empty {
            color: #c6ceda;
        }

        /* CURRICULUM FOOTER SECTION - BALANCED 2-COLUMN LAYOUT */
        .curriculum-section {
            margin-top: 2.5mm;
            border: .35mm solid #1e293b;
            border-radius: 1.2mm;
            padding: 2mm;
            background: #f8fafc;
        }
        .curriculum-header {
            font-size: 7.2pt;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.2mm;
            margin-bottom: 1.5mm;
            padding-bottom: 0.8mm;
            border-bottom: .28mm solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .curriculum-layout {
            display: flex;
            gap: 2.5mm;
        }
        .curriculum-col-left {
            width: 58%;
        }
        .curriculum-col-right {
            width: 42%;
            display: flex;
            flex-direction: column;
            gap: 2mm;
        }
        .curriculum-cat-box {
            background: #ffffff;
            border: .28mm solid #cbd5e1;
            border-radius: 1mm;
            padding: 1.5mm;
        }
        .curriculum-cat-title {
            font-size: 6.8pt;
            font-weight: 800;
            color: #1e3a8a;
            text-transform: uppercase;
            margin-bottom: 1mm;
            padding-bottom: 0.6mm;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
        }
        .curriculum-cat-title.mulok {
            color: #047857;
        }
        .curriculum-cat-title.pilihan {
            color: #6d28d9;
        }
        .curriculum-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6pt;
        }
        .curriculum-table th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 800;
            text-align: left;
            padding: 0.6mm 0.8mm;
            border-bottom: 1px solid #cbd5e1;
            font-size: 5.8pt;
            text-transform: uppercase;
        }
        .curriculum-table td {
            padding: 0.6mm 0.8mm;
            border-bottom: 1px dashed #e2e8f0;
            vertical-align: middle;
            color: #0f172a;
        }
        .curriculum-table tr:last-child td {
            border-bottom: none;
        }
        .code-badge {
            font-family: Consolas, monospace;
            font-weight: 800;
            color: #1e293b;
            background: #e2e8f0;
            padding: 0.2mm 0.6mm;
            border-radius: 0.5mm;
            font-size: 5.8pt;
        }

        /* SIGNATURE SECTION */
        .signature-section {
            margin-top: 3mm;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 0 4mm;
        }
        .sig-box {
            text-align: center;
            width: 70mm;
            font-size: 6.8pt;
        }
        .sig-title {
            color: #334155;
            margin-bottom: 0.5mm;
        }
        .sig-role {
            font-weight: 700;
            color: #0f172a;
        }
        .sig-space {
            height: 12mm;
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
            font-size: 6pt;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 1mm;
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
            .curriculum-section, .signature-section { break-inside: avoid; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <strong>Preview Jadwal Pelajaran <?= esc($fullClassTitle) ?></strong>
    <button onclick="window.print()">Cetak F4 Landscape</button>
</div>
<main class="page">
    <?= view('schedules/reports/_print_header', ['context' => $context]) ?>

    <div class="title">
        <h1>JADWAL PELAJARAN <?= esc($fullClassTitle) ?> - T.A. <?= esc($context['year_name'] ?? '') ?></h1>
    </div>

    <?php
    $days = $grid['days'] ?? [];
    $slotsByDay = [];
    $max = 0;
    foreach (($grid['slots'] ?? []) as $slot) {
        $slotsByDay[(int)$slot['day_id']][(int)$slot['slot_number']] = $slot;
        $max = max($max, (int)$slot['slot_number']);
    }

    $color = function($hex) {
        $hex = preg_match('/^#[0-9a-f]{6}$/i', (string)$hex) ? $hex : '#e7eef9';
        $r = hexdec(substr($hex, 1, 2));
        $g = hexdec(substr($hex, 3, 2));
        $b = hexdec(substr($hex, 5, 2));
        return [$hex, (($r*.299 + $g*.587 + $b*.114) > 155 ? '#111827' : '#fff')];
    };
    ?>

    <!-- MAIN SCHEDULE GRID -->
    <table class="grid">
        <thead>
            <tr>
                <th class="jp">JP</th>
                <th class="time">Waktu</th>
                <?php foreach ($days as $day): ?>
                    <th class="day"><?= esc($day['day_name']) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <!-- PRE-ACADEMIC ROUTINE (APEL PAGI & DOA 777) -->
            <tr>
                <td class="jp" style="background:#e2e8f0; font-size:6pt;">PRE</td>
                <td class="time" style="background:#f1f5f9; font-weight:700;">07:15 - 07:30</td>
                <td colspan="<?= count($days) ?>">
                    <div class="routine-banner">APEL PAGI & DOA 777</div>
                </td>
            </tr>

            <?php for ($number = 1; $number <= $max; $number++): ?>
                <?php
                $first = null;
                foreach ($days as $d) {
                    if (isset($slotsByDay[(int)$d['id']][$number])) {
                        $first = $slotsByDay[(int)$d['id']][$number];
                        break;
                    }
                }
                ?>
                <tr>
                    <td class="jp">JP <?= $number ?></td>
                    <td class="time"><?= $first ? esc(substr((string)$first['start_time'], 0, 5) . ' - ' . substr((string)$first['end_time'], 0, 5)) : '-' ?></td>
                    <?php foreach ($days as $day): ?>
                        <?php
                        $slot = $slotsByDay[(int)$day['id']][$number] ?? null;
                        $sid = $slot ? (int)$slot['id'] : 0;
                        $fixed = $grid['fixed_map'][$sid] ?? null;
                        $entry = $grid['entry_map'][$sid] ?? null;
                        ?>
                        <td>
                            <?php if ($fixed): ?>
                                <div class="fixed"><?= esc(strtoupper($fixed['title'])) ?></div>
                            <?php elseif ($entry): ?>
                                <?php
                                [$bg, $fg] = $color($entry['color_label'] ?? '');
                                $tInitial = esc($entry['teacher_initial'] ?? '');
                                ?>
                                <div class="lesson" style="background:<?= esc($bg) ?>; color:<?= esc($fg) ?>">
                                    <div class="subject"><?= esc(strtoupper($entry['subject_code'] ?: $entry['subject_name'])) ?></div>
                                    <div class="teacher"><?= esc(($tInitial !== '' ? $tInitial . ' - ' : '') . $entry['teacher_name']) ?></div>
                                    <?php if (!empty($entry['room_name'])): ?>
                                        <div class="room"><?= esc($entry['room_name']) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="empty">-</span>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- MID-DAY BREAK ROUTINE (ISTIRAHAT AFTER JP 5) -->
                <?php if ($number === 5): ?>
                    <tr>
                        <td class="jp" style="background:#e2e8f0; font-size:6pt;">BREAK</td>
                        <td class="time" style="background:#f1f5f9; font-weight:700;">10:50 - 11:05</td>
                        <td colspan="<?= count($days) ?>">
                            <div class="routine-banner break">ISTIRAHAT</div>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endfor; ?>

            <!-- POST-ACADEMIC ROUTINE (APEL SIANG & DOA PENUTUP) -->
            <tr>
                <td class="jp" style="background:#e2e8f0; font-size:6pt;">POST</td>
                <td class="time" style="background:#f1f5f9; font-weight:700;">13:45 - 14:00</td>
                <td colspan="<?= count($days) ?>">
                    <div class="routine-banner post">APEL SIANG & DOA PENUTUP</div>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- CURRICULUM SUBJECT INDEX FOOTER -->
    <?php $curriculum = $grid['curriculum_subjects'] ?? []; ?>
    <div class="curriculum-section">
        <div class="curriculum-header">
            <span>DAFTAR MATA PELAJARAN & GURU PENGAMPU (LEGENDA KURIKULUM) <?= esc($fullClassTitle) ?></span>
            <span style="font-size:5.8pt; font-weight:600; color:#64748b;">Sistem Informasi Kurikulum WMVAA Akademia</span>
        </div>

        <div class="curriculum-layout">
            <!-- LEFT COLUMN: A. MATA PELAJARAN UMUM / WAJIB -->
            <div class="curriculum-col-left">
                <div class="curriculum-cat-box">
                    <div class="curriculum-cat-title">
                        <span>A. MATA PELAJARAN UMUM / WAJIB</span>
                        <span><?= count($curriculum['WAJIB'] ?? []) ?> Mapel</span>
                    </div>
                    <table class="curriculum-table">
                        <thead>
                            <tr>
                                <th style="width: 6%;">No</th>
                                <th style="width: 14%;">Kode</th>
                                <th style="width: 40%;">Mata Pelajaran</th>
                                <th style="width: 32%;">Guru Pengampu</th>
                                <th style="width: 8%; text-align:center;">JP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($curriculum['WAJIB'])): ?>
                                <?php foreach ($curriculum['WAJIB'] as $idx => $s): ?>
                                    <tr>
                                        <td style="font-weight:700; text-align:center;"><?= $idx + 1 ?></td>
                                        <td><span class="code-badge"><?= esc($s['subject_code']) ?></span></td>
                                        <td style="font-weight:700; color:#0f172a;"><?= esc($s['subject_name']) ?></td>
                                        <td><strong><?= esc($s['teacher_initial']) ?></strong> - <?= esc($s['formatted_teacher_name']) ?></td>
                                        <td style="text-align:center; font-weight:800;"><?= esc($s['total_jp']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center; color:#94a3b8; font-style:italic;">Tidak ada mata pelajaran wajib</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- RIGHT COLUMN: B. MUATAN LOKAL & C. PEMINATAN -->
            <div class="curriculum-col-right">
                <!-- B. MUATAN LOKAL (MULOK) -->
                <div class="curriculum-cat-box">
                    <div class="curriculum-cat-title mulok">
                        <span>B. MUATAN LOKAL (MULOK)</span>
                        <span><?= count($curriculum['MUATAN_LOKAL'] ?? []) ?> Mapel</span>
                    </div>
                    <table class="curriculum-table">
                        <thead>
                            <tr>
                                <th style="width: 6%;">No</th>
                                <th style="width: 16%;">Kode</th>
                                <th style="width: 38%;">Mata Pelajaran</th>
                                <th style="width: 32%;">Guru Pengampu</th>
                                <th style="width: 8%; text-align:center;">JP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($curriculum['MUATAN_LOKAL'])): ?>
                                <?php foreach ($curriculum['MUATAN_LOKAL'] as $idx => $s): ?>
                                    <tr>
                                        <td style="font-weight:700; text-align:center;"><?= $idx + 1 ?></td>
                                        <td><span class="code-badge"><?= esc($s['subject_code']) ?></span></td>
                                        <td style="font-weight:700; color:#0f172a;"><?= esc($s['subject_name']) ?></td>
                                        <td><strong><?= esc($s['teacher_initial']) ?></strong> - <?= esc($s['formatted_teacher_name']) ?></td>
                                        <td style="text-align:center; font-weight:800;"><?= esc($s['total_jp']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center; color:#94a3b8; font-style:italic;">Tidak ada muatan lokal</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- C. MATA PELAJARAN PILIHAN / PEMINATAN -->
                <div class="curriculum-cat-box">
                    <div class="curriculum-cat-title pilihan">
                        <span>C. MATA PELAJARAN PILIHAN / PEMINATAN</span>
                        <span><?= count($curriculum['PILIHAN'] ?? []) ?> Mapel</span>
                    </div>
                    <table class="curriculum-table">
                        <thead>
                            <tr>
                                <th style="width: 6%;">No</th>
                                <th style="width: 16%;">Kode</th>
                                <th style="width: 38%;">Mata Pelajaran</th>
                                <th style="width: 32%;">Guru Pengampu</th>
                                <th style="width: 8%; text-align:center;">JP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($curriculum['PILIHAN'])): ?>
                                <?php foreach ($curriculum['PILIHAN'] as $idx => $s): ?>
                                    <tr>
                                        <td style="font-weight:700; text-align:center;"><?= $idx + 1 ?></td>
                                        <td><span class="code-badge"><?= esc($s['subject_code']) ?></span></td>
                                        <td style="font-weight:700; color:#0f172a;"><?= esc($s['subject_name']) ?></td>
                                        <td><strong><?= esc($s['teacher_initial']) ?></strong> - <?= esc($s['formatted_teacher_name']) ?></td>
                                        <td style="text-align:center; font-weight:800;"><?= esc($s['total_jp']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center; color:#94a3b8; font-style:italic;">Tidak ada mata pelajaran pilihan</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- SIGNATURE SECTION -->
    <div class="signature-section">
        <div class="sig-box">
            <div class="sig-title">Mengetahui,</div>
            <div class="sig-role">Wali <?= esc($fullClassTitle) ?></div>
            <div class="sig-space"></div>
            <div class="sig-name">( <?= esc($homeroomTeacherFormatted) ?> )</div>
        </div>

        <div class="sig-box">
            <div class="sig-title">Ditetapkan di: Sogokmo</div>
            <div class="sig-title">Pada Tanggal: <?= esc($formattedDate) ?></div>
            <div class="sig-role" style="margin-top:0.5mm;">Menyetujui,<br><strong>Direktur</strong></div>
            <div class="sig-space" style="height:10mm;"></div>
            <div class="sig-name">( <?= esc($dirName) ?> )</div>
        </div>
    </div>

    <div class="foot">
        <span>Waktu mengikuti slot resmi sekolah. Kegiatan rutin terkunci & pre/pasca akademik ditampilkan dengan latar khusus.</span>
        <span class="status">Status: <?= esc($context['workflow_status'] ?? 'DRAFT') ?></span>
    </div>
</main>
</body>
</html>
