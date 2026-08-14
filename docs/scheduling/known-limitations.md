# Batasan Penjadwalan

## Generator

- Generator memakai heuristik greedy deterministik dan menghasilkan kandidat *best effort*. Kandidat dapat parsial bila seluruh kebutuhan tidak mungkin ditempatkan tanpa benturan.
- Jam disebarkan lintas hari dan entri yang dikunci dipertahankan, tetapi hasilnya belum dijamin optimum secara matematis.
- Penerapan kandidat selalu memerlukan persetujuan pengguna; kandidat tidak langsung menimpa jadwal aktif.
- Bobot soft constraint tersimpan di database, tetapi belum seluruhnya menjadi fungsi optimasi multi-kandidat.
- Team teaching sengaja OFF. Assignment/requirement dengan anggota tim atau guru kedua ditolak sampai junction multi-guru final tersedia; lihat `team-teaching-contract.md`.

## Keluaran

- Jadwal kelas dan guru tersedia sebagai halaman cetak ramah A4; pengguna dapat mencetak atau memilih **Save as PDF** dari browser.
- SK pembagian tugas kolektif dan surat tugas per guru tersedia dari versi pembagian tugas yang sama.
- Belum tersedia sinkronisasi WhatsApp/SMS, Google Calendar/iCal, atau API Kurikulum Merdeka.
- Jadwal piket masih terpisah dari generator jadwal pelajaran. Substitusi guru sementara sudah ikut perhitungan resource, availability, konflik intra-unit/lintas-unit, editor, scoring, dan generator; identitas guru pemilik tetap digunakan pada cetak.
- Audit konflik memiliki fingerprint unik dan row lock, tetapi race edit-vs-delete serta approve-vs-apply masih perlu harness staging proses terpisah.

## Keamanan dan Operasional

- CSP production-like kompatibel dan browser test tidak menemukan violation, tetapi policy masih memakai `unsafe-inline` karena inline handler/style legacy. Migrasi nonce dan event listener belum selesai.
- Generator dan audit masih query-intensive (masing-masing 666 dan 507 query pada dataset UAT), walau durasi lokal <1 detik per operasi.
- Worktree audit 2 Agustus 2026 berisi perubahan pengguna yang bercampur; jangan merge atau deploy sampai patch direview dan Git clean.
