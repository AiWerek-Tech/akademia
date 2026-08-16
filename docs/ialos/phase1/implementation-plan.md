# Rencana Implementasi Phase 1 — IALOS Education

## Sasaran penerimaan

Menghadirkan IALOS Education sebagai satu modul terpadu di dalam WMVAA Academia dengan fondasi data pendidikan yang dapat diaudit: referensi resmi global, adaptasi sekolah/guru dengan lineage, CP→elemen→TP→ATP, paket pembelajaran, coverage, import terkontrol, serta keamanan lintas unit.

## Tahap commit

1. Schema dan reference data: migration add-only, model, permission, feature flag, delapan dimensi.
2. Services dan coverage: registry, lineage, OCC, workflow, clone, coverage, paket, import.
3. UI dan secure workflows: route permission-filtered, controller, menu, tampilan progresif.
4. Tests: migration/seeder/domain/security/import dan regression suite.
5. Docs: arsitektur, schema, lineage/versioning, permission matrix, import guide, ADR, dan hasil uji.

## Aturan implementasi

- UUID dipakai pada URL; integer PK tetap internal.
- Data nasional global dan immutable setelah publikasi; adaptasi selalu unit-scoped.
- Semua mutasi memakai transaksi, audit, validasi domain, dan OCC atomik.
- Import hanya staging sampai eksplisit diaplikasikan.
- Tidak memasukkan data CP/TP contoh ke production seeder.
- Phase 1 berhenti sebelum modul AI, assessment, lesson planning lanjutan, atau penggantian Kumer.

## Definition of done

- Migration dapat up/down/up dan seluruh FK/index valid.
- Seeder idempotent dan menghasilkan tepat delapan dimensi.
- Permission dan unit isolation diuji termasuk akses UUID langsung.
- Workflow ATP dan locked immutability diuji.
- Coverage mendeteksi missing, duplicate, serta mismatch mapel/fase.
- UI dasar dapat dipakai pada aplikasi lokal tanpa merusak alur yang sudah berjalan.
- Dokumentasi dan laporan test sesuai keadaan aktual.
