# Walkthrough — Implementation of Interactive Curriculum Matrix & Advanced Web CRUD Engine

Modul **Interactive Curriculum Matrix & Advanced Web CRUD Engine** telah berhasil diimplementasikan sepenuhnya pada proyek **WMVAA Akademia**. Pengguna kini tidak perlu lagi mengunggah file Excel untuk mengelola struktur kurikulum secara cepat dan massal.

---

## 🚀 Perubahan Komponen & File Utama

### 1. Backend Service Layer
*   **[`CurriculumStructureService.php`](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/CurriculumStructureService.php)**:
    *   Ditambahkan method `getMatrixView()` untuk me-format data struktur menjadi grid 2-dimensi (Mata Pelajaran $\times$ Tingkat Kelas) beserta kalkulasi total jam per minggu per tingkat secara otomatis.
    *   Ditambahkan method `cloneFromPreviousVersion()` untuk menyalin seluruh struktur dari kurikulum versi sebelumnya dalam 1x klik.
    *   Ditambahkan method `applyUnitPreset()` untuk mengisi struktur default bagi seluruh mata pelajaran aktif milik unit sekolah (SMP/SMA).

### 2. Controller & Routing
*   **[`CurriculumMatrixController.php`](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Controllers/CurriculumMatrixController.php)**:
    *   Endpoint `GET curriculum/(:segment)/matrix`: Menampilkan halaman Editor Matriks Interaktif.
    *   Endpoint AJAX `POST curriculum/(:segment)/matrix/update-cell`: Mengubah/menyimpan jam pelajaran per sel secara instan tanpa *reload* halaman (menghapus otomatis jika diisi 0 JP).
    *   Endpoint `POST curriculum/(:segment)/matrix/bulk-store`: Penambahan massal multiple mapel ke multiple tingkat kelas.
    *   Endpoint `POST curriculum/(:segment)/matrix/clone-previous`: Salin struktur dari versi kurikulum sebelumnya.
    *   Endpoint `POST curriculum/(:segment)/matrix/apply-preset`: Penerapan preset standar unit.
*   **[`Routes.php`](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Config/Routes.php)**: Mendaftarkan seluruh rute baru modul matriks.

### 3. Tampilan & UI Interface
*   **[`app/Views/curriculum/matrix/index.php`](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Views/curriculum/matrix/index.php)**:
    *   **Live Interactive Grid Matrix Table**: Input sel berbasis angka jam pelajaran (JP) dengan penanda warna *soft blue* untuk sel terisi.
    *   **Live AJAX Autosave & Status Badge**: Indikator "Tersimpan" otomatis muncul di pojok kanan atas begitu sel diubah.
    *   **Real-time Column & Grand Total Recalculation**: Kalkulasi otomatis footer total jam per tingkat dan KPI total jam sekolah.
    *   **Modals**: Modal Tambah Massal, Modal Salin Kurikulum Lalu, dan Modal Preset Standar Unit.
*   **[`app/Views/curriculum/versions/show.php`](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Views/curriculum/versions/show.php)**:
    *   Menambahkan *View Switcher Toggle*: **Tampilan List** $\leftrightarrow$ **Editor Matriks**.

---

## 🧪 Pengujian & Verifikasi

- **Verifikasi Sintaks PHP**: Seluruh file PHP baru dan teredit ([`CurriculumStructureService.php`](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/CurriculumStructureService.php), [`CurriculumMatrixController.php`](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Controllers/CurriculumMatrixController.php), [`Routes.php`](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Config/Routes.php)) lolos uji `php -l` tanpa syntax error.
- **Kepatuhan RBAC**: Seluruh endpoint dibatasi secara ketat oleh *permission* `curriculum.view` dan `curriculum.manage`.
