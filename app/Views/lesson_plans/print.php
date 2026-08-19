<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RPP - <?= esc($plan['session_label'] ?? 'Modul Ajar') ?> - <?= esc($plan['subject_name'] ?? '') ?></title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; color: #000; margin: 0; padding: 20px; line-height: 1.5; }
        .no-print { margin-bottom: 15px; text-align: right; }
        .no-print button { padding: 8px 16px; font-size: 11pt; background: #0284c7; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }

        /* Kop / Header */
        .header-table { width: 100%; border-bottom: 3px double #000; margin-bottom: 15px; padding-bottom: 10px; }
        .header-table td { vertical-align: middle; }
        .school-title { font-size: 14pt; font-weight: bold; text-transform: uppercase; text-align: center; }
        .school-sub { font-size: 10pt; text-align: center; font-style: italic; }

        /* Judul Dokumen */
        .doc-title { font-size: 13pt; font-weight: bold; text-align: center; text-transform: uppercase; margin: 15px 0 10px 0; }
        .doc-subtitle { font-size: 11pt; text-align: center; font-style: italic; margin-bottom: 15px; }

        /* Meta tables */
        .meta-table { width: 100%; margin-bottom: 12px; font-size: 10.5pt; }
        .meta-table td { padding: 2px 5px; vertical-align: top; }
        .meta-label { font-weight: bold; width: 22%; }

        /* Section headings */
        .section-heading { font-size: 11.5pt; font-weight: bold; background: #f2f2f2; border: 1px solid #000; padding: 4px 8px; margin: 12px 0 6px 0; text-transform: uppercase; }
        .sub-heading { font-size: 10.5pt; font-weight: bold; margin: 6px 0 3px 0; }

        /* Tables */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 10pt; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 4px 6px; }
        .data-table th { background-color: #f2f2f2; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 9.5pt; }
        .data-table td { vertical-align: top; }

        /* Content box */
        .content-box { border: 1px solid #999; padding: 8px 10px; margin-bottom: 10px; font-size: 10.5pt; min-height: 20px; background: #fafafa; }
        .content-box p { margin: 2px 0; }

        /* Empty placeholder */
        .empty-note { color: #999; font-style: italic; font-size: 10pt; }

        /* Signature */
        .signature-table { width: 100%; margin-top: 30px; font-size: 11pt; }
        .signature-table td { text-align: center; vertical-align: top; width: 33%; }

        /* Stage card */
        .stage-block { border: 1px solid #000; margin-bottom: 8px; page-break-inside: avoid; }
        .stage-header { background: #f2f2f2; border-bottom: 1px solid #000; padding: 4px 8px; font-weight: bold; font-size: 10.5pt; }
        .stage-body { padding: 6px 8px; font-size: 10pt; }
        .stage-body table { width: 100%; border-collapse: collapse; font-size: 10pt; }
        .stage-body table td { padding: 2px 4px; vertical-align: top; }
        .stage-body table .label-col { width: 28%; font-weight: bold; }

        @media print {
            @page { size: A4; margin: 1.5cm; }
            body { padding: 0; font-size: 10.5pt; }
            .no-print { display: none !important; }
            .stage-block { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<!-- Print Button -->
<div class="no-print">
    <button onclick="window.print()">🖨️ Cetak RPP / Print PDF</button>
    <a href="<?= base_url('lesson-plans/' . $plan['uuid'] . '/export-pdf') ?>" style="padding: 8px 16px; font-size: 11pt; background: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; margin-left: 8px;">📄 Unduh PDF</a>
    <a href="<?= base_url('lesson-plans/' . $plan['uuid'] . '/export-docx') ?>" style="padding: 8px 16px; font-size: 11pt; background: #2563eb; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; margin-left: 8px;">📥 Unduh DOCX (Word)</a>
    <a href="<?= base_url('lesson-plans/' . $plan['uuid']) ?>" style="padding: 8px 16px; font-size: 11pt; background: #64748b; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; margin-left: 8px;">← Kembali</a>
</div>

<!-- Kop Sekolah -->
<table class="header-table">
    <tr>
        <td style="width: 12%; text-align: center;">
            <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo" style="height: 60px; width: auto;" onerror="this.style.display='none';">
        </td>
        <td style="width: 88%;">
            <div class="school-title">YAYASAN PENDIDIKAN ADVENT SOGOKMO</div>
            <div class="school-title" style="font-size: 12pt;"><?= esc($plan['unit_name'] ?? '') ?></div>
            <div class="school-sub">Alamat: Jl. Raya Sogokmo, Wamena, Jayawijaya, Papua Pegunungan</div>
        </td>
    </tr>
</table>

<div class="doc-title">RENCANA PELAKSANAAN PEMBELAJARAN (RPP)</div>
<div class="doc-subtitle">Modul Ajar Pembelajaran Mendalam — <?= esc($plan['subject_name'] ?? '') ?></div>

<!-- Identitas RPP -->
<table class="meta-table">
    <tr>
        <td class="meta-label">Mata Pelajaran</td><td style="width: 2%;">:</td>
        <td style="width: 28%;"><?= esc($plan['subject_name'] ?? '-') ?></td>
        <td class="meta-label">Kelas / Fase</td><td style="width: 2%;">:</td>
        <td style="width: 28%;"><?= esc($plan['grade_name'] ?? '-') ?></td>
    </tr>
    <tr>
        <td class="meta-label">Guru Pengampu</td><td>:</td>
        <td><?= esc($plan['teacher_name'] ?? '-') ?></td>
        <td class="meta-label">Tahun / Periode</td><td>:</td>
        <td><?= esc($plan['period_name'] ?? '-') ?></td>
    </tr>
    <tr>
        <td class="meta-label">Pertemuan Ke-</td><td>:</td>
        <td>Ke-<?= (int) $plan['session_number'] ?></td>
        <td class="meta-label">Tanggal</td><td>:</td>
        <td><?= date('l, d F Y', strtotime($plan['date'])) ?></td>
    </tr>
    <tr>
        <td class="meta-label">Alokasi Waktu</td><td>:</td>
        <td><?= (int) ($summary['total_estimated_minutes'] ?? 0) ?> menit</td>
        <td class="meta-label">Sumber / Asal</td><td>:</td>
        <td><?= esc($plan['source_type'] ?? 'CUSTOM') ?>
            <?php if (!empty($plan['pack_code'])): ?> (Pack: <?= esc($plan['pack_code']) ?>)<?php endif ?>
        </td>
    </tr>
    <tr>
        <td class="meta-label">Label Sesi</td><td>:</td>
        <td colspan="4"><?= esc($plan['session_label'] ?? 'Pertemuan ' . (int) $plan['session_number']) ?></td>
    </tr>
    <?php if (!empty($plan['unit_title'])): ?>
    <tr>
        <td class="meta-label">Unit / Bab</td><td>:</td>
        <td colspan="4"><?= esc($plan['unit_title']) ?></td>
    </tr>
    <?php endif ?>
</table>

<!-- I. IDENTITAS & KONTEKS -->
<div class="section-heading">I. Identifikasi & Konteks Pembelajaran</div>
<table class="meta-table">
    <tr>
        <td class="meta-label" style="vertical-align:top;">Identifikasi Konteks</td>
        <td style="width: 2%;">:</td>
        <td><div class="content-box"><?= nl2br(esc($plan['identification_notes'] ?? '')) ?: '<span class="empty-note">—</span>' ?></div></td>
    </tr>
    <tr>
        <td class="meta-label" style="vertical-align:top;">Kesiapan Peserta Didik</td>
        <td>:</td>
        <td><div class="content-box"><?= nl2br(esc($plan['learner_readiness'] ?? '')) ?: '<span class="empty-note">—</span>' ?></div></td>
    </tr>
    <tr>
        <td class="meta-label" style="vertical-align:top;">Karakteristik Materi</td>
        <td>:</td>
        <td><div class="content-box"><?= nl2br(esc($plan['material_characteristics'] ?? '')) ?: '<span class="empty-note">—</span>' ?></div></td>
    </tr>
    <tr>
        <td class="meta-label" style="vertical-align:top;">Dimensi Profil Lulusan</td>
        <td>:</td>
        <td><div class="content-box"><?= nl2br(esc($plan['graduate_profile_dimensions'] ?? '')) ?: '<span class="empty-note">—</span>' ?></div></td>
    </tr>
</table>

<!-- II. TUJUAN PEMBELAJARAN -->
<div class="section-heading">II. Tujuan Pembelajaran (TP)</div>
<?php if (!empty($objectives)): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:5%;">No.</th>
                <th style="width:15%;">Kode TP</th>
                <th>Pernyataan Tujuan</th>
                <th style="width:15%;">Peran</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($objectives as $i => $obj): ?>
            <tr>
                <td class="text-center"><?= $i + 1 ?></td>
                <td style="font-family: monospace; font-size: 9.5pt;"><?= esc($obj['code'] ?? '-') ?></td>
                <td><?= esc($obj['statement'] ?? '-') ?></td>
                <td style="text-align: center;"><?= esc($obj['role'] ?? 'PRIMARY') ?></td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php else: ?>
    <div class="content-box empty-note">Belum ada tujuan pembelajaran yang dialokasikan.</div>
<?php endif ?>

<!-- III. DESAIN PEMBELAJARAN -->
<div class="section-heading">III. Desain Pembelajaran Mendalam (Deep Learning)</div>
<table class="meta-table">
    <tr>
        <td class="meta-label" style="vertical-align:top;">Praktik Pedagogis</td>
        <td style="width: 2%;">:</td>
        <td><div class="content-box"><?= esc($plan['pedagogical_practice'] ?? '') ?: '<span class="empty-note">—</span>' ?></div></td>
    </tr>
    <tr>
        <td class="meta-label" style="vertical-align:top;">Kemitraan Pembelajaran</td>
        <td>:</td>
        <td><div class="content-box"><?= nl2br(esc($plan['learning_partnership'] ?? '')) ?: '<span class="empty-note">—</span>' ?></div></td>
    </tr>
    <tr>
        <td class="meta-label" style="vertical-align:top;">Lingkungan Pembelajaran</td>
        <td>:</td>
        <td><div class="content-box"><?= nl2br(esc($plan['learning_environment'] ?? '')) ?: '<span class="empty-note">—</span>' ?></div></td>
    </tr>
    <tr>
        <td class="meta-label" style="vertical-align:top;">Pemanfaatan Digital</td>
        <td>:</td>
        <td><div class="content-box"><?= nl2br(esc($plan['digital_utilization'] ?? '')) ?: '<span class="empty-note">—</span>' ?></div></td>
    </tr>
    <tr>
        <td class="meta-label" style="vertical-align:top;">Koneksi Antarmapel</td>
        <td>:</td>
        <td><div class="content-box"><?= nl2br(esc($plan['interdisciplinary_notes'] ?? '')) ?: '<span class="empty-note">—</span>' ?></div></td>
    </tr>
</table>

<!-- IV. TAHAPAN PENGALAMAN BELAJAR -->
<div class="section-heading">IV. Tahapan Pengalaman Belajar (Understand · Apply · Reflect)</div>
<?php if (!empty($stages)): ?>
    <?php foreach ($stages as $s): ?>
        <?php
        $type = strtoupper($s['stage_type']);
        ?>
        <div class="stage-block">
            <div class="stage-header">
                <?= esc($type === 'MEMAHAMI' ? '🔵 MEMAHAMI' : ($type === 'MENGAPLIKASI' ? '🟡 MENGAPLIKASI' : '🟢 MEREFLEKSI')) ?>
                — <?= esc($s['title'] ?? $type) ?>
                <?php if (!empty($s['estimated_minutes'])): ?>
                    <span style="float:right; font-weight:normal; font-size: 9.5pt;"><?= (int) $s['estimated_minutes'] ?> menit</span>
                <?php endif ?>
            </div>
            <div class="stage-body">
                <?php if (!empty($s['description'])): ?>
                    <p><?= nl2br(esc($s['description'])) ?></p>
                <?php endif ?>
                <?php if (!empty($s['notes'])): ?>
                    <p><strong>Catatan:</strong> <?= nl2br(esc($s['notes'])) ?></p>
                <?php endif ?>

                <?php
                // Find activities linked to this stage
                $stageActivities = array_filter($activities, fn($a) => (int) ($a['lesson_plan_stage_id'] ?? 0) === (int) $s['id']);
                if (!empty($stageActivities)):
                ?>
                <table class="data-table" style="margin-top: 6px;">
                    <thead>
                        <tr>
                            <th style="width:5%;">No.</th>
                            <th>Aktivitas</th>
                            <th style="width:15%;">Moda</th>
                            <th style="width:15%;">Pengelompokan</th>
                            <th style="width:12%;">Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stageActivities as $i => $act): ?>
                        <tr>
                            <td style="text-align:center;"><?= $i + 1 ?></td>
                            <td>
                                <strong><?= esc($act['custom_title'] ?? '-') ?></strong>
                                <?php if (!empty($act['custom_description'])): ?>
                                    <br><span style="font-size: 9pt;"><?= nl2br(esc($act['custom_description'])) ?></span>
                                <?php endif ?>
                                <?php if (!empty($act['graduate_profile_alignment'])): ?>
                                    <br><span style="font-size: 9pt; font-style:italic;">Profil Lulusan: <?= esc($act['graduate_profile_alignment']) ?></span>
                                <?php endif ?>
                                <?php if (!empty($act['teacher_notes'])): ?>
                                    <br><span style="font-size: 9pt; color: #666;"><em>Guru: <?= nl2br(esc($act['teacher_notes'])) ?></em></span>
                                <?php endif ?>
                            </td>
                            <td style="text-align:center; font-size: 9.5pt;"><?= esc($act['delivery_mode'] ?? '-') ?></td>
                            <td style="text-align:center; font-size: 9.5pt;"><?= esc($act['grouping_mode'] ?? '-') ?></td>
                            <td style="text-align:center; font-size: 9.5pt;"><?= !empty($act['estimated_minutes']) ? (int) $act['estimated_minutes'] . ' mnt' : '-' ?></td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
                <?php endif ?>
            </div>
        </div>
    <?php endforeach ?>
<?php else: ?>
    <div class="content-box empty-note">Tahapan pengalaman belajar belum disusun.</div>
<?php endif ?>

<!-- Unlinked activities -->
<?php
$unlinkedActivities = array_filter($activities, fn($a) => empty($a['lesson_plan_stage_id']));
if (!empty($unlinkedActivities)):
?>
<div class="sub-heading" style="margin-top: 8px;">Aktivitas Tanpa Tahapan</div>
<table class="data-table">
    <thead>
        <tr>
            <th style="width:5%;">No.</th>
            <th>Aktivitas</th>
            <th style="width:15%;">Moda</th>
            <th style="width:12%;">Waktu</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($unlinkedActivities as $i => $act): ?>
        <tr>
            <td style="text-align:center;"><?= $i + 1 ?></td>
            <td>
                <strong><?= esc($act['custom_title'] ?? '-') ?></strong>
                <?php if (!empty($act['custom_description'])): ?>
                    <br><span style="font-size: 9pt;"><?= nl2br(esc($act['custom_description'])) ?></span>
                <?php endif ?>
            </td>
            <td style="text-align:center; font-size: 9.5pt;"><?= esc($act['delivery_mode'] ?? '-') ?></td>
            <td style="text-align:center; font-size: 9.5pt;"><?= !empty($act['estimated_minutes']) ? (int) $act['estimated_minutes'] . ' mnt' : '-' ?></td>
        </tr>
        <?php endforeach ?>
    </tbody>
</table>
<?php endif ?>

<!-- V. ASESMEN -->
<div class="section-heading">V. Rencana Asesmen</div>
<?php if (!empty($assessments)): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:5%;">No.</th>
                <th style="width:15%;">Tujuan</th>
                <th>Metode & Bentuk</th>
                <th>Kriteria / Indikator</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assessments as $i => $asm): ?>
            <tr>
                <td style="text-align:center;"><?= $i + 1 ?></td>
                <td style="text-align:center; font-family: monospace; font-size: 9.5pt;"><?= esc($asm['assessment_purpose'] ?? '-') ?></td>
                <td><?= esc($asm['recommended_method'] ?? '-') ?></td>
                <td><?= nl2br(esc($asm['criteria_reference'] ?? '-')) ?></td>
                <td><?= nl2br(esc($asm['notes'] ?? '-')) ?></td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>

    <?php
    // Show rubrics grouped by assessment
    $hasRubrics = false;
    foreach ($assessments as $asm) {
        if (!empty($rubricsByAssessment[$asm['uuid']])) {
            $hasRubrics = true;
            break;
        }
    }
    if ($hasRubrics):
    ?>
    <div class="sub-heading">Rubrik Penilaian</div>
    <?php foreach ($assessments as $asm): ?>
        <?php $rubrics = $rubricsByAssessment[$asm['uuid']] ?? []; ?>
        <?php if (!empty($rubrics)): ?>
        <div style="margin-bottom: 8px;">
            <span style="font-size: 9.5pt; font-weight: bold;"><?= esc($asm['recommended_method']) ?> (<?= esc($asm['assessment_purpose']) ?>):</span>
            <table class="data-table" style="margin-top: 3px;">
                <thead>
                    <tr>
                        <th style="width:5%;">No.</th>
                        <th>Kriteria Penilaian</th>
                        <th>Tingkatan Rubrik</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rubrics as $j => $rub): ?>
                    <tr>
                        <td style="text-align:center;"><?= $j + 1 ?></td>
                        <td><?= esc($rub['criterion_description'] ?? '-') ?></td>
                        <td style="font-size: 9pt;"><?= esc($rub['rubric_levels'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php endif ?>
    <?php endforeach ?>
    <?php endif ?>

<?php else: ?>
    <div class="content-box empty-note">Belum ada rencana asesmen.</div>
<?php endif ?>

<!-- VI. SUMBER DAYA -->
<?php
$allResources = [];
foreach ($activities as $a) {
    $aRes = $resourcesByActivity[$a['uuid']] ?? [];
    foreach ($aRes as $r) {
        $allResources[] = $r;
    }
}
if (!empty($allResources)):
?>
<div class="section-heading">VI. Sumber Daya & Kebutuhan Sarana</div>
<table class="data-table">
    <thead>
        <tr>
            <th style="width:5%;">No.</th>
            <th>Sumber Daya</th>
            <th style="width:20%;">Terkait Aktivitas</th>
            <th style="width:10%;">Jumlah</th>
            <th style="width:10%;">Wajib</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($allResources as $i => $res): ?>
        <tr>
            <td style="text-align:center;"><?= $i + 1 ?></td>
            <td><?= esc($res['resource_title'] ?? $res['custom_description'] ?? '-') ?></td>
            <td style="font-size: 9.5pt;"><?= esc($res['activity_title'] ?? '-') ?></td>
            <td style="text-align:center;"><?= (int) ($res['quantity'] ?? 1) ?></td>
            <td style="text-align:center;"><?= ($res['is_required'] ?? 1) ? 'Ya' : 'Opsional' ?></td>
        </tr>
        <?php endforeach ?>
    </tbody>
</table>
<?php endif ?>

<!-- TANDA TANGAN -->
<table class="signature-table">
    <tr>
        <td style="width: 33%;">
            <strong>Guru Pengampu</strong>
            <br><br><br><br>
            <u><?= esc($plan['teacher_name'] ?? '________________') ?></u>
            <br>NIP. ________________
        </td>
        <td style="width: 34%;">
            <strong>Kepala Sekolah</strong>
            <br><br><br><br>
            <u>________________________</u>
            <br>NIP. ________________
        </td>
        <td style="width: 33%;">
            <strong>Mengetahui,<br>Kepala Unit</strong>
            <br><br><br>
            <u>________________________</u>
            <br>NIP. ________________
        </td>
    </tr>
</table>

<div style="margin-top: 20px; font-size: 8pt; color: #999; text-align: center; border-top: 1px solid #ccc; padding-top: 5px;">
    Dokumen ini dihasilkan oleh WMVAA Academia IALOS Education · Revisi <?= (int) ($plan['revision_number'] ?? 1) ?> · <?= date('d/m/Y H:i') ?>
</div>

</body>
</html>
