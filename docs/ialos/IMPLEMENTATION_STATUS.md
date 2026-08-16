# Status Implementasi IALOS Education

Tanggal verifikasi: 16 Agustus 2026.

Dokumen ini adalah sumber status implementasi. Blueprint pada `docs/WMVAA_Academia_IALOS` tetap menjadi target produk; status **selesai** hanya diberikan bila schema, service, RBAC, UI, audit, dan pengujian sudah tersedia.

## Terimplementasi dan terintegrasi

- IALOS Education menjadi bounded context dalam modular monolith WMVAA Academia, bukan aplikasi kedua.
- Autentikasi, session, pemilih unit/periode, RBAC, audit log, layout, dashboard utama, dan database memakai fondasi aplikasi yang sama.
- Control Center IALOS menyesuaikan persona platform, pimpinan, kurikulum, guru, dan viewer.
- Menu berada dalam kategori `IALOS Education`; setiap domain memiliki route dan halaman terpisah, bukan satu halaman bertab.
- Phase 1 Education Foundation: registry regulasi, sumber kurikulum, delapan dimensi profil lulusan, CP/elemen, TP/kriteria/lineage adaptasi, ATP/workflow/clone, coverage, paket pembelajaran, dan staged import.
- Digital KSP foundation: versi KSP per unit/periode, sembilan checklist bagian, konteks sekolah, visi-misi-tujuan, organisasi pembelajaran, evaluasi periodik, readiness, lifecycle review/approval/lock/supersede, optimistic concurrency, audit, dan immutability setelah draft.

## Status roadmap blueprint

| Fase | Status | Catatan |
|---|---|---|
| 1. Fondasi pendidikan | Selesai | Domain, provenance, workflow, coverage, import, UI, dan test tersedia. |
| 2. Digital KSP | Berjalan | Fondasi dan workflow inti tersedia; generator dokumen/PDF, evidence attachment, serta action plan lanjutan belum lengkap. |
| 3. Teaching & Learning Engine | Belum lengkap | Modul ajar, jurnal, dan kehadiran yang sudah ada perlu disatukan ke model evidence blueprint. |
| 4. Assessment & Mastery | Belum dimulai | Gradebook, mastery, remediation, dan reporting belum diimplementasikan sebagai domain IALOS. |
| 5. Kokurikuler | Belum dimulai | Workflow proyek dan evidence belum tersedia. |
| 6. Ekstrakurikuler & karakter | Belum dimulai | Perlu integrasi terhadap siswa, jadwal, kehadiran, dan reporting. |
| 7. Monitoring & analytics | Belum dimulai | Dashboard lintas domain dan alert belum tersedia. |
| 8. AI governance | Belum dimulai | Human approval, provenance, prompt/output audit, dan data boundary wajib didahulukan. |
| 9. Integrasi eksternal | Belum dimulai | Adapter dan outbox perlu dibangun setelah domain internal stabil. |
| 10. Hardening | Berjalan berkelanjutan | Regression, security, observability, backup/restore, dan performance gate mengikuti tiap fase. |

## Deployment

Jalankan migrasi dan seeder dalam maintenance window:

```bash
php spark migrate
php spark db:seed EducationFoundationSeeder
```

`EducationFoundationSeeder` juga memasang permission Digital KSP secara idempotent. Setelah deploy, verifikasi role mapping pada halaman Role & Permission sebelum digunakan pengguna produksi.

## Verifikasi build ini

- Focused IALOS Education: 25 test, 81 assertion, lulus.
- Full regression WMVAA Academia/IALOS Education: 285 test, 1.108 assertion, lulus tanpa error/failure.
- Composer strict validation dan PHP syntax lint: lulus.
- Seluruh route IALOS/KSP membawa filter autentikasi, unit access, password-change guard, dan permission domain.
- Browser smoke unauthenticated: route terlindungi mengarah ke login dan login render normal.

## Prinsip kelanjutan

Implementasi fase berikutnya wajib memperluas aplikasi yang sama, memakai identifier dan scope yang sudah ada, serta tidak membuat mini-app atau database paralel. Setiap fase harus melewati migration rollback/reapply, unit isolation, authorization, audit, concurrency, dan full regression suite sebelum dinyatakan selesai.
