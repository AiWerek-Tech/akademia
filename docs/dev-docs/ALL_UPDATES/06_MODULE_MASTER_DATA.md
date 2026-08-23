# WMVAA Akademia — Master Data Modules

## 1. Overview

Master Data merupakan fondasi semua operasi akademik. Data diinisialisasi sebelum menyusun penugasan, jadwal, dan kurikulum.

---

## 2. School Units (`/settings/units`)

### 2.1 Deskripsi

Unit sekolah (SMP, SMA) merupakan pembagian data utama. Semua data operasional dipisahkan berdasarkan `unit_id`.

### 2.2 Fields

| Field | Tipe | Keterangan |
|---|---|---|
| `code` | VARCHAR(20) | Kode unit (SMP, SMA) |
| `name` | VARCHAR(100) | Nama unit |
| `is_active` | TINYINT(1) | Status aktif |

### 2.3 Fitur

- **CRUD**: Create, Read, Update unit
- **Multi-Unit**: Mendukung beberapa unit dalam satu instalasi
- **User Access**: Setiap user di-link ke unit melalui `user_unit_access`
- **Context Switching**: User dapat switch antar unit

---

## 3. Academic Years & Periods (`/academic-years`, `/academic-periods`)

### 3.1 Academic Years

| Field | Tipe | Keterangan |
|---|---|---|
| `name` | VARCHAR(50) | Contoh: "2026/2027" |
| `start_date` | DATE | Tanggal mulai |
| `end_date` | DATE | Tanggal akhir |
| `status` | VARCHAR(20) | draft/active/archived |

**Fitur**:
- CRUD Tahun Akademik
- Activate: Mengaktifkan tahun akademik

### 3.2 Academic Periods

| Field | Tipe | Keterangan |
|---|---|---|
| `academic_year_id` | BIGINT FK | Tahun akademik |
| `semester_number` | TINYINT | 1 (Ganjil) / 2 (Genap) |
| `name` | VARCHAR(100) | Nama periode |
| `workflow_status` | VARCHAR(50) | Status workflow |
| `is_active` | TINYINT(1) | Status aktif |

**Fitur**:
- CRUD Periode Akademik
- Activate: Mengaktifkan periode (menonaktifkan lainnya)
- Workflow: draft → active

### 3.3 Fungsi

Periode akademik menjadi scope untuk:
- Kurikulum versi
- Penugasan mengajar
- Jadwal pelajaran
- Absensi
- Rapor

---

## 4. Teachers Master (`/teachers`)

### 4.1 Deskripsi

Master guru bersifat **global** (lintas unit). Satu guru dapat ditugaskan di beberapa unit.

### 4.2 Fields

| Field | Tipe | Keterangan |
|---|---|---|
| `nip` | VARCHAR(30) UNIQUE | Nomor Induk Pegawai |
| `nik` | VARCHAR(20) | Nomor Induk Kependudukan |
| `full_name` | VARCHAR(100) | Nama lengkap |
| `email` | VARCHAR(100) | Email |
| `phone` | VARCHAR(20) | Nomor telepon |
| `employment_status` | VARCHAR(30) | Status kepegawaian |
| `is_active` | TINYINT(1) | Status aktif |

### 4.3 Fitur

| Aksi | Deskripsi |
|---|---|
| **Create** | Form tambah guru baru |
| **Read** | Daftar guru dengan filtering & search |
| **Detail** | Profil lengkap guru |
| **Update** | Edit data guru |
| **Export** | Export ke Excel |
| **Verify** | Verifikasi data guru |
| **Duplicate Detection** | Review guru duplikat (`/duplicates`) |

### 4.4 Related Tables

- `teacher_identifiers` — Identitas tambahan
- `teacher_qualifications` — Kualifikasi pendidikan
- `teacher_unit_assignments` — Penugasan per unit
- `teacher_availability_rules` — Aturan ketersediaan
- `teacher_additional_duties` — Tugas tambahan

### 4.5 Services

| Service | Fungsi |
|---|---|
| `TeacherService` | CRUD, query, business logic |
| `TeacherDuplicateDetectionService` | Deteksi duplikasi berdasarkan NIP/NIK/nama |
| `TeacherMergeService` | Merge data guru duplikat |
| `TeacherProfileCompletenessService` | Cek kelengkapan profil |
| `TeacherAccountProvisioningService` | Provisioning akun user dari data guru |

---

## 5. Subjects Master (`/subjects`)

### 5.1 Deskripsi

Mata pelajaran bersifat global (lintas unit).

### 5.2 Fields

| Field | Tipe | Keterangan |
|---|---|---|
| `code` | VARCHAR(20) UNIQUE | Kode mapel |
| `name` | VARCHAR(100) | Nama mapel |
| `category` | VARCHAR(50) | Kategori (umum, khusus, lokal) |
| `is_active` | TINYINT(1) | Status aktif |

### 5.3 Fitur

- CRUD Mata Pelajaran
- Export ke Excel
- Alias management (`subject_aliases`)

---

## 6. Grade Levels (`/grade-levels`)

### 6.1 Deskripsi

Tingkat kelas per unit. Contoh: VII, VIII, IX (SMP) atau X, XI, XII (SMA).

### 6.2 Fields

| Field | Tipe | Keterangan |
|---|---|---|
| `unit_id` | BIGINT FK | Unit sekolah |
| `code` | VARCHAR(20) | Kode (VII, VIII, IX, X, XI, XII) |
| `name` | VARCHAR(50) | Nama tingkat |
| `grade_number` | TINYINT | Nomor urut grade |

---

## 7. Classrooms / Rombel (`/classrooms`)

### 7.1 Deskripsi

Rombongan belajar (kelas) per periode akademik. Setiap rombel terikat ke periode, unit, dan tingkat.

### 7.2 Fields

| Field | Tipe | Keterangan |
|---|---|---|
| `academic_period_id` | BIGINT FK | Periode akademik |
| `unit_id` | BIGINT FK | Unit sekolah |
| `grade_level_id` | BIGINT FK | Tingkat kelas |
| `code` | VARCHAR(20) | Kode rombel (contoh: VII-A) |
| `name` | VARCHAR(50) | Nama rombel |

### 7.3 Fitur

| Aksi | Deskripsi |
|---|---|
| **Create** | Tambah rombel baru |
| **Read** | Daftar rombel per periode |
| **Edit** | Update data rombel |
| **Students** | Lihat daftar siswa di rombel |
| **Assign Student** | Tambah siswa ke rombel |
| **Remove Student** | Hapus siswa dari rombel |
| **Export** | Export daftar rombel |
| **Copy Period** | Salin rombel dari periode sebelumnya |
| **Promote** | Kenaikan kelas ke periode berikutnya |

### 7.4 Copy Period

Fitur untuk menyalin struktur rombel dari periode akademik sebelumnya ke periode baru.

### 7.5 Promote

Fitur untuk mempromosikan siswa ke tingkat berikutnya secara massal.

---

## 8. Rooms Master (`/rooms`)

### 8.1 Deskripsi

Ruang sekolah (kelas, lab, perpustakaan, dll).

### 8.2 Fields

| Field | Tipe | Keterangan |
|---|---|---|
| `unit_id` | BIGINT FK | Unit sekolah |
| `code` | VARCHAR(20) | Kode ruang |
| `name` | VARCHAR(50) | Nama ruang |
| `capacity` | INT | Kapasitas |
| `room_type_id` | BIGINT FK | Tipe ruang |

### 8.3 Fitur

- CRUD Ruang Sekolah
- Export ke Excel
- Room types management

---

## 9. Students Master (`/students`)

### 9.1 Deskripsi

Data peserta didik. Dikelola melalui **import staging pipeline** atau form manual.

### 9.2 Fitur

| Aksi | Deskripsi | Permission |
|---|---|---|
| **Read** | Daftar siswa | `students.view` |
| **Create** | Tambah siswa manual | `students.manage` |
| **Update** | Edit data siswa | `students.manage` |
| **Delete** | Hapus siswa | `students.manage` |

### 9.3 Related

- `student_attendances` — Kehadiran siswa
- `student_elective_submissions` — Pilihan mapel elektif

---

## 10. Education Foundation References

### 10.1 Regulations (`/references/regulations`)

Regulasi pendidikan yang menjadi dasar kurikulum.

| Fitur | Deskripsi |
|---|---|
| **CRUD** | Kelola regulasi |
| **Versioning** | Versi dokumen regulasi |
| **Category** | Kategorisasi regulasi |

### 10.2 Curriculum Sources (`/references/curriculum-sources`)

Sumber-sumber kurikulum yang digunakan.

### 10.3 Graduate Profile (`/references/graduate-profile`)

Profil lulusan dengan dimensi-dimensinya.

| Fitur | Deskripsi |
|---|---|
| **Dimensions** | Dimensi profil lulusan |
| **CRUD** | Kelola dimensi |

---

## 11. Routine Activities (`/routine-activities`)

### 11.1 Deskripsi

Kegiatan rutin sekolah yang menjadi fixed activity dalam penjadwalan.

### 11.2 Fields

| Field | Tipe | Keterangan |
|---|---|---|
| `name` | VARCHAR(100) | Nama kegiatan |
| `day_code` | VARCHAR(3) | Hari (MON-SUN) |
| `start_time` | TIME | Jam mulai |
| `end_time` | TIME | Jam selesai |
| `placement_zone` | ENUM | Zona penempatan (morning/afternoon) |
| `teaching_load` | DECIMAL | Beban mengajar terkait |
| `unit_id` | BIGINT FK | Unit |

### 11.3 Fitur

- CRUD Kegiatan Rutin
- Configure fixed activity slots
- Auto-sync ke jadwal

---

## 12. Duties & Duty Schedules

### 12.1 Additional Duty Types (`/duties`)

Tipe-tipe tugas tambahan guru (piket, bimbingan, lab, dll).

### 12.2 Duty Schedules (`/duty-schedules`)

Jadwal piket guru.

| Fitur | Deskripsi |
|---|---|
| **Generate** | Generate jadwal piket otomatis |
| **Store** | Simpan jadwal manual |
| **Clear** | Hapus semua jadwal |
| **Print** | Cetak jadwal piket |
| **Delete** | Hapus item jadwal |

---

## 13. Import Master Data (`/imports/master`)

### 13.1 Staging Pipeline

```
Download Template → Fill Excel → Upload → Validation → Preview → Apply
```

### 13.2 Supported Imports

| Type | Template | Target Table |
|---|---|---|
| Teachers | Excel template | `teachers` |
| Subjects | Excel template | `subjects` |
| Students | Excel template | `students` |
| Classrooms | Excel template | `classrooms` |

### 13.3 Workflow

1. **Download Template**: Unduh template Excel kosong
2. **Fill Data**: Isi data di Excel
3. **Upload**: Upload file Excel
4. **Validation**: Sistem memvalidasi data
5. **Preview**: Lihat data yang akan diimport
6. **Apply**: Terapkan data ke database

### 13.4 Tables

| Table | Fungsi |
|---|---|
| `master_import_batches` | Batch import |
| `master_import_rows` | Baris data per batch |
