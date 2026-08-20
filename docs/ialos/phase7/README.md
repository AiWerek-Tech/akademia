# Phase 7 — Cocurricular & Character (Kokurikuler)

Dokumentasi resmi untuk **Phase 7: Cocurricular & Character Engine** pada ekosistem WMVAA Academia / IALOS Education (Blueprint §7–8, §5, §6).

## Dokumen dalam folder ini

- [Dokumentasi Implementasi Phase 7](cocurricular-implementation.md) — Arsitektur teknis, model data (15 tabel), workflow program kokurikuler, asesmen dimensi profil lulusan, evaluasi INPUT→PROCESS→OUTPUT→OUTCOME, narasi otomatis, dan dukungan opsional 7KAIH.

## Ringkasan Ruang Lingkup

1. **Program Kokurikuler:** Klasifikasi per tujuan (`COCURRICULAR` / `EXTRACURRICULAR` / `FIXED_SCHOOL_ACTIVITY` / `FORMATION` / `SERVICE`) dengan alur kerja Need Analysis → Dimensi Profil Lulusan → Tema → Pemetaan Mapel/TP → Desain → Jadwal → Eksekusi → Monitoring Formatif → Bukti Sumatif → Laporan → Evaluasi → Tindak lanjut.
2. **Junction lintas-disiplin:** Pemetaan program ke dimensi profil lulusan, mapel, Tujuan Pembelajaran (TP), guru, kelas, mitra, dan sumber daya — satu bukti dapat menyumbang ke beberapa alignment tanpa duplikasi.
3. **Workflow Program:** `DRAFT` → `ACTIVE` → `COMPLETED` → `ARCHIVED` dengan transisi tervalidasi dan audit trail.
4. **Eksekusi Sesi:** Penjadwalan sesi (`PLAN` → `EXECUTED`/`CANCELLED`), pelaksanaan kelas, dan penandaan selesai.
5. **Asesmen Formatif & Sumatif:** Observasi (observasi, jurnal, peer feedback, self assessment, refleksi) dan bukti sumatif (performance, project, action, product, presentation, final reflection) berfokus pada pencapaian Dimensi Profil Lulusan.
6. **Hasil Siswa & Laporan:** Matriks murid × dimensi dengan level `EMERGING` / `DEVELOPING` / `PROFICIENT` / `EXEMPLARY`, distribusi per dimensi, dan narasi otomatis per siswa untuk rapor.
7. **Evaluasi IPOO:** Skor kualitas `INPUT` → `PROCESS` → `OUTPUT` → `OUTCOME` dengan status kesehatan program.
8. **7KAIH (opsional):** Gerakan 7 Kebiasaan Anak Indonesia Hebat — definisi kebiasaan, tantangan mingguan, diary/check-in, monitoring guru, partisipasi orang tua — dikendalikan feature flag `ialos_7kahi`.