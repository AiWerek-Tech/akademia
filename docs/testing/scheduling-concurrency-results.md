# Hasil Concurrency dan OCC

- Dua conflict audit bersamaan: keduanya selesai, 16 finding aktif unik, tidak ada duplicate fingerprint; history resolved tetap ada.
- Dua apply kandidat yang sama: satu sukses, satu ditolak kode 409 karena revision berubah.
- Setelah race: revision 2, workflow DRAFT, satu revision history apply, satu candidate applied, 109 candidate entry + 6 locked entry.
- Reopen conflict: fingerprint dan row ID dipertahankan, status kembali ACTIVE.
- Kandidat parsial, stale, dan already-applied ditolak secara terkontrol.

Race edit-vs-delete dan approve-vs-apply belum memiliki harness proses terpisah lengkap; perlindungan transaksi/OCC ada, tetapi skenario tersebut tetap wajib diulang pada staging sebelum produksi.
