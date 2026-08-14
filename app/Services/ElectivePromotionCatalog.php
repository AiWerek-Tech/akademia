<?php

namespace App\Services;

/**
 * Student-facing introduction for the SMA elective offerings.
 * These are defaults only; school-specific descriptions remain authoritative.
 */
final class ElectivePromotionCatalog
{
    private const ITEMS = [
        'BIO' => [
            'description' => 'Mempelajari kehidupan dari tingkat sel, jaringan, organ, genetika, evolusi, hingga ekosistem. Siswa berlatih mengamati, melakukan eksperimen, membaca data, dan menjelaskan gejala biologis secara ilmiah.',
            'study_relevance' => 'Cocok untuk Kedokteran, Kedokteran Gigi, Keperawatan, Farmasi, Biologi, Bioteknologi, Gizi, Peternakan, Pertanian, dan Ilmu Lingkungan. Prospek kerja: tenaga kesehatan, peneliti, analis laboratorium, ahli gizi, bioteknolog, konservasionis, dan pengelola lingkungan.',
            'prerequisites' => 'Minat pada makhluk hidup, ketelitian mengamati, dan kesiapan membaca data serta laporan praktikum.',
        ],
        'EKON' => [
            'description' => 'Menganalisis kebutuhan dan kelangkaan, perilaku konsumen-produsen, pasar, kebijakan ekonomi, perbankan, perdagangan, serta ekonomi digital. Pembelajaran diarahkan pada studi kasus dan pengambilan keputusan berbasis data.',
            'study_relevance' => 'Mendukung Manajemen, Akuntansi, Ekonomi Pembangunan, Bisnis Digital, Perbankan, Administrasi Bisnis, dan Ilmu Ekonomi. Prospek kerja: analis bisnis, akuntan, perencana keuangan, perbankan, wirausaha, analis pasar, dan staf administrasi bisnis.',
            'prerequisites' => 'Minat pada angka, berita ekonomi, pemecahan masalah, dan cara kerja bisnis atau keuangan.',
        ],
        'FISIKA' => [
            'description' => 'Mengkaji gerak, gaya, energi, listrik, gelombang, kalor, dan gejala alam melalui konsep, pemodelan matematika, eksperimen, serta penyelesaian masalah nyata.',
            'study_relevance' => 'Menjadi fondasi Teknik Sipil, Mesin, Elektro, Informatika, Arsitektur, Fisika, Astronomi, Geofisika, dan bidang energi. Prospek kerja: engineer, analis teknis, peneliti, ahli energi, pengembang teknologi, dan quality control.',
            'prerequisites' => 'Kemauan menggunakan matematika, berpikir logis, melakukan pengukuran, dan menguji dugaan melalui eksperimen.',
        ],
        'GEO' => [
            'description' => 'Mempelajari bumi, bentang alam, cuaca, iklim, penduduk, sumber daya, kebencanaan, wilayah, dan hubungan manusia dengan lingkungan menggunakan peta serta data geospasial.',
            'study_relevance' => 'Relevan untuk Geografi, Geologi, Geodesi, Planologi, Kehutanan, Kelautan, Meteorologi, Teknik Lingkungan, dan Pariwisata. Prospek kerja: analis GIS, perencana wilayah, surveyor, analis kebencanaan, konsultan lingkungan, dan pengelola pariwisata.',
            'prerequisites' => 'Minat pada peta, lingkungan, fenomena alam-sosial, observasi lapangan, dan analisis ruang.',
        ],
        'INFO' => [
            'description' => 'Mengembangkan cara berpikir komputasional melalui algoritma, data, pemrograman, sistem informasi, keamanan digital, dan pembuatan solusi teknologi yang bermanfaat.',
            'study_relevance' => 'Mendukung Informatika, Sistem Informasi, Teknik Komputer, Teknologi Informasi, Sains Data, dan bidang digital. Prospek kerja: software developer, web/mobile developer, system analyst, database administrator, QA tester, dan IT support.',
            'prerequisites' => 'Rasa ingin tahu, ketekunan memecahkan masalah, dan kesiapan belajar melalui praktik serta debugging.',
        ],
        'KIMIA' => [
            'description' => 'Mempelajari struktur dan perubahan materi, ikatan kimia, reaksi, larutan, asam-basa, energi, serta penerapannya melalui perhitungan dan praktikum yang aman.',
            'study_relevance' => 'Menjadi dasar Farmasi, Kedokteran, Kimia, Teknik Kimia, Teknologi Pangan, Industri, Material, dan Lingkungan. Prospek kerja: analis laboratorium, formulator, ahli quality control, peneliti, engineer proses, dan pengembang produk.',
            'prerequisites' => 'Ketelitian, minat pada eksperimen, kemampuan berhitung, dan komitmen menjalankan prosedur keselamatan laboratorium.',
        ],
        'K-AI' => [
            'description' => 'Mengenalkan pemrograman, data, kecerdasan buatan, machine learning, etika teknologi, dan cara merancang solusi digital secara bertanggung jawab melalui proyek sederhana.',
            'study_relevance' => 'Relevan untuk Informatika, Sains Data, Kecerdasan Buatan, Teknik Komputer, Robotika, dan Bisnis Digital. Prospek kerja: AI/data analyst, machine learning engineer, automation developer, prompt/AI specialist, software developer, dan digital entrepreneur.',
            'prerequisites' => 'Minat kuat pada teknologi, logika, eksplorasi data, dan kemauan belajar bertahap melalui proyek serta percobaan.',
        ],
        'PRAK' => [
            'description' => 'Mengubah ide menjadi produk atau layanan melalui perancangan, pemilihan bahan, produksi, pengemasan, pemasaran, perhitungan biaya, dan evaluasi usaha secara kreatif.',
            'study_relevance' => 'Mendukung Kewirausahaan, Manajemen, Desain Produk, Desain Komunikasi Visual, Tata Boga, Agroteknologi, dan Bisnis Digital. Prospek kerja: entrepreneur, product developer, creative business owner, marketing, content creator, dan pengelola UMKM.',
            'prerequisites' => 'Kreativitas, kemauan membuat prototipe, kerja tim, kemampuan berkomunikasi, dan keberanian belajar dari kegagalan.',
        ],
        'SOSIO' => [
            'description' => 'Menganalisis interaksi sosial, kelompok, lembaga, perubahan sosial, budaya, masalah sosial, dan masyarakat digital melalui pengamatan, diskusi, serta kajian data lapangan.',
            'study_relevance' => 'Mendukung Sosiologi, Ilmu Komunikasi, Psikologi, Hukum, Hubungan Internasional, Pendidikan, Administrasi Publik, dan Pekerjaan Sosial. Prospek kerja: peneliti sosial, HR, communicator, community development, konsultan, pendidik, dan analis kebijakan.',
            'prerequisites' => 'Keterbukaan terhadap perbedaan, kemampuan mendengar dan berdiskusi, serta minat memahami perilaku manusia dan masyarakat.',
        ],
    ];

    public static function enrich(array $offering): array
    {
        $code = strtoupper(trim((string) ($offering['subject_code'] ?? '')));
        $item = self::ITEMS[$code] ?? null;
        if ($item === null) {
            return $offering;
        }

        foreach ($item as $field => $value) {
            if (trim((string) ($offering[$field] ?? '')) === '') {
                $offering[$field] = $value;
            }
        }
        $offering['promotion_source'] = trim((string) ($offering['description'] ?? '')) === $item['description'] ? 'catalog' : 'school';
        return $offering;
    }

    public static function all(): array
    {
        return self::ITEMS;
    }
}
