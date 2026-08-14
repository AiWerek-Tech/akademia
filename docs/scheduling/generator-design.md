# Desain Generator Jadwal

`DeterministicGreedyScheduleGenerator` membentuk satu kandidat yang dapat direproduksi dari versi jadwal, pembagian tugas, slot aktif, aturan ketersediaan, ruangan, dan entri terkunci.

## Alur

1. Persiapan versi dilakukan secara eksplisit sebelum generasi: profil perencanaan, hari sekolah, slot JP, dan kebutuhan mengajar harus sudah tersedia.
2. Generator tidak menjalankan sinkronisasi destruktif atau mengubah jadwal terpasang.
3. Generator memuat slot dengan urutan stabil dan menjalankan maksimal 24 variasi ordering yang bounded.
4. Entri terkunci dimasukkan lebih dahulu ke peta okupansi.
5. Kelas dengan slack kapasitas terkecil dan guru dengan beban tinggi diprioritaskan; tie-breaker selalu deterministik.
6. Kebutuhan ditempatkan dengan pemeriksaan benturan kelas, guru, ruangan, ketersediaan, jadwal lintas unit, pola blok, dan jarak pertemuan.
7. Team teaching ditolak ketika feature flag OFF; anggota tidak pernah diratakan menjadi satu guru.
8. Hasil disimpan sebagai kandidat yang terikat ke revisi versi saat dibuat. Pengguna meninjau ringkasan dan memilih **Terapkan Kandidat**.
9. Penerapan memakai transaksi, row lock, dan OCC. Kandidat ditolak bila sudah diterapkan, basi, parsial, atau menghasilkan konflik kritis/jam tidak terpenuhi.
10. Conflict history yang mereferensikan entry lama di-resolve dan FK entry dilepas sebelum replacement; fingerprint/description tetap disimpan.
11. Audit konflik dijalankan sebelum commit dan sebelum jadwal disetujui atau dikunci.

## Sifat Hasil

- Deterministik dan *best effort*.
- Kebutuhan yang tidak tertampung tetap dilaporkan.
- Tidak menghapus entri yang dikunci.
- Belum merupakan solver optimasi global; lihat `known-limitations.md`.
