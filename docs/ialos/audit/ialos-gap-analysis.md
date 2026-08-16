# Analisis Gap IALOS

| Kapabilitas blueprint | Kondisi awal | Keputusan Phase 1 |
|---|---|---|
| Regulasi dan sumber resmi | Hanya referensi teks bebas pada versi kurikulum | Tambah registri ber-versi dan provenance immutable |
| Profil lulusan | Belum dimodelkan | Seed tepat delapan dimensi resmi |
| CP dan elemen | Belum ada entitas semantik | Tambah CP per versi/mapel/fase dan elemen berurutan |
| TP dan kriteria | Belum ada | Tambah tujuan, kriteria, lineage adaptasi, dan scope unit |
| ATP | Belum ada | Tambah workflow lengkap, urutan item, clone, lock |
| Coverage | Belum ada | Hitung deterministik dari TP, ATP item, fase, mapel, dan paket |
| Paket pembelajaran | Belum ada | Tambah paket unit-scoped yang menautkan TP/ATP |
| Concurrency | Check revision belum atomik | Terapkan update `WHERE id AND revision_number`; stale = HTTP 409 |
| Isolasi unit | Fondasi tersedia | Semua data adaptasi melewati `UnitScopeService` dan query scope |
| Import | Staging generik tersedia | Tambah staging khusus; validate/preview/apply, bukan source of truth |
| Audit | Fondasi tersedia | Catat mutasi dan transisi workflow melalui `AuditService` |
| UI | Belum ada | Tambah navigasi referensi/perencanaan dan halaman dasar bertahap |

> Status terkini dan cakupan fase lanjutan dicatat di [`../IMPLEMENTATION_STATUS.md`](../IMPLEMENTATION_STATUS.md). Phase 1 kini memakai halaman domain terpisah dan Control Center berbasis peran; Digital KSP sedang dilanjutkan sebagai bagian dari aplikasi yang sama.

## Risiko dan mitigasi

- Salah-scope data nasional: nasional selalu `unit_id IS NULL`, hanya read-only di workflow sekolah.
- Adaptasi kehilangan asal: `parent_objective_id` wajib untuk SCHOOL/TEACHER.
- ATP lintas mapel/fase: divalidasi pada setiap penambahan item dan coverage.
- Data terkunci berubah: layanan menolak mutasi status `LOCKED`/`ARCHIVED`.
- Konflik edit: OCC atomik dan respons 409, bukan last-write-wins.
- Migrasi mengganggu produksi: hanya add-table/add-index; tidak mengubah tabel Kumer.

## Dependensi

Phase 1 bergantung pada master unit, mapel, fase/tingkat, periode, pengguna, versi kurikulum, RBAC, dan audit yang sudah ada. Tidak ada layanan eksternal atau AI.
