# Panduan Import IALOS Education

Import Phase 1 menerima array JSON terkontrol. Setiap baris memiliki `entity_type` `CP` atau `TP` beserta field domainnya. Pipeline:

1. Pilih unit dan versi kurikulum.
2. Upload/paste payload; sistem menghitung SHA-256 dan menolak duplikat isi pada unit yang sama.
3. Sistem menyimpan batch dan row staging, lalu memberi status `VALID` atau `ERROR` beserta detail.
4. Tinjau preview. Batch dengan error tidak dapat diaplikasikan.
5. Apply menjalankan seluruh baris dalam satu transaksi dan menulis target entity serta audit batch.

Staging bukan source of truth. Hanya row yang sukses melalui apply yang menjadi CP/TP. Import unit tidak diizinkan membuat TP nasional. Data Informatika X yang dipakai pada automated test hanya fixture test dan tidak masuk production seeder.
