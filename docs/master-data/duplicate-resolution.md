# Panduan Resolusi Duplikasi Guru

Sistem WMVAA Akademia menerapkan sistem deteksi duplikasi berbasis fuzzy matching (Levenshtein distance & Soundex similarity) pada input nama guru dan tanggal lahir/unit utama.

---

## 1. Alur Deteksi Duplikasi

Saat membuat atau mengupdate guru baru:
1. Sistem mengecek kesamaan NIP, NIK, dan Nomor Pegawai secara eksak. Jika ditemukan kesamaan eksak, sistem akan menolak pendaftaran langsung (Exception).
2. Jika tidak ada kecocokan eksak, sistem menghitung kemiripan fuzzy nama (Soundex & Levenshtein) dan atribut lainnya (No HP, Email).
3. Jika score kecocokan $\ge 60$:
   - Guru baru tetap disimpan (status `INCOMPLETE`).
   - Sistem membuat grup review duplikasi (`DuplicateReviewGroup`) dengan status `OPEN`.
   - Admin didorong untuk melakukan peninjauan manual melalui menu **Review Duplikasi**.

---

## 2. Pilihan Keputusan Review

Ketika meninjau calon duplikasi, Admin memiliki 3 pilihan keputusan:

### A. KEEP_SEPARATE (Simpan Terpisah)
- Digunakan jika nama mirip tetapi terbukti merupakan dua individu yang berbeda.
- Dampak: Status grup diubah menjadi `RESOLVED`, kedua guru tetap aktif secara terpisah.

### B. REVIEW_LATER (Tinjau Nanti)
- Digunakan jika admin belum dapat memutuskan (membutuhkan konfirmasi pihak sekolah/guru bersangkutan).
- Dampak: Grup tetap `OPEN`.

### C. MERGE (Gabungkan)
- Digunakan jika terbukti data tersebut adalah individu yang sama (duplikat).
- Admin memilih guru mana yang menjadi **Data Utama (Canonical)**.
- Admin dapat memilih field mana saja yang dipakai (misal: mengambil Email dari guru A, dan NIP dari guru B).
- Dampak:
  - Data utama diperbarui dengan gabungan informasi pilihan.
  - Data duplikat diubah statusnya menjadi `ARCHIVED` dan di-soft delete (`deleted_at` diisi).
  - Hubungan unit kerja (`teacher_unit_assignments`) digabungkan ke data utama.
  - Catatan audit log mencatat aksi penggabungan ini secara detail.
