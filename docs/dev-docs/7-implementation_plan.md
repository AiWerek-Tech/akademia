# Advanced Curriculum Matrix & Web CRUD Engine

Rencana pengembangan modul **Interactive Curriculum Matrix & Advanced Web CRUD** pada **WMVAA Akademia** untuk menghilangkan ketergantungan pada Impor Template Excel dan memungkinkan pengisian/pengeditan struktur kurikulum secara instan, otomatis, dan canggih langsung dari Web App.

---

## 💡 Konsep Utama & Alur Kerja

Saat ini pengisian struktur kurikulum dapat dilakukan via modal per mapel (satu per satu) atau melalui unggah file Excel. Untuk memberikan pengalaman yang jauh lebih cepat, interaktif, dan canggih tanpa Excel, kita akan membangun **Interactive Matrix Editor**:

```
+-----------------------------------------------------------------------------------+
|  INTERACTIVE CURRICULUM MATRIX EDITOR                                             |
|  [⚡ Salin dari Versi Lalu]  [🪄 Muat Preset Standar Unit]  [➕ Tambah Mapel Massal] |
+-----------------------------------------------------------------------------------+
| Mata Pelajaran          | Kategori       | Kelas VII | Kelas VIII | Kelas IX | Action |
+-----------------------------------------------------------------------------------+
| Matematika              | Intrakurikuler | [ 4.0 ]   | [ 4.0 ]    | [ 5.0 ]  | ⚙️ 🗑️  |
| Bahasa Indonesia        | Intrakurikuler | [ 5.0 ]   | [ 5.0 ]    | [ 5.0 ]  | ⚙️ 🗑️  |
| Pendidikan Agama (PAK)  | Intrakurikuler | [ 3.0 ]   | [ 3.0 ]    | [ 3.0 ]  | ⚙️ 🗑️  |
| IPAS / IPA Terpadu      | Intrakurikuler | [ 4.0 ]   | [ 4.0 ]    | [ 4.0 ]  | ⚙️ 🗑️  |
+-----------------------------------------------------------------------------------+
| TOTAL JAM EFLEKTIF PER MINGGU            |   16.0 JP |   16.0 JP  |  17.0 JP |        |
+-----------------------------------------------------------------------------------+
```

---

## 🛠️ Fitur Canggih Yang Akan Diberikan

### 1. **Grid Matriks Interaktif (Live Cell Editing)**
*   Mata pelajaran ditampilkan sebagai baris, dan Tingkat Kelas (VII-IX / X-XII) sebagai kolom.
*   **Inline Editing**: Pengguna cukup mengklik sel jam (JP) untuk mengubah angka secara instan via AJAX tanpa *reload* halaman.
*   **Kalkulasi Real-Time**: Total Jam Pelajaran (JP) per tingkat kelas dan total keseluruhan sekolah otomatis dihitung secara *live* saat angka diubah.

### 2. **Auto-Population & Template Generator (1-Click)**
*   **Salin Kurikulum Sebelumnya**: Menyalin seluruh struktur dari kurikulum versi aktif sebelumnya dalam 1x klik.
*   **Muat Preset Standar Unit**: Otomatis membuat struktur awal berdasarkan daftar Mata Pelajaran aktif di Unit sekolah (SMP / SMA).

### 3. **Penambahan & Editing Massal (Bulk CRUD)**
*   **Modal Multi-Select Mapel**: Menambahkan beberapa mata pelajaran sekaligus ke seluruh tingkat kelas secara bersamaan.
*   **Batch Action**: Mengatur toggle "Masuk Rapor" atau "Hitung Beban Mengajar" untuk semua mapel sekaligus.

### 4. **Live Audit & Rule Checker**
*   Pemeriksaan otomatis di samping grid yang memberi tahu jika ada mapel wajib yang jamnya 0 JP atau melebihi kuota jam per minggu yang diizinkan.

---

## 📐 Perubahan Komponen & Kode

---

### Backend (Controllers & Services)

#### [NEW] [CurriculumMatrixController.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Controllers/CurriculumMatrixController.php)
- Method `matrix(string $uuid)`: Menyiapkan data struktur dalam format matriks (Mapel $\times$ Tingkat Kelas).
- Method `updateCell()`: AJAX endpoint untuk menyimpan perubahan JP (Jam Pelajaran) secara cepat saat pengguna mengetik angka di tabel.
- Method `bulkStore()`: Menambahkan multiple mapel sekaligus ke tingkat kelas yang dipilih.
- Method `cloneFromPrevious()`: Salin struktur dari versi kurikulum sebelumnya.
- Method `applyUnitPreset()`: Otomatis mengisi struktur awal dari master mapel unit.

#### [MODIFY] [CurriculumStructureService.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/CurriculumStructureService.php)
- Menambahkan method `getMatrixView(int $versionId, int $unitId)` untuk me-format data relasi menjadi matriks 2-dimensi.
- Menambahkan method `batchUpsertStructures(int $versionId, array $matrixData)` untuk transaksi database massal yang aman (*atomic transaction*).

#### [MODIFY] [Routes.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Config/Routes.php)
- Menambahkan rute baru untuk editor matriks interaktif dan AJAX API endpoints.

---

### Frontend & UI

#### [NEW] [matrix.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Views/curriculum/matrix/index.php)
- Halaman UI Matriks Kurikulum dengan desain modern, responsive, dan dilengkapi *hotkeys* (Navigasi panah keyboard di sel tabel).

#### [MODIFY] [show.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Views/curriculum/versions/show.php)
- Menambahkan tombol pilihan tampilan: **Tampilan Matriks Grid Interaktif** dan **Tampilan Tabel List Detail**.

---

## 🧪 Rencana Verifikasi

### 1. Pengujian Unit & Service
- Menjalankan PHPUnit test untuk pengujian `batchUpsertStructures` dan deteksi bentrok unique scope.

### 2. Pengujian Manual & UI
- Uji coba pengisian jam di sel matriks secara langsung (AJAX inline edit).
- Uji coba fitur "Salin Kurikulum Sebelumnya" dan "Muat Preset Standar Unit".
- Memastikan total kalkulasi JP per minggu berubah secara real-time.
