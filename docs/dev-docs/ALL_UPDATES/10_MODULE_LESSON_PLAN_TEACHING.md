# WMVAA Akademia — Lesson Plans & Teaching Workspace

## 1. Lesson Plans / Rencana Pembelajaran (`/lesson-plans`)

### 1.1 Concept

Rencana Pembelajaran (RPP) menggabungkan desain pembelajaran, tahapan, aktivitas, dan asesmen untuk satu sesi mengajar.

### 1.2 Workflow

```
draft → validated → reviewed → approved → locked
```

### 1.3 Components

| Component | Deskripsi |
|---|---|
| **Overview** | Ringkasan rencana |
| **Design** | Desain pembelajaran (catatan, pendekatan) |
| **Objectives** | Tujuan pembelajaran yang terhubung |
| **Stages** | Tahapan pembelajaran (pendahuluan, inti, penutup) |
| **Activities** | Aktivitas per tahapan |
| **Assessments** | Asesmen terkait |
| **Rubrics** | Rubric penilaian |
| **Resources** | Materi pendukung |

### 1.4 Features

| Aksi | Deskripsi |
|---|---|
| **Create** | Buat RPP baru |
| **View** | Detail RPP |
| **Design** | Edit desain |
| **Add Objective** | Tambah tujuan pembelajaran |
| **Add Stage** | Tambah tahapan |
| **Add Activity** | Tambah aktivitas |
| **Add Assessment** | Tambah asesmen |
| **Add Rubric** | Tambah rubric |
| **Link Resource** | Tautkan resource ke aktivitas |
| **Update Activity** | Edit aktivitas |
| **Delete Rubric** | Hapus rubric |
| **Delete Resource** | Hapus resource |
| **Clone** | Salin RPP |
| **Transition** | Workflow transitions |
| **Validate** | Validasi RPP |
| **Print** | Cetak RPP |
| **Export DOCX** | Export ke Word |
| **Export PDF** | Export ke PDF |

### 1.5 Tables

| Table | Fungsi |
|---|---|
| `lesson_plans` | Rencana pembelajaran utama |
| Related objectives, stages, activities, assessments, rubrics | Komponen RPP |

### 1.6 Export Formats

| Format | Method | Library |
|---|---|---|
| **PDF** | `LessonPlanPdfService` | dompdf |
| **DOCX** | `LessonPlanDocxService` | phpword |
| **Print** | HTML print layout | Browser print |

---

## 2. Daily Teaching Workspace (`/teaching`)

### 2.1 Concept

Ruang mengajar harian = workspace real-time untuk guru selama proses pembelajaran berlangsung.

### 2.2 Today's View (`/teaching/today`)

Menampilkan jadwal mengajar hari ini untuk guru yang login.

| Widget | Deskripsi |
|---|---|
| **Today's Schedule** | Daftar jadwal mengajar hari ini |
| **Session Cards** | Card untuk setiap sesi mengajar |
| **Quick Actions** | Mulai sesi, absensi, observasi |

### 2.3 Teaching Session

#### Session Lifecycle

```
init → started → (activities in progress) → completed → reflected
```

#### Features

| Aksi | Route | Deskripsi |
|---|---|---|
| **Init Session** | `POST /teaching/session/init` | Inisialisasi sesi baru |
| **View Session** | `GET /teaching/session/:id` | Detail sesi |
| **Start Session** | `POST /teaching/session/:id/start` | Mulai sesi |
| **Complete Session** | `POST /teaching/session/:id/complete` | Selesaikan sesi |
| **Toggle Activity** | `POST /teaching/session/:id/activities/toggle` | Toggle status aktivitas |
| **Add Observation** | `POST /teaching/session/:id/observations` | Catat observasi |
| **Delete Observation** | `POST /teaching/session/:id/observations/:oid/delete` | Hapus observasi |
| **Quick Attendance** | `POST /teaching/session/:id/attendance/quick` | Presensi cepat |
| **Link Plan** | `POST /teaching/session/:id/link-plan` | Tautkan RPP |

#### Observation

Catatan observasi selama sesi mengajar berlangsung (contoh: siswa antusias, ada masalah perilaku, dll).

#### Reflection

Refleksi setelah sesi mengajar selesai:

| Aksi | Route | Deskripsi |
|---|---|---|
| **View Reflection** | `GET /teaching/session/:id/reflect` | Form refleksi |
| **Save Reflection** | `POST /teaching/session/:id/reflect` | Simpan refleksi |

### 2.4 Attendance System

#### 2.4.1 Quick Attendance

Absensi cepat dari workspace mengajar.

#### 2.4.2 Offline Attendance (`/teaching/attendance/offline`)

Input absensi untuk sesi yang sudah lewat (offline mode).

| Aksi | Route | Deskripsi |
|---|---|---|
| **Form** | `GET /teaching/attendance/offline` | Form input offline |
| **Save** | `POST /teaching/attendance/offline/save` | Simpan absensi offline |

#### 2.4.3 Attendance History (`/teaching/attendance/history`)

Riwayat semua sesi absensi guru.

#### 2.4.4 Attendance Recap (`/teaching/attendance/recap`)

Rekapitulasi kehadiran siswa.

#### 2.4.5 Edit Attendance

| Aksi | Route | Deskripsi |
|---|---|---|
| **Edit Form** | `GET /teaching/attendance/session/:id/edit` | Form edit |
| **Update** | `POST /teaching/attendance/session/:id/update` | Simpan perubahan |

---

## 3. Teacher Portal

### 3.1 Schedule Portal (`/portal/schedule`)

Jadwal mengajar personal guru (read-only).

| Feature | Deskripsi |
|---|---|
| **View Schedule** | Jadwal per hari/minggu |
| **Filter** | Filter by class, grade |
| **Substitution Info** | Tampilkan substitusi |

### 3.2 Workload Portal (`/portal/workload`)

Beban mengajar personal guru.

| Feature | Deskripsi |
|---|---|
| **View Workload** | JP mingguan, distribusi |
| **Per Class** | breakdown per rombel |

### 3.3 Assignment Document Portal (`/portal/assignment-document`)

SK Pembagian Tugas personal.

| Feature | Deskripsi |
|---|---|
| **Preview** | Pratinjau SK |
| **Print** | Cetak SK |
| **Official Status** | Status keresmian dokumen |

### 3.4 Duty Schedule Portal (`/portal/duty-schedule`)

Jadwal piket personal guru.

### 3.5 Elective Portal (`/portal/electives`)

Daftar mapel pilihan yang diampu guru.

| Feature | Deskripsi |
|---|---|
| **View** | Daftar siswa per mapel |
| **Export** | Export daftar siswa |

### 3.6 Homeroom Portal (`/portal/classroom`)

Portal wali kelas untuk kelas binaan.

| Feature | Deskripsi |
|---|---|
| **View Students** | Daftar siswa di kelas |
| **Class Info** | Informasi rombel |

---

## 4. Teacher Subject Attendance Portal (`/portal/attendance`)

### 4.1 Features

| Aksi | Route | Deskripsi |
|---|---|---|
| **Index** | `GET /portal/attendance` | Daftar sesi absensi |
| **Form** | `GET /portal/attendance/record` | Form input baru |
| **Edit** | `GET /portal/attendance/session/:id` | Edit sesi |
| **Save** | `POST /portal/attendance/save` | Simpan absensi |
| **Delete** | `POST /portal/attendance/session/:id/delete` | Hapus sesi |
| **Print Journal** | `GET /portal/attendance/session/:id/print` | Cetak jurnal |
| **Print Recap** | `GET /portal/attendance/recap/print` | Cetak rekap |

---

## 5. Services

| Service | Fungsi |
|---|---|
| `LessonPlanService` | CRUD RPP |
| `LessonPlanDocxService` | Export ke Word |
| `LessonPlanPdfService` | Export ke PDF |
| `TeachingWorkspaceService` | Workspace harian |
| `AttendanceService` | Manajemen absensi |
| `TeacherScheduleReadService` | Baca jadwal guru |
| `TeacherPortalController` | Portal personal |
| `TeacherElectivePortalService` | Portal elektif |
| `HomeroomPortalController` | Portal wali kelas |
