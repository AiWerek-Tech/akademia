# Panduan Keluaran Jadwal

## Jadwal Kelas

Buka versi jadwal, pilih kelas, lalu klik **Cetak Kelas**. Halaman cetak menampilkan matriks hari × JP beserta mata pelajaran, guru, dan ruang. Gunakan dialog cetak browser untuk mencetak atau menyimpan PDF.

## Jadwal Guru

Endpoint laporan guru menampilkan daftar hari, JP, kelas, mata pelajaran, dan ruang:

`/schedules/{versionId}/reports/teacher/{teacherId}`

Keluaran dibatasi oleh akses unit dan izin `schedules.export`.

## Format Integrasi

Tambahkan `?format=json` pada endpoint laporan kelas/guru untuk memperoleh representasi JSON. Ekspor Excel khusus jadwal unit/ruang belum tersedia dan tidak diklaim sebagai fitur aktif.
