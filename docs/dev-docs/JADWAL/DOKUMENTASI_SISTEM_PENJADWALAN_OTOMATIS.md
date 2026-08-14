# DOKUMENTASI TEKNIS SISTEM PENJADWALAN OTOMATIS (WMVAA AKADEMIA)
## Comprehensive End-to-End Technical Audit & Architecture Specification

---

> [!IMPORTANT]
> **Status Dokumen**: Authoritative System Specification & Technical Audit
> **Ekosistem**: WMVAA HUB / WMVAA Akademia Suite
> **Lokasi File**: `E:\xampp\htdocs\wmvaa.id\public_html\app.wmvaa.id\wmvaa-akademia\docs\dev-docs\JADWAL`
> **Tanggal Audit**: 2 Agustus 2026

---

## 📋 DAFTAR ISI & STRUKTUR MODUL DOKUMENTASI

Dokumentasi ini diuraikan secara detail tanpa ada logika, skema basis data, maupun algoritma yang disembunyikan. Dokumentasi terbagi menjadi modul-modul berikut:

| Modul | Nama Dokumen | Topik & Cakupan Utama |
| :--- | :--- | :--- |
| **Master Index** | [DOKUMENTASI_SISTEM_PENJADWALAN_OTOMATIS.md](./DOKUMENTASI_SISTEM_PENJADWALAN_OTOMATIS.md) | Arsitektur Utama, End-to-End Pipeline Data, Isolasi Unit Lintas Jenjang, & Rangkuman Audit |
| **Modul 01** | [01_KURIKULUM_DAN_PEMBAGIAN_JP.md](./01_KURIKULUM_DAN_PEMBAGIAN_JP.md) | Struktur Kurikulum, Perhitungan Total JP, Verifikasi Mapel Pilihan (Fase F), Single Source of Truth |
| **Modul 02** | [02_KEGIATAN_RUTIN_DAN_SLOT_WAKTU.md](./02_KEGIATAN_RUTIN_DAN_SLOT_WAKTU.md) | Template Slot Hari/Jam, Penguncian Otomatis (*Fixed Activities*), Hari Khusus (Jumat/Rabu/Senin), & Kapasitas Efektif |
| **Modul 03** | [03_PENUGASAN_GURU_DAN_LINTAS_JENJANG.md](./03_PENUGASAN_GURU_DAN_LINTAS_JENJANG.md) | *Teaching Assignments*, *Dual Assignment*, Availability Rule, Matriks Kesibukan Lintas Unit (SMP vs SMA) |
| **Modul 04** | [04_GENERATOR_JADWAL_DETERMINISTIK.md](./04_GENERATOR_JADWAL_DETERMINISTIK.md) | *Deterministic Greedy Engine*, Pra-syarat Kapasitas, 24 Strategi Variatif, Blok Pedagogis (2+2+1), & Fallback 1-JP |
| **Modul 05** | [05_FINALISASI_DAN_PENERAPAN_OTOMATIS.md](./05_FINALISASI_DAN_PENERAPAN_OTOMATIS.md) | **[FOKUS UTAMA]** *Auto-Apply Threshold*, Eliminasi FK Cascade Violation, Deteksi & Audit Konflik (CRITICAL vs SOFT) |
| **Modul 06** | [06_MASTER_MATRIX_DAN_CETAK_DOKUMEN.md](./06_MASTER_MATRIX_DAN_CETAK_DOKUMEN.md) | Tampilan Master Matrix Lintas Jenjang, *Interactive Drag & Drop*, & Modul Cetak Dokumen (Multi-Unit/Kelas/Guru) |

---

## 🏛️ 1. ARSITEKTUR PIPELINE DATA PENJADWALAN

Sistem Penjadwalan Otomatis WMVAA Akademia mengadopsi pola **Deterministic Multi-Stage Data Pipeline** yang menghubungkan modul Kurikulum, Kegiatan Rutin, Penugasan Guru, hingga Generator dan Penerapan Otomatis.

```mermaid
flowchart TD
    subgraph STAGE_1 ["1. SINKRONISASI PRASYARAT (DATA PREPARATION)"]
        A1["Kurikulum (Wajib + Pilihan Approved)"] --> A3["Structure Effective Hours (Single Source of Truth)"]
        A2["Teaching Assignments (Active Version)"] --> A3
        A3 --> B1["ScheduleRequirementSyncService"]
        B1 --> B2["schedule_requirements (Tabel Kebutuhan Jam)"]
    end

    subgraph STAGE_2 ["2. INISIALISASI SLOT & LINGKUP UNIT"]
        C1["curriculum_planning_settings (Slot Per Hari/Jam)"] --> C3["ScheduleSetupService"]
        C2["schedule_fixed_activities (Kegiatan Rutin)"] --> C3
        C3 --> C4["schedule_day_slots (Slot Waktu LESSON & FIXED)"]
        C3 --> C5["Isolasi Unit Sekolah (unit_id: 1=SMP, 2=SMA)"]
    end

    subgraph STAGE_3 ["3. ENGINE GENERATOR (DETERMINISTIC GREEDY)"]
        D1["Capacity Preflight Check (Lesson Capacity vs Required Hours)"] --> D2{"Kapasitas Cukup?"}
        D2 -- Tidak --> D3["Throw Capacity Exception & Hentikan"]
        D2 -- Ya --> D4["Loop 24 Strategi Generasi Bounded Variants"]
        D4 --> D5["Penempatan Blok Pedagogis (buildJpBlocks: 2+2+...+1)"]
        D5 --> D6["Pencegahan Bentrok Guru Lintas Unit (Cross-Unit Occupancy)"]
        D6 --> D7["Fallback Penempatan Slot Tunggal (1-JP Fallback Search)"]
        D7 --> D8["Simpan Kandidat ke schedule_generation_candidates"]
    end

    subgraph STAGE_4 ["4. FINALISASI & AUTO-APPLY (FOKUS UTAMA)"]
        E1["Evaluasi Kandidat Terbaik (usort by unplaced_reqs & total_slots)"] --> E2{"unplaced_reqs <= 1?"}
        E2 -- Ya --> E3["applyCandidate() Triggered"]
        E3 --> E4["Safe FK Cascade Delete (conflicts -> entries -> requirements)"]
        E4 --> E5["Insert 107+ Candidate Entries to schedule_entries"]
        E5 --> E6["Conflict Detection Service Audit"]
        E6 --> E7{"CRITICAL / UNMET_HOURS Conflicts?"}
        E7 -- Ada --> E8["DB Trans Rollback & Surfacing Error Modal"]
        E7 -- 0 Critical --> E9["DB Trans Complete & Set Applied Status (100% Published)"]
    end

    subgraph STAGE_5 ["5. MASTER MATRIX & PRINTING"]
        F1["Master Matrix Multi-Jenjang (SMP & SMA)"] --> F2["Interactive Drag & Drop Active"]
        F1 --> F3["Cetak Dokumen (Print PDF / Per Kelas / Per Guru)"]
    end

    B2 --> D1
    C4 --> D1
    C5 --> D1
    D8 --> E1
```

---

## 🔒 2. ISOLASI UNIT SEKOLAH & ATURAN LINTAS JENJANG

SistemWMVAA Akademia mendukung operasional sekolah multi-jenjang (**SMP Advent Sogokmo** dan **SMA Advent Sogokmo**) yang berbagi fasilitas gedung dan beberapa tenaga pengajar (*Shared Teachers*).

### 2.1 Peta Unit Sekolah (`school_units`)
- **`unit_id = 1`**: SMP Advent Sogokmo (Tingkat / Grade 7, 8, 9)
- **`unit_id = 2`**: SMA Advent Sogokmo (Tingkat / Grade 10, 11, 12)

### 2.2 Aturan Isolasi Data (`unit_id` Enforcement)
1. **Versi Jadwal (`schedule_versions`)**:
   Setiap versi jadwal diikat secara eksplisit ke satu unit sekolah (`unit_id = 1` atau `unit_id = 2`).
2. **Sinkronisasi Kebutuhan (`syncFromAssignments`)**:
   Tabel `schedule_requirements` memfilter penugasan guru strictly berdasarkan `ta.unit_id = $version['unit_id']`. Hal ini mencegah tercampurnya kebutuhan jam mengajar SMP ke dalam jadwal SMA.
3. **Validasi Guru Lintas Unit (`getCrossUnitTeacherScheduleOccupancy`)**:
   Meskipun versi jadwal diisolasi per unit, guru yang mengajar di kedua jenjang (misal: Pak Yohanis Ondy, Bu Sarlota Yansip, Bu Maria Sinaga, Pak Frengky Lokobal) dipindai jam kesibukannya secara real-time pada tabel `schedule_entries` unit lawan.

---

## 📂 3. RINGKASAN MODUL DOKUMENTASI TEKNIS

Detail teknis secara mendalam dari setiap modul tersimpan dalam file-file terpisah pada direktori `docs/dev-docs/JADWAL`:

1. **[01_KURIKULUM_DAN_PEMBAGIAN_JP.md](./01_KURIKULUM_DAN_PEMBAGIAN_JP.md)**
   Membahas algoritma kalkulasi JP otomatis, integrasi Kurikulum Merdeka (Intrakurikuler, P5, Mapel Pilihan Fase F), verifikasi `elective_offerings.is_approved = 1`, dan sinkronisasi `schedule_requirements`.
2. **[02_KEGIATAN_RUTIN_DAN_SLOT_WAKTU.md](./02_KEGIATAN_RUTIN_DAN_SLOT_WAKTU.md)**
   Membahas pembuatan template hari/jam (`schedule_day_slots`), penguncian otomatis kegiatan rutin (`schedule_fixed_activities`), dan rumus kalkulasi kapasitas efektif slot jam pelajaran (`baseLessonCapacity - fixedCount`).
3. **[03_PENUGASAN_GURU_DAN_LINTAS_JENJANG.md](./03_PENUGASAN_GURU_DAN_LINTAS_JENJANG.md)**
   Membahas pengelolaan penugasan mengajar (`teaching_assignments`), aturan ketersediaan guru (`teacher_availability_rules`), dan fungsi deteksi bentrok ganda lintas unit.
4. **[04_GENERATOR_JADWAL_DETERMINISTIK.md](./04_GENERATOR_JADWAL_DETERMINISTIK.md)**
   Membahas arsitektur *Deterministic Greedy Engine*, pemeriksaan pra-syarat kapasitas (*Preflight Check*), 24 variasi strategi pemetaan, alokasi blok 2-JP, dan *fallback* slot 1-JP.
5. **[05_FINALISASI_DAN_PENERAPAN_OTOMATIS.md](./05_FINALISASI_DAN_PENERAPAN_OTOMATIS.md)** **[FOKUS UTAMA]**
   Membahas secara mendetail alur pengeksekusian `applyCandidate`, eliminasi kegagalan *Foreign Key Constraint*, evaluasi *Auto-Apply Threshold*, dan klasifikasi tingkat keparahan konflik.
6. **[06_MASTER_MATRIX_DAN_CETAK_DOKUMEN.md](./06_MASTER_MATRIX_DAN_CETAK_DOKUMEN.md)**
   Membahas rendering UI Master Matrix Multi-Jenjang, penggeseran manual kartu pelajaran (*Drag & Drop*), dan modul cetak dokumen jadwal.

---
*Dokumen ini dibuat dan dipelihara oleh Tim Antigravity Antigravity Core AI Architecture untuk WMVAA Akademia.*
