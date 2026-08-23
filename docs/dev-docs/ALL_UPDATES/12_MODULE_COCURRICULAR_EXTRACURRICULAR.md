# WMVAA Akademia — Cocurricular & Extracurricular Module

## 1. Cocurricular Programs (`/cocurricular`)

### 1.1 Concept

Program kokurikuler = kegiatan yang terintegrasi dengan kurikulum inti (contoh: projek P5, bakti sosial, class meeting).

### 1.2 Feature Flag

Modul ini dikontrol oleh feature flag: `ialos_phase7_cocurricular`

### 1.3 Workflow

```
draft → active → completed
```

### 1.4 Features

| Aksi | Deskripsi |
|---|---|
| **Create** | Buat program kokurikuler baru |
| **View** | Detail program |
| **Edit** | Edit program |
| **Transition** | Status transitions |
| **Delete** | Hapus program |
| **Store Session** | Tambah sesi kegiatan |
| **Update Session** | Edit sesi |
| **Delete Session** | Hapus sesi |
| **Execute Session** | Eksekusi sesi (mulai) |
| **Cancel Session** | Batalkan sesi |
| **Store Observation** | Catat observasi |
| **Update Observation** | Edit observasi |
| **Delete Observation** | Hapus observasi |
| **Store Evidence** | Upload bukti |
| **Download Evidence** | Download bukti |
| **Delete Evidence** | Hapus bukti |
| **Save Results** | Simpan hasil penilaian |
| **Store Evaluation** | Tambah evaluasi |
| **Update Evaluation** | Edit evaluasi |
| **Delete Evaluation** | Hapus evaluasi |
| **Report** | Laporan program |
| **Student Narrative** | Narasi per siswa |
| **Rubric Descriptors** | Deskripsi rubric |

### 1.5 7KAIH (Optional)

Feature flag: `ialos_7kahi` (membutuhkan `ialos_phase7_cocurricular` aktif)

7KAIH = 7 Kebiasaan Aktivitas Inti Holistik

| Fitur | Route | Deskripsi |
|---|---|---|
| **Habits** | `GET /cocurricular/habits` | Daftar kebiasaan |
| **Store Habit** | `POST /cocurricular/habits` | Tambah kebiasaan |
| **Update Habit** | `POST /cocurricular/habits/:id/update` | Edit kebiasaan |
| **Delete Habit** | `POST /cocurricular/habits/:id/delete` | Hapus kebiasaan |
| **Check-ins** | `GET /cocurricular/checkins` | Daftar check-in |
| **Save Checkins** | `POST /cocurricular/checkins/save` | Simpan check-in |

### 1.6 Tables

| Table | Fungsi |
|---|---|
| `cocurricular_programs` | Program |
| `cocurricular_sessions` | Sesi |
| `cocurricular_observations` | Observasi |
| `cocurricular_evidences` | Bukti |
| `cocurricular_evaluations` | Evaluasi |
| `cocurricular_habits` | Kebiasaan 7KAIH |
| `cocurricular_checkins` | Check-in kebiasaan |

---

## 2. Extracurricular Programs (`/extracurricular`)

### 2.1 Concept

Program ekstrakurikuler = kegiatan di luar kurikulum inti (contoh: OSIS, pramuka, futsal, English club).

### 2.2 Workflow

```
draft → active → completed
```

### 2.3 Features

| Aksi | Deskripsi |
|---|---|
| **Create** | Buat program ekstrakurikuler baru |
| **View** | Detail program |
| **Edit** | Edit program |
| **Transition** | Status transitions |
| **Delete** | Hapus program |
| **Members** | Kelola anggota |
| **Add Member** | Tambah anggota |
| **Remove Member** | Hapus anggota |
| **Sessions** | Kelola sesi |
| **Create Session** | Tambah sesi |
| **Delete Session** | Hapus sesi |
| **Attendance** | Presensi per sesi |
| **Save Attendance** | Simpan presensi |
| **Competencies** | Kompetensi yang dilatih |
| **Add Competency** | Tambah kompetensi |
| **Delete Competency** | Hapus kompetensi |
| **Add Achievement** | Tambah prestasi |
| **Evaluations** | Evaluasi program |
| **Save Evaluation** | Simpan evaluasi |
| **Reports** | Laporan program |
| **Student Report** | Laporan per siswa |
| **Student Narrative** | Narasi per siswa |
| **Certificate** | Sertifikat prestasi |
| **Student Summary** | Ringkasan per siswa |

### 2.4 Reports

| Report | Route | Deskripsi |
|---|---|---|
| **Program Reports** | `GET /extracurricular/:id/reports` | Laporan keseluruhan |
| **Student Report** | `GET /extracurricular/:id/report/:sid` | Laporan per siswa |
| **Student Narrative** | `GET /extracurricular/:id/student-narrative/:sid` | Narasi per siswa |
| **Certificate** | `GET /extracurricular/:id/certificate/:sid` | Sertifikat |
| **Student Summary** | `GET /extracurricular/student/:sid/summary` | Ringkasan siswa |

### 2.5 Tables

| Table | Fungsi |
|---|---|
| `extracurricular_programs` | Program |
| `extracurricular_members` | Anggota |
| `extracurricular_sessions` | Sesi |
| `extracurricular_attendance` | Presensi per sesi |
| `extracurricular_competencies` | Kompetensi yang dilatih |
| `extracurricular_evaluations` | Evaluasi program (INPUT/PROCESS/OUTPUT/OUTCOME) |
| `extracurricular_achievements` | Prestasi/prestasi anggota (linked ke competencies) |

---

## 3. Services

| Service | Fungsi |
|---|---|
| `CocurricularService` | CRUD kokurikuler, sesi, observasi, evaluasi |
| `ExtracurricularService` | CRUD ekstrakurikuler, anggota, sesi, kompetensi, evaluasi |
