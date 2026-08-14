\# Konsep Dasar Modul Pemilihan Mata Pelajaran Pilihan



\## WMVAA Academia — CodeIgniter 4 + MySQL



Modul ini sebaiknya tidak hanya menjadi formulir pemilihan mapel, tetapi menjadi satu rangkaian proses:



> \*\*Sekolah menyiapkan pilihan → siswa memilih → sistem memvalidasi → sekolah meninjau → sistem membentuk kelompok belajar → sistem menyusun blok jadwal → sekolah mengesahkan.\*\*



Karena jumlah siswa SMA Advent Sogokmo relatif kecil:



\* Kelas XI: 14 siswa

\* Kelas XII: 15 siswa



maka sistem sebaiknya dirancang fleksibel untuk sekolah kecil, tetapi tetap dapat digunakan ketika jumlah siswa bertambah.



\---



\# 1. Tujuan utama modul



Modul ini harus membantu sekolah mengelola:



1\. daftar mata pelajaran pilihan yang tersedia;

2\. kapasitas setiap mata pelajaran;

3\. guru pengampu;

4\. pilihan utama dan pilihan cadangan siswa;

5\. rekomendasi guru BK;

6\. persetujuan orang tua;

7\. validasi jumlah pilihan siswa;

8\. pembentukan kelompok belajar;

9\. penyusunan blok jadwal;

10\. deteksi benturan pilihan;

11\. penerbitan jadwal siswa;

12\. perubahan pilihan paling lambat kelas XI semester 2;

13\. kelanjutan pilihan dari kelas XI ke kelas XII.



\---



\# 2. Aktor dalam sistem



\## A. Administrator



Mengelola:



\* tahun pelajaran;

\* semester;

\* data siswa;

\* data guru;

\* data mata pelajaran;

\* hak akses;

\* pengaturan umum sistem.



\## B. Wakil Kepala Sekolah Bidang Kurikulum



Mengelola:



\* daftar mata pelajaran pilihan;

\* guru pengampu;

\* kapasitas kelas;

\* jumlah minimal peminat;

\* blok jadwal;

\* pembentukan kelompok;

\* hasil penempatan siswa;

\* pengesahan jadwal.



\## C. Guru BK



Mengelola:



\* profil minat siswa;

\* rekomendasi mata pelajaran;

\* catatan konseling;

\* persetujuan atau penolakan perubahan pilihan;

\* kesesuaian pilihan dengan rencana studi lanjut.



\## D. Wali Kelas



Melihat:



\* status pilihan siswa;

\* siswa yang belum memilih;

\* siswa yang memilih terlalu sedikit atau terlalu banyak;

\* siswa yang membutuhkan konsultasi;

\* hasil penempatan kelompok.



\## E. Siswa



Dapat:



\* melihat mapel yang disediakan sekolah;

\* membaca deskripsi setiap mapel;

\* memilih 4–5 mapel;

\* menentukan urutan prioritas;

\* memilih mapel cadangan;

\* melihat rekomendasi;

\* mengirim pilihan;

\* melihat status persetujuan;

\* melihat kelompok dan jadwal final;

\* mengajukan perubahan pilihan.



\## F. Orang Tua atau Wali



Opsional, tetapi sangat baik bila tersedia:



\* melihat pilihan anak;

\* menyetujui secara digital;

\* memberi catatan;

\* melihat jadwal final.



\## G. Kepala Sekolah



Melihat ringkasan dan mengesahkan:



\* daftar mapel yang dibuka;

\* hasil kelompok belajar;

\* keputusan final penempatan siswa;

\* perubahan pilihan.



\---



\# 3. Alur besar sistem



\## Tahap 1 — Persiapan sekolah



Kurikulum menentukan:



\* tahun pelajaran;

\* tingkat yang melakukan pemilihan;

\* periode pemilihan;

\* jumlah minimal mapel yang harus dipilih;

\* jumlah maksimal mapel;

\* jumlah pilihan cadangan;

\* daftar mapel yang tersedia;

\* guru pengampu;

\* kapasitas;

\* syarat tertentu;

\* jumlah minimal peminat;

\* hari dan jam yang tersedia.



Contoh konfigurasi:



| Pengaturan               |               Nilai |

| ------------------------ | ------------------: |

| Minimal pilihan utama    |                   4 |

| Maksimal pilihan utama   |                   5 |

| Pilihan cadangan         |                   2 |

| Minimal mapel disediakan |                   7 |

| Periode pemilihan        |       1–15 Mei 2027 |

| Tingkat sasaran          |  Kelas X naik ke XI |

| Batas perubahan          | Kelas XI Semester 2 |



\---



\## Tahap 2 — Publikasi mata pelajaran



Sekolah memilih mapel yang dibuka.



Contoh:



\* Biologi;

\* Fisika;

\* Kimia;

\* Matematika Tingkat Lanjut;

\* Ekonomi;

\* Geografi;

\* Sosiologi;

\* Informatika.



Setiap mapel memiliki informasi:



\* nama;

\* kode;

\* deskripsi;

\* tujuan;

\* bidang studi lanjut yang relevan;

\* guru pengampu;

\* kapasitas maksimal;

\* jumlah minimal peminat;

\* jumlah JP;

\* ruang atau laboratorium;

\* prasyarat;

\* status tersedia.



\---



\## Tahap 3 — Siswa memilih



Siswa login dan melihat kartu mata pelajaran.



Setiap kartu sebaiknya menampilkan:



\* nama mata pelajaran;

\* jumlah JP;

\* guru pengampu;

\* kuota;

\* jumlah peminat sementara;

\* relevansi terhadap kuliah atau pekerjaan;

\* rekomendasi BK;

\* tombol pilih.



Siswa kemudian mengisi:



\### Pilihan utama



Minimal 4 dan maksimal 5 mapel.



\### Pilihan cadangan



Misalnya 2 mapel.



\### Informasi tambahan



\* cita-cita;

\* program studi yang diminati;

\* rencana setelah lulus;

\* alasan pemilihan;

\* persetujuan bahwa pilihan telah dipahami.



Contoh:



| Urutan     | Pilihan                       |

| ---------- | ----------------------------- |

| Utama 1    | Biologi                       |

| Utama 2    | Kimia                         |

| Utama 3    | Matematika Tingkat Lanjut     |

| Utama 4    | Ekonomi                       |

| Utama 5    | Bahasa Inggris Tingkat Lanjut |

| Cadangan 1 | Informatika                   |

| Cadangan 2 | Sosiologi                     |



Urutan sangat penting karena dipakai sistem ketika tidak semua pilihan dapat dipenuhi.



\---



\# 4. Status proses pemilihan



Gunakan status yang jelas:



```text

draft

submitted

waiting\_parent\_approval

waiting\_bk\_review

needs\_revision

approved

scheduled

finalized

change\_requested

changed

rejected

```



Alur yang disarankan:



```text

Draft

&#x20; ↓

Dikirim Siswa

&#x20; ↓

Persetujuan Orang Tua

&#x20; ↓

Review Guru BK

&#x20; ↓

Validasi Kurikulum

&#x20; ↓

Pembentukan Kelompok

&#x20; ↓

Penyusunan Jadwal

&#x20; ↓

Disahkan

```



Untuk sekolah yang belum menggunakan akun orang tua, tahap persetujuan orang tua dapat diganti dengan:



\* unggah surat persetujuan;

\* kode OTP;

\* tanda tangan manual;

\* persetujuan wali kelas.



\---



\# 5. Struktur menu aplikasi



\## Menu Kurikulum



```text

Kurikulum

├── Pengaturan Pemilihan Mapel

├── Mata Pelajaran Tersedia

├── Guru Pengampu

├── Periode Pemilihan

├── Rekap Pilihan Siswa

├── Analisis Peminat

├── Pembentukan Kelompok

├── Penyusunan Blok Jadwal

├── Validasi Benturan

├── Jadwal Final

└── Perubahan Pilihan

```



\## Menu Siswa



```text

Akademik

├── Profil Minat

├── Pilih Mata Pelajaran

├── Rekomendasi BK

├── Status Pilihan

├── Kelompok Belajar

├── Jadwal Saya

└── Ajukan Perubahan

```



\## Menu Guru BK



```text

Bimbingan Konseling

├── Data Minat Siswa

├── Pilihan Siswa

├── Rekomendasi

├── Siswa Bermasalah

├── Review Perubahan

└── Laporan

```



\---



\# 6. Desain basis data



Berikut struktur dasar yang cukup kuat untuk dikembangkan.



\## 6.1 Tabel tahun pelajaran



```sql

academic\_years

\- id

\- name

\- start\_date

\- end\_date

\- is\_active

\- created\_at

\- updated\_at

```



Contoh:



```text

2026/2027

2027/2028

```



\---



\## 6.2 Tabel semester



```sql

semesters

\- id

\- academic\_year\_id

\- semester\_number

\- name

\- start\_date

\- end\_date

\- is\_active

```



\---



\## 6.3 Tabel mata pelajaran



```sql

subjects

\- id

\- code

\- name

\- short\_name

\- category

\- phase

\- grade\_scope

\- weekly\_hours

\- annual\_hours

\- is\_active

\- created\_at

\- updated\_at

```



Nilai `category`:



```text

required

elective

local\_content

adventist

```



Contoh:



```text

BIO      Biologi

FIS      Fisika

KIM      Kimia

MAT-TL   Matematika Tingkat Lanjut

EKO      Ekonomi

GEO      Geografi

SOS      Sosiologi

INF      Informatika

```



\---



\## 6.4 Tabel periode pemilihan



```sql

elective\_periods

\- id

\- academic\_year\_id

\- source\_grade

\- target\_grade

\- title

\- start\_at

\- end\_at

\- min\_primary\_choices

\- max\_primary\_choices

\- max\_backup\_choices

\- minimum\_subjects\_offered

\- allow\_changes

\- change\_deadline

\- status

\- created\_by

\- created\_at

\- updated\_at

```



Status:



```text

draft

published

selection\_open

processing

finalized

closed

```



\---



\## 6.5 Tabel mapel yang disediakan sekolah



Tabel ini berbeda dari master `subjects`.



```sql

elective\_offerings

\- id

\- elective\_period\_id

\- subject\_id

\- teacher\_id

\- minimum\_students

\- maximum\_students

\- weekly\_hours

\- room\_id

\- priority\_level

\- is\_open

\- notes

\- created\_at

\- updated\_at

```



Contoh:



| Mapel   | Minimum | Maksimum |

| ------- | ------: | -------: |

| Biologi |       3 |       20 |

| Fisika  |       3 |       20 |

| Kimia   |       3 |       20 |

| Ekonomi |       3 |       25 |



\---



\## 6.6 Tabel pilihan siswa



```sql

student\_elective\_submissions

\- id

\- elective\_period\_id

\- student\_id

\- career\_plan

\- intended\_major

\- selection\_reason

\- status

\- submitted\_at

\- parent\_approved\_at

\- bk\_reviewed\_at

\- curriculum\_approved\_at

\- finalized\_at

\- created\_at

\- updated\_at

```



\---



\## 6.7 Detail mata pelajaran yang dipilih



```sql

student\_elective\_choices

\- id

\- submission\_id

\- offering\_id

\- choice\_type

\- priority\_order

\- is\_recommended

\- allocation\_status

\- created\_at

\- updated\_at

```



`choice\_type`:



```text

primary

backup

```



`allocation\_status`:



```text

pending

allocated

waitlisted

rejected

replaced

```



\---



\## 6.8 Rekomendasi BK



```sql

bk\_elective\_recommendations

\- id

\- student\_id

\- elective\_period\_id

\- recommended\_subject\_id

\- recommendation\_level

\- notes

\- created\_by

\- created\_at

```



`recommendation\_level`:



```text

strongly\_recommended

recommended

neutral

not\_recommended

```



\---



\## 6.9 Persetujuan orang tua



```sql

parent\_elective\_approvals

\- id

\- submission\_id

\- parent\_user\_id

\- approval\_status

\- notes

\- approved\_at

\- signature\_file

\- created\_at

```



\---



\## 6.10 Kelompok belajar mapel pilihan



```sql

elective\_groups

\- id

\- elective\_period\_id

\- offering\_id

\- group\_code

\- group\_name

\- grade\_level

\- teacher\_id

\- room\_id

\- capacity

\- status

\- created\_at

\- updated\_at

```



Contoh:



```text

BIO-XI-A

EKO-XI-A

MATTL-XI-A

BIO-XII-A

```



\---



\## 6.11 Anggota kelompok



```sql

elective\_group\_members

\- id

\- group\_id

\- student\_id

\- source\_choice\_id

\- allocation\_method

\- allocation\_score

\- is\_locked

\- created\_at

```



`allocation\_method`:



```text

automatic

manual

override

```



\---



\## 6.12 Blok jadwal



```sql

schedule\_blocks

\- id

\- academic\_year\_id

\- semester\_id

\- block\_code

\- block\_name

\- day\_of\_week

\- start\_time

\- end\_time

\- sequence\_number

\- grade\_level

\- created\_at

```



Contoh:



```text

BLOK-A

Senin

08:00–09:30

```



\---



\## 6.13 Penempatan mapel pada blok



```sql

elective\_group\_schedules

\- id

\- group\_id

\- schedule\_block\_id

\- teacher\_id

\- room\_id

\- created\_at

```



\---



\## 6.14 Konflik jadwal



```sql

schedule\_conflicts

\- id

\- elective\_period\_id

\- conflict\_type

\- reference\_type

\- reference\_id

\- description

\- severity

\- resolution\_status

\- resolved\_by

\- resolved\_at

\- created\_at

```



Jenis konflik:



```text

student\_overlap

teacher\_overlap

room\_overlap

capacity\_exceeded

minimum\_not\_reached

missing\_teacher

insufficient\_blocks

```



\---



\# 7. Logika validasi pemilihan siswa



Saat siswa menekan tombol \*\*Kirim Pilihan\*\*, sistem harus memeriksa:



\## Validasi dasar



\* siswa termasuk peserta periode;

\* periode masih dibuka;

\* siswa belum memiliki pilihan final;

\* jumlah pilihan utama minimal 4;

\* jumlah pilihan utama maksimal 5;

\* jumlah pilihan cadangan tidak lebih dari ketentuan;

\* tidak ada mapel yang dipilih dua kali;

\* mapel masih aktif;

\* mapel benar-benar disediakan sekolah.



Contoh validasi CI4:



```php

$primaryCount = count($primaryChoices);

$backupCount  = count($backupChoices);



if ($primaryCount < 4 || $primaryCount > 5) {

&#x20;   return redirect()->back()

&#x20;       ->withInput()

&#x20;       ->with('error', 'Pilih 4 sampai 5 mata pelajaran utama.');

}



if ($backupCount > 2) {

&#x20;   return redirect()->back()

&#x20;       ->withInput()

&#x20;       ->with('error', 'Pilihan cadangan maksimal 2 mata pelajaran.');

}



$allChoices = array\_merge($primaryChoices, $backupChoices);



if (count($allChoices) !== count(array\_unique($allChoices))) {

&#x20;   return redirect()->back()

&#x20;       ->withInput()

&#x20;       ->with('error', 'Mata pelajaran yang sama tidak boleh dipilih dua kali.');

}

```



\---



\# 8. Sistem pembentukan kelompok otomatis



Setelah semua siswa mengirim pilihan, sistem menghitung jumlah peminat setiap mapel.



Contoh:



| Mapel         | Peminat utama | Cadangan |

| ------------- | ------------: | -------: |

| Biologi       |             7 |        2 |

| Fisika        |             4 |        3 |

| Kimia         |             7 |        1 |

| Matematika TL |             8 |        2 |

| Ekonomi       |            10 |        1 |

| Geografi      |             6 |        3 |

| Sosiologi     |             7 |        2 |

| Informatika   |             8 |        1 |



Sistem kemudian menentukan status:



```text

LAYAK DIBUKA

PERLU REVIEW

KUOTA PENUH

TIDAK MEMENUHI MINIMUM

TIDAK ADA GURU

```



\## Aturan dasar



```text

Jika peminat utama >= minimum\_students

&#x20;   → kelompok dapat dibuat



Jika peminat utama > maximum\_students

&#x20;   → buat kelompok tambahan atau gunakan prioritas



Jika peminat utama < minimum\_students

&#x20;   → tawarkan pilihan cadangan



Jika guru tidak tersedia

&#x20;   → kelompok tidak dapat diproses otomatis

```



\---



\# 9. Skor prioritas penempatan siswa



Ketika kuota terbatas, sistem perlu menentukan siapa yang mendapat tempat.



Gunakan skor transparan.



Contoh:



| Komponen                         | Bobot |

| -------------------------------- | ----: |

| Pilihan prioritas pertama        |    50 |

| Pilihan prioritas kedua          |    40 |

| Pilihan prioritas ketiga         |    30 |

| Pilihan prioritas keempat        |    20 |

| Pilihan prioritas kelima         |    10 |

| Direkomendasikan BK              |   +15 |

| Relevan dengan rencana studi     |   +10 |

| Nilai prasyarat memenuhi         |   +10 |

| Siswa kelas XII yang melanjutkan |   +30 |



Rumus sederhana:



```text

allocation\_score =

priority\_score

\+ bk\_score

\+ academic\_score

\+ continuation\_score

```



Namun untuk sekolah Anda, dengan jumlah siswa kecil, sistem sebaiknya lebih mengutamakan \*\*pemenuhan pilihan siswa\*\*, bukan kompetisi kuota.



\---



\# 10. Cara sistem menyusun blok jadwal otomatis



Ini bagian paling penting.



Sistem tidak boleh langsung menempatkan mapel ke blok secara acak. Sistem harus membaca kombinasi pilihan siswa.



\## Prinsip utama



Dua mata pelajaran yang sering dipilih bersama harus ditempatkan pada blok yang berbeda.



Misalnya:



\* 8 siswa memilih Biologi dan Kimia;

\* 6 siswa memilih Ekonomi dan Sosiologi;

\* 7 siswa memilih Matematika TL dan Informatika.



Maka pasangan tersebut tidak boleh berada dalam blok yang sama.



\---



\# 11. Matriks konflik mata pelajaran



Sistem membuat matriks berdasarkan pilihan siswa.



Contoh:



| Mapel  | BIO | FIS | KIM | MAT-TL | EKO | GEO | SOS | INF |

| ------ | --: | --: | --: | -----: | --: | --: | --: | --: |

| BIO    |   - |   2 |   7 |      5 |   4 |   2 |   3 |   3 |

| FIS    |   2 |   - |   4 |      4 |   2 |   1 |   0 |   3 |

| KIM    |   7 |   4 |   - |      6 |   3 |   1 |   1 |   4 |

| MAT-TL |   5 |   4 |   6 |      - |   4 |   2 |   2 |   6 |

| EKO    |   4 |   2 |   3 |      4 |   - |   6 |   8 |   5 |

| GEO    |   2 |   1 |   1 |      2 |   6 |   - |   6 |   4 |

| SOS    |   3 |   0 |   1 |      2 |   8 |   6 |   - |   5 |

| INF    |   3 |   3 |   4 |      6 |   5 |   4 |   5 |   - |



Angka menunjukkan jumlah siswa yang mengambil kedua mapel.



Semakin besar angkanya, semakin tidak boleh dua mapel ditempatkan dalam blok yang sama.



\---



\# 12. Algoritma penempatan blok



Untuk tahap awal, jangan langsung menggunakan algoritma AI yang rumit. Gunakan algoritma heuristik yang mudah dikontrol.



\## Tahapan



\### Langkah 1



Urutkan mapel berdasarkan jumlah peminat tertinggi.



\### Langkah 2



Tempatkan mapel paling populer ke blok pertama.



\### Langkah 3



Untuk mapel berikutnya, cari blok dengan total konflik terkecil.



\### Langkah 4



Periksa:



\* benturan siswa;

\* benturan guru;

\* benturan ruang;

\* jumlah blok;

\* kapasitas kelas.



\### Langkah 5



Hitung nilai kualitas jadwal.



Contoh:



```text

schedule\_score =

(total pilihan siswa terpenuhi × 100)

\- (benturan siswa × 50)

\- (benturan guru × 100)

\- (benturan ruang × 80)

\- (pilihan utama gagal × 40)

```



Sistem menghasilkan beberapa alternatif:



```text

Alternatif A: 96% pilihan terpenuhi

Alternatif B: 93% pilihan terpenuhi

Alternatif C: 91% pilihan terpenuhi

```



Kurikulum memilih alternatif terbaik atau melakukan penyesuaian manual.



\---



\# 13. Contoh hasil blok otomatis



Misalnya sistem menghasilkan:



| Blok | Kelompok yang berjalan bersamaan |

| ---- | -------------------------------- |

| A    | Biologi / Fisika                 |

| B    | Kimia / Ekonomi                  |

| C    | Matematika TL / Geografi         |

| D    | Sosiologi / Informatika          |



Namun sistem harus memeriksa apakah ada siswa yang memilih:



\* Biologi dan Fisika;

\* Kimia dan Ekonomi;

\* Matematika TL dan Geografi;

\* Sosiologi dan Informatika.



Jika banyak siswa memilih dua mapel dalam satu blok, susunan tersebut buruk.



Sistem kemudian mencoba alternatif lain:



| Blok | Kelompok                |

| ---- | ----------------------- |

| A    | Biologi / Sosiologi     |

| B    | Kimia / Geografi        |

| C    | Matematika TL / Ekonomi |

| D    | Fisika / Informatika    |



Alternatif terbaik adalah yang menghasilkan benturan paling sedikit.



\---



\# 14. Penjadwalan kelas XI dan XII



Karena XI dan XII berbeda tingkat, data jadwal harus tetap dipisahkan.



Tetapi sistem dapat menyediakan opsi:



```text

\[ ] Izinkan kelompok lintas tingkat

```



Pilihan ini hanya digunakan jika:



\* mapel sama;

\* peminat sedikit;

\* guru sama;

\* ruang sama;

\* sekolah menyetujui kelas multitingkat.



Jika diaktifkan, sistem dapat membentuk:



```text

BIO-F-01

Anggota:

\- XI: 4 siswa

\- XII: 5 siswa

```



Namun progres pembelajaran tetap disimpan per tingkat:



```sql

elective\_group\_learning\_tracks

\- id

\- group\_id

\- grade\_level

\- teaching\_module\_id

\- learning\_progress

```



Saya menyarankan pada versi awal, kelompok XI dan XII tetap dipisahkan. Fitur kelas gabungan dapat menjadi fitur lanjutan.



\---



\# 15. Mode otomatis dan manual



Sistem harus menyediakan tiga mode.



\## A. Otomatis penuh



Sistem:



\* menghitung peminat;

\* membuat kelompok;

\* menempatkan siswa;

\* menyusun blok;

\* memeriksa benturan;

\* menghasilkan jadwal.



Cocok ketika data sudah lengkap.



\## B. Semi otomatis



Sistem membuat rekomendasi, lalu kurikulum dapat:



\* memindahkan siswa;

\* menukar blok;

\* mengganti guru;

\* mengganti ruang;

\* membuka atau menutup kelompok.



Ini adalah mode yang paling saya rekomendasikan.



\## C. Manual



Sekolah mengatur seluruhnya secara manual, tetapi sistem tetap memvalidasi konflik.



\---



\# 16. Tampilan dashboard kurikulum



Dashboard perlu menampilkan kartu ringkasan:



```text

Peserta Pemilihan       14

Sudah Memilih           12

Belum Memilih            2

Sudah Disetujui BK      10

Perlu Revisi             2

Mapel Tersedia           8

Kelompok Layak           8

Konflik Jadwal           3

```



Tambahkan grafik:



\* jumlah peminat per mapel;

\* pilihan pertama terbanyak;

\* mapel di bawah batas minimum;

\* siswa dengan kombinasi konflik;

\* tingkat pemenuhan pilihan.



\---



\# 17. Tampilan formulir siswa



Gunakan desain kartu, bukan tabel yang terlalu padat.



Contoh:



```text

┌──────────────────────────────────┐

│ BIOLOGI                          │

│ 5 JP/Minggu                      │

│ Guru: Ibu ...                    │

│ Peminat: 7 dari 20               │

│ Cocok untuk: Kesehatan, Sains    │

│                                  │

│ \[Pilih] \[Lihat Detail]           │

└──────────────────────────────────┘

```



Setelah dipilih:



```text

Pilihan Saya



1\. Biologi                       ↕

2\. Kimia                         ↕

3\. Matematika Tingkat Lanjut     ↕

4\. Ekonomi                       ↕

5\. Informatika                   ↕



Cadangan:

1\. Sosiologi

2\. Geografi

```



Gunakan drag-and-drop untuk mengurutkan prioritas.



\---



\# 18. Notifikasi sistem



Sistem dapat mengirim notifikasi:



\## Kepada siswa



\* periode pemilihan dibuka;

\* belum memilih;

\* pilihan perlu direvisi;

\* pilihan disetujui;

\* salah satu mapel tidak dapat dibuka;

\* kelompok telah ditetapkan;

\* jadwal telah tersedia.



\## Kepada guru BK



\* ada siswa yang mengirim pilihan;

\* ada pilihan tidak sesuai rekomendasi;

\* ada permohonan perubahan;

\* ada siswa belum memiliki rencana studi.



\## Kepada kurikulum



\* mapel di bawah minimum;

\* guru bentrok;

\* ruang bentrok;

\* jadwal belum final;

\* pilihan siswa belum seluruhnya terpenuhi.



\---



\# 19. Perubahan mata pelajaran



Siswa tidak boleh mengedit pilihan final secara langsung.



Gunakan modul permohonan perubahan:



```sql

elective\_change\_requests

\- id

\- student\_id

\- academic\_year\_id

\- current\_offering\_id

\- requested\_offering\_id

\- reason

\- status

\- requested\_at

\- bk\_decision

\- curriculum\_decision

\- parent\_approval

\- approved\_at

```



Alur:



```text

Siswa Mengajukan

&#x20; ↓

Orang Tua Menyetujui

&#x20; ↓

BK Menilai

&#x20; ↓

Kurikulum Memeriksa Kuota dan Jadwal

&#x20; ↓

Disetujui atau Ditolak

```



Validasi:



\* belum melewati batas perubahan;

\* mapel baru memiliki kapasitas;

\* jadwal tidak bentrok;

\* siswa mampu mengejar materi;

\* perubahan tidak mengurangi pilihan di bawah empat.



\---



\# 20. Struktur CodeIgniter 4



Gunakan struktur modular.



```text

app/

├── Controllers/

│   └── Electives/

│       ├── SettingsController.php

│       ├── OfferingsController.php

│       ├── StudentSelectionController.php

│       ├── ReviewController.php

│       ├── AllocationController.php

│       ├── SchedulingController.php

│       └── ChangeRequestController.php

│

├── Models/

│   ├── ElectivePeriodModel.php

│   ├── ElectiveOfferingModel.php

│   ├── StudentElectiveSubmissionModel.php

│   ├── StudentElectiveChoiceModel.php

│   ├── ElectiveGroupModel.php

│   ├── ElectiveGroupMemberModel.php

│   ├── ScheduleBlockModel.php

│   └── ElectiveChangeRequestModel.php

│

├── Services/

│   └── Electives/

│       ├── SelectionValidationService.php

│       ├── DemandAnalysisService.php

│       ├── GroupAllocationService.php

│       ├── ConflictMatrixService.php

│       ├── ScheduleGeneratorService.php

│       └── ElectiveFinalizationService.php

│

├── Entities/

│   ├── ElectivePeriod.php

│   ├── ElectiveOffering.php

│   └── StudentElectiveSubmission.php

│

├── Database/

│   ├── Migrations/

│   └── Seeds/

│

└── Views/

&#x20;   └── electives/

```



Jangan menaruh seluruh logika di controller.



Controller hanya menangani:



\* request;

\* permission;

\* validasi dasar;

\* memanggil service;

\* mengembalikan response.



Algoritma pengelompokan dan jadwal ditempatkan di `Services`.



\---



\# 21. Contoh service pembentukan kelompok



```php

<?php



namespace App\\Services\\Electives;



use RuntimeException;



class GroupAllocationService

{

&#x20;   public function allocate(array $offerings, array $choices): array

&#x20;   {

&#x20;       $result = \[

&#x20;           'groups' => \[],

&#x20;           'unallocated' => \[],

&#x20;           'warnings' => \[],

&#x20;       ];



&#x20;       foreach ($offerings as $offering) {

&#x20;           $subjectChoices = array\_filter(

&#x20;               $choices,

&#x20;               static fn(array $choice): bool =>

&#x20;                   (int) $choice\['offering\_id'] === (int) $offering\['id']

&#x20;                   \&\& $choice\['choice\_type'] === 'primary'

&#x20;           );



&#x20;           usort(

&#x20;               $subjectChoices,

&#x20;               static fn(array $a, array $b): int =>

&#x20;                   $a\['priority\_order'] <=> $b\['priority\_order']

&#x20;           );



&#x20;           $count = count($subjectChoices);

&#x20;           $minimum = (int) $offering\['minimum\_students'];

&#x20;           $maximum = (int) $offering\['maximum\_students'];



&#x20;           if ($count < $minimum) {

&#x20;               $result\['warnings']\[] = \[

&#x20;                   'offering\_id' => $offering\['id'],

&#x20;                   'message' => 'Jumlah peminat belum mencapai batas minimum.',

&#x20;               ];



&#x20;               continue;

&#x20;           }



&#x20;           $allocated = array\_slice($subjectChoices, 0, $maximum);

&#x20;           $overflow  = array\_slice($subjectChoices, $maximum);



&#x20;           $result\['groups']\[] = \[

&#x20;               'offering\_id' => $offering\['id'],

&#x20;               'students' => $allocated,

&#x20;           ];



&#x20;           foreach ($overflow as $choice) {

&#x20;               $result\['unallocated']\[] = $choice;

&#x20;           }

&#x20;       }



&#x20;       return $result;

&#x20;   }

}

```



Pada versi lanjutan, urutan tidak hanya berdasarkan prioritas, tetapi juga skor rekomendasi BK dan relevansi rencana studi.



\---



\# 22. API endpoint yang diperlukan



\## Untuk sekolah



```text

GET    /api/elective-periods

POST   /api/elective-periods

PUT    /api/elective-periods/{id}



GET    /api/elective-offerings

POST   /api/elective-offerings

PUT    /api/elective-offerings/{id}



GET    /api/elective-analysis/{periodId}

POST   /api/elective-allocation/generate

POST   /api/elective-schedule/generate

POST   /api/elective-schedule/finalize

```



\## Untuk siswa



```text

GET    /api/student/electives/available

GET    /api/student/electives/my-selection

POST   /api/student/electives/save-draft

POST   /api/student/electives/submit

GET    /api/student/electives/status

POST   /api/student/electives/change-request

```



\## Untuk BK



```text

GET    /api/bk/elective-reviews

POST   /api/bk/elective-reviews/{studentId}

GET    /api/bk/elective-risk-list

```



\---



\# 23. Hak akses



Gunakan permission, bukan hanya role.



Contoh:



```text

electives.settings.manage

electives.offerings.manage

electives.selection.submit

electives.selection.review

electives.selection.approve

electives.groups.generate

electives.groups.override

electives.schedule.generate

electives.schedule.finalize

electives.change.request

electives.change.approve

```



Dengan demikian, wakil kurikulum dan kepala sekolah dapat memiliki izin berbeda walaupun sama-sama admin.



\---



\# 24. Tahapan pengembangan



\## Fase 1 — MVP



Fokus pada:



\* pengaturan periode;

\* daftar mapel tersedia;

\* form pilihan siswa;

\* pilihan utama dan cadangan;

\* rekap peminat;

\* persetujuan BK;

\* pembentukan kelompok sederhana;

\* jadwal manual;

\* laporan.



\## Fase 2 — Semi otomatis



Tambahkan:



\* matriks konflik;

\* rekomendasi blok jadwal;

\* penempatan siswa otomatis;

\* deteksi benturan;

\* alternatif jadwal;

\* perubahan pilihan;

\* persetujuan orang tua.



\## Fase 3 — Otomatisasi lanjutan



Tambahkan:



\* optimasi jadwal;

\* skor pemenuhan pilihan;

\* simulasi beberapa skenario;

\* pembentukan kelas lintas rombel;

\* prediksi kebutuhan guru;

\* rekomendasi berdasarkan rencana studi lanjut;

\* integrasi rapor dan absensi.



\---



\# 25. Konsep keputusan otomatis yang aman



Sistem tidak boleh langsung mengesahkan keputusan tanpa kontrol sekolah.



Gunakan alur:



```text

Generate

&#x20; ↓

Preview

&#x20; ↓

Review

&#x20; ↓

Manual Adjustment

&#x20; ↓

Validate Again

&#x20; ↓

Finalize

```



Tombol yang tersedia:



```text

\[Analisis Pilihan]

\[Bangun Kelompok Otomatis]

\[Susun Blok Jadwal]

\[Cek Benturan]

\[Simpan Sebagai Draf]

\[Sahkan]

```



Sebelum disahkan, tampilkan:



```text

Pilihan utama terpenuhi       93%

Siswa tanpa jadwal bentrok    14/14

Guru tanpa bentrok             8/8

Ruang tanpa bentrok            8/8

Mapel di bawah minimum         1

Siswa menggunakan cadangan     2

```



\---



\# 26. Konsep final yang saya rekomendasikan



Untuk WMVAA Academia, modul ini sebaiknya memakai prinsip:



> \*\*Pilihan siswa menjadi dasar, sumber daya sekolah menjadi batasan, dan sistem bertugas mencari susunan kelompok serta jadwal dengan konflik paling kecil.\*\*



Arsitektur utamanya:



```text

Master Mata Pelajaran

&#x20;       ↓

Periode Pemilihan

&#x20;       ↓

Mapel yang Disediakan Sekolah

&#x20;       ↓

Pilihan Siswa

&#x20;       ↓

Review Orang Tua dan BK

&#x20;       ↓

Analisis Peminat

&#x20;       ↓

Pembentukan Kelompok

&#x20;       ↓

Matriks Konflik

&#x20;       ↓

Penyusunan Blok Jadwal

&#x20;       ↓

Validasi Guru, Siswa, Ruang

&#x20;       ↓

Pengesahan

&#x20;       ↓

Integrasi Jadwal, Absensi, dan Rapor

```



Untuk jumlah 14 siswa kelas XI dan 15 siswa kelas XII, gunakan \*\*mode semi otomatis\*\*. Sistem membuat rekomendasi kelompok dan jadwal, tetapi kurikulum tetap memiliki kendali akhir. Ini jauh lebih aman daripada otomatis penuh, karena kondisi nyata seperti guru merangkap, kegiatan sekolah Advent, ibadah chapel, work education, dan keterbatasan ruang sering tidak dapat diputuskan hanya dari angka.
