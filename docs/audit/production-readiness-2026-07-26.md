# Audit Kesiapan Produksi — 26 Juli 2026

## Ringkasan

Audit dilakukan terhadap dokumentasi, perubahan terbaru, 190 test yang ditemukan, konfigurasi, skema MariaDB, pipeline master data, kurikulum, pembagian tugas, beban kerja, penjadwalan, dan keluaran dokumen.

Status saat audit dimulai **belum siap produksi**. Terdapat lima blocker:

1. Migrasi penjadwalan tercatat selesai tetapi 21 tabel jadwal tidak ada.
2. Sebelas halaman Penugasan/Beban/Jadwal merender section layout lama sehingga konten tampak kosong.
3. Foreign key jadwal menunjuk `users.id`, sedangkan penugasan memakai `teachers.id`, dengan tipe kolom yang juga berbeda.
4. Seeder izin tidak mengenali nama role aplikasi sehingga Super Admin dan tim kurikulum tidak mendapat akses jadwal.
5. Seeder Penugasan mengosongkan seluruh `role_permissions`, sehingga izin Master/Kurikulum hilang setiap kali seeder dijalankan.

Kelima blocker tersebut telah diperbaiki dan diverifikasi pada database aplikasi.

## Perbaikan yang Diterapkan

### Database dan keamanan cakupan

- Cadangan dibuat sebelum reparasi skema.
- 21 tabel penjadwalan dipulihkan dan migrasi dicatat ulang.
- `teacher_id`/`second_teacher_id` pada empat tabel jadwal disamakan menjadi `BIGINT UNSIGNED` dan tujuh foreign key diarahkan ke `teachers`.
- `unit_id` ditambahkan ke versi jadwal agar isolasi SMP/SMA dapat ditegakkan.
- Controller editor, generator, konflik, import, laporan, workflow, dan kandidat memvalidasi cakupan unit.
- Filter autentikasi, akses unit, dan wajib ganti password kini mencakup seluruh route jadwal.

### UI/UX

- Semua view Penugasan, Tugas Tambahan, Kebijakan Beban, dan Jadwal memakai layout aktif `layouts/admin` dan section `main_content`.
- Halaman jadwal memakai Bootstrap 5, pilihan data saling memfilter, serta menampilkan prasyarat yang belum lengkap.
- Hari, slot, dan kebutuhan mengajar disiapkan otomatis ketika versi jadwal dibuat.
- Editor menampilkan kisi aktual per kelas, status kandidat, audit konflik, dan dialog modern SweetAlert2.
- Dashboard menampilkan kesiapan sampai struktur kurikulum, pembagian tugas, dan jadwal.

### Integrasi keluaran

- SK pembagian tugas kolektif dibuat dari versi penugasan, unit, periode, guru, mapel, kelas, JP, dan tugas tambahan.
- Surat tugas individual tersedia per guru.
- Profil unit menyimpan kepala sekolah, identitas, kota penetapan, dan prefix nomor SK.
- Jadwal kelas dan guru tersedia dalam format cetak A4/Save as PDF.

### Generator

- Kebutuhan disinkronkan dari `teaching_assignments` memakai kelas dan guru master yang benar.
- Penempatan diurutkan per JP lalu hari agar tidak menumpuk di Senin.
- Beban dibatasi dan disebarkan lintas hari.
- Entri terkunci dimasukkan ke peta okupansi dan tidak ditimpa.
- Generator menolak proses dengan pesan jelas bila slot atau kebutuhan belum tersedia.

## Bukti Verifikasi

- PHP 8.2 lint: seluruh berkas PHP perubahan lolos.
- Composer manifest: valid.
- Composer audit: tidak ada advisory kerentanan pada dependency terkunci.
- Database aplikasi: 68 tabel, 12 migrasi, tidak ada foreign key lintas schema.
- Permission jadwal: terpasang untuk `super_admin`, `wakasek_kurikulum`, `admin_smp`, `admin_sma`, `kepala_sekolah`, `tata_usaha`, `guru`, dan `viewer_yayasan` sesuai kebutuhan role.
- Matriks izin dibangun ulang secara idempoten: 78 permission dan 333 pasangan role-permission tanpa truncate lintas modul.
- Verifikasi bersih setelah perbaikan: 45 test kritis lulus dengan 181 assertions (import/template, seeder, route-security, profil kurikulum, matriks penugasan, beban kerja, generator, dan import/ekspor jadwal).
- Audit suite yang lebih luas sebelumnya menemukan dua kegagalan kontrak identitas guru dan fixture akses unit; keduanya diperbaiki. Tiga skenario planning lama masih berstatus skipped karena fixture tidak menyediakan struktur target.
- Full suite monolitik tetap aktif tanpa deadlock tetapi melewati batas eksekusi 20 menit. Penyebab yang teramati adalah `RefreshDatabase` membangun ulang seluruh skema berulang kali pada banyak kelas test. Ini dicatat sebagai utang performa CI; hasilnya tidak dihitung sebagai kelulusan.

## Gate yang Masih Wajib Sebelum Go-Live Publik

Istilah “100%” tidak boleh dinyatakan hanya berdasarkan test lokal. Sebelum dibuka ke internet, lakukan:

1. Isi data nyata melalui template dan lakukan UAT oleh Wakasek Kurikulum.
2. Optimalkan fixture/migrasi test, lalu jalankan seluruh 190 test sampai selesai pada CI dengan ekstensi coverage opsional dinonaktifkan atau dipasang.
3. Uji restore dari backup pada mesin terpisah.
4. Ubah `CI_ENVIRONMENT` menjadi `production`, gunakan HTTPS, aktifkan secure cookie, dan simpan secret di environment server.
5. Uji beban dengan volume guru, rombel, mapel, dan aturan jadwal nyata.
6. Verifikasi redaksi serta nomor SK oleh kepala sekolah sebelum dokumen ditandatangani.

## Keterbatasan yang Disengaja

- Generator bersifat heuristik *best effort*, bukan solver optimum global.
- PDF dihasilkan melalui dialog cetak browser; engine PDF server-side belum dipasang.
- WhatsApp/SMS, iCal/Google Calendar, jadwal piket, dan guru pengganti belum tersedia.

Dokumen ini menggantikan klaim lama yang menyamakan kelulusan subset 37 test dengan kesiapan produksi penuh.
