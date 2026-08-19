<?php

namespace App\Services;

use Config\Database;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Style\Border;

class LessonPlanDocxService
{
    /**
     * Generate a DOCX file for the given lesson plan and return its absolute path.
     * The caller is responsible for sending the file and cleaning up.
     */
    public static function generate(string $planUuid): string
    {
        $plan = LessonPlanService::summary($planUuid);
        $planData = $plan['plan'];
        $db = Database::connect();
        $planId = (int) $planData['id'];

        // Gather all data
        $objectives = $db->table('lesson_plan_objectives lpo')
            ->join('learning_objectives_tp tp', 'tp.id=lpo.learning_objective_id')
            ->where('lpo.lesson_plan_id', $planId)
            ->orderBy('lpo.sequence_order')
            ->get()->getResultArray();

        $stages = $db->table('lesson_plan_stages')
            ->where('lesson_plan_id', $planId)
            ->orderBy('sequence_order')
            ->get()->getResultArray();

        $activities = $db->table('lesson_plan_activities')
            ->where('lesson_plan_id', $planId)
            ->orderBy('sequence_order')
            ->get()->getResultArray();

        $assessments = $db->table('lesson_plan_assessments')
            ->where('lesson_plan_id', $planId)
            ->orderBy('sequence_order')
            ->get()->getResultArray();

        // Rubrics grouped by assessment
        $rubricsByAssessment = [];
        $assessIds = array_column($assessments, 'id');
        if ($assessIds !== []) {
            $allRubrics = $db->table('lesson_plan_assessment_rubrics')
                ->whereIn('lesson_plan_assessment_id', $assessIds)
                ->orderBy('sequence_order')
                ->get()->getResultArray();
            $uuidMap = array_column($assessments, 'uuid', 'id');
            foreach ($allRubrics as $r) {
                $assessUuid = $uuidMap[(int) $r['lesson_plan_assessment_id']] ?? '';
                $rubricsByAssessment[$assessUuid][] = $r;
            }
        }

        // Build DOCX
        $phpWord = new PhpWord();
        $phpWord->getDocInfo()
            ->setCreator('IALOS Education WMVAA Academia')
            ->setTitle('RPP - ' . ($planData['session_label'] ?? 'Modul Ajar'))
            ->setSubject('Rencana Pelaksanaan Pembelajaran');

        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(11);

        // Styles
        $phpWord->addTitleStyle(1, ['name' => 'Times New Roman', 'size' => 14, 'bold' => true], ['spaceBefore' => 240, 'spaceAfter' => 120, 'keepNext' => true]);
        $phpWord->addTitleStyle(2, ['name' => 'Times New Roman', 'size' => 12, 'bold' => true], ['spaceBefore' => 180, 'spaceAfter' => 80, 'keepNext' => true]);

        $section = $phpWord->addSection([
            'pageSizeW' => 11906, 'pageSizeH' => 16838,
            'marginTop' => (int) round(Converter::cmToTwip(2)),
            'marginBottom' => (int) round(Converter::cmToTwip(2)),
            'marginLeft' => (int) round(Converter::cmToTwip(2.5)),
            'marginRight' => (int) round(Converter::cmToTwip(2)),
        ]);

        // Footer with page number
        $footer = $section->addFooter();
        $footer->addPreserveText('RPP — ' . esc($planData['subject_name'] ?? '') . '  •  Halaman {PAGE} dari {NUMPAGES}', ['size' => 8, 'color' => '666666'], ['alignment' => 'center']);

        // ── HEADER / KOP ──
        $headerTable = $section->addTable(['borderSize' => 0, 'width' => 100 * 50, 'align' => 'center']);
        $headerTable->addRow();
        $logoPath = WRITEPATH . '../assets/img/logo.png';
        if (is_file($logoPath)) {
            $headerTable->addCell(1500)->addImage($logoPath, ['width' => 50, 'height' => 50]);
        } else {
            $headerTable->addCell(1500);
        }
        $cell = $headerTable->addCell(8000);
        $cell->addText('YAYASAN PENDIDIKAN ADVENT SOGOKMO', ['bold' => true, 'size' => 14, 'name' => 'Times New Roman']);
        $cell->addText($planData['unit_name'] ?? '', ['bold' => true, 'size' => 12, 'name' => 'Times New Roman']);
        $cell->addText('Alamat: Jl. Raya Sogokmo, Wamena, Jayawijaya, Papua Pegunungan', ['italic' => true, 'size' => 9]);

        // Double line separator
        $section->addParagraphStyle('separator', ['borderBottom' => ['color' => '000000', 'size' => 6, 'space' => 1]]);
        $section->addText('', 'separator');

        // ── TITLE ──
        $section->addText('RENCANA PELAKSANAAN PEMBELAJARAN (RPP)', ['bold' => true, 'size' => 13, 'name' => 'Times New Roman', 'alignment' => 'center']);
        $section->addText('Modul Ajar Pembelajaran Mendalam — ' . esc($planData['subject_name'] ?? ''), ['italic' => true, 'size' => 11, 'alignment' => 'center']);
        $section->addTextBreak(4);

        // ── META TABLE ──
        self::addMetaTable($section, [
            ['Mata Pelajaran', $planData['subject_name'] ?? '-', 'Kelas / Fase', $planData['grade_name'] ?? '-'],
            ['Guru Pengampu', $planData['teacher_name'] ?? '-', 'Tahun / Periode', $planData['period_name'] ?? '-'],
            ['Pertemuan Ke-', 'Ke-' . (int) $planData['session_number'], 'Tanggal', date('l, d F Y', strtotime($planData['date']))],
            ['Alokasi Waktu', (int) ($plan['total_estimated_minutes'] ?? 0) . ' menit', 'Sumber', $planData['source_type'] ?? 'CUSTOM'],
            ['Label Sesi', $planData['session_label'] ?? 'Pertemuan ' . (int) $planData['session_number'], '', ''],
        ]);

        // ── I. IDENTIFIKASI & KONTEKS ──
        $section->addTitle('I. Identifikasi & Konteks Pembelajaran', 1);
        foreach ([
            'Identifikasi Konteks' => $planData['identification_notes'] ?? '',
            'Kesiapan Peserta Didik' => $planData['learner_readiness'] ?? '',
            'Karakteristik Materi' => $planData['material_characteristics'] ?? '',
            'Dimensi Profil Lulusan' => $planData['graduate_profile_dimensions'] ?? '',
        ] as $label => $value) {
            $section->addText($label . ':', ['bold' => true, 'size' => 10]);
            $section->addText($value ?: '—', ['size' => 10, 'color' => $value ? '000000' : '999999']);
            $section->addTextBreak(1);
        }

        // ── II. TUJUAN PEMBELAJARAN ──
        $section->addTitle('II. Tujuan Pembelajaran (TP)', 1);
        if ($objectives !== []) {
            $table = self::addBorderedTable($section, ['No.', 'Kode TP', 'Pernyataan Tujuan', 'Peran']);
            foreach ($objectives as $i => $obj) {
                $row = $table->addRow();
                $row->addCell(600)->addText((string) ($i + 1), ['alignment' => 'center', 'size' => 10]);
                $row->addCell(1500)->addText($obj['code'] ?? '-', ['size' => 9, 'name' => 'Courier New']);
                $row->addCell(5500)->addText($obj['statement'] ?? '-', ['size' => 10]);
                $row->addCell(1400)->addText($obj['role'] ?? 'PRIMARY', ['size' => 9, 'alignment' => 'center']);
            }
        } else {
            $section->addText('Belum ada tujuan pembelajaran yang dialokasikan.', ['italic' => true, 'color' => '999999']);
        }

        // ── III. DESAIN PEMBELAJARAN ──
        $section->addTitle('III. Desain Pembelajaran Mendalam (Deep Learning)', 1);
        foreach ([
            'Praktik Pedagogis' => $planData['pedagogical_practice'] ?? '',
            'Kemitraan Pembelajaran' => $planData['learning_partnership'] ?? '',
            'Lingkungan Pembelajaran' => $planData['learning_environment'] ?? '',
            'Pemanfaatan Digital' => $planData['digital_utilization'] ?? '',
            'Koneksi Antarmapel' => $planData['interdisciplinary_notes'] ?? '',
        ] as $label => $value) {
            $section->addText($label . ':', ['bold' => true, 'size' => 10]);
            $section->addText($value ?: '—', ['size' => 10, 'color' => $value ? '000000' : '999999']);
            $section->addTextBreak(1);
        }

        // ── IV. TAHAPAN PENGALAMAN BELAJAR ──
        $section->addTitle('IV. Tahapan Pengalaman Belajar (Understand · Apply · Reflect)', 1);
        if ($stages !== []) {
            foreach ($stages as $s) {
                $type = strtoupper($s['stage_type']);
                $prefix = match ($type) {
                    'MEMAHAMI' => '🔵 MEMAHAMI',
                    'MENGAPLIKASI' => '🟡 MENGAPLIKASI',
                    'MEREFLEKSI' => '🟢 MEREFLEKSI',
                    default => $type,
                };
                $title = $prefix . ' — ' . ($s['title'] ?? $type);
                if (! empty($s['estimated_minutes'])) {
                    $title .= ' (' . (int) $s['estimated_minutes'] . ' menit)';
                }
                $section->addText($title, ['bold' => true, 'size' => 11, 'color' => '1E293B']);
                if (! empty($s['description'])) {
                    $section->addText($s['description'], ['size' => 10]);
                }
                if (! empty($s['notes'])) {
                    $section->addText('Catatan: ' . $s['notes'], ['size' => 9, 'italic' => true, 'color' => '555555']);
                }

                // Activities for this stage
                $stageActivities = array_filter($activities, fn($a) => (int) ($a['lesson_plan_stage_id'] ?? 0) === (int) $s['id']);
                if ($stageActivities !== []) {
                    $table = self::addBorderedTable($section, ['No.', 'Aktivitas', 'Moda', 'Pengelompokan', 'Waktu']);
                    foreach ($stageActivities as $i => $act) {
                        $row = $table->addRow();
                        $row->addCell(500)->addText((string) ($i + 1), ['alignment' => 'center', 'size' => 10]);
                        $actText = $act['custom_title'] ?? '-';
                        if (! empty($act['custom_description'])) {
                            $actText .= "\n" . $act['custom_description'];
                        }
                        if (! empty($act['graduate_profile_alignment'])) {
                            $actText .= "\nProfil Lulusan: " . $act['graduate_profile_alignment'];
                        }
                        $row->addCell(4500)->addText($actText, ['size' => 9]);
                        $row->addCell(1500)->addText($act['delivery_mode'] ?? '-', ['size' => 9, 'alignment' => 'center']);
                        $row->addCell(1500)->addText($act['grouping_mode'] ?? '-', ['size' => 9, 'alignment' => 'center']);
                        $row->addCell(1000)->addText(! empty($act['estimated_minutes']) ? (int) $act['estimated_minutes'] . ' mnt' : '-', ['size' => 9, 'alignment' => 'center']);
                    }
                }
                $section->addTextBreak(2);
            }

            // Unlinked activities
            $unlinked = array_filter($activities, fn($a) => empty($a['lesson_plan_stage_id']));
            if ($unlinked !== []) {
                $section->addText('Aktivitas Tanpa Tahapan:', ['bold' => true, 'size' => 10]);
                $table = self::addBorderedTable($section, ['No.', 'Aktivitas', 'Moda', 'Waktu']);
                foreach ($unlinked as $i => $act) {
                    $row = $table->addRow();
                    $row->addCell(500)->addText((string) ($i + 1), ['alignment' => 'center', 'size' => 10]);
                    $row->addCell(5500)->addText($act['custom_title'] ?? '-', ['size' => 9]);
                    $row->addCell(1500)->addText($act['delivery_mode'] ?? '-', ['size' => 9, 'alignment' => 'center']);
                    $row->addCell(1000)->addText(! empty($act['estimated_minutes']) ? (int) $act['estimated_minutes'] . ' mnt' : '-', ['size' => 9, 'alignment' => 'center']);
                }
            }
        } else {
            $section->addText('Tahapan pengalaman belajar belum disusun.', ['italic' => true, 'color' => '999999']);
        }

        // ── V. ASESMEN ──
        $section->addTitle('V. Rencana Asesmen', 1);
        if ($assessments !== []) {
            $table = self::addBorderedTable($section, ['No.', 'Tujuan', 'Metode & Bentuk', 'Kriteria / Indikator', 'Catatan']);
            foreach ($assessments as $i => $asm) {
                $row = $table->addRow();
                $row->addCell(500)->addText((string) ($i + 1), ['alignment' => 'center', 'size' => 10]);
                $row->addCell(1200)->addText($asm['assessment_purpose'] ?? '-', ['size' => 9, 'alignment' => 'center']);
                $row->addCell(2500)->addText($asm['recommended_method'] ?? '-', ['size' => 9]);
                $row->addCell(2500)->addText($asm['criteria_reference'] ?? '-', ['size' => 9]);
                $row->addCell(2300)->addText($asm['notes'] ?? '-', ['size' => 9]);
            }

            // Rubrics
            $hasRubrics = false;
            foreach ($assessments as $asm) {
                if (! empty($rubricsByAssessment[$asm['uuid']])) {
                    $hasRubrics = true;
                    break;
                }
            }
            if ($hasRubrics) {
                $section->addText('Rubrik Penilaian:', ['bold' => true, 'size' => 11, 'spaceBefore' => 120]);
                foreach ($assessments as $asm) {
                    $rubrics = $rubricsByAssessment[$asm['uuid']] ?? [];
                    if ($rubrics === []) {
                        continue;
                    }
                    $section->addText($asm['recommended_method'] . ' (' . $asm['assessment_purpose'] . '):', ['bold' => true, 'size' => 10]);
                    $table = self::addBorderedTable($section, ['No.', 'Kriteria Penilaian', 'Tingkatan Rubrik']);
                    foreach ($rubrics as $j => $rub) {
                        $row = $table->addRow();
                        $row->addCell(500)->addText((string) ($j + 1), ['alignment' => 'center', 'size' => 10]);
                        $row->addCell(4500)->addText($rub['criterion_description'] ?? '-', ['size' => 9]);
                        $row->addCell(4000)->addText($rub['rubric_levels'] ?? '-', ['size' => 9]);
                    }
                    $section->addTextBreak(2);
                }
            }
        } else {
            $section->addText('Belum ada rencana asesmen.', ['italic' => true, 'color' => '999999']);
        }

        // ── VI. SUMBER DAYA ──
        $allResources = [];
        foreach ($activities as $a) {
            $aRes = $db->table('lesson_plan_activity_resources lar')
                ->select('lar.*, lr.title resource_title')
                ->join('learning_resources lr', 'lr.id = lar.learning_resource_id', 'left')
                ->where('lar.lesson_plan_activity_id', (int) $a['id'])
                ->get()->getResultArray();
            foreach ($aRes as $r) {
                $r['activity_title'] = $a['custom_title'] ?? '-';
                $allResources[] = $r;
            }
        }
        if ($allResources !== []) {
            $section->addTitle('VI. Sumber Daya & Kebutuhan Sarana', 1);
            $table = self::addBorderedTable($section, ['No.', 'Sumber Daya', 'Terkait Aktivitas', 'Jumlah', 'Wajib']);
            foreach ($allResources as $i => $res) {
                $row = $table->addRow();
                $row->addCell(500)->addText((string) ($i + 1), ['alignment' => 'center', 'size' => 10]);
                $row->addCell(3000)->addText($res['resource_title'] ?? $res['custom_description'] ?? '-', ['size' => 9]);
                $row->addCell(2500)->addText($res['activity_title'] ?? '-', ['size' => 9]);
                $row->addCell(1000)->addText((string) ($res['quantity'] ?? 1), ['size' => 9, 'alignment' => 'center']);
                $row->addCell(1000)->addText(($res['is_required'] ?? 1) ? 'Ya' : 'Opsional', ['size' => 9, 'alignment' => 'center']);
            }
        }

        // ── TANDA TANGAN ──
        $section->addTextBreak(6);
        $sigTable = $section->addTable(['borderSize' => 0, 'width' => 100 * 50]);
        $sigTable->addRow();
        $c1 = $sigTable->addCell(3300);
        $c1->addText('Guru Pengampu', ['bold' => true, 'size' => 11, 'alignment' => 'center']);
        $c1->addText("\n\n\n\n");
        $c1->addText('________________________', ['alignment' => 'center']);
        $c1->addText('NIP. ________________', ['size' => 9, 'alignment' => 'center']);

        $c2 = $sigTable->addCell(3400);
        $c2->addText('Kepala Sekolah', ['bold' => true, 'size' => 11, 'alignment' => 'center']);
        $c2->addText("\n\n\n\n");
        $c2->addText('________________________', ['alignment' => 'center']);
        $c2->addText('NIP. ________________', ['size' => 9, 'alignment' => 'center']);

        $c3 = $sigTable->addCell(3300);
        $c3->addText("Mengetahui,\nKepala Unit", ['bold' => true, 'size' => 11, 'alignment' => 'center']);
        $c3->addText("\n\n\n");
        $c3->addText('________________________', ['alignment' => 'center']);
        $c3->addText('NIP. ________________', ['size' => 9, 'alignment' => 'center']);

        // ── SAVE ──
        $filename = 'RPP-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $planData['subject_name'] ?? 'export')
            . '-' . ($planData['date'] ?? date('Y-m-d'))
            . '-Rev' . (int) ($planData['revision_number'] ?? 1)
            . '.docx';
        $tempPath = WRITEPATH . 'exports/' . $filename;

        // Ensure exports directory exists
        $dir = dirname($tempPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

        return $tempPath;
    }

    // ── Helpers ──

    private static function addMetaTable($section, array $rows): void
    {
        $table = $section->addTable(['borderSize' => 0, 'width' => 100 * 50]);
        foreach ($rows as $row) {
            $tr = $table->addRow();
            // Label 1
            $tr->addCell(2200, ['valign' => 'center'])->addText($row[0], ['bold' => true, 'size' => 10]);
            $tr->addCell(200, ['valign' => 'center'])->addText(':', ['size' => 10]);
            $tr->addCell(3300, ['valign' => 'center'])->addText($row[1], ['size' => 10]);
            // Label 2 (if exists)
            if ($row[2] !== '') {
                $tr->addCell(2200, ['valign' => 'center'])->addText($row[2], ['bold' => true, 'size' => 10]);
                $tr->addCell(200, ['valign' => 'center'])->addText(':', ['size' => 10]);
                $tr->addCell(3300, ['valign' => 'center'])->addText($row[3], ['size' => 10]);
            }
        }
    }

    private static function addBorderedTable($section, array $headers): \PhpOffice\PhpWord\Element\Table
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'width' => 100 * 50,
        ]);
        $headerRow = $table->addRow();
        foreach ($headers as $h) {
            $headerRow->addCell(null, ['bgColor' => 'E2E8F0'])->addText($h, [
                'bold' => true, 'size' => 9, 'alignment' => 'center',
                'name' => 'Times New Roman',
            ]);
        }
        return $table;
    }
}
