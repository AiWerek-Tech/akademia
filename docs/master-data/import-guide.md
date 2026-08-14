# Panduan Import Master Data

Sistem import master data terpadu (Guru, Mata Pelajaran, Tingkat Kelas, Kelas/Rombel, dan Ruangan) menggunakan staging agar data divalidasi sebelum masuk ke database operasional.

---

## Alur Proses Import

```
[Upload File Excel] ──> [Simpan ke Staging] ──> [Validasi & Deteksi Duplikat] 
                                                        │
[ Terapkan / Commit ] <── [ Tinjau Preview / Edit ] <───┘
```

1. **Unduh Template**: Unduh template Excel resmi untuk entitas terkait. Template memiliki tab instruksi pengisian.
2. **Upload File**: Upload file `.xlsx`, `.xls`, atau `.csv`. Sistem akan melakukan hashing berkas untuk mencegah upload ganda.
3. **Validasi Staging**:
   - Baris berstatus **VALID**: Siap di-insert.
   - Baris berstatus **WARNING**: Terdeteksi data sudah ada (akan di-update) atau terdeteksi calon duplikat.
   - Baris berstatus **ERROR**: Data tidak valid (misal format email salah atau nama kosong). Baris ini akan dilewati (skip).
4. **Preview**: Admin meninjau nilai, pesan validasi, dan rencana aksi (`INSERT`, `UPDATE`, atau `SKIP`). Kandidat duplikat guru ditahan untuk ditangani melalui Review Duplikat.
5. **Apply**: Setelah disetujui, data dimasukkan ke database operasional dalam satu transaksi database.

## Satu Template Resmi

- Tombol **Template** pada halaman Guru, Mata Pelajaran, Tingkat Kelas, Kelas/Rombel, dan Ruangan mengunduh file dari generator yang sama dengan halaman **Import Master Data**.
- Definisi kolom, kolom wajib, contoh, format tanggal/teks, dropdown, dan referensi berasal dari satu kontrak backend (`MasterImportService`). Tidak ada lagi template khusus halaman yang terpisah.
- Tombol **Export Data** menghasilkan workbook dengan sheet dan header teknis yang sama. Sheet **Data Import** dari hasil export dapat dipakai sebagai dasar koreksi atau pemindahan data lalu diunggah kembali melalui Import Master.
- Jangan mengganti nama sheet **Data Import** atau header teknis pada baris pertama. Nama yang ramah pengguna, status wajib/opsional, aturan, dan contoh tersedia pada sheet **Petunjuk Pengisian**.

## Batas dan Keamanan

- Maksimum 10 MB, 10.000 baris data, dan 52 kolom.
- Header harus unik dan sama dengan template terbaru.
- Baris kosong dan format Excel yang hanya memperbesar dimensi sheet tidak dihitung sebagai data.
- File identik dideteksi melalui SHA-256 agar tidak diterapkan dua kali.
- Nilai unit selalu diperiksa terhadap akses pengguna.
