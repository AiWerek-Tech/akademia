<?php

namespace App\Services;

/**
 * AdaptiveModeService — Manages Plugged vs Unplugged learning adaptations
 * for schools with variable internet/hardware availability (e.g. Papua Pegunungan).
 */
class AdaptiveModeService
{
    /**
     * Common Unplugged strategies mapped to computer science and general subject concepts.
     */
    private const UNPLUGGED_ADAPTATIONS = [
        'algoritma' => [
            'unplugged_title'    => 'Simulasi Algoritma Berbasis Kartu & Robot Manusia',
            'materials_needed'   => 'Kertas HVS/Karton, Spidol warna, Kartu instruksi bernomor, Tali pembatas jalur.',
            'activity_procedure' => 'Siswa berperan sebagai "Programmer" yang menulis instruksi langkah demi langkah di kartu, dan siswa lain sebagai "Robot" yang menjalankan instruksi di grid lantai. Jika robot menabrak rintangan, programmer harus melakukan "debugging" pada urutan kartu.',
            'learning_outcome'   => 'Memahami konsep sekuensial, percabangan, dan perulangan logika tanpa memerlukan komputer.',
        ],
        'jaringan' => [
            'unplugged_title'    => 'Simulasi Topologi Jaringan & Paket Data Manusia',
            'materials_needed'   => 'Benang wol/tali rafia warna-warni, Amplop surat berisi pesan (paket data), Cap stempel router.',
            'activity_procedure' => 'Siswa membentuk simpul jaringan (Node, Switch, Router). Pesan rahasia dipecah menjadi beberapa bagian (paket) bernomor urut dan dikirimkan melewati jalur tali. Siswa router memeriksa alamat tujuan dan meneruskan paket. Di tujuan, paket disusun kembali.',
            'learning_outcome'   => 'Memahami mekanisme transmisi paket TCP/IP, addressing, enkapsulasi, dan packet loss secara interaktif.',
        ],
        'database' => [
            'unplugged_title'    => 'Sistem Kartu Indeks & Relasi Tabel Fisik',
            'materials_needed'   => 'Kartu index card (warna berbeda per tabel), Klip kertas, Kotak arsip kecil.',
            'activity_procedure' => 'Siswa membuat kartu entitas (Siswa, Mata Pelajaran, Nilai) dengan Primary Key manual. Melakukan operasi JOIN dengan mencocokkan kode ID menggunakan tali/klip dan melakukan pencarian terindeks secara manual.',
            'learning_outcome'   => 'Memahami konsep entitas, relasi 1-to-many, kunci utama (primary key), dan foreign key secara konkret.',
        ],
        'keamanan' => [
            'unplugged_title'    => 'Kriptografi Caesar Cipher & Enkripsi Kertas Scytale',
            'materials_needed'   => 'Roda sandi putar dari karton, Tongkat/spidol silinder, Pita kertas pesan.',
            'activity_procedure' => 'Siswa membuat roda sandi putar (Caesar Wheel) untuk mengenkripsi dan mendekripsi pesan antar kelompok dengan kunci pergeseran rahasia.',
            'learning_outcome'   => 'Memahami prinsip enkripsi simetris, cipher text, plain text, dan pentingnya kerahasiaan kunci.',
        ],
        'default' => [
            'unplugged_title'    => 'Aktivitas Hands-On Konseptual Terstruktur',
            'materials_needed'   => 'Lembar Kerja Cetak / Papan Tulis, Sticky notes, Kartu studi kasus.',
            'activity_procedure' => 'Siswa mengorganisasi konsep menggunakan diagram alur fisik di papan tulis, berdiskusi dalam kelompok kecil, dan mempresentasikan kesimpulan secara lisan menggunakan media gambar.',
            'learning_outcome'   => 'Mencapai tujuan pemahaman konsep esensial tanpa ketergantungan pada perangkat listrik atau koneksi internet.',
        ],
    ];

    /**
     * Suggest an unplugged adaptation based on activity keywords.
     */
    public function getUnpluggedAlternative(string $conceptName, string $activityDescription = ''): array
    {
        $text = mb_strtolower($conceptName . ' ' . $activityDescription);

        foreach (self::UNPLUGGED_ADAPTATIONS as $key => $data) {
            if ($key !== 'default' && str_contains($text, $key)) {
                return array_merge(['matched_keyword' => $key], $data);
            }
        }

        return array_merge(['matched_keyword' => 'general'], self::UNPLUGGED_ADAPTATIONS['default']);
    }
}
