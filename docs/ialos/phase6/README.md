# Phase 6 — Assessment, Mastery & Reporting Engine

Dokumentasi resmi untuk **Phase 6: Assessment, Mastery & Reporting Engine** pada ekosistem WMVAA Academia / IALOS Education.

## Dokumen dalam folder ini

- [Dokumentasi Implementasi Phase 6](assessment-mastery-implementation.md) — Arsitektur teknis, model data (11 tabel), state machine assessment, engine mastery berbasis TP, rekomendasi intervensi, dan kebijakan pelaporan.

## Ringkasan Ruang Lingkup

1. **Assessment Builder:** Penyusunan assessment (formatif/sumatif, angka/kategori/deskripsi), pemetaan ke Tujuan Pembelajaran (TP) dan kriteria ketercapaian, dengan workflow `DRAFT` → `PUBLISHED` → `CLOSED` yang tervalidasi.
2. **Gradebook Digital:** Input nilai siswa per kriteria dengan fallback status otomatis, bukti (evidence) dan umpan balik (feedback) per siswa, serta perhitungan mastery per TP secara otomatis.
3. **Mastery Engine berbasis TP:** Agregasi status kriteria ke level mastery murid (`NEEDS_SUPPORT` / `DEVELOPING` / `ACHIEVED` / `ADVANCED`) dengan aturan worst-case dominates, disimpan di `mastery_records` dengan versi dan jejak sumber.
4. **Intervensi & Remedial:** Rekomendasi otomatis intervensi (`REMEDIAL`, `REINFORCEMENT`, `ENRICHMENT`) dari analisis mastery, dengan alur `RECOMMENDED` → `APPROVED` → `COMPLETED`/`CANCELLED`.
5. **Mastery Board & Reporting:** Matriks murid × TP per kelas/mata pelajaran, ringkasan mastery, dan kebijakan pelaporan (reporting policies) dengan versioning immutable.