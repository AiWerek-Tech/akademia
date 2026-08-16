# ADR-0001: Model Semantik Add-only untuk IALOS Education

Status: Accepted — 2026-08-16

## Keputusan

Menambahkan tabel semantik terpisah dan menghubungkannya ke master/versi kurikulum yang ada. Modul Kumer tidak diubah atau diganti.

## Alasan

Dokumen blob tidak cukup untuk coverage, lineage, workflow, dan audit granular. Big-bang replacement berisiko pada penjadwalan serta proses produksi yang telah berjalan. Struktur add-only memungkinkan rollout lewat feature flag dan rollback schema tanpa mutasi tabel lama.

## Konsekuensi

Area kurikulum lama dan model semantik baru hidup berdampingan pada masa transisi. Integrasi dijaga lewat foreign key, konteks pengguna/unit yang sama, dan navigasi terpadu WMVAA Academia. Penghapusan atau migrasi Kumer bukan bagian Phase 1.
