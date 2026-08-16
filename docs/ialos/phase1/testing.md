# Laporan Pengujian Phase 1

Environment: PHP 8.2.20, CodeIgniter 4.7.4, PHPUnit 10.5.64, MySQL test database terpisah.

## Hasil terverifikasi

- Composer strict validation: lulus.
- Composer security audit: lulus, tanpa advisory.
- Migration rollback → reapply: lulus.
- Seeder dijalankan dua kali: lulus/idempotent.
- Focused IALOS Education: 20 test, 56 assertion, lulus.
- CSRF enforcement: 13 test, 18 assertion, lulus.
- PHP lint untuk seluruh 43 file PHP Phase 1: lulus.
- Browser smoke test route referensi: tamu diarahkan ke login, halaman login render normal, tanpa console warning/error.

- Full regression suite: 280 test, 1.083 assertion, lulus tanpa error/failure.
- Fixture regresi versi jadwal dibuat unik per test agar isolasi transaksi tetap deterministik ketika suite lengkap dijalankan.

## Coverage acceptance

Test mencakup schema, delapan dimensi, immutability referensi nasional, hierarki CP/elemen, kriteria TP, lineage adaptasi, unit isolation, OCC stale revision, mismatch/duplicate ATP, workflow/lock/clone, coverage missing-to-complete, relasi paket, serta staging/apply import.
