# Audit Produksi — Absensi Siswa & Jurnal Pembelajaran

Tanggal: 11 Agustus 2026

## Arsitektur operasional

Satu ledger `attendance_sessions` kini menangani empat jenis sesi:

- `MORNING_ASSEMBLY`: apel dan absensi pagi oleh wali kelas/pengelola.
- `CLASSROOM`: kontrol kehadiran kelas oleh wali kelas.
- `SUBJECT`: presensi dan jurnal guru mata pelajaran berdasarkan jadwal resmi.
- `AFTERNOON_ASSEMBLY`: apel dan absensi siang/pulang.

Setiap sesi memiliki sumber deterministik, kunci unik, tanggal, kelas, guru/petugas, jadwal, workflow, revisi, dan audit trail. Sesi yang sama tidak dapat dibuat ganda, termasuk pada permintaan bersamaan.

## Integrasi

- Kalender pendidikan aktif menolak sesi pada hari non-efektif.
- Jadwal `APPROVED/LOCKED` menghasilkan timeline guru per tanggal dan menggabungkan blok JP berurutan.
- Substitusi guru sementara memperoleh akses penugasan selama tanggal substitusi aktif.
- Wali kelas memperoleh agenda pagi, kelas, dan siang untuk kelas binaannya.
- Jam apel siang mengikuti akhir slot pembelajaran resmi; jam operasional dapat dikonfigurasi per tahun ajaran dan unit.
- Status ketidakhadiran dari sesi sebelumnya menjadi saran untuk sesi berikutnya, bukan keputusan otomatis.
- Filter unit mendukung data SMP, SMA, atau gabungan sesuai scope akun.

## Status siswa

Status yang didukung: Hadir, Terlambat, Izin, Sakit, Alpa, dan Dispensasi. Keterlambatan menyimpan jam datang dan durasi menit. Setiap catatan dapat menyimpan alasan dan referensi sesi sumber.

## Jurnal

Jurnal mapel mencakup topik, tujuan pembelajaran, aktivitas pembelajaran, asesmen/capaian, tindak lanjut, dan catatan/kendala. Sesi non-mapel memakai catatan kegiatan yang lebih ringkas. Dokumen cetak mengikuti jenis sesi dan menampilkan semua bagian yang relevan.

## Workflow dan keamanan

- Guru dapat menyimpan draft atau mengirim sesi.
- Pengelola hanya dapat memverifikasi sesi yang sudah dikirim.
- Verifikasi mengunci sesi dan menyimpan pengguna serta waktu verifikasi.
- Koreksi wajib melalui aksi buka kembali dan verifikasi ulang.
- Arsip memakai soft delete; kunci sumber diubah agar sesi pengganti dapat dibuat tanpa menghapus jejak.
- Semua mutasi memakai POST, CSRF, permission, unit scope, ownership kelas/penugasan, validasi roster penuh, dan optimistic revision.
- Aktivitas create, update, arsip, verify, reopen, bulk verify, dan perubahan pengaturan dicatat pada audit log.

## UI/UX

Portal guru diubah menjadi timeline mobile-first: navigasi tanggal, ringkasan agenda, kartu apel/kelas/mapel/siang, status progres, dan riwayat. Form menggunakan kartu siswa, pencarian, status satu sentuhan, penghitung langsung, saran sesi sebelumnya, jurnal terstruktur, serta tombol draft/kirim yang tetap mudah dijangkau pada mobile.

Pengaturan tersedia melalui **Sistem → Pengaturan Absensi**. Monitoring eksekutif mendukung filter unit, kelas, mapel, jenis sesi, dan rentang tanggal.

## Verifikasi

- Migrasi `UpgradeIntegratedAttendanceJournal` dan `EnforceAttendanceSessionUniqueness` berhasil diterapkan.
- PHP lint: 20 berkas lulus.
- Tes integrasi lintas absensi, jadwal, kalender, role, dan dashboard: 26 tes / 171 assertion lulus.
- Suite unit dan security: 65 tes / 224 assertion lulus.
- Satu-satunya warning PHPUnit adalah extension code coverage yang tidak tersedia pada PHP CLI.
- Error lama `Unknown column teachers.code` telah diperbaiki dengan identifier guru yang benar dan left join aman untuk sesi non-mapel.

## Data aktif saat audit

Pengaturan SMP dan SMA tersedia untuk tahun ajaran aktif. Default apel pagi 06:45–07:30 dan apel siang 14:00–15:30; awal apel siang akan menyesuaikan akhir jadwal pelajaran pada tanggal/unit terkait. Propagasi saran ketidakhadiran aktif dan input mapel di luar jadwal nonaktif.
