# Source Lineage dan Versioning

## Referensi resmi

Setiap dokumen tercatat sebagai regulasi, lalu memiliki satu atau lebih versi dengan URL, SHA-256, MIME type, dan waktu publikasi. Versi yang sudah dipublikasikan immutable; koreksi dilakukan dengan versi baru. `curriculum_sources` menautkan data kurikulum ke versi regulasi/provenance yang sesuai.

## Lineage TP

- `NATIONAL`: `unit_id` dan `parent_objective_id` harus null.
- `SCHOOL`: wajib memiliki unit dan parent TP nasional.
- `TEACHER`: wajib memiliki unit dan parent; dapat diturunkan dari adaptasi sekolah.

TP nasional berstatus `PUBLISHED`, `LOCKED`, atau `ARCHIVED` tidak dapat diedit. Pengguna membuat adaptasi sehingga sumber resmi tetap utuh.

Coverage membentuk effective set: bila sebuah TP memiliki adaptasi pada unit aktif, parent-nya tidak dihitung lagi sebagai kewajiban terpisah. Adaptasi bertingkat mengikuti prinsip yang sama sehingga turunan paling spesifik menjadi target coverage.

## Versioning dan konflik

Update menerima revision yang terakhir dilihat pengguna. SQL update memakai kondisi `id = ? AND revision_number = ?`. Jika tidak tepat satu baris berubah, layanan melempar `ConcurrencyException`; endpoint mengembalikan HTTP 409. ATP terkunci hanya dapat dilanjutkan melalui clone DRAFT dengan `parent_sequence_id`.
