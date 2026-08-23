<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Laporan Supervisi Akademik') ?></title>
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
        .school-title {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--school-navy);
            margin: 0;
        }
        .school-subtitle {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            margin-top: 2px;
        }
        .school-address {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        .report-title-box {
            text-align: center;
            margin-bottom: 20px;
        }
        .report-title {
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
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
        .info-table td {
            padding: 4px 6px;
            font-size: 12px;
            vertical-align: top;
        }
        .section-header {
            font-size: 12.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--school-navy);
            border-bottom: 1.5px solid #0f172a;
            padding-bottom: 4px;
            margin-top: 18px;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }
        .content-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 12px;
            line-height: 1.6;
            margin-bottom: 12px;
            background-color: #f8fafc;
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
                <a href="<?= base_url('quality/supervisions') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i data-lucide="arrow-left" class="w-4 h-4 d-inline-block me-1"></i> Kembali ke Supervisi
                </a>
                <span class="text-muted small">|</span>
                <span class="fw-bold text-gray-900 small">Pratinjau Lembar Supervisi Akademik</span>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary shadow-sm rounded-pill px-4" onclick="window.print()">
                    <i data-lucide="printer" class="w-4 h-4 d-inline-block me-1"></i> Cetak Dokumen (A4)
                </button>
            </div>
        </div>
    </div>

    <!-- Official Document Layout -->
    <div class="report-page">

        <!-- Kop Sekolah -->
        <div class="school-header">
            <h1 class="school-title"><?= esc($unit['name'] ?? 'WMVAA ACADEMIA') ?></h1>
            <div class="school-subtitle">Sistem Penjaminan Mutu & Pengembangan Profesionalisme Guru</div>
            <div class="school-address">Kampus WMVAA · Lembar Resmi Observasi & Supervisi Akademik</div>
        </div>

        <!-- Judul Laporan -->
        <div class="report-title-box">
            <div class="report-title">Lembar Hasil Supervisi & Observasi Pembelajaran</div>
            <div class="report-subtitle">Periode Akademik: <?= esc($period['name'] ?? 'Aktif') ?></div>
        </div>

        <!-- Identitas Supervisi -->
        <div class="row g-2 mb-3 info-table">
            <div class="col-6">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 140px; font-weight: 600;">Guru yang Diobservasi</td>
                        <td style="width: 10px;">:</td>
                        <td style="font-weight: 700;"><?= esc($record['teacher_name'] ?? '—') ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Supervisor / Penilai</td>
                        <td>:</td>
                        <td><?= esc($record['supervisor_name'] ?? '—') ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Tanggal Supervisi</td>
                        <td>:</td>
                        <td><?= date('d F Y', strtotime($record['observation_date'])) ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-6">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 130px; font-weight: 600;">Mata Pelajaran</td>
                        <td style="width: 10px;">:</td>
                        <td><?= esc($record['subject_name'] ?? '—') ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Kelas / Rombel</td>
                        <td>:</td>
                        <td><?= esc($record['classroom_name'] ?? '—') ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Tipe Observasi</td>
                        <td>:</td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= esc($record['observation_type']) ?></span>
                            · Status: <span class="fw-semibold text-<?= $record['status'] === 'COMPLETED' ? 'success' : 'secondary' ?>"><?= esc($record['status']) ?></span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Predikat Rating Keseluruhan -->
        <div class="p-2 px-3 rounded-3 mb-3 border d-flex justify-content-between align-items-center" style="background-color: #f1f5f9;">
            <span class="fw-bold text-uppercase" style="font-size: 11.5px; color: var(--school-navy);">Predikat Capaian Kinerja Pembelajaran:</span>
            <?php
            $ratingText = match($record['overall_rating']) {
                'EXCELLENT'         => 'SANGAT BAIK (EXCELLENT)',
                'GOOD'              => 'BAIK (GOOD)',
                'SATISFACTORY'      => 'CUKUP (SATISFACTORY)',
                'NEEDS_IMPROVEMENT' => 'PERLU PENDAMPINGAN (NEEDS IMPROVEMENT)',
                default             => 'BELUM DINILAI',
            };
            $ratingClass = match($record['overall_rating']) {
                'EXCELLENT'         => 'bg-success text-white',
                'GOOD'              => 'bg-primary text-white',
                'SATISFACTORY'      => 'bg-warning text-dark',
                'NEEDS_IMPROVEMENT' => 'bg-danger text-white',
                default             => 'bg-secondary text-white',
            };
            ?>
            <span class="badge <?= $ratingClass ?> px-3 py-1.5 rounded-pill fw-bold" style="font-size: 11.5px;">
                <?= $ratingText ?>
            </span>
        </div>

        <!-- Aspek 1: Kekuatan -->
        <div class="section-header">1. Kekuatan & Praktik Baik Pembelajaran (Strengths)</div>
        <div class="content-box">
            <?= !empty($record['strengths']) ? nl2br(esc($record['strengths'])) : '<span class="text-muted italic">Tidak ada catatan kekuatan khusus.</span>' ?>
        </div>

        <!-- Aspek 2: Area Pertumbuhan -->
        <div class="section-header">2. Area Pertumbuhan & Pengembangan Pedagogik (Areas for Growth)</div>
        <div class="content-box">
            <?= !empty($record['areas_for_growth']) ? nl2br(esc($record['areas_for_growth'])) : '<span class="text-muted italic">Tidak ada area perbaikan khusus.</span>' ?>
        </div>

        <!-- Aspek 3: Rekomendasi -->
        <div class="section-header">3. Rekomendasi & Rencana Tindak Lanjut (Action Plan)</div>
        <div class="content-box">
            <?= !empty($record['recommendations']) ? nl2br(esc($record['recommendations'])) : '<span class="text-muted italic">Tidak ada rekomendasi khusus.</span>' ?>
        </div>

        <!-- Aspek 4: Pendampingan Lanjutan -->
        <?php if ($record['follow_up_needed']): ?>
            <div class="section-header text-danger">4. Catatan Pendampingan Lanjutan (Follow-up Notes)</div>
            <div class="content-box border-warning bg-warning-subtle">
                <strong>Status: Perlu Pendampingan Lanjutan (Follow-up Required)</strong><br>
                <?= !empty($record['follow_up_notes']) ? nl2br(esc($record['follow_up_notes'])) : 'Pendampingan klinis terjadwal bersama supervisor/rekan sejawat.' ?>
            </div>
        <?php endif; ?>

        <!-- Triple Signature Block -->
        <div class="signature-grid">
            <div class="signature-box">
                <div>Guru yang Diobservasi,</div>
                <div class="signature-space"></div>
                <div class="signature-name"><?= esc($record['teacher_name'] ?? '—') ?></div>
                <div class="signature-nip">Guru Pengampu</div>
            </div>
            <div class="signature-box">
                <div>Supervisor / Penilai,</div>
                <div class="signature-space"></div>
                <div class="signature-name"><?= esc($record['supervisor_name'] ?? '—') ?></div>
                <div class="signature-nip">Tim Penjaminan Mutu</div>
            </div>
            <div class="signature-box">
                <div>Mengetahui,</div>
                <div class="signature-space"></div>
                <div class="signature-name">Kepala Sekolah</div>
                <div class="signature-nip">Kepala <?= esc($unit['name'] ?? 'Sekolah') ?></div>
            </div>
        </div>

    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
