# Status Implementasi IALOS Education

Tanggal verifikasi: 17 Agustus 2026.

Dokumen ini adalah sumber status implementasi. Blueprint pada `docs/WMVAA_Academia_IALOS` tetap menjadi target produk; status **selesai** hanya diberikan bila schema, service, RBAC, UI, audit, dan pengujian sudah tersedia.

## Terimplementasi dan terintegrasi

- IALOS Education menjadi bounded context dalam modular monolith WMVAA Academia, bukan aplikasi kedua.
- Autentikasi, session, pemilih unit/periode, RBAC, audit log, layout, dashboard utama, dan database memakai fondasi aplikasi yang sama.
- Control Center IALOS menyesuaikan persona platform, pimpinan, kurikulum, guru, dan viewer.
- Menu berada dalam kategori `IALOS Education`; setiap domain memiliki route dan halaman terpisah, bukan satu halaman bertab.
- Phase 1 Education Foundation: registry regulasi, sumber kurikulum, delapan dimensi profil lulusan, CP/elemen, TP/kriteria/lineage adaptasi, ATP/workflow/clone, coverage, paket pembelajaran, dan staged import.
- Digital KSP Phase 2 final: versi KSP per unit/periode, sembilan checklist bagian, konteks sekolah, visi-misi-tujuan, organisasi pembelajaran, evaluasi periodik, improvement action beserta owner/due date/success indicator, evidence/provenance, typed compliance preview terintegrasi Regulation Registry, readiness, lifecycle review/approval/lock/supersede, optimistic concurrency, audit, immutability setelah draft, serta historical DOCX/PDF generation.

## Status roadmap blueprint

| Fase | Status | Catatan |
|---|---|---|
| 1. Fondasi pendidikan | Selesai | Domain, provenance, workflow, coverage, import, UI, dan test tersedia. |
| 2. Digital KSP | Selesai | Empat exit area final closure lulus: compliance, evidence/provenance, immutable DOCX/PDF generation, dan pilot/browser acceptance end-to-end. |
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
php spark db:seed NumeracyImprovementActionSeeder
php spark db:seed Phase2KspPilotSeeder
```

`EducationFoundationSeeder` juga memasang permission Digital KSP secara idempotent. Setelah deploy, verifikasi role mapping pada halaman Role & Permission sebelum digunakan pengguna produksi.

## Verifikasi build ini

- Focused Phase 2 closure: 16 test, 128 assertion, lulus.
- Full regression IALOS Education: 303 test, 1.242 assertion, lulus tanpa error/failure (baseline sebelumnya 287/1.114).
- Composer strict validation, Composer security audit, lint 569 file PHP, dan `git diff --check`: lulus.
- Full migrate → rollback → reapply pada database disposable: 118 → 1 → 118 tabel, lulus.
- Seluruh route IALOS/KSP membawa filter autentikasi, unit access, password-change guard, dan permission domain.
- Pilot nyata `SMP Advent Sogokmo` / `KSP-SMP-2026-2027` / Semester Ganjil 2026/2027: context, evidence file+hash, VMG, organisasi, evaluasi, improvement action, compliance, REVIEW→APPROVED→LOCKED, DOCX, dan PDF lulus melalui browser lokal.
- DOCX dibuka melalui Microsoft Word dan diperiksa visual 9 halaman; PDF dirender dan diperiksa visual 5 halaman tanpa clipping/overflow.

## Prinsip kelanjutan

Implementasi fase berikutnya wajib memperluas aplikasi yang sama, memakai identifier dan scope yang sudah ada, serta tidak membuat mini-app atau database paralel. Setiap fase harus melewati migration rollback/reapply, unit isolation, authorization, audit, concurrency, dan full regression suite sebelum dinyatakan selesai.
