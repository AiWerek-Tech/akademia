# 📚 DOKUMENTASI MODUL KALENDER PENDIDIKAN (EDUCATIONAL CALENDAR)
## WMVAA AKADEMIA — YAYASAN PENDIDIKAN ADVENT PAPUA (YPAP)

Dokumentasi ini berisi konsep dasar, rancangan arsitektur, skema database, algoritma penyusunan otomatis (*automatic generation*), serta tata letak cetak dokumen untuk **Modul Kalender Pendidikan Otomatis WMVAA Akademia**.

---

### 📂 Struktur Dokumentasi:

1. [**`Konsep_Dasar_Kalender_Pendidikan.md`**](./Konsep_Dasar_Kalender_Pendidikan.md)
   - Latar belakang & urgensi modul kalender pendidikan.
   - Analisis kalender resmi Dinas Pendidikan Kabupaten Jayawijaya (TA 2026/2027).
   - Analisis format & aturan khusus Sekolah Advent (WMVAA Sogokmo).
   - Konsep 5-Layer Arsitektur Kalender Pendidikan.
   - Formula perhitungan HES (Hari Efektif Sekolah), HEB (Hari Efektif Belajar), dan Minggu Efektif.

2. [**`Spesifikasi_Teknis_Dan_Desain_Sistem.md`**](./Spesifikasi_Teknis_Dan_Desain_Sistem.md)
   - Skema database (tabel `academic_calendars`, `academic_calendar_days`, `academic_calendar_events`, `academic_calendar_event_types`).
   - Algoritma *Automatic Calendar Generator* (Tahap 1 s.d. Tahap 5).
   - Integrasi sub-sistem (Penjadwalan Otomatis, Absensi Siswa, Jadwal Piket Guru).
   - Desain antarmuka UI/UX (Matriks Bulan 1..31 & Editor Warna).
   - Spesifikasi cetak PDF Landscape resmi YPAP (Kop, Matriks, Keterangan Warna, Program Sekolah, Tanda Tangan Direktur).

---

### 📌 Referensi Dokumen Dasar:
- **Kalender Dinas**: *Keputusan Kepala Dinas Pendidikan dan Kebudayaan Kabupaten Jayawijaya Nomor 400.3/1473.a/P&K/2026 Tanggal 16 Juni 2026 (TA 2026/2027)*.
- **Format WMVAA**: *Format Kalender Pendidikan SMP-SMA Advent Sogokmo T.A. 2024/2025 - Semester 2*.

---
*Dokumen ini disusun sebagai panduan pengembang (developer guide) untuk implementasi Modul Kalender Pendidikan WMVAA Akademia.*
