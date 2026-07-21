# Panduan Export Master Data

Sistem menyediakan fitur ekspor untuk seluruh data master ke format berkas Excel (.xlsx).

---

## Fitur Ekspor

- **Format Didukung**: Excel `.xlsx` secara penuh. PDF status saat ini ditandai sebagai keterbatasan sistem (tidak diimplementasikan karena dependensi mPDF/dompdf tidak terpasang di server).
- **Keamanan & Isolation**:
  - Hanya pengguna dengan izin spesifik (misal `teachers.export`) yang dapat mengekspor.
  - Hasil ekspor disaring otomatis berdasarkan filter unit aktif (SMP / SMA) dan periode aktif pengguna untuk mencegah kebocoran data antar unit.
- **Audit**: Setiap aktivitas ekspor dicatat ke dalam audit log sistem.
