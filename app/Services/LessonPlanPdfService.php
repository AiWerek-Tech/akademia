<?php

namespace App\Services;

use Config\Database;
use Dompdf\Dompdf;
use Dompdf\Options;

class LessonPlanPdfService
{
    /**
     * Generate a PDF file for the given lesson plan and return its absolute path.
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

        // Resources
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

        // Build HTML
        $html = self::buildHtml($planData, $plan, $objectives, $stages, $activities, $assessments, $rubricsByAssessment, $allResources);

        // Render PDF
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Save to writable/exports/
        $filename = 'RPP-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $planData['subject_name'] ?? 'export')
            . '-' . ($planData['date'] ?? date('Y-m-d'))
            . '-Rev' . (int) ($planData['revision_number'] ?? 1)
            . '.pdf';
        $tempPath = WRITEPATH . 'exports/' . $filename;

        $dir = dirname($tempPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($tempPath, $dompdf->output());

        return $tempPath;
    }

    private static function buildHtml(
        array $plan,
        array $summary,
        array $objectives,
        array $stages,
        array $activities,
        array $assessments,
        array $rubricsByAssessment,
        array $allResources
    ): string {
        $totalMinutes = (int) ($summary['total_estimated_minutes'] ?? 0);

        $html = '<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
@page { margin: 55px 50px 60px 50px; }
body { font-family: DejaVu Sans, sans-serif; color: #1a1a1a; font-size: 9.5pt; line-height: 1.45; margin: 0; }
.school-header { text-align: center; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 10px; }
.school-name { font-size: 13pt; font-weight: bold; text-transform: uppercase; }
.school-unit { font-size: 11pt; font-weight: bold; }
.school-address { font-size: 8pt; font-style: italic; color: #555; }
.doc-title { text-align: center; font-size: 12pt; font-weight: bold; text-transform: uppercase; margin: 10px 0 3px; }
.doc-subtitle { text-align: center; font-size: 9.5pt; font-style: italic; margin-bottom: 10px; color: #444; }
.meta-table { width: 100%; border-collapse: collapse; font-size: 9.5pt; margin-bottom: 8px; }
.meta-table td { padding: 1.5px 4px; vertical-align: top; }
.meta-label { font-weight: bold; width: 18%; }
.section-title { font-size: 10.5pt; font-weight: bold; background: #e8e8e8; border: 1px solid #999; padding: 3px 6px; margin: 10px 0 5px; text-transform: uppercase; }
.field-label { font-weight: bold; font-size: 9pt; margin-top: 3px; }
.field-value { font-size: 9pt; margin-bottom: 4px; min-height: 12px; border-bottom: 1px solid #ddd; padding-bottom: 2px; }
.empty { color: #999; font-style: italic; }
table.data { width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-bottom: 8px; }
table.data th, table.data td { border: 1px solid #666; padding: 3px 5px; vertical-align: top; }
table.data th { background: #e8e8e8; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 8pt; }
table.data td.center { text-align: center; }
.stage-block { border: 1px solid #666; margin-bottom: 6px; page-break-inside: avoid; }
.stage-header { background: #e8e8e8; border-bottom: 1px solid #666; padding: 3px 6px; font-weight: bold; font-size: 9.5pt; }
.stage-body { padding: 4px 6px; font-size: 9pt; }
.stage-body .desc { margin-bottom: 4px; }
.stage-body .notes { font-style: italic; font-size: 8.5pt; color: #555; }
.sig-table { width: 100%; margin-top: 25px; font-size: 9.5pt; }
.sig-table td { text-align: center; vertical-align: top; width: 33%; padding-top: 5px; }
.sig-line { border-bottom: 1px solid #000; width: 150px; display: inline-block; margin-bottom: 2px; }
.footer-text { margin-top: 15px; font-size: 7pt; color: #999; text-align: center; border-top: 1px solid #ccc; padding-top: 3px; }
</style>
</head>
<body>';

        // ── HEADER ──
        $html .= '<div class="school-header">';
        $html .= '<div class="school-name">YAYASAN PENDIDIKAN ADVENT SOGOKMO</div>';
        $html .= '<div class="school-unit">' . self::h($plan['unit_name'] ?? '') . '</div>';
        $html .= '<div class="school-address">Alamat: Jl. Raya Sogokmo, Wamena, Jayawijaya, Papua Pegunungan</div>';
        $html .= '</div>';

        // ── TITLE ──
        $html .= '<div class="doc-title">RENCANA PELAKSANAAN PEMBELAJARAN (RPP)</div>';
        $html .= '<div class="doc-subtitle">Modul Ajar Pembelajaran Mendalam — ' . self::h($plan['subject_name'] ?? '') . '</div>';

        // ── META ──
        $html .= '<table class="meta-table">';
        $metaRows = [
            ['Mata Pelajaran', $plan['subject_name'] ?? '-', 'Kelas / Fase', $plan['grade_name'] ?? '-'],
            ['Guru Pengampu', $plan['teacher_name'] ?? '-', 'Tahun / Periode', $plan['period_name'] ?? '-'],
            ['Pertemuan Ke-', 'Ke-' . (int) $plan['session_number'], 'Tanggal', date('l, d F Y', strtotime($plan['date']))],
            ['Alokasi Waktu', $totalMinutes . ' menit', 'Sumber', $plan['source_type'] ?? 'CUSTOM'],
            ['Label Sesi', $plan['session_label'] ?? 'Pertemuan ' . (int) $plan['session_number'], '', ''],
        ];
        foreach ($metaRows as $row) {
            $html .= '<tr>';
            $html .= '<td class="meta-label">' . self::h($row[0]) . '</td><td style="width:2%">:</td>';
            $html .= '<td style="width:30%">' . self::h($row[1]) . '</td>';
            if ($row[2] !== '') {
                $html .= '<td class="meta-label">' . self::h($row[2]) . '</td><td style="width:2%">:</td>';
                $html .= '<td style="width:30%">' . self::h($row[3]) . '</td>';
            }
            $html .= '</tr>';
        }
        if (! empty($plan['unit_title'])) {
            $html .= '<tr><td class="meta-label">Unit / Bab</td><td>:</td><td colspan="4">' . self::h($plan['unit_title']) . '</td></tr>';
        }
        $html .= '</table>';

        // ── I. IDENTIFIKASI ──
        $html .= '<div class="section-title">I. Identifikasi &amp; Konteks Pembelajaran</div>';
        $identFields = [
            'Identifikasi Konteks' => $plan['identification_notes'] ?? '',
            'Kesiapan Peserta Didik' => $plan['learner_readiness'] ?? '',
            'Karakteristik Materi' => $plan['material_characteristics'] ?? '',
            'Dimensi Profil Lulusan' => $plan['graduate_profile_dimensions'] ?? '',
        ];
        foreach ($identFields as $label => $value) {
            $html .= '<div class="field-label">' . self::h($label) . '</div>';
            $html .= '<div class="field-value">' . ($value ? nl2br(self::h($value)) : '<span class="empty">—</span>') . '</div>';
        }

        // ── II. TUJUAN PEMBELAJARAN ──
        $html .= '<div class="section-title">II. Tujuan Pembelajaran (TP)</div>';
        if ($objectives !== []) {
            $html .= '<table class="data"><thead><tr><th style="width:5%">No.</th><th style="width:15%">Kode TP</th><th>Pernyataan Tujuan</th><th style="width:12%">Peran</th></tr></thead><tbody>';
            foreach ($objectives as $i => $obj) {
                $html .= '<tr><td class="center">' . ($i + 1) . '</td><td>' . self::h($obj['code'] ?? '-') . '</td><td>' . self::h($obj['statement'] ?? '-') . '</td><td class="center">' . self::h($obj['role'] ?? 'PRIMARY') . '</td></tr>';
            }
            $html .= '</tbody></table>';
        } else {
            $html .= '<p class="empty">Belum ada tujuan pembelajaran yang dialokasikan.</p>';
        }

        // ── III. DESAIN ──
        $html .= '<div class="section-title">III. Desain Pembelajaran Mendalam (Deep Learning)</div>';
        $designFields = [
            'Praktik Pedagogis' => $plan['pedagogical_practice'] ?? '',
            'Kemitraan Pembelajaran' => $plan['learning_partnership'] ?? '',
            'Lingkungan Pembelajaran' => $plan['learning_environment'] ?? '',
            'Pemanfaatan Digital' => $plan['digital_utilization'] ?? '',
            'Koneksi Antarmapel' => $plan['interdisciplinary_notes'] ?? '',
        ];
        foreach ($designFields as $label => $value) {
            $html .= '<div class="field-label">' . self::h($label) . '</div>';
            $html .= '<div class="field-value">' . ($value ? nl2br(self::h($value)) : '<span class="empty">—</span>') . '</div>';
        }

        // ── IV. TAHAPAN ──
        $html .= '<div class="section-title">IV. Tahapan Pengalaman Belajar (Understand · Apply · Reflect)</div>';
        if ($stages !== []) {
            foreach ($stages as $s) {
                $type = strtoupper($s['stage_type']);
                $prefix = match ($type) {
                    'MEMAHAMI' => 'MEMAHAMI',
                    'MENGAPLIKASI' => 'MENGAPLIKASI',
                    'MEREFLEKSI' => 'MEREFLEKSI',
                    default => $type,
                };
                $title = $prefix . ' — ' . ($s['title'] ?? $type);
                if (! empty($s['estimated_minutes'])) {
                    $title .= ' (' . (int) $s['estimated_minutes'] . ' menit)';
                }

                $html .= '<div class="stage-block">';
                $html .= '<div class="stage-header">' . self::h($title) . '</div>';
                $html .= '<div class="stage-body">';
                if (! empty($s['description'])) {
                    $html .= '<div class="desc">' . nl2br(self::h($s['description'])) . '</div>';
                }
                if (! empty($s['notes'])) {
                    $html .= '<div class="notes">Catatan: ' . nl2br(self::h($s['notes'])) . '</div>';
                }

                // Activities for this stage
                $stageActivities = array_filter($activities, fn($a) => (int) ($a['lesson_plan_stage_id'] ?? 0) === (int) $s['id']);
                if ($stageActivities !== []) {
                    $html .= '<table class="data"><thead><tr><th style="width:5%">No.</th><th>Aktivitas</th><th style="width:15%">Moda</th><th style="width:15%">Pengelompokan</th><th style="width:10%">Waktu</th></tr></thead><tbody>';
                    foreach ($stageActivities as $i => $act) {
                        $actText = self::h($act['custom_title'] ?? '-');
                        if (! empty($act['custom_description'])) {
                            $actText .= '<br><span style="font-size:8pt">' . nl2br(self::h($act['custom_description'])) . '</span>';
                        }
                        if (! empty($act['graduate_profile_alignment'])) {
                            $actText .= '<br><span style="font-size:8pt;font-style:italic">Profil Lulusan: ' . self::h($act['graduate_profile_alignment']) . '</span>';
                        }
                        if (! empty($act['teacher_notes'])) {
                            $actText .= '<br><span style="font-size:8pt;color:#555"><em>Guru: ' . nl2br(self::h($act['teacher_notes'])) . '</em></span>';
                        }
                        $html .= '<tr><td class="center">' . ($i + 1) . '</td><td>' . $actText . '</td><td class="center">' . self::h($act['delivery_mode'] ?? '-') . '</td><td class="center">' . self::h($act['grouping_mode'] ?? '-') . '</td><td class="center">' . (! empty($act['estimated_minutes']) ? (int) $act['estimated_minutes'] . ' mnt' : '-') . '</td></tr>';
                    }
                    $html .= '</tbody></table>';
                }
                $html .= '</div></div>';
            }

            // Unlinked activities
            $unlinked = array_filter($activities, fn($a) => empty($a['lesson_plan_stage_id']));
            if ($unlinked !== []) {
                $html .= '<div class="field-label" style="margin-top:6px">Aktivitas Tanpa Tahapan</div>';
                $html .= '<table class="data"><thead><tr><th style="width:5%">No.</th><th>Aktivitas</th><th style="width:15%">Moda</th><th style="width:10%">Waktu</th></tr></thead><tbody>';
                foreach ($unlinked as $i => $act) {
                    $html .= '<tr><td class="center">' . ($i + 1) . '</td><td>' . self::h($act['custom_title'] ?? '-') . '</td><td class="center">' . self::h($act['delivery_mode'] ?? '-') . '</td><td class="center">' . (! empty($act['estimated_minutes']) ? (int) $act['estimated_minutes'] . ' mnt' : '-') . '</td></tr>';
                }
                $html .= '</tbody></table>';
            }
        } else {
            $html .= '<p class="empty">Tahapan pengalaman belajar belum disusun.</p>';
        }

        // ── V. ASESMEN ──
        $html .= '<div class="section-title">V. Rencana Asesmen</div>';
        if ($assessments !== []) {
            $html .= '<table class="data"><thead><tr><th style="width:5%">No.</th><th style="width:13%">Tujuan</th><th>Metode &amp; Bentuk</th><th>Kriteria / Indikator</th><th>Catatan</th></tr></thead><tbody>';
            foreach ($assessments as $i => $asm) {
                $html .= '<tr><td class="center">' . ($i + 1) . '</td><td class="center">' . self::h($asm['assessment_purpose'] ?? '-') . '</td><td>' . self::h($asm['recommended_method'] ?? '-') . '</td><td>' . nl2br(self::h($asm['criteria_reference'] ?? '-')) . '</td><td>' . nl2br(self::h($asm['notes'] ?? '-')) . '</td></tr>';
            }
            $html .= '</tbody></table>';

            // Rubrics
            $hasRubrics = false;
            foreach ($assessments as $asm) {
                if (! empty($rubricsByAssessment[$asm['uuid']])) {
                    $hasRubrics = true;
                    break;
                }
            }
            if ($hasRubrics) {
                $html .= '<div class="field-label">Rubrik Penilaian</div>';
                foreach ($assessments as $asm) {
                    $rubrics = $rubricsByAssessment[$asm['uuid']] ?? [];
                    if ($rubrics === []) {
                        continue;
                    }
                    $html .= '<div style="font-weight:bold;font-size:9pt;margin-bottom:3px">' . self::h($asm['recommended_method']) . ' (' . self::h($asm['assessment_purpose']) . '):</div>';
                    $html .= '<table class="data"><thead><tr><th style="width:5%">No.</th><th>Kriteria Penilaian</th><th>Tingkatan Rubrik</th></tr></thead><tbody>';
                    foreach ($rubrics as $j => $rub) {
                        $html .= '<tr><td class="center">' . ($j + 1) . '</td><td>' . self::h($rub['criterion_description'] ?? '-') . '</td><td style="font-size:8pt">' . self::h($rub['rubric_levels'] ?? '-') . '</td></tr>';
                    }
                    $html .= '</tbody></table>';
                }
            }
        } else {
            $html .= '<p class="empty">Belum ada rencana asesmen.</p>';
        }

        // ── VI. SUMBER DAYA ──
        if ($allResources !== []) {
            $html .= '<div class="section-title">VI. Sumber Daya &amp; Kebutuhan Sarana</div>';
            $html .= '<table class="data"><thead><tr><th style="width:5%">No.</th><th>Sumber Daya</th><th style="width:20%">Terkait Aktivitas</th><th style="width:10%">Jumlah</th><th style="width:10%">Wajib</th></tr></thead><tbody>';
            foreach ($allResources as $i => $res) {
                $html .= '<tr><td class="center">' . ($i + 1) . '</td><td>' . self::h($res['resource_title'] ?? $res['custom_description'] ?? '-') . '</td><td>' . self::h($res['activity_title'] ?? '-') . '</td><td class="center">' . (int) ($res['quantity'] ?? 1) . '</td><td class="center">' . (($res['is_required'] ?? 1) ? 'Ya' : 'Opsional') . '</td></tr>';
            }
            $html .= '</tbody></table>';
        }

        // ── TANDA TANGAN ──
        $html .= '<table class="sig-table">';
        $html .= '<tr>';
        $html .= '<td><strong>Guru Pengampu</strong><br><br><br><br><span class="sig-line"></span><br>' . self::h($plan['teacher_name'] ?? '________________') . '<br><span style="font-size:8pt">NIP. ________________</span></td>';
        $html .= '<td><strong>Kepala Sekolah</strong><br><br><br><br><span class="sig-line"></span><br>________________________<br><span style="font-size:8pt">NIP. ________________</span></td>';
        $html .= '<td><strong>Mengetahui,<br>Kepala Unit</strong><br><br><br><span class="sig-line"></span><br>________________________<br><span style="font-size:8pt">NIP. ________________</span></td>';
        $html .= '</tr></table>';

        // Footer
        $html .= '<div class="footer-text">Dokumen ini dihasilkan oleh WMVAA Academia IALOS Education · Revisi ' . (int) ($plan['revision_number'] ?? 1) . ' · ' . date('d/m/Y H:i') . '</div>';

        $html .= '</body></html>';

        return $html;
    }

    private static function h(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
