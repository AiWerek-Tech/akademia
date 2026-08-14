# Analisis Implementasi Pemilihan Mata Pelajaran

## Dasar dan batas analisis

Analisis ini membandingkan Permendikdasmen Nomor 13 Tahun 2025, khususnya
Lampiran bagian struktur kurikulum SMA/MA kelas XI dan XII (halaman dokumen
28-33), dengan `Konsep_Mapel_Pilihan.md` dan arsitektur WMVAA Academia.

## Aturan normatif yang menjadi hard constraint

1. Pada Fase F, sekolah wajib membuka seluruh kelompok mata pelajaran wajib
   dan menyediakan sedikitnya 7 mata pelajaran pilihan.
2. Setiap peserta didik memilih 4 sampai 5 mata pelajaran pilihan, disesuaikan
   dengan minat, bakat, kemampuan, dan sumber daya satuan pendidikan.
3. Penggantian mapel hanya diperbolehkan paling lambat kelas XI semester 2
   dan harus berdasarkan penilaian ulang satuan pendidikan.
4. Koding dan Kecerdasan Artifisial dapat disediakan sesuai sumber daya dan
   dipilih sesuai minat; bukan mapel yang wajib dibuka oleh setiap sekolah.

Konsekuensinya, jumlah 4-5 dan minimum 7 tidak dibuat bebas-konfigurasi pada
UI. Sistem menolaknya sebagai blocker publikasi bila data tidak patuh.

Rentang 20 sampai 25 JP per minggu ditampilkan sebagai acuan/peringatan, bukan
blocker. Alokasi aktual setiap mapel mengikuti `effective_weekly_hours` pada
struktur kurikulum yang dapat memakai nilai resmi maupun custom. Karena itu
validasi pengiriman didasarkan pada jumlah 4-5 mapel, bukan penjumlahan JP.

## Kebijakan sekolah, bukan bunyi eksplisit regulasi

Pilihan cadangan, kapasitas, minimum peminat, urutan prioritas, rekomendasi BK,
persetujuan orang tua, skor alokasi, dan optimasi blok jadwal adalah mekanisme
operasional yang baik, tetapi bukan hard constraint yang dinyatakan dalam
bagian regulasi tersebut. Sistem harus melabelinya sebagai kebijakan sekolah
dan menyimpan jejak keputusan.

## Keputusan implementasi

Fondasi pertama mencakup:

- periode pemilihan per unit, tahun pelajaran, dan tingkat tujuan;
- penawaran mapel dari master mapel kategori `PILIHAN`;
- guru pengampu, JP, minimum peminat, kapasitas, dan informasi untuk siswa;
- pemeriksaan kepatuhan sebelum publikasi;
- pemisahan permission `view`, `manage`, dan `publish`;
- unit scope, audit publikasi, optimistic revision field, dan status `DRAFT`
  serta `PUBLISHED`.

Implementasi dilengkapi dengan peserta pemilihan yang terikat pada unit, tahun
pelajaran, tingkat asal, dan opsional akun pengguna. Pilihan disimpan sebagai
submission dengan detail urutan utama/cadangan, review BK dan kurikulum, serta
pengajuan perubahan yang membutuhkan alasan dan penilaian ulang sekolah.
Rekap peminat utama/cadangan dan matriks konflik antarmapel dihitung langsung
dari submission non-draf sehingga dapat menjadi kontrak input untuk penyusunan
kelompok dan blok jadwal.

Setiap periode wajib menunjuk versi kurikulum yang telah disetujui atau dikunci.
Mapel penawaran harus berkategori `PILIHAN`, tersedia pada unit, dan tercantum
pada struktur kurikulum tingkat tujuan. Dengan demikian modul tidak membentuk
sumber data kurikulum paralel.

## Roadmap integrasi

1. Sinkronisasi peserta dengan SIS eksternal apabila kelak tersedia.
2. Alokasi semi-otomatis dengan preview, override beralasan, dan audit.
3. Integrasi rekomendasi blok ke `schedule_versions`.
4. Laporan final pemenuhan pilihan, penggunaan cadangan, dan alokasi kelompok.

Keputusan otomatis tidak boleh langsung mengesahkan hasil. Urutan yang aman:
generate, preview, review, penyesuaian manual, validasi ulang, lalu finalisasi.
