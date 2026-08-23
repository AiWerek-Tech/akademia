# Phase 9 — Reporting & Portfolio

Dokumentasi resmi untuk **Phase 9: Reporting & Portfolio Engine** pada ekosistem WMVAA Academia / IALOS Education (Blueprint §7–12).

## Dokumen dalam folder ini

- [Dokumentasi Implementasi Phase 9](reporting-implementation.md) — Arsitektur teknis, model data, snapshot generation, narasi dengan review wajib, portofolio auto-pull, dan decision support kenaikan kelas.

## Ringkasan Ruang Lingkup

1. **Rapor sebagai Output (Blueprint §8):** Tidak ada entry data mentah di modul rapor — semua angka ditarik dari pipeline *Teaching → Evidence → Assessment → TP Attainment → Summative → Rapor*.
2. **Reporting Policies ber-versioning (Blueprint §7):** `reporting_policies` per unit/periode/mapel dengan metode kalkulasi AVERAGE/WEIGHTED/etc dan `version`.
3. **Snapshot Rapor (Blueprint §9):** `report_snapshots` per siswa per periode — status `DRAFT → LOCKED → PUBLISHED`, beku setelah lock. Daftar mapel & guru diambil dari `teaching_assignments` (PRIMARY/ACTIVE); nilai akhir dari `summative_results` ter-validasi; penguasaan TP & cakupan TP dari `mastery_records` dan assessment `PUBLISHED`/`CLOSED`; kehadiran per mapel dari sesi presensi.
4. **Narasi dengan Review Wajib (Blueprint §10):** Guru/AI menulis narasi per mata pelajaran (`report_narratives`). **Rapor tidak dapat diterbitkan sebelum seluruh narasi disetujui guru** (status `DRAFT → APPROVED`).
5. **Portofolio Auto-Pull (Blueprint §11):** `portfolio_collections` mereferensikan bukti yang sudah ada (`source_type` + `source_id`) tanpa menduplikasi file — dari *assessment evidence*, bukti kokurikuler, dan pencapaian ekstrakurikuler.
6. **Decision Support Kenaikan Kelas (Blueprint §12):** `promotionReadiness` mengelompokkan siswa menjadi `READY` / `REVIEW` / `ATTENTION` berdasarkan kelengkapan nilai, kehadiran (<75% memberi peringatan), penguasaan kompetensi, dan intervensi belum tuntas. Keputusan akhir tetap milik sekolah.
7. **Dashboard Kelas:** Rata-rata skor, distribusi predikat, rata-rata mastery per mapel.
8. **RBAC:** `reporting.view` / `reporting.manage`.