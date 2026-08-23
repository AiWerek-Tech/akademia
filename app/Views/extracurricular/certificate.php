<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Sertifikat Penghargaan') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --primary-navy: #0f172a;
            --accent-gold: #b45309;
            --light-gold: #fef3c7;
            --border-gold: #d97706;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 20px 0;
            color: #1e293b;
        }
        .cert-container {
            width: 297mm;
            min-height: 210mm;
            margin: 0 auto;
            background: #ffffff;
            position: relative;
            padding: 16mm 20mm;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            box-sizing: border-box;
        }
        .cert-outer-border {
            border: 4px solid var(--primary-navy);
            height: 100%;
            min-height: 178mm;
            padding: 6px;
            position: relative;
        }
        .cert-inner-border {
            border: 1.5px solid var(--border-gold);
            height: 100%;
            min-height: 174mm;
            padding: 25px 35px;
            position: relative;
            background: radial-gradient(circle at center, #ffffff 60%, #fffdf7 100%);
        }
        .corner-ribbon {
            position: absolute;
            width: 32px;
            height: 32px;
            border-color: var(--border-gold);
        }
        .corner-tl { top: 4px; left: 4px; border-top: 3px solid; border-left: 3px solid; }
        .corner-tr { top: 4px; right: 4px; border-top: 3px solid; border-right: 3px solid; }
        .corner-bl { bottom: 4px; left: 4px; border-bottom: 3px solid; border-left: 3px solid; }
        .corner-br { bottom: 4px; right: 4px; border-bottom: 3px solid; border-right: 3px solid; }
        
        .font-cinzel { font-family: 'Cinzel', serif; }
        .font-playfair { font-family: 'Playfair Display', Georgia, serif; }

        .cert-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .cert-school-name {
            font-family: 'Cinzel', serif;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--primary-navy);
            text-transform: uppercase;
        }
        .cert-main-title {
            font-family: 'Cinzel', serif;
            font-size: 28px;
            font-weight: 800;
            color: var(--border-gold);
            letter-spacing: 3px;
            margin-top: 6px;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        .cert-subtitle {
            font-size: 11px;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 600;
        }
        .cert-no {
            font-size: 10px;
            font-family: monospace;
            color: #94a3b8;
            margin-top: 4px;
        }
        .student-name-box {
            margin: 20px auto 14px;
            text-align: center;
        }
        .student-name {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-navy);
            border-bottom: 2px solid #cbd5e1;
            display: inline-block;
            padding: 0 40px 6px;
        }
        .cert-body-text {
            font-size: 14px;
            line-height: 1.6;
            color: #334155;
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        .badge-pill-custom {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: var(--light-gold);
            color: var(--accent-gold);
            border: 1px solid #fde68a;
            padding: 6px 20px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 13px;
            margin: 12px 0;
        }
        .cert-footer {
            margin-top: 35px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 0 30px;
        }
        .sig-block {
            text-align: center;
            width: 220px;
        }
        .sig-line {
            border-bottom: 1px solid #64748b;
            margin-top: 55px;
            margin-bottom: 5px;
        }
        .sig-name {
            font-weight: 700;
            font-size: 13px;
            color: var(--primary-navy);
        }
        .sig-role {
            font-size: 11px;
            color: #64748b;
        }
        .qr-section {
            text-align: center;
        }
        .qr-box {
            display: inline-block;
            padding: 6px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }
        .token-text {
            font-size: 9px;
            font-family: monospace;
            color: #94a3b8;
            margin-top: 4px;
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
            .cert-container {
                box-shadow: none !important;
                width: 100% !important;
                min-height: 100vh !important;
                margin: 0 !important;
                padding: 10mm 15mm !important;
            }
            @page {
                size: A4 landscape;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Control Toolbar (Screen Only) -->
    <div class="container no-print mb-4" style="max-width: 297mm;">
        <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm border">
            <div class="d-flex align-items-center gap-2">
                <a href="<?= base_url('extracurricular/' . $cert['program']['id'] . '/reports') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i data-lucide="arrow-left" class="w-4 h-4 d-inline-block me-1"></i> Kembali ke Laporan
                </a>
                <span class="text-muted small">|</span>
                <span class="fw-bold text-gray-900 small">Pratinjau Piagam & Sertifikat Resmi</span>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary shadow-sm rounded-pill px-4" onclick="window.print()">
                    <i data-lucide="printer" class="w-4 h-4 d-inline-block me-1"></i> Cetak Dokumen (A4 Landscape)
                </button>
            </div>
        </div>
    </div>

    <!-- Official Certificate Layout -->
    <div class="cert-container">
        <div class="cert-outer-border">
            <div class="cert-inner-border">
                <div class="corner-ribbon corner-tl"></div>
                <div class="corner-ribbon corner-tr"></div>
                <div class="corner-ribbon corner-bl"></div>
                <div class="corner-ribbon corner-br"></div>

                <!-- Header -->
                <div class="cert-header">
                    <div class="cert-school-name"><?= esc($cert['unit_name']) ?> — WMVAA ACADEMIA</div>
                    <div class="cert-main-title"><?= ! empty($cert['achievement']) ? 'PIAGAM PENGHARGAAN & PRESTASI' : 'SERTIFIKAT TANDA KECAKAPAN' ?></div>
                    <div class="cert-subtitle">Certificate of Excellence & Character Honor</div>
                    <div class="cert-no">Nomor: <?= esc($cert['certificate_no']) ?></div>
                </div>

                <!-- Recipient Info -->
                <div class="text-center text-muted small text-uppercase tracking-wider" style="letter-spacing: 2px;">
                    Diberikan secara terhormat kepada:
                </div>

                <div class="student-name-box">
                    <div class="student-name"><?= esc($cert['student']['full_name']) ?></div>
                    <div class="text-muted small mt-1">
                        Kelas: <strong><?= esc($cert['student']['classroom_name'] ?? '—') ?></strong> | 
                        Peran: <strong><?= esc($cert['member']['role'] ?? 'MEMBER') ?></strong>
                    </div>
                </div>

                <!-- Narrative / Citation -->
                <div class="cert-body-text">
                    <?php if (! empty($cert['achievement'])): ?>
                        Telah menunjukkan dedikasi, integritas karakter, dan keunggulan kompetensi dalam penguasaan:
                        <div>
                            <span class="badge-pill-custom">
                                <i data-lucide="award" class="w-4 h-4"></i>
                                <?= esc($cert['achievement']['competency_name'] ?? 'Kompetensi Khusus') ?>
                                <?php if (! empty($cert['achievement']['level'])): ?>
                                    — Level <?= esc($cert['achievement']['level']) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php if (! empty($cert['achievement']['remarks'])): ?>
                            <p class="mb-0 text-muted fst-italic">"<?= esc($cert['achievement']['remarks']) ?>"</p>
                        <?php endif; ?>
                    <?php else: ?>
                        Telah menyelesaikan seluruh rangkaian kegiatan, latihan pembinaan karakter, dan pembentukan keterampilan pada program ekstrakurikuler <strong><?= esc($cert['program']['title']) ?></strong> dengan penuh semangat dan keteladanan.
                    <?php endif; ?>
                </div>

                <!-- Footer & Signatures -->
                <div class="cert-footer">
                    <div class="sig-block">
                        <div class="sig-role">Pembina Ekstrakurikuler</div>
                        <div class="sig-line"></div>
                        <div class="sig-name"><?= esc($cert['coach_name']) ?></div>
                        <div class="sig-role"><?= esc($cert['program']['title']) ?></div>
                    </div>

                    <div class="qr-section">
                        <div class="qr-box">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=72x72&data=<?= urlencode($cert['verification_token']) ?>" alt="QR Verification" width="64" height="64">
                        </div>
                        <div class="token-text">VERIFIKASI DIGITAL: <?= esc(substr($cert['verification_token'], 0, 12)) ?></div>
                        <div class="text-muted" style="font-size: 9px;"><?= esc($cert['issue_date']) ?></div>
                    </div>

                    <div class="sig-block">
                        <div class="sig-role">Kepala Satuan Pendidikan</div>
                        <div class="sig-line"></div>
                        <div class="sig-name"><?= esc($cert['headmaster_name']) ?></div>
                        <div class="sig-role"><?= esc($cert['unit_name']) ?></div>
                    </div>
                </div>

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
