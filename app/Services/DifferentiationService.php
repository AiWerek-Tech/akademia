<?php

namespace App\Services;

/**
 * DifferentiationService — Suggests differentiated learning strategies
 * (Diferensiasi Konten, Proses, Produk) tailored to learner readiness tiers
 * and subjects.
 */
class DifferentiationService
{
    /**
     * Generate 3-tiered differentiated instruction strategies for a lesson plan.
     *
     * @param string $topic      Lesson topic or TP description
     * @param string $subject    Subject name (e.g. Informatika, Matematika, Bahasa)
     * @param string $phase      Curriculum phase (e.g. D, E, F)
     * @return array Differentiated strategies for Content, Process, and Product
     */
    public function generateStrategies(string $topic, string $subject = 'Umum', string $phase = 'E'): array
    {
        return [
            'topic'   => $topic,
            'subject' => $subject,
            'phase'   => $phase,
            'dimensions' => [
                'content' => [
                    'label'       => 'Diferensiasi Konten',
                    'icon'        => 'book-open',
                    'description' => 'Variasi materi ajar dan tingkat kedalaman aksesibilitas konten.',
                    'tiers' => [
                        'tier_1' => [
                            'group'       => 'Kelompok Perlu Pendampingan',
                            'description' => 'Materi disajikan dengan panduan visual konkret, infografis ringkas, glosarium istilah penting, dan panduan langkah per langkah.',
                            'scaffold'    => 'Gunakan lembar kerja terstruktur (guided worksheet) dengan contoh pengerjaan yang sudah terisi sebagian.',
                        ],
                        'tier_2' => [
                            'group'       => 'Kelompok Reguler / Mandiri',
                            'description' => 'Materi standar berbasis buku teks, bahan bacaan digital/studi kasus, dan instruksi tertulis.',
                            'scaffold'    => 'Pertanyaan pemantik semi-mandiri dan diskusi kelompok kecil untuk pemecahan masalah.',
                        ],
                        'tier_3' => [
                            'group'       => 'Kelompok Pengayaan (Advanced)',
                            'description' => 'Materi eksploratif tingkat lanjut, jurnal/referensi industri, studi kasus nyata multi-variabel, dan problem-solving terbuka.',
                            'scaffold'    => 'Tantangan investigasi mandiri dengan batas parameter yang lebih luas (open-ended problem).',
                        ],
                    ],
                ],
                'process' => [
                    'label'       => 'Diferensiasi Proses',
                    'icon'        => 'activity',
                    'description' => 'Variasi cara siswa mengolah dan mengkonstruksi pemahaman konsep.',
                    'tiers' => [
                        'tier_1' => [
                            'group'       => 'Kelompok Perlu Pendampingan',
                            'description' => 'Bimbingan terarah (direct scaffolding) dari guru di meja asistensi khusus, demonstrasi berulang, dan peer-tutoring.',
                            'scaffold'    => 'Aktivitas dibagi menjadi chunk kecil berdurasi 10–15 menit dengan check-in berkala.',
                        ],
                        'tier_2' => [
                            'group'       => 'Kelompok Reguler / Mandiri',
                            'description' => 'Kolaborasi berpasangan (Think-Pair-Share) atau kelompok kooperatif dengan pembagian peran terstruktur.',
                            'scaffold'    => 'Fasilitasi monitoring berkala saat guru berkeliling memantau progres kelompok.',
                        ],
                        'tier_3' => [
                            'group'       => 'Kelompok Pengayaan (Advanced)',
                            'description' => 'Inkuiri mandiri (Self-directed inquiry), riset komparasi, dan peran sebagai fasilitator sebaya bagi kelompok lain.',
                            'scaffold'    => 'Umpan balik berbasis kriteria evaluasi tingkat tinggi dan peer-review mendalam.',
                        ],
                    ],
                ],
                'product' => [
                    'label'       => 'Diferensiasi Produk',
                    'icon'        => 'layers',
                    'description' => 'Variasi bentuk unjuk kerja atau bukti pemahaman yang dihasilkan siswa.',
                    'tiers' => [
                        'tier_1' => [
                            'group'       => 'Kelompok Perlu Pendampingan',
                            'description' => 'Menyusun poster diagram konsep sederhana, rekaman audio penjelasan singkat, atau mengisi format template baku.',
                            'scaffold'    => 'Rubrik sederhana yang fokus pada kebenaran konsep dasar esensial.',
                        ],
                        'tier_2' => [
                            'group'       => 'Kelompok Reguler / Mandiri',
                            'description' => 'Membuat laporan analisis terstruktur, presentasi slide multimedia, atau demonstrasi simulasi interaktif.',
                            'scaffold'    => 'Rubrik standar mencakup ketepatan prosedur, kejelasan analisis, dan sistematika penyajian.',
                        ],
                        'tier_3' => [
                            'group'       => 'Kelompok Pengayaan (Advanced)',
                            'description' => 'Merancang prototipe/projek aplikatif terintegrasi, video tutorial komprehensif, atau solusi masalah nyata untuk lingkungan sekolah.',
                            'scaffold'    => 'Rubrik holistik mencakup aspek orisinalitas, dampak solusi, efisiensi teknis, dan daya persuasi.',
                        ],
                    ],
                ],
            ],
        ];
    }
}
