# WMVAA Academia — Integrated Academic & Learning Operating System (IALOS)

## Paket Dokumentasi Arsitektur, PRD, dan Roadmap Implementasi

**Versi:** 1.0
**Tanggal:** 13 Agustus 2026
**Status:** Blueprint strategis dan teknis — siap digunakan sebagai dasar pengembangan bertahap
**Target aplikasi:** WMVAA Academia (SMP Advent Sogokmo & SMA Advent Sogokmo / WMVAA)

---

## Tujuan Paket Ini

Paket ini menerjemahkan regulasi dan panduan pendidikan nasional yang dipelajari menjadi rancangan sistem digital terpadu. Target akhirnya bukan sekadar aplikasi administrasi sekolah, melainkan **Integrated Academic & Learning Operating System** yang menghubungkan:

**Regulasi → KSP → Struktur Kurikulum → Penugasan Guru → Jadwal → Perencanaan Pembelajaran → Pelaksanaan → Asesmen → Tindak Lanjut → Kokurikuler/Ekstrakurikuler → Rapor/Portofolio → Evaluasi Sekolah → Perbaikan Kurikulum.**

Dokumentasi ini sengaja membedakan antara:

1. **ketentuan/regulasi nasional yang menjadi rujukan;**
2. **kebijakan dan adaptasi sekolah;**
3. **desain produk dan arsitektur perangkat lunak yang merupakan rancangan WMVAA Academia.**

---

## Isi Paket

1. `01_VISI_DAN_PRINSIP_PRODUK.md` — visi produk, prinsip desain, batasan dan tujuan.
2. `02_PRD_MASTER_IALOS.md` — Product Requirements Document utama.
3. `03_ARSITEKTUR_DOMAIN_DAN_MODUL.md` — bounded context/domain dan hubungan antarmodul.
4. `04_REGULATION_AND_COMPLIANCE_ENGINE.md` — registry regulasi, rule engine dan compliance checker.
5. `05_KSP_DIGITAL_OPERATING_MODEL.md` — digitalisasi Kurikulum Satuan Pendidikan.
6. `06_TEACHING_AND_LEARNING_ENGINE.md` — lesson planning, teaching mode, evidence dan pembelajaran mendalam.
7. `07_ASSESSMENT_MASTERY_REPORTING.md` — asesmen diagnostik/formatif/sumatif, mastery dan rapor.
8. `08_COCURRICULAR_EXTRACURRICULAR_CHARACTER.md` — kokurikuler, ekstrakurikuler, kegiatan khas sekolah dan profil lulusan.
9. `09_DATA_MODEL_AND_INTEGRATION_BLUEPRINT.md` — model data konseptual dan integrasi dengan modul Academia saat ini.
10. `10_AI_ACADEMIC_COPILOT_AND_GOVERNANCE.md` — AI copilot yang aman, terukur dan tidak mengambil keputusan final.
11. `11_ROADMAP_MILESTONES_IMPLEMENTASI.md` — tahapan pembangunan yang menghindari big-bang rewrite.
12. `12_ACCEPTANCE_SECURITY_AND_QUALITY_GATES.md` — definisi selesai, test, security, audit dan quality gate.
13. `13_SOURCE_TO_SYSTEM_TRACEABILITY.md` — matriks sumber nasional → kebutuhan sistem.
14. `14_IMPLEMENTATION_AGENT_BRIEF.md` — instruksi ringkas untuk AI/software agent ketika implementasi dimulai.
15. `WMVAA_Academia_IALOS_Master_PRD.docx` — versi Word ringkas-komprehensif untuk dibaca, dibagikan, dan direview.

---

## Sumber Utama yang Dianalisis

- Permendikdasmen Nomor 13 Tahun 2025 tentang perubahan kurikulum.
- Keputusan Menteri Pendidikan Dasar dan Menengah Nomor 126/P/2025 tentang Pedoman Implementasi Pembelajaran Mendalam.
- Panduan Pembelajaran dan Asesmen, Edisi Revisi 2025.
- Panduan Kokurikuler 2025.
- Panduan Pengembangan Kurikulum Satuan Pendidikan, Edisi Revisi 2025.
- Panduan Guru Informatika Kelas X yang sebelumnya dianalisis sebagai contoh kaya struktur subject-learning-pack.

---

## Prinsip Implementasi

**Jangan membangun seluruh sistem sekaligus.** Pertahankan modul WMVAA Academia yang sudah stabil, lalu tambahkan lapisan baru secara bertahap dengan migration, service contract, API/domain boundary, test dan feature flag.

**Dokumen bukan sumber data utama.** KSP, RPP/modul ajar, rapor, laporan supervisi, dan laporan kokurikuler sebaiknya menjadi *rendered views/output* dari data terstruktur.

**Guru tetap pengambil keputusan pedagogis.** Sistem memberi rekomendasi, validasi, otomatisasi administrasi dan evidence; AI tidak boleh diam-diam mengubah CP/TP, memberi nilai akhir, mengunci rapor, menentukan kenaikan kelas, kelulusan, atau keputusan disipliner.

**Semua aturan harus berversi.** Perubahan regulasi di masa depan tidak boleh merusak histori tahun ajaran lama.

---

## Target Arsitektur Akhir

```text
NATIONAL REGULATION
        ↓
REGULATION & COMPLIANCE ENGINE
        ↓
DIGITAL KSP
        ↓
CURRICULUM / CP / TP / ATP
        ↓
WORKFORCE + SCHEDULING
        ↓
TEACHING & LEARNING EXECUTION
        ↓
ASSESSMENT + EVIDENCE + MASTERY
        ↓
FOLLOW-UP / REMEDIAL / ENRICHMENT
        ↓
COCURRICULAR / EXTRACURRICULAR
        ↓
REPORTING / PORTFOLIO
        ↓
QUALITY IMPROVEMENT / KSP REVISION
```
