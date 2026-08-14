# Substitusi Guru Sementara

Substitusi jadwal memisahkan identitas tampilan dari resource pengajar fisik. Entri tetap menyimpan `teacher_id` guru pemilik agar nama, inisial, dan warna pada grid/cetak tidak berubah. Selama rentang aktif pada `teacher_schedule_substitutions`, generator, editor, availability, scoring, dan audit konflik memakai `substitute_teacher_id` sebagai resource.

## Pengaturan melalui aplikasi

1. Buka **Penugasan & Jadwal → Substitusi Guru**.
2. Klik **Tambah Substitusi**.
3. Pilih periode akademik, guru cuti/pemilik jadwal, dan guru pengganti/pelaksana.
4. Tentukan tanggal mulai dan selesai di dalam rentang periode akademik.
5. Simpan dengan status **Aktif**. Sistem langsung mengaudit ulang jadwal terbaru setiap unit pada periode tersebut; bila muncul konflik kritis, UI menampilkan peringatan untuk generate atau penyesuaian jadwal.
6. Gunakan tombol edit untuk mengubah guru/tanggal/catatan, atau tombol jeda untuk menonaktifkan aturan sebelum waktunya.
7. Tekan ikon **Auto Repair** pada aturan aktif. Sistem menganalisis jadwal sumber terbaru seluruh unit pada periode tersebut dan menampilkan pratinjau perpindahan slot paling sedikit.
8. Periksa daftar **Dari → Ke**, kemudian pilih **Terapkan Atomik**. Tidak ada perubahan parsial: kandidat ditolak seluruhnya bila jadwal telah berubah, ada entri terkunci, atau audit final masih menemukan konflik/gap.

UI menolak guru yang sama, rentang tanggal tidak valid, tanggal di luar periode akademik, substitusi aktif yang beririsan untuk guru pemilik yang sama, dan rantai substitusi yang membentuk siklus.

## Jaminan Auto Repair

- Benturan guru dihitung berdasarkan irisan jam aktual lintas unit, bukan sekadar nomor JP.
- Guru pemilik, inisial, warna, mapel, kelas, ruang, dan jumlah JP tidak diganti; kandidat hanya memindahkan slot.
- Entri terkunci dan kegiatan rutin tidak dipindahkan. Ketersediaan guru, kelas, dan ruang tetap menjadi batas keras.
- Blok wajib 2 JP tetap berurutan; jumlah JP setiap kebutuhan harus tetap sama.
- Kandidat menyimpan revisi dasar setiap versi. Perubahan paralel membuat kandidat kedaluwarsa dan wajib dianalisis ulang.
- Penerapan mengunci versi terkait dalam urutan tetap, menjaga referensi histori/pengecualian, menjalankan validasi waktu efektif dan audit jadwal resmi, lalu commit sekali. Kegagalan apa pun me-rollback seluruh perpindahan.
- Status **Aman · 0 perubahan** berarti jadwal sudah bebas konflik kritis pada masa substitusi dan tidak perlu ditulis ulang.

Konfigurasi aktif saat ini:

- Guru pemilik: Saray Barusa (`SB`, `#F4B183`)
- Guru pelaksana: Marthen Refasi
- Periode akademik: 2026/2027 Ganjil
- Berlaku: 9 Agustus 2026 sampai 9 Oktober 2026

Sesudah `effective_to`, resolusi otomatis kembali memakai Saray sebagai resource. Riwayat jadwal dan identitas cetak tidak perlu diubah.

Penyesuaian jadwal SMP yang diterapkan:

- IPS Kelas VIII milik Saray: Selasa JP 2–3 dan Kamis JP 1.
- IPS Kelas VII milik Saray: Senin JP 6–7 dan Kamis JP 7.
- IPS Kelas IX milik Marthen tetap Kamis JP 3–5.
- SBDP Kelas IX milik Marthen tetap Selasa JP 8–9.

Audit pascaperubahan menghasilkan nol konflik kritis di SMP dan SMA, serta nol overlap waktu antara entri Saray dan Marthen.
