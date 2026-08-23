<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Buku Rapor Hasil Belajar') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Times+New+Roman&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --school-navy: #0f172a;
            --school-gray: #334155;
        }
        body {
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            margin: 0;
            padding: 20px 0;
            font-size: 13px;
        }
        .report-page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 18mm 20mm;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            box-sizing: border-box;
            position: relative;
        }
        .school-header {
            text-align: center;
            border-bottom: 2.5px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
            position: relative;
        }
        .school-header::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            right: 0;
            height: 1px;
            background-color: #0f172a;
        }
        .school-title {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--school-navy);
            margin: 0;
        }
        .school-subtitle {
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            margin: 2px 0;
        }
        .school-address {
            font-size: 11px;
            color: #64748b;
            margin: 0;
        }
        .report-title-box {
            text-align: center;
            margin: 16px 0 20px;
        }
        .report-title {
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--school-navy);
            margin-bottom: 2px;
        }
        .report-subtitle {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .student-info-table td {
            padding: 3px 6px;
            font-size: 12px;
            vertical-align: top;
        }
        .section-header {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--school-navy);
            border-bottom: 1.5px solid #0f172a;
            padding-bottom: 4px;
            margin-top: 24px;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }
        .report-data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 16px;
        }
        .report-data-table th, .report-data-table td {
            border: 1px solid #0f172a;
            padding: 6px 8px;
        }
        .report-data-table th {
            background-color: #f1f5f9;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
            font-size: 11px;
        }
        .signature-grid {
            margin-top: 35px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        .signature-box {
            text-align: center;
            width: 30%;
            font-size: 12px;
        }
        .signature-space {
            height: 60px;
        }
        .signature-name {
            font-weight: 700;
            border-bottom: 1px solid #0f172a;
            display: inline-block;
            min-width: 150px;
            padding-bottom: 2px;
        }
        .signature-nip {
            font-size: 11px;
            color: #64748b;
            margin-top: 3px;
        }
        .page-break {
            page-break-before: always;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .report-page {
                box-shadow: none !important;
                width: 100% !important;
                min-height: auto !important;
                margin: 0 !important;
                padding: 10mm 15mm !important;
            }
            @page {
                size: A4 portrait;
                margin: 10mm 12mm;
            }
        }
    </style>
</head>
<body>

    <!-- Screen Control Bar -->
    <div class="container no-print mb-4" style="max-width: 210mm;">
        <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm border">
            <div class="d-flex align-items-center gap-2">
                <a href="<?= base_url('reporting/' . $snapshot['id']) ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i data-lucide="arrow-left" class="w-4 h-4 d-inline-block me-1"></i> Kembali ke Detail
                </a>
                <span class="text-muted small">|</span>
                <span class="fw-bold text-gray-900 small">Pratinjau Cetak Rapor Resmi</span>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary shadow-sm rounded-pill px-4" onclick="window.print()">
                    <i data-lucide="printer" class="w-4 h-4 d-inline-block me-1"></i> Cetak Dokumen Rapor (A4)
                </button>
            </div>
        </div>
    </div>

    <!-- Official Report Card Layout -->
    <div class="report-page">

        <!-- Kop Sekolah -->
        <div class="school-header">
            <h1 class="school-title"><?= esc($snapshot['unit_name'] ?? 'WMVAA ACADEMIA') ?></h1>
            <div class="school-subtitle">Sistem Manajemen Pendidikan & Karakter Terpadu</div>
            <div class="school-address">Kampus WMVAA · Terakreditasi · Laporan Capaian Hasil Belajar Siswa</div>
        </div>

        <!-- Judul Rapor -->
        <div class="report-title-box">
            <div class="report-title">Laporan Capaian Hasil Belajar (Rapor)</div>
            <div class="report-subtitle">Kurikulum Merdeka & Pembentukan Karakter</div>
        </div>

        <!-- Identitas Siswa -->
        <div class="row g-2 mb-4 student-info-table">
            <div class="col-7">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 130px; font-weight: 600;">Nama Peserta Didik</td>
                        <td style="width: 10px;">:</td>
                        <td style="font-weight: 700;"><?= esc($snapshot['student_name'] ?? '—') ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">NIS / NISN</td>
                        <td>:</td>
                        <td><?= esc($snapshot['student_number'] ?? '—') ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Satuan Pendidikan</td>
                        <td>:</td>
                        <td><?= esc($snapshot['unit_name'] ?? 'WMVAA') ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-5">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 110px; font-weight: 600;">Kelas / Rombel</td>
                        <td style="width: 10px;">:</td>
                        <td style="font-weight: 700;"><?= esc($snapshot['classroom_name'] ?? '—') ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Fase / Tingkat</td>
                        <td>:</td>
                        <td>Kelas <?= esc($snapshot['current_grade'] ?? '—') ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Semester / Tahun</td>
                        <td>:</td>
                        <td><?= esc($snapshot['period_name'] ?? 'Semester Aktif') ?> (<?= esc($snapshot['academic_year_name'] ?? date('Y')) ?>)</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- A. CAPAIAN PEMBELAJARAN INTRAKURIKULER -->
        <div class="section-header">A. Capaian Pembelajaran Intrakurikuler</div>
        <table class="report-data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 30%; text-align: left;">Mata Pelajaran</th>
                    <th style="width: 10%;">Nilai Akhir</th>
                    <th style="width: 10%;">Predikat</th>
                    <th style="text-align: left;">Capaian Kompetensi & Deskripsi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($snapshot['subjects'])): ?>
                    <tr>
                        <td colspan="5" class="text-center py-3 text-muted"><em>Belum ada data nilai intrakurikuler tercatat.</em></td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($snapshot['subjects'] as $s): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td class="fw-semibold">
                                <?= esc($s['subject_name']) ?>
                                <?php if (! empty($s['teacher_name'])): ?>
                                    <div class="text-muted" style="font-size: 10px; font-weight: normal;">Guru: <?= esc($s['teacher_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-bold fs-6"><?= $s['final_score'] !== null ? number_format((float) $s['final_score'], 0) : '—' ?></td>
                            <td class="text-center fw-bold"><?= esc($s['final_predicate'] ?? '—') ?></td>
                            <td style="font-size: 11.5px; line-height: 1.45;">
                                <?= nl2br(esc($s['auto_narrative'] ?? '—')) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- B. KOKURIKULER & KARAKTER (P5 / 7KAIH) -->
        <div class="section-header">B. Projek Penguatan Profil Pelajar Pancasila & Kokurikuler</div>
        <table class="report-data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 35%; text-align: left;">Kegiatan / Tema Projek</th>
                    <th style="width: 25%; text-align: left;">Dimensi Karakter</th>
                    <th style="text-align: left;">Catatan Capaian Perkembangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($snapshot['cocurriculars'])): ?>
                    <tr>
                        <td colspan="4" class="text-center py-2 text-muted" style="font-size: 11px;">
                            <em>Telah aktif mengikuti projek penguatan karakter dan kokurikuler dengan menunjukkan perkembangan profil pelajar yang positif.</em>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($snapshot['cocurriculars'] as $c): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td class="fw-semibold"><?= esc($c['program_title'] ?? 'Projek Kokurikuler') ?></td>
                            <td><?= esc($c['dimension_name'] ?? 'Dimensi Profil') ?></td>
                            <td style="font-size: 11px;"><?= esc($c['notes'] ?? 'Menunjukkan keikutsertaan dan pengamalan nilai karakter yang sangat baik.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- C. EKSTRAKURIKULER & KEPANDUAN (PATHFINDER / HONORS) -->
        <div class="section-header">C. Ekstrakurikuler & Pengembangan Bakat</div>
        <table class="report-data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 30%; text-align: left;">Kegiatan Ekstrakurikuler</th>
                    <th style="width: 15%; text-align: center;">Predikat</th>
                    <th style="text-align: left;">Keterangan & Capaian Keterampilan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($snapshot['extracurriculars'])): ?>
                    <tr>
                        <td colspan="4" class="text-center py-2 text-muted" style="font-size: 11px;"><em>Belum ada kegiatan ekstrakurikuler yang diikuti pada periode ini.</em></td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($snapshot['extracurriculars'] as $e): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td class="fw-semibold"><?= esc($e['program_title']) ?></td>
                            <td class="text-center fw-bold"><?= esc($e['predicate_label']) ?> (<?= esc($e['predicate']) ?>)</td>
                            <td style="font-size: 11px; line-height: 1.4;"><?= esc($e['narrative']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- D. REKAPITULASI PRESENSI & CATATAN WALI KELAS -->
        <div class="row g-3">
            <div class="col-5">
                <div class="section-header" style="margin-top: 10px;">D. Rekapitulasi Kehadiran</div>
                <table class="report-data-table mb-0">
                    <tr>
                        <td style="width: 60%; font-weight: 600;">Hadir (Tepat Waktu)</td>
                        <td class="text-center fw-bold"><?= (int) ($snapshot['attendance_summary']['hadir'] ?? 0) ?> hari</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Sakit</td>
                        <td class="text-center"><?= (int) ($snapshot['attendance_summary']['sakit'] ?? 0) ?> hari</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Izin</td>
                        <td class="text-center"><?= (int) ($snapshot['attendance_summary']['izin'] ?? 0) ?> hari</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Tanpa Keterangan (Alpa)</td>
                        <td class="text-center"><?= (int) ($snapshot['attendance_summary']['alpa'] ?? 0) ?> hari</td>
                    </tr>
                </table>
            </div>
            <div class="col-7">
                <div class="section-header" style="margin-top: 10px;">E. Catatan Perkembangan Wali Kelas</div>
                <div style="border: 1px solid #0f172a; padding: 10px 12px; min-height: 106px; font-size: 11.5px; line-height: 1.5; background: #fff;">
                    Ananda <strong><?= esc($snapshot['student_name']) ?></strong> menunjukkan kemajuan belajar dan adaptasi sosial yang sangat baik selama semester ini. Terus pertahankan semangat belajar, ketekunan ibadah, dan asah potensi kepemimpinan untuk menyongsong capaian yang lebih tinggi.
                </div>
            </div>
        </div>

        <!-- Tanda Tangan & Pengesahan -->
        <div class="signature-grid">
            <div class="signature-box">
                <div>Mengetahui,</div>
                <div>Orang Tua / Wali Siswa,</div>
                <div class="signature-space"></div>
                <div class="signature-name">................................................</div>
            </div>

            <div class="signature-box">
                <div>Kota Sekolah, <?= date('d F Y') ?></div>
                <div>Wali Kelas,</div>
                <div class="signature-space"></div>
                <div class="signature-name"><?= esc($snapshot['homeroom_teacher']) ?></div>
                <div class="signature-nip">NIP. —</div>
            </div>

            <div class="signature-box">
                <div>Mengetahui,</div>
                <div>Kepala Satuan Pendidikan,</div>
                <div class="signature-space"></div>
                <div class="signature-name"><?= esc($snapshot['headmaster']) ?></div>
                <div class="signature-nip">NIP. —</div>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>
