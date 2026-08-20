<?php

namespace App\Services;

/**
 * RubricGeneratorService — Generates standardized 4-tier assessment rubrics
 * based on Bloom's Revised Taxonomy (C1-C6) and Marzano levels.
 *
 * Tier Levels:
 *   1. Perlu Bimbingan (Needs Support) — 0–60
 *   2. Cukup (Developing)             — 61–75
 *   3. Baik (Achieved)                — 76–90
 *   4. Sangat Baik (Advanced)         — 91–100
 */
class RubricGeneratorService
{
    /**
     * Bloom's Revised Taxonomy verbs mapping to cognitive levels.
     */
    private const BLOOM_LEVELS = [
        'C1' => [
            'name'  => 'Mengingat (Remembering)',
            'verbs' => ['mengingat', 'menyebutkan', 'menuliskan', 'mendaftar', 'mengidentifikasi', 'menamai', 'mencocokkan'],
            'tiers' => [
                'needs_support' => 'Belum mampu mengingat atau menyebutkan konsep dasar meskipun telah diberi bimbingan.',
                'developing'    => 'Mampu menyebutkan sebagian kecil konsep dasar dengan bantuan stimulus atau petunjuk.',
                'achieved'      => 'Mampu menyebutkan dan menuliskan seluruh konsep dasar dengan tepat secara mandiri.',
                'advanced'      => 'Mampu mengingat seluruh konsep dasar dengan sangat lancar dan memberikan contoh kontekstual.',
            ],
        ],
        'C2' => [
            'name'  => 'Memahami (Understanding)',
            'verbs' => ['memahami', 'menjelaskan', 'menguraikan', 'merangkum', 'membedakan', 'mengklasifikasikan', 'mengilustrasikan'],
            'tiers' => [
                'needs_support' => 'Belum mampu menjelaskan makna atau konsep utama, masih terdapat miskonsepsi mendasar.',
                'developing'    => 'Mampu menjelaskan garis besar konsep dengan bantuan arahan, namun penjelasan belum runtut.',
                'achieved'      => 'Mampu menjelaskan dan menguraikan konsep dengan kata-kata sendiri secara tepat dan runtut.',
                'advanced'      => 'Mampu menguraikan konsep secara komprehensif, mengaitkannya dengan situasi nyata secara mendalam.',
            ],
        ],
        'C3' => [
            'name'  => 'Menerapkan (Applying)',
            'verbs' => ['menerapkan', 'menggunakan', 'mempraktikkan', 'mengoperasikan', 'menghitung', 'mendemonstrasikan', 'menjalankan'],
            'tiers' => [
                'needs_support' => 'Belum mampu menerapkan prosedur atau rumus yang tepat tanpa pendampingan intensif.',
                'developing'    => 'Mampu menerapkan prosedur pada kasus sederhana namun masih menemui kendala pada langkah lanjutan.',
                'achieved'      => 'Mampu menerapkan seluruh prosedur atau rumus dengan benar dan menghasilkan solusi yang tepat.',
                'advanced'      => 'Mampu menerapkan prosedur secara efisien, adaptif terhadap variasi masalah baru dengan presisi tinggi.',
            ],
        ],
        'C4' => [
            'name'  => 'Menganalisis (Analyzing)',
            'verbs' => ['menganalisis', 'membandingkan', 'menelaah', 'mengidentifikasi pola', 'mengurai', 'mendiagnosis', 'memeriksa'],
            'tiers' => [
                'needs_support' => 'Belum mampu mengenali hubungan sebab-akibat atau membedakan komponen masalah.',
                'developing'    => 'Mampu menelaah sebagian komponen masalah namun analisis hubungan antar-komponen belum utuh.',
                'achieved'      => 'Mampu menganalisis komponen masalah secara sistematis dan menarik kesimpulan yang valid.',
                'advanced'      => 'Mampu menganalisis pola kompleks, mengidentifikasi akar masalah, serta menyajikan analisis kritis multi-sudut pandang.',
            ],
        ],
        'C5' => [
            'name'  => 'Mengevaluasi (Evaluating)',
            'verbs' => ['mengevaluasi', 'menilai', 'mengkritisi', 'memvalidasi', 'memutuskan', 'menguji', 'mempertahankan'],
            'tiers' => [
                'needs_support' => 'Belum mampu memberikan justifikasi atau penilaian berbasis kriteria yang jelas.',
                'developing'    => 'Mampu memberikan penilaian namun argumen pendukung masih terbatas atau kurang objektif.',
                'achieved'      => 'Mampu mengevaluasi dan memberikan justifikasi berbasis kriteria serta data yang relevan.',
                'advanced'      => 'Mampu menyusun pertimbangan evaluatif kritis yang mendalam, objektif, dan mempertimbangkan konsekuensi jangka panjang.',
            ],
        ],
        'C6' => [
            'name'  => 'Mencipta (Creating)',
            'verbs' => ['mencipta', 'merancang', 'mengembangkan', 'membuat', 'menyusun', 'mengkonstruksi', 'memproduksi', 'menggubah'],
            'tiers' => [
                'needs_support' => 'Belum menghasilkan produk atau karya sesuai spesifikasi dasar yang ditentukan.',
                'developing'    => 'Menghasilkan karya yang memenuhi sebagian spesifikasi dasar namun masih memerlukan perbaikan struktur.',
                'achieved'      => 'Menghasilkan karya/produk lengkap, fungsional, dan sesuai seluruh kriteria perancangan.',
                'advanced'      => 'Menghasilkan karya yang sangat orisinal, bernilai estetika/fungsional tinggi, serta inovatif melebihi ekspektasi standar.',
            ],
        ],
    ];

    /**
     * Generate rubric descriptors for a given learning objective (TP) text.
     *
     * @param string $tpText Learning objective text or title
     * @return array Rubric descriptors and detected Bloom level
     */
    public function generateForObjective(string $tpText): array
    {
        $detectedLevel = $this->detectBloomLevel($tpText);
        $template = self::BLOOM_LEVELS[$detectedLevel];

        return [
            'detected_bloom_level' => $detectedLevel,
            'bloom_name'           => $template['name'],
            'objective_text'       => $tpText,
            'levels' => [
                [
                    'tier'        => 'NEEDS_SUPPORT',
                    'name'        => 'Perlu Bimbingan',
                    'score_range' => '0–60',
                    'descriptor'  => $template['tiers']['needs_support'],
                    'badge_class' => 'danger',
                ],
                [
                    'tier'        => 'DEVELOPING',
                    'name'        => 'Cukup / Berkembang',
                    'score_range' => '61–75',
                    'descriptor'  => $template['tiers']['developing'],
                    'badge_class' => 'warning',
                ],
                [
                    'tier'        => 'ACHIEVED',
                    'name'        => 'Baik / Tercapai',
                    'score_range' => '76–90',
                    'descriptor'  => $template['tiers']['achieved'],
                    'badge_class' => 'success',
                ],
                [
                    'tier'        => 'ADVANCED',
                    'name'        => 'Sangat Baik / Mahir',
                    'score_range' => '91–100',
                    'descriptor'  => $template['tiers']['advanced'],
                    'badge_class' => 'primary',
                ],
            ],
        ];
    }

    /**
     * Detect Bloom level by scanning verbs in the TP description.
     */
    public function detectBloomLevel(string $text): string
    {
        $lower = mb_strtolower($text);

        // Scan from C6 down to C1 (higher order verbs have priority)
        foreach (['C6', 'C5', 'C4', 'C3', 'C2', 'C1'] as $lvl) {
            foreach (self::BLOOM_LEVELS[$lvl]['verbs'] as $verb) {
                if (str_contains($lower, $verb)) {
                    return $lvl;
                }
            }
        }

        // Default to C2 (Understanding) if no specific verb is matched
        return 'C2';
    }
}
