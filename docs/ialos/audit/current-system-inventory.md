# Inventaris Sistem Saat Ini

Tanggal audit: 2026-08-16

Cabang dasar: `fix/scheduling-production-blockers` (`96cf9de`)

## Fondasi yang dipakai kembali

- CodeIgniter 4.7/PHP 8.2 dengan modular monolith, migration berurutan, dan transaksi layanan.
- Konteks organisasi: `school_units`, `user_unit_access`, dan `UnitScopeService`.
- Konteks waktu: `academic_years` dan `academic_periods`.
- Identitas akademik: `subjects`, `subject_unit_availability`, dan `grade_levels`.
- Versi kurikulum: `curriculum_versions`, workflow, revision history, validasi, import staging, dan ekspor.
- Keamanan: route permission filter, RBAC `roles`/`permissions`/`role_permissions`, CSRF global, serta UUID publik.
- Akuntabilitas: `audit_logs` dan `AuditService` dengan redaksi field sensitif.
- Pola pengujian: database terisolasi per kelas, transaksi per test, feature/security tests.

## Komponen baru yang diperlukan

- Registri regulasi dan versi regulasi.
- Registri sumber kurikulum dengan hash dan metadata provenance.
- Delapan dimensi profil lulusan.
- Model semantik CP, elemen, TP, kriteria, ATP, item ATP, dan paket pembelajaran.
- Relasi paket-ke-TP dan paket-ke-ATP.
- Staging import khusus IALOS Education.
- Layanan lineage adaptasi nasional-sekolah-guru, coverage, workflow, OCC atomik, dan import.
- UI dasar dan permission IALOS Phase 1.

## Batas perubahan

Modul kurikulum Kumer, penjadwalan, beban mengajar, dan portal operasional tidak dirombak. Integrasi dilakukan lewat foreign key ke master/versi yang telah stabil dan route baru yang spesifik.

## Baseline

- `composer validate --strict`: lulus.
- `composer audit`: tidak menemukan advisory.
- `CsrfEnforcementTest`: 13 test, 18 assertion, lulus.
- Full suite dijalankan terhadap database test terpisah `wmvaa_akademia_ialos_final_test`; hasil akhir dicatat di laporan pengujian Phase 1.
