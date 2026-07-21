# Panduan Import Master Data

Sistem import master data terpadu (Guru, Mapel, Kelas, Ruangan) menggunakan alur staging untuk memastikan validitas data sebelum dimasukkan ke database operasional.

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
4. **Preview & Keputusan**: Admin dapat meninjau preview baris data, dan mengubah keputusan per baris (`INSERT`, `UPDATE`, `SKIP`).
5. **Apply**: Setelah disetujui, data dimasukkan ke database operasional dalam satu transaksi database.
