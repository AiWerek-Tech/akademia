# Milestone Plan — WMVAA Akademia

Proyek WMVAA Akademia direncanakan dalam beberapa Milestone berikut.

---

## Milestone 1: Manajemen Sesi & Autentikasi
- **Status**: ✅ **SELESAI**
- **Cakupan**: Login, Logout, Ganti Password Terpaksa, Manajemen User, RBAC dasar, Unit/Period switcher, Audit Logging, CSRF, Dark/Light Mode.

## Milestone 2: Master Data Akademik Terpadu
- **Status**: ✅ **SELESAI & DIKERASKAN (HARDENED)**
- **Cakupan**:
  - **Guru**: Manajemen profil lengkap, hitung persentase kelengkapan data, pengaitan unit (SMP/SMA/Multi-unit), verifikasi admin.
  - **Duplikasi Guru**: Deteksi duplikasi berbasis Fuzzy Name, NIP, NIK, No HP, penggabungan data (merge), soft-delete data terduplikasi, dan riwayat audit.
  - **Mata Pelajaran**: Kode unik global, ketersediaan per unit sekolah, nama alias mapel, optimisic locking.
  - **Tingkat Kelas**: Data awal (seeder) SMP VII-IX dan SMA X-XII beserta fasenya, seeder rerun-safe.
  - **Rombongan Belajar (Kelas)**: Pembuatan rombel baru per periode & unit, fungsi salin/copy rombel lintas periode akademik, optimistic locking.
  - **Ruangan**: Ruangan unit-specific dan bersama (shared), kapasitas non-negatif, detail fasilitas (JSON).
  - **Import & Export**: Impor dari berkas Excel menggunakan alur staging (upload -> validasi baris & fuzzy check -> preview -> commit), ekspor data ke Excel.

## Milestone 3: Perencanaan Kurikulum & Penjadwalan
- **Status**: ⏳ **BELUM DIMULAI**
- **Cakupan**: Matriks struktur kurikulum, jam pelajaran (JP) mingguan, penugasan guru (teaching assignments), beban kerja (workload), penjadwalan otomatis/heuristik, piket harian, SK resmi (PDF).
